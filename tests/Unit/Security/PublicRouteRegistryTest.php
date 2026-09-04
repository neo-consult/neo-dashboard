<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Security;

use NeoDashboard\Core\Security\PublicRouteRegistry;
use PHPUnit\Framework\TestCase;

final class PublicRouteRegistryTest extends TestCase
{
    public function testItMatchesExactRoutesAfterNormalizingTheRequest(): void
    {
        $registry = new PublicRouteRegistry();
        $registry->registerExact('/robots.txt');

        self::assertTrue($registry->isPublic('/robots.txt?cache=1'));
        self::assertFalse($registry->isPublic('/folder/robots.txt'));
        self::assertFalse($registry->isPublic('/robots.txt.bak'));
    }

    public function testItMatchesPrefixRoutesOnSegmentBoundaries(): void
    {
        $registry = new PublicRouteRegistry();
        $registry->registerPrefix('/wp-content/uploads');

        self::assertTrue($registry->isPublic('/wp-content/uploads'));
        self::assertTrue($registry->isPublic('/wp-content/uploads/document.pdf'));
        self::assertFalse($registry->isPublic('/wp-content/uploads-private/document.pdf'));
        self::assertFalse($registry->isPublic('/backup/wp-content/uploads/document.pdf'));
    }

    public function testTypedRoutesPreserveExactAndPrefixIntent(): void
    {
        $registry = new PublicRouteRegistry();
        $registry->registerExact('/einwilligung');
        $registry->registerPrefix('/einwilligung');
        $registry->registerExact('/robots.txt');

        self::assertTrue($registry->isPublic('/einwilligung'));
        self::assertTrue($registry->isPublic('/einwilligung/session/123'));
        self::assertTrue($registry->isPublic('/robots.txt'));
        self::assertFalse($registry->isPublic('/robots.txt/extra'));
        self::assertCount(3, $registry->all());
    }

    public function testDuplicateRulesAreIdempotent(): void
    {
        $registry = new PublicRouteRegistry();
        $registry->registerExact('/favicon.ico');
        $registry->registerExact('/favicon.ico');
        $registry->registerPrefix('/favicon.ico');

        self::assertCount(2, $registry->all());
    }

    /**
     * @dataProvider traversalRequestProvider
     */
    public function testTraversalRequestsAreNeverPublic(string $request): void
    {
        $registry = new PublicRouteRegistry();
        $registry->registerPrefix('/public');

        self::assertFalse($registry->isPublic($request));
    }

    /**
     * @return array<string, array{string}>
     */
    public function traversalRequestProvider(): array
    {
        return [
            'parent traversal' => ['/public/../private'],
            'encoded traversal' => ['/public/%2e%2e/private'],
            'backslash traversal' => ['\\public\\..\\private'],
            'current directory' => ['/public/./document.pdf'],
        ];
    }

    public function testAnEmptyRegistryKeepsEveryRoutePrivate(): void
    {
        $registry = new PublicRouteRegistry();

        self::assertFalse($registry->isPublic('/'));
        self::assertFalse($registry->isPublic('/neo-dashboard'));
    }
}
