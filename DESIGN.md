---
name: LOG Project Management
description: Israel's guide-sign system for planning work. Navy plates with a white keyline, works-zone orange for anything active, road paper for reading.
colors:
  plate: "#13212f"
  plate-2: "#1b2c3d"
  plate-line: "#3a4e62"
  legend: "#f2f4f1"
  legend-muted: "#a3b3bf"
  paper: "#e7eae6"
  paper-2: "#f4f5f2"
  ink: "#15202b"
  slate: "#53606a"
  rule: "#b8c0c2"
  rule-soft: "#d3d8d6"
  works: "#f7931d"
  works-deep: "#b9650a"
  sign-ink: "#15202b"
  danger: "#b3261e"
  night-rule-soft: "#2a3d50"
  map-fog: "#1d2a3a"
  map-sea: "#2f4a63"
  map-road-national: "#9db1c6"
  map-road-regional: "#b4c4d4"
  map-road: "#e3ebf2"
  map-road-major: "#ffffff"
typography:
  display:
    fontFamily: "'Ploni Tzar', 'Ploni', system-ui, sans-serif"
    fontSize: "clamp(2.75rem, 6vw, 6rem)"
    fontWeight: 700
    lineHeight: 0.94
    letterSpacing: "-0.035em"
  headline:
    fontFamily: "'Ploni Tzar', 'Ploni', system-ui, sans-serif"
    fontSize: "clamp(2.375rem, 4.8vw, 4.5rem)"
    fontWeight: 700
    lineHeight: 0.96
    letterSpacing: "-0.035em"
  destination:
    fontFamily: "'Ploni Tzar', 'Ploni', system-ui, sans-serif"
    fontSize: "clamp(1.75rem, 3vw, 2.75rem)"
    fontWeight: 700
    lineHeight: 1.02
    letterSpacing: "-0.025em"
  title:
    fontFamily: "'Ploni Tzar', 'Ploni', system-ui, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 500
    lineHeight: 1.1
    letterSpacing: "-0.01em"
  figure:
    fontFamily: "'Ploni Tzar', 'Ploni', system-ui, sans-serif"
    fontSize: "clamp(2.25rem, 3.2vw, 3.25rem)"
    fontWeight: 700
    lineHeight: 1
    letterSpacing: "-0.02em"
    fontFeature: "tnum"
  sign-legend:
    fontFamily: "'Ploni Tzar', 'Ploni', system-ui, sans-serif"
    fontSize: "1.0625rem"
    fontWeight: 700
    lineHeight: 1.2
  body:
    fontFamily: "'Ploni', system-ui, sans-serif"
    fontSize: "1.0625rem"
    fontWeight: 400
    lineHeight: 1.6
  prose:
    fontFamily: "'Ploni', system-ui, sans-serif"
    fontSize: "1.1875rem"
    fontWeight: 400
    lineHeight: 1.7
  label:
    fontFamily: "'Ploni Tzar', 'Ploni', system-ui, sans-serif"
    fontSize: "0.8125rem"
    fontWeight: 500
    lineHeight: 1.4
rounded:
  small: "6px"
  chip: "8px"
  plate: "10px"
  panel: "12px"
  board: "14px"
  gantry: "20px"
spacing:
  keyline: "2px"
  keyline-inset: "5px"
  plate-gap: "8px"
  gutter: "clamp(16px, 4vw, 56px)"
  section: "clamp(56px, 7vw, 108px)"
  header: "84px"
  header-phone: "68px"
  container: "1320px"
components:
  button-primary:
    backgroundColor: "{colors.plate}"
    textColor: "{colors.legend}"
    typography: "{typography.sign-legend}"
    rounded: "{rounded.plate}"
    padding: "1.05em 1.6em"
  button-primary-hover:
    backgroundColor: "{colors.plate}"
    textColor: "{colors.legend}"
  button-day:
    backgroundColor: "{colors.legend}"
    textColor: "{colors.sign-ink}"
    typography: "{typography.sign-legend}"
    rounded: "{rounded.plate}"
    padding: "1.05em 1.6em"
  button-works:
    backgroundColor: "{colors.works}"
    textColor: "{colors.plate}"
    typography: "{typography.sign-legend}"
    rounded: "{rounded.plate}"
    padding: "1.05em 1.6em"
  button-works-hover:
    backgroundColor: "{colors.legend}"
    textColor: "{colors.plate}"
  destination-plate:
    backgroundColor: "{colors.plate}"
    textColor: "{colors.legend}"
    rounded: "{rounded.plate}"
    height: "78px"
    width: "min(100%, 620px)"
  destination-plate-go:
    backgroundColor: "{colors.works}"
    textColor: "{colors.plate}"
    rounded: "5px"
    width: "68px"
  lane-filter:
    backgroundColor: "{colors.plate}"
    textColor: "{colors.legend}"
    rounded: "{rounded.plate}"
    padding: "0.7rem 1.15rem 0.75rem"
  distance-sign:
    backgroundColor: "{colors.plate}"
    textColor: "{colors.legend}"
    typography: "{typography.figure}"
    rounded: "{rounded.plate}"
    padding: "1.15rem 1.65rem 1rem"
  route-shield:
    backgroundColor: "{colors.works}"
    textColor: "{colors.sign-ink}"
    rounded: "0.35em"
    padding: "0.3em 0.5em 0.24em"
  sheet-label:
    backgroundColor: "{colors.works}"
    textColor: "{colors.plate}"
    rounded: "{rounded.plate}"
    padding: "0.8rem 1.2rem 0.75rem"
  marker:
    backgroundColor: "{colors.plate}"
    rounded: "{rounded.small}"
    size: "26px"
  marker-active:
    backgroundColor: "{colors.works}"
  white-sign:
    backgroundColor: "{colors.legend}"
    textColor: "{colors.ink}"
    rounded: "{rounded.panel}"
    padding: "8px"
  destination-row:
    textColor: "{colors.ink}"
    typography: "{typography.destination}"
    padding: "clamp(1.25rem, 2.2vw, 1.9rem) 0"
  destination-row-hover:
    backgroundColor: "{colors.plate}"
    textColor: "{colors.legend}"
    rounded: "{rounded.plate}"
    padding: "clamp(1.25rem, 2.2vw, 1.9rem) 1.5rem"
  chip:
    backgroundColor: "transparent"
    textColor: "{colors.slate}"
    rounded: "{rounded.chip}"
    padding: "0.6rem 1.1rem"
  chip-active:
    backgroundColor: "{colors.plate}"
    textColor: "{colors.legend}"
  input-field:
    backgroundColor: "{colors.legend}"
    textColor: "{colors.ink}"
    rounded: "{rounded.small}"
    padding: "0.8em 0.95em"
  title-band:
    backgroundColor: "{colors.plate}"
    textColor: "{colors.legend}"
    typography: "{typography.display}"
  gantry-plate:
    backgroundColor: "{colors.plate}"
    textColor: "{colors.legend}"
    rounded: "{rounded.gantry}"
  night-section:
    backgroundColor: "{colors.plate}"
    textColor: "{colors.legend}"
---

# Design System: LOG Project Management

## Overview

**Creative North Star: "The Guide Sign"**

The site is Israel's road guide-sign system turned to planning work. Every surface answers the same three questions a sign answers: where you are, how far it is, and which way is forward. The building block is the plate: a navy ground, a white keyline inset a few pixels from the edge, rounded corners. Buttons, the destination search, the filters, the figures, the map markers, the menu and the page-change cover are all plates of different sizes. Nothing is a floating card with a soft blur; a thing either is a sign or it sits on the road.

There are two grounds. Navy plate carries the home map, every page's title band, the full-screen menu, the control company's whole page and the footer. Road paper, a cool off-white, carries reading below the title band. Works-zone orange is LOG's own colour on the map and the colour of anything active: hover keylines, focus rings, the current lane, the route shields, the "go" button, the lane line that draws down the page as you scroll. A white sign with an ink keyline (the project card, the list drawer, the dialog, the cookie banner) is the third material, used when a sign has to hold reading content over the map or a page.

Density follows signage: big legends in Ploni Tzar, tabular figures, one arrow shape for every link that goes somewhere, and rows of destinations instead of card grids. Motion is GSAP: legends rise out of line masks, plates unroll from their top edge, arrows step forward on hover, figures count once. Under reduced motion nothing moves and nothing is hidden.

**Key Characteristics:**
- The plate (navy, 2px white keyline inset 5px, 10px corners) is the one container, drawn with two inset shadows and no extra element.
- Works orange marks LOG's work and every active state, and nothing decorative.
- Route shields number the pages: a small orange shield at the start of each page title and menu row.
- One authored guide-sign arrow (broad head, square shaft), pointing forward, which is left in RTL.
- Ploni Tzar for every legend and figure, Ploni for running text, tabular numerals for every number.
- Night is a token swap, never a repaint of individual components.

## Colors

A signage palette: two navies and a retroreflective white for the plates, a cool road paper for reading, one orange that means "active" or "LOG".

### Primary
- **Works-Zone Orange** (`works`): LOG's projects and the 3D city on the map, the route shield, the destination plate's "go" button, the sheet label on title bands, hover and focus keylines, focus rings (3px outline, 3px offset), selection, caret, the active lane underline, the lane line and the stage dashes.
- **Deep Works Orange** (`works-deep`): orange that has to read as small text or an arrow on paper (link-arrow hover, ghost-button hover keyline). On night grounds it swaps back to `works`.

### Neutral
- **Guide-Sign Navy** (`plate`): the plate ground. Home map backdrop, title bands, header strip, gantry menu, distance signs, markers, the footer and the control company's page.
- **Raised Navy** (`plate-2`): media fallbacks, the menu's search input, offices on the footer, the ring-wipe band.
- **Plate Rule** (`plate-line`): 2px dividers on navy (menu rows, the distance sign's divider, zoom button seam, footer rules).
- **Retroreflective White** (`legend`): text and keylines on navy; the ground of the white sign and of input fields.
- **Faded Legend** (`legend-muted`): secondary text on navy, distances in menu rows, back links, the hero lede.
- **Road Paper** (`paper`): the day ground of reading pages and the scrollbar track.
- **Light Paper** (`paper-2`): quiet panels on paper (offices, job sidebar, client logos) with a 2px inset rule.
- **Road Ink** (`ink`): text on paper, 3px heading rules over lists, field borders.
- **Slate** (`slate`): secondary text on paper, labels, meta lines.
- **Rule** / **Soft Rule** (`rule`, `rule-soft`): 2px row dividers and table rules; soft rule for dividers inside white signs.
- **Sign Ink** (`sign-ink`): the legend and keyline of white signs and of the route shield. It is never swapped by a night world, so a white sign stays a white sign on navy.
- **Night Soft Rule** (`night-rule-soft`): the `rule-soft` value inside night worlds.
- **Danger** (`danger`): field errors only.

### Map palette
The 3D map (the `C` object in `src/hero/scene.js`) uses fog and sea navies (`map-fog`, `map-sea`), four road greys stepping from national to major (`map-road-national` to `map-road-major`), and `works` for buildings and plans. The city is orange because it is LOG's work; the roads are pale so markers and legends stay readable on top.

### Named Rules
**The Works Zone Rule.** Orange means one of two things: LOG's work, or "this is active". Hover, focus, current page, selected filter, live count. If orange appears on something idle and decorative, it is wrong.

**The Token Swap Rule.** Night is made by reassigning tokens on a container (`paper` becomes `plate`, `ink` becomes `legend`, `slate` becomes `legend-muted`, `rule` becomes `plate-line`, `rule-soft` becomes `night-rule-soft`, `works-deep` becomes `works`). Components follow by themselves. Never write a night variant of a component by hand.

## Typography

**Display Font:** Ploni Tzar (with Ploni, system-ui)
**Body Font:** Ploni (with system-ui)

**Character:** Ploni Tzar is the sign legend: condensed, bold, tight, set at large sizes with negative tracking so headings read like gantry text. Ploni does the reading in lighter weights. Both are licensed and self-hosted (Ploni 300 to 600, Ploni Tzar 300 to 700).

### Hierarchy
- **Display** (700, clamp(2.75rem, 6vw, 6rem), 0.94): page titles in the title band. The home headline runs a little larger and tighter (up to 6rem, 0.9) with its last word in works orange.
- **Headline** (700, clamp(2.375rem, 4.8vw, 4.5rem), 0.96): section titles on paper, max about 16ch.
- **Destination** (700, clamp(1.75rem, 3vw, 2.75rem), 1.02): names in destination rows (practice areas, jobs). The menu's rows use the same voice at up to 5.5rem, also scaled by viewport height.
- **Title** (500 to 700, 1.5rem, 1.1): step titles, card titles (1.625rem, 700), sidebar heads.
- **Figure** (700, clamp(2.25rem, 3.2vw, 3.25rem), 1, tabular): counts on distance signs.
- **Sign legend** (700, 1.0625rem): buttons, menu toggle, header links, anything that is the text on a plate.
- **Body** (400, 1.0625rem, 1.6) and **Prose** (400, 1.1875rem, 1.7, max 62 to 66ch): running text.
- **Label** (500, 0.8125rem to 0.875rem, Ploni Tzar): filter captions, card meta, table heads, stage labels. Sentence case, no tracking, never uppercase.

### Named Rules
**The Tabular Rule.** Every number (counts, units, distances, years, shields, grid references) is set with tabular numerals. Grid references are isolated left-to-right inside RTL text.

**The Legend Rule.** Text that sits on a plate is Ploni Tzar 700. Ploni regular is for reading, not for signs.

## Layout

RTL throughout; "start" is the right edge, "forward" is left. The container is 1320px wide inside a fluid gutter of clamp(16px, 4vw, 56px). Sections breathe at clamp(56px, 7vw, 108px) top and bottom. The header is 84px (68px on phones) and every page without a full-screen cover hangs it over a navy strip that the page's title band continues, so the gantry always reads light on navy.

The home page is the map, full viewport. The headline sits straight on the sea at the start side with no panel behind it; a gradient scrim darkens the start and bottom edges instead. Under it the destination plate and three lane filters hang 8px apart. The distance sign stands at the bottom end corner, the lane-split pair at the bottom start, and map controls as small plates along the end edge.

Below the map, content is rows, not grids of boxes: destination rows with a 3px ink rule on top and 2px rules between. Project tiles use an auto-fill grid (min 300px, gaps 3.25rem by 1.75rem). Steps are four columns on one dashed orange lane, turning vertical on phones. Title bands are a two-column grid with the title on the start side and the orange sheet label at the end.

**The Lane Line.** One dashed orange line (4px wide, 30px dash, 24px gap; 3px, 18/16 on phones) runs down the start edge of the main content and draws in as the reader scrolls.

Breakpoints observed: 1180px (lane-split loses its second line), 900px (header collapses, the lane split moves to a fixed bottom bar, menu goes single column), 760px (home becomes legend on top, tools at the bottom; process goes vertical), 700px and 560px (form and footer columns). A short-viewport rule (max-height 760px) drops the hero lede.

## Elevation & Depth

Depth comes from the plate's keyline and from the navy against paper, not from shadow stacks. The keyline itself is two inset box-shadows: the ground colour for the inset (5px), then the line colour for 2px more. Plates that stand on the map also cast one soft drop shadow, always the same cool near-black, offset straight down as if lit from above.

### Shadow Vocabulary
- **Keyline** (`box-shadow: inset 0 0 0 5px <ground>, inset 0 0 0 7px <line>`): every plate. Small plates scale it down (3px/5px on markers and map controls, 4px/6px on filters and sheet labels, 6px/8px on figure signs, 10px/13px on the menu).
- **Map plate drop** (`0 24px 50px -20px rgba(5,10,16,.7)`): plates over the map (destination plate, distance sign, lane split).
- **White sign drop** (`0 30px 60px -18px rgba(5,10,16,.7), 0 4px 12px rgba(5,10,16,.3)`): the project card, list drawer and dialog over the map or page.
- **Control drop** (`0 10px 24px -10px rgba(5,10,16,.7)`): small map controls.
- **Focus ring** (`outline: 3px solid works; outline-offset: 3px`; fields use `0 0 0 3px works`).

### Named Rules
**The Keyline Rule.** State changes the keyline, not the shadow. Hover and focus recolour the keyline to orange; a plate does not lift, grow a glow, or change its drop.

## Shapes

Rounded rectangles only, with corners that grow with the sign: 6px for small controls, markers, fields and the hero card's inner image; 8px for chips, steps and the twin-nav items; 10px for the standard plate; 12px for white signs and large figure signs; 14px for the orientation board and phone sheets; 20px for the full-screen gantry. The route shield's corner is relative (0.35em) so it scales with the heading it sits in. Lines are 2px (row rules, keylines, field borders) or 3px (rules above lists and figure groups). The one arrow is a filled silhouette, masked from a CSS variable and coloured with `currentColor`.

## Components

### Buttons
Signs you can press.
- **Shape:** the plate (10px corners, 2px keyline inset 5px).
- **Primary:** navy plate, white legend and keyline, Ploni Tzar 700 at 1.0625rem, 1.05em by 1.6em padding.
- **Hover / Focus:** the keyline turns orange; background and size stay put. Arrows inside step 6px forward.
- **Day:** white plate with a sign-ink keyline; hover keyline turns deep orange.
- **Works:** orange plate with a navy keyline, used where the action is the page's main way forward on navy (control company section). Hover swaps the ground to white.
- **Text links that go somewhere:** Ploni Tzar 700 followed by the arrow; the arrow steps forward and turns deep orange on hover.

### Destination plate (signature)
The "לאן?" search on the home map: a 78px navy plate, orange "לאן?" legend at 1.5rem, white input, and a 68px orange "go" block with the arrow, inset 9px. Focus anywhere inside turns the keyline orange. Suggestions drop below as a white sign; the highlighted row turns into a navy plate.

### Lane filters
Three equal plates under the destination plate with a dimmed white keyline (55%) and a small label over a bold value. Hover or focus turns the keyline orange; a set filter turns its value orange. On phones they fold into a toggle and open as a bottom sheet.

### Distance sign
A navy plate holding live figures as label and tabular number pairs, with a 2px plate-line rule above an orange "all projects" link. The same sign shape carries project figures and page figures elsewhere.

### Route shield
A small orange shield with a sign-ink keyline holding the page's two-digit number, set inline at the start of the page title and of its menu row (`00` on home). Size is relative to the heading, never below 0.8125rem.

### Markers
Every project on the map is the same 26px navy plate with an orange centre. Hover, focus or active: it scales 1.3x and inverts (orange plate, navy keyline and centre). Clusters are 40px plates with a white count. Edge counters are 32px plates pointing at what lies past the screen edge.

### White sign
The project card, list drawer, apply dialog and cookie banner: retroreflective white ground, ink keyline, 12px corners, 8px padding around content. The card's "go" bar inside is a navy plate that turns orange on hover.

### Destination rows
Practice areas and jobs: name, one line, a count and the arrow in a three-column row between 2px rules. Hover turns the whole row into a navy sign (padding opens 1.5rem, 10px corners), the count and arrow go orange and the arrow steps 8px forward.

### Chips
- **Style:** transparent with a 2px rule keyline, slate Ploni Tzar 700 text, 8px corners.
- **State:** hover darkens the keyline to ink; active becomes a navy plate with an orange keyline.

### Inputs / Fields
- **Style:** white ground, 2px ink border, 6px corners, 0.8em by 0.95em padding; selects carry a small triangle at the left.
- **Focus:** border stays ink, an orange 3px ring appears outside.
- **Error:** border turns danger red with a 25% red ring; hint text in danger red.

### Navigation
- **Gantry header:** fixed, transparent over the map, solid navy once the page scrolls. Wordmark at the start, "talk to us" and the menu plate at the end.
- **Lane split:** the two worlds (project management, control company) as two plates that part ways, each with a 45-degree lane arrow. On the home map they stand as large plates at the bottom start; on other pages they sit in the header with a 4px orange underline for the current world; on phones they become a fixed two-cell bar at the bottom edge.
- **Menu:** a full-screen gantry plate (20px corners, 13px keyline inset) of destination rows with live counts and the shield number; the current page shows an orange "אתה כאן" tag. It unrolls from the top edge and names rise in line masks.

### Stage row and steps
Project stage is four lane dashes lit in orange up to "now"; a finished project's dashes go ink. Numbered steps are route shields (2.75rem navy plates) sitting on one dashed orange lane.

### Title band and sheet label
Every inner page opens on a navy band that continues the header strip, with a display title, an optional meta line and lede, and an orange sheet label at the end giving how much lies down this road. Hero images hang from the band into the paper.

### Motion
GSAP. Legends rise from line masks (yPercent 112, power4.out, 1.05 to 1.15s, 0.08s stagger). Plates and tiles unroll from their top edge with a clip-path inset rounded to 10px (expo.out, about 1.2s). Figures count up once (expo.out, 1.4s). The lane line scales in with scroll. The menu unrolls in 0.8s expo.inOut; a navy cover closes over the page on exit (0.55s expo.in) and lifts on arrival. Arrows translate 6px over 0.45s on `cubic-bezier(.16, 1, .3, 1)`. Reduced motion turns all of it off and shows everything.

## Swap points for TwoPointFour

When TwoPointFour's visual language lands, the world changes in three places and nowhere else:
1. `wp-content/themes/log/src/styles/tokens.css`: every colour, font, radius, keyline and icon token.
2. The night token swap in `src/styles/pages.css` on `.sheet--night, .section--control, .section--night, .site-footer` and on `body.page-template-page-control` (the only place `night-rule-soft` is written).
3. The `C` palette at the top of `src/hero/scene.js` for the 3D map.

One copy lives outside the theme: the coming-soon gate (`log-core/inc/coming-soon.php`) renders without the theme's CSS, so it carries the few tokens it uses inline. Update it with tokens.css.

The legacy aliases in tokens.css (`--orange`, `--night`, `--night-ink`, `--grey`, `--line` and the rest) point at the new tokens; new code uses the new names.

## Do's and Don'ts

### Do:
- **Do** build every new container as a plate: navy ground, white 2px keyline inset 5px, 10px corners, drawn with the two inset shadows.
- **Do** reserve works orange for LOG's work and active state, and use `works-deep` when orange has to be read as small text on paper.
- **Do** make night by swapping tokens on the container (The Token Swap Rule).
- **Do** use the one guide-sign arrow for every link that goes somewhere, pointing forward (left), and move it forward on hover.
- **Do** set every number in tabular numerals and isolate grid references left to right.
- **Do** put a route shield at the start of every page title and its menu row.
- **Do** show state by recolouring the keyline orange, not by lifting or glowing.

### Don't:
- **Don't** put a panel behind the home headline or anything that hides the map; the headline sits on the sea.
- **Don't** fall back to a centred headline with two buttons, or a three-card service grid; lists of things are destination rows.
- **Don't** use a second icon family, emoji or glyph characters as icons; the authored arrow, close, plus, minus and caret masks are the whole set.
- **Don't** add small uppercase kickers above headings; the route shield and the title band already say where you are.
- **Don't** paint night variants of single components or hard-code navy into a component.
- **Don't** let a white sign's keyline swap with the night world; it stays `sign-ink`.
