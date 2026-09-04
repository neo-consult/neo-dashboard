<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Rest;

final readonly class RestRouteDefinition
{
    /** @param array<string, array<string, mixed>> $args */
    public function __construct(
        public string $route,
        public mixed $callback,
        public string|array $methods,
        public array $args = [],
        public string $capability = 'read',
    ) {}
}
