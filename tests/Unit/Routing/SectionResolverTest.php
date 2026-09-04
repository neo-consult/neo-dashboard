<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Routing;

use NeoDashboard\Core\Extension\Registry\SectionRegistry;
use NeoDashboard\Core\Routing\SectionResolver;
use NeoDashboard\Core\Routing\SectionRoute;
use NeoDashboard\Core\Routing\SectionResolutionStatus;
use PHPUnit\Framework\TestCase;

final class SectionResolverTest extends TestCase
{
    /** @dataProvider validRoutes */
    public function testItNormalizesValidSectionRoutes(string $query, string $expected): void
    {
        self::assertSame($expected, SectionRoute::fromQuery($query)?->slug());
    }

    /** @return iterable<string, array{string, string}> */
    public function validRoutes(): iterable
    {
        yield 'root section' => ['neo-calendar', 'neo-calendar'];
        yield 'nested section' => ['/neo-calendar/work-time/', 'neo-calendar/work-time'];
        yield 'encoded slash' => ['neo-calendar%2Fsettings', 'neo-calendar/settings'];
    }

    /** @dataProvider invalidRoutes */
    public function testItRejectsInvalidSectionRoutes(mixed $query): void
    {
        self::assertNull(SectionRoute::fromQuery($query));
    }

    /** @return iterable<string, array{mixed}> */
    public function invalidRoutes(): iterable
    {
        yield 'empty' => [''];
        yield 'traversal' => ['../settings'];
        yield 'double slash' => ['calendar//settings'];
        yield 'absolute URL' => ['https://example.test/calendar'];
        yield 'non string' => [42];
    }

    public function testItResolvesOnlyARegisteredAndAllowedSection(): void
    {
        $registry = new SectionRegistry();
        $registry->add('calendar', [
            'slug' => 'calendar',
            'label_callback' => static fn(): string => 'Calendar',
            'callback' => static fn(): string => 'content',
            'roles' => ['administrator'],
        ]);
        $resolver = new SectionResolver($registry);

        $resolved = $resolver->resolve('calendar', ['calendar' => ['slug' => 'calendar']]);
        self::assertNotNull($resolved);
        self::assertSame('calendar', $resolved->slug());
        self::assertSame('Calendar', $resolved->label());
        self::assertSame(['administrator'], $resolved->roles());
        self::assertIsCallable($resolved->callback());

        self::assertNull($resolver->resolve('calendar', []));
        self::assertNull($resolver->resolve('missing'));
    }

    public function testItDistinguishesRootFoundAndNotFoundRequests(): void
    {
        $registry = new SectionRegistry();
        $registry->add('calendar', ['slug' => 'calendar']);
        $resolver = new SectionResolver($registry);

        self::assertSame(SectionResolutionStatus::ROOT, $resolver->resolveRequest('')->status());
        self::assertSame(SectionResolutionStatus::FOUND, $resolver->resolveRequest('calendar')->status());
        self::assertSame(SectionResolutionStatus::NOT_FOUND, $resolver->resolveRequest('missing')->status());
        self::assertSame(SectionResolutionStatus::NOT_FOUND, $resolver->resolveRequest('../settings')->status());
        self::assertSame(SectionResolutionStatus::NOT_FOUND, $resolver->resolveRequest('calendar', [])->status());
    }
}
