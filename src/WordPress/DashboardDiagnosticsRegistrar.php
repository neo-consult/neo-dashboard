<?php

declare(strict_types=1);

namespace NeoDashboard\Core\WordPress;

use NeoDashboard\Core\LifecycleLogger;

final readonly class DashboardDiagnosticsRegistrar
{
    public function __construct(
        private LifecycleLogger $lifecycleLogger,
        private bool $enabled,
    ) {}

    public function registerHooks(): void
    {
        if ($this->enabled) {
            $this->lifecycleLogger->registerHooks();
        }
    }
}
