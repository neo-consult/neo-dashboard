<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Routing;

use NeoDashboard\Core\Routing\DashboardRouteRegistrar;
use PHPUnit\Framework\TestCase;

final class DashboardRouteRegistrarTest extends TestCase
{
    public function testItAddsTheDashboardSectionQueryVariableOnlyOnce(): void
    {
        $registrar = new DashboardRouteRegistrar();

        self::assertSame(
            ['page', DashboardRouteRegistrar::QUERY_VAR_SECTION],
            $registrar->addQueryVar(['page']),
        );
        self::assertSame(
            ['page', DashboardRouteRegistrar::QUERY_VAR_SECTION],
            $registrar->addQueryVar(['page', DashboardRouteRegistrar::QUERY_VAR_SECTION]),
        );
    }
}
