<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Presentation;

use NeoDashboard\Core\Presentation\UIComponentRenderer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class UIComponentRendererTest extends TestCase
{
    public function testItRendersAComponentWithArguments(): void
    {
        $renderer = new UIComponentRenderer(dirname(__DIR__, 2) . '/Fixtures');

        self::assertSame('Component: Dashboard', $renderer->render('ui-component', [
            'label' => 'Dashboard',
        ]));
    }

    public function testItRejectsAnUnknownComponent(): void
    {
        $renderer = new UIComponentRenderer(dirname(__DIR__, 2) . '/Fixtures');

        $this->expectException(RuntimeException::class);
        $renderer->render('missing');
    }
}
