# Konzept: Verbesserte Sprach-Registrierung für Plugins

## Problem-Analyse

### Aktuelles Problem
Die Plugin-Registrierung funktioniert nicht zuverlässig, weil:
1. **Timing-Problem**: Der `LanguageManager` wird im `Dashboard`-Konstruktor erstellt, der beim `init` Hook ausgeführt wird
2. **Reihenfolge-Problem**: Plugins versuchen sich beim `init` Hook (Priority 1) zu registrieren, aber die Reihenfolge ist nicht garantiert
3. **Hook-Abhängigkeit**: Die Registrierung hängt von einem Action-Hook ab, der möglicherweise nicht rechtzeitig ausgeführt wird

### WordPress Hook-Reihenfolge
```
1. plugins_loaded    - Alle Plugins sind geladen
2. init              - WordPress ist initialisiert, User ist verfügbar
3. wp_loaded         - Alles ist geladen
```

## Lösungsansätze

### Ansatz 1: Statisches Registry-Pattern (EMPFOHLEN) ⭐

**Prinzip**: Plugins registrieren ihre Sprachen in einem statischen Array, das der `LanguageManager` beim Initialisieren abruft.

**Vorteile**:
- ✅ Keine Hook-Abhängigkeit
- ✅ Funktioniert unabhängig von der Ausführungsreihenfolge
- ✅ Einfach zu implementieren
- ✅ Thread-safe (statisches Array)

**Nachteile**:
- ⚠️ Statisches Array muss verwaltet werden

**Implementierung**:
```php
// In LanguageManager.php
class LanguageManager
{
    private static array $pluginLanguagesRegistry = [];
    
    public static function registerPluginLanguages(string $plugin_id, array $languages): void
    {
        self::$pluginLanguagesRegistry[$plugin_id] = $languages;
    }
    
    public function __construct()
    {
        // Beim Initialisieren: Statisches Registry laden
        $this->pluginLanguages = self::$pluginLanguagesRegistry;
    }
}

// In Plugin Bootstrap.php
// Kann zu jedem Zeitpunkt aufgerufen werden (z.B. beim Laden der Datei)
LanguageManager::registerPluginLanguages('neo-calendar', [
    'de_DE' => 'Deutsch',
    'en_US' => 'English',
    'uk_UA' => 'Українська',
]);
```

### Ansatz 2: Lazy Registration mit Fallback

**Prinzip**: Die Plugins registrieren sich erst, wenn sie tatsächlich benötigt werden (beim ersten Aufruf von `getLanguageForPlugin()`).

**Vorteile**:
- ✅ Keine Timing-Probleme
- ✅ Automatische Registrierung bei Bedarf

**Nachteile**:
- ⚠️ Komplexere Implementierung
- ⚠️ Mögliche Performance-Probleme bei vielen Plugins

**Implementierung**:
```php
// In LanguageManager.php
public function getLanguageForPlugin(string $plugin_id, ?string $requested_language = null): string
{
    // Wenn Plugin nicht registriert: Versuche es jetzt zu registrieren
    if (!isset($this->pluginLanguages[$plugin_id])) {
        do_action('neo_dashboard_register_languages', $plugin_id);
        
        // Wenn immer noch nicht registriert: Fallback
        if (!isset($this->pluginLanguages[$plugin_id])) {
            return $this->defaultLanguage;
        }
    }
    
    // ... restliche Logik
}
```

### Ansatz 3: Frühe Registrierung beim `plugins_loaded` Hook

**Prinzip**: Die Plugins registrieren sich beim `plugins_loaded` Hook mit einer späteren Priority (z.B. 20), nachdem der `LanguageManager` initialisiert wurde.

**Vorteile**:
- ✅ Nutzt WordPress Hook-System
- ✅ Klare Reihenfolge

**Nachteile**:
- ⚠️ Abhängig von der Initialisierungsreihenfolge
- ⚠️ Funktioniert nicht, wenn `LanguageManager` später initialisiert wird

**Implementierung**:
```php
// In Plugin Bootstrap.php
add_action('plugins_loaded', function () {
    if (class_exists(\NeoDashboard\Core\Manager\LanguageManager::class)) {
        $languageManager = \NeoDashboard\Core\Manager\LanguageManager::getInstance();
        $languageManager->registerPluginLanguages('neo-calendar', [
            'de_DE' => 'Deutsch',
            'en_US' => 'English',
            'uk_UA' => 'Українська',
        ]);
    }
}, 20);
```

### Ansatz 4: Direkter Zugriff mit Singleton-Pattern

**Prinzip**: Der `LanguageManager` wird als Singleton implementiert, und die Plugins können direkt darauf zugreifen.

**Vorteile**:
- ✅ Direkter Zugriff ohne Hooks
- ✅ Garantierte Verfügbarkeit

**Nachteile**:
- ⚠️ Singleton-Pattern kann problematisch sein
- ⚠️ Enge Kopplung zwischen Plugins und `LanguageManager`

## Empfohlene Lösung: Ansatz 1 (Statisches Registry-Pattern)

### Implementierungsplan

#### Phase 1: LanguageManager erweitern
1. Statisches Array `$pluginLanguagesRegistry` hinzufügen
2. Statische Methode `registerPluginLanguages()` hinzufügen
3. Im Konstruktor: Statisches Registry in Instanz-Variable laden
4. Bestehende `registerPluginLanguages()`-Methode als Wrapper beibehalten (für Rückwärtskompatibilität)

#### Phase 2: Plugins anpassen
1. Plugins registrieren ihre Sprachen direkt beim Laden der Datei (nicht in einem Hook)
2. Verwenden statische Methode `LanguageManager::registerPluginLanguages()`

#### Phase 3: Action-Hook beibehalten (optional)
1. Action-Hook `neo_dashboard_register_languages` bleibt für Rückwärtskompatibilität
2. Ruft intern die statische Methode auf

### Code-Beispiel

```php
// wp-content/plugins/neo-dashboard/src/Manager/LanguageManager.php
class LanguageManager
{
    private static array $pluginLanguagesRegistry = [];
    
    private array $pluginLanguages = [];
    
    /**
     * Statische Registrierung (kann zu jedem Zeitpunkt aufgerufen werden)
     */
    public static function registerPluginLanguages(string $plugin_id, array $languages): void
    {
        self::$pluginLanguagesRegistry[$plugin_id] = $languages;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Neo Dashboard LanguageManager] Plugin {$plugin_id} registered languages: " . implode(', ', array_keys($languages)));
        }
    }
    
    public function __construct()
    {
        // Statisches Registry in Instanz-Variable laden
        $this->pluginLanguages = self::$pluginLanguagesRegistry;
        
        // Action-Hook für dynamische Registrierung (Rückwärtskompatibilität)
        add_action('neo_dashboard_register_languages', [$this, 'registerPluginLanguagesInstance'], 10, 2);
        
        // ... restliche Initialisierung
    }
    
    /**
     * Instanz-Methode (für Action-Hook)
     */
    public function registerPluginLanguagesInstance(string $plugin_id, array $languages): void
    {
        self::registerPluginLanguages($plugin_id, $languages);
        $this->pluginLanguages[$plugin_id] = $languages;
    }
}

// wp-content/plugins/neo-calendar/src/Bootstrap.php
// Registrierung beim Laden der Datei (kein Hook nötig)
if (class_exists(\NeoDashboard\Core\Manager\LanguageManager::class)) {
    \NeoDashboard\Core\Manager\LanguageManager::registerPluginLanguages('neo-calendar', [
        'de_DE' => 'Deutsch',
        'en_US' => 'English',
        'uk_UA' => 'Українська',
    ]);
}
```

## Migration

### Schritt 1: LanguageManager erweitern
- Statisches Registry hinzufügen
- Statische Methode implementieren
- Bestehende Methode als Wrapper beibehalten

### Schritt 2: Plugins migrieren
- Direkte Registrierung beim Laden der Datei
- Hook-basierte Registrierung entfernen

### Schritt 3: Testen
- Alle Plugins testen
- Logs prüfen
- Übersetzungen verifizieren

## Vorteile der empfohlenen Lösung

1. **Keine Timing-Probleme**: Registrierung funktioniert unabhängig von Hooks
2. **Einfache Implementierung**: Minimaler Code-Overhead
3. **Rückwärtskompatibilität**: Bestehende Action-Hooks funktionieren weiterhin
4. **Thread-safe**: Statisches Array ist sicher
5. **Performance**: Keine Hook-Overhead

## Nachteile

1. **Statisches Array**: Muss verwaltet werden
2. **Direkte Abhängigkeit**: Plugins müssen `LanguageManager`-Klasse kennen

## Fazit

Das **Statische Registry-Pattern** ist die beste Lösung, weil:
- ✅ Es die Timing-Probleme vollständig löst
- ✅ Es einfach zu implementieren ist
- ✅ Es rückwärtskompatibel ist
- ✅ Es performant ist

Die Implementierung sollte in 3 Phasen erfolgen, wobei Phase 1 (LanguageManager erweitern) die wichtigste ist.

