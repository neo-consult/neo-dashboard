<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Security;

interface AccessCapabilityResolver
{
    public function canAccessDashboard(): bool;

    public function canAccessAdmin(): bool;
}
