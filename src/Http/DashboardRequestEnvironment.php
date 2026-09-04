<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Http;

interface DashboardRequestEnvironment
{
    public function isAdmin(): bool;

    public function adminPage(): string;

    public function requestUri(): string;

    public function queryVar(string $name): string;
}
