/**
 * The road (docs/home-scroll-story.md, chapters 3 and 4): after the map, the drive goes on along a night road.
 *
 * Chapter 3: each practice area is a gantry sign over the road. The drive doesn't run at one speed: it slows
 * under each sign, so the sign stands at reading size for a good stretch of the scroll, then moves on quickly
 * to the next. The markings on the asphalt travel with it. Each sign is the area's link to its projects.
 * In the corner stands the index: every area with its count, the one overhead marked. A row drives to its sign,
 * so a visitor sees all the areas at once and can skip to the one they came for.
 *
 * Chapter 4: an exit sign stands on the start shoulder, "יציאה · חברת הבקרה". Its face fades, and the window it
 * leaves opens until the control company's world fills the screen: the two worlds meet only through an exit.
 * The world is a place, LOG's aerial roundabout at night, which settles as the window opens; then the legend
 * rises and the three roles are laid along one thin line.
 *
 * Plain CSS 3D and ScrollTrigger, no WebGL, so it runs whenever motion is allowed (main.js). The DOM is the
 * ordinary pair of sections; without this module they read as rows and a night band.
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

// Where a sign is held for reading, as its scale on screen (1 is its real size): close on a phone, where the sign
// is the screen's width, a step back on a wide screen. A short window holds it further off, so its top clears the header.
const READ = { wide: 0.87, phone: 0.95 };
const EYE = 800;       // the scene's perspective (drive.css)
const SIGN_TOP = 214;  // the sign's top edge above the eye line, at real size (px)
const GAP = 2400;      // road between two gantries
const EXIT_RUN = 1600; // road driven while the exit opens
const PERIOD = 230;    // one dash and its gap on the asphalt (--period in drive.css)
const DWELL = 0.82;    // how much of its speed the drive gives up under a sign (0: steady, 1: a full stop)
const FIRST = 0.3;     // the first sign is overhead this many signs after the road fills the screen (plus --lead)
const phone = matchMedia('(max-width: 760px)');

const clamp01 = (x) => Math.min(1, Math.max(0, x));
const span = ([a, b], p) => clamp01((p - a) / (b - a));
const ease = (t) => t * t * (3 - 2 * t);
const lerp = (a, b, t) => a + (b - a) * t;
/** The drive, in signs: slow where u is whole (a sign overhead), fast between. Never runs backwards while DWELL < 1. */
const glide = (u) => u - (DWELL * Math.sin(2 * Math.PI * u)) / (2 * Math.PI);

export function initRoad(road) {
  const gantries = road.querySelector('.gantries');
  const exit = road.querySelector('.exit');
  const plane = road.querySelector('.road__plane');
  if (!exit || !plane) return;
  document.documentElement.classList.add('road-on');

  const signs = gantries ? [...gantries.querySelectorAll('.domain')] : [];
  const n = signs.length;
  if (gantries) gantries.style.setProperty('--n', n);
  const rows = n ? buildIndex(gantries, signs, (i) => driveTo(i, false)) : [];
  const corner = gantries?.querySelector('.gantries__legend'); // the title and the index

  const exitSign = exit.querySelector('.exit-sign');
  const world = exit.querySelector('.exit-world');
  const body = exit.querySelector('.exit-world__body');
  const place = exit.querySelector('.exit-world__place img');
  const roles = exit.querySelector('.exit-roles');
  // The window keeps the sign's keyline while it opens.
  const frame = document.createElement('span');
  frame.className = 'exit-frame';
  frame.setAttribute('aria-hidden', 'true');
  exit.querySelector('.exit__inner').append(frame);

  let u = -2, r = 0, cur = -1; // u: the drive along the gantries, in signs; sign i is overhead at u = i + first
  // The depth a sign is read at. Measured on refresh, never inside the scroll frame.
  const header = document.querySelector('.site-header');
  let read = -120;
  // --lead (drive.css): extra road before the first sign, in signs, while the build chapter is still lifting off the
  // screen above it. The chapter is that much longer, and every sign that much later.
  let lead = 0, first = FIRST;
  function measure() {
    // From the header's edge down to the eye line. The edge is where the header ends in the window, not its height:
    // a signed-in editor has WordPress's bar above it.
    const room = innerHeight * 0.4 - ((header?.getBoundingClientRect().bottom || 84) + 14);
    const scale = Math.max(0.5, Math.min(phone.matches ? READ.phone : READ.wide, room / SIGN_TOP));
    read = EYE - EYE / scale;
    lead = gantries ? parseFloat(getComputedStyle(gantries).getPropertyValue('--lead')) || 0 : 0;
    first = FIRST + lead;
  }
  measure();
  function update() {
    // Gantries: each one's depth follows the drive; it reads at `read`, fades in from the far end, out overhead.
    signs.forEach((li, i) => {
      const z = read + glide(u - first - i) * GAP;
      const o = span([-4200, -3000], z) * (1 - span([250, 560], z));
      li.style.transform = `translate3d(0, -260px, ${z.toFixed(1)}px)`;
      li.style.opacity = o.toFixed(3); // opacity only: a sign out of sight must still take keyboard focus
      li.style.zIndex = String(10000 + Math.round(z)); // nearer gantries in front
      const link = li.querySelector('.domain__link');
      if (link) link.style.pointerEvents = o > 0.6 && z < 200 ? 'auto' : 'none';
    });
    // The markings: the plane slides towards us by the road driven, less every whole period of the dashes.
    plane.style.setProperty('--travel', `${(-((glide(u - first) * GAP + r * EXIT_RUN + 1e6) % PERIOD)).toFixed(1)}px`);
    // The index marks the sign overhead. It leaves with the last sign, before the chapter lets go of the screen,
    // so the open road before the exit is only road.
    const now = n ? Math.min(n - 1, Math.max(0, Math.round(u - first))) : -1;
    if (now !== cur) {
      cur = now;
      rows.forEach((row, i) => row.setAttribute('aria-current', i === cur ? 'true' : 'false'));
    }
    if (corner) {
      const o = 1 - span([n + lead - 0.62, n + lead - 0.12], u);
      corner.style.opacity = o.toFixed(3);
      corner.inert = o < 0.05;
    }

    // The exit: the sign comes up with its chapter, its face fades, the window opens, the world's legend rises.
    const face = 1 - span([0.34, 0.42], r);
    const open = ease(span([0.4, 0.72], r));
    exitSign.style.transform = `scale(${lerp(0.82, 1, ease(span([0, 0.34], r))).toFixed(3)})`;
    exitSign.style.opacity = face.toFixed(3);
    exitSign.style.visibility = face > 0.001 ? 'visible' : 'hidden';
    if (r < 0.34) {
      world.style.clipPath = 'inset(50% 50% 50% 50%)';
      frame.style.opacity = 0;
    } else {
      // Read only while the window opens: these reads come after this frame's writes and force a layout.
      const w = world.getBoundingClientRect(), s = exitSign.getBoundingClientRect();
      const [t, rt, b, l] = [s.top - w.top, w.right - s.right, w.bottom - s.bottom, s.left - w.left].map((v) => Math.max(0, v * (1 - open)));
      world.style.clipPath = `inset(${t.toFixed(1)}px ${rt.toFixed(1)}px ${b.toFixed(1)}px ${l.toFixed(1)}px round ${(10 * (1 - open)).toFixed(1)}px)`;
      // The frame sits on the same rectangle, inset by the keyline's 8px, and leaves once the world is open.
      const box = frame.parentElement.getBoundingClientRect(), k = 8 * (1 - open);
      Object.assign(frame.style, {
        transform: `translate(${(w.left + l - box.left + k).toFixed(1)}px, ${(w.top + t - box.top + k).toFixed(1)}px)`,
        width: `${Math.max(0, w.width - l - rt - 2 * k).toFixed(1)}px`,
        height: `${Math.max(0, w.height - t - b - 2 * k).toFixed(1)}px`,
        opacity: (1 - span([0.68, 0.76], r)).toFixed(3),
      });
    }
    if (place) place.style.transform = `scale(${lerp(1.16, 1, ease(span([0.4, 0.8], r))).toFixed(4)})`;
    const legend = span([0.64, 0.76], r);
    body.style.opacity = legend.toFixed(3);
    body.style.transform = `translateY(${((1 - ease(legend)) * 40).toFixed(1)}px)`;
    roles?.classList.toggle('is-in', r > 0.76); // CSS lays the lines and raises the rows, staggered (drive.css)
  }

  // The drive starts as the road comes up from the bottom of the screen, so the first sign is already on its way
  // when the road fills it: the measure runs from there to the end of the chapter, one "lap" of scroll per sign.
  let lane = null;
  const lap = (t) => Math.max(1, (t.end - t.start - innerHeight) / (n + lead));
  if (gantries && n) {
    // The first refresh runs inside create(), before `lane` is assigned: read the trigger it hands over.
    const drive = (self) => { u = (self.scroll() - self.start - innerHeight) / lap(self); update(); };
    lane = ScrollTrigger.create({ trigger: gantries, start: 'top bottom', end: 'bottom bottom', onUpdate: drive, onRefresh: (self) => { measure(); drive(self); } });
  }
  const exitTrigger = ScrollTrigger.create({
    trigger: exit, start: 'top bottom', end: 'bottom bottom',
    onUpdate: (self) => { r = self.progress; update(); }, onRefresh: (self) => { r = self.progress; update(); },
  });

  // Keyboard: a sign or the control link that takes focus brings its moment of the road on screen.
  const scrollTo = (y, now) => {
    if (window.LOGlenis) window.LOGlenis.scrollTo(y, now ? { immediate: true } : { duration: 1.2 });
    else window.scrollTo({ top: y, behavior: now ? 'auto' : 'smooth' });
  };
  const goTo = (trigger, progress) => scrollTo(trigger.start + (trigger.end - trigger.start) * progress, true);
  /** Bring sign i overhead: at once for the keyboard, as a short drive from the index. */
  function driveTo(i, now) {
    if (lane) scrollTo(lane.start + innerHeight + (i + first) * lap(lane), now);
  }
  signs.forEach((li, i) => li.querySelector('.domain__link')?.addEventListener('focus', () => driveTo(i, true)));
  body.querySelector('a')?.addEventListener('focus', () => goTo(exitTrigger, 0.9));

  dispatchEvent(new Event('resize')); // the chapters just grew: the lane line and the triggers measure again
  ScrollTrigger.refresh();
}

/**
 * The index of the practice areas: number, name, how many projects. It stands with the chapter's title in the
 * start corner, clear of the signs. Rows are buttons (they move the page, they don't leave it); the sign itself
 * stays the link to the area's projects. Returns the buttons, in order.
 */
function buildIndex(gantries, signs, onPick) {
  const inner = gantries.querySelector('.gantries__inner');
  const title = gantries.querySelector('.section__title');
  const legend = document.createElement('div');
  legend.className = 'gantries__legend';
  const list = document.createElement('ol');
  list.className = 'lanes';
  list.setAttribute('aria-label', 'תחומי הפעילות: מעבר לשלט');
  const cell = (cls, text, hide) => {
    const el = document.createElement('span');
    el.className = cls;
    el.textContent = text;
    if (hide) el.setAttribute('aria-hidden', 'true');
    return el;
  };
  const rows = signs.map((li, i) => {
    const row = document.createElement('li');
    const go = document.createElement('button');
    go.type = 'button';
    go.className = 'lanes__go';
    go.append(
      cell('lanes__n', String(i + 1).padStart(2, '0'), true),
      cell('lanes__name', li.querySelector('.domain__name')?.textContent.trim() || ''),
      cell('lanes__count', li.dataset.count || '', true),
    );
    go.addEventListener('click', () => onPick(i));
    row.append(go);
    list.append(row);
    return go;
  });
  const chapter = inner.querySelector(':scope > .chapter-n');
  if (chapter) legend.append(chapter);
  if (title) legend.append(title);
  legend.append(list);
  inner.prepend(legend);
  return rows;
}
