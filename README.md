# MicroApp PHP Framework

MicroApp is a minimalist PHP micro-backend framework built to run on top of the fast [Workerman](https://github.com/walkor/Workerman) HTTP server. It enables you to quickly create lightweight micro-backends using Symfony commands and attribute-based routing.

## Features

- Minimal setup, rapid development for micro-backends
- Runs on Workerman for high-performance HTTP serving
- Attribute-based routes using PHP 8+ attributes
- Symfony Console integration for easy command management
- Configurable port, interface, and worker count

## Getting Started

### Requirements

- PHP 8.0+
- [Workerman](https://github.com/walkor/Workerman)
- Symfony Console

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
php myapp.php server:start --listen=127.0.0.1 --port=8081
```

## Configuration Options

| Option         | Description                          | Default   |
|----------------|--------------------------------------|-----------|
| `--port`       | Port to listen on                    | `8080`    |
| `--listen`     | Interface/address to bind            | `0.0.0.0` |
| `--workers`    | Number of PHP worker processes       | `4`       |
| `--reuse_port` | Use SO_REUSEPORT if available        | `false`   |

## License

MIT

## Credits

- [Workerman](https://github.com/walkor/Workerman)
- [Symfony Console](https://symfony.com/doc/current/components/console.html)
