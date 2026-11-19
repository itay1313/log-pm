/**
 * CountUp (after React Bits), for figures: a number counts up once as it comes into view, from 70% of its value, so
 * it never reads as missing. Hebrew grouping (Intl he-IL), the figure's own decimals, and the words around it are
 * kept, and it always ends on exactly the text the page was served with. Without motion the figure simply stands.
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

const NUM = /\d[\d,]*(?:\.\d+)?/;

export function countUp(selector) {
  const mm = gsap.matchMedia();
  mm.add('(prefers-reduced-motion: no-preference)', () => {
    for (const el of document.querySelectorAll(selector)) {
      const text = el.textContent;
      const m = text.match(NUM);
      const target = m ? Number(m[0].replace(/,/g, '')) : 0;
      if (!(target >= 10)) continue; // small numbers read better standing still
      const decimals = (m[0].split('.')[1] || '').length;
      const fmt = new Intl.NumberFormat('he-IL', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
      const o = { v: target * 0.7 };
      const paint = () => { el.textContent = text.replace(NUM, fmt.format(o.v)); };
      ScrollTrigger.create({
        trigger: el, start: 'top 92%', once: true,
        onEnter: () => { paint(); gsap.to(o, { v: target, duration: 1.4, ease: 'expo.out', onUpdate: paint, onComplete: () => { el.textContent = text; } }); },
      });
    }
  });
  return mm;
}
