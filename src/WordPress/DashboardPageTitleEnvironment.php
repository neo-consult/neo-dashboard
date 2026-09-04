<?php

declare(strict_types=1);

namespace NeoDashboard\Core\WordPress;

interface DashboardPageTitleEnvironment
{
    public function section(): string;

    public function siteName(): string;

    public function dashboardLabel(): string;

    public function notFoundLabel(): string;
}
