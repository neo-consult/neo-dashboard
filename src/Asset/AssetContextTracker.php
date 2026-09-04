<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Asset;

final class AssetContextTracker
{
    /** @var array<string, true> */
    private array $enqueuedContexts = [];

    public function claim(string $context): bool
    {
        if (isset($this->enqueuedContexts[$context])) {
            return false;
        }

        $this->enqueuedContexts[$context] = true;
        return true;
    }
}
