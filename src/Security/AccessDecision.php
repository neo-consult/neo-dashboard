<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Security;

/**
 * Framework-independent outcome of a dashboard access decision.
 */
enum AccessDecision: string
{
    case Allow = 'allow';
    case RedirectToLogin = 'redirect_to_login';
    case RedirectToDashboard = 'redirect_to_dashboard';
    case Deny = 'deny';
}
