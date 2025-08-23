#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use CrazyGoat\MicroApp\Attributes\Route;
use CrazyGoat\MicroApp\MicroApp;
use CrazyGoat\MicroApp\Middlewares\MiddlewareInterface;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

class MetricsMiddleware implements MiddlewareInterface
{
    private ?\GlobalData\Client $metrics = null;

    public function __invoke(Request $request, callable $next): Response
    {
        if ($this->metrics === null) {
            return $next($request);
        }
        $start = microtime(true);
        if ($request->method() === 'GET' && $request->uri() === '/metrics') {
            $response = new Response(200, [
                'Content-Type' => 'text/plain'
            ], sprintf(
                "active_connections %d\n" .
                "total_connections %d\n" .
                "total_requests %d\n" .
                "total_response_time %f\n",
                $this->metrics->active_connections,
                $this->metrics->total_connections,
                $this->metrics->total_requests,
                $this->metrics->total_response_time,
            ));
        } else {
            /** @var Response $response */
            $response = $next($request);
        }
        $end = microtime(true);

        $this->metrics->increment('total_requests');
        $this->metrics->increment('total_response_time', $end - $start);

        return $response;
    }

    public function startMetrics(): self
    {
        $this->metrics = new \GlobalData\Client('127.0.0.1:2207');
        $this->metrics->add('total_requests', 0);
        $this->metrics->add('total_response_time', 0.0);
        $this->metrics->add('active_connections', 0);
        $this->metrics->add('total_connections', 0);

        return $this;
    }

    public function connection(): void
    {
        $this->metrics->increment('active_connections');
        $this->metrics->increment('total_connections');;
    }

    public function connectionClose(): void
    {
        $this->metrics->increment('active_connections', -1);
    }

}

class HelloWorldController
{
    #[Route]
    public function handle(Request $request): Response
    {
        return new Response(body: 'Hello World');
    }
}

$metrics = new MetricsMiddleware();

(new MicroApp())
        ->withController(new HelloWorldController())
        ->withMiddleware($metrics, 0)
        ->onEvent('onServerStart', fn() => new GlobalData\Server('0.0.0.0', 2207))
        ->onEvent('onWorkerStart', fn() => $metrics->startMetrics())
        ->onEvent('onConnect', fn() => $metrics->connection())
        ->onEvent('onClose', fn() => $metrics->connectionClose())
        ->getApplication()
        ->run();