<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Http;

final class CurrentRequestTypeProvider implements RequestTypeProvider
{
    private ?string $currentType = null;

    public function __construct(
        private readonly RequestContextFactory $contextFactory,
    ) {}

    public function type(): string
    {
        return $this->currentType ??= $this->contextFactory->create()->type->value;
    }
}
