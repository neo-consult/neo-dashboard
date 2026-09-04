# Konzept: Einheitliche Tabellen-Darstellung für Neo-Plugins

**Erstellt am:** 2025-01-XX  
**Zweck:** Vereinheitlichung der Tabellen-Darstellung in neo-surveys und neo-contacts  
**Prinzip:** Bootstrap-Klassen bevorzugen, minimales Custom CSS

---

## Inhaltsverzeichnis

1. [Aktuelle Situation](#aktuelle-situation)
2. [Ziel-Konzept](#ziel-konzept)
3. [HTML-Struktur](#html-struktur)
4. [JavaScript-Implementierung](#javascript-implementierung)
5. [Loading-Feedback](#loading-feedback)
6. [Bootstrap-Klassen](#bootstrap-klassen)
7. [Migration-Plan](#migrations-plan)

---

## Aktuelle Situation

### neo-surveys
- ✅ **Nachladen-Mechanismus:** Tabellen werden komplett per JavaScript nachgeladen
- ✅ **HTML-Struktur:** `list-group` und `list-group-item` (Bootstrap-Klassen)
- ✅ **Loading:** Spinner im Container vor dem Nachladen
- ✅ **Struktur:** Card > Card-Body > Container (wird per JS gefüllt)
- ✅ **Konsistenz:** Einheitliche Darstellung, keine Mischung aus PHP und JS

### neo-contacts
- ⚠️ **Nachladen-Mechanismus:** Initial PHP-gerendert (`list-group`), nach Sortierung JavaScript (`<table>`)
- ⚠️ **HTML-Struktur:** Mischung aus `list-group` (initial) und `<table>` (nach Sortierung)
- ⚠️ **Loading:** Spinner im tbody (nur wenn Tabelle existiert)
- ⚠️ **Struktur:** Card > Card-Body > list-group (initial) oder table (nach Sortierung)
- ⚠️ **Konsistenz:** Inkonsistente Darstellung je nach Zustand

---

## Ziel-Konzept

### Grundprinzipien

1. **Komplett JavaScript-basiert:** Alle Tabellen werden per JavaScript nachgeladen (wie in neo-surveys)
2. **Bootstrap list-group:** Verwendung von `list-group` und `list-group-item` für konsistente Darstellung
3. **Minimales Custom CSS:** Nur Bootstrap-Klassen verwenden, Custom CSS auf ein Minimum reduzieren
4. **Einheitliches Loading:** Konsistentes Loading-Feedback für alle Zustände
5. **Responsive Design:** Bootstrap-Klassen für responsive Verhalten nutzen

---

## HTML-Struktur

### PHP-Template (Minimal)

```php
<div class="container-fluid">
    <!-- Header mit Buttons -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="btn-group">
            <!-- Export-Button -->
        </div>
        <div>
            <!-- Add-Button -->
        </div>
    </div>

    <!-- Card-Container -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Titel</h5>
                    <!-- Filter/Sort-Optionen -->
                </div>
                <div class="card-body">
                    <!-- Container für JavaScript-generierten Inhalt -->
                    <div id="items-list">
                        <!-- Wird per JavaScript gefüllt -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
```

### JavaScript-generierte Struktur

#### 1. Loading-State

```html
<div id="items-list">
    <div class="text-center py-4">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Lädt...</span>
        </div>
        <p class="mt-2 text-muted small mb-0">Lade Daten...</p>
    </div>
</div>
```

#### 2. Empty-State

```html
<div id="items-list">
    <div class="text-center py-5">
        <i class="bi-[icon] fs-1 text-muted d-block mb-2"></i>
        <p class="text-muted mb-0">Noch keine Einträge vorhanden.</p>
    </div>
</div>
```

#### 3. List-Group-Struktur (Empfohlen)

```html
<div id="items-list">
    <div class="list-group list-group-flush">
        <div class="list-group-item px-3 py-4 border-bottom" data-item-id="1">
            <div class="d-flex justify-content-between align-items-center">
                <!-- Hauptinhalt -->
                <div class="flex-grow-1 me-3">
                    <h6 class="mb-2">
                        <i class="bi-[icon] text-primary"></i>
                        Titel
                    </h6>
                    <div class="small text-muted">
                        <div class="mb-1">
                            <i class="bi-[icon]"></i>
                            Zusatzinformation
                        </div>
                        <div class="mb-1">
                            <i class="bi-calendar3"></i>
                            Datum
                        </div>
                    </div>
                </div>
                <!-- Action-Buttons -->
                <div class="btn-group btn-group-sm flex-shrink-0" role="group">
                    <button type="button" class="btn btn-sm btn-outline-info" 
                            data-bs-toggle="tooltip" 
                            data-bs-placement="top" 
                            data-bs-title="Anzeigen">
                        <i class="bi-eye"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" 
                            data-bs-toggle="tooltip" 
                            data-bs-placement="top" 
                            data-bs-title="Bearbeiten">
                        <i class="bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" 
                            data-bs-toggle="tooltip" 
                            data-bs-placement="top" 
                            data-bs-title="Löschen">
                        <i class="bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <!-- Weitere Items... -->
    </div>
</div>
```

#### 4. Alternative: Table-Struktur (Für tabellarische Daten)

```html
<div id="items-list">
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead>
                <tr>
                    <th>Spalte 1</th>
                    <th>Spalte 2</th>
                    <th>Spalte 3</th>
                    <th class="text-end">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Wert 1</td>
                    <td>Wert 2</td>
                    <td>Wert 3</td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm" role="group">
                            <!-- Buttons -->
                        </div>
                    </td>
                </tr>
                <!-- Weitere Zeilen... -->
            </tbody>
        </table>
    </div>
</div>
```

**Empfehlung:** `list-group` für komplexe Einträge mit mehreren Informationen, `table` für einfache tabellarische Daten.

---

## JavaScript-Implementierung

### Grundstruktur

```javascript
(function($) {
    'use strict';
    
    window.pluginNamespace = {
        init: function() {
            // Initialisierung
            this.initDataTable();
        },
        
        initDataTable: function() {
            const $container = $('#items-list');
            
            // Initial: Loading-State anzeigen
            $container.html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Lädt...</span>
                    </div>
                    <p class="mt-2 text-muted small mb-0">Lade Daten...</p>
                </div>
            `);
            
            // Filter initialisieren (falls vorhanden)
            this.loadFilterOptions();
            
            // Daten laden
            this.loadItemsList();
            
            // Event-Listener für Filter
            const self = this;
            $('#filter-select').on('change', function() {
                self.loadItemsList();
            });
        },
        
        loadItemsList: function() {
            const $container = $('#items-list');
            
            // Loading-State anzeigen
            $container.html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Lädt...</span>
                    </div>
                    <p class="mt-2 text-muted small mb-0">Lade Daten...</p>
                    </div>
            `);
            
            // AJAX-Request
            ajaxRequest({
                action: 'get_items',
                data: {
                    filter: $('#filter-select').val() || '',
                    // ... weitere Filter
                },
                onSuccess: (data) => {
                    this.renderItemsList(data.items);
                },
                onError: () => {
                    $container.html(`
                        <div class="alert alert-danger">
                            <i class="bi-exclamation-triangle"></i>
                            Fehler beim Laden der Daten. Bitte versuchen Sie es erneut.
                        </div>
                    `);
                }
            });
        },
        
        renderItemsList: function(items) {
            const $container = $('#items-list');
            
            if (!items || items.length === 0) {
                $container.html(`
                    <div class="text-center py-5">
                        <i class="bi-[icon] fs-1 text-muted d-block mb-2"></i>
                        <p class="text-muted mb-0">Noch keine Einträge vorhanden.</p>
                    </div>
                `);
                return;
            }
            
            let html = '<div class="list-group list-group-flush">';
            
            items.forEach((item) => {
                html += this.renderItem(item);
            });
            
            html += '</div>';
            $container.html(html);
            
            // Tooltips für dynamisch hinzugefügten Content initialisieren
            this.initTooltips($container);
        },
        
        renderItem: function(item) {
            // Item-spezifisches Rendering
            return `
                <div class="list-group-item px-3 py-4 border-bottom" data-item-id="${item.id}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1 me-3">
                            <h6 class="mb-2">
                                <i class="bi-[icon] text-primary"></i>
                                ${item.title}
                            </h6>
                            <div class="small text-muted">
                                <!-- Zusatzinformationen -->
                            </div>
                        </div>
                        <div class="btn-group btn-group-sm flex-shrink-0" role="group">
                            <!-- Action-Buttons -->
                        </div>
                    </div>
                </div>
            `;
        },
        
        initTooltips: function($container) {
            // Event auslösen für Tooltip-Initialisierung
            setTimeout(() => {
                const event = new CustomEvent('neoDashboardContentAdded', {
                    detail: {
                        container: $container[0] || $container
                    }
                });
                document.dispatchEvent(event);
            }, 100);
        },
        
        deleteItem: function(itemId, itemName) {
            confirm('Sind Sie sicher?', {
                type: 'danger',
                title: 'Bestätigung des Löschens',
                confirmText: 'Löschen',
                cancelText: 'Abbrechen'
            }).then((confirmed) => {
                if (!confirmed) return;
                
                // SOFORT: Loading-Feedback
                const $container = $('#items-list');
                $container.html(`
                    <div class="text-center py-4">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                            <span class="visually-hidden">Lädt...</span>
                        </div>
                        <span class="text-muted">Lösche Eintrag...</span>
                    </div>
                `);
                
                // AJAX-Request
                ajaxRequest({
                    action: 'delete_item',
                    data: { item_id: itemId },
                    onSuccess: () => {
                        showSuccess('Eintrag erfolgreich gelöscht');
                        this.loadItemsList(); // Liste neu laden
                    },
                    onError: () => {
                        showError('Fehler beim Löschen');
                        this.loadItemsList(); // Liste neu laden (auch bei Fehler)
                    }
                });
            });
        }
    };
    
    // Initialisierung bei DOM-Ready
    $(document).ready(function() {
        if ($('#items-list').length) {
            window.pluginNamespace.init();
        }
    });
    
})(jQuery);
```

---

## Loading-Feedback

### Konsistente Loading-States

#### 1. Initial Loading (beim ersten Laden)

```html
<div class="text-center py-4">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Lädt...</span>
    </div>
    <p class="mt-2 text-muted small mb-0">Lade Daten...</p>
</div>
```

#### 2. Reload Loading (beim Neuladen)

```html
<div class="text-center py-4">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Lädt...</span>
    </div>
    <p class="mt-2 text-muted small mb-0">Lade Daten...</p>
</div>
```

#### 3. Delete Loading (beim Löschen)

```html
<div class="text-center py-4">
    <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
        <span class="visually-hidden">Lädt...</span>
    </div>
    <span class="text-muted">Lösche Eintrag...</span>
</div>
```

**Bootstrap-Klassen:**
- `spinner-border` / `spinner-border-sm` für Spinner
- `text-primary` für Farbe
- `text-muted` für Text
- `text-center` für Zentrierung
- `py-4` / `py-3` für Padding

---

## Bootstrap-Klassen

### Empfohlene Klassen-Struktur

#### Container und Layout
```html
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Titel</h5>
                </div>
                <div class="card-body">
                    <!-- Inhalt -->
                </div>
            </div>
        </div>
    </div>
</div>
```

#### List-Group
```html
<div class="list-group list-group-flush">
    <div class="list-group-item px-3 py-4 border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <div class="flex-grow-1 me-3">
                <h6 class="mb-2">Titel</h6>
                <div class="small text-muted">Info</div>
            </div>
            <div class="btn-group btn-group-sm flex-shrink-0" role="group">
                <!-- Buttons -->
            </div>
        </div>
    </div>
</div>
```

#### Buttons
```html
<div class="btn-group btn-group-sm" role="group">
    <button type="button" class="btn btn-sm btn-outline-info">
        <i class="bi-eye"></i>
    </button>
    <button type="button" class="btn btn-sm btn-outline-warning">
        <i class="bi-pencil"></i>
    </button>
    <button type="button" class="btn btn-sm btn-outline-danger">
        <i class="bi-trash"></i>
    </button>
</div>
```

#### Icons und Text
```html
<h6 class="mb-2">
    <i class="bi-[icon] text-primary"></i>
    Titel
</h6>
<div class="small text-muted">
    <div class="mb-1">
        <i class="bi-[icon]"></i>
        Information
    </div>
</div>
```

### Button-Farben (Bootstrap-Standard)

- `btn-outline-info` - Anzeigen (blau)
- `btn-outline-secondary` - Zusätzliche Aktionen (grau)
- `btn-outline-warning` - Bearbeiten (gelb)
- `btn-outline-danger` - Löschen (rot)
- `btn-outline-success` - Aktivieren/Erstellen (grün)
- `btn-outline-primary` - Primäre Aktion (blau)

---

## Custom CSS (Minimal)

### Nur notwendige Anpassungen

```css
/* Position für Loading-Container */
#items-list {
    position: relative;
    min-height: 200px;
}

/* Dark Mode: Automatisch durch Bootstrap-Variablen */
/* Keine zusätzlichen CSS-Regeln nötig, wenn Bootstrap-Klassen korrekt verwendet werden */
```

**Prinzip:** So wenig Custom CSS wie möglich. Bootstrap-Klassen für alles nutzen.

---

## Migration-Plan

### Phase 1: neo-contacts auf list-group umstellen

1. **PHP-Templates anpassen:**
   - Entfernen der initialen `list-group` PHP-Rendering
   - Nur leeren Container `<div id="items-list"></div>` belassen

2. **JavaScript anpassen:**
   - `neoContactsLoadPeopleTable()` und `neoContactsLoadOrgsTable()` so ändern, dass sie immer `list-group` rendern
   - `neoContactsRenderPeopleTable()` und `neoContactsRenderOrgsTable()` auf `list-group` umstellen
   - Entfernen der Tabellen-Logik

3. **Loading-Feedback vereinheitlichen:**
   - Konsistentes Loading-Feedback für alle Zustände
   - Gleiche Spinner-Struktur wie in neo-surveys

### Phase 2: Konsistenz prüfen

1. **Vergleich beider Plugins:**
   - Gleiche HTML-Struktur
   - Gleiche Bootstrap-Klassen
   - Gleiches Loading-Feedback

2. **Custom CSS reduzieren:**
   - Überprüfen, ob alle Custom CSS-Regeln durch Bootstrap-Klassen ersetzt werden können
   - Minimale Anpassungen nur für Dark Mode (falls nötig)

### Phase 3: Dokumentation

1. **Best Practices dokumentieren:**
   - In `LEARNINGS.md` ergänzen
   - Code-Beispiele für zukünftige Entwicklungen

---

## Vorteile des Konzepts

### 1. Konsistenz
- ✅ Einheitliche Darstellung in beiden Plugins
- ✅ Gleiche User Experience
- ✅ Einfacher zu warten

### 2. Bootstrap-First
- ✅ Maximale Nutzung von Bootstrap-Klassen
- ✅ Minimales Custom CSS
- ✅ Bessere Wartbarkeit

### 3. Performance
- ✅ Komplett JavaScript-basiert = keine doppelte Rendering-Logik
- ✅ Konsistentes Loading-Feedback
- ✅ Keine Mischung aus PHP und JS

### 4. Wartbarkeit
- ✅ Einheitliche Code-Struktur
- ✅ Einfacher zu erweitern
- ✅ Weniger Code-Duplikation

---

## Code-Beispiele

### Beispiel 1: Personen-Liste (neo-contacts)

```javascript
renderPeopleList: function(people) {
    const $container = $('#people-list');
    
    if (!people || people.length === 0) {
        $container.html(`
            <div class="text-center py-5">
                <i class="bi-person fs-1 text-muted d-block mb-2"></i>
                <p class="text-muted mb-0">Noch keine Personen vorhanden.</p>
            </div>
        `);
        return;
    }
    
    let html = '<div class="list-group list-group-flush">';
    
    people.forEach((person) => {
        const fullName = (person.first_name || '') + ' ' + (person.last_name || '');
        const displayName = fullName.trim() || 'Ohne Namen';
        
        // Organisationen
        let orgsHtml = '';
        if (person.orgs && person.orgs.length > 0) {
            const orgNames = person.orgs.map(org => {
                let orgName = org.name;
                if (org.role_title) {
                    orgName += ` <small class="text-muted">(${org.role_title})</small>`;
                }
                return orgName;
            });
            orgsHtml = orgNames.join(', ');
        } else {
            orgsHtml = '<span class="text-danger">— Keine Zuordnung</span>';
        }
        
        // Kontakt-Informationen
        let contactHtml = '<div class="small">';
        if (person.email) {
            contactHtml += `<div><a href="mailto:${person.email}" class="text-decoration-none"><i class="bi-envelope"></i> ${person.email}</a></div>`;
        }
        if (person.phone) {
            contactHtml += `<div class="text-muted"><i class="bi-telephone"></i> ${person.phone}</div>`;
        }
        if (!person.email && !person.phone) {
            contactHtml += '<span class="text-muted">–</span>';
        }
        contactHtml += '</div>';
        
        const createdDate = new Date(person.created_at).toLocaleDateString('de-DE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        html += `
            <div class="list-group-item px-3 py-4 border-bottom" data-person-id="${person.id}">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="flex-grow-1 me-3">
                        <h6 class="mb-2">
                            <i class="bi-person text-primary"></i>
                            ${displayName}
                        </h6>
                        <div class="small text-muted">
                            <div class="mb-1">
                                <i class="bi-building"></i>
                                ${orgsHtml}
                            </div>
                            ${contactHtml}
                            <div class="mb-1">
                                <i class="bi-calendar3"></i>
                                ${createdDate}
                            </div>
                        </div>
                    </div>
                    <div class="btn-group btn-group-sm flex-shrink-0" role="group">
                        ${this.renderActionButtons(person)}
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    $container.html(html);
    
    this.initTooltips($container);
}
```

### Beispiel 2: Loading-Feedback beim Löschen

```javascript
deletePerson: function(personId, personName) {
    confirm('Sind Sie sicher?', {
        type: 'danger',
        title: 'Bestätigung des Löschens',
        confirmText: 'Löschen',
        cancelText: 'Abbrechen'
    }).then((confirmed) => {
        if (!confirmed) return;
        
        // SOFORT: Loading-Feedback
        const $container = $('#people-list');
        $container.html(`
            <div class="text-center py-4">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                    <span class="visually-hidden">Lädt...</span>
                </div>
                <span class="text-muted">Lösche Person...</span>
            </div>
        `);
        
        // AJAX-Request
        ajaxRequest({
            action: 'delete_person',
            data: { person_id: personId },
            onSuccess: () => {
                showSuccess('Person erfolgreich gelöscht');
                this.loadPeopleList(); // Liste neu laden
            },
            onError: () => {
                showError('Fehler beim Löschen');
                this.loadPeopleList(); // Liste neu laden (auch bei Fehler)
            }
        });
    });
}
```

---

## Checkliste für Implementierung

### PHP-Templates
- [ ] Initiale PHP-Rendering entfernen
- [ ] Nur leeren Container `<div id="items-list"></div>` belassen
- [ ] Card-Struktur mit Bootstrap-Klassen verwenden
- [ ] Filter/Sort-Optionen im Card-Header

### JavaScript
- [ ] `initDataTable()` Funktion implementieren
- [ ] `loadItemsList()` Funktion implementieren
- [ ] `renderItemsList()` Funktion implementieren
- [ ] `renderItem()` Funktion implementieren
- [ ] Loading-Feedback konsistent implementieren
- [ ] Delete-Feedback konsistent implementieren
- [ ] Tooltip-Initialisierung nach Rendering

### CSS
- [ ] Custom CSS auf Minimum reduzieren
- [ ] Nur Bootstrap-Klassen verwenden
- [ ] Dark Mode durch Bootstrap-Variablen (falls möglich)

### Testing
- [ ] Initiales Laden testen
- [ ] Filter/Sort testen
- [ ] Delete-Funktion testen
- [ ] Loading-Feedback testen
- [ ] Empty-State testen
- [ ] Responsive Design testen

---

## Zusammenfassung

### Empfohlene Struktur

1. **PHP:** Minimal - nur Container-Struktur
2. **JavaScript:** Komplett - alle Daten per AJAX laden
3. **HTML:** Bootstrap `list-group` für komplexe Einträge
4. **CSS:** Minimal - nur Bootstrap-Klassen
5. **Loading:** Konsistent - gleiche Spinner-Struktur

### Vorteile

- ✅ Konsistente Darstellung
- ✅ Bessere Performance (keine doppelte Rendering-Logik)
- ✅ Einfacher zu warten
- ✅ Bootstrap-First Ansatz
- ✅ Einheitliche User Experience

---

**Status:** Konzept  
**Nächste Schritte:** Implementierung in neo-contacts, dann Konsistenz-Prüfung

