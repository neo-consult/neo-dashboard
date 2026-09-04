<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Widget;

use NeoDashboard\Core\Logger;

final readonly class WidgetAjaxController
{
    public function __construct(
        private WidgetRenderService $renderer,
        private WidgetAjaxRequest $request,
        private WidgetAjaxResponder $responder,
        private Logger $logger,
        private bool $debug,
    ) {}

    public function handle(): void
    {
        if (!$this->request->isAuthenticated()) {
            $this->error('Keine Berechtigung', 403);
            return;
        }
        if (!$this->request->hasValidNonce()) {
            $this->error('Sicherheitsprüfung fehlgeschlagen', 403);
            return;
        }
        $widgetId = $this->request->widgetId();
        if ($widgetId === '') {
            $this->error('Ungültiges Widget', 400);
            return;
        }

        $this->request->markWidgetRequest();
        $result = $this->renderer->render($widgetId);
        match ($result->status()) {
            WidgetRenderStatus::SUCCESS => $this->responder->success(['html' => $result->html()]),
            WidgetRenderStatus::NOT_FOUND => $this->error('Widget nicht gefunden', 404),
            WidgetRenderStatus::FORBIDDEN => $this->error('Keine Berechtigung', 403),
            WidgetRenderStatus::INVALID_CALLBACK => $this->error('Widget-Callback nicht verfügbar', 500),
            WidgetRenderStatus::FAILED => $this->renderFailure($widgetId, $result),
        };
    }

    private function renderFailure(string $widgetId, WidgetRenderResult $result): void
    {
        if ($this->debug) {
            $this->logger->error('Widget callback error', [
                'id' => $widgetId,
                'message' => $result->error()?->getMessage(),
            ]);
        }
        $this->error('Fehler beim Laden des Widgets', 500);
    }

    private function error(string $message, int $status): void
    {
        $this->responder->error(['message' => $message], $status);
    }
}
