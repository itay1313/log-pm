#!/usr/bin/env node
/**
 * Draws the brand book's downloadable grounds: the city in lines on the night ground, as JPGs.
 *
 * The six places are set in log_bk_backgrounds() (wp-content/themes/log/inc/brand-book.php). This runs that PHP as
 * it is (php-wasm, so no PHP install is needed), lays each drawing on the night ground with the theme's own tokens
 * and city styles, and photographs it in a local Chrome over the DevTools protocol:
 *
 *   assets/brand/backgrounds/log-background-<slug>.jpg     3840×2160, what people download
 *   assets/brand/backgrounds/log-background-<slug>-s.jpg   480×270, the thumbnail on the chapter's opening sheet
 *
 *   node tools/brand-backgrounds.mjs [slug …]      (run `npm run build` in the theme first: it writes tokens.css)
 *
 * The pictures are committed: they are LOG's own drawings, not client material. CHROME=/path/to/chrome picks
 * another browser.
 */
import { spawn, execFileSync } from 'node:child_process';
import { mkdtempSync, mkdirSync, readFileSync, rmSync, writeFileSync, existsSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const theme = join(root, 'wp-content/themes/log');
const out = join(theme, 'assets/brand/backgrounds');
const only = process.argv.slice(2);
const chrome = process.env.CHROME || [
  '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  '/Applications/Chromium.app/Contents/MacOS/Chromium',
  '/usr/bin/google-chrome',
  '/usr/bin/chromium',
].find(existsSync);
const fail = (msg) => { console.error(`brand-backgrounds: ${msg}`); process.exit(1); };
if (!chrome) fail('no Chrome found; set CHROME=/path/to/chrome');

const work = mkdtempSync(join(tmpdir(), 'brand-bg-'));

// The drawings, from the theme's own PHP (tools/brand-backgrounds.php stands in for WordPress).
const drawn = JSON.parse(execFileSync('npx', ['--yes', '@php-wasm/cli', join(root, 'tools/brand-backgrounds.php'), theme], { encoding: 'utf8', maxBuffer: 64 << 20 }));
const grounds = drawn.filter((g) => !only.length || only.includes(g.slug));
if (!grounds.length) fail(`no such ground: ${only.join(', ')}`);

const tokens = join(theme, 'assets/brand/tokens.css');
if (!existsSync(tokens)) fail('assets/brand/tokens.css is missing: run `npm run build` in the theme first');
// The sheet's own rules for a city used as a ground (brand.css, .bk-opener__city): dark walls, the drawing stepped back.
// A thumbnail is a quarter of the width: its lines are drawn heavier and at full strength, or they wash out.
const page = (svg, small = false) => `<!doctype html><meta charset="utf-8"><style>${readFileSync(tokens, 'utf8')}${readFileSync(join(theme, 'src/styles/city.css'), 'utf8')}
html, body { margin: 0; height: 100%; background: var(--night); overflow: hidden; }
.cty { position: fixed; inset: 0; width: 100%; height: 100%; --cty-wall: var(--night-deep); opacity: .72; }
${small ? '.cty { opacity: 1; } .cty .w, .cty .r { stroke-width: 2.8; } .cty__roads { stroke-width: 2; } .cty__bound { stroke-width: 6; }' : ''}
</style>${svg}`;

const profile = join(work, 'profile');
const proc = spawn(chrome, [
  '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--hide-scrollbars',
  '--remote-debugging-port=0', `--user-data-dir=${profile}`, 'about:blank',
], { stdio: ['ignore', 'ignore', 'pipe'] });
const exited = new Promise((ok) => proc.on('exit', ok));
const sleep = (ms) => new Promise((ok) => setTimeout(ok, ms));

try {
  const wsUrl = await new Promise((ok, no) => {
    let buf = '';
    proc.stderr.on('data', (d) => { buf += d; const m = buf.match(/DevTools listening on (ws:\/\/\S+)/); if (m) ok(m[1]); });
    setTimeout(() => no(new Error('Chrome did not start')), 20000);
  });
  const ws = new WebSocket(wsUrl);
  await new Promise((ok) => ws.addEventListener('open', ok, { once: true }));
  let seq = 0;
  const pending = new Map();
  ws.addEventListener('message', ({ data }) => {
    const m = JSON.parse(data);
    if (!m.id || !pending.has(m.id)) return;
    const { ok, no } = pending.get(m.id);
    pending.delete(m.id);
    m.error ? no(new Error(m.error.message)) : ok(m.result);
  });
  const send = (method, params = {}, sessionId) => new Promise((ok, no) => {
    const id = ++seq;
    pending.set(id, { ok, no });
    ws.send(JSON.stringify({ id, method, params, ...(sessionId ? { sessionId } : {}) }));
  });
  const { targetId } = await send('Target.createTarget', { url: 'about:blank' });
  const { sessionId } = await send('Target.attachToTarget', { targetId, flatten: true });
  const tab = (method, params) => send(method, params, sessionId);
  await tab('Page.enable');
  const { frameTree } = await tab('Page.getFrameTree');

  mkdirSync(out, { recursive: true });
  for (const { slug, name, svg } of grounds) {
    // [ file suffix, CSS width, CSS height, device pixels per CSS pixel, JPEG quality ]
    for (const [suffix, width, height, scale, quality] of [['', 1920, 1080, 2, 84], ['-s', 240, 135, 2, 84]]) {
      await tab('Page.setDocumentContent', { frameId: frameTree.frame.id, html: page(svg, suffix !== '') });
      await tab('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: scale, mobile: false });
      await sleep(250);
      const { data } = await tab('Page.captureScreenshot', { format: 'jpeg', quality });
      const file = join(out, `log-background-${slug}${suffix}.jpg`);
      writeFileSync(file, Buffer.from(data, 'base64'));
      console.log(`${name}: ${file.slice(root.length + 1)} (${width * scale}×${height * scale}, ${Math.round(Buffer.byteLength(data, 'base64') / 1024)} KB)`);
    }
  }
  await send('Browser.close').catch(() => {});
} finally {
  proc.kill();
  await Promise.race([exited, sleep(3000)]);
  rmSync(work, { recursive: true, force: true });
}
