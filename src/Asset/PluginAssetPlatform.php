<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Asset;

interface PluginAssetPlatform
{
    /** @param list<string> $dependencies */
    public function enqueueStyle(string $handle, string $source, array $dependencies, string $version): void;

    /** @param list<string> $dependencies */
    public function enqueueScript(
        string $handle,
        string $source,
        array $dependencies,
        string $version,
        bool $inFooter,
    ): void;

    /** @return array<string, mixed> */
    public function existingLocalization(string $handle, string $objectName): array;

    /** @param array<string, mixed> $data */
    public function localize(string $handle, string $objectName, array $data): void;
}
