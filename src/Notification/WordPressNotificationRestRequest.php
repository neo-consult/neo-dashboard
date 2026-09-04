<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Notification;

use WP_REST_Request;

final readonly class WordPressNotificationRestRequest implements NotificationRestRequest
{
    public function __construct(private WP_REST_Request $request) {}

    public function notificationId(): string
    {
        return sanitize_key((string) $this->request['id']);
    }

    public function userId(): int
    {
        return get_current_user_id();
    }
}
