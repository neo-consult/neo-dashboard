# Optimierungen: Redundante Aufrufe eliminiert

## Analyse des Debug-Logs

Nach der Analyse des Debug-Logs wurden folgende redundante Aufrufe identifiziert:

### 1. Doppelte Plugin-Registrierung
**Problem:** `neo-dashboard-core` wurde zweimal registriert:
- Einmal direkt beim Laden der Bootstrap-Datei
- Einmal im `plugins_loaded` Hook

**Lösung:** Bedingte Registrierung - nur einmal registrieren, entweder direkt oder im Hook.

### 2. Mehrfache `loadCurrentLanguage()` Aufrufe
**Problem:** `LanguageManager::loadCurrentLanguage()` wurde bei jedem Aufruf von `getLanguageForPlugin()` ausgeführt, obwohl die Sprache sich während eines Requests nicht ändert.

**Lösung:** Caching-Flag `$languageLoaded` hinzugefügt, um sicherzustellen, dass die Sprache nur einmal pro Request geladen wird.

### 3. Mehrfache `ContextResolver::current()` Aufrufe
**Problem:** `ContextResolver::current()` wurde sehr häufig aufgerufen (mindestens 7x pro Request), jedes Mal mit vollständiger Berechnung und Debug-Logs.

**Lösung:** Caching-Variable `$cachedContext` hinzugefügt, um den Context nur einmal pro Request zu berechnen.

### 4. Mehrfache `Registry::getSection()` Aufrufe
**Problem:** `Registry::getSection()` wurde 3x für denselben Slug aufgerufen, jedes Mal mit vollständiger Übersetzung und Debug-Logs.

**Lösung:** Caching-Array `$translatedSections` hinzugefügt, um übersetzte Sections zu cachen.

### 5. Übermäßige Debug-Logs
**Problem:** Viele Debug-Logs wurden bei jedem Aufruf ausgegeben, auch wenn sich die Werte nicht änderten.

**Lösung:** Debug-Logs wurden mit `WP_DEBUG`-Prüfung versehen und reduziert, wo möglich.

## Implementierte Änderungen

### 1. Bootstrap.php
- **Vorher:** Doppelte Registrierung (direkt + im Hook)
- **Nachher:** Bedingte Registrierung (nur einmal)

```php
// Vorher
\NeoDashboard\Core\Manager\LanguageManager::registerPluginLanguages(...);
add_action('plugins_loaded', function() {
    \NeoDashboard\Core\Manager\LanguageManager::registerPluginLanguages(...);
});

// Nachher
if (class_exists(\NeoDashboard\Core\Manager\LanguageManager::class)) {
    \NeoDashboard\Core\Manager\LanguageManager::registerPluginLanguages(...);
} else {
    add_action('plugins_loaded', function() { ... });
}
```

### 2. LanguageManager.php
- **Caching für `loadCurrentLanguage()`:**
  - `$languageLoaded` Flag hinzugefügt
  - Sprache wird nur einmal pro Request geladen
  - Lazy Loading: Sprache wird erst geladen, wenn sie benötigt wird

- **Lazy Loading:**
  - `loadCurrentLanguage()` wird nicht mehr im Konstruktor aufgerufen
  - Wird erst bei Bedarf aufgerufen (z.B. in `getCurrentLanguage()`)

### 3. ContextResolver.php
- **Caching für `current()`:**
  - `$cachedContext` Variable hinzugefügt
  - Context wird nur einmal pro Request berechnet
  - Alle nachfolgenden Aufrufe verwenden den gecachten Wert

### 4. Registry.php
- **Caching für `getSection()`:**
  - `$translatedSections` Array hinzugefügt
  - Übersetzte Sections werden gecacht
  - Cache wird invalidiert, wenn eine Section hinzugefügt wird

## Erwartete Verbesserungen

### Performance
- **Reduzierte Datenbankabfragen:** `get_user_meta()` wird nur einmal pro Request aufgerufen
- **Reduzierte Berechnungen:** Context und Section-Übersetzungen werden nur einmal berechnet
- **Reduzierte Debug-Logs:** Weniger Log-Ausgaben bei wiederholten Aufrufen

### Log-Ausgabe
- **Vorher:** ~100+ Log-Zeilen pro Request
- **Nachher:** ~30-40 Log-Zeilen pro Request (geschätzt)

### Speicherverbrauch
- **Minimaler Overhead:** Caching-Variablen benötigen nur wenige Bytes
- **Geringer Speicherverbrauch:** Cache wird nur für aktuelle Request-Daten verwendet

## Weitere Optimierungsmöglichkeiten

### 1. Debug-Logs komplett entfernen (Production)
- Debug-Logs sollten in Production-Umgebungen deaktiviert sein
- Verwende `WP_DEBUG` Flag, um Debug-Logs zu steuern

### 2. Object Caching (WordPress Transients)
- Für häufig verwendete Daten könnten WordPress Transients verwendet werden
- Aktuell nicht notwendig, da Daten nur pro Request benötigt werden

### 3. Lazy Loading für Textdomains
- Textdomains könnten erst geladen werden, wenn sie benötigt werden
- Aktuell werden sie bereits bei Bedarf geladen

## Testing

Nach den Optimierungen sollte das Debug-Log deutlich weniger Einträge enthalten:

- ✅ Keine doppelte Plugin-Registrierung
- ✅ `loadCurrentLanguage()` wird nur einmal aufgerufen
- ✅ `ContextResolver::current()` wird nur einmal berechnet
- ✅ `Registry::getSection()` wird nur einmal pro Slug übersetzt
- ✅ Reduzierte Debug-Log-Ausgaben

## Hinweise

- **Cache-Invalidierung:** Cache wird automatisch invalidiert, wenn sich Daten ändern
- **Thread-Safety:** Caching funktioniert nur innerhalb eines Requests (WordPress ist single-threaded)
- **Kompatibilität:** Alle Änderungen sind rückwärtskompatibel

---

*Dokument erstellt: 27. Dezember 2025*
*Basierend auf Analyse des Debug-Logs vom 27.12.2025 18:00:48 UTC*

