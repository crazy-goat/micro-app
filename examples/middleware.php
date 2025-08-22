#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use CrazyGoat\MicroApp\Attributes\Route;
use CrazyGoat\MicroApp\MicroApp;
use CrazyGoat\MicroApp\Middlewares\MiddlewareInterface;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

class TimerMiddleware implements MiddlewareInterface
{
    public function __invoke(Request $request, callable $next): Response
    {
        $start = microtime(true);
        /** @var Response $response */
        $response = $next($request);
        $end = microtime(true);
        $response->withHeader('X-Response-Time-Ms', sprintf('%.3f', ($end - $start) * 1000.0));
        return $response;
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

(new MicroApp())
        ->withController(new HelloWorldController())
        ->withMiddleware(new TimerMiddleware(), 0)
        ->getApplication()
        ->run();