/**
 * Page transition from the hero into a project (and the matching reveal on arrival).
 *
 * leave(): a ring with the project's name opens around the pin, ink floods out of it to cover
 * the screen, then we navigate. The project page starts covered (inline script in header.php adds
 * html.is-wiping before first paint), and enter() opens the ink from the centre while the ring
 * flies off. Reduced motion: plain navigation.
 */
import { gsap } from 'gsap';

const KEY = 'log-wipe';

/**
 * The ring (Marseille's signature): the project's name around a circle. Browsers don't lay Hebrew
 * on a textPath reliably, so each letter is placed by hand, counter-clockwise from the top, which
 * reads right-to-left across the top of the ring.
 */
function ring(title) {
  const unit = `${title} · לוג ניהול פרויקטים · `;
  const per = 13.2; // px of arc per letter at r=118
  const fit = Math.max(1, Math.round((2 * Math.PI * 118) / per / unit.length));
  const chars = [...unit.repeat(fit)];
  const step = 360 / chars.length;
  const letters = chars.map((ch, i) => {
    const a = -i * step;
    const r = (a * Math.PI) / 180;
    const x = 150 + 118 * Math.sin(r), y = 150 - 118 * Math.cos(r);
    return `<text x="${x.toFixed(1)}" y="${y.toFixed(1)}" transform="rotate(${a.toFixed(2)} ${x.toFixed(1)} ${y.toFixed(1)})">${escapeXml(ch)}</text>`;
  }).join('');
  return `
    <svg class="wipe__ring" viewBox="0 0 300 300" aria-hidden="true">
      <circle cx="150" cy="150" r="138" class="wipe__band"/>
      <circle cx="150" cy="150" r="98" class="wipe__inner"/>
      <g class="wipe__text">${letters}</g>
      <circle cx="150" cy="150" r="6" class="wipe__dot"/>
    </svg>`;
}

export function leave(url, { x, y, title }) {
  if (matchMedia('(prefers-reduced-motion: reduce)').matches) { location.href = url; return; }
  try { sessionStorage.setItem(KEY, JSON.stringify({ title, t: Date.now() })); } catch {}
  const link = document.createElement('link');
  link.rel = 'prefetch';
  link.href = url;
  document.head.appendChild(link);

  const o = document.createElement('div');
  o.className = 'wipe';
  o.innerHTML = `<div class="wipe__fill"></div>${ring(title)}`;
  document.body.appendChild(o);
  const fill = o.querySelector('.wipe__fill');
  const r = o.querySelector('.wipe__ring');
  const cover = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));

  gsap.set(r, { x: x - 150, y: y - 150, scale: 0.15, rotation: -90, opacity: 0, transformOrigin: '50% 50%' });
  gsap.set(fill, { clipPath: `circle(0px at ${x}px ${y}px)` });
  gsap.timeline({ onComplete: () => { location.href = url; } })
    .to(r, { opacity: 1, scale: 1, rotation: 0, duration: 1.0, ease: 'expo.out' })
    .to(fill, { clipPath: `circle(${cover}px at ${x}px ${y}px)`, duration: 1.0, ease: 'expo.inOut' }, 0.55)
    .to(r, { x: innerWidth / 2 - 150, y: innerHeight / 2 - 150, duration: 1.0, ease: 'expo.inOut' }, 0.55);
}

export function enter() {
  let data = null;
  try { data = JSON.parse(sessionStorage.getItem(KEY) || 'null'); sessionStorage.removeItem(KEY); } catch {}
  const root = document.documentElement;
  if (!data || Date.now() - data.t > 15000) { root.classList.remove('is-wiping'); return; }

  const o = document.createElement('div');
  o.className = 'wipe';
  o.innerHTML = `<div class="wipe__fill"></div>${ring(data.title || '')}`;
  document.body.appendChild(o);
  root.classList.remove('is-wiping');
  const fill = o.querySelector('.wipe__fill');
  const r = o.querySelector('.wipe__ring');
  const cx = innerWidth / 2, cy = innerHeight / 2;
  gsap.set(r, { x: cx - 150, y: cy - 150, transformOrigin: '50% 50%' });
  gsap.set(fill, { clipPath: `circle(${Math.hypot(cx, cy)}px at ${cx}px ${cy}px)` });

  const intro = document.querySelectorAll('.project__hero-copy > *, .project__media, .project__facts');
  gsap.timeline({ onComplete: () => o.remove() })
    .to(r, { rotation: 120, scale: 2.6, opacity: 0, duration: 1.2, ease: 'expo.in' }, 0.1)
    .to(fill, { clipPath: `circle(0px at ${cx}px ${cy}px)`, duration: 1.1, ease: 'expo.inOut' }, 0.35)
    .from(intro, { y: 40, opacity: 0, duration: 1.1, stagger: 0.08, ease: 'expo.out' }, 0.9);
}

function escapeXml(s) {
  return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}
