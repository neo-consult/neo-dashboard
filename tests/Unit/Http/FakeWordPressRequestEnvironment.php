<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Http;

use NeoDashboard\Core\Http\WordPressRequestEnvironment;

final class FakeWordPressRequestEnvironment implements WordPressRequestEnvironment
{
    public bool $ajax = false;
    public bool $rest = false;
    public bool $cli = false;
    public bool $cron = false;
    public bool $admin = false;
    public bool $authenticated = false;
    public string $uri = '/';
    public string $script = 'index.php';
    public string $dashboardPath = '/neo-dashboard';
    public string $section = '';

    public function isAjax(): bool
    {
        return $this->ajax;
    }

    public function isRest(): bool
    {
        return $this->rest;
    }

    public function isCli(): bool
    {
        return $this->cli;
    }

    public function isCron(): bool
    {
        return $this->cron;
    }

    public function isAdmin(): bool
    {
        return $this->admin;
    }

    public function isAuthenticated(): bool
    {
        return $this->authenticated;
    }

    public function requestUri(): string
    {
        return $this->uri;
    }

    public function currentScript(): string
    {
        return $this->script;
    }

    public function dashboardBasePath(): string
    {
        return $this->dashboardPath;
    }

    public function dashboardSection(): string
    {
        return $this->section;
    }
}
