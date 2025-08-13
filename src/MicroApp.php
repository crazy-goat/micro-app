<?php

declare(strict_types=1);

namespace CrazyGoat\MicroApp;

use CrazyGoat\MicroApp\Attributes\Route;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

use function FastRoute\simpleDispatcher;

use ReflectionClass;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Worker;

#[AsCommand(name: 'server', description: 'Hello World application')]
class MicroApp extends Command
{
    private const DEFAULT_LISTEN = '0.0.0.0';
    private const DEFAULT_PORT = 8080;
    private const DEFAULT_WORKER_COUNT = 4;

    /** @var object[] */
    protected array $controllers = [];
    private Dispatcher $dispatcher;
    private bool $dev = false;
    private ?int $maxRequest = null;
    private bool $needReload = false;
    private bool $reloadOnException = false;

    public function withController(object $controller): self
    {
        $this->controllers[] = $controller;

        return $this;
    }

    public function getApplication(): ?Application
    {
        if (!parent::getApplication() instanceof Application) {
            $app = new Application();
            $app->add($this);
            $this->setApplication($app);
        }

        return parent::getApplication();
    }

    protected function configure(): void
    {
        $this->addArgument('server_command', InputArgument::REQUIRED, 'The command to execute [connections, start, status, stop, restart, reload]');

        $this->addOption('port', 'p', InputOption::VALUE_REQUIRED, 'Port to listen', self::DEFAULT_PORT);
        $this->addOption('reuse_port', 'R', InputOption::VALUE_NONE, 'Use SO_REUSEPORT if available');
        $this->addOption('listen', 'l', InputOption::VALUE_REQUIRED, 'Listen to listen', self::DEFAULT_LISTEN);
        $this->addOption('workers', 'w', InputOption::VALUE_REQUIRED, 'Number of workers to run', self::DEFAULT_WORKER_COUNT);

        $this->addOption('dev', 'd', InputOption::VALUE_NONE, 'Restart every request');
        $this->addOption('max-request', 'm', InputOption::VALUE_REQUIRED, 'Restart every N request');
        $this->addOption('reload-on-exception', 'r', InputOption::VALUE_NONE, 'Restart on exception');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->serve($input, $output);
    }

    protected function serve(InputInterface $input, OutputInterface $output): int
    {
        $this->dispatcher = $this->getDispatcher();
        $worker = new Worker(
            sprintf("http://%s:%d", $input->getOption('listen'), $input->getOption('port')),
        );

        $this->dev = boolval($input->getOption('dev'));
        $this->maxRequest = $input->getOption('max-request') === null ? null : intval($input->getOption('max-request'));
        $this->reloadOnException = boolval($input->getOption('reload-on-exception'));

        $worker->name = $this->getName() ?? 'MicroApp';
        $worker->count = intval($input->getOption('workers'));
        $worker->onMessage = $this->onMessage(...);
        $worker->reusePort = boolval($input->getOption('reuse_port'));

        Worker::$command = $input->getArgument('server_command');
        Worker::runAll();

        return self::SUCCESS;
    }

    private function getDispatcher(): Dispatcher
    {
        return simpleDispatcher(function (RouteCollector $r): void {
            foreach ($this->controllers as $controller) {
                $class = new ReflectionClass($controller);
                foreach ($class->getMethods() as $method) {
                    $attrs = $method->getAttributes(Route::class);
                    foreach ($attrs as $attr) {
                        $instance = $attr->newInstance();
                        $r->addRoute($instance->methods, $instance->pattern, [$controller, $method->getName()]);
                    }
                }
            }
        });
    }

    final public function onMessage(TcpConnection $connection, Request $request): void
    {
        $response = new Response();
        $routeInfo = $this->dispatcher->dispatch($request->method(), $request->uri());
        [$matchResult, $handler, $vars] = $routeInfo;
        switch ($matchResult) {
            case Dispatcher::NOT_FOUND:
                $response->withStatus(404)->withBody(Response::PHRASES[404]);
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                $response->withStatus(405)
                    ->withBody(
                        sprintf(
                            "%s Allowed methods: %s",
                            Response::PHRASES[405],
                            implode(', ', $allowedMethods),
                        ),
                    );
                break;
            case Dispatcher::FOUND:
                try {
                    $request->context['arguments'] = $vars;
                    if (!is_callable($handler)) {
                        throw new \RuntimeException('Handler is not callable');
                    }
                    $response = call_user_func_array($handler, [$request]);
                } catch (\Throwable $exception) {
                    $response->withStatus(500)->withBody($this->dev ? $exception->getMessage() : Response::PHRASES[500]);
                    if ($this->reloadOnException) {
                        $this->needReload = true;
                    } else {
                        throw $exception;
                    }
                }
                break;
        }
        $connection->send($response);

        if ($this->needReload()) {
            $this->reload();
        }
    }

    private function reload(bool $all = false): void
    {
        Worker::log('Reloading '.($all ? 'all workers' : 'single worker'));
        posix_kill($all ? posix_getppid() : posix_getpid(), SIGUSR2);
    }

    private function needReload(): bool
    {
        if ($this->dev || $this->needReload) {
            return true;
        }

        if ($this->maxRequest !== null) {
            $this->maxRequest--;

            if ($this->maxRequest <= 0) {
                return true;
            }
        }

        return false;
    }
}
