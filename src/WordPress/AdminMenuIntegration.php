<?php
declare(strict_types=1);

namespace NeoDashboard\Core\WordPress;

final class AdminMenuIntegration
{
    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_head', [$this, 'renderRedirectScript']);
    }

    public function registerMenu(): void
    {
        add_menu_page(
            'Neo Dashboard',
            'Neo Dashboard',
            'read',
            'neo-dashboard-link',
            '',
            'dashicons-dashboard',
            3,
        );
    }

    public function renderRedirectScript(): void
    {
        $dashboardUrl = wp_json_encode(home_url('/neo-dashboard'));
        echo <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function () {
    const link = document.querySelector('a[href*="neo-dashboard-link"]');
    if (link) {
        link.href = {$dashboardUrl};
        link.target = '_self';
    }
});
</script>
HTML;
    }
}
