/**
 * The logo assembles once per visit, in the header on the first page load:
 * the O's four quarters drive into the roundabout (counter-clockwise, the way traffic turns here)
 * and lock, then the orange sweeps across the grey, the plan turning into a road.
 * With reduced motion the logo is simply there, finished.
 *
 * The markup comes from inc/brand.php: quarters carry data-q, and two clip rects (data-sweep="on"/"off")
 * show the orange pieces and their grey twins. At rest the orange covers everything, so without JS
 * the logo is already final.
 */
import { gsap } from 'gsap';

const SEEN = 'log-logo';
// Each quarter's outward direction once it's in place (screen y points down).
const OUT = { 1: [1, -1], 2: [-1, -1], 3: [-1, 1], 4: [1, 1] };

export function assemble(logo) {
  const html = document.documentElement;
  const svg = logo.querySelector('svg');
  const quarters = [...(svg?.querySelectorAll('[data-q]') ?? [])].sort((a, b) => a.dataset.q - b.dataset.q);
  const on = svg?.querySelector('[data-sweep="on"]');
  const off = svg?.querySelector('[data-sweep="off"]');
  const done = () => html.classList.remove('logo-intro');
  if (quarters.length !== 4 || !on || !off) return done();

  try { sessionStorage.setItem(SEEN, '1'); } catch { /* storage blocked: it plays again next page */ }

  const mm = gsap.matchMedia();
  mm.add({ motion: '(prefers-reduced-motion: no-preference)', still: '(prefers-reduced-motion: reduce)' }, (ctx) => {
    if (ctx.conditions.still) return done();

    const { width: W } = svg.viewBox.baseVal;
    // The roads split the O symmetrically, so the quarters' joint box is centred on the O.
    const boxes = quarters.map((q) => q.getBBox());
    const x0 = Math.min(...boxes.map((b) => b.x));
    const x1 = Math.max(...boxes.map((b) => b.x + b.width));
    const y0 = Math.min(...boxes.map((b) => b.y));
    const y1 = Math.max(...boxes.map((b) => b.y + b.height));
    const origin = `${(x0 + x1) / 2} ${(y0 + y1) / 2}`;
    const reach = (x1 - x0) * 0.16; // how far out a quarter starts

    // Before the first frame: quarters a quarter-turn back and pushed out, everything grey.
    quarters.forEach((q) => {
      const [dx, dy] = OUT[q.dataset.q];
      // A quarter that starts a quarter-turn clockwise back sits one place on, so it is pushed out from there.
      gsap.set(q, { svgOrigin: origin, rotation: 90, x: -dy * reach, y: dx * reach, opacity: 0 });
    });
    gsap.set(on, { attr: { width: 0 } });
    gsap.set(off, { attr: { x: 0, width: W } });
    done();

    const tl = gsap.timeline({ delay: 0.15 });
    tl.to(quarters, { rotation: 0, x: 0, y: 0, duration: 1.25, ease: 'expo.out', stagger: 0.09 }, 0)
      .to(quarters, { opacity: 1, duration: 0.35, ease: 'power1.out', stagger: 0.09 }, 0)
      .to(on, { attr: { width: W }, duration: 0.95, ease: 'power3.inOut' }, 0.8)
      .to(off, { attr: { x: W, width: 0 }, duration: 0.95, ease: 'power3.inOut' }, 0.8)
      .add(() => gsap.set(quarters, { clearProps: 'transform,opacity' }));

    // Leaving reduced motion mid-way (or the context being reverted) lands on the finished logo.
    return () => {
      tl.kill();
      gsap.set(quarters, { clearProps: 'transform,opacity' });
      gsap.set(on, { attr: { width: W } });
      gsap.set(off, { attr: { x: W, width: 0 } });
    };
  });
}
