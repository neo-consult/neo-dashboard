<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Language;

final class WordPressUserLanguageStore implements UserLanguageStore
{
    private const META_KEY = 'neo_dashboard_language';

    public function load(): ?string
    {
        if (!is_user_logged_in()) {
            return null;
        }

        $language = (string) get_user_meta(get_current_user_id(), self::META_KEY, true);
        return $language !== '' ? $language : null;
    }

    public function save(string $languageCode): void
    {
        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), self::META_KEY, $languageCode);
        }
    }

    public function identity(): string
    {
        return is_user_logged_in() ? (string) get_current_user_id() : 'anonymous';
    }
}
