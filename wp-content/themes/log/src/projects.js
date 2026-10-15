/**
 * /projects: filters (domain, scale) shared by the gallery and the map, gallery ↔ map toggle,
 * state in the URL hash so a filtered map view can be shared (spec §8).
 *   #view=map&domain=urban-renewal,master-plans&scale=l&p=123
 */
export function initProjects() {
  const grid = document.getElementById('projects-grid');
  const mapEl = document.getElementById('projects-map');
  const national = document.getElementById('projects-national');
  const boxes = [...document.querySelectorAll('.filters input[type=checkbox]')];
  const viewButtons = [...document.querySelectorAll('.filters__view button')];
  const count = document.querySelector('.filters__count');
  const empty = document.getElementById('projects-empty');
  let mapApi = null;
  let mapLoading = null;

  const state = readHash();
  boxes.forEach((b) => (b.checked = (state[b.name === 'log_domain' ? 'domain' : 'scale'] || []).includes(b.value)));

  const active = () => {
    const a = { domain: [], scale: [] };
    for (const b of boxes) if (b.checked) a[b.name === 'log_domain' ? 'domain' : 'scale'].push(b.value);
    return a;
  };
  const matches = (a, domain, scale) => (!a.domain.length || a.domain.includes(domain)) && (!a.scale.length || a.scale.includes(scale));

  function apply({ fromHash = false } = {}) {
    const a = active();
    let shown = 0;
    if (grid) for (const card of grid.children) {
      const ok = matches(a, card.dataset.domain, card.dataset.scale);
      card.hidden = !ok;
      if (ok) shown++;
    }
    if (mapApi) shown = mapApi.setFeatures((p) => matches(a, p.domain, p.scale) && !p.national) + (national ? national.querySelectorAll('li:not([hidden])').length : 0);
    if (national) for (const li of national.querySelectorAll('li')) li.hidden = !matches(a, li.dataset.domain, li.dataset.scale);
    const filtered = a.domain.length || a.scale.length;
    if (count) count.textContent = filtered ? `${shown} פרויקטים` : '';
    if (empty) empty.hidden = shown > 0;
    if (!fromHash) writeHash();
  }

  async function setView(view, { fromHash = false } = {}) {
    viewButtons.forEach((b) => { const on = b.dataset.view === view; b.classList.toggle('is-active', on); b.setAttribute('aria-pressed', String(on)); });
    if (grid) grid.hidden = view === 'map';
    if (mapEl) mapEl.hidden = view !== 'map';
    if (national) national.hidden = view !== 'map';
    if (view === 'map') {
      if (!mapApi && !mapLoading) {
        mapEl.classList.add('is-loading');
        mapLoading = import('./map.js').then((m) => m.mount(mapEl, { onSelect: (p) => writeHash(p.id) })).then((api) => { mapApi = api; window.LOGmap = api; mapEl.classList.remove('is-loading'); apply({ fromHash: true }); const h = readHash(); if (h.p) mapEl.addEventListener('log:map-ready', () => api.focus(Number(h.p)), { once: true }); });
      } else if (mapApi) { mapApi.resize(); apply({ fromHash: true }); }
    }
    if (!fromHash) writeHash();
  }

  function readHash() {
    const q = new URLSearchParams(location.hash.replace(/^#/, ''));
    return { view: q.get('view') || 'grid', domain: (q.get('domain') || '').split(',').filter(Boolean), scale: (q.get('scale') || '').split(',').filter(Boolean), p: q.get('p') };
  }
  function writeHash(p) {
    const a = active();
    const q = new URLSearchParams();
    const view = viewButtons.find((b) => b.classList.contains('is-active'))?.dataset.view || 'grid';
    if (view !== 'grid') q.set('view', view);
    if (a.domain.length) q.set('domain', a.domain.join(','));
    if (a.scale.length) q.set('scale', a.scale.join(','));
    if (p) q.set('p', p);
    const h = q.toString();
    history.replaceState(null, '', h ? `#${h}` : location.pathname + location.search);
  }

  boxes.forEach((b) => b.addEventListener('change', () => apply()));
  viewButtons.forEach((b) => b.addEventListener('click', () => setView(b.dataset.view)));
  document.querySelector('.filters__clear')?.addEventListener('click', () => { boxes.forEach((b) => (b.checked = false)); apply(); });

  // Legacy deep link from project pages: #map=ID
  const legacy = location.hash.match(/^#map=(\d+)$/);
  if (legacy) { history.replaceState(null, '', `#view=map&p=${legacy[1]}`); }
  const h = readHash();
  apply({ fromHash: true });
  setView(h.view === 'map' || legacy ? 'map' : 'grid', { fromHash: true });
}
