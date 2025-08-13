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

1. **Create a Symfony Console Command that extends `MicroApp`:**

```php
namespace Apps;

use CrazyGoat\MicroApp\MicroApp;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use CrazyGoat\MicroApp\Attributes\Route;

#[AsCommand(name: 'my-micro-backend', description: 'Hello World application')]
class MyMicroBackend extends MicroApp
{
    #[Route('GET', '/hello')]
    public function hello(Request $request): Response
    {
        return new Response(200, [], 'Hello, world!');
    }
}
```

- Your command **must** have at least one function with the `#[Route]` attribute.
- Each route function **must** accept a `Request` and return a `Response`.

2. **Create a Symfony Application and register your command:**

```php
#myapp.php
namespace Apps;

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Console\Application;
$application = new Application();
application->add(new MyMicroBackend());
$application->run();
```
3. **Run your application:**

Example:

```bash
php myapp.php my-micro-backend --listen=127.0.0.1 --port=8081
```

## Configuration Options

| Option         | Description                          | Default    |
|----------------|--------------------------------------|------------|
| `--port`       | Port to listen on                    | `8081`     |
| `--listen`     | Interface/address to bind            | `0.0.0.0`  |
| `--workers`    | Number of PHP worker processes       | `4`        |
| `--reuse_port` | Use SO_REUSEPORT if available        | `true`     |

## License

MIT

## Credits

- [Workerman](https://github.com/walkor/Workerman)
- [Symfony Console](https://symfony.com/doc/current/components/console.html)
