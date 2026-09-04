<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Runtime;

final class WordPressHookBus implements HookBus
{
    public function addAction(string $hook, callable $callback, int $priority): void
    {
        add_action($hook, $callback, $priority);
    }

    public function dispatch(string $hook): void
    {
        do_action($hook);
    }
}
