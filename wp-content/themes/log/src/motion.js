/**
 * The page's motion grammar (direction contract): one authored moment per sheet, never scattered effects.
 * - Each sheet draws its neatline once as it enters (CSS transition, triggered here).
 * - Title blocks and the home cartouche settle from an already-visible state with an exponential ease-out.
 * - Figures in legends count up once, from 70% of their value, so nothing ever reads as missing.
 * Reduced motion: nothing moves, nothing hides.
 */
import { gsap } from 'gsap';

export function initMotion() {
  if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  document.documentElement.classList.add('js-motion');
  const ease = 'expo.out';

  // Neatlines draw in as each sheet enters.
  const sheets = document.querySelectorAll('.section, .page-header');
  const io = new IntersectionObserver((entries) => {
    for (const e of entries) {
      if (!e.isIntersecting) continue;
      e.target.classList.add('is-drawn');
      io.unobserve(e.target);
      settle(e.target);
    }
  }, { threshold: 0.12 });
  sheets.forEach((s) => io.observe(s));

  function settle(sheet) {
    const parts = sheet.matches('.page-header')
      ? sheet.children
      : sheet.querySelectorAll(':scope > .container > :is(h2, .section__lede, .section__prose, .link-row, .process, .domains, .grid, .people, .values, .roles, .numbers, .expertise, .jobs, .clients-tabs, .partners-row, .btn)');
    if (!parts.length) return;
    gsap.fromTo(parts, { y: 18, opacity: 0.35 }, { y: 0, opacity: 1, duration: 1.1, ease, stagger: 0.07, clearProps: 'transform,opacity' });
    countUp(sheet);
  }

  function countUp(root) {
    const fmt = new Intl.NumberFormat('he-IL');
    for (const dd of root.querySelectorAll('.numbers dd, .project__numbers dd')) {
      const target = Number(dd.textContent.replace(/[^\d.]/g, ''));
      if (!target || target < 10) continue;
      const o = { v: target * 0.7 };
      gsap.to(o, { v: target, duration: 1.4, ease, onUpdate: () => (dd.textContent = fmt.format(Math.round(o.v))) });
    }
  }

  // Home: the cartouche settles once the page is up; its rows follow one another.
  const cartouche = document.querySelector('.cartouche');
  if (cartouche) {
    gsap.fromTo(cartouche, { y: 28 }, { y: 0, duration: 1.4, ease, delay: 0.15 });
    gsap.fromTo(cartouche.children, { y: 12, opacity: 0.25 }, { y: 0, opacity: 1, duration: 1.1, ease, stagger: 0.06, delay: 0.25, clearProps: 'transform,opacity' });
    const frame = document.querySelector('.hero-frame');
    if (frame) gsap.fromTo(frame, { clipPath: 'inset(0 0 100% 0)' }, { clipPath: 'inset(0 0 0% 0)', duration: 1.8, ease: 'power3.inOut', delay: 0.1, clearProps: 'clipPath' });
  }

  // Project page: the title block rises over the cover.
  const projectCopy = document.querySelector('.project__hero-copy');
  if (projectCopy) gsap.fromTo(projectCopy.children, { y: 22, opacity: 0.3 }, { y: 0, opacity: 1, duration: 1.3, ease, stagger: 0.08, delay: 0.2, clearProps: 'transform,opacity' });
}
