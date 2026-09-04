<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Routing;

enum SectionResolutionStatus: string
{
    case ROOT = 'root';
    case FOUND = 'found';
    case NOT_FOUND = 'not_found';
}
