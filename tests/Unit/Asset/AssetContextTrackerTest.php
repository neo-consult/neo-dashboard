<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Asset;

use NeoDashboard\Core\Asset\AssetContextTracker;
use PHPUnit\Framework\TestCase;

final class AssetContextTrackerTest extends TestCase
{
    public function testItClaimsEachContextOnlyOnce(): void
    {
        $tracker = new AssetContextTracker();

        self::assertTrue($tracker->claim('dashboard-home'));
        self::assertFalse($tracker->claim('dashboard-home'));
        self::assertTrue($tracker->claim('neo-calendar'));
        self::assertFalse($tracker->claim('neo-calendar'));
    }

    public function testSeparateRuntimeTrackersDoNotShareState(): void
    {
        $firstRuntime = new AssetContextTracker();
        $secondRuntime = new AssetContextTracker();

        self::assertTrue($firstRuntime->claim('dashboard-home'));
        self::assertTrue($secondRuntime->claim('dashboard-home'));
    }
}
