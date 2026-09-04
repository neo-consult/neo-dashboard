<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Manager;

use NeoDashboard\Core\Extension\Definition\SectionDefinition;
use NeoDashboard\Core\Extension\Registry\SectionRegistry;
use NeoDashboard\Core\Logger;

/** WordPress facade for section registration. */
class SectionManager
{
    private SectionRegistry $registry;
    public function __construct(SectionRegistry $registry, private Logger $logger)
    {
        $this->registry = $registry;
    }

    public function registerDefault(): void
    {
        add_action('neo_dashboard_register_section', [$this, 'register']);
    }

    public function register(SectionDefinition $definition): string
    {
        $slug = $definition->slug();

        if (!$this->registry->add($slug, $definition->toArray())) {
            $this->logger->debug('SectionManager: duplicate registration skipped', ['slug' => $slug]);
            return $slug;
        }

        return $slug;
    }

}
