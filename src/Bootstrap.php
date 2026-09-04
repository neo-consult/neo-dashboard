<?php
declare(strict_types=1);

namespace NeoDashboard\Core;

use NeoDashboard\Core\Access\RoleAccessPolicy;
use NeoDashboard\Core\Access\UserRoleResolver;
use NeoDashboard\Core\Http\CurrentRequestTypeProvider;
use NeoDashboard\Core\Http\RequestTypeProvider;
use NeoDashboard\Core\Extension\Registry\DashboardRegistries;
use NeoDashboard\Core\Extension\Registry\NavigationRegistry;
use NeoDashboard\Core\Extension\Registry\NotificationRegistry;
use NeoDashboard\Core\Extension\Registry\SectionRegistry;
use NeoDashboard\Core\Extension\Registry\WidgetRegistry;
use NeoDashboard\Core\Navigation\NavigationTreeBuilder;
use NeoDashboard\Core\Presentation\DashboardPageTitleProvider;
use NeoDashboard\Core\Presentation\DashboardPageTitleBuilder;
use NeoDashboard\Core\Runtime\DashboardRuntimeCompositionRoot;
use NeoDashboard\Core\Routing\DashboardRouteRegistrar;
use NeoDashboard\Core\Routing\SectionResolver;
use NeoDashboard\Core\Http\NativeWordPressRequestEnvironment;
use NeoDashboard\Core\Http\WordPressRequestContextFactory;
use NeoDashboard\Core\Security\DashboardAccessPolicy;
use NeoDashboard\Core\Security\PublicRouteRegistry;
use NeoDashboard\Core\Security\WordPressAccessCapabilityResolver;
use NeoDashboard\Core\Security\WordPressAccessController;
use NeoDashboard\Core\Security\WordPressAccessDecisionHandler;
use NeoDashboard\Core\Security\WordPressAccessEnforcer;
use NeoDashboard\Core\Security\WordPressPublicRouteLoader;
use NeoDashboard\Core\WordPress\AdminMenuIntegration;
use NeoDashboard\Core\WordPress\DashboardDocumentTitle;
use NeoDashboard\Core\WordPress\DashboardDiagnosticsRegistrar;
use NeoDashboard\Core\WordPress\DashboardCapabilityInstaller;
use NeoDashboard\Core\WordPress\DashboardLanguageLoader;
use NeoDashboard\Core\WordPress\DashboardLifecycleRegistrar;
use NeoDashboard\Core\WordPress\DashboardRuntimeInitializer;
use NeoDashboard\Core\WordPress\WordPressOutputCleanup;

/** Plugin lifecycle facade. Runtime concerns live in dedicated adapters. */
final class Bootstrap
{
    private ?DashboardLanguageLoader $languageLoader = null;
    private ?DashboardDocumentTitle $documentTitle = null;
    private ?DashboardRuntimeInitializer $runtimeInitializer = null;
    private ?RequestTypeProvider $requestTypeProvider = null;
    private ?DashboardPageTitleProvider $pageTitleProvider = null;
    private ?SectionResolver $sectionResolver = null;
    private ?RoleAccessPolicy $roleAccessPolicy = null;
    private ?DashboardRegistries $registries = null;
    private ?WordPressAccessController $accessController = null;
    private ?DashboardLifecycleRegistrar $lifecycleRegistrar = null;
    private ?DashboardDiagnosticsRegistrar $diagnosticsRegistrar = null;
    private ?PerformanceTimer $performanceTimer = null;
    private ?Logger $logger = null;

    public function registerHooks(): void
    {
        $this->lifecycleRegistrar()->registerHooks();
        $this->diagnosticsRegistrar()->registerHooks();
        $this->roleAccessPolicy();

        $this->runtimeInitializer()->registerHooks();
        $this->languageLoader()->registerHooks();
        $this->documentTitle()->registerHooks();
        (new WordPressOutputCleanup())->registerHooks();
        (new AdminMenuIntegration())->registerHooks();

        $this->accessController()->registerHooks();
    }

    private function languageLoader(): DashboardLanguageLoader
    {
        return $this->languageLoader ??= new DashboardLanguageLoader($this->requestTypeProvider());
    }

    private function runtimeInitializer(): DashboardRuntimeInitializer
    {
        return $this->runtimeInitializer ??= (new DashboardRuntimeCompositionRoot(
            $this->registries(),
            $this->requestTypeProvider(),
            $this->roleAccessPolicy(),
            $this->performanceTimer(),
            $this->logger(),
        ))->create();
    }

    private function requestTypeProvider(): RequestTypeProvider
    {
        return $this->requestTypeProvider ??= new CurrentRequestTypeProvider(
            new WordPressRequestContextFactory(new NativeWordPressRequestEnvironment()),
        );
    }

    private function documentTitle(): DashboardDocumentTitle
    {
        return $this->documentTitle ??= new DashboardDocumentTitle($this->pageTitleProvider());
    }

    private function pageTitleProvider(): DashboardPageTitleProvider
    {
        if ($this->pageTitleProvider === null) {
            $this->pageTitleProvider = new WordPress\WordPressDashboardPageTitleProvider(
                $this->sectionResolver(),
                new DashboardPageTitleBuilder(),
                new WordPress\NativeDashboardPageTitleEnvironment(),
            );
        }

        return $this->pageTitleProvider;
    }

    private function sectionResolver(): SectionResolver
    {
        return $this->sectionResolver ??= new SectionResolver(
            $this->registries()->sections(),
        );
    }

    private function registries(): DashboardRegistries
    {
        return $this->registries ??= new DashboardRegistries(
            new NavigationRegistry(new NavigationTreeBuilder()),
            new SectionRegistry(),
            new WidgetRegistry(),
            new NotificationRegistry(),
        );
    }

    private function roleAccessPolicy(): RoleAccessPolicy
    {
        return $this->roleAccessPolicy ??= new RoleAccessPolicy();
    }

    private function accessController(): WordPressAccessController
    {
        if ($this->accessController === null) {
            $publicRoutes = new PublicRouteRegistry();
            $this->accessController = new WordPressAccessController(
                new WordPressAccessEnforcer(
                    new WordPressRequestContextFactory(new NativeWordPressRequestEnvironment()),
                    new DashboardAccessPolicy($publicRoutes),
                    new WordPressAccessCapabilityResolver(),
                    new WordPressPublicRouteLoader($publicRoutes),
                    new WordPressAccessDecisionHandler(new UserRoleResolver()),
                ),
            );
        }

        return $this->accessController;
    }

    private function lifecycleRegistrar(): DashboardLifecycleRegistrar
    {
        return $this->lifecycleRegistrar ??= new DashboardLifecycleRegistrar(
            NEO_DASHBOARD_PLUGIN_FILE,
            new DashboardRouteRegistrar(),
            new DashboardCapabilityInstaller(),
        );
    }

    private function diagnosticsRegistrar(): DashboardDiagnosticsRegistrar
    {
        return $this->diagnosticsRegistrar ??= new DashboardDiagnosticsRegistrar(
            new LifecycleLogger(
                $this->requestTypeProvider(),
                $this->performanceTimer(),
                $this->logger(),
            ),
            defined('WP_DEBUG') && WP_DEBUG,
        );
    }

    private function performanceTimer(): PerformanceTimer
    {
        return $this->performanceTimer ??= new PerformanceTimer($this->logger());
    }

    private function logger(): Logger
    {
        return $this->logger ??= new Logger();
    }
}
