/**
 * The drive (docs/home-scroll-story.md): the home page as one journey with the map pinned behind it.
 *
 * At the top the visitor has the map. The first wheel or swipe down takes the camera from them and the scroll
 * moves it from then on; scrolling back to the top lands it exactly where they left it and hands it back.
 *
 * Chapter 1, the whole country: the camera rises from Gush Dan to all of Israel seen from over the sea,
 * every project lights up as a beacon rippling out from where the camera stood, the figures count with them,
 * and the legend lights word by word.
 *
 * Only loaded with WebGL and with motion allowed (hero.js). Without it the chapter is an ordinary night section.
 */
import Lenis from 'lenis';
import 'lenis/dist/lenis.css';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';

gsap.registerPlugin(ScrollTrigger, SplitText);

// Israel from over the sea, looking east: the coast runs across the screen, the sea in front holds the legend.
const WIDE = { tx: -34000, tz: -38000, radius: 380000, phi: 0.74, theta: -Math.PI / 2 - 0.1 };
// Phones stand the country upright, north up, in the band between the figures and the legend.
const TALL = { tx: 2000, tz: -42000, radius: 560000, phi: 0.42, theta: -0.06 };
const phone = matchMedia('(max-width: 760px)');
const DRIFT = 0.16; // the camera keeps turning this far while the chapter reads

// Where each beat sits along the drive, as scroll progress 0 → 1.
const AT = {
  ui: [0, 0.05],        // the map's tools fade
  rise: [0, 0.34],      // the camera climbs to the country while the legend scrolls in
  beacons: [0.3, 0.6],  // projects light up, the figures count with them
  title: [0.3, 0.42],
  prose: [0.44, 0.78],
  links: [0.76, 0.84],
};

const lerp = (a, b, t) => a + (b - a) * t;
const clamp01 = (x) => Math.min(1, Math.max(0, x));
const span = ([a, b], p) => clamp01((p - a) / (b - a));
const ease = (t) => t * t * (3 - 2 * t);

export function initDrive(hero, scene, points) {
  const drive = document.getElementById('drive');
  const chapter = drive?.querySelector('.drive-country');
  if (!drive || !chapter) return;

  const root = document.documentElement;
  root.classList.add('drive-on');
  dispatchEvent(new Event('resize')); // the chapter just grew: the lane line and the triggers measure again
  smoothScroll();

  const tools = [hero.querySelector('.hero-lead'), hero.querySelector('.hero-bar')].filter(Boolean);
  const inner = chapter.querySelector('.drive-country__inner');
  const title = chapter.querySelector('.section__title');
  const prose = chapter.querySelector('.section__prose');
  const links = chapter.querySelector('.link-row');
  const figureList = chapter.querySelector('.drive-figures');
  const figures = [...chapter.querySelectorAll('.drive-figures dd[data-count]')].map((dd) => ({ dd, n: Number(dd.dataset.count) }));
  const fmt = new Intl.NumberFormat('he-IL');

  const home = scene.homeView();
  scene.setBeacons(points, home.tx, home.tz);

  // The legend lights word by word: dim words are already there to read, never hidden.
  const titleWords = SplitText.create(title, { type: 'words' }).words;
  const proseWords = SplitText.create(prose, { type: 'words' }).words;
  const dim = 0.16;

  let from = null; // the view the visitor left, while the scroll holds the camera

  function take() {
    from = scene.hold();
    hero.classList.add('is-driving');
    document.body.classList.remove('at-map'); // the lane-split plates go back up to the header
    tools.forEach((el) => (el.inert = true));
    document.activeElement?.closest?.('.hero') && document.activeElement.blur();
  }
  function giveBack() {
    scene.setView(from);
    from = null;
    scene.release();
    hero.classList.remove('is-driving');
    document.body.classList.add('at-map');
    tools.forEach((el) => (el.inert = false));
  }

  function render(p) {
    if (p > 0.001 && !from) take();
    else if (p <= 0.001 && from) giveBack();

    gsap.set(tools, { opacity: 1 - span(AT.ui, p) });

    if (from) {
      // Climb in log space and let the target follow the radius, so the place under the camera stays put
      // until the country opens out.
      const COUNTRY = phone.matches ? TALL : WIDE;
      const t = ease(span(AT.rise, p));
      const radius = Math.exp(lerp(Math.log(from.radius), Math.log(COUNTRY.radius), t));
      const k = clamp01((radius - from.radius) / (COUNTRY.radius - from.radius));
      let dTheta = COUNTRY.theta - from.theta;
      dTheta = Math.atan2(Math.sin(dTheta), Math.cos(dTheta));
      const drift = DRIFT * span([AT.rise[1], 1], p);
      scene.setView({
        tx: lerp(from.tx, COUNTRY.tx, k),
        tz: lerp(from.tz, COUNTRY.tz, k),
        radius,
        phi: lerp(from.phi, COUNTRY.phi, t),
        theta: from.theta + dTheta * t + drift,
      });
    }

    const lit = span(AT.beacons, p);
    scene.lightBeacons(lit);
    for (const { dd, n } of figures) dd.textContent = fmt.format(Math.round(n * lit));
    if (figureList) figureList.style.opacity = span([AT.beacons[0] - 0.06, AT.beacons[0]], p).toFixed(3); // no zeros waiting on screen

    inner.style.setProperty('--scrim', span([0.12, 0.34], p).toFixed(3));
    light(titleWords, span(AT.title, p));
    light(proseWords, span(AT.prose, p));
    gsap.set(links, { opacity: lerp(dim, 1, span(AT.links, p)) });
  }

  /** Words lit up to t (0 → 1), each fading in over a few neighbours so the edge is soft. */
  function light(words, t) {
    const n = words.length, soft = 3;
    const head = t * (n + soft);
    for (let i = 0; i < n; i++) words[i].style.opacity = lerp(dim, 1, clamp01((head - i) / soft)).toFixed(3);
  }

  ScrollTrigger.create({
    trigger: drive,
    start: 'top top',
    end: 'bottom bottom',
    onUpdate: (self) => render(self.progress),
    onRefresh: (self) => render(self.progress),
  });
  ScrollTrigger.refresh();
}

/**
 * Lenis smooths the wheel and hands ScrollTrigger the same time base. Overlays with their own scroll keep it,
 * Ctrl/⌘ + wheel stays the map's zoom, and the page stands still under the menu and the phone sheets.
 */
function smoothScroll() {
  const lenis = new Lenis({
    prevent: (node) => !!node.closest?.('dialog, .hero-drawer, .hero-suggest, .hero-card, .hero-filters, [data-lenis-prevent]'),
    virtualScroll: ({ event }) => !(event.ctrlKey || event.metaKey),
  });
  lenis.on('scroll', ScrollTrigger.update);
  gsap.ticker.add((time) => lenis.raf(time * 1000));
  gsap.ticker.lagSmoothing(0);
  const body = document.body;
  new MutationObserver(() => {
    if (body.classList.contains('menu-open') || body.classList.contains('sheet-open')) lenis.stop();
    else lenis.start();
  }).observe(body, { attributes: true, attributeFilter: ['class'] });
  window.LOGlenis = lenis;
}
