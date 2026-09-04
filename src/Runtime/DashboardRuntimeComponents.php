<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Runtime;

interface DashboardRuntimeComponents
{
    public function registerHooks(): void;

    public function prepareAssets(): void;

    /** @return list<ManagerRegistration> */
    public function managerRegistrations(): array;

    public function loadWidgetDefinitions(): void;
}
