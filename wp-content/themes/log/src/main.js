import './styles/main.css';
import { illustration } from './hero/illustration.js';

/**
 * Entry. Everything heavy (the map) is a dynamic import, so pages that don't
 * mount it never pay for it.
 */

// Header: search and mobile menu toggles
for (const btn of document.querySelectorAll('.site-header [aria-controls]')) {
  const target = document.getElementById(btn.getAttribute('aria-controls'));
  if (!target) continue;
  btn.addEventListener('click', () => {
    const open = btn.getAttribute('aria-expanded') === 'true';
    btn.setAttribute('aria-expanded', String(!open));
    target.hidden = open;
    if (!open) target.querySelector('input')?.focus();
  });
}

// Back links on level-3 pages (spec v6 §5): when the visitor came from the area page (or, for projects,
// from the home map), go back in history so search, filters, view, open popup and scroll are as they left them.
// From outside the site, the link simply leads to the area page.
for (const link of document.querySelectorAll('a[data-back]')) {
  let from = null;
  try { from = document.referrer ? new URL(document.referrer) : null; } catch { /* no referrer */ }
  if (!from || from.origin !== location.origin) continue;
  const trim = (p) => p.replace(/\/+$/, '');
  const fromHome = from.pathname === '/' && link.dataset.backHome;
  if (!fromHome && trim(from.pathname) !== trim(new URL(link.href).pathname)) continue; // the area page itself, not a sibling item
  if (fromHome) { link.textContent = link.dataset.backHome; link.href = from.href; }
  link.addEventListener('click', (e) => {
    if (history.length < 2) return;
    e.preventDefault();
    history.back();
  });
}

// Copy link buttons (job, knowledge item)
for (const btn of document.querySelectorAll('[data-copy-link]')) {
  btn.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(location.href);
      const label = btn.textContent;
      btn.textContent = 'הקישור הועתק';
      setTimeout(() => (btn.textContent = label), 1800);
    } catch {
      prompt('העתיקו את הקישור:', location.href);
    }
  });
}

// Tabs (clients by body type): arrow keys move between tabs, one panel shown at a time.
for (const wrap of document.querySelectorAll('[data-tabs]')) {
  const tabs = [...wrap.querySelectorAll('[role=tab]')];
  if (!tabs.length) continue;
  const select = (tab) => {
    tabs.forEach((t) => {
      const on = t === tab;
      t.setAttribute('aria-selected', String(on));
      t.tabIndex = on ? 0 : -1;
      document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
    });
  };
  tabs.forEach((t, i) => {
    t.addEventListener('click', () => select(t));
    t.addEventListener('keydown', (e) => {
      const d = { ArrowLeft: 1, ArrowRight: -1 }[e.key]; // RTL: left is next
      if (!d) return;
      const next = tabs[(i + d + tabs.length) % tabs.length];
      select(next);
      next.focus();
    });
  });
  select(tabs.find((t) => t.getAttribute('aria-selected') === 'true') || tabs[0]);
}

// Team tabs: filter people by organisational unit
const teamGrid = document.getElementById('team-grid');
if (teamGrid) {
  const chips = document.querySelectorAll('.filters--chips .chip');
  chips.forEach((chip) =>
    chip.addEventListener('click', () => {
      chips.forEach((c) => { c.classList.toggle('is-active', c === chip); c.setAttribute('aria-pressed', String(c === chip)); });
      const f = chip.dataset.filter;
      for (const card of teamGrid.children) card.hidden = !!f && !(card.dataset.unit || '').split(' ').includes(f);
    })
  );
}

// Projects archive: filters, gallery ↔ map toggle, URL state. The map itself loads on first toggle.
if (document.getElementById('projects-grid') || document.getElementById('projects-map')) {
  import('./projects.js').then((m) => m.initProjects());
}

// Forms: contact and apply
if (document.querySelector('form[data-endpoint]')) {
  import('./forms.js').then((m) => m.initForms());
}

// Project page without a photo: draw its building before the arrival wipe opens.
const art = document.querySelector('.project__art[data-id]');
if (art) art.innerHTML = illustration({ id: art.dataset.id, domain: art.dataset.domain, stage: art.dataset.stage }, 'project__art-svg', true);

// Arriving through the hero's ring transition
if (document.documentElement.classList.contains('is-wiping')) {
  import('./hero/transition.js').then((m) => m.enter()).catch(() => document.documentElement.classList.remove('is-wiping'));
}

// Project page: mini-map, lightbox, video in view
if (document.querySelector('.project')) {
  import('./project.js').then((m) => m.initProject());
}

// Home map: interface first, then the 3D layer (reduced motion gets the map without animation).
const hero = document.getElementById('hero');
// Header and twin buttons sit on the cover picture (light) until it scrolls away.
const cover = document.body.classList.contains('has-cover') && (hero || document.querySelector('.project__hero'));
if (cover) {
  document.body.classList.add('over-hero');
  new IntersectionObserver(([e]) => document.body.classList.toggle('over-hero', e.intersectionRatio > 0.12), { threshold: [0, 0.12, 0.5] }).observe(cover);
}
if (hero) {
  import('./hero.js').then((m) => m.initHero(hero)).catch((err) => { console.error(err); hero.classList.add('no-webgl'); });
}
