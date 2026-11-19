/**
 * Three text effects after React Bits (DepthText, WarpText, MaskedHeading), rebuilt small in GSAP and CSS.
 * Each is used once in the brand book, where it says something: none of them is a default.
 *
 * - depthText: the headline is a plan that rises into a building. Copies of the words stack back in space, in
 *   orange, and the block leans towards the pointer. It grows from flat.
 * - warpText: heat over asphalt. The words arrive bent through an SVG displacement and settle straight; a pointer
 *   passing over stirs them again.
 * - maskedText: a photograph shows through the letters and drifts as the page scrolls.
 *
 * Callers run these inside gsap.matchMedia (no reduced motion); each returns what the caller needs to drive it and
 * an undo that puts the plain text back.
 */
import { gsap } from 'gsap';

/** Stack the element's words back in space. Returns { stage, undo }; tween stage's --grow from 0 to 1. */
export function depthText(el, { layers = 18 } = {}) {
  const html = el.innerHTML;
  const copies = Array.from({ length: layers }, (_, i) => `<span style="--i:${layers - i}">${html}</span>`).join('');
  el.classList.add('fx-depth');
  el.innerHTML = `<span class="fx-depth__stage" style="--layers:${layers}"><span class="fx-depth__layers" aria-hidden="true">${copies}</span><span class="fx-depth__face">${html}</span></span>`;
  const stage = el.firstElementChild;
  return { stage, undo: () => { el.classList.remove('fx-depth'); el.innerHTML = html; } };
}

/** Lean a depthText stage towards the pointer while it is over `area`. Returns an undo. */
export function depthTilt(stage, area, { base = [-4, 7], reach = [5, 9] } = {}) {
  if (!matchMedia('(pointer: fine)').matches) return () => {};
  const rx = gsap.quickTo(stage, 'rotationX', { duration: 0.9, ease: 'power3.out' });
  const ry = gsap.quickTo(stage, 'rotationY', { duration: 0.9, ease: 'power3.out' });
  const move = (e) => {
    const r = area.getBoundingClientRect();
    rx(base[0] - ((e.clientY - r.top) / r.height - 0.5) * 2 * reach[0]);
    ry(base[1] + ((e.clientX - r.left) / r.width - 0.5) * 2 * reach[1]);
  };
  const leave = () => { rx(base[0]); ry(base[1]); };
  area.addEventListener('pointermove', move);
  area.addEventListener('pointerleave', leave);
  return () => { area.removeEventListener('pointermove', move); area.removeEventListener('pointerleave', leave); };
}

let warpFilter = null;
function warp() {
  if (warpFilter) return warpFilter;
  const ns = 'http://www.w3.org/2000/svg';
  const svg = document.createElementNS(ns, 'svg');
  svg.setAttribute('aria-hidden', 'true');
  svg.style.cssText = 'position:absolute;width:0;height:0';
  svg.innerHTML = '<filter id="fx-warp" x="-10%" y="-40%" width="120%" height="180%" color-interpolation-filters="sRGB"><feTurbulence type="fractalNoise" baseFrequency="0.006 0.05" numOctaves="2" seed="7" result="n"/><feDisplacementMap in="SourceGraphic" in2="n" scale="0" xChannelSelector="R" yChannelSelector="G"/></filter>';
  document.body.append(svg);
  warpFilter = { noise: svg.querySelector('feTurbulence'), map: svg.querySelector('feDisplacementMap') };
  return warpFilter;
}

/**
 * A tween that brings the element in bent and lets it settle straight. Add it to a timeline, or play it alone.
 * The filter is taken off when it ends, so settled text is ordinary text again.
 */
export function warpText(el, { from = 70, duration = 2 } = {}) {
  const { noise, map } = warp();
  const o = { scale: from, f: 0.05 };
  return gsap.to(o, {
    scale: 0, f: 0.012, duration, ease: 'expo.out',
    onStart: () => { el.style.filter = 'url(#fx-warp)'; },
    onUpdate: () => { map.setAttribute('scale', o.scale.toFixed(2)); noise.setAttribute('baseFrequency', `0.006 ${o.f.toFixed(4)}`); },
    onComplete: () => { el.style.filter = ''; },
  });
}

/** Let a photograph show through the letters and drift with the scroll. Returns an undo. */
export function maskedText(el, url, trigger = el) {
  if (!url) return () => {};
  el.classList.add('fx-masked');
  el.style.setProperty('--fx-image', `url("${url}")`);
  const drift = gsap.fromTo(el, { backgroundPosition: '50% 20%' }, { backgroundPosition: '50% 80%', ease: 'none', scrollTrigger: { trigger, start: 'top bottom', end: 'bottom top', scrub: true } });
  return () => { drift.scrollTrigger?.kill(); drift.kill(); el.classList.remove('fx-masked'); el.style.removeProperty('--fx-image'); el.style.removeProperty('background-position'); };
}
