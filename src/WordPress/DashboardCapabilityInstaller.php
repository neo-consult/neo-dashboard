<?php

declare(strict_types=1);

namespace NeoDashboard\Core\WordPress;

final class DashboardCapabilityInstaller
{
    public function install(): void
    {
        foreach (['neo_editor', 'neo_mitarbeiter'] as $roleName) {
            $role = get_role($roleName);
            if ($role !== null) {
                $role->add_cap('read');
                $role->add_cap('neo_dashboard_access');
            }
        }
    }
}
