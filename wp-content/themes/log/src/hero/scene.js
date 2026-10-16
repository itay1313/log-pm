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
import { toLocal, toLngLat, lngLatToTile, tilesInBox, ISRAEL_BOX } from './geo.js';

const C = {
  fog: '#1d2a3a',
  sea: '#2f4a63',
  building: '#ff8d3a',
  roadNational: '#9db1c6',
  roadRegional: '#b4c4d4',
  road: '#e3ebf2',
  roadMajor: '#ffffff',
  plan: '#f7931d',
};
const NATIONAL = { tx: 70000, tz: -20000, radius: 430000, phi: 0.62, theta: -0.18 };
// Home: Gush Dan, tilted, the city standing. Target nudged east so the copy (right) has room.
const [HX, HZ] = toLocal(34.795, 32.05);
const HOME = { tx: HX + 1700, tz: HZ, radius: 9800, phi: 1.07, theta: -0.42 };
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
  // Glow: the orange city blooms softly against the navy (the reference's "lit" look).
  const composer = new EffectComposer(renderer);
  composer.addPass(new RenderPass(scene, camera));
  const bloom = new UnrealBloomPass(new THREE.Vector2(1, 1), 0.62, 0.55, 0.52);
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
  controls.enableZoom = false; // armed by a click (hero.js), so the page keeps scrolling
  controls.zoomSpeed = 1.4;
  controls.rotateSpeed = 0.5;
  controls.autoRotateSpeed = -0.12;

  // Materials --------------------------------------------------------------
  const fogUniforms = { uFogNear: { value: 1e5 }, uFogFar: { value: 1e6 } };
  const revealUniforms = { uReveal: { value: reduced ? 1e9 : 0 }, uCenter: { value: new THREE.Vector2(NATIONAL.tx, NATIONAL.tz) } };
  const mat = {
    national: lineMaterial(C.roadNational, 0.42, fogUniforms, revealUniforms),
    regional: lineMaterial(C.roadRegional, 0.0, fogUniforms),
    road: lineMaterial(C.road, 0.0, fogUniforms),
    roadMajor: lineMaterial(C.roadMajor, 0.0, fogUniforms),
    rail: lineMaterial(C.road, 0.0, fogUniforms),
    sea: seaMaterial(C.sea, reduced ? 1 : 0, revealUniforms),
    building: new THREE.MeshLambertMaterial({ color: C.building, emissive: '#6a2700', side: THREE.FrontSide }),
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

  // Camera ------------------------------------------------------------------
  const view = { ...NATIONAL };
  function applyView() {
    const t = new THREE.Vector3(view.tx, 0, view.tz);
    camera.position.copy(t).add(new THREE.Vector3().setFromSpherical(new THREE.Spherical(view.radius, view.phi, view.theta)));
    controls.target.copy(t);
    camera.lookAt(t);
  }
  function readView() {
    const s = new THREE.Spherical().setFromVector3(camera.position.clone().sub(controls.target));
    Object.assign(view, { tx: controls.target.x, tz: controls.target.z, radius: s.radius, phi: s.phi, theta: s.theta });
  }
  let tween = null;
  function moveTo(to, { duration = 2.2, ease = 'power3.inOut' } = {}) {
    readView();
    tween?.kill();
    controls.enabled = false;
    controls.autoRotate = false;
    // shortest way round
    if (to.theta !== undefined) {
      let d = to.theta - view.theta;
      d = Math.atan2(Math.sin(d), Math.cos(d));
      to = { ...to, theta: view.theta + d };
    }
    return new Promise((resolve) => {
      tween = gsap.to(view, { ...to, duration: reduced ? 0 : duration, ease, onUpdate: applyView, onComplete: () => { controls.enabled = true; controls.update(); resolve(); } });
    });
  }

  // Frame loop --------------------------------------------------------------
  let running = true, raf = 0, lastLod = 0;
  const frameCallbacks = new Set();
  const smooth = (a, b, x) => { const t = Math.min(1, Math.max(0, (x - a) / (b - a))); return t * t * (3 - 2 * t); };

  function frame(now) {
    raf = requestAnimationFrame(frame);
    if (!running) return;
    if (!tween?.isActive()) controls.update();
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
    mat.planFill.opacity = 0.7 - 0.6 * smooth(60000, 14000, r); // city level: the edge marks the plan, not a flood of orange
    mat.planFillLarge.opacity = 0.14 * (1 - smooth(80000, 30000, r)); // regional plans: gone by city level
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

  function resize() {
    const w = container.clientWidth, h = container.clientHeight;
    renderer.setSize(w, h, false);
    composer.setPixelRatio(renderer.getPixelRatio());
    composer.setSize(w, h);
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
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
      if (reduced) { Object.assign(view, HOME); applyView(); return; }
      // From high above the whole country, the map drawing itself in, down into Tel Aviv.
      Object.assign(view, { ...NATIONAL, radius: 1300000, phi: 0.02, theta: NATIONAL.theta - 1.1 });
      applyView();
      gsap.to(mat.sea.uniforms.uOpacity, { value: 1, duration: 2.6, ease: 'power2.out', delay: 0.3 });
      gsap.to(revealUniforms.uReveal, { value: 750000, duration: 3.4, ease: 'power2.inOut', delay: 0.1 });
      await moveTo({ ...NATIONAL, radius: 520000 }, { duration: 2.2, ease: 'power2.inOut' });
      await moveTo(HOME, { duration: 4.2, ease: 'power3.inOut' });
      controls.autoRotate = true;
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

    home() { return moveTo(HOME, { duration: 2.4 }); },

    country() { return moveTo(NATIONAL, { duration: 2.6 }); },

    stopDrift() { controls.autoRotate = false; },

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

/** Sea: flat colour that fades out radially from Israel, so square tile edges never show. */
function seaMaterial(color, opacity, reveal) {
  return new THREE.ShaderMaterial({
    transparent: true,
    depthWrite: false,
    side: THREE.DoubleSide,
    uniforms: { uColor: { value: new THREE.Color(color) }, uOpacity: { value: opacity }, uCenter: reveal.uCenter, uReveal: reveal.uReveal },
    vertexShader: /* glsl */ `
      varying vec2 vXZ;
      void main() { vec4 wp = modelMatrix * vec4(position, 1.0); vXZ = wp.xz; gl_Position = projectionMatrix * viewMatrix * wp; }`,
    fragmentShader: /* glsl */ `
      uniform vec3 uColor; uniform float uOpacity; uniform vec2 uCenter; uniform float uReveal;
      varying vec2 vXZ;
      void main() {
        float d = distance(vXZ, uCenter);
        float edge = 1.0 - smoothstep(170000.0, 330000.0, d);
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
      uniform float uFogNear; uniform float uFogFar;
      varying vec2 vXZ; varying float vDepth;
      void main() {
        float reveal = 1.0 - smoothstep(uReveal * 0.82, uReveal, distance(vXZ, uCenter));
        float fog = 1.0 - smoothstep(uFogNear, uFogFar, vDepth);
        gl_FragColor = vec4(uColor, uOpacity * reveal * fog);
      }`,
  });
}
