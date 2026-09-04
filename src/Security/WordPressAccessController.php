<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Security;

final readonly class WordPressAccessController
{
    public function __construct(private WordPressAccessEnforcer $enforcer) {}

    public function registerHooks(): void
    {
        add_action('template_redirect', [$this, 'enforce'], 1);
        add_filter('login_redirect', [$this, 'loginRedirect'], 10, 3);
        add_action('admin_init', [$this, 'enforce'], 1);
        add_action('after_setup_theme', [$this, 'hideAdminBar']);
    }

    public function enforce(): void
    {
        $this->enforcer->enforce();
    }

    public function loginRedirect(mixed $redirectTo, mixed $request, mixed $user): string
    {
        if (isset($user->errors)) {
            return (string) $redirectTo;
        }

        if (user_can($user, 'manage_options')) {
            return admin_url();
        }

        if (user_can($user, 'neo_dashboard_access')) {
            return home_url('/neo-dashboard');
        }

        return home_url();
    }

    public function hideAdminBar(): void
    {
        if (current_user_can('neo_dashboard_access') && !current_user_can('manage_options')) {
            show_admin_bar(false);
        }
    }
}
