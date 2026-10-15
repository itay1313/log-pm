/**
 * Projects map (spec §7–§8). Loaded lazily; MapLibre GL + OpenFreeMap today, Mapbox when a token
 * exists in settings. Reads /wp-json/log/v1/map (GeoJSON), draws plan boundaries in orange,
 * ring markers, clusters from afar, a card popup on click, and takes filters from the page.
 *
 * Themes: 'dark' (default — deep ink, the city as a faint grid, LOG's work glowing in orange,
 * in the spirit of the Marseille reference) and 'paper' (the spec's light palette).
 * mount(el, { theme, mini, focusId }) — mini = non-interactive single-project map (project page).
 */
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';

const ISRAEL = { center: [34.95, 31.5], zoom: 6.6 };
const ORANGE = '#f7931d';
const THEMES = {
  dark: {
    bg: '#15161c', land: '#1b1c23', water: '#0f1015', building: '#23242c', road: '#2e2f38', roadMajor: '#3a3b46',
    boundary: '#2a2b34', label: '#8b8c96', ringFill: '#15161c', ink: '#f4f1ec', planFill: ORANGE, planOpacity: [0.18, 0.4], glow: 'rgba(247,147,29,.35)', building3d: '#2a2b34',
  },
  paper: {
    bg: '#fbfaf8', land: '#f4f1ec', water: '#e9eef1', building: '#ece8e1', road: '#e1ddd7', roadMajor: '#d6d1c9',
    boundary: '#d6d1c9', label: '#6e6f73', ringFill: '#fbfaf8', ink: '#24242b', planFill: ORANGE, planOpacity: [0.15, 0.35], glow: 'rgba(247,147,29,.25)', building3d: '#e1ddd7',
  },
};
const STAGE_LABEL = { 1: 'איתור והיתכנות', 2: 'תכנון וקידום סטטוטורי', 3: 'ליווי ואישור', 4: 'בקרה ומעקב' };
const SCALE_LABEL = { s: 'S', m: 'M', l: 'L', xl: 'XL' };
const DOMAIN_LABEL = {
  'urban-renewal': 'התחדשות עירונית', 'master-plans': 'תכניות מתאר', 'detailed-plans': 'תכניות מפורטות',
  'national-plans': 'תכניות לאומיות', 'open-spaces': 'שטחים פתוחים', 'rural-arab': 'המגזר הכפרי והחברה הערבית',
};

let rtlLoaded = false;

export const PITCH_BY_ZOOM = [[9, 0], [11, 20], [12.5, 45], [14.5, 60]];
export function pitchFor(z) {
  if (z <= PITCH_BY_ZOOM[0][0]) return 0;
  for (let i = 1; i < PITCH_BY_ZOOM.length; i++) {
    const [z0, p0] = PITCH_BY_ZOOM[i - 1], [z1, p1] = PITCH_BY_ZOOM[i];
    if (z <= z1) return p0 + ((z - z0) / (z1 - z0)) * (p1 - p0);
  }
  return PITCH_BY_ZOOM.at(-1)[1];
}

/**
 * hero = full-screen home map: pitch follows zoom, 3D buildings near, plan areas extruded,
 * hover states, scroll-zoom only after a click on the map, no popup (the page draws a panel).
 */
export async function mount(el, { theme = 'dark', mini = false, hero = false, focusId = null, onSelect, onHover, onMove } = {}) {
  const T = THEMES[theme] || THEMES.dark;
  el.dataset.mapTheme = theme;
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
    minZoom: mini ? 11 : 5.5,
    maxZoom: 17,
    maxBounds: [[31, 27], [39, 35.5]],
    attributionControl: { compact: true },
    interactive: !mini,
    pitch: 0,
    scrollZoom: !hero,
    locale: { 'NavigationControl.ZoomIn': 'הגדלה', 'NavigationControl.ZoomOut': 'הקטנה' },
  });
  if (!mini) {
    map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-left');
    map.touchZoomRotate.disableRotation();
  }

  let current = mini && focusId ? { ...all, features: all.features.filter((f) => f.id === focusId) } : all;
  let popup = null;
  let selectedId = null;

  const points = (fc) => ({ type: 'FeatureCollection', features: fc.features.filter((f) => f.geometry) });
  const polygons = (fc) => ({
    type: 'FeatureCollection',
    features: fc.features.filter((f) => f.properties.boundary).map((f) => ({ type: 'Feature', id: f.id, geometry: f.properties.boundary, properties: { id: f.id, title: f.properties.title, height: Math.min(260, Math.max(18, (Number(f.properties.units) || 300) / 60)) } })),
  });

  // style.load fires once the style JSON is in: the right moment to add layers (the 'load' event
  // can be delayed by throttled frames). If the style is already there, run now.
  const onStyle = () => {
    if (map.getLayer('rings')) return;
    restyle(map, T);

    map.addSource('plans', { type: 'geojson', data: polygons(current) });
    map.addLayer({ id: 'plans-glow', type: 'line', source: 'plans', minzoom: 9, paint: { 'line-color': T.glow, 'line-width': 10, 'line-blur': 8 } });
    map.addLayer({ id: 'plans-fill', type: 'fill', source: 'plans', minzoom: 9, paint: { 'fill-color': T.planFill, 'fill-opacity': ['interpolate', ['linear'], ['zoom'], 9, T.planOpacity[0], 14, T.planOpacity[1]] } });
    map.addLayer({ id: 'plans-line', type: 'line', source: 'plans', minzoom: 9, paint: { 'line-color': ORANGE, 'line-width': 1.5 } });

    if (hero) {
      // Existing city as low uniform masses from zoom 13 (see docs/hero-spike.md), LOG's plans as orange volumes.
      const src = Object.keys(map.getStyle().sources).find((s) => /openmaptiles|composite|mapbox/.test(s));
      if (src) {
        map.addLayer({ id: 'buildings-3d', type: 'fill-extrusion', source: src, 'source-layer': 'building', minzoom: 13, paint: {
          'fill-extrusion-color': T.building3d, 'fill-extrusion-height': ['coalesce', ['get', 'render_height'], ['get', 'height'], 7], 'fill-extrusion-base': ['coalesce', ['get', 'render_min_height'], ['get', 'min_height'], 0],
          'fill-extrusion-opacity': ['interpolate', ['linear'], ['zoom'], 13, 0, 14, 0.9],
        } }, 'plans-glow');
      }
      map.addLayer({ id: 'plans-3d', type: 'fill-extrusion', source: 'plans', minzoom: 12, paint: {
        'fill-extrusion-color': ORANGE, 'fill-extrusion-height': ['get', 'height'], 'fill-extrusion-opacity': ['interpolate', ['linear'], ['zoom'], 12, 0, 13.5, 0.55],
      } });
      map.on('zoom', () => { if (!map.isMoving() || map._heroZooming) map.setPitch(pitchFor(map.getZoom())); });
      map.on('wheel', () => { map._heroZooming = true; });
      map.on('zoomend', () => { map._heroZooming = false; map.easeTo({ pitch: pitchFor(map.getZoom()), duration: 300 }); });
      // Page scroll stays normal: the wheel zooms the map only after a click on it (spec §7).
      el.addEventListener('click', () => { map.scrollZoom.enable(); el.classList.add('is-armed'); });
      el.addEventListener('mouseleave', () => { map.scrollZoom.disable(); el.classList.remove('is-armed'); });
      map.on('move', () => onMove?.());
    }

    map.addSource('projects', { type: 'geojson', data: points(current), cluster: !mini, clusterRadius: 44, clusterMaxZoom: 11, promoteId: 'id' });
    if (!mini) {
      map.addLayer({ id: 'cluster-glow', type: 'circle', source: 'projects', filter: ['has', 'point_count'], paint: { 'circle-color': T.glow, 'circle-radius': ['step', ['get', 'point_count'], 26, 5, 32, 15, 40], 'circle-blur': 1 } });
      map.addLayer({ id: 'clusters', type: 'circle', source: 'projects', filter: ['has', 'point_count'], paint: { 'circle-color': T.ringFill, 'circle-radius': ['step', ['get', 'point_count'], 16, 5, 20, 15, 26], 'circle-stroke-color': ORANGE, 'circle-stroke-width': 4 } });
      map.addLayer({ id: 'cluster-count', type: 'symbol', source: 'projects', filter: ['has', 'point_count'], layout: { 'text-field': ['get', 'point_count_abbreviated'], 'text-size': 14, 'text-font': ['Noto Sans Bold'] }, paint: { 'text-color': T.ink } });
    }
    map.addLayer({ id: 'ring-glow', type: 'circle', source: 'projects', filter: ['!', ['has', 'point_count']], paint: { 'circle-color': T.glow, 'circle-radius': ['interpolate', ['linear'], ['zoom'], 6, 12, 12, 18], 'circle-blur': 1 } });
    map.addLayer({ id: 'rings', type: 'circle', source: 'projects', filter: ['!', ['has', 'point_count']], paint: { 'circle-color': ['case', ['boolean', ['feature-state', 'selected'], false], ORANGE, T.ringFill], 'circle-radius': ['interpolate', ['linear'], ['zoom'], 6, ['case', ['boolean', ['feature-state', 'hover'], false], 9, 6], 12, ['case', ['boolean', ['feature-state', 'hover'], false], 14, 9]], 'circle-stroke-color': ORANGE, 'circle-stroke-width': 3, 'circle-radius-transition': { duration: 200 } } });
    if (!mini) {
      map.addLayer({ id: 'ring-labels', type: 'symbol', source: 'projects', filter: ['!', ['has', 'point_count']], minzoom: 10, layout: { 'text-field': ['get', 'title'], 'text-size': 13, 'text-offset': [0, 1.3], 'text-anchor': 'top', 'text-font': ['Noto Sans Regular'], 'text-optional': true }, paint: { 'text-color': T.ink, 'text-halo-color': T.bg, 'text-halo-width': 1.5 } });

      map.on('click', 'clusters', (e) => {
        const f = map.queryRenderedFeatures(e.point, { layers: ['clusters'] })[0];
        map.getSource('projects').getClusterExpansionZoom(f.properties.cluster_id).then((z) => map.easeTo({ center: f.geometry.coordinates, zoom: Math.min(z + 0.5, 14) }));
      });
      map.on('click', 'rings', (e) => { const f = e.features[0]; if (hero) flyTo(f); else select(f); });
      let hoverId = null;
      map.on('mousemove', 'rings', (e) => {
        const f = e.features[0];
        if (hoverId !== f.id) {
          if (hoverId !== null) map.setFeatureState({ source: 'projects', id: hoverId }, { hover: false });
          hoverId = f.id;
          map.setFeatureState({ source: 'projects', id: hoverId }, { hover: true });
          onHover?.(f.properties, e.point);
        }
      });
      map.on('mouseleave', 'rings', () => { if (hoverId !== null) map.setFeatureState({ source: 'projects', id: hoverId }, { hover: false }); hoverId = null; onHover?.(null); });
      for (const l of ['clusters', 'rings']) {
        map.on('mouseenter', l, () => (map.getCanvas().style.cursor = 'pointer'));
        map.on('mouseleave', l, () => (map.getCanvas().style.cursor = ''));
      }
      map.on('click', (e) => { if (!map.queryRenderedFeatures(e.point, { layers: ['rings', 'clusters'] }).length) deselect(); });
      el.addEventListener('keydown', (e) => { if (e.key === 'Escape') deselect(); });
    }

    if (mini && focusId) {
      const f = current.features.find((x) => x.geometry);
      if (f) map.jumpTo({ center: f.geometry.coordinates, zoom: 13 });
    } else {
      fit(current);
    }
    el.dispatchEvent(new CustomEvent('log:map-ready'));
  };
  if (map.isStyleLoaded()) onStyle(); else map.once('style.load', onStyle);

  function select(feature) {
    deselect();
    selectedId = feature.id ?? feature.properties.id;
    map.setFeatureState({ source: 'projects', id: selectedId }, { selected: true });
    if (hero) { onSelect?.(feature.properties, feature.geometry.coordinates); return; }
    popup = new maplibregl.Popup({ offset: 14, closeButton: true, closeOnClick: false, maxWidth: '320px', className: `log-popup log-popup--${theme}` })
      .setLngLat(feature.geometry.coordinates)
      .setHTML(card(feature.properties))
      .addTo(map);
    popup.on('close', () => { if (selectedId) { map.setFeatureState({ source: 'projects', id: selectedId }, { selected: false }); selectedId = null; } });
    popup.getElement().querySelector('.log-popup__link')?.focus();
    onSelect?.(feature.properties);
  }
  /** Camera in, then open (spec §7: the ring opens, the camera tilts and flies). */
  function flyTo(f) {
    const zoom = hero ? 14.2 : 13.5;
    map.once('moveend', () => select({ ...f, id: f.id ?? f.properties.id, geometry: f.geometry }));
    map.flyTo({ center: f.geometry.coordinates, zoom, pitch: hero ? pitchFor(zoom) : 0, bearing: hero ? -15 : 0, duration: hero ? 1600 : 900, essential: true });
  }
  function deselect() { popup?.remove(); popup = null; if (hero && selectedId !== null) { map.setFeatureState({ source: 'projects', id: selectedId }, { selected: false }); selectedId = null; } }

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
      flyTo(f);
      return true;
    },
    deselect,
    /** Back to the whole country (hero "חזרה למפה"). */
    reset() { deselect(); map.easeTo({ pitch: 0, bearing: 0, duration: 800 }); fit(current); },
    /** Screen position for a lng/lat, for edge indicators. */
    project: (lngLat) => map.project(lngLat),
    bounds: () => map.getBounds(),
    current: () => current,
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

/**
 * Recolour whichever basemap loaded into the theme: one background, land, water, buildings, two road
 * weights, boundaries, labels. No POIs, no transit, no house numbers. Hebrew names where OSM has them.
 */
function restyle(map, T) {
  for (const l of map.getStyle().layers) {
    const id = l.id;
    if (l.type === 'background') { map.setPaintProperty(id, 'background-color', T.bg); continue; }
    if (/poi|airport|aeroway|housenum|transit|ferry|oneway|shield/.test(id)) { map.setLayoutProperty(id, 'visibility', 'none'); continue; }
    if (l.type === 'fill') {
      if (/water/.test(id)) map.setPaintProperty(id, 'fill-color', T.water);
      else if (/building/.test(id)) map.setPaintProperty(id, 'fill-color', T.building);
      else map.setPaintProperty(id, 'fill-color', T.land);
      if (map.getPaintProperty(id, 'fill-outline-color') !== undefined) map.setPaintProperty(id, 'fill-outline-color', T.land);
      continue;
    }
    if (l.type === 'line') {
      if (/boundary|admin/.test(id)) map.setPaintProperty(id, 'line-color', T.boundary);
      else if (/water/.test(id)) map.setPaintProperty(id, 'line-color', T.water);
      else if (/motorway|trunk|primary|major|rail/.test(id)) {
        // Major roads: a faint skeleton from afar, present up close.
        map.setPaintProperty(id, 'line-color', T.roadMajor);
        map.setPaintProperty(id, 'line-opacity', ['interpolate', ['linear'], ['zoom'], 6, 0.25, 9, 0.6, 12, 1]);
      } else {
        // Minor roads only appear at the regional level, so the country reads as calm as the reference.
        map.setPaintProperty(id, 'line-color', T.road);
        map.setPaintProperty(id, 'line-opacity', ['interpolate', ['linear'], ['zoom'], 8.5, 0, 10.5, 1]);
      }
      continue;
    }
    if (l.type === 'symbol') {
      map.setPaintProperty(id, 'text-color', T.label);
      map.setPaintProperty(id, 'text-halo-color', T.bg);
      map.setPaintProperty(id, 'text-halo-width', 1);
      if (map.getLayoutProperty(id, 'text-field')) map.setLayoutProperty(id, 'text-field', ['coalesce', ['get', 'name:he'], ['get', 'name']]);
      if (map.getLayoutProperty(id, 'icon-image')) map.setLayoutProperty(id, 'icon-image', '');
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
