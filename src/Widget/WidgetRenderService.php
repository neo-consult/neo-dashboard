<?php
declare(strict_types=1);
namespace NeoDashboard\Core\Widget;

final class WidgetRenderService
{
    public function __construct(
        private readonly WidgetProvider $widgets,
        private readonly WidgetAccess $access,
        private readonly WidgetCache $cache,
    ) {}

    public function render(string $widgetId): WidgetRenderResult
    {
        $widget = $this->widgets->find($widgetId);
        if ($widget === null) {
            return WidgetRenderResult::notFound();
        }
        if (!$this->access->allows($widget)) {
            return WidgetRenderResult::forbidden();
        }
        $ttl = max(0, (int) ($widget['cache_ttl'] ?? 0));
        if ($ttl > 0 && ($cached = $this->cache->get($widgetId, $ttl)) !== null) {
            return WidgetRenderResult::success($cached);
        }
        $callback = $widget['callback'] ?? null;
        if (!is_callable($callback)) {
            return WidgetRenderResult::invalidCallback();
        }
        try {
            ob_start();
            call_user_func($callback);
            $html = (string) ob_get_clean();
        } catch (\Throwable $error) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            return WidgetRenderResult::failed($error);
        }
        if ($ttl > 0) {
            $this->cache->put($widgetId, $html, $ttl);
        }
        return WidgetRenderResult::success($html);
    }
}
