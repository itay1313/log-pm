/**
 * Raises the drawn city (inc/city.php) out of the ground, the way the home map opens: a wave that starts at one
 * point and runs outward, each house growing from its footprint to its height. Then the plan, if the drawing has
 * one: the boundary draws itself, the plate fills, the dimension lines stretch and the area counts up.
 *
 * Returns a GSAP timeline to place on a page's own, or to tie to the scroll (pass scrub: true, so it can also run
 * backwards); the drawing waits at its start. timeline.undo() puts the drawing back as it was served, for when
 * the page's motion is reverted.
 */
import { gsap } from 'gsap';

const fmt = new Intl.NumberFormat('he-IL');
const NS = 'http://www.w3.org/2000/svg';

/** Every house as its three ground corners and its height on the page, read back from the walls' path. */
function houses(svg) {
  return [...svg.querySelectorAll('.b')].map((g) => {
    const w = g.querySelector('.w');
    const p = w.getAttribute('d').match(/-?\d+(\.\d+)?/g).map(Number);
    // M left L corner L right L right-top ... (inc/city.php)
    const [lx, ly, cx, cy, rx, ry, , rty] = p;
    return { w, roof: g.querySelector('.r'), floors: g.querySelector('.f'), lx, ly, cx, cy, rx, ry, h: ry - rty, d: w.getAttribute('d') };
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
          b.w.setAttribute('d', b.d);
          b.roof.removeAttribute('transform');
          if (b.floors) b.floors.style.opacity = '';
          continue;
        }
        b.done = false;
        const h = b.h * e;
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
