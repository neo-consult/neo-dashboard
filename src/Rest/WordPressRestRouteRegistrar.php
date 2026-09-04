<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Rest;

use WP_REST_Request;

final readonly class WordPressRestRouteRegistrar implements RestRouteRegistrar
{
    public function __construct(
        private string $namespace,
        private RestPermissionChecker $permissionChecker,
        private RestEndpointResponder $responder,
    ) {}

    public function register(array $routes): void
    {
        foreach ($routes as $definition) {
            register_rest_route($this->namespace, $definition->route, [
                'methods' => $definition->methods,
                'callback' => fn(WP_REST_Request $request): mixed => $this->responder->respond(
                    $request,
                    $definition->callback,
                    $definition->route,
                ),
                'permission_callback' => fn(): bool => $this->permissionChecker->can(
                    $definition->capability,
                ),
                'args' => $definition->args,
            ]);
        }
    }
}
