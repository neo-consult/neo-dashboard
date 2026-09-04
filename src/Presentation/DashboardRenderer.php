<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Presentation;

use WP_User;

final class DashboardRenderer
{
    public function __construct(
        private readonly string $templatePath,
        private readonly UserMenuRenderer $userMenuRenderer,
    ) {}

    public function render(DashboardViewModel $viewModel, WP_User $user): string
    {
        $current_section = $viewModel->currentSection();
        $sidebar = $viewModel->sidebar();
        $notifications = $viewModel->notifications();
        $sections = $viewModel->sections();
        $widgets = $viewModel->widgets();
        $active_section = $viewModel->activeSection();
        $section_not_found = $viewModel->sectionNotFound();
        $user_menu_html = $this->userMenuRenderer->render($user);

        ob_start();
        include $this->templatePath;
        return (string) ob_get_clean();
    }
}
