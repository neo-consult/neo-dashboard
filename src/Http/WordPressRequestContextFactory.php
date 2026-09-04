<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Http;

/**
 * Translates native WordPress request state into a deterministic value object.
 */
final readonly class WordPressRequestContextFactory implements RequestContextFactory
{
    public function __construct(
        private WordPressRequestEnvironment $environment,
    ) {}

    public function create(): RequestContext
    {
        $path = RequestContext::normalizePath($this->environment->requestUri());
        $dashboardBasePath = RequestContext::normalizePath($this->environment->dashboardBasePath());

        return new RequestContext(
            $this->detectType(),
            $path,
            $this->isDashboardPath($path, $dashboardBasePath)
                || $this->environment->dashboardSection() !== '',
            $this->isLoginRequest($path),
            $this->environment->isAuthenticated(),
            $this->environment->currentScript() === 'admin-post.php',
        );
    }

    private function detectType(): RequestType
    {
        if ($this->environment->isAjax()) {
            return RequestType::Ajax;
        }

        if ($this->environment->isRest()) {
            return RequestType::Rest;
        }

        if ($this->environment->isCli()) {
            return RequestType::Cli;
        }

        if ($this->environment->isCron()) {
            return RequestType::Cron;
        }

        if ($this->environment->isAdmin()) {
            return RequestType::Admin;
        }

        return RequestType::Web;
    }

    private function isDashboardPath(string $path, string $dashboardBasePath): bool
    {
        return $path === $dashboardBasePath
            || str_starts_with($path, $dashboardBasePath . '/');
    }

    private function isLoginRequest(string $path): bool
    {
        return in_array($this->environment->currentScript(), ['wp-login.php', 'wp-register.php'], true)
            || $path === '/wp-login.php'
            || str_ends_with($path, '/wp-login.php')
            || $path === '/wp-register.php'
            || str_ends_with($path, '/wp-register.php');
    }
}
