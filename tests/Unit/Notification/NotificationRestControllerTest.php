<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Notification;

use NeoDashboard\Core\Extension\Registry\NotificationRegistry;
use NeoDashboard\Core\Notification\NotificationRestController;
use NeoDashboard\Core\Notification\NotificationRestRequest;
use NeoDashboard\Core\Notification\NotificationRestRequestFactory;
use NeoDashboard\Core\Notification\NotificationService;
use NeoDashboard\Core\Notification\NotificationUserContext;
use NeoDashboard\Core\Notification\NotificationUserState;
use NeoDashboard\Core\Notification\NotificationVisibilityFilter;
use PHPUnit\Framework\TestCase;

final class NotificationRestControllerTest extends TestCase
{
    public function testItReturnsNotificationsVisibleToTheCurrentUser(): void
    {
        $registry = new NotificationRegistry();
        $registry->add('member-info', [
            'id' => 'member-info',
            'roles' => ['member'],
            'priority' => 10,
        ]);
        $registry->add('admin-info', [
            'id' => 'admin-info',
            'roles' => ['administrator'],
            'priority' => 5,
        ]);
        $state = new RestNotificationUserState();
        $controller = $this->controller($registry, $state);

        self::assertSame([[
            'id' => 'member-info',
            'roles' => ['member'],
            'priority' => 10,
        ]], $controller->active());
    }

    public function testItDismissesTheRequestedNotificationForTheRequestUser(): void
    {
        $state = new RestNotificationUserState();
        $controller = $this->controller(new NotificationRegistry(), $state);
        $request = new RestNotificationRequest('release-note', 42);

        self::assertSame(['dismissed' => 'release-note'], $controller->dismiss($request));
        self::assertSame([['release-note', 42]], $state->dismissals);
    }

    private function controller(
        NotificationRegistry $registry,
        RestNotificationUserState $state,
    ): NotificationRestController {
        return new NotificationRestController(
            new NotificationService($registry, $state, new NotificationVisibilityFilter()),
            new UnusedNotificationRequestFactory(),
        );
    }
}

final class RestNotificationUserState implements NotificationUserState
{
    /** @var list<array{string, int}> */
    public array $dismissals = [];

    public function current(): NotificationUserContext
    {
        return new NotificationUserContext(42, ['member'], [], 1000);
    }

    public function dismiss(string $id, int $userId): bool
    {
        $this->dismissals[] = [$id, $userId];
        return true;
    }
}

final readonly class RestNotificationRequest implements NotificationRestRequest
{
    public function __construct(private string $id, private int $userIdValue) {}

    public function notificationId(): string { return $this->id; }
    public function userId(): int { return $this->userIdValue; }
}

final class UnusedNotificationRequestFactory implements NotificationRestRequestFactory
{
    public function create(mixed $request): NotificationRestRequest
    {
        throw new \LogicException('Not used by these application-service tests.');
    }
}
