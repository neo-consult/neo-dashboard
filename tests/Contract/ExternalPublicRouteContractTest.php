<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Contract;

use NeoDashboard\Core\Http\RequestContext;
use NeoDashboard\Core\Http\RequestType;
use NeoDashboard\Core\Security\PublicRouteRegistry;
use NeoDashboard\Core\Tests\Support\WordPressTestEnvironment;
use PHPUnit\Framework\TestCase;

final class ExternalPublicRouteContractTest extends TestCase
{
    private static bool $pluginsLoaded = false;

    protected function setUp(): void
    {
        WordPressTestEnvironment::reset();

        if (!self::$pluginsLoaded) {
            require_once dirname(__DIR__, 3) . '/neo-surveys-extern/neo-surveys-extern.php';
            require_once dirname(__DIR__, 3) . '/neo-privacy-extern/neo-privacy-extern.php';
            self::$pluginsLoaded = true;

            return;
        }

        \NeoSurveysExtern\Plugin::init();
        \NeoPrivacyExtern\Plugin::init();
    }

    public function testSurveysExternRegistersTypedPrefixRoutes(): void
    {
        update_option('neo_surveys_extern_public_page_slug', 'umfrage');

        $registry = new PublicRouteRegistry();
        do_action('neo_dashboard_register_public_routes', $registry, new RequestContext(RequestType::Web, '/private'));

        self::assertTrue($registry->isPublic('/umfrage'));
        self::assertTrue($registry->isPublic('/umfrage/session/abc'));
        self::assertTrue($registry->isPublic('/index.php/umfrage'));
        self::assertTrue($registry->isPublic('/index.php/umfrage/session/abc'));
    }

    public function testPrivacyExternRegistersTypedExactRoutes(): void
    {
        update_option('neo_privacy_extern_public_page_slug', 'einwilligung');

        $registry = new PublicRouteRegistry();
        do_action('neo_dashboard_register_public_routes', $registry, new RequestContext(RequestType::Web, '/private'));

        self::assertTrue($registry->isPublic('/einwilligung'));
        self::assertTrue($registry->isPublic('/index.php/einwilligung'));
        self::assertFalse($registry->isPublic('/einwilligung/session/abc'));
    }
}
