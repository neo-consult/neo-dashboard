<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Runtime;

use Closure;

final class ManagerRegistration
{
    private readonly Closure $callback;

    public function __construct(callable $callback, private readonly int $priority)
    {
        $this->callback = Closure::fromCallable($callback);
    }

    public function callback(): Closure { return $this->callback; }
    public function priority(): int { return $this->priority; }
}
