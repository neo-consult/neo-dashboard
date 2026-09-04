<?php

declare(strict_types=1);

namespace NeoDashboard\Core\WordPress;

use NeoDashboard\Core\Routing\DashboardRouteRegistrar;

final class NativeDashboardPageTitleEnvironment implements DashboardPageTitleEnvironment
{
    public function section(): string
    {
        return (string) get_query_var(DashboardRouteRegistrar::QUERY_VAR_SECTION, '');
    }

    public function siteName(): string
    {
        return (string) get_bloginfo('name');
    }

    public function dashboardLabel(): string
    {
        return __('Dashboard', 'neo-dashboard-core');
    }

    public function notFoundLabel(): string
    {
        return __('Bereich nicht gefunden', 'neo-dashboard-core');
    }
}
