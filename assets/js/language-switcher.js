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
            
            // Prüfe, ob wir nach einer Sprachänderung neu geladen wurden
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('lang_changed') === '1') {
                // Sprache wurde bereits geändert, nur UI aktualisieren
                this.updateUI(this.currentLanguage);
                // Parameter aus URL entfernen (für saubere URLs)
                urlParams.delete('lang_changed');
                const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
                window.history.replaceState({}, '', newUrl);
                return;
            }
            
            // Event-Listener
            this.setupEventListeners();
            
            // UI mit aktueller Sprache aktualisieren (wichtig: nach loadSavedLanguage!)
            // Dies stellt sicher, dass Flag und Code korrekt angezeigt werden
            this.updateUI(this.currentLanguage);
        },
        
        updateUI: function(languageCode) {
            const lang = this.availableLanguages[languageCode];
            if (!lang) return;
            
            // UI aktualisieren
            const flagEl = document.getElementById('language-flag');
            const codeEl = document.getElementById('language-code');
            
            if (flagEl) flagEl.textContent = lang.flag;
            if (codeEl) codeEl.textContent = lang.code.substring(0, 2).toUpperCase();
            
            // HTML-Attribute setzen
            document.documentElement.setAttribute('data-neo-language', languageCode);
            document.documentElement.setAttribute('lang', languageCode.substring(0, 2));
            
            // Dropdown neu rendern
            this.renderDropdown();
        },
        
        loadSavedLanguage: function() {
            // Priorität: Backend (NeoDash) > localStorage
            if (typeof NeoDash !== 'undefined' && NeoDash.currentLanguage) {
                this.currentLanguage = NeoDash.currentLanguage;
                // Synchronisiere localStorage mit Backend
                localStorage.setItem(STORAGE_KEY, this.currentLanguage);
            } else {
                const saved = localStorage.getItem(STORAGE_KEY);
                if (saved && this.availableLanguages[saved]) {
                    this.currentLanguage = saved;
                } else if (this.availableLanguages[DEFAULT_LANGUAGE]) {
                    // Fallback auf Standard-Sprache
                    this.currentLanguage = DEFAULT_LANGUAGE;
                }
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
                
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.setLanguage(lang.code, true);
                });
                
                dropdown.appendChild(item);
            });
        },
        
        setLanguage: function(languageCode, save = true, skipEvent = false) {
            if (!this.availableLanguages[languageCode]) {
                languageCode = DEFAULT_LANGUAGE;
            }
            
            // Prüfe, ob sich die Sprache wirklich geändert hat
            if (this.currentLanguage === languageCode) {
                // Sprache ist bereits gesetzt, kein Update nötig
                // Nur bei save=true und wenn sich die Sprache geändert hat, weiter machen
                if (!save) {
                    return;
                }
            }
            
            const previousLanguage = this.currentLanguage;
            this.currentLanguage = languageCode;
            const lang = this.availableLanguages[languageCode];
            
            if (!lang) {
                console.warn('Language not found:', languageCode);
                // Zurück zur vorherigen Sprache, wenn ungültig
                this.currentLanguage = previousLanguage;
                return;
            }
            
            // UI aktualisieren
            const flagEl = document.getElementById('language-flag');
            const codeEl = document.getElementById('language-code');
            
            if (flagEl) flagEl.textContent = lang.flag;
            if (codeEl) codeEl.textContent = lang.code.substring(0, 2).toUpperCase();
            
            // HTML-Attribute setzen
            document.documentElement.setAttribute('data-neo-language', languageCode);
            document.documentElement.setAttribute('lang', languageCode.substring(0, 2));
            
            // Speichern
            if (save) {
                localStorage.setItem(STORAGE_KEY, languageCode);
                
                // AJAX-Request an Backend (für Server-seitige Übersetzungen)
                this.notifyBackend(languageCode);
            }
            
            // Event für Plugins nur auslösen, wenn nicht explizit übersprungen
            // und nur wenn sich die Sprache wirklich geändert hat
            if (!skipEvent && previousLanguage !== languageCode) {
                window.dispatchEvent(new CustomEvent('neo-dashboard-language-changed', {
                    detail: { language: languageCode }
                }));
            }
            
            // Dropdown neu rendern
            this.renderDropdown();
        },
        
        notifyBackend: function(languageCode) {
            if (typeof jQuery === 'undefined') {
                return;
            }
            
            // Prüfe, ob wir bereits einen Reload-Parameter haben (verhindert Endlosschleife)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('lang_changed') === '1') {
                // Seite wurde bereits neu geladen, kein weiterer Reload nötig
                return;
            }
            
            // Loading-Feedback anzeigen
            this.showLoading();
            
            // AJAX-URL ermitteln
            let ajaxUrl = '/wp-admin/admin-ajax.php';
            if (typeof NeoDash !== 'undefined' && NeoDash.ajaxurl) {
                ajaxUrl = NeoDash.ajaxurl;
            } else if (typeof ajaxurl !== 'undefined') {
                ajaxUrl = ajaxurl;
            }
            
            const self = this;
            jQuery.ajax({
                url: ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'neo_dashboard_set_language',
                    language: languageCode,
                    nonce: (typeof NeoDash !== 'undefined' && NeoDash.nonce) ? NeoDash.nonce : ''
                },
                success: function(response) {
                    if (response && response.success) {
                        // Seite neu laden für vollständige Übersetzung
                        // WordPress lädt Textdomains beim init Hook, daher ist ein Reload nötig
                        // Parameter hinzufügen, um Endlosschleife zu verhindern
                        const currentUrl = new URL(window.location.href);
                        currentUrl.searchParams.set('lang_changed', '1');
                        window.location.href = currentUrl.toString();
                    } else {
                        // Fehler: Loading-Feedback entfernen
                        self.hideLoading();
                    }
                },
                error: function(xhr, status, error) {
                    // Fehler: Loading-Feedback entfernen
                    self.hideLoading();
                    // Fehler ist nicht kritisch, Sprache wurde bereits im localStorage gespeichert
                    if (typeof console !== 'undefined' && console.warn) {
                        console.warn('Language change notification failed:', error);
                    }
                }
            });
        },
        
        showLoading: function() {
            const button = document.getElementById('language-toggle-navbar');
            if (!button) return;
            
            // Button deaktivieren
            button.disabled = true;
            
            // Spinner hinzufügen
            const flagEl = document.getElementById('language-flag');
            const codeEl = document.getElementById('language-code');
            
            if (flagEl) {
                flagEl.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            }
            if (codeEl) {
                codeEl.textContent = '...';
            }
        },
        
        hideLoading: function() {
            const button = document.getElementById('language-toggle-navbar');
            if (!button) return;
            
            // Button aktivieren
            button.disabled = false;
            
            // UI mit aktueller Sprache wiederherstellen
            this.updateUI(this.currentLanguage);
        },
        
        setupEventListeners: function() {
            // Event von anderen Quellen (z.B. Backend)
            // WICHTIG: skipEvent = true verhindert Endlosschleife
            window.addEventListener('neo-dashboard-language-changed', (e) => {
                if (e.detail && e.detail.language) {
                    this.setLanguage(e.detail.language, false, true);
                }
            });
        }
    };
    
    // Initialisierung
    document.addEventListener('DOMContentLoaded', function() {
        languageManager.init();
    });
    
    // Fallback für bereits geladene Seite
    if (document.readyState === 'loading') {
        // Wird durch DOMContentLoaded behandelt
    } else {
        setTimeout(function() {
            languageManager.init();
        }, 100);
    }
    
    // Export für Plugins
    window.NeoDashLanguage = languageManager;
})();

