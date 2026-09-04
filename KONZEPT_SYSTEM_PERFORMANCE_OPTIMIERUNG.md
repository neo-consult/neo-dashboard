# Konzept: System-Performance-Optimierung (Arbeitsweise)

> Historischer Entwurf. Verweise auf `src/Helper.php` beschreiben den damaligen
> Zustand; die Klasse wurde nach Migration aller Verbraucher entfernt.

## 1. Problemanalyse - Echte Performance-Probleme

### 1.1 AssetManager - Ineffiziente Asset-Verarbeitung

**Problem:**
- AssetManager iteriert durch **ALLE** Plugin-Assets, auch die die nicht geladen werden müssen
- Für jeden Context werden alle Assets aller Plugins geprüft
- `in_array()` Prüfungen für jedes Asset (auch wenn Context nicht passt)

**Aktueller Code:**
```php
foreach ($this->plugin_assets as $plugin => $assets) {
    foreach ($assets[$type] ?? [] as $handle => $config) {
        $contexts = $config['contexts'] ?? ['*'];
        
        if (!in_array('*', $contexts) && !in_array($context, $contexts)) {
            continue; // Asset wird übersprungen, aber Prüfung wurde bereits durchgeführt
        }
        // Asset wird geladen
    }
}
```

**Impact:**
- Bei 50 Assets und Context `neo-calendar/projects`: 50 Prüfungen
- Viele Assets werden geprüft, aber nicht geladen
- O(n) Komplexität für alle Assets

**Optimierung:**
- Assets nach Context indexieren (Reverse-Index)
- Nur relevante Assets für Context laden
- O(1) Lookup statt O(n) Iteration

### 1.2 getPageTitle() - Mehrfache Registry-Aufrufe

**Problem:**
- `getPageTitle()` ruft `getSections()` auf, was **alle** Sections zurückgibt
- `getSections()` gibt komplette Array zurück (auch wenn nur eine Section benötigt wird)
- Wird mehrfach aufgerufen (Filter + Helper)

**Aktueller Code:**
```php
$registry = \NeoDashboard\Core\Registry::instance();
$all_sections = $registry->getSections(); // Gibt ALLE Sections zurück
$active_section = $registry->getSection($section); // Dann wird nur eine Section benötigt
```

**Impact:**
- Unnötige Array-Kopie aller Sections
- Mehrfache Aufrufe von `getSections()`
- Memory-Overhead

**Optimierung:**
- Direkt `getSection()` aufrufen (ohne `getSections()`)
- Caching für `getPageTitle()` Ergebnis

### 1.3 getPageTitle() - Kein Caching

**Problem:**
- `getPageTitle()` wird mehrfach aufgerufen (Filter + Helper)
- Jedes Mal wird die komplette Berechnung durchgeführt
- Kein Caching des Ergebnisses

**Aktueller Code:**
```php
public static function getPageTitle(): string
{
    // Wird bei jedem Aufruf neu berechnet
    $section = get_query_var(...);
    $registry = \NeoDashboard\Core\Registry::instance();
    $all_sections = $registry->getSections();
    $active_section = $registry->getSection($section);
    // ...
}
```

**Impact:**
- Mehrfache Berechnungen pro Request
- Unnötige Registry-Aufrufe

**Optimierung:**
- Statisches Caching für `getPageTitle()` Ergebnis
- Nur einmal pro Request berechnen

### 1.4 AssetManager - Keine Context-Indexierung

**Problem:**
- Assets werden linear durchsucht
- Keine Vorindexierung nach Context
- Jedes Asset muss einzeln geprüft werden

**Aktueller Code:**
```php
// Assets werden so gespeichert:
$this->plugin_assets[$plugin][$type][$handle] = $config;

// Beim Laden wird durch alle iteriert:
foreach ($this->plugin_assets as $plugin => $assets) {
    foreach ($assets[$type] ?? [] as $handle => $config) {
        // Prüfung für jedes Asset
    }
}
```

**Impact:**
- O(n) Komplexität
- Viele unnötige Prüfungen

**Optimierung:**
- Reverse-Index: Assets nach Context gruppieren
- O(1) Lookup für Context-spezifische Assets

### 1.5 Viele redundante AJAX-Requests

**Problem:**
- Am Ende des Logs sehe ich viele AJAX-Requests
- Jeder Request registriert Plugins neu
- Möglicherweise unnötige AJAX-Calls von JavaScript

**Beispiel aus Log:**
```
[16:04:21] [AJAX] Plugin neo-dashboard-core registered languages (static): ...
[16:04:21] [AJAX] Plugin neo-dashboard-core registered languages (static): ...
... (viele weitere AJAX-Requests)
```

**Impact:**
- Viele zusätzliche Requests nach Seitenaufruf
- Server-Overhead
- Möglicherweise unnötige JavaScript-Aufrufe

**Optimierung:**
- JavaScript-Code prüfen (warum so viele AJAX-Requests?)
- Batch-Requests wo möglich
- Lazy-Loading von Daten

---

## 2. Optimierungskonzept

### 2.1 AssetManager - Context-Indexierung

**Strategie:**
- Reverse-Index: Assets nach Context gruppieren
- Beim Registrieren: Assets in Context-Index eintragen
- Beim Laden: Nur relevante Assets aus Index holen

**Implementierung:**
```php
// Beim Registrieren:
private function registerPluginAssets(string $plugin, array $assets): void
{
    $this->plugin_assets[$plugin] = $assets;
    
    // Reverse-Index erstellen
    foreach ($assets as $type => $type_assets) {
        foreach ($type_assets as $handle => $config) {
            $contexts = $config['contexts'] ?? ['*'];
            
            foreach ($contexts as $context) {
                $this->context_index[$context][$type][$handle] = [
                    'plugin' => $plugin,
                    'config' => $config
                ];
            }
            
            // Auch für '*' Context
            if (in_array('*', $contexts)) {
                $this->context_index['*'][$type][$handle] = [
                    'plugin' => $plugin,
                    'config' => $config
                ];
            }
        }
    }
}

// Beim Laden:
private function loadPluginAssets(string $context, string $type): void
{
    // Nur relevante Assets aus Index holen
    $assets_to_load = [];
    
    // Assets für spezifischen Context
    if (isset($this->context_index[$context][$type])) {
        $assets_to_load = array_merge($assets_to_load, $this->context_index[$context][$type]);
    }
    
    // Assets für '*' Context
    if (isset($this->context_index['*'][$type])) {
        $assets_to_load = array_merge($assets_to_load, $this->context_index['*'][$type]);
    }
    
    // Nur relevante Assets laden
    foreach ($assets_to_load as $handle => $data) {
        $this->enqueueAsset($handle, $data['config'], $type);
    }
}
```

**Erwartete Verbesserung:**
- O(n) → O(1) für Context-Lookup
- Nur relevante Assets werden verarbeitet
- ~80-90% weniger Iterationen

### 2.2 getPageTitle() - Caching

**Strategie:**
- Statisches Caching für `getPageTitle()` Ergebnis
- Nur einmal pro Request berechnen

**Implementierung:**
```php
private static ?string $cachedPageTitle = null;
private static ?string $cachedPageTitleSection = null;

public static function getPageTitle(): string
{
    $section = get_query_var(\NeoDashboard\Core\Router::QUERY_VAR_SECTION, '');
    
    // Cache: Page-Titel wurde bereits für diese Section berechnet
    if (self::$cachedPageTitle !== null && self::$cachedPageTitleSection === $section) {
        return self::$cachedPageTitle;
    }
    
    // Berechnung (wie bisher)
    $title = self::calculatePageTitle($section);
    
    // Cache speichern
    self::$cachedPageTitle = $title;
    self::$cachedPageTitleSection = $section;
    
    return $title;
}
```

**Erwartete Verbesserung:**
- Mehrfache Aufrufe → Einmalige Berechnung
- ~50-70% weniger Registry-Aufrufe

### 2.3 getPageTitle() - Direkter getSection() Aufruf

**Strategie:**
- `getSections()` nicht aufrufen, wenn nur eine Section benötigt wird
- Direkt `getSection()` aufrufen

**Implementierung:**
```php
// Vorher:
$all_sections = $registry->getSections(); // Alle Sections
$active_section = $registry->getSection($section); // Nur eine Section

// Nachher:
$active_section = $registry->getSection($section); // Direkt, ohne getSections()
```

**Erwartete Verbesserung:**
- Keine unnötige Array-Kopie
- ~30-50% weniger Memory-Overhead

### 2.4 AssetManager - Early Return bei leeren Assets

**Strategie:**
- Prüfung ob Assets vorhanden sind, bevor Iteration
- Early Return wenn keine Assets

**Implementierung:**
```php
private function loadPluginAssets(string $context, string $type): void
{
    // Early Return wenn keine Assets
    if (empty($this->plugin_assets)) {
        return;
    }
    
    // Prüfung ob Context-Index existiert
    if (empty($this->context_index[$context][$type]) && empty($this->context_index['*'][$type])) {
        return;
    }
    
    // Nur relevante Assets laden
    // ...
}
```

**Erwartete Verbesserung:**
- Weniger unnötige Iterationen
- ~10-20% Performance-Verbesserung

### 2.5 JavaScript - AJAX-Request-Optimierung

**Strategie:**
- JavaScript-Code prüfen
- Batch-Requests wo möglich
- Lazy-Loading von Daten

**Implementierung:**
- JavaScript-Dateien analysieren
- Unnötige AJAX-Requests identifizieren
- Batch-Requests implementieren

**Erwartete Verbesserung:**
- ~50-70% weniger AJAX-Requests
- Schnellere Seitenaufrufe

---

## 3. Implementierungsplan

### Phase 1: AssetManager Context-Indexierung
**Datei:** `wp-content/plugins/neo-dashboard/src/Manager/AssetManager.php`

**Änderungen:**
- Reverse-Index für Assets nach Context
- `registerPluginAssets()` erweitern
- `loadPluginAssets()` optimieren

**Risiko:** Mittel (neue Datenstruktur)

### Phase 2: getPageTitle() Caching
**Datei:** `wp-content/plugins/neo-dashboard/src/Helper.php`

**Änderungen:**
- Statisches Caching für `getPageTitle()`
- Direkter `getSection()` Aufruf

**Risiko:** Niedrig (nur Caching)

### Phase 3: AssetManager Early Returns
**Datei:** `wp-content/plugins/neo-dashboard/src/Manager/AssetManager.php`

**Änderungen:**
- Early Returns bei leeren Assets
- Prüfungen vor Iteration

**Risiko:** Niedrig (nur Optimierungen)

### Phase 4: JavaScript AJAX-Optimierung
**Datei:** JavaScript-Dateien in Plugins

**Änderungen:**
- Unnötige AJAX-Requests identifizieren
- Batch-Requests implementieren
- Lazy-Loading

**Risiko:** Mittel (JavaScript-Änderungen)

---

## 4. Erwartete Ergebnisse

### Performance-Verbesserungen

**AssetManager:**
- Vorher: O(n) Iteration durch alle Assets
- Nachher: O(1) Lookup für Context-spezifische Assets
- **Verbesserung: 80-90% weniger Iterationen**

**getPageTitle():**
- Vorher: Mehrfache Berechnungen pro Request
- Nachher: Einmalige Berechnung mit Caching
- **Verbesserung: 50-70% weniger Berechnungen**

**Memory-Usage:**
- Vorher: Unnötige Array-Kopien
- Nachher: Direkte Zugriffe, Caching
- **Verbesserung: 30-50% weniger Memory-Overhead**

**AJAX-Requests:**
- Vorher: Viele einzelne Requests
- Nachher: Batch-Requests, Lazy-Loading
- **Verbesserung: 50-70% weniger Requests**

### Gesamt-Performance

**Seitenaufruf-Zeit:**
- Vorher: ~200-300ms
- Nachher: ~150-200ms (geschätzt)
- **Verbesserung: ~25-35%**

**Memory-Usage:**
- Vorher: ~10-15 MB
- Nachher: ~7-10 MB (geschätzt)
- **Verbesserung: ~30-40%**

---

## 5. Testing-Strategie

### 5.1 Performance-Tests
- Seitenaufruf-Zeit messen (vor/nach)
- Memory-Usage messen
- Anzahl der Iterationen zählen

### 5.2 Funktionalitätstests
- Alle Assets werden korrekt geladen
- Page-Titel wird korrekt angezeigt
- Alle Sections funktionieren

### 5.3 Regression-Tests
- Alle bestehenden Funktionen testen
- Verschiedene Contexts testen
- Verschiedene Plugins testen

---

## 6. Risiken und Mitigation

### Risiko 1: Context-Index wird nicht korrekt aktualisiert
**Mitigation:**
- Index wird beim Registrieren erstellt
- Tests für verschiedene Szenarien
- Fallback auf alte Methode bei Fehlern

### Risiko 2: Caching führt zu falschen Ergebnissen
**Mitigation:**
- Cache wird bei Section-Änderungen invalidiert
- Tests für verschiedene Szenarien
- Fallback auf Berechnung bei Fehlern

### Risiko 3: JavaScript-Änderungen brechen Funktionalität
**Mitigation:**
- Schrittweise Änderungen
- Umfangreiche Tests
- Rollback-Plan vorhanden

---

## 7. Zusammenfassung

**Hauptprobleme:**
1. AssetManager iteriert durch alle Assets (O(n))
2. getPageTitle() wird mehrfach berechnet (kein Caching)
3. getSections() wird aufgerufen, obwohl nur eine Section benötigt wird
4. Viele redundante AJAX-Requests

**Lösungen:**
1. Context-Indexierung für Assets (O(1) Lookup)
2. Caching für getPageTitle()
3. Direkter getSection() Aufruf
4. JavaScript AJAX-Optimierung

**Erwartete Verbesserung:**
- ~25-35% schnellere Seitenaufrufe
- ~30-40% weniger Memory-Usage
- ~50-70% weniger AJAX-Requests
- ~80-90% weniger Asset-Iterationen

**Risiko:**
- Niedrig bis Mittel
- Schrittweise Einführung möglich
- Rollback-Plan vorhanden
