<?php

declare(strict_types=1);

namespace {
    if (!function_exists('esc_attr')) {
        function esc_attr(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES); }
    }
    if (!function_exists('esc_html')) {
        function esc_html(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES); }
    }
    if (!function_exists('esc_url')) {
        function esc_url(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES); }
    }
}

namespace NeoDashboard\Core\Tests\Unit\Presentation {
    use NeoDashboard\Core\Presentation\WidgetComponentRenderer;
    use NeoDashboard\Core\Presentation\WidgetValueColorPolicy;
    use PHPUnit\Framework\TestCase;

    final class WidgetComponentRendererContractTest extends TestCase
    {
        private WidgetComponentRenderer $renderer;

        protected function setUp(): void
        {
            $this->renderer = new WidgetComponentRenderer(
                dirname(__DIR__, 3) . '/templates/components/widgets',
                new WidgetValueColorPolicy(),
            );
        }

        public function testItRendersNestedStatGridWithAutomaticColors(): void
        {
            $html = $this->renderer->render('stat-grid', [
                'columns' => 2,
                'items' => [[
                    'icon' => 'bi-building',
                    'label' => 'Organisationen',
                    'value' => 3,
                    'value_color' => 'auto',
                ]],
            ]);

            self::assertStringContainsString('widget-stat-grid', $html);
            self::assertStringContainsString('widget-stat-item', $html);
            self::assertStringContainsString('bg-info', $html);
            self::assertStringContainsString('Organisationen', $html);
        }

        public function testItRendersNestedActionRowAndButton(): void
        {
            $html = $this->renderer->render('action-row', [
                'actions' => [[
                    'href' => '/contacts',
                    'text' => 'Kontakte',
                    'icon' => 'bi-people',
                ]],
            ]);

            self::assertStringContainsString('widget-action-row', $html);
            self::assertStringContainsString('href="/contacts"', $html);
            self::assertStringContainsString('Kontakte', $html);
        }
    }
}
