<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Http;

use NeoDashboard\Core\Routing\DashboardRouteRegistrar;
use WP_User;

final class NativeDashboardRequest implements DashboardRequest
{
    public function user(): WP_User
    {
        return wp_get_current_user();
    }

    public function section(): string
    {
        return (string) get_query_var(DashboardRouteRegistrar::QUERY_VAR_SECTION, '');
    }
}
