import './styles/main.css';

/**
 * Entry. Everything heavy (the map) is a dynamic import, so pages that don't
 * mount it never pay for it.
 */

const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;

// Header: search and mobile menu toggles
for (const btn of document.querySelectorAll('[aria-controls]')) {
  const target = document.getElementById(btn.getAttribute('aria-controls'));
  if (!target) continue;
  btn.addEventListener('click', () => {
    const open = btn.getAttribute('aria-expanded') === 'true';
    btn.setAttribute('aria-expanded', String(!open));
    target.hidden = open;
    if (!open) target.querySelector('input')?.focus();
  });
}

// Copy link buttons (job, knowledge item)
for (const btn of document.querySelectorAll('[data-copy-link]')) {
  btn.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(location.href);
      const label = btn.textContent;
      btn.textContent = 'הקישור הועתק';
      setTimeout(() => (btn.textContent = label), 1800);
    } catch {
      prompt('העתיקו את הקישור:', location.href);
    }
  });
}

// Counters in the hero: count up once when visible
const counters = document.querySelectorAll('[data-count]');
if (counters.length && !reduced && 'IntersectionObserver' in window) {
  const fmt = new Intl.NumberFormat('he-IL');
  const io = new IntersectionObserver((entries) => {
    for (const e of entries) {
      if (!e.isIntersecting) continue;
      io.unobserve(e.target);
      const end = Number(e.target.dataset.count) || 0;
      const t0 = performance.now();
      const tick = (t) => {
        const p = Math.min(1, (t - t0) / 1400);
        e.target.textContent = fmt.format(Math.round(end * (1 - Math.pow(1 - p, 3))));
        if (p < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    }
  }, { threshold: 0.4 });
  counters.forEach((c) => io.observe(c));
}

// Team chips: filter people by domain
const teamGrid = document.getElementById('team-grid');
if (teamGrid) {
  const chips = document.querySelectorAll('.filters--chips .chip');
  chips.forEach((chip) =>
    chip.addEventListener('click', () => {
      chips.forEach((c) => { c.classList.toggle('is-active', c === chip); c.setAttribute('aria-pressed', String(c === chip)); });
      const f = chip.dataset.filter;
      for (const card of teamGrid.children) card.hidden = !!f && card.dataset.domain !== f;
    })
  );
}

// Project filters (client-side on data attributes; the map view hooks in later)
const grid = document.getElementById('projects-grid');
if (grid) {
  const boxes = document.querySelectorAll('.filters input[type=checkbox]');
  const count = document.querySelector('.filters__count');
  const apply = () => {
    const active = {};
    for (const b of boxes) if (b.checked) (active[b.name] ||= []).push(b.value);
    let shown = 0;
    for (const card of grid.children) {
      const ok = Object.entries(active).every(([tax, vals]) => vals.includes(card.dataset[tax === 'log_domain' ? 'domain' : 'scale']));
      card.hidden = !ok;
      if (ok) shown++;
    }
    if (count) count.textContent = Object.keys(active).length ? `${shown} פרויקטים` : '';
  };
  boxes.forEach((b) => b.addEventListener('change', apply));
}

// Forms: contact and apply
if (document.querySelector('form[data-endpoint]')) {
  import('./forms.js').then((m) => m.initForms());
}

// Map: only where a mount point exists, and only once it's near the viewport.
const mapMount = document.getElementById('hero-map') || document.getElementById('projects-map');
if (mapMount && window.LOG?.mapbox?.token && !reduced) {
  const io = new IntersectionObserver((entries) => {
    if (!entries.some((e) => e.isIntersecting)) return;
    io.disconnect();
    import('./map.js').then((m) => m.mount(mapMount)).catch(console.error);
  }, { rootMargin: '200px' });
  io.observe(mapMount);
}
