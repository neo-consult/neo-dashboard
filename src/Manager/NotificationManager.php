<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Manager;

use NeoDashboard\Core\Extension\Definition\NotificationDefinition;
use NeoDashboard\Core\Extension\Registry\NotificationRegistry;
use NeoDashboard\Core\Logger;
use NeoDashboard\Core\Notification\NotificationRestController;

/** WordPress facade for notification registration and delivery. */
class NotificationManager
{
    private NotificationRegistry $registry;
    private NotificationRestController $restController;

    public function __construct(
        NotificationRegistry $registry,
        NotificationRestController $restController,
        private Logger $logger,
    ) {
        $this->registry = $registry;
        $this->restController = $restController;
    }

    public function registerDefault(): void
    {
        add_action('neo_dashboard_register_notification', [$this, 'register']);
        add_action('neo_dashboard_register_rest_routes', [$this, 'registerRestRoutes']);
    }

    public function register(NotificationDefinition $definition): string
    {
        $id = $definition->id();
        $args = $definition->toArray();
        $this->registry->add($id, $args);
        $this->logger->info('NotificationManager:register', ['id' => $id]);

        return $id;
    }

    public function registerRestRoutes(RestManager $rest): void
    {
        $this->restController->registerRoutes($rest);
    }
}
