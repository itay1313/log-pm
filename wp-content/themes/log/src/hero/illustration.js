/**
 * Stand-in project image until a project has a featured image: a small isometric block,
 * drawn as SVG and seeded by the project id so each plan keeps its own picture.
 *
 * The project's own building is LOG orange (the same orange as the city on the map), the
 * neighbourhood around it navy. Plans still in stages 1-2 are drawn as a ghosted outline:
 * the building is proposed, not there yet.
 */
const COS = Math.cos(Math.PI / 6);
const S = 16; // px per ground unit
const ZS = 7; // px per floor
const OX = 200, OY = 92;

const PAL = {
  context: { top: '#3d5169', left: '#2d3d51', right: '#223143', win: '#1a2533', lit: '#f6c27c' },
  project: { top: '#ffbf80', left: '#f7931d', right: '#c66812', win: '#a4520a', lit: '#fff0dc' },
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
const poly = (pts, attrs) => `<polygon points="${pts.map((p) => p.map((n) => n.toFixed(1)).join(',')).join(' ')}" ${attrs}/>`;

function box(b, rand) {
  const { x, y, w, d, h, kind, ghost } = b;
  const c = PAL[kind];
  const out = [];
  if (ghost) {
    const a = 'fill="rgba(247,147,29,.16)" stroke="#f7931d" stroke-width="1.2" stroke-linejoin="round"';
    out.push(poly([pt(x + w, y, 0), pt(x + w, y + d, 0), pt(x + w, y + d, h), pt(x + w, y, h)], a));
    out.push(poly([pt(x, y + d, 0), pt(x + w, y + d, 0), pt(x + w, y + d, h), pt(x, y + d, h)], a));
    out.push(poly([pt(x, y, h), pt(x + w, y, h), pt(x + w, y + d, h), pt(x, y + d, h)], 'fill="rgba(255,191,128,.22)" stroke="#ffbf80" stroke-width="1.2" stroke-linejoin="round"'));
    // Floor lines, like a massing study.
    for (let z = 1; z < h; z += 1) out.push(`<polyline points="${[pt(x, y + d, z), pt(x + w, y + d, z), pt(x + w, y, z)].map((p) => p.join(',')).join(' ')}" fill="none" stroke="#f7931d" stroke-opacity=".45" stroke-width=".7"/>`);
    return out.join('');
  }
  out.push(poly([pt(x + w, y, 0), pt(x + w, y + d, 0), pt(x + w, y + d, h), pt(x + w, y, h)], `fill="${c.right}"`));
  out.push(poly([pt(x, y + d, 0), pt(x + w, y + d, 0), pt(x + w, y + d, h), pt(x, y + d, h)], `fill="${c.left}"`));
  out.push(poly([pt(x, y, h), pt(x + w, y, h), pt(x + w, y + d, h), pt(x, y + d, h)], `fill="${c.top}"`));
  // Windows: one row per floor on both visible faces, a few of them lit.
  const litChance = kind === 'project' ? 0.28 : 0.12;
  const win = (corners, lit) => poly(corners, `fill="${lit ? c.lit : c.win}"${lit ? '' : ' opacity=".75"'}`);
  for (let z = 0.35; z + 0.5 < h; z += 1) {
    for (let u = 0.2; u + 0.32 <= d - 0.1; u += 0.62) {
      out.push(win([pt(x + w, y + u, z), pt(x + w, y + u + 0.32, z), pt(x + w, y + u + 0.32, z + 0.48), pt(x + w, y + u, z + 0.48)], rand() < litChance));
    }
    for (let u = 0.2; u + 0.32 <= w - 0.1; u += 0.62) {
      out.push(win([pt(x + u, y + d, z), pt(x + u + 0.32, y + d, z), pt(x + u + 0.32, y + d, z + 0.48), pt(x + u, y + d, z + 0.48)], rand() < litChance));
    }
  }
  return out.join('');
}

/** Shadow on the ground, cast away from a sun low in the west-north-west. */
function shadow(b) {
  const { x, y, w, d, h } = b;
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

/** Lay out a 10x10 block for a domain; the project building sits near the middle. */
function layout(domain, stage, rand) {
  const items = [];
  const ghost = stage <= 2;
  const ctx = (x, y, w, d, h) => items.push({ type: 'box', x, y, w, d, h, kind: 'context' });
  const proj = (x, y, w, d, h) => items.push({ type: 'box', x, y, w, d, h, kind: 'project', ghost });
  const trees = (n, x0, y0, x1, y1) => { for (let i = 0; i < n; i++) items.push({ type: 'tree', x: x0 + rand() * (x1 - x0), y: y0 + rand() * (y1 - y0), r: 0.35 + rand() * 0.25 }); };
  const grid = (hMin, hMax, skip = () => false) => {
    for (let gx = 0; gx < 10; gx += 3.4) for (let gy = 0; gy < 10; gy += 3.4) {
      if (skip(gx, gy) || rand() < 0.15) continue;
      const front = gx + gy > 9; // the near row stays low so the project reads
      ctx(gx + 0.3, gy + 0.3, 2.2 + rand() * 0.6, 2.2 + rand() * 0.6, front ? Math.min(hMin + 1, 5) : hMin + Math.round(rand() * (hMax - hMin)));
    }
  };
  const centre = (gx, gy) => gx > 2 && gx < 6 && gy > 2 && gy < 6;

  switch (domain) {
    case 'urban-renewal': // the old four-storey block in front, the new tower rising behind it
      grid(3, 5, centre);
      proj(3.7, 3.4, 2.4, 2.4, 13 + Math.round(rand() * 5));
      ctx(3.6, 6.2, 2.6, 1.4, 4);
      trees(5, 0, 6, 3, 10);
      break;
    case 'rural-arab':
      for (let i = 0; i < 14; i++) {
        const x = rand() * 9, y = rand() * 9;
        if (Math.abs(x - 4.5) < 1.6 && Math.abs(y - 4.5) < 1.6) continue;
        ctx(x, y, 1 + rand() * 0.6, 1 + rand() * 0.6, 1 + Math.round(rand() * 2));
      }
      proj(4, 4, 2, 1.7, 3);
      trees(12, 0, 0, 10, 10);
      break;
    case 'open-spaces':
      // A park with a pond, a pavilion, and the city standing along its far edges.
      for (let i = 0; i < 3; i++) ctx(0.3 + i * 3.4, 0.2, 2.6, 1.6, 5 + Math.round(rand() * 5));
      for (let i = 1; i < 3; i++) ctx(0.2, 0.3 + i * 3.4, 1.6, 2.6, 4 + Math.round(rand() * 4));
      items.push({ type: 'pond', x: 5.6, y: 6.2, r: 1.5 });
      proj(3.4, 3.6, 1.8, 1.3, 1);
      trees(18, 2.2, 2.2, 10, 10);
      break;
    case 'master-plans':
    case 'national-plans':
      grid(4, 12, centre);
      proj(3.8, 3.8, 2.2, 2.2, 14 + Math.round(rand() * 5));
      proj(6.6, 3.9, 1.6, 1.6, 10 + Math.round(rand() * 4));
      trees(4, 0, 0, 10, 10);
      break;
    default: // detailed plans: a mid-rise neighbourhood with the plan's blocks in the middle
      grid(5, 9, centre);
      proj(3.6, 3.6, 1.6, 2.6, 8 + Math.round(rand() * 3));
      proj(5.6, 3.6, 1.6, 2.6, 7 + Math.round(rand() * 3));
      trees(6, 0, 0, 10, 10);
  }
  // Painter's order: far (small x+y) first.
  return items.sort((a, b) => (a.x + (a.w || 0) / 2 + a.y + (a.d || 0) / 2) - (b.x + (b.w || 0) / 2 + b.y + (b.d || 0) / 2));
}

export function illustration(p) {
  const rand = rng(p.id);
  const stage = Math.min(4, Math.max(1, Number(p.stage) || 1));
  const items = layout(p.domain, stage, rand);
  const ground = [pt(-0.6, -0.6, 0), pt(10.6, -0.6, 0), pt(10.6, 10.6, 0), pt(-0.6, 10.6, 0)];
  const streets = [];
  for (const s of [3.25, 6.65]) {
    streets.push(`<line x1="${pt(s, -0.6, 0)[0]}" y1="${pt(s, -0.6, 0)[1]}" x2="${pt(s, 10.6, 0)[0]}" y2="${pt(s, 10.6, 0)[1]}"/>`);
    streets.push(`<line x1="${pt(-0.6, s, 0)[0]}" y1="${pt(-0.6, s, 0)[1]}" x2="${pt(10.6, s, 0)[0]}" y2="${pt(10.6, s, 0)[1]}"/>`);
  }
  const uid = `il${p.id}`;
  return `<svg class="hero-card__art" viewBox="0 0 400 250" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
    <defs>
      <radialGradient id="${uid}s" cx=".28" cy=".05" r="1.1"><stop offset="0" stop-color="#4a6178"/><stop offset=".45" stop-color="#26364a"/><stop offset="1" stop-color="#121922"/></radialGradient>
      <radialGradient id="${uid}g" cx=".5" cy=".55" r=".5"><stop offset="0" stop-color="#f7931d" stop-opacity=".22"/><stop offset="1" stop-color="#f7931d" stop-opacity="0"/></radialGradient>
    </defs>
    <rect width="400" height="250" fill="url(#${uid}s)"/>
    ${poly(ground, 'fill="#203044" stroke="#33475d" stroke-width="1"')}
    <g stroke="#9db1c6" stroke-opacity=".28" stroke-width="1.4">${streets.join('')}</g>
    <ellipse cx="200" cy="165" rx="150" ry="80" fill="url(#${uid}g)"/>
    ${items.filter((it) => it.type === 'pond').map(pond).join('')}
    ${items.filter((it) => it.type === 'box' && !it.ghost).map(shadow).join('')}
    ${items.filter((it) => it.type !== 'pond').map((it) => (it.type === 'tree' ? tree(it) : box(it, rand))).join('')}
  </svg>`;
}
