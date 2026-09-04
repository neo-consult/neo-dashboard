<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Security;

use NeoDashboard\Core\Access\UserRoleResolver;
use NeoDashboard\Core\Http\RequestContext;
use NeoDashboard\Core\Http\RequestType;
use NeoDashboard\Core\Security\AccessDecision;
use NeoDashboard\Core\Security\PublicRouteRegistry;
use NeoDashboard\Core\Security\WordPressAccessDecisionHandler;
use NeoDashboard\Core\Security\WordPressPublicRouteLoader;
use PHPUnit\Framework\TestCase;

final class WordPressBoundaryAdaptersTest extends TestCase
{
    public function testPublicRouteLoaderRegistersCompatibilityDefaultsWithoutWordPress(): void
    {
        $registry = new PublicRouteRegistry();
        $loader = new WordPressPublicRouteLoader($registry);

        $loader->load(new RequestContext(RequestType::Web, '/private'));

        self::assertTrue($registry->isPublic('/wp-cron.php'));
        self::assertTrue($registry->isPublic('/wp-includes/script-loader.php'));
        self::assertTrue($registry->isPublic('/wp-content/uploads/document.pdf'));
        self::assertTrue($registry->isPublic('/wp-content/themes/theme/style.css'));
        self::assertTrue($registry->isPublic('/wp-content/plugins/plugin/script.js'));
        self::assertTrue($registry->isPublic('/xmlrpc.php'));
        self::assertTrue($registry->isPublic('/robots.txt'));
        self::assertTrue($registry->isPublic('/favicon.ico'));
        self::assertFalse($registry->isPublic('/wp-cron.php/extra'));
    }

    public function testPublicRouteLoaderDispatchesTypedWordPressRegistrations(): void
    {
        add_action('neo_dashboard_register_public_routes', static function (PublicRouteRegistry $registry, RequestContext $context): void {
            self::assertSame('/private', $context->path);

            $registry->registerExact('/einwilligung');
            $registry->registerPrefix('/umfrage');
        }, 10, 2);

        $registry = new PublicRouteRegistry();
        $loader = new WordPressPublicRouteLoader($registry);

        $loader->load(new RequestContext(RequestType::Web, '/private'));

        self::assertTrue($registry->isPublic('/einwilligung'));
        self::assertTrue($registry->isPublic('/umfrage/session/123'));
        self::assertFalse($registry->isPublic('/einwilligung/extra'));
    }

    public function testAllowDecisionHasNoWordPressSideEffect(): void
    {
        $handler = new WordPressAccessDecisionHandler(new UserRoleResolver());

        $handler->handle(
            AccessDecision::Allow,
            new RequestContext(RequestType::Web, '/neo-dashboard'),
        );

        self::assertTrue(true);
    }
}
