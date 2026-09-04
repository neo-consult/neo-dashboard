<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Widget;

use NeoDashboard\Core\Runtime\HookBus;
use NeoDashboard\Core\Widget\WidgetDefinitionLoader;
use PHPUnit\Framework\TestCase;

final class WidgetDefinitionLoaderTest extends TestCase
{
    public function testReceiverAndDefinitionsAreLoadedOnlyOnce(): void
    {
        $hooks = new RecordingHookBus();
        $loader = new WidgetDefinitionLoader($hooks);
        $receiver = static fn(array $definition): string => (string) $definition['id'];

        $loader->registerReceiver($receiver);
        $loader->registerReceiver($receiver);
        $loader->load($receiver);
        $loader->load($receiver);

        self::assertSame([
            'add:neo_dashboard_register_widget:10',
            'dispatch:neo_dashboard_register_widgets',
        ], $hooks->events);
    }

    public function testLoadingFirstAlsoRegistersTheReceiverBeforeDispatch(): void
    {
        $hooks = new RecordingHookBus();
        $loader = new WidgetDefinitionLoader($hooks);

        $loader->load(static fn(): string => 'widget');

        self::assertSame([
            'add:neo_dashboard_register_widget:10',
            'dispatch:neo_dashboard_register_widgets',
        ], $hooks->events);
    }
}

final class RecordingHookBus implements HookBus
{
    /** @var array<int, string> */
    public array $events = [];

    public function addAction(string $hook, callable $callback, int $priority): void
    {
        $this->events[] = "add:{$hook}:{$priority}";
    }

    public function dispatch(string $hook): void
    {
        $this->events[] = "dispatch:{$hook}";
    }
}
