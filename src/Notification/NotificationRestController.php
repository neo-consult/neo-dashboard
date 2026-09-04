<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Notification;

use NeoDashboard\Core\Manager\RestManager;
final readonly class NotificationRestController
{
    public function __construct(
        private NotificationService $service,
        private NotificationRestRequestFactory $requestFactory,
    ) {}

    public function registerRoutes(RestManager $rest): void
    {
        $rest->registerRoute(
            '/notifications',
            fn(mixed $request): array => $this->active(),
            'GET',
            [],
            'read',
        );

        $rest->registerRoute(
            '/notifications/(?P<id>[a-zA-Z0-9_-]+)/dismiss',
            fn(mixed $request): array => $this->dismiss($this->requestFactory->create($request)),
            'POST',
            [
                'id' => [
                    'required' => true,
                ],
            ],
            'read',
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function active(): array
    {
        return $this->service->activeForCurrentUser();
    }

    /** @return array{dismissed: string} */
    public function dismiss(NotificationRestRequest $request): array
    {
        $id = $request->notificationId();
        $this->service->dismiss($id, $request->userId());

        return ['dismissed' => $id];
    }
}
