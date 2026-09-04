# Konzept: Performance-Optimierung für normale Seitenaufrufe (WEB)

> Historischer Entwurf. Verweise auf `src/Helper.php` beschreiben den damaligen
> Zustand; die Klasse wurde nach Migration aller Verbraucher entfernt.

## 1. Problemanalyse basierend auf Debug-Log

### 1.1 Identifizierte Performance-Probleme

#### Problem 1: AssetManager - Übermäßiges Logging
**Aktuelles Verhalten:**
- Jedes Asset wird geloggt, auch wenn es nicht geladen wird
- "Skipping asset..." wird für jedes nicht passende Asset geloggt
- "Processing plugin..." wird für jedes Plugin geloggt
- "Available plugins..." wird bei jedem Aufruf geloggt

**Beispiel aus Log:**
```
[16:04:19] AssetManager: Processing plugin: neo-contacts
[16:04:19] AssetManager: Asset neo-contacts-core contexts: Array(...), current: neo-calendar/projects
[16:04:19] AssetManager: Successfully enqueued CSS: neo-contacts-core...
[16:04:19] AssetManager: Asset neo-contacts-common contexts: Array(...), current: neo-calendar/projects
[16:04:19] AssetManager: Skipping asset neo-contacts-common for context neo-calendar/projects
[16:04:19] AssetManager: Asset neo-contacts-organizations contexts: Array(...), current: neo-calendar/projects
[16:04:19] AssetManager: Skipping asset neo-contacts-organizations for context neo-calendar/projects
... (viele weitere "Skipping" Logs)
```

**Impact:**
- ~30-50 Log-Einträge pro Seitenaufruf nur für Asset-Verarbeitung
- Viele redundante Informationen
- Performance-Overhead durch String-Operationen und Array-Dumps

#### Problem 2: SectionManager - Logging bei jeder Registrierung
**Aktuelles Verhalten:**
- Jede Section-Registrierung wird geloggt
- Auch bei normalen Operationen (nicht nur Fehler)

**Beispiel aus Log:**
```
[16:04:15] [Neo Dashboard SectionManager] [WEB] register() - slug: 'neo-contacts/dashboard'
[16:04:15] [Neo Dashboard SectionManager] [WEB] register() - slug: 'neo-contacts/organizations'
[16:04:16] [Neo Dashboard SectionManager] [WEB] register() - slug: 'neo-contacts/people'
... (viele weitere Registrierungen)
```

**Impact:**
- ~15-20 Log-Einträge pro Seitenaufruf
- Redundante Informationen (Sections werden nur einmal registriert)

#### Problem 3: Registry - Übermäßiges Logging bei getSection()
**Aktuelles Verhalten:**
- `getSection()` loggt bei jedem Aufruf
- Array-Dumps von Section-Daten (inkl. Closure-Objekte)
- Alle verfügbaren Sections werden geloggt

**Beispiel aus Log:**
```
[16:04:18] [Neo Dashboard Registry] [WEB] getSection() called - slug: 'neo-calendar/projects'
[16:04:18] [Neo Dashboard Registry] [WEB] getSection() - available sections: neo-contacts/dashboard, ...
[16:04:18] [Neo Dashboard Registry] [WEB] getSection() - section data before translation: Array(...)
[16:04:18] [Neo Dashboard Registry] [WEB] getSection() - translated label: 'Projekte'
```

**Impact:**
- ~5-10 Log-Einträge pro `getSection()` Aufruf
- Große Array-Dumps (inkl. Closure-Objekte)
- Performance-Overhead durch `print_r()`

#### Problem 4: Helper - Übermäßiges Logging bei getPageTitle()
**Aktuelles Verhalten:**
- Jeder Schritt in `getPageTitle()` wird geloggt
- Array-Dumps von Section-Daten
- Mehrfache Aufrufe mit vollständigem Logging

**Beispiel aus Log:**
```
[16:04:18] [Neo Dashboard Bootstrap] [WEB] getPageTitle() filter called - current title: ''
[16:04:18] [Neo Dashboard Helper] [WEB] getPageTitle() called - section: 'neo-calendar/projects', site_name: ''
[16:04:18] [Neo Dashboard Helper] [WEB] getPageTitle() - all sections: neo-contacts/dashboard, ...
[16:04:18] [Neo Dashboard Helper] [WEB] getPageTitle() - active_section found: yes
[16:04:18] [Neo Dashboard Helper] [WEB] getPageTitle() - active_section data: Array(...)
[16:04:18] [Neo Dashboard Helper] [WEB] getPageTitle() - using section title: 'Projekte' ...
[16:04:18] [Neo Dashboard Bootstrap] [WEB] getPageTitle() filter returning: 'Projekte'
```

**Impact:**
- ~7-10 Log-Einträge pro `getPageTitle()` Aufruf
- Große Array-Dumps
- Performance-Overhead durch `print_r()` und `implode()`

#### Problem 5: ContextResolver - Übermäßiges Logging
**Aktuelles Verhalten:**
- Jeder Schritt in `current()` wird geloggt
- Array-Dumps von Path-Parts
- Auch bei gecachten Aufrufen (Cache funktioniert, aber Logging passiert trotzdem nicht)

**Beispiel aus Log:**
```
[16:04:18] [ContextResolver] [WEB] Raw data - URI: '/neo-dashboard/neo-calendar/projects/', pagename: 'neo-dashboard', section: 'neo-calendar/projects'
[16:04:18] [ContextResolver] [WEB] Path parts: Array(...)
[16:04:18] [ContextResolver] [WEB] Final context from URI: 'neo-calendar/projects'
```

**Impact:**
- ~3-5 Log-Einträge pro `current()` Aufruf (nur bei erster Berechnung)
- Array-Dumps

#### Problem 6: Viele redundante AJAX-Requests
**Aktuelles Verhalten:**
- Am Ende des Logs sehe ich viele AJAX-Requests
- Jeder Request registriert Plugins neu
- Scheint ein separates Problem zu sein (möglicherweise von JavaScript ausgelöst)

**Beispiel aus Log:**
```
[16:04:21] [Neo Dashboard LanguageManager] [AJAX] Plugin neo-dashboard-core registered languages (static): de_DE, en_US, uk_UA
[16:04:21] [Neo Dashboard LanguageManager] [AJAX] Plugin neo-dashboard-core registered languages (static): de_DE, en_US, uk_UA
... (viele weitere AJAX-Requests)
```

**Impact:**
- Viele zusätzliche Requests nach Seitenaufruf
- Möglicherweise unnötige AJAX-Calls von JavaScript

---

## 2. Optimierungskonzept

### 2.1 Grundprinzipien

1. **Logging nur bei Fehlern oder wichtigen Events**
   - Normale Operationen nicht loggen
   - Nur bei Fehlern, Warnungen oder unerwarteten Situationen loggen

2. **Reduzierte Array-Dumps**
   - Keine `print_r()` für große Arrays
   - Nur relevante Informationen loggen
   - Keine Closure-Objekte in Logs

3. **Caching-bewusstes Logging**
   - Nur bei tatsächlichen Berechnungen loggen (nicht bei Cache-Hits)
   - Cache-Hits nicht loggen

4. **Selektives Logging**
   - Unterschiedliche Log-Level für verschiedene Situationen
   - Debug-Logs nur bei Bedarf

### 2.2 Detaillierte Optimierungen

#### Optimierung 1: AssetManager - Logging reduzieren

**Aktuell:**
```php
error_log("AssetManager: Processing plugin: {$plugin}");
error_log("AssetManager: Asset {$handle} contexts: " . print_r($contexts, true) . ", current: {$context}");
error_log("AssetManager: Skipping asset {$handle} for context {$context}");
error_log("AssetManager: Successfully enqueued CSS: {$handle}...");
```

**Neu:**
```php
// Kein Logging bei normalen Operationen
// Nur bei Fehlern loggen:
if (defined('WP_DEBUG') && WP_DEBUG && $error) {
    error_log("AssetManager: ERROR - {$error_message}");
}
```

**Erwartete Verbesserung:**
- ~30-50 Log-Einträge → ~0-2 Log-Einträge (nur bei Fehlern)
- 95-100% weniger Logs

#### Optimierung 2: SectionManager - Logging reduzieren

**Aktuell:**
```php
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log("[Neo Dashboard SectionManager] [{$request_type}] register() - slug: '{$slug}'");
}
```

**Neu:**
```php
// Kein Logging bei normalen Registrierungen
// Nur bei Duplikaten oder Fehlern loggen
if (defined('WP_DEBUG') && WP_DEBUG && $is_duplicate) {
    error_log("[Neo Dashboard SectionManager] WARNING: Section '{$slug}' already registered");
}
```

**Erwartete Verbesserung:**
- ~15-20 Log-Einträge → ~0-1 Log-Einträge (nur bei Duplikaten)
- 95-100% weniger Logs

#### Optimierung 3: Registry - Logging reduzieren

**Aktuell:**
```php
error_log("[Neo Dashboard Registry] [{$request_type}] getSection() called - slug: '{$slug}'");
error_log("[Neo Dashboard Registry] [{$request_type}] getSection() - available sections: " . implode(', ', array_keys($this->sections)));
error_log("[Neo Dashboard Registry] [{$request_type}] getSection() - section data before translation: " . print_r($section, true));
error_log("[Neo Dashboard Registry] [{$request_type}] getSection() - translated label: '{$section['label']}'");
```

**Neu:**
```php
// Kein Logging bei normalen Aufrufen
// Nur bei Fehlern (Section nicht gefunden) loggen
if (defined('WP_DEBUG') && WP_DEBUG && !$section) {
    error_log("[Neo Dashboard Registry] ERROR: Section '{$slug}' not found");
}
```

**Erwartete Verbesserung:**
- ~5-10 Log-Einträge → ~0-1 Log-Einträge (nur bei Fehlern)
- 90-100% weniger Logs
- Keine Array-Dumps mehr

#### Optimierung 4: Helper - Logging reduzieren

**Aktuell:**
```php
error_log("[Neo Dashboard Helper] [{$request_type}] getPageTitle() called - section: '{$section}', site_name: '{$site_name}'");
error_log("[Neo Dashboard Helper] [{$request_type}] getPageTitle() - all sections: " . implode(', ', array_keys($all_sections)));
error_log("[Neo Dashboard Helper] [{$request_type}] getPageTitle() - active_section found: " . ($active_section ? 'yes' : 'no'));
error_log("[Neo Dashboard Helper] [{$request_type}] getPageTitle() - active_section data: " . print_r($active_section, true));
error_log("[Neo Dashboard Helper] [{$request_type}] getPageTitle() - using section title: '{$title}' ...");
```

**Neu:**
```php
// Kein Logging bei normalen Aufrufen
// Nur bei Fehlern oder unerwarteten Situationen loggen
if (defined('WP_DEBUG') && WP_DEBUG && $error) {
    error_log("[Neo Dashboard Helper] ERROR: getPageTitle() - {$error_message}");
}
```

**Erwartete Verbesserung:**
- ~7-10 Log-Einträge → ~0-1 Log-Einträge (nur bei Fehlern)
- 90-100% weniger Logs
- Keine Array-Dumps mehr

#### Optimierung 5: ContextResolver - Logging reduzieren

**Aktuell:**
```php
error_log("[ContextResolver] [{$request_type}] Raw data - URI: '{$uri}', pagename: '{$pagename}', section: '{$section}'");
error_log("[ContextResolver] [{$request_type}] Path parts: " . print_r($path_parts, true));
error_log("[ContextResolver] [{$request_type}] Final context from URI: '{$context}'");
```

**Neu:**
```php
// Kein Logging bei normalen Aufrufen
// Nur bei Fehlern oder unerwarteten Situationen loggen
if (defined('WP_DEBUG') && WP_DEBUG && $error) {
    error_log("[ContextResolver] ERROR: {$error_message}");
}
```

**Erwartete Verbesserung:**
- ~3-5 Log-Einträge → ~0-1 Log-Einträge (nur bei Fehlern)
- 80-100% weniger Logs
- Keine Array-Dumps mehr

#### Optimierung 6: Bootstrap - getPageTitle() Filter-Logging reduzieren

**Aktuell:**
```php
error_log("[Neo Dashboard Bootstrap] [{$request_type}] getPageTitle() filter called - current title: '{$title}'");
error_log("[Neo Dashboard Bootstrap] [{$request_type}] getPageTitle() filter returning: '{$new_title}'");
```

**Neu:**
```php
// Kein Logging bei normalen Aufrufen
// Nur bei Fehlern loggen
```

**Erwartete Verbesserung:**
- ~2 Log-Einträge → ~0 Log-Einträge
- 100% weniger Logs

---

## 3. Implementierungsplan

### Phase 1: AssetManager Optimierung
**Datei:** `wp-content/plugins/neo-dashboard/src/Manager/AssetManager.php`

**Änderungen:**
- Entferne Logging bei normalen Asset-Verarbeitungen
- Nur bei Fehlern loggen
- Reduziere Array-Dumps

**Risiko:** Niedrig (nur Logging-Änderungen)

### Phase 2: SectionManager Optimierung
**Datei:** `wp-content/plugins/neo-dashboard/src/Manager/SectionManager.php`

**Änderungen:**
- Entferne Logging bei normalen Registrierungen
- Nur bei Duplikaten loggen

**Risiko:** Niedrig (nur Logging-Änderungen)

### Phase 3: Registry Optimierung
**Datei:** `wp-content/plugins/neo-dashboard/src/Registry.php`

**Änderungen:**
- Entferne Logging bei normalen `getSection()` Aufrufen
- Entferne Array-Dumps
- Nur bei Fehlern loggen

**Risiko:** Niedrig (nur Logging-Änderungen)

### Phase 4: Helper Optimierung
**Datei:** `wp-content/plugins/neo-dashboard/src/Helper.php`

**Änderungen:**
- Entferne Logging bei normalen `getPageTitle()` Aufrufen
- Entferne Array-Dumps
- Nur bei Fehlern loggen

**Risiko:** Niedrig (nur Logging-Änderungen)

### Phase 5: ContextResolver Optimierung
**Datei:** `wp-content/plugins/neo-dashboard/src/Manager/ContextResolver.php`

**Änderungen:**
- Entferne Logging bei normalen `current()` Aufrufen
- Entferne Array-Dumps
- Nur bei Fehlern loggen

**Risiko:** Niedrig (nur Logging-Änderungen)

### Phase 6: Bootstrap Optimierung
**Datei:** `wp-content/plugins/neo-dashboard/src/Bootstrap.php`

**Änderungen:**
- Entferne Logging bei `getPageTitle()` Filter-Aufrufen

**Risiko:** Niedrig (nur Logging-Änderungen)

---

## 4. Erwartete Ergebnisse

### Log-Reduktion

**Vorher (pro Seitenaufruf):**
- AssetManager: ~30-50 Log-Einträge
- SectionManager: ~15-20 Log-Einträge
- Registry: ~5-10 Log-Einträge
- Helper: ~7-10 Log-Einträge
- ContextResolver: ~3-5 Log-Einträge
- Bootstrap: ~2 Log-Einträge
- **Gesamt: ~60-100 Log-Einträge**

**Nachher (pro Seitenaufruf):**
- AssetManager: ~0-2 Log-Einträge (nur bei Fehlern)
- SectionManager: ~0-1 Log-Einträge (nur bei Duplikaten)
- Registry: ~0-1 Log-Einträge (nur bei Fehlern)
- Helper: ~0-1 Log-Einträge (nur bei Fehlern)
- ContextResolver: ~0-1 Log-Einträge (nur bei Fehlern)
- Bootstrap: ~0 Log-Einträge
- **Gesamt: ~0-6 Log-Einträge**

**Verbesserung: 90-100% weniger Log-Einträge**

### Performance-Verbesserungen

1. **Reduzierte String-Operationen**
   - Keine `print_r()` für große Arrays
   - Keine `implode()` für große Arrays
   - Weniger String-Konkatenationen

2. **Reduzierte I/O-Operationen**
   - Weniger `error_log()` Aufrufe
   - Weniger Datei-Schreibvorgänge

3. **Reduzierter Speicherverbrauch**
   - Keine temporären Arrays für Logging
   - Keine Closure-Serialisierung für Logs

**Erwartete Performance-Verbesserung:**
- ~5-10% schnellere Seitenaufrufe (durch weniger Logging-Overhead)
- ~50-70% weniger Speicherverbrauch für Logging

---

## 5. Testing-Strategie

### 5.1 Vor/Nach-Vergleich
- Log-Dateien vor/nach Optimierung vergleichen
- Anzahl der Log-Einträge zählen
- Performance messen (Seitenaufruf-Zeit)

### 5.2 Funktionalitätstests
- Alle Funktionen weiterhin testen
- Sicherstellen, dass Fehler weiterhin geloggt werden
- Sicherstellen, dass wichtige Events weiterhin geloggt werden

### 5.3 Regression-Tests
- Alle bestehenden Funktionen testen
- Verschiedene Seiten testen
- Verschiedene Contexts testen

---

## 6. Risiken und Mitigation

### Risiko 1: Wichtige Informationen gehen verloren
**Mitigation:**
- Nur normale Operationen reduzieren
- Fehler und Warnungen weiterhin loggen
- Wichtige Events weiterhin loggen

### Risiko 2: Debugging wird schwieriger
**Mitigation:**
- Logging kann bei Bedarf reaktiviert werden
- Fehler werden weiterhin geloggt
- Wichtige Events werden weiterhin geloggt

### Risiko 3: Performance-Verbesserung zu gering
**Mitigation:**
- Schrittweise Optimierung
- Messung vor/nach jeder Phase
- Weitere Optimierungen bei Bedarf

---

## 7. Zusammenfassung

**Hauptproblem:**
- Übermäßiges Logging bei normalen Operationen
- Viele redundante Log-Einträge
- Performance-Overhead durch String-Operationen und Array-Dumps

**Lösung:**
- Logging nur bei Fehlern oder wichtigen Events
- Reduzierte Array-Dumps
- Caching-bewusstes Logging

**Erwartete Verbesserung:**
- 90-100% weniger Log-Einträge
- ~5-10% schnellere Seitenaufrufe
- ~50-70% weniger Speicherverbrauch für Logging

**Risiko:**
- Niedrig (nur Logging-Änderungen)
- Schrittweise Einführung möglich
- Rollback-Plan vorhanden
