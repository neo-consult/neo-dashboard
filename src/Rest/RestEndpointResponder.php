<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Rest;

use NeoDashboard\Core\Http\RestExceptionMapper;
use NeoDashboard\Core\Logger;
use Throwable;
use WP_REST_Request;
use WP_REST_Response;

final class RestEndpointResponder
{
    public function __construct(
        private readonly RestExceptionMapper $exceptionMapper,
        private readonly Logger $logger,
    ) {}

    public function respond(WP_REST_Request $request, callable $callback, string $route): mixed
    {
        try {
            return rest_ensure_response([
                'success' => true,
                'data' => $callback($request),
            ]);
        } catch (Throwable $exception) {
            $this->logger->error('REST callback failed', [
                'route' => $route,
                'exception' => $exception::class,
                'code' => $exception->getCode(),
            ]);

            $failure = $this->exceptionMapper->map($exception);

            return new WP_REST_Response([
                'success' => false,
                'code' => $failure->code,
                'message' => $failure->message,
            ], $failure->status);
        }
    }
}
