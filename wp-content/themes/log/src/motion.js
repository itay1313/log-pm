/**
 * The motion grammar (direction contract): guide signs mount, legends rise, arrows travel, the lane line draws.
 * - Legends (display headings) rise line by line inside masks: SplitText lines, power4.out.
 * - Plates mount from the gantry: a clip from the top edge opening downwards, expo.out.
 * - The lane line, one orange works marking down the start edge of every page, draws with the scroll.
 * - Lists arrive row by row as they come into view; pictures unroll from the top; figures count once from 70%.
 * - The menu gantry drops in and its destinations rise; leaving a page, a navy plate covers it and lifts on arrival.
 * Reduced motion: this module never initialises, nothing moves and nothing is hidden (the menu opens without tweens).
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';

gsap.registerPlugin(ScrollTrigger, SplitText);

const reduced = () => matchMedia('(prefers-reduced-motion: reduce)').matches;

export function initMotion() {
  if (reduced()) return;
  document.documentElement.classList.add('js-motion');
  pageCover();
  heroIntro();
  titleBands();
  sections();
  laneLine();
}

/** Display headings rise line by line inside masks. Re-splits when fonts land or the width changes. */
function rise(el, vars = {}) {
  if (!el) return null;
  return SplitText.create(el, {
    type: 'lines',
    mask: 'lines',
    autoSplit: true,
    onSplit(self) {
      gsap.set(el, { opacity: 1 });
      return gsap.from(self.lines, { yPercent: 112, duration: 1.15, stagger: 0.09, ease: 'power4.out', ...vars });
    },
  });
}

/** A plate mounts from the gantry: it opens downwards from its top edge. */
const mount = (targets, vars = {}) => gsap.fromTo(targets,
  { clipPath: 'inset(0% 0% 100% 0%)', y: -10 },
  { clipPath: 'inset(0% 0% 0% 0%)', y: 0, duration: 0.95, ease: 'expo.out', stagger: 0.07, clearProps: 'clipPath,transform', ...vars });

/* ---------------------------------------------------------------- home */

function heroIntro() {
  const hero = document.getElementById('hero');
  if (!hero) return;
  rise(hero.querySelector('.hero__title'), { delay: 0.15 });
  const lede = hero.querySelector('.hero__lede');
  if (lede) gsap.fromTo(lede, { opacity: 0, y: 14 }, { opacity: 1, y: 0, duration: 1, ease: 'expo.out', delay: 0.55, clearProps: 'transform' });
  // Plates hidden at load (the phone filter sheet) are released too, so they show when opened.
  const all = hero.querySelectorAll('.hero-search, .hero-filters .filters__group, .hero-filter-toggle');
  gsap.set(all, { opacity: 1 });
  mount([...all].filter((el) => el.offsetParent), { delay: 0.7 });
  const dist = hero.querySelector('.hero-dist');
  if (dist) { gsap.set(dist, { opacity: 1 }); mount(dist, { delay: 1.05 }); }
  const bar = hero.querySelectorAll('.hero-bar > *');
  gsap.set(bar, { opacity: 1 });
  gsap.from(bar, { x: -16, opacity: 0, duration: 0.8, stagger: 0.06, ease: 'expo.out', delay: 1.2, clearProps: 'transform' });
  // Leaving the map is the drive's business (home/drive.js): it fades the map's tools as the camera is taken.
}

/* ---------------------------------------------------------------- inner pages */

function titleBands() {
  for (const band of document.querySelectorAll('.page-header, .about-hero__copy, .careers-hero__copy, .control-hero__strip, .project__hero-copy')) {
    const title = band.querySelector('h1');
    rise(title, { delay: 0.1 });
    const rest = [...band.querySelectorAll(':scope > :not(h1), :scope > .container > :not(h1)')].filter((el) => !el.contains(title));
    gsap.set(rest, { opacity: 1 });
    gsap.from(rest, { opacity: 0, y: 16, duration: 1, stagger: 0.08, ease: 'expo.out', delay: 0.45, clearProps: 'transform' });
  }
}

function sections() {
  // Section legends
  for (const h of document.querySelectorAll('.site-main .section__title, .site-main .section h2:not(.section__title), .featured__title, .national__title')) {
    if (h.closest('.hero, .page-header, .drive-country')) continue; // the drive lights its own legend
    SplitText.create(h, {
      type: 'lines', mask: 'lines', autoSplit: true,
      onSplit: (self) => gsap.from(self.lines, { yPercent: 112, duration: 1.05, stagger: 0.08, ease: 'power4.out', scrollTrigger: { trigger: h, start: 'top 88%', once: true } }),
    });
  }

  // Rows of a legend arrive one after another, each with its rule.
  const rows = '.domain, .jobs__item, .expertise li, .values__item, .roles__item, .process li, .search-results li, .knowledge-list li, .national__list li, .facts > div, .numbers > div, .project__numbers > div, .timeline li, .office, .partners li';
  rowsIn(rows);

  // Pictures and plates unroll from the top as they come near.
  const media = gsap.utils.toArray('.card__media, .person__photo, .news__media, .about-hero__media, .careers-hero__media, .control-hero__media, .offices__item, .minimap, .gallery__grid figure');
  ScrollTrigger.batch(media, {
    start: 'top 92%', once: true,
    onEnter: (batch) => gsap.fromTo(batch, { clipPath: 'inset(0% 0% 100% 0% round 10px)' }, { clipPath: 'inset(0% 0% 0% 0% round 10px)', duration: 1.2, stagger: 0.08, ease: 'expo.out', clearProps: 'clipPath' }),
  });

  // Prose and ledes settle from an already-visible state.
  const settle = gsap.utils.toArray('.site-main .section__lede, .site-main .section__prose, .story-block, .link-row, .section > .container > .btn, .card__body').filter((el) => !el.closest('.drive-country'));
  ScrollTrigger.batch(settle, {
    start: 'top 92%', once: true,
    onEnter: (batch) => gsap.fromTo(batch, { opacity: 0.2, y: 22 }, { opacity: 1, y: 0, duration: 1.1, stagger: 0.07, ease: 'expo.out', clearProps: 'transform,opacity' }),
  });

  // Figures count once, from 70% of their value, so nothing ever reads as missing.
  const fmt = new Intl.NumberFormat('he-IL');
  for (const dd of document.querySelectorAll('.numbers dd, .project__numbers dd, .domain__count')) {
    const target = Number(dd.textContent.replace(/[^\d.]/g, ''));
    if (!target || target < 10) continue;
    const text = dd.textContent;
    const o = { v: target * 0.7 };
    ScrollTrigger.create({
      trigger: dd, start: 'top 92%', once: true,
      onEnter: () => gsap.to(o, { v: target, duration: 1.4, ease: 'expo.out', onUpdate: () => (dd.textContent = text.replace(/[\d,.]+/, fmt.format(Math.round(o.v)))) }),
    });
  }
}

function rowsIn(selector) {
  const items = gsap.utils.toArray(selector).filter((el) => !el.closest('.hero, .gantry'));
  if (!items.length) return;
  gsap.set(items, { opacity: 0, y: 26 });
  ScrollTrigger.batch(items, {
    start: 'top 94%', once: true,
    onEnter: (batch) => gsap.to(batch, { opacity: 1, y: 0, duration: 0.9, stagger: 0.06, ease: 'expo.out', clearProps: 'transform,opacity' }),
  });
}

/** The lane line (main.js puts it down) is drawn as far as the reader has come. */
function laneLine() {
  const line = document.querySelector('.lane-line');
  if (!line) return;
  gsap.fromTo(line, { scaleY: 0 }, { scaleY: 1, ease: 'none', scrollTrigger: { trigger: line, start: 'top 75%', end: 'bottom 75%', scrub: 0.6 } });
}

/* ---------------------------------------------------------------- menu */

export function menuIn(menu) {
  const plate = menu.querySelector('.gantry__plate');
  const names = menu.querySelectorAll('.dest__name > span');
  const rest = menu.querySelectorAll('.gantry__top > *, .dest__dist, .dest__row .arrow, .gantry__side > *');
  gsap.killTweensOf([plate, names, rest]);
  return gsap.timeline()
    .fromTo(plate, { clipPath: 'inset(0% 0% 100% 0% round 20px)' }, { clipPath: 'inset(0% 0% 0% 0% round 20px)', duration: 0.8, ease: 'expo.inOut', clearProps: 'clipPath' })
    .fromTo(names, { yPercent: 110 }, { yPercent: 0, duration: 1, stagger: 0.055, ease: 'power4.out' }, 0.32)
    .fromTo(rest, { opacity: 0, y: 12 }, { opacity: 1, y: 0, duration: 0.7, stagger: 0.025, ease: 'expo.out', clearProps: 'transform' }, 0.5);
}

export function menuOut(menu) {
  const plate = menu.querySelector('.gantry__plate');
  gsap.killTweensOf(plate);
  return gsap.fromTo(plate, { clipPath: 'inset(0% 0% 0% 0% round 20px)' }, { clipPath: 'inset(100% 0% 0% 0% round 20px)', duration: 0.5, ease: 'expo.in' })
    .then(() => gsap.set(plate, { clearProps: 'clipPath' }));
}

/* ---------------------------------------------------------------- page change */

/** Leaving a page a navy plate covers it from the bottom; on arrival it lifts away to the top. */
function pageCover() {
  const html = document.documentElement;
  const cover = document.createElement('div');
  cover.className = 'page-cover';
  cover.setAttribute('aria-hidden', 'true');
  cover.innerHTML = '<span class="page-cover__mark"><span class="logo__ring"></span></span>';
  document.body.appendChild(cover);

  if (html.classList.contains('is-covered')) {
    try { sessionStorage.removeItem('log-cover'); } catch { /* storage blocked */ }
    gsap.set(cover, { visibility: 'visible', clipPath: 'inset(0% 0% 0% 0%)' });
    html.classList.remove('is-covered');
    gsap.to(cover, { clipPath: 'inset(0% 0% 100% 0%)', duration: 0.85, ease: 'expo.inOut', delay: 0.05, onComplete: () => gsap.set(cover, { visibility: 'hidden' }) });
  }

  document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href]');
    if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if ((a.target && a.target !== '_self') || a.hasAttribute('download')) return;
    const url = new URL(a.href, location.href);
    if (url.origin !== location.origin || !/^https?:/.test(url.protocol)) return;
    if (url.pathname === location.pathname && url.search === location.search) return; // same page, a #hash
    if (/\/wp-(admin|login)|\.(pdf|zip|docx?|xlsx?|pptx?)$/i.test(url.pathname)) return;
    e.preventDefault();
    try { sessionStorage.setItem('log-cover', '1'); } catch { /* storage blocked */ }
    gsap.set(cover, { visibility: 'visible' });
    gsap.fromTo(cover, { clipPath: 'inset(100% 0% 0% 0%)' }, { clipPath: 'inset(0% 0% 0% 0%)', duration: 0.55, ease: 'expo.in', onComplete: () => location.assign(url.href) });
  });

  // Back from the bfcache: the page must not come back covered.
  addEventListener('pageshow', (e) => {
    if (!e.persisted) return;
    gsap.set(cover, { visibility: 'hidden', clipPath: 'inset(0% 0% 100% 0%)' });
    html.classList.remove('is-covered');
  });
}
