/**
 * The web brand book (/brand).
 *
 * 1. Live values: colour codes, contrast ratios and the contents' page numbers are read from the page itself,
 *    so the book can't disagree with the site.
 * 2. The route: one thin line down the side of the whole book, round a small roundabout at every chapter,
 *    orange as far as you've come, with the dot travelling on it.
 * 3. The riso fields: a quiet print texture behind the book and down the side of the night sheets, which a
 *    pointer or a finger stirs.
 * 4. Motion (GSAP): the cover opens like the site does, lines run to their edges and cross, the dot lands last;
 *    every sheet then plays once as it arrives: headline out of its mask, the period landing, lines drawing,
 *    photographs unrolling, bars growing to their numbers. Arrow keys turn the pages.
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
import { riso } from '../fx/riso.js';

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

/**
 * Where each sheet starts in the scroll. Stacked sheets hold at the top of the window, so this is counted from
 * the layout (heights, the gap, and the room a held sheet leaves after itself), never measured from the screen.
 */
function starts() {
  const gap = parseFloat(getComputedStyle(pages[0].parentElement).rowGap) || 0;
  let y = 0;
  return pages.map((p) => {
    const at = y;
    y += p.offsetHeight + gap + (parseFloat(getComputedStyle(p).marginBottom) || 0);
    return at;
  });
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
    const tops = starts();
    const y = (i) => PAD + (tops[pages.indexOf(chapters[i])] / tops[tops.length - 1]) * (h - 2 * PAD);
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

/* ---------------------------------------------------------------- 3. the riso fields */

/**
 * A quiet print texture you can touch (src/fx/riso.js): behind the whole book, where it shows between the sheets,
 * and down the end side of the night sheets that open and close things. With reduced motion it is a still print.
 */
if (!document.querySelector('.bk-lock')) {
  const still = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const field = (cls) => { const c = document.createElement('canvas'); c.className = cls; c.setAttribute('aria-hidden', 'true'); return c; };
  const desk = field('bk-riso bk-riso--desk');
  document.body.prepend(desk);
  riso(desk, window, { cell: 7, density: 0.36, still });
  for (const page of document.querySelectorAll('.bk-cover, .bk-opener, .bk-remember, .bk-end')) {
    const side = field('bk-riso bk-riso--side');
    page.append(side);
    riso(side, page, { cell: 6, density: 0.4, still });
  }
}

/* ---------------------------------------------------------------- 4. motion */

const mm = gsap.matchMedia();
mm.add({ motion: '(prefers-reduced-motion: no-preference)', wide: '(min-width: 1100px)' }, (ctx) => {
  if (!ctx.conditions.motion) return undefined;
  const wide = ctx.conditions.wide; // on a phone the sheets reflow: they don't stack, and nothing holds
  // Arrow keys, Page Up/Down and Space turn the pages, one sheet at a time.
  const turn = (e) => {
    if (e.target.closest('input, textarea, select, [contenteditable]') || e.metaKey || e.ctrlKey || e.altKey) return;
    const dir = ['ArrowDown', 'PageDown', ' '].includes(e.key) && !e.shiftKey ? 1 : ['ArrowUp', 'PageUp'].includes(e.key) || (e.key === ' ' && e.shiftKey) ? -1 : 0;
    if (!dir || !wide) return; // a phone scrolls the reflowed sheets as an ordinary page
    const tops = starts();
    let at = 0;
    tops.forEach((t, i) => { if (t <= scrollY + 4) at = i; });
    // Going back from the middle of a sheet returns to its start first.
    const next = dir < 0 && scrollY - tops[at] > 8 ? at : gsap.utils.clamp(0, pages.length - 1, at + dir);
    if (Math.abs(tops[next] - scrollY) < 4) return;
    e.preventDefault();
    gsap.to(window, { scrollTo: tops[next], duration: 1, ease: 'power3.inOut', overwrite: true });
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
    if (!next || !wide) return;
    gsap.to(page, { scale: 0.92, '--dim': 0.62, ease: 'none', scrollTrigger: { trigger: next, start: 'top bottom', end: 'top 12%', scrub: true } });
  });

  // Three sheets hold while a scene plays with the scroll. Each has room after itself for it (.bk-page--hold).
  const scene = (page) => gsap.timeline({ defaults: { ease: 'none' }, scrollTrigger: { trigger: page, start: () => `top ${parseFloat(getComputedStyle(page).top) || 0}px`, end: () => `+=${innerHeight * 1.1}`, scrub: 0.6 } });

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

  // Every other sheet plays once, as it arrives.
  const items = [
    '.bk-rules > li', '.bk-rows > li', '.bk-how__grid > li', '.bk-toc__list > li', '.bk-junction__words > li', '.bk-vision__cols > div',
    '.bk-tone__cols > div > h3', '.bk-quote', '.bk-logo__versions > li', '.bk-wrong__grid > li', '.bk-anatomy__notes > div', '.bk-anatomy__language > *',
    '.bk-colors__foot > div', '.bk-icons__grid > li', '.bk-table tr', '.bk-type__faces > li', '.bk-formats__row > li', '.bk-lines__kinds > li > :not(.ln)',
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
      const held = wide && page.classList.contains('bk-page--hold'); // its scene runs with the scroll, above
      const lines = page.querySelectorAll(held ? ':scope .bk-line' : ':scope .bk-line, :scope .bk-timeline__line, :scope .bk-chart__avg');
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
      const bands = page.querySelector('.bk-bands');
      if (bands) tl.fromTo(bands, { clipPath: 'inset(0% 0% 100% 0%)' }, { clipPath: 'inset(0% 0% 0% 0%)', duration: 1.3, ease: 'power4.out', clearProps: 'clipPath' }, 0.4);
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
      const circles = page.querySelectorAll(held ? '.bk-highlights__row .circ' : '.bk-timeline .circ, .bk-highlights__row .circ');
      if (circles.length) tl.from(circles, { scale: 0, duration: 0.8, stagger: 0.1, ease: 'back.out(1.8)', clearProps: 'transform' }, 0.8);

      // Then the words, row by row.
      const rows = [...page.querySelectorAll(items)].filter((el) => !el.closest('.bk-mini') && !(held && el.closest('.bk-timeline')));
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
