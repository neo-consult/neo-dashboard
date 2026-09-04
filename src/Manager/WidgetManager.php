<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Manager;

use NeoDashboard\Core\Extension\Definition\WidgetDefinition;
use NeoDashboard\Core\Extension\Registry\WidgetRegistry;
use NeoDashboard\Core\Logger;
use NeoDashboard\Core\Widget\WidgetDefinitionLoader;

/** WordPress facade for widget definition registration. */
class WidgetManager
{
    private WidgetRegistry $registry;
    private WidgetDefinitionLoader $loader;

    public function __construct(
        WidgetRegistry $registry,
        WidgetDefinitionLoader $loader,
        private Logger $logger,
    ) {
        $this->registry = $registry;
        $this->loader = $loader;
    }

    public function registerDefault(): void
    {
        $this->loader->registerReceiver([$this, 'register']);
    }

    public function loadDefinitions(): void
    {
        $this->loader->load([$this, 'register']);
    }

    public function register(WidgetDefinition $definition): string
    {
        $this->registry->add($definition->id(), $definition->toArray());

        return $definition->id();
    }

}
