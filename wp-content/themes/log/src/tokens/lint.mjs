#!/usr/bin/env node
/**
 * Colour lint. Every colour on the site comes from src/tokens/tokens.json: CSS reads var(--…) and mixes them with
 * color-mix(), JS reads tokens.js (color, alpha, mix). This fails the build when a raw colour (#hex, rgb()/rgba(),
 * 0xrrggbb) appears anywhere else in src/, so the palette can't drift one literal at a time.
 *
 * Allowed: lines that draw a mask (only black and white matter there), and lines marked `colour-ok: <reason>`,
 * for light that isn't paint (a three.js sun's temperature).
 */
import { readFileSync, readdirSync, statSync } from 'node:fs';
import { dirname, join, relative, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const theme = resolve(here, '../..');
const src = join(theme, 'src');
const skip = new Set([join(src, 'tokens'), join(src, 'styles/tokens.gen.css')]);

const walk = (dir) => readdirSync(dir).flatMap((name) => {
  const path = join(dir, name);
  if (skip.has(path)) return [];
  return statSync(path).isDirectory() ? walk(path) : /\.(css|js)$/.test(name) ? [path] : [];
});

// A hex colour as a value: after a quote, colon, space, comma or bracket, never a CSS #id in a selector.
const raw = /(?:^|['"`(:,\s])(#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8}))\b(?![\w-]*\s*[{,]\s*$)|\brgba?\(\s*\d|\b0x[0-9a-f]{6}\b/i;

let found = 0;
for (const file of walk(src)) {
  readFileSync(file, 'utf8').split('\n').forEach((line, i) => {
    if (/colour-ok:|mask/i.test(line)) return;
    const hit = line.match(raw);
    if (!hit) return;
    found++;
    console.error(`colours: ${relative(theme, file)}:${i + 1}  ${(hit[1] || hit[0]).trim()}  → use a token (tokens.json)`);
  });
}
if (found) console.error(`colours: ${found} raw colour${found > 1 ? 's' : ''} outside tokens.json`);
process.exit(found ? 1 : 0);
