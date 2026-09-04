<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Language;

final class PluginLanguageSelector
{
    /** @param array<string, string> $supported */
    public function select(string $current, string $default, array $supported): string
    {
        if (isset($supported[$current])) {
            return $current;
        }
        if (isset($supported[$default])) {
            return $default;
        }
        return array_key_first($supported) ?? $default;
    }
}
