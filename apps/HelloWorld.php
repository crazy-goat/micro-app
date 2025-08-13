<?php

namespace Apps;

use CrazyGoat\MicroApp\Attributes\Route;
use CrazyGoat\MicroApp\MicroApp;
use Symfony\Component\Console\Attribute\AsCommand;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

#[AsCommand(name: 'server:hello-world', description: 'Hello World application')]
class HelloWorld extends MicroApp
{
    #[Route]
    protected function helloWorld(Request $request): Response {
        return new Response(body:'Hello World');
    }
}