<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Presentation;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

final readonly class WidgetComponentRenderer
{
    private const TEMPLATES = [
        'stat-item' => 'stat-item.php',
        'stat-grid' => 'stat-grid.php',
        'action-row' => 'action-row.php',
        'action-button' => 'action-button.php',
        'empty-state' => 'empty-state.php',
        'list' => 'list.php',
        'alert' => 'alert.php',
        'header' => 'header.php',
    ];

    public function __construct(
        private string $templateDirectory,
        private WidgetValueColorPolicy $valueColorPolicy,
    ) {}

    /** @param array<string, mixed> $variables */
    public function render(string $component, array $variables): string
    {
        if (!isset(self::TEMPLATES[$component])) {
            throw new InvalidArgumentException("Unknown widget component: {$component}");
        }

        $template = rtrim($this->templateDirectory, '/\\') . DIRECTORY_SEPARATOR
            . self::TEMPLATES[$component];
        if (!is_file($template)) {
            throw new RuntimeException("Widget component template not found: {$component}");
        }

        $variables = $this->normalize($component, $variables);
        $bufferLevel = ob_get_level();
        ob_start();

        try {
            extract($variables, EXTR_SKIP);
            include $template;
            return (string) ob_get_clean();
        } catch (Throwable $exception) {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }
            throw $exception;
        }
    }

    /** @param array<string, mixed> $variables @return array<string, mixed> */
    private function normalize(string $component, array $variables): array
    {
        return match ($component) {
            'stat-item' => $this->normalizeStatItem($variables),
            'stat-grid' => [
                'items' => $variables['items'] ?? [],
                'columns' => (int) ($variables['columns'] ?? 2),
            ],
            'action-row' => [
                'actions' => $variables['actions'] ?? [],
                'layout' => $variables['layout'] ?? 'inline',
                'align' => $variables['align'] ?? 'start',
            ],
            'action-button' => [
                'href' => $variables['href'] ?? '#',
                'text' => $variables['text'] ?? '',
                'icon' => $variables['icon'] ?? null,
                'class' => $variables['class'] ?? 'btn-primary',
                'id' => $variables['id'] ?? null,
                'style' => $variables['style'] ?? null,
                'onclick' => $variables['onclick'] ?? null,
                'title' => $variables['title'] ?? null,
            ],
            'empty-state' => [
                'icon' => $variables['icon'] ?? 'bi-inbox',
                'message' => $variables['message'] ?? 'Keine Daten vorhanden.',
                'action' => $variables['action'] ?? null,
            ],
            'list' => [
                'items' => $variables['items'] ?? [],
                'flush' => $variables['flush'] ?? true,
            ],
            'alert' => [
                'type' => $variables['type'] ?? 'info',
                'icon' => $variables['icon'] ?? 'bi-info-circle',
                'message' => $variables['message'] ?? '',
            ],
            'header' => [
                'icon' => $variables['icon'] ?? 'bi-grid',
                'label' => $variables['label'] ?? '',
                'actions' => $variables['actions'] ?? null,
                'class' => $variables['class'] ?? '',
            ],
        };
    }

    /** @param array<string, mixed> $variables @return array<string, mixed> */
    private function normalizeStatItem(array $variables): array
    {
        $value = $variables['value'] ?? 0;
        $valueColor = $variables['value_color'] ?? null;
        if ($valueColor === null || $valueColor === 'auto') {
            $valueColor = $this->valueColorPolicy->color($value);
        }

        return [
            'icon' => $variables['icon'] ?? '',
            'label' => $variables['label'] ?? '',
            'value' => $value,
            'value_color' => $valueColor,
            'icon_color' => $variables['icon_color'] ?? $valueColor,
            'action' => $variables['action'] ?? null,
        ];
    }

}
