<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Widget;

interface WidgetAjaxRequest
{
    public function isAuthenticated(): bool;
    public function hasValidNonce(): bool;
    public function widgetId(): string;
    public function markWidgetRequest(): void;
}
