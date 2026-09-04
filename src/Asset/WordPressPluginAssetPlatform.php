<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Asset;

final class WordPressPluginAssetPlatform implements PluginAssetPlatform
{
    public function enqueueStyle(string $handle, string $source, array $dependencies, string $version): void
    {
        wp_enqueue_style($handle, $source, $dependencies, $version);
    }

    public function enqueueScript(
        string $handle,
        string $source,
        array $dependencies,
        string $version,
        bool $inFooter,
    ): void {
        wp_enqueue_script($handle, $source, $dependencies, $version, $inFooter);
    }

    public function existingLocalization(string $handle, string $objectName): array
    {
        $script = $GLOBALS['wp_scripts']->registered[$handle]->extra['data'] ?? null;
        if (!is_string($script)) {
            return [];
        }

        if (preg_match('/var\s+' . preg_quote($objectName, '/') . '\s*=\s*({.*?});/s', $script, $matches) !== 1) {
            return [];
        }

        $data = json_decode($matches[1], true);
        return is_array($data) ? $data : [];
    }

    public function localize(string $handle, string $objectName, array $data): void
    {
        wp_localize_script($handle, $objectName, $data);
    }
}
