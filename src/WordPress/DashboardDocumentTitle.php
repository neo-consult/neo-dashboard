<?php
declare(strict_types=1);

namespace NeoDashboard\Core\WordPress;

use NeoDashboard\Core\Presentation\DashboardPageTitleProvider;

final class DashboardDocumentTitle
{
    public function __construct(
        private readonly DashboardPageTitleProvider $titleProvider,
    ) {}

    public function registerHooks(): void
    {
        add_filter('neo_dashboard_page_title', [$this, 'pageTitle'], 10);
        add_filter('document_title_parts', [$this, 'filterDocumentTitle'], 999);
        add_filter('wp_title', [$this, 'filterWpTitle'], 999, 2);
    }

    public function pageTitle(string $currentTitle = ''): string
    {
        return $this->titleProvider->title();
    }

    /** @param array<string, string> $titleParts @return array<string, string> */
    public function filterDocumentTitle(array $titleParts): array
    {
        return $this->applyDocumentTitle(
            $titleParts,
            $this->titleProvider->hasSection(),
            $this->titleProvider->title(),
        );
    }

    public function filterWpTitle(string $title, string $separator = '&raquo;'): string
    {
        return $this->applyLegacyTitle(
            $title,
            $this->titleProvider->hasSection(),
            $this->titleProvider->title(),
        );
    }

    /** @param array<string, string> $parts @return array<string, string> */
    public function applyDocumentTitle(array $parts, bool $hasSection, string $pageTitle): array
    {
        if (!$hasSection || $pageTitle === '') {
            return $parts;
        }
        $parts['title'] = $pageTitle;
        $parts['site'] = '';
        return $parts;
    }

    public function applyLegacyTitle(string $title, bool $hasSection, string $pageTitle): string
    {
        return $hasSection && $pageTitle !== '' ? $pageTitle : $title;
    }

}
