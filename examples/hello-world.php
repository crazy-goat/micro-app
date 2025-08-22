#!/usr/bin/env php
<?php

require __DIR__.'/../vendor/autoload.php';

use CrazyGoat\MicroApp\Attributes\Route;
use CrazyGoat\MicroApp\MicroApp;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

class HelloWorldController
{
    #[Route]
    public function handle(Request $request): Response
    {
        return new Response(body:'Hello World');
    }

    #[Route(pattern: '/hello/{name}')]
    public function hello(Request $request): Response
    {
        return new Response(body:'Hello '.$request->context['router']['arguments']['name']);
    }
}

(new MicroApp())
        ->withController(new HelloWorldController())
        ->getApplication()
        ->run();