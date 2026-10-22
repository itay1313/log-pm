/**
 * Home map (spec v6 §8). Two layers:
 *
 * 1. The interface, which works without WebGL: free search with suggestions, three filters,
 *    the three live numbers, "פריסה ארצית" and "תצוגת רשימה" lists, and "לכל הפרויקטים" carrying
 *    the same search. State lives in the address: /?q=&domain=&stage=&authority=&p={slug}.
 * 2. The 3D map (hero/map3d.js, loaded after the interface): pins, clusters, edge counters, the
 *    project card, zoom buttons, Ctrl/⌘ + wheel zoom. If WebGL is missing or the map fails,
 *    the list view takes its place, so the content is always reachable.
 *
 * The numbers count the current group: public projects after search and filters, each once,
 * national plans included. Moving the camera never changes them.
 */
import { FILTERS, matches, matchesQuery, mergeQuery, recordFromProps, readState, stateQuery, setOptionCounts } from './filter.js';
import { track } from './consent.js';
import { gsap } from 'gsap';

const fmt = new Intl.NumberFormat('he-IL');
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const slugOf = (p) => decodeURIComponent(p.slug || '');
const placeOf = (p) => [...new Set([p.city, p.authorityName].filter(Boolean))].join(' · ');

export async function initHero(hero) {
  const form = hero.querySelector('.hero-tools');
  const input = form.querySelector('input[name=q]');
  const suggest = hero.querySelector('.hero-suggest');
  const selects = Object.fromEntries(FILTERS.map((k) => [k, form.querySelector(`select[name=${k}]`)]).filter(([, el]) => el));
  const statsEl = form.querySelector('.hero__stats');
  const emptyEl = form.querySelector('.hero-empty');
  const allLink = form.querySelector('.hero-all-link');
  const drawer = hero.querySelector('.hero-drawer');
  const listButtons = [...hero.querySelectorAll('.hero-list-btn')];
  const projectsUrl = allLink.getAttribute('href').split('?')[0];

  const data = await fetch(`${window.LOG.rest}map`).then((r) => r.json());
  const features = data.features;
  const hasUnits = features.some((f) => f.properties.units);
  const hasSqm = features.some((f) => f.properties.sqm);

  const state = readState();
  let locked = new URLSearchParams(location.search).get('p');
  input.value = state.q;
  for (const k of FILTERS) {
    if (selects[k]) selects[k].value = [...selects[k].options].some((o) => o.value === state[k]) ? state[k] : '';
    state[k] = selects[k]?.value || '';
  }

  const pred = (p, skip) => matches(state, recordFromProps(p), skip);
  const still = matchMedia('(prefers-reduced-motion: reduce)').matches;
  let map = null;

  /* ---------------------------------------------------------------- interface */

  function apply({ fit = false } = {}) {
    const group = features.filter((f) => pred(f.properties));
    const sum = (k) => group.reduce((n, f) => n + (Number(f.properties[k]) || 0), 0);
    // A number the data doesn't carry at all isn't shown; with no results, the numbers read zero.
    setStat('projects', group.length, true);
    setStat('units', sum('units'), hasUnits);
    setStat('sqm', sum('sqm'), hasSqm);

    for (const [k, sel] of Object.entries(selects)) {
      const n = {};
      for (const f of features) if (pred(f.properties, k)) n[f.properties[k]] = (n[f.properties[k]] || 0) + 1;
      setOptionCounts(sel, n);
    }

    const filtered = !!(state.q || FILTERS.some((k) => state[k]));
    emptyEl.hidden = group.length > 0;
    hero.classList.toggle('is-filtered', filtered);
    allLink.href = projectsUrl + (stateQuery(state) ? `?${stateQuery(state)}` : '');
    listButtons.forEach((b) => {
      const n = listItems(b.dataset.list).length;
      b.querySelector('span').textContent = fmt.format(n);
      if (b.dataset.list === 'national') b.hidden = n === 0; // no national plans in the group: no empty list to open
    });
    if (!drawer.hidden) renderList(drawer.dataset.list);
    map?.refilter({ fit: fit && filtered && group.length > 0 });
    writeUrl();
  }
  function setStat(key, value, show) {
    const el = statsEl.querySelector(`[data-stat=${key}]`);
    if (!el) return;
    el.hidden = !show;
    roll(el.querySelector('dd'), value);
  }
  /** The distance sign rolls to a new figure; the first figure counts up from 70% as the page opens. */
  function roll(dd, value) {
    const from = dd.dataset.v === undefined ? value * 0.7 : Number(dd.dataset.v);
    dd.dataset.v = value;
    dd._roll?.kill();
    if (still || from === value) { dd.textContent = fmt.format(value); return; }
    const o = { v: from };
    dd._roll = gsap.to(o, { v: value, duration: 1.1, ease: 'expo.out', onUpdate: () => (dd.textContent = fmt.format(Math.round(o.v))) });
  }
  function writeUrl() {
    const qs = mergeQuery(location.search, state, { p: locked });
    const url = location.pathname + (qs ? `?${qs}` : '') + location.hash;
    if (url !== location.pathname + location.search + location.hash) history.replaceState(history.state, '', url);
  }

  // Filters apply at once; free text applies on Enter or when cleared (suggestions show as you type).
  Object.entries(selects).forEach(([k, sel]) => sel.addEventListener('change', () => { state[k] = sel.value; apply({ fit: true }); track('filter', { filter: k, value: sel.value || 'all', where: 'map' }); }));
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const active = suggest.querySelector('[aria-selected=true]');
    if (active && !suggest.hidden) { choose(active.dataset.slug); return; }
    state.q = input.value.trim();
    closeSuggest();
    apply({ fit: true });
    if (state.q) track('search', { term: state.q, where: 'map', results: features.filter((f) => pred(f.properties)).length });
  });
  input.addEventListener('search', () => { if (!input.value && state.q) { state.q = ''; apply(); } });
  form.querySelector('.hero-clear').addEventListener('click', () => {
    input.value = '';
    state.q = '';
    for (const k of FILTERS) { state[k] = ''; if (selects[k]) selects[k].value = ''; }
    apply();
    map?.home();
  });

  // Phones: the filters open as a bottom sheet; the numbers and search stay on the map.
  const filterToggle = form.querySelector('.hero-filter-toggle');
  const setFiltersOpen = (open) => {
    hero.classList.toggle('filters-open', open);
    filterToggle?.setAttribute('aria-expanded', String(open));
    document.body.classList.toggle('sheet-open', open);
  };
  filterToggle?.addEventListener('click', () => setFiltersOpen(!hero.classList.contains('filters-open')));
  form.querySelector('.hero-filters__done')?.addEventListener('click', () => { setFiltersOpen(false); filterToggle?.focus(); });

  // Suggestions: projects by name, city or authority.
  input.addEventListener('input', () => {
    const q = input.value.trim();
    if (q.length < 2) return closeSuggest();
    const hits = features.filter((f) => matchesQuery(recordFromProps(f.properties).search, q))
      .sort((a, b) => Number(!a.properties.title.startsWith(q)) - Number(!b.properties.title.startsWith(q)))
      .slice(0, 8);
    if (!hits.length) return closeSuggest();
    suggest.innerHTML = hits.map((f, i) => {
      const p = f.properties;
      return `<li role="option" id="hero-opt-${i}" data-slug="${esc(slugOf(p))}" aria-selected="false"><strong>${esc(p.title)}</strong><span>${esc(placeOf(p))}</span></li>`;
    }).join('');
    suggest.hidden = false;
    input.setAttribute('aria-expanded', 'true');
  });
  input.addEventListener('keydown', (e) => {
    if (suggest.hidden || !['ArrowDown', 'ArrowUp', 'Escape'].includes(e.key)) return;
    e.preventDefault();
    e.stopPropagation();
    if (e.key === 'Escape') return closeSuggest();
    const opts = [...suggest.children];
    const i = opts.findIndex((o) => o.getAttribute('aria-selected') === 'true');
    const next = opts[(i + (e.key === 'ArrowDown' ? 1 : -1) + opts.length) % opts.length];
    opts.forEach((o) => o.setAttribute('aria-selected', String(o === next)));
    input.setAttribute('aria-activedescendant', next.id);
  });
  suggest.addEventListener('mousedown', (e) => { const li = e.target.closest('li'); if (li) { e.preventDefault(); choose(li.dataset.slug); } });
  input.addEventListener('blur', () => setTimeout(closeSuggest, 150));
  function closeSuggest() {
    suggest.hidden = true;
    suggest.innerHTML = '';
    input.setAttribute('aria-expanded', 'false');
    input.removeAttribute('aria-activedescendant');
  }

  /** A chosen suggestion: fly to it on the map, or open its page, or the list it lives in. The plate answers like an overhead sign. */
  const destLabel = form.querySelector('.hero-search__label');
  const askLabel = destLabel?.textContent;
  input.addEventListener('input', () => { if (destLabel) destLabel.textContent = askLabel; });
  function choose(slug) {
    closeSuggest();
    const f = features.find((x) => slugOf(x.properties) === slug);
    if (!f) return;
    input.value = f.properties.title;
    if (destLabel) destLabel.textContent = 'יעד';
    if (map && f.geometry && !f.properties.national && map.focusSlug(slug)) return;
    if (f.properties.page) { location.href = f.properties.url; return; }
    openList(f.properties.national || !f.geometry ? 'national' : 'all');
  }

  /* ---------------------------------------------------------------- lists */

  function listItems(kind) {
    return features.filter((f) => pred(f.properties) && (kind === 'all' || f.properties.national || !f.geometry))
      .sort((a, b) => a.properties.title.localeCompare(b.properties.title, 'he'));
  }
  function renderList(kind) {
    const items = listItems(kind);
    const cols = kind === 'national' ? ['שם', 'לקוח', 'שלב'] : ['שם', 'עיר', 'רשות', 'תחום', 'שלב'];
    const cells = (p) => (kind === 'national' ? [p.client, p.stageLabel] : [p.city, p.authorityName, p.domainName, p.stageLabel]);
    drawer.dataset.list = kind;
    drawer.innerHTML = `
      <div class="hero-drawer__head">
        <h2>${kind === 'national' ? 'פריסה ארצית' : 'כל הפרויקטים בקבוצה'} <span>${fmt.format(items.length)}</span></h2>
        ${map ? '<button type="button" class="hero-drawer__close" aria-label="סגירת הרשימה"><i class="icon icon--close" aria-hidden="true"></i></button>' : ''}
      </div>
      ${items.length ? `<table class="hero-table">
        <thead><tr>${cols.map((c) => `<th scope="col">${c}</th>`).join('')}</tr></thead>
        <tbody>${items.map((f) => {
          const p = f.properties;
          const onMap = map && f.geometry && !p.national;
          const name = onMap
            ? `<button type="button" data-slug="${esc(slugOf(p))}">${esc(p.title)}</button>`
            : p.page ? `<a href="${esc(p.url)}">${esc(p.title)}</a>` : esc(p.title);
          return `<tr><th scope="row">${name}</th>${cells(p).map((v) => `<td>${esc(v || '')}</td>`).join('')}</tr>`;
        }).join('')}</tbody></table>` : '<p class="hero-drawer__empty">אין פרויקטים בקבוצה הזו.</p>'}`;
  }
  function openList(kind) {
    renderList(kind);
    drawer.hidden = false;
    listButtons.forEach((b) => b.setAttribute('aria-expanded', String(b.dataset.list === kind)));
    if (map) document.body.classList.add('sheet-open');
  }
  function closeList() {
    if (!map) return; // without the map, the list is the view
    drawer.hidden = true;
    listButtons.forEach((b) => b.setAttribute('aria-expanded', 'false'));
    document.body.classList.remove('sheet-open');
  }
  listButtons.forEach((b) => b.addEventListener('click', () => (b.getAttribute('aria-expanded') === 'true' ? closeList() : openList(b.dataset.list))));
  drawer.addEventListener('click', (e) => {
    if (e.target.closest('.hero-drawer__close')) return closeList();
    const b = e.target.closest('button[data-slug]');
    if (b && map) { closeList(); map.focusSlug(b.dataset.slug); }
  });
  hero.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    if (hero.classList.contains('filters-open')) { setFiltersOpen(false); filterToggle?.focus(); return; }
    if (!drawer.hidden && map) { closeList(); listButtons[0]?.focus(); } else map?.closeCard();
  });

  /* ---------------------------------------------------------------- map */

  apply();
  hero.classList.add('is-data'); // the numbers and filters are live before the map is

  const fallback = (err) => {
    if (err) console.error(err);
    map = null;
    hero.classList.add('no-webgl');
    openList('all');
  };
  if (!webgl()) return fallback();
  try {
    const { mount3D } = await import('./hero/map3d.js');
    map = await mount3D(hero, features, {
      pred: (p) => pred(p),
      onLock: (slug) => { locked = slug; writeUrl(); if (slug) track('map_popup_open', { project: slug }); },
      onSheet: (open) => document.body.classList.toggle('sheet-open', open || !drawer.hidden),
    });
  } catch (err) {
    return fallback(err);
  }
  apply();
  // The drive: the scroll takes the camera from here on down the page (reduced motion keeps the map still).
  if (!still) import('./home/drive.js').then((m) => m.initDrive(hero, map.scene, map.points)).catch((err) => console.error(err));
  await map.intro();
  if (locked) map.focusSlug(locked);
}

function webgl() {
  try { const c = document.createElement('canvas'); return !!(c.getContext('webgl2') || c.getContext('webgl')); } catch { return false; }
}
