# Konzept: Multi-Language-System für Neo Dashboard

**Erstellt am:** 2025-12-26  
**Version:** 1.0  
**Status:** Konzept  
**Zweck:** Professionelles Multi-Language-System für neo-dashboard und alle abhängigen Plugins

---

## Inhaltsverzeichnis

1. [Übersicht](#übersicht)
2. [Architektur](#architektur)
3. [Sprach-Registrierung](#sprach-registrierung)
4. [Frontend-Integration](#frontend-integration)
5. [Backend-Integration](#backend-integration)
6. [Persistierung](#persistierung)
7. [Fallback-Mechanismus](#fallback-mechanismus)
8. [Implementierungsplan](#implementierungsplan)
9. [Best Practices](#best-practices)

---

## Übersicht

### Ziele

- **Zentrale Sprachauswahl** in neo-dashboard für alle Plugins
- **Automatische Fallback-Mechanismen** auf Standard-Sprache (Deutsch)
- **Plugin-Registrierung** ihrer unterstützten Sprachen
- **WordPress i18n/l10n Integration** für professionelle Übersetzungen
- **Persistierung** der Sprachauswahl pro Benutzer
- **Dynamische Sprachumschaltung** ohne Seiten-Reload

### Standard-Sprache

- **Standard:** Deutsch (`de_DE`)
- **Fallback:** Wenn Plugin die gewählte Sprache nicht unterstützt, wird Deutsch verwendet

### Unterstützte Sprachen (Initial)

- Deutsch (`de_DE`) - Standard
- Englisch (`en_US`)
- Weitere Sprachen können von Plugins registriert werden

---

## Architektur

### Komponenten

```
┌─────────────────────────────────────────────────────────┐
│                    neo-dashboard                        │
│  ┌──────────────────────────────────────────────────┐  │
│  │         LanguageManager (Core)                    │  │
│  │  - Registriert verfügbare Sprachen               │  │
│  │  - Verwaltet Sprachauswahl                        │  │
│  │  - Stellt API für Plugins bereit                  │  │
│  └──────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────┐  │
│  │         LanguageSwitcher (Frontend)               │  │
│  │  - Dropdown in Navbar                            │  │
│  │  - Sprachumschaltung                              │  │
│  │  - Persistierung in localStorage                  │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
                        │
                        │ Plugin-Registrierung
                        ▼
┌─────────────────────────────────────────────────────────┐
│              Plugin (z.B. neo-calendar)                 │
│  ┌──────────────────────────────────────────────────┐  │
│  │         Plugin registriert Sprachen              │  │
│  │  - do_action('neo_dashboard_register_languages') │  │
│  │  - ['de_DE', 'en_US']                            │  │
│  └──────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────┐  │
│  │         Plugin lädt Übersetzungen                 │  │
│  │  - load_plugin_textdomain()                       │  │
│  │  - Basierend auf aktueller Sprache               │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

### Datenfluss

1. **Plugin-Registrierung:** Plugin meldet unterstützte Sprachen an neo-dashboard
2. **Sprachauswahl:** Benutzer wählt Sprache im Dropdown
3. **Persistierung:** Sprache wird in localStorage gespeichert
4. **Event-Dispatch:** neo-dashboard sendet Event an alle Plugins
5. **Plugin-Reaktion:** Plugins laden entsprechende Übersetzungen
6. **Fallback:** Wenn Plugin Sprache nicht unterstützt, wird Deutsch verwendet

---

## Sprach-Registrierung

### Plugin-Registrierung

Plugins registrieren ihre unterstützten Sprachen über einen WordPress Action Hook:

```php
// In Plugin Bootstrap (z.B. neo-calendar/src/Bootstrap.php)

public function registerLanguages(): void
{
    do_action('neo_dashboard_register_languages', 'neo-calendar', [
        'de_DE' => 'Deutsch',
        'en_US' => 'English',
        // Weitere Sprachen...
    ]);
}
```

### LanguageManager (neo-dashboard)

```php
// wp-content/plugins/neo-dashboard/src/Manager/LanguageManager.php

namespace NeoDashboard\Manager;

class LanguageManager
{
    private array $availableLanguages = [];
    private array $pluginLanguages = [];
    private string $defaultLanguage = 'de_DE';
    private string $currentLanguage = 'de_DE';
    
    public function __construct()
    {
        // Standard-Sprachen registrieren
        $this->availableLanguages = [
            'de_DE' => [
                'code' => 'de_DE',
                'name' => 'Deutsch',
                'native_name' => 'Deutsch',
                'flag' => '🇩🇪',
                'default' => true
            ],
            'en_US' => [
                'code' => 'en_US',
                'name' => 'English',
                'native_name' => 'English',
                'flag' => '🇺🇸',
                'default' => false
            ]
        ];
        
        // Hook für Plugin-Registrierung
        add_action('neo_dashboard_register_languages', [$this, 'registerPluginLanguages'], 10, 2);
    }
    
    /**
     * Registriert Sprachen eines Plugins
     */
    public function registerPluginLanguages(string $plugin_id, array $languages): void
    {
        $this->pluginLanguages[$plugin_id] = $languages;
        
        // Verfügbare Sprachen erweitern (falls neue Sprache)
        foreach ($languages as $code => $name) {
            if (!isset($this->availableLanguages[$code])) {
                // Neue Sprache hinzufügen (mit Standard-Flag)
                $this->availableLanguages[$code] = [
                    'code' => $code,
                    'name' => $name,
                    'native_name' => $name,
                    'flag' => $this->getFlagForLanguage($code),
                    'default' => false
                ];
            }
        }
    }
    
    /**
     * Gibt alle verfügbaren Sprachen zurück
     */
    public function getAvailableLanguages(): array
    {
        return $this->availableLanguages;
    }
    
    /**
     * Prüft ob Plugin eine Sprache unterstützt
     */
    public function pluginSupportsLanguage(string $plugin_id, string $language_code): bool
    {
        if (!isset($this->pluginLanguages[$plugin_id])) {
            return false;
        }
        
        return isset($this->pluginLanguages[$plugin_id][$language_code]);
    }
    
    /**
     * Gibt die aktuelle Sprache zurück
     */
    public function getCurrentLanguage(): string
    {
        return $this->currentLanguage;
    }
    
    /**
     * Setzt die aktuelle Sprache
     */
    public function setCurrentLanguage(string $language_code): void
    {
        if (!isset($this->availableLanguages[$language_code])) {
            $language_code = $this->defaultLanguage;
        }
        
        $this->currentLanguage = $language_code;
        
        // Event für Plugins
        do_action('neo_dashboard_language_changed', $language_code);
    }
    
    /**
     * Gibt die beste Sprache für ein Plugin zurück (mit Fallback)
     */
    public function getLanguageForPlugin(string $plugin_id, ?string $preferred_language = null): string
    {
        $language = $preferred_language ?? $this->currentLanguage;
        
        // Prüfe ob Plugin die Sprache unterstützt
        if ($this->pluginSupportsLanguage($plugin_id, $language)) {
            return $language;
        }
        
        // Fallback auf Standard-Sprache
        return $this->defaultLanguage;
    }
    
    /**
     * Flag für Sprachcode (vereinfacht)
     */
    private function getFlagForLanguage(string $code): string
    {
        $flags = [
            'de_DE' => '🇩🇪',
            'en_US' => '🇺🇸',
            'fr_FR' => '🇫🇷',
            'es_ES' => '🇪🇸',
            'it_IT' => '🇮🇹',
            // Weitere...
        ];
        
        return $flags[$code] ?? '🌐';
    }
}
```

---

## Frontend-Integration

### Navbar-Integration

```php
// wp-content/plugins/neo-dashboard/templates/partials/navbar.php

<div class="d-flex align-items-center ms-auto">
    <!-- Sprachauswahl -->
    <div class="dropdown me-2">
        <button id="language-toggle-navbar" 
                class="btn btn-outline-secondary" 
                type="button"
                data-bs-toggle="dropdown"
                data-bs-auto-close="true"
                aria-expanded="false"
                data-bs-toggle="tooltip"
                data-bs-placement="bottom"
                data-bs-title="Sprache auswählen">
            <span id="language-flag">🇩🇪</span>
            <span id="language-code" class="d-none d-md-inline ms-1">DE</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" id="language-dropdown">
            <!-- Wird dynamisch gefüllt -->
        </ul>
    </div>
    
    <!-- Theme-Toggle -->
    <button id="theme-toggle-navbar" 
            class="btn btn-outline-secondary me-2" 
            data-bs-toggle="tooltip"
            data-bs-placement="bottom"
            data-bs-title="Theme wechseln (Hell/Dunkel)">
        🌙
    </button>
    
    <!-- Weitere Buttons... -->
</div>
```

### JavaScript-Integration

```javascript
// wp-content/plugins/neo-dashboard/assets/js/language-switcher.js

(function() {
    'use strict';
    
    if (!document.body.classList.contains('neo-dashboard-standalone')) {
        return;
    }
    
    const STORAGE_KEY = 'neo-dashboard-language';
    const DEFAULT_LANGUAGE = 'de_DE';
    
    const languageManager = {
        currentLanguage: DEFAULT_LANGUAGE,
        availableLanguages: {},
        
        init: function() {
            // Verfügbare Sprachen von Backend laden
            if (typeof NeoDash !== 'undefined' && NeoDash.languages) {
                this.availableLanguages = NeoDash.languages;
            }
            
            // Gespeicherte Sprache laden
            this.loadSavedLanguage();
            
            // Dropdown rendern
            this.renderDropdown();
            
            // Event-Listener
            this.setupEventListeners();
            
            // Initiale Sprache setzen
            this.setLanguage(this.currentLanguage, false);
        },
        
        loadSavedLanguage: function() {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved && this.availableLanguages[saved]) {
                this.currentLanguage = saved;
            }
        },
        
        renderDropdown: function() {
            const dropdown = document.getElementById('language-dropdown');
            if (!dropdown) return;
            
            dropdown.innerHTML = '';
            
            Object.values(this.availableLanguages).forEach(lang => {
                const item = document.createElement('li');
                item.className = 'dropdown-item d-flex align-items-center';
                
                if (lang.code === this.currentLanguage) {
                    item.classList.add('active');
                }
                
                item.innerHTML = `
                    <span class="me-2">${lang.flag}</span>
                    <span>${lang.native_name}</span>
                    ${lang.code === this.currentLanguage ? '<i class="bi-check ms-auto"></i>' : ''}
                `;
                
                item.addEventListener('click', () => {
                    this.setLanguage(lang.code, true);
                });
                
                dropdown.appendChild(item);
            });
        },
        
        setLanguage: function(languageCode, save = true) {
            if (!this.availableLanguages[languageCode]) {
                languageCode = DEFAULT_LANGUAGE;
            }
            
            this.currentLanguage = languageCode;
            const lang = this.availableLanguages[languageCode];
            
            // UI aktualisieren
            const flagEl = document.getElementById('language-flag');
            const codeEl = document.getElementById('language-code');
            
            if (flagEl) flagEl.textContent = lang.flag;
            if (codeEl) codeEl.textContent = lang.code.substring(0, 2).toUpperCase();
            
            // HTML-Attribut setzen
            document.documentElement.setAttribute('data-neo-language', languageCode);
            document.documentElement.setAttribute('lang', languageCode.substring(0, 2));
            
            // Speichern
            if (save) {
                localStorage.setItem(STORAGE_KEY, languageCode);
            }
            
            // Event für Plugins
            window.dispatchEvent(new CustomEvent('neo-dashboard-language-changed', {
                detail: { language: languageCode }
            }));
            
            // AJAX-Request an Backend (für Server-seitige Übersetzungen)
            this.notifyBackend(languageCode);
            
            // Dropdown neu rendern
            this.renderDropdown();
        },
        
        notifyBackend: function(languageCode) {
            if (typeof jQuery === 'undefined' || !NeoDash || !NeoDash.ajaxurl) {
                return;
            }
            
            jQuery.ajax({
                url: NeoDash.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'neo_dashboard_set_language',
                    language: languageCode,
                    nonce: NeoDash.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Optional: Seite neu laden für vollständige Übersetzung
                        // window.location.reload();
                    }
                }
            });
        },
        
        setupEventListeners: function() {
            // Event von anderen Quellen (z.B. Backend)
            window.addEventListener('neo-dashboard-language-changed', (e) => {
                if (e.detail && e.detail.language) {
                    this.setLanguage(e.detail.language, false);
                }
            });
        }
    };
    
    // Initialisierung
    document.addEventListener('DOMContentLoaded', function() {
        languageManager.init();
    });
    
    // Export für Plugins
    window.NeoDashLanguage = languageManager;
})();
```

### Backend-Lokalisierung

```php
// wp-content/plugins/neo-dashboard/src/Manager/DashboardManager.php

public function localizeScripts(): void
{
    $languageManager = $this->getLanguageManager();
    
    wp_localize_script('neo-dashboard-core', 'NeoDash', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('neo_dashboard_nonce'),
        'languages' => $languageManager->getAvailableLanguages(),
        'currentLanguage' => $languageManager->getCurrentLanguage(),
        // ...
    ]);
}
```

---

## Backend-Integration

### WordPress i18n/l10n Integration

```php
// Plugin Bootstrap (z.B. neo-calendar/src/Bootstrap.php)

public function loadTextdomain(): void
{
    $languageManager = $this->getLanguageManager();
    $plugin_id = 'neo-calendar';
    
    // Beste Sprache für Plugin ermitteln
    $language = $languageManager->getLanguageForPlugin($plugin_id);
    
    // Textdomain laden
    $domain = 'neo-calendar';
    $mofile = NEO_CALENDAR_PLUGIN_PATH . "languages/{$domain}-{$language}.mo";
    
    if (file_exists($mofile)) {
        load_textdomain($domain, $mofile);
    } else {
        // Fallback auf Standard-Sprache
        $default_mofile = NEO_CALENDAR_PLUGIN_PATH . "languages/{$domain}-de_DE.mo";
        if (file_exists($default_mofile)) {
            load_textdomain($domain, $default_mofile);
        }
    }
    
    // WordPress Standard-Loading
    load_plugin_textdomain(
        $domain,
        false,
        dirname(plugin_basename(NEO_CALENDAR_PLUGIN_FILE)) . '/languages'
    );
}
```

### Hook für Sprachänderung

```php
// Plugin reagiert auf Sprachänderung

add_action('neo_dashboard_language_changed', function($language_code) {
    // Textdomain neu laden
    $this->loadTextdomain();
    
    // Optional: Cache leeren
    // wp_cache_flush();
}, 10, 1);
```

### AJAX-Handler für Sprachänderung

```php
// wp-content/plugins/neo-dashboard/src/Manager/AjaxManager.php

public function ajaxSetLanguage(): void
{
    check_ajax_referer('neo_dashboard_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Nicht angemeldet']);
        return;
    }
    
    $language_code = sanitize_text_field($_POST['language'] ?? '');
    $languageManager = $this->getLanguageManager();
    
    if (!$languageManager->isLanguageAvailable($language_code)) {
        wp_send_json_error(['message' => 'Sprache nicht verfügbar']);
        return;
    }
    
    // Sprache setzen
    $languageManager->setCurrentLanguage($language_code);
    
    // Optional: In User-Meta speichern
    update_user_meta(get_current_user_id(), 'neo_dashboard_language', $language_code);
    
    wp_send_json_success([
        'language' => $language_code,
        'message' => 'Sprache geändert'
    ]);
}
```

---

## Persistierung

### Frontend (localStorage)

```javascript
// Sprache wird in localStorage gespeichert
localStorage.setItem('neo-dashboard-language', 'en_US');
const saved = localStorage.getItem('neo-dashboard-language');
```

### Backend (User Meta)

```php
// Optional: Pro Benutzer speichern
update_user_meta($user_id, 'neo_dashboard_language', 'en_US');
$language = get_user_meta($user_id, 'neo_dashboard_language', true) ?: 'de_DE';
```

### Priorität

1. **localStorage** (Frontend) - für sofortige Umschaltung
2. **User Meta** (Backend) - für persistente Einstellung
3. **Default** - Deutsch

---

## Fallback-Mechanismus

### Hierarchie

```
1. Gewählte Sprache (wenn Plugin unterstützt)
   ↓
2. Standard-Sprache (de_DE)
   ↓
3. Englisch (en_US) - optional
   ↓
4. Original-Text (falls keine Übersetzung)
```

### Implementierung

```php
// LanguageManager::getLanguageForPlugin()

public function getLanguageForPlugin(string $plugin_id, ?string $preferred_language = null): string
{
    $language = $preferred_language ?? $this->currentLanguage;
    
    // 1. Prüfe ob Plugin die gewählte Sprache unterstützt
    if ($this->pluginSupportsLanguage($plugin_id, $language)) {
        return $language;
    }
    
    // 2. Fallback auf Standard-Sprache
    if ($this->pluginSupportsLanguage($plugin_id, $this->defaultLanguage)) {
        return $this->defaultLanguage;
    }
    
    // 3. Fallback auf erste verfügbare Sprache des Plugins
    if (isset($this->pluginLanguages[$plugin_id]) && !empty($this->pluginLanguages[$plugin_id])) {
        return array_key_first($this->pluginLanguages[$plugin_id]);
    }
    
    // 4. Standard-Sprache als letzter Fallback
    return $this->defaultLanguage;
}
```

---

## Implementierungsplan

### Phase 1: Core-System (neo-dashboard)

1. ✅ **LanguageManager erstellen**
   - Sprach-Registrierung
   - Plugin-API
   - Fallback-Logik

2. ✅ **Frontend-Integration**
   - Dropdown in Navbar
   - JavaScript für Sprachumschaltung
   - localStorage-Persistierung

3. ✅ **Backend-Integration**
   - AJAX-Handler für Sprachänderung
   - User Meta-Persistierung
   - Event-System

### Phase 2: Plugin-Integration

4. ✅ **neo-calendar**
   - Sprach-Registrierung
   - Textdomain-Loading
   - Übersetzungs-Dateien

5. ✅ **neo-contacts**
   - Sprach-Registrierung
   - Textdomain-Loading
   - Übersetzungs-Dateien

6. ✅ **neo-surveys**
   - Sprach-Registrierung
   - Textdomain-Loading
   - Übersetzungs-Dateien

### Phase 3: Übersetzungen

7. ✅ **Deutsche Übersetzungen** (Standard)
8. ✅ **Englische Übersetzungen**
9. ✅ **Weitere Sprachen** (nach Bedarf)

---

## Best Practices

### Für Plugin-Entwickler

#### 1. Sprach-Registrierung

```php
// Immer im Bootstrap registrieren
public function registerLanguages(): void
{
    do_action('neo_dashboard_register_languages', 'plugin-id', [
        'de_DE' => 'Deutsch',
        'en_US' => 'English',
    ]);
}
```

#### 2. Textdomain-Loading

```php
// Immer nach Sprachänderung neu laden
add_action('neo_dashboard_language_changed', function($language_code) {
    $this->loadTextdomain();
}, 10, 1);
```

#### 3. Übersetzungs-Funktionen verwenden

```php
// Immer __() oder _e() verwenden
echo __('Mein Text', 'plugin-textdomain');
_e('Mein Text', 'plugin-textdomain');
```

#### 4. Übersetzungs-Dateien

```
plugin-name/
  languages/
    plugin-name-de_DE.po
    plugin-name-de_DE.mo
    plugin-name-en_US.po
    plugin-name-en_US.mo
```

#### 5. JavaScript-Übersetzungen

```php
// Übersetzungen an Frontend übergeben
wp_localize_script('plugin-js', 'pluginAjax', [
    'strings' => [
        'delete_confirm' => __('Sind Sie sicher?', 'plugin-textdomain'),
        'error' => __('Ein Fehler ist aufgetreten', 'plugin-textdomain'),
    ],
    'currentLanguage' => $languageManager->getCurrentLanguage(),
]);
```

### Für neo-dashboard

#### 1. Konsistente API

- Einheitliche Methoden-Namen
- Klare Dokumentation
- Event-System für Plugins

#### 2. Performance

- Caching von Sprach-Daten
- Lazy Loading von Übersetzungen
- Minimale AJAX-Requests

#### 3. Benutzerfreundlichkeit

- Klare Sprachauswahl
- Flaggen für visuelle Erkennung
- Native Namen für Sprachen

---

## Technische Details

### Sprach-Codes

- **Format:** `ll_CC` (z.B. `de_DE`, `en_US`)
- **Standard:** `de_DE` (Deutsch)
- **Fallback:** `de_DE` wenn nicht verfügbar

### Event-System

```javascript
// Frontend Event
window.dispatchEvent(new CustomEvent('neo-dashboard-language-changed', {
    detail: { language: 'en_US' }
}));

// Backend Action
do_action('neo_dashboard_language_changed', 'en_US');
```

### CSS-Integration

```css
/* Sprach-spezifische Styles */
html[data-neo-language="ar"] {
    direction: rtl;
}

html[data-neo-language="de_DE"] {
    direction: ltr;
}
```

---

## Zusammenfassung

### Vorteile

- ✅ **Zentrale Verwaltung** aller Sprachen
- ✅ **Automatischer Fallback** auf Standard-Sprache
- ✅ **Plugin-Autonomie** bei Sprach-Registrierung
- ✅ **WordPress-Standard** (i18n/l10n)
- ✅ **Benutzerfreundlich** mit visueller Sprachauswahl
- ✅ **Performance-optimiert** mit Caching

### Nächste Schritte

1. LanguageManager implementieren
2. Frontend-Integration in Navbar
3. Plugin-API dokumentieren
4. Erste Plugin-Integration (neo-calendar)
5. Übersetzungen erstellen

---

**Erstellt am:** 2025-12-26  
**Version:** 1.0  
**Status:** Konzept - Bereit für Implementierung

