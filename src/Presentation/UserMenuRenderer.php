<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Presentation;

use WP_User;

interface UserMenuRenderer
{
    public function render(WP_User $user): string;
}
