<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Presentation;

interface DashboardPageTitleProvider
{
    public function title(): string;

    public function hasSection(): bool;
}
