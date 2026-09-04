<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Presentation;

use NeoDashboard\Core\Presentation\DashboardPageTitleBuilder;
use PHPUnit\Framework\TestCase;

final class DashboardPageTitleBuilderTest extends TestCase
{
    /** @dataProvider titleCases */
    public function testItBuildsTitles(string $site, bool $hasSection, ?string $section, string $expected): void
    {
        self::assertSame($expected, (new DashboardPageTitleBuilder())->build(
            $site,
            'Dashboard',
            'Bereich nicht gefunden',
            $hasSection,
            $section,
        ));
    }

    public function titleCases(): iterable
    {
        yield 'dashboard' => ['Neo', false, null, 'Neo – Dashboard'];
        yield 'section' => ['Neo', true, 'Kalender', 'Kalender – Neo'];
        yield 'not found' => ['Neo', true, null, 'Bereich nicht gefunden – Neo'];
        yield 'site without name' => ['', false, null, 'Neo Dashboard'];
        yield 'section without site' => ['', true, 'Kalender', 'Kalender'];
    }
}
