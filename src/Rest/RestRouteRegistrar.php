<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Rest;

interface RestRouteRegistrar
{
    /** @param array<string, RestRouteDefinition> $routes */
    public function register(array $routes): void;
}
