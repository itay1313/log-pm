/**
 * Project page (spec §9): mini-map (dark, non-interactive, click → big map), gallery lightbox,
 * hero video that plays only while on screen.
 */
export function initProject() {
  // Mini-map
  const mini = document.querySelector('.minimap[data-project]');
  if (mini && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const io = new IntersectionObserver((entries) => {
      if (!entries.some((e) => e.isIntersecting)) return;
      io.disconnect();
      import('./map.js').then((m) => m.mount(mini, { mini: true, focusId: Number(mini.dataset.project) })).then(() => mini.classList.add('is-ready')).catch(console.error);
    }, { rootMargin: '200px' });
    io.observe(mini);
  }

  // Hero video: play while visible, pause otherwise (spec §17 performance)
  const video = document.querySelector('video.project__media');
  if (video) {
    const io = new IntersectionObserver((entries) => entries.forEach((e) => (e.isIntersecting ? video.play().catch(() => {}) : video.pause())), { threshold: 0.3 });
    io.observe(video);
  }

  // Gallery lightbox
  const figures = [...document.querySelectorAll('.gallery__grid figure')];
  if (!figures.length) return;
  const dialog = document.createElement('dialog');
  dialog.className = 'lightbox';
  dialog.innerHTML = `
    <button class="lightbox__close" type="button" aria-label="סגירה">×</button>
    <button class="lightbox__nav lightbox__nav--prev" type="button" aria-label="הקודם">‹</button>
    <figure class="lightbox__figure"><img alt=""><figcaption></figcaption></figure>
    <button class="lightbox__nav lightbox__nav--next" type="button" aria-label="הבא">›</button>
    <p class="lightbox__count" aria-live="polite"></p>`;
  document.body.appendChild(dialog);
  const img = dialog.querySelector('img'), cap = dialog.querySelector('figcaption'), count = dialog.querySelector('.lightbox__count');
  let i = 0;
  const show = (n) => {
    i = (n + figures.length) % figures.length;
    const src = figures[i].querySelector('img');
    img.src = src.dataset.full || src.src;
    img.alt = src.alt;
    cap.textContent = figures[i].querySelector('figcaption')?.textContent || '';
    count.textContent = `${i + 1} / ${figures.length}`;
  };
  figures.forEach((f, n) => {
    f.tabIndex = 0;
    f.setAttribute('role', 'button');
    const open = () => { show(n); dialog.showModal(); };
    f.addEventListener('click', open);
    f.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); } });
  });
  dialog.querySelector('.lightbox__close').addEventListener('click', () => dialog.close());
  dialog.querySelector('.lightbox__nav--prev').addEventListener('click', () => show(i - 1));
  dialog.querySelector('.lightbox__nav--next').addEventListener('click', () => show(i + 1));
  dialog.addEventListener('keydown', (e) => { if (e.key === 'ArrowRight') show(i - 1); if (e.key === 'ArrowLeft') show(i + 1); }); // RTL: right = previous
  dialog.addEventListener('click', (e) => { if (e.target === dialog) dialog.close(); });
}
