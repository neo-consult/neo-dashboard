<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Widget;

use NeoDashboard\Core\Widget\WidgetAccess;
use NeoDashboard\Core\Widget\WidgetCache;
use NeoDashboard\Core\Widget\WidgetProvider;
use NeoDashboard\Core\Widget\WidgetRenderService;
use NeoDashboard\Core\Widget\WidgetRenderStatus;
use PHPUnit\Framework\TestCase;

final class WidgetRenderServiceTest extends TestCase
{
    public function testItDistinguishesMissingForbiddenAndInvalidWidgets(): void
    {
        self::assertSame(WidgetRenderStatus::NOT_FOUND, $this->service(null)->render('x')->status());
        self::assertSame(WidgetRenderStatus::FORBIDDEN, $this->service(['callback' => fn() => null], false)->render('x')->status());
        self::assertSame(WidgetRenderStatus::INVALID_CALLBACK, $this->service(['callback' => null])->render('x')->status());
    }

    public function testItRendersAndCachesWidgetHtml(): void
    {
        $cache = new FakeWidgetCache();
        $service = $this->service(['callback' => static function (): void { echo 'widget'; }, 'cache_ttl' => 60], true, $cache);
        self::assertSame('widget', $service->render('summary')->html());
        self::assertSame('widget', $cache->stored['summary']);
        $cache->values['summary'] = 'cached';
        self::assertSame('cached', $service->render('summary')->html());
    }

    public function testItConvertsCallbackExceptionsIntoFailureResults(): void
    {
        $result = $this->service(['callback' => static function (): void { throw new \RuntimeException('broken'); }])->render('x');
        self::assertSame(WidgetRenderStatus::FAILED, $result->status());
        self::assertSame('broken', $result->error()?->getMessage());
    }

    private function service(?array $widget, bool $allowed = true, ?WidgetCache $cache = null): WidgetRenderService
    {
        return new WidgetRenderService(
            new FakeWidgetProvider($widget),
            new FakeWidgetAccess($allowed),
            $cache ?? new FakeWidgetCache(),
        );
    }
}

final class FakeWidgetProvider implements WidgetProvider
{
    public function __construct(private readonly ?array $widget) {}
    public function find(string $widgetId): ?array { return $this->widget; }
}

final class FakeWidgetAccess implements WidgetAccess
{
    public function __construct(private readonly bool $allowed) {}
    public function allows(array $widget): bool { return $this->allowed; }
}

final class FakeWidgetCache implements WidgetCache
{
    public array $values = [];
    public array $stored = [];
    public function get(string $widgetId, int $ttl): ?string { return $this->values[$widgetId] ?? null; }
    public function put(string $widgetId, string $html, int $ttl): void { $this->stored[$widgetId] = $html; }
}
