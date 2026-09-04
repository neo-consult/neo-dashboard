<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Http;

interface RequestContextFactory
{
    public function create(): RequestContext;
}
