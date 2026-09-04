<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Http;

/**
 * Reads the native WordPress request state for the infrastructure boundary.
 */
final class NativeWordPressRequestEnvironment implements WordPressRequestEnvironment
{
    public function isAjax(): bool
    {
        return function_exists('wp_doing_ajax')
            ? wp_doing_ajax()
            : defined('DOING_AJAX') && DOING_AJAX;
    }

    public function isRest(): bool
    {
        return defined('REST_REQUEST') && REST_REQUEST;
    }

    public function isCli(): bool
    {
        return defined('WP_CLI') && WP_CLI;
    }

    public function isCron(): bool
    {
        return defined('DOING_CRON') && DOING_CRON;
    }

    public function isAdmin(): bool
    {
        return function_exists('is_admin') && is_admin();
    }

    public function isAuthenticated(): bool
    {
        return function_exists('is_user_logged_in') && is_user_logged_in();
    }

    public function requestUri(): string
    {
        return isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])
            ? $_SERVER['REQUEST_URI']
            : '/';
    }

    public function currentScript(): string
    {
        global $pagenow;

        if (isset($pagenow) && is_string($pagenow)) {
            return $pagenow;
        }

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

        return is_string($scriptName) ? basename($scriptName) : '';
    }

    public function dashboardBasePath(): string
    {
        if (!function_exists('home_url')) {
            return '/neo-dashboard';
        }

        $dashboardUrl = home_url('/neo-dashboard');
        $path = function_exists('wp_parse_url')
            ? wp_parse_url($dashboardUrl, PHP_URL_PATH)
            : parse_url($dashboardUrl, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : '/neo-dashboard';
    }

    public function dashboardSection(): string
    {
        if (!function_exists('get_query_var')) {
            return '';
        }

        global $wp_query;
        if (!is_object($wp_query)) {
            return '';
        }

        $section = get_query_var('neo_section', '');

        return is_scalar($section) ? (string) $section : '';
    }
}
