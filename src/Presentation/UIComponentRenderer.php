<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Presentation;

use RuntimeException;
use Throwable;

final readonly class UIComponentRenderer
{
    public function __construct(private string $templateDirectory) {}

    /** @param array<string, mixed> $arguments */
    public function render(string $componentPath, array $arguments = []): string
    {
        $componentFile = rtrim($this->templateDirectory, '/\\')
            . DIRECTORY_SEPARATOR . $componentPath . '.php';
        if (!is_file($componentFile)) {
            throw new RuntimeException("UI component not found: {$componentPath}");
        }

        $args = $arguments;
        $bufferLevel = ob_get_level();
        ob_start();

        try {
            include $componentFile;
            return (string) ob_get_clean();
        } catch (Throwable $exception) {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }
            throw $exception;
        }
    }

    /** @param array<string, mixed> $arguments */
    public function modal(array $arguments): string
    {
        return $this->render('layout/modal', $arguments);
    }
}
