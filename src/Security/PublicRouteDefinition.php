<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Security;

use InvalidArgumentException;
use NeoDashboard\Core\Http\RequestContext;

/**
 * A normalized rule for a route that may be accessed without authentication.
 */
final readonly class PublicRouteDefinition
{
    public string $path;

    public function __construct(
        string $path,
        public PublicRouteMatch $match = PublicRouteMatch::Exact,
    ) {
        if (self::containsTraversalSegment($path)) {
            throw new InvalidArgumentException('Public routes must not contain traversal segments.');
        }

        $this->path = RequestContext::normalizePath($path);

        if ($this->match === PublicRouteMatch::Prefix && $this->path === '/') {
            throw new InvalidArgumentException('The root path cannot be registered as a public prefix.');
        }
    }

    public function matches(string $path): bool
    {
        if ($this->match === PublicRouteMatch::Exact) {
            return $path === $this->path;
        }

        return $path === $this->path || str_starts_with($path, $this->path . '/');
    }

    public function key(): string
    {
        return $this->match->value . ':' . $this->path;
    }

    public static function containsTraversalSegment(string $uri): bool
    {
        $path = preg_split('/[?#]/', rawurldecode(str_replace('\\', '/', $uri)), 2)[0] ?? '';
        $segments = explode('/', $path);

        return in_array('..', $segments, true) || in_array('.', $segments, true);
    }
}
