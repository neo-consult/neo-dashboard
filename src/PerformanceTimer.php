<?php
declare(strict_types=1);

namespace NeoDashboard\Core;


/**
 * Performance-Timer für Timing- und Memory-Messungen
 */
class PerformanceTimer
{
    public function __construct(private Logger $logger) {}

    private array $timings = [];
    private float $requestStartTime = 0.0;
    private int $requestStartMemory = 0;
    private bool $initialized = false;
    
    /**
     * Initialisiert den Timer (wird automatisch beim ersten Aufruf gemacht)
     */
    public function init(): void
    {
        if ($this->initialized) {
            return;
        }
        
        $this->requestStartTime = microtime(true);
        $this->requestStartMemory = memory_get_usage(true);
        $this->initialized = true;
    }
    
    /**
     * Startet einen Timer für eine Operation
     */
    public function start(string $category, string $operation): void
    {
        $this->init();
        
        $key = "{$category}:{$operation}";
        $this->timings[$key] = [
            'category' => $category,
            'operation' => $operation,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
        ];
    }
    
    /**
     * Stoppt einen Timer für eine Operation
     * 
     * @return float Dauer in Sekunden
     */
    public function stop(string $category, string $operation): float
    {
        $this->init();
        
        $key = "{$category}:{$operation}";
        if (!isset($this->timings[$key])) {
            return 0.0;
        }
        
        $current_time = microtime(true);
        $current_memory = memory_get_usage(true);
        
        $duration = $current_time - $this->timings[$key]['start_time'];
        $memory_delta = $current_memory - $this->timings[$key]['start_memory'];
        
        $this->timings[$key]['duration'] = $duration;
        $this->timings[$key]['end_time'] = $current_time;
        $this->timings[$key]['end_memory'] = $current_memory;
        $this->timings[$key]['memory_delta'] = $memory_delta;
        
        return $duration;
    }
    
    /**
     * Gibt alle Timings zurück
     */
    public function getTimings(): array
    {
        return $this->timings;
    }
    
    /**
     * Gibt Timings nach Kategorie gruppiert zurück
     */
    public function getTimingsByCategory(): array
    {
        $result = [];
        foreach ($this->timings as $key => $timing) {
            $category = $timing['category'];
            if (!isset($result[$category])) {
                $result[$category] = [];
            }
            $result[$category][] = $timing;
        }
        return $result;
    }
    
    /**
     * Gibt die aktuelle Memory-Usage zurück
     */
    public function getMemoryUsage(): int
    {
        return memory_get_usage(true);
    }
    
    /**
     * Gibt den Memory-Delta seit Request-Start zurück
     */
    public function getMemoryDelta(): int
    {
        $this->init();
        return memory_get_usage(true) - $this->requestStartMemory;
    }
    
    /**
     * Gibt die Request-Dauer zurück
     */
    public function getRequestDuration(): float
    {
        $this->init();
        return microtime(true) - $this->requestStartTime;
    }
    
    /**
     * Gibt die Request-Start-Zeit zurück
     */
    public function getRequestStartTime(): float
    {
        $this->init();
        return $this->requestStartTime;
    }
    
    /**
     * Gibt die Request-Start-Memory zurück
     */
    public function getRequestStartMemory(): int
    {
        $this->init();
        return $this->requestStartMemory;
    }
    
    /**
     * Loggt eine Performance-Zusammenfassung
     */
    public function logSummary(string $requestType): void
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG || !$this->logger->isEnabled()) {
            return;
        }
        
        $this->init();
        
        $total_duration = $this->getRequestDuration();
        $memory_peak = memory_get_peak_usage(true);
        $memory_delta = $this->requestStartMemory > 0 ? $memory_peak - $this->requestStartMemory : 0;
        
        $categories = $this->getTimingsByCategory();
        $category_totals = [];
        foreach ($categories as $category => $timings) {
            $category_totals[$category] = array_sum(array_column($timings, 'duration'));
        }
        
        $this->logger->info('Request Performance Summary', [
            'request_type' => $requestType,
            'total_duration' => round($total_duration, 4),
            'memory_peak' => $memory_peak,
            'memory_start' => $this->requestStartMemory,
            'memory_delta' => $memory_delta,
            'category_totals' => $category_totals,
            'timing_count' => count($this->timings),
        ], 'PERFORMANCE');
    }
}
