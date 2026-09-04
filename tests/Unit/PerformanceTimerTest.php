<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit;

use NeoDashboard\Core\PerformanceTimer;
use NeoDashboard\Core\Logger;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class PerformanceTimerTest extends TestCase
{
    public function testItRecordsCompletedOperationsByCategory(): void
    {
        $timer = new PerformanceTimer(new Logger());

        $timer->start('dashboard', 'render');
        $duration = $timer->stop('dashboard', 'render');

        self::assertGreaterThanOrEqual(0.0, $duration);
        self::assertArrayHasKey('dashboard:render', $timer->getTimings());
        self::assertCount(1, $timer->getTimingsByCategory()['dashboard']);
    }

    public function testSeparateTimersDoNotShareMeasurements(): void
    {
        $first = new PerformanceTimer(new Logger());
        $second = new PerformanceTimer(new Logger());

        $first->start('assets', 'css');

        self::assertArrayHasKey('assets:css', $first->getTimings());
        self::assertSame([], $second->getTimings());
    }

    public function testTimerOwnsNoStaticRuntimeState(): void
    {
        foreach ((new ReflectionClass(PerformanceTimer::class))->getProperties() as $property) {
            self::assertFalse($property->isStatic(), $property->getName());
        }
    }
}
