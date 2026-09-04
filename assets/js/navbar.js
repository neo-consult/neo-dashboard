document.addEventListener('DOMContentLoaded', function() {
    // Collapse-Toggle Handler
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(toggle => {
        toggle.onclick = function() {
            // Unterstütze sowohl href als auch data-bs-target
            let targetSelector = this.getAttribute('href') || this.getAttribute('data-bs-target');
            if (!targetSelector) return; // Kein Ziel gefunden, nichts tun
            
            // Entferne # am Anfang, falls vorhanden
            const target = targetSelector.startsWith('#') ? targetSelector.substring(1) : targetSelector;
            
            document.querySelectorAll('.collapse.show').forEach(collapse => {
                if (collapse.id !== target) {
                    const bsCollapse = bootstrap.Collapse.getInstance(collapse);
                    if (bsCollapse) bsCollapse.hide();
                }
            });
        };
    });
    
    // Tooltips für Elemente mit data-tooltip-title initialisieren
    // (für Elemente, die bereits data-bs-toggle für andere Funktionen verwenden)
    document.querySelectorAll('[data-tooltip-title]').forEach(element => {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            // Prüfe ob bereits eine Tooltip-Instanz existiert
            if (!bootstrap.Tooltip.getInstance(element)) {
                const title = element.getAttribute('data-tooltip-title');
                const placement = element.getAttribute('data-tooltip-placement') || 'top';
                new bootstrap.Tooltip(element, {
                    title: title,
                    placement: placement,
                    trigger: 'hover focus'
                });
            }
        }
    });
});



