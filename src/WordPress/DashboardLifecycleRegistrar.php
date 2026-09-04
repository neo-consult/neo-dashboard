<?php

declare(strict_types=1);

namespace NeoDashboard\Core\WordPress;

use NeoDashboard\Core\Installer;
use NeoDashboard\Core\Roles;
use NeoDashboard\Core\Routing\DashboardRouteRegistrar;

final readonly class DashboardLifecycleRegistrar
{
    public function __construct(
        private string $pluginFile,
        private DashboardRouteRegistrar $routeRegistrar,
        private DashboardCapabilityInstaller $capabilityInstaller,
    ) {}

    public function registerHooks(): void
    {
        // WordPress parses rewrite query variables before the `init` hook.
        // Keep this filter available as soon as the plugin is loaded so nested
        // URLs such as /neo-dashboard/neo-calendar/projects are not discarded.
        add_filter('query_vars', [$this->routeRegistrar, 'addQueryVar']);
        add_action('init', [$this->routeRegistrar, 'synchronizeRewriteRules'], 1);

        register_activation_hook($this->pluginFile, [$this, 'activateRouting']);
        register_activation_hook($this->pluginFile, [Installer::class, 'activate']);
        register_activation_hook($this->pluginFile, [Roles::class, 'addRoles']);
        register_activation_hook($this->pluginFile, [$this->capabilityInstaller, 'install']);
        register_deactivation_hook($this->pluginFile, [$this, 'deactivate']);
    }

    public function activateRouting(): void
    {
        $this->routeRegistrar->synchronizeRewriteRules();
    }

    public function deactivate(): void
    {
        flush_rewrite_rules();
        Roles::removeRoles();
    }
}
