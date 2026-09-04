<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Access;

final class RoleAccessPolicy
{
    /** @param array<int, string> $userRoles @param array<int, string>|null $requiredRoles */
    public function allows(array $userRoles, ?array $requiredRoles): bool
    {
        return $requiredRoles === null
            || $requiredRoles === []
            || array_intersect($requiredRoles, $userRoles) !== [];
    }
}
