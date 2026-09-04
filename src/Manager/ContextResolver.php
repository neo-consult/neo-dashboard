<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Manager;

use NeoDashboard\Core\Http\DashboardRequestEnvironment;
use NeoDashboard\Core\Routing\DashboardRouteRegistrar;

final class ContextResolver
{
    private ?string $cachedContext = null;
    private DashboardRequestEnvironment $environment;

    public function __construct(
        DashboardRequestEnvironment $environment,
    ) {
        $this->environment = $environment;
    }
    
    public function isDashboard(): bool
    {
        if ($this->environment->isAdmin()) {
            if ($this->environment->adminPage() === 'neo-dashboard') {
                return true;
            }

            $uri = $this->environment->requestUri();
            return strpos($uri, 'wp-admin') !== false && strpos($uri, 'neo-dashboard') !== false;
        }

        $section = $this->environment->queryVar(DashboardRouteRegistrar::QUERY_VAR_SECTION);
        $page = $this->environment->queryVar('pagename');
        $uri = $this->environment->requestUri();

        return $section !== '' || $page === 'neo-dashboard' || str_starts_with($uri, '/neo-dashboard');
    }

    public function current(): string
    {
        if ($this->cachedContext !== null) {
            return $this->cachedContext;
        }

        $section = $this->environment->queryVar(DashboardRouteRegistrar::QUERY_VAR_SECTION);
        $uri = $this->environment->requestUri();
        $cleanUri = (string) strtok($uri, '?');
        if ($cleanUri === '/neo-dashboard/' || $cleanUri === '/neo-dashboard') {
            $this->cachedContext = 'dashboard-home';
            return $this->cachedContext;
        }

        if (str_starts_with($cleanUri, '/neo-dashboard/')) {
            $pathParts = explode('/', trim($cleanUri, '/'));
            if (count($pathParts) >= 2 && $pathParts[0] === 'neo-dashboard') {
                $context = implode('/', array_slice($pathParts, 1));
                $this->cachedContext = $context;
                return $this->cachedContext;
            }
        }

        if ($section !== '') {
            $this->cachedContext = $section;
            return $this->cachedContext;
        }

        $this->cachedContext = 'dashboard-home';
        return $this->cachedContext;
    }
}
