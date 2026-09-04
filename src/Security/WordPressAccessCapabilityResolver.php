<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Security;

final class WordPressAccessCapabilityResolver implements AccessCapabilityResolver
{
    public function canAccessDashboard(): bool
    {
        return function_exists('current_user_can')
            && current_user_can('neo_dashboard_access');
    }

    public function canAccessAdmin(): bool
    {
        return function_exists('current_user_can')
            && current_user_can('manage_options');
    }
}
