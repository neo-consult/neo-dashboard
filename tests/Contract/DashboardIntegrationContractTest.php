<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Contract;

use NeoDashboard\Core\Asset\PluginAssetDefinition;
use NeoDashboard\Core\Extension\Definition\NavigationItemDefinition;
use NeoDashboard\Core\Extension\Definition\SectionDefinition;
use NeoDashboard\Core\Extension\Definition\WidgetDefinition;
use NeoDashboard\Core\Tests\Support\WordPressTestEnvironment;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

final class DashboardIntegrationContractTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../../';

    private static bool $pluginsBooted = false;

    protected function setUp(): void
    {
        WordPressTestEnvironment::reset();
        self::bootPluginEntrypoints();
    }

    /** @return iterable<string, array{0:string,1:callable,2:bool,3:string,4:?string,5:string}> */
    public static function pluginProvider(): iterable
    {
        yield 'neo-calendar' => [
            'neo-calendar',
            [self::class, 'collectNeoCalendar'],
            true,
            'neo-calendar',
            \NeoCalendar\Manager\CapabilitiesManager::class,
            self::ROOT . 'neo-calendar/languages',
        ];

        yield 'neo-contacts' => [
            'neo-contacts',
            [self::class, 'collectNeoContacts'],
            true,
            'neo-contacts',
            \NeoContacts\Manager\CapabilitiesManager::class,
            self::ROOT . 'neo-contacts/languages',
        ];

        yield 'neo-surveys' => [
            'neo-surveys',
            [self::class, 'collectNeoSurveys'],
            true,
            'neo-surveys',
            \NeoSurveys\Manager\CapabilitiesManager::class,
            self::ROOT . 'neo-surveys/languages',
        ];

        yield 'neo-privacy' => [
            'neo-privacy',
            [self::class, 'collectNeoPrivacy'],
            false,
            'neo-privacy',
            null,
            self::ROOT . 'neo-privacy/languages',
        ];

        yield 'neo-templates' => [
            'neo-templates',
            [self::class, 'collectNeoTemplates'],
            false,
            'neo-templates',
            null,
            self::ROOT . 'neo-templates/languages',
        ];

        yield 'job-board-integration' => [
            'job-board-integration',
            [self::class, 'collectJobBoardIntegration'],
            true,
            'job-board-integration',
            null,
            self::ROOT . 'job-board-integration/languages',
        ];
    }

    /**
     * @dataProvider pluginProvider
     */
    public function testDashboardPluginContracts(
        string $pluginId,
        callable $collector,
        bool $expectsWidget,
        string $textDomain,
        ?string $capabilitiesClass,
        string $languagesPath,
    ): void {
        $contract = new DashboardContractCollector();
        $contract->register();

        $collector($contract);

        self::assertNotEmpty($contract->sidebars, $pluginId . ' should register sidebar items.');
        self::assertNotEmpty($contract->sections, $pluginId . ' should register sections.');

        if ($expectsWidget) {
            self::assertNotEmpty($contract->widgets, $pluginId . ' should register at least one widget.');
        }

        $sectionSlugs = array_keys($contract->sections);
        self::assertCount(
            count(array_unique($sectionSlugs)),
            $sectionSlugs,
            $pluginId . ' should not register duplicate section slugs.',
        );

        foreach ($contract->sidebars as $slug => $definition) {
            $parent = $definition['parent'] ?? null;
            if (is_string($parent) && $parent !== '') {
                self::assertArrayHasKey(
                    $parent,
                    $contract->sidebars,
                    $pluginId . " references missing sidebar parent {$parent}.",
                );
            }
        }

        foreach ($contract->sections as $slug => $definition) {
            self::assertTrue(
                is_callable($definition['callback'] ?? null),
                $pluginId . " section {$slug} must expose a callable callback.",
            );
        }

        foreach ($contract->widgets as $id => $definition) {
            self::assertTrue(
                is_callable($definition['callback'] ?? null),
                $pluginId . " widget {$id} must expose a callable callback.",
            );
        }

        foreach ($contract->assets as $definition) {
            foreach ($definition->assets() as $asset) {
                if (preg_match('#^https?://#', $asset->source) === 1) {
                    self::assertNotFalse(
                        filter_var($asset->source, FILTER_VALIDATE_URL),
                        $pluginId . " asset {$asset->handle} should provide a valid URL.",
                    );
                    continue;
                }

                $localPath = $this->resolveLocalAssetPath($pluginId, $asset->source);
                self::assertFileExists(
                    $localPath,
                    $pluginId . " asset {$asset->handle} should exist locally.",
                );
            }
        }

        if (is_dir($languagesPath)) {
            self::assertFileExists($languagesPath . DIRECTORY_SEPARATOR . $textDomain . '.pot');

            foreach (['de_DE', 'en_US', 'uk_UA'] as $locale) {
                $po = $languagesPath . DIRECTORY_SEPARATOR . $textDomain . '-' . $locale . '.po';
                $mo = $languagesPath . DIRECTORY_SEPARATOR . $textDomain . '-' . $locale . '.mo';

                self::assertTrue(
                    is_file($po) || is_file($mo),
                    $pluginId . " should provide translation assets for {$locale}.",
                );
            }
        }

        if ($capabilitiesClass !== null) {
            self::assertTrue(
                defined($capabilitiesClass . '::CAPABILITIES'),
                $pluginId . ' should expose a capability contract.',
            );
            /** @var array<string, string> $capabilities */
            $capabilities = $capabilitiesClass::CAPABILITIES;
            self::assertNotEmpty($capabilities, $pluginId . ' should define capabilities.');
        }
    }

    private function resolveLocalAssetPath(string $pluginId, string $source): string
    {
        $prefix = 'http://example.test/wp-content/plugins/' . $pluginId . '/';

        if (str_starts_with($source, $prefix)) {
            return self::ROOT . $pluginId . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, substr($source, strlen($prefix)));
        }

        return self::ROOT . $pluginId . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim(parse_url($source, PHP_URL_PATH) ?? '', '/'));
    }

    private static function collectNeoCalendar(DashboardContractCollector $collector): void
    {
        $manager = (new ReflectionClass(\NeoCalendar\Manager\DashboardManager::class))->newInstanceWithoutConstructor();
        $manager->registerComponents();
        $manager->registerWidget();
    }

    private static function collectNeoContacts(DashboardContractCollector $collector): void
    {
        $reflection = new ReflectionClass(\NeoContacts\Manager\DashboardManager::class);
        $manager = $reflection->newInstanceWithoutConstructor();

        self::invokePrivate($manager, 'registerAssets');
        self::invokePrivate($manager, 'registerComponents');
        $manager->registerWidgets();
    }

    private static function collectNeoSurveys(DashboardContractCollector $collector): void
    {
        $manager = (new ReflectionClass(\NeoSurveys\Manager\DashboardManager::class))->newInstanceWithoutConstructor();
        $manager->registerComponents();
        $manager->registerWidget();
    }

    private static function collectNeoPrivacy(DashboardContractCollector $collector): void
    {
        $assetManager = new \NeoPrivacy\Manager\AssetManager();
        $assetManager->register();

        $reflection = new ReflectionClass(\NeoPrivacy\Manager\DashboardManager::class);
        $manager = $reflection->newInstanceWithoutConstructor();
        self::setPrivateProperty($manager, 'assetManager', $assetManager);
        self::invokePrivate($manager, 'registerComponents');
    }

    private static function collectNeoTemplates(DashboardContractCollector $collector): void
    {
        $reflection = new ReflectionClass(\NeoTemplates\Manager\DashboardManager::class);
        $manager = $reflection->newInstanceWithoutConstructor();

        $templatesManager = (new ReflectionClass(\NeoTemplates\Manager\TemplatesManager::class))->newInstanceWithoutConstructor();
        self::setPrivateProperty($manager, 'templatesManager', $templatesManager);

        self::invokePrivate($manager, 'registerAssets');
        self::invokePrivate($manager, 'registerComponents');
    }

    private static function collectJobBoardIntegration(DashboardContractCollector $collector): void
    {
        $manager = (new ReflectionClass(\Job_Board_Integration::class))->newInstanceWithoutConstructor();
        $manager->register_dashboard_components();
        $manager->register_dashboard_widget();
    }

    private static function invokePrivate(object $object, string $method): mixed
    {
        $reflectionMethod = new ReflectionMethod($object, $method);
        return $reflectionMethod->invoke($object);
    }

    private static function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflectionProperty = new ReflectionProperty($object, $property);
        $reflectionProperty->setValue($object, $value);
    }

    private static function bootPluginEntrypoints(): void
    {
        if (self::$pluginsBooted) {
            return;
        }

        require_once self::ROOT . 'neo-calendar/neo-calendar.php';
        require_once self::ROOT . 'neo-contacts/neo-contacts.php';
        require_once self::ROOT . 'neo-surveys/neo-surveys.php';
        require_once self::ROOT . 'neo-privacy/neo-privacy.php';
        require_once self::ROOT . 'neo-templates/neo-templates.php';
        require_once self::ROOT . 'job-board-integration/job-board-integration.php';

        self::$pluginsBooted = true;
    }
}

final class DashboardContractCollector
{
    /** @var array<string, array<string, mixed>> */
    public array $sidebars = [];

    /** @var array<string, array<string, mixed>> */
    public array $sections = [];

    /** @var array<string, array<string, mixed>> */
    public array $widgets = [];

    /** @var list<PluginAssetDefinition> */
    public array $assets = [];

    public function register(): void
    {
        add_action('neo_dashboard_register_sidebar_item', [$this, 'collectSidebar']);
        add_action('neo_dashboard_register_section', [$this, 'collectSection']);
        add_action('neo_dashboard_register_widget', [$this, 'collectWidget']);
        add_action('neo_dashboard_register_plugin_assets', [$this, 'collectAssets'], 10, 2);
    }

    public function collectSidebar(NavigationItemDefinition $definition): void
    {
        $this->sidebars[$definition->slug()] = $definition->toArray();
    }

    public function collectSection(SectionDefinition $definition): void
    {
        $this->sections[$definition->slug()] = $definition->toArray();
    }

    public function collectWidget(WidgetDefinition $definition): void
    {
        $this->widgets[$definition->id()] = $definition->toArray();
    }

    public function collectAssets(PluginAssetDefinition $definition): void
    {
        $this->assets[] = $definition;
    }
}
