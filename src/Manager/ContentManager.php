<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Manager;

use WP_User;
use NeoDashboard\Core\Logger;
use NeoDashboard\Core\Extension\Registry\DashboardRegistries;
use NeoDashboard\Core\Access\RoleAccessPolicy;
use NeoDashboard\Core\Presentation\DashboardRenderer;
use NeoDashboard\Core\Presentation\DashboardResponseHandler;
use NeoDashboard\Core\Presentation\DashboardViewModelFactory;
use NeoDashboard\Core\Http\DashboardRequest;

class ContentManager
{
    private DashboardViewModelFactory $viewModelFactory;
    private DashboardResponseHandler $responseHandler;
    private DashboardRenderer $renderer;
    private RoleAccessPolicy $accessPolicy;
    private DashboardRegistries $registries;

    public function __construct(
        RoleAccessPolicy $accessPolicy,
        DashboardViewModelFactory $viewModelFactory,
        DashboardResponseHandler $responseHandler,
        DashboardRenderer $renderer,
        DashboardRegistries $registries,
        private DashboardRequest $request,
        private Logger $logger,
    ) {
        $this->registries = $registries;
        $this->viewModelFactory = $viewModelFactory;
        $this->responseHandler = $responseHandler;
        $this->renderer = $renderer;
        $this->accessPolicy = $accessPolicy;
    }

    /**
     * Aufruf in Template dasboard_blanc.php
     * Registriert die Hooks für unser eigenständiges Blank‑Template:
     * - neo_dashboard_head   → CSS
     * - neo_dashboard_footer → JS
     * - show_admin_bar       → Admin‑Bar ausblenden auf Dashboard‑Seiten
     */
    public function registerDefault(): void
    {
        add_action( 'neo_dashboard_body_content',   [ $this, 'render' ] );
    }

    
    /**
     * Haupt-Renderer für das Dashboard. Gibt Sidebar, Sections, Notifications und Widgets aus.
     */
    public function render(): void
    {
        $user = $this->request->user();
        $section = $this->request->section();

        $viewModel = $this->viewModelFactory->create(
            (string) $section,
            fn(array $definition): bool => $this->accessPolicy->allows(
                is_array($user->roles) ? $user->roles : [],
                $definition['roles'] ?? null,
            ),
        );
        $this->responseHandler->apply($viewModel);

        echo $this->renderer->render($viewModel, $user);
    }

}
