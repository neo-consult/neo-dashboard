<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Routing;

final class DashboardRouteRegistrar
{
    public const QUERY_VAR_SECTION = 'neo_section';

    /** Bump this value whenever the dashboard URL structure changes. */
    private const REWRITE_RULES_VERSION = '1';
    private const REWRITE_RULES_VERSION_OPTION = 'neo_dashboard_rewrite_rules_version';

    public function register(): void
    {
        add_rewrite_tag('%' . self::QUERY_VAR_SECTION . '%', '(.+)');
        add_rewrite_rule(
            '^neo-dashboard/?$',
            'index.php?pagename=neo-dashboard',
            'top',
        );
        add_rewrite_rule(
            '^neo-dashboard/(.+)?$',
            'index.php?pagename=neo-dashboard&' . self::QUERY_VAR_SECTION . '=$matches[1]',
            'top',
        );
    }

    /**
     * Registers the rules and refreshes WordPress' persisted rewrite cache once
     * after a routing change. Registering a rule alone does not update that
     * cache for already active plugins.
     */
    public function synchronizeRewriteRules(): void
    {
        $this->register();

        if (get_option(self::REWRITE_RULES_VERSION_OPTION) === self::REWRITE_RULES_VERSION) {
            return;
        }

        flush_rewrite_rules(false);
        update_option(self::REWRITE_RULES_VERSION_OPTION, self::REWRITE_RULES_VERSION, false);
    }

    /** @param list<string> $vars @return list<string> */
    public function addQueryVar(array $vars): array
    {
        if (!in_array(self::QUERY_VAR_SECTION, $vars, true)) {
            $vars[] = self::QUERY_VAR_SECTION;
        }
        return $vars;
    }
}
