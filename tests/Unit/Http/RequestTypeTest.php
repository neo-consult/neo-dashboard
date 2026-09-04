<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Http;

use NeoDashboard\Core\Http\RequestType;
use PHPUnit\Framework\TestCase;

final class RequestTypeTest extends TestCase
{
    public function testAsyncRequestTypesAreRecognized(): void
    {
        self::assertTrue(RequestType::Ajax->isAsync());
        self::assertTrue(RequestType::Rest->isAsync());
        self::assertFalse(RequestType::Web->isAsync());
    }

    public function testSystemRequestTypesAreRecognized(): void
    {
        self::assertTrue(RequestType::Cron->isSystem());
        self::assertTrue(RequestType::Cli->isSystem());
        self::assertFalse(RequestType::Admin->isSystem());
    }
}
