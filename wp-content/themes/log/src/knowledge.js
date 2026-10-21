/**
 * /knowledge (spec v6 §13): type tabs, free search and year, one value each. The featured item shows
 * only without filters; the list shows 12, then "טעינת עוד". State in the address: ?type=&q=&year=.
 */
import { matchesQuery } from './filter.js';

const PAGE = 12;

export function initKnowledge() {
  const form = document.querySelector('.filters--knowledge');
  const grid = document.getElementById('knowledge-grid');
  if (!form || !grid) return;
  const tabs = [...form.querySelectorAll('.type-tabs .chip')];
  const search = form.querySelector('input[name=q]');
  const year = form.querySelector('select[name=year]');
  const featured = document.getElementById('knowledge-featured');
  const more = document.getElementById('knowledge-more');
  const empty = document.getElementById('knowledge-empty');
  const cards = [...grid.children];
  let limit = Math.max(PAGE, Number(history.state?.limit) || PAGE);

  const state = () => ({ type: tabs.find((t) => t.classList.contains('is-active'))?.dataset.type || '', q: search.value.trim(), year: year?.value || '' });

  function apply() {
    const s = state();
    let n = 0;
    for (const c of cards) {
      const ok = (!s.type || c.dataset.type === s.type) && (!s.year || c.dataset.year === s.year) && matchesQuery(c.dataset.search, s.q);
      if (ok) n++;
      c.hidden = !ok || n > limit;
    }
    const filtered = s.type || s.q || s.year;
    if (featured) featured.hidden = !!filtered;
    more.hidden = n <= limit;
    empty.hidden = n > 0 || (!filtered && featured);
  }
  function write(push) {
    const s = state();
    const q = new URLSearchParams(location.search);
    for (const k of ['type', 'q', 'year']) { if (s[k]) q.set(k, s[k]); else q.delete(k); }
    const url = location.pathname + (q.toString() ? `?${q}` : '');
    if (url !== location.pathname + location.search) history[push ? 'pushState' : 'replaceState']({ limit }, '', url);
  }
  function setType(type) {
    tabs.forEach((t) => { const on = t.dataset.type === type; t.classList.toggle('is-active', on); t.setAttribute('aria-pressed', String(on)); });
  }
  function restore() {
    const q = new URLSearchParams(location.search);
    setType(tabs.some((t) => t.dataset.type === q.get('type')) ? q.get('type') : '');
    search.value = q.get('q') || '';
    if (year) year.value = [...year.options].some((o) => o.value === q.get('year')) ? q.get('year') : '';
    limit = Math.max(PAGE, Number(history.state?.limit) || PAGE);
    apply();
  }

  tabs.forEach((t) => t.addEventListener('click', () => { setType(t.dataset.type); limit = PAGE; apply(); write(true); }));
  year?.addEventListener('change', () => { limit = PAGE; apply(); write(true); });
  let typing = 0;
  search.addEventListener('input', () => { limit = PAGE; apply(); clearTimeout(typing); typing = setTimeout(() => write(false), 350); });
  form.addEventListener('submit', (e) => { e.preventDefault(); write(true); });
  more.addEventListener('click', () => { limit += PAGE; history.replaceState({ ...history.state, limit }, ''); apply(); });
  empty.querySelector('.filters__clear')?.addEventListener('click', () => { setType(''); search.value = ''; if (year) year.value = ''; limit = PAGE; apply(); write(true); });
  window.addEventListener('popstate', restore);
  restore();
}
