<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Presentation;

final class WidgetValueColorPolicy
{
    public function color(int|float|string $value): string
    {
        if (!is_numeric($value)) {
            return 'primary';
        }

        $number = (float) $value;
        return match (true) {
            $number < 0 => 'danger',
            $number === 0.0 => 'secondary',
            $number < 10 => 'info',
            $number < 50 => 'primary',
            default => 'success',
        };
    }
}
