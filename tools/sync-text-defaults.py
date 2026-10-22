#!/usr/bin/env python3
"""
Copies the fallback copy that templates pass to log_text() / log_rows() into the ACF field groups,
as "default_value" (text fields) and "log_default_rows" (repeaters). The editor then opens a page and
sees the text that is live on the site, instead of an empty field with a grey hint.

Templates stay the source of the copy: rerun this after changing a fallback in a template.
Edits the acf-json files line by line, so their hand formatting survives.

    python3 tools/sync-text-defaults.py               # theme next to this script
    python3 tools/sync-text-defaults.py <theme-dir>   # read templates from another checkout
    python3 tools/sync-text-defaults.py --check       # exit 1 if the json is out of date (CI)
"""

import glob
import json
import os
import re
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
ACF_DIR = os.path.join(ROOT, 'wp-content/plugins/log-core/acf-json')

# Template → the field group its page-level (non-"option") texts live in.
PAGE_GROUPS = {
    'front-page.php': 'group_log_page_home',
    'page-about.php': 'group_log_page_about',
    'page-control.php': 'group_log_page_control',
    'page-contact.php': 'group_log_page_contact',
}
OPTION_GROUPS = ['group_log_texts', 'group_log_settings']

PHP_STR = r"""'(?:[^'\\]|\\.)*'|"(?:[^"\\]|\\.)*\""""


def php_string(lit: str) -> str:
    body = lit[1:-1]
    if lit[0] == "'":
        return re.sub(r"\\(['\\])", r'\1', body)
    return re.sub(r'\\(.)', lambda m: {'n': '\n', 't': '\t'}.get(m.group(1), m.group(1)), body)


def balanced(src: str, start: int) -> str:
    """The [...] literal that opens at src[start]."""
    depth, i, quote = 0, start, None
    while i < len(src):
        c = src[i]
        if quote:
            if c == '\\':
                i += 1
            elif c == quote:
                quote = None
        elif c in '\'"':
            quote = c
        elif c == '[':
            depth += 1
        elif c == ']':
            depth -= 1
            if depth == 0:
                return src[start:i + 1]
        i += 1
    return ''


def collect(theme: str):
    texts, rows = {}, {}  # (group|None for options, name) → value
    files = glob.glob(os.path.join(theme, '*.php')) + glob.glob(os.path.join(theme, 'template-parts/*.php'))
    for path in sorted(files):
        src = open(path, encoding='utf-8').read()
        base = os.path.basename(path)
        for m in re.finditer(r"log_text\(\s*'([a-z0-9_]+)'\s*,\s*(%s)\s*(,\s*'option')?" % PHP_STR, src):
            group = None if m.group(3) else PAGE_GROUPS.get(base)
            if group is None and not m.group(3):
                continue
            add(texts, (group, m.group(1)), php_string(m.group(2)), base)
        for m in re.finditer(r"log_rows\(\s*'([a-z0-9_]+)'\s*,\s*\[", src):
            literal = balanced(src, m.end() - 1)
            parsed = []
            for row in re.finditer(r"\[((?:\s*'[a-z0-9_]+'\s*=>\s*(?:%s)\s*,?)+)\s*\]" % PHP_STR, literal[1:-1]):
                parsed.append({k: php_string(v) for k, v in re.findall(r"'([a-z0-9_]+)'\s*=>\s*(%s)" % PHP_STR, row.group(1))})
            if parsed:
                scope = "'option'" in src[m.end() - 1 + len(literal):][:20]
                add(rows, (None if scope else PAGE_GROUPS.get(base), m.group(1)), parsed, base)
    return texts, rows


def add(store, key, value, where):
    if key in store and store[key][0] != value:
        print(f'! {key[1]}: different fallback in {where} and {store[key][1]}, keeping {store[key][1]}', file=sys.stderr)
        return
    store.setdefault(key, (value, where))


def apply(texts, rows, check: bool) -> int:
    stale = 0
    for path in sorted(glob.glob(os.path.join(ACF_DIR, '*.json'))):
        group = os.path.basename(path)[:-5]
        scope = group if group in PAGE_GROUPS.values() else (None if group in OPTION_GROUPS else False)
        if scope is False:
            continue
        lines = open(path, encoding='utf-8').read().split('\n')
        out, changed = [], False
        for line in lines:
            m = re.search(r'"name": "([a-z0-9_]+)", "type": "(text|textarea|repeater)"', line)
            # Top-level fields only: sub fields sit deeper than two levels of indent.
            if m and len(line) - len(line.lstrip()) <= 4:
                name, kind = m.groups()
                if kind == 'repeater' and (scope, name) in rows:
                    line = set_key(line, 'log_default_rows', rows[(scope, name)][0], before='"sub_fields"')
                elif kind != 'repeater' and (scope, name) in texts:
                    line = set_key(re.sub(r', "placeholder": (%s)' % r'"(?:[^"\\]|\\.)*"', '', line), 'default_value', texts[(scope, name)][0])
            changed |= line != lines[len(out)]
            out.append(line)
        if changed:
            stale += 1
            print(('stale ' if check else 'updated ') + os.path.relpath(path, ROOT))
            if not check:
                open(path, 'w', encoding='utf-8').write('\n'.join(out))
                json.load(open(path, encoding='utf-8'))  # still valid json
    return stale


def set_key(line: str, key: str, value, before: str = None) -> str:
    encoded = json.dumps(value, ensure_ascii=False, separators=(', ', ': '))
    line = re.sub(r', "%s": (?:"(?:[^"\\]|\\.)*"|\[.*?\}\])' % key, '', line)
    if before and before in line:
        return line.replace(before, f'"{key}": {encoded}, {before}', 1)
    return re.sub(r' \}(,?)$', lambda m: f', "{key}": {encoded} }}{m.group(1)}', line, count=1)


if __name__ == '__main__':
    args = [a for a in sys.argv[1:] if a != '--check']
    theme = args[0] if args else os.path.join(ROOT, 'wp-content/themes/log')
    texts, rows = collect(theme)
    print(f'{len(texts)} texts, {len(rows)} lists found in {os.path.relpath(theme, ROOT) if theme.startswith(ROOT) else theme}')
    stale = apply(texts, rows, '--check' in sys.argv)
    sys.exit(1 if stale and '--check' in sys.argv else 0)
