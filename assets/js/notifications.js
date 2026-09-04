/**
 * Neo Dashboard – Notification Client
 * -----------------------------------
 * v3.1.0 – 20 May 2025
 *
 * Ruft aktive Notifications via REST-API ab,
 * rendert sie als Bootstrap-Alerts und sorgt
 * für persistentes Dismiss (POST /dismiss).
 */

/* eslint-disable no-undef */
(() => {
	'use strict';

	// ---------------------------------------------------------------------
	// Konfiguration
	// ---------------------------------------------------------------------
	const normalizeApiRoot = (input) => {
		const raw = String(input || '').trim();
		const fallback = `${window.location.origin}/wp-json/neo-dashboard/v1`;

		let url;
		try {
			// Handles absolute URLs and relative paths
			url = new URL(raw || fallback, window.location.origin);
		} catch (e) {
			url = new URL(fallback, window.location.origin);
		}

		// If someone passed wpApiSettings.root (usually "/wp-json/"), append our namespace
		// Example: http://localhost:8080/wp-json/  ->  http://localhost:8080/wp-json/neo-dashboard/v1
		if (/\/wp-json\/?$/.test(url.pathname)) {
			url.pathname = url.pathname.replace(/\/wp-json\/?$/, '/wp-json/neo-dashboard/v1');
		}

		// Fix common local-dev mismatch: siteurl without port but page runs on :8080
		// Keep same host, but align protocol+port to current page.
		try {
			if (url.hostname === window.location.hostname) {
				url.protocol = window.location.protocol;
				url.port = window.location.port;
			}
		} catch (e) {
			// noop
		}

		// Drop trailing slash for safe concatenation
		const out = url.toString().replace(/\/+$/, '');
		return out;
	};

	const CONFIG = {
		apiRoot:
			normalizeApiRoot(
				(typeof NeoDash !== 'undefined' && NeoDash.restUrl) ||
				(window?.wpApiSettings?.root ? `${window.wpApiSettings.root}neo-dashboard/v1` : '') ||
				'/wp-json/neo-dashboard/v1'
			),
		nonce:
			(typeof NeoDash !== 'undefined' && NeoDash.nonce) ||
			window?.wpApiSettings?.nonce ||
			'',
		containerSelector: '#neo-notification-container',
	};

	const log = (...args) => {
		/* global console */
		if (window.localStorage?.getItem('neoDashDebug') === '1') {
			console.debug('[NotificationClient]', ...args);
		}
	};

	// ---------------------------------------------------------------------
	// Helper
	// ---------------------------------------------------------------------

	/**
	 * Liefert HTTP-Header inkl. Nonce, sofern vorhanden.
	 */
	const defaultHeaders = () => {
		const headers = { 'Content-Type': 'application/json' };
		if (CONFIG.nonce) {
			headers['X-WP-Nonce'] = CONFIG.nonce;
		}
		return headers;
	};

	/**
	 * Erstellt ein Alert-Element aus einer Notification-Payload.
	 *
	 * @param {{id:string, message:string, type:string, dismissible:boolean}} note
	 * @returns {HTMLElement}
	 */
	const createAlert = (note) => {
		const alert = document.createElement('div');
		alert.className = `alert alert-${note.type} ${
			note.dismissible ? 'alert-dismissible' : ''
		} fade show mb-2`;
		alert.setAttribute('role', 'alert');
		alert.dataset.id = note.id;

		alert.innerHTML = `
            <span class="neo-note-message">${note.message}</span>
            ${
				note.dismissible
					? '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
					: ''
			}
        `;

		if (note.dismissible) {
			// Bootstrap `closed.bs.alert` Event feuert NACH Animation
			alert.addEventListener('closed.bs.alert', () => dismiss(note.id));
		}

		return alert;
	};

	/**
	 * POST /dismiss, anschließend Eintrag lokal entfernen.
	 *
	 * @param {string} id
	 */
	const dismiss = async (id) => {
		try {
			await fetch(`${CONFIG.apiRoot}/notifications/${id}/dismiss`, {
				method: 'POST',
				headers: defaultHeaders(),
				credentials: 'same-origin',
			});
			log('dismissed', id);
		} catch (err) /* istanbul ignore next */ {
			// keep console clean unless debug is enabled
			log('Dismiss failed', err);
		}
	};

	/**
	 * Lädt Notifications, rendert sie in den Container.
	 */
	const loadNotifications = async () => {
		const container = document.querySelector(CONFIG.containerSelector);
		if (!container) {
			log('Container not found – alerts disabled');
		}

		try {
			const res = await fetch(`${CONFIG.apiRoot}/notifications`, {
				headers: defaultHeaders(),
				credentials: 'same-origin',
			});
			if (!res.ok) throw new Error(res.statusText);
			const payload = await res.json();
			const list = Array.isArray(payload)
				? payload
				: Array.isArray(payload?.data)
					? payload.data
					: [];

			const toastNotes = list.filter((note) => note.display === 'toast');
			const alertNotes = list.filter((note) => note.display !== 'toast');

			// Alerts rendern (falls Container vorhanden)
			if (container) {
				container.innerHTML = '';
				alertNotes.forEach((note) => container.appendChild(createAlert(note)));
			}

			// Toasts anzeigen
			toastNotes.forEach((note) => {
				if (typeof window.NeoDash?.toast !== 'function') {
					if (container) {
						container.appendChild(createAlert(note));
					}
					return;
				}

				const toastElement = window.NeoDash.toast(
					note.type || 'info',
					note.message || '',
					note.dismissible ? { duration: 0 } : {}
				);

				if (note.dismissible && toastElement) {
					toastElement.addEventListener('hidden.bs.toast', () => dismiss(note.id));
				}
			});

			log('rendered', list.length, 'notifications');
		} catch (err) {
			// keep console clean unless debug is enabled
			log('Load failed', err, { apiRoot: CONFIG.apiRoot });
		}
	};

	// ---------------------------------------------------------------------
	// Init
	// ---------------------------------------------------------------------
	document.addEventListener('DOMContentLoaded', loadNotifications);
})();
