<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Http;

use WP_User;

interface DashboardRequest
{
    public function user(): WP_User;

    public function section(): string;
}
