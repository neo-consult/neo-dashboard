<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Access;

use NeoDashboard\Core\Access\UserRoleResolver;
use PHPUnit\Framework\TestCase;

final class UserRoleResolverTest extends TestCase
{
    /** @dataProvider roles */
    public function testItResolvesRolesByExplicitPriority(array $roles, string $expected): void
    {
        self::assertSame($expected, (new UserRoleResolver())->primary($roles));
    }

    public function roles(): iterable
    {
        yield 'guest' => [[], 'guest'];
        yield 'unknown' => [['subscriber'], 'unknown'];
        yield 'employee' => [['neo_mitarbeiter'], 'neo_mitarbeiter'];
        yield 'editor wins over employee' => [['neo_mitarbeiter', 'neo_editor'], 'neo_editor'];
        yield 'administrator wins' => [['neo_editor', 'administrator'], 'administrator'];
    }

    public function testItRecognizesAdministratorsStrictly(): void
    {
        $resolver = new UserRoleResolver();
        self::assertTrue($resolver->isAdministrator(['administrator']));
        self::assertFalse($resolver->isAdministrator(['neo_editor']));
    }
}
