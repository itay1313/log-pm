/**
 * Decodes one OpenMapTiles vector tile into ready-to-upload geometry, off the main thread.
 *
 * mode 'national' (z8): sea, main roads, city/town names.
 * mode 'regional' (z11): sea, roads down to minor.
 * mode 'detail'   (z14): sea, every road, rail, and extruded buildings.
 *
 * Output arrays are Float32Array in local metres (see geo.js), transferred back without copying.
 */
import { VectorTile } from '@mapbox/vector-tile';
import Pbf from 'pbf';
import earcut from 'earcut';
import { toLocal } from './geo.js';

const MAJOR = new Set(['motorway', 'trunk', 'primary']);
const MID = new Set(['secondary', 'tertiary']);
const MINOR = new Set(['minor', 'service']);
const HEIGHT_SCALE = 1.9; // a little drama: the reference's city reads chunky
const DEFAULT_HEIGHT = 8; // most Israeli buildings have no height in OSM (docs/hero-spike.md)

self.onmessage = async (e) => {
  const { id, url, z, x, y, mode } = e.data;
  try {
    const res = await fetch(url);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const tile = new VectorTile(new Pbf(new Uint8Array(await res.arrayBuffer())));
    const water = [], major = [], roads = [], rail = [], bPos = [], bNor = [], places = [];

    const wl = tile.layers.water;
    if (wl) for (let i = 0; i < wl.length; i++) {
      const f = wl.feature(i);
      if (f.type !== 3) continue;
      eachPolygon(f.toGeoJSON(x, y, z).geometry, (rings) => fill(rings, mode === 'detail' ? -1 : -5, water));
    }

    const tl = tile.layers.transportation;
    if (tl) for (let i = 0; i < tl.length; i++) {
      const f = tl.feature(i);
      if (f.type !== 2) continue;
      const c = f.properties.class;
      let target = null;
      if (MAJOR.has(c)) target = major;
      else if (mode !== 'national' && MID.has(c)) target = roads;
      else if (mode !== 'national' && c === 'minor') target = roads;
      else if (mode === 'detail' && MINOR.has(c)) target = roads;
      else if (mode === 'detail' && (c === 'rail' || c === 'transit')) target = rail;
      if (!target) continue;
      lines(f.toGeoJSON(x, y, z).geometry, mode === 'detail' ? 0.6 : 2, target);
    }

    if (mode === 'detail' && tile.layers.building) {
      const bl = tile.layers.building;
      for (let i = 0; i < bl.length; i++) {
        const f = bl.feature(i);
        if (f.type !== 3) continue;
        // Buildings straddling tiles appear in both: keep each only in the tile that holds its centre.
        const g = f.loadGeometry()[0];
        let cx = 0, cy = 0;
        for (const p of g) { cx += p.x; cy += p.y; }
        cx /= g.length; cy /= g.length;
        if (cx < 0 || cy < 0 || cx >= f.extent || cy >= f.extent) continue;
        // OpenMapTiles writes 5 m when OSM has no height: vary those (stable per building) so the
        // city reads as a skyline, not a carpet. Real heights are kept as they are.
        const rh = Number(f.properties.render_height) || 0;
        const known = rh > 5;
        const jitter = ((Math.sin(cx * 12.9898 + cy * 78.233 + x * 3.1 + y * 7.7) * 43758.5453) % 1 + 1) % 1;
        const top = (known ? rh : DEFAULT_HEIGHT + jitter * 10) * HEIGHT_SCALE;
        const base = (Number(f.properties.render_min_height) || 0) * HEIGHT_SCALE;
        if (top <= base) continue;
        eachPolygon(f.toGeoJSON(x, y, z).geometry, (rings) => extrude(rings, base, top, bPos, bNor));
      }
    }

    if (mode === 'national' && tile.layers.place) {
      const pl = tile.layers.place;
      for (let i = 0; i < pl.length; i++) {
        const f = pl.feature(i);
        const p = f.properties;
        if (p.class !== 'city' && p.class !== 'town') continue;
        const [lng, lat] = f.toGeoJSON(x, y, z).geometry.coordinates;
        places.push({ name: p['name:he'] || p.name, lng, lat, rank: Number(p.rank) || 99, cls: p.class });
      }
    }

    const pack = (a) => (a.length ? new Float32Array(a) : null);
    const out = { id, water: pack(water), major: pack(major), roads: pack(roads), rail: pack(rail), bPos: pack(bPos), bNor: pack(bNor), places };
    const transfer = ['water', 'major', 'roads', 'rail', 'bPos', 'bNor'].map((k) => out[k]?.buffer).filter(Boolean);
    self.postMessage(out, transfer);
  } catch (err) {
    self.postMessage({ id, error: String(err) });
  }
};

function eachPolygon(geom, cb) {
  if (geom.type === 'Polygon') cb(geom.coordinates.map(ring));
  else if (geom.type === 'MultiPolygon') geom.coordinates.forEach((poly) => cb(poly.map(ring)));
}

function ring(coords) {
  const r = coords.map(([lng, lat]) => toLocal(lng, lat));
  const a = r[0], b = r[r.length - 1];
  if (r.length > 1 && a[0] === b[0] && a[1] === b[1]) r.pop();
  return r;
}

function fill(rings, y, out) {
  const flat = [], holes = [];
  let n = 0;
  rings.forEach((r, i) => { if (i) holes.push(n); for (const [x, z] of r) { flat.push(x, z); n++; } });
  const tri = earcut(flat, holes, 2);
  for (const k of tri) out.push(flat[k * 2], y, flat[k * 2 + 1]);
}

function lines(geom, y, out) {
  const parts = geom.type === 'LineString' ? [geom.coordinates] : geom.type === 'MultiLineString' ? geom.coordinates : [];
  for (const part of parts) {
    let prev = null;
    for (const [lng, lat] of part) {
      const p = toLocal(lng, lat);
      if (prev) out.push(prev[0], y, prev[1], p[0], y, p[1]);
      prev = p;
    }
  }
}

function extrude(rings, base, top, pos, nor) {
  const flat = [], holes = [];
  let n = 0;
  rings.forEach((r, i) => { if (i) holes.push(n); for (const [x, z] of r) { flat.push(x, z); n++; } });
  const tri = earcut(flat, holes, 2);
  // Roofs must face up (counter-clockwise seen from above), or the renderer lights their underside.
  for (let i = 0; i < tri.length; i += 3) {
    const a = tri[i] * 2, b = tri[i + 1] * 2, c = tri[i + 2] * 2;
    const up = (flat[b + 1] - flat[a + 1]) * (flat[c] - flat[a]) - (flat[b] - flat[a]) * (flat[c + 1] - flat[a + 1]);
    const order = up >= 0 ? [a, b, c] : [a, c, b];
    for (const k of order) { pos.push(flat[k], top, flat[k + 1]); nor.push(0, 1, 0); }
  }

  rings.forEach((r, ri) => {
    let cx = 0, cz = 0;
    for (const [x, z] of r) { cx += x; cz += z; }
    cx /= r.length; cz /= r.length;
    for (let i = 0; i < r.length; i++) {
      const [x1, z1] = r[i];
      const [x2, z2] = r[(i + 1) % r.length];
      const dx = x2 - x1, dz = z2 - z1;
      const len = Math.hypot(dx, dz) || 1;
      let nx = dz / len, nz = -dx / len;
      let outward = nx * ((x1 + x2) / 2 - cx) + nz * ((z1 + z2) / 2 - cz);
      if (ri > 0) outward = -outward; // holes face inward
      if (outward < 0) { nx = -nx; nz = -nz; }
      // The quad below winds towards (-dz, dx); reverse it when the outward normal points the other way.
      if (nx * -dz + nz * dx >= 0) pos.push(x1, base, z1, x2, base, z2, x2, top, z2, x1, base, z1, x2, top, z2, x1, top, z1);
      else pos.push(x1, base, z1, x2, top, z2, x2, base, z2, x1, base, z1, x1, top, z1, x2, top, z2);
      for (let k = 0; k < 6; k++) nor.push(nx, 0, nz);
    }
  });
}
