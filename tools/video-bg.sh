#!/usr/bin/env bash
# Turns a raw clip into one of the theme's background videos (template-parts/video-bg.php).
#
#   tools/video-bg.sh <source.mp4> <name> [start=0] [length=14] [crop-y=0.5] [speed=1] [zoom=1]
#
# What it does, in order:
#   1. cuts <length> seconds from <start>, optionally slows it (<speed> 0.8 = 20% slower),
#   2. crops to 16:9, <zoom> 0.8 keeps the middle 80% (<crop-y> 0 keeps the top, 1 the bottom: drone shots
#      usually want less sky),
#   3. grades it into the site's night: most colour out, black mapped onto the navy plate (#13212f) so the
#      frame's edges melt into the sections around it, highlights capped at ~64% so the CSS scrim on top
#      (src/styles/video.css) can be light and still keep white text above 4.5:1, a soft vignette,
#   4. crossfades the last second into the first so the loop has no seam,
#   5. writes <name>-1600.mp4, <name>-960.mp4 and a <name>.jpg poster into the theme's assets/video/.
#
# No audio track, faststart, H.264 High so every browser plays it. Check the result with tools/video-contrast.py.
# SAT=0.6 keeps more of the colour (night footage, where lit windows and the horizon glow are the point).
set -euo pipefail

src=${1:?source clip}; name=${2:?name}; start=${3:-0}; len=${4:-14}; cropy=${5:-0.5}; speed=${6:-1}; zoom=${7:-1}
out="$(cd "$(dirname "$0")/.." && pwd)/wp-content/themes/log/assets/video"
mkdir -p "$out"
fade=1.2

# The grade. Values are tuned by eye against --plate and checked by video-contrast.py, not by theory.
grade="hue=s=${SAT:-0.25},eq=contrast=1.25:gamma=0.9,\
curves=r='0/0.05 0.5/0.22 1/0.62':g='0/0.1 0.5/0.27 1/0.64':b='0/0.15 0.5/0.33 1/0.66',\
vignette=angle=PI/4"

# Slowing down: setpts stretches time; at 0.8 and above the judder stays invisible on a drone glide.
pts="setpts=PTS/${speed}"
body=$(python3 -c "print(round($len - $fade, 3))")
off=$(python3 -c "print(round($len - 2 * $fade, 3))")

tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT

# One master at 1600 wide: graded, cropped, looped. The smaller sizes come from it.
ffmpeg -v error -y -ss "$start" -i "$src" -an -filter_complex "\
[0:v]$pts,trim=duration=$len,setpts=PTS-STARTPTS,\
crop=w='min(iw,ih*16/9)*$zoom':h='min(ih,iw*9/16)*$zoom':x='(iw-ow)/2':y='(ih-oh)*$cropy',\
scale=1600:900:flags=lanczos,fps=25,$grade,format=yuv420p,split[a][b];\
[a]trim=start=$fade,setpts=PTS-STARTPTS[body];\
[b]trim=duration=$fade,setpts=PTS-STARTPTS[head];\
[body][head]xfade=transition=fade:duration=$fade:offset=$off,format=yuv420p[v]" \
  -map '[v]' -c:v libx264 -preset slow -crf 16 -t "$body" "$tmp/master.mp4"

enc() { # width crf maxrate
  ffmpeg -v error -y -i "$tmp/master.mp4" -vf "scale=$1:-2:flags=lanczos" -an \
    -c:v libx264 -profile:v high -preset veryslow -crf "$2" -maxrate "$3" -bufsize "$3" \
    -pix_fmt yuv420p -movflags +faststart "$out/$name-$1.mp4"
}
enc 1600 26 2400k
enc 960 27 900k
ffmpeg -v error -y -ss 0.4 -i "$tmp/master.mp4" -frames:v 1 -vf scale=1600:-2 -q:v 5 "$out/$name.jpg"

ls -l "$out/$name"-*.mp4 "$out/$name.jpg"
