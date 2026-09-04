<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Manager;

use NeoDashboard\Core\Rest\RestRouteCollection;
use NeoDashboard\Core\Rest\RestRouteDefinition;
use NeoDashboard\Core\Rest\RestRouteRegistrar;

/** WordPress facade for the Neo Dashboard REST extension API. */
class RestManager
{
    private RestRouteCollection $routes;
    private RestRouteRegistrar $registrar;

    public function __construct(
        RestRouteCollection $routes,
        RestRouteRegistrar $registrar,
    ) {
        $this->routes = $routes;
        $this->registrar = $registrar;
    }

    public function registerHooks(): void
    {
        add_action('rest_api_init', [$this, 'init'], 9);
    }

    public function init(): void
    {
        do_action('neo_dashboard_register_rest_routes', $this);
        $this->registerRoutes();
    }

    /** @param array<string, array<string, mixed>> $args */
    public function registerRoute(
        string $route,
        callable $callback,
        string|array $methods = 'POST',
        array $args = [],
        string $capability = 'read',
    ): void {
        $this->routes->add(new RestRouteDefinition(
            $route,
            $callback,
            $methods,
            $args,
            $capability,
        ));
    }

    private function registerRoutes(): void
    {
        $this->registrar->register($this->routes->all());
    }
}
