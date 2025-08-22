<?php

declare(strict_types=1);

namespace CrazyGoat\MicroApp\Middlewares;

use CrazyGoat\MicroApp\Exceptions\MethodNotAllowedException;
use CrazyGoat\MicroApp\Exceptions\RouteNoteFoundException;
use FastRoute\Dispatcher;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

class RouterMiddleware implements MiddlewareInterface
{
    private ?Dispatcher $dispatcher = null;

    public function withDispatcher(Dispatcher $dispatcher): self
    {
        $this->dispatcher = $dispatcher;
        return $this;
    }

    public function __invoke(Request $request, callable $next): Response
    {
        if (!$this->dispatcher instanceof \FastRoute\Dispatcher) {
            return $next($request);
        }

        $routeInfo = $this->dispatcher->dispatch($request->method(), $request->uri());
        switch ($routeInfo[0] ?? null) {
            default:
            case Dispatcher::NOT_FOUND:
                throw new RouteNoteFoundException();
            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new MethodNotAllowedException(sprintf("%s Allowed methods: %s", Response::PHRASES[405], implode(', ', $routeInfo[1])));
            case Dispatcher::FOUND:
                $handler = $routeInfo[1] ?? null;
                $vars = $routeInfo[2] ?? [];
                $request->context['router']['arguments'] = $vars;
                $request->context['router']['handler'] = $handler;
                $response = $next($request);
                break;
        }

        return $response;
    }
}
