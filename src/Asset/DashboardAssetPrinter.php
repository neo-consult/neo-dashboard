<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Asset;

use NeoDashboard\Core\Manager\FaviconManager;

final class DashboardAssetPrinter
{
    public function __construct(
        private readonly FaviconManager $favicon,
        private readonly PluginAssetEnqueuer $plugins,
    ) {}

    public function print(string $type, string $context, string $section, ?string $pluginPrefix): void
    {
        if ($type === 'css') {
            $this->favicon->printLinks();
        }
        if ($pluginPrefix !== null) {
            do_action("neo_dashboard_enqueue_{$pluginPrefix}_assets_{$type}", $context);
        }
        $this->plugins->enqueue($context, $type);
        do_action("neo_dashboard_enqueue_page_{$type}", $context, $section);

        $function = $type === 'css' ? 'wp_print_styles' : 'wp_print_scripts';
        if (function_exists($function)) {
            $function();
        }
    }
}
