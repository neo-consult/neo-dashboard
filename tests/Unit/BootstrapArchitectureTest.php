<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit;

use NeoDashboard\Core\Bootstrap;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class BootstrapArchitectureTest extends TestCase
{
    public function testBootstrapOwnsNoStaticRuntimeState(): void
    {
        $reflection = new ReflectionClass(Bootstrap::class);

        foreach ($reflection->getProperties() as $property) {
            self::assertFalse($property->isStatic(), $property->getName());
        }
    }

    public function testBootstrapOnlyExposesHookRegistration(): void
    {
        $reflection = new ReflectionClass(Bootstrap::class);
        self::assertSame(['registerHooks'], array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
        ));
        self::assertFalse($reflection->getMethod('registerHooks')->isStatic());
    }
}
