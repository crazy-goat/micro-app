<?php

declare(strict_types=1);

namespace CrazyGoat\MicroApp;

class EventDispatcher
{
    /** @var array<string, callable[]> $listeners */
    private array $listeners = [];

    public function on(string $event, callable $callback): void
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }

        $this->listeners[$event][] = $callback;
    }

    /** @param mixed[] ...$args */
    public function dispatch(string $event, ...$args): void
    {
        if (!isset($this->listeners[$event])) {
            return;
        }

        foreach ($this->listeners[$event] as $callback) {
            call_user_func_array($callback, $args);
        }
    }
}
