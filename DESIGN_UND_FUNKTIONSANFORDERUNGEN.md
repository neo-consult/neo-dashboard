# Design- und Funktionsanforderungen für Neo Dashboard Plugins

Diese Dokumentation fasst alle Design- und Funktionsanforderungen zusammen, die für die Entwicklung und Gestaltung von Plugins im Neo Dashboard Framework gelten. Sie dient als Referenz für die konsistente Implementierung neuer Plugins und die Anpassung bestehender Plugins.

## Inhaltsverzeichnis

1. [Tooltip-System](#tooltip-system)
2. [Widget-Architektur](#widget-architektur)
3. [Benutzermenü](#benutzermenü)
4. [Layout und Spacing](#layout-und-spacing)
5. [Dark Mode Support](#dark-mode-support)
6. [Responsive Design](#responsive-design)
7. [JavaScript-Konventionen](#javascript-konventionen)
8. [CSS-Variablen und Theme-System](#css-variablen-und-theme-system)
9. [Modal-System](#modal-system)
10. [Button-Styling](#button-styling)
11. [Formular-Elemente](#formular-elemente)
12. [Tabellen und Listen](#tabellen-und-listen)
13. [Toast Notifications](#toast-notifications)

---

## Tooltip-System

### Anforderungen

- **Konsistente Darstellung** in White Mode und Dark Mode
- **Automatische Initialisierung** für statische und dynamische Inhalte
- **Deutsche Texte** für alle Tooltips
- **Korrekte Positionierung** (standardmäßig `top`)

### Implementierung

#### HTML-Struktur

```html
<!-- Standard-Tooltip -->
<button type="button" 
        class="btn btn-primary"
        data-bs-toggle="tooltip"
        data-bs-placement="top"
        data-bs-title="Tooltip-Text auf Deutsch">
    <i class="bi-icon"></i> Button-Text
</button>

<!-- Tooltip für Elemente mit anderen data-bs-toggle (z.B. Dropdown) -->
<a href="#" 
   class="dropdown-toggle"
   data-bs-toggle="dropdown"
   data-tooltip-title="Benutzermenü öffnen"
   data-tooltip-placement="bottom">
    Inhalt
</a>
```

#### CSS-Styling

**White Mode:**
- Hintergrund: `#ffffff` (weiß)
- Text: `#212529` (dunkel)
- Border: `#dee2e6` (hellgrau)
- Schatten: `0 2px 8px rgba(0, 0, 0, 0.15)`

**Dark Mode:**
- Hintergrund: `var(--neo-theme-secondary-bg)`
- Text: `var(--neo-theme-text)`
- Border: `var(--neo-theme-border)`
- Schatten: `0 2px 8px rgba(0, 0, 0, 0.3)`

#### JavaScript-Initialisierung

```javascript
// Automatische Initialisierung in dashboard.js
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
  new bootstrap.Tooltip(el);
});

// Für Elemente mit data-tooltip-title
document.querySelectorAll('[data-tooltip-title]').forEach(element => {
  const title = element.getAttribute('data-tooltip-title');
  const placement = element.getAttribute('data-tooltip-placement') || 'top';
  new bootstrap.Tooltip(element, {
    title: title,
    placement: placement,
    trigger: 'hover focus'
  });
});
```

### Best Practices

- ✅ Verwende immer `data-bs-title` statt `title` für Bootstrap-Kompatibilität
- ✅ Setze `data-bs-placement="top"` als Standard
- ✅ Verwende deutsche Texte für alle Tooltips
- ✅ Initialisiere Tooltips nach dynamischem Content-Loading
- ❌ Vermeide doppelte Tooltip-Initialisierung

---

## Widget-Architektur

### Anforderungen

- **Einheitliche Header-Struktur** für alle Widgets
- **Zentrale Verwaltung** im `neo-dashboard` Plugin
- **Unterstützung für Header-Actions** (Buttons/Links)
- **Konsistente Abstände und Spacing**

### Widget-Header-Komponente

#### Verwendung

```php
// In Widget-Templates
<?php
$header_icon = 'bi-building';
$header_label = 'Widget-Titel';
$header_actions = [
    [
        'type' => 'link',
        'href' => '/path/to/page',
        'icon' => 'bi-arrow-right',
        'text' => 'Alle anzeigen',
        'class' => 'btn-outline-primary btn-sm',
        'title' => 'Zur Übersichtsseite'
    ]
];
$header_class = '';
include NEO_DASHBOARD_TEMPLATE_PATH . 'components/widgets/header.php';
?>
```

#### Widget-Registrierung mit Header-Actions

```php
do_action('neo_dashboard_register_widget', [
    'id' => 'my-widget',
    'label' => 'Mein Widget',
    'icon' => 'bi-building',
    'callback' => [$this, 'render_widget'],
    'header_actions' => [
        [
            'type' => 'link',
            'href' => '/path/to/page',
            'icon' => 'bi-arrow-right',
            'text' => 'Alle anzeigen',
            'class' => 'btn-outline-primary btn-sm',
            'title' => 'Zur Übersichtsseite'
        ]
    ],
    'header_class' => 'custom-class'
]);
```

### Widget-Layout

#### Container-Struktur

```html
<div class="row g-4">
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card h-100 shadow-sm">
            <!-- Widget Header -->
            <!-- Widget Body -->
        </div>
    </div>
</div>
```

#### Responsive Spalten

- **Mobile (col-12)**: 1 Spalte
- **Tablet (col-md-6)**: 2 Spalten
- **Desktop (col-xl-4)**: 3 Spalten

#### Card-Klassen

- `h-100`: Gleiche Höhe für alle Cards in einer Row
- `shadow-sm`: Leichter Schatten für Tiefe
- `g-4`: Gleichmäßige Abstände zwischen Cards

### Widget-Komponenten

Verwende den zentralen Widget-Renderer:

```php
// Stat-Item
$widgetComponentRenderer->render('stat-item', [
    'icon' => 'bi-building',
    'label' => 'Organisationen',
    'value' => 42,
    'value_color' => 'primary',
    'action' => [
        'href' => '/path',
        'icon' => 'bi-arrow-right',
        'title' => 'Zur Seite',
        'class' => 'btn-outline-secondary'
    ]
]);

// Action-Button
$widgetComponentRenderer->render('action-button', [
    'href' => '/path',
    'text' => 'Button-Text',
    'icon' => 'bi-arrow-right',
    'class' => 'btn-primary',
    'title' => 'Tooltip-Text'
]);

// Empty-State
$widgetComponentRenderer->render('empty-state', [
    'icon' => 'bi-inbox',
    'message' => 'Keine Daten vorhanden.',
    'action' => [
        'href' => '/path',
        'text' => 'Aktion',
        'icon' => 'bi-plus'
    ]
]);
```

---

## Benutzermenü

### Anforderungen

- **Einheitliche Gestaltung** in Navbar und Offcanvas
- **Avatar mit Fallback** (Initialen bei fehlendem Avatar)
- **Dropdown schließt automatisch** bei Klick außerhalb
- **Tooltip schließt** beim Öffnen des Dropdowns

### Avatar-Styling

#### Größe und Aussehen

- **Größe**: 40px × 40px
- **Border**: 2px solid mit Theme-Farben
- **Schatten**: `0 2px 4px rgba(0, 0, 0, 0.1)`
- **Border-Radius**: 50% (rund)

#### Hover-Effekt

- Border-Farbe ändert sich zu Primary-Farbe
- Leichte Vergrößerung (`transform: scale(1.05)`)
- Stärkerer Schatten

#### Fallback (Initialen)

- **Automatische Generierung** aus Vor- und Nachname
- **Gradient-Hintergrund**: Primary zu Accent
- **Weißer Text** mit Text-Schatten
- **Wird automatisch angezeigt**, wenn Avatar nicht geladen werden kann

#### CSS-Implementierung

```css
.user-avatar {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border: 2px solid var(--neo-theme-border);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease-in-out;
}

.user-avatar:hover {
    border-color: var(--neo-theme-primary);
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3);
    transform: scale(1.05);
}

.user-avatar-fallback {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--neo-theme-primary) 0%, var(--neo-theme-accent) 100%);
    border: 2px solid var(--neo-theme-border);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
```

### Dropdown-Verhalten

#### Auto-Close

- Dropdown schließt automatisch bei Klick außerhalb
- Funktioniert in Navbar und Offcanvas
- Nutzt Bootstrap's `autoClose: true` Option

#### JavaScript-Implementierung

```javascript
// Dropdown Auto-Close
document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(toggle => {
  const dropdown = new bootstrap.Dropdown(toggle, {
    autoClose: true
  });
});

// Zusätzlicher Event-Listener
document.addEventListener('click', (e) => {
  const clickedElement = e.target;
  const isDropdownToggle = clickedElement.closest('[data-bs-toggle="dropdown"]');
  const isDropdownMenu = clickedElement.closest('.dropdown-menu');
  
  if (!isDropdownToggle && !isDropdownMenu) {
    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
      const dropdown = menu.closest('.dropdown');
      if (dropdown) {
        const toggle = dropdown.querySelector('[data-bs-toggle="dropdown"]');
        if (toggle) {
          const dropdownInstance = bootstrap.Dropdown.getInstance(toggle);
          if (dropdownInstance) {
            dropdownInstance.hide();
          }
        }
      }
    });
  }
});
```

### Benutzerrolle-Anzeige

#### White Mode

- **Farbe**: `#6c757d` (dunkelgrau)
- **Schriftgröße**: `0.75rem`
- **Explizite CSS-Regel** erforderlich (überschreibt Bootstrap's text-muted)

#### Dark Mode

- **Farbe**: `rgba(255, 255, 255, 0.7)` (hellgrau)
- **Schriftgröße**: `0.75rem`

#### CSS-Implementierung

```css
/* White Mode */
.user-menu-toggle small.text-muted {
    color: #6c757d !important;
}

html:not([data-neo-theme="dark"]) .user-menu-toggle small.text-muted {
    color: #6c757d !important;
}

/* Dark Mode */
html[data-neo-theme="dark"] .user-menu-toggle small.text-muted {
    color: rgba(255, 255, 255, 0.7) !important;
}
```

---

## Layout und Spacing

### Container-Struktur

#### Widget-Seiten (Startseite)

```html
<div class="row g-4">
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card h-100 shadow-sm">
            <!-- Content -->
        </div>
    </div>
</div>
```

#### Inhalts-Seiten (Plugin-Seiten)

```html
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <!-- Content -->
            </div>
        </div>
    </div>
</div>
```

### Spacing-Konventionen

- **Row-Abstände**: `g-4` (1.5rem zwischen Cards)
- **Card-Margins**: Keine (wird durch `g-4` gehandhabt)
- **Card-Body Padding**: Standard Bootstrap (`p-3` oder `card-body`)
- **Button-Gruppen**: `gap-2` für Abstände zwischen Buttons

### Card-Klassen

- `h-100`: Gleiche Höhe in einer Row
- `shadow-sm`: Leichter Schatten
- `mb-4`: Nur wenn nicht in `row g-4` verwendet

---

## Dark Mode Support

### CSS-Variablen

Verwende immer CSS-Variablen für Theme-Farben:

```css
:root {
    --neo-theme-bg: #ffffff;
    --neo-theme-text: #212529;
    --neo-theme-primary: #007cba;
    --neo-theme-secondary-bg: #f8f9fa;
    --neo-theme-border: #dee2e6;
    --neo-theme-accent: #e9ecef;
}

html[data-neo-theme="dark"] {
    --neo-theme-bg: #1e1e1e;
    --neo-theme-text: #f0f0f0;
    --neo-theme-primary: #0ea5e9;
    --neo-theme-secondary-bg: #2a2a2a;
    --neo-theme-border: #404040;
    --neo-theme-accent: #3f3f3f;
}
```

### Dark Mode CSS-Regeln

#### Struktur

```css
/* Basis-Styling (White Mode) */
.element {
    background-color: var(--neo-theme-bg, #ffffff);
    color: var(--neo-theme-text, #212529);
    border-color: var(--neo-theme-border, #dee2e6);
}

/* Dark Mode Override */
html[data-neo-theme="dark"] .element {
    background-color: var(--neo-theme-bg) !important;
    color: var(--neo-theme-text) !important;
    border-color: var(--neo-theme-border) !important;
}
```

#### Wichtige Elemente

- **Cards**: `background-color`, `border-color`, `color`
- **Card-Header**: `background-color`, `border-bottom-color`
- **Buttons**: Border-Farben, Hover-Effekte
- **Tabellen**: `background-color`, `border-color`, `color`
- **Formulare**: `background-color`, `border-color`, `color`
- **Modals**: Alle inneren Elemente
- **Listen**: `background-color`, `border-color`, `color`

### Best Practices

- ✅ Verwende immer CSS-Variablen
- ✅ Setze `!important` nur bei Dark Mode Overrides
- ✅ Teste beide Modi (White/Dark)
- ✅ Prüfe Kontraste für Lesbarkeit
- ❌ Vermeide hardcodierte Farben

---

## Responsive Design

### Breakpoints

- **Mobile**: < 768px (col-12)
- **Tablet**: ≥ 768px (col-md-*)
- **Desktop**: ≥ 1200px (col-xl-*)

### Layout-Patterns

#### Widget-Grid

```html
<div class="row g-4">
    <div class="col-12 col-md-6 col-xl-4">
        <!-- Widget -->
    </div>
</div>
```

#### Filter-Bereiche

```html
<div class="row g-3">
    <div class="col-12 col-md-6 col-lg-4">
        <!-- Filter-Feld -->
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <!-- Filter-Feld -->
    </div>
</div>
```

#### Datums-Felder (auf neuer Zeile)

```html
<div class="row g-3">
    <div class="col-md-4">
        <!-- Anderes Feld -->
    </div>
    <div class="col-md-4">
        <!-- Anderes Feld -->
    </div>
    <div class="col-md-4">
        <!-- Anderes Feld -->
    </div>
    <div class="col-md-6">
        <!-- Von Datum -->
    </div>
    <div class="col-md-6">
        <!-- Bis Datum -->
    </div>
</div>
```

### Mobile-Optimierungen

- **Navbar**: Toggle-Button rechts positioniert
- **Sidebar**: Offcanvas für Mobile
- **Buttons**: Volle Breite auf Mobile bei Bedarf
- **Tabellen**: Umwandlung zu List-Group-Items

---

## JavaScript-Konventionen

### Tooltip-Initialisierung

#### Statische Inhalte

```javascript
// Automatisch in dashboard.js
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
  new bootstrap.Tooltip(el);
});
```

#### Dynamische Inhalte

```javascript
// Nach AJAX-Laden oder dynamischem Rendering
function initTooltips() {
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    const existingTooltip = bootstrap.Tooltip.getInstance(el);
    if (!existingTooltip) {
      new bootstrap.Tooltip(el);
    }
  });
}

// Aufruf nach Content-Update
setTimeout(() => {
  initTooltips();
}, 100);
```

### Dropdown-Management

```javascript
// Initialisierung mit autoClose
document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(toggle => {
  let dropdownInstance = bootstrap.Dropdown.getInstance(toggle);
  
  if (!dropdownInstance) {
    dropdownInstance = new bootstrap.Dropdown(toggle, {
      autoClose: true
    });
  }
});
```

### Event-Handler

- Verwende `DOMContentLoaded` für Initialisierung
- Nutze `setTimeout` für verzögerte Initialisierung (z.B. nach AJAX)
- Prüfe auf doppelte Initialisierung (z.B. bei Tooltips)
- Verwende Event-Delegation für dynamische Elemente

---

## CSS-Variablen und Theme-System

### Verfügbare Variablen

```css
--neo-theme-bg              /* Hintergrundfarbe */
--neo-theme-text            /* Textfarbe */
--neo-theme-primary          /* Primärfarbe (Buttons, Links) */
--neo-theme-secondary-bg    /* Sekundärer Hintergrund (Cards, Sidebar) */
--neo-theme-border           /* Border-Farbe */
--neo-theme-accent           /* Akzentfarbe (Hover-Effekte) */
```

### Verwendung

```css
.element {
    background-color: var(--neo-theme-bg, #ffffff);
    color: var(--neo-theme-text, #212529);
    border-color: var(--neo-theme-border, #dee2e6);
}
```

### Fallback-Werte

- Immer Fallback-Werte für White Mode angeben
- Format: `var(--neo-theme-variable, #default-color)`

---

## Modal-System

### Anforderungen

- **Zentrale Modals** im `neo-contacts` Plugin (als Beispiel)
- **Dark Mode Support** für alle Modal-Elemente
- **Konsistente Styling** mit Theme-Variablen

### Modal-Struktur

```html
<div class="modal fade" id="modalId" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Titel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Content -->
            </div>
            <div class="modal-footer">
                <!-- Buttons -->
            </div>
        </div>
    </div>
</div>
```

### Dark Mode für Modals

```css
html[data-neo-theme="dark"] .modal-content {
    background-color: var(--neo-theme-bg) !important;
    border-color: var(--neo-theme-border) !important;
    color: var(--neo-theme-text) !important;
}

html[data-neo-theme="dark"] .modal-header {
    background-color: var(--neo-theme-secondary-bg) !important;
    border-bottom-color: var(--neo-theme-border) !important;
}

html[data-neo-theme="dark"] .modal-footer {
    border-top-color: var(--neo-theme-border) !important;
}
```

---

## Button-Styling

### Button-Klassen

#### Standard-Buttons

- `btn-primary`: Primäre Aktionen
- `btn-outline-primary`: Sekundäre Aktionen
- `btn-outline-secondary`: Tertiäre Aktionen
- `btn-outline-danger`: Gefährliche Aktionen (Löschen)
- `btn-outline-warning`: Warnungen (Bearbeiten)
- `btn-outline-info`: Informationen (Anzeigen)

#### Button-Größen

- `btn-sm`: Kleine Buttons (in Headers, Tabellen)
- Standard: Normale Buttons
- `btn-lg`: Große Buttons (selten verwendet)

### Button-Gruppen

```html
<div class="btn-group btn-group-sm" role="group">
    <button type="button" class="btn btn-sm btn-outline-info">
        <i class="bi-eye"></i>
    </button>
    <button type="button" class="btn btn-sm btn-outline-secondary">
        <i class="bi-sticky"></i>
    </button>
    <button type="button" class="btn btn-sm btn-outline-warning">
        <i class="bi-pencil"></i>
    </button>
    <button type="button" class="btn btn-sm btn-outline-danger">
        <i class="bi-trash"></i>
    </button>
</div>
```

### Dark Mode für Buttons

```css
html[data-neo-theme="dark"] .btn-outline-primary,
html[data-neo-theme="dark"] .btn-outline-secondary {
    border-color: var(--neo-theme-border) !important;
    color: var(--neo-theme-text) !important;
}

html[data-neo-theme="dark"] .btn-outline-primary:hover,
html[data-neo-theme="dark"] .btn-outline-secondary:hover {
    background-color: var(--neo-theme-primary) !important;
    border-color: var(--neo-theme-primary) !important;
    color: #ffffff !important;
}
```

---

## Formular-Elemente

### Input-Felder

```html
<div class="mb-3">
    <label for="inputId" class="form-label">
        <i class="bi-icon"></i> Label-Text
    </label>
    <input type="text" 
           id="inputId" 
           class="form-control"
           placeholder="Placeholder-Text">
</div>
```

### Select-Felder

```html
<select id="selectId" class="form-select">
    <option value="">Alle</option>
    <option value="1">Option 1</option>
</select>
```

### Dark Mode für Formulare

```css
html[data-neo-theme="dark"] .form-control,
html[data-neo-theme="dark"] .form-select {
    background-color: var(--neo-theme-secondary-bg) !important;
    border-color: var(--neo-theme-border) !important;
    color: var(--neo-theme-text) !important;
}

html[data-neo-theme="dark"] .form-label {
    color: var(--neo-theme-text) !important;
}
```

---

## Tabellen und Listen

### Tabellen → List-Group

**Veraltet**: Tabellen für Datenlisten
**Empfohlen**: List-Group-Items für bessere Responsive-Darstellung

#### List-Group-Struktur

```html
<div class="list-group list-group-flush">
    <div class="list-group-item px-0 py-3 border-bottom">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div class="flex-grow-1">
                <h6 class="mb-1">
                    <i class="bi-icon text-primary"></i>
                    Titel
                </h6>
                <div class="small text-muted">
                    <!-- Zusatzinformationen -->
                </div>
            </div>
        </div>
        <div class="btn-group btn-group-sm" role="group">
            <!-- Action-Buttons -->
        </div>
    </div>
</div>
```

### Dark Mode für Listen

```css
html[data-neo-theme="dark"] .list-group-item {
    background-color: var(--neo-theme-bg) !important;
    border-color: var(--neo-theme-border) !important;
    color: var(--neo-theme-text) !important;
}

html[data-neo-theme="dark"] .list-group-item h6 {
    color: var(--neo-theme-text) !important;
}

html[data-neo-theme="dark"] .list-group-item .text-muted {
    color: rgba(255, 255, 255, 0.7) !important;
}
```

---

## Toast Notifications

### Anforderungen

- **Einheitliche API**: Nutze `NeoDash.toast*` statt eigener Implementierungen.
- **Bootstrap‑Toasts**: Kein eigenes Markup oder eigene CSS‑Implementierung.
- **i18n‑fähig**: Standardtitel/Labels stammen aus `NeoDash.strings`.
- **A11y‑konform**: `success/info` → `status/polite`, `error/warning` → `alert/assertive`.
- **Theme‑kompatibel**: Keine Inline‑Farben, Dark‑Mode muss funktionieren.

### JavaScript‑API

```js
NeoDash.toast('info', 'Kurze Info');
NeoDash.toastSuccess('Erfolg gespeichert!');
NeoDash.toastError('Fehler beim Speichern');
NeoDash.toastWarning('Achtung: prüfen Sie die Eingaben');
NeoDash.toastInfo('Hinweis für den Nutzer');
```

**Optionen:**

```js
NeoDash.toast('info', 'Nachricht', {
  duration: 8000,   // ms (0 = dauerhaft)
  title: 'Hinweis', // optionaler Titel
  autohide: true
});
```

### Globale Defaults (optional)

```js
window.NeoDash = window.NeoDash || {};
window.NeoDash.toastDefaults = {
  duration: 5000,
  containerClass: 'position-fixed top-0 end-0 p-3',
  containerZIndex: 9999
};
```

### Best Practices

- ✅ Verwende kurze, klare Texte (1–2 Sätze).
- ✅ Fehler/Warnungen sparsam einsetzen.
- ✅ Bei längeren Prozessen `duration: 0` + manuelles Schließen.
- ❌ Keine eigenen Toast‑Container pro Plugin anlegen.
- ❌ Keine Inline‑Styles im Toast‑Body setzen.

### Beispiele: Toast + Confirm

```js
// Erfolgs-Toast nach Aktion
NeoDash.toastSuccess('Eintrag gespeichert');

// Fehler-Toast mit langem Timeout
NeoDash.toastError('Fehler beim Speichern', { duration: 10000 });

// Confirm + Toast
NeoDash.confirm('Möchten Sie diesen Eintrag löschen?', {
  title: 'Löschen bestätigen',
  type: 'danger',
  confirmText: 'Löschen',
  cancelText: 'Abbrechen',
  onConfirm: () => {
    // ... AJAX/Delete
    NeoDash.toastSuccess('Eintrag gelöscht');
  }
});
```

### Review‑Checkliste (Plugins)

- **API‑Nutzung**: Toasts via `NeoDash.toast*` statt eigener Implementierung.
- **A11y**: Keine manuellen `role`/`aria-live` Änderungen nötig.
- **i18n**: Texte über `__()`/`esc_*__()` (PHP) oder `NeoDash.strings` (JS).
- **Dauer/Autohide**: Nur bei längeren Prozessen `duration: 0`.
- **Design**: Keine Inline‑Styles im Toast‑Body, Dark‑Mode prüfen.

### Do / Don’t (Notifications)

**Do**
- Nutze Toasts für kurzlebige Rückmeldungen (z. B. „Gespeichert“).
- Nutze zentrale Dashboard‑Notifications für dauerhafte Hinweise/Warnungen.
- Halte Texte kurz und eindeutig.
- Prüfe die Anzeige in Dark‑Mode.

**Don’t**
- Keine doppelten Hinweise (Toast + Banner für dasselbe Ereignis).
- Keine Inline‑Styles für Toast‑Body.
- Keine eigene Toast‑Implementierung im Plugin.

### Migration (bestehende Plugins)

- **Alte Toasts entfernen**: Eigene Toast‑Container/JS‑Toasts löschen.
- **Auf `NeoDash.toast*` umstellen**: überall konsistent verwenden.
- **Strings zentralisieren**: JS‑Texte aus `NeoDash.strings` beziehen.
- **Doppelte Hinweise prüfen**: Banner‑Notifications vs. Toasts.

## Navbar und Sidebar

### Navbar

#### Struktur

- **Links**: Logo und Brand
- **Rechts**: Theme-Toggle, Mobile-Menü-Toggle, Benutzermenü
- **Mobile**: Offcanvas-Sidebar

#### Tooltips

- Theme-Toggle: "Theme wechseln (Hell/Dunkel)"
- Mobile-Menü-Toggle: "Menü öffnen"
- Benutzermenü: "Benutzermenü öffnen"

### Sidebar

#### Desktop-Sidebar

- **Position**: Links, fest
- **Breite**: `col-md-3 col-lg-3`
- **Tooltips**: Auf Links mit `data-tooltip-title`

#### Mobile-Sidebar (Offcanvas)

- **Position**: Offcanvas links
- **Breite**: 280px
- **Schließen-Button**: Mit Tooltip "Menü schließen"

### Sidebar-Links

```html
<a href="/path" 
   class="nav-link"
   data-bs-toggle="tooltip"
   data-bs-placement="right"
   data-bs-title="Zu Seitenname">
    <i class="bi-icon"></i>
    Seitenname
</a>
```

---

## Pagination

### Struktur

```html
<nav aria-label="Pagination">
    <ul class="pagination pagination-sm mb-0">
        <li class="page-item">
            <a class="page-link" href="#" onclick="loadPage(1); return false;"
               data-bs-toggle="tooltip" 
               data-bs-placement="top" 
               data-bs-title="Erste Seite">
                1
            </a>
        </li>
        <!-- Weitere Seiten -->
    </ul>
</nav>
```

### Dark Mode

```css
html[data-neo-theme="dark"] .pagination .page-link {
    background-color: var(--neo-theme-secondary-bg) !important;
    border-color: var(--neo-theme-border) !important;
    color: var(--neo-theme-text) !important;
}

html[data-neo-theme="dark"] .pagination .page-item.active .page-link {
    background-color: var(--neo-theme-primary) !important;
    border-color: var(--neo-theme-primary) !important;
}
```

---

## Filter-Bereiche

### Struktur

```html
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Filter</h5>
        <button type="button" 
                class="btn btn-sm btn-outline-secondary" 
                onclick="toggleFilter()"
                data-bs-toggle="tooltip"
                data-bs-placement="top"
                data-bs-title="Filter ein-/ausblenden">
            <i class="bi-chevron-down" id="filter-icon"></i>
        </button>
    </div>
    <div class="card-body" id="filter-panel" style="display: none;">
        <form class="row g-3">
            <!-- Filter-Felder -->
        </form>
    </div>
</div>
```

### Responsive Layout

- **Textsuche**: `col-12`
- **Select-Felder**: `col-md-6 col-lg-4` oder `col-md-6 col-lg-3`
- **Datums-Felder**: `col-md-6 col-lg-6` (auf neuer Zeile)

---

## Icons

### Bootstrap Icons

- **Verwendung**: Bootstrap Icons (`bi-*`)
- **Größen**: Standard oder mit `fs-*` Klassen
- **Farben**: `text-primary`, `text-muted`, etc.

### Icon-Farben in Dark Mode

```css
html[data-neo-theme="dark"] .bi-icon {
    color: var(--neo-theme-text) !important;
}

html[data-neo-theme="dark"] .bi-icon.text-primary {
    color: var(--neo-theme-primary) !important;
}
```

---

## Überschriften und Text

### Überschriften-Struktur

- **Hauptüberschrift**: Nur einmal pro Seite (entweder in Card-Header oder als Page-Title)
- **Vermeide doppelte Überschriften** zwischen Navbar und Content

### Text-Farben

- **Standard**: `var(--neo-theme-text)`
- **Muted**: `text-muted` (mit Dark Mode Override)
- **Primary**: `text-primary` für wichtige Elemente

---

## Best Practices Checkliste

### Design

- [ ] Alle Tooltips auf Deutsch
- [ ] Konsistente Button-Farben (Primary, Secondary, Danger, Warning, Info)
- [ ] Einheitliche Widget-Header-Struktur
- [ ] Dark Mode Support für alle Elemente
- [ ] Responsive Design (Mobile, Tablet, Desktop)

### Funktionalität

- [ ] Tooltips initialisiert (statisch und dynamisch)
- [ ] Dropdowns schließen bei Klick außerhalb
- [ ] AJAX-Requests werden bei Modal-Close abgebrochen
- [ ] Pagination funktioniert korrekt
- [ ] Filter funktionieren server-seitig

### Code-Qualität

- [ ] CSS-Variablen für Theme-Farben verwendet
- [ ] Keine hardcodierten Farben
- [ ] Konsistente Klassennamen
- [ ] Kommentare für komplexe Logik
- [ ] Fehlerbehandlung implementiert

### Performance

- [ ] Lazy Loading für Avatare
- [ ] Caching für Statistiken
- [ ] Debouncing für Filter-Inputs
- [ ] Pagination für große Listen

---

## Implementierungs-Beispiele

### Beispiel: Neues Plugin-Widget

```php
// In Plugin-Manager
public function registerWidgets(): void
{
    do_action('neo_dashboard_register_widget', [
        'id' => 'my-plugin-widget',
        'label' => 'Mein Widget',
        'icon' => 'bi-icon',
        'callback' => [$this, 'render_widget'],
        'header_actions' => [
            [
                'type' => 'link',
                'href' => '/neo-dashboard/my-plugin',
                'icon' => 'bi-arrow-right',
                'text' => 'Alle anzeigen',
                'class' => 'btn-outline-primary btn-sm',
                'title' => 'Zur Übersichtsseite'
            ]
        ],
        'priority' => 10,
        'roles' => ['administrator', 'neo_editor']
    ]);
}

public function render_widget(): void
{
    // Verwende den zentralen Widget-Renderer
    echo $widgetComponentRenderer->render('stat-item', [
        'icon' => 'bi-icon',
        'label' => 'Statistik',
        'value' => 42,
        'value_color' => 'primary'
    ]);
}
```

### Beispiel: Neue Plugin-Seite

```php
// Template-Struktur
?>
<div class="container-fluid">
    <div class="d-flex justify-content-end mb-4">
        <button type="button" 
                class="btn btn-primary"
                onclick="myFunction()"
                data-bs-toggle="tooltip"
                data-bs-placement="top"
                data-bs-title="Neues Element erstellen">
            <i class="bi-plus-circle"></i> Neues Element
        </button>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Überschrift</h5>
                </div>
                <div class="card-body">
                    <!-- Content -->
                </div>
            </div>
        </div>
    </div>
</div>
```

---

## Wichtige Dateien und Pfade

### Neo Dashboard

- **Template-Pfad**: `NEO_DASHBOARD_TEMPLATE_PATH`
- **Widget-Header**: `templates/components/widgets/header.php`
- **Widget-Renderer**: `src/Presentation/WidgetComponentRenderer.php`
- **CSS**: `assets/dashboard.css`
- **JavaScript**: `assets/js/dashboard.js`

### Plugin-Integration

- **Widget-Registrierung**: `do_action('neo_dashboard_register_widget', [...])`
- **Section-Registrierung**: `do_action('neo_dashboard_register_section', [...])`
- **Sidebar-Item**: `do_action('neo_dashboard_register_sidebar_item', [...])`

---

## Changelog

### Version 1.0 (Aktuell)

- Tooltip-System implementiert
- Widget-Header-Komponente erstellt
- Benutzermenü mit Avatar-Fallback
- Dark Mode Support für alle Elemente
- Responsive Layout-Standards
- JavaScript-Konventionen definiert

---

## Kontakt und Support

Bei Fragen zur Implementierung oder Anpassung dieser Standards, konsultiere die bestehenden Plugins:
- `neo-contacts`: Beispiel für vollständige Plugin-Integration
- `neo-calendar`: Beispiel für Widget-Implementierung
- `neo-umfrage`: Beispiel für Section-Integration

---

**Letzte Aktualisierung**: 2025-01-XX
**Version**: 1.0

