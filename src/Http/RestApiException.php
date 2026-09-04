<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Http;

use RuntimeException;

/**
 * Explicitly marks an exception as safe to expose through the REST API.
 */
final class RestApiException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $publicMessage,
        public readonly int $status = 400,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($publicMessage, $status, $previous);
    }
}
