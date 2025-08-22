<?php

declare(strict_types=1);

namespace CrazyGoat\MicroApp\Exceptions;

class MethodNotAllowedException extends HttpException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(405, $message);
    }
}
