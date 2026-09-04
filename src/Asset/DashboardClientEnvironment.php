<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Asset;

interface DashboardClientEnvironment
{
    public function restUrl(): string;
    public function ajaxUrl(): string;
    public function restNonce(): string;
    public function widgetNonce(): string;

    /** @return array<string, string> */
    public function strings(): array;
}
