/**
 * Israeli Transverse Mercator (ITM, the national survey grid) from WGS84 lat/lng.
 * Transverse Mercator on GRS80 with the ITM origin. The WGS84 → Israel 1993 datum shift is left out,
 * so values are good to a few tens of metres: fine for a margin readout rounded to 10 m, not for surveying.
 * Same formula as log_itm() in log-core.
 */
const A = 6378137;
const F = 1 / 298.257222101;
const E2 = F * (2 - F);
const EP2 = E2 / (1 - E2);
const RAD = Math.PI / 180;
const LAT0 = 31.73439361 * RAD;
const LON0 = 35.20451694 * RAD;
const K0 = 1.0000067;
const FE = 219529.584;
const FN = 626907.39;

function meridian(phi) {
  const e4 = E2 * E2, e6 = e4 * E2;
  return A * ((1 - E2 / 4 - (3 * e4) / 64 - (5 * e6) / 256) * phi
    - ((3 * E2) / 8 + (3 * e4) / 32 + (45 * e6) / 1024) * Math.sin(2 * phi)
    + ((15 * e4) / 256 + (45 * e6) / 1024) * Math.sin(4 * phi)
    - ((35 * e6) / 3072) * Math.sin(6 * phi));
}
const M0 = meridian(LAT0);

/** @returns {[number, number]} [easting, northing] in metres */
export function toITM(lat, lng) {
  const phi = lat * RAD;
  const s = Math.sin(phi), c = Math.cos(phi), t = Math.tan(phi);
  const n = A / Math.sqrt(1 - E2 * s * s);
  const T = t * t, C = EP2 * c * c, a = (lng * RAD - LON0) * c;
  const x = K0 * n * (a + ((1 - T + C) * a ** 3) / 6 + ((5 - 18 * T + T * T + 72 * C - 58 * EP2) * a ** 5) / 120);
  const y = K0 * (meridian(phi) - M0 + n * t * (a * a / 2 + ((5 - T + 9 * C + 4 * C * C) * a ** 4) / 24 + ((61 - 58 * T + T * T + 600 * C - 330 * EP2) * a ** 6) / 720));
  return [FE + x, FN + y];
}

const fmt = new Intl.NumberFormat('he-IL', { maximumFractionDigits: 0, useGrouping: false });
/** "180350 / 664120": easting / northing, rounded to 10 m. */
export function itmLabel(lat, lng) {
  const [e, n] = toITM(lat, lng);
  return `${fmt.format(Math.round(e / 10) * 10)} / ${fmt.format(Math.round(n / 10) * 10)}`;
}
