<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Security;

use InvalidArgumentException;
use NeoDashboard\Core\Security\PublicRouteDefinition;
use NeoDashboard\Core\Security\PublicRouteMatch;
use PHPUnit\Framework\TestCase;

final class PublicRouteDefinitionTest extends TestCase
{
    public function testItNormalizesRegisteredRoutes(): void
    {
        $route = new PublicRouteDefinition('/einwilligung//extern/');

        self::assertSame('/einwilligung/extern', $route->path);
    }

    public function testExactRoutesOnlyMatchTheSamePath(): void
    {
        $route = new PublicRouteDefinition('/umfrage', PublicRouteMatch::Exact);

        self::assertTrue($route->matches('/umfrage'));
        self::assertFalse($route->matches('/umfrage/antwort'));
        self::assertFalse($route->matches('/umfrage-malicious'));
    }

    public function testPrefixRoutesUsePathSegmentBoundaries(): void
    {
        $route = new PublicRouteDefinition('/umfrage', PublicRouteMatch::Prefix);

        self::assertTrue($route->matches('/umfrage'));
        self::assertTrue($route->matches('/umfrage/antwort'));
        self::assertFalse($route->matches('/umfrage-malicious'));
    }

    /**
     * @dataProvider invalidRouteProvider
     */
    public function testItRejectsUnsafeDefinitions(string $path, PublicRouteMatch $match): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PublicRouteDefinition($path, $match);
    }

    /**
     * @return array<string, array{string, PublicRouteMatch}>
     */
    public function invalidRouteProvider(): array
    {
        return [
            'parent traversal' => ['/public/../private', PublicRouteMatch::Exact],
            'encoded traversal' => ['/public/%2e%2e/private', PublicRouteMatch::Exact],
            'current directory segment' => ['/public/./file', PublicRouteMatch::Exact],
            'public root prefix' => ['/', PublicRouteMatch::Prefix],
        ];
    }
}
