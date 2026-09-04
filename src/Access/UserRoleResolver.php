<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Access;

final class UserRoleResolver
{
    /** @param string[] $roles */
    public function primary(array $roles): string
    {
        foreach (['administrator', 'neo_editor', 'neo_mitarbeiter'] as $role) {
            if (in_array($role, $roles, true)) {
                return $role;
            }
        }

        return $roles === [] ? 'guest' : 'unknown';
    }

    /** @param string[] $roles */
    public function isAdministrator(array $roles): bool
    {
        return in_array('administrator', $roles, true);
    }
}
