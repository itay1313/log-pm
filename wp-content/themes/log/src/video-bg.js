/**
 * Background videos (template-parts/video-bg.php).
 *
 * - Nothing loads until a video comes within a screen of the window: first the poster, then the clip
 *   (960 wide on phones, 1600 elsewhere). Off screen, or with the tab hidden, it pauses.
 * - Reduced motion: the poster stays as a still and no clip is fetched. Save-Data or a 2G link: the same.
 * - The pause button stops every background video on the site, and the choice is kept for later visits.
 */
const KEY = 'log-bg-video';
const read = () => { try { return localStorage.getItem(KEY); } catch { return null; } };
const write = (v) => { try { v ? localStorage.setItem(KEY, v) : localStorage.removeItem(KEY); } catch { /* private mode: this visit only */ } };

export function initVideoBg(nodes) {
  const still = matchMedia('(prefers-reduced-motion: reduce)');
  const net = navigator.connection;
  const lean = !!net && (net.saveData || /2g$/.test(net.effectiveType || ''));
  const narrow = matchMedia('(max-width: 900px)').matches;
  let stopped = read() === 'off';

  const items = nodes.map((el) => ({ el, video: el.querySelector('video'), btn: el.querySelector('.vbg__toggle'), near: false, seen: false }));
  const moving = () => !stopped && !still.matches && !lean && !document.hidden;

  const update = (it) => {
    const { video } = it;
    if (!it.near) return;
    if (!video.poster) video.poster = video.dataset.poster;
    if (!moving()) { video.pause(); return; }
    if (!video.src) video.src = narrow ? video.dataset.narrow : video.dataset.wide; // starts buffering a screen early
    if (it.seen) video.play().catch(() => {}); // autoplay refused (Low Power Mode): the poster stays
    else video.pause();
  };
  const sync = () => items.forEach((it) => {
    it.btn.hidden = still.matches || lean;
    it.btn.setAttribute('aria-pressed', String(stopped));
    it.el.classList.toggle('is-stopped', stopped);
    update(it);
  });

  // Two lines of sight: a screen ahead to fetch, the window itself to play.
  const watch = (key, rootMargin) => new IntersectionObserver((entries) => entries.forEach((e) => {
    const it = items.find((i) => i.el === e.target);
    it[key] = e.isIntersecting;
    update(it);
  }), { rootMargin });
  const ahead = watch('near', '100% 0px');
  const inView = watch('seen', '0px');

  items.forEach((it) => {
    ahead.observe(it.el);
    inView.observe(it.el);
    it.video.addEventListener('playing', () => it.el.classList.add('is-playing'), { once: true });
    it.btn.addEventListener('click', () => { stopped = !stopped; write(stopped ? 'off' : null); sync(); });
  });
  still.addEventListener('change', sync);
  document.addEventListener('visibilitychange', sync);
  sync();
}
