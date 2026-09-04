# Konzept: Lokale Asset-Verwaltung für neo-dashboard

## 1. Aktueller Stand

### 1.1 Externe CDN-Assets (von CDN geladen)

#### neo-dashboard (AssetManager.php)
1. **Bootstrap CSS**
   - CDN: `https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css`
   - Version: 5.3.2
   - Lokal: ❌ Nicht vorhanden

2. **Bootstrap JS**
   - CDN: `https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js`
   - Version: 5.3.2
   - Lokal: ❌ Nicht vorhanden

3. **Bootstrap Icons**
   - CDN: `https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css`
   - Version: 1.10.5
   - Lokal: ❌ Nicht vorhanden

#### neo-calendar (DashboardManager.php)
4. **Flatpickr CSS**
   - CDN: `https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css`
   - Version: Nicht festgelegt
   - Lokal: ❌ Nicht vorhanden

5. **Flatpickr JS**
   - CDN: `https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js`
   - Version: Nicht festgelegt
   - Lokal: ❌ Nicht vorhanden

6. **Flatpickr Locale (de)**
   - CDN: `https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/de.js`
   - Version: Nicht festgelegt
   - Lokal: ❌ Nicht vorhanden

### 1.2 Lokale Assets (bereits vorhanden)

#### neo-dashboard
- `assets/css/dashboard.css`
- `assets/css/sections.css`
- `assets/css/widgets.css`
- `assets/css/theme-switcher.css`
- `assets/js/*.js` (verschiedene JS-Dateien)

#### neo-calendar
- `assets/css/neo-calendar-core.css`
- `assets/js/*.js` (verschiedene JS-Dateien)

### 1.3 Aktuelle Implementierung

**AssetManager.php:**
```php
private const BOOTSTRAP_VERSION = '5.3.2';
private const ICONS_VERSION     = '1.10.5';

// Bootstrap CSS
wp_enqueue_style(
    'neo-dashboard-bootstrap',
    "https://cdn.jsdelivr.net/npm/bootstrap@" . self::BOOTSTRAP_VERSION . "/dist/css/bootstrap.min.css",
    [],
    self::BOOTSTRAP_VERSION
);

// Bootstrap JS
wp_enqueue_script(
    'neo-dashboard-bootstrap',
    "https://cdn.jsdelivr.net/npm/bootstrap@" . self::BOOTSTRAP_VERSION . "/dist/js/bootstrap.bundle.min.js",
    ['jquery'],
    self::BOOTSTRAP_VERSION,
    true
);
```

---

## 2. Anforderungen

### 2.1 Hauptanforderungen
1. **Alle Assets lokal vorhalten**: Keine CDN-Abhängigkeiten
2. **Versionskontrolle**: Exakte Versionen für Reproduzierbarkeit
3. **Performance**: Lokale Assets sind schneller (kein externer Request)
4. **Offline-Fähigkeit**: System funktioniert ohne Internet
5. **Sicherheit**: Keine externe Abhängigkeit zu CDNs

### 2.2 Technische Anforderungen
1. **Strukturierte Ablage**: Assets in klar definierten Verzeichnissen
2. **Wartbarkeit**: Einfache Aktualisierung von Bibliotheken
3. **Kompatibilität**: Bestehender Code sollte minimal geändert werden müssen

---

## 3. Lösungsansätze

### 3.1 Option 1: Manueller Download (Empfohlen)

**Vorgehen:**
1. Assets manuell von CDN/Repository herunterladen
2. In lokale Verzeichnisse kopieren
3. AssetManager auf lokale Pfade umstellen

**Vorteile:**
- ✅ Einfach umzusetzen
- ✅ Keine zusätzlichen Abhängigkeiten
- ✅ Volle Kontrolle über Versionen
- ✅ Sofort einsatzbereit

**Nachteile:**
- ⚠️ Manuelle Updates erforderlich
- ⚠️ Mehrere Dateien pro Bibliothek (z.B. Flatpickr CSS + JS + Locales)

**Struktur:**
```
wp-content/plugins/neo-dashboard/assets/
  vendor/
    bootstrap/
      5.3.2/
        css/bootstrap.min.css
        js/bootstrap.bundle.min.js
    bootstrap-icons/
      1.10.5/
        font/bootstrap-icons.css
        font/fonts/bootstrap-icons.woff2
```

### 3.2 Option 2: npm/node_modules Integration

**Vorgehen:**
1. `package.json` erstellen
2. Assets via npm installieren
3. Assets nach Build-Verzeichnis kopieren (via Build-Script)

**Vorteile:**
- ✅ Automatische Versionsverwaltung
- ✅ Einfache Updates (`npm update`)
- ✅ Professionelle Dependency-Management

**Nachteile:**
- ⚠️ Erfordert npm/Node.js in Entwicklungsumgebung
- ⚠️ Build-Prozess erforderlich
- ⚠️ Mehr Komplexität

**Struktur:**
```
wp-content/plugins/neo-dashboard/
  package.json
  node_modules/
  assets/
    vendor/ (kopiert von node_modules)
```

### 3.3 Option 3: Composer (PHP Package Manager)

**Vorgehen:**
1. Assets via Composer installieren (z.B. `oomphinc/composer-installers-extender`)
2. Assets in Verzeichnis kopieren

**Vorteile:**
- ✅ Konsistent mit PHP-Workflow
- ✅ Version-Management

**Nachteile:**
- ⚠️ Erfordert Composer-Konfiguration
- ⚠️ Weniger Standard für Frontend-Assets

---

## 4. Empfohlene Lösung: Option 1 (Manueller Download)

### 4.1 Begründung

1. **Einfachheit**: Keine zusätzlichen Build-Tools erforderlich
2. **Klarheit**: Alle Assets sichtbar im Repository
3. **Kontrolle**: Exakte Versionen, keine unerwarteten Updates
4. **Performance**: Direkte Verfügbarkeit, keine Build-Zeit

### 4.2 Implementierungsplan

#### Phase 1: Verzeichnisstruktur erstellen

```
wp-content/plugins/neo-dashboard/assets/
  vendor/
    bootstrap/
      5.3.2/
        css/
          bootstrap.min.css
          bootstrap.min.css.map (optional)
        js/
          bootstrap.bundle.min.js
          bootstrap.bundle.min.js.map (optional)
    bootstrap-icons/
      1.10.5/
        font/
          bootstrap-icons.css
          fonts/
            bootstrap-icons.woff2
            bootstrap-icons.woff (optional)

wp-content/plugins/neo-calendar/assets/
  vendor/
    flatpickr/
      4.6.13/ (oder aktuelle Version)
        css/
          flatpickr.min.css
        js/
          flatpickr.min.js
          l10n/
            de.js
```

#### Phase 2: Assets herunterladen

**Bootstrap 5.3.2:**
- CSS: https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css
- JS: https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js

**Bootstrap Icons 1.10.5:**
- CSS: https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css
- Fonts: Von GitHub Release herunterladen

**Flatpickr (aktuelle Version, z.B. 4.6.13):**
- CSS: https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css
- JS: https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js
- Locale (de): https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/de.js

#### Phase 3: AssetManager anpassen

**Änderungen in AssetManager.php:**
```php
// Neue Konstante für lokale Asset-Pfade
private const ASSETS_VENDOR_DIR = 'assets/vendor';

// Bootstrap CSS - LOKAL
wp_enqueue_style(
    'neo-dashboard-bootstrap',
    plugin_dir_url(NEO_DASHBOARD_PLUGIN_FILE) . self::ASSETS_VENDOR_DIR . '/bootstrap/' . self::BOOTSTRAP_VERSION . '/css/bootstrap.min.css',
    [],
    self::BOOTSTRAP_VERSION
);

// Bootstrap JS - LOKAL
wp_enqueue_script(
    'neo-dashboard-bootstrap',
    plugin_dir_url(NEO_DASHBOARD_PLUGIN_FILE) . self::ASSETS_VENDOR_DIR . '/bootstrap/' . self::BOOTSTRAP_VERSION . '/js/bootstrap.bundle.min.js',
    ['jquery'],
    self::BOOTSTRAP_VERSION,
    true
);
```

#### Phase 4: neo-calendar DashboardManager anpassen

**Änderungen in DashboardManager.php:**
```php
// Neue Konstante
private const FLATPICKR_VERSION = '4.6.13';
private const ASSETS_VENDOR_DIR = 'assets/vendor';

// Flatpickr CSS - LOKAL
'flatpickr-css' => [
    'src' => $plugin_url . self::ASSETS_VENDOR_DIR . '/flatpickr/' . self::FLATPICKR_VERSION . '/css/flatpickr.min.css',
    'deps' => [],
    'contexts' => $all_calendar_contexts
],

// Flatpickr JS - LOKAL
'flatpickr-js' => [
    'src' => $plugin_url . self::ASSETS_VENDOR_DIR . '/flatpickr/' . self::FLATPICKR_VERSION . '/js/flatpickr.min.js',
    'deps' => [],
    'contexts' => $all_calendar_contexts
],

// Flatpickr Locale (de) - LOKAL
'flatpickr-locale-de' => [
    'src' => $plugin_url . self::ASSETS_VENDOR_DIR . '/flatpickr/' . self::FLATPICKR_VERSION . '/js/l10n/de.js',
    'deps' => ['flatpickr-js'],
    'contexts' => $all_calendar_contexts
],
```

---

## 5. Detaillierte Dateistruktur

### 5.1 neo-dashboard/assets/vendor/

```
vendor/
  bootstrap/
    5.3.2/
      css/
        bootstrap.min.css
      js/
        bootstrap.bundle.min.js
  bootstrap-icons/
    1.10.5/
      font/
        bootstrap-icons.css
        fonts/
          bootstrap-icons.woff2
```

### 5.2 neo-calendar/assets/vendor/

```
vendor/
  flatpickr/
    4.6.13/
      css/
        flatpickr.min.css
      js/
        flatpickr.min.js
        l10n/
          de.js
```

---

## 6. Code-Änderungen

### 6.1 AssetManager.php

**Vorher:**
```php
wp_enqueue_style(
    'neo-dashboard-bootstrap',
    "https://cdn.jsdelivr.net/npm/bootstrap@" . self::BOOTSTRAP_VERSION . "/dist/css/bootstrap.min.css",
    [],
    self::BOOTSTRAP_VERSION
);
```

**Nachher:**
```php
wp_enqueue_style(
    'neo-dashboard-bootstrap',
    plugin_dir_url(NEO_DASHBOARD_PLUGIN_FILE) . 'assets/vendor/bootstrap/' . self::BOOTSTRAP_VERSION . '/css/bootstrap.min.css',
    [],
    self::BOOTSTRAP_VERSION
);
```

### 6.2 DashboardManager.php (neo-calendar)

**Vorher:**
```php
'flatpickr-css' => [
    'src' => 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
    'deps' => [],
    'contexts' => $all_calendar_contexts
],
```

**Nachher:**
```php
private const FLATPICKR_VERSION = '4.6.13'; // Neue Konstante

'flatpickr-css' => [
    'src' => $plugin_url . 'assets/vendor/flatpickr/' . self::FLATPICKR_VERSION . '/css/flatpickr.min.css',
    'deps' => [],
    'contexts' => $all_calendar_contexts
],
```

---

## 7. Download-Quellen

### 7.1 Bootstrap 5.3.2
- **Releases**: https://github.com/twbs/bootstrap/releases/tag/v5.3.2
- **CDN (zum Download)**: https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/
- **Dateien**: `css/bootstrap.min.css`, `js/bootstrap.bundle.min.js`

### 7.2 Bootstrap Icons 1.10.5
- **Releases**: https://github.com/twbs/icons/releases/tag/v1.10.5
- **CDN (zum Download)**: https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/
- **Dateien**: `font/bootstrap-icons.css`, `font/fonts/bootstrap-icons.woff2`

### 7.3 Flatpickr 4.6.13 (oder aktuelle Version)
- **Releases**: https://github.com/flatpickr/flatpickr/releases
- **CDN (zum Download)**: https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/
- **Dateien**: `css/flatpickr.min.css`, `js/flatpickr.min.js`, `js/l10n/de.js`

---

## 8. Vorteile der Lösung

1. **✅ Keine CDN-Abhängigkeiten**: System funktioniert vollständig offline
2. **✅ Bessere Performance**: Lokale Assets sind schneller (keine externe DNS-Auflösung, keine Netzwerk-Latenz)
3. **✅ Versionskontrolle**: Exakte Versionen im Repository
4. **✅ Sicherheit**: Keine externe Abhängigkeit, die kompromittiert werden könnte
5. **✅ Einfachheit**: Keine Build-Tools erforderlich
6. **✅ Wartbarkeit**: Klare Struktur, einfache Updates

---

## 9. Update-Strategie

### 9.1 Für zukünftige Updates

1. **Neue Version herunterladen**
2. **In neuen Version-Ordner kopieren** (z.B. `5.3.3/`)
3. **Version-Konstante aktualisieren**
4. **Testen**
5. **Alte Version optional entfernen** (für Cleanup)

### 9.2 Beispiel-Update-Prozess

```bash
# 1. Neue Version herunterladen
# 2. In assets/vendor/bootstrap/5.3.3/ kopieren
# 3. In AssetManager.php: BOOTSTRAP_VERSION = '5.3.3' ändern
# 4. Testen
# 5. Optional: 5.3.2/ Verzeichnis löschen
```

---

## 10. Risiken und Mitigation

### Risiko 1: Assets nicht gefunden
**Mitigation**: Prüfung, ob Dateien existieren (optional, aber empfohlen)

```php
$css_path = plugin_dir_path(NEO_DASHBOARD_PLUGIN_FILE) . 'assets/vendor/bootstrap/' . self::BOOTSTRAP_VERSION . '/css/bootstrap.min.css';
if (!file_exists($css_path)) {
    error_log("Bootstrap CSS nicht gefunden: {$css_path}");
    // Fallback zu CDN (optional) oder Fehlerbehandlung
}
```

### Risiko 2: Große Repository-Größe
**Mitigation**: 
- Assets sind relativ klein (Bootstrap ~200KB, Flatpickr ~50KB)
- Git-LFS könnte verwendet werden (optional)

### Risiko 3: Manuelle Updates vergessen
**Mitigation**: 
- Dokumentation im Code (Kommentare mit Version)
- Regelmäßige Security-Audits

---

## 11. Implementierungs-Schritte

1. ✅ **Verzeichnisstruktur erstellen** (manuell)
2. ✅ **Assets herunterladen und kopieren** (manuell)
3. ✅ **AssetManager.php anpassen** (Code-Änderung)
4. ✅ **DashboardManager.php (neo-calendar) anpassen** (Code-Änderung)
5. ✅ **Testen**: Seite laden, Assets prüfen (Browser DevTools)
6. ✅ **Optional**: Alte CDN-Referenzen entfernen/kommentieren

---

## 12. Zusammenfassung

**Empfohlene Lösung**: Option 1 (Manueller Download)

**Vorteile:**
- ✅ Einfach umzusetzen
- ✅ Keine zusätzlichen Tools
- ✅ Volle Kontrolle
- ✅ Sofort einsatzbereit

**Nachteile:**
- ⚠️ Manuelle Updates erforderlich
- ⚠️ Repository wird etwas größer

**Nächste Schritte:**
1. Assets herunterladen
2. Verzeichnisstruktur erstellen
3. Code anpassen
4. Testen

---

**Version:** 1.0  
**Datum:** 2026-01-11  
**Autor:** AI-Assistent
