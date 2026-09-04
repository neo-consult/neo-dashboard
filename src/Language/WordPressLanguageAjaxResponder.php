<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Language;

final class WordPressLanguageAjaxResponder implements LanguageAjaxResponder
{
    public function success(array $data): void
    {
        wp_send_json_success($data);
    }

    public function error(array $data): void
    {
        wp_send_json_error($data);
    }
}
