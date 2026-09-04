<?php
declare(strict_types=1);
namespace NeoDashboard\Core\Widget;

final class WordPressWidgetCache implements WidgetCache
{
    public function get(string $widgetId, int $ttl): ?string
    {
        $cached = get_transient($this->key($widgetId));
        return $cached === false ? null : (string) $cached;
    }

    public function put(string $widgetId, string $html, int $ttl): void
    {
        set_transient($this->key($widgetId), $html, $ttl);
    }

    private function key(string $widgetId): string
    {
        return 'neo_dashboard_widget_' . $widgetId . '_' . get_current_user_id() . '_' . get_user_locale();
    }
}
