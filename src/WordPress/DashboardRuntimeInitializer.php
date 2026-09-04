<?php
declare(strict_types=1);

namespace NeoDashboard\Core\WordPress;

use NeoDashboard\Core\Http\RequestTypeProvider;
use NeoDashboard\Core\Manager\RestManager;
use NeoDashboard\Core\Runtime\DashboardRuntimePipeline;
use NeoDashboard\Core\Widget\WidgetAjaxController;
use NeoDashboard\Core\PerformanceTimer;

final class DashboardRuntimeInitializer
{
    private RequestTypeProvider $requestTypeProvider;
    private DashboardRouter $router;
    private DashboardRuntimePipeline $pipeline;
    private RestManager $restManager;
    private WidgetAjaxController $widgetController;

    public function __construct(
        RequestTypeProvider $requestTypeProvider,
        DashboardRouter $router,
        DashboardRuntimePipeline $pipeline,
        RestManager $restManager,
        WidgetAjaxController $widgetController,
        private PerformanceTimer $performance,
    ) {
        $this->requestTypeProvider = $requestTypeProvider;
        $this->router = $router;
        $this->pipeline = $pipeline;
        $this->restManager = $restManager;
        $this->widgetController = $widgetController;
    }

    public function registerHooks(): void
    {
        $this->pipeline->registerHooks();
        add_action('plugins_loaded', [$this, 'initializeRest'], 1);
        add_action('init', [$this, 'initialize']);
        add_action('init', [$this, 'registerWidgetAjax'], 1);
    }

    public function initializeRest(): void
    {
        $this->restManager->registerHooks();
    }

    public function registerWidgetAjax(): void
    {
        add_action('wp_ajax_neo_dashboard_widget', [
            $this->widgetController,
            'handle',
        ]);
    }

    public function initialize(): void
    {
        $this->performance->start('bootstrap', 'init');
        $requestType = $this->requestTypeProvider->type();

        if ($requestType === 'WEB') {
            $this->performance->start('bootstrap', 'router_hooks');
            $this->router->registerHooks();
            $this->performance->stop('bootstrap', 'router_hooks');
        }

        $this->performance->start('bootstrap', 'dashboard_run');
        $this->pipeline->run($requestType);
        $this->performance->stop('bootstrap', 'dashboard_run');
        $this->performance->stop('bootstrap', 'init');
    }
}
