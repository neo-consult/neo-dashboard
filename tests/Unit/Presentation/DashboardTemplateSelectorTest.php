<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Presentation;

use NeoDashboard\Core\Presentation\DashboardTemplateSelector;
use PHPUnit\Framework\TestCase;

final class DashboardTemplateSelectorTest extends TestCase
{
    public function testItSelectsAnExistingDashboardTemplateOnlyForDashboardRequests(): void
    {
        $selector = new DashboardTemplateSelector();
        self::assertSame(__FILE__, $selector->select('theme.php', true, __FILE__));
        self::assertSame('theme.php', $selector->select('theme.php', false, __FILE__));
        self::assertSame('theme.php', $selector->select('theme.php', true, __FILE__ . '.missing'));
    }
}
