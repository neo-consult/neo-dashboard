<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Access;

use NeoDashboard\Core\Access\RoleAccessPolicy;
use PHPUnit\Framework\TestCase;

final class RoleAccessPolicyTest extends TestCase
{
    /** @dataProvider accessCases */
    public function testItEvaluatesRoleAccess(array $userRoles, ?array $requiredRoles, bool $expected): void
    {
        self::assertSame($expected, (new RoleAccessPolicy())->allows($userRoles, $requiredRoles));
    }

    public function accessCases(): iterable
    {
        yield 'no restriction' => [['subscriber'], null, true];
        yield 'empty restriction' => [[], [], true];
        yield 'matching role' => [['editor', 'member'], ['administrator', 'editor'], true];
        yield 'missing role' => [['subscriber'], ['editor'], false];
    }
}
