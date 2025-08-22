<?php

declare(strict_types=1);

namespace CrazyGoat\MicroApp\Middlewares;

use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

interface MiddlewareInterface
{
    public function __invoke(Request $request, callable $next): Response;
}
