<?php
declare(strict_types=1);
namespace NeoDashboard\Core\Widget;

final class WidgetRenderResult
{
    private function __construct(
        private readonly WidgetRenderStatus $status,
        private readonly string $html = '',
        private readonly ?\Throwable $error = null,
    ) {}

    public static function success(string $html): self { return new self(WidgetRenderStatus::SUCCESS, $html); }
    public static function notFound(): self { return new self(WidgetRenderStatus::NOT_FOUND); }
    public static function forbidden(): self { return new self(WidgetRenderStatus::FORBIDDEN); }
    public static function invalidCallback(): self { return new self(WidgetRenderStatus::INVALID_CALLBACK); }
    public static function failed(\Throwable $error): self { return new self(WidgetRenderStatus::FAILED, '', $error); }
    public function status(): WidgetRenderStatus { return $this->status; }
    public function html(): string { return $this->html; }
    public function error(): ?\Throwable { return $this->error; }
}
