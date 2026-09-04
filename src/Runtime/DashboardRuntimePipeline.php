<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Runtime;

use NeoDashboard\Core\PerformanceTimer;

final class DashboardRuntimePipeline
{
    private const MINIMAL_REQUESTS = ['AJAX', 'REST', 'CRON', 'CLI', 'ADMIN'];

    public function __construct(
        private readonly DashboardRuntimeComponents $components,
        private readonly HookBus $hooks,
        private readonly PerformanceTimer $performance,
    ) {}

    public function registerManagers(): void
    {
        $this->performance->start('dashboard', 'register_managers');
        $this->components->prepareAssets();

        foreach ($this->components->managerRegistrations() as $registration) {
            $this->hooks->addAction(
                'neo_dashboard_pre_init',
                $registration->callback(),
                $registration->priority(),
            );
        }

        $this->performance->stop('dashboard', 'register_managers');
    }

    public function registerHooks(): void
    {
        $this->components->registerHooks();
    }

    public function run(string $requestType): void
    {
        $this->performance->start('dashboard', 'run');
        if (in_array($requestType, self::MINIMAL_REQUESTS, true)) {
            $this->performance->stop('dashboard', 'run');
            return;
        }

        $this->registerManagers();
        $this->dispatchTimed('neo_dashboard_pre_init', 'hooks_pre_init');
        $this->dispatchTimed('neo_dashboard_init', 'hooks_init');

        $this->performance->start('dashboard', 'widgets');
        $this->components->loadWidgetDefinitions();
        $this->performance->stop('dashboard', 'widgets');
        $this->performance->stop('dashboard', 'run');
    }

    private function dispatchTimed(string $hook, string $timer): void
    {
        $this->performance->start('dashboard', $timer);
        $this->hooks->dispatch($hook);
        $this->performance->stop('dashboard', $timer);
    }
}
