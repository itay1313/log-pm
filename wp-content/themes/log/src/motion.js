/**
 * The motion grammar (direction contract): guide signs mount, legends rise, arrows travel, the lane line draws.
 * - Legends (display headings) rise line by line inside masks: SplitText lines, power4.out.
 * - Plates mount from the gantry: a clip from the top edge opening downwards, expo.out.
 * - The lane line, one thin grey line down the start edge of every page, turns orange as far as the reader has come.
 * - Lines and circles of the line system draw in from the start side; the dot lands last (lines/draw.js).
 * - Lists arrive row by row as they come into view; pictures unroll from the top; figures count once from 70%.
 * - The main calls to action lean towards a fine pointer; the client strip runs faster as the page scrolls (src/fx).
 * - The menu gantry drops in and its destinations rise; leaving a page, a navy plate covers it and lifts on arrival.
 * Reduced motion: this module never initialises, nothing moves and nothing is hidden (the menu opens without tweens).
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';
import { initSmooth } from './smooth.js';
import { drawLines, splitCircle } from './lines/draw.js';
import { countUp } from './fx/count.js';
import { magnet } from './fx/magnet.js';
import { velocityStrip } from './fx/velocity.js';

gsap.registerPlugin(ScrollTrigger, SplitText);

const reduced = () => matchMedia('(prefers-reduced-motion: reduce)').matches;

export function initMotion() {
  if (reduced()) return;
  document.documentElement.classList.add('js-motion');
  initSmooth();
  remeasure();
  rollLabels();
  pageCover();
  heroIntro();
  openers();
  sections();
  laneLine();
  drawLines();
  footer();
  // After React Bits, in GSAP (src/fx): the main calls lean to the pointer; the client strip answers the scroll.
  magnet('.btn--primary, .btn--works, .site-header__talk');
  liquid('.btn, .menu-toggle, .gantry__close, .hero-filter-toggle');
  velocityStrip('.logo-run');
}

/**
 * Liquid glass (base.css): the highlight inside a button follows the pointer. One listener for the page; it writes
 * two custom properties on the button under the pointer and nothing else, so it costs no layout.
 */
function liquid(selector) {
  if (!matchMedia('(pointer: fine)').matches) return;
  addEventListener('pointermove', (e) => {
    const el = e.target.closest?.(selector);
    if (!el) return;
    const r = el.getBoundingClientRect();
    el.style.setProperty('--lx', `${((e.clientX - r.left) / r.width * 100).toFixed(1)}%`);
    el.style.setProperty('--ly', `${((e.clientY - r.top) / r.height * 100).toFixed(1)}%`);
  }, { passive: true });
}

/**
 * Scroll scenes measure where they start and end once; when the page grows after load (the map falling back to
 * its list, a late image, the drive's chapters switching on), they measure again.
 */
function remeasure() {
  let h = document.body.scrollHeight, t = 0;
  new ResizeObserver(() => {
    if (Math.abs(document.body.scrollHeight - h) < 2) return;
    h = document.body.scrollHeight;
    clearTimeout(t);
    t = setTimeout(() => ScrollTrigger.refresh(), 150);
  }).observe(document.body);
}

/**
 * Pills roll their label on hover: the words slide up out of the pill and the same words follow from below.
 * Only the first run of text is doubled; arrows and counts stay put. The copy is hidden from assistive tech.
 */
function rollLabels() {
  for (const el of document.querySelectorAll('.btn, .site-header__talk, .menu-toggle__label, .gantry__close > span, .hero-all-link, .twin-nav__name')) {
    const text = [...el.childNodes].find((n) => n.nodeType === 3 && n.textContent.trim());
    if (!text || el.querySelector('.roll')) continue;
    const roll = document.createElement('span');
    roll.className = 'roll';
    const label = text.textContent.trim();
    roll.innerHTML = '<span></span><span aria-hidden="true"></span>';
    roll.firstChild.textContent = label;
    roll.lastChild.textContent = label;
    text.replaceWith(roll);
  }
}

/**
 * Display headings rise line by line inside masks. Re-splits when the width changes. The first split waits for
 * the webfonts: lines measured in the wider fallback face break in the wrong places.
 */
function rise(el, { delay = 0, ...vars } = {}) {
  if (!el) return null;
  return document.fonts.ready.then(() => SplitText.create(el, {
    type: 'lines',
    mask: 'lines',
    autoSplit: true,
    onSplit(self) {
      gsap.set(el, { opacity: 1 });
      const tl = gsap.timeline({ delay });
      tl.from(self.lines, { yPercent: 112, duration: 1.15, stagger: 0.09, ease: 'power4.out', ...vars });
      // The junction lands: the orange period arrives after its words, from nothing to full, with a small overshoot.
      const dot = el.querySelector('.period--dot');
      if (dot) tl.from(dot, { scale: 0, transformOrigin: '50% 80%', duration: 0.7, ease: 'back.out(3)', clearProps: 'transform' }, '-=0.45');
      return tl;
    },
  }));
}

/** A plate mounts from the gantry: it opens downwards from its top edge. */
const mount = (targets, vars = {}) => gsap.fromTo(targets,
  { clipPath: 'inset(0% 0% 100% 0%)', y: -10 },
  { clipPath: 'inset(0% 0% 0% 0%)', y: 0, duration: 0.95, ease: 'expo.out', stagger: 0.07, clearProps: 'clipPath,transform', ...vars });

/* ---------------------------------------------------------------- home */

function heroIntro() {
  const hero = document.getElementById('hero');
  if (!hero) return;
  // On the first visit the roundabout opens the page (home/roundabout.js); the headline rises as its night lifts.
  if (document.documentElement.classList.contains('ra-intro')) {
    document.addEventListener('log:opened', () => heroIn(hero), { once: true });
  } else heroIn(hero);
}

function heroIn(hero) {
  // The headline comes up out of a blur, word by word, as if the lens found it. Words, not lines: the browser keeps
  // the wrapping (SplitText's line detection breaks Hebrew headlines in the wrong places).
  const title = hero.querySelector('.hero__title');
  if (title) document.fonts.ready.then(() => SplitText.create(title, {
    type: 'words',
    onSplit(self) {
      gsap.set(title, { opacity: 1 });
      // The blur paints every word every frame: touch screens get the rise alone.
      const lens = matchMedia('(pointer: coarse)').matches ? {} : { filter: 'blur(14px)' };
      return gsap.from(self.words, { yPercent: 38, opacity: 0, ...lens, duration: 1.4, stagger: 0.07, delay: 0.15, ease: 'expo.out' });
    },
  }));
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

/**
 * The page opener (template-parts/opener.php) draws itself the way the book's lines do: the line from the start
 * side, the crossing line down through it, the dot last with a small overshoot. The page's number comes up from
 * under the line, the title rises on it, and what stands under the line settles. As the page scrolls on, the number
 * sinks slowly back under the line. Transforms and opacity only.
 */
function openers() {
  // Arriving from the home map, a project's page opens under the wipe (hero/transition.js): wait for it to clear.
  const wait = document.documentElement.dataset.wiped ? 1.15 : 0.1;
  for (const op of document.querySelectorAll('.opener')) {
    const q = (sel) => op.querySelector(sel);
    const [h, v, dot, n, place] = ['.opener__h', '.opener__v', '.opener__dot', '.opener__n', '.opener__place > :is(img, video, .project__art)'].map(q);
    const rest = op.querySelectorAll('.opener__kicker, .opener__text > *, .opener__side > *');
    gsap.set([q('.opener__cross'), n, ...rest], { opacity: 1 });
    const tl = gsap.timeline({ delay: wait });
    tl.fromTo(h, { scaleX: 0 }, { scaleX: 1, duration: 1.25, ease: 'power3.inOut' }, 0);
    if (v.offsetParent) {
      tl.fromTo(v, { scaleY: 0 }, { scaleY: 1, duration: 0.95, ease: 'power3.out' }, 0.5)
        .fromTo(dot, { scale: 0 }, { scale: 1, duration: 0.7, ease: 'back.out(3)' }, 0.85);
    }
    if (n) tl.fromTo(n, { yPercent: 55, opacity: 0 }, { yPercent: 0, opacity: 1, duration: 1.7, ease: 'expo.out' }, 0.15);
    tl.fromTo(rest, { opacity: 0, y: 16 }, { opacity: 1, y: 0, duration: 1, stagger: 0.07, ease: 'expo.out', clearProps: 'transform' }, 0.55);
    if (place) tl.fromTo(place, { scale: 1.07 }, { scale: 1, duration: 2.6, ease: 'expo.out', clearProps: 'transform' }, 0);
    rise(q('.opener__title'), { delay: wait + 0.2 });
    if (n) gsap.to(n, { y: () => n.offsetHeight * 0.22, ease: 'none', scrollTrigger: { trigger: op, start: 'top top', end: 'bottom top', scrub: true, invalidateOnRefresh: true } });
  }
}

function sections() {
  // Section legends
  for (const h of document.querySelectorAll('.site-main .section__title, .site-main .section h2:not(.section__title), .featured__title, .national__title')) {
    if (h.closest('.hero, .opener, .drive-country, .drive-build')) continue; // the drive moves its own legends
    SplitText.create(h, {
      type: 'lines', mask: 'lines', autoSplit: true,
      onSplit: (self) => {
        const tl = gsap.timeline({ scrollTrigger: { trigger: h, start: 'top 88%', once: true } });
        tl.from(self.lines, { yPercent: 112, duration: 1.05, stagger: 0.08, ease: 'power4.out' });
        // A headline that closes on the orange period: the dot lands after its words, with a small overshoot.
        const dot = h.querySelector('.period--dot');
        if (dot) tl.from(dot, { scale: 0, transformOrigin: '50% 80%', duration: 0.7, ease: 'back.out(3)', clearProps: 'transform' }, '-=0.45');
        return tl;
      },
    });
  }

  // An inner page's section hangs from a thin line: it draws from the start side as the section arrives (pages.css).
  if (!document.body.classList.contains('home')) {
    ScrollTrigger.batch('.site-main .section', { start: 'top 88%', once: true, onEnter: (batch) => batch.forEach((el) => el.classList.add('is-in')) });
  }

  // Rows of a legend arrive one after another, each with its rule.
  const rows = '.domain, .jobs__item, .expertise li, .values__item, .roles__item, .process li, .results li, .knowledge-list li, .national__list li, .facts > div, .numbers > div, .project__numbers > div, .timeline li, .office, .partners li';
  rowsIn(rows);

  // Pictures unroll from the top as they come near. People don't: their split circle draws itself (lines/draw.js).
  const media = gsap.utils.toArray('.card__media, .news__media, .opener-hang__media, .offices__item, .minimap, .gallery__grid figure');
  ScrollTrigger.batch(media, {
    start: 'top 92%', once: true,
    onEnter: (batch) => gsap.fromTo(batch, { clipPath: 'inset(0% 0% 100% 0%)' }, { clipPath: 'inset(0% 0% 0% 0%)', duration: 1.2, stagger: 0.08, ease: 'expo.out', clearProps: 'clipPath' }),
  });

  // Prose and ledes settle from an already-visible state.
  const settle = gsap.utils.toArray('.site-main .section__lede, .site-main .section__prose, .story-block, .link-row, .section > .container > .btn, .card__body').filter((el) => !el.closest('.drive-country, .drive-build'));
  ScrollTrigger.batch(settle, {
    start: 'top 92%', once: true,
    onEnter: (batch) => gsap.fromTo(batch, { opacity: 0.2, y: 22 }, { opacity: 1, y: 0, duration: 1.1, stagger: 0.07, ease: 'expo.out', clearProps: 'transform,opacity' }),
  });

  // Figures count once, from 70% of their value, so nothing ever reads as missing (fx/count.js).
  countUp('.numbers dd, .project__numbers dd, .domain__count');
}

function rowsIn(selector) {
  const items = gsap.utils.toArray(selector).filter((el) => !el.closest('.hero, .gantry, .drive-build, .road'));
  if (!items.length) return;
  gsap.set(items, { opacity: 0, y: 26 });
  ScrollTrigger.batch(items, {
    start: 'top 94%', once: true,
    onEnter: (batch) => gsap.to(batch, { opacity: 1, y: 0, duration: 0.9, stagger: 0.06, ease: 'expo.out', clearProps: 'transform,opacity' }),
  });
}

/**
 * The lane line (main.js puts it down) is the switch line of the brand book: grey road ahead, orange as far as the
 * reader has come, through a short blend. --p is where the switch sits, from the top.
 */
function laneLine() {
  const line = document.querySelector('.lane-line');
  if (!line) return;
  gsap.fromTo(line, { '--p': '0%' }, { '--p': '100%', ease: 'none', scrollTrigger: { trigger: line, start: 'top 75%', end: 'bottom 75%', scrub: 0.6 } });
}

/** The last junction: the legend rises and the destinations unroll; the lines and the dot draw themselves (lines/draw.js). */
function footer() {
  const f = document.querySelector('.site-footer');
  if (!f) return;
  const cta = f.querySelector('.site-footer__cta-text');
  if (cta) SplitText.create(cta, {
    type: 'lines', mask: 'lines', autoSplit: true,
    onSplit: (self) => gsap.from(self.lines, { yPercent: 112, duration: 1.15, ease: 'power4.out', scrollTrigger: { trigger: f, start: 'top 80%', once: true } }),
  });
  const board = f.querySelector('.site-footer__board');
  if (board) {
    gsap.fromTo(board, { clipPath: 'inset(0% 0% 100% 0%)' }, { clipPath: 'inset(-10% -2% -2% -2%)', duration: 1.2, ease: 'expo.out', clearProps: 'clipPath', scrollTrigger: { trigger: board, start: 'top 88%', once: true } });
    gsap.from(board.querySelectorAll('li'), { opacity: 0, y: 18, duration: 0.8, stagger: 0.06, ease: 'expo.out', delay: 0.25, clearProps: 'transform,opacity', scrollTrigger: { trigger: board, start: 'top 88%', once: true } });
  }
}

/* ---------------------------------------------------------------- menu */

export function menuIn(menu) {
  const plate = menu.querySelector('.gantry__plate');
  const names = menu.querySelectorAll('.dest__name > span');
  const rest = menu.querySelectorAll('.gantry__top > *, .dest__n, .dest__dist, .dest__row .arrow, .gantry__side > :not(.gantry__place)');
  const place = menu.querySelector('.gantry__place');
  gsap.killTweensOf([plate, names, rest, place]);
  // The sheet opens, then draws itself the way the book's lines do: the line from the start side, the crossing
  // line down through it, the dot last with a small overshoot. The place behind the side column settles from closer.
  const tl = gsap.timeline()
    .fromTo(plate, { clipPath: 'inset(0% 0% 100% 0% round 20px)' }, { clipPath: 'inset(0% 0% 0% 0% round 20px)', duration: 0.8, ease: 'expo.inOut', clearProps: 'clipPath' })
    .fromTo(plate, { '--mh': 0 }, { '--mh': 1, duration: 1.1, ease: 'power3.inOut' }, 0.3)
    .fromTo(plate, { '--mv': 0 }, { '--mv': 1, duration: 0.9, ease: 'power3.out' }, 0.75)
    .fromTo(plate, { '--md': 0 }, { '--md': 1, duration: 0.7, ease: 'back.out(3)' }, 1.05)
    .fromTo(names, { yPercent: 110 }, { yPercent: 0, duration: 1, stagger: 0.055, ease: 'power4.out' }, 0.32)
    .fromTo(rest, { opacity: 0, y: 12 }, { opacity: 1, y: 0, duration: 0.7, stagger: 0.02, ease: 'expo.out', clearProps: 'transform' }, 0.5);
  if (place) tl.fromTo(place, { scale: 1.14 }, { scale: 1, duration: 1.8, ease: 'expo.out', clearProps: 'transform' }, 0.2);
  return tl;
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
  cover.innerHTML = `<span class="page-cover__mark">${splitCircle()}</span>`;
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
