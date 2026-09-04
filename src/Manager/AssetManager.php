<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Manager;

use NeoDashboard\Core\Asset\AssetCatalog;
use NeoDashboard\Core\Asset\AssetContextTracker;
use NeoDashboard\Core\Asset\CoreAssetEnqueuer;
use NeoDashboard\Core\Asset\DashboardAssetPrinter;
use NeoDashboard\Core\Asset\PluginAssetDefinition;
use NeoDashboard\Core\Logger;
use NeoDashboard\Core\Extension\Registry\SectionRegistry;
use NeoDashboard\Core\Http\DashboardRequest;

/** WordPress hook facade for the dashboard asset subsystem. */
final class AssetManager
{
    private ContextResolver $context;
    private AssetCatalog $catalog;
    private CoreAssetEnqueuer $coreEnqueuer;
    private DashboardAssetPrinter $assetPrinter;
    private SectionRegistry $sections;
    private AssetContextTracker $contextTracker;

    public function __construct(
        ContextResolver $context,
        AssetCatalog $catalog,
        CoreAssetEnqueuer $coreEnqueuer,
        DashboardAssetPrinter $assetPrinter,
        SectionRegistry $sections,
        AssetContextTracker $contextTracker,
        private DashboardRequest $request,
        private Logger $logger,
    ) {
        $this->context = $context;
        $this->catalog = $catalog;
        $this->coreEnqueuer = $coreEnqueuer;
        $this->assetPrinter = $assetPrinter;
        $this->sections = $sections;
        $this->contextTracker = $contextTracker;
    }

    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets'], 5);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets'], 5);
        add_action('wp_enqueue_scripts', [$this, 'dequeueWordPressAssets'], 999);
        add_action('admin_enqueue_scripts', [$this, 'dequeueWordPressAssets'], 999);
        add_action('neo_dashboard_head', [$this, 'printCssAssets'], 5);
        add_action('neo_dashboard_footer', [$this, 'printJsAssets'], 5);
        add_filter('show_admin_bar', [$this, 'maybeHideAdminBar']);
        add_action('neo_dashboard_register_plugin_assets', [$this, 'registerPluginAssets']);
    }

    public function printCssAssets(): void
    {
        $this->printAssets('css');
    }

    public function printJsAssets(): void
    {
        $this->printAssets('js');
    }

    public function dequeueWordPressAssets(): void
    {
        if (!$this->context->isDashboard()) {
            return;
        }
        foreach (['wp-block-library', 'wp-block-library-theme', 'global-styles', 'classic-theme-styles'] as $handle) {
            wp_dequeue_style($handle);
            wp_deregister_style($handle);
        }
    }

    public function enqueueAssets(): void
    {
        if (!$this->context->isDashboard()) {
            return;
        }
        $context = $this->context->current();
        if (!$this->contextTracker->claim($context)) {
            return;
        }
        $this->coreEnqueuer->enqueue();
    }

    public function printAssets(string $type): void
    {
        if (!$this->context->isDashboard() || !in_array($type, ['css', 'js'], true)) {
            return;
        }
        $this->enqueueAssets();
        $this->dequeueWordPressAssets();
        $context = $this->context->current();
        $this->assetPrinter->print(
            $type,
            $context,
            $this->request->section(),
            $this->pluginPrefix($context),
        );
    }

    public function registerPluginAssets(PluginAssetDefinition $definition): void
    {
        $this->catalog->registerPlugin($definition);
    }

    public function maybeHideAdminBar(bool $show): bool
    {
        return $this->context->isDashboard() ? false : $show;
    }

    private function pluginPrefix(string $section): ?string
    {
        $prefix = explode('/', $section)[0];
        if (in_array($prefix, ['neo-dashboard', 'dashboard', 'admin', 'home'], true)) {
            return null;
        }
        $sections = $this->sections->all();
        return isset($sections[$section]) || isset($sections[$prefix]) ? $prefix : null;
    }
}
