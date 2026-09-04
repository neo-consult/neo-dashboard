<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Widget;

use NeoDashboard\Core\Logger;
use NeoDashboard\Core\Widget\WidgetAccess;
use NeoDashboard\Core\Widget\WidgetAjaxController;
use NeoDashboard\Core\Widget\WidgetAjaxRequest;
use NeoDashboard\Core\Widget\WidgetAjaxResponder;
use NeoDashboard\Core\Widget\WidgetCache;
use NeoDashboard\Core\Widget\WidgetProvider;
use NeoDashboard\Core\Widget\WidgetRenderService;
use PHPUnit\Framework\TestCase;

final class WidgetAjaxControllerTest extends TestCase
{
    /** @dataProvider rejectedRequests */
    public function testItRejectsInvalidRequests(
        AjaxWidgetRequest $request,
        string $message,
        int $status,
    ): void {
        $responder = new AjaxWidgetResponder();

        $this->controller(null, true, $request, $responder)->handle();

        self::assertSame([['error', ['message' => $message], $status]], $responder->responses);
        self::assertFalse($request->marked);
    }

    public function rejectedRequests(): iterable
    {
        yield 'anonymous' => [new AjaxWidgetRequest(false, false, 'summary'), 'Keine Berechtigung', 403];
        yield 'invalid nonce' => [new AjaxWidgetRequest(true, false, 'summary'), 'Sicherheitsprüfung fehlgeschlagen', 403];
        yield 'missing widget' => [new AjaxWidgetRequest(true, true, ''), 'Ungültiges Widget', 400];
    }

    /** @dataProvider renderOutcomes */
    public function testItMapsRenderOutcomes(?array $widget, bool $allowed, array $expected): void
    {
        $request = new AjaxWidgetRequest(true, true, 'summary');
        $responder = new AjaxWidgetResponder();

        $this->controller($widget, $allowed, $request, $responder)->handle();

        self::assertSame([$expected], $responder->responses);
        self::assertTrue($request->marked);
    }

    public function renderOutcomes(): iterable
    {
        yield 'success' => [
            ['callback' => static function (): void { echo 'widget'; }],
            true,
            ['success', ['html' => 'widget'], 200],
        ];
        yield 'not found' => [null, true, ['error', ['message' => 'Widget nicht gefunden'], 404]];
        yield 'forbidden' => [['callback' => static fn() => null], false, ['error', ['message' => 'Keine Berechtigung'], 403]];
        yield 'invalid callback' => [['callback' => null], true, ['error', ['message' => 'Widget-Callback nicht verfügbar'], 500]];
        yield 'failure' => [[
            'callback' => static function (): void { throw new \RuntimeException('broken'); },
        ], true, ['error', ['message' => 'Fehler beim Laden des Widgets'], 500]];
    }

    private function controller(
        ?array $widget,
        bool $allowed,
        AjaxWidgetRequest $request,
        AjaxWidgetResponder $responder,
    ): WidgetAjaxController {
        return new WidgetAjaxController(
            new WidgetRenderService(
                new AjaxWidgetProvider($widget),
                new AjaxWidgetAccess($allowed),
                new AjaxWidgetCache(),
            ),
            $request,
            $responder,
            new Logger(),
            false,
        );
    }
}

final class AjaxWidgetRequest implements WidgetAjaxRequest
{
    public bool $marked = false;

    public function __construct(
        private bool $authenticated,
        private bool $validNonce,
        private string $id,
    ) {}

    public function isAuthenticated(): bool { return $this->authenticated; }
    public function hasValidNonce(): bool { return $this->validNonce; }
    public function widgetId(): string { return $this->id; }
    public function markWidgetRequest(): void { $this->marked = true; }
}

final class AjaxWidgetResponder implements WidgetAjaxResponder
{
    /** @var list<array{string, array<string, mixed>, int}> */
    public array $responses = [];

    public function success(array $data): void { $this->responses[] = ['success', $data, 200]; }
    public function error(array $data, int $status): void { $this->responses[] = ['error', $data, $status]; }
}

final readonly class AjaxWidgetProvider implements WidgetProvider
{
    public function __construct(private ?array $widget) {}
    public function find(string $widgetId): ?array { return $this->widget; }
}

final readonly class AjaxWidgetAccess implements WidgetAccess
{
    public function __construct(private bool $allowed) {}
    public function allows(array $widget): bool { return $this->allowed; }
}

final class AjaxWidgetCache implements WidgetCache
{
    public function get(string $widgetId, int $ttl): ?string { return null; }
    public function put(string $widgetId, string $html, int $ttl): void {}
}
