# Übersetzungen für Neo Dashboard Core

Dieses Verzeichnis enthält die Übersetzungsdateien für `neo-dashboard-core`.

## Dateien

- `neo-dashboard-core.pot` - Template-Datei mit allen übersetzbaren Strings
- `neo-dashboard-core-de_DE.po` - Deutsche Übersetzung
- `neo-dashboard-core-en_US.po` - Englische Übersetzung
- `neo-dashboard-core-uk_UA.po` - Ukrainische Übersetzung
- `*.mo` - Kompilierte Binärdateien (werden automatisch generiert)

## Kompilierung

Um die `.po`-Dateien zu `.mo`-Dateien zu kompilieren:

```bash
php compile-translations.php
```

## Unterstützte Sprachen

- **de_DE** (Deutsch) - Standard-Sprache
- **en_US** (English)
- **uk_UA** (Українська)

## Neue Übersetzungen hinzufügen

1. Neue Strings in PHP-Dateien mit `__()`, `esc_html__()`, `esc_attr__()` oder `esc_js__()` markieren
2. `.pot`-Datei aktualisieren (manuell oder mit Tools wie `xgettext`)
3. Übersetzungen in den `.po`-Dateien hinzufügen
4. Mit `compile-translations.php` kompilieren

