/**
 * Magnet (after React Bits): the page's main calls to action lean towards the pointer as it comes near and settle
 * back as it leaves, a few pixels, never more. One listener for all of them. Only with a fine pointer and motion
 * allowed: on touch there is nothing to lean towards.
 */
import { gsap } from 'gsap';

export function magnet(selector, { strength = 0.2, reach = 48 } = {}) {
  const mm = gsap.matchMedia();
  mm.add('(prefers-reduced-motion: no-preference) and (pointer: fine)', () => {
    const items = [...document.querySelectorAll(selector)].map((el) => ({
      el,
      x: gsap.quickTo(el, 'x', { duration: 0.7, ease: 'power3.out' }),
      y: gsap.quickTo(el, 'y', { duration: 0.7, ease: 'power3.out' }),
      on: false,
    }));
    if (!items.length) return undefined;
    const move = (e) => {
      for (const it of items) {
        const r = it.el.getBoundingClientRect();
        if (!r.width) continue; // hidden: the menu's button while the menu is shut
        const dx = e.clientX - (r.left + r.width / 2);
        const dy = e.clientY - (r.top + r.height / 2);
        const near = Math.abs(dx) < r.width / 2 + reach && Math.abs(dy) < r.height / 2 + reach;
        if (!near && !it.on) continue;
        it.on = near;
        it.x(near ? dx * strength : 0);
        it.y(near ? dy * strength : 0);
      }
    };
    addEventListener('pointermove', move, { passive: true });
    return () => {
      removeEventListener('pointermove', move);
      gsap.set(items.map((it) => it.el), { clearProps: 'transform' });
    };
  });
  return mm;
}
