<?php
declare(strict_types=1);
namespace NeoDashboard\Core\Widget;

enum WidgetRenderStatus
{
    case SUCCESS;
    case NOT_FOUND;
    case FORBIDDEN;
    case INVALID_CALLBACK;
    case FAILED;
}
