<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Http;

final readonly class RestFailure
{
    public function __construct(
        public string $code,
        public string $message,
        public int $status,
    ) {}
}
