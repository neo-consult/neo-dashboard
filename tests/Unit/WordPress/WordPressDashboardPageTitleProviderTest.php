<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\WordPress;

use NeoDashboard\Core\Extension\Registry\SectionRegistry;
use NeoDashboard\Core\Presentation\DashboardPageTitleBuilder;
use NeoDashboard\Core\Routing\SectionResolver;
use NeoDashboard\Core\WordPress\DashboardPageTitleEnvironment;
use NeoDashboard\Core\WordPress\WordPressDashboardPageTitleProvider;
use PHPUnit\Framework\TestCase;

final class WordPressDashboardPageTitleProviderTest extends TestCase
{
    public function testItBuildsAndCachesRootSectionAndNotFoundTitles(): void
    {
        $registry = new SectionRegistry();
        $registry->add('calendar', [
            'slug' => 'calendar',
            'label_callback' => static fn(): string => 'Kalender',
        ]);
        $environment = new FakeDashboardPageTitleEnvironment();
        $provider = new WordPressDashboardPageTitleProvider(
            new SectionResolver($registry),
            new DashboardPageTitleBuilder(),
            $environment,
        );

        self::assertSame('Neo – Dashboard', $provider->title());
        self::assertFalse($provider->hasSection());

        $environment->section = 'calendar';
        self::assertSame('Kalender – Neo', $provider->title());
        self::assertSame('Kalender – Neo', $provider->title());
        self::assertTrue($provider->hasSection());
        self::assertSame(2, $environment->siteNameCalls);

        $environment->section = 'missing';
        self::assertSame('Bereich nicht gefunden – Neo', $provider->title());
        self::assertSame(3, $environment->siteNameCalls);
    }
}

final class FakeDashboardPageTitleEnvironment implements DashboardPageTitleEnvironment
{
    public string $section = '';
    public int $siteNameCalls = 0;

    public function section(): string { return $this->section; }
    public function siteName(): string
    {
        $this->siteNameCalls++;
        return 'Neo';
    }
    public function dashboardLabel(): string { return 'Dashboard'; }
    public function notFoundLabel(): string { return 'Bereich nicht gefunden'; }
}
