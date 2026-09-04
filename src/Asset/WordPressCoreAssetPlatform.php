<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Asset;

final class WordPressCoreAssetPlatform implements CoreAssetPlatform
{
    public function baseUrl(): string
    {
        return plugin_dir_url(NEO_DASHBOARD_PLUGIN_FILE);
    }

    public function exists(string $relativePath): bool
    {
        return is_file(plugin_dir_path(NEO_DASHBOARD_PLUGIN_FILE) . $relativePath);
    }

    public function enqueueStyle(string $handle, string $source, array $dependencies, string $version): void
    {
        wp_enqueue_style($handle, $source, $dependencies, $version);
    }

    public function enqueueScript(string $handle, string $source, array $dependencies, string $version): void
    {
        wp_enqueue_script($handle, $source, $dependencies, $version, true);
    }

    public function localize(string $handle, string $objectName, array $data): void
    {
        wp_localize_script($handle, $objectName, $data);
    }
}
