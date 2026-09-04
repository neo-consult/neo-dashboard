<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Rest;

interface RestPermissionChecker
{
    public function can(string $capability): bool;
}
