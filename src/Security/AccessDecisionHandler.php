<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Security;

use NeoDashboard\Core\Http\RequestContext;

interface AccessDecisionHandler
{
    public function handle(AccessDecision $decision, RequestContext $context): void;
}
