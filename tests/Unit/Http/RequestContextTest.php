<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Http;

use NeoDashboard\Core\Http\RequestContext;
use NeoDashboard\Core\Http\RequestType;
use PHPUnit\Framework\TestCase;

final class RequestContextTest extends TestCase
{
    /**
     * @dataProvider pathProvider
     */
    public function testItNormalizesRequestPaths(string $uri, string $expectedPath): void
    {
        self::assertSame($expectedPath, RequestContext::normalizePath($uri));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function pathProvider(): array
    {
        return [
            'empty path' => ['', '/'],
            'root path' => ['/', '/'],
            'missing leading slash' => ['neo-dashboard/calendar', '/neo-dashboard/calendar'],
            'trailing slash' => ['/neo-dashboard/calendar/', '/neo-dashboard/calendar'],
            'duplicate slashes' => ['//neo-dashboard///calendar//', '/neo-dashboard/calendar'],
            'query string' => ['/neo-dashboard/calendar?page=2', '/neo-dashboard/calendar'],
            'absolute URL' => ['https://example.test/neo-dashboard/calendar?tab=week', '/neo-dashboard/calendar'],
            'encoded path' => ['/einwilligung%20extern/', '/einwilligung extern'],
            'backslashes' => ['\\neo-dashboard\\calendar', '/neo-dashboard/calendar'],
        ];
    }

    public function testItExposesTheRequestContextWithoutWordPressDependencies(): void
    {
        $context = new RequestContext(
            RequestType::Ajax,
            '/wp-admin/admin-ajax.php?foo=bar',
            false,
            false,
            true,
        );

        self::assertSame('/wp-admin/admin-ajax.php', $context->path);
        self::assertTrue($context->isAjax());
        self::assertFalse($context->isAdmin());
        self::assertFalse($context->isRest());
        self::assertFalse($context->isSystem());
        self::assertTrue($context->isAuthenticated);
    }
}
