# Anleitung: Assets für lokale Verwendung herunterladen

Diese Anleitung beschreibt, wie die externen Assets (Bootstrap, Bootstrap Icons, Flatpickr) heruntergeladen und lokal abgelegt werden.

---

## 1. Bootstrap 5.3.2

### Download-Quellen:
- **CSS**: https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css
- **JS**: https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js
- **GitHub Release**: https://github.com/twbs/bootstrap/releases/tag/v5.3.2

### Ziel-Verzeichnis:
```
wp-content/plugins/neo-dashboard/assets/vendor/bootstrap/5.3.2/
```

### Dateien kopieren:
1. `css/bootstrap.min.css` → `wp-content/plugins/neo-dashboard/assets/vendor/bootstrap/5.3.2/css/bootstrap.min.css`
2. `js/bootstrap.bundle.min.js` → `wp-content/plugins/neo-dashboard/assets/vendor/bootstrap/5.3.2/js/bootstrap.bundle.min.js`

### Option 1: Direkter Download (Empfohlen)
```bash
# CSS herunterladen
curl -o wp-content/plugins/neo-dashboard/assets/vendor/bootstrap/5.3.2/css/bootstrap.min.css https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css

# JS herunterladen
curl -o wp-content/plugins/neo-dashboard/assets/vendor/bootstrap/5.3.2/js/bootstrap.bundle.min.js https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js
```

### Option 2: Manueller Download
1. CSS-URL im Browser öffnen: https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css
2. Inhalt kopieren und als `bootstrap.min.css` speichern
3. JS-URL im Browser öffnen: https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js
4. Inhalt kopieren und als `bootstrap.bundle.min.js` speichern

---

## 2. Bootstrap Icons 1.10.5

### Download-Quellen:
- **CSS**: https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css
- **GitHub Release**: https://github.com/twbs/icons/releases/tag/v1.10.5
- **Font-Dateien**: Aus GitHub Release ZIP

### Ziel-Verzeichnis:
```
wp-content/plugins/neo-dashboard/assets/vendor/bootstrap-icons/1.10.5/font/
```

### Dateien kopieren:
1. `bootstrap-icons.css` → `wp-content/plugins/neo-dashboard/assets/vendor/bootstrap-icons/1.10.5/font/bootstrap-icons.css`
2. `fonts/bootstrap-icons.woff2` → `wp-content/plugins/neo-dashboard/assets/vendor/bootstrap-icons/1.10.5/font/fonts/bootstrap-icons.woff2`

### Option 1: GitHub Release ZIP (Empfohlen)
1. GitHub Release öffnen: https://github.com/twbs/icons/releases/tag/v1.10.5
2. `bootstrap-icons-1.10.5.zip` herunterladen
3. ZIP entpacken
4. Kopieren:
   - `font/bootstrap-icons.css` → Ziel-Verzeichnis
   - `font/fonts/bootstrap-icons.woff2` → Ziel-Verzeichnis/fonts/

### Option 2: CSS-Download (Font-Dateien müssen separat heruntergeladen werden)
```bash
# CSS herunterladen
curl -o wp-content/plugins/neo-dashboard/assets/vendor/bootstrap-icons/1.10.5/font/bootstrap-icons.css https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css

# Font-Datei herunterladen (URL aus CSS-Datei entnehmen)
# Die CSS-Datei enthält Referenzen auf die Font-Dateien
```

**WICHTIG**: Die CSS-Datei referenziert Font-Dateien (`fonts/bootstrap-icons.woff2`). Diese müssen ebenfalls heruntergeladen werden!

**Font-URLs** (aus CSS):
- `fonts/bootstrap-icons.woff2`: https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/fonts/bootstrap-icons.woff2
- `fonts/bootstrap-icons.woff` (optional): https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/fonts/bootstrap-icons.woff

### CSS-Anpassung (wenn Font-Dateien lokal sind):
Nach dem Download müssen die Font-Pfade in `bootstrap-icons.css` angepasst werden:

**Vorher:**
```css
@font-face {
  font-family: "bootstrap-icons";
  src: url("./fonts/bootstrap-icons.woff2") format("woff2"),
       url("./fonts/bootstrap-icons.woff") format("woff");
}
```

**Nachher:**
```css
@font-face {
  font-family: "bootstrap-icons";
  src: url("./fonts/bootstrap-icons.woff2") format("woff2"),
       url("./fonts/bootstrap-icons.woff") format("woff");
}
```

Die Pfade sollten bereits relativ sein (`./fonts/`), daher sollte keine Änderung nötig sein, wenn die Struktur stimmt.

---

## 3. Flatpickr 4.6.13

### Download-Quellen:
- **CSS**: https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css
- **JS**: https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js
- **Locale (de)**: https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/de.js
- **GitHub Release**: https://github.com/flatpickr/flatpickr/releases

### Ziel-Verzeichnis:
```
wp-content/plugins/neo-calendar/assets/vendor/flatpickr/4.6.13/
```

### Dateien kopieren:
1. `css/flatpickr.min.css` → `wp-content/plugins/neo-calendar/assets/vendor/flatpickr/4.6.13/css/flatpickr.min.css`
2. `js/flatpickr.min.js` → `wp-content/plugins/neo-calendar/assets/vendor/flatpickr/4.6.13/js/flatpickr.min.js`
3. `js/l10n/de.js` → `wp-content/plugins/neo-calendar/assets/vendor/flatpickr/4.6.13/js/l10n/de.js`

### Option 1: Direkter Download (Empfohlen)
```bash
# CSS herunterladen
curl -o wp-content/plugins/neo-calendar/assets/vendor/flatpickr/4.6.13/css/flatpickr.min.css https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css

# JS herunterladen
curl -o wp-content/plugins/neo-calendar/assets/vendor/flatpickr/4.6.13/js/flatpickr.min.js https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js

# Locale (de) herunterladen
curl -o wp-content/plugins/neo-calendar/assets/vendor/flatpickr/4.6.13/js/l10n/de.js https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/de.js
```

### Option 2: Manueller Download
1. CSS-URL im Browser öffnen: https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css
2. Inhalt kopieren und als `flatpickr.min.css` speichern
3. JS-URL im Browser öffnen: https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js
4. Inhalt kopieren und als `flatpickr.min.js` speichern
5. Locale-URL im Browser öffnen: https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/de.js
6. Inhalt kopieren und als `de.js` speichern

---

## 4. Verifizierung

Nach dem Download sollten folgende Dateien existieren:

### neo-dashboard:
- ✅ `assets/vendor/bootstrap/5.3.2/css/bootstrap.min.css`
- ✅ `assets/vendor/bootstrap/5.3.2/js/bootstrap.bundle.min.js`
- ✅ `assets/vendor/bootstrap-icons/1.10.5/font/bootstrap-icons.css`
- ✅ `assets/vendor/bootstrap-icons/1.10.5/font/fonts/bootstrap-icons.woff2`

### neo-calendar:
- ✅ `assets/vendor/flatpickr/4.6.13/css/flatpickr.min.css`
- ✅ `assets/vendor/flatpickr/4.6.13/js/flatpickr.min.js`
- ✅ `assets/vendor/flatpickr/4.6.13/js/l10n/de.js`

---

## 5. Windows PowerShell (Alternative zu curl)

Falls `curl` nicht verfügbar ist, kann PowerShell verwendet werden:

```powershell
# Bootstrap CSS
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" -OutFile "wp-content/plugins/neo-dashboard/assets/vendor/bootstrap/5.3.2/css/bootstrap.min.css"

# Bootstrap JS
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" -OutFile "wp-content/plugins/neo-dashboard/assets/vendor/bootstrap/5.3.2/js/bootstrap.bundle.min.js"

# Bootstrap Icons CSS
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" -OutFile "wp-content/plugins/neo-dashboard/assets/vendor/bootstrap-icons/1.10.5/font/bootstrap-icons.css"

# Bootstrap Icons Font
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/fonts/bootstrap-icons.woff2" -OutFile "wp-content/plugins/neo-dashboard/assets/vendor/bootstrap-icons/1.10.5/font/fonts/bootstrap-icons.woff2"

# Flatpickr CSS
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" -OutFile "wp-content/plugins/neo-calendar/assets/vendor/flatpickr/4.6.13/css/flatpickr.min.css"

# Flatpickr JS
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js" -OutFile "wp-content/plugins/neo-calendar/assets/vendor/flatpickr/4.6.13/js/flatpickr.min.js"

# Flatpickr Locale (de)
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/de.js" -OutFile "wp-content/plugins/neo-calendar/assets/vendor/flatpickr/4.6.13/js/l10n/de.js"
```

---

## 6. Nach dem Download

1. **Seite testen**: Dashboard-Seite im Browser öffnen
2. **Browser DevTools prüfen**: Netzwerk-Tab öffnen, prüfen ob Assets geladen werden
3. **Fehler prüfen**: Console-Tab prüfen auf 404-Fehler
4. **Funktionalität testen**: Bootstrap-Komponenten und Flatpickr testen

---

**Version:** 1.0  
**Datum:** 2026-01-11  
**Autor:** AI-Assistent
