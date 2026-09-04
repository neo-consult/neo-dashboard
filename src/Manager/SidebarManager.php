<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Manager;

use NeoDashboard\Core\Extension\Definition\NavigationItemDefinition;
use NeoDashboard\Core\Extension\Registry\NavigationRegistry;
use NeoDashboard\Core\Logger;

/** WordPress facade for navigation registration. */
class SidebarManager
{
    private NavigationRegistry $registry;
    public function __construct(NavigationRegistry $registry, private Logger $logger)
    {
        $this->registry = $registry;
    }

    public function registerDefault(): void
    {
        $this->register(NavigationItemDefinition::fromArray([
            'slug' => 'home',
            'label_callback' => static fn(): string => __('Start', 'neo-dashboard-core'),
            'icon' => 'bi-house',
            'url' => '/neo-dashboard/',
            'position' => 0,
            'roles' => null,
            'is_group' => false,
        ]));

        add_action('neo_dashboard_register_sidebar_item', [$this, 'register']);
    }

    public function register(NavigationItemDefinition $definition): string
    {
        $this->registry->add($definition->slug(), $definition->toArray());

        return $definition->slug();
    }

}
