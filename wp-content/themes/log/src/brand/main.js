/**
 * The web brand book (/brand).
 *
 * 1. Live values: colour codes, contrast ratios and the contents' page numbers are read from the page itself,
 *    so the book can't disagree with the site.
 *    Icons are written out as standalone SVG files with those same live colours, so each can be taken away.
 * 2. The route: one thin line down the side of the whole book, round a small roundabout at the start, at every
 *    chapter and at the end, orange as far as you've come, with the dot travelling on it. Every roundabout is a link
 *    with its stop's name; near the pointer the line opens into the whole menu.
 * 3. The riso fields: a quiet print texture down the side of the night sheets, which a pointer or a finger stirs.
 * 4. Motion (GSAP). The book is an ordinary page that scrolls, and what is on it answers the scroll: lines and
 *    crossings draw as far as you have come, photographs drift inside their frames, the drawn city stands up, and
 *    every headline, picture and row arrives when it reaches the window, not when its sheet does.
 * 5. The opening: the first load happens under a night veil that opens along the cover's own line.
 *
 * A computer that can't keep up gets the same book with less moving (lite, below): no drifting photographs, no
 * city that follows the scroll, still print fields. It is decided by the machine's size, or by watching the frames.
 *
 * Everything in 4 lives inside gsap.matchMedia: with reduced motion (and in the PDF, which asks for it) nothing is
 * ever hidden, and the sheets are simply there, finished.
 */
import './brand.css';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { ScrollToPlugin } from 'gsap/ScrollToPlugin';
import { SplitText } from 'gsap/SplitText';
import { drawLines } from '../lines/draw.js';
import { depthText, depthTilt, maskedText, warpText } from '../fx/text.js';
import { riso, risoRest } from '../fx/riso.js';
import { cityPulse, cityRise } from '../fx/city.js';

gsap.registerPlugin(ScrollTrigger, ScrollToPlugin, SplitText);

const pages = [...document.querySelectorAll('.bk-page')];

/**
 * lite: true on a small machine (four cores or four gigabytes, or less), or from the moment the frames stop keeping
 * up with the scroll. What can be switched off after the fact registers itself in `lighten`.
 */
let lite = (navigator.hardwareConcurrency || 8) <= 4 || (navigator.deviceMemory || 8) <= 4;
const lighten = [];
function goLite() {
  if (lite) return;
  lite = true;
  document.documentElement.classList.add('bk-lite');
  risoRest();
  lighten.splice(0).forEach((f) => f());
}
if (lite) { document.documentElement.classList.add('bk-lite'); risoRest(); }
const two = (n) => String(n).padStart(2, '0');

/* ---------------------------------------------------------------- 1. live values */

/** A token as the browser resolved it, as rgb channels. A probe element turns any colour syntax into rgb(). */
const probe = document.createElement('i');
probe.style.display = 'none';
document.body.append(probe);
function rgb(token) {
  probe.style.color = `var(${token})`;
  const m = getComputedStyle(probe).color.match(/[\d.]+/g);
  return m ? m.slice(0, 3).map(Number) : null;
}
const hex = (c) => '#' + c.map((v) => Math.round(v).toString(16).padStart(2, '0')).join('').toUpperCase();

// WCAG 2 relative luminance and contrast ratio.
const lum = (c) => {
  const [r, g, b] = c.map((v) => {
    const s = v / 255;
    return s <= 0.03928 ? s / 12.92 : ((s + 0.055) / 1.055) ** 2.4;
  });
  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
};
const contrast = (a, b) => {
  const [hi, lo] = [lum(a), lum(b)].sort((x, y) => y - x);
  return (hi + 0.05) / (lo + 0.05);
};

for (const el of document.querySelectorAll('[data-token-value]')) {
  const c = rgb(el.dataset.tokenValue);
  if (c) el.textContent = hex(c);
}

// A pair's ratio and its verdict: under 4.5 it fails, and the row says so.
for (const el of document.querySelectorAll('[data-ratio]')) {
  const [fg, bg] = el.dataset.ratio.split(' ').map(rgb);
  if (!fg || !bg) continue;
  const ratio = contrast(fg, bg);
  el.textContent = `${ratio.toFixed(2)} : 1`;
  const verdict = el.parentElement.querySelector('[data-verdict]');
  if (verdict) {
    verdict.textContent = ratio >= 4.5 ? 'עובר' : 'נופל';
    verdict.classList.toggle('is-fail', ratio < 4.5);
  }
}
// An icon is taken away as the book draws it: a file of its own with the two live colours written in. The line is
// the sheet's (2 on an icon of 58), and so are the dashes, both in the icon's own 48 units.
const inkNow = rgb('--ink');
const worksNow = rgb('--works');
if (inkNow && worksNow) {
  for (const link of document.querySelectorAll('a[data-icon]')) {
    const copy = link.querySelector('svg')?.cloneNode(true);
    if (!copy) continue;
    for (const el of copy.querySelectorAll('*')) {
      if (el.classList.contains('o')) el.setAttribute('stroke', hex(worksNow));
      if (el.classList.contains('d')) el.setAttribute('stroke-dasharray', '2.5 3.3');
      el.removeAttribute('class');
    }
    const file = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="96" height="96" fill="none" stroke="${hex(inkNow)}" stroke-width="1.66" stroke-linecap="round" stroke-linejoin="round">${copy.innerHTML}</svg>`;
    link.href = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(file)}`;
  }
}
probe.remove();

// The contents: each chapter's page is where its first sheet really is.
for (const link of document.querySelectorAll('[data-toc]')) {
  const at = pages.indexOf(document.getElementById(`ch-${link.dataset.toc}`));
  const slot = link.querySelector('[data-toc-page]');
  if (at >= 0 && slot) slot.textContent = `עמוד ${two(at + 1)}`;
}

/** Where an element starts in the document. */
const top = (el) => el.getBoundingClientRect().top + scrollY;
const calm = matchMedia('(prefers-reduced-motion: reduce)');

/* ---------------------------------------------------------------- 2. the route */

/**
 * One thin line runs down the side of the book from its first sheet to its last: the road. At the start, at every
 * chapter and at the end it goes round a small roundabout (LOG's O), one side then the other. It is orange as far
 * as you have come, and the dot travels on it. The roundabouts are the book's menu: each is a link, and says its
 * name under the pointer and for a moment when you drive into it. When the pointer comes near the line all of them
 * say their names at once. It is state, not animation, so it also runs with reduced motion.
 */
const chapters = pages.filter((p) => p.id.startsWith('ch-'));
let tour = () => {}; // the line introduces itself once, after the opening
if (chapters.length) {
  const route = document.createElement('nav');
  route.className = 'bk-route';
  route.setAttribute('aria-label', 'פרקי הספר');
  document.body.append(route);
  const first = document.getElementById('start');
  const last = document.getElementById('end');
  const places = [
    ...(first ? [{ el: first, n: '', name: 'התחלה' }] : []),
    ...chapters.map((el) => ({ el, n: el.dataset.chapter, name: el.getAttribute('aria-label') })),
    ...(last ? [{ el: last, n: '', name: 'סיום' }] : []),
  ];
  const at = places.map((p) => pages.indexOf(p.el));
  const R = 8, X = 22, PAD = 26;
  const wide = matchMedia('(min-width: 1100px)'); // under it the line isn't shown
  let length = 0, done = null, dot = null, label = null, stops = [], current = -1, flash = 0, tops = [], shown = -1, queued = false;

  const build = () => {
    const h = innerHeight;
    tops = pages.map(top);
    const y = (i) => PAD + (tops[at[i]] / tops[tops.length - 1]) * (h - 2 * PAD);
    let d = `M${X} 0`;
    places.forEach((_, i) => { d += ` V${(y(i) - R).toFixed(1)} A${R} ${R} 0 0 ${i % 2} ${X} ${(y(i) + R).toFixed(1)}`; });
    d += ` V${h}`;
    route.innerHTML = `<svg width="${X * 2}" height="${h}" aria-hidden="true"><path class="bk-route__road" d="${d}"/>${places.map((_, i) =>
      `<path class="bk-route__island" d="M${X} ${(y(i) - R).toFixed(1)} A${R} ${R} 0 0 ${1 - (i % 2)} ${X} ${(y(i) + R).toFixed(1)}"/>`).join('')}<path class="bk-route__done" d="${d}"/><circle class="bk-route__dot" r="5"/></svg>`
      + places.map((p, i) => `<a href="#${p.el.id}" style="top:${y(i).toFixed(1)}px;--i:${i}"><span>${p.n ? `<b>${p.n}</b>` : ''}${p.name}</span></a>`).join('')
      + `<p class="bk-route__hint" style="top:${(h * 0.24).toFixed(1)}px" aria-hidden="true"><b>כל כיכר בקו היא פרק.</b>לוחצים עליה ומגיעים אליו.</p>`
      + '<p class="bk-route__page" aria-hidden="true"></p>';
    done = route.querySelector('.bk-route__done');
    dot = route.querySelector('.bk-route__dot');
    label = route.querySelector('.bk-route__page');
    stops = [...route.querySelectorAll('a')];
    length = done.getTotalLength();
    done.style.strokeDasharray = `${length} ${length}`;
    current = -1;
    shown = -1;
    update();
  };

  const update = () => {
    queued = false;
    if (!done || !wide.matches) return;
    const max = document.documentElement.scrollHeight - innerHeight;
    const p = max > 0 ? Math.min(1, Math.max(0, scrollY / max)) : 0;
    done.style.strokeDashoffset = String(length * (1 - p));
    const here = done.getPointAtLength(length * p);
    dot.setAttribute('cx', here.x.toFixed(1));
    dot.setAttribute('cy', here.y.toFixed(1));
    // The current sheet is the last whose top has come past the middle of the window.
    let page = 0;
    const middle = scrollY + innerHeight / 2;
    for (let i = 0; i < tops.length && tops[i] <= middle; i++) page = i;
    if (page !== shown) { shown = page; label.textContent = two(page + 1); }
    label.style.translate = `0 ${here.y.toFixed(1)}px`;
    // And the current stop is the last one that sheet has passed.
    let stop = 0;
    at.forEach((index, i) => { if (index <= page) stop = i; });
    if (stop === current) return;
    const arriving = current >= 0;
    current = stop;
    clearTimeout(flash);
    stops.forEach((a, i) => { a.classList.toggle('is-current', i === stop); a.classList.remove('is-flash'); });
    // Driving into a stop, its roundabout says its name for a moment.
    if (arriving) {
      stops[stop].classList.add('is-flash');
      flash = setTimeout(() => stops[stop]?.classList.remove('is-flash'), 1800);
    }
  };

  // Near the line the whole menu opens; it stays open while the pointer is on one of its rows.
  addEventListener('pointermove', (e) => {
    if (e.pointerType === 'touch') return;
    route.classList.toggle('is-near', e.clientX > innerWidth - 96 || !!e.target.closest?.('.bk-route'));
  }, { passive: true });
  document.documentElement.addEventListener('pointerleave', () => route.classList.remove('is-near'));

  tour = () => {
    if (scrollY > innerHeight * 0.4 || !matchMedia('(min-width: 1100px)').matches) return;
    try {
      if (sessionStorage.getItem('bk-route-met')) return;
      sessionStorage.setItem('bk-route-met', '1');
    } catch { /* a private window: the line simply introduces itself again */ }
    route.classList.add('is-tour');
    const over = () => { route.classList.remove('is-tour'); removeEventListener('scroll', leave); };
    const leave = () => { if (scrollY > innerHeight * 0.5) over(); };
    addEventListener('scroll', leave, { passive: true });
    setTimeout(over, 6500);
  };

  // Once a frame at most; the sheets' places are read again when they can have moved.
  addEventListener('scroll', () => { if (!queued) { queued = true; requestAnimationFrame(update); } }, { passive: true });
  addEventListener('resize', build, { passive: true });
  addEventListener('load', build);
  build();
}

// A link to a sheet (the route's roundabouts, the contents, the way back from the last sheet) drives there instead of jumping.
addEventListener('click', (e) => {
  const link = e.target.closest?.('a[href^="#"]');
  const to = link && link.hash.length > 1 && document.getElementById(link.hash.slice(1));
  if (!to || !to.classList.contains('bk-page') || calm.matches || e.metaKey || e.ctrlKey || e.shiftKey) return;
  e.preventDefault();
  const y = top(to);
  gsap.to(window, { scrollTo: { y, autoKill: true }, duration: gsap.utils.clamp(0.9, 2.2, Math.abs(y - scrollY) / 4200), ease: 'power3.inOut', overwrite: true });
  history.replaceState(null, '', link.hash);
});

/* ---------------------------------------------------------------- 3. the riso fields */

/**
 * A quiet print texture you can touch (src/fx/riso.js), down the end side of the night sheets that open and close
 * things. With reduced motion it is a still print.
 */
if (!document.querySelector('.bk-lock')) {
  for (const page of document.querySelectorAll('.bk-opener, .bk-remember, .bk-end')) { // the cover carries the city instead
    const side = document.createElement('canvas');
    side.className = 'bk-riso bk-riso--side';
    side.setAttribute('aria-hidden', 'true');
    page.append(side);
    riso(side, page, { cell: 6, density: 0.4, still: calm.matches || lite });
  }
}

/* ---------------------------------------------------------------- 4. motion */

const E = 'expo.out';
let intro = null; // the cover's own entrance, played by the opening
let opened = false;

function motion() {
  const mm = gsap.matchMedia();
  mm.add({ motion: '(prefers-reduced-motion: no-preference)', wide: '(min-width: 1100px)' }, (ctx) => {
    if (!ctx.conditions.motion) return undefined;
    const wide = ctx.conditions.wide; // under it the sheets reflow into a column
    const undo = [];

    // Arrow keys, Page Up/Down and Space go from sheet to sheet.
    const turn = (e) => {
      if (e.target.closest('input, textarea, select, [contenteditable]') || e.metaKey || e.ctrlKey || e.altKey) return;
      const dir = ['ArrowDown', 'PageDown', ' '].includes(e.key) && !e.shiftKey ? 1 : ['ArrowUp', 'PageUp'].includes(e.key) || (e.key === ' ' && e.shiftKey) ? -1 : 0;
      if (!dir || !wide) return; // a phone scrolls the reflowed sheets as an ordinary page
      const tops = pages.map(top);
      let at = 0;
      tops.forEach((t, i) => { if (t <= scrollY + 4) at = i; });
      // Going back from the middle of a sheet returns to its start first.
      const next = dir < 0 && scrollY - tops[at] > 8 ? at : gsap.utils.clamp(0, pages.length - 1, at + dir);
      if (Math.abs(tops[next] - scrollY) < 4) return;
      e.preventDefault();
      gsap.to(window, { scrollTo: { y: tops[next], autoKill: true }, duration: 1, ease: 'power3.inOut', overwrite: true });
    };
    addEventListener('keydown', turn);

    // While the page scrolls, count the frames that came late. A run of them, and the book goes lite.
    let late = 0;
    const watch = (time, delta) => {
      if (lite) { gsap.ticker.remove(watch); return; }
      if (!ScrollTrigger.isScrolling()) return;
      late = delta > 34 ? late + 1 : Math.max(0, late - 0.2);
      if (late > 20) goLite();
    };
    gsap.ticker.add(watch);
    undo.push(() => { gsap.ticker.remove(watch); lighten.length = 0; });

    /** Once, when the element reaches the window. */
    const once = (trigger, start = 'top 86%') => ({ trigger, start, once: true });
    /** With the scroll, between two places in the window. Clamped, so the first and last sheets can finish. */
    const along = (trigger, start, end, scrub = 0.6) => ({ trigger, start: `clamp(${start})`, end: `clamp(${end})`, scrub });
    /** Each element waits for its own place in the window; those that reach it together arrive one after another. */
    const arrive = (els, from, to, each = 0.07) => {
      els = gsap.utils.toArray(els);
      if (!els.length) return;
      gsap.set(els, from);
      ScrollTrigger.batch(els, { start: 'top 92%', once: true, onEnter: (batch) => gsap.to(batch, { ease: E, ...to, stagger: each, overwrite: true }) });
    };
    const land = { scale: 0, duration: 0.7, ease: 'back.out(3)', clearProps: 'transform' };

    /** The headline comes up out of line masks; its orange period lands after its words. */
    function headline(tl, title, at) {
      const dot = title.querySelector('.bk-dot');
      const split = SplitText.create(title, { type: 'lines', mask: 'lines' });
      tl.from(split.lines, { yPercent: 108, duration: 1.15, stagger: 0.09, ease: 'power4.out' }, at);
      if (dot) tl.from(dot, land, '-=0.5');
      tl.add(() => split.revert(), '>');
    }

    // The cover is opened by the veil (5): the photograph settles from close up and the name rises.
    const cover = document.querySelector('.bk-cover');
    if (cover) {
      const tl = gsap.timeline({ paused: true });
      const photo = cover.querySelector('.bk-photo--bleed');
      if (photo) tl.from(photo, { scale: 1.24, duration: 3, ease: E }, 0);
      tl.from(cover.querySelector('.bk-kicker'), { opacity: 0, x: 24, duration: 0.9, ease: E }, 0.4);
      // The book's name is a plan that rises into a building: flat at first, then it gains its depth and leans.
      const name = cover.querySelector('.bk-title');
      if (name) {
        const depth = depthText(name, { layers: lite ? 8 : 18 });
        undo.push(depth.undo);
        tl.from(depth.stage, { yPercent: 36, opacity: 0, duration: 1.2, ease: E }, 0.5)
          .fromTo(depth.stage, { '--grow': 0, rotationX: 0, rotationY: 0 }, { '--grow': 1, rotationX: -4, rotationY: 7, duration: 2, ease: E }, 0.8)
          .from(depth.stage.querySelector('.fx-depth__face .bk-dot'), land, 1.4)
          .add(() => { if (!lite) undo.push(depthTilt(depth.stage, cover)); });
      }
      tl.from(cover.querySelectorAll('.bk-lede, .bk-cover__version'), { opacity: 0, y: 16, duration: 1, stagger: 0.12, ease: E }, 1.05);
      // The city beside the name stands up out of the photograph, from the crossing outward. Once it stands it
      // answers the scroll: taller the further down you are, and a wave of it stretches or ducks with the wheel.
      const town = cover.querySelector('.bk-cover__city');
      if (town) {
        if (lite) {
          tl.from(town, { opacity: 0, duration: 1.8, ease: 'power2.out' }, 0.3); // it is simply there
        } else {
          const from = [0.11, 0.84]; // where the cover's two lines cross
          const rise = cityRise(town, { from, duration: 3.2 });
          tl.add(rise, 0.3);
          const still = cityPulse(town, { trigger: cover, from, ready: () => rise.progress() === 1 });
          undo.push(rise.undo, still);
          lighten.push(still);
          gsap.fromTo(town, { yPercent: 0 }, { yPercent: 9, ease: 'none', scrollTrigger: along(cover, 'top top', 'bottom top', true) });
        }
      }
      if (opened) tl.progress(1);
      intro = tl;
    }

    // Three sheets carry a scene that runs with the scroll while the sheet crosses the window.
    const scene = (page) => gsap.timeline({ defaults: { ease: 'none' }, scrollTrigger: along(page, 'top 72%', 'top 4%') });

    // Colour: the bands open in the proportions of a typical page, mostly paper, a sliver of orange, then even out.
    const colours = wide && document.querySelector('.bk-colors.bk-page--hold');
    if (colours) {
      const bands = [...colours.querySelectorAll('.bk-band')];
      const tl = scene(colours);
      bands.forEach((band) => {
        const share = Number(getComputedStyle(band).getPropertyValue('--share')) || 1;
        tl.fromTo(band, { flexGrow: share / (100 / bands.length) }, { flexGrow: 1, duration: 1, ease: 'power2.inOut' }, 0.12);
        tl.fromTo(band.querySelector('figcaption'), { opacity: share > 15 ? 1 : 0 }, { opacity: 1, duration: 0.3 }, 0.75);
      });
    }

    // The logo: its quarters drive apart to show the cuts, then lock again; the language beside it follows.
    const anatomy = wide && document.querySelector('.bk-anatomy.bk-page--hold');
    if (anatomy) {
      const quarters = [...anatomy.querySelectorAll('.bk-anatomy__logo [data-q]')];
      const out = { 1: [1, -1], 2: [-1, -1], 3: [-1, 1], 4: [1, 1] };
      const tl = scene(anatomy);
      quarters.forEach((q) => {
        const [x, y] = out[q.dataset.q] || [0, 0];
        const far = (q.getBBox?.().width || 40) * 0.22;
        tl.to(q, { x: x * far, y: y * far, rotation: x * y * -8, duration: 0.38, ease: 'power2.inOut' }, 0.1)
          .to(q, { x: 0, y: 0, rotation: 0, duration: 0.38, ease: 'power3.inOut' }, 0.6);
      });
      const ring = anatomy.querySelector('.bk-anatomy__language .circ svg');
      if (ring) tl.fromTo(ring, { rotation: 0 }, { rotation: -360, duration: 1, transformOrigin: '50% 50%' }, 0);
      const sw = anatomy.querySelector('.bk-anatomy__language .ln--switch');
      if (sw) tl.fromTo(sw, { '--at': '96%' }, { '--at': '18%', duration: 0.8 }, 0.1);
    }

    // The timeline: the line travels edge to edge with the scroll, and each month lands as the line reaches it.
    const time = wide && document.querySelector('.bk-time.bk-page--hold');
    if (time) {
      const tl = scene(time);
      tl.fromTo(time.querySelector('.bk-timeline__line'), { scaleX: 0 }, { scaleX: 1, duration: 1 }, 0);
      time.querySelectorAll('.bk-timeline li').forEach((li, i) => {
        tl.fromTo(li.querySelector('.circ'), { scale: 0 }, { scale: 1, duration: 0.12, ease: 'back.out(2.4)' }, 0.1 + i * 0.22)
          .fromTo(li.querySelectorAll(':scope > :not(.circ)'), { opacity: 0, y: 18 }, { opacity: 1, y: 0, duration: 0.14, stagger: 0.03, ease: 'power2.out' }, 0.14 + i * 0.22);
      });
    }

    const items = [
      '.bk-rules > li', '.bk-rows > li', '.bk-how__grid > li', '.bk-toc__list > li', '.bk-junction__words > li', '.bk-vision__cols > div',
      '.bk-tone__cols > div > h3', '.bk-quote', '.bk-logo__versions > li', '.bk-wrong__grid > li', '.bk-anatomy__notes > div', '.bk-anatomy__language > *',
      '.bk-colors__foot > div', '.bk-icons__grid > li', '.bk-table tr', '.bk-scale > li', '.bk-get__label', '.bk-get li', '.bk-again', '.bk-formats__row > li', '.bk-lines__kinds > li > :not(.ln)',
      '.bk-circles__kinds > li > :not(.circ)', '.bk-minis > li > :not(.bk-mini)', '.bk-photos__cols h3', '.bk-scrims__places > ul:first-child > li > :not(.bk-scrims__case)',
      '.bk-scrims__person > p', '.bk-social__frames .bk-cap', '.bk-highlights__row > li', '.bk-banner h3', '.bk-banner__text', '.bk-fb__cols h3', '.bk-fb__cols h3 + p',
      '.bk-sign', '.bk-note', '.bk-grid__sheet > p', '.bk-timeline li > :not(.circ)',
    ].join(',');
    const unroll = ':scope .bk-photo:not(.bk-photo--bleed), :scope .bk-mini, :scope .bk-frame, :scope .bk-scrims__case, :scope .bk-scrims__portrait, :scope .bk-grid__sheet, :scope .bk-format, :scope .bk-banner__avatar';

    for (const page of pages) {
      if (page.classList.contains('bk-lock')) continue;
      const first = page === cover; // its entrance belongs to the opening
      const held = wide && page.classList.contains('bk-page--hold'); // its scene runs with the scroll, above

      // A photograph that fills its sheet drifts behind it for as long as the sheet is in the window.
      const bleed = page.querySelector(':scope > .bk-photo--bleed');
      const still = bleed?.querySelector('img');
      if (still && !lite) {
        gsap.set(still, { scale: 1.12 });
        const drift = gsap.fromTo(still, { yPercent: -5 }, { yPercent: 5, ease: 'none', scrollTrigger: along(page, 'top bottom', 'bottom top', true) });
        lighten.push(() => { drift.scrollTrigger?.kill(); drift.kill(); gsap.set(still, { yPercent: 0 }); });
        if (!first) gsap.from(bleed, { scale: 1.14, duration: 2.6, ease: E, scrollTrigger: once(page, 'top 82%') });
      }

      // The two lines cross as the sheet comes up, and the dot lands where they meet.
      const cross = !first && page.querySelector(':scope > .bk-cross');
      if (cross) {
        gsap.timeline({ defaults: { ease: 'none' }, scrollTrigger: along(page, 'top 80%', 'top 16%') })
          .from(cross.querySelector('.bk-cross__v'), { scaleY: 0, duration: 1 }, 0)
          .from(cross.querySelector('.bk-cross__h'), { scaleX: 0, duration: 1 }, 0.08)
          .from(cross.querySelector('.bk-cross__dot'), { scale: 0, duration: 0.22, ease: 'back.out(3)' }, 0.86);
      }

      // A chapter's number rises into place and keeps drifting across, slower than the page; its name drifts the other way.
      const numeral = page.querySelector('.bk-opener__n');
      if (numeral) {
        gsap.from(numeral, { yPercent: 24, opacity: 0, duration: 1.8, ease: E, scrollTrigger: once(page, 'top 72%') });
        gsap.fromTo(numeral, { xPercent: -8 }, { xPercent: 8, ease: 'none', scrollTrigger: along(page, 'top bottom', 'bottom top', 0.8) });
        const body = page.querySelector('.bk-opener__body');
        if (body && wide) gsap.fromTo(body, { y: 46 }, { y: -46, ease: 'none', scrollTrigger: along(page, 'top bottom', 'bottom top', 0.8) });
      }

      // The head: label, headline, sentence.
      const title = !first && page.querySelector('.bk-title');
      const head = title && (title.closest('.bk-head, .bk-opener__body') || title);
      if (head) {
        const tl = gsap.timeline({ scrollTrigger: once(head, 'top 86%'), defaults: { ease: E } });
        const kicker = head.querySelector('.bk-kicker');
        if (kicker) tl.from(kicker, { opacity: 0, x: 24, duration: 0.9 }, 0);
        headline(tl, title, 0.08);
        // "We are the junction" arrives through heat over asphalt and settles; a pointer passing over stirs it again.
        if (page.classList.contains('bk-junction')) {
          tl.add(warpText(title), 0.08);
          const stir = () => warpText(title, { from: 26, duration: 1.4 });
          title.addEventListener('pointerenter', stir);
          undo.push(() => title.removeEventListener('pointerenter', stir));
        }
        const lede = head.querySelector('.bk-lede');
        if (lede) tl.from(lede, { opacity: 0, y: 18, duration: 1 }, 0.36);
      }
      // The leading idea carries the place inside its letters.
      const masked = page.querySelector('[data-fx-masked]');
      if (masked) undo.push(maskedText(masked, masked.dataset.fxMasked, page));

      // The drawn city stands up out of the ground as far as you have scrolled; a plan on it draws itself after.
      const city = !first && page.querySelector('.cty'); // the cover's belongs to the opening
      if (city && !lite) {
        const rise = cityRise(city, { scrub: true });
        const run = gsap.timeline({ scrollTrigger: along(city, 'top 84%', wide ? 'top 6%' : 'top 22%', 0.9) }).add(rise);
        undo.push(rise.undo);
        lighten.push(() => { run.scrollTrigger?.kill(); run.progress(1); });
      }

      // Structure: every line of the page runs from the start side to the edge as it climbs the window.
      page.querySelectorAll(held ? ':scope .bk-line' : ':scope .bk-line, :scope .bk-timeline__line, :scope .bk-chart__avg').forEach((line) => {
        gsap.fromTo(line, { scaleX: 0 }, { scaleX: 1, ease: 'none', scrollTrigger: along(line, 'top 97%', 'top 58%') });
      });
      arrive(page.querySelectorAll('.bk-junction__tick'), { scaleY: 0 }, { scaleY: 1, duration: 0.6, ease: 'power3.out' }, 0.14);

      // Pictures and specimens unroll from their top edge; a photograph settles from a little closer, then drifts in its frame.
      const frames = [...page.querySelectorAll(unroll)].filter((f) => !f.parentElement.closest('.bk-mini, .bk-frame, .bk-scrims__case, .bk-scrims__portrait'));
      if (frames.length) {
        const inside = (f) => (f.matches('.bk-photo') ? f.querySelector(':scope > img') : null);
        frames.map(inside).filter(Boolean).forEach((img) => {
          gsap.set(img, { scale: 1.08 });
          if (lite) return;
          const drift = gsap.fromTo(img, { yPercent: -3.5 }, { yPercent: 3.5, ease: 'none', scrollTrigger: along(img.parentElement, 'top bottom', 'bottom top', true) });
          lighten.push(() => { drift.scrollTrigger?.kill(); drift.kill(); gsap.set(img, { yPercent: 0 }); });
        });
        gsap.set(frames, { clipPath: 'inset(0% 0% 100% 0%)' });
        ScrollTrigger.batch(frames, {
          start: 'top 92%', once: true,
          onEnter: (batch) => {
            gsap.to(batch, { clipPath: 'inset(0% 0% 0% 0%)', duration: 1.35, ease: E, stagger: 0.09, clearProps: 'clipPath' });
            const photos = batch.map(inside).filter(Boolean);
            if (photos.length) gsap.fromTo(photos, { scale: 1.28 }, { scale: 1.08, duration: 2, ease: E, stagger: 0.09 });
          },
        });
      }

      // Colour arrives as paint: chips fill from the top, the ratio bar from the start side.
      arrive(page.querySelectorAll('.bk-bands'), { clipPath: 'inset(0% 0% 100% 0%)' }, { clipPath: 'inset(0% 0% 0% 0%)', duration: 1.3, ease: 'power4.out', clearProps: 'clipPath' });
      arrive(page.querySelectorAll('.bk-ratio__bar i, .bk-nightfam__chips i'), { scaleX: 0 }, { scaleX: 1, duration: 0.9, ease: 'power3.out' }, 0.06);

      // A bar grows to exactly its number, and the number counts with it.
      const chart = page.querySelector(':scope > .bk-data__cols .bk-chart');
      const bars = chart ? page.querySelectorAll(':scope > .bk-data__cols .bk-chart__bar') : [];
      if (bars.length) {
        const tl = gsap.timeline({ scrollTrigger: once(chart, 'top 80%') });
        tl.from(bars, { scaleY: 0, duration: 1.2, stagger: 0.06, ease: 'power4.out' }, 0);
        const fmt = new Intl.NumberFormat('he-IL');
        page.querySelectorAll(':scope > .bk-data__cols .bk-chart__value').forEach((el, i) => {
          const end = Number(el.textContent.replace(/[^\d]/g, ''));
          const o = { v: 0 };
          tl.from(el, { opacity: 0, duration: 0.3 }, i * 0.06)
            .to(o, { v: end, duration: 1.2, ease: 'power4.out', onUpdate: () => { el.textContent = fmt.format(Math.round(o.v)); } }, i * 0.06);
        });
      }

      // Circles on a line land on it one after another (the drawn ones belong to lines/draw.js).
      arrive(page.querySelectorAll(held ? '.bk-highlights__row .circ' : '.bk-timeline .circ, .bk-highlights__row .circ'), { scale: 0 }, { scale: 1, duration: 0.8, ease: 'back.out(1.8)', clearProps: 'transform' }, 0.1);

      // Then the words, each row as it comes.
      const rows = [...page.querySelectorAll(items)].filter((el) => !el.closest('.bk-mini') && !(held && el.closest('.bk-timeline')));
      arrive(rows, { opacity: 0, y: 28 }, { opacity: 1, y: 0, duration: 1.05, clearProps: 'transform,opacity' }, 0.06);

      arrive(page.querySelectorAll(':scope > .bk-folio'), { opacity: 0 }, { opacity: 1, duration: 0.9, ease: 'power2.out', clearProps: 'opacity' });
    }

    return () => { removeEventListener('keydown', turn); undo.forEach((f) => f()); intro = null; };
  });
  // The line system's specimens draw the way they do on the site.
  drawLines();
}

/* ---------------------------------------------------------------- 5. the opening */

/**
 * The veil (page-brand.php, brand.css) covers the first load. LOG's O turns where the cover's two lines cross;
 * when the book is ready the O closes into the dot, the lines run out of it to the edges, and the night ground
 * opens along the horizontal one onto the cover, which has the same two lines in the same place. Arriving
 * part-way down the book (a reload, a link to a chapter) the veil simply lifts.
 */
function open() {
  const html = document.documentElement;
  const veil = document.querySelector('.bk-veil');
  const finish = () => { opened = true; veil?.remove(); html.classList.remove('bk-loading', 'bk-opening'); setTimeout(tour, 2200); };
  if (!veil || !html.classList.contains('bk-loading')) {
    intro?.play();
    finish();
    return;
  }
  html.classList.add('bk-opening');
  if (scrollY > innerHeight * 0.4) {
    intro?.progress(1);
    gsap.to(veil, { opacity: 0, duration: 0.5, ease: 'power2.out', onComplete: finish });
    return;
  }
  // Where the cover's lines really cross, to the pixel. Without a cover (the lock) or without its cross (a phone)
  // the O is in the middle, and its lines leave with the veil.
  const at = document.querySelector('.bk-cover .bk-cross__dot')?.getBoundingClientRect();
  const real = !!at && at.width > 0;
  const box = veil.getBoundingClientRect();
  if (real) {
    veil.style.setProperty('--vx', `${(at.left + at.width / 2 - box.left).toFixed(2)}px`);
    veil.style.setProperty('--vy', `${(at.top + at.height / 2 - box.top).toFixed(2)}px`);
  }
  // The veil's logo is the cover's, in the cover's place.
  const logo = veil.querySelector('.bk-veil__logo');
  const there = document.querySelector('.bk-cover__logo .logo')?.getBoundingClientRect();
  if (logo && there?.width) {
    Object.assign(logo.style, { top: `${there.top - box.top}px`, left: `${there.left - box.left}px`, right: 'auto' });
    logo.firstElementChild.style.setProperty('--logo-w', `${there.width}px`);
  }
  veil.classList.add('is-ready');
  const [above, below] = veil.querySelectorAll('.bk-veil__half');
  const ring = veil.querySelector('.bk-veil__o');
  const dot = veil.querySelector('.bk-veil__dot');
  const lines = [veil.querySelector('.bk-veil__h'), veil.querySelector('.bk-veil__v')];
  const tl = gsap.timeline({ onComplete: finish });
  tl.to(ring.querySelectorAll('path'), { opacity: 1, duration: 0.22, stagger: 0.04, ease: 'power2.out' }, 0)
    .to(ring, { scale: 0.16, duration: 0.45, ease: 'power3.inOut', transformOrigin: '50% 50%' }, 0.22)
    .to(ring, { opacity: 0, duration: 0.14 }, 0.55)
    .to(dot, { scale: 1, duration: 0.5, ease: 'back.out(3)' }, 0.5)
    .to(lines[0], { scaleX: 1, duration: 0.85, ease: 'power3.inOut' }, 0.62)
    .to(lines[1], { scaleY: 1, duration: 0.85, ease: 'power3.inOut' }, 0.7)
    .addLabel('open', 1.08)
    .to(above, { yPercent: -100, duration: 1.25, ease: 'expo.inOut' }, 'open')
    .to(below, { yPercent: 100, duration: 1.25, ease: 'expo.inOut' }, 'open')
    .add(() => intro?.play(), 'open+=0.25');
  if (!real) tl.to([...lines, dot], { opacity: 0, duration: 0.5, ease: 'power2.out' }, 'open+=0.75');
}

// The book waits for its faces and for the cover's photograph, but not for long.
const wait = (ms) => new Promise((ok) => setTimeout(ok, ms));
const photo = document.querySelector('.bk-cover .bk-photo img');
Promise.all([
  Promise.race([document.fonts.ready, wait(3000)]),
  photo && !photo.complete ? Promise.race([photo.decode().catch(() => {}), wait(3000)]) : null,
]).then(() => {
  motion();
  ScrollTrigger.refresh();
  open();
});
