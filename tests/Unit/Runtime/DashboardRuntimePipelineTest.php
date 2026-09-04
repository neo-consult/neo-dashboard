<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Runtime;

use NeoDashboard\Core\Runtime\DashboardRuntimeComponents;
use NeoDashboard\Core\Runtime\DashboardRuntimePipeline;
use NeoDashboard\Core\Runtime\HookBus;
use NeoDashboard\Core\Runtime\ManagerRegistration;
use NeoDashboard\Core\PerformanceTimer;
use NeoDashboard\Core\Logger;
use PHPUnit\Framework\TestCase;

final class DashboardRuntimePipelineTest extends TestCase
{
    public function testWebRuntimePreparesRegistersDispatchesAndLoadsInOrder(): void
    {
        $events = [];
        $components = new FakeRuntimeComponents($events);
        $hooks = new FakeHookBus($events);
        $pipeline = new DashboardRuntimePipeline($components, $hooks, new PerformanceTimer(new Logger()));

        $pipeline->run('WEB');

        self::assertSame([
            'prepare-assets',
            'register:neo_dashboard_pre_init:5',
            'register:neo_dashboard_pre_init:10',
            'dispatch:neo_dashboard_pre_init',
            'manager:sections',
            'manager:sidebar',
            'dispatch:neo_dashboard_init',
            'load-widgets',
        ], $events);
    }

    /** @dataProvider minimalRequestTypes */
    public function testNonWebRuntimeSkipsDashboardComponents(string $requestType): void
    {
        $events = [];
        (new DashboardRuntimePipeline(
            new FakeRuntimeComponents($events),
            new FakeHookBus($events),
            new PerformanceTimer(new Logger()),
        ))->run($requestType);

        self::assertSame([], $events);
    }

    public function testItRegistersGlobalRuntimeHooksExplicitly(): void
    {
        $events = [];
        (new DashboardRuntimePipeline(
            new FakeRuntimeComponents($events),
            new FakeHookBus($events),
            new PerformanceTimer(new Logger()),
        ))->registerHooks();

        self::assertSame(['register-hooks'], $events);
    }

    public function minimalRequestTypes(): iterable
    {
        foreach (['AJAX', 'REST', 'CRON', 'CLI', 'ADMIN'] as $type) {
            yield $type => [$type];
        }
    }
}

final class FakeRuntimeComponents implements DashboardRuntimeComponents
{
    public function __construct(private array &$events) {}

    public function registerHooks(): void { $this->events[] = 'register-hooks'; }
    public function prepareAssets(): void { $this->events[] = 'prepare-assets'; }
    public function managerRegistrations(): array
    {
        return [
            new ManagerRegistration(fn() => $this->events[] = 'manager:sections', 5),
            new ManagerRegistration(fn() => $this->events[] = 'manager:sidebar', 10),
        ];
    }
    public function loadWidgetDefinitions(): void { $this->events[] = 'load-widgets'; }
}

final class FakeHookBus implements HookBus
{
    /** @var array<string, array<int, callable>> */
    private array $callbacks = [];

    public function __construct(private array &$events) {}

    public function addAction(string $hook, callable $callback, int $priority): void
    {
        $this->events[] = "register:{$hook}:{$priority}";
        $this->callbacks[$hook][$priority] = $callback;
    }

    public function dispatch(string $hook): void
    {
        $this->events[] = "dispatch:{$hook}";
        if (!isset($this->callbacks[$hook])) {
            return;
        }
        ksort($this->callbacks[$hook]);
        foreach ($this->callbacks[$hook] as $callback) {
            $callback();
        }
    }
}
