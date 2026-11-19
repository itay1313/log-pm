/**
 * Lines draw themselves the way a road is laid: from the start side to the end, then the crossing line cuts
 * through, then the dot lands, last, with a small overshoot (the headline's period does the same).
 * Circles close counter-clockwise from the top, the way traffic turns in a roundabout here; a split circle's
 * quarters follow one another; a dashed circle is uncovered through its mask so the dashes never break.
 *
 * Works on .ln and .circ with data-draw (inc/lines.php): "in" draws once as it comes into view, "scrub" ties
 * the line to the scroll. With reduced motion nothing is hidden and nothing moves.
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { DrawSVGPlugin } from 'gsap/DrawSVGPlugin';

gsap.registerPlugin(ScrollTrigger, DrawSVGPlugin);

/** The clip that hides a line, measured in px so it tweens cleanly to the open inset(0). */
function hiddenClip(el) {
  if (el.classList.contains('ln--y')) return `inset(0px 0px ${el.offsetHeight}px 0px)`;
  const w = el.offsetWidth;
  return getComputedStyle(el).direction === 'rtl' ? `inset(0px 0px 0px ${w}px)` : `inset(0px ${w}px 0px 0px)`;
}

function line(el) {
  const scrub = el.dataset.draw === 'scrub';
  const x = el.querySelector('.ln__x');
  const dot = el.querySelector('.ln__dot');
  const len = el.classList.contains('ln--y') ? el.offsetHeight : el.offsetWidth;
  const tl = gsap.timeline({
    scrollTrigger: scrub
      ? { trigger: el, start: 'top 90%', end: 'bottom 45%', scrub: 0.6 }
      : { trigger: el, start: 'top 88%', once: true },
  });
  tl.fromTo(el, { clipPath: hiddenClip(el) }, {
    clipPath: 'inset(0px 0px 0px 0px)', ease: scrub ? 'none' : 'power3.inOut',
    duration: gsap.utils.clamp(0.7, 1.5, len / 800), clearProps: 'clipPath',
  });
  if (x) tl.fromTo(x, { scale: 0 }, { scale: 1, duration: 0.6, ease: 'power3.out', clearProps: 'transform' }, '-=0.2');
  if (dot) tl.fromTo(dot, { scale: 0 }, { scale: 1, duration: 0.6, ease: 'back.out(3)', clearProps: 'transform' }, '-=0.25');
}

function circle(el) {
  const strokes = [...el.querySelectorAll('svg > :is(circle, path)')].filter((s) => !s.closest('mask'));
  const mask = el.querySelector('.circ__mask');
  const inner = el.querySelector('.circ__in');
  const tl = gsap.timeline({ scrollTrigger: { trigger: el, start: 'top 88%', once: true } });
  // Counter-clockwise: each path runs clockwise, so it is drawn back from its end.
  const ccw = { drawSVG: '100% 100%' };
  const drawn = { drawSVG: '0% 100%', duration: 1.1, ease: 'power3.inOut' };
  if (mask) tl.fromTo(mask, ccw, drawn);
  else if (strokes.length > 1) tl.fromTo([...strokes].reverse(), ccw, { ...drawn, duration: 0.55, stagger: 0.12 });
  else tl.fromTo(strokes, ccw, drawn);
  if (inner) tl.from(inner, { opacity: 0, scale: 0.9, duration: 0.7, ease: 'expo.out', clearProps: 'opacity,transform' }, '-=0.5');
}

/**
 * The split circle's markup for a page built in JS (the page cover), the same geometry as log_circle( 'split' ):
 * four arcs on r=49, cut 22° at each quarter, orange at top right and bottom left.
 */
export function splitCircle() {
  const pt = (deg) => `${(50 + 49 * Math.sin(deg * Math.PI / 180)).toFixed(3)} ${(50 - 49 * Math.cos(deg * Math.PI / 180)).toFixed(3)}`;
  const arc = (from, cls) => `<path class="${cls}" d="M${pt(from + 11)} A49 49 0 0 1 ${pt(from + 79)}"/>`;
  return `<span class="circ circ--split"><svg viewBox="0 0 100 100" aria-hidden="true" focusable="false">${arc(0, 'circ__on')}${arc(90, 'circ__off')}${arc(180, 'circ__on')}${arc(270, 'circ__off')}</svg></span>`;
}

/** Draw every line and circle under root. Returns the matchMedia context, so a caller can revert it. */
export function drawLines(root = document) {
  const mm = gsap.matchMedia();
  mm.add('(prefers-reduced-motion: no-preference)', () => {
    root.querySelectorAll('.ln[data-draw]').forEach(line);
    root.querySelectorAll('.circ[data-draw]').forEach(circle);
  });
  return mm;
}
