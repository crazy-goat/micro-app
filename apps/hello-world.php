#!/usr/bin/env php
<?php

namespace Apps;

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
}

(new MicroApp())
        ->withController(new HelloWorldController())
        ->getApplication()
        ->run();