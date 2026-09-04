/*
 * Neo Dashboard – Core JS Helpers
 * --------------------------------
 * Abhängigkeit: Bootstrap 5.3 (Bundle)
 *
 * Features
 *   • Auto‑Close der Offcanvas‑Sidebar auf mobilen Geräten nach Klick auf echten Nav‑Link
 *   • Automatisches Aktivieren von Tooltips & Popovers (data‑bs‑toggle‑Attribut)
 *   • Globales Custom Event "neoDashboardReady" nach DOM‑Load & Bootstrap‑Init
 */

(() => {
  "use strict";
  
  // Singleton-Pattern für Tooltip-Instanzen
  // Stellt sicher, dass jedes Element nur eine Tooltip-Instanz hat
  const TooltipSingleton = {
    instances: new WeakMap(),
    
    /**
     * Erstellt oder gibt eine bestehende Tooltip-Instanz zurück
     * @param {HTMLElement} element - Das DOM-Element
     * @param {Object} options - Tooltip-Optionen (optional)
     * @returns {bootstrap.Tooltip|null} - Die Tooltip-Instanz oder null bei Fehler
     */
    getInstance: function(element, options = null) {
      if (!element || typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
        return null;
      }
      
      // Prüfe, ob bereits eine Instanz in unserem WeakMap existiert
      if (this.instances.has(element)) {
        return this.instances.get(element);
      }
      
      // Prüfe, ob Bootstrap bereits eine Instanz hat
      let existingInstance = bootstrap.Tooltip.getInstance(element);
      
      // Prüfe auch _bsTooltip direkt
      if (!existingInstance && element._bsTooltip) {
        existingInstance = element._bsTooltip;
      }
      
      // Wenn bereits eine Instanz existiert, verwende sie
      if (existingInstance) {
        this.instances.set(element, existingInstance);
        // Stelle sicher, dass die Instanz auch in Bootstrap's Data-System registriert ist
        this._registerInBootstrapData(element, existingInstance);
        return existingInstance;
      }
      
      // Erstelle eine neue Instanz
      try {
        let tooltipInstance;
        
        if (options) {
          // Verwende benutzerdefinierte Optionen
          tooltipInstance = new bootstrap.Tooltip(element, options);
        } else {
          // Verwende Standard-Optionen basierend auf Attributen
          const title = element.getAttribute('data-tooltip-title') || element.getAttribute('data-bs-title') || element.getAttribute('title');
          const placement = element.getAttribute('data-tooltip-placement') || element.getAttribute('data-bs-placement') || 'top';
          
          if (title) {
            tooltipInstance = new bootstrap.Tooltip(element, {
              title: title,
              placement: placement,
              trigger: 'hover focus'
            });
          } else {
            // Verwende Standard-Tooltip-Initialisierung
            tooltipInstance = new bootstrap.Tooltip(element);
          }
        }
        
        // Speichere die Instanz in unserem WeakMap
        this.instances.set(element, tooltipInstance);
        
        // Stelle sicher, dass die Instanz auch in Bootstrap's Data-System registriert ist
        this._registerInBootstrapData(element, tooltipInstance);
        
        return tooltipInstance;
      } catch (error) {
        // Ignoriere Fehler, wenn Tooltip bereits existiert
        if (error.message && (error.message.includes('already initialized') || error.message.includes('more than one instance'))) {
          // Versuche, die bestehende Instanz zu erhalten
          const existing = bootstrap.Tooltip.getInstance(element) || element._bsTooltip;
          if (existing) {
            this.instances.set(element, existing);
            return existing;
          }
        }
        return null;
      }
    },
    
    /**
     * Entfernt eine Tooltip-Instanz
     * @param {HTMLElement} element - Das DOM-Element
     */
    dispose: function(element) {
      if (!element) return;
      
      const instance = this.instances.get(element) || bootstrap.Tooltip.getInstance(element);
      if (instance) {
        try {
          instance.dispose();
        } catch (e) {
          // Ignoriere Fehler
        }
      }
      
      this.instances.delete(element);
    },
    
    /**
     * Initialisiert Tooltips für mehrere Elemente
     * @param {NodeList|Array} elements - Die DOM-Elemente
     * @param {Object} options - Tooltip-Optionen (optional)
     */
    initAll: function(elements, options = null) {
      if (!elements) return;
      
      Array.from(elements).forEach(element => {
        this.getInstance(element, options);
      });
    },
    
    /**
     * Registriert eine Tooltip-Instanz in Bootstrap's Data-System
     * Dies verhindert, dass Bootstrap versucht, ein neues Tooltip zu erstellen
     * @private
     * @param {HTMLElement} element - Das DOM-Element
     * @param {bootstrap.Tooltip} instance - Die Tooltip-Instanz
     */
    _registerInBootstrapData: function(element, instance) {
      if (!element || !instance) return;
      
      try {
        // Setze die Instanz direkt in Bootstrap's internem System
        element._bsTooltip = instance;
        
        // Versuche, die Instanz auch über Bootstrap's Data API zu registrieren
        if (bootstrap.Data && bootstrap.Data.getInstance) {
          const existingDataInstance = bootstrap.Data.getInstance(element, 'tooltip');
          if (!existingDataInstance) {
            // Setze die Instanz manuell in Bootstrap's Data-System
            // Verwende einen try-catch, um Warnungen zu unterdrücken
            try {
              if (bootstrap.Data.set) {
                // Setze die Instanz stillschweigend
                bootstrap.Data.set(element, { name: 'tooltip', instance: instance });
              }
            } catch (e) {
              // Ignoriere Fehler - die Instanz ist bereits in _bsTooltip gesetzt
            }
          }
        }
      } catch (e) {
        // Ignoriere Fehler - die Instanz ist bereits in _bsTooltip gesetzt
      }
    }
  };
  
  // Mache Singleton global verfügbar, damit es auch von anderen Plugins verwendet werden kann
  window.NeoDashboardTooltipSingleton = TooltipSingleton;
  
  document.addEventListener("DOMContentLoaded", () => {
    // WICHTIG: Tooltips MÜSSEN VOR Dropdowns initialisiert werden, um Konflikte zu vermeiden
    // Bootstrap versucht beim Initialisieren eines Dropdowns, auch Tooltips zu initialisieren
    
    // Schritt 1: Initialisiere Tooltips für Elemente mit data-bs-toggle="tooltip"
    // (außerhalb von Cards, Cards werden später initialisiert)
    // Verwende Singleton-Pattern, um mehrfache Initialisierungen zu vermeiden
    TooltipSingleton.initAll(
      document.querySelectorAll('[data-bs-toggle="tooltip"]:not(.card [data-bs-toggle="tooltip"])')
    );
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
      // Prüfe ob bereits eine Popover-Instanz existiert
      const existingPopover = bootstrap.Popover.getInstance(el);
      if (!existingPopover) {
        try {
          new bootstrap.Popover(el);
        } catch (error) {
          // Ignoriere Fehler, wenn Popover bereits existiert (z.B. durch Race Condition)
          console.warn('[dashboard.js] Popover konnte nicht initialisiert werden:', error);
        }
      }
    });
    
    // Schritt 2: Initialisiere Tooltips für Elemente mit data-tooltip-title
    // WICHTIG: Gemäß Bootstrap-Empfehlung sollten Tooltips NICHT auf Elementen mit Dropdowns sein
    // Elemente mit Dropdowns werden in Schritt 3 behandelt (Tooltip wird auf untergeordnetes Element verschoben)
    // Hier initialisieren wir nur Tooltips für Elemente OHNE Dropdown
    document.querySelectorAll('[data-tooltip-title]:not([data-bs-toggle="dropdown"]):not([data-bs-toggle*="dropdown"])').forEach(element => {
      const tooltip = TooltipSingleton.getInstance(element);
      
      // Tooltip schließen, wenn andere Komponenten geöffnet werden (z.B. Modal, Offcanvas)
      if (tooltip) {
        // Optional: Tooltip bei anderen Events schließen
        element.addEventListener('show.bs.modal', () => {
          tooltip.hide();
        }, { once: false });
      }
    });

    // Sidebar Collapse: Dynamische Pfeil-Änderung beim Auf- und Zuklappen
    // Funktioniert für Desktop-Sidebar und Offcanvas-Sidebar
    const initSidebarChevrons = () => {
      // Finde alle Collapse-Elemente in der Sidebar (Desktop und Mobile)
      const sidebarCollapses = document.querySelectorAll('.desktop-sidebar .collapse, #sidebarOffcanvas .collapse');
      
      sidebarCollapses.forEach(collapseElement => {
        // Finde den zugehörigen Toggle-Link
        const collapseId = collapseElement.id;
        if (!collapseId) return;
        
        const toggleLink = document.querySelector(`[href="#${collapseId}"]`);
        if (!toggleLink) return;
        
        // Finde das Chevron-Icon im Toggle-Link
        const chevronIcon = toggleLink.querySelector('.bi-chevron-down, .bi-chevron-right');
        if (!chevronIcon) return;
        
        // Event-Listener für das Öffnen (show)
        collapseElement.addEventListener('show.bs.collapse', () => {
          chevronIcon.classList.remove('bi-chevron-right');
          chevronIcon.classList.add('bi-chevron-down');
          toggleLink.setAttribute('aria-expanded', 'true');
        });
        
        // Event-Listener für das Schließen (hide)
        collapseElement.addEventListener('hide.bs.collapse', () => {
          chevronIcon.classList.remove('bi-chevron-down');
          chevronIcon.classList.add('bi-chevron-right');
          toggleLink.setAttribute('aria-expanded', 'false');
        });
      });
    };
    
    // Initialisiere die Chevron-Funktionalität
    initSidebarChevrons();

    // Offcanvas‑Auto‑Hide: nur für Links ohne Collapse‑Toggle oder Gruppen‑Toggle-Href
    const off = document.getElementById('sidebarOffcanvas');
    if (off) {
      off.querySelectorAll('a.nav-link').forEach(link => {
        link.addEventListener('click', (e) => {
          const toggle = link.getAttribute('data-bs-toggle');
          const href = link.getAttribute('href');
          // Ignoriere Collapse-Toggles und Group hrefs
          if ((toggle === 'collapse') || (href && href.startsWith('#group'))) {
            return;
          }
          const bs = bootstrap.Offcanvas.getInstance(off);
          if (bs) bs.hide();
        });
      });
    }

    // Schritt 3: Initialisiere Dropdowns NACH Tooltips
    // Dropdown Auto-Close: Initialisiere alle Dropdowns mit autoClose-Option
    // Dies stellt sicher, dass Dropdowns automatisch schließen, wenn man außerhalb klickt
    // Funktioniert für Navbar und Offcanvas
    document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(toggle => {
      // Prüfe, ob bereits eine Instanz existiert
      let dropdownInstance = bootstrap.Dropdown.getInstance(toggle);
      
      if (!dropdownInstance) {
        // WICHTIG: Gemäß Bootstrap-Dokumentation sollten Tooltips NICHT auf demselben Element wie Dropdowns sein
        // Lösung: Entferne Tooltip-Attribute vom Dropdown-Element, um Konflikte zu vermeiden
        // Die HTML-Struktur sollte bereits korrekt sein (Tooltip auf untergeordnetem Element)
        const hasTooltipTitle = toggle.hasAttribute('data-tooltip-title');
        const hasTooltipToggle = toggle.hasAttribute('data-bs-toggle') && toggle.getAttribute('data-bs-toggle').includes('tooltip');
        
        // Entferne Tooltip-Attribute vom Dropdown-Element
        if (hasTooltipTitle) {
          toggle.removeAttribute('data-tooltip-title');
          toggle.removeAttribute('data-tooltip-placement');
        }
        
        if (hasTooltipToggle) {
          const toggleValue = toggle.getAttribute('data-bs-toggle');
          if (toggleValue === 'tooltip') {
            // Button hat nur Tooltip, kein Dropdown - entferne das Attribut
            toggle.removeAttribute('data-bs-toggle');
          } else if (toggleValue.includes('tooltip')) {
            // Entferne 'tooltip' vom Button, behalte 'dropdown'
            const values = toggleValue.split(' ').filter(v => v !== 'tooltip' && v !== '');
            if (values.length > 0) {
              toggle.setAttribute('data-bs-toggle', values.join(' '));
            } else {
              toggle.removeAttribute('data-bs-toggle');
            }
          }
        }
        
        // Entferne auch data-bs-title und data-bs-placement, falls vorhanden
        if (toggle.hasAttribute('data-bs-title')) {
          toggle.removeAttribute('data-bs-title');
        }
        
        if (toggle.hasAttribute('data-bs-placement')) {
          toggle.removeAttribute('data-bs-placement');
        }
        
        try {
          // Initialisiere Dropdown mit autoClose: true (Standard, aber explizit gesetzt)
          dropdownInstance = new bootstrap.Dropdown(toggle, {
            autoClose: true // Schließt automatisch bei Klick außerhalb
          });
        } catch (error) {
          // Ignoriere Fehler, wenn Dropdown bereits existiert
          if (!error.message || (!error.message.includes('already initialized') && !error.message.includes('more than one instance'))) {
            console.warn('[dashboard.js] Dropdown konnte nicht initialisiert werden:', error);
          }
        }
      } else {
        // Aktualisiere bestehende Instanz mit autoClose-Option
        dropdownInstance._config.autoClose = true;
      }
    });

    // Zusätzlicher Event-Listener für zuverlässiges Schließen
    // Funktioniert für Navbar und Offcanvas
    document.addEventListener('click', (e) => {
      const clickedElement = e.target;
      
      // Prüfe, ob der Klick auf einen Dropdown-Toggle war
      const isDropdownToggle = clickedElement.closest('[data-bs-toggle="dropdown"]');
      
      // Prüfe, ob der Klick innerhalb eines Dropdown-Menüs war
      const isDropdownMenu = clickedElement.closest('.dropdown-menu');
      
      // Wenn der Klick weder auf einen Toggle noch innerhalb des Menüs war
      if (!isDropdownToggle && !isDropdownMenu) {
        // Schließe alle offenen Dropdowns (sowohl in Navbar als auch in Offcanvas)
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
          // Finde den zugehörigen Dropdown-Container
          const dropdown = menu.closest('.dropdown');
          if (dropdown) {
            // Finde den Toggle-Button
            const toggle = dropdown.querySelector('[data-bs-toggle="dropdown"]');
            if (toggle) {
              const dropdownInstance = bootstrap.Dropdown.getInstance(toggle);
              if (dropdownInstance) {
                dropdownInstance.hide();
              } else {
                // Fallback: Entferne die 'show' Klasse manuell und setze aria-expanded
                menu.classList.remove('show');
                toggle.setAttribute('aria-expanded', 'false');
              }
            }
          }
        });
      }
    });

    // Avatar Fallback: Zeige Initialen, wenn Avatar nicht geladen
    document.querySelectorAll('.user-avatar-wrapper').forEach(wrapper => {
      const avatar = wrapper.querySelector('img.user-avatar');
      const fallback = wrapper.querySelector('.user-avatar-fallback');
      
      if (avatar && fallback) {
        // Funktion zum Anzeigen des Fallbacks
        const showFallback = () => {
          avatar.style.display = 'none';
          fallback.style.display = 'flex';
        };
        
        // Prüfe, ob Avatar geladen wurde
        if (avatar.complete) {
          // Avatar bereits geladen - prüfe ob gültig
          if (!avatar.naturalWidth || avatar.naturalHeight === 0 || !avatar.src || avatar.src.includes('mystery')) {
            showFallback();
          }
        } else {
          // Warte auf Avatar-Laden
          avatar.addEventListener('load', () => {
            if (!avatar.naturalWidth || avatar.naturalHeight === 0) {
              showFallback();
            }
          });
          
          avatar.addEventListener('error', () => {
            showFallback();
          });
          
          // Timeout: Falls Avatar nach 2 Sekunden nicht geladen wurde
          setTimeout(() => {
            if (!avatar.complete || !avatar.naturalWidth || avatar.naturalHeight === 0) {
              showFallback();
            }
          }, 2000);
        }
      }
    });

    // Tooltips für dynamisch geladene Widgets initialisieren
    // Wird nach dem neoDashboardReady Event aufgerufen, um sicherzustellen, dass alle Widgets gerendert sind
    // Verwende Singleton-Pattern, um mehrfache Initialisierungen zu vermeiden
    const initWidgetTooltips = (container = document) => {
      const selector = container === document ? '.card [data-bs-toggle="tooltip"]' : '[data-bs-toggle="tooltip"]';
      TooltipSingleton.initAll(container.querySelectorAll(selector));
    };
    
    // Initialisiere Tooltips nach kurzer Verzögerung (für dynamisch geladene Widgets)
    // Nur einmal ausführen, um mehrfache Initialisierungen zu vermeiden
    let initialTooltipInitDone = false;
    const doInitialTooltipInit = () => {
      if (initialTooltipInitDone) return;
      initialTooltipInitDone = true;
      setTimeout(() => {
        initWidgetTooltips();
      }, 300);
    };
    
    doInitialTooltipInit();

    const loadAsyncWidgets = () => {
      const containers = document.querySelectorAll('.neo-widget-async[data-widget-id]');
      if (!containers.length || !window.NeoDash || !NeoDash.ajaxurl || !NeoDash.widgetNonce) {
        if (!containers.length) {
          console.warn('[Neo Dashboard] Keine async Widgets gefunden.');
        } else {
          console.warn('[Neo Dashboard] NeoDash AJAX Config fehlt.', {
            hasNeoDash: !!window.NeoDash,
            ajaxurl: NeoDash && NeoDash.ajaxurl,
            widgetNonce: NeoDash && NeoDash.widgetNonce
          });
        }
        return;
      }
      const errorHtml = (NeoDash.strings && NeoDash.strings.widget_load_error)
        ? `<div class="text-danger small">${NeoDash.strings.widget_load_error}</div>`
        : '<div class="text-danger small">Fehler beim Laden des Widgets.</div>';

      containers.forEach((container) => {
        const widgetId = container.getAttribute('data-widget-id');
        if (!widgetId) return;

        const state = container.dataset.widgetState;
        if (state === 'loading' || state === 'loaded') return;
        container.dataset.widgetState = 'loading';

        const formData = new FormData();
        formData.append('action', 'neo_dashboard_widget');
        formData.append('widget_id', widgetId);
        formData.append('nonce', NeoDash.widgetNonce);

        fetch(NeoDash.ajaxurl, { method: 'POST', body: formData })
          .then((res) => res.json())
          .then((data) => {
            if (data && data.success && data.data && typeof data.data.html === 'string') {
              container.innerHTML = data.data.html;
              container.dataset.widgetState = 'loaded';
              document.dispatchEvent(new CustomEvent('neoDashboardContentAdded', {
                detail: { container: container }
              }));
            } else {
              container.dataset.widgetState = 'error';
              container.innerHTML = errorHtml;
            }
          })
          .catch(() => {
            container.dataset.widgetState = 'error';
            container.innerHTML = errorHtml;
          });
      });
    };
    
    // Initialisiere Tooltips auch nach dem Event (für Widgets, die nach dem Event geladen werden)
    // Verwende once: true, um sicherzustellen, dass der Listener nur einmal ausgeführt wird
    document.addEventListener('neoDashboardReady', () => {
      setTimeout(() => {
        initWidgetTooltips();
        loadAsyncWidgets();
      }, 200);
    }, { once: true });

    // Globales Event (nachdem der Listener registriert ist)
    document.dispatchEvent(new CustomEvent("neoDashboardReady"));

    // Fallback: async Widgets direkt anstoßen
    setTimeout(() => {
      loadAsyncWidgets();
    }, 0);
    
    // Höre auf Events, wenn neuer Content hinzugefügt wird (z.B. von Plugins)
    // Dies ist in Ordnung, da es für neue Container ist
    document.addEventListener('neoDashboardContentAdded', (e) => {
      const container = e.detail?.container || document;
      setTimeout(() => {
        initWidgetTooltips(container);
      }, 50);
    });
  });
})();
