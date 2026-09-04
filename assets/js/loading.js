/* eslint-disable no-undef */
(() => {
  'use strict';

  // Reuse NeoDash namespace
  if (typeof window.NeoDash === 'undefined') {
    window.NeoDash = {};
  }

  const STRINGS = (window.NeoDash && window.NeoDash.strings) ? window.NeoDash.strings : {};

  const DEFAULTS = {
    // Accessible text for screen readers (visual spinner is CSS)
    ariaBusyLabel: STRINGS.loading || 'Lädt…',
  };

  const ButtonState = new WeakMap();
  const OverlayState = new WeakMap();

  function resolveEl(input) {
    if (!input) return null;
    if (typeof input === 'string') {
      try {
        return document.querySelector(input);
      } catch (e) {
        return null;
      }
    }
    if (input instanceof HTMLElement) return input;
    // support jQuery objects without hard dependency
    if (typeof input === 'object' && input.jquery && input[0] instanceof HTMLElement) return input[0];
    return null;
  }

  function ensureBtnContentWrapper(btn) {
    // If button already contains .btn-content, don't wrap again
    if (btn.querySelector('.btn-content')) return;
    const html = btn.innerHTML;
    btn.innerHTML = `<span class="btn-content">${html}</span>`;
  }

  function setButtonLoading(button, options = {}) {
    const btn = resolveEl(button);
    if (!btn) return () => {};

    // Prevent double init
    if (btn.classList.contains('btn-loading')) return () => {};

    const state = {
      html: btn.innerHTML,
      disabled: btn.disabled === true,
      ariaBusy: btn.getAttribute('aria-busy'),
      ariaLabel: btn.getAttribute('aria-label'),
    };
    ButtonState.set(btn, state);

    ensureBtnContentWrapper(btn);
    btn.classList.add('btn-loading');
    btn.disabled = true;
    btn.setAttribute('aria-busy', 'true');
    // Keep existing aria-label, but ensure at least something meaningful
    if (!state.ariaLabel) {
      btn.setAttribute('aria-label', options.ariaLabel || DEFAULTS.ariaBusyLabel);
    }

    return () => {
      const s = ButtonState.get(btn);
      btn.classList.remove('btn-loading');
      if (s) {
        btn.innerHTML = s.html;
        btn.disabled = s.disabled;
        if (s.ariaBusy === null) btn.removeAttribute('aria-busy'); else btn.setAttribute('aria-busy', s.ariaBusy);
        if (s.ariaLabel === null) btn.removeAttribute('aria-label'); else btn.setAttribute('aria-label', s.ariaLabel);
      }
      ButtonState.delete(btn);
    };
  }

  function showOverlay(container, options = {}) {
    const root = resolveEl(container);
    if (!root) return () => {};

    // Target: modal-content if container is a modal
    let target = root;
    if (root.classList.contains('modal')) {
      const mc = root.querySelector('.modal-content');
      if (mc) target = mc;
    } else {
      const parentModal = root.closest ? root.closest('.modal') : null;
      if (parentModal) {
        const mc = parentModal.querySelector('.modal-content');
        if (mc) target = mc;
      }
    }

    // Ensure positioned container
    const pos = window.getComputedStyle(target).position;
    if (pos === 'static' || !pos) {
      target.style.position = 'relative';
    }

    // Reuse existing overlay if present
    let overlay = target.querySelector('.modal-loading-overlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.className = 'modal-loading-overlay';
      overlay.innerHTML = '<div class="modal-loading-spinner" aria-hidden="true"></div>';
      target.appendChild(overlay);
    }

    overlay.style.display = 'flex';
    overlay.style.visibility = 'visible';
    overlay.style.opacity = '1';

    OverlayState.set(overlay, { target });

    return () => {
      try {
        overlay.style.setProperty('display', 'none', 'important');
        overlay.style.visibility = 'hidden';
        overlay.style.opacity = '0';
      } catch (e) {
        // ignore
      }
      OverlayState.delete(overlay);
    };
  }

  function getSubmitButton(form, submitter) {
    if (submitter && submitter instanceof HTMLElement) return submitter;
    const explicit = form.querySelector('[data-neo-loading-submit="1"]');
    if (explicit) return explicit;
    return form.querySelector('button[type="submit"], input[type="submit"]');
  }

  function bindDocumentHandlers() {
    // Forms: opt-in via data-neo-loading-form="1"
    document.addEventListener('submit', (e) => {
      const form = e.target;
      if (!(form instanceof HTMLFormElement)) return;
      if (form.getAttribute('data-neo-loading-form') !== '1') return;

      // Guard: avoid double-submit via Enter etc.
      if (form.dataset.neoLoadingActive === '1') return;
      form.dataset.neoLoadingActive = '1';

      const submitter = e.submitter || null;
      const btn = getSubmitButton(form, submitter);
      const cleanupBtn = btn ? setButtonLoading(btn) : () => {};

      let cleanupOverlay = () => {};
      const overlayMode = form.getAttribute('data-neo-loading-overlay') || '';
      if (overlayMode === 'modal') {
        cleanupOverlay = showOverlay(form);
      }

      // If navigation happens, cleanup is irrelevant; still keep for AJAX cases
      // (plugins can call cleanup manually by preventing default + doing AJAX)
      form._neoLoadingCleanup = () => {
        cleanupOverlay();
        cleanupBtn();
        delete form.dataset.neoLoadingActive;
      };
    }, true);

    // Buttons/links: opt-in via data-neo-loading-button="1"
    document.addEventListener('click', (e) => {
      const el = e.target instanceof HTMLElement ? e.target.closest('[data-neo-loading-button="1"]') : null;
      if (!el) return;
      // don't break disabled buttons
      if (el instanceof HTMLButtonElement && el.disabled) return;
      setButtonLoading(el);
    }, true);
  }

  // Public API
  window.NeoDash.loading = {
    setButtonLoading,
    showOverlay,
    bind: bindDocumentHandlers,
  };

  // Auto-bind on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindDocumentHandlers);
  } else {
    setTimeout(bindDocumentHandlers, 0);
  }
})();

