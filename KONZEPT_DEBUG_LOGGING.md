# Konzept: Systematisches Debug-Logging für neo-dashboard

> Historischer Entwurf; die Beispiele wurden auf die aktuelle
> `CurrentRequestTypeProvider::type()`-API aktualisiert.

## 1. Ziele

### 1.1 Hauptziele
1. **Performance-Analyse**: Timing-Informationen für wichtige Operationen
2. **WordPress Lifecycle**: Vollständige Sichtbarkeit des WordPress-Lebenszyklus
3. **Plugin-Lifecycle**: Sichtbarkeit der neo-dashboard Initialisierung
4. **Strukturiertes Logging**: Konsistente Formatierung und Kategorisierung
5. **Debugging-Hilfe**: Einfache Identifikation von Performance-Problemen

### 1.2 Anforderungen
- Timing-Informationen für wichtige Operationen
- Request-Typ-Erkennung (WEB, AJAX, REST, CRON, CLI)
- Plugin-Initialisierungsschritte
- WordPress Hook-Timing
- Memory-Usage-Informationen
- Strukturierte Log-Formatierung

---

## 2. Aktueller Stand

### 2.1 Vorhandene Komponenten

#### LifecycleLogger
- Loggt WordPress Lifecycle Hooks
- Verwendet `Logger::info()`
- Zeigt Hook-Namen und Timestamps
- **Problem**: Keine Timing-Informationen (Dauer zwischen Hooks)
- **Problem**: Keine Memory-Usage-Informationen

#### Logger
- Strukturiertes Logging in Datei
- Unterstützt verschiedene Log-Level
- JSON-Format für Daten
- **Gut**: Funktioniert bereits

#### CurrentRequestTypeProvider::type()
- Erkennt Request-Typ (WEB, AJAX, REST, CRON, CLI)
- **Gut**: Funktioniert bereits

### 2.2 Fehlende Komponenten

1. **Performance-Timer**: Timing-Informationen sammeln
2. **Memory-Tracker**: Memory-Usage-Informationen
3. **Erweiterter LifecycleLogger**: Mit Timing und Memory
4. **Plugin-Initialisierungs-Logging**: neo-dashboard Schritte
5. **Strukturierte Performance-Logs**: Für Analyse

---

## 3. Konzept

### 3.1 Performance-Timer-Klasse

**Zweck**: Timing-Informationen für Operationen sammeln

**Funktionen**:
- Start/Stop-Timer für Operationen
- Kategorisierung von Timings (Plugin-Init, Asset-Loading, etc.)
- Zusammenfassung am Ende des Requests
- Memory-Usage-Tracking

**Struktur**:
```php
class PerformanceTimer
{
    private static array $timings = [];
    private static array $memory_snapshots = [];
    private static float $request_start_time;
    private static int $request_start_memory;
    
    public static function start(string $category, string $operation): void
    public static function stop(string $category, string $operation): void
    public static function getTimings(): array
    public static function logSummary(): void
    public static function getMemoryUsage(): int
}
```

### 3.2 Erweiterter LifecycleLogger

**Zweck**: WordPress Lifecycle mit Timing und Memory-Usage loggen

**Erweiterungen**:
- Timing zwischen Hooks (Dauer zwischen Hook-Feuerungen)
- Memory-Usage bei jedem Hook
- Request-Start-Zeit und -Memory
- Request-End-Zusammenfassung

**Struktur**:
```php
class LifecycleLogger
{
    private static float $hook_start_times = [];
    private static float $request_start_time;
    private static int $request_start_memory;
    
    // Loggt Hook mit Timing und Memory
    private function logHook(string $hook_name): void
    {
        $current_time = microtime(true);
        $current_memory = memory_get_usage(true);
        $previous_hook = $this->getPreviousHook();
        $duration_since_previous = $previous_hook ? $current_time - $this->hook_start_times[$previous_hook] : 0;
        $memory_delta = $current_memory - self::$request_start_memory;
        
        Logger::info("WP Lifecycle Hook fired", [
            'hook' => $hook_name,
            'timestamp' => $current_time,
            'duration_since_previous' => $duration_since_previous,
            'memory_usage' => $current_memory,
            'memory_delta' => $memory_delta,
            'request_type' => (new CurrentRequestTypeProvider())->type(),
        ]);
    }
}
```

### 3.3 Plugin-Initialisierungs-Logging

**Zweck**: neo-dashboard Initialisierungsschritte loggen

**Bereiche**:
1. **Bootstrap-Initialisierung**
   - Plugin-Laden
   - Hook-Registrierung
   - Manager-Initialisierung

2. **Dashboard-Initialisierung**
   - Manager-Registrierung
   - Hook-Ausführung
   - Asset-Registrierung

3. **Plugin-Registrierungen**
   - Section-Registrierungen
   - Asset-Registrierungen
   - Komponenten-Registrierungen

**Struktur**:
```php
// In Bootstrap.php
PerformanceTimer::start('bootstrap', 'plugin_load');
// ... Initialisierung ...
PerformanceTimer::stop('bootstrap', 'plugin_load');

// In Dashboard.php
PerformanceTimer::start('dashboard', 'manager_registration');
// ... Manager-Registrierung ...
PerformanceTimer::stop('dashboard', 'manager_registration');
```

### 3.4 Strukturierte Log-Formatierung

**Format**:
```
[YYYY-MM-DD HH:MM:SS] [LEVEL] [REQUEST_TYPE] [CATEGORY] Message | Data: {...}
```

**Beispiele**:
```
[2026-01-11 16:38:00] [INFO] [WEB] [LIFECYCLE] WP Hook: init fired | Data: {"hook":"init","duration_since_previous":0.0234,"memory_usage":5242880,"memory_delta":1048576}
[2026-01-11 16:38:00] [INFO] [WEB] [BOOTSTRAP] Plugin loaded | Data: {"duration":0.0123,"memory_delta":524288}
[2026-01-11 16:38:00] [INFO] [WEB] [DASHBOARD] Manager registered | Data: {"duration":0.0045,"memory_delta":262144}
[2026-01-11 16:38:00] [INFO] [WEB] [ASSETS] Assets loaded | Data: {"context":"neo-calendar/projects","count":3,"duration":0.0023}
```

### 3.5 Performance-Zusammenfassung

**Zweck**: Zusammenfassung am Ende des Requests

**Informationen**:
- Gesamte Request-Dauer
- Dauer pro Kategorie
- Memory-Peak
- Anzahl der Hooks
- Anzahl der Assets geladen
- Anzahl der Sections registriert

**Ausgabe**:
```php
// Am Ende des Requests (shutdown Hook)
PerformanceTimer::logSummary();
```

**Format**:
```
[2026-01-11 16:38:01] [INFO] [WEB] [PERFORMANCE] Request Summary | Data: {
    "total_duration": 0.2345,
    "memory_peak": 8388608,
    "memory_delta": 4194304,
    "categories": {
        "bootstrap": 0.0123,
        "dashboard": 0.0045,
        "assets": 0.0023,
        "rendering": 0.2154
    },
    "hooks_fired": 15,
    "assets_loaded": 8,
    "sections_registered": 16
}
```

---

## 4. Implementierungsplan

### Phase 1: PerformanceTimer-Klasse

**Datei:** `wp-content/plugins/neo-dashboard/src/PerformanceTimer.php`

**Funktionen**:
- Start/Stop-Timer
- Memory-Tracking
- Zusammenfassung
- Logging-Integration

**Risiko:** Niedrig (neue Klasse)

### Phase 2: Erweiterter LifecycleLogger

**Datei:** `wp-content/plugins/neo-dashboard/src/LifecycleLogger.php`

**Änderungen**:
- Timing zwischen Hooks
- Memory-Usage bei jedem Hook
- Request-Start-Tracking
- Zusammenfassung am Ende

**Risiko:** Niedrig (erweitert bestehende Klasse)

### Phase 3: Bootstrap-Logging

**Datei:** `wp-content/plugins/neo-dashboard/src/Bootstrap.php`

**Änderungen**:
- PerformanceTimer-Integration
- Initialisierungs-Schritte loggen
- Timing-Informationen

**Risiko:** Niedrig (nur Logging hinzufügen)

### Phase 4: Dashboard-Logging

**Datei:** `wp-content/plugins/neo-dashboard/src/Dashboard.php`

**Änderungen**:
- PerformanceTimer-Integration
- Manager-Registrierung loggen
- Timing-Informationen

**Risiko:** Niedrig (nur Logging hinzufügen)

### Phase 5: AssetManager-Logging

**Datei:** `wp-content/plugins/neo-dashboard/src/Manager/AssetManager.php`

**Änderungen**:
- PerformanceTimer-Integration
- Asset-Loading-Timing
- Zusammenfassung der geladenen Assets

**Risiko:** Niedrig (nur Logging hinzufügen)

### Phase 6: Logger-Erweiterung

**Datei:** `wp-content/plugins/neo-dashboard/src/Logger.php`

**Änderungen**:
- Kategorien-Support
- Request-Typ-Integration
- Verbesserte Formatierung

**Risiko:** Niedrig (erweitert bestehende Klasse)

---

## 5. Detaillierte Code-Änderungen

### 5.1 PerformanceTimer.php

```php
<?php
declare(strict_types=1);

namespace NeoDashboard\Core;

/**
 * Performance-Timer für Timing- und Memory-Messungen
 */
class PerformanceTimer
{
    private static array $timings = [];
    private static array $memory_snapshots = [];
    private static float $request_start_time;
    private static int $request_start_memory;
    private static bool $initialized = false;
    
    /**
     * Initialisiert den Timer (wird automatisch beim ersten Aufruf gemacht)
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }
        
        self::$request_start_time = microtime(true);
        self::$request_start_memory = memory_get_usage(true);
        self::$initialized = true;
    }
    
    /**
     * Startet einen Timer für eine Operation
     */
    public static function start(string $category, string $operation): void
    {
        self::init();
        
        $key = "{$category}:{$operation}";
        self::$timings[$key] = [
            'category' => $category,
            'operation' => $operation,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
        ];
    }
    
    /**
     * Stoppt einen Timer für eine Operation
     */
    public static function stop(string $category, string $operation): float
    {
        self::init();
        
        $key = "{$category}:{$operation}";
        if (!isset(self::$timings[$key])) {
            return 0.0;
        }
        
        $current_time = microtime(true);
        $current_memory = memory_get_usage(true);
        
        $duration = $current_time - self::$timings[$key]['start_time'];
        $memory_delta = $current_memory - self::$timings[$key]['start_memory'];
        
        self::$timings[$key]['duration'] = $duration;
        self::$timings[$key]['end_time'] = $current_time;
        self::$timings[$key]['end_memory'] = $current_memory;
        self::$timings[$key]['memory_delta'] = $memory_delta;
        
        return $duration;
    }
    
    /**
     * Gibt alle Timings zurück
     */
    public static function getTimings(): array
    {
        return self::$timings;
    }
    
    /**
     * Gibt Timings nach Kategorie gruppiert zurück
     */
    public static function getTimingsByCategory(): array
    {
        $result = [];
        foreach (self::$timings as $key => $timing) {
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
    public static function getMemoryUsage(): int
    {
        return memory_get_usage(true);
    }
    
    /**
     * Gibt den Memory-Delta seit Request-Start zurück
     */
    public static function getMemoryDelta(): int
    {
        self::init();
        return memory_get_usage(true) - self::$request_start_memory;
    }
    
    /**
     * Gibt die Request-Dauer zurück
     */
    public static function getRequestDuration(): float
    {
        self::init();
        return microtime(true) - self::$request_start_time;
    }
    
    /**
     * Loggt eine Performance-Zusammenfassung
     */
    public static function logSummary(): void
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        self::init();
        
        $request_type = (new CurrentRequestTypeProvider())->type();
        $total_duration = self::getRequestDuration();
        $memory_peak = memory_get_peak_usage(true);
        $memory_delta = self::$request_start_memory > 0 ? $memory_peak - self::$request_start_memory : 0;
        
        $categories = self::getTimingsByCategory();
        $category_totals = [];
        foreach ($categories as $category => $timings) {
            $category_totals[$category] = array_sum(array_column($timings, 'duration'));
        }
        
        Logger::info('Request Performance Summary', [
            'request_type' => $request_type,
            'total_duration' => round($total_duration, 4),
            'memory_peak' => $memory_peak,
            'memory_start' => self::$request_start_memory,
            'memory_delta' => $memory_delta,
            'category_totals' => $category_totals,
            'timing_count' => count(self::$timings),
        ]);
    }
}
```

### 5.2 Erweiterter LifecycleLogger

```php
<?php
declare(strict_types=1);

namespace NeoDashboard\Core;

use NeoDashboard\Core\Logger;
use NeoDashboard\Core\PerformanceTimer;

class LifecycleLogger
{
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
    
    private static float $request_start_time;
    private static int $request_start_memory;
    private static array $hook_times = [];
    private static ?string $previous_hook = null;
    private static bool $initialized = false;
    
    public function __construct()
    {
        if (!self::$initialized) {
            self::$request_start_time = microtime(true);
            self::$request_start_memory = memory_get_usage(true);
            self::$initialized = true;
        }
        
        // WordPress Lifecycle Hooks
        foreach ($this->hooks as $hook) {
            add_action($hook, function() use ($hook) {
                $this->logHook($hook);
            }, 0);
        }
        
        // Performance-Zusammenfassung am Ende
        add_action('shutdown', [self::class, 'logPerformanceSummary'], 999);
    }
    
    /**
     * Loggt einen Hook mit Timing und Memory-Informationen
     */
    private function logHook(string $hook_name): void
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $current_time = microtime(true);
        $current_memory = memory_get_usage(true);
        $memory_delta = $current_memory - self::$request_start_memory;
        $duration_since_start = $current_time - self::$request_start_time;
        
        $duration_since_previous = 0.0;
        if (self::$previous_hook !== null && isset(self::$hook_times[self::$previous_hook])) {
            $duration_since_previous = $current_time - self::$hook_times[self::$previous_hook];
        }
        
        self::$hook_times[$hook_name] = $current_time;
        
        Logger::info('WP Lifecycle Hook fired', [
            'hook' => $hook_name,
            'duration_since_start' => round($duration_since_start, 4),
            'duration_since_previous' => round($duration_since_previous, 4),
            'previous_hook' => self::$previous_hook,
            'memory_usage' => $current_memory,
            'memory_delta' => $memory_delta,
            'request_type' => (new CurrentRequestTypeProvider())->type(),
        ]);
        
        self::$previous_hook = $hook_name;
    }
    
    /**
     * Loggt Performance-Zusammenfassung am Ende des Requests
     */
    public static function logPerformanceSummary(): void
    {
        PerformanceTimer::logSummary();
    }
}
```

### 5.3 Logger-Erweiterung

**Erweiterung für Kategorien und Request-Typ**:
```php
public static function log(string $message, array $data = [], string $level = 'INFO', ?string $category = null): void
{
    // ...
    $request_type = (new CurrentRequestTypeProvider())->type();
    $category_prefix = $category ? "[{$category}] " : '';
    
    $line = sprintf(
        "[%s] [%s] [%s] %s%s | Data: %s\n",
        $timestamp,
        strtoupper($level),
        $request_type,
        $category_prefix,
        $message,
        json_encode($data, JSON_UNESCAPED_UNICODE)
    );
    // ...
}
```

### 5.4 Bootstrap-Integration

```php
// In Bootstrap::registerHooks()
PerformanceTimer::start('bootstrap', 'register_hooks');
// ... Code ...
PerformanceTimer::stop('bootstrap', 'register_hooks');

// In Bootstrap::init()
PerformanceTimer::start('bootstrap', 'init');
// ... Code ...
PerformanceTimer::stop('bootstrap', 'init');
```

### 5.5 Dashboard-Integration

```php
// In Dashboard::registerManagers()
PerformanceTimer::start('dashboard', 'register_managers');
// ... Code ...
PerformanceTimer::stop('dashboard', 'register_managers');

// In Dashboard::run()
PerformanceTimer::start('dashboard', 'run');
// ... Code ...
PerformanceTimer::stop('dashboard', 'run');
```

### 5.6 AssetManager-Integration

```php
// In AssetManager::loadPluginAssets()
PerformanceTimer::start('assets', "load_{$type}");
// ... Code ...
$duration = PerformanceTimer::stop('assets', "load_{$type}");

Logger::info("Assets loaded", [
    'context' => $context,
    'type' => $type,
    'count' => count($assets_to_load),
    'duration' => round($duration, 4),
]);
```

---

## 6. Erwartete Ergebnisse

### 6.1 Log-Format

**Vorher**:
```
[2026-01-11 16:38:00] [INFO] WP Lifecycle Hook fired | Data: {"hook":"init","timestamp":1704988680.1234}
```

**Nachher**:
```
[2026-01-11 16:38:00] [INFO] [WEB] [LIFECYCLE] WP Lifecycle Hook fired | Data: {"hook":"init","duration_since_start":0.0234,"duration_since_previous":0.0123,"memory_usage":5242880,"memory_delta":1048576}
[2026-01-11 16:38:01] [INFO] [WEB] [PERFORMANCE] Request Performance Summary | Data: {"total_duration":0.2345,"memory_peak":8388608,"category_totals":{"bootstrap":0.0123,"dashboard":0.0045}}
```

### 6.2 Performance-Analyse

**Möglichkeiten**:
- Identifikation langsamer Operationen
- Memory-Usage-Trends
- Hook-Timing-Analyse
- Kategorie-basierte Performance-Analyse
- Vergleich zwischen Request-Typen

### 6.3 Debugging-Hilfe

- Vollständige Sichtbarkeit des WordPress Lifecycles
- Timing-Informationen für alle wichtigen Operationen
- Memory-Usage-Tracking
- Strukturierte Logs für einfache Analyse

---

## 7. Testing-Strategie

### 7.1 Funktionalitätstests
- Timer start/stop funktioniert
- Memory-Tracking funktioniert
- Zusammenfassung wird geloggt
- Lifecycle-Hooks werden geloggt

### 7.2 Performance-Tests
- Timer-Overhead minimal
- Memory-Overhead minimal
- Log-Datei-Größe akzeptabel

### 7.3 Integration-Tests
- Alle Komponenten integriert
- Logs werden korrekt formatiert
- Zusammenfassung am Ende vorhanden

---

## 8. Risiken und Mitigation

### Risiko 1: Performance-Overhead durch Logging
**Mitigation**:
- Nur bei WP_DEBUG aktiv
- Minimale Operationen
- Strukturiertes Format

### Risiko 2: Log-Datei wird zu groß
**Mitigation**:
- Nur bei WP_DEBUG aktiv
- Rotation möglich
- Kompakte Formatierung

### Risiko 3: Zu viele Logs
**Mitigation**:
- Strukturierte Kategorien
- Wichtige Events priorisieren
- Konfigurierbare Log-Level

---

## 9. Zusammenfassung

**Hauptziele**:
1. Performance-Analyse durch Timing-Informationen
2. WordPress Lifecycle-Sichtbarkeit
3. Strukturiertes Debug-Logging
4. Memory-Usage-Tracking

**Implementierung**:
1. PerformanceTimer-Klasse
2. Erweiterter LifecycleLogger
3. Integration in Bootstrap, Dashboard, AssetManager
4. Logger-Erweiterung für Kategorien

**Erwartete Verbesserung**:
- Vollständige Performance-Sichtbarkeit
- Einfache Identifikation von Bottlenecks
- Strukturierte Logs für Analyse
- Memory-Usage-Tracking

**Risiko**: Niedrig (nur Logging, keine Funktionalitätsänderungen)
