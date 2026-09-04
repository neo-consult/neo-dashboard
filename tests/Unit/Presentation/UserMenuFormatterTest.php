<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Presentation;

use NeoDashboard\Core\Presentation\UserMenuFormatter;
use PHPUnit\Framework\TestCase;

final class UserMenuFormatterTest extends TestCase
{
    /** @dataProvider initialsCases */
    public function testItBuildsInitials(string $displayName, string $login, string $expected): void
    {
        self::assertSame($expected, (new UserMenuFormatter())->initials($displayName, $login));
    }

    public function initialsCases(): iterable
    {
        yield 'first and last name' => ['André Neo', 'admin', 'AN'];
        yield 'single name' => ['Neo', 'admin', 'NE'];
        yield 'login fallback' => ['', 'admin-neo', 'AD'];
        yield 'extra spaces' => ['  André   Neo  ', 'admin', 'AN'];
    }

    /** @dataProvider roleCases */
    public function testItMapsKnownRoles(string $role, ?string $expected): void
    {
        self::assertSame($expected, (new UserMenuFormatter())->roleLabel($role));
    }

    public function roleCases(): iterable
    {
        yield 'administrator' => ['administrator', 'Administrator'];
        yield 'editor' => ['neo_editor', 'Editor'];
        yield 'employee' => ['neo_mitarbeiter', 'Mitarbeiter'];
        yield 'unknown' => ['subscriber', null];
    }
}
