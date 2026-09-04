<?php
declare(strict_types=1);
namespace NeoDashboard\Core\Widget;

use NeoDashboard\Core\Access\RoleAccessPolicy;

final class WordPressWidgetAccess implements WidgetAccess
{
    private RoleAccessPolicy $policy;

    public function __construct(RoleAccessPolicy $policy)
    {
        $this->policy = $policy;
    }

    public function allows(array $widget): bool
    {
        $user = wp_get_current_user();

        return $this->policy->allows(
            is_array($user->roles) ? $user->roles : [],
            $widget['roles'] ?? null,
        );
    }
}
