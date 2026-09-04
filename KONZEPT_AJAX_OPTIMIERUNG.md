# Konzept: AJAX-Optimierung für neo-dashboard

> Historischer Entwurf; die Beispiele wurden auf die aktuelle
> `CurrentRequestTypeProvider::type()`-API aktualisiert.

## 1. Problemanalyse

### 1.1 Aktuelles Verhalten

Bei jedem AJAX-Request passiert folgendes:

1. **WordPress Plugin-Lebenszyklus**
   - Alle Plugins werden neu geladen (`plugins_loaded`)
   - Alle Hooks werden neu ausgeführt (`init`)
   - Bootstrap::init() wird aufgerufen

2. **neo-dashboard Initialisierung**
   - `Dashboard::run()` wird aufgerufen
   - `neo_dashboard_pre_init` Hook wird gefeuert
   - `neo_dashboard_init` Hook wird gefeuert
   - Alle Manager werden registriert

3. **Plugin-Registrierungen**
   - Alle Plugins (neo-calendar, neo-contacts, neo-surveys) registrieren ihre Sections
   - LanguageManager wird initialisiert
   - Assets werden registriert (obwohl sie nicht benötigt werden)

### 1.2 Performance-Probleme

#### Problem 1: `neo_dashboard_init` wird bei AJAX gefeuert
**Aktueller Code:**
```php
// wp-content/plugins/neo-dashboard/src/Bootstrap.php:153
public static function init(): void
{
    $router = new Router();
    $router->registerHooks();
    
    $dashboard = new Dashboard();
    $dashboard->run(); // <-- Wird auch bei AJAX aufgerufen!
}
```

**Problem:**
- `Dashboard::run()` feuert `neo_dashboard_init`
- Dieser Hook triggert alle Plugin-Registrierungen
- Sections, Assets, Widgets werden registriert (obwohl nicht benötigt)

**Impact:**
- ~50-100ms Overhead pro AJAX-Request
- Unnötige Initialisierung von Objekten
- Mehrfache LanguageManager-Aufrufe

#### Problem 2: Sections werden bei jedem Request neu registriert
**Aktueller Code:**
```php
// wp-content/plugins/neo-dashboard/src/Manager/SectionManager.php:31
public function register(array $args): string
{
    // Prüfe ob Section bereits registriert ist
    if (isset($registry->getSections()[$slug])) {
        return $slug; // Early return vorhanden
    }
    // ...
}
```

**Problem:**
- Sections werden bei jedem Request versucht zu registrieren
- Registry prüft zwar, aber Hook-Aufrufe passieren trotzdem
- Callback-Funktionen werden ausgeführt (loadTextdomain, etc.)

**Impact:**
- ~10-20ms Overhead pro Request
- Mehrfache String-Übersetzungen

#### Problem 3: LanguageManager wird bei jedem Request initialisiert
**Aktueller Code:**
```php
// wp-content/plugins/neo-dashboard/src/Manager/LanguageManager.php:60
add_filter('neo_dashboard_get_language_for_plugin', [$this, 'getLanguageForPlugin'], 10, 2);
```

**Problem:**
- Filter wird bei jedem String-Export aufgerufen
- Plugin-Registrierung wird bei jedem Request neu geloggt
- Kein Caching auf System-Ebene

**Impact:**
- ~5-10ms Overhead pro Request
- Sehr viele Log-Einträge

#### Problem 4: Keine Unterscheidung zwischen Request-Typen
**Aktueller Code:**
```php
// wp-content/plugins/neo-dashboard/src/Dashboard.php:61
public function run(): void
{
    $this->registerManagers();
    do_action('neo_dashboard_pre_init');
    do_action('neo_dashboard_init'); // <-- Keine AJAX-Prüfung!
}
```

**Problem:**
- `run()` wird bei allen Request-Typen aufgerufen
- Keine Early-Returns für AJAX/REST/CRON
- Manager werden unnötig registriert

**Impact:**
- ~20-30ms Overhead pro AJAX-Request

### 1.3 Messbare Auswirkungen

**Aktuelle Situation:**
- Seitenladezeit: ~200-300ms (Frontend)
- AJAX-Request: ~50-100ms Overhead (unnötig)
- Log-Einträge: ~50-100 pro AJAX-Request

**Nach Optimierung (Ziel):**
- Seitenladezeit: ~200-300ms (unverändert)
- AJAX-Request: ~5-10ms Overhead (nur notwendige Initialisierung)
- Log-Einträge: ~5-10 pro AJAX-Request

**Erwartete Verbesserung:**
- 80-90% weniger Overhead bei AJAX-Requests
- 80-90% weniger Log-Einträge
- Bessere Performance bei vielen AJAX-Requests

---

## 2. Optimierungskonzept

### 2.1 Grundprinzipien

1. **Early Return bei AJAX**
   - Dashboard-Initialisierung nur bei Frontend/Admin
   - Sections nur bei Bedarf registrieren
   - Assets nicht bei AJAX laden

2. **Request-Typ-Erkennung**
   - Klare Unterscheidung: WEB, AJAX, REST, CRON, CLI
   - Verschiedene Initialisierungs-Pfade je nach Typ

3. **Caching auf System-Ebene**
   - Sections einmalig registrieren
   - Language-Registrierungen cachen
   - Manager-Instanzen wiederverwenden

4. **Selektive Initialisierung**
   - Nur notwendige Manager bei AJAX
   - Router-Hooks nur bei WEB
   - Asset-Registrierung nur bei WEB

### 2.2 Architektur-Änderungen

#### Änderung 1: Dashboard::run() mit Request-Typ-Prüfung

**Aktuell:**
```php
public function run(): void
{
    $this->registerManagers();
    do_action('neo_dashboard_pre_init');
    do_action('neo_dashboard_init');
}
```

**Neu:**
```php
public function run(): void
{
    $request_type = (new CurrentRequestTypeProvider())->type();
    
    // Bei AJAX/REST/CRON/CLI: Nur notwendige Manager registrieren
    if (in_array($request_type, ['AJAX', 'REST', 'CRON', 'CLI'])) {
        $this->registerMinimalManagers();
        return;
    }
    
    // Bei WEB: Vollständige Initialisierung
    $this->registerManagers();
    do_action('neo_dashboard_pre_init');
    do_action('neo_dashboard_init');
}
```

#### Änderung 2: Minimal-Manager für AJAX

**Neue Methode:**
```php
private function registerMinimalManagers(): void
{
    // Nur AjaxManager und LanguageManager bei AJAX
    // Sections, Assets, Widgets werden nicht benötigt
    $this->ajaxManager->register();
    // LanguageManager wird lazy geladen, wenn benötigt
}
```

#### Änderung 3: Plugin-spezifische Early-Returns

**In Plugins (z.B. neo-calendar):**
```php
public function registerComponents(): void
{
    // Early Return bei AJAX - Sections werden nicht benötigt
    if ((new CurrentRequestTypeProvider())->type() === 'AJAX') {
        // Nur AJAX-Handler registrieren, keine Sections/Assets
        return;
    }
    
    // Normale Registrierung für WEB-Requests
    // ...
}
```

#### Änderung 4: Optimierte Router-Hooks

**Aktuell:**
```php
public function registerHooks(): void
{
    $this->registerRoutes();
    add_filter('query_vars', [$this, 'addQueryVars']);
    add_filter('template_include', [$this, 'maybe_load_dashboard_template'], 99);
    // ...
}
```

**Neu:**
```php
public function registerHooks(): void
{
    // Nur bei WEB-Requests
    if ((new CurrentRequestTypeProvider())->type() !== 'WEB') {
        return;
    }
    
    $this->registerRoutes();
    add_filter('query_vars', [$this, 'addQueryVars']);
    add_filter('template_include', [$this, 'maybe_load_dashboard_template'], 99);
    // ...
}
```

---

## 3. Implementierungsplan

### Phase 1: Core-Optimierungen (neo-dashboard)

#### 1.1 Dashboard::run() optimieren
**Datei:** `wp-content/plugins/neo-dashboard/src/Dashboard.php`

**Änderungen:**
- Request-Typ-Prüfung hinzufügen
- `registerMinimalManagers()` Methode erstellen
- Early Return bei AJAX/REST/CRON/CLI

**Risiko:** Niedrig (nur neue Methode, bestehende bleibt)

#### 1.2 Router-Hooks optimieren
**Datei:** `wp-content/plugins/neo-dashboard/src/Router.php`

**Änderungen:**
- Request-Typ-Prüfung in `registerHooks()`
- Early Return bei non-WEB Requests

**Risiko:** Niedrig (nur Prüfung hinzufügen)

#### 1.3 Bootstrap::init() optimieren
**Datei:** `wp-content/plugins/neo-dashboard/src/Bootstrap.php`

**Änderungen:**
- Request-Typ-Prüfung vor Dashboard-Run
- Router-Hooks nur bei WEB

**Risiko:** Niedrig (Early Return)

#### 1.4 LanguageManager optimieren
**Datei:** `wp-content/plugins/neo-dashboard/src/Manager/LanguageManager.php`

**Änderungen:**
- Plugin-Registrierung cachen (bereits vorhanden)
- Filter-Caching für `getLanguageForPlugin()`
- Reduzierte Logging bei AJAX

**Risiko:** Mittel (Caching-Logik)

### Phase 2: Plugin-Optimierungen (neo-calendar, etc.)

#### 2.1 registerComponents() optimieren
**Datei:** `wp-content/plugins/neo-calendar/src/Manager/DashboardManager.php`

**Änderungen:**
- Early Return bei AJAX-Requests
- Sections/Assets nur bei WEB registrieren
- AJAX-Handler bereits separat registriert (keine Änderung nötig)

**Risiko:** Niedrig (Early Return)

#### 2.2 Andere Plugins
- neo-contacts
- neo-surveys
- neo-templates

**Änderungen:**
- Gleiche Optimierung wie neo-calendar

**Risiko:** Niedrig (konsistentes Pattern)

### Phase 3: Registry-Optimierungen

#### 3.1 Statisches Caching
**Datei:** `wp-content/plugins/neo-dashboard/src/Registry.php`

**Änderungen:**
- Prüfung bereits vorhanden (`addSection()` prüft auf Duplikate)
- Optimierung: Prüfung vor Hook-Aufruf

**Risiko:** Niedrig (nur Logik-Verbesserung)

---

## 4. Detaillierte Code-Änderungen

### 4.1 Dashboard.php

```php
/**
 * Führt die Dashboard‑Initialisierung aus: ruft registerHooks() auf
 */
public function run(): void
{
    $request_type = (new \NeoDashboard\Core\Http\CurrentRequestTypeProvider())->type();
    
    // Bei AJAX/REST/CRON/CLI: Nur notwendige Manager registrieren
    if (in_array($request_type, ['AJAX', 'REST', 'CRON', 'CLI'], true)) {
        $this->registerMinimalManagers();
        return;
    }
    
    // Bei WEB: Vollständige Initialisierung
    $this->registerManagers();
    do_action('neo_dashboard_pre_init');
    do_action('neo_dashboard_init');
}

/**
 * Registriert nur notwendige Manager für AJAX/REST/CRON/CLI
 */
private function registerMinimalManagers(): void
{
    // Nur AjaxManager bei AJAX/REST (für AJAX-Handler)
    // LanguageManager wird lazy geladen, wenn benötigt
    // Sections, Assets, Widgets werden nicht benötigt
    // (Assets werden client-seitig nicht geladen bei AJAX)
}
```

### 4.2 Router.php

```php
/** Registriert alle Hooks für das Dashboard */
public function registerHooks(): void
{
    // Nur bei WEB-Requests (Frontend/Admin)
    if ((new \NeoDashboard\Core\Http\CurrentRequestTypeProvider())->type() !== 'WEB') {
        return;
    }
    
    $this->registerRoutes();
    add_filter('query_vars', [ $this, 'addQueryVars' ]);
    add_filter('template_include', [ $this, 'maybe_load_dashboard_template' ], 99 );
    // ...
}
```

### 4.3 Bootstrap.php

```php
public static function init(): void
{
    $request_type = (new \NeoDashboard\Core\Http\CurrentRequestTypeProvider())->type();
    
    // Router-Hooks nur bei WEB-Requests
    if ($request_type === 'WEB') {
        $router = new Router();
        $router->registerHooks();
    }
    
    // Dashboard initialisieren (entscheidet intern, ob minimal oder vollständig)
    $dashboard = new Dashboard();
    $dashboard->run();
}
```

### 4.4 DashboardManager.php (neo-calendar)

```php
public function registerComponents(): void
{
    // Early Return bei AJAX/REST/CRON/CLI - Sections werden nicht benötigt
    $request_type = (new \NeoDashboard\Core\Http\CurrentRequestTypeProvider())->type();
    if (in_array($request_type, ['AJAX', 'REST', 'CRON', 'CLI'], true)) {
        // AJAX-Handler werden bereits separat in AjaxManager::register() registriert
        // Sections und Assets werden nur bei WEB benötigt
        return;
    }
    
    // Normale Registrierung für WEB-Requests
    // ...
}
```

### 4.5 LanguageManager.php

```php
/**
 * Cache für Plugin-Sprachabfragen (verhindert mehrfache Filter-Aufrufe)
 */
private static array $cachedPluginLanguages = [];

/**
 * Gibt die Sprache für ein Plugin zurück (mit Caching)
 */
public function getLanguageForPlugin(string $language_code, string $plugin_id): string
{
    // Cache-Key generieren
    $cache_key = "{$plugin_id}:{$language_code}";
    
    // Cache: Sprache wurde bereits ermittelt
    if (isset(self::$cachedPluginLanguages[$cache_key])) {
        return self::$cachedPluginLanguages[$cache_key];
    }
    
    // Normale Logik (existiert bereits)
    $language = $this->determineLanguageForPlugin($language_code, $plugin_id);
    
    // Cache speichern
    self::$cachedPluginLanguages[$cache_key] = $language;
    
    return $language;
}
```

---

## 5. Testing-Strategie

### 5.1 Unit-Tests
- Request-Typ-Erkennung testen
- Early-Returns bei AJAX testen
- Caching-Funktionalität testen

### 5.2 Integration-Tests
- AJAX-Handler funktionieren weiterhin
- Sections werden korrekt registriert (bei WEB)
- Assets werden korrekt geladen (bei WEB)

### 5.3 Performance-Tests
- Messung der AJAX-Request-Zeit vor/nach Optimierung
- Log-Einträge zählen vor/nach Optimierung
- Memory-Usage prüfen

### 5.4 Regression-Tests
- Alle bestehenden Funktionen testen
- Verschiedene Request-Typen testen (WEB, AJAX, REST, CRON, CLI)
- Verschiedene Plugins testen (neo-calendar, neo-contacts, neo-surveys)

---

## 6. Migrations-Strategie

### 6.1 Schrittweise Einführung
1. **Phase 1:** Core-Optimierungen (neo-dashboard)
   - Testen mit einem Plugin (neo-calendar)
   - Logs prüfen, Performance messen

2. **Phase 2:** Plugin-Optimierungen
   - Alle Plugins nacheinander optimieren
   - Rückwärtskompatibilität sicherstellen

3. **Phase 3:** Registry-Optimierungen
   - Weitere Performance-Verbesserungen
   - Finale Tests

### 6.2 Rückwärtskompatibilität
- Bestehende Hooks bleiben funktionsfähig
- Alte Plugins ohne Optimierung funktionieren weiterhin
- Neue Plugins können Optimierung nutzen

### 6.3 Fallback-Mechanismen
- Bei Fehlern: Vollständige Initialisierung
- Debug-Modus: Altes Verhalten für Vergleich
- Logging: Unterschiedliche Logs für optimiert/nicht-optimiert

---

## 7. Erwartete Ergebnisse

### 7.1 Performance-Verbesserungen

**AJAX-Requests:**
- Vorher: ~50-100ms Overhead
- Nachher: ~5-10ms Overhead
- **Verbesserung: 80-90%**

**Log-Einträge:**
- Vorher: ~50-100 pro AJAX-Request
- Nachher: ~5-10 pro AJAX-Request
- **Verbesserung: 80-90%**

**Memory-Usage:**
- Vorher: ~5-10 MB zusätzlich pro AJAX-Request
- Nachher: ~1-2 MB zusätzlich pro AJAX-Request
- **Verbesserung: 70-80%**

### 7.2 Code-Qualität
- Klare Trennung zwischen Request-Typen
- Bessere Wartbarkeit
- Konsistentes Pattern für Plugins

### 7.3 Skalierbarkeit
- Bessere Performance bei vielen AJAX-Requests
- Reduzierte Server-Last
- Schnellere Response-Zeiten

---

## 8. Risiken und Mitigation

### 8.1 Risiken

**Risiko 1: Breaking Changes**
- **Mitigation:** Rückwärtskompatibilität sicherstellen
- **Test:** Umfangreiche Regression-Tests

**Risiko 2: Plugins ohne Optimierung**
- **Mitigation:** Alte Plugins funktionieren weiterhin
- **Test:** Testen mit nicht-optimierten Plugins

**Risiko 3: Caching-Probleme**
- **Mitigation:** Cache-Invalidierung bei Sprachänderung
- **Test:** Cache-Verhalten testen

### 8.2 Rollback-Plan
- Feature-Flag für Optimierungen
- Altes Verhalten bei Bedarf reaktivierbar
- Logs für Debugging

---

## 9. Nächste Schritte

### 9.1 Sofort umsetzbar (Phase 1)
1. ✅ `Dashboard::run()` optimieren
2. ✅ `Router::registerHooks()` optimieren
3. ✅ `Bootstrap::init()` optimieren
4. ✅ `LanguageManager` Caching verbessern

### 9.2 Mittelfristig (Phase 2)
1. ✅ Plugin-Optimierungen (neo-calendar)
2. ✅ Weitere Plugins optimieren
3. ✅ Tests durchführen

### 9.3 Langfristig (Phase 3)
1. ✅ Registry-Optimierungen
2. ✅ Weitere Performance-Verbesserungen
3. ✅ Dokumentation aktualisieren

---

## 10. Zusammenfassung

**Hauptproblem:**
- `neo_dashboard_init` wird bei jedem Request (auch AJAX) gefeuert
- Unnötige Initialisierung von Sections, Assets, Widgets
- Performance-Overhead bei AJAX-Requests

**Lösung:**
- Request-Typ-Erkennung in Core
- Early Returns bei AJAX/REST/CRON/CLI
- Minimale Initialisierung für AJAX
- Caching auf System-Ebene

**Erwartete Verbesserung:**
- 80-90% weniger Overhead bei AJAX-Requests
- 80-90% weniger Log-Einträge
- Bessere Performance bei vielen AJAX-Requests

**Risiko:**
- Niedrig (rückwärtskompatibel)
- Schrittweise Einführung möglich
- Rollback-Plan vorhanden
