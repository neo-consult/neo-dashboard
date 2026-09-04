<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Runtime;

use NeoDashboard\Core\Manager\AssetManager;
use NeoDashboard\Core\Manager\ContentManager;
use NeoDashboard\Core\Manager\LanguageManager;
use NeoDashboard\Core\Manager\NotificationManager;
use NeoDashboard\Core\Manager\SectionManager;
use NeoDashboard\Core\Manager\SidebarManager;
use NeoDashboard\Core\Manager\WidgetManager;

final class DashboardManagerContainer implements DashboardRuntimeComponents
{
    private SectionManager $sections;
    private ContentManager $content;
    private SidebarManager $sidebar;
    private WidgetManager $widgets;
    private NotificationManager $notifications;
    private AssetManager $assets;
    private LanguageManager $language;

    public function __construct(
        SectionManager $sections,
        ContentManager $content,
        SidebarManager $sidebar,
        WidgetManager $widgets,
        NotificationManager $notifications,
        AssetManager $assets,
        LanguageManager $language,
    ) {
        $this->sections = $sections;
        $this->content = $content;
        $this->sidebar = $sidebar;
        $this->widgets = $widgets;
        $this->notifications = $notifications;
        $this->assets = $assets;
        $this->language = $language;
    }

    public function prepareAssets(): void
    {
        $this->assets->register();
    }

    public function registerHooks(): void
    {
        $this->language->registerHooks();
    }

    public function managerRegistrations(): array
    {
        return [
            new ManagerRegistration([$this->sections, 'registerDefault'], 5),
            new ManagerRegistration([$this->sidebar, 'registerDefault'], 10),
            new ManagerRegistration([$this->widgets, 'registerDefault'], 15),
            new ManagerRegistration([$this->notifications, 'registerDefault'], 20),
            new ManagerRegistration([$this->content, 'registerDefault'], 30),
        ];
    }

    public function loadWidgetDefinitions(): void
    {
        $this->widgets->loadDefinitions();
    }
}
