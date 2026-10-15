/**
 * Projects map (spec §7–§8). Loaded lazily; MapLibre GL + OpenFreeMap today, Mapbox when a token
 * exists in settings. Reads /wp-json/log/v1/map (GeoJSON), draws plan boundaries in orange,
 * ring markers, clusters from afar, a card popup on click, and takes filters from the page.
 */
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';

const ISRAEL = { center: [34.95, 31.5], zoom: 6.6, bounds: [[33.9, 29.3], [36.0, 33.4]] };
const PAPER = '#fbfaf8', INK = '#24242b', ORANGE = '#f7931d', GREY = '#6e6f73', LINE = '#e1ddd7';
const STAGE_LABEL = { 1: 'איתור והיתכנות', 2: 'תכנון וקידום סטטוטורי', 3: 'ליווי ואישור', 4: 'בקרה ומעקב' };
const SCALE_LABEL = { s: 'S', m: 'M', l: 'L', xl: 'XL' };

let rtlLoaded = false;

export async function mount(el, { onSelect } = {}) {
  const res = await fetch(`${window.LOG.rest}map`);
  const all = await res.json();

  if (!rtlLoaded) {
    maplibregl.setRTLTextPlugin('https://unpkg.com/@mapbox/mapbox-gl-rtl-text@0.3.0/dist/mapbox-gl-rtl-text.js', true);
    rtlLoaded = true;
  }

  const map = new maplibregl.Map({
    container: el,
    style: styleUrl(),
    center: ISRAEL.center,
    zoom: ISRAEL.zoom,
    minZoom: 5.5,
    maxZoom: 17,
    maxBounds: [[31, 27], [39, 35.5]],
    attributionControl: { compact: true },
    locale: { 'NavigationControl.ZoomIn': 'הגדלה', 'NavigationControl.ZoomOut': 'הקטנה' },
  });
  map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-left');
  map.touchZoomRotate.disableRotation();

  let current = all;
  let popup = null;

  const points = (fc) => ({ type: 'FeatureCollection', features: fc.features.filter((f) => f.geometry) });
  const polygons = (fc) => ({
    type: 'FeatureCollection',
    features: fc.features.filter((f) => f.properties.boundary).map((f) => ({ type: 'Feature', id: f.id, geometry: f.properties.boundary, properties: { id: f.id, title: f.properties.title } })),
  });

  map.on('load', () => {
    paperise(map);

    map.addSource('plans', { type: 'geojson', data: polygons(current) });
    map.addLayer({ id: 'plans-fill', type: 'fill', source: 'plans', minzoom: 10, paint: { 'fill-color': ORANGE, 'fill-opacity': ['interpolate', ['linear'], ['zoom'], 10, 0.15, 14, 0.35] } });
    map.addLayer({ id: 'plans-line', type: 'line', source: 'plans', minzoom: 10, paint: { 'line-color': ORANGE, 'line-width': 1.5 } });

    map.addSource('projects', { type: 'geojson', data: points(current), cluster: true, clusterRadius: 44, clusterMaxZoom: 11, promoteId: 'id' });
    map.addLayer({ id: 'clusters', type: 'circle', source: 'projects', filter: ['has', 'point_count'], paint: { 'circle-color': PAPER, 'circle-radius': ['step', ['get', 'point_count'], 16, 5, 20, 15, 26], 'circle-stroke-color': ORANGE, 'circle-stroke-width': 4 } });
    map.addLayer({ id: 'cluster-count', type: 'symbol', source: 'projects', filter: ['has', 'point_count'], layout: { 'text-field': ['get', 'point_count_abbreviated'], 'text-size': 14, 'text-font': ['Noto Sans Bold'] }, paint: { 'text-color': INK } });
    map.addLayer({ id: 'rings', type: 'circle', source: 'projects', filter: ['!', ['has', 'point_count']], paint: { 'circle-color': ['case', ['boolean', ['feature-state', 'selected'], false], ORANGE, PAPER], 'circle-radius': ['interpolate', ['linear'], ['zoom'], 6, 6, 12, 9], 'circle-stroke-color': ORANGE, 'circle-stroke-width': 3 } });
    map.addLayer({ id: 'ring-labels', type: 'symbol', source: 'projects', filter: ['!', ['has', 'point_count']], minzoom: 10, layout: { 'text-field': ['get', 'title'], 'text-size': 13, 'text-offset': [0, 1.3], 'text-anchor': 'top', 'text-font': ['Noto Sans Regular'], 'text-optional': true }, paint: { 'text-color': INK, 'text-halo-color': PAPER, 'text-halo-width': 1.5 } });

    map.on('click', 'clusters', (e) => {
      const f = map.queryRenderedFeatures(e.point, { layers: ['clusters'] })[0];
      map.getSource('projects').getClusterExpansionZoom(f.properties.cluster_id).then((z) => map.easeTo({ center: f.geometry.coordinates, zoom: Math.min(z + 0.5, 14) }));
    });
    map.on('click', 'rings', (e) => select(e.features[0]));
    for (const l of ['clusters', 'rings']) {
      map.on('mouseenter', l, () => (map.getCanvas().style.cursor = 'pointer'));
      map.on('mouseleave', l, () => (map.getCanvas().style.cursor = ''));
    }
    map.on('click', (e) => { if (!map.queryRenderedFeatures(e.point, { layers: ['rings', 'clusters'] }).length) deselect(); });
    el.addEventListener('keydown', (e) => { if (e.key === 'Escape') deselect(); });

    fit(current);
    el.dispatchEvent(new CustomEvent('log:map-ready'));
  });

  let selectedId = null;
  function select(feature) {
    deselect();
    selectedId = feature.id ?? feature.properties.id;
    map.setFeatureState({ source: 'projects', id: selectedId }, { selected: true });
    popup = new maplibregl.Popup({ offset: 14, closeButton: true, closeOnClick: false, maxWidth: '320px', className: 'log-popup' })
      .setLngLat(feature.geometry.coordinates)
      .setHTML(card(feature.properties))
      .addTo(map);
    popup.on('close', () => { if (selectedId) { map.setFeatureState({ source: 'projects', id: selectedId }, { selected: false }); selectedId = null; } });
    popup.getElement().querySelector('.log-popup__link')?.focus();
    onSelect?.(feature.properties);
  }
  function deselect() { popup?.remove(); popup = null; }

  function fit(fc) {
    const pts = points(fc).features;
    if (!pts.length) { map.jumpTo({ center: ISRAEL.center, zoom: ISRAEL.zoom }); return; }
    const b = new maplibregl.LngLatBounds();
    pts.forEach((f) => b.extend(f.geometry.coordinates));
    map.fitBounds(b, { padding: 60, maxZoom: 12, duration: 600 });
  }

  return {
    map,
    /** Replace the shown set (filters). Keeps clusters honest by swapping the data, not filtering layers. */
    setFeatures(predicate) {
      current = { ...all, features: all.features.filter((f) => predicate(f.properties)) };
      map.getSource('projects')?.setData(points(current));
      map.getSource('plans')?.setData(polygons(current));
      deselect();
      fit(current);
      return current.features.length;
    },
    /** Deep link: fly to and open one project. */
    focus(id) {
      const f = all.features.find((x) => x.id === id && x.geometry);
      if (!f) return false;
      map.once('moveend', () => select({ ...f, id: f.id }));
      map.flyTo({ center: f.geometry.coordinates, zoom: 13.5, duration: 900 });
      return true;
    },
    resize: () => map.resize(),
    all,
  };
}

/** Mapbox when LOG has a token, OpenFreeMap otherwise. Same code path either way. */
function styleUrl() {
  const mb = window.LOG?.mapbox;
  if (mb?.token && mb?.style) return `https://api.mapbox.com/styles/v1/${mb.style.replace('mapbox://styles/', '')}?access_token=${mb.token}`;
  return 'https://tiles.openfreemap.org/styles/positron';
}

/** Spec §7: paper background, roads in grey, no POI noise. Applied over whichever basemap loaded. */
function paperise(map) {
  const style = map.getStyle();
  for (const l of style.layers) {
    if (l.type === 'background') map.setPaintProperty(l.id, 'background-color', PAPER);
    if (/poi|airport|aeroway|housenum|transit|ferry/.test(l.id)) map.setLayoutProperty(l.id, 'visibility', 'none');
    if (l.type === 'fill' && /landuse|landcover|park|wood|residential/.test(l.id)) map.setPaintProperty(l.id, 'fill-color', '#f4f1ec');
    if (l.type === 'fill' && /water/.test(l.id)) map.setPaintProperty(l.id, 'fill-color', '#e9eef1');
    if (l.type === 'fill' && /building/.test(l.id)) map.setPaintProperty(l.id, 'fill-color', '#ece8e1');
    if (l.type === 'line' && /road|highway|motorway|trunk|primary|secondary|street|path|rail/.test(l.id)) map.setPaintProperty(l.id, 'line-color', LINE);
    if (l.type === 'line' && /boundary|admin/.test(l.id)) map.setPaintProperty(l.id, 'line-color', '#d6d1c9');
    if (l.type === 'symbol') {
      map.setPaintProperty(l.id, 'text-color', GREY);
      // Hebrew names where OSM has them, otherwise the local name.
      if (map.getLayoutProperty(l.id, 'text-field')) map.setLayoutProperty(l.id, 'text-field', ['coalesce', ['get', 'name:he'], ['get', 'name']]);
    }
  }
}

function esc(s) { return String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }
function num(n) { return n ? new Intl.NumberFormat('he-IL').format(Number(n)) : ''; }

function card(p) {
  const stage = Math.min(4, Math.max(1, Number(p.stage) || 1));
  const meta = [p.domain && DOMAIN_LABEL[p.domain], p.city, SCALE_LABEL[p.scale]].filter(Boolean).join(' · ');
  const figure = p.units ? `<strong>${num(p.units)}</strong> יח"ד` : p.dunam ? `<strong>${num(p.dunam)}</strong> דונם` : '';
  return `
    <article class="log-popup__card">
      ${p.image ? `<img class="log-popup__img" src="${esc(p.image)}" alt="">` : ''}
      <span class="stage stage--${stage}" role="img" aria-label="שלב ${stage} מתוך 4: ${STAGE_LABEL[stage]}"><i></i><i></i><i></i><i></i></span>
      <p class="log-popup__meta">${esc(meta)}</p>
      <h3 class="log-popup__title">${esc(p.title)}</h3>
      <p class="log-popup__figure">${figure}${p.year ? ` <span>${esc(p.year)}</span>` : ''}</p>
      <a class="btn btn--primary log-popup__link" href="${esc(p.url)}">לעמוד המלא</a>
    </article>`;
}

const DOMAIN_LABEL = {
  'urban-renewal': 'התחדשות עירונית', 'master-plans': 'תכניות מתאר', 'detailed-plans': 'תכניות מפורטות',
  'national-plans': 'תכניות לאומיות', 'open-spaces': 'שטחים פתוחים', 'rural-arab': 'המגזר הכפרי והחברה הערבית',
};
