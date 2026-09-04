<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\WordPress;

use NeoDashboard\Core\Presentation\DashboardPageTitleProvider;
use NeoDashboard\Core\WordPress\DashboardDocumentTitle;
use PHPUnit\Framework\TestCase;

final class DashboardDocumentTitleTest extends TestCase
{
    public function testItUsesTheInjectedTitleProvider(): void
    {
        $provider = new class implements DashboardPageTitleProvider {
            public function title(): string { return 'Kalender – Neo'; }
            public function hasSection(): bool { return true; }
        };
        $filter = new DashboardDocumentTitle($provider);

        self::assertSame('Kalender – Neo', $filter->pageTitle());
        self::assertSame(
            ['title' => 'Kalender – Neo', 'site' => ''],
            $filter->filterDocumentTitle(['title' => 'Theme', 'site' => 'Neo']),
        );
        self::assertSame('Kalender – Neo', $filter->filterWpTitle('Theme'));
    }

    public function testItReplacesDocumentTitleOnlyForAResolvedSection(): void
    {
        $filter = new DashboardDocumentTitle($this->providerWithoutSection());
        $parts = ['title' => 'Theme', 'site' => 'Website'];

        self::assertSame($parts, $filter->applyDocumentTitle($parts, false, 'Calendar'));
        self::assertSame($parts, $filter->applyDocumentTitle($parts, true, ''));
        self::assertSame(
            ['title' => 'Calendar', 'site' => ''],
            $filter->applyDocumentTitle($parts, true, 'Calendar'),
        );
    }

    public function testItReplacesLegacyTitleOnlyForAResolvedSection(): void
    {
        $filter = new DashboardDocumentTitle($this->providerWithoutSection());
        self::assertSame('Theme', $filter->applyLegacyTitle('Theme', false, 'Calendar'));
        self::assertSame('Calendar', $filter->applyLegacyTitle('Theme', true, 'Calendar'));
    }

    private function providerWithoutSection(): DashboardPageTitleProvider
    {
        return new class implements DashboardPageTitleProvider {
            public function title(): string { return ''; }
            public function hasSection(): bool { return false; }
        };
    }
}
