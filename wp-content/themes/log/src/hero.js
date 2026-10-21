/**
 * Home hero (spec §7): the 3D map and its interface.
 *
 *  - pins from the CMS (/log/v1/map), clustered with counts while far away
 *  - hover/focus a pin: a card (image, place, title, short text, button) — statskog-style
 *  - click a pin: the camera dives in and the city rises; click the card's button: ring + wipe into the project
 *  - edge counters: how many projects lie beyond each edge of the screen, click to go there
 *  - bottom bar: compass (reset), scale, "all projects" drawer with domain chips
 *  - city names at the national level; the hero copy steps aside once you start exploring
 *  - wheel zooms the map only after a click on it, so the page keeps scrolling
 *  - /?p={slug} opens on that project with its card locked open; locking a card writes the same address
 */
import { createScene } from './hero/scene.js';
import { leave } from './hero/transition.js';
import { toLocal } from './hero/geo.js';
import { illustration } from './hero/illustration.js';

const DOMAIN_LABEL = {
  'urban-renewal': 'התחדשות עירונית', 'master-plans': 'תכניות מתאר', 'detailed-plans': 'תכניות מפורטות',
  'national-plans': 'תכניות לאומיות', 'open-spaces': 'שטחים פתוחים', 'rural-arab': 'המגזר הכפרי והחברה הערבית',
};
const fmt = new Intl.NumberFormat('he-IL');
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const CLUSTER_PX = 46;
const clusterPx = (r) => (r > 150000 ? 64 : r > 30000 ? 54 : CLUSTER_PX);

export async function initHero(hero) {
  const stage = hero.querySelector('.hero-stage');
  const pinsEl = hero.querySelector('.hero-pins');
  const labelsEl = hero.querySelector('.hero-labels');
  const edgesEl = hero.querySelector('.hero-edges');
  const card = hero.querySelector('.hero-card');
  const drawer = hero.querySelector('.hero-drawer');
  const compass = hero.querySelector('.hero-compass');
  const scaleEl = hero.querySelector('.hero-scale');
  const allBtn = hero.querySelector('.hero-all');
  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (!webgl()) { hero.classList.add('no-webgl'); return; }

  const [scene, data] = await Promise.all([
    createScene(stage, { reduced }),
    fetch(`${window.LOG.rest}map`).then((r) => r.json()),
  ]);

  // Projects with a place on the map
  const all = data.features
    .filter((f) => f.geometry && !f.properties.national)
    .map((f) => { const [x, z] = toLocal(...f.geometry.coordinates); return { id: f.id, p: f.properties, x, z }; });
  let active = '';
  const shown = () => all.filter((it) => !active || it.p.domain === active);
  scene.setPlans(data.features.filter((f) => !active || f.properties.domain === active));
  if (allBtn) allBtn.querySelector('span').textContent = fmt.format(all.length);

  hero.classList.add('is-ready');
  window.LOGhero = scene;

  // Arm wheel-zoom on click; disarm when the pointer leaves.
  stage.addEventListener('pointerdown', () => { scene.controls.enableZoom = true; hero.classList.add('is-armed'); scene.stopDrift(); });
  hero.addEventListener('pointerleave', () => { scene.controls.enableZoom = false; hero.classList.remove('is-armed'); });
  scene.controls.addEventListener('start', () => { scene.stopDrift(); closeCard(); });

  // Pins: a pool of DOM nodes re-laid out every frame.
  const pool = [];
  function pinNode(i) {
    if (!pool[i]) {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'hero-pin';
      b.innerHTML = '<span class="hero-pin__shape" aria-hidden="true"></span><span class="hero-pin__count" aria-hidden="true"></span>';
      pinsEl.appendChild(b);
      b.addEventListener('mouseenter', () => b._item && openCard(b._item, b));
      b.addEventListener('focus', () => b._item && openCard(b._item, b));
      b.addEventListener('mouseleave', () => scheduleCloseUnlessLocked());
      b.addEventListener('click', () => {
        if (b._cluster) return scene.fit(b._cluster.map((it) => [it.x, it.z]));
        if (b._item) { scene.flyTo(b._item.x, b._item.z); openCard(b._item, b); lock(b._item); }
      });
      pool[i] = b;
    }
    return pool[i];
  }

  let cardItem = null, cardPin = null, closeTimer = 0;
  function openCard(item, pin) {
    clearTimeout(closeTimer);
    if (cardItem?.id === item.id) return;
    cardItem = item; cardPin = pin;
    const p = item.p;
    const st = p.completed ? 4 : Math.min(4, Math.max(1, Number(p.stageNumber) || 1));
    const place = [...new Set([p.city, p.authorityName].filter(Boolean))].join(' · ');
    const facts = [
      p.units ? [fmt.format(p.units), 'יח"ד'] : null,
      p.dunam ? [fmt.format(p.dunam), 'דונם'] : null,
      p.year ? [p.year, 'שנת תכנית'] : null,
    ].filter(Boolean).slice(0, 2);
    const domain = p.domainName || DOMAIN_LABEL[p.domain];
    card.innerHTML = `
      <div class="hero-card__media${p.image ? '' : ' is-art'}">
        ${p.image ? `<img src="${esc(p.image)}" alt="" loading="eager" decoding="async">` : illustration(p)}
        ${domain ? `<span class="hero-card__tag">${esc(domain)}</span>` : ''}
      </div>
      <div class="hero-card__body">
        ${place ? `<p class="hero-card__meta">${esc(place)}</p>` : ''}
        <h3 class="hero-card__title">${esc(p.title)}</h3>
        ${p.excerpt ? `<p class="hero-card__text">${esc(p.excerpt)}</p>` : ''}
        ${facts.length ? `<dl class="hero-card__facts">${facts.map(([v, k]) => `<div><dd>${esc(v)}</dd><dt>${esc(k)}</dt></div>`).join('')}</dl>` : ''}
        <div class="hero-card__stage" style="--st:${st}">
          <span class="hero-card__bar" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
          <span>${esc(p.stageLabel)}</span>
        </div>
      </div>
      ${p.page ? `<a class="hero-card__go" href="${esc(p.url)}"><span>לעמוד הפרויקט</span><i aria-hidden="true">←</i></a>` : ''}`;
    card.hidden = false;
    card.classList.remove('is-in');
    requestAnimationFrame(() => card.classList.add('is-in'));
    placeCard();
  }
  function scheduleClose() { clearTimeout(closeTimer); closeTimer = setTimeout(closeCard, 320); }
  function closeCard() { clearTimeout(closeTimer); card.hidden = true; card.classList.remove('is-in'); cardItem = null; cardPin = null; lock(null); }

  // A locked card (clicked, or opened from /?p=) lives in the address so it can be shared and returned to.
  let locked = null;
  function lock(item) {
    if (item === locked) return;
    locked = item;
    const q = new URLSearchParams(location.search);
    if (item) q.set('p', decodeURIComponent(item.p.slug)); else q.delete('p');
    const qs = q.toString();
    history.replaceState(history.state, '', location.pathname + (qs ? `?${qs}` : '') + location.hash);
  }
  function scheduleCloseUnlessLocked() { if (!locked) scheduleClose(); }
  card.addEventListener('mouseenter', () => clearTimeout(closeTimer));
  card.addEventListener('mouseleave', scheduleCloseUnlessLocked);
  card.addEventListener('click', (e) => {
    const go = e.target.closest('.hero-card__go');
    if (!go || !cardItem) return;
    e.preventDefault();
    const s = scene.toScreen(cardItem.x, cardItem.z);
    const rect = stage.getBoundingClientRect();
    scene.flyTo(cardItem.x, cardItem.z, { radius: 650, phi: 1.08, duration: 1.8 });
    leave(go.href, { x: rect.left + (s?.x ?? rect.width / 2), y: rect.top + (s?.y ?? rect.height / 2), title: cardItem.p.title });
  });
  function placeCard() {
    if (!cardItem || card.hidden) return;
    const s = scene.toScreen(cardItem.x, cardItem.z);
    if (!s) return closeCard();
    const W = stage.clientWidth, H = stage.clientHeight, cw = card.offsetWidth, ch = card.offsetHeight;
    const left = s.x > W / 2 ? s.x - cw - 34 : s.x + 34;
    const top = Math.min(H - ch - 16, Math.max(84, s.y - ch / 2)); // 84: clear of the domain toggle
    card.style.transform = `translate(${Math.round(left)}px, ${Math.round(top)}px)`;
  }

  // City names at the national level: only cities where LOG works, from the CMS, sized by count.
  const cities = (() => {
    const m = new Map();
    for (const it of all) {
      const c = it.p.city;
      if (!c) continue;
      const e = m.get(c) || { name: c, x: 0, z: 0, n: 0 };
      e.x += it.x; e.z += it.z; e.n++;
      m.set(c, e);
    }
    return [...m.values()].map((e) => ({ ...e, x: e.x / e.n, z: e.z / e.n })).sort((a, b) => b.n - a.n).slice(0, 12);
  })();
  labelsEl.innerHTML = cities.map((c) => `<span class="hero-label" data-n="${c.n}">${esc(c.name)}</span>`).join('');
  const labelNodes = [...labelsEl.children];
  function layoutLabels(r) {
    const op = r > 120000 ? Math.min(1, (r - 120000) / 90000) : 0;
    labelsEl.style.opacity = op;
    if (!op) return;
    cities.forEach((c, i) => {
      const s = scene.toScreen(c.x, c.z);
      labelNodes[i].style.display = s ? '' : 'none';
      if (s) labelNodes[i].style.transform = `translate(${Math.round(s.x)}px, ${Math.round(s.y + 30)}px)`;
    });
  }

  // Edge counters for what's off-screen
  function layoutEdges(offscreen) {
    const W = stage.clientWidth, H = stage.clientHeight, cx = W / 2, cy = H / 2, pad = 30;
    const buckets = new Map();
    for (const { it, s } of offscreen) {
      const dx = (s ? s.x : it._sx) - cx, dy = (s ? s.y : it._sy) - cy;
      const k = Math.min((cx - pad) / Math.abs(dx || 1e-6), (cy - pad) / Math.abs(dy || 1e-6));
      const ex = cx + dx * k, ey = cy + dy * k;
      const key = `${Math.round(ex / 160)}:${Math.round(ey / 160)}`;
      const b = buckets.get(key) || { x: 0, y: 0, items: [] };
      b.x += ex; b.y += ey; b.items.push(it);
      buckets.set(key, b);
    }
    const list = [...buckets.values()];
    edgesEl.innerHTML = list.map((b, i) => {
      const x = b.x / b.items.length, y = b.y / b.items.length;
      const ang = Math.atan2(y - cy, x - cx) * 180 / Math.PI + 90;
      return `<button type="button" class="hero-edge" data-i="${i}" style="transform:translate(${x.toFixed(0)}px,${y.toFixed(0)}px)" aria-label="${b.items.length} פרויקטים בכיוון הזה"><i style="transform:rotate(${ang.toFixed(0)}deg)"></i><span>${b.items.length}</span></button>`;
    }).join('');
    edgesEl._buckets = list;
  }
  edgesEl.addEventListener('click', (e) => {
    const b = e.target.closest('.hero-edge');
    if (b) scene.fit(edgesEl._buckets[Number(b.dataset.i)].items.map((it) => [it.x, it.z]));
  });

  // Per-frame layout
  let edgeTick = 0;
  scene.onFrame((r) => {
    const W = stage.clientWidth, H = stage.clientHeight;
    const vis = [], off = [];
    for (const it of shown()) {
      const s = scene.toScreen(it.x, it.z);
      if (s && s.x > -10 && s.x < W + 10 && s.y > -10 && s.y < H + 10) vis.push({ it, s });
      else off.push({ it, s });
    }
    // Greedy screen-space clustering
    const clusters = [];
    for (const v of vis) {
      const px = clusterPx(r);
      const c = clusters.find((c) => Math.abs(c.x - v.s.x) < px && Math.abs(c.y - v.s.y) < px);
      if (c) { c.items.push(v.it); c.x = (c.x * (c.items.length - 1) + v.s.x) / c.items.length; c.y = (c.y * (c.items.length - 1) + v.s.y) / c.items.length; }
      else clusters.push({ x: v.s.x, y: v.s.y, items: [v.it] });
    }
    clusters.forEach((c, i) => {
      const b = pinNode(i);
      const single = c.items.length === 1;
      b._item = single ? c.items[0] : null;
      b._cluster = single ? null : c.items;
      b.classList.toggle('is-cluster', !single);
      b.classList.toggle('is-active', single && cardItem?.id === c.items[0].id);
      b.querySelector('.hero-pin__count').textContent = single ? '' : c.items.length;
      b.setAttribute('aria-label', single ? `${c.items[0].p.title}${c.items[0].p.city ? `, ${c.items[0].p.city}` : ''}` : `${c.items.length} פרויקטים`);
      b.style.transform = `translate(${Math.round(c.x)}px, ${Math.round(c.y)}px)`;
      b.hidden = false;
      if (single && cardItem?.id === c.items[0].id) cardPin = b;
    });
    for (let i = clusters.length; i < pool.length; i++) pool[i].hidden = true;

    if (++edgeTick % 6 === 0) layoutEdges(off);
    placeCard();
    layoutLabels(r);
    hero.classList.toggle('is-exploring', r < scene.national.radius * 0.55);

    if (compass) compass.style.setProperty('--rot', `${(-scene.theta() * 180) / Math.PI}deg`);
    if (scaleEl) {
      const m = scene.metresPerPixel() * 80;
      const nice = [10, 20, 50, 100, 200, 500, 1000, 2000, 5000, 10000, 20000, 50000, 100000].find((n) => n >= m) || 100000;
      scaleEl.style.setProperty('--w', `${(80 * nice) / m}px`);
      scaleEl.querySelector('span').textContent = nice >= 1000 ? `${nice / 1000} ק"מ` : `${nice} מ'`;
    }
  });

  // Bottom bar
  compass?.addEventListener('click', () => { closeCard(); scene.home(); });
  allBtn?.addEventListener('click', () => {
    const open = drawer.hidden;
    drawer.hidden = !open;
    allBtn.setAttribute('aria-expanded', String(open));
    if (open) renderDrawer();
  });
  function renderDrawer() {
    const domains = [...new Set(all.map((it) => it.p.domain).filter(Boolean))];
    const byCity = new Map();
    for (const it of shown()) { const c = it.p.city || 'ללא עיר'; byCity.set(c, [...(byCity.get(c) || []), it]); }
    drawer.innerHTML = `
      <div class="hero-drawer__chips" role="group" aria-label="סינון לפי תחום">
        <button type="button" class="chip${active ? '' : ' is-active'}" data-domain="" aria-pressed="${!active}">הכל</button>
        ${domains.map((d) => `<button type="button" class="chip${active === d ? ' is-active' : ''}" data-domain="${esc(d)}" aria-pressed="${active === d}">${esc(DOMAIN_LABEL[d] || d)}</button>`).join('')}
      </div>
      <div class="hero-drawer__list">
        ${[...byCity.entries()].sort((a, b) => b[1].length - a[1].length).map(([city, items]) => `
          <section><h4>${esc(city)} <span>${items.length}</span></h4>
          <ul>${items.map((it) => `<li><button type="button" data-id="${it.id}">${esc(it.p.title)}</button></li>`).join('')}</ul></section>`).join('')}
      </div>`;
  }
  drawer?.addEventListener('click', (e) => {
    const chip = e.target.closest('.chip');
    if (chip) {
      active = chip.dataset.domain;
      scene.setPlans(data.features.filter((f) => !active || f.properties.domain === active));
      renderDrawer();
      return;
    }
    const b = e.target.closest('button[data-id]');
    if (b) {
      const it = all.find((x) => x.id === Number(b.dataset.id));
      drawer.hidden = true;
      allBtn.setAttribute('aria-expanded', 'false');
      flyAndLock(it);
    }
  });
  hero.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    if (!drawer.hidden) { drawer.hidden = true; allBtn.setAttribute('aria-expanded', 'false'); allBtn.focus(); }
    else if (!card.hidden) closeCard();
  });

  // Pause when off-screen or hidden
  new IntersectionObserver(([e]) => (e.isIntersecting ? scene.resume() : scene.pause())).observe(hero);
  document.addEventListener('visibilitychange', () => (document.hidden ? scene.pause() : scene.resume()));

  function flyAndLock(it) {
    scene.flyTo(it.x, it.z).then(() => { const pin = pool.find((p) => p._item?.id === it.id); if (pin) { openCard(it, pin); lock(it); } });
  }

  hero.classList.add('is-intro');
  await scene.intro();
  hero.classList.remove('is-intro');
  window.LOGhero = scene;

  const wanted = new URLSearchParams(location.search).get('p');
  const target = wanted && all.find((it) => decodeURIComponent(it.p.slug) === wanted);
  if (target) flyAndLock(target);
}

function webgl() {
  try { const c = document.createElement('canvas'); return !!(c.getContext('webgl2') || c.getContext('webgl')); } catch { return false; }
}
