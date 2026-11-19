/**
 * A riso field: a low-resolution canvas of ordered-dither dots in one tone, drifting slowly, blown up with hard
 * pixels like a risograph print. It answers a touch: the pointer wakes the dots under it and leaves a short trail,
 * its core in the accent colour. Quiet by design: one tone close to its ground, a few dots of orange.
 *
 * The tone is the canvas's CSS `color`, the accent its `outline-color` (so both are tokens, set in CSS), and how
 * strong the field is, is its CSS opacity. It draws only while on screen. With reduced motion it is one still frame.
 */
const BAYER = [0, 8, 2, 10, 12, 4, 14, 6, 3, 11, 1, 9, 15, 7, 13, 5].map((v) => (v + 0.5) / 16);
const hash = (x, y) => { const s = Math.sin(x * 127.1 + y * 311.7) * 43758.5453; return s - Math.floor(s); };
function noise(x, y) {
  const xi = Math.floor(x), yi = Math.floor(y), xf = x - xi, yf = y - yi;
  const u = xf * xf * (3 - 2 * xf), v = yf * yf * (3 - 2 * yf);
  const a = hash(xi, yi), b = hash(xi + 1, yi), c = hash(xi, yi + 1), d = hash(xi + 1, yi + 1);
  return a + (b - a) * u + (c - a) * v + (a - b - c + d) * u * v;
}
const channels = (css) => (css.match(/[\d.]+/g) || [0, 0, 0]).slice(0, 3).map(Number);

/** Start a field on `canvas`; `host` is the element whose pointer it follows (window for a fixed backdrop). */
export function riso(canvas, host = canvas.parentElement, { cell = 6, fps = 20, density = 0.42, still = false } = {}) {
  const ctx = canvas.getContext('2d');
  if (!ctx) return () => {};
  const style = getComputedStyle(canvas);
  const tone = channels(style.color), accent = channels(style.outlineColor);
  const seed = Math.random() * 1000;
  let w = 0, h = 0, image = null, trail = null, raf = 0, last = 0, seen = false;

  const size = () => {
    const r = canvas.getBoundingClientRect();
    w = Math.max(1, Math.ceil(r.width / cell));
    h = Math.max(1, Math.ceil(r.height / cell));
    canvas.width = w; canvas.height = h;
    image = ctx.createImageData(w, h);
    trail = new Float32Array(w * h);
  };

  const draw = (time) => {
    const t = seed + time / 1000;
    const px = image.data;
    for (let y = 0, i = 0; y < h; y++) {
      for (let x = 0; x < w; x++, i++) {
        // A soft field, and over it blocks that drift at their own pace: the patchwork of a misregistered print.
        const soft = noise(x * 0.034 + t * 0.05, y * 0.034 - t * 0.03);
        const block = noise(Math.floor(x / 9) * 0.83 + t * 0.07, Math.floor(y / 5) * 0.83 + 31);
        const touch = trail[i];
        const v = (soft * 0.62 + block * 0.38 - 0.5) * 2.1 + density + touch;
        const o = i * 4;
        if (v > BAYER[(y & 3) * 4 + (x & 3)] + 0.18) {
          const c = touch > 0.55 ? accent : tone;
          px[o] = c[0]; px[o + 1] = c[1]; px[o + 2] = c[2]; px[o + 3] = 255;
        } else px[o + 3] = 0;
        if (touch) trail[i] = touch < 0.01 ? 0 : touch * 0.94;
      }
    }
    ctx.putImageData(image, 0, 0);
  };

  const loop = (time) => {
    raf = requestAnimationFrame(loop);
    if (time - last < 1000 / fps) return;
    last = time;
    draw(time);
  };
  const run = (on) => {
    cancelAnimationFrame(raf);
    raf = 0;
    if (on && !still && !document.hidden) raf = requestAnimationFrame(loop);
  };

  const touchAt = (e) => {
    if (!seen || !trail) return;
    const r = canvas.getBoundingClientRect();
    const cx = (e.clientX - r.left) / r.width * w, cy = (e.clientY - r.top) / r.height * h;
    if (cx < -8 || cy < -8 || cx > w + 8 || cy > h + 8) return;
    const R = 7;
    for (let y = Math.max(0, Math.floor(cy - R)); y < Math.min(h, cy + R); y++) {
      for (let x = Math.max(0, Math.floor(cx - R)); x < Math.min(w, cx + R); x++) {
        const d = Math.hypot(x - cx, y - cy) / R;
        if (d < 1) trail[y * w + x] = Math.min(1, trail[y * w + x] + (1 - d) * (1 - d) * 0.5);
      }
    }
  };

  size();
  draw(0);
  const io = new IntersectionObserver(([entry]) => { seen = entry.isIntersecting; run(seen); });
  io.observe(canvas);
  const ro = new ResizeObserver(() => { size(); draw(performance.now()); });
  ro.observe(canvas);
  const visibility = () => run(seen);
  const target = host === window ? window : host;
  if (!still) target.addEventListener('pointermove', touchAt, { passive: true });
  document.addEventListener('visibilitychange', visibility);

  return () => {
    run(false); io.disconnect(); ro.disconnect();
    target.removeEventListener('pointermove', touchAt);
    document.removeEventListener('visibilitychange', visibility);
    canvas.remove();
  };
}
