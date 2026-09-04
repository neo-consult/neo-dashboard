<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Presentation;

use NeoDashboard\Core\Access\UserRoleResolver;
use WP_User;

final readonly class WordPressUserMenuRenderer implements UserMenuRenderer
{
    public function __construct(
        private UserMenuFormatter $formatter,
        private UserRoleResolver $roleResolver,
    ) {}

    public function render(WP_User $user): string
    {
        if (!is_user_logged_in()) {
            $loginUrl = wp_login_url(home_url('/neo-dashboard/'));

            return '<a class="btn btn-outline-primary btn-sm" href="' . esc_url($loginUrl)
                . '"><i class="bi bi-box-arrow-in-right me-1"></i>Anmelden</a>';
        }

        $avatar = get_avatar($user->ID, 40, '', $user->display_name, [
            'class' => 'user-avatar rounded-circle me-2',
            'extra_attr' => 'loading="lazy"',
        ]);
        $initials = $this->formatter->initials($user->display_name, $user->user_login);
        $roleLabel = $this->formatter->roleLabel(
            $this->roleResolver->primary(is_array($user->roles) ? $user->roles : []),
        );
        $roleMarkup = $roleLabel === null
            ? ''
            : '<small class="text-muted d-block">' . esc_html($roleLabel) . '</small>';

        return sprintf(
            '<div class="dropdown text-end">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle user-menu-toggle"
                   data-bs-toggle="dropdown" data-tooltip-title="%s"
                   data-tooltip-placement="bottom" aria-expanded="false">
                    <div class="user-avatar-wrapper position-relative me-2">
                        %s
                        <div class="user-avatar-fallback" style="display: none;">
                            <span class="user-avatar-initials">%s</span>
                        </div>
                    </div>
                    <div class="d-flex flex-column ms-2">
                        <span class="fw-semibold">%s</span>%s
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><h6 class="dropdown-header">%s</h6></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="%s"><i class="bi bi-box-arrow-right me-2"></i>%s</a></li>
                </ul>
            </div>',
            esc_attr(__('Benutzermenü öffnen', 'neo-dashboard-core')),
            $avatar,
            esc_html($initials),
            esc_html($user->display_name),
            $roleMarkup,
            esc_html($user->display_name),
            esc_url(wp_logout_url(home_url('/neo-dashboard/'))),
            esc_html(__('Abmelden', 'neo-dashboard-core')),
        );
    }
}
