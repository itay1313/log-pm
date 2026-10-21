/**
 * The 3D layer of the home map (spec v6 §8). Mounted by hero.js once the interface is live.
 *
 *  - Pins for public projects with a place; projects outside the current filter are hidden.
 *    All pins look the same: no size or stage encoding, no legend.
 *  - Hover (desktop) opens the card beside the pin; leaving closes it.
 *  - Click: a project with a page goes to it (ring + wipe); without one, the card locks open.
 *    Touch: first tap opens the card, a tap elsewhere closes it, the card's button goes to the page.
 *    Keyboard: Tab through pins (each is a button named after its project), Enter locks the card, Esc closes.
 *  - Overlapping projects (same spot even at the closest zoom): one marker with a number; click opens a picker.
 *  - Edge counters from the regional level down: how many projects lie beyond each edge; click goes there.
 *  - Zoom buttons, "כל הארץ", a live scale bar. Wheel zooms only with Ctrl/⌘; a plain wheel scrolls the page.
 *  - A locked card writes /?p={slug} through onLock, so it can be shared and returned to.
 */
import { createScene } from './scene.js';
import { leave } from './transition.js';
import { toLocal, toLngLat } from './geo.js';
import { itmLabel, toITM } from '../itm.js';
import { Vector3, Raycaster, Plane } from 'three';
import { illustration } from './illustration.js';

const fmt = new Intl.NumberFormat('he-IL');
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const slugOf = (p) => decodeURIComponent(p.slug || '');
const CLUSTER_PX = 46;
const clusterPx = (r) => (r > 150000 ? 64 : r > 30000 ? 54 : CLUSTER_PX);
const OVERLAP_M = 60;          // closer than this, projects can't be told apart by zooming
const EDGES_FROM_RADIUS = 300000; // edge counters only from the regional level down

export async function mount3D(hero, features, { pred, onLock, onSheet }) {
  const stage = hero.querySelector('.hero-stage');
  const pinsEl = hero.querySelector('.hero-pins');
  const labelsEl = hero.querySelector('.hero-labels');
  const edgesEl = hero.querySelector('.hero-edges');
  const card = hero.querySelector('.hero-card');
  const compass = hero.querySelector('.hero-compass');
  const scaleEl = hero.querySelector('.hero-scale');
  const hint = hero.querySelector('.hero__hint');
  const frame = hero.querySelector('.hero-frame');
  const coordEl = hero.querySelector('[data-coord]');
  const scaleText = hero.querySelector('[data-scale-text]');
  const scaleFmt = new Intl.NumberFormat('he-IL');
  // Margin lettering: easting along the top neatline, northing along the side, at three ticks each.
  const marks = [];
  if (frame) {
    for (const side of ['top', 'side']) for (const at of [0.25, 0.5, 0.75]) {
      const el = document.createElement('span');
      el.className = `hero-frame__tick hero-frame__tick--${side}`;
      el.style.setProperty('--at', `${at * 100}%`);
      frame.appendChild(el);
      marks.push({ el, side, at });
    }
  }
  const ray = new Raycaster(), ground = new Plane(new Vector3(0, 1, 0), 0), hitPt = new Vector3(), ndc = new Vector3();
  /** Grid coordinates under a screen point (stage px), or null above the horizon. */
  function gridAt(px, py) {
    ndc.set((px / stage.clientWidth) * 2 - 1, -(py / stage.clientHeight) * 2 + 1, 0.5);
    ray.setFromCamera(ndc, scene.camera);
    if (!ray.ray.intersectPlane(ground, hitPt)) return null;
    const [lng, lat] = toLngLat(hitPt.x, hitPt.z);
    return toITM(lat, lng);
  }
  function layoutMarks() {
    if (!marks.length) return;
    const f = frame.getBoundingClientRect(), st = stage.getBoundingClientRect();
    for (const m of marks) {
      // Top ticks read along the frame's top edge; side ticks along its left (end) edge.
      const px = m.side === 'top' ? f.right - st.left - f.width * m.at : f.left - st.left + 4;
      const py = m.side === 'top' ? f.top - st.top + 4 : f.top - st.top + f.height * m.at;
      const g = gridAt(px, py);
      m.el.textContent = g ? String(Math.round(g[m.side === 'top' ? 0 : 1] / 100) * 100) : '';
    }
  }
  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const sheet = matchMedia('(max-width: 700px)');

  const scene = await createScene(stage, { reduced });

  // Projects with a place on the map
  const all = features
    .filter((f) => f.geometry && !f.properties.national)
    .map((f) => { const [x, z] = toLocal(...f.geometry.coordinates); return { id: f.id, p: f.properties, x, z }; });
  const shown = () => all.filter((it) => pred(it.p));
  scene.setPlans(features.filter((f) => pred(f.properties)));

  hero.classList.add('is-ready');
  window.LOGhero = scene;

  // Wheel: Ctrl/⌘ zooms the map; otherwise the page scrolls and a short hint explains.
  let hintTimer = 0;
  stage.addEventListener('wheel', (e) => {
    const zoom = e.ctrlKey || e.metaKey;
    scene.controls.enableZoom = zoom;
    if (zoom) { scene.stopDrift(); return; }
    hero.classList.add('show-hint');
    clearTimeout(hintTimer);
    hintTimer = setTimeout(() => hero.classList.remove('show-hint'), 1400);
  }, { capture: true, passive: true });
  if (hint && /Mac|iPhone|iPad/.test(navigator.platform)) hint.textContent = 'כדי להתקרב: ⌘ וגלילה';
  // Touch: one finger scrolls the page, two fingers pan and pinch.
  scene.controls.touches.ONE = -1;
  let explored = false; // edge counters are wayfinding for exploring: the opening view stays clean
  const explore = () => { explored = true; };
  stage.addEventListener('pointerdown', () => { scene.stopDrift(); explore(); });
  stage.addEventListener('wheel', (e) => { if (e.ctrlKey || e.metaKey) explore(); }, { passive: true });
  scene.controls.addEventListener('start', () => { scene.stopDrift(); if (!locked) closeCard(); });

  hero.querySelector('.hero-zoom__in')?.addEventListener('click', () => { scene.stopDrift(); explore(); scene.zoomBy(0.5); });
  hero.querySelector('.hero-zoom__out')?.addEventListener('click', () => { scene.stopDrift(); explore(); scene.zoomBy(2); });
  compass?.addEventListener('click', () => { closeCard(); scene.country(); });

  // Pins: a pool of DOM buttons re-laid out every frame.
  let lastPointer = 'mouse';
  pinsEl.addEventListener('pointerdown', (e) => (lastPointer = e.pointerType));
  const pool = [];
  function pinNode(i) {
    if (!pool[i]) {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'hero-pin';
      b.innerHTML = '<span class="hero-pin__shape" aria-hidden="true"></span><span class="hero-pin__count" aria-hidden="true"></span>';
      pinsEl.appendChild(b);
      b.addEventListener('mouseenter', () => { if (b._item && !locked && lastPointer === 'mouse') openCard(b._item); });
      b.addEventListener('mouseleave', () => { if (!locked) scheduleClose(); });
      b.addEventListener('click', (e) => {
        if (b._cluster) return clusterClick(b._cluster);
        if (!b._item) return;
        const it = b._item;
        const keyboard = e.detail === 0;
        if (!keyboard && lastPointer === 'mouse' && it.p.page) return go(it);
        scene.flyTo(it.x, it.z);
        openCard(it);
        lock(it);
      });
      pool[i] = b;
    }
    return pool[i];
  }
  function clusterClick(items) {
    let minX = Infinity, maxX = -Infinity, minZ = Infinity, maxZ = -Infinity;
    for (const it of items) { minX = Math.min(minX, it.x); maxX = Math.max(maxX, it.x); minZ = Math.min(minZ, it.z); maxZ = Math.max(maxZ, it.z); }
    if (Math.max(maxX - minX, maxZ - minZ) < OVERLAP_M || scene.radius() < 900) return openPicker(items);
    scene.fit(items.map((it) => [it.x, it.z]));
  }

  /* ---------------------------------------------------------------- card */

  let cardItem = null, closeTimer = 0, locked = null;
  function openCard(item) {
    clearTimeout(closeTimer);
    if (cardItem === item && !card.hidden) return;
    cardItem = item;
    const p = item.p;
    const st = p.completed ? 4 : Math.min(4, Math.max(1, Number(p.stageNumber) || 1));
    const stageRow = `<div class="stage-row${p.completed ? ' stage-row--done' : ''}"><span class="stage-row__track" aria-hidden="true" style="--now:${st}"><i></i><i></i><i></i><i></i><span class="stage-row__fill"></span></span><span class="stage-row__label">${esc(p.stageLabel)}</span></div>`;
    const place = [...new Set([p.city, p.authorityName].filter(Boolean))].join(' · ');
    const facts = [
      p.units ? [fmt.format(p.units), 'יח"ד'] : null,
      p.sqm ? [fmt.format(p.sqm), 'מ"ר'] : null,
      p.dunam ? [fmt.format(p.dunam), 'דונם'] : null,
    ].filter(Boolean).slice(0, 3);
    card.innerHTML = `
      <button type="button" class="hero-card__close" aria-label="סגירה"><i class="icon icon--close" aria-hidden="true"></i></button>
      <div class="hero-card__media${p.image ? '' : ' is-art'}">
        ${p.image ? `<img src="${esc(p.image)}" alt="" loading="eager" decoding="async">` : illustration(p)}
        ${p.domainName ? `<span class="hero-card__tag">${esc(p.domainName)}</span>` : ''}
      </div>
      <div class="hero-card__body">
        <p class="hero-card__meta"><span>${esc(place)}</span>${p.grid ? `<span class="grid-ref">${esc(p.grid)}</span>` : ''}</p>
        <h3 class="hero-card__title">${esc(p.title)}</h3>
        ${facts.length ? `<dl class="hero-card__facts">${facts.map(([v, k]) => `<div><dd>${esc(v)}</dd><dt>${esc(k)}</dt></div>`).join('')}</dl>` : ''}
        <div class="hero-card__stage">${stageRow}</div>
      </div>
      ${p.page ? `<a class="hero-card__go" href="${esc(p.url)}"><span>לעמוד הפרויקט</span><i aria-hidden="true"></i></a>` : ''}`;
    showCard();
  }
  /** Overlapping projects: a short list to pick from, in the card's place. */
  function openPicker(items) {
    const it0 = items[0];
    cardItem = { ...it0, picker: true };
    card.innerHTML = `
      <button type="button" class="hero-card__close" aria-label="סגירה"><i class="icon icon--close" aria-hidden="true"></i></button>
      <div class="hero-card__body">
        <p class="hero-card__meta">${fmt.format(items.length)} פרויקטים באותו מקום</p>
        <ul class="hero-card__pick">${items.map((it) => `<li><button type="button" data-id="${it.id}"><strong>${esc(it.p.title)}</strong><span>${esc([it.p.domainName, it.p.stageLabel].filter(Boolean).join(' · '))}</span></button></li>`).join('')}</ul>
      </div>`;
    showCard();
    card.querySelector('.hero-card__pick button')?.focus();
  }
  function showCard() {
    card.hidden = false;
    card.classList.toggle('is-sheet', sheet.matches);
    card.classList.remove('is-in');
    requestAnimationFrame(() => card.classList.add('is-in'));
    onSheet(sheet.matches);
    placeCard();
  }
  function scheduleClose() { clearTimeout(closeTimer); closeTimer = setTimeout(closeCard, 320); }
  function closeCard() {
    clearTimeout(closeTimer);
    card.hidden = true;
    card.classList.remove('is-in');
    cardItem = null;
    lock(null);
    onSheet(false);
  }
  function lock(item) {
    const slug = item ? slugOf(item.p) : null;
    if (slug === (locked && slugOf(locked.p))) return;
    locked = item;
    onLock(slug);
  }
  function go(item) {
    const s = scene.toScreen(item.x, item.z);
    const rect = stage.getBoundingClientRect();
    scene.flyTo(item.x, item.z, { radius: 650, phi: 1.08, duration: 1.8 });
    leave(item.p.url, { x: rect.left + (s?.x ?? rect.width / 2), y: rect.top + (s?.y ?? rect.height / 2), title: item.p.title });
  }
  card.addEventListener('mouseenter', () => clearTimeout(closeTimer));
  card.addEventListener('mouseleave', () => { if (!locked) scheduleClose(); });
  card.addEventListener('click', (e) => {
    if (e.target.closest('.hero-card__close')) return closeCard();
    const pick = e.target.closest('.hero-card__pick button');
    if (pick) {
      const it = all.find((x) => x.id === Number(pick.dataset.id));
      if (it) { openCard(it); lock(it); }
      return;
    }
    const goEl = e.target.closest('.hero-card__go');
    if (goEl && cardItem && !cardItem.picker) { e.preventDefault(); go(cardItem); }
  });
  // Touch: a tap anywhere else on the map closes the card.
  stage.addEventListener('pointerup', (e) => { if (e.pointerType !== 'mouse' && !card.hidden) closeCard(); });

  function placeCard() {
    if (!cardItem || card.hidden) return;
    if (card.classList.contains('is-sheet')) { card.style.transform = ''; return; }
    const s = scene.toScreen(cardItem.x, cardItem.z);
    if (!s) return closeCard();
    const W = stage.clientWidth, H = stage.clientHeight, cw = card.offsetWidth, ch = card.offsetHeight;
    const left = s.x > W / 2 ? s.x - cw - 34 : s.x + 34;
    const top = Math.min(H - ch - 16, Math.max(84, s.y - ch / 2));
    card.style.transform = `translate(${Math.round(left)}px, ${Math.round(top)}px)`;
  }

  /* ---------------------------------------------------------------- labels and edges */

  // Major city names at the national level, from where LOG works.
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
  labelsEl.innerHTML = cities.map((c) => `<span class="hero-label">${esc(c.name)}</span>`).join('');
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

  function layoutEdges(offscreen, r) {
    if (r > EDGES_FROM_RADIUS || !explored) { if (edgesEl.innerHTML) edgesEl.innerHTML = ''; return; }
    // Counters sit just inside the sheet's neatline frame, never under the header.
    const f = frame?.getBoundingClientRect(), st = stage.getBoundingClientRect();
    const box = f ? { l: f.left - st.left + 22, r: f.right - st.left - 22, t: f.top - st.top + 44, b: f.bottom - st.top - 112 } : { l: 30, r: stage.clientWidth - 30, t: 30, b: stage.clientHeight - 30 };
    const cx = (box.l + box.r) / 2, cy = (box.t + box.b) / 2;
    const buckets = new Map();
    for (const { it, s } of offscreen) {
      if (!s) continue;
      const dx = s.x - cx, dy = s.y - cy;
      const k = Math.min((box.r - box.l) / 2 / Math.abs(dx || 1e-6), (box.b - box.t) / 2 / Math.abs(dy || 1e-6));
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
      return `<button type="button" class="hero-edge" data-i="${i}" style="transform:translate(${x.toFixed(0)}px,${y.toFixed(0)}px)" aria-label="${b.items.length} פרויקטים בכיוון הזה. מעבר לקרוב ביותר."><i style="transform:rotate(${ang.toFixed(0)}deg)"></i><span>${b.items.length}</span></button>`;
    }).join('');
    edgesEl._buckets = list;
  }
  edgesEl.addEventListener('click', (e) => {
    const b = e.target.closest('.hero-edge');
    if (!b) return;
    // Go to the nearest project in that direction.
    const items = edgesEl._buckets[Number(b.dataset.i)].items;
    const cx = stage.clientWidth / 2, cy = stage.clientHeight / 2;
    let best = items[0], bd = Infinity;
    for (const it of items) { const s = scene.toScreen(it.x, it.z); if (!s) continue; const d = (s.x - cx) ** 2 + (s.y - cy) ** 2; if (d < bd) { bd = d; best = it; } }
    scene.flyTo(best.x, best.z, { radius: Math.max(scene.radius(), 2600) });
  });

  /* ---------------------------------------------------------------- frame */

  let edgeTick = 0;
  scene.onFrame((r) => {
    const W = stage.clientWidth, H = stage.clientHeight;
    const vis = [], off = [];
    const fr = frame?.getBoundingClientRect(), sr = stage.getBoundingClientRect();
    const inner = fr ? { l: fr.left - sr.left + 6, r: fr.right - sr.left - 6, t: fr.top - sr.top + 30, b: fr.bottom - sr.top - 6 } : { l: -10, r: W + 10, t: -10, b: H + 10 };
    for (const it of shown()) {
      const s = scene.toScreen(it.x, it.z);
      if (s && s.x > inner.l && s.x < inner.r && s.y > inner.t && s.y < inner.b) vis.push({ it, s });
      else off.push({ it, s });
    }
    // Greedy screen-space clustering
    const clusters = [];
    const px = clusterPx(r);
    for (const v of vis) {
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
      b.setAttribute('aria-label', single ? `${c.items[0].p.title}${c.items[0].p.city ? `, ${c.items[0].p.city}` : ''}` : `${c.items.length} פרויקטים במקום הזה`);
      b.style.transform = `translate(${Math.round(c.x)}px, ${Math.round(c.y)}px)`;
      b.hidden = false;
    });
    for (let i = clusters.length; i < pool.length; i++) pool[i].hidden = true;

    if (++edgeTick % 6 === 0) {
      layoutEdges(off, r);
      // The sheet margin: the camera centre's grid reference and the map scale, live.
      const [lng, lat] = toLngLat(scene.controls.target.x, scene.controls.target.z);
      if (coordEl) coordEl.textContent = itmLabel(lat, lng);
      layoutMarks();
      if (scaleText) {
        const denom = scene.metresPerPixel() / 0.0002645833; // CSS px at 96 dpi
        const mag = 10 ** Math.floor(Math.log10(denom));
        scaleText.textContent = `קנ״מ 1:${scaleFmt.format(Math.round(denom / mag) * mag)}`;
      }
    }
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

  // Pause when off-screen or hidden
  new IntersectionObserver(([e]) => (e.isIntersecting ? scene.resume() : scene.pause())).observe(hero);
  document.addEventListener('visibilitychange', () => (document.hidden ? scene.pause() : scene.resume()));

  function focusItem(it) {
    explore();
    scene.flyTo(it.x, it.z).then(() => { openCard(it); lock(it); });
  }

  return {
    async intro() {
      hero.classList.add('is-intro');
      await scene.intro();
      hero.classList.remove('is-intro');
    },
    /** Search or filters changed: swap the plans, close a card that no longer matches, optionally frame the group. */
    refilter({ fit = false } = {}) {
      scene.setPlans(features.filter((f) => pred(f.properties)));
      if (cardItem && !cardItem.picker && !pred(cardItem.p)) closeCard();
      const pts = shown();
      if (fit && pts.length) scene.fit(pts.map((it) => [it.x, it.z]));
    },
    focusSlug(slug) {
      const it = all.find((x) => slugOf(x.p) === slug);
      if (!it) return false;
      focusItem(it);
      return true;
    },
    closeCard,
    home: () => scene.country(),
  };
}
