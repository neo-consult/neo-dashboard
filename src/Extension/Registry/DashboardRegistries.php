<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Extension\Registry;

final readonly class DashboardRegistries
{
    public function __construct(
        private NavigationRegistry $navigation,
        private SectionRegistry $sections,
        private WidgetRegistry $widgets,
        private NotificationRegistry $notifications,
    ) {}

    public function navigation(): NavigationRegistry { return $this->navigation; }
    public function sections(): SectionRegistry { return $this->sections; }
    public function widgets(): WidgetRegistry { return $this->widgets; }
    public function notifications(): NotificationRegistry { return $this->notifications; }
}
