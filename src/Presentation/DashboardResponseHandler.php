<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Presentation;

final class DashboardResponseHandler
{
    public function apply(DashboardViewModel $viewModel): void
    {
        if (!$viewModel->sectionNotFound() || headers_sent()) {
            return;
        }

        status_header(404);
        nocache_headers();
    }
}
