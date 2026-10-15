/**
 * Home hero (spec §7): the full-screen project map with its chrome.
 *  - domain chips filter the map and the counts
 *  - hover: ring grows + a name tooltip follows the cursor
 *  - select: camera tilts and flies in, a side panel opens (bottom sheet on phones), Esc / "חזרה למפה" returns
 *  - edge dots: projects outside the view sit on the screen edge in their direction, click to fly there
 *  - keyboard: every project is a real <button> in a list; Tab reaches them, Enter selects
 */
import { mount } from './map.js';

const DOMAIN_LABEL = {
  'urban-renewal': 'התחדשות עירונית', 'master-plans': 'תכניות מתאר', 'detailed-plans': 'תכניות מפורטות',
  'national-plans': 'תכניות לאומיות', 'open-spaces': 'שטחים פתוחים', 'rural-arab': 'המגזר הכפרי והחברה הערבית',
};
const STAGE_LABEL = { 1: 'איתור והיתכנות', 2: 'תכנון וקידום סטטוטורי', 3: 'ליווי ואישור', 4: 'בקרה ומעקב' };
const SCALE_LABEL = { s: 'S', m: 'M', l: 'L', xl: 'XL' };
const fmt = new Intl.NumberFormat('he-IL');
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

export async function initHero(hero) {
  const mapEl = hero.querySelector('#hero-map');
  const panel = hero.querySelector('.hero-panel');
  const tooltip = hero.querySelector('.hero-tip');
  const chips = hero.querySelector('.hero-chips');
  const edges = hero.querySelector('.hero-edges');
  const list = hero.querySelector('.hero-list');
  const summary = hero.querySelector('.hero-summary');
  let active = '';
  let order = [];
  let selected = null;

  const api = await mount(mapEl, {
    theme: 'dark', hero: true,
    onHover: (p, point) => {
      if (!p) { tooltip.hidden = true; return; }
      tooltip.textContent = p.city && !p.title.includes(p.city) ? `${p.title} · ${p.city}` : p.title;
      tooltip.style.transform = `translate(${point.x}px, ${point.y}px)`;
      tooltip.hidden = false;
    },
    onSelect: (p) => openPanel(p),
    onMove: () => drawEdges(),
  });
  hero.classList.add('is-ready');

  const visible = () => api.all.features.filter((f) => f.geometry && (!active || f.properties.domain === active));

  function applyFilter() {
    api.setFeatures((p) => (!active || p.domain === active) && !p.national);
    order = visible().map((f) => f.id);
    const units = visible().reduce((s, f) => s + (Number(f.properties.units) || 0), 0);
    if (summary) summary.innerHTML = `<strong>${fmt.format(order.length)}</strong> פרויקטים · <strong>${fmt.format(units)}</strong> יח"ד`;
    buildList();
    closePanel({ keepCamera: true });
  }

  // Chips
  if (chips) {
    const domains = [...new Set(api.all.features.map((f) => f.properties.domain).filter(Boolean))];
    chips.innerHTML = `<button type="button" class="chip is-active" data-domain="" aria-pressed="true">הכל</button>` +
      domains.map((d) => `<button type="button" class="chip" data-domain="${esc(d)}" aria-pressed="false">${esc(DOMAIN_LABEL[d] || d)}</button>`).join('');
    chips.addEventListener('click', (e) => {
      const b = e.target.closest('.chip');
      if (!b) return;
      active = b.dataset.domain;
      chips.querySelectorAll('.chip').forEach((c) => { const on = c === b; c.classList.toggle('is-active', on); c.setAttribute('aria-pressed', String(on)); });
      applyFilter();
    });
  }

  // Keyboard list
  function buildList() {
    if (!list) return;
    list.innerHTML = visible().map((f) => `<li><button type="button" data-id="${f.id}">${esc(f.properties.title)}${f.properties.city ? ` · ${esc(f.properties.city)}` : ''}</button></li>`).join('');
  }
  list?.addEventListener('click', (e) => { const b = e.target.closest('button[data-id]'); if (b) api.focus(Number(b.dataset.id)); });

  // Panel
  function openPanel(p) {
    selected = p.id;
    const stage = Math.min(4, Math.max(1, Number(p.stage) || 1));
    const meta = [p.domain && DOMAIN_LABEL[p.domain], p.city, SCALE_LABEL[p.scale]].filter(Boolean).join(' · ');
    const figure = p.units ? `<strong>${fmt.format(p.units)}</strong> יח"ד` : p.dunam ? `<strong>${fmt.format(p.dunam)}</strong> דונם` : '';
    const idx = order.indexOf(p.id);
    const next = order.length > 1 ? order[(idx + 1) % order.length] : null;
    panel.innerHTML = `
      <button type="button" class="hero-panel__close" aria-label="סגירה">×</button>
      ${p.image ? `<img class="hero-panel__img" src="${esc(p.image)}" alt="">` : '<div class="hero-panel__img hero-panel__img--empty"></div>'}
      <div class="hero-panel__body">
        <span class="stage stage--${stage}" role="img" aria-label="שלב ${stage} מתוך 4: ${STAGE_LABEL[stage]}"><i></i><i></i><i></i><i></i></span>
        <span class="hero-panel__stage">שלב ${stage} מתוך 4 · ${STAGE_LABEL[stage]}</span>
        <p class="hero-panel__meta">${esc(meta)}</p>
        <h3 class="hero-panel__title">${esc(p.title)}</h3>
        <p class="hero-panel__figure">${figure}${p.year ? ` <span>${esc(p.year)}</span>` : ''}</p>
        <div class="hero-panel__actions">
          <a class="btn btn--primary" href="${esc(p.url)}">לעמוד המלא</a>
          ${next ? `<button type="button" class="btn btn--dark" data-next="${next}">הפרויקט הבא</button>` : ''}
          <button type="button" class="btn btn--ghost-dark" data-back>חזרה למפה</button>
        </div>
      </div>`;
    panel.hidden = false;
    hero.classList.add('has-panel');
    panel.querySelector('.btn--primary').focus({ preventScroll: true });
  }
  function closePanel({ keepCamera = false } = {}) {
    if (panel.hidden) return;
    panel.hidden = true;
    hero.classList.remove('has-panel');
    selected = null;
    if (keepCamera) api.deselect(); else api.reset();
  }
  panel.addEventListener('click', (e) => {
    if (e.target.closest('.hero-panel__close') || e.target.closest('[data-back]')) closePanel();
    const n = e.target.closest('[data-next]');
    if (n) api.focus(Number(n.dataset.next));
  });
  hero.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePanel(); });

  // Edge dots for off-screen projects
  function drawEdges() {
    if (!edges) return;
    const b = api.bounds();
    const w = mapEl.clientWidth, h = mapEl.clientHeight, cx = w / 2, cy = h / 2, pad = 18;
    const out = visible().filter((f) => !b.contains(f.geometry.coordinates));
    if (api.map.getZoom() < 7.2) out.length = 0; // whole country in view: nothing is "outside"
    edges.innerHTML = out.slice(0, 12).map((f) => {
      const pt = api.project(f.geometry.coordinates);
      const dx = pt.x - cx, dy = pt.y - cy;
      const k = Math.min((cx - pad) / Math.abs(dx || 1), (cy - pad) / Math.abs(dy || 1));
      const x = cx + dx * k, y = cy + dy * k;
      return `<button type="button" class="hero-edge" style="transform:translate(${x.toFixed(0)}px,${y.toFixed(0)}px)" data-id="${f.id}" aria-label="${esc(f.properties.title)}" title="${esc(f.properties.title)}"></button>`;
    }).join('');
  }
  edges?.addEventListener('click', (e) => { const b = e.target.closest('.hero-edge'); if (b) api.focus(Number(b.dataset.id)); });

  applyFilter();
  mapEl.addEventListener('log:map-ready', drawEdges, { once: true });
  window.LOGhero = api;
}
