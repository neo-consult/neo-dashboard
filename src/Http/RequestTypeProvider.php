<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Http;

interface RequestTypeProvider
{
    public function type(): string;
}
