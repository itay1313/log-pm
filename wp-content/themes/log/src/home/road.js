/**
 * The road (docs/home-scroll-story.md, chapters 3 and 4): after the map, the drive goes on along a night road.
 *
 * Chapter 3: each practice area is a gantry sign over the road. The scroll drives forward; a sign comes out of
 * the distance, stands readable for a while, and passes overhead as the next one comes. The markings on the
 * asphalt travel with it. Each sign is the area's link to its projects.
 *
 * Chapter 4: an exit sign stands on the start shoulder, "יציאה · חברת הבקרה". Its face fades, and the window it
 * leaves opens until the control company's world fills the screen: the two worlds meet only through an exit.
 *
 * Plain CSS 3D and ScrollTrigger, no WebGL, so it runs whenever motion is allowed (main.js). The DOM is the
 * ordinary pair of sections; without this module they read as rows and a night band.
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

const Z_READ = -600; // where a sign is held for reading (px, in the scene's perspective of 800)
const GAP = 2400;    // road between two gantries
const EXIT_RUN = 1600; // road driven while the exit opens

const clamp01 = (x) => Math.min(1, Math.max(0, x));
const span = ([a, b], p) => clamp01((p - a) / (b - a));
const ease = (t) => t * t * (3 - 2 * t);
const lerp = (a, b, t) => a + (b - a) * t;

export function initRoad(road) {
  const gantries = road.querySelector('.gantries');
  const exit = road.querySelector('.exit');
  const plane = road.querySelector('.road__plane');
  if (!exit || !plane) return;
  document.documentElement.classList.add('road-on');

  const signs = gantries ? [...gantries.querySelectorAll('.domain')] : [];
  const n = signs.length;
  if (gantries) gantries.style.setProperty('--n', n);

  const exitSign = exit.querySelector('.exit-sign');
  const world = exit.querySelector('.exit-world');
  const body = exit.querySelector('.exit-world__body');
  // The window keeps the sign's keyline while it opens.
  const frame = document.createElement('span');
  frame.className = 'exit-frame';
  frame.setAttribute('aria-hidden', 'true');
  exit.querySelector('.exit__inner').append(frame);

  let q = 0, r = 0;
  function update() {
    // Gantries: each one's depth follows the scroll; it reads at Z_READ, fades in from the far end, out overhead.
    signs.forEach((li, i) => {
      const z = Z_READ + (q * n - (i + 0.5)) * GAP;
      const o = span([-4200, -3000], z) * (1 - span([250, 560], z));
      li.style.transform = `translate3d(0, -260px, ${z.toFixed(1)}px)`;
      li.style.opacity = o.toFixed(3); // opacity only: a sign out of sight must still take keyboard focus
      li.style.zIndex = String(10000 + Math.round(z)); // nearer gantries in front
      const link = li.querySelector('.domain__link');
      if (link) link.style.pointerEvents = o > 0.6 && z < 200 ? 'auto' : 'none';
    });
    plane.style.setProperty('--travel', `${(-(q * n * GAP + r * EXIT_RUN)).toFixed(1)}px`);

    // The exit: the sign comes up with its chapter, its face fades, the window opens, the world's legend rises.
    const face = 1 - span([0.36, 0.44], r);
    const open = ease(span([0.42, 0.78], r));
    exitSign.style.transform = `scale(${lerp(0.82, 1, ease(span([0, 0.34], r))).toFixed(3)})`;
    exitSign.style.opacity = face.toFixed(3);
    exitSign.style.visibility = face > 0.001 ? 'visible' : 'hidden';
    const w = world.getBoundingClientRect(), s = exitSign.getBoundingClientRect();
    if (r < 0.36) {
      world.style.clipPath = 'inset(50% 50% 50% 50%)';
      frame.style.opacity = 0;
    } else {
      const [t, rt, b, l] = [s.top - w.top, w.right - s.right, w.bottom - s.bottom, s.left - w.left].map((v) => Math.max(0, v * (1 - open)));
      world.style.clipPath = `inset(${t.toFixed(1)}px ${rt.toFixed(1)}px ${b.toFixed(1)}px ${l.toFixed(1)}px round ${(10 * (1 - open)).toFixed(1)}px)`;
      // The frame sits on the same rectangle, inset by the keyline's 8px, and leaves once the world is open.
      const box = frame.parentElement.getBoundingClientRect(), k = 8 * (1 - open);
      Object.assign(frame.style, {
        transform: `translate(${(w.left + l - box.left + k).toFixed(1)}px, ${(w.top + t - box.top + k).toFixed(1)}px)`,
        width: `${Math.max(0, w.width - l - rt - 2 * k).toFixed(1)}px`,
        height: `${Math.max(0, w.height - t - b - 2 * k).toFixed(1)}px`,
        opacity: (1 - span([0.74, 0.84], r)).toFixed(3),
      });
    }
    const legend = span([0.7, 0.84], r);
    body.style.opacity = legend.toFixed(3);
    body.style.transform = `translateY(${((1 - ease(legend)) * 40).toFixed(1)}px)`;
  }

  if (gantries && n) {
    ScrollTrigger.create({
      trigger: gantries, start: 'top top', end: 'bottom bottom',
      onUpdate: (self) => { q = self.progress; update(); }, onRefresh: (self) => { q = self.progress; update(); },
    });
  }
  const exitTrigger = ScrollTrigger.create({
    trigger: exit, start: 'top bottom', end: 'bottom bottom',
    onUpdate: (self) => { r = self.progress; update(); }, onRefresh: (self) => { r = self.progress; update(); },
  });

  // Keyboard: a sign or the control link that takes focus brings its moment of the road on screen.
  const goTo = (trigger, progress) => {
    const y = trigger.start + (trigger.end - trigger.start) * progress;
    if (window.LOGlenis) window.LOGlenis.scrollTo(y, { immediate: true }); else window.scrollTo(0, y);
  };
  signs.forEach((li, i) => li.querySelector('.domain__link')?.addEventListener('focus', () => {
    const t = ScrollTrigger.getAll().find((x) => x.trigger === gantries);
    if (t) goTo(t, (i + 0.5) / n);
  }));
  body.querySelector('a')?.addEventListener('focus', () => goTo(exitTrigger, 0.9));

  dispatchEvent(new Event('resize')); // the chapters just grew: the lane line and the triggers measure again
  ScrollTrigger.refresh();
}
