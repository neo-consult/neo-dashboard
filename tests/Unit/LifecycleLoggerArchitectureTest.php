<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit;

use NeoDashboard\Core\LifecycleLogger;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class LifecycleLoggerArchitectureTest extends TestCase
{
    public function testLifecycleMeasurementsAreInstanceScoped(): void
    {
        $reflection = new ReflectionClass(LifecycleLogger::class);

        foreach ($reflection->getProperties() as $property) {
            self::assertFalse($property->isStatic(), $property->getName());
        }
    }
}
