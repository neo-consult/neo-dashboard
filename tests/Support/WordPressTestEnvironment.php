<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Support;

final class WordPressTestEnvironment
{
    /** @var array<string, array<int, array{priority: int, callback: callable}>> */
    private static array $actions = [];

    /** @var array<string, array<int, array{priority: int, callback: callable}>> */
    private static array $filters = [];

    /** @var array<string, bool> */
    private static array $loadedTextdomains = [];

    /** @var array<string, array<string, mixed>> */
    public static array $localizedScripts = [];

    /** @var array<int, string> */
    public static array $deactivatedPlugins = [];

    /** @var array<int, string> */
    public static array $currentUserRoles = ['administrator'];

    /** @var array<string, bool> */
    public static array $capabilities = [];

    /** @var array<string, mixed> */
    public static array $queryVars = [];

    /** @var array<string, mixed> */
    public static array $options = [];

    public static bool $allowAllCapabilities = true;
    public static bool $loggedIn = false;
    public static bool $doingAjax = false;
    public static bool $isAdmin = false;

    public static function reset(): void
    {
        self::$actions = [];
        self::$filters = [];
        self::$loadedTextdomains = [];
        self::$localizedScripts = [];
        self::$deactivatedPlugins = [];
        self::$currentUserRoles = ['administrator'];
        self::$capabilities = [];
        self::$queryVars = [];
        self::$options = [];
        self::$allowAllCapabilities = true;
        self::$loggedIn = false;
        self::$doingAjax = false;
        self::$isAdmin = false;

        $_SERVER['REQUEST_URI'] = '/neo-dashboard';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        global $pagenow, $wp_query;
        $pagenow = 'index.php';
        $wp_query = (object) [];
    }

    public static function addAction(string $hook, callable $callback, int $priority): void
    {
        self::$actions[$hook][] = [
            'priority' => $priority,
            'callback' => $callback,
        ];
    }

    public static function doAction(string $hook, mixed ...$args): void
    {
        $callbacks = self::$actions[$hook] ?? [];
        usort(
            $callbacks,
            static fn(array $left, array $right): int => $left['priority'] <=> $right['priority'],
        );

        foreach ($callbacks as $callback) {
            ($callback['callback'])(...$args);
        }
    }

    public static function addFilter(string $hook, callable $callback, int $priority): void
    {
        self::$filters[$hook][] = [
            'priority' => $priority,
            'callback' => $callback,
        ];
    }

    public static function applyFilters(string $hook, mixed $value, mixed ...$args): mixed
    {
        $callbacks = self::$filters[$hook] ?? [];
        usort(
            $callbacks,
            static fn(array $left, array $right): int => $left['priority'] <=> $right['priority'],
        );

        foreach ($callbacks as $callback) {
            $value = ($callback['callback'])($value, ...$args);
        }

        return $value;
    }

    public static function currentUserCan(string $capability): bool
    {
        return self::$allowAllCapabilities || (self::$capabilities[$capability] ?? false);
    }

    public static function localizeScript(string $handle, string $objectName, array $data): void
    {
        self::$localizedScripts[$handle] = [
            'object_name' => $objectName,
            'data' => $data,
        ];
    }

    public static function isTextdomainLoaded(string $domain): bool
    {
        return self::$loadedTextdomains[$domain] ?? false;
    }

    public static function loadTextdomain(string $domain): void
    {
        self::$loadedTextdomains[$domain] = true;
    }

    public static function unloadTextdomain(string $domain): void
    {
        unset(self::$loadedTextdomains[$domain]);
    }

    public static function deactivatePlugin(string $plugin): void
    {
        self::$deactivatedPlugins[] = $plugin;
    }
}
