<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Asset;

final class CoreAssetEnqueuer
{
    private const BOOTSTRAP_VERSION = '5.3.2';
    private const ICONS_VERSION = '1.10.5';
    private const VENDOR_DIR = 'assets/vendor';

    private bool $localized = false;

    public function __construct(
        private readonly CoreAssetPlatform $platform,
        private readonly CoreAssetManifest $manifest,
        private readonly CoreAssetLocalizationData $localization,
        private readonly string $pluginVersion,
    ) {}

    public function enqueue(): void
    {
        $baseUrl = $this->platform->baseUrl();
        $this->platform->enqueueStyle(
            'neo-dashboard-bootstrap',
            $baseUrl . self::VENDOR_DIR . '/bootstrap/' . self::BOOTSTRAP_VERSION . '/css/bootstrap.min.css',
            [],
            self::BOOTSTRAP_VERSION,
        );
        $this->platform->enqueueStyle(
            'neo-dashboard-bootstrap-icons',
            $baseUrl . self::VENDOR_DIR . '/bootstrap-icons/' . self::ICONS_VERSION . '/font/bootstrap-icons.css',
            [],
            self::ICONS_VERSION,
        );
        $this->platform->enqueueScript(
            'neo-dashboard-bootstrap',
            $baseUrl . self::VENDOR_DIR . '/bootstrap/' . self::BOOTSTRAP_VERSION . '/js/bootstrap.bundle.min.js',
            ['jquery'],
            self::BOOTSTRAP_VERSION,
        );

        foreach ($this->manifest->localAssets() as $asset) {
            $this->enqueueLocal($asset, $baseUrl);
        }
    }

    /** @param array{path: string, type: string, handle: string, deps: list<string>} $asset */
    private function enqueueLocal(array $asset, string $baseUrl): void
    {
        if (!$this->platform->exists($asset['path'])) {
            return;
        }
        if ($asset['type'] === 'style') {
            $this->platform->enqueueStyle($asset['handle'], $baseUrl . $asset['path'], $asset['deps'], $this->pluginVersion);
            return;
        }
        $this->platform->enqueueScript($asset['handle'], $baseUrl . $asset['path'], $asset['deps'], $this->pluginVersion);
        if ($asset['handle'] === 'neo-dashboard-core' && !$this->localized) {
            $this->localized = true;
            $this->platform->localize('neo-dashboard-core', 'NeoDash', $this->localization->build());
        }
    }
}
