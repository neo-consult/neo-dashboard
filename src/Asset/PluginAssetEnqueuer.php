<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Asset;

use NeoDashboard\Core\PerformanceTimer;

final readonly class PluginAssetEnqueuer
{
    public function __construct(
        private AssetCatalog $catalog,
        private PerformanceTimer $performance,
        private PluginAssetPlatform $platform,
    ) {}

    public function enqueue(string $context, string $type): void
    {
        $this->performance->start('assets', "load_{$type}");
        foreach ($this->catalog->forContext($context, $type) as $entry) {
            $this->enqueueOne($entry['definition']);
        }
        $this->performance->stop('assets', "load_{$type}");
    }

    private function enqueueOne(AssetDefinition $asset): void
    {
        if ($asset->type === 'css') {
            $this->platform->enqueueStyle($asset->handle, $asset->source, $asset->dependencies, $asset->version);
            return;
        }

        $this->platform->enqueueScript(
            $asset->handle,
            $asset->source,
            $asset->dependencies,
            $asset->version,
            $asset->inFooter,
        );
        $this->localize($asset);
    }

    private function localize(AssetDefinition $asset): void
    {
        $localization = $asset->localize;
        if ($localization === null) {
            return;
        }

        $objectName = $localization['object_name'] ?? null;
        $data = $localization['data'] ?? [];
        if (is_callable($data)) {
            $data = $data();
        }
        if (!is_string($objectName) || $objectName === '' || !is_array($data) || $data === []) {
            return;
        }

        $data = array_replace(
            $data,
            $this->platform->existingLocalization($asset->handle, $objectName),
        );
        $this->platform->localize($asset->handle, $objectName, $data);
    }
}
