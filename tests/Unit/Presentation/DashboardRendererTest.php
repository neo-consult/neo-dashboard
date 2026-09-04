<?php

declare(strict_types=1);

namespace {
    if (!class_exists('WP_User')) {
        class WP_User {}
    }
}

namespace NeoDashboard\Core\Tests\Unit\Presentation {
    use NeoDashboard\Core\Extension\Registry\NavigationRegistry;
    use NeoDashboard\Core\Extension\Registry\NotificationRegistry;
    use NeoDashboard\Core\Extension\Registry\SectionRegistry;
    use NeoDashboard\Core\Extension\Registry\WidgetRegistry;
    use NeoDashboard\Core\Presentation\DashboardRenderer;
    use NeoDashboard\Core\Navigation\NavigationTreeBuilder;
    use NeoDashboard\Core\Presentation\DashboardViewModelFactory;
    use NeoDashboard\Core\Presentation\UserMenuRenderer;
    use NeoDashboard\Core\Routing\SectionResolver;
    use PHPUnit\Framework\TestCase;
    use WP_User;

    final class DashboardRendererTest extends TestCase
    {
        public function testItRendersTheUserMenuOnceAndPassesTheMarkupToTheTemplate(): void
        {
            $menuRenderer = new class implements UserMenuRenderer {
                public int $calls = 0;
                public function render(WP_User $user): string
                {
                    $this->calls++;
                    return '<nav-user>Menu</nav-user>';
                }
            };
            $sections = new SectionRegistry();
            $viewModel = (new DashboardViewModelFactory(
                new NavigationRegistry(new NavigationTreeBuilder()),
                new NotificationRegistry(),
                $sections,
                new WidgetRegistry(),
                new SectionResolver($sections),
            ))->create('', static fn(array $definition): bool => true);
            $renderer = new DashboardRenderer(
                dirname(__DIR__, 2) . '/Fixtures/dashboard-renderer.php',
                $menuRenderer,
            );

            self::assertSame('<nav-user>Menu</nav-user>', $renderer->render($viewModel, new WP_User()));
            self::assertSame(1, $menuRenderer->calls);
        }
    }
}
