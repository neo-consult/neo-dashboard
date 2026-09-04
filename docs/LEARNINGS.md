# Erkenntnisse aus der Entwicklung von Neo-Plugins

Diese Dokumentation fasst die wichtigsten Lektionen, Best Practices und Lösungsansätze zusammen, die während der Entwicklung der Neo-Plugins (neo-surveys, neo-contacts, neo-calendar) gewonnen wurden. Diese Erkenntnisse können als Referenz für die Verbesserung und Entwicklung weiterer WordPress-Plugins dienen.

## Inhaltsverzeichnis

1. [Bootstrap-Komponenten-Konflikte](#bootstrap-komponenten-konflikte)
2. [AJAX-Handling und Fehlerbehandlung](#ajax-handling-und-fehlerbehandlung)
3. [Rollen- und Capability-System](#rollen-und-capability-system)
4. [User Experience (UX) und Loading-Feedback](#user-experience-ux-und-loading-feedback)
5. [Formular-Handling: Create vs. Update](#formular-handling-create-vs-update)
6. [Type Safety in PHP](#type-safety-in-php)
7. [Logging-System](#logging-system)
8. [Export-Funktionalität](#export-funktionalität)
9. [Frontend-Backend-Kommunikation](#frontend-backend-kommunikation)
10. [Template-Struktur und Bootstrap-Klassen](#template-struktur-und-bootstrap-klassen)
11. [Manager-Pattern für Business-Logik](#manager-pattern-für-business-logik)
12. [Performance-Optimierung](#performance-optimierung)

---

## Bootstrap-Komponenten-Konflikte

### Problem
Bootstrap zeigt Warnungen, wenn mehrere Instanzen derselben Komponente (z.B. Tooltip) auf einem Element initialisiert werden.

**Fehlermeldung:**
```
Bootstrap doesn't allow more than one instance per element. Bound instance: bs.tooltip
```

### Ursachen
1. **Mehrfache Event-Listener**: Initialisierung in Funktionen, die mehrfach aufgerufen werden
2. **DOM-Manipulationen**: Bei AJAX-Inhalten werden bereits initialisierte Komponenten erneut initialisiert
3. **Tooltip auf Dropdown-Element**: Tooltip und Dropdown auf demselben DOM-Element

### Lösungsansätze

#### ✅ Empfohlene Lösung: Komponenten auf verschiedene Elemente trennen
```javascript
// FALSCH: Tooltip und Dropdown auf demselben Element
<button data-bs-toggle="dropdown" data-bs-toggle="tooltip" title="Info">
  Button
</button>

// RICHTIG: Tooltip auf Wrapper-Element
<span data-bs-toggle="tooltip" title="Info">
  <button data-bs-toggle="dropdown">
    Button
  </button>
</span>

// ODER: Tooltip-Attribute vom Dropdown-Element entfernen
// Tooltip nur auf separaten Elementen verwenden
```

#### Alternative: Singleton Pattern für Tooltips
```javascript
const TooltipSingleton = {
    instances: new Map(),
    
    getInstance: function(element) {
        const key = element;
        
        // Prüfe ob bereits eine Instanz existiert
        if (this.instances.has(key)) {
            return this.instances.get(key);
        }
        
        // Prüfe ob Bootstrap bereits eine Instanz hat
        const existing = bootstrap.Tooltip.getInstance(element);
        if (existing) {
            this.instances.set(key, existing);
            return existing;
        }
        
        // Erstelle neue Instanz
        const tooltip = new bootstrap.Tooltip(element);
        this.instances.set(key, tooltip);
        return tooltip;
    },
    
    dispose: function(element) {
        const key = element;
        if (this.instances.has(key)) {
            const tooltip = this.instances.get(key);
            tooltip.dispose();
            this.instances.delete(key);
        }
    }
};

// Verwendung
const tooltip = TooltipSingleton.getInstance(element);
```

### Best Practices
- **Trennung der Komponenten**: Tooltips und Dropdowns nie auf demselben Element
- **Vor Initialisierung prüfen**: `bootstrap.Tooltip.getInstance(element)` verwenden
- **Cleanup bei DOM-Änderungen**: Vor dem Entfernen von Elementen `dispose()` aufrufen
- **Event-Namespacing**: Event-Listener mit Namespaces versehen für sauberes Cleanup

---

## AJAX-Handling und Fehlerbehandlung

### Problem
AJAX-Requests können auf verschiedene Weise fehlschlagen, und die Fehlerbehandlung muss robust sein.

### Best Practices

#### 1. Konsistente Response-Validierung
```javascript
success: function (response) {
    // Prüfe ob response ein String ist (HTML-Fehler)
    if (typeof response === 'string') {
        console.error('AJAX returned HTML instead of JSON');
        showError('Fehler beim Verarbeiten der Anfrage');
        return;
    }
    
    // Prüfe ob response ein Objekt ist
    if (!response || typeof response !== 'object') {
        console.error('Invalid response format:', typeof response);
        showError('Ungültige Antwort vom Server');
        return;
    }
    
    // Prüfe success explizit (kann true, 1, oder "1" sein)
    if (response.success === true || response.success === 1 || response.success === "1") {
        // Erfolg
    } else {
        // Fehler
    }
}
```

#### 2. Explizite dataType-Angabe
```javascript
$.ajax({
    url: ajaxUrl,
    type: 'POST',
    dataType: 'json', // WICHTIG: Explizit JSON angeben
    data: { /* ... */ },
    success: function(response) { /* ... */ },
    error: function(xhr, status, error) {
        // Fehlerbehandlung
    }
});
```

#### 3. Error-Handler für alle AJAX-Calls
```javascript
error: function (xhr, status, error) {
    console.error('AJAX Error:', { xhr, status, error });
    
    // Benutzerfreundliche Fehlermeldung
    showError('Fehler beim Verarbeiten der Anfrage');
    
    // Bei kritischen Fehlern: Liste aktualisieren
    if (typeof reloadList === 'function') {
        reloadList();
    }
}
```

#### 4. Nonce-Verification im Backend
```php
public function ajaxHandler(): void
{
    try {
        // Nonce-Verification
        check_ajax_referer('plugin_nonce', 'nonce');

        // Login-Check
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
            return;
        }

        // Capability-Check
        if (!CapabilitiesManager::userCan('required_capability')) {
            wp_send_json_error(['message' => 'Unzureichende Berechtigung']);
            return;
        }

        // Validierung
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            wp_send_json_error(['message' => 'Ungültige ID']);
            return;
        }
        
        // Verarbeitung...
    } catch (\InvalidArgumentException $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    } catch (\Exception $e) {
        LoggerHelper::error($e->getMessage(), 'ajaxHandler');
        wp_send_json_error(['message' => 'Fehler beim Verarbeiten']);
    }
}
```

---

## Rollen- und Capability-System

### Konzept
Ein feingranulares Capability-System ermöglicht präzise Kontrolle über Benutzerrechte.

### Implementierung

#### 1. Capabilities definieren
```php
class CapabilitiesManager
{
    const CAPABILITIES = [
        'plugin_view_all' => 'Alle Einträge anzeigen',
        'plugin_manage_all' => 'Alle Einträge verwalten',
        'plugin_manage_own' => 'Eigene Einträge verwalten',
        'plugin_view_logs' => 'Logs anzeigen',
        'plugin_manage_logs' => 'Logs verwalten',
    ];
}
```

#### 2. Capabilities zuweisen
```php
public static function assignCapabilities(): void
{
    $admin = get_role('administrator');
    $editor = get_role('editor');
    $mitarbeiter = get_role('mitarbeiter');
    
    // Administrator: Alle Rechte
    foreach (self::CAPABILITIES as $cap => $desc) {
        $admin->add_cap($cap);
    }
    
    // Editor: Bestimmte Rechte
    $editor->add_cap('plugin_view_all');
    $editor->add_cap('plugin_manage_all');
    // ...
}
```

#### 3. Helper-Methoden für komplexe Checks
```php
public static function canEditEntry(?int $entry_user_id): bool
{
    $current_user_id = get_current_user_id();
    
    // Administrator kann alles
    if (self::userCan('plugin_manage_all')) {
        return true;
    }
    
    // Eigene Einträge bearbeiten
    if (self::userCan('plugin_manage_own')) {
        // Wenn entry_user_id null oder 0 ist, gehört es keinem Benutzer
        if ($entry_user_id === null || $entry_user_id === 0) {
            return false; // Keine Bearbeitung von "anonymen" Einträgen
        }
        return $entry_user_id === $current_user_id;
    }
    
    return false;
}
```

#### 4. Frontend-Integration
```php
// Backend: Capabilities an Frontend übergeben
wp_localize_script('plugin-js', 'pluginAjax', [
    'capabilities' => [
        'view_all' => current_user_can('plugin_view_all'),
        'manage_all' => current_user_can('plugin_manage_all'),
        'manage_own' => current_user_can('plugin_manage_own'),
        // ...
    ]
]);
```

```javascript
// Frontend: UI-Elemente basierend auf Capabilities anzeigen/verstecken
if (pluginAjax.capabilities.manage_all || 
    (pluginAjax.capabilities.manage_own && entry.user_id === pluginAjax.currentUserId)) {
    // Bearbeiten-Button anzeigen
}
```

#### 5. Sicherheitsprüfungen in AJAX-Handlern
```php
// Immer prüfen, bevor Daten verarbeitet werden
if (!CapabilitiesManager::canEditEntry($entry_user_id)) {
    LoggerHelper::warning(
        sprintf(
            'Berechtigungsverweigerung: Benutzer %d versuchte Eintrag %d zu bearbeiten',
            get_current_user_id(),
            $entry_id
        ),
        'ajaxUpdateEntry'
    );
    wp_send_json_error(['message' => 'Unzureichende Berechtigung']);
    return;
}
```

### Best Practices
- **Defensive Programmierung**: Immer prüfen, ob `user_id` null oder 0 ist
- **Logging**: Unauthorisierte Zugriffsversuche protokollieren
- **Frontend + Backend**: Capabilities sowohl im Frontend (UX) als auch im Backend (Sicherheit) prüfen
- **Granulare Rechte**: Viele spezifische Capabilities statt wenige allgemeine

---

## User Experience (UX) und Loading-Feedback

### Problem
Benutzer müssen wissen, dass ihre Aktion verarbeitet wird, besonders bei langsamen AJAX-Requests.

### Lösungen

#### 1. Sofortiges visuelles Feedback beim Löschen
```javascript
confirm('Sind Sie sicher?', {
    // ...
}).then((confirmed) => {
    if (!confirmed) return;
    
    // SOFORT: Container mit Loading-Spinner ersetzen
    const $listContainer = $('#list-content');
    if ($listContainer.length) {
        $listContainer.html(`
            <div class="text-center py-4">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                    <span class="visually-hidden">Lädt...</span>
                </div>
                <span class="text-muted">Lösche Eintrag...</span>
            </div>
        `);
    }
    
    // Dann AJAX-Request starten
    $.ajax({ /* ... */ });
});
```

#### 2. Loading-Feedback in Modals
```javascript
loadDataForEdit: function (id) {
    // Container leeren und Loading anzeigen
    const $container = $('#form-container');
    if ($container.length) {
        $container.html(`
            <div class="text-center py-3">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                    <span class="visually-hidden">Lädt...</span>
                </div>
                <span class="text-muted">Lade Daten...</span>
            </div>
        `);
    }
    
    // AJAX-Request mit Loading-Overlay
    ajaxRequestWithLoading({
        action: 'get_data',
        data: { id: id },
        loadingModal: '#editModal',
        onSuccess: function(data) { /* ... */ }
    });
}
```

**⚠️ Wichtig: CSS !important überschreiben**

Wenn das Modal-Loading-Overlay CSS `display: flex !important` verwendet, kann `$overlay.hide()` nicht funktionieren. Lösung:

```javascript
function showModalLoading(modalSelector) {
    // ... overlay erstellen ...
    
    // Cleanup-Funktion
    return function() {
        if ($overlay && $overlay.length) {
            // WICHTIG: setProperty() mit 'important' um CSS !important zu überschreiben
            if ($overlay[0] && $overlay[0].style) {
                $overlay[0].style.setProperty('display', 'none', 'important');
            }
            $overlay.css({
                'visibility': 'hidden',
                'opacity': '0'
            });
            $overlay.hide(); // Zusätzlich für Kompatibilität
        }
    };
}
```

**Erkenntnis:** `jQuery.css()` unterstützt kein `!important`. Verwende `Element.style.setProperty(property, value, 'important')` um CSS `!important` zu überschreiben.

#### 3. Button-States während AJAX
```javascript
handleFormSubmit: function(e, $form) {
    const $submitBtn = $form.find('button[type="submit"]');
    const originalHtml = $submitBtn.html();
    
    // Button deaktivieren und Loading-State anzeigen
    $submitBtn.prop('disabled', true)
              .html('<span class="spinner-border spinner-border-sm me-2"></span>Speichern...');
    
    $.ajax({
        // ...
        complete: function() {
            // Button wieder aktivieren
            $submitBtn.prop('disabled', false).html(originalHtml);
        }
    });
}
```

### Best Practices
- **Sofortiges Feedback**: Loading-State sofort nach Benutzeraktion anzeigen
- **Konsistente Patterns**: Gleiche Loading-Patterns für ähnliche Aktionen verwenden
- **Accessibility**: `role="status"` und `visually-hidden` für Screen Reader
- **Fehlerbehandlung**: Auch bei Fehlern Feedback geben und Zustand zurücksetzen
- **Fallback-Mechanismen**: Immer prüfen, ob DOM-Elemente existieren, bevor man sie manipuliert

---

## Formular-Handling: Create vs. Update

### Problem
Ein Formular muss sowohl für das Erstellen neuer Einträge als auch für das Bearbeiten bestehender Einträge verwendet werden können.

### Lösung

#### 1. Frontend: Action basierend auf ID prüfen
```javascript
handleFormSubmit: function(e, $form) {
    const formData = this.collectFormData($form);
    let action = '';
    
    // Prüfe ob ID gesetzt ist (Bearbeitung) oder nicht (Neu)
    const entryId = String(formData.id || formData.entry_id || '').trim();
    if (entryId && entryId !== '' && entryId !== '0' && 
        entryId !== 'undefined' && entryId !== 'null') {
        action = 'update_entry';
    } else {
        action = 'save_entry';
    }
    
    $.ajax({
        action: action,
        // ...
    });
}
```

#### 2. Backend: Separate Handler für Create und Update
```php
// Create-Handler
public function ajaxSaveEntry(): void
{
    // Sicherheitsprüfung: Keine ID erlaubt beim Erstellen
    if (isset($_POST['entry_id']) && !empty($_POST['entry_id']) && $_POST['entry_id'] !== '0') {
        LoggerHelper::warning('Versuch, neuen Eintrag mit ID zu erstellen');
        wp_send_json_error(['message' => 'Fehler: Eintrag-ID vorhanden. Bitte Update-Funktion verwenden.']);
        return;
    }
    
    // Neuen Eintrag erstellen
    $entry_id = $this->createEntry($data);
    // ...
}

// Update-Handler
public function ajaxUpdateEntry(): void
{
    // ID ist erforderlich
    $entry_id = isset($_POST['entry_id']) ? (int) $_POST['entry_id'] : 0;
    if ($entry_id <= 0) {
        wp_send_json_error(['message' => 'Keine Eintrag-ID angegeben']);
        return;
    }
    
    // Bestehenden Eintrag aktualisieren
    $this->updateEntry($entry_id, $data);
    // ...
}
```

#### 3. Formular zurücksetzen beim Öffnen
```javascript
openAddModal: function () {
    // Formular zurücksetzen
    const $form = $('#entry-form');
    if ($form.length > 0) {
        $form[0].reset();
    }
    
    // ID-Feld explizit leeren und entfernen
    $('#entry-id').val('').remove();
    $form.find('input[name="entry_id"], input[name="id"]').remove();
    
    // Weitere Felder zurücksetzen...
}
```

#### 4. Update-Methode: Alte Werte löschen, neue speichern
```php
public function updateEntry(int $entry_id, array $data): bool
{
    global $wpdb;
    $table = $this->dbManager->getTableValues();
    
    // Lösche alle bestehenden Werte für diesen Eintrag
    $wpdb->delete($table, ['entry_id' => $entry_id], ['%d']);
    
    // Speichere neue Werte (verwendet die gleiche Methode wie beim Erstellen)
    return $this->saveEntryValues($entry_id, $data);
}
```

### Best Practices
- **Explizite Prüfung**: ID-String zu String konvertieren und auf leere Werte prüfen
- **Sicherheitsprüfungen**: Backend prüft, ob Create ohne ID und Update mit ID aufgerufen wird
- **Sauberes Reset**: Beim Öffnen des "Neu"-Modals alle ID-Felder entfernen
- **Wiederverwendbare Methoden**: Update verwendet Create-Methode nach dem Löschen

---

## Type Safety in PHP

### Problem
PHP ist schwach typisiert, was zu `TypeError` führen kann, wenn falsche Typen übergeben werden.

### Lösungen

#### 1. Strict Types aktivieren
```php
<?php
declare(strict_types=1);
```

#### 2. Explizite Type Casts
```php
// FALSCH: Kann TypeError verursachen
$id = $data['id'];
$entry = $this->getEntry($id); // Erwartet int

// RICHTIG: Explizit casten
$id = (int)($data['id'] ?? 0);
if ($id <= 0) {
    continue; // Überspringe ungültige IDs
}
$entry = $this->getEntry($id);
```

#### 3. Type Hints in Methoden
```php
public function getEntry(int $id): ?object
{
    // $id ist garantiert ein int
}

public function invalidateCache(int $entry_id): void
{
    // $entry_id ist garantiert ein int
}
```

#### 4. Validierung vor Verwendung
```php
// In ExportManager.php
$ids_raw = array_unique(array_column($entries, 'id'));
$entries_map = [];

foreach ($ids_raw as $id_raw) {
    $id = (int)$id_raw; // Explicitly cast to int
    if ($id <= 0) {
        continue; // Überspringe ungültige IDs
    }
    $entry = $this->getEntry($id);
    // ...
}
```

#### 5. Error Handling für TypeErrors
```php
try {
    $this->cacheManager->invalidateCache((int)$entry_id);
} catch (\TypeError $e) {
    LoggerHelper::error(
        sprintf('TypeError in invalidateCache: %s', $e->getMessage()),
        'ajaxDeleteEntry'
    );
    // Fehler protokollieren, aber nicht die gesamte Operation abbrechen
}
```

### Best Practices
- **Strict Types**: Immer `declare(strict_types=1);` verwenden
- **Type Hints**: Alle Methodenparameter und Rückgabewerte typisieren
- **Explizite Casts**: Vor der Verwendung explizit casten
- **Validierung**: Nach dem Casten auf gültige Werte prüfen (z.B. `> 0`)
- **Error Handling**: TypeErrors abfangen und protokollieren

---

## Logging-System

### Konzept
Ein zentrales Logging-System für Debugging, Fehleranalyse und Audit-Trails.

### Implementierung

#### 1. LogsManager für Datenbank-Interaktionen
```php
class LogsManager
{
    public function log(string $level, string $message, string $context = '', array $data = []): bool
    {
        // Nur loggen wenn WP_DEBUG aktiviert ist (außer Errors)
        if ($level !== 'error' && (!defined('WP_DEBUG') || !WP_DEBUG)) {
            return false;
        }
        
        global $wpdb;
        $table = $this->dbManager->getTableLogs();
        
        return $wpdb->insert(
            $table,
            [
                'level' => $level,
                'message' => $message,
                'context' => $context,
                'data' => json_encode($data),
                'user_id' => get_current_user_id(),
                'ip_address' => $this->getClientIp(),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%d', '%s', '%s']
        ) !== false;
    }
}
```

#### 2. LoggerHelper für einfache Verwendung
```php
class LoggerHelper
{
    private static ?ErrorManager $errorManager = null;
    
    private static function init(): void
    {
        if (self::$errorManager === null) {
            $dbManager = new DatabaseManager();
            self::$errorManager = new ErrorManager($dbManager);
        }
    }
    
    public static function error(string $message, string $context = '', array $data = []): void
    {
        self::init();
        $context_array = !empty($context) ? ['context' => $context] : [];
        $context_array = array_merge($context_array, $data);
        self::$errorManager->error($message, $context_array);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[Plugin] [ERROR] [%s] %s', $context, $message));
        }
    }
    
    public static function warning(string $message, string $context = '', array $data = []): void
    {
        self::init();
        // ... ähnlich wie error()
    }
    
    // Weitere Methoden: info(), debug()
}
```

#### 3. Verwendung im Code
```php
// Fehler protokollieren
try {
    // Code...
} catch (\Exception $e) {
    LoggerHelper::error($e->getMessage(), 'ajaxSaveEntry');
    wp_send_json_error(['message' => 'Fehler beim Speichern']);
}

// Warnungen protokollieren (z.B. unautorisierte Zugriffe)
if (!CapabilitiesManager::canEditEntry($entry_user_id)) {
    LoggerHelper::warning(
        sprintf(
            'Berechtigungsverweigerung: Benutzer %d versuchte Eintrag %d zu bearbeiten',
            get_current_user_id(),
            $entry_id
        ),
        'ajaxUpdateEntry'
    );
    wp_send_json_error(['message' => 'Unzureichende Berechtigung']);
    return;
}
```

#### 4. Performance-Optimierung
```php
// Selektive Spalten-Abfrage statt SELECT *
$logs = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT id, level, message, context, created_at 
         FROM {$table} 
         WHERE level = %s 
         ORDER BY created_at DESC 
         LIMIT %d OFFSET %d",
        $level,
        $limit,
        $offset
    )
);

// Index auf häufig abgefragten Spalten
CREATE INDEX idx_level_created ON {$table} (level, created_at);
```

### Best Practices
- **Conditional Logging**: Debug-Logs nur bei `WP_DEBUG` aktivieren
- **Strukturierte Daten**: Zusätzliche Daten als JSON speichern
- **Kontext-Informationen**: Immer Context angeben für bessere Filterung
- **Performance**: Selektive Spalten-Abfragen und Indizes verwenden
- **Cleanup**: Alte Logs regelmäßig löschen (z.B. älter als 30 Tage)
- **LoggerHelper**: Statische Helper-Klasse für vereinfachtes Logging

**Erkenntnis:** Statische Helper-Klassen vereinfachen die Verwendung und reduzieren Code-Duplikation erheblich.

---

## Export-Funktionalität

### Konzept
Daten in verschiedenen Formaten exportieren (CSV, Excel, JSON).

### Implementierung

#### 1. ExportManager für verschiedene Formate
```php
class ExportManager
{
    public function exportToCsv(array $filters = []): void
    {
        $entries = $this->getEntries($filters);
        
        // Header setzen
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $this->sanitizeFilename('export-' . date('Y-m-d')) . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // BOM für Excel-Kompatibilität
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header-Zeile
        fputcsv($output, ['ID', 'Name', 'Datum', /* ... */]);
        
        // Daten-Zeilen
        foreach ($entries as $entry) {
            fputcsv($output, [
                $entry->id,
                $entry->name,
                $entry->date,
                // ...
            ]);
        }
        
        fclose($output);
        exit;
    }
}
```

#### 2. Filename-Sanitization
```php
private function sanitizeFilename(string $filename): string
{
    // Entferne gefährliche Zeichen
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '-', $filename);
    
    // Begrenze Länge
    if (strlen($filename) > 100) {
        $filename = substr($filename, 0, 100);
    }
    
    return $filename;
}
```

#### 3. Frontend-Integration mit admin-post.php
```javascript
// Export-Button klicken
exportData: function(format) {
    // Filter aus UI sammeln
    const filters = {
        // ... Filter-Daten
    };
    
    // Hidden Form für Download erstellen
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = pluginAjax.ajaxurl.replace('admin-ajax.php', 'admin-post.php');
    
    // Hidden inputs
    form.appendChild(createInput('action', `plugin_export_${format}`));
    form.appendChild(createInput('_wpnonce', pluginAjax.export_nonce));
    
    // Filter hinzufügen
    Object.keys(filters).forEach(key => {
        if (filters[key]) {
            form.appendChild(createInput(key, filters[key]));
        }
    });
    
    // Form submit und entfernen
    document.body.appendChild(form);
    form.submit();
    setTimeout(() => document.body.removeChild(form), 100);
}
```

### Best Practices
- **Filter-Integration**: Export sollte die gleichen Filter wie die Liste verwenden
- **Filename-Sanitization**: Dateinamen immer sanitizen und Länge begrenzen
- **Excel-Kompatibilität**: BOM für UTF-8 in CSV-Dateien
- **Form-basierte Downloads**: Hidden Form für Downloads verwenden (bessere Browser-Kompatibilität)
- **admin-post.php verwenden**: Für File-Downloads immer `admin-post.php` verwenden, nicht AJAX

**Erkenntnis:** `admin-post.php` ist die richtige Lösung für File-Downloads in WordPress. Einfacher und zuverlässiger als AJAX.

---

## Frontend-Backend-Kommunikation

### Best Practices

#### 1. Lokalisierung von Scripts
```php
wp_localize_script('plugin-js', 'pluginAjax', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('plugin_nonce'),
    'currentUserId' => get_current_user_id(),
    'capabilities' => [
        'view_all' => current_user_can('plugin_view_all'),
        'manage_all' => current_user_can('plugin_manage_all'),
        // ...
    ],
    'urls' => [
        'list' => admin_url('admin.php?page=plugin/list'),
        // ...
    ],
    'strings' => [
        'confirm_delete' => 'Sind Sie sicher, dass Sie diesen Eintrag löschen möchten?',
        'error' => 'Ein Fehler ist aufgetreten',
        // ...
    ],
    'debug' => defined('WP_DEBUG') && WP_DEBUG,
]);
```

#### 2. Zentrale AJAX-Helper-Funktion
```javascript
function ajaxRequestWithLoading(options) {
    const {
        action,
        data = {},
        loadingModal = null,
        onSuccess = null,
        onError = null,
        errorMessage = 'Fehler beim Laden der Daten'
    } = options;
    
    // Loading-Overlay anzeigen
    if (loadingModal) {
        const $modal = $(loadingModal);
        if ($modal.length) {
            // Overlay im Modal anzeigen
        }
    }
    
    $.ajax({
        url: pluginAjax.ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: action,
            nonce: pluginAjax.nonce,
            ...data
        },
        success: function(response) {
            // Response-Validierung...
            if (onSuccess) onSuccess(response.data);
        },
        error: function(xhr, status, error) {
            if (onError) onError();
            else showError(errorMessage);
        },
        complete: function() {
            // Loading-Overlay ausblenden
        }
    });
}
```

#### 3. Conditional Console Logging
```javascript
function debugLog(...args) {
    if (pluginAjax.debug) {
        console.log('[Plugin]', ...args);
    }
}

function debugError(...args) {
    if (pluginAjax.debug) {
        console.error('[Plugin]', ...args);
    }
}
```

#### 4. jQuery no-conflict Mode

**Problem:** WordPress lädt jQuery im no-conflict-Modus. Das bedeutet, dass `$` nicht global verfügbar ist, sondern nur `jQuery`.

**Fehlermeldung:**
```
jQuery.Deferred exception: $ is not a function
TypeError: $ is not a function
```

**Ursache:**
```javascript
// FALSCH: $ außerhalb des Callbacks verwendet
function initList() {
    const $container = $('#list'); // Fehler: $ is not a function
    $container.html('...');
}

jQuery(document).ready(function($) {
    if ($('#list').length) {
        initList(); // Fehler tritt hier auf
    }
});
```

**Lösung:**
```javascript
// RICHTIG: jQuery statt $ verwenden
function initList() {
    const $container = jQuery('#list'); // Korrekt: jQuery ist global verfügbar
    $container.html('...');
}

jQuery(document).ready(function($) {
    // Hier ist $ verfügbar, da es als Parameter übergeben wird
    if ($('#list').length) {
        initList();
    }
});
```

**Alternative Lösung:**
```javascript
// Alle Funktionen innerhalb des Callbacks definieren
jQuery(document).ready(function($) {
    function initList() {
        const $container = $('#list'); // $ ist hier verfügbar
        $container.html('...');
    }
    
    if ($('#list').length) {
        initList();
    }
});
```

**Best Practice:**
- **Außerhalb von Callbacks**: Immer `jQuery` statt `$` verwenden
- **Innerhalb von Callbacks**: `$` kann verwendet werden, wenn es als Parameter übergeben wird
- **Konsistenz**: Einheitlich `jQuery` verwenden, um Verwirrung zu vermeiden

**Erkenntnis:** In WordPress-Plugins sollte immer `jQuery` statt `$` verwendet werden, es sei denn, man befindet sich innerhalb eines `jQuery(document).ready(function($) { ... })` Callbacks, wo `$` als Parameter übergeben wird.

### Best Practices
- **Zentrale Konfiguration**: Alle AJAX-URLs, Nonces, etc. über `wp_localize_script`
- **Konsistente Patterns**: Gleiche AJAX-Patterns für ähnliche Operationen
- **Error-Handling**: Immer Error-Handler definieren
- **Loading-States**: Konsistente Loading-Feedback-Mechanismen
- **Context-basierte Asset-Loading**: Assets nur in den Kontexten laden, in denen sie tatsächlich benötigt werden
- **jQuery no-conflict Mode**: Immer `jQuery` statt `$` verwenden, außer innerhalb von Callbacks mit `$` als Parameter

#### Context-basierte Asset-Loading

**Problem:** JavaScript-Dateien wurden in allen Kontexten geladen, auch wenn nicht benötigt.

**Lösung:**
```php
// DashboardManager.php
$contexts = [
    'plugin-common' => ['plugin', 'plugin/list', 'plugin/dashboard', 'dashboard-home'],
    'plugin-list' => ['plugin/list', 'plugin/dashboard', 'dashboard-home'],
    'plugin-forms' => ['plugin/list', 'plugin/dashboard'],
];
```

**Erkenntnis:** Wildcard `'*'` sollte vermieden werden. Spezifische Kontexte verbessern Performance erheblich.

---

## Template-Struktur und Bootstrap-Klassen

### Konzept
Weniger Custom CSS, mehr Bootstrap-Klassen für konsistente und wartbare Templates.

### Best Practices

#### 1. Einheitliche Seiten-Wrapper-Struktur
```php
// FALSCH: Unnötige Container-Wrapper
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Content -->
        </div>
    </div>
</div>

// RICHTIG: Spezifischer Seiten-Wrapper
<div class="plugin-page-name-page">
    <!-- Content -->
</div>
```

**Erkenntnis:** `sections.css` setzt bereits volle Breite für Sections. Zusätzliche `container-fluid`/`row`/`col-12` Wrapper sind überflüssig und werden durch CSS-Regeln auf 0 gesetzt.

#### 2. Bootstrap-Klassen für Statistik-Cards
```php
// Konsistente Struktur für alle Statistik-Cards
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Label</div>
                        <div class="h4 mb-0 text-success" id="stat-id">0</div>
                        <div class="text-muted small">Einheit</div>
                    </div>
                    <i class="bi-icon-name fs-1 text-success opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
</div>
```

**Wichtige Bootstrap-Klassen:**
- `g-3`: Gutter-Abstände zwischen Spalten (1rem)
- `mb-4`: Margin-Bottom für Abstände
- `text-muted small`: Für Labels und Einheiten
- `h4 mb-0`: Für Zahlen
- `fs-1 opacity-25`: Für Icons
- `border-success`, `border-warning`, `border-info`, `border-danger`: Border-Varianten
- `text-primary`, `text-success`, etc.: Text-Farben

#### 3. Gutter-Abstände für Grid-Layouts
```php
// WICHTIG: g-3 für einheitliche Abstände zwischen Cards
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card">...</div>
    </div>
    <div class="col-md-3">
        <div class="card">...</div>
    </div>
</div>
```

**Problem:** `sections.css` setzt Padding von `col-*` auf 0. Lösung: `g-*` Klassen verwenden.

**Erkenntnis:** Bootstrap `g-*` Klassen (Gutter) stellen die Abstände zwischen Spalten wieder her, auch wenn das Padding durch CSS-Regeln entfernt wurde.

#### 4. Template-basierte Rendering-Struktur
```php
// DashboardManager.php
private function renderTemplate(string $template_name, array $vars = [], string $subdir = ''): void
{
    $template_path = PLUGIN_PATH . 'templates';
    
    if ($subdir) {
        $template_path .= '/' . $subdir;
    }
    
    $template_path .= '/' . $template_name . '.php';
    
    if (!file_exists($template_path)) {
        error_log("Template nicht gefunden: {$template_path}");
        return;
    }
    
    extract($vars, EXTR_SKIP);
    include $template_path;
}

// Verwendung
public function renderWorkTimePage(): void
{
    $vars = [
        'nonce' => wp_create_nonce('plugin_nonce'),
        'current_user_id' => get_current_user_id(),
        'current_date' => date('Y-m-d'),
    ];
    
    $this->renderTemplate('work-time', $vars, 'pages');
}
```

**Vorteile:**
- Trennung von Logik und Darstellung
- Wiederverwendbare Templates
- Einfache Wartung
- Konsistente Struktur

#### 5. Dark Mode Styling mit CSS-Variablen
```css
/* Basis-Styling (White Mode) */
.card {
    background-color: var(--neo-theme-bg, #ffffff);
    color: var(--neo-theme-text, #212529);
    border-color: var(--neo-theme-border, #dee2e6);
}

/* Dark Mode Override */
html[data-neo-theme="dark"] .card {
    background-color: var(--neo-theme-bg) !important;
    color: var(--neo-theme-text) !important;
    border-color: var(--neo-theme-border) !important;
}

/* Spezifische Komponenten */
html[data-neo-theme="dark"] .card-body .h4 {
    color: var(--neo-theme-text) !important;
}

html[data-neo-theme="dark"] .list-group-item.px-0 {
    background-color: var(--neo-theme-bg) !important;
    border-color: var(--neo-theme-border) !important;
    color: var(--neo-theme-text) !important;
}
```

**Best Practices:**
- Immer CSS-Variablen verwenden
- `!important` nur bei Dark Mode Overrides
- Systematische Prüfung aller Komponenten
- Spezifische Selektoren für komplexe Strukturen

### Best Practices
- **Weniger CSS, mehr Bootstrap**: Custom CSS nur wenn Bootstrap keine Lösung bietet
- **Einheitliche Struktur**: Alle Seiten-Templates folgen dem gleichen Muster
- **Gutter-Klassen**: Immer `g-*` für Grid-Abstände verwenden
- **Template-System**: HTML in separate Template-Dateien auslagern
- **Dark Mode**: Systematisch alle Komponenten prüfen und stylen
- **Konsistente Klassen**: Gleiche Bootstrap-Klassen für ähnliche Komponenten

**Erkenntnis:** Eine konsistente Template-Struktur mit Bootstrap-Klassen reduziert Custom CSS erheblich und verbessert die Wartbarkeit.

---

## Manager-Pattern für Business-Logik

### Konzept
Komplexe Business-Logik in dedizierte Manager-Klassen auslagern, um Code-Organisation und Wartbarkeit zu verbessern.

### Implementierung

#### 1. WorkTimeManager für Arbeitszeit-Logik
```php
class WorkTimeManager
{
    private DatabaseManager $dbManager;
    private AttendanceManager $attendanceManager;
    
    public function createWorkTime(array $data): int
    {
        // Validierung
        $this->attendanceManager->validateWorkTime($data);
        
        // Überschneidungsprüfung
        if ($this->attendanceManager->hasWorkTimeOverlap($data)) {
            throw new \InvalidArgumentException('Arbeitszeit überschneidet sich mit bestehender');
        }
        
        // Erstellen
        return $this->dbManager->insertWorkTime($data);
    }
    
    public function getWorkTimes(int $user_id, array $filters = []): array
    {
        // Selektive Spalten
        return $this->dbManager->getWorkTimes($user_id, $filters);
    }
}
```

#### 2. AttendanceManager für Regeln und Validierung
```php
class AttendanceManager
{
    private const DEFAULT_RULES = [
        'max_work_hours_per_day' => 12,
        'max_work_hours_per_week' => 50,
        'min_break_duration' => 30,
        // ...
    ];
    
    public function validateWorkTime(array $data): void
    {
        // Regeln prüfen
        $rules = $this->getRules();
        
        // Validierung...
    }
    
    public function hasWorkTimeOverlap(array $data): bool
    {
        // Überschneidungsprüfung
    }
}
```

#### 3. AJAXManager delegiert an Manager
```php
class AjaxManager
{
    private WorkTimeManager $workTimeManager;
    private AttendanceManager $attendanceManager;
    
    public function ajaxSaveWorkTime(): void
    {
        // Sicherheitsprüfungen
        $this->checkLogin();
        $this->checkNonce();
        $this->checkCapability();
        
        // Delegation an WorkTimeManager
        try {
            $work_time_id = $this->workTimeManager->createWorkTime($data);
            wp_send_json_success(['id' => $work_time_id]);
        } catch (\InvalidArgumentException $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}
```

#### 4. Bootstrap.php initialisiert Manager
```php
class Bootstrap
{
    private function initializeManagers(): void
    {
        $dbManager = new DatabaseManager();
        
        // Manager initialisieren
        $attendanceManager = new AttendanceManager();
        $workTimeManager = new WorkTimeManager($dbManager);
        $workTimeManager->setAttendanceManager($attendanceManager);
        
        $eventManager = new EventManager($dbManager);
        $eventManager->setAttendanceManager($attendanceManager);
        
        // AJAXManager bekommt Manager injiziert
        $ajaxManager = new AjaxManager();
        $ajaxManager->setWorkTimeManager($workTimeManager);
        $ajaxManager->setEventManager($eventManager);
    }
}
```

### Best Practices
- **Separation of Concerns**: Jeder Manager hat eine klare Verantwortlichkeit
- **Dependency Injection**: Manager werden injiziert, nicht direkt instanziiert
- **Business-Logik in Managern**: AJAXManager enthält nur Sicherheitsprüfungen und Delegation
- **Wiederverwendbarkeit**: Manager können von verschiedenen Stellen verwendet werden
- **Testbarkeit**: Manager können isoliert getestet werden

**Erkenntnis:** Das Manager-Pattern verbessert Code-Organisation erheblich, besonders bei komplexer Business-Logik mit vielen Regeln und Validierungen.

---

## Performance-Optimierung

### Best Practices

#### 1. Selektive Datenbankabfragen
```php
// SCHLECHT: SELECT *
$entries = $wpdb->get_results("SELECT * FROM {$table} WHERE status = 'active'");

// GUT: Nur benötigte Spalten
$entries = $wpdb->get_results(
    "SELECT id, name, created_at 
     FROM {$table} 
     WHERE status = 'active'"
);
```

#### 2. Indizes auf häufig abgefragten Spalten
```php
// Bei Tabellenerstellung
CREATE INDEX idx_level_created ON {$table} (level, created_at);
CREATE INDEX idx_user_id ON {$table} (user_id);
CREATE INDEX idx_status ON {$table} (status);
```

#### 3. Pagination für große Datensätze
```php
public function getEntries(array $filters = [], int $limit = 50, int $offset = 0): array
{
    $where = $this->buildWhereClause($filters);
    
    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, name, created_at 
             FROM {$table} 
             {$where}
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $limit,
            $offset
        )
    );
}
```

#### 4. Caching für Statistiken
```php
public function getStatistics(int $id): array
{
    $cache_key = "plugin_stats_{$id}";
    $cached = wp_cache_get($cache_key);
    
    if ($cached !== false) {
        return $cached;
    }
    
    $stats = $this->calculateStatistics($id);
    wp_cache_set($cache_key, $stats, '', 3600); // 1 Stunde
    
    return $stats;
}

public function invalidateCache(int $id): void
{
    wp_cache_delete("plugin_stats_{$id}");
    wp_cache_delete('plugin_general_stats');
}
```

#### 5. Migration-Methoden mit Fehlerbehandlung

**Wichtig:** Alle Datenbankoperationen, auch in Migration-Methoden, sollten Fehlerbehandlung haben.

```php
public function addPerformanceIndexes(): void
{
    global $wpdb;
    
    $index_exists = $wpdb->get_results(
        "SHOW INDEX FROM {$this->table} WHERE Key_name = 'idx_created_at'"
    );
    
    if (empty($index_exists)) {
        $result = $wpdb->query(
            "ALTER TABLE {$this->table} 
             ADD INDEX idx_created_at (created_at)"
        );
        
        // Fehlerbehandlung
        if ($result === false && !empty($wpdb->last_error)) {
            LoggerHelper::error(
                'Fehler beim Hinzufügen des Index',
                'addPerformanceIndexes',
                ['error' => $wpdb->last_error]
            );
        }
    }
}
```

**Erkenntnis:** Migration-Methoden ohne Fehlerbehandlung können zu stummen Fehlern führen.

### Best Practices
- **Nur benötigte Daten laden**: Selektive Spalten-Abfragen
- **Indizes verwenden**: Auf häufig gefilterten/sortierten Spalten
- **Pagination**: Große Datensätze paginieren
- **Caching**: Statische oder selten ändernde Daten cachen
- **Cleanup**: Alte Daten regelmäßig löschen
- **Fehlerbehandlung**: Auch in Migration-Methoden

---

## Zusammenfassung: Checkliste für Plugin-Entwicklung

### Sicherheit
- [ ] Nonce-Verification für alle AJAX-Handler
- [ ] Capability-Checks für alle Operationen
- [ ] Input-Validierung und Sanitization
- [ ] Output-Escaping
- [ ] SQL-Injection-Schutz (Prepared Statements)
- [ ] Logging von unautorisierten Zugriffsversuchen

### Code-Qualität
- [ ] `declare(strict_types=1);` verwenden
- [ ] Type Hints für alle Methoden
- [ ] Explizite Type Casts vor Verwendung
- [ ] Error Handling für alle kritischen Operationen
- [ ] Konsistente Namenskonventionen

### User Experience
- [ ] Sofortiges visuelles Feedback bei Aktionen
- [ ] Loading-States für alle AJAX-Requests
- [ ] Konsistente Fehlermeldungen
- [ ] Bestätigungs-Dialoge für destruktive Aktionen
- [ ] Accessibility (ARIA-Labels, Screen Reader Support)

### Performance
- [ ] Selektive Datenbankabfragen
- [ ] Indizes auf häufig abgefragten Spalten
- [ ] Pagination für große Datensätze
- [ ] Caching für statische Daten
- [ ] Asset-Optimierung (Minification, etc.)
- [ ] Context-basierte Asset-Loading (keine Wildcards)

### Wartbarkeit
- [ ] Zentrale Helper-Klassen (LoggerHelper, ValidationHelper)
- [ ] Konsistente Code-Struktur
- [ ] Dokumentation komplexer Logik
- [ ] Modularer Code (Manager-Pattern)
- [ ] Testbarkeit (Dependency Injection wo möglich)
- [ ] Code-Duplikation vermeiden (Private Helper-Methoden)
- [ ] Migration-Methoden mit Fehlerbehandlung
- [ ] Template-basierte Rendering-Struktur
- [ ] Einheitliche Seiten-Wrapper-Struktur
- [ ] Bootstrap-Klassen statt Custom CSS wo möglich

---

## Weitere Ressourcen

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- [Bootstrap 5 Documentation](https://getbootstrap.com/docs/5.3/)
- [jQuery AJAX Best Practices](https://api.jquery.com/jquery.ajax/)

---

**Erstellt am:** 2025-01-XX  
**Aktualisiert am:** 2025-12-26  
**Version:** 2.0  
**Quellen:** Erkenntnisse aus neo-surveys, neo-contacts und neo-calendar Entwicklung  
**Zweck:** Zentrale Referenz für alle Neo-Plugins

