<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Presentation;

use NeoDashboard\Core\Presentation\WidgetValueColorPolicy;
use PHPUnit\Framework\TestCase;

final class WidgetValueColorPolicyTest extends TestCase
{
    /** @dataProvider valueCases */
    public function testItMapsValuesToBootstrapColors(int|float|string $value, string $expected): void
    {
        self::assertSame($expected, (new WidgetValueColorPolicy())->color($value));
    }

    public function valueCases(): iterable
    {
        yield 'negative' => [-1, 'danger'];
        yield 'zero' => [0, 'secondary'];
        yield 'small' => [9, 'info'];
        yield 'medium lower bound' => [10, 'primary'];
        yield 'medium upper bound' => [49.9, 'primary'];
        yield 'large' => [50, 'success'];
        yield 'numeric string' => ['8', 'info'];
        yield 'text' => ['available', 'primary'];
    }
}
