/**
 * The hero scene: Israel as a dark navy plate, the city rising in orange as you approach.
 *
 * Three levels of detail from OpenMapTiles vector tiles (OpenFreeMap, no account):
 *   national z8  — sea + main roads, drawn in with a radial reveal on load
 *   regional z11 — the road grid, faded in below ~160 km
 *   detail   z14 — every road + extruded buildings that rise out of the ground below ~40 km
 * LOG's own plan boundaries (from the CMS) sit on top as orange plates with a white edge.
 *
 * The scene knows nothing about the DOM UI; hero.js asks it for screen positions every frame.
 */
import * as THREE from 'three';
import { MapControls } from 'three/examples/jsm/controls/MapControls.js';
import { EffectComposer } from 'three/examples/jsm/postprocessing/EffectComposer.js';
import { RenderPass } from 'three/examples/jsm/postprocessing/RenderPass.js';
import { UnrealBloomPass } from 'three/examples/jsm/postprocessing/UnrealBloomPass.js';
import { OutputPass } from 'three/examples/jsm/postprocessing/OutputPass.js';
import { gsap } from 'gsap';
import earcut from 'earcut';
import { toLocal, toLngLat, lngLatToTile, tilesInBox, tileBoxLocal, ISRAEL_BOX } from './geo.js';

const C = {
  fog: '#1d2a3a',
  sea: '#2f4a63',
  building: '#f7931d', // the pinned orange city (PRODUCT.md brand commitments), in the brand orange itself
  roadNational: '#9db1c6',
  roadRegional: '#b4c4d4',
  road: '#e3ebf2',
  roadMajor: '#ffffff',
  plan: '#f7931d',
};
const NATIONAL = { tx: 70000, tz: -20000, radius: 430000, phi: 0.62, theta: -0.18 };
// Home: Gush Dan from the north, looking south. The sea fills the start (right) side, where the
// headline sits; the orange city stands on the end side.
const [HX, HZ] = toLocal(34.795, 32.05);
const HOME = { tx: HX - 4400, tz: HZ + 300, radius: 11500, phi: 0.98, theta: Math.PI - 0.47 };
const SWAY = 0.1; // the resting camera breathes this far and back instead of orbiting the sea away
const MID_AT = 9000; // below this the full-detail z14 city takes over from the z13 one
const REGIONAL_AT = 170000;
const DETAIL_AT = 42000;
const MAX_DETAIL_TILES = 30;
const MAX_MID_TILES = 12;
const MAX_REGIONAL_TILES = 16;

export async function createScene(container, { reduced = false } = {}) {
  const tpl = await tileTemplate();

  const renderer = new THREE.WebGLRenderer({ antialias: true, powerPreference: 'high-performance' });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.75));
  container.appendChild(renderer.domElement);

  const scene = new THREE.Scene();
  scene.background = skyTexture();
  scene.fog = new THREE.Fog(C.fog, 1e5, 1e6);
  const camera = new THREE.PerspectiveCamera(30, 1, 10, 1e7);
  // A trace of glow only: the survey sheet is printed, not lit (no SaaS halo).
  const composer = new EffectComposer(renderer);
  composer.addPass(new RenderPass(scene, camera));
  const bloom = new UnrealBloomPass(new THREE.Vector2(1, 1), 0.14, 0.35, 0.78);
  composer.addPass(bloom);
  composer.addPass(new OutputPass());

  scene.add(new THREE.HemisphereLight(0xfff1e2, 0x1b2430, 1.35));
  const sun = new THREE.DirectionalLight(0xfff4ea, 2.6);
  sun.position.set(-0.55, 1, 0.35);
  scene.add(sun);

  const controls = new MapControls(camera, renderer.domElement);
  controls.enableDamping = true;
  controls.dampingFactor = 0.075;
  controls.screenSpacePanning = false;
  controls.minDistance = 450;
  controls.maxDistance = 1200000;
  controls.maxPolarAngle = 1.18;
  controls.enableZoom = false; // only with Ctrl/⌘ held (hero.js), so the page keeps scrolling
  controls.zoomSpeed = 1.4;
  controls.rotateSpeed = 0.5;
  controls.autoRotateSpeed = -0.12;

  // Materials --------------------------------------------------------------
  const fogUniforms = { uFogNear: { value: 1e5 }, uFogFar: { value: 1e6 } };
  const revealUniforms = {
    uReveal: { value: reduced ? 1e9 : 0 },
    uCenter: { value: new THREE.Vector2(NATIONAL.tx, NATIONAL.tz) },
    uBox: { value: new THREE.Vector4(...tileBoxLocal(...ISRAEL_BOX, 8)) },
  };
  const mat = {
    national: lineMaterial(C.roadNational, 0.42, fogUniforms, revealUniforms),
    regional: lineMaterial(C.roadRegional, 0.0, fogUniforms),
    road: lineMaterial(C.road, 0.0, fogUniforms),
    roadMajor: lineMaterial(C.roadMajor, 0.0, fogUniforms),
    rail: lineMaterial(C.road, 0.0, fogUniforms),
    sea: seaMaterial(C.sea, reduced ? 1 : 0, revealUniforms),
    building: siteBuildings(new THREE.MeshLambertMaterial({ color: C.building, emissive: '#241000', side: THREE.FrontSide })),
    planFill: new THREE.MeshBasicMaterial({ color: C.plan, transparent: true, opacity: 0.55, depthWrite: false, side: THREE.DoubleSide }),
    planFillLarge: new THREE.MeshBasicMaterial({ color: C.plan, transparent: true, opacity: 0.14, depthWrite: false, side: THREE.DoubleSide }),
    planEdge: lineMaterial('#fff6ec', 1.0, fogUniforms),
  };

  const groups = { national: new THREE.Group(), regional: new THREE.Group(), mid: new THREE.Group(), detail: new THREE.Group(), plans: new THREE.Group() };
  Object.values(groups).forEach((g) => scene.add(g));

  // Tiles -------------------------------------------------------------------
  const pool = [0, 1].map(() => new Worker(new URL('./tile-worker.js', import.meta.url), { type: 'module' }));
  const waiting = new Map();
  let seq = 0, rr = 0;
  pool.forEach((w) => (w.onmessage = (e) => { const cb = waiting.get(e.data.id); waiting.delete(e.data.id); cb?.(e.data); }));
  const fetchTile = (z, x, y, mode) => new Promise((resolve) => {
    const id = ++seq;
    waiting.set(id, resolve);
    pool[rr++ % pool.length].postMessage({ id, url: tpl.replace('{z}', z).replace('{x}', x).replace('{y}', y), z, x, y, mode: mode === 'mid' ? 'detail' : mode });
  });

  const loaded = { regional: new Map(), mid: new Map(), detail: new Map() };
  const places = [];

  function addTile(data, mode, key) {
    if (data.error) return null;
    const holder = new THREE.Group();
    holder.userData.key = key;
    if (data.water) holder.add(mesh(data.water, null, mat.sea, 0));
    if (mode === 'national') {
      if (data.major) holder.add(segments(data.major, mat.national));
    } else if (mode === 'regional') {
      if (data.major) holder.add(segments(data.major, mat.regional));
      if (data.roads) holder.add(segments(data.roads, mat.regional));
    } else {
      if (data.major) holder.add(segments(data.major, mat.roadMajor));
      if (data.roads) holder.add(segments(data.roads, mat.road));
      if (data.rail) holder.add(segments(data.rail, mat.rail));
      if (data.bPos) {
        const b = mesh(data.bPos, data.bNor, mat.building, 2);
        // Rise out of the ground, nearest tiles first.
        b.scale.y = 0.001;
        const centre = tileCentre(key);
        const d = centre.distanceTo(new THREE.Vector2(controls.target.x, controls.target.z));
        gsap.to(b.scale, { y: 1, duration: reduced ? 0 : 1.6, delay: reduced ? 0 : Math.min(0.9, d / 6000), ease: 'expo.out' });
        holder.add(b);
      }
    }
    if (data.places?.length) places.push(...data.places);
    groups[mode].add(holder);
    return holder;
  }

  function tileCentre(key) {
    const [z, x, y] = key.split('/').map(Number);
    const n = 2 ** z;
    const lng = ((x + 0.5) / n) * 360 - 180;
    const lat = (Math.atan(Math.sinh(Math.PI * (1 - (2 * (y + 0.5)) / n))) * 180) / Math.PI;
    const [lx, lz] = toLocal(lng, lat);
    return new THREE.Vector2(lx, lz);
  }

  function ensureAround(lng, lat, mode) {
    const z = mode === 'detail' ? 14 : mode === 'mid' ? 13 : 11;
    const map = loaded[mode];
    const [cx, cy] = lngLatToTile(lng, lat, z);
    const ring = [[0, 0], [1, 0], [-1, 0], [0, 1], [0, -1], [1, 1], [-1, -1], [1, -1], [-1, 1]];
    for (const [dx, dy] of ring) {
      const key = `${z}/${cx + dx}/${cy + dy}`;
      if (map.has(key)) continue;
      map.set(key, null);
      fetchTile(z, cx + dx, cy + dy, mode).then((d) => {
        if (!map.has(key)) return; // evicted while loading
        map.set(key, addTile(d, mode, key));
        evict(mode);
      });
    }
  }

  function evict(mode) {
    const map = loaded[mode];
    const max = mode === 'detail' ? MAX_DETAIL_TILES : mode === 'mid' ? MAX_MID_TILES : MAX_REGIONAL_TILES;
    if (map.size <= max) return;
    const t = new THREE.Vector2(controls.target.x, controls.target.z);
    const far = [...map.entries()].filter(([, g]) => g).sort((a, b) => tileCentre(b[0]).distanceTo(t) - tileCentre(a[0]).distanceTo(t));
    while (map.size > max && far.length) {
      const [key, g] = far.shift();
      g.traverse((o) => o.geometry?.dispose());
      groups[mode].remove(g);
      map.delete(key);
    }
  }

  // National layer, all at once.
  const nationalReady = Promise.all(tilesInBox(...ISRAEL_BOX, 8).map(([x, y]) => fetchTile(8, x, y, 'national').then((d) => addTile(d, 'national', `8/${x}/${y}`))));

  // Plans from the CMS ------------------------------------------------------
  function setPlans(features) {
    groups.plans.clear();
    for (const f of features) {
      const b = f.properties.boundary;
      if (!b) continue;
      const polys = b.type === 'Polygon' ? [b.coordinates] : b.type === 'MultiPolygon' ? b.coordinates : [];
      for (const poly of polys) {
        const rings = poly.map((r) => r.map(([lng, lat]) => toLocal(lng, lat)));
        const flat = [], holes = [];
        let n = 0;
        rings.forEach((r, i) => { if (i) holes.push(n); for (const [x, z] of r) { flat.push(x, z); n++; } });
        const tri = earcut(flat, holes, 2);
        const pos = new Float32Array(tri.length * 3);
        tri.forEach((k, i) => { pos[i * 3] = flat[k * 2]; pos[i * 3 + 1] = 4; pos[i * 3 + 2] = flat[k * 2 + 1]; });
        // Regional-scale plans (tens of km²) would swamp the map: faint fill, keep the edge.
        let area = 0;
        for (let i = 0; i < tri.length; i += 3) {
          const a = tri[i] * 2, b = tri[i + 1] * 2, c = tri[i + 2] * 2;
          area += Math.abs((flat[b] - flat[a]) * (flat[c + 1] - flat[a + 1]) - (flat[c] - flat[a]) * (flat[b + 1] - flat[a + 1])) / 2;
        }
        const fill = mesh(pos, null, area > 40e6 ? mat.planFillLarge : mat.planFill, 1);
        fill.userData.id = f.id;
        groups.plans.add(fill);
        const edge = [];
        for (const r of rings) for (let i = 0; i < r.length - 1; i++) edge.push(r[i][0], 6, r[i][1], r[i + 1][0], 6, r[i + 1][1]);
        groups.plans.add(segments(new Float32Array(edge), mat.planEdge));
      }
    }
  }

  // Beacons: one point per project for the drive's national chapter, lit in turn as uLit runs 0 → 1.
  const beaconMat = new THREE.ShaderMaterial({
    transparent: true, depthTest: false, depthWrite: false,
    uniforms: { uLit: { value: 0 }, uSize: { value: 11 }, uWorks: { value: new THREE.Color(C.plan) }, uPlate: { value: new THREE.Color(C.fog) } },
    vertexShader: /* glsl */ `
      attribute float aOrder;
      uniform float uLit, uSize;
      varying float vOn;
      void main() {
        vOn = smoothstep(aOrder - 0.05, aOrder, uLit);
        float pop = 1.0 + 1.6 * vOn * (1.0 - vOn); // a beat bigger while it lights
        gl_PointSize = uSize * pop;
        gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
      }`,
    fragmentShader: /* glsl */ `
      uniform vec3 uWorks, uPlate;
      varying float vOn;
      void main() {
        vec2 c = abs(gl_PointCoord - 0.5);
        float d = max(c.x, c.y);
        if (d > 0.5 || vOn < 0.01) discard;
        // The marker in miniature: works orange at the heart, a navy keyline round it.
        gl_FragColor = vec4(d > 0.32 ? uPlate : uWorks, vOn);
      }`,
  });
  const beacons = new THREE.Points(new THREE.BufferGeometry(), beaconMat);
  beacons.renderOrder = 10;
  beacons.frustumCulled = false;
  beacons.visible = false;
  scene.add(beacons);
  /** Points in local x/z, lit in order of distance from (ox, oz): a ripple out from where the camera stood. */
  function setBeacons(points, ox, oz) {
    const ranked = points.map(([x, z]) => [x, z, Math.hypot(x - ox, z - oz)]).sort((a, b) => a[2] - b[2]);
    const pos = new Float32Array(ranked.length * 3), order = new Float32Array(ranked.length);
    ranked.forEach(([x, z], i) => { pos.set([x, 20, z], i * 3); order[i] = 0.05 + 0.95 * (i / Math.max(1, ranked.length - 1)); });
    beacons.geometry.setAttribute('position', new THREE.BufferAttribute(pos, 3));
    beacons.geometry.setAttribute('aOrder', new THREE.BufferAttribute(order, 1));
  }

  // The build site (home/drive.js, chapter 2) --------------------------------
  // One plan drawn on its own: a dashed boundary that draws itself, its own fill, and a mask that lets
  // the building shader flatten what stands inside and raise it again floor by floor.
  const site = { group: new THREE.Group(), ribbon: null, fill: null, mask: null };
  site.group.renderOrder = 11;
  site.group.visible = false;
  scene.add(site.group);
  const ribbonMat = new THREE.ShaderMaterial({
    transparent: true, depthTest: false, depthWrite: false, side: THREE.DoubleSide,
    uniforms: { uDraw: { value: 0 }, uTotal: { value: 1 }, uWidth: { value: 6 }, uDash: { value: 60 }, uColor: { value: new THREE.Color(C.plan) } },
    vertexShader: /* glsl */ `
      attribute float aSide, aDist;
      attribute vec2 aDir;
      uniform float uWidth;
      varying float vDist;
      void main() {
        vDist = aDist;
        vec3 p = position + vec3(-aDir.y, 0.0, aDir.x) * aSide * uWidth * 0.5;
        gl_Position = projectionMatrix * modelViewMatrix * vec4(p, 1.0);
      }`,
    fragmentShader: /* glsl */ `
      uniform float uDraw, uTotal, uDash;
      uniform vec3 uColor;
      varying float vDist;
      void main() {
        if (vDist > uDraw * uTotal || fract(vDist / uDash) > 0.56) discard; // the lane line's 30 on, 24 off
        gl_FragColor = vec4(uColor, 1.0);
      }`,
  });
  const siteFillMat = new THREE.MeshBasicMaterial({ color: C.plan, transparent: true, opacity: 0, depthWrite: false, side: THREE.DoubleSide });

  /** Rings of one plan (local x/z, outer first) become the site. Returns its centre, size and rings. */
  function setSite(boundary) {
    site.group.clear();
    const polys = boundary.type === 'Polygon' ? [boundary.coordinates] : boundary.type === 'MultiPolygon' ? boundary.coordinates : [];
    const local = polys.map((poly) => poly.map((r) => r.map(([lng, lat]) => toLocal(lng, lat))));
    const outer = local.flatMap((poly) => poly[0]);
    if (!outer.length) return null;
    let minX = Infinity, maxX = -Infinity, minZ = Infinity, maxZ = -Infinity;
    for (const [x, z] of outer) { minX = Math.min(minX, x); maxX = Math.max(maxX, x); minZ = Math.min(minZ, z); maxZ = Math.max(maxZ, z); }
    const pad = Math.max(maxX - minX, maxZ - minZ) * 0.04 + 10;
    minX -= pad; minZ -= pad; maxX += pad; maxZ += pad;

    // Mask: the plan painted white on black over its bounding box (even-odd keeps the holes).
    const N = 1024, cv = document.createElement('canvas');
    cv.width = cv.height = N;
    const g = cv.getContext('2d');
    g.fillStyle = '#000'; g.fillRect(0, 0, N, N); g.fillStyle = '#fff';
    const toPx = ([x, z]) => [((x - minX) / (maxX - minX)) * N, ((z - minZ) / (maxZ - minZ)) * N];
    for (const poly of local) {
      g.beginPath();
      for (const ring of poly) ring.forEach((pt, i) => { const [px, py] = toPx(pt); i ? g.lineTo(px, py) : g.moveTo(px, py); });
      g.fill('evenodd');
    }
    site.mask?.dispose();
    site.mask = new THREE.CanvasTexture(cv);
    site.mask.flipY = false;
    Object.assign(mat.building.userData.site, { mask: site.mask, min: [minX, minZ], size: [maxX - minX, maxZ - minZ] });
    mat.building.userData.apply?.();

    // Fill
    const pos = [];
    for (const poly of local) {
      const flat = [], holes = [];
      let n = 0;
      poly.forEach((r, i) => { if (i) holes.push(n); for (const [x, z] of r) { flat.push(x, z); n++; } });
      for (const k of earcut(flat, holes, 2)) pos.push(flat[k * 2], 5, flat[k * 2 + 1]);
    }
    site.fill = mesh(new Float32Array(pos), null, siteFillMat, 11);
    site.group.add(site.fill);

    // Boundary ribbon along every outer ring, one quad per segment, distance carried for the dash and the draw.
    const P = [], side = [], dir = [], dist = [], idx = [];
    let total = 0;
    for (const poly of local) {
      const r = poly[0];
      for (let i = 0; i < r.length - 1; i++) {
        const [ax, az] = r[i], [bx, bz] = r[i + 1];
        const len = Math.hypot(bx - ax, bz - az);
        if (!len) continue;
        const dx = (bx - ax) / len, dz = (bz - az) / len, b = P.length / 3;
        for (const [x, z, d] of [[ax, az, total], [bx, bz, total + len]]) for (const sd of [-1, 1]) { P.push(x, 8, z); side.push(sd); dir.push(dx, dz); dist.push(d); }
        idx.push(b, b + 1, b + 2, b + 1, b + 3, b + 2);
        total += len;
      }
    }
    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.Float32BufferAttribute(P, 3));
    geo.setAttribute('aSide', new THREE.Float32BufferAttribute(side, 1));
    geo.setAttribute('aDir', new THREE.Float32BufferAttribute(dir, 2));
    geo.setAttribute('aDist', new THREE.Float32BufferAttribute(dist, 1));
    geo.setIndex(idx);
    site.ribbon = new THREE.Mesh(geo, ribbonMat);
    site.ribbon.renderOrder = 12;
    site.ribbon.frustumCulled = false;
    site.group.add(site.ribbon);
    ribbonMat.uniforms.uTotal.value = total;

    const cx = outer.reduce((a, [x]) => a + x, 0) / outer.length, cz = outer.reduce((a, [, z]) => a + z, 0) / outer.length;
    return { cx, cz, span: Math.max(maxX - minX, maxZ - minZ) - 2 * pad, rings: local.map((poly) => poly[0]) };
  }

  // Camera ------------------------------------------------------------------
  const view = { ...NATIONAL };
  function applyView() {
    const t = new THREE.Vector3(view.tx, 0, view.tz);
    camera.position.copy(t).add(new THREE.Vector3().setFromSpherical(new THREE.Spherical(view.radius, view.phi, view.theta)));
    controls.target.copy(t);
    camera.lookAt(t);
    camera.updateMatrixWorld(); // toScreen() reads it before the next render (the drive's overlay)
  }
  function readView() {
    const s = new THREE.Spherical().setFromVector3(camera.position.clone().sub(controls.target));
    Object.assign(view, { tx: controls.target.x, tz: controls.target.z, radius: s.radius, phi: s.phi, theta: s.theta });
  }
  let tween = null, sway = null, settle = null;
  // While the page's scroll drives the camera (home/drive.js), moves and the sway stand aside.
  let held = false, heldSwaying = false, introDone = false;
  /** The resting camera sways slowly around home, so the sea stays under the headline. */
  function startSway() {
    if (reduced || held) return;
    readView();
    sway?.kill();
    sway = gsap.to(view, { theta: view.theta + SWAY, duration: 18, ease: 'sine.inOut', yoyo: true, repeat: -1, onUpdate: applyView });
  }
  function moveTo(to, { duration = 2.2, ease = 'power3.inOut' } = {}) {
    if (held) return Promise.resolve();
    readView();
    tween?.kill();
    sway?.kill();
    controls.enabled = false;
    controls.autoRotate = false;
    // shortest way round
    if (to.theta !== undefined) {
      let d = to.theta - view.theta;
      d = Math.atan2(Math.sin(d), Math.cos(d));
      to = { ...to, theta: view.theta + d };
    }
    // A move cut short by another never settles (the opening must not carry on over a visitor's flight);
    // only hold() settles the one in flight.
    return new Promise((resolve) => {
      settle = resolve;
      tween = gsap.to(view, { ...to, duration: reduced ? 0 : duration, ease, onUpdate: applyView, onComplete: () => { settle = null; controls.enabled = true; controls.update(); resolve(); } });
    });
  }

  // Frame loop --------------------------------------------------------------
  let running = true, raf = 0, lastLod = 0;
  const frameCallbacks = new Set();
  const smooth = (a, b, x) => { const t = Math.min(1, Math.max(0, (x - a) / (b - a))); return t * t * (3 - 2 * t); };

  function frame(now) {
    raf = requestAnimationFrame(frame);
    if (!running) return;
    if (!held && !tween?.isActive()) controls.update();
    const r = camera.position.distanceTo(controls.target);
    camera.near = Math.max(1, r * 0.004);
    camera.far = r * 40;
    camera.updateProjectionMatrix();
    scene.fog.near = r * 1.4;
    scene.fog.far = r * 5.5;
    fogUniforms.uFogNear.value = r * 1.4;
    fogUniforms.uFogFar.value = r * 5.5;

    // Level of detail: fade layers in as the camera comes down.
    mat.regional.uniforms.uOpacity.value = 0.34 * smooth(REGIONAL_AT, REGIONAL_AT * 0.55, r);
    mat.road.uniforms.uOpacity.value = 0.42 * smooth(DETAIL_AT, DETAIL_AT * 0.45, r);
    mat.rail.uniforms.uOpacity.value = 0.25 * smooth(DETAIL_AT, DETAIL_AT * 0.45, r);
    mat.roadMajor.uniforms.uOpacity.value = 0.55 * smooth(DETAIL_AT, DETAIL_AT * 0.45, r);
    mat.national.uniforms.uOpacity.value = 0.42 * (1 - 0.6 * smooth(DETAIL_AT * 1.5, DETAIL_AT * 0.4, r));
    mat.planFill.opacity = 0.7 - 0.42 * smooth(60000, 14000, r); // city level: the plan stays readable under the orange city, its paper edge marks it
    mat.planFillLarge.opacity = 0.14 * (1 - smooth(80000, 30000, r)); // regional plans: gone by city level
    if (site.ribbon) {
      const mpp = (2 * r * Math.tan((camera.fov * Math.PI) / 360)) / container.clientHeight;
      ribbonMat.uniforms.uWidth.value = 6 * mpp;
      ribbonMat.uniforms.uDash.value = 54 * mpp;
    }
    groups.regional.visible = r < REGIONAL_AT;
    groups.mid.visible = r < DETAIL_AT * 1.2 && r >= MID_AT;
    groups.detail.visible = r < MID_AT;

    if (now - lastLod > 250) {
      lastLod = now;
      const [lng, lat] = toLngLat(controls.target.x, controls.target.z);
      if (r < REGIONAL_AT) ensureAround(lng, lat, 'regional');
      if (r < DETAIL_AT && r >= MID_AT * 0.8) ensureAround(lng, lat, 'mid');
      if (r < MID_AT * 1.2) ensureAround(lng, lat, 'detail');
    }

    composer.render();
    frameCallbacks.forEach((cb) => { try { cb(r); } catch (err) { console.error(err); } });
  }

  let shift = 0; // fraction of the width the picture slides towards the end side, clear of a legend
  function resize() {
    const w = container.clientWidth, h = container.clientHeight;
    renderer.setSize(w, h, false);
    composer.setPixelRatio(renderer.getPixelRatio());
    composer.setSize(w, h);
    camera.aspect = w / h;
    if (shift) camera.setViewOffset(w, h, shift * w, 0, w, h); else camera.clearViewOffset();
    camera.updateProjectionMatrix();
    beaconMat.uniforms.uSize.value = (w < 700 ? 8 : 11) * renderer.getPixelRatio();
  }
  const ro = new ResizeObserver(resize);
  ro.observe(container);
  resize();
  applyView();
  raf = requestAnimationFrame(frame);

  const v3 = new THREE.Vector3();
  return {
    renderer, camera, controls, scene, groups,
    ready: nationalReady,
    places,
    setPlans,
    onFrame: (cb) => frameCallbacks.add(cb),
    pause: () => { running = false; },
    resume: () => { running = true; },
    radius: () => camera.position.distanceTo(controls.target),
    theta: () => new THREE.Spherical().setFromVector3(camera.position.clone().sub(controls.target)).theta,
    national: HOME,

    /** Screen position (CSS px within the container) for a local x/z; null when behind the camera. */
    toScreen(x, z, y = 0) {
      v3.set(x, y, z).project(camera);
      if (v3.z > 1) return null;
      return { x: (v3.x * 0.5 + 0.5) * container.clientWidth, y: (-v3.y * 0.5 + 0.5) * container.clientHeight };
    },

    /** Metres per CSS pixel at the target, for the scale bar. */
    metresPerPixel() {
      const r = camera.position.distanceTo(controls.target);
      return (2 * r * Math.tan((camera.fov * Math.PI) / 360)) / container.clientHeight;
    },

    /** Opening move: from straight above, far out, swing down into the national view while the map draws itself. */
    async intro() {
      // Preload Gush Dan so the city is rising as the camera arrives.
      const [hlng, hlat] = toLngLat(HOME.tx, HOME.tz);
      ensureAround(hlng, hlat, 'regional');
      ensureAround(hlng, hlat, 'mid');
      if (reduced) { Object.assign(view, HOME); applyView(); introDone = true; return; }
      // From high above the whole country, the map drawing itself in, down into Tel Aviv.
      Object.assign(view, { ...NATIONAL, radius: 1300000, phi: 0.02, theta: NATIONAL.theta - 1.1 });
      applyView();
      gsap.to(mat.sea.uniforms.uOpacity, { value: 1, duration: 2.6, ease: 'power2.out', delay: 0.3 });
      gsap.to(revealUniforms.uReveal, { value: 750000, duration: 3.4, ease: 'power2.inOut', delay: 0.1 });
      await moveTo({ ...NATIONAL, radius: 520000 }, { duration: 2.2, ease: 'power2.inOut' });
      await moveTo(HOME, { duration: 4.2, ease: 'power3.inOut' });
      if (!held) introDone = true;
      startSway();
    },

    moveTo,

    /** Fly to a local point: dive and tilt (spec §7 "the camera tilts and flies"). Preloads the city on the way. */
    flyTo(x, z, { radius = 2600, phi = 0.98, duration = 2.6 } = {}) {
      const [lng, lat] = toLngLat(x, z);
      ensureAround(lng, lat, 'regional');
      ensureAround(lng, lat, 'detail');
      readView();
      return moveTo({ tx: x, tz: z, radius, phi, theta: view.theta + 0.35 }, { duration, ease: 'power3.inOut' });
    },

    /** Frame a set of local points. */
    fit(points, { duration = 2 } = {}) {
      if (!points.length) return Promise.resolve();
      let minX = Infinity, maxX = -Infinity, minZ = Infinity, maxZ = -Infinity;
      for (const [x, z] of points) { minX = Math.min(minX, x); maxX = Math.max(maxX, x); minZ = Math.min(minZ, z); maxZ = Math.max(maxZ, z); }
      const span = Math.max(maxX - minX, maxZ - minZ, 4000);
      return moveTo({ tx: (minX + maxX) / 2, tz: (minZ + maxZ) / 2, radius: span * 2.2, phi: 0.8 }, { duration });
    },

    home() { return moveTo(HOME, { duration: 2.4 }).then(startSway); },

    /** Zoom buttons: scale the distance to the target, inside the controls' limits. Preloads detail when close. */
    zoomBy(factor) {
      readView();
      const radius = Math.min(controls.maxDistance, Math.max(controls.minDistance, view.radius * factor));
      if (radius < 20000) {
        const [lng, lat] = toLngLat(view.tx, view.tz);
        ensureAround(lng, lat, 'regional');
        ensureAround(lng, lat, 'detail');
      }
      return moveTo({ radius }, { duration: 0.7, ease: 'power2.out' });
    },

    country() { return moveTo(NATIONAL, { duration: 2.6 }); },

    /** The page's scroll takes the camera: any move stops where it is, and the view it left is returned. */
    hold() {
      if (held) return { ...view };
      readView();
      heldSwaying = !!sway?.isActive();
      tween?.kill();
      sway?.kill();
      held = true;
      settle?.();
      settle = null;
      controls.enabled = false;
      return { ...view };
    },
    /** Set the view outright (the scroll calls this every frame it moves). */
    setView(v) { Object.assign(view, v); applyView(); },
    /** Hand the camera back. An interrupted opening finishes its flight home; a resting camera sways again. */
    release() {
      if (!held) return;
      held = false;
      controls.enabled = true;
      controls.update();
      if (!introDone) { introDone = true; moveTo(HOME, { duration: 2.4 }).then(startSway); } else if (heldSwaying) startSway();
    },
    setBeacons,
    /** 0 → 1 lights the beacons in turn; 0 hides them. */
    lightBeacons(v) { beaconMat.uniforms.uLit.value = v; beacons.visible = v > 0; },
    homeView: () => ({ ...HOME }),

    setSite,
    /** The site's state: on (flatten inside), draw (boundary 0 → 1), fill, rise (metres), dim (the city around), tint. */
    site({ on = false, draw = 0, fill = 0, rise = 0, dim = 0, tint = 0 } = {}) {
      const u = mat.building.userData.site;
      Object.assign(u, { on: on ? 1 : 0, rise, dim, tint });
      mat.building.userData.apply?.();
      site.group.visible = on;
      ribbonMat.uniforms.uDraw.value = draw;
      siteFillMat.opacity = fill;
    },
    /** All the other plans on or off (the build site draws its own). */
    showPlans(v) { groups.plans.visible = v; },
    /** Load the city around a point ahead of the camera. */
    preload(x, z) {
      const [lng, lat] = toLngLat(x, z);
      ensureAround(lng, lat, 'mid');
      ensureAround(lng, lat, 'detail');
    },
    /** Slide the picture by a fraction of its width (positive: towards the end side, the left in RTL). */
    setShift(f) {
      if (Math.abs(f - shift) < 1e-4) return;
      shift = f;
      resize();
    },

    stopDrift() { controls.autoRotate = false; sway?.kill(); },

    dispose() {
      cancelAnimationFrame(raf);
      ro.disconnect();
      pool.forEach((w) => w.terminate());
      controls.dispose();
      composer.dispose();
      renderer.dispose();
    },
  };
}

/**
 * The building material, taught about the build site: inside the site's mask the buildings are cut down to
 * uRise (metres, risen floor by floor by the drive) and tinted towards white; the city around can be dimmed.
 * Values live in material.userData.site and are pushed to the shader by userData.apply().
 */
function siteBuildings(material) {
  const u = {
    uSiteMask: { value: null }, uSiteMin: { value: new THREE.Vector2() }, uSiteSize: { value: new THREE.Vector2(1, 1) },
    uSiteOn: { value: 0 }, uRise: { value: 0 }, uDim: { value: 0 }, uTint: { value: 0 },
    uSiteColor: { value: new THREE.Color('#f2f4f1') }, uDimColor: { value: new THREE.Color('#1b2c3d') },
  };
  material.userData.site = { mask: null, min: [0, 0], size: [1, 1], on: 0, rise: 0, dim: 0, tint: 0 };
  material.userData.apply = () => {
    const s = material.userData.site;
    u.uSiteMask.value = s.mask;
    u.uSiteMin.value.set(...s.min);
    u.uSiteSize.value.set(...s.size);
    u.uSiteOn.value = s.mask ? s.on : 0;
    u.uRise.value = s.rise;
    u.uDim.value = s.dim;
    u.uTint.value = s.tint;
  };
  material.onBeforeCompile = (shader) => {
    Object.assign(shader.uniforms, u);
    shader.vertexShader = shader.vertexShader
      .replace('void main() {', `uniform sampler2D uSiteMask;
        uniform vec2 uSiteMin, uSiteSize;
        uniform float uSiteOn, uRise;
        varying float vSite;
        void main() {`)
      .replace('#include <begin_vertex>', `#include <begin_vertex>
        vSite = 0.0;
        if (uSiteOn > 0.5) {
          vec2 siteUv = ((modelMatrix * vec4(transformed, 1.0)).xz - uSiteMin) / uSiteSize;
          if (siteUv.x >= 0.0 && siteUv.y >= 0.0 && siteUv.x <= 1.0 && siteUv.y <= 1.0) vSite = step(0.5, texture2D(uSiteMask, siteUv).r);
          if (vSite > 0.5) transformed.y = min(transformed.y, uRise);
        }`);
    shader.fragmentShader = shader.fragmentShader
      .replace('void main() {', `uniform float uDim, uTint, uSiteOn;
        uniform vec3 uSiteColor, uDimColor;
        varying float vSite;
        void main() {`)
      .replace('#include <color_fragment>', `#include <color_fragment>
        diffuseColor.rgb = mix(diffuseColor.rgb, uSiteColor, vSite * uTint);
        diffuseColor.rgb = mix(diffuseColor.rgb, uDimColor, (1.0 - vSite) * uDim * uSiteOn);`);
  };
  return material;
}

/** The navy sky behind everything (was CSS; now in-scene so the glow pass has a background). */
function skyTexture() {
  const c = document.createElement('canvas');
  c.width = c.height = 512;
  const g = c.getContext('2d');
  const grd = g.createRadialGradient(110, 50, 10, 200, 160, 620);
  grd.addColorStop(0, '#4a6178');
  grd.addColorStop(0.32, '#2b3d50');
  grd.addColorStop(0.62, '#1d2a3a');
  grd.addColorStop(1, '#121922');
  g.fillStyle = grd;
  g.fillRect(0, 0, 512, 512);
  const tex = new THREE.CanvasTexture(c);
  tex.colorSpace = THREE.SRGBColorSpace;
  return tex;
}

async function tileTemplate() {
  try {
    const j = await fetch('https://tiles.openfreemap.org/planet').then((r) => r.json());
    return j.tiles[0];
  } catch {
    return 'https://tiles.openfreemap.org/planet/20261011_080001_pt/{z}/{x}/{y}.pbf';
  }
}

function mesh(pos, nor, material, renderOrder) {
  const g = new THREE.BufferGeometry();
  g.setAttribute('position', new THREE.BufferAttribute(pos, 3));
  if (nor) g.setAttribute('normal', new THREE.BufferAttribute(nor, 3));
  const m = new THREE.Mesh(g, material);
  m.renderOrder = renderOrder;
  m.frustumCulled = true;
  return m;
}

function segments(pos, material) {
  const g = new THREE.BufferGeometry();
  g.setAttribute('position', new THREE.BufferAttribute(pos, 3));
  const l = new THREE.LineSegments(g, material);
  l.renderOrder = 3;
  return l;
}

/** 1 inside the loaded tile box, easing to 0 over `feather` metres at its edges (rounded corners, no visible square). */
const BOX_FADE = /* glsl */ `
  float boxFade(vec2 p, vec4 box, float feather) {
    vec2 c = (box.xy + box.zw) * 0.5;
    vec2 q = abs(p - c) - ((box.zw - box.xy) * 0.5 - feather);
    float outside = length(max(q, 0.0)) + min(max(q.x, q.y), 0.0);
    return 1.0 - smoothstep(0.0, feather, outside);
  }`;

/** Sea: flat colour that fades out radially from Israel, so square tile edges never show. */
function seaMaterial(color, opacity, reveal) {
  return new THREE.ShaderMaterial({
    transparent: true,
    depthWrite: false,
    side: THREE.DoubleSide,
    uniforms: { uColor: { value: new THREE.Color(color) }, uOpacity: { value: opacity }, uCenter: reveal.uCenter, uReveal: reveal.uReveal, uBox: reveal.uBox },
    vertexShader: /* glsl */ `
      varying vec2 vXZ;
      void main() { vec4 wp = modelMatrix * vec4(position, 1.0); vXZ = wp.xz; gl_Position = projectionMatrix * viewMatrix * wp; }`,
    fragmentShader: /* glsl */ `
      uniform vec3 uColor; uniform float uOpacity; uniform vec2 uCenter; uniform float uReveal; uniform vec4 uBox;
      varying vec2 vXZ;
      ${BOX_FADE}
      void main() {
        float d = distance(vXZ, uCenter);
        float edge = (1.0 - smoothstep(170000.0, 330000.0, d)) * boxFade(vXZ, uBox, 110000.0);
        float reveal = 1.0 - smoothstep(uReveal * 0.8, uReveal, d);
        gl_FragColor = vec4(uColor, uOpacity * edge * reveal);
      }`,
  });
}

/** Thin lines with distance fog and an optional radial reveal (the intro "drawing" of the country). */
function lineMaterial(color, opacity, fog, reveal = null) {
  return new THREE.ShaderMaterial({
    transparent: true,
    depthWrite: false,
    uniforms: {
      uColor: { value: new THREE.Color(color) },
      uOpacity: { value: opacity },
      uReveal: reveal ? reveal.uReveal : { value: 1e9 },
      uCenter: reveal ? reveal.uCenter : { value: new THREE.Vector2() },
      uBox: reveal ? reveal.uBox : { value: new THREE.Vector4(-1e9, -1e9, 1e9, 1e9) },
      ...fog,
    },
    vertexShader: /* glsl */ `
      varying vec2 vXZ; varying float vDepth;
      void main() {
        vec4 wp = modelMatrix * vec4(position, 1.0);
        vXZ = wp.xz;
        vec4 mv = viewMatrix * wp;
        vDepth = -mv.z;
        gl_Position = projectionMatrix * mv;
      }`,
    fragmentShader: /* glsl */ `
      uniform vec3 uColor; uniform float uOpacity; uniform float uReveal; uniform vec2 uCenter;
      uniform float uFogNear; uniform float uFogFar; uniform vec4 uBox;
      varying vec2 vXZ; varying float vDepth;
      ${BOX_FADE}
      void main() {
        float reveal = 1.0 - smoothstep(uReveal * 0.82, uReveal, distance(vXZ, uCenter));
        float fog = 1.0 - smoothstep(uFogNear, uFogFar, vDepth);
        gl_FragColor = vec4(uColor, uOpacity * reveal * fog * boxFade(vXZ, uBox, 60000.0));
      }`,
  });
}
