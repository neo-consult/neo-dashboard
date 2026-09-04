<?php
declare(strict_types=1);

namespace NeoDashboard\Core;

use NeoDashboard\Core\Logger;
use NeoDashboard\Core\PerformanceTimer;
use NeoDashboard\Core\Http\RequestTypeProvider;

/**
 * Class LifecycleLogger
 *
 * Logs key WordPress lifecycle hooks for debugging and performance analysis.
 */
class LifecycleLogger
{
    public function __construct(
        private RequestTypeProvider $requestTypeProvider,
        private PerformanceTimer $performance,
        private Logger $logger,
    ) {}

    /**
     * @var string[] List of core hooks to track
     */
    protected array $hooks = [
        'muplugins_loaded',
        'plugins_loaded',
        'after_setup_theme',
        'init',
        'wp_loaded',
        'template_redirect',
        'template_include',
        'wp_footer',
        'shutdown',
    ];
    
    private float $requestStartTime = 0.0;
    private int $requestStartMemory = 0;
    /** @var array<string, float> */
    private array $hookTimes = [];
    private ?string $previousHook = null;
    private bool $initialized = false;
    
    public function registerHooks(): void
    {
        if (!$this->initialized) {
            $this->requestStartTime = microtime(true);
            $this->requestStartMemory = memory_get_usage(true);
            $this->initialized = true;
        }
        
        // WordPress Lifecycle Hooks
        foreach ($this->hooks as $hook) {
            add_action($hook, function() use ($hook) {
                $this->logHook($hook);
            }, 0);
        }
        
        // Performance-Zusammenfassung am Ende
        add_action('shutdown', [$this, 'logPerformanceSummary'], 999);
    }
    
    /**
     * Loggt einen Hook mit Timing und Memory-Informationen
     */
    private function logHook(string $hook_name): void
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG || !$this->logger->isEnabled()) {
            return;
        }
        
        $current_time = microtime(true);
        $current_memory = memory_get_usage(true);
        $memory_delta = $current_memory - $this->requestStartMemory;
        $duration_since_start = $current_time - $this->requestStartTime;
        
        $duration_since_previous = 0.0;
        if ($this->previousHook !== null && isset($this->hookTimes[$this->previousHook])) {
            $duration_since_previous = $current_time - $this->hookTimes[$this->previousHook];
        }
        
        $this->hookTimes[$hook_name] = $current_time;
        
        $this->logger->info('WP Lifecycle Hook fired', [
            'hook' => $hook_name,
            'duration_since_start' => round($duration_since_start, 4),
            'duration_since_previous' => round($duration_since_previous, 4),
            'previous_hook' => $this->previousHook,
            'memory_usage' => $current_memory,
            'memory_delta' => $memory_delta,
        ], 'LIFECYCLE');
        
        $this->previousHook = $hook_name;
    }
    
    /**
     * Loggt Performance-Zusammenfassung am Ende des Requests
     */
    public function logPerformanceSummary(): void
    {
        $this->performance->logSummary($this->requestTypeProvider->type());
    }
    
    /**
     * Logs every hook or filter fired. Use with caution: very verbose.
     *
     * @param mixed       $value     Value passed through the hook or filter.
     * @param string|null $hook_name Name of the hook being fired, or null.
     * @return mixed
     */
    public function logAllHooks()
    {
        // Retrieve all arguments passed to this 'all' hook
        $args = func_get_args();
        $hook_name = $args[0] ?? null;

        // Only log once per hook firing and if we have a valid hook name
        if (is_string($hook_name) && did_action($hook_name) === 1) {
            $this->logger->info('WP All-Hook fired', [
                'hook'      => $hook_name,
                'timestamp' => microtime(true),
            ], 'LIFECYCLE');
        }

        // Return the original value (first argument) to not interfere with filters
        return $args[0] ?? null;
    }
}
