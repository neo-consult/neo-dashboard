<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Runtime;

interface HookBus
{
    public function addAction(string $hook, callable $callback, int $priority): void;
    public function dispatch(string $hook): void;
}
