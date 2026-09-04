<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Asset;

final class AssetCatalog
{
    /** @var array<string, array<string, array<string, array{plugin: string, definition: AssetDefinition}>>> */
    private array $index = [];

    public function registerPlugin(PluginAssetDefinition $definition): void
    {
        $pluginId = $definition->pluginId();
        $this->removePlugin($pluginId);
        foreach ($definition->assets() as $asset) {
            $this->indexAsset($pluginId, $asset);
        }
    }

    /** @return array<string, array{plugin: string, definition: AssetDefinition}> */
    public function forContext(string $context, string $type): array
    {
        if (!in_array($type, ['css', 'js'], true)) {
            return [];
        }
        return array_merge(
            $this->index[$context][$type] ?? [],
            $this->index['*'][$type] ?? [],
        );
    }

    private function indexAsset(string $pluginId, AssetDefinition $asset): void
    {
        foreach ($asset->contexts as $context) {
            $this->index[$context][$asset->type][$asset->handle] = [
                'plugin' => $pluginId,
                'definition' => $asset,
            ];
        }
    }

    private function removePlugin(string $pluginId): void
    {
        foreach ($this->index as $context => $types) {
            foreach ($types as $type => $assets) {
                foreach ($assets as $handle => $asset) {
                    if ($asset['plugin'] === $pluginId) {
                        unset($this->index[$context][$type][$handle]);
                    }
                }
            }
        }
    }
}
