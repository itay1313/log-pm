#!/usr/bin/env python3
"""
Worst-case contrast of the site's white legend (#f2f4f1) over a background video, frame by frame.

    tools/video-contrast.py <video.mp4> [scrim-alpha=0] [region=0,0,1,1]

scrim-alpha is the opacity of the navy plate (#13212f) the CSS lays over the video where the text sits.
region is the box the text covers, as fractions of the frame (x0,y0,x1,y1); RTL text starts on the right.
Reports, over two frames a second, the luminance of the brightest 1% of pixels and the darkest contrast it
leaves white text. WCAG AA: 4.5 for body text, 3 for large headlines. Standard library only.
"""
import subprocess, sys

W, H = 320, 180
LEGEND = (0xF2, 0xF4, 0xF1)
PLATE = (0x13, 0x21, 0x2F)


def lin(c):
    c /= 255
    return c / 12.92 if c <= 0.04045 else ((c + 0.055) / 1.055) ** 2.4


def lum(rgb):
    r, g, b = (lin(v) for v in rgb)
    return 0.2126 * r + 0.7152 * g + 0.0722 * b


def main():
    path = sys.argv[1]
    alpha = float(sys.argv[2]) if len(sys.argv) > 2 else 0.0
    x0, y0, x1, y1 = (float(v) for v in (sys.argv[3] if len(sys.argv) > 3 else '0,0,1,1').split(','))
    raw = subprocess.run(
        ['ffmpeg', '-v', 'error', '-i', path, '-vf', f'fps=2,scale={W}:{H}', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-'],
        check=True, capture_output=True,
    ).stdout
    frame = W * H * 3
    L_text = lum(LEGEND)
    worst, p99s = 1e9, []
    cols = range(int(x0 * W), int(x1 * W))
    rows = range(int(y0 * H), int(y1 * H))
    for f in range(len(raw) // frame):
        buf = raw[f * frame:(f + 1) * frame]
        ls = []
        for y in rows:
            base = y * W * 3
            for x in cols:
                i = base + x * 3
                # The scrim composites in sRGB, the way the browser blends an rgba() layer.
                px = tuple(buf[i + k] * (1 - alpha) + PLATE[k] * alpha for k in range(3))
                ls.append(lum(px))
        ls.sort()
        p99 = ls[int(len(ls) * 0.99)]
        p99s.append(p99)
        worst = min(worst, (L_text + 0.05) / (p99 + 0.05))
    print(f'{path}: frames={len(p99s)} scrim={alpha} region={x0},{y0},{x1},{y1}')
    print(f'  brightest 1% luminance: max {max(p99s):.3f}, mean {sum(p99s) / len(p99s):.3f}')
    print(f'  worst contrast for #f2f4f1: {worst:.2f}:1  ({"passes AA body" if worst >= 4.5 else "large text only" if worst >= 3 else "FAILS"})')


if __name__ == '__main__':
    main()
