# Neo Dashboard Core

**Version:** 3.74.0
**Requires PHP:** 8.1+  
**License:** GPL-2.0-or-later

---

Ein zentrales Dashboard-Framework, das WordPress-Plugins nahtlos in eine einheitliche BenutzeroberflÃ¤che integriert. Entwickler kÃ¶nnen eigene Sidebar-Gruppen, Sections, Widgets und Notifications per Hook-API registrieren.

## âœ¨ Neue Features (3.0.3)

- ðŸ”— **WP-Admin Integration**: Link zu Neo Dashboard im WordPress Admin-MenÃ¼
- ðŸ“Š **Neo Umfrage**: VollstÃ¤ndige Statistik-Seite mit Feld-Analyse
- ðŸŽ¨ **Icon Buttons**: Moderne Icon-basierte Aktionsbuttons
- ðŸ“± **Responsive Design**: Optimiert fÃ¼r alle BildschirmgrÃ¶ÃŸen
- ðŸŒ“ **Dark Theme Support**: VollstÃ¤ndige UnterstÃ¼tzung fÃ¼r dunkle Themes
- ðŸ”§ **Domain Changer**: Neues Plugin fÃ¼r einfache Domain-Verwaltung

## ðŸ“¦ Installation

1. **Upload**  
   Kopiere den Ordner `neo-dashboard-core` in dein Verzeichnis `wp-content/plugins/`.
2. **Aktivieren**  
   Aktiviere das Plugin im WordPressâ€‘Admin unter **Plugins**.
3. **Seite anlegen**  
   Bei der ersten Aktivierung wird automatisch eine Seite mit dem Slug `neo-dashboard` erstellt und das Blank-Template zugewiesen.
4. **Permalinks**  
   (Nur bei URL-Problemen) Gehe zu **Einstellungen > Permalinks** und klicke auf â€žÃ„nderungen speichernâ€œ, um Rewrite-Rules zu flushen.

---

## ðŸš€ Schnellstart

1. Im Browser aufrufen:  
   `https://deine-domain.de/neo-dashboard/`
2. Hooks nutzen:  
   Registriere in deinem Plugin unter `add_action('neo_dashboard_init', ...)` eigene Komponenten.

```php
use NeoDashboard\Core\Extension\Definition\NavigationItemDefinition;
use NeoDashboard\Core\Extension\Definition\SectionDefinition;

add_action('neo_dashboard_init', function() {
    // Sidebar-Gruppe
    do_action('neo_dashboard_register_sidebar_item', NavigationItemDefinition::fromArray([
        'slug' => 'my-plugin',
        'label_callback' => static fn(): string => 'My Plugin',
        'icon' => 'bi-puzzle',
        'url' => '/neo-dashboard/my-plugin',
        'position' => 20,
        'is_group' => true,
    ]));

    // Section unter Gruppe
    do_action('neo_dashboard_register_sidebar_item', NavigationItemDefinition::fromArray([
        'slug' => 'my-plugin-settings',
        'label_callback' => static fn(): string => 'Einstellungen',
        'icon' => 'bi-gear',
        'url' => '/neo-dashboard/my-plugin-settings',
        'position' => 21,
        'parent' => 'my-plugin',
    ]));

    // Section im Content-Bereich
    do_action('neo_dashboard_register_section', SectionDefinition::fromArray([
        'slug' => 'my-plugin-settings',
        'label_callback' => static fn(): string => 'Einstellungen',
        'callback' => static function (): void {
            require plugin_dir_path(__FILE__) . 'templates/settings.php';
        },
    ]));
});
```

---

## ðŸ”Œ Architektur & Extensibility

- **PSRâ€‘4 Autoloader** lÃ¤dt alle Klassen im Namespace `NeoDashboard\Core`.
- **Bootstrap**: Schlanke Lebenszyklus-Fassade fÃ¼r Aktivierung und
  Deaktivierung. Laufzeitstart, Sprache, Dokumenttitel, Admin-MenÃ¼ und
  WordPress-Ausgabebereinigung liegen in getrennten Adaptern.
- **Dashboard-Laufzeit**: `DashboardRuntimePipeline` steuert die explizite
  Startreihenfolge. `DashboardManagerContainer` erzeugt und verbindet die
  Manager nicht mehr selbst, sondern erhÃ¤lt sie von
  `DashboardRuntimeCompositionRoot`; der `HookBus` kapselt WordPress-Hooks.
- **Registries**: Getrennte Registries verwalten Navigation, Sections, Widgets
  und Notifications. `DashboardRegistries` bÃ¼ndelt ihre Instanzen
  pro Runtime und wird vom Composition Root explizit an alle Verbraucher
  Ã¼bergeben; ein globaler Registry-Singleton existiert nicht mehr.
- **Manager**-Klassen: SidebarManager, SectionManager, WidgetManager, NotificationManager, AssetManager.
- **Assets**: `AssetCatalog` verwaltet Plugin- und Seiten-Assets sowie deren
  Kontextindex. `CoreAssetManifest` beschreibt die lokalen Core-Dateien;
  `CoreAssetEnqueuer`, `PluginAssetEnqueuer` und `DashboardAssetPrinter`
  kapseln Enqueueing, JavaScript-Lokalisierung und Standalone-Ausgabe.
  `CoreAssetPlatform` und `DashboardClientEnvironment` trennen WordPress-
  Enqueueing, DateiprÃ¼fung, URLs, Nonces und Ãœbersetzungen von der
  testbaren Asset-Orchestrierung und dem Aufbau der Client-Konfiguration.
  Auch Plugin-Assets laufen Ã¼ber `PluginAssetPlatform`; vorhandene
  Lokalisierungsdaten werden generisch nach Script-Handle und Objektname
  erhalten, ohne plugin-spezifische SonderfÃ¤lle im Core.
  `AssetManager` bleibt die kompatible WordPress-Hook-Fassade.
- **Sprachen**: `LanguageCatalog` verwaltet verfÃ¼gbare und Plugin-spezifische
  Sprachen. `PluginLanguageSelector` kapselt die Fallbackregel;
  `LanguageManager` Ã¼bernimmt nur WordPress-Hooks, Benutzer-Metadaten und AJAX.
- **Widget-AJAX**: `WidgetRenderService` kapselt AuflÃ¶sung, Zugriff, Cache und
  Rendering mit typisierten Ergebnissen. Provider, ZugriffsprÃ¼fung und Cache
  sind austauschbar. Der WordPress-unabhÃ¤ngige `WidgetAjaxController` arbeitet
  ausschlieÃŸlich mit `WidgetAjaxRequest` und `WidgetAjaxResponder`; konkrete
  WordPress-Adapter Ã¼bernehmen globale Eingaben, Nonce-PrÃ¼fung und kompatible
  JSON-Antworten. Die Composition Root Ã¼bergibt den Controller direkt an den
  Runtime-Initializer.
- **REST**: `RestRouteCollection` hÃ¤lt typisierte Routendefinitionen;
  `WordPressRestRouteRegistrar`, `WordPressRestPermissionChecker` und
  `RestEndpointResponder` kapseln Registrierung, Berechtigungen und einheitliche
  Erfolgs- beziehungsweise Fehlerantworten. `RestManager` bleibt Fassade fÃ¼r
  die Ã¶ffentliche Extension-API.
- **Notifications**: `NotificationVisibilityFilter` entscheidet unabhÃ¤ngig von
  WordPress Ã¼ber Ablauf, Rollen, Ausblendestatus und PrioritÃ¤t.
  `WordPressNotificationUserState` kapselt Benutzer-Metadaten;
  `NotificationService` und `NotificationRestController` bilden Anwendungsfall
  und REST-Transport ab. `NotificationManager` bleibt die Hook-Fassade.
- **Navigation und Sections**: `NavigationTreeBuilder` erzeugt sortierte,
  hierarchische Sichtdaten einschlieÃŸlich Labels und Tooltips an einer einzigen
  Stelle. `SidebarManager` und `SectionManager` erhalten ihre Registries als
  AbhÃ¤ngigkeiten und bleiben schlanke WordPress-Hook-Fassaden.
  Section-AuflÃ¶sung erfolgt direkt Ã¼ber den injizierten `SectionResolver`;
  Registry-Ergebnisse sind cache-stabil.
- **Widget-Definitionen**: `WidgetDefinitionLoader` kapselt den idempotenten
  Hook- und Ladezyklus; `WidgetSorter` erzeugt die priorisierte Sicht.
  `WidgetManager` erhÃ¤lt Registry, Loader und Sortierer als AbhÃ¤ngigkeiten und
  beschrÃ¤nkt sich auf die kompatible Registrierungsfassade.
- **Zugriff und Seitentitel**: `RoleAccessPolicy` entscheidet unabhÃ¤ngig von
  WordPress Ã¼ber rollenbasierte Sichtbarkeit und wird von Content- und Widget-
  Zugriff als explizite AbhÃ¤ngigkeit aus der Composition Root gemeinsam
  verwendet.
  `DashboardPageTitleBuilder` kapselt Dashboard-,
  Section- und 404-Titel. `WordPressDashboardPageTitleProvider` Ã¼bernimmt
  WordPress-Abfragen, Section-AuflÃ¶sung und anfrageweites Caching;
  `DashboardDocumentTitle` nutzt ihn direkt.
- **Request-Zugriff**: `WordPressAccessController` registriert die WordPress-
  Hooks fÃ¼r den injizierten `WordPressAccessEnforcer`; Ã¶ffentliche Plugins
  melden exakte und PrÃ¤fix-Pfade typisiert Ã¼ber
  `neo_dashboard_register_public_routes` an.
- **Servergrenzen**: Der Core verÃ¤ndert keine `.htaccess`-Datei und fÃ¼hrt kein
  eigenes Login- oder IP-Protokoll. Direkter Dateizugriff wird durch die
  vorhandenen `ABSPATH`-Guards und die Serverkonfiguration begrenzt.
- **BenutzermenÃ¼**: `UserMenuFormatter` kapselt Unicode-sichere Initialen und
  Rollenbezeichnungen. `UserRoleResolver` bestimmt Rollen mit expliziter
  PrioritÃ¤t und versorgt BenutzermenÃ¼ sowie Access-Denied-Ansicht.
  `WordPressUserMenuRenderer` erzeugt Avatar, Login-/
  Logout-URLs und Markup einmal pro Rendering; beide MenÃ¼templates erhalten
  das fertige Markup vom `DashboardRenderer`.
- **Widget-Komponenten**: `WidgetComponentRenderer` rendert ausschlieÃŸlich
  bekannte Komponenten-Templates und bereinigt Output-Buffer auch bei Fehlern.
  `WidgetValueColorPolicy` kapselt KPI-Farbschwellen und
  Fachplugins erzeugen oder erhalten den zustandsarmen Renderer als normale
  ObjektabhÃ¤ngigkeit; ein globaler Renderer-Kontext ist nicht erforderlich.
- **Request- und Section-AuflÃ¶sung**: `CurrentRequestTypeProvider` Ã¼bernimmt
  den typisierten Request-Kontext einschlieÃŸlich `ADMIN`.
  Der Provider wird pro Anfrage zwischengespeichert und vom Composition Root
  gemeinsam an Runtime-Initializer und Router Ã¼bergeben.
  Interne Dienste erhalten den Provider explizit; punktuelle statische Logger-
  und Template-Grenzen erzeugen den zustandsarmen Provider lokal.
- **Presentation**: `DashboardViewModelFactory` erstellt zugriffsgefilterte
  Sichtdaten; `DashboardResponseHandler` setzt den HTTP-Status und
  `DashboardRenderer` rendert das Layout.
- **DashboardRouter**: WordPress-Adapter fÃ¼r URL-Registrierung,
  Template-Auswahl und Body-Klassen steuert. Das Blank-Template besitzt das
  vollstÃ¤ndige HTML-Dokument; ein Output-Buffer oder Regex-Filtering wird
  nicht mehr verwendet.
- **Templates**: Blank-Template (`dashboard-blank.php`) und Layout-Template (`dashboard-layout.php`) basierend auf BootstrapÂ 5.

---

## ðŸ› ï¸ Hook-API Ãœbersicht

| Hook                             | Parameter                                 | Beschreibung                                   |
|----------------------------------|-------------------------------------------|------------------------------------------------|
| `neo_dashboard_init`             | —                                         | Wird nach Core-Setup gefeuert.                 |
| `neo_dashboard_register_sidebar_item` | `NavigationItemDefinition $definition` | Registriert ein Sidebar-Item (inkl. Gruppen).  |
| `neo_dashboard_register_section` | `SectionDefinition $definition`           | Registriert eine Section im Hauptbereich.      |
| `neo_dashboard_register_widget`  | `WidgetDefinition $definition`            | Registriert ein Widget im Dashboard.           |
| `neo_dashboard_register_notification` | `NotificationDefinition $definition` | Registriert eine Notification oben im Layout.  |
| `neo_dashboard_register_public_routes` | `PublicRouteRegistry $registry`, `RequestContext $context` | Registriert Ã¶ffentliche Exakt- oder PrÃ¤fix-Routen. |
| `neo_dashboard_enqueue_assets`   | —                                         | Fügt eigene CSS/JS nach Core-Assets ein.       |

**Registrierung erfolgt typisiert** (Beispiel Sidebar-Item):
```php
NavigationItemDefinition::fromArray([
  'slug' => 'key',
  'label_callback' => static fn(): string => 'Titel',
  'icon' => 'bi-icon',
  'url' => '/neo-dashboard/key',
  'position' => 10,
  'roles' => ['administrator'],
  'parent' => 'group-slug',
  'is_group' => false,
]);
```


---

## ðŸ“š Beispiele

### Sidebar-Gruppierung

```php
use NeoDashboard\Core\Extension\Definition\NavigationItemDefinition;

add_action('neo_dashboard_init', function() {
    // Gruppe definieren
    do_action('neo_dashboard_register_sidebar_item', NavigationItemDefinition::fromArray([
        'slug' => 'weather-plugin',
        'label_callback' => static fn(): string => 'Wetter-Plugin',
        'icon' => 'bi-cloud',
        'url' => '/neo-dashboard/weather-plugin',
        'position' => 10,
        'is_group' => true,
    ]));

    // Unterpunkte
    foreach (['3days','7days'] as $type) {
        do_action('neo_dashboard_register_sidebar_item', NavigationItemDefinition::fromArray([
            'slug' => 'weather-'.$type,
            'label_callback' => static fn(): string => ($type==='3days'?'3-Tage':'7-Tage').'-Wetter',
            'icon' => 'bi-calendar',
            'url' => '/neo-dashboard/weather-'.$type,
            'position' => ($type==='3days'?11:12),
            'parent' => 'weather-plugin',
        ]));
    }
});
```

### Wetter-Widget & Notification
```php
use NeoDashboard\Core\Extension\Definition\NotificationDefinition;
use NeoDashboard\Core\Extension\Definition\WidgetDefinition;

add_action('neo_dashboard_init', function() {
    // Aktuelles Wetter-Widget
    do_action('neo_dashboard_register_widget', WidgetDefinition::fromArray([
        'id' => 'current-weather',
        'label' => 'Aktuelles Wetter',
        'icon' => 'bi-thermometer-half',
        'priority' => 5,
        'callback' => function(){ /* Anzeige-Code */ },
    ]));

    // Unwetter-Warnung
    if ( $will_storm ) {
        do_action('neo_dashboard_register_notification', NotificationDefinition::fromArray([
            'id' => 'storm-alert',
            'message' => '<strong>⚠️ Gewitter erwartet!</strong>',
            'priority' => 1,
            'dismissible' => true,
        ]));
    }
});
```

---

## ðŸ”” Toast Notifications (Plugins)


```js
NeoDash.toast('info', 'Kurze Info');
NeoDash.toastSuccess('Erfolg gespeichert!');
NeoDash.toastError('Fehler beim Speichern');
NeoDash.toastWarning('Achtung: prÃ¼fen Sie die Eingaben');
NeoDash.toastInfo('Hinweis fÃ¼r den Nutzer');
```

**Optionen:**

```js
NeoDash.toast('info', 'Nachricht', {
  duration: 8000,   // ms (0 = dauerhaft)
  title: 'Hinweis', // optionaler Titel
  autohide: true
});
```

**Globale Defaults (optional):**

```js
window.NeoDash = window.NeoDash || {};
window.NeoDash.toastDefaults = {
  duration: 5000,
  containerClass: 'position-fixed top-0 end-0 p-3',
  containerZIndex: 9999
};
```

**A11yâ€‘Hinweis:**  
Erfolg/Info verwenden `role="status"` + `aria-live="polite"`, Fehler/Warnung `role="alert"` + `aria-live="assertive"`.

---

## ðŸŽ¨ Templates & Styling

- **`dashboard-blank.php`**: Komplettes Standalone-HTML ohne Theme-Header/Footer.  
- **`dashboard-layout.php`**: Haupt-Layout mit Navbar, Offcanvas-Sidebar (mobil) und Desktop-Sidebar, Content-Bereich.  
- **Styles**: Passe `assets/dashboard.css` an oder fÃ¼ge eigene CSS/JS mit `neo_dashboard_enqueue_assets` hinzu.

---

## ðŸ”Œ Integrierte Plugins

### Neo Umfrage
Plugin fÃ¼r Erstellung und Verwaltung von Umfragen.

**Features:**
- Template-basierte Umfragen
- Verschiedene Feldtypen (Text, Nummer, Radio, Checkbox, Select)
- Detaillierte Statistik-Seite mit Feld-Analyse
- DataTables-Integration fÃ¼r Ãœbersichten
- VollstÃ¤ndig responsive

### Neo Calendar
Kalender-Plugin mit FullCalendar-Integration.

**Features:**
- Event-Verwaltung
- Responsive Design fÃ¼r alle GerÃ¤te
- Mobile-optimierte Bedienung

### Neo Domain Changer
Einfache Domain-Verwaltung direkt aus WordPress.

**Features:**
- Sichere Domain-Validierung
- Automatische Skript-AusfÃ¼hrung
- Logging fÃ¼r Debugging
- WP-Admin Integration

---

## ðŸŽ¨ UI Components

### Buttons
- Icon-basierte Aktionsbuttons mit Bootstrap Icons
- Farbvarianten: Primary, Secondary, Danger, Warning, Success
- Hover-Effekte und Tooltips

### Tables
- DataTables-Integration
- Responsive Design
- Filterung und Sortierung
- Dark Theme Support

### Forms
- Moderne Form-Gestaltung
- Validierung
- Responsive Layouts

### Notifications
- Fixed-Position Benachrichtigungen
- Automatisches Ausblenden nach 5 Sekunden
- Slide-In Animation
- Farben bleiben in Dark Theme erhalten

---

## ðŸ“± Responsive Breakpoints

- **Desktop**: â‰¥ 1200px (4 Spalten Grid)
- **Tablet**: 768px - 1024px (25% Sidebar, 75% Content)
- **Mobile**: < 768px (100% Width, Offcanvas Sidebar)
- **Small Mobile**: < 480px (Kompakte UI-Elemente)

---

## Authentifizierter Smoke-Test

Der Smoke-Test prÃ¼ft Anmeldung, Dashboard, Neo-Calendar-Assets einschlieÃŸlich
Lokalisierung und einen echten Widget-AJAX-Aufruf. Das Passwort wird nicht im
Repository gespeichert, sondern nur Ã¼ber die Prozessumgebung Ã¼bergeben:

```powershell
$env:NEO_DASHBOARD_SMOKE_PASSWORD = '<Passwort>'
powershell -NoProfile -ExecutionPolicy Bypass -File tests/smoke-authenticated.ps1
Remove-Item Env:NEO_DASHBOARD_SMOKE_PASSWORD
```



