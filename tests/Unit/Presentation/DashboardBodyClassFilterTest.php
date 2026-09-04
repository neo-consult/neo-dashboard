<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Presentation;

use NeoDashboard\Core\Presentation\DashboardBodyClassFilter;
use PHPUnit\Framework\TestCase;

final class DashboardBodyClassFilterTest extends TestCase
{
    public function testItFiltersThemeClassesOnlyForDashboardRequests(): void
    {
        $filter = new DashboardBodyClassFilter();
        $classes = ['page', 'page-id-42', 'page-id-custom', 'custom'];

        self::assertSame($classes, $filter->filter($classes, false));
        self::assertSame(
            ['page-id-custom', 'custom', 'neo-dashboard-standalone'],
            $filter->filter($classes, true),
        );
    }
}
