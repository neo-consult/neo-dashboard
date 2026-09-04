<?php

declare(strict_types=1);

$composerAutoloader = dirname(__DIR__) . '/vendor/autoload.php';

if (is_file($composerAutoloader)) {
    require_once $composerAutoloader;
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'NeoDashboard\\Core\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = dirname(__DIR__) . '/src/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR);
}

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', dirname(__DIR__, 3));
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'plugins');
}

if (!defined('NEO_DASHBOARD_TEMPLATE_PATH')) {
    define('NEO_DASHBOARD_TEMPLATE_PATH', dirname(__DIR__) . '/templates/');
}

\NeoDashboard\Core\Tests\Support\WordPressTestEnvironment::reset();

if (!function_exists('__')) {
    function __(string $text, ?string $domain = null): string
    {
        return $text;
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__(string $text, ?string $domain = null): string
    {
        return $text;
    }
}

if (!function_exists('esc_html_e')) {
    function esc_html_e(string $text, ?string $domain = null): void
    {
        echo $text;
    }
}

if (!function_exists('add_action')) {
    function add_action(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        \NeoDashboard\Core\Tests\Support\WordPressTestEnvironment::addAction($hook, $callback, $priority);
    }
}

if (!function_exists('do_action')) {
    function do_action(string $hook, mixed ...$args): void
    {
        \NeoDashboard\Core\Tests\Support\WordPressTestEnvironment::doAction($hook, ...$args);
    }
}

if (!function_exists('add_filter')) {
    function add_filter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        \NeoDashboard\Core\Tests\Support\WordPressTestEnvironment::addFilter($hook, $callback, $priority);
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
    {
        return \NeoDashboard\Core\Tests\Support\WordPressTestEnvironment::applyFilters($hook, $value, ...$args);
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can(string $capability): bool
    {
        return \NeoDashboard\Core\Tests\Support\WordPressTestEnvironment::currentUserCan($capability);
    }
}

if (!function_exists('user_can')) {
    function user_can(int $userId, string $capability): bool
    {
        return \NeoDashboard\Core\Tests\Support\WordPressTestEnvironment::currentUserCan($capability);
    }
}

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in(): bool
    {
        return \NeoDashboard\Core\Tests\Support\WordPressTestEnvironment::$loggedIn;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id(): int
    {
        return \NeoDashboard\Core\Tests\Support\WordPressTestEnvironment::$loggedIn ? 1 : 0;
    }
}

if (!function_exists('wp_doing_ajax')) {
    function wp_doing_ajax(): bool
    {
        return \NeoDashboard\Core\Tests\Support\WordPressTestEnvironment::$doingAjax;
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return \NeoDashboard\Core\Tests\Support\WordPressTestEnvironment::$isAdmin;
    }
}

if (!function_exists('home_url')) {
    function home_url(string $path = ''): string
    {
        return 'http://example.test' . ($path === '' ? '' : '/' . ltrim($path, '/'));
    }
}

if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string
    {
        return 'http://example.test/wp-admin/' . ltrim($path, '/');
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path(string $file): string
    {
        $path = is_dir($file) ? $file : dirname($file);
        return rtrim($path, '\\/') . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url(string $file): string
    {
        $path = str_replace('\\', '/', is_dir($file) ? rtrim($file, '\\/') : dirname($file));
        $pluginsRoot = str_replace('\\', '/', rtrim(WP_PLUGIN_DIR, '\\/'));

        if (str_starts_with($path, $pluginsRoot . '/')) {
            $relative = substr($path, strlen($pluginsRoot) + 1);
            return 'http://example.test/wp-content/plugins/' . trim($relative, '/') . '/';
        }

        return 'http://example.test/' . trim(basename($path), '/') . '/';
    }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename(string $file): string
    {
        $normalizedFile = str_replace('\\', '/', $file);
        $normalizedRoot = str_replace('\\', '/', rtrim(WP_PLUGIN_DIR, '\\/')) . '/';

        if (str_starts_with($normalizedFile, $normalizedRoot)) {
            return substr($normalizedFile, strlen($normalizedRoot));
        }

        return basename($normalizedFile);
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce(string $action): string
    {
        return 'nonce-' . md5($action);
    }
}

if (!function_exists('is_textdomain_loaded')) {
    function is_textdomain_loaded(string $domain): bool
    {
        return \NeoDashboard\Core\Tests\Support\WordPressTestEnvironment::isTextdomainLoaded($domain);
    }
}

if (!function_exists('load_textdomain')) {
    function load_textdomain(string $domain, string $mofile): bool
    {
        \NeoDashboard\Core\Tests\Support\WordPressTestEnvironment::loadTextdomain($domain);
        return true;
    }
}

if (!function_exists('unload_textdomain')) {
    function unload_textdomain(string $domain): bool
    {
        \NeoDashboard\Core\Tests\Support\WordPressTestEnvironment::unloadTextdomain($domain);
        return true;
    }
}

if (!function_exists('load_plugin_textdomain')) {
    function load_plugin_textdomain(string $domain, bool $deprecated = false, string $pluginRelPath = ''): bool
    {
        \NeoDashboard\Core\Tests\Support\WordPressTestEnvironment::loadTextdomain($domain);
        return true;
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook(string $file, callable $callback): void
    {
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook(string $file, callable $callback): void
    {
    }
}

if (!function_exists('deactivate_plugins')) {
    function deactivate_plugins(string $plugin): void
    {
        \NeoDashboard\Core\Tests\Support\WordPressTestEnvironment::deactivatePlugin($plugin);
    }
}

if (!function_exists('wp_get_current_user')) {
    function wp_get_current_user(): object
    {
        return (object) ['roles' => \NeoDashboard\Core\Tests\Support\WordPressTestEnvironment::$currentUserRoles];
    }
}

if (!function_exists('wp_script_is')) {
    function wp_script_is(string $handle, string $status = 'enqueued'): bool
    {
        return false;
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script(string $handle, string $objectName, array $data): bool
    {
        \NeoDashboard\Core\Tests\Support\WordPressTestEnvironment::localizeScript($handle, $objectName, $data);
        return true;
    }
}

if (!function_exists('wp_parse_url')) {
    function wp_parse_url(string $url, int $component = -1): string|array|int|false|null
    {
        return parse_url($url, $component);
    }
}

if (!function_exists('get_query_var')) {
    function get_query_var(string $key, mixed $default = ''): mixed
    {
        return \NeoDashboard\Core\Tests\Support\WordPressTestEnvironment::$queryVars[$key] ?? $default;
    }
}

if (!function_exists('get_option')) {
    function get_option(string $key, mixed $default = false): mixed
    {
        return \NeoDashboard\Core\Tests\Support\WordPressTestEnvironment::$options[$key] ?? $default;
    }
}

if (!function_exists('update_option')) {
    function update_option(string $key, mixed $value, bool $autoload = true): bool
    {
        \NeoDashboard\Core\Tests\Support\WordPressTestEnvironment::$options[$key] = $value;
        return true;
    }
}

if (!function_exists('sanitize_title')) {
    function sanitize_title(string $title): string
    {
        $title = strtolower(trim($title));
        $title = preg_replace('/[^a-z0-9_-]+/i', '-', $title) ?? '';

        return trim($title, '-');
    }
}

if (!isset($GLOBALS['wpdb']) || !is_object($GLOBALS['wpdb'])) {
    $GLOBALS['wpdb'] = new class {
        public string $prefix = 'wp_';
    };
}
