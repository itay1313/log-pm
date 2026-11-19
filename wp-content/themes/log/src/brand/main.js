/**
 * The web brand book (/brand). No map, no Lenis, no site JS: it writes the live token values into the
 * pages and draws each page's line as it comes into view.
 */
import './brand.css';
import { drawLines } from '../lines/draw.js';

const html = document.documentElement;
const rootStyle = getComputedStyle(html);

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

for (const el of document.querySelectorAll('[data-contrast]')) {
  const [fg, bg] = el.dataset.contrast.split(' ').map(rgb);
  if (!fg || !bg) continue;
  const ratio = contrast(fg, bg);
  // A pair under 4.5:1 says so in the book itself.
  el.textContent = `ניגודיות ${ratio.toFixed(1)}:1` + (ratio < 4.5 ? ' · מתחת ל־4.5' : '');
}

// A size token is a clamp() between a phone and a wide screen; show its two ends in px.
for (const el of document.querySelectorAll('[data-size-range]')) {
  const raw = rootStyle.getPropertyValue(el.dataset.sizeRange).trim();
  const rootPx = parseFloat(rootStyle.fontSize) || 16;
  const px = (v) => Math.round(v.endsWith('rem') ? parseFloat(v) * rootPx : parseFloat(v));
  const clamp = raw.match(/^clamp\(\s*([\d.]+r?e?m|[\d.]+px)\s*,.*,\s*([\d.]+r?e?m|[\d.]+px)\s*\)$/);
  el.textContent = clamp ? `${px(clamp[1])}–${px(clamp[2])}px` : `${px(raw)}px`;
}
probe.remove();

// Each page draws its line and drops its dot once, the first time it is mostly on screen.
if (!matchMedia('(prefers-reduced-motion: reduce)').matches && 'IntersectionObserver' in window) {
  html.classList.add('motion');
  const io = new IntersectionObserver((entries) => {
    for (const e of entries) {
      if (e.isIntersecting) {
        e.target.classList.add('is-in');
        io.unobserve(e.target);
      }
    }
  }, { threshold: 0.35 });
  document.querySelectorAll('.bk-page').forEach((p) => io.observe(p));
}

// The line system's specimens draw the way they do on the site.
drawLines();
