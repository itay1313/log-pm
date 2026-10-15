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

// Projects archive: filters, gallery ↔ map toggle, URL state. The map itself loads on first toggle.
if (document.getElementById('projects-grid') || document.getElementById('projects-map')) {
  import('./projects.js').then((m) => m.initProjects());
}

// Forms: contact and apply
if (document.querySelector('form[data-endpoint]')) {
  import('./forms.js').then((m) => m.initForms());
}

// Hero map: design phase, not started. Mounts only once LOG's Mapbox token exists.
const hero = document.getElementById('hero-map');
if (hero && window.LOG?.mapbox?.token && !reduced) {
  const io = new IntersectionObserver((entries) => {
    if (!entries.some((e) => e.isIntersecting)) return;
    io.disconnect();
    import('./map.js').then((m) => m.mount(hero)).catch(console.error);
  }, { rootMargin: '200px' });
  io.observe(hero);
}
