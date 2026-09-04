<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Security;

use NeoDashboard\Core\Http\RequestContext;

interface PublicRouteLoader
{
    public function load(RequestContext $context): void;
}
