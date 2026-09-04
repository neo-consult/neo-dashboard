<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Language;

final class WordPressLanguageAjaxRequest implements LanguageAjaxRequest
{
    public function hasValidNonce(): bool
    {
        return isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'wp_rest') !== false;
    }

    public function isAuthenticated(): bool
    {
        return is_user_logged_in();
    }

    public function languageCode(): string
    {
        return sanitize_text_field($_POST['language'] ?? '');
    }
}
