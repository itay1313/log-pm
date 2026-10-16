/**
 * Stand-in project image until a project has a featured image: a small isometric block,
 * drawn as SVG and seeded by the project id so each plan keeps its own picture.
 *
 * The project's own building is LOG orange (the same orange as the city on the map), the
 * neighbourhood around it navy. Plans still in stages 1-2 are drawn as a ghosted massing
 * study: the building is proposed, not there yet.
 *
 * Buildings are composed, not single boxes: podium + set-back tower + crown, stepped
 * terraces, L-shaped houses, slabs with roof plant. Each part has a shopfront band on the
 * ground floor, floor-slab lines, a parapet and a lit front corner.
 */
const COS = Math.cos(Math.PI / 6);
const S = 16; // px per ground unit
const ZS = 7; // px per floor
const OX = 200, OY = 92;

const PAL = {
  context: { top: '#40556e', roof: '#33465c', left: '#2d3d51', right: '#223143', win: '#1a2533', lit: '#f6c27c', slab: '#4b6079', shop: '#e9a659', rim: '#5d7590' },
  project: { top: '#ffc58c', roof: '#f0a456', left: '#f7931d', right: '#c66812', win: '#9c4d08', lit: '#fff0dc', slab: '#ffb46a', shop: '#fff3e2', rim: '#ffd9b0' },
};

function rng(seed) {
  let a = (Number(seed) || 1) * 2654435761 >>> 0;
  return () => {
    a = (a + 0x6d2b79f5) >>> 0;
    let t = a;
    t = Math.imul(t ^ (t >>> 15), t | 1);
    t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };
}

const pt = (x, y, z) => [OX + (x - y) * COS * S, OY + (x + y) * 0.5 * S - z * ZS];
const f = (p) => p.map((n) => n.toFixed(1)).join(',');
const poly = (pts, attrs) => `<polygon points="${pts.map(f).join(' ')}" ${attrs}/>`;
const line = (pts, attrs) => `<polyline points="${pts.map(f).join(' ')}" fill="none" ${attrs}/>`;

// Faces of a part: x+w side (right on screen), y+d side (left on screen), top.
const faceR = ({ x, y, w, d, z0, h }) => [pt(x + w, y, z0), pt(x + w, y + d, z0), pt(x + w, y + d, z0 + h), pt(x + w, y, z0 + h)];
const faceL = ({ x, y, w, d, z0, h }) => [pt(x, y + d, z0), pt(x + w, y + d, z0), pt(x + w, y + d, z0 + h), pt(x, y + d, z0 + h)];
const faceT = ({ x, y, w, d, z0, h }, i = 0) => [pt(x + i, y + i, z0 + h), pt(x + w - i, y + i, z0 + h), pt(x + w - i, y + d - i, z0 + h), pt(x + i, y + d - i, z0 + h)];

function ghostPart(b) {
  const a = 'fill="rgba(247,147,29,.14)" stroke="#f7931d" stroke-width="1.1" stroke-linejoin="round"';
  const out = [poly(faceR(b), a), poly(faceL(b), a), poly(faceT(b), 'fill="rgba(255,191,128,.24)" stroke="#ffc58c" stroke-width="1.1" stroke-linejoin="round"')];
  for (let z = b.z0 + 1; z < b.z0 + b.h; z += 1) out.push(line([pt(b.x, b.y + b.d, z), pt(b.x + b.w, b.y + b.d, z), pt(b.x + b.w, b.y, z)], 'stroke="#f7931d" stroke-opacity=".4" stroke-width=".6"'));
  return out.join('');
}

function part(b, rand) {
  if (b.ghost) return ghostPart(b);
  const { x, y, w, d, z0, h, kind } = b;
  const c = PAL[kind];
  const out = [poly(faceR(b), `fill="${c.right}"`), poly(faceL(b), `fill="${c.left}"`)];
  const top = z0 + h;

  // Ground floor: a continuous shopfront on taller buildings, so they stand on a street.
  let first = z0;
  if (z0 === 0 && h >= 4 && !b.roof) {
    const g = 0.12, gh = 0.85;
    out.push(poly([pt(x + w, y + 0.15, g), pt(x + w, y + d - 0.15, g), pt(x + w, y + d - 0.15, gh), pt(x + w, y + 0.15, gh)], `fill="${c.shop}" opacity=".55"`));
    out.push(poly([pt(x + 0.15, y + d, g), pt(x + w - 0.15, y + d, g), pt(x + w - 0.15, y + d, gh), pt(x + 0.15, y + d, gh)], `fill="${c.shop}" opacity=".7"`));
    first = 1;
  }

  // Windows, one row per floor; a few lit.
  const litChance = kind === 'project' ? 0.3 : 0.12;
  const win = (q, lit) => poly(q, `fill="${lit ? c.lit : c.win}"${lit ? '' : ' opacity=".7"'}`);
  if (!b.roof) {
    const ww = b.ribbon ? 0.5 : 0.3, step = b.ribbon ? 0.58 : 0.6;
    for (let z = first + 0.3; z + 0.5 <= top; z += 1) {
      for (let u = 0.18; u + ww <= d - 0.12; u += step) out.push(win([pt(x + w, y + u, z), pt(x + w, y + u + ww, z), pt(x + w, y + u + ww, z + 0.5), pt(x + w, y + u, z + 0.5)], rand() < litChance));
      for (let u = 0.18; u + ww <= w - 0.12; u += step) out.push(win([pt(x + u, y + d, z), pt(x + u + ww, y + d, z), pt(x + u + ww, y + d, z + 0.5), pt(x + u, y + d, z + 0.5)], rand() < litChance));
    }
    // Floor slabs on the project: the horizontal lines that make it read as architecture.
    if (kind === 'project') {
      for (let z = first; z < top; z += b.ribbon ? 1 : 2) out.push(line([pt(x, y + d, z), pt(x + w, y + d, z), pt(x + w, y, z)], `stroke="${c.slab}" stroke-width=".8" stroke-opacity=".8"`));
    }
  }

  // Roof: parapet ring around a darker roof deck.
  out.push(poly(faceT(b), `fill="${c.top}"`));
  if (w > 0.8 && d > 0.8 && !b.roof) out.push(poly(faceT(b, 0.12), `fill="${c.roof}"`));
  // The front corner catches the light.
  out.push(line([pt(x + w, y + d, z0), pt(x + w, y + d, top)], `stroke="${c.rim}" stroke-width=".9" stroke-opacity=".7"`));
  out.push(line([pt(x, y + d, top), pt(x + w, y + d, top), pt(x + w, y, top)], `stroke="${c.rim}" stroke-width=".8" stroke-opacity=".6"`));
  return out.join('');
}

/** Massing types. Each returns parts bottom-up, back-to-front. */
const MASSING = {
  slab(x, y, w, d, h, kind, rand) {
    const parts = [{ x, y, w, d, z0: 0, h }];
    if (h > 3 && rand() < 0.7) { // roof plant
      const pw = Math.min(0.8, w * 0.35), pd = Math.min(0.7, d * 0.3);
      parts.push({ x: x + 0.25 + rand() * (w - pw - 0.5), y: y + 0.25 + rand() * (d - pd - 0.5), w: pw, d: pd, z0: h, h: 0.8, roof: true });
    }
    return parts.map((p) => ({ ...p, kind }));
  },
  tower(x, y, w, d, h, kind, rand) {
    const pod = 2 + Math.round(rand());
    const i = Math.min(w, d) * 0.18;
    const parts = [
      { x, y, w, d, z0: 0, h: pod },
      { x: x + i, y: y + i, w: w - 2 * i, d: d - 2 * i, z0: pod, h: h - pod - 1.5, ribbon: true },
      { x: x + i * 1.8, y: y + i * 1.8, w: w - 3.6 * i, d: d - 3.6 * i, z0: h - 1.5, h: 1.5, ribbon: true }, // crown
    ];
    return parts.map((p) => ({ ...p, kind }));
  },
  stepped(x, y, w, d, h, kind) {
    // Terraces stepping down toward the viewer (+y), the high part at the back.
    const h1 = Math.max(2, Math.round(h * 0.45)), h2 = Math.max(h1 + 1, Math.round(h * 0.75));
    const parts = [
      { x, y, w, d: d * 0.4, z0: 0, h },
      { x, y: y + d * 0.4, w, d: d * 0.3, z0: 0, h: h2 },
      { x, y: y + d * 0.7, w, d: d * 0.3, z0: 0, h: h1 },
    ];
    return parts.map((p) => ({ ...p, kind }));
  },
  ell(x, y, w, d, h, kind) {
    // L-shaped house: a back wing across, a front wing down one side.
    const parts = [
      { x, y, w, d: d * 0.5, z0: 0, h },
      { x: x + w * 0.5, y: y + d * 0.5, w: w * 0.5, d: d * 0.5, z0: 0, h: Math.max(1, h - 1) },
    ];
    return parts.map((p) => ({ ...p, kind }));
  },
  pavilion(x, y, w, d, h, kind) {
    // Low glass hall under an overhanging roof plate.
    return [
      { x: x + 0.2, y: y + 0.2, w: w - 0.4, d: d - 0.4, z0: 0, h: 1.2, ribbon: true, kind },
      { x, y, w, d, z0: 1.2, h: 0.3, roof: true, kind },
    ];
  },
};

/** One shadow per building, from its tallest part, cast away from a low west-north-west sun. */
function shadow({ x, y, w, d, h }) {
  const sx = h * 0.22, sy = h * 0.08;
  return poly([pt(x + w, y, 0), pt(x + w + sx, y + sy, 0), pt(x + w + sx, y + d + sy, 0), pt(x + sx, y + d + sy, 0), pt(x, y + d, 0), pt(x + w, y + d, 0)], 'fill="#0d141c" opacity=".45"');
}

function pond(o) {
  const [cx, cy] = pt(o.x, o.y, 0);
  return `<ellipse cx="${cx.toFixed(1)}" cy="${cy.toFixed(1)}" rx="${(o.r * S * 1.25).toFixed(1)}" ry="${(o.r * S * 0.62).toFixed(1)}" fill="#2f4a63" stroke="#4a6a86" stroke-width="1"/>`;
}

function tree(t) {
  const [cx, cy] = pt(t.x, t.y, 0);
  const r = t.r * S;
  return `<ellipse cx="${cx.toFixed(1)}" cy="${(cy + 1).toFixed(1)}" rx="${(r * 1.1).toFixed(1)}" ry="${(r * 0.45).toFixed(1)}" fill="#111a24" opacity=".5"/>`
    + `<circle cx="${cx.toFixed(1)}" cy="${(cy - r * 0.9).toFixed(1)}" r="${r.toFixed(1)}" fill="#3e5a52"/>`
    + `<circle cx="${(cx - r * 0.3).toFixed(1)}" cy="${(cy - r * 1.15).toFixed(1)}" r="${(r * 0.5).toFixed(1)}" fill="#557466"/>`;
}

/** Lay out a 10x10 block for a domain; the project sits near the middle. */
function layout(domain, stage, rand) {
  const items = [];
  const ghost = stage <= 2;
  const add = (type, x, y, w, d, h, kind) => items.push({ type: 'building', x, y, w, d, h, kind, ghost: kind === 'project' && ghost, parts: MASSING[type](x, y, w, d, h, kind, rand) });
  const ctx = (x, y, w, d, h, type) => {
    if (!type) { const r = rand(); type = h >= 7 && r < 0.3 ? 'tower' : h >= 4 && r < 0.55 ? 'stepped' : 'slab'; }
    add(type, x, y, w, d, h, 'context');
  };
  const proj = (type, x, y, w, d, h) => add(type, x, y, w, d, h, 'project');
  const trees = (n, x0, y0, x1, y1) => { for (let i = 0; i < n; i++) items.push({ type: 'tree', x: x0 + rand() * (x1 - x0), y: y0 + rand() * (y1 - y0), r: 0.35 + rand() * 0.25 }); };
  const grid = (hMin, hMax, skip = () => false) => {
    for (let gx = 0; gx < 10; gx += 3.4) for (let gy = 0; gy < 10; gy += 3.4) {
      if (skip(gx, gy) || rand() < 0.15) continue;
      const front = gx + gy > 9; // the near row stays low so the project reads
      ctx(gx + 0.3, gy + 0.3, 2.2 + rand() * 0.6, 2.2 + rand() * 0.6, front ? Math.min(hMin + 1, 5) : hMin + Math.round(rand() * (hMax - hMin)), front ? 'slab' : null);
    }
  };
  const centre = (gx, gy) => gx > 2 && gx < 6 && gy > 2 && gy < 6;

  switch (domain) {
    case 'urban-renewal': // the old four-storey block in front, the new tower rising behind it
      grid(3, 5, centre);
      proj('tower', 3.7, 3.4, 2.4, 2.4, 14 + Math.round(rand() * 5));
      ctx(3.6, 6.2, 2.6, 1.4, 4, 'slab');
      trees(5, 0, 6, 3, 10);
      break;
    case 'rural-arab':
      for (let i = 0; i < 14; i++) {
        const x = rand() * 9, y = rand() * 9;
        if (Math.abs(x - 4.5) < 1.6 && Math.abs(y - 4.5) < 1.6) continue;
        ctx(x, y, 1.1 + rand() * 0.6, 1.1 + rand() * 0.6, 1 + Math.round(rand() * 2), rand() < 0.5 ? 'ell' : 'slab');
      }
      proj('ell', 3.9, 3.9, 2.2, 2, 3);
      trees(12, 0, 0, 10, 10);
      break;
    case 'open-spaces':
      // A park with a pond, a pavilion, and the city standing along its far edges.
      for (let i = 0; i < 3; i++) ctx(0.3 + i * 3.4, 0.2, 2.6, 1.6, 5 + Math.round(rand() * 5));
      for (let i = 1; i < 3; i++) ctx(0.2, 0.3 + i * 3.4, 1.6, 2.6, 4 + Math.round(rand() * 4));
      items.push({ type: 'pond', x: 5.6, y: 6.2, r: 1.5 });
      proj('pavilion', 3.3, 3.5, 2, 1.5, 1.5);
      trees(18, 2.2, 2.2, 10, 10);
      break;
    case 'master-plans':
    case 'national-plans':
      grid(4, 12, centre);
      proj('tower', 3.8, 3.8, 2.2, 2.2, 15 + Math.round(rand() * 4));
      proj('stepped', 6.5, 3.9, 1.7, 1.8, 9 + Math.round(rand() * 3));
      trees(4, 0, 0, 10, 10);
      break;
    default: // detailed plans: a mid-rise neighbourhood with the plan's terraced blocks in the middle
      grid(5, 9, centre);
      proj('stepped', 3.6, 3.5, 1.7, 2.8, 9 + Math.round(rand() * 2));
      proj('slab', 5.7, 3.8, 1.5, 2.4, 6 + Math.round(rand() * 2));
      trees(6, 0, 0, 10, 10);
  }
  // Painter's order: far (small x+y) first.
  const depth = (a) => a.x + (a.w || 0) / 2 + a.y + (a.d || 0) / 2;
  return items.sort((a, b) => depth(a) - depth(b));
}

/** @param {{id, domain, stage}} p  @param {string} cls class for the <svg> */
export function illustration(p, cls = 'hero-card__art') {
  const rand = rng(p.id);
  const stage = Math.min(4, Math.max(1, Number(p.stage) || 1));
  const items = layout(p.domain, stage, rand);
  const ground = [pt(-0.6, -0.6, 0), pt(10.6, -0.6, 0), pt(10.6, 10.6, 0), pt(-0.6, 10.6, 0)];
  const streets = [];
  for (const s of [3.25, 6.65]) {
    streets.push(line([pt(s, -0.6, 0), pt(s, 10.6, 0)], ''));
    streets.push(line([pt(-0.6, s, 0), pt(10.6, s, 0)], ''));
  }
  const uid = `il${p.id}`;
  const draw = (it) => {
    if (it.type === 'tree') return tree(it);
    return it.parts.map((q) => part({ ...q, ghost: it.ghost }, rand)).join('');
  };
  return `<svg class="${cls}" viewBox="0 0 400 250" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
    <defs>
      <radialGradient id="${uid}s" cx=".28" cy=".05" r="1.1"><stop offset="0" stop-color="#4a6178"/><stop offset=".45" stop-color="#26364a"/><stop offset="1" stop-color="#121922"/></radialGradient>
      <radialGradient id="${uid}g" cx=".5" cy=".55" r=".5"><stop offset="0" stop-color="#f7931d" stop-opacity=".22"/><stop offset="1" stop-color="#f7931d" stop-opacity="0"/></radialGradient>
    </defs>
    <rect width="400" height="250" fill="url(#${uid}s)"/>
    ${poly(ground, 'fill="#203044" stroke="#33475d" stroke-width="1"')}
    <g stroke="#9db1c6" stroke-opacity=".28" stroke-width="1.4">${streets.join('')}</g>
    <ellipse cx="200" cy="165" rx="150" ry="80" fill="url(#${uid}g)"/>
    ${items.filter((it) => it.type === 'pond').map(pond).join('')}
    ${items.filter((it) => it.type === 'building' && !it.ghost).map(shadow).join('')}
    ${items.filter((it) => it.type !== 'pond').map(draw).join('')}
  </svg>`;
}
