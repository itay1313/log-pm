/**
 * Cookie consent and measurement (spec v6 §6, §18, §21).
 * - The choice is kept in localStorage ('yes' | 'no'). No choice yet: the banner shows.
 * - Measurement (GA4, id from settings) loads only after 'yes'. Until then track() is a no-op.
 * - track(name, params) never sends personal details; callers pass ids, topics and places only.
 */
const KEY = 'log-consent';
let loaded = false;

const read = () => { try { return localStorage.getItem(KEY); } catch { return null; } };
const write = (v) => { try { localStorage.setItem(KEY, v); } catch { /* private mode: ask again next visit */ } };

export function consent() { return read() === 'yes'; }

export function track(name, params = {}) {
  if (!consent() || typeof window.gtag !== 'function') return;
  window.gtag('event', name, params);
}

function loadMeasurement() {
  const id = window.LOG?.ga;
  if (loaded || !id || !consent()) return;
  loaded = true;
  window.dataLayer = window.dataLayer || [];
  window.gtag = function gtag() { window.dataLayer.push(arguments); }; // eslint-disable-line prefer-rest-params
  window.gtag('js', new Date());
  window.gtag('config', id, { anonymize_ip: true });
  const s = document.createElement('script');
  s.async = true;
  s.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(id)}`;
  document.head.appendChild(s);
}

export function initConsent() {
  const banner = document.querySelector('.cookie-banner');
  const show = () => { if (banner) { banner.hidden = false; banner.querySelector('[data-consent]')?.focus({ preventScroll: true }); } };
  banner?.addEventListener('click', (e) => {
    const b = e.target.closest('[data-consent]');
    if (!b) return;
    write(b.dataset.consent);
    banner.hidden = true;
    if (b.dataset.consent === 'yes') loadMeasurement();
    else if (loaded) location.reload(); // withdrawing consent: drop the loaded scripts
  });
  document.querySelectorAll('[data-cookie-settings]').forEach((b) => b.addEventListener('click', show));
  const v = read();
  if (v === 'yes') loadMeasurement();
  else if (v === null && banner) banner.hidden = false;
  window.logTrack = track;
}
