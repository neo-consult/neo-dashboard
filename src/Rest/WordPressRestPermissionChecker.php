<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Rest;

final class WordPressRestPermissionChecker implements RestPermissionChecker
{
    public function can(string $capability): bool
    {
        return current_user_can($capability);
    }
}
