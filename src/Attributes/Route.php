<?php

namespace CrazyGoat\MicroApp\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        public string|array $methods = 'GET',
        public string $pattern= '/',
    )
    {
    }
}