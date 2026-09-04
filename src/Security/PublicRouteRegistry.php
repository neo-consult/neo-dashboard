<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Security;

use NeoDashboard\Core\Http\RequestContext;

/**
 * Stores public route rules and performs boundary-aware path matching.
 */
final class PublicRouteRegistry
{
    /** @var array<string, PublicRouteDefinition> */
    private array $routes = [];

    public function register(PublicRouteDefinition $route): void
    {
        $this->routes[$route->key()] = $route;
    }

    public function registerExact(string $path): void
    {
        $this->register(new PublicRouteDefinition($path, PublicRouteMatch::Exact));
    }

    public function registerPrefix(string $path): void
    {
        $this->register(new PublicRouteDefinition($path, PublicRouteMatch::Prefix));
    }

    public function isPublic(string $uri): bool
    {
        if (PublicRouteDefinition::containsTraversalSegment($uri)) {
            return false;
        }

        $path = RequestContext::normalizePath($uri);

        foreach ($this->routes as $route) {
            if ($route->matches($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<PublicRouteDefinition>
     */
    public function all(): array
    {
        return array_values($this->routes);
    }
}
