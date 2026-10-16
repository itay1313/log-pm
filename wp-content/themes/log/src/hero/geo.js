/**
 * Geography for the hero scene. One local tangent plane centred on Israel, in metres:
 * x grows east, z grows south (three.js: y is up). Shared by the scene and the tile worker.
 */
export const ORIGIN = { lng: 34.95, lat: 31.6 };
const R = 6378137;
const RAD = Math.PI / 180;
const K = Math.cos(ORIGIN.lat * RAD);

export function toLocal(lng, lat) {
  return [(lng - ORIGIN.lng) * RAD * R * K, -(lat - ORIGIN.lat) * RAD * R];
}

export function toLngLat(x, z) {
  return [ORIGIN.lng + x / (R * K) / RAD, ORIGIN.lat - z / R / RAD];
}

export function lngLatToTile(lng, lat, z) {
  const n = 2 ** z;
  const x = Math.floor(((lng + 180) / 360) * n);
  const r = lat * RAD;
  const y = Math.floor(((1 - Math.log(Math.tan(r) + 1 / Math.cos(r)) / Math.PI) / 2) * n);
  return [x, y];
}

/** All tiles at zoom z covering a lng/lat box. */
export function tilesInBox(west, south, east, north, z) {
  const [x0, y0] = lngLatToTile(west, north, z);
  const [x1, y1] = lngLatToTile(east, south, z);
  const out = [];
  for (let y = y0; y <= y1; y++) for (let x = x0; x <= x1; x++) out.push([x, y]);
  return out;
}

/** Local x/z bounds of the tiles tilesInBox returns, so layers can fade out before the square edge. */
export function tileBoxLocal(west, south, east, north, z) {
  const n = 2 ** z;
  const [x0, y0] = lngLatToTile(west, north, z);
  const [x1, y1] = lngLatToTile(east, south, z);
  const lng = (x) => (x / n) * 360 - 180;
  const lat = (y) => Math.atan(Math.sinh(Math.PI * (1 - (2 * y) / n))) / RAD;
  const [minX, minZ] = toLocal(lng(x0), lat(y0));
  const [maxX, maxZ] = toLocal(lng(x1 + 1), lat(y1 + 1));
  return [minX, minZ, maxX, maxZ];
}

// West reaches one tile column out to sea, so the Mediterranean fades out instead of ending at a tile edge.
export const ISRAEL_BOX = [32.6, 29.3, 36.1, 33.5];
