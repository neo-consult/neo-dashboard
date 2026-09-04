<?php

declare(strict_types=1);

namespace NeoDashboard\Core\WordPress;

use NeoDashboard\Core\Presentation\DashboardPageTitleBuilder;
use NeoDashboard\Core\Presentation\DashboardPageTitleProvider;
use NeoDashboard\Core\Routing\SectionResolver;

final class WordPressDashboardPageTitleProvider implements DashboardPageTitleProvider
{
    private ?string $cachedTitle = null;
    private ?string $cachedSection = null;

    public function __construct(
        private readonly SectionResolver $sectionResolver,
        private readonly DashboardPageTitleBuilder $titleBuilder,
        private readonly DashboardPageTitleEnvironment $environment,
    ) {}

    public function title(): string
    {
        $section = $this->environment->section();
        if ($this->cachedTitle !== null && $this->cachedSection === $section) {
            return $this->cachedTitle;
        }

        $resolved = $section === '' ? null : $this->sectionResolver->resolve($section);
        $this->cachedTitle = $this->titleBuilder->build(
            $this->environment->siteName(),
            $this->environment->dashboardLabel(),
            $this->environment->notFoundLabel(),
            $section !== '',
            $resolved?->label(),
        );
        $this->cachedSection = $section;

        return $this->cachedTitle;
    }

    public function hasSection(): bool
    {
        return $this->environment->section() !== '';
    }
}
