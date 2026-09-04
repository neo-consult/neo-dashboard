# Plugin-Isolation und Konfliktvermeidung im Neo Dashboard

## Übersicht

Das Neo Dashboard ist so konzipiert, dass mehrere Plugins nahtlos zusammenarbeiten können, ohne sich gegenseitig zu stören. Dieses Dokument beschreibt die verschiedenen Mechanismen zur Isolation und Konfliktvermeidung.

## 1. PHP-Namespaces

### Konzept
Jedes Plugin verwendet einen eigenen PHP-Namespace, um Namenskonflikte zu vermeiden.

### Implementierung
```php
// neo-surveys
namespace NeoSurveys\Manager;

// neo-contacts
namespace NeoContacts\Manager;

// neo-calendar
namespace NeoCalendar\Manager;
```

### Vorteile
- ✅ Keine Klassennamen-Konflikte
- ✅ Saubere Code-Organisation
- ✅ Autoloading-kompatibel

---

## 2. Plugin-ID-basierte Asset-Registrierung

### Konzept
Jedes Plugin registriert seine Assets mit einer eindeutigen Plugin-ID. Assets werden nur in spezifischen Kontexten geladen.

### Implementierung
```php
// In DashboardManager.php jedes Plugins
do_action('neo_dashboard_register_plugin_assets', 'neo-calendar', [
    'css' => [
        'neo-calendar-core' => [
            'src' => $plugin_url . 'assets/css/neo-calendar-core.css',
            'deps' => ['neo-dashboard-core'],
            'contexts' => ['neo-calendar', 'neo-calendar/overview', 'neo-calendar/work-time']
        ]
    ],
    'js' => [
        'neo-calendar-common' => [
            'src' => $plugin_url . 'assets/js/neo-calendar-common.js',
            'deps' => ['jquery', 'neo-dashboard-core'],
            'contexts' => ['neo-calendar', 'neo-calendar/overview']
        ]
    ]
]);
```

### Context-basiertes Loading
```php
// AssetManager.php
private function loadPluginAssets(string $context, string $type): void
{
    foreach ($this->plugin_assets as $plugin => $assets) {
        foreach ($assets[$type] ?? [] as $handle => $config) {
            $contexts = $config['contexts'] ?? ['*'];
            
            // Nur laden, wenn Context passt
            if (!in_array('*', $contexts) && !in_array($context, $contexts)) {
                continue; // Asset wird übersprungen
            }
            
            wp_enqueue_style($handle, $src, $deps, $ver);
        }
    }
}
```

### Vorteile
- ✅ Assets werden nur geladen, wenn benötigt (Performance)
- ✅ Keine Konflikte durch doppeltes Laden
- ✅ Klare Plugin-Identifikation

---

## 3. JavaScript-Namespaces

### Konzept
Jedes Plugin verwendet einen eigenen JavaScript-Namespace im `window`-Objekt.

### Implementierung

#### neo-surveys
```javascript
// Zentrale Namespace-Struktur
if (typeof window.NeoSurveys === 'undefined') {
    window.NeoSurveys = {};
}

window.NeoSurveys.Core = { /* ... */ };
window.NeoSurveys.Modals = { /* ... */ };
window.NeoSurveys.Surveys = { /* ... */ };
```

#### neo-contacts
```javascript
// Lokalisierte Variablen mit Plugin-Präfix
window.neoContactsAjax = {
    ajaxurl: '...',
    nonce: '...',
    currentUserId: 123
};

// Funktionen mit Plugin-Präfix
function neoContactsShowPersonModal() { /* ... */ }
function neoContactsLoadPeopleList() { /* ... */ }
```

#### neo-calendar
```javascript
// Lokalisierte Variablen mit Plugin-Präfix
window.neoCalendarAjax = {
    ajaxurl: '...',
    nonce: '...',
    current_user_id: 123
};

// Helper-Funktionen mit Plugin-Präfix
window.neoCalendarDebugLog = function() { /* ... */ };
window.neoCalendarConfirm = function() { /* ... */ };
```

### Best Practices
1. **Immer Plugin-Präfix verwenden**: `neoCalendar*`, `neoContacts*`, `neoSurveys*`
2. **Namespace-Prüfung**: Immer prüfen, ob Namespace existiert
3. **Keine globalen Variablen ohne Präfix**: Vermeide `myFunction()` → verwende `neoCalendarMyFunction()`

### Vorteile
- ✅ Keine Variablen-/Funktionsnamen-Konflikte
- ✅ Klare Plugin-Identifikation im Code
- ✅ Einfaches Debugging

---

## 4. Zentrale Event-Systeme

### Konzept
Das Dashboard stellt zentrale Events bereit, die Plugins nutzen können, um Konflikte zu vermeiden.

### Implementierung

#### neoDashboardReady Event
```javascript
// In dashboard.js
document.dispatchEvent(new CustomEvent("neoDashboardReady"));

// In Plugins
document.addEventListener('neoDashboardReady', function() {
    // Plugin-Initialisierung
});
```

#### neoDashboardContentAdded Event
```javascript
// In dashboard.js - für Tooltip-Initialisierung
document.addEventListener('neoDashboardContentAdded', (e) => {
    const container = e.detail?.container || document;
    TooltipSingleton.initAll(container.querySelectorAll('[data-bs-toggle="tooltip"]'));
});

// In Plugins - nach dynamischem Content
const event = new CustomEvent('neoDashboardContentAdded', {
    detail: { container: element },
    bubbles: true
});
document.dispatchEvent(event);
```

### Vorteile
- ✅ Zentrale Tooltip-Initialisierung verhindert doppelte Instanzen
- ✅ Plugins müssen sich nicht um Initialisierung kümmern
- ✅ Konsistente Verhaltensweise

---

## 5. Bootstrap-Konfliktvermeidung

### Problem
Bootstrap zeigt Warnungen, wenn mehrere Instanzen derselben Komponente auf einem Element initialisiert werden.

### Lösung: Zentrale Bootstrap-Patch
```javascript
// In AssetManager.php - printBootstrapPatch()
// Unterdrückt Bootstrap-Warnungen GLOBAL
console.warn = function(...args) {
    const message = args.join(' ');
    if (suppressedMessages.some(msg => message.includes(msg))) {
        return; // Unterdrücke Bootstrap-Warnungen
    }
    originalConsoleWarn.apply(console, args);
};

// Patch Bootstrap's Data.set Methode
bootstrap.Data.set = function(element, config) {
    // Prüfe auf existierende Instanz
    const existing = bootstrap.Data.get(element, config.name);
    if (existing) {
        return existing; // Verwende existierende Instanz
    }
    return originalDataSet(element, config);
};
```

### Vorteile
- ✅ Zentrale Lösung für alle Plugins
- ✅ Keine Warnungen in der Konsole
- ✅ Plugins müssen sich nicht selbst darum kümmern

---

## 6. Registry-System

### Konzept
Zentrale Registrierung von Sections, Widgets und Sidebar-Items über eine Registry.

### Implementierung
```php
// SectionManager.php
public function register(array $args): string
{
    $slug = sanitize_key((string) $args['slug']);
    Registry::instance()->addSection($slug, $args);
    return $slug;
}

// In Plugins
do_action('neo_dashboard_register_section', [
    'slug' => 'neo-calendar/work-time',
    'label' => 'Arbeitszeit',
    'callback' => [$this, 'renderWorkTimePage']
]);
```

### Vorteile
- ✅ Zentrale Verwaltung aller Komponenten
- ✅ Keine Slug-Konflikte (sanitize_key)
- ✅ Einfache Abfrage aller registrierten Komponenten

---

## 7. Asset-Lokalisierung mit Merge-Logik

### Problem
Mehrere Scripts können dasselbe Objekt lokalisieren (z.B. `neoCalendarAjax`).

### Lösung: Merge-Logik
```php
// In AssetManager.php
if ($object_name === 'neoCalendarAjax' && isset($GLOBALS['wp_scripts']->registered[$handle]->extra['data'])) {
    // Versuche existierende Daten zu extrahieren
    $existing_data = json_decode($matches[1], true) ?? [];
    // Merge: existierende Daten (z.B. page_type) haben Vorrang
    $data = array_merge($data, $existing_data);
}
```

### Vorteile
- ✅ Mehrere Scripts können dasselbe Objekt erweitern
- ✅ Keine Überschreibung von existierenden Daten
- ✅ Flexibles System für Plugin-Erweiterungen

---

## 8. jQuery no-conflict Mode

### Problem
WordPress lädt jQuery im no-conflict-Modus. `$` ist nicht global verfügbar.

### Lösung: Konsistente Verwendung
```javascript
// RICHTIG: jQuery statt $ verwenden
function myFunction() {
    const $container = jQuery('#container');
    $container.html('...');
}

// ODER: $ innerhalb von Callbacks
jQuery(document).ready(function($) {
    // Hier ist $ verfügbar
    $('#container').html('...');
});
```

### Vorteile
- ✅ Keine Konflikte mit anderen Libraries
- ✅ Konsistente Verwendung über alle Plugins
- ✅ Keine "jQuery is not defined" Fehler

---

## 9. Conditional Console Logging

### Konzept
Debug-Logs werden nur ausgegeben, wenn `WP_DEBUG` aktiviert ist.

### Implementierung
```javascript
// Helper-Funktionen in jedem Plugin
function neoCalendarDebugLog() {
    if (typeof neoCalendarAjax !== 'undefined' && neoCalendarAjax.debug === true) {
        console.log.apply(console, arguments);
    }
}

// Verwendung
neoCalendarDebugLog('Debug-Meldung');
```

### Vorteile
- ✅ Keine Console-Spam in Produktion
- ✅ Einheitliches Verhalten über alle Plugins
- ✅ Einfaches Debugging bei Bedarf

---

## 10. Dependency Management

### Konzept
Assets deklarieren ihre Abhängigkeiten explizit.

### Implementierung
```php
'neo-calendar-common' => [
    'src' => $plugin_url . 'assets/js/neo-calendar-common.js',
    'deps' => ['jquery', 'neo-dashboard-core'], // Abhängigkeiten
    'contexts' => ['neo-calendar']
]
```

### Vorteile
- ✅ Korrekte Ladereihenfolge
- ✅ Keine "undefined" Fehler
- ✅ WordPress verwaltet Dependencies automatisch

---

## Checkliste für Plugin-Entwickler

### ✅ PHP
- [ ] Eigener Namespace verwenden (`NeoPluginName\Manager`)
- [ ] Plugin-ID bei Asset-Registrierung verwenden
- [ ] Context-basiertes Asset-Loading implementieren

### ✅ JavaScript
- [ ] Eigener Namespace/Präfix verwenden (`neoPluginName*`)
- [ ] `jQuery` statt `$` verwenden (außer in Callbacks)
- [ ] Conditional Console Logging implementieren
- [ ] `neoDashboardContentAdded` Event für Tooltip-Initialisierung nutzen

### ✅ CSS
- [ ] Plugin-spezifische CSS-Klassen verwenden (z.B. `.neo-calendar-*`)
- [ ] Bootstrap-Klassen bevorzugen, Custom CSS minimieren
- [ ] Keine globalen Styles ohne Präfix

### ✅ Events & Hooks
- [ ] WordPress Hooks mit Plugin-Präfix verwenden
- [ ] Custom Events mit Plugin-Präfix verwenden
- [ ] Zentrale Dashboard-Events nutzen, wo möglich

### ✅ Asset-Handles
- [ ] Eindeutige Handles verwenden (z.B. `neo-calendar-core`)
- [ ] Keine Konflikte mit anderen Plugins
- [ ] Dependencies korrekt deklarieren

---

## Zusammenfassung

Das Neo Dashboard verwendet ein **Multi-Layer-Isolations-Konzept**:

1. **PHP-Ebene**: Namespaces
2. **Asset-Ebene**: Plugin-IDs und Context-basiertes Loading
3. **JavaScript-Ebene**: Namespaces/Präfixe
4. **Event-Ebene**: Zentrale Events für gemeinsame Aufgaben
5. **Bootstrap-Ebene**: Zentrale Patches für Konfliktvermeidung
6. **Registry-Ebene**: Zentrale Verwaltung aller Komponenten

Dieses Konzept stellt sicher, dass:
- ✅ Plugins sich nicht gegenseitig stören
- ✅ Performance optimal bleibt (nur benötigte Assets werden geladen)
- ✅ Code wartbar und erweiterbar bleibt
- ✅ Konflikte frühzeitig erkannt und vermieden werden

---

**Erstellt am:** 2025-01-XX  
**Version:** 1.0  
**Zweck:** Dokumentation des Plugin-Isolations-Konzepts für Neo Dashboard

