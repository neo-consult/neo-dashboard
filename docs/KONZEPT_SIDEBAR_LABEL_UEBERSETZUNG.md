# Konzept: Sidebar-Label-Übersetzung

## Problem-Analyse

### Aktueller Ablauf

1. **Zeitpunkt: `neo_dashboard_init` Hook**
   - Plugins registrieren Sidebar-Items via `do_action('neo_dashboard_register_sidebar_item', [...])`
   - Labels werden bereits mit `__('Label', 'textdomain')` übersetzt
   - **Problem:** Textdomains sind zu diesem Zeitpunkt noch nicht geladen!

2. **Zeitpunkt: `init` Hook (Priority 20)**
   - Textdomains werden geladen (`loadTextdomain()`)
   - **Zu spät:** Sidebar-Items sind bereits registriert mit falschen/fehlenden Übersetzungen

3. **Zeitpunkt: Render-Zeit**
   - Sidebar wird gerendert
   - Labels werden aus der Registry gelesen
   - **Problem:** Labels sind bereits übersetzt (oder nicht), aber möglicherweise in falscher Sprache

### Root Cause

**Timing-Problem:** Sidebar-Registrierung (`neo_dashboard_init`) passiert **vor** Textdomain-Laden (`init` Priority 20).

---

## Lösungsvorschläge

### Vorschlag 1: Label Key + Textdomain Pattern ⭐ (Empfohlen)

**Konzept:**
- Plugins registrieren Labels mit `label_key` (Original-String) und `textdomain`
- Übersetzung erfolgt zur Render-Zeit mit `__($item['label_key'], $item['textdomain'])`

**Implementierung:**

```php
// Plugin registriert:
do_action('neo_dashboard_register_sidebar_item', [
    'slug' => 'neo-calendar-group',
    'label_key' => 'Kalender',  // Original-String
    'textdomain' => 'neo-calendar',
    'icon' => 'bi-calendar-event',
    // ...
]);

// SidebarManager übersetzt zur Render-Zeit:
private function translateLabel(array $item): string
{
    if (isset($item['label_key']) && isset($item['textdomain'])) {
        return __($item['label_key'], $item['textdomain']);
    }
    // Fallback für alte Registrierungen
    return $item['label'] ?? '';
}
```

**Vorteile:**
- ✅ Explizit und klar: Textdomain ist immer bekannt
- ✅ Keine Magie: Keine automatische Erkennung nötig
- ✅ Rückwärtskompatibel: `label` als Fallback möglich
- ✅ Performance: Keine Regex-Erkennung nötig
- ✅ Type-safe: Klare Struktur

**Nachteile:**
- ⚠️ Plugins müssen angepasst werden (aber nur minimal)

**Migration:**
```php
// Alt:
'label' => __('Kalender', 'neo-calendar'),

// Neu:
'label_key' => 'Kalender',
'textdomain' => 'neo-calendar',
```

---

### Vorschlag 2: Lazy Translation mit Callables

**Konzept:**
- Plugins registrieren Labels als Closures/Callables
- Zur Render-Zeit werden Closures ausgewertet

**Implementierung:**

```php
// Plugin registriert:
do_action('neo_dashboard_register_sidebar_item', [
    'slug' => 'neo-calendar-group',
    'label' => fn() => __('Kalender', 'neo-calendar'),  // Closure
    'icon' => 'bi-calendar-event',
    // ...
]);

// SidebarManager evaluiert zur Render-Zeit:
private function translateLabel(array $item): string
{
    if (isset($item['label']) && is_callable($item['label'])) {
        return call_user_func($item['label']);
    }
    return $item['label'] ?? '';
}
```

**Vorteile:**
- ✅ Sehr flexibel: Plugins haben volle Kontrolle
- ✅ Keine Änderungen an der Registry-Struktur nötig
- ✅ Kann auch komplexe Logik enthalten

**Nachteile:**
- ⚠️ Closures müssen serialisiert werden (kann problematisch sein)
- ⚠️ Weniger explizit: Textdomain ist nicht direkt sichtbar
- ⚠️ Debugging schwieriger

---

### Vorschlag 3: Filter-Hook zur Render-Zeit

**Konzept:**
- Filter `neo_dashboard_translate_sidebar_label` wird zur Render-Zeit aufgerufen
- Plugins können ihre eigene Übersetzungslogik implementieren

**Implementierung:**

```php
// SidebarManager ruft Filter auf:
private function translateLabel(array $item, string $slug): string
{
    $label = $item['label'] ?? '';
    
    // Filter für Plugin-spezifische Übersetzung
    $translated = apply_filters(
        'neo_dashboard_translate_sidebar_label',
        $label,
        $slug,
        $item
    );
    
    return $translated;
}

// Plugin implementiert Filter:
add_filter('neo_dashboard_translate_sidebar_label', function($label, $slug, $item) {
    if (str_starts_with($slug, 'neo-calendar')) {
        // Übersetze mit neo-calendar Textdomain
        return __($label, 'neo-calendar');
    }
    return $label;
}, 10, 3);
```

**Vorteile:**
- ✅ Sehr flexibel: Plugins haben volle Kontrolle
- ✅ Kann auch für andere Zwecke verwendet werden
- ✅ Keine Änderungen an der Registrierung nötig

**Nachteile:**
- ⚠️ Jedes Plugin muss den Filter implementieren
- ⚠️ Weniger explizit: Textdomain ist nicht direkt sichtbar
- ⚠️ Performance: Filter wird für jedes Label aufgerufen

---

### Vorschlag 4: Deferred Registration

**Konzept:**
- Sidebar-Items werden erst **nach** dem Laden der Textdomains registriert
- Neuer Hook `neo_dashboard_register_sidebar_items` wird nach `init` (Priority 20) ausgelöst

**Implementierung:**

```php
// Dashboard.php:
public function run(): void
{
    $this->registerManagers();
    do_action('neo_dashboard_pre_init');
    do_action('neo_dashboard_init');
    
    // NEU: Nach Textdomain-Laden
    add_action('init', function() {
        do_action('neo_dashboard_register_sidebar_items');
    }, 21); // Nach Textdomain-Laden (Priority 20)
}

// Plugin registriert:
add_action('neo_dashboard_register_sidebar_items', function() {
    do_action('neo_dashboard_register_sidebar_item', [
        'slug' => 'neo-calendar-group',
        'label' => __('Kalender', 'neo-calendar'),  // Jetzt funktioniert es!
        // ...
    ]);
});
```

**Vorteile:**
- ✅ Labels werden zur richtigen Zeit übersetzt
- ✅ Keine Änderungen an der Registry-Struktur nötig
- ✅ Rückwärtskompatibel: Alte Registrierungen funktionieren weiterhin

**Nachteile:**
- ⚠️ Timing-Änderung: Könnte andere Plugins beeinflussen
- ⚠️ Zwei Hooks: `neo_dashboard_init` und `neo_dashboard_register_sidebar_items`
- ⚠️ Verwirrend: Wann registriert man was?

---

## Empfehlung

### ⭐ Vorschlag 1: Label Key + Textdomain Pattern

**Warum?**
1. **Explizit und klar:** Textdomain ist immer bekannt, keine Magie
2. **Performance:** Keine Regex-Erkennung oder Filter-Aufrufe nötig
3. **Type-safe:** Klare Struktur, IDE-Support
4. **Rückwärtskompatibel:** `label` als Fallback möglich
5. **Minimale Änderungen:** Plugins müssen nur `label` durch `label_key` + `textdomain` ersetzen

**Migration:**
```php
// Alt (funktioniert weiterhin als Fallback):
'label' => __('Kalender', 'neo-calendar'),

// Neu (empfohlen):
'label_key' => 'Kalender',
'textdomain' => 'neo-calendar',
```

**Implementierung:**
- `SidebarManager::register()` akzeptiert beide Varianten
- `SidebarManager::translateLabel()` priorisiert `label_key` + `textdomain`
- `Registry::getSidebarTree()` verwendet `translateLabel()`

---

## Implementierungsplan

1. **`SidebarManager::register()` erweitern**
   - Akzeptiert `label_key` + `textdomain` ODER `label`
   - Validiert, dass mindestens eines vorhanden ist

2. **`SidebarManager::translateLabel()` anpassen**
   - Priorität: `label_key` + `textdomain` > `label` (Fallback)
   - Entfernt automatische Textdomain-Erkennung

3. **`Registry::getSidebarTree()` anpassen**
   - Verwendet `SidebarManager::translateLabel()`

4. **Plugins migrieren**
   - `label` → `label_key` + `textdomain`
   - Alte `label`-Registrierungen funktionieren weiterhin als Fallback

---

## Beispiel-Implementierung

```php
// SidebarManager.php
public function register(array $args): string
{
    $slug = trim((string) $args['slug']);
    
    // Validierung: label_key + textdomain ODER label
    if (!isset($args['label']) && (!isset($args['label_key']) || !isset($args['textdomain']))) {
        throw new \InvalidArgumentException("Sidebar item '{$slug}' must have either 'label' or 'label_key' + 'textdomain'");
    }
    
    Registry::instance()->addSidebarItem($slug, $args);
    return $slug;
}

private function translateLabel(array $item): string
{
    // Priorität 1: label_key + textdomain (neu, empfohlen)
    if (isset($item['label_key']) && isset($item['textdomain'])) {
        return __($item['label_key'], $item['textdomain']);
    }
    
    // Priorität 2: label (Fallback für alte Registrierungen)
    if (isset($item['label'])) {
        return $item['label'];
    }
    
    return '';
}
```

---

## Fazit

**Vorschlag 1 (Label Key + Textdomain)** ist die beste Lösung, weil:
- ✅ Explizit und klar
- ✅ Performance-optimiert
- ✅ Rückwärtskompatibel
- ✅ Minimale Änderungen nötig

Die automatische Textdomain-Erkennung kann als Fallback beibehalten werden, sollte aber nicht die primäre Methode sein.

