<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Security;

enum PublicRouteMatch: string
{
    case Exact = 'exact';
    case Prefix = 'prefix';
}
