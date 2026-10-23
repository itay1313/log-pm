/**
 * Smooth scrolling for every page (motion allowed only; motion.js starts it). Lenis eases the wheel and hands
 * ScrollTrigger the same time base, so scrubbed scenes and the page move as one.
 *
 * Left alone: anything with its own scroll (dialogs, the menu, map drawers and sheets, suggestion lists), a
 * projects map that has the wheel (its zoom), Ctrl/⌘ + wheel (the home map's zoom), and touch, which stays
 * native. In-page anchors stay native too, so a skip link still moves focus. The page stands still while the
 * menu or a phone sheet is open.
 */
import Lenis from 'lenis';
import 'lenis/dist/lenis.css';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

const OWN_SCROLL = 'dialog, .hero-drawer, .hero-suggest, .hero-card, .hero-filters, [data-lenis-prevent]';

export function initSmooth() {
  if (window.LOGlenis) return window.LOGlenis;
  const lenis = new Lenis({
    prevent: (node) => {
      if (!node.closest) return false;
      if (node.closest(OWN_SCROLL)) return true;
      const map = node.closest('.maplibregl-map');
      return !!map && (map.classList.contains('is-armed') || !!map.closest('#projects-map'));
    },
    virtualScroll: ({ event }) => !(event.ctrlKey || event.metaKey),
  });
  lenis.on('scroll', ScrollTrigger.update);
  gsap.ticker.add((time) => lenis.raf(time * 1000));
  gsap.ticker.lagSmoothing(0);

  const body = document.body;
  const hold = () => (body.classList.contains('menu-open') || body.classList.contains('sheet-open') ? lenis.stop() : lenis.start());
  new MutationObserver(hold).observe(body, { attributes: true, attributeFilter: ['class'] });
  hold();

  window.LOGlenis = lenis;
  return lenis;
}
