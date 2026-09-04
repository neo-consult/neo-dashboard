# Neo Dashboard – Konzept Widgets Startseite

## Ziel
Eine übersichtliche Startseite, die in <10 Sekunden Orientierung schafft:
- **Status** (Was ist kritisch?)
- **Fokus** (Was ist als Nächstes zu tun?)
- **Aktivität** (Was hat sich zuletzt geändert?)

## Leitprinzipien
- **Modular**: Widgets werden von Plugins registriert.
- **Priorisiert**: Kritische Widgets oben.
- **Kompakt**: 1–2 Aktionen pro Widget.
- **Schnell**: Caching pro Widget/Plugin.
- **Barrierearm**: klare Labels, keine reinen Farb-Codes.

## Layout-Grid (Startseite)
- **Spalte links (2/3)**: KPIs, Listen, Aktivität
- **Spalte rechts (1/3)**: Quick Actions, Hinweise/Warnings

## Widget-Kategorien (mit Beispielen)

### 1) Überblick & KPIs
- **Globaler Status** (gesamt): z. B. „Heute 5 Termine, 2 offene Aufgaben“
- **Plugin-KPIs** (pro Plugin 2–3 Zahlen)
  - `neo-calendar`: Termine heute, Meetings diese Woche, offene Abwesenheiten
  - `neo-contacts`: neue Personen (7 Tage), fehlende Zuordnungen
  - `neo-surveys`: neue Umfragen (7 Tage), Antworten gesamt

### 2) Schnellaktionen
- **Kontext-Aktionen** (pro Plugin 1–2)
  - Neue Person/Organisation
  - Neuer Termin/Meeting
  - Neue Umfrage
- Actions müssen **rolle-basiert** sein.

### 3) Hinweise & Qualitätschecks
- **Datenqualität** (Warnungen/Infos)
  - Personen ohne Organisation
  - Organisationen ohne Personen
  - Einstellungen unvollständig
- **Systemhinweise** (z. B. Integrationen deaktiviert)

### 4) Letzte Aktivität
- **Letzte Änderungen** pro Plugin (kurze Liste 5–10 Einträge)
- Zeitstempel + Link zur Detailseite.

### 5) Trends / Verlauf (optional)
- Kleine Sparklines oder „Vorwoche vs. jetzt“
- Nur wenn Daten zuverlässig verfügbar.

## Priorisierung (Beispiel)
1. **Warnings / Fehler** (höchste Priorität)
2. **KPIs** (kurz, oben links)
3. **Quick Actions** (oben rechts)
4. **Aktivität** (unten)

## Widget-Definition (API-Form)
Jedes Widget liefert:
- `id`, `label`, `icon`, `callback`, `priority`
- optional `roles`, `contexts`, `cache_ttl`

## Performance-Strategie
- **Cache pro Widget** (Default 300s, Plugin‑Settings überschreibbar)
- Lazy‑Load lange Listen (AJAX nach dem Rendern)

## Standard‑Widgets (Startseite)
1. **Dashboard‑Überblick** (global)
2. **Quick Actions** (global)
3. **Plugin‑KPIs** (je Plugin)
4. **Hinweise** (global)
5. **Letzte Aktivität** (je Plugin)

## Erweiterbarkeit
Plugins können Widgets melden:
- KPI‑Widget (Zahlen)
- Activity‑Widget (Liste)
- Action‑Widget (Buttons)

## Offene Fragen
- Sollte ein **globales Activity‑Log** aggregiert werden?
- Soll es **Widget‑Konfiguration** pro User geben (an/aus)?

---

## Ist‑Zustand (Mechanismus)
**Registrierung**
- Plugins registrieren Widgets über `do_action('neo_dashboard_register_widget', $args)`.
- `WidgetManager::register()` speichert Widgets in der `Registry` und sortiert nach `priority`.

**Rendering**
- `ContentManager::render()` lädt Widgets nur auf der Startseite (wenn keine aktive Section).
- `templates/partials/widgets.php` rendert ein Grid (3 Spalten) und ruft `callback` pro Widget.
- Header‑Actions werden über `templates/components/widgets/header.php` unterstützt.

**Rollen**
- Zugriff wird über `Helper::user_has_access()` gefiltert (roles in Widget‑Args).

**Helper**
- `WidgetHelper` liefert UI‑Bausteine (Stat‑Item, Action‑Button, Empty‑State, List, Alert, Header).

---

## Analyse / Schwachstellen
- **Keine Caching‑Konvention**: Widgets machen ggf. teure Abfragen ohne TTL‑Vorgabe.
- **Kein Async‑Pattern**: Große Widgets blocken das Rendern.
- **Fehlerhandling uneinheitlich**: Callback‑Fehler sind nicht standardisiert.
- **Grid fest**: 3 Spalten, keine dynamische Breite je Widget.
- **Keine Konsistenz‑Checks**: Widgets können uneinheitliche Header/Actions liefern.

---

## Konzept (Verbesserungen)

### 1) Widget‑Vertrag erweitern
Neue optionale Felder:
- `cache_ttl` (Default 300s)
- `size` (`sm|md|lg` → steuert Spaltenbreite)
- `load` (`sync|async`)
- `skeleton` (HTML für Ladeskelett)

### 2) Einheitliches Rendering
- Standard‑Header via `WidgetHelper::renderHeader`.
- Standard‑Empty‑State via `WidgetHelper::renderEmptyState`.
- Fehler‑Fallback in `widgets.php` für nicht‑callable Callbacks.

### 3) Performance & Async
- Bei `load=async`: initial Skeleton, danach AJAX‑Load pro Widget.
- Cache über Transients, Key: `neo_dashboard_widget_{id}`.

### 4) Layout‑Flexibilität
- `size=sm` → `col-12 col-md-6 col-xl-4`
- `size=md` → `col-12 col-lg-6`
- `size=lg` → `col-12`

### 5) Governance
- Minimal‑Checkliste pro Widget:
  - i18n‑Strings
  - leere Zustände
  - Rollen/Capabilities
  - max. 1–2 Actions
- Soll ein **globales Activity‑Log** aggregiert werden?
- Soll es **Widget‑Konfiguration** pro User geben (an/aus)?
