<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Security;

use NeoDashboard\Core\Http\RequestContext;

/**
 * Imports WordPress defaults and dispatches typed public-route registration
 * into the boundary-aware route registry.
 */
final readonly class WordPressPublicRouteLoader implements PublicRouteLoader
{
    /** @var list<string> */
    private const DEFAULT_EXACT_PATHS = [
        '/wp-cron.php',
        '/xmlrpc.php',
        '/robots.txt',
        '/favicon.ico',
    ];

    /** @var list<string> */
    private const DEFAULT_PREFIX_PATHS = [
        '/wp-includes',
        '/wp-content/uploads',
        '/wp-content/themes',
        '/wp-content/plugins',
    ];

    public function __construct(
        private PublicRouteRegistry $registry,
    ) {}

    public function load(RequestContext $context): void
    {
        foreach (self::DEFAULT_EXACT_PATHS as $path) {
            $this->registry->registerExact($path);
        }

        foreach (self::DEFAULT_PREFIX_PATHS as $path) {
            $this->registry->registerPrefix($path);
        }

        if (function_exists('do_action')) {
            do_action('neo_dashboard_register_public_routes', $this->registry, $context);
        }
    }
}
