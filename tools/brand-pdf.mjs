#!/usr/bin/env node
/**
 * Prints the web brand book (/brand) to a PDF: one 1280×720 sheet per PDF page, the same pages people read online.
 *
 * Drives a local Chrome over the DevTools protocol, so it needs no npm package: it opens the book, unlocks it with
 * the page password, forces reduced motion (every line drawn, every dot landed), waits for the webfonts, and prints
 * at the CSS page size (brand.css: @page 1280px 720px, one .bk-page per page).
 *
 *   LOG_BRAND_PASSWORD=… node tools/brand-pdf.mjs [url] [out.pdf]
 *
 * url defaults to the local Playground (tools/dev.sh), out to docs/LOG-brand-v4.pdf, which git ignores: the PDF is
 * the client's document and the repo is public. The password is the brand page's, from WordPress (Pages → brand →
 * Visibility); it is never written anywhere. CHROME=/path/to/chrome picks another browser.
 */
import { spawn } from 'node:child_process';
import { mkdtempSync, mkdirSync, rmSync, writeFileSync, existsSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const url = process.argv[2] || 'http://127.0.0.1:8881/brand/';
const out = resolve(process.argv[3] || join(root, 'docs/LOG-brand-v4.pdf'));
const password = process.env.LOG_BRAND_PASSWORD || '';
const chrome = process.env.CHROME || [
  '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  '/Applications/Chromium.app/Contents/MacOS/Chromium',
  '/usr/bin/google-chrome',
  '/usr/bin/chromium',
].find(existsSync);

const fail = (msg) => { console.error(`brand-pdf: ${msg}`); process.exit(1); };
if (!chrome) fail('no Chrome found; set CHROME=/path/to/chrome');

const profile = mkdtempSync(join(tmpdir(), 'brand-pdf-'));
const proc = spawn(chrome, [
  '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
  '--remote-debugging-port=0', `--user-data-dir=${profile}`, 'about:blank',
], { stdio: ['ignore', 'ignore', 'pipe'] });

// Chrome keeps writing to its profile until it has exited, so the folder goes only after that.
const exited = new Promise((ok) => proc.on('exit', ok));
const removeProfile = () => { try { rmSync(profile, { recursive: true, force: true, maxRetries: 5, retryDelay: 100 }); } catch { /* the OS cleans tmp */ } };
process.on('exit', () => { try { proc.kill(); } catch { /* gone */ } });

// Chrome prints its DevTools address on stderr once it is up.
const wsUrl = await new Promise((ok, no) => {
  let buf = '';
  const t = setTimeout(() => no(new Error('Chrome did not start within 20s')), 20000);
  proc.stderr.on('data', (d) => {
    buf += d;
    const m = buf.match(/DevTools listening on (ws:\/\/\S+)/);
    if (m) { clearTimeout(t); ok(m[1]); }
  });
}).catch((e) => fail(e.message));

// A minimal DevTools client: send() resolves with the result, once() waits for an event.
const ws = new WebSocket(wsUrl);
await new Promise((ok) => ws.addEventListener('open', ok, { once: true }));
let seq = 0;
const pending = new Map();
const waiters = [];
ws.addEventListener('message', ({ data }) => {
  const msg = JSON.parse(data);
  if (msg.id && pending.has(msg.id)) {
    const { ok, no } = pending.get(msg.id);
    pending.delete(msg.id);
    msg.error ? no(new Error(msg.error.message)) : ok(msg.result);
  } else if (msg.method) {
    for (const w of waiters.filter((w) => w.method === msg.method && w.sessionId === msg.sessionId)) {
      waiters.splice(waiters.indexOf(w), 1);
      w.ok(msg.params);
    }
  }
});
const send = (method, params = {}, sessionId) => new Promise((ok, no) => {
  const id = ++seq;
  pending.set(id, { ok, no });
  ws.send(JSON.stringify({ id, method, params, ...(sessionId ? { sessionId } : {}) }));
});
const once = (method, sessionId, ms = 30000) => new Promise((ok, no) => {
  const w = { method, sessionId, ok };
  waiters.push(w);
  setTimeout(() => { if (waiters.includes(w)) { waiters.splice(waiters.indexOf(w), 1); no(new Error(`timed out waiting for ${method}`)); } }, ms);
});

const { targetId } = await send('Target.createTarget', { url: 'about:blank' });
const { sessionId } = await send('Target.attachToTarget', { targetId, flatten: true });
const page = (method, params) => send(method, params, sessionId);
const evaluate = async (expression) => (await page('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true })).result.value;
const goto = async (to) => { const loaded = once('Page.loadEventFired', sessionId); await page('Page.navigate', { url: to }); await loaded; };

await page('Page.enable');
await page('Runtime.enable');
// The finished state: no line waiting to draw, no dot waiting to land.
await page('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
await page('Emulation.setDeviceMetricsOverride', { width: 1328, height: 800, deviceScaleFactor: 1, mobile: false });

try {
  await goto(url);
} catch (e) {
  fail(`couldn't open ${url} (${e.message}); is the site running?`);
}

if (await evaluate('!!document.querySelector(".bk-lock")')) {
  if (!password) fail('the book is locked: set LOG_BRAND_PASSWORD to the brand page password');
  await evaluate(`(() => { const f = document.querySelector('.bk-lock form'); f.post_password.value = ${JSON.stringify(password)}; setTimeout(() => f.submit(), 0); })()`);
  // The form posts and WordPress redirects back; wait for the book itself rather than for one load event.
  let open = false;
  for (let i = 0; i < 180 && !open; i++) {
    await new Promise((ok) => setTimeout(ok, 500));
    open = await evaluate('document.readyState === "complete" && document.querySelectorAll(".bk-page").length > 1 && !document.querySelector(".bk-lock")').catch(() => false);
  }
  if (!open) fail('the password was not accepted, or the book took more than 90s to load');
}

// Photographs load lazily on screen; a print needs every one of them decoded first.
const missing = await evaluate(`(async () => {
  const imgs = [...document.images];
  imgs.forEach((img) => { img.loading = 'eager'; });
  await Promise.all(imgs.map((img) => img.decode().catch(() => {})));
  return imgs.filter((img) => !img.naturalWidth).length;
})()`);
if (missing) console.warn(`brand-pdf: ${missing} image(s) didn't load; their frames print empty`);
const sheets = await evaluate('document.fonts.ready.then(() => document.querySelectorAll(".bk-page").length)');
const fonts = await evaluate('[...document.fonts].filter((f) => f.status === "loaded").map((f) => f.family.replace(/"/g, "") + " " + f.weight)');
if (!sheets) fail('no .bk-page sheets on the page');

const { data } = await page('Page.printToPDF', {
  preferCSSPageSize: true,
  printBackground: true,
  displayHeaderFooter: false,
  marginTop: 0, marginBottom: 0, marginLeft: 0, marginRight: 0,
});
const pdf = Buffer.from(data, 'base64');
mkdirSync(dirname(out), { recursive: true });
writeFileSync(out, pdf);

const pages = (pdf.toString('latin1').match(/\/Type\s*\/Page(?!s)/g) || []).length;
console.log(`brand-pdf: ${out}`);
console.log(`brand-pdf: ${pages} PDF pages from ${sheets} sheets, ${(pdf.length / 1024 / 1024).toFixed(1)} MB`);
console.log(`brand-pdf: fonts loaded: ${[...new Set(fonts)].join(', ') || 'none'}`);
await send('Browser.close').catch(() => {});
await Promise.race([exited, new Promise((ok) => setTimeout(ok, 5000))]);
removeProfile();
if (pages !== sheets) fail(`page count ${pages} doesn't match ${sheets} sheets: a sheet broke across pages`);
process.exit(0);
