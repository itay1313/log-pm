/**
 * Raises the drawn city (inc/city.php) out of the ground, the way the home map opens: a wave that starts at one
 * point and runs outward, each house growing from its footprint to its height. Then the plan, if the drawing has
 * one: the boundary draws itself, the plate fills, the dimension lines stretch and the area counts up.
 *
 * Returns a GSAP timeline to place on a page's own, or to tie to the scroll (pass scrub: true, so it can also run
 * backwards); the drawing waits at its start. timeline.undo() puts the drawing back as it was served, for when
 * the page's motion is reverted.
 *
 * cityPulse() is for a city that already stands: it answers the scroll, and gives back a function that stops it.
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

const fmt = new Intl.NumberFormat('he-IL');
const NS = 'http://www.w3.org/2000/svg';

/**
 * Every house as its three ground corners and its height on the page, read back from the walls' path. Read once
 * per drawing, while it is still as it was served: after that the paths are being moved.
 */
const read = new WeakMap();
function houses(svg) {
  if (!read.has(svg)) read.set(svg, measure(svg));
  return read.get(svg);
}
function measure(svg) {
  return [...svg.querySelectorAll('.b')].map((g) => {
    const w = g.querySelector('.w');
    const p = w.getAttribute('d').match(/-?\d+(\.\d+)?/g).map(Number);
    // M left L corner L right L right-top ... (inc/city.php)
    const [lx, ly, cx, cy, rx, ry, , rty] = p;
    const floors = g.querySelector('.f');
    return { g, w, roof: g.querySelector('.r'), floors, lx, ly, cx, cy, rx, ry, h: ry - rty, d: w.getAttribute('d'), fd: floors?.getAttribute('d') };
  });
}

export function cityRise(svg, { from = [0.5, 0.62], duration = 2.6, scrub = false } = {}) {
  const tl = gsap.timeline();
  const restore = [];
  tl.undo = () => restore.forEach((f) => f());
  const box = svg.viewBox.baseVal;
  const list = houses(svg);
  if (list.length) {
    const ox = box.width * from[0], oy = box.height * from[1];
    const reach = Math.max(...list.map((b) => Math.hypot(b.cx - ox, b.cy - oy))) || 1;
    for (const b of list) b.at = Math.hypot(b.cx - ox, b.cy - oy) / reach;
    const front = 0.34; // how much of the run one house takes to stand up
    const set = (p) => {
      for (const b of list) {
        const t = gsap.utils.clamp(0, 1, (p * (1 + front) - b.at) / front);
        const e = 1 - (1 - t) ** 3;
        if (t >= 1) {
          if (b.done) continue;
          b.done = true;
          b.drawn = b.h;
          b.w.setAttribute('d', b.d);
          b.roof.removeAttribute('transform');
          if (b.floors) b.floors.style.opacity = '';
          continue;
        }
        b.done = false;
        const h = b.h * e;
        if (b.drawn !== undefined && Math.abs(h - b.drawn) < 0.05) continue; // nothing to redraw: most of a scrubbed run
        b.drawn = h;
        b.w.setAttribute('d', `M${b.lx} ${b.ly}L${b.cx} ${b.cy}L${b.rx} ${b.ry}L${b.rx} ${(b.ry - h).toFixed(1)}L${b.cx} ${(b.cy - h).toFixed(1)}L${b.lx} ${(b.ly - h).toFixed(1)}ZM${b.cx} ${b.cy}L${b.cx} ${(b.cy - h).toFixed(1)}`);
        b.roof.setAttribute('transform', `translate(0 ${(b.h - h).toFixed(1)})`);
        if (b.floors) b.floors.style.opacity = 0;
      }
    };
    const run = { p: 0 };
    tl.fromTo(run, { p: 0 }, { p: 1, duration, ease: 'power1.inOut', onUpdate: () => set(run.p), onStart: () => set(0) }, 0);
    tl.eventCallback('onComplete', () => set(1));
    restore.push(() => set(1));
    set(0);
  }

  const roads = svg.querySelector('.cty__roads');
  if (roads) tl.from(roads, { opacity: 0, duration: 1.2, ease: 'power2.out' }, 0);

  // The plan, after the city around it stands.
  const bound = svg.querySelector('.cty__bound');
  if (bound) {
    const at = list.length ? duration * 0.45 : 0;
    // The boundary is dashed, so it can't draw itself by its own dashes: a solid copy of it, drawn, is its mask.
    const id = `cty-draw-${Math.random().toString(36).slice(2, 8)}`;
    const mask = document.createElementNS(NS, 'mask');
    mask.id = id;
    mask.setAttribute('maskUnits', 'userSpaceOnUse');
    for (const k of ['x', 'y', 'width', 'height']) mask.setAttribute(k, box[k]);
    const pen = bound.cloneNode();
    pen.removeAttribute('class');
    pen.setAttribute('pathLength', '1');
    Object.assign(pen.style, { fill: 'none', stroke: 'white', strokeWidth: 8, strokeDasharray: 1, strokeDashoffset: 1 }); // the mask's pen
    mask.appendChild(pen);
    svg.appendChild(mask);
    bound.setAttribute('mask', `url(#${id})`);
    const unmask = () => { bound.removeAttribute('mask'); mask.remove(); };
    restore.push(unmask);
    // Tied to the scroll the pen can be taken back, so the mask stays until undo().
    tl.to(pen, { strokeDashoffset: 0, duration: 1.8, ease: 'power2.inOut', onComplete: scrub ? undefined : unmask }, at);

    const plate = svg.querySelector('.cty__plate');
    if (plate) tl.from(plate, { fillOpacity: 0, duration: 1.1, ease: 'power2.out' }, at + 0.9);
    const inner = svg.querySelectorAll('.cty__streets, .cty__rounds');
    if (inner.length) tl.from(inner, { opacity: 0, duration: 0.9 }, at + 1.2);

    svg.querySelectorAll('.cty__dim').forEach((dim, i) => {
      const t = at + 1.5 + i * 0.25;
      tl.fromTo(dim.querySelector('.cty__dim-line'), { strokeDasharray: 1, strokeDashoffset: 1 }, { strokeDashoffset: 0, duration: 1, ease: 'power3.inOut', clearProps: 'strokeDasharray,strokeDashoffset' }, t)
        .from(dim.querySelector('.cty__dim-ticks'), { opacity: 0, duration: 0.3 }, t + 0.8)
        .from(dim.querySelector('text'), { opacity: 0, duration: 0.6 }, t + 0.7);
    });

    const area = svg.querySelector('.cty__area');
    if (area) {
      const num = area.querySelector('[data-count]');
      const end = Number(num.dataset.count), o = { v: 0 };
      restore.push(() => { num.textContent = fmt.format(end); });
      tl.from(area, { opacity: 0, duration: 0.6 }, at + 1.4)
        .to(o, { v: end, duration: 1.4, ease: 'power3.out', onUpdate: () => { num.textContent = fmt.format(Math.round(o.v)); } }, at + 1.4);
    }
  }

  const pins = svg.querySelectorAll('.cty__pin');
  if (pins.length) tl.from(pins, { scale: 0, transformOrigin: '50% 50%', duration: 0.7, ease: 'back.out(2)', stagger: 0.12 }, bound ? '>-0.6' : duration * 0.7);

  return tl;
}

/**
 * A standing city answers the scroll. Two things move every house's height, towers more than houses:
 * where you are (the further the trigger has gone up the window, the taller the city), and how you are moving
 * (scrolling down stretches it, scrolling up ducks it, and it springs back when the wheel rests). The second runs
 * through the city as a wave from one point, so it reads as something passing through, not a zoom. A house that
 * is moving takes the full orange line (.is-lit, styles/city.css).
 *
 * ready: asked every frame; the city holds still until it says yes (while cityRise is still raising it).
 */
export function cityPulse(svg, { trigger = svg, from = [0.5, 0.62], ready = () => true } = {}) {
  const box = svg.viewBox.baseVal;
  const list = houses(svg);
  if (!list.length) return () => {};
  const ox = box.width * from[0], oy = box.height * from[1];
  const reach = Math.max(...list.map((b) => Math.hypot(b.cx - ox, b.cy - oy))) || 1;
  const tall = Math.max(...list.map((b) => b.h)) || 1;
  const LAG = 26; // frames the wave takes to cross the city
  for (const b of list) {
    b.lag = Math.round((Math.hypot(b.cx - ox, b.cy - oy) / reach) * LAG);
    b.amp = 0.22 + 0.5 * (b.h / tall);
    b.k = 1;
    // A tower's storeys: how far apart they are on the page, and how many (inc/city.php draws them from the ground up).
    if (b.floors) {
      const [, y1] = b.fd.match(/-?\d+(\.\d+)?/g).map(Number);
      b.step = b.ly - y1;
      b.n = (b.fd.match(/M/g) || []).length;
    }
  }
  const draw = (b, k) => {
    b.k = k;
    b.drawn = undefined; // cityRise's memory of this house no longer holds
    const lit = Math.abs(k - b.rest) > 0.07;
    if (lit !== b.lit) { b.lit = lit; b.g.classList.toggle('is-lit', lit); }
    if (Math.abs(k - 1) < 0.004) { // exactly as served
      b.w.setAttribute('d', b.d);
      b.roof.removeAttribute('transform');
      if (b.floors) b.floors.setAttribute('d', b.fd);
      return;
    }
    const h = b.h * k;
    const up = (y) => (y - h).toFixed(1);
    b.w.setAttribute('d', `M${b.lx} ${b.ly}L${b.cx} ${b.cy}L${b.rx} ${b.ry}L${b.rx} ${up(b.ry)}L${b.cx} ${up(b.cy)}L${b.lx} ${up(b.ly)}ZM${b.cx} ${b.cy}L${b.cx} ${up(b.cy)}`);
    b.roof.setAttribute('transform', `translate(0 ${(b.h - h).toFixed(1)})`);
    if (b.floors) {
      let d = '';
      for (let i = 1; i <= b.n; i++) {
        const z = b.step * i * k;
        d += `M${b.lx} ${(b.ly - z).toFixed(1)}L${b.cx} ${(b.cy - z).toFixed(1)}L${b.rx} ${(b.ry - z).toFixed(1)}`;
      }
      b.floors.setAttribute('d', d);
    }
  };

  const wave = new Float32Array(LAG + 1); // the last frames of the wheel's push, newest first
  let push = 0, felt = 0, gone = 0, live = false;
  for (const b of list) b.rest = 1;
  const tick = () => {
    felt += (push - felt) * 0.16;
    push *= 0.84; // the wheel lets go
    wave.copyWithin(1, 0);
    wave[0] = felt;
    let moving = Math.abs(push) > 0.002 || Math.abs(felt) > 0.002;
    for (const b of list) {
      b.rest = 1 + gone * b.amp * 0.85;
      const k = Math.max(0.12, b.rest + wave[b.lag] * b.amp);
      if (Math.abs(k - b.k) > 0.003) draw(b, k);
      if (Math.abs(wave[b.lag]) > 0.002) moving = true;
    }
    if (!moving) {
      for (const b of list) if (b.lit || Math.abs(b.k - b.rest) > 0.001) draw(b, b.rest);
      gsap.ticker.remove(tick);
      live = false;
    }
  };
  const st = ScrollTrigger.create({
    trigger, start: 'clamp(top top)', end: 'clamp(bottom top)',
    onUpdate: (self) => {
      if (!ready()) return;
      gone = self.progress;
      push = gsap.utils.clamp(-1, 1, self.getVelocity() / 2400);
      if (!live) { live = true; gsap.ticker.add(tick); }
    },
  });
  return () => {
    st.kill();
    gsap.ticker.remove(tick);
    for (const b of list) { b.rest = 1; draw(b, 1); }
  };
}
