<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Notification;

final class WordPressNotificationUserState implements NotificationUserState
{
    private const USER_META_KEY = 'neo_dismissed_notifications';

    public function current(): NotificationUserContext
    {
        $user = wp_get_current_user();
        $dismissed = get_user_meta($user->ID, self::USER_META_KEY, true);

        return new NotificationUserContext(
            (int) $user->ID,
            is_array($user->roles) ? $user->roles : [],
            is_array($dismissed) ? $dismissed : [],
            time(),
        );
    }

    public function dismiss(string $id, int $userId): bool
    {
        $dismissed = get_user_meta($userId, self::USER_META_KEY, true);
        $dismissed = is_array($dismissed) ? $dismissed : [];

        if (in_array($id, $dismissed, true)) {
            return false;
        }

        $dismissed[] = $id;
        update_user_meta($userId, self::USER_META_KEY, $dismissed);

        return true;
    }
}
