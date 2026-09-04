<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Rest;

final class RestRouteCollection
{
    /** @var array<string, RestRouteDefinition> */
    private array $routes = [];

    public function add(RestRouteDefinition $definition): void
    {
        $this->routes[$definition->route] = $definition;
    }

    /** @return array<string, RestRouteDefinition> */
    public function all(): array
    {
        return $this->routes;
    }
}
