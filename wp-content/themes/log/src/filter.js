/**
 * Shared project search and filters (spec v6 §8–9): the home map and /projects use the same rules.
 * One value per filter, or all. Free text matches name, city and authority.
 */
export const FILTERS = ['domain', 'stage', 'authority'];

/** Lower-case, no niqqud, no geresh/gershayim or quotes, single spaces. */
export const norm = (s) => String(s ?? '').toLowerCase().replace(/[֑-ׇ]/g, '').replace(/["'`׳״]/g, '').replace(/\s+/g, ' ').trim();

/** Every word of the query appears in the haystack; a leading ה (the definite article) is optional. */
export function matchesQuery(haystack, q) {
  if (!q) return true;
  const h = norm(haystack);
  return norm(q).split(' ').every((w) => h.includes(w) || (w.length > 2 && w[0] === 'ה' && h.includes(w.slice(1))));
}

/** A map feature's properties as a filter record. */
export const recordFromProps = (p) => ({ domain: p.domain, stage: p.stage, authority: p.authority, search: `${p.title} ${p.city || ''} ${p.authorityName || ''}` });

/** state = { q, domain, stage, authority }; `skip` ignores one filter (for faceted counts). */
export const matches = (state, rec, skip) => FILTERS.every((k) => k === skip || !state[k] || rec[k] === state[k]) && matchesQuery(rec.search, state.q);

export function readState(search = location.search) {
  const q = new URLSearchParams(search);
  return { q: q.get('q') || '', ...Object.fromEntries(FILTERS.map((k) => [k, q.get(k) || ''])) };
}

/** Query string for a state (without the leading ?); extra keys are appended when truthy. */
export function stateQuery(state, extra = {}) {
  const q = new URLSearchParams();
  if (state.q) q.set('q', state.q);
  for (const k of FILTERS) if (state[k]) q.set(k, state[k]);
  for (const [k, v] of Object.entries(extra)) if (v) q.set(k, v);
  return q.toString();
}

/** Rewrite each <option>'s count from a { value: n } map; hide empty options except the selected one. */
export function setOptionCounts(select, counts) {
  for (const o of select.options) {
    if (!o.value) continue;
    o.dataset.label ||= o.textContent.replace(/\s*\(\d+\)$/, '');
    o.textContent = `${o.dataset.label} (${counts[o.value] || 0})`;
    o.hidden = !counts[o.value] && o.value !== select.value;
  }
}

/**
 * The current query string with our keys rewritten and everything else kept (UTM tags, etc.).
 * `extra` holds page keys (view, p): empty values remove them.
 */
export function mergeQuery(search, state, extra = {}) {
  const q = new URLSearchParams(search);
  for (const k of ['q', ...FILTERS]) { if (state[k]) q.set(k, state[k]); else q.delete(k); }
  for (const [k, v] of Object.entries(extra)) { if (v) q.set(k, v); else q.delete(k); }
  return q.toString();
}
