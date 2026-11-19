/**
 * The roundabout moment (inc/roundabout.php): an aerial roundabout at night shrinks until its ring is the logo's O,
 * the city falls away, the ring turns into the logo, the O's four quarters lock (counter-clockwise, the way traffic
 * turns here), the orange sweeps across the grey, and four thin lines run out of the O to the edges.
 *
 * Home: the stage is fixed over the first screen on the first page of a visit. At the end the map shows through,
 * the logo flies into the header, and the page's own opening starts (onOpen). Any input hurries it along.
 * Book: the stage is a sheet; it plays when it comes into view and holds its last frame. replay() runs it again.
 *
 * The camera is the SVG's viewBox: it is set every frame from one number, the screen size of one logo unit,
 * eased in log space so the rise never stalls high or rushes the end. Reduced motion never gets here.
 */
import { gsap } from 'gsap';

const LOGO_W = 1005.832; // the logo's viewBox width
const O = { x: 504.108, y: 190, R: 190 }; // the O's centre and outer radius, in logo units
const SCENE = 1200; // the drawing spans -SCENE..SCENE both ways

export function roundabout(stage, { mode = 'home', headerLogo = null, onOpen, onDone } = {}) {
  const svg = stage.querySelector('.ra__city');
  const world = svg?.querySelector('.ra__world');
  const logo = stage.querySelector('.ra__logo .logo');
  const mark = logo?.querySelector('svg');
  if (!svg || !world || !mark) return null;

  const fades = svg.querySelectorAll('.ra__fade');
  const lit = svg.querySelector('.ra__ring--lit');
  const lines = stage.querySelectorAll('.ra__line');
  const across = stage.querySelectorAll('.ra__line--e, .ra__line--w');
  const down = stage.querySelectorAll('.ra__line--n, .ra__line--s');
  const quarters = [...mark.querySelectorAll('[data-q]')];
  const rest = [...mark.children].filter((el) => el.tagName !== 'defs' && !el.hasAttribute('data-q'));
  const on = mark.querySelector('[data-sweep="on"]');
  const off = mark.querySelector('[data-sweep="off"]');
  const traffic = [];
  let tl = null;

  // Cars keep moving under everything: round the ring counter-clockwise, in and out along the arms.
  function drive() {
    const ring = svg.querySelector('[data-ra-ring]');
    if (ring) traffic.push(gsap.to(ring, { rotation: -360, svgOrigin: '0 0', duration: 22, ease: 'none', repeat: -1 }));
    svg.querySelectorAll('[data-ra-lane]').forEach((lane, i) => {
      const inbound = lane.dataset.raLane === 'in';
      const [from, to] = inbound ? [1150, 215] : [205, 1150];
      traffic.push(gsap.fromTo(lane, { x: from }, { x: to, duration: 6.5, ease: inbound ? 'power1.out' : 'power1.in', repeat: -1, delay: -i * 1.7 }));
    });
  }

  function play() {
    tl?.kill();
    traffic.forEach((t) => t.kill());
    traffic.length = 0;
    stage.classList.add('is-live');

    const box = stage.getBoundingClientRect();
    const W = box.width, H = box.height;
    const mb = mark.getBoundingClientRect();
    const k = mb.width / LOGO_W; // px per logo unit when the ring has become the O
    const cx = mb.left - box.left + O.x * k; // the O's centre on the stage
    const cy = mb.top - box.top + O.y * k;
    const s0 = (0.62 * Math.min(W, H)) / (2 * O.R); // at the start the ring fills most of the short side
    const sEdge = (Math.max(W, H) / (2 * SCENE)) * 1.15; // below this the drawing's edge would show
    const sFade = Math.max(s0 * 0.78, sEdge * 1.5);

    const cam = { t: 0, rot: 38 };
    const frame = () => {
      const s = Math.exp(Math.log(s0) + (Math.log(k) - Math.log(s0)) * cam.t);
      svg.setAttribute('viewBox', `${-cx / s} ${-cy / s} ${W / s} ${H / s}`);
      world.setAttribute('transform', `rotate(${cam.rot})`);
      const f = gsap.utils.clamp(0, 1, (s - sEdge) / (sFade - sEdge));
      fades.forEach((g) => { g.style.opacity = f; });
      lit.style.opacity = gsap.utils.clamp(0, 1, (cam.t - 0.55) / 0.35);
    };

    // The first frame: deep in the city, the logo not there yet, the lines not drawn.
    gsap.set(logo, { opacity: 0 });
    gsap.set(across, { scaleX: 0, opacity: 1 });
    gsap.set(down, { scaleY: 0, opacity: 1 });
    gsap.set(quarters, { svgOrigin: `${O.x} ${O.y}`, rotation: 12 });
    gsap.set(rest, { opacity: 0 });
    if (on && off) { gsap.set(on, { attr: { width: 0 } }); gsap.set(off, { attr: { x: 0, width: LOGO_W } }); }
    gsap.set(stage, { backgroundColor: '' });
    frame();
    drive();

    tl = gsap.timeline({ onComplete: () => onDone?.() });
    tl.to(cam, { t: 1, rot: 12, duration: 2.1, ease: 'power2.inOut', onUpdate: frame }, 0.35)
      // The ring is the O now: the logo takes over, its letters come up beside it, its quarters lock.
      .set(logo, { opacity: 1 }, '>')
      .set(svg, { opacity: 0 }, '<')
      .to(rest, { opacity: 1, duration: 0.5, ease: 'power1.out', stagger: 0.04 }, '<')
      .to(quarters, { rotation: 0, duration: 0.85, ease: 'back.out(2.2)' }, '<')
      .addLabel('locked', '-=0.2');
    if (on && off) {
      tl.to(on, { attr: { width: LOGO_W }, duration: 0.95, ease: 'power3.inOut' }, 'locked')
        .to(off, { attr: { x: LOGO_W, width: 0 }, duration: 0.95, ease: 'power3.inOut' }, 'locked');
    }
    // The roads go on without the city: four thin lines out of the O's cuts, to the edges.
    tl.to(across, { scaleX: 1, duration: 0.9, ease: 'power3.inOut', stagger: 0.06 }, 'locked+=0.25')
      .to(down, { scaleY: 1, duration: 0.9, ease: 'power3.inOut', stagger: 0.06 }, 'locked+=0.31')
      .add(() => traffic.forEach((t) => t.kill()));

    if (mode === 'home') {
      tl.addLabel('open', '+=0.25')
        .add(() => onOpen?.(), 'open')
        .to(stage, { backgroundColor: 'transparent', duration: 0.9, ease: 'power2.inOut' }, 'open')
        .to(lines, { opacity: 0, duration: 0.7, ease: 'power1.in' }, 'open+=0.15');
      if (headerLogo) {
        tl.add(() => {
          // The stage's logo flies into the header's, then hands over to it.
          const a = logo.getBoundingClientRect();
          const b = headerLogo.getBoundingClientRect();
          gsap.to(logo, {
            x: b.left + b.width / 2 - (a.left + a.width / 2),
            y: b.top + b.height / 2 - (a.top + a.height / 2),
            scale: b.width / a.width,
            duration: 1, ease: 'expo.inOut',
          });
        }, 'open');
        tl.to({}, { duration: 1 }, 'open');
      } else {
        tl.to(logo, { opacity: 0, duration: 0.6 }, 'open+=0.3');
      }
    }
    return tl;
  }

  // The finished frame: what reduced motion and print see, and where the book rests.
  function rest_() {
    tl?.kill();
    traffic.forEach((t) => t.kill());
    stage.classList.remove('is-live');
    gsap.set([logo, svg, lines, quarters, rest, stage], { clearProps: 'all' });
    if (on && off) { gsap.set(on, { attr: { width: LOGO_W } }); gsap.set(off, { attr: { x: LOGO_W, width: 0 } }); }
  }

  return { play, hurry: () => tl?.timeScale(4), rest: rest_ };
}

/** The home's opening: the first page of a visit, motion allowed (the head script set .ra-intro). */
export function openHome(stage) {
  const html = document.documentElement;
  const headerLogo = document.querySelector('.site-header__logo .logo');
  stage.style.animation = 'none'; // the CSS fallback that hides a stage the script never took
  if (headerLogo) headerLogo.style.animation = 'none';
  try { sessionStorage.setItem('log-ra', '1'); sessionStorage.setItem('log-logo', '1'); } catch { /* storage blocked */ }

  const events = ['pointerdown', 'wheel', 'keydown', 'touchstart'];
  let hurry = () => {};
  const off = () => events.forEach((e) => removeEventListener(e, hurry));
  let opened = false;
  const open = () => {
    if (opened) return;
    opened = true;
    document.dispatchEvent(new CustomEvent('log:opened'));
  };
  const finish = () => {
    open();
    if (headerLogo) headerLogo.style.opacity = '';
    stage.remove();
    html.classList.remove('ra-intro');
    off();
  };
  const ra = roundabout(stage, { mode: 'home', headerLogo, onOpen: open, onDone: finish });
  if (!ra) return finish();

  // Any input means "I'm here for the site": the moment plays out four times faster.
  hurry = () => ra.hurry();
  events.forEach((e) => addEventListener(e, hurry, { passive: true }));

  document.fonts.ready.then(() => ra.play());
}

/** A book sheet: play once as it comes into view; the replay button runs it again. */
export function bookStage(stage) {
  const ra = roundabout(stage, { mode: 'book' });
  if (!ra) return;
  const io = new IntersectionObserver(([e]) => {
    if (!e.isIntersecting) return;
    io.disconnect();
    ra.play();
  }, { threshold: 0.6 });
  io.observe(stage);
  stage.closest('.bk-page')?.querySelector('[data-ra-replay]')?.addEventListener('click', () => ra.play());
  // Printing shows the finished frame, whatever was on screen.
  addEventListener('beforeprint', () => ra.rest());
}
