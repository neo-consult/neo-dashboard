# Anleitung: Übersetzungs-Dateien erstellen und verwenden

## Übersicht

Alle Plugins (`neo-calendar`, `neo-contacts`, `neo-surveys`) unterstützen jetzt Multi-Language. Die Übersetzungs-Dateien befinden sich in den `languages/` Verzeichnissen jedes Plugins.

## Dateitypen

- **`.pot`** - Template-Datei (Portable Object Template)
  - Basis-Datei mit allen zu übersetzenden Strings
  - Wird als Vorlage für neue Übersetzungen verwendet
  - Wird nicht direkt von WordPress geladen

- **`.po`** - Portable Object (Text-Format)
  - Bearbeitbare Übersetzungs-Datei
  - Enthält die Übersetzungen für eine bestimmte Sprache
  - Kann mit einem Text-Editor oder speziellen Tools bearbeitet werden

- **`.mo`** - Machine Object (Binär-Format)
  - Kompilierte Version der `.po`-Datei
  - Wird von WordPress tatsächlich geladen
  - Muss aus der `.po`-Datei generiert werden

## Erstellen von Übersetzungen

### Option 1: Poedit (Empfohlen)

1. **Poedit installieren**: https://poedit.net/
2. **Neue Übersetzung erstellen**:
   - Poedit öffnen
   - `Datei` → `Neue Übersetzung aus POT-Datei`
   - `.pot`-Datei auswählen (z.B. `neo-calendar.pot`)
   - Sprache auswählen (z.B. `en_US` für Englisch)
   - Übersetzungen eintragen
   - Speichern (erstellt automatisch `.po` und `.mo`)

3. **Bestehende Übersetzung bearbeiten**:
   - Poedit öffnen
   - `.po`-Datei öffnen (z.B. `neo-calendar-en_US.po`)
   - Übersetzungen bearbeiten
   - Speichern (aktualisiert automatisch `.mo`)

### Option 2: WordPress CLI (wp i18n)

```bash
# .pot-Datei aktualisieren
wp i18n make-pot wp-content/plugins/neo-calendar wp-content/plugins/neo-calendar/languages/neo-calendar.pot

# .po zu .mo kompilieren
wp i18n make-mo wp-content/plugins/neo-calendar/languages/
```

### Option 3: msgfmt (gettext)

```bash
# .po zu .mo kompilieren
msgfmt -o neo-calendar-en_US.mo neo-calendar-en_US.po
```

## Dateinamen-Konvention

- Template: `{textdomain}.pot`
- Übersetzung: `{textdomain}-{locale}.po` und `{textdomain}-{locale}.mo`

Beispiele:
- `neo-calendar.pot`
- `neo-calendar-de_DE.po` / `neo-calendar-de_DE.mo`
- `neo-calendar-en_US.po` / `neo-calendar-en_US.mo`

## Unterstützte Sprachen

Aktuell registriert:
- **Deutsch (de_DE)** - Standard-Sprache
- **Englisch (en_US)**
- **Ukrainisch (uk_UA)**

## Neue Sprache hinzufügen

1. **Plugin-Registrierung**:
   In `Bootstrap.php` des Plugins die neue Sprache registrieren:
   ```php
   do_action('neo_dashboard_register_languages', 'neo-calendar', [
       'de_DE' => 'Deutsch',
       'en_US' => 'English',
       'fr_FR' => 'Français', // Neu
   ]);
   ```

2. **Übersetzungs-Dateien erstellen**:
   - `.pot`-Datei als Vorlage verwenden
   - Neue `.po`-Datei erstellen (z.B. `neo-calendar-fr_FR.po`)
   - Übersetzungen eintragen
   - Zu `.mo` kompilieren

3. **Sprache in LanguageManager registrieren**:
   In `neo-dashboard/src/Manager/LanguageManager.php` die neue Sprache hinzufügen:
   ```php
   $this->availableLanguages = [
       // ...
       'fr_FR' => [
           'code' => 'fr_FR',
           'name' => 'French',
           'native_name' => 'Français',
           'flag' => '🇫🇷',
           'default' => false
       ],
   ];
   ```

## Strings in Templates übersetzbar machen

Aktuell sind noch nicht alle Strings mit Übersetzungs-Funktionen versehen. Um Strings übersetzbar zu machen:

### PHP-Templates

```php
// Statt:
echo "Arbeitszeit eintragen";

// Verwende:
echo esc_html__('Arbeitszeit eintragen', 'neo-calendar');

// Oder für direkte Ausgabe:
_e('Arbeitszeit eintragen', 'neo-calendar');
```

### JavaScript-Strings

JavaScript-Strings werden über `wp_localize_script` lokalisiert:

```php
wp_localize_script('neo-calendar-common', 'neoCalendarAjax', [
    'strings' => [
        'save_success' => __('Arbeitszeit gespeichert', 'neo-calendar'),
        'save_error' => __('Fehler beim Speichern', 'neo-calendar'),
    ],
]);
```

## Aktualisieren der .pot-Datei

Wenn neue Strings hinzugefügt werden:

1. **Mit Poedit**:
   - `.pot`-Datei öffnen
   - `Katalog` → `Aus Quellcode aktualisieren`
   - Plugin-Verzeichnis auswählen
   - Speichern

2. **Mit WordPress CLI**:
   ```bash
   wp i18n make-pot wp-content/plugins/neo-calendar wp-content/plugins/neo-calendar/languages/neo-calendar.pot
   ```

3. **Übersetzungen aktualisieren**:
   - `.po`-Dateien öffnen
   - `Katalog` → `Aus POT-Datei aktualisieren`
   - Neue Strings übersetzen
   - Speichern (aktualisiert `.mo`)

## Fallback-Mechanismus

Wenn eine Übersetzung für die gewählte Sprache nicht vorhanden ist:
1. Plugin prüft, ob die gewählte Sprache unterstützt wird
2. Falls nicht, wird die Standard-Sprache (de_DE) verwendet
3. Falls auch diese nicht vorhanden ist, wird der Original-String angezeigt

## Best Practices

1. **Konsistente Textdomain**: Immer die korrekte Textdomain verwenden
2. **Kontext hinzufügen**: Bei mehrdeutigen Strings Kontext-Informationen hinzufügen
3. **Plural-Forms**: Bei Plural-Strings die korrekte Plural-Form verwenden
4. **Escape-Funktionen**: Immer `esc_html__()`, `esc_attr__()`, etc. verwenden
5. **Regelmäßig aktualisieren**: `.pot`-Dateien regelmäßig aktualisieren, wenn neue Strings hinzugefügt werden

## Tools

- **Poedit**: https://poedit.net/ (GUI, Windows/Mac/Linux)
- **WPML String Translation**: Für WordPress-spezifische Übersetzungen
- **WordPress CLI**: `wp i18n` Befehle
- **gettext**: Kommandozeilen-Tools (`msgfmt`, `xgettext`)

