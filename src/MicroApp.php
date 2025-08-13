<?php

namespace CrazyGoat\MicroApp;

use FastRoute\ConfigureRoutes;
use CrazyGoat\MicroApp\Attributes\Route;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Worker;

use function FastRoute\simpleDispatcher;

class MicroApp extends Command
{
    protected bool $reusePort = true;
    protected int $workerCount = 4;
    protected string $listen = '0.0.0.0';

    protected int $port = 8081;
    private Dispatcher $dispatcher;

    protected function configure(): void
    {
        $this->addOption('port', 'p', InputOption::VALUE_REQUIRED, 'Port to listen', $this->port);
        $this->addOption('reuse_port', 'R', InputOption::VALUE_NONE, 'Use SO_REUSEPORT if available');
        $this->addOption('listen', 'l', InputOption::VALUE_REQUIRED, 'Listen to listen', $this->listen);
        $this->addOption('workers', 'w', InputOption::VALUE_REQUIRED, 'Number of workers to run', $this->workerCount);

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
            sprintf("http://%s:%d", $input->getOption('listen'), $input->getOption('port'))
        );
        $worker->name = $this->getName() ?? 'MicroApp';
        $worker->count = $input->getOption('workers');
        $worker->onMessage = [$this, 'onMessage'];
        $worker->reusePort = boolval($input->getOption('reuse_port'));

        Worker::$command = 'start';
        Worker::runAll();

        return self::SUCCESS;
    }

    private function getDispatcher(): Dispatcher
    {
        return simpleDispatcher(static function (RouteCollector $r) {
            $class = new ReflectionClass(static::class);
            foreach ($class->getMethods() as $method) {
                $attrs = $method->getAttributes(Route::class);
                foreach ($attrs as $attr) {
                    $instance = $attr->newInstance();
                    $r->addRoute($instance->methods, $instance->pattern, $method->getName());
                }
            }
        });
    }

    final public function onMessage(TcpConnection $connection, Request $request): void
    {
        $response = new Response();
        $routeInfo = $this->dispatcher->dispatch($request->method(), $request->uri());
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                $response->withStatus(404)->withBody(Response::PHRASES[404]);
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                $response->withStatus(405)
                    ->withBody(
                        sprintf("%s Allowed methods: %s",
                            Response::PHRASES[405],
                            implode(', ', $allowedMethods))
                    );
                break;
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                $request->context['arguments'] = $vars;
                $response = call_user_func_array([$this, $handler], [$request]);
                break;
        }
        $connection->send($response);
    }
}