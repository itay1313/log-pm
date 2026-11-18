#!/usr/bin/env python3
"""
LOG's wordmark, drawn from its construction. Writes:
  wp-content/themes/log/assets/brand/logo.svg     the wordmark (q1-q4 grouped, colour from CSS vars)
  wp-content/themes/log/assets/brand/favicon.svg  the O alone

A stand-in until LOG sends the master file. The numbers come from fitting circles and edges to the
1011x383 logo image inside the v3 brand book (max edge residual 0.3 px). The book's image agrees with
this construction to under 1 px everywhere; it drifts in two places, which this file does not copy:
the O's horizontal cut is 58.8 units there (60 here) and the G's top bar sits 0.7 units high.

The construction, in logo units:
  - one stroke, s = 60. Every ring is R 190 outside, r 130 inside.
  - a horizontal road one stroke wide runs through all three rings at their centre line.
    The O also has a vertical road, so it falls into four quarters: a roundabout seen from above.
  - each ring starts where the one before it leaves the road (letter spacing = ao + ai), so the
    O's top-right quarter and the G's arm share one cut end, and the L's bowl and the O share another.
    That shared end is where the colour switches from grey to orange.
  - the G's two bars stop where its outer circle meets the road. The crossbar starts 51.4 right of
    the G's centre.

Run: python3 tools/logo-construct.py
"""
import math
from pathlib import Path

BRAND = Path(__file__).resolve().parent.parent / 'wp-content/themes/log/assets/brand'

s = 60.0          # stroke
R = 190.0         # outer radius
r = R - s         # inner radius
h = s / 2         # half a road
gx = 51.4         # G crossbar start, from the G's centre
ao = math.sqrt(R * R - h * h)  # where the outer circle meets a road edge
ai = math.sqrt(r * r - h * h)  # where the inner circle meets a road edge
d = ao + ai                    # letter spacing
cy = R
cL, cO = R, R + d
cG = cO + d
GR = cG + ao                   # right edge of the G's bars
W, H = GR, 2 * R
yT, yB = cy - h, cy + h        # the road's edges


def n(v):
    t = ('%.3f' % round(v, 3)).rstrip('0').rstrip('.')
    return '0' if t == '-0' else t


def P(*a):
    return ' '.join(n(x) if isinstance(x, (int, float)) else x for x in a)


paths = {
    # The O's quarters, numbered like the plane's quadrants: q1 top-right, then counter-clockwise.
    'q1': P('M', cO + h, cy - ao, 'A', R, R, 0, 0, 1, cO + ao, yT, 'H', cO + ai, 'A', r, r, 0, 0, 0, cO + h, cy - ai, 'Z'),
    'q2': P('M', cO - h, cy - ao, 'V', cy - ai, 'A', r, r, 0, 0, 0, cO - ai, yT, 'H', cO - ao, 'A', R, R, 0, 0, 1, cO - h, cy - ao, 'Z'),
    'q3': P('M', cO - ao, yB, 'H', cO - ai, 'A', r, r, 0, 0, 0, cO - h, cy + ai, 'V', cy + ao, 'A', R, R, 0, 0, 1, cO - ao, yB, 'Z'),
    'q4': P('M', cO + h, cy + ai, 'A', r, r, 0, 0, 0, cO + ai, yB, 'H', cO + ao, 'A', R, R, 0, 0, 1, cO + h, cy + ao, 'Z'),
    'l-stem': P('M', 0, 0, 'H', s, 'V', yT, 'H', 0, 'Z'),
    'l-bowl': P('M', cL - ao, yB, 'H', cL - ai, 'A', r, r, 0, 0, 0, cL + ai, yB, 'H', cL + ao, 'A', R, R, 0, 0, 1, cL - ao, yB, 'Z'),
    'g-arm': P('M', cG - ao, yT, 'A', R, R, 0, 0, 1, cG, 0, 'H', GR, 'V', s, 'H', cG, 'A', r, r, 0, 0, 0, cG - ai, yT, 'Z'),
    # The bowl's right end tucks one unit under the crossbar, so no hairline shows between them.
    'g-bowl': P('M', cG - ao, yB, 'H', cG - ai, 'A', r, r, 0, 0, 0, cG + ai, yB, 'V', yB - 1, 'H', GR, 'V', yB, 'A', R, R, 0, 0, 1, cG - ao, yB, 'Z'),
    'g-bar': P('M', cG + gx, yT, 'H', GR, 'V', yB, 'H', cG + gx, 'Z'),
}

# Paint order matters where strokes cross: the L's bowl over q3, q1 over the G's arm, the G's bowl over q4.
# Third field: which gradient an orange piece takes (v: light at the top, d: deep top-left, h: deep left).
ORDER = [('q3', 'd'), ('l-bowl', None), ('l-stem', 'v'), ('q2', None), ('g-arm', None),
         ('q1', 'd'), ('q4', None), ('g-bowl', 'h'), ('g-bar', None)]

GREY = 'fill:var(--logo-a,#77787b)'
LIGHT = '--logo-b,#f7931d'
DEEP = '--logo-b-deep,#f67b1c'


def gradient(gid, x2, y2, first, last):
    return (f'<linearGradient id="logo_{gid}" x1="0" y1="0" x2="{x2}" y2="{y2}">'
            f'<stop offset="0" style="stop-color:var({first})"/>'
            f'<stop offset="1" style="stop-color:var({last})"/></linearGradient>')


def wordmark():
    out = [
        f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {n(W)} {n(H)}" width="{n(W)}" height="{n(H)}">',
        '<!-- LOG wordmark: a stand-in drawn by tools/logo-construct.py until LOG sends the master file.',
        '     Colours: --logo-a (grey), --logo-b to --logo-b-deep (the orange gradient).',
        '     Each orange piece has a grey twin. The orange shows through #logo_on, the grey through #logo_off;',
        '     moving both rects sweeps the colour across. At rest the orange covers everything. -->',
        '<defs>',
        gradient('v', 0, 1, LIGHT, DEEP),
        gradient('d', 1, 1, DEEP, LIGHT),
        gradient('h', 1, 0, DEEP, LIGHT),
        f'<clipPath id="logo_on"><rect data-sweep="on" x="0" y="0" width="{n(W)}" height="{n(H)}"/></clipPath>',
        f'<clipPath id="logo_off"><rect data-sweep="off" x="{n(W)}" y="0" width="0" height="{n(H)}"/></clipPath>',
        '</defs>',
    ]
    for name, grad in ORDER:
        dd = paths[name]
        q = f' data-q="{name[1]}"' if name[0] == 'q' else ''
        if grad is None:
            out.append(f'<path class="logo__{name}"{q} d="{dd}" style="{GREY}"/>')
        else:
            out.append(f'<g class="logo__{name}"{q}>'
                       f'<path d="{dd}" clip-path="url(#logo_off)" style="{GREY}"/>'
                       f'<path d="{dd}" clip-path="url(#logo_on)" style="fill:url(#logo_{grad})"/></g>')
    out.append('</svg>')
    return '\n'.join(out) + '\n'


def favicon():
    # A favicon can't read the page's CSS, so its colours are literal: --logo-grey and --works.
    pad = 8
    out = [f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="{n(cO - R - pad)} {-pad} {n(2 * R + 2 * pad)} {n(2 * R + 2 * pad)}">',
           '<style>.a{fill:#77787b}.b{fill:#f7931d}@media (prefers-color-scheme:dark){.a{fill:#c4c6c8}}</style>']
    for q, c in (('q1', 'b'), ('q2', 'a'), ('q3', 'b'), ('q4', 'a')):
        out.append(f'<path class="{c}" d="{paths[q]}"/>')
    out.append('</svg>')
    return '\n'.join(out) + '\n'


if __name__ == '__main__':
    BRAND.mkdir(parents=True, exist_ok=True)
    (BRAND / 'logo.svg').write_text(wordmark())
    (BRAND / 'favicon.svg').write_text(favicon())
    print(f'logo.svg {n(W)} x {n(H)}; O centre {n(cO)}, {n(cy)}')
