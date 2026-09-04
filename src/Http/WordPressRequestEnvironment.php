<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Http;

/**
 * Boundary for reading request state from WordPress and PHP globals.
 */
interface WordPressRequestEnvironment
{
    public function isAjax(): bool;

    public function isRest(): bool;

    public function isCli(): bool;

    public function isCron(): bool;

    public function isAdmin(): bool;

    public function isAuthenticated(): bool;

    public function requestUri(): string;

    public function currentScript(): string;

    public function dashboardBasePath(): string;

    public function dashboardSection(): string;
}
