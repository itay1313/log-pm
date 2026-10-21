/**
 * /projects (spec v6 §9): free search + three filters (domain, stage, authority), one value each,
 * shared by the gallery and the map. State lives in the query string so a filtered view can be
 * shared, and back/forward step through it:
 *   /projects?q=חולון&domain=urban-renewal&stage=stage-2&authority=חולון&view=map&p={slug}
 */
const FILTERS = ['domain', 'stage', 'authority'];

/** Lower-case, no niqqud, no geresh/gershayim or quotes, single spaces. */
export const norm = (s) => String(s ?? '').toLowerCase().replace(/[֑-ׇ]/g, '').replace(/["'`׳״]/g, '').replace(/\s+/g, ' ').trim();

/** Every word of the query appears in the haystack; a leading ה (the definite article) is optional. */
export function matchesQuery(haystack, q) {
  if (!q) return true;
  const h = norm(haystack);
  return norm(q).split(' ').every((w) => h.includes(w) || (w.length > 2 && w[0] === 'ה' && h.includes(w.slice(1))));
}

export function initProjects() {
  const form = document.querySelector('form.filters');
  const grid = document.getElementById('projects-grid');
  const mapEl = document.getElementById('projects-map');
  const national = document.getElementById('projects-national');
  const search = form?.querySelector('input[name=q]');
  const selects = Object.fromEntries(FILTERS.map((k) => [k, form?.querySelector(`select[name=${k}]`)]).filter(([, el]) => el));
  const viewButtons = [...document.querySelectorAll('.filters__view button')];
  const count = document.querySelector('.filters__count');
  const empty = document.getElementById('projects-empty');
  const clears = [...document.querySelectorAll('.filters__clear')];
  const items = [...(grid?.children || []), ...(national?.querySelectorAll('li') || [])];
  let mapApi = null;
  let mapLoading = null;
  let openSlug = null;

  // Option labels without their counts, so counts can be rewritten as filters change.
  for (const sel of Object.values(selects)) for (const o of sel.options) if (o.value) o.dataset.label ||= o.textContent.replace(/\s*\(\d+\)$/, '');

  const readUrl = () => {
    const q = new URLSearchParams(location.search);
    return { q: q.get('q') || '', view: q.get('view') === 'map' ? 'map' : 'grid', p: q.get('p'), ...Object.fromEntries(FILTERS.map((k) => [k, q.get(k) || ''])) };
  };
  const current = () => ({
    q: search?.value.trim() || '',
    view: viewButtons.find((b) => b.classList.contains('is-active'))?.dataset.view || 'grid',
    p: openSlug,
    ...Object.fromEntries(FILTERS.map((k) => [k, selects[k]?.value || ''])),
  });
  function writeUrl({ push = false } = {}) {
    const s = current();
    const q = new URLSearchParams();
    if (s.q) q.set('q', s.q);
    for (const k of FILTERS) if (s[k]) q.set(k, s[k]);
    if (s.view === 'map') q.set('view', 'map');
    if (s.view === 'map' && s.p) q.set('p', s.p);
    const url = location.pathname + (q.toString() ? `?${q}` : '');
    if (url !== location.pathname + location.search) history[push ? 'pushState' : 'replaceState'](null, '', url);
  }

  // One predicate for cards (data-*) and map features (properties).
  const match = (s, rec, skip) => FILTERS.every((k) => k === skip || !s[k] || rec[k] === s[k]) && matchesQuery(rec.search, s.q);
  const fromEl = (el) => ({ domain: el.dataset.domain, stage: el.dataset.stage, authority: el.dataset.authority, search: el.dataset.search });
  const fromProps = (p) => ({ domain: p.domain, stage: p.stage, authority: p.authority, search: `${p.title} ${p.city || ''} ${p.authorityName || ''}` });

  function apply() {
    const s = current();
    let shown = 0;
    for (const el of items) {
      const ok = match(s, fromEl(el));
      el.hidden = !ok;
      if (ok && el.closest('#projects-grid')) shown++;
    }
    // National plans are in the list under the map, not in the grid: count them once, in either view.
    const nationalShown = national ? national.querySelectorAll('li:not([hidden])').length : 0;
    if (mapApi) mapApi.setFeatures((p) => !p.national && match(s, fromProps(p)));

    // Faceted counts: each option counts what it would show, given the other filters and the search.
    for (const [k, sel] of Object.entries(selects)) {
      const n = {};
      for (const el of grid?.children || []) {
        const r = fromEl(el);
        if (match(s, r, k)) n[r[k]] = (n[r[k]] || 0) + 1;
      }
      for (const o of sel.options) {
        if (!o.value) continue;
        o.textContent = `${o.dataset.label} (${n[o.value] || 0})`;
        o.hidden = !n[o.value] && o.value !== sel.value;
      }
    }

    const filtered = s.q || FILTERS.some((k) => s[k]);
    if (count) count.textContent = filtered ? (shown === 1 ? 'פרויקט אחד' : `${shown} פרויקטים`) : '';
    clears.forEach((b) => { if (b.closest('form')) b.hidden = !filtered; });
    if (empty) empty.hidden = shown + nationalShown > 0;
  }

  async function setView(view) {
    viewButtons.forEach((b) => { const on = b.dataset.view === view; b.classList.toggle('is-active', on); b.setAttribute('aria-pressed', String(on)); });
    if (grid) grid.hidden = view === 'map';
    if (mapEl) mapEl.hidden = view !== 'map';
    if (national) national.hidden = view !== 'map';
    if (view !== 'map') return;
    if (mapApi) { mapApi.resize(); apply(); return; }
    if (mapLoading) return;
    mapEl.classList.add('is-loading');
    mapLoading = import('./map.js')
      .then((m) => m.mount(mapEl, { onSelect: (p) => { openSlug = decodeURIComponent(p.slug); writeUrl(); } }))
      .then((api) => {
        mapApi = api;
        window.LOGmap = api;
        mapEl.classList.remove('is-loading');
        apply();
        const wanted = readUrl().p;
        if (wanted) mapEl.addEventListener('log:map-ready', () => api.focusSlug(wanted), { once: true });
      });
  }

  function restore() {
    const s = readUrl();
    if (search) search.value = s.q;
    for (const k of FILTERS) if (selects[k]) selects[k].value = [...selects[k].options].some((o) => o.value === s[k]) ? s[k] : '';
    openSlug = s.p;
    apply();
    setView(s.view);
  }

  form?.addEventListener('submit', (e) => { e.preventDefault(); writeUrl({ push: true }); });
  let typing = 0;
  search?.addEventListener('input', () => { apply(); clearTimeout(typing); typing = setTimeout(() => writeUrl(), 350); });
  Object.values(selects).forEach((sel) => sel.addEventListener('change', () => { apply(); writeUrl({ push: true }); }));
  viewButtons.forEach((b) => b.addEventListener('click', () => { setView(b.dataset.view); writeUrl({ push: true }); }));
  clears.forEach((b) => b.addEventListener('click', () => {
    if (search) search.value = '';
    Object.values(selects).forEach((sel) => (sel.value = ''));
    apply();
    writeUrl({ push: true });
  }));
  window.addEventListener('popstate', restore);

  // Old links from before spec v6: #view=map&p=123 or #map=123.
  const legacy = location.hash.match(/(?:^#map=|[#&]p=)(\d+)/);
  if (location.hash && /view=map|map=/.test(location.hash)) {
    history.replaceState(null, '', `${location.pathname}?view=map`);
    if (legacy) mapEl?.addEventListener('log:map-ready', () => mapApi?.focus(Number(legacy[1])), { once: true });
  }
  restore();
}
