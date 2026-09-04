<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Widget;

use NeoDashboard\Core\Runtime\HookBus;

final class WidgetDefinitionLoader
{
    private bool $receiverRegistered = false;
    private bool $definitionsLoaded = false;

    public function __construct(private readonly HookBus $hooks) {}

    public function registerReceiver(callable $receiver): void
    {
        if ($this->receiverRegistered) {
            return;
        }

        $this->receiverRegistered = true;
        $this->hooks->addAction('neo_dashboard_register_widget', $receiver, 10);
    }

    public function load(callable $receiver): void
    {
        if ($this->definitionsLoaded) {
            return;
        }

        $this->definitionsLoaded = true;
        $this->registerReceiver($receiver);
        $this->hooks->dispatch('neo_dashboard_register_widgets');
    }
}
