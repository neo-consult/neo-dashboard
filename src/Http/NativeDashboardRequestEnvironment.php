<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Http;

final class NativeDashboardRequestEnvironment implements DashboardRequestEnvironment
{
    public function isAdmin(): bool
    {
        return is_admin();
    }

    public function adminPage(): string
    {
        return (string) ($_GET['page'] ?? '');
    }

    public function requestUri(): string
    {
        return (string) ($_SERVER['REQUEST_URI'] ?? '');
    }

    public function queryVar(string $name): string
    {
        return (string) get_query_var($name, '');
    }
}
