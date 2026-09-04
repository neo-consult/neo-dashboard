<?php

declare(strict_types=1);

namespace NeoDashboard\Core\WordPress;

use NeoDashboard\Core\Http\RequestTypeProvider;
use NeoDashboard\Core\Logger;
use NeoDashboard\Core\Manager\ContentManager;
use NeoDashboard\Core\Presentation\DashboardBodyClassFilter;
use NeoDashboard\Core\Presentation\DashboardTemplateSelector;
use NeoDashboard\Core\Routing\DashboardRouteRegistrar;
use NeoDashboard\Core\Routing\SectionRoute;

final readonly class DashboardRouter
{
    public function __construct(
        private ContentManager $contentManager,
        private DashboardRouteRegistrar $routeRegistrar,
        private DashboardTemplateSelector $templateSelector,
        private DashboardBodyClassFilter $bodyClassFilter,
        private RequestTypeProvider $requestTypeProvider,
        private Logger $logger,
    ) {}

    public function registerHooks(): void
    {
        if ($this->requestTypeProvider->type() !== 'WEB') {
            return;
        }

        $this->routeRegistrar->register();
        add_filter('query_vars', [$this->routeRegistrar, 'addQueryVar']);
        add_filter('template_include', [$this, 'selectTemplate'], 99);
        add_shortcode('neo-dashboard', [$this->contentManager, 'render']);
        add_shortcode('dashboard', [$this->contentManager, 'render']);
        add_filter('body_class', [$this, 'filterBodyClasses'], 99);
    }

    public function selectTemplate(?string $template): string
    {
        $section = SectionRoute::fromQuery(
            get_query_var(DashboardRouteRegistrar::QUERY_VAR_SECTION),
        );
        $this->logger->info('NeoDashboard Template-Weiche', ['neo_section' => $section?->slug()]);

        return $this->templateSelector->select(
            $template ?? '',
            $this->isDashboardRequest($section),
            plugin_dir_path(dirname(__DIR__)) . 'templates/dashboard-blank.php',
        );
    }

    /** @param list<string> $classes @return list<string> */
    public function filterBodyClasses(array $classes): array
    {
        $section = SectionRoute::fromQuery(
            get_query_var(DashboardRouteRegistrar::QUERY_VAR_SECTION),
        );

        return $this->bodyClassFilter->filter(
            $classes,
            $this->isDashboardRequest($section),
        );
    }

    private function isDashboardRequest(?SectionRoute $section): bool
    {
        return is_page('neo-dashboard') || $section !== null;
    }
}
