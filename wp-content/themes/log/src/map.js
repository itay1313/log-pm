/**
 * Map module. Loaded lazily by main.js. This is the stub the hero will grow from:
 * fetches /log/v1/map and logs feature count. Mapbox GL arrives in the hero phase.
 */
export async function mount(el) {
  const res = await fetch(`${window.LOG.rest}map`);
  const geojson = await res.json();
  el.dataset.features = String(geojson.features.length);
  el.setAttribute('aria-hidden', 'false');
  el.setAttribute('role', 'img');
  el.setAttribute('aria-label', `מפת הפרויקטים: ${geojson.features.length} פרויקטים`);
}
