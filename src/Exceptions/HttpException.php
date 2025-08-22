<?php

declare(strict_types=1);

namespace CrazyGoat\MicroApp\Exceptions;

use Workerman\Protocols\Http\Response;

class HttpException extends \Exception
{
    public function __construct(int $statusCode, ?string $message = null)
    {
        parent::__construct($message ?? Response::PHRASES[$statusCode], $statusCode);
    }
}
