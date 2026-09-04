<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Runtime;

use NeoDashboard\Core\Access\RoleAccessPolicy;
use NeoDashboard\Core\Access\UserRoleResolver;
use NeoDashboard\Core\Asset\AssetCatalog;
use NeoDashboard\Core\Asset\AssetContextTracker;
use NeoDashboard\Core\Asset\CoreAssetEnqueuer;
use NeoDashboard\Core\Asset\CoreAssetLocalizationData;
use NeoDashboard\Core\Asset\CoreAssetManifest;
use NeoDashboard\Core\Asset\DashboardAssetPrinter;
use NeoDashboard\Core\Asset\PluginAssetEnqueuer;
use NeoDashboard\Core\Asset\WordPressCoreAssetPlatform;
use NeoDashboard\Core\Asset\WordPressDashboardClientEnvironment;
use NeoDashboard\Core\Asset\WordPressPluginAssetPlatform;
use NeoDashboard\Core\Extension\Registry\DashboardRegistries;
use NeoDashboard\Core\Http\RequestTypeProvider;
use NeoDashboard\Core\Http\NativeDashboardRequestEnvironment;
use NeoDashboard\Core\Http\NativeDashboardRequest;
use NeoDashboard\Core\Http\RestExceptionMapper;
use NeoDashboard\Core\Language\LanguageCatalog;
use NeoDashboard\Core\Language\LanguageAjaxController;
use NeoDashboard\Core\Language\LanguagePreferenceService;
use NeoDashboard\Core\Language\PluginLanguageSelector;
use NeoDashboard\Core\Language\WordPressLanguageChangeNotifier;
use NeoDashboard\Core\Language\WordPressLanguageAjaxRequest;
use NeoDashboard\Core\Language\WordPressLanguageAjaxResponder;
use NeoDashboard\Core\Language\WordPressUserLanguageStore;
use NeoDashboard\Core\Manager\AssetManager;
use NeoDashboard\Core\Manager\ContentManager;
use NeoDashboard\Core\Manager\ContextResolver;
use NeoDashboard\Core\Manager\FaviconManager;
use NeoDashboard\Core\Manager\LanguageManager;
use NeoDashboard\Core\Manager\NotificationManager;
use NeoDashboard\Core\Manager\RestManager;
use NeoDashboard\Core\Manager\SectionManager;
use NeoDashboard\Core\Manager\SidebarManager;
use NeoDashboard\Core\Manager\WidgetManager;
use NeoDashboard\Core\Notification\NotificationRestController;
use NeoDashboard\Core\Notification\NotificationService;
use NeoDashboard\Core\Notification\NotificationVisibilityFilter;
use NeoDashboard\Core\Notification\WordPressNotificationUserState;
use NeoDashboard\Core\Notification\WordPressNotificationRestRequestFactory;
use NeoDashboard\Core\Presentation\DashboardBodyClassFilter;
use NeoDashboard\Core\Presentation\DashboardRenderer;
use NeoDashboard\Core\Presentation\DashboardResponseHandler;
use NeoDashboard\Core\Presentation\DashboardTemplateSelector;
use NeoDashboard\Core\Presentation\DashboardViewModelFactory;
use NeoDashboard\Core\Presentation\UserMenuFormatter;
use NeoDashboard\Core\Presentation\WordPressUserMenuRenderer;
use NeoDashboard\Core\Rest\RestEndpointResponder;
use NeoDashboard\Core\Rest\RestRouteCollection;
use NeoDashboard\Core\Rest\WordPressRestPermissionChecker;
use NeoDashboard\Core\Rest\WordPressRestRouteRegistrar;
use NeoDashboard\Core\Routing\DashboardRouteRegistrar;
use NeoDashboard\Core\Routing\SectionResolver;
use NeoDashboard\Core\Widget\WidgetDefinitionLoader;
use NeoDashboard\Core\Widget\RegisteredWidgetProvider;
use NeoDashboard\Core\Widget\WidgetRenderService;
use NeoDashboard\Core\Widget\WordPressWidgetAccess;
use NeoDashboard\Core\Widget\WidgetAjaxController;
use NeoDashboard\Core\Widget\WordPressWidgetAjaxRequest;
use NeoDashboard\Core\Widget\WordPressWidgetAjaxResponder;
use NeoDashboard\Core\Widget\WordPressWidgetCache;
use NeoDashboard\Core\WordPress\DashboardRuntimeInitializer;
use NeoDashboard\Core\WordPress\DashboardRouter;
use NeoDashboard\Core\PerformanceTimer;
use NeoDashboard\Core\Logger;

final readonly class DashboardRuntimeCompositionRoot
{
    public function __construct(
        private DashboardRegistries $registries,
        private RequestTypeProvider $requestTypeProvider,
        private RoleAccessPolicy $roleAccessPolicy,
        private PerformanceTimer $performance,
        private Logger $logger,
    ) {}

    public function create(): DashboardRuntimeInitializer
    {
        $hookBus = new WordPressHookBus();
        $dashboardRequest = new NativeDashboardRequest();
        $widgets = new WidgetManager(
            $this->registries->widgets(),
            new WidgetDefinitionLoader($hookBus),
            $this->logger,
        );
        $content = new ContentManager(
            $this->roleAccessPolicy,
            new DashboardViewModelFactory(
                $this->registries->navigation(),
                $this->registries->notifications(),
                $this->registries->sections(),
                $this->registries->widgets(),
                new SectionResolver($this->registries->sections()),
            ),
            new DashboardResponseHandler(),
            new DashboardRenderer(
                NEO_DASHBOARD_TEMPLATE_PATH . 'dashboard-layout.php',
                new WordPressUserMenuRenderer(
                    new UserMenuFormatter(),
                    new UserRoleResolver(),
                ),
            ),
            $this->registries,
            $dashboardRequest,
            $this->logger,
        );
        $widgetController = new WidgetAjaxController(
            new WidgetRenderService(
                new RegisteredWidgetProvider($widgets, $this->registries->widgets()),
                new WordPressWidgetAccess($this->roleAccessPolicy),
                new WordPressWidgetCache(),
            ),
            new WordPressWidgetAjaxRequest(),
            new WordPressWidgetAjaxResponder(),
            $this->logger,
            defined('WP_DEBUG') && WP_DEBUG,
        );
        $context = new ContextResolver(new NativeDashboardRequestEnvironment());
        $assetCatalog = new AssetCatalog();
        $pluginAssetEnqueuer = new PluginAssetEnqueuer(
            $assetCatalog,
            $this->performance,
            new WordPressPluginAssetPlatform(),
        );
        $favicon = new FaviconManager($context);
        $notificationService = new NotificationService(
            $this->registries->notifications(),
            new WordPressNotificationUserState(),
            new NotificationVisibilityFilter(),
        );
        $languageCatalog = new LanguageCatalog();
        $languagePreferences = new LanguagePreferenceService(
            $languageCatalog,
            new PluginLanguageSelector(),
            new WordPressUserLanguageStore(),
            new WordPressLanguageChangeNotifier(),
        );
        $language = new LanguageManager(
            $languageCatalog,
            $languagePreferences,
            new LanguageAjaxController(
                $languageCatalog,
                $languagePreferences,
                new WordPressLanguageAjaxRequest(),
                new WordPressLanguageAjaxResponder(),
            ),
        );
        $components = new DashboardManagerContainer(
            new SectionManager($this->registries->sections(), $this->logger),
            $content,
            new SidebarManager($this->registries->navigation(), $this->logger),
            $widgets,
            new NotificationManager(
                $this->registries->notifications(),
                new NotificationRestController(
                    $notificationService,
                    new WordPressNotificationRestRequestFactory(),
                ),
                $this->logger,
            ),
            new AssetManager(
                $context,
                $assetCatalog,
                new CoreAssetEnqueuer(
                    new WordPressCoreAssetPlatform(),
                    new CoreAssetManifest(),
                    new CoreAssetLocalizationData(
                        $context,
                        $languageCatalog,
                        $languagePreferences,
                        $dashboardRequest,
                        new WordPressDashboardClientEnvironment(),
                    ),
                    NEO_DASHBOARD_VERSION,
                ),
                new DashboardAssetPrinter($favicon, $pluginAssetEnqueuer),
                $this->registries->sections(),
                new AssetContextTracker(),
                $dashboardRequest,
                $this->logger,
            ),
            $language,
        );
        $pipeline = new DashboardRuntimePipeline($components, $hookBus, $this->performance);
        $router = new DashboardRouter(
            $content,
            new DashboardRouteRegistrar(),
            new DashboardTemplateSelector(),
            new DashboardBodyClassFilter(),
            $this->requestTypeProvider,
            $this->logger,
        );

        return new DashboardRuntimeInitializer(
            $this->requestTypeProvider,
            $router,
            $pipeline,
            new RestManager(
                new RestRouteCollection(),
                new WordPressRestRouteRegistrar(
                    'neo-dashboard/v1',
                    new WordPressRestPermissionChecker(),
                    new RestEndpointResponder(new RestExceptionMapper(), $this->logger),
                ),
            ),
            $widgetController,
            $this->performance,
        );
    }
}
