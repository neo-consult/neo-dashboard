<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Presentation;

final class DashboardPageTitleBuilder
{
    public function build(
        string $siteName,
        string $dashboardLabel,
        string $notFoundLabel,
        bool $hasSection,
        ?string $sectionLabel,
    ): string {
        if (!$hasSection) {
            if (trim($siteName) === '') {
                return 'Neo Dashboard';
            }
            return $this->combine($siteName, $dashboardLabel, true);
        }

        $label = trim((string) $sectionLabel);
        return $this->combine($siteName, $label !== '' ? $label : $notFoundLabel, false);
    }

    private function combine(string $siteName, string $label, bool $siteFirst): string
    {
        $siteName = trim($siteName);
        if ($siteName === '') {
            return $label;
        }

        return $siteFirst
            ? $siteName . ' – ' . $label
            : $label . ' – ' . $siteName;
    }
}
