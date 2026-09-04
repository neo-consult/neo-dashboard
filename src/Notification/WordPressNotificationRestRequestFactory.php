<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Notification;

use InvalidArgumentException;
use WP_REST_Request;

final class WordPressNotificationRestRequestFactory implements NotificationRestRequestFactory
{
    public function create(mixed $request): NotificationRestRequest
    {
        if (!$request instanceof WP_REST_Request) {
            throw new InvalidArgumentException('Expected a WordPress REST request.');
        }

        return new WordPressNotificationRestRequest($request);
    }
}
