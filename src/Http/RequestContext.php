<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Http;

/**
 * Immutable, WordPress-independent description of a request.
 *
 * WordPress globals and functions must be translated into this value object by
 * an infrastructure adapter. Access policies can then be tested without
 * bootstrapping WordPress.
 */
final readonly class RequestContext
{
    public string $path;

    public function __construct(
        public RequestType $type,
        string $path,
        public bool $isDashboard = false,
        public bool $isLogin = false,
        public bool $isAuthenticated = false,
        public bool $isAdminPost = false,
    ) {
        $this->path = self::normalizePath($path);
    }

    public function isAdmin(): bool
    {
        return $this->type === RequestType::Admin;
    }

    public function isAjax(): bool
    {
        return $this->type === RequestType::Ajax;
    }

    public function isRest(): bool
    {
        return $this->type === RequestType::Rest;
    }

    public function isSystem(): bool
    {
        return $this->type->isSystem();
    }

    public static function normalizePath(string $uri): string
    {
        $trimmedUri = trim($uri);

        // REQUEST_URI values beginning with multiple slashes are paths, but
        // parse_url() interprets "//segment" as a protocol-relative host.
        if (str_starts_with($trimmedUri, '//')) {
            $path = preg_split('/[?#]/', $trimmedUri, 2)[0] ?? '';
        } else {
            $path = parse_url($trimmedUri, PHP_URL_PATH);
        }

        if (!is_string($path) || $path === '') {
            return '/';
        }

        $decodedPath = rawurldecode($path);
        $normalizedPath = preg_replace('#/+#', '/', str_replace('\\', '/', $decodedPath));

        if (!is_string($normalizedPath) || $normalizedPath === '') {
            return '/';
        }

        $normalizedPath = '/' . ltrim($normalizedPath, '/');

        if ($normalizedPath !== '/') {
            $normalizedPath = rtrim($normalizedPath, '/');
        }

        return $normalizedPath;
    }
}
