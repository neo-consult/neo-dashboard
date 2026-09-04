<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Http;

use Throwable;

/**
 * Maps exceptions to public REST failures without exposing internal details.
 */
final class RestExceptionMapper
{
    public function map(Throwable $exception): RestFailure
    {
        if ($exception instanceof RestApiException) {
            return new RestFailure(
                $exception->errorCode,
                $exception->getMessage(),
                $this->normalizeStatus($exception->status),
            );
        }

        // Compatibility for extensions using HTTP client error codes on
        // ordinary exceptions. Server-side codes remain private.
        $status = (int) $exception->getCode();
        if ($status >= 400 && $status <= 499) {
            $message = trim($exception->getMessage());

            return new RestFailure(
                'neo_dashboard_request_failed',
                $message !== '' ? $message : 'The request could not be processed.',
                $status,
            );
        }

        return new RestFailure(
            'neo_dashboard_internal_error',
            'The request could not be processed.',
            500,
        );
    }

    private function normalizeStatus(int $status): int
    {
        return $status >= 400 && $status <= 599 ? $status : 500;
    }
}
