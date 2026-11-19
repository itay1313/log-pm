/**
 * ScrollVelocity (after React Bits), on the client logo strip: it runs on its own, forward (to the left, the way
 * Hebrew reads on), and scrolling pushes it: faster the faster the page moves, backwards while the page scrolls up,
 * easing back to its own pace after. Hovering still stops it. It only works while on screen.
 * Without motion it is the still, wrapped row from pages.css.
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

export function velocityStrip(selector) {
  const mm = gsap.matchMedia();
  mm.add('(prefers-reduced-motion: no-preference)', () => {
    const undo = [];
    for (const run of document.querySelectorAll(selector)) {
      const track = run.firstElementChild;
      if (!track) continue;
      const count = Number(getComputedStyle(run).getPropertyValue('--count')) || 8;
      const pace = 50 / (count * 3.2); // % of the track per second: the CSS loop's own speed
      const wrap = gsap.utils.wrap(-50, 0);
      const setX = gsap.quickSetter(track, 'xPercent');
      let x = 0, speed = 1, boost = 0, dir = 1, hover = false;

      track.style.animation = 'none'; // the CSS loop hands over to this one
      const st = ScrollTrigger.create({
        trigger: run, start: 'top bottom', end: 'bottom top',
        onUpdate: (self) => {
          dir = self.direction;
          boost = Math.max(boost, gsap.utils.clamp(0, 6, Math.abs(self.getVelocity()) / 250));
        },
      });
      const tick = (time, dt) => {
        if (!st.isActive) return;
        boost *= 0.92;
        speed += ((hover ? 0 : dir * (1 + boost)) - speed) * 0.08;
        x = wrap(x - speed * pace * Math.min(dt, 64) / 1000);
        setX(x);
      };
      const enter = () => { hover = true; };
      const leave = () => { hover = false; };
      run.addEventListener('pointerenter', enter);
      run.addEventListener('pointerleave', leave);
      gsap.ticker.add(tick);
      undo.push(() => {
        gsap.ticker.remove(tick);
        st.kill();
        run.removeEventListener('pointerenter', enter);
        run.removeEventListener('pointerleave', leave);
        track.style.animation = '';
        gsap.set(track, { clearProps: 'transform' });
      });
    }
    return () => undo.forEach((f) => f());
  });
  return mm;
}
