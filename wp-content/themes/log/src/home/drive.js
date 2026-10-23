/**
 * The drive (docs/home-scroll-story.md): the home page as one journey with the map pinned behind it.
 *
 * At the top the visitor has the map. The first wheel or swipe down takes the camera from them and the scroll
 * moves it from then on; scrolling back to the top lands it exactly where they left it and hands it back.
 *
 * Chapter 1, the whole country: the camera rises from Gush Dan to all of Israel seen from over the sea,
 * every project lights up as a beacon rippling out from where the camera stood, the figures count with them,
 * and the legend lights word by word.
 *
 * Chapter 2, the build: the camera dives into one real plan and LOG's four steps happen on it. The boundary
 * draws itself as a dashed works line with the grid reference running; the site fills and dimension lines
 * stretch along it; the buildings standing inside it (OpenStreetMap's, as on the rest of the map) rise again
 * floor by floor; the camera turns round the site and a white sign hangs from it with the plan's real stage.
 *
 * Only loaded with WebGL and with motion allowed (hero.js). Without it both chapters are ordinary sections.
 */
import Lenis from 'lenis';
import 'lenis/dist/lenis.css';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';
import { toLocal, toLngLat } from '../hero/geo.js';
import { itmLabel } from '../itm.js';

gsap.registerPlugin(ScrollTrigger, SplitText);

// Israel from over the sea, looking east: the coast runs across the screen, the sea in front holds the legend.
const WIDE = { tx: -34000, tz: -38000, radius: 380000, phi: 0.74, theta: -Math.PI / 2 - 0.1 };
// Phones stand the country upright, north up, in the band between the figures and the legend.
const TALL = { tx: 2000, tz: -42000, radius: 560000, phi: 0.42, theta: -0.06 };
const phone = matchMedia('(max-width: 760px)');
const DRIFT = 0.16; // the camera keeps turning this far while the chapter reads

// Chapter 1 beats, as its scroll progress 0 → 1.
const AT = {
  ui: [0, 0.05],        // the map's tools fade
  rise: [0, 0.34],      // the camera climbs to the country while the legend scrolls in
  beacons: [0.3, 0.6],  // projects light up, the figures count with them
  title: [0.3, 0.42],
  prose: [0.44, 0.78],
  links: [0.76, 0.84],
};

// Chapter 2 beats. The first fifth is the flight in, while the chapter scrolls up; then one step per fifth.
const BUILD = {
  fly: [0, 0.2],
  steps: [[0.2, 0.4], [0.4, 0.6], [0.6, 0.8], [0.8, 1]],
  draw: [0.23, 0.36],   // 1. the boundary draws itself
  fill: [0.42, 0.5],    // 2. the site fills, dimension lines stretch
  dims: [0.44, 0.56],
  rise: [0.62, 0.78],   // 3. the buildings rise floor by floor
  sign: [0.83, 0.9],    // 4. the camera turns, the white sign hangs from the site
  lede: [0.9, 0.96],
};
const RISE_TOP = 320; // scene metres: the tile worker scales heights 1.9×, so this clears any tower
const FLOOR = 6;      // one floor (3.2 m) in scene metres: the rise steps by it
const MAX_SITE = 3e6; // the default plan must be a neighbourhood, not a region (m²)

const lerp = (a, b, t) => a + (b - a) * t;
const clamp01 = (x) => Math.min(1, Math.max(0, x));
const span = ([a, b], p) => clamp01((p - a) / (b - a));
const ease = (t) => t * t * (3 - 2 * t);
const turn = (from, to) => { const d = to - from; return Math.atan2(Math.sin(d), Math.cos(d)); };
const fmt = new Intl.NumberFormat('he-IL');
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

/**
 * A flight between two views: radius in log space, and the target follows the radius so the place under the
 * camera holds still while it climbs (or comes down) and only slides while far out.
 */
function fly(a, b, t) {
  const radius = Math.exp(lerp(Math.log(a.radius), Math.log(b.radius), t));
  const k = a.radius === b.radius ? t : clamp01((radius - a.radius) / (b.radius - a.radius));
  return { tx: lerp(a.tx, b.tx, k), tz: lerp(a.tz, b.tz, k), radius, phi: lerp(a.phi, b.phi, t), theta: a.theta + turn(a.theta, b.theta) * t };
}

export function initDrive(hero, scene, points, features) {
  const drive = document.getElementById('drive');
  const chapter = drive?.querySelector('.drive-country');
  if (!drive || !chapter) return;

  const root = document.documentElement;
  root.classList.add('drive-on');
  smoothScroll();

  const tools = [hero.querySelector('.hero-lead'), hero.querySelector('.hero-bar')].filter(Boolean);
  const inner = chapter.querySelector('.drive-country__inner');
  const title = chapter.querySelector('.section__title');
  const prose = chapter.querySelector('.section__prose');
  const links = chapter.querySelector('.link-row');
  const figureList = chapter.querySelector('.drive-figures');
  const figures = [...chapter.querySelectorAll('.drive-figures dd[data-count]')].map((dd) => ({ dd, n: Number(dd.dataset.count) }));

  const home = scene.homeView();
  scene.setBeacons(points, home.tx, home.tz);

  // The legend lights word by word: dim words are already there to read, never hidden.
  const titleWords = SplitText.create(title, { type: 'words' }).words;
  const proseWords = SplitText.create(prose, { type: 'words' }).words;
  const dim = 0.16;

  const build = buildChapter(drive.querySelector('.drive-build'), scene, features, hero);
  dispatchEvent(new Event('resize')); // the chapters just grew: the lane line and the triggers measure again

  let from = null; // the view the visitor left, while the scroll holds the camera
  let p1 = 0, p2 = 0;

  function take() {
    from = scene.hold();
    hero.classList.add('is-driving');
    document.body.classList.remove('at-map'); // the lane-split plates go back up to the header
    tools.forEach((el) => (el.inert = true));
    document.activeElement?.closest?.('.hero') && document.activeElement.blur();
  }
  function giveBack() {
    scene.setView(from);
    from = null;
    scene.release();
    hero.classList.remove('is-driving');
    document.body.classList.add('at-map');
    tools.forEach((el) => (el.inert = false));
  }

  /** Chapter 1's camera at progress p: from the visitor's view up to the country, then a slow turn. */
  function countryView(p) {
    const C = phone.matches ? TALL : WIDE;
    const v = fly(from, C, ease(span(AT.rise, p)));
    v.theta += DRIFT * span([AT.rise[1], 1], p);
    return v;
  }

  function update() {
    if (p1 > 0.001 && !from) take();
    else if (p1 <= 0.001 && from) giveBack();

    gsap.set(tools, { opacity: 1 - span(AT.ui, p1) });

    if (from) {
      const v = build && p2 > 0 ? build.view(countryView(1), p2) : countryView(p1);
      scene.setView(v);
    }
    build?.render(p2, p1);

    const lit = span(AT.beacons, p1) * (1 - span([0.04, 0.14], p2));
    scene.lightBeacons(lit);
    for (const { dd, n } of figures) dd.textContent = fmt.format(Math.round(n * span(AT.beacons, p1)));
    if (figureList) figureList.style.opacity = span([AT.beacons[0] - 0.06, AT.beacons[0]], p1).toFixed(3); // no zeros waiting on screen

    inner.style.setProperty('--scrim', span([0.12, 0.34], p1).toFixed(3));
    light(titleWords, span(AT.title, p1));
    light(proseWords, span(AT.prose, p1));
    gsap.set(links, { opacity: lerp(dim, 1, span(AT.links, p1)) });
  }

  /** Words lit up to t (0 → 1), each fading in over a few neighbours so the edge is soft. */
  function light(words, t) {
    const n = words.length, soft = 3;
    const head = t * (n + soft);
    for (let i = 0; i < n; i++) words[i].style.opacity = lerp(dim, 1, clamp01((head - i) / soft)).toFixed(3);
  }

  const track = (el, set) => ScrollTrigger.create({
    trigger: el, start: 'top bottom', end: 'bottom bottom',
    onUpdate: (self) => { set(self.progress); update(); },
    onRefresh: (self) => { set(self.progress); update(); },
  });
  track(chapter, (p) => { p1 = p; if (p > 0.5) build?.preload(); });
  if (build) track(build.el, (p) => { p2 = p; });
  ScrollTrigger.refresh();
}

/**
 * Chapter 2. Finds the plan (the editor's choice, or the most housing units on up to 3 km²), lays it into the
 * scene, builds the overlay, and returns view(start, q) for the camera and render(q) for everything else.
 * Returns null, leaving the section as plain "how we work", when no plan has a boundary to build on.
 */
function buildChapter(el, scene, features, hero) {
  if (!el) return null;
  const f = pickPlan(features, Number(el.dataset.project) || 0);
  if (!f) return null;
  const site = scene.setSite(f.properties.boundary);
  if (!site) return null;
  el.classList.add('is-live');

  const p = f.properties;
  const projectsUrl = (hero.querySelector('.hero-all-link')?.getAttribute('href') || '/projects/').split('?')[0];
  const href = p.page && p.url ? p.url : `${projectsUrl}?view=map&p=${encodeURIComponent(decodeURIComponent(p.slug || ''))}`;
  const place = [...new Set([p.city, p.authorityName].filter(Boolean))].join(' · ');

  const innerEl = el.querySelector('.drive-build__inner');
  const head = el.querySelector('.build-head');
  const projectLine = el.querySelector('.build-project');
  projectLine.innerHTML = `על תכנית אמיתית: <a href="${esc(href)}"><strong>${esc(p.title)}</strong></a>${p.city ? `, ${esc(p.city)}` : ''}`;
  projectLine.hidden = false;
  const steps = [...el.querySelectorAll('.process li')];
  const lede = el.querySelector('.section__lede');

  // Dimension lines on the plan's own axes (its principal direction), set off from the site.
  const pts = site.rings.flat();
  const mx = pts.reduce((a, [x]) => a + x, 0) / pts.length, mz = pts.reduce((a, [, z]) => a + z, 0) / pts.length;
  let sxx = 0, szz = 0, sxz = 0;
  for (const [x, z] of pts) { sxx += (x - mx) ** 2; szz += (z - mz) ** 2; sxz += (x - mx) * (z - mz); }
  const ang = 0.5 * Math.atan2(2 * sxz, sxx - szz);
  const U = [Math.cos(ang), Math.sin(ang)], V = [-Math.sin(ang), Math.cos(ang)];
  let u0 = Infinity, u1 = -Infinity, v0 = Infinity, v1 = -Infinity;
  for (const [x, z] of pts) {
    const u = (x - mx) * U[0] + (z - mz) * U[1], v = (x - mx) * V[0] + (z - mz) * V[1];
    u0 = Math.min(u0, u); u1 = Math.max(u1, u); v0 = Math.min(v0, v); v1 = Math.max(v1, v);
  }
  const off = Math.max(u1 - u0, v1 - v0) * 0.09;
  const at = (u, v) => [mx + U[0] * u + V[0] * v, mz + U[1] * u + V[1] * v];
  const dims = [
    { a: at(u0, v1 + off), b: at(u1, v1 + off), len: u1 - u0 },
    { a: at(u1 + off, v0), b: at(u1 + off, v1), len: v1 - v0 },
  ];
  const dunam = Number(p.dunam) || null;

  // Overlay: dimension lines, labels, the leader and the white sign (all aria-hidden but the sign's link).
  const overlay = document.createElement('div');
  overlay.className = 'build-overlay';
  overlay.innerHTML = `
    <svg width="100%" height="100%" aria-hidden="true">
      ${dims.map(() => '<path class="dim" d=""/>').join('')}
      <line class="leader" x1="0" y1="0" x2="0" y2="0"/><circle class="leader-dot" r="6" cx="-99" cy="-99"/>
    </svg>
    ${dims.map((d) => `<span class="build-label" aria-hidden="true">${fmt.format(Math.round(d.len / 10) * 10)} מ'</span>`).join('')}
    ${dunam ? `<span class="build-label build-label--area" aria-hidden="true">${fmt.format(dunam)} דונם<small>שטח התכנית</small></span>` : ''}
    <div class="build-sign">
      <p class="build-sign__meta">${esc(place)}</p>
      <h3 class="build-sign__title">${esc(p.title)}</h3>
      <dl class="build-sign__facts">
        ${p.units ? `<div><dt>יחידות דיור</dt><dd>${fmt.format(p.units)}</dd></div>` : ''}
        ${dunam ? `<div><dt>דונם</dt><dd>${fmt.format(dunam)}</dd></div>` : ''}
      </dl>
      <div class="stage-row${p.completed ? ' stage-row--done' : ''}"><span class="stage-row__track" aria-hidden="true"><i></i><i></i><i></i><i></i><span class="stage-row__fill"></span></span><span class="stage-row__label">${esc(p.stageLabel || '')}</span></div>
      <a class="build-sign__go" href="${esc(href)}"><span>${p.page ? 'לעמוד הפרויקט' : 'התכנית במפת הפרויקטים'}</span><i aria-hidden="true"></i></a>
    </div>`;
  innerEl.prepend(overlay);
  const coord = document.createElement('p');
  coord.className = 'build-coord';
  coord.setAttribute('aria-hidden', 'true');
  coord.innerHTML = 'רשת ישראל<span></span>';
  innerEl.append(coord);
  const coordValue = coord.querySelector('span');

  const paths = [...overlay.querySelectorAll('.dim')];
  const labels = [...overlay.querySelectorAll('.build-label:not(.build-label--area)')];
  const areaLabel = overlay.querySelector('.build-label--area');
  const leader = overlay.querySelector('.leader'), dot = overlay.querySelector('.leader-dot');
  const sign = overlay.querySelector('.build-sign');
  const track = sign.querySelector('.stage-row__track');
  const now = p.completed ? 4 : Math.min(4, Math.max(1, Number(p.stageNumber) || 1));

  let preloaded = false;
  const preload = () => { if (!preloaded) { preloaded = true; scene.preload(site.cx, site.cz); } };

  /** The camera: fly in from the country, then a short path round the site, a beat per step. */
  const R = Math.min(7000, Math.max(1400, site.span * 1.9));
  function view(start, q) {
    const theta0 = start.theta + 0.3;
    const keys = [
      [0.2, { tx: site.cx, tz: site.cz, radius: R, phi: 0.92, theta: theta0 }],
      [0.4, { tx: site.cx, tz: site.cz, radius: R * 0.96, phi: 0.88, theta: theta0 + 0.1 }],
      [0.6, { tx: site.cx, tz: site.cz, radius: R * 0.94, phi: 0.76, theta: theta0 + 0.2 }],
      [0.8, { tx: site.cx, tz: site.cz, radius: R * 0.8, phi: 1.02, theta: theta0 + 0.36 }],
      [1, { tx: site.cx, tz: site.cz, radius: R * 0.9, phi: 0.98, theta: theta0 + 1.25 }],
    ];
    if (q <= keys[0][0]) return fly(start, keys[0][1], ease(span(BUILD.fly, q)));
    let i = 1;
    while (i < keys.length - 1 && q > keys[i][0]) i++;
    const [qa, a] = keys[i - 1], [qb, b] = keys[i];
    const t = ease(span([qa, qb], q));
    return { tx: a.tx, tz: a.tz, radius: lerp(a.radius, b.radius, t), phi: lerp(a.phi, b.phi, t), theta: lerp(a.theta, b.theta, t) };
  }

  function render(q) {
    const live = q > 0;
    const ox = live ? overlay.getBoundingClientRect().left : 0; // screen x → overlay x (a scrollbar shifts it)
    if (live) preload();
    // Slide the picture off the steps' side on wide screens.
    scene.setShift(phone.matches ? 0 : 0.13 * ease(span(BUILD.fly, q)));
    scene.showPlans(!live);
    const risen = span(BUILD.rise, q);
    const h = risen >= 1 ? 1e5 : Math.floor((RISE_TOP * ease(risen)) / FLOOR) * FLOOR;
    scene.site({
      on: live,
      draw: span(BUILD.draw, q),
      fill: 0.3 * span(BUILD.fill, q),
      rise: h,
      dim: 0.6 * span([0.1, 0.22], q),
      tint: span([BUILD.rise[0], BUILD.rise[0] + 0.05], q),
    });

    innerEl.style.setProperty('--scrim', span([0.08, 0.2], q).toFixed(3));

    // One step at a time: in from the start side, out upwards as the next arrives.
    steps.forEach((li, i) => {
      const [a, b] = BUILD.steps[i];
      const inn = span([a, a + 0.035], q);
      const out = i < steps.length - 1 ? span([b - 0.03, b], q) : 0;
      const o = inn * (1 - out);
      li.style.opacity = o.toFixed(3);
      li.style.visibility = o > 0.001 ? 'visible' : 'hidden';
      li.style.transform = `translate(${((1 - inn) * 48).toFixed(1)}px, ${(-out * 28).toFixed(1)}px)`;
    });
    if (lede) lede.style.opacity = span(BUILD.lede, q).toFixed(3);
    if (head) head.style.opacity = phone.matches ? (1 - span(BUILD.sign, q)).toFixed(3) : '';

    // The grid reference under the camera, while the site is the subject.
    coord.style.opacity = span([0.18, 0.24], q).toFixed(3);
    if (live) {
      const [lng, lat] = toLngLat(site.cx, site.cz);
      coordValue.textContent = itmLabel(lat, lng);
    }

    // Dimension lines stretch from their middles; they leave as the buildings rise.
    const dShow = span(BUILD.dims, q) * (1 - span([BUILD.rise[0], BUILD.rise[0] + 0.05], q));
    dims.forEach((d, i) => {
      const A = shifted(scene.toScreen(...d.a), ox), B = shifted(scene.toScreen(...d.b), ox);
      if (!A || !B || dShow <= 0) { paths[i].setAttribute('d', ''); labels[i].style.opacity = 0; return; }
      const k = ease(span(BUILD.dims, q));
      const mxs = (A.x + B.x) / 2, mys = (A.y + B.y) / 2;
      const ax = mxs + (A.x - mxs) * k, ay = mys + (A.y - mys) * k, bx = mxs + (B.x - mxs) * k, by = mys + (B.y - mys) * k;
      const len = Math.hypot(bx - ax, by - ay) || 1, nx = (-(by - ay) / len) * 7, ny = ((bx - ax) / len) * 7;
      paths[i].setAttribute('d', `M${ax},${ay}L${bx},${by}M${ax - nx},${ay - ny}L${ax + nx},${ay + ny}M${bx - nx},${by - ny}L${bx + nx},${by + ny}`);
      paths[i].style.opacity = dShow;
      labels[i].style.opacity = dShow;
      labels[i].style.transform = `translate(${mxs + nx * 3.2}px, ${mys + ny * 3.2}px)`;
    });
    if (areaLabel) {
      const c = shifted(scene.toScreen(site.cx, site.cz), ox);
      areaLabel.style.opacity = c ? dShow : 0;
      if (c) areaLabel.style.transform = `translate(${c.x}px, ${c.y}px)`;
    }

    // Step 4: the white sign hangs from the site on a leader, its stage dashes filling to now.
    const s = span(BUILD.sign, q);
    sign.style.opacity = s.toFixed(3);
    sign.style.visibility = s > 0.001 ? 'visible' : 'hidden';
    track.style.setProperty('--now', Math.max(0.01, now * ease(span([BUILD.sign[0] + 0.02, BUILD.sign[1] + 0.04], q))).toFixed(3));
    const anchor = s > 0 ? shifted(scene.toScreen(site.cx, site.cz, 40), ox) : null;
    if (anchor) {
      const W = overlay.clientWidth, H = innerHeight, sw = sign.offsetWidth, sh = sign.offsetHeight;
      const x = phone.matches ? (W - sw) / 2 : Math.max(16, Math.min(anchor.x - sw - 120, W * 0.06));
      const y = phone.matches ? Math.max(84, H * 0.12) : Math.min(H - sh - 40, Math.max(110, anchor.y - sh * 0.75));
      sign.style.transform = `translate(${Math.round(x)}px, ${Math.round(y + (1 - s) * 16)}px)`;
      const ex = phone.matches ? x + sw / 2 : x + sw, ey = phone.matches ? y + sh : y + sh * 0.5;
      const lx = lerp(anchor.x, ex, ease(s)), ly = lerp(anchor.y, ey, ease(s));
      leader.setAttribute('x1', anchor.x); leader.setAttribute('y1', anchor.y);
      leader.setAttribute('x2', lx); leader.setAttribute('y2', ly);
      dot.setAttribute('cx', anchor.x); dot.setAttribute('cy', anchor.y);
      leader.style.opacity = dot.style.opacity = s;
    } else {
      leader.style.opacity = dot.style.opacity = 0;
    }
  }

  return { el, view, render, preload };
}

const shifted = (pt, ox) => (pt ? { x: pt.x - ox, y: pt.y } : null);

/** The editor's plan if it has a boundary; otherwise the most housing units among plans of up to 3 km². */
function pickPlan(features, id) {
  const usable = features.filter((f) => f.properties.boundary && f.geometry && !f.properties.national);
  if (id) {
    const chosen = usable.find((f) => f.id === id);
    if (chosen) return chosen;
  }
  const area = (b) => {
    const polys = b.type === 'Polygon' ? [b.coordinates] : b.type === 'MultiPolygon' ? b.coordinates : [];
    let a = 0;
    for (const poly of polys) {
      const r = poly[0].map(([lng, lat]) => toLocal(lng, lat));
      for (let i = 0; i < r.length - 1; i++) a += r[i][0] * r[i + 1][1] - r[i + 1][0] * r[i][1];
    }
    return Math.abs(a) / 2;
  };
  return usable
    .filter((f) => area(f.properties.boundary) <= MAX_SITE)
    .sort((a, b) => (Number(b.properties.units) || 0) - (Number(a.properties.units) || 0))[0] || null;
}

/**
 * Lenis smooths the wheel and hands ScrollTrigger the same time base. Overlays with their own scroll keep it,
 * Ctrl/⌘ + wheel stays the map's zoom, and the page stands still under the menu and the phone sheets.
 */
function smoothScroll() {
  const lenis = new Lenis({
    prevent: (node) => !!node.closest?.('dialog, .hero-drawer, .hero-suggest, .hero-card, .hero-filters, [data-lenis-prevent]'),
    virtualScroll: ({ event }) => !(event.ctrlKey || event.metaKey),
  });
  lenis.on('scroll', ScrollTrigger.update);
  gsap.ticker.add((time) => lenis.raf(time * 1000));
  gsap.ticker.lagSmoothing(0);
  const body = document.body;
  new MutationObserver(() => {
    if (body.classList.contains('menu-open') || body.classList.contains('sheet-open')) lenis.stop();
    else lenis.start();
  }).observe(body, { attributes: true, attributeFilter: ['class'] });
  window.LOGlenis = lenis;
}
