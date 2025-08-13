<?php

declare(strict_types=1);

namespace CrazyGoat\MicroApp\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Route
{
    /**
     * @param string|string[] $methods
     */
    public function __construct(
        public string|array $methods = 'GET',
        public string $pattern = '/',
    ) {
    }
}
