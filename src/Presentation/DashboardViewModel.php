<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Presentation;

use NeoDashboard\Core\Routing\SectionResolution;

final class DashboardViewModel
{
    /**
     * @param array<string, array<string, mixed>> $sidebar
     * @param array<string, array<string, mixed>> $notifications
     * @param array<string, array<string, mixed>> $sections
     * @param array<string, array<string, mixed>> $widgets
     */
    public function __construct(
        private readonly string $currentSection,
        private readonly array $sidebar,
        private readonly array $notifications,
        private readonly array $sections,
        private readonly array $widgets,
        private readonly SectionResolution $resolution,
    ) {}

    public function currentSection(): string { return $this->currentSection; }
    public function sidebar(): array { return $this->sidebar; }
    public function notifications(): array { return $this->notifications; }
    public function sections(): array { return $this->sections; }
    public function widgets(): array { return $this->widgets; }
    public function resolution(): SectionResolution { return $this->resolution; }
    public function activeSection(): ?array { return $this->resolution->section()?->toArray(); }
    public function sectionNotFound(): bool { return $this->resolution->isNotFound(); }
}
