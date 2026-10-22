import './styles/main.css';
import { illustration } from './hero/illustration.js';
import { initConsent, track } from './consent.js';

initConsent();
import('./motion.js').then((m) => m.initMotion()).catch(() => document.documentElement.classList.remove('js-motion'));

// Measurement events that belong to a page load (spec v6 §21). Nothing personal is sent.
{
  const from = (() => {
    try {
      const r = document.referrer ? new URL(document.referrer) : null;
      if (!r || r.origin !== location.origin) return r ? 'external' : 'direct';
      if (r.pathname === '/') return new URLSearchParams(r.search).get('p') ? 'map' : 'home';
      if (r.pathname.startsWith('/projects')) return new URLSearchParams(r.search).get('view') === 'map' ? 'map' : 'list';
      if (r.pathname.startsWith('/search')) return 'search';
      return 'site';
    } catch { return 'direct'; }
  })();
  const utm = new URLSearchParams(location.search).get('utm_source');
  const project = document.querySelector('.project[data-project]');
  if (project) track('project_view', { project: project.dataset.project, from });
  const job = document.querySelector('.job[data-job]');
  if (job) track('job_view', { job: job.dataset.job, source: utm || from });
  const search = document.querySelector('[data-search-term]');
  if (search?.dataset.searchTerm) track('search', { term: search.dataset.searchTerm, where: 'site', results: Number(search.dataset.searchResults) });
}

/**
 * Entry. Everything heavy (the map) is a dynamic import, so pages that don't
 * mount it never pay for it.
 */

// The gantry: the header turns into a solid navy bar once the page moves.
const header = document.querySelector('.site-header');
if (header) {
  const solid = () => header.classList.toggle('is-solid', scrollY > 8);
  addEventListener('scroll', solid, { passive: true });
  solid();
}

// The menu: a modal dialog (focus stays inside, Esc closes, the page behind is inert). The map pauses under it.
const menu = document.getElementById('site-menu');
const menuBtn = document.querySelector('.menu-toggle');
if (menu?.showModal && menuBtn) {
  const still = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
  let closing = false;
  const open = () => {
    menu.showModal();
    menuBtn.setAttribute('aria-expanded', 'true');
    document.body.classList.add('menu-open');
    window.LOGhero?.pause();
    if (!still()) import('./motion.js').then((m) => m.menuIn(menu)).catch(() => {});
  };
  const close = async () => {
    if (closing || !menu.open) return;
    closing = true;
    if (!still()) await import('./motion.js').then((m) => m.menuOut(menu)).catch(() => {});
    menu.close();
    closing = false;
  };
  menuBtn.addEventListener('click', open);
  menu.querySelector('.gantry__close')?.addEventListener('click', close);
  menu.addEventListener('cancel', (e) => { e.preventDefault(); close(); });
  menu.addEventListener('click', (e) => { if (e.target === menu) close(); });
  menu.addEventListener('close', () => {
    menuBtn.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('menu-open');
    window.LOGhero?.resume();
    menuBtn.focus();
  });
}

// The lane line: one orange works marking down the start edge of every long page, from the end of its opening
// band to the footer. It stands for everyone; motion.js draws it with the scroll when motion is allowed.
{
  const main = document.querySelector('.site-main');
  const first = document.getElementById('hero') || main?.querySelector('.page-header, .about-hero, .careers-hero, .control-hero, .project__hero');
  if (main && main.offsetHeight > innerHeight * 1.5) {
    const line = document.createElement('div');
    line.className = 'lane-line';
    line.setAttribute('aria-hidden', 'true');
    main.appendChild(line);
    const place = () => { line.style.top = `${first ? first.offsetTop + first.offsetHeight : 0}px`; };
    place();
    addEventListener('resize', place, { passive: true });
    addEventListener('load', place);
  }
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

// Job application in a dialog over the job page (spec v6 §14). Closing returns to the page.
const applyDialog = document.getElementById('apply');
if (applyDialog?.showModal) {
  let opener = null;
  const open = (btn) => { opener = btn || null; applyDialog.showModal(); applyDialog.querySelector('input:not([type=hidden]):not(.hp)')?.focus(); };
  document.querySelectorAll('[data-apply]').forEach((b) => b.addEventListener('click', () => open(b)));
  applyDialog.querySelector('.apply-dialog__close')?.addEventListener('click', () => applyDialog.close());
  applyDialog.addEventListener('click', (e) => { if (e.target === applyDialog) applyDialog.close(); });
  applyDialog.addEventListener('close', () => opener?.focus());
  if (location.hash === '#apply') open();
}

// Contact: a topic on the side sign picks it in the form and moves on to the first field.
for (const b of document.querySelectorAll('[data-topic]')) {
  b.addEventListener('click', () => {
    const select = document.querySelector('.form--contact select[name=topic]');
    if (!select) return;
    select.value = b.dataset.topic;
    select.dispatchEvent(new Event('change', { bubbles: true }));
    document.querySelectorAll('[data-topic]').forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
    document.querySelector('.form--contact input[name=name]')?.focus();
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

// Knowledge center: type tabs, search, year, load more
if (document.getElementById('knowledge-grid')) {
  import('./knowledge.js').then((m) => m.initKnowledge());
}

// Forms: contact and apply
if (document.querySelector('form[data-endpoint]')) {
  import('./forms.js').then((m) => m.initForms());
}

// Project tiles without a photo: draw the project's building as it scrolls near (the same drawing as the map card).
const tileArt = document.querySelectorAll('.card__placeholder[data-id]');
if (tileArt.length) {
  const draw = (el) => { el.innerHTML = illustration({ id: el.dataset.id, domain: el.dataset.domain, stage: el.dataset.stage }, 'card__art'); el.classList.add('is-drawn'); };
  // The first 24 (one page of the list) draw at once, so no tile is ever seen blank; the rest as they come near.
  const eager = [...tileArt].slice(0, 24);
  eager.forEach(draw);
  const rest = [...tileArt].slice(24);
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => entries.forEach((e) => { if (e.isIntersecting) { draw(e.target); io.unobserve(e.target); } }), { rootMargin: '800px' });
    rest.forEach((el) => io.observe(el));
  } else rest.forEach(draw);
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
if (hero) {
  // While the map fills the screen, phones park the lane bar (the search sits on that edge); off screen the map stops drawing.
  new IntersectionObserver(([e]) => {
    document.body.classList.toggle('at-map', e.intersectionRatio > 0.35);
    if (document.body.classList.contains('menu-open')) return;
    if (e.isIntersecting) window.LOGhero?.resume(); else window.LOGhero?.pause();
  }, { threshold: [0, 0.35, 0.6] }).observe(hero);
  import('./hero.js').then((m) => m.initHero(hero)).catch((err) => { console.error(err); hero.classList.add('no-webgl'); });
}
