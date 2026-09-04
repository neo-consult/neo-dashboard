<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Widget;

final class WordPressWidgetAjaxRequest implements WidgetAjaxRequest
{
    public function isAuthenticated(): bool { return is_user_logged_in(); }

    public function hasValidNonce(): bool
    {
        return check_ajax_referer('neo_dashboard_widget', 'nonce', false) !== false;
    }

    public function widgetId(): string
    {
        return sanitize_key((string) ($_POST['widget_id'] ?? ''));
    }

    public function markWidgetRequest(): void
    {
        if (!defined('NEO_DASHBOARD_WIDGET_AJAX')) {
            define('NEO_DASHBOARD_WIDGET_AJAX', true);
        }
    }
}
