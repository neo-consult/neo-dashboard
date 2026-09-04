<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit;

use NeoDashboard\Core\Logger;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class LoggerArchitectureTest extends TestCase
{
    public function testLoggerExposesNoStaticServiceMethodsOrState(): void
    {
        $reflection = new ReflectionClass(Logger::class);

        foreach ($reflection->getMethods() as $method) {
            self::assertFalse($method->isStatic(), $method->getName());
        }
        foreach ($reflection->getProperties() as $property) {
            self::assertFalse($property->isStatic(), $property->getName());
        }
    }

    public function testLoggingIsDisabledByDefaultWhenDebugIsOff(): void
    {
        self::assertFalse((new Logger())->isEnabled());
    }
}
