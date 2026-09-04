<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Security;

use NeoDashboard\Core\Http\RequestContext;

/**
 * Makes access decisions without calling WordPress functions or performing IO.
 */
final readonly class DashboardAccessPolicy
{
    public function __construct(
        private PublicRouteRegistry $publicRoutes,
    ) {}

    public function decide(
        RequestContext $context,
        bool $canAccessDashboard,
        bool $canAccessAdmin,
    ): AccessDecision {
        // These contexts have their own authentication and permission handling.
        if (
            $context->isSystem()
            || $context->isLogin
            || $context->isAdminPost
            || $context->isAjax()
            || $context->isRest()
        ) {
            return AccessDecision::Allow;
        }

        if ($this->publicRoutes->isPublic($context->path)) {
            return AccessDecision::Allow;
        }

        // WordPress itself handles authentication for the administration area.
        if ($context->isAdmin()) {
            if ($context->isAuthenticated && $canAccessDashboard && !$canAccessAdmin) {
                return AccessDecision::RedirectToDashboard;
            }

            return AccessDecision::Allow;
        }

        if (!$context->isAuthenticated) {
            return AccessDecision::RedirectToLogin;
        }

        if ($context->isDashboard) {
            return $canAccessDashboard || $canAccessAdmin
                ? AccessDecision::Allow
                : AccessDecision::Deny;
        }

        if ($canAccessDashboard && !$canAccessAdmin) {
            return AccessDecision::RedirectToDashboard;
        }

        return AccessDecision::Allow;
    }
}
