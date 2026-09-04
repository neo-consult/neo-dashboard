<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Http;

/**
 * Describes the execution context of the current WordPress request.
 */
enum RequestType: string
{
    case Web = 'WEB';
    case Admin = 'ADMIN';
    case Ajax = 'AJAX';
    case Rest = 'REST';
    case Cron = 'CRON';
    case Cli = 'CLI';

    public function isAsync(): bool
    {
        return $this === self::Ajax || $this === self::Rest;
    }

    public function isSystem(): bool
    {
        return $this === self::Cron || $this === self::Cli;
    }
}
