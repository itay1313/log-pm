# Background video sources

Every file here is made by `tools/video-bg.sh` (navy grade, seamless loop, 1600 and 960 wide, poster).
Both licenses allow commercial use with no credit required; they don't allow reselling the raw clips.

| Files | Where it plays | Source | License | Command |
|---|---|---|---|---|
| `footer-city-*` | Footer, under "בואו נדבר" (every page but contact) | Pexels 39370152, twilight cityscape from above | Pexels License | `SAT=0.5 tools/video-bg.sh night.mp4 footer-city 4 14 0.5 0.9` |
| `control-city-*` | Control company opening | Pexels 31383775, drone between residential towers in Israel | Pexels License | `tools/video-bg.sh towers.mp4 control-city 6 14 0.5 0.8` |
| `careers-build-*` | Careers opening | Pexels 12757594, tower and crane at sunset | Pexels License | `SAT=0.55 tools/video-bg.sh crane.mp4 careers-build 1 14 0.5 0.9` |

Pages: pexels.com/video/stunning-twilight-cityscape-from-above-39370152,
pexels.com/video/stunning-aerial-view-of-modern-high-rise-buildings-31383775,
pexels.com/video/drone-shot-of-a-skyscraper-at-sunset-12757594.

When LOG has its own drone footage of its projects, run it through the same script under the same name and it
replaces the stock clip with no code change. Check it with `tools/video-contrast.py <file> 0.45 0,0.35,1,1`.
