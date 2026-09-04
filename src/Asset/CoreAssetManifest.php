<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Asset;

final class CoreAssetManifest
{
    /** @return list<array{path: string, type: 'style'|'script', handle: string, deps: list<string>}> */
    public function localAssets(): array
    {
        return [
            ['path' => 'assets/dashboard.css', 'type' => 'style', 'handle' => 'neo-dashboard-core', 'deps' => ['neo-dashboard-bootstrap']],
            ['path' => 'assets/widgets.css', 'type' => 'style', 'handle' => 'neo-dashboard-widgets', 'deps' => ['neo-dashboard-core']],
            ['path' => 'assets/sections.css', 'type' => 'style', 'handle' => 'neo-dashboard-sections', 'deps' => ['neo-dashboard-core']],
            ['path' => 'assets/js/dashboard.js', 'type' => 'script', 'handle' => 'neo-dashboard-core', 'deps' => ['neo-dashboard-bootstrap']],
            ['path' => 'assets/js/notifications.js', 'type' => 'script', 'handle' => 'neo-dashboard-notifications', 'deps' => ['neo-dashboard-core']],
            ['path' => 'assets/js/confirm.js', 'type' => 'script', 'handle' => 'neo-dashboard-confirm', 'deps' => ['neo-dashboard-bootstrap']],
            ['path' => 'assets/js/toast.js', 'type' => 'script', 'handle' => 'neo-dashboard-toast', 'deps' => ['neo-dashboard-bootstrap']],
            ['path' => 'assets/js/loading.js', 'type' => 'script', 'handle' => 'neo-dashboard-loading', 'deps' => ['neo-dashboard-bootstrap', 'neo-dashboard-core']],
            ['path' => 'assets/js/navbar.js', 'type' => 'script', 'handle' => 'neo-dashboard-navbar', 'deps' => ['neo-dashboard-bootstrap']],
            ['path' => 'assets/js/language-switcher.js', 'type' => 'script', 'handle' => 'neo-dashboard-language-switcher', 'deps' => ['neo-dashboard-bootstrap', 'neo-dashboard-core']],
            ['path' => 'assets/theme-switcher.css', 'type' => 'style', 'handle' => 'neo-dashboard-theme-switcher', 'deps' => ['neo-dashboard-core']],
            ['path' => 'assets/js/theme-switcher.js', 'type' => 'script', 'handle' => 'neo-dashboard-theme-switcher', 'deps' => ['neo-dashboard-core']],
        ];
    }
}
