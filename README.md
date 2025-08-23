# MicroApp PHP Framework

MicroApp is a minimalist PHP micro-backend framework built to run on top of the fast [Workerman](https://github.com/walkor/Workerman) HTTP server. It enables you to quickly create lightweight micro-backends using Symfony commands and attribute-based routing.

## Features

- Minimal setup, rapid development for micro-backends
- Runs on Workerman for high-performance HTTP serving
- Attribute-based routes using PHP 8+ attributes
- Symfony Console integration for easy command management
- Configurable port, interface, and worker count
- Event Dispatcher for custom event handling
- Middleware Support for request/response processing

## Getting Started

### Dependencies

- PHP 8.1+
- [Workerman](https://github.com/walkor/Workerman) 
- [Symfony Console](https://symfony.com/doc/current/components/console.html)
- [FastRoute](https://github.com/nikic/FastRoute)

### Installation

Install MicroApp via Composer:

```bash
composer require crazy-goat/micro-app
```

### Creating a Micro-Backend

1. **Create a Symfony Application using `MicroApp`:**

```php 
<?php 
# myapp.php
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
```
- Your controller **must** have at least one function with the `#[Route]` attribute.
- Each route function **must** accept a `Request` and return a `Response`.

2. **Run your application:**

Example:

```bash
php myapp.php server start --listen=127.0.0.1 --port=8081
```

## Configuration Options

| Option                  | Description                                                 | Default   |
|-------------------------|-------------------------------------------------------------|-----------|
| `--port`                | Port to listen on                                           | `8080`    |
| `--listen`              | Interface/address to bind                                   | `0.0.0.0` |
| `--workers`             | Number of PHP worker processes                              | `4`       |
| `--reuse_port`          | Use SO_REUSEPORT if available                               | `false`   |
| `--dev`                 | Reload server every request. Use for development            | `false`   |
| `--max-reques`          | Reload server N request. Use this if you have memory leaks. | `null`    |
| `--reload-on-exception` | If exception appears in code, reload worker.                | `false`   |

## Server commands

- `server start` - Start the server
- `server stop` - Stop the server
- `server restart` - Restart the server
- `server reload` - Reload the server
- `server status` - Show the server status
- `server connections` - Show the server connections

## Event Dispatcher

MicroApp includes a simple Event Dispatcher that allows you to hook into various lifecycle events of the application. 
You can register listeners using the `onEvent()` method on your `MicroApp` instance.

### Available Events

| Event Name        | Parameters                                                            |
|-------------------|-----------------------------------------------------------------------|
| `onServerStart`   | `Worker $worker`                                                      |
| `onMessage`       | `TcpConnection $connection`, `Request $request`                       |
| `onResponse`      | `TcpConnection $connection`, `Request $request`, `Response $response` |
| `onWorkerStart`   | `Worker $worker`                                                      |
| `onConnect`       | `TcpConnection $connection`                                           |
| `onClose`         | `TcpConnection $connection`                                           |
| `onWorkerReload`  | `Worker $worker`                                                      |

### Example

```php
(new MicroApp())
        ->withController(new HelloWorldController())
        ->onEvent('onConnect', function (TcpConnection $connection) {
            echo "Client connected from {" . $connection->getRemoteIp() . ":" . $connection->getRemotePort() . "}\n";
        })
        ->getApplication()
        ->run();
```

## Middlewares

MicroApp supports middleware to process requests before they reach your controller and to modify responses before they are sent. 
Middlewares are classes that implement `CrazyGoat\MicroApp\Middlewares\MiddlewareInterface`.

### Example Middleware

```php
class MySimpleMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        // Pre-process request
        echo "Middleware executed before controller.\n";

        $response = $next($request); // Call the next middleware or controller

        // Post-process response
        echo "Middleware executed after controller.\n";

        return $response;
    }
}

// Registering the middleware
(new MicroApp())
        ->withMiddleware(new MySimpleMiddleware())
        ->withController(new HelloWorldController())
        ->getApplication()
        ->run();
```

## License

MIT

## Credits

- [Workerman](https://github.com/walkor/Workerman)
- [Symfony Console](https://symfony.com/doc/current/components/console.html)
- [FastRoute](https://github.com/nikic/FastRoute)