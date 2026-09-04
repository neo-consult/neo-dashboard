<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Asset;

use InvalidArgumentException;

final readonly class PluginAssetDefinition
{
    /** @param list<AssetDefinition> $assets */
    private function __construct(
        private string $pluginId,
        private array $assets,
    ) {}

    /** @param array<string, mixed> $assets */
    public static function fromArray(string $pluginId, array $assets): self
    {
        if (preg_match('/^[a-z0-9_-]+$/', $pluginId) !== 1) {
            throw new InvalidArgumentException('Plugin asset id must be a normalized slug.');
        }

        $definitions = [];
        foreach (['css', 'js'] as $type) {
            $typeAssets = $assets[$type] ?? [];
            if (!is_array($typeAssets)) {
                throw new InvalidArgumentException("Plugin {$type} assets must be an array.");
            }
            foreach ($typeAssets as $handle => $config) {
                if (!is_string($handle) || $handle === '' || !is_array($config)) {
                    throw new InvalidArgumentException('Every plugin asset requires a handle and configuration.');
                }
                $definitions[] = AssetDefinition::fromArray($handle, $type, $config);
            }
        }

        return new self($pluginId, $definitions);
    }

    public function pluginId(): string { return $this->pluginId; }

    /** @return list<AssetDefinition> */
    public function assets(): array { return $this->assets; }

    public function count(string $type): int
    {
        return count(array_filter(
            $this->assets,
            static fn(AssetDefinition $asset): bool => $asset->type === $type,
        ));
    }
}
