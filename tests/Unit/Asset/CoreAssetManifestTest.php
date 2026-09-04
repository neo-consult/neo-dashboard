<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Asset;

use NeoDashboard\Core\Asset\CoreAssetManifest;
use PHPUnit\Framework\TestCase;

final class CoreAssetManifestTest extends TestCase
{
    public function testCoreHandlesAreUniquePerAssetType(): void
    {
        $seen = [];
        foreach ((new CoreAssetManifest())->localAssets() as $asset) {
            $key = $asset['type'] . ':' . $asset['handle'];
            self::assertArrayNotHasKey($key, $seen);
            $seen[$key] = true;
        }
        self::assertCount(12, $seen);
    }
}
