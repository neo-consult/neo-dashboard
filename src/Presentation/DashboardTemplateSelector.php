<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Presentation;

final class DashboardTemplateSelector
{
    public function select(string $currentTemplate, bool $dashboardRequest, string $dashboardTemplate): string
    {
        return $dashboardRequest && is_file($dashboardTemplate)
            ? $dashboardTemplate
            : $currentTemplate;
    }
}
