<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Presentation;

use InvalidArgumentException;
use NeoDashboard\Core\Presentation\WidgetComponentRenderer;
use NeoDashboard\Core\Presentation\WidgetValueColorPolicy;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class WidgetComponentRendererTest extends TestCase
{
    public function testItRejectsUnknownComponents(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new WidgetComponentRenderer(__DIR__, new WidgetValueColorPolicy()))->render('../secret', []);
    }

    public function testItRejectsMissingKnownTemplates(): void
    {
        $this->expectException(RuntimeException::class);
        (new WidgetComponentRenderer(__DIR__, new WidgetValueColorPolicy()))->render('alert', []);
    }
}
