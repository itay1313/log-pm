/**
 * The web brand book (/brand).
 *
 * 1. Live values: colour codes, contrast ratios and the contents' page numbers are read from the page itself,
 *    so the book can't disagree with the site.
 * 2. The route: one thin line down the side of the whole book, round a small roundabout at every chapter,
 *    orange as far as you've come, with the dot travelling on it.
 * 3. Motion (GSAP): the cover opens like the site does, lines run to their edges and cross, the dot lands last;
 *    every sheet then plays once as it arrives: headline out of its mask, the period landing, lines drawing,
 *    photographs unrolling, bars growing to their numbers. Arrow keys turn the pages.
 *
 * Everything in 3 lives inside gsap.matchMedia: with reduced motion (and in the PDF, which asks for it) nothing is
 * ever hidden, and the sheets are simply there, finished.
 */
import './brand.css';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { ScrollToPlugin } from 'gsap/ScrollToPlugin';
import { SplitText } from 'gsap/SplitText';
import { drawLines } from '../lines/draw.js';
import { depthText, depthTilt, maskedText, warpText } from '../fx/text.js';

gsap.registerPlugin(ScrollTrigger, ScrollToPlugin, SplitText);

const pages = [...document.querySelectorAll('.bk-page')];
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
probe.remove();

// The contents: each chapter's page is where its first sheet really is.
for (const link of document.querySelectorAll('[data-toc]')) {
  const at = pages.indexOf(document.getElementById(`ch-${link.dataset.toc}`));
  const slot = link.querySelector('[data-toc-page]');
  if (at >= 0 && slot) slot.textContent = `עמוד ${two(at + 1)}`;
}

/* ---------------------------------------------------------------- 2. the route */

/**
 * One thin line runs down the side of the book from its first sheet to its last: the road. At every chapter it
 * goes round a small roundabout (LOG's O), one side then the other. It is orange as far as you have come, and
 * the dot travels on it. The roundabouts are links. It is state, not animation, so it also runs with reduced motion.
 */
const chapters = pages.filter((p) => p.id.startsWith('ch-'));
if (chapters.length) {
  const route = document.createElement('nav');
  route.className = 'bk-route';
  route.setAttribute('aria-label', 'פרקי הספר');
  document.body.append(route);
  const R = 7, X = 22, PAD = 26;
  let length = 0, done = null, dot = null, label = null, stops = [];

  const build = () => {
    const h = innerHeight;
    const y = (i) => PAD + (pages.indexOf(chapters[i]) / (pages.length - 1)) * (h - 2 * PAD);
    let d = `M${X} 0`;
    chapters.forEach((_, i) => { d += ` V${(y(i) - R).toFixed(1)} A${R} ${R} 0 0 ${i % 2} ${X} ${(y(i) + R).toFixed(1)}`; });
    d += ` V${h}`;
    route.innerHTML = `<svg width="${X * 2}" height="${h}" aria-hidden="true"><path class="bk-route__road" d="${d}"/>${chapters.map((_, i) =>
      `<path class="bk-route__island" d="M${X} ${(y(i) - R).toFixed(1)} A${R} ${R} 0 0 ${1 - (i % 2)} ${X} ${(y(i) + R).toFixed(1)}"/>`).join('')}<path class="bk-route__done" d="${d}"/><circle class="bk-route__dot" r="5"/></svg>`
      + chapters.map((p, i) => `<a href="#${p.id}" style="top:${y(i).toFixed(1)}px"><span>${p.dataset.chapter} · ${p.getAttribute('aria-label')}</span></a>`).join('')
      + '<p class="bk-route__page" aria-hidden="true"></p>';
    done = route.querySelector('.bk-route__done');
    dot = route.querySelector('.bk-route__dot');
    label = route.querySelector('.bk-route__page');
    stops = [...route.querySelectorAll('a')];
    length = done.getTotalLength();
    done.style.strokeDasharray = `${length} ${length}`;
    update();
  };

  const update = () => {
    if (!done) return;
    const max = document.documentElement.scrollHeight - innerHeight;
    const p = max > 0 ? Math.min(1, Math.max(0, scrollY / max)) : 0;
    done.style.strokeDashoffset = String(length * (1 - p));
    const at = done.getPointAtLength(length * p);
    dot.setAttribute('cx', at.x.toFixed(1));
    dot.setAttribute('cy', at.y.toFixed(1));
    // Sheets stack (sticky), so the current one is the last whose top has come past the middle of the window.
    let page = 0;
    pages.forEach((el, i) => { if (el.getBoundingClientRect().top <= innerHeight / 2) page = i; });
    label.textContent = two(page + 1);
    label.style.top = `${at.y.toFixed(1)}px`;
    const ch = pages[page].dataset.chapter;
    stops.forEach((a, i) => a.classList.toggle('is-current', chapters[i].dataset.chapter === ch));
  };

  addEventListener('scroll', update, { passive: true });
  addEventListener('resize', build, { passive: true });
  build();
}

/* ---------------------------------------------------------------- 3. motion */

const mm = gsap.matchMedia();
mm.add('(prefers-reduced-motion: no-preference)', () => {
  // Arrow keys, Page Up/Down and Space turn the pages, one sheet at a time.
  const turn = (e) => {
    if (e.target.closest('input, textarea, select, [contenteditable]') || e.metaKey || e.ctrlKey || e.altKey) return;
    const dir = ['ArrowDown', 'PageDown', ' '].includes(e.key) && !e.shiftKey ? 1 : ['ArrowUp', 'PageUp'].includes(e.key) || (e.key === ' ' && e.shiftKey) ? -1 : 0;
    if (!dir) return;
    // Stacked sheets hold at the top, so where each one starts is counted, not measured: equal sheets, equal gaps.
    const step = pages[0].offsetHeight + parseFloat(getComputedStyle(pages[0].parentElement).rowGap || 0);
    const at = Math.round(scrollY / step);
    const next = gsap.utils.clamp(0, pages.length - 1, at + dir);
    if (next === at && Math.abs(scrollY - at * step) < 4) return;
    e.preventDefault();
    gsap.to(window, { scrollTo: next * step, duration: 1, ease: 'power3.inOut', overwrite: true });
  };
  addEventListener('keydown', turn);

  const undo = [];
  const rise = { yPercent: 108, duration: 1.1, stagger: 0.09, ease: 'power4.out' };
  const land = { scale: 0, duration: 0.7, ease: 'back.out(3)', clearProps: 'transform' };

  /** The headline comes up out of line masks; its orange period lands after its words. */
  function headline(tl, page, at) {
    const title = page.querySelector('.bk-title');
    if (!title) return;
    const dot = title.querySelector('.bk-dot');
    const split = SplitText.create(title, { type: 'lines', mask: 'lines' });
    tl.from(split.lines, rise, at);
    if (dot) tl.from(dot, land, '-=0.5');
    tl.add(() => split.revert(), '>');
  }

  /** Two lines run in from their edges and cross; the dot lands where they meet. */
  function cross(tl, page, at) {
    const el = page.querySelector(':scope > .bk-cross');
    if (!el) return;
    tl.from(el.querySelector('.bk-cross__v'), { scaleY: 0, duration: 1.3, ease: 'power3.inOut' }, at)
      .from(el.querySelector('.bk-cross__h'), { scaleX: 0, duration: 1.3, ease: 'power3.inOut' }, at + 0.15)
      .from(el.querySelector('.bk-cross__dot'), land, at + 1.1);
  }

  // The cover opens the book: the photograph settles from close up, the lines cross, the name rises.
  const cover = document.querySelector('.bk-cover');
  if (cover) {
    const tl = gsap.timeline({ delay: 0.15 });
    const img = cover.querySelector('.bk-photo img');
    if (img) tl.from(img, { scale: 1.28, duration: 2.6, ease: 'expo.out' }, 0).from(cover.querySelector('.bk-photo'), { opacity: 0, duration: 1.2, ease: 'power2.out' }, 0);
    cross(tl, cover, 0.25);
    tl.from(cover.querySelector('.bk-cover__logo'), { opacity: 0, y: -14, duration: 0.9, ease: 'expo.out' }, 0.5)
      .from(cover.querySelector('.bk-kicker'), { opacity: 0, x: 24, duration: 0.9, ease: 'expo.out' }, 0.6);
    // The book's name is a plan that rises into a building: flat at first, then it gains its depth and leans.
    const name = cover.querySelector('.bk-title');
    if (name) {
      const depth = depthText(name);
      undo.push(depth.undo);
      tl.from(depth.stage, { yPercent: 36, opacity: 0, duration: 1.2, ease: 'expo.out' }, 0.7)
        .fromTo(depth.stage, { '--grow': 0, rotationX: 0, rotationY: 0 }, { '--grow': 1, rotationX: -4, rotationY: 7, duration: 2, ease: 'expo.out' }, 1)
        .from(depth.stage.querySelector('.fx-depth__face .bk-dot'), land, 1.6)
        .add(() => undo.push(depthTilt(depth.stage, cover)));
    }
    tl.from(cover.querySelectorAll('.bk-lede, .bk-cover__version'), { opacity: 0, y: 16, duration: 1, stagger: 0.12, ease: 'expo.out' }, 1.25);
    // Leaving it, the camera keeps descending into the roundabout.
    if (img) gsap.to(img, { scale: 1.14, yPercent: 6, ease: 'none', scrollTrigger: { trigger: cover, start: 'top top', end: 'bottom top', scrub: true } });
  }

  // The stack: as the next sheet slides over, the one under it steps back and dims.
  pages.forEach((page, i) => {
    const next = pages[i + 1];
    if (!next) return;
    gsap.to(page, { scale: 0.92, '--dim': 0.62, ease: 'none', scrollTrigger: { trigger: next, start: 'top bottom', end: 'top 12%', scrub: true } });
  });

  // Every other sheet plays once, as it arrives.
  const items = [
    '.bk-rules > li', '.bk-rows > li', '.bk-how__grid > li', '.bk-toc__list > li', '.bk-junction__words > li', '.bk-vision__cols > div',
    '.bk-tone__cols > div > h3', '.bk-quote', '.bk-logo__versions > li', '.bk-wrong__grid > li', '.bk-anatomy__notes > div', '.bk-anatomy__language > *',
    '.bk-swatch figcaption', '.bk-colors__foot > div', '.bk-table tr', '.bk-type__faces > li', '.bk-formats__row > li', '.bk-lines__kinds > li > :not(.ln)',
    '.bk-circles__kinds > li > :not(.circ)', '.bk-minis > li > :not(.bk-mini)', '.bk-photos__cols h3', '.bk-scrims__places > ul:first-child > li > :not(.bk-scrims__case)',
    '.bk-scrims__person > p', '.bk-social__frames .bk-cap', '.bk-highlights__row > li', '.bk-banner h3', '.bk-banner__text', '.bk-fb__cols h3', '.bk-fb__cols h3 + p',
    '.bk-sign', '.bk-note', '.bk-grid__sheet > p', '.bk-timeline li > :not(.circ)',
  ].join(',');
  const unroll = ':scope .bk-photo:not(.bk-photo--bleed), :scope .bk-mini, :scope .bk-frame, :scope .bk-scrims__case, :scope .bk-scrims__portrait, :scope .bk-grid__sheet, :scope .bk-format, :scope .bk-banner__avatar';

  document.fonts.ready.then(() => {
    for (const page of pages) {
      if (page === cover || page.classList.contains('bk-lock')) continue;
      const tl = gsap.timeline({ scrollTrigger: { trigger: page, start: 'top 74%', once: true }, defaults: { ease: 'expo.out' } });

      const bleed = page.querySelector(':scope > .bk-photo--bleed img');
      if (bleed) {
        tl.from(bleed, { scale: 1.2, duration: 2.2 }, 0);
        gsap.fromTo(bleed, { yPercent: -4 }, { yPercent: 4, ease: 'none', scrollTrigger: { trigger: page, start: 'top bottom', end: 'bottom top', scrub: true } });
      }
      cross(tl, page, 0.1);

      // A chapter's number rises into place and keeps drifting, slower than the page.
      const numeral = page.querySelector('.bk-opener__n');
      if (numeral) {
        tl.from(numeral, { yPercent: 26, opacity: 0, duration: 1.8 }, 0.1);
        gsap.fromTo(numeral, { xPercent: -5 }, { xPercent: 5, ease: 'none', scrollTrigger: { trigger: page, start: 'top bottom', end: 'bottom top', scrub: true } });
      }
      const kicker = page.querySelector('.bk-head .bk-kicker, .bk-opener__body .bk-kicker');
      if (kicker) tl.from(kicker, { opacity: 0, x: 24, duration: 0.9 }, 0);
      headline(tl, page, 0.08);
      // "We are the junction" arrives through heat over asphalt and settles; a pointer passing over stirs it again.
      if (page.classList.contains('bk-junction')) {
        const title = page.querySelector('.bk-title');
        tl.add(warpText(title), 0.08);
        const stir = () => warpText(title, { from: 26, duration: 1.4 });
        title.addEventListener('pointerenter', stir);
        undo.push(() => title.removeEventListener('pointerenter', stir));
      }
      // The leading idea carries the place inside its letters.
      const masked = page.querySelector('[data-fx-masked]');
      if (masked) undo.push(maskedText(masked, masked.dataset.fxMasked, page));
      const lede = page.querySelector('.bk-head .bk-lede');
      if (lede) tl.from(lede, { opacity: 0, y: 16, duration: 1 }, 0.4);

      // Structure first: the lines of the page run from the start side to the edge.
      const lines = page.querySelectorAll(':scope .bk-line, :scope .bk-timeline__line, :scope .bk-chart__avg');
      if (lines.length) tl.from(lines, { scaleX: 0, duration: 1.3, ease: 'power3.inOut', stagger: 0.12 }, 0.35);
      const ticks = page.querySelectorAll('.bk-junction__tick');
      if (ticks.length) tl.from(ticks, { scaleY: 0, duration: 0.6, stagger: 0.14, ease: 'power3.out' }, 0.9);

      // Pictures and specimens unroll from their top edge; the photograph inside settles from a little closer.
      const frames = [...page.querySelectorAll(unroll)].filter((f) => !f.parentElement.closest('.bk-mini, .bk-frame, .bk-scrims__case, .bk-scrims__portrait'));
      if (frames.length) {
        tl.fromTo(frames, { clipPath: 'inset(0% 0% 100% 0%)' }, { clipPath: 'inset(0% 0% 0% 0%)', duration: 1.25, stagger: 0.08, clearProps: 'clipPath' }, 0.45);
        const inner = frames.map((f) => f.querySelector('img')).filter(Boolean);
        if (inner.length) tl.from(inner, { scale: 1.18, duration: 1.8, stagger: 0.08, clearProps: 'transform' }, 0.45);
      }

      // Colour arrives as paint: chips fill from the top, the ratio bar from the start side.
      const chips = page.querySelectorAll('.bk-swatch__chip');
      if (chips.length) tl.from(chips, { scaleY: 0, duration: 1, stagger: 0.07, ease: 'power4.out' }, 0.45);
      const ratio = page.querySelectorAll('.bk-ratio__bar i, .bk-nightfam__chips i');
      if (ratio.length) tl.from(ratio, { scaleX: 0, duration: 0.9, stagger: 0.06, ease: 'power3.out' }, 0.9);

      // A bar grows to exactly its number, and the number counts with it.
      const bars = page.querySelectorAll(':scope > .bk-data__cols .bk-chart__bar');
      if (bars.length) {
        tl.from(bars, { scaleY: 0, duration: 1.2, stagger: 0.06, ease: 'power4.out' }, 0.6);
        page.querySelectorAll(':scope > .bk-data__cols .bk-chart__value').forEach((el, i) => {
          const end = Number(el.textContent.replace(/[^\d]/g, ''));
          const o = { v: 0 };
          const fmt = new Intl.NumberFormat('he-IL');
          tl.from(el, { opacity: 0, duration: 0.3 }, 0.6 + i * 0.06)
            .to(o, { v: end, duration: 1.2, ease: 'power4.out', onUpdate: () => { el.textContent = fmt.format(Math.round(o.v)); } }, 0.6 + i * 0.06);
        });
      }

      // Circles on a line land on it one after another (the drawn ones belong to lines/draw.js).
      const circles = page.querySelectorAll('.bk-timeline .circ, .bk-highlights__row .circ');
      if (circles.length) tl.from(circles, { scale: 0, duration: 0.8, stagger: 0.1, ease: 'back.out(1.8)', clearProps: 'transform' }, 0.8);

      // Then the words, row by row.
      const rows = [...page.querySelectorAll(items)].filter((el) => !el.closest('.bk-mini'));
      if (rows.length) tl.from(rows, { opacity: 0, y: 22, duration: 0.9, stagger: { each: 0.045, from: 'start' }, clearProps: 'transform,opacity' }, 0.55);

      const folio = page.querySelector('.bk-folio');
      if (folio) tl.from(folio, { opacity: 0, duration: 0.8 }, 0.9);
    }
    ScrollTrigger.refresh();
  });

  return () => { removeEventListener('keydown', turn); undo.forEach((f) => f()); };
});

// The line system's specimens draw the way they do on the site.
drawLines();
