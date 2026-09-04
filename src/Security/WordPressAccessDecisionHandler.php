<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Security;

use NeoDashboard\Core\Access\UserRoleResolver;
use NeoDashboard\Core\Http\RequestContext;

/**
 * Executes access decisions at the WordPress boundary.
 */
final class WordPressAccessDecisionHandler implements AccessDecisionHandler
{
    public function __construct(
        private readonly UserRoleResolver $roleResolver,
    ) {}

    public function handle(AccessDecision $decision, RequestContext $context): void
    {
        match ($decision) {
            AccessDecision::Allow => null,
            AccessDecision::RedirectToLogin => $this->redirectToLogin($context),
            AccessDecision::RedirectToDashboard => $this->redirectToDashboard(),
            AccessDecision::Deny => $this->deny(),
        };
    }

    private function redirectToLogin(RequestContext $context): never
    {
        $returnUrl = home_url($context->path);
        $loginUrl = wp_login_url($returnUrl);

        wp_safe_redirect($loginUrl);
        exit;
    }

    private function redirectToDashboard(): never
    {
        wp_safe_redirect(home_url('/neo-dashboard'));
        exit;
    }

    private function deny(): never
    {
        status_header(403);

        $templatePath = defined('NEO_DASHBOARD_TEMPLATE_PATH')
            ? NEO_DASHBOARD_TEMPLATE_PATH . 'access-denied.php'
            : '';

        if ($templatePath !== '' && is_file($templatePath)) {
            $is_logged_in = is_user_logged_in();
            $user = $is_logged_in ? wp_get_current_user() : null;
            $roles = $user !== null && is_array($user->roles) ? $user->roles : [];
            $current_role = $this->roleResolver->primary($roles);
            $is_neo_admin = $this->roleResolver->isAdministrator($roles);
            include $templatePath;
            exit;
        }

        wp_die(
            esc_html__('You are not allowed to access this page.', 'neo-dashboard-core'),
            esc_html__('Access denied', 'neo-dashboard-core'),
            ['response' => 403],
        );
    }
}
