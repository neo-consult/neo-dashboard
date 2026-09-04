<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Asset;

interface CoreAssetPlatform
{
    public function baseUrl(): string;

    public function exists(string $relativePath): bool;

    /** @param list<string> $dependencies */
    public function enqueueStyle(string $handle, string $source, array $dependencies, string $version): void;

    /** @param list<string> $dependencies */
    public function enqueueScript(string $handle, string $source, array $dependencies, string $version): void;

    /** @param array<string, mixed> $data */
    public function localize(string $handle, string $objectName, array $data): void;
}
