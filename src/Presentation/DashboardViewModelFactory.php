<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Presentation;

use NeoDashboard\Core\Extension\Registry\NavigationRegistry;
use NeoDashboard\Core\Extension\Registry\NotificationRegistry;
use NeoDashboard\Core\Extension\Registry\SectionRegistry;
use NeoDashboard\Core\Extension\Registry\WidgetRegistry;
use NeoDashboard\Core\Routing\SectionResolver;

final class DashboardViewModelFactory
{
    public function __construct(
        private readonly NavigationRegistry $navigation,
        private readonly NotificationRegistry $notifications,
        private readonly SectionRegistry $sections,
        private readonly WidgetRegistry $widgets,
        private readonly SectionResolver $sectionResolver,
    ) {}

    /** @param callable(array<string, mixed>): bool $canAccess */
    public function create(string $currentSection, callable $canAccess): DashboardViewModel
    {
        $sidebar = array_filter($this->navigation->tree(), $canAccess);
        $notifications = array_filter($this->notifications->all(), $canAccess);
        $sections = array_filter($this->sections->all(), $canAccess);
        $widgets = array_filter($this->widgets->all(), $canAccess);

        return new DashboardViewModel(
            $currentSection,
            $sidebar,
            $notifications,
            $sections,
            $widgets,
            $this->sectionResolver->resolveRequest($currentSection, $sections),
        );
    }
}
