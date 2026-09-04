<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Widget;

final class WordPressWidgetAjaxResponder implements WidgetAjaxResponder
{
    public function success(array $data): void { wp_send_json_success($data); }
    public function error(array $data, int $status): void { wp_send_json_error($data, $status); }
}
