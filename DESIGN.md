---
name: LOG Project Management
description: The site follows LOG's brand book. One thin line, one orange dot, night for places and paper for people.
colors:
  night: "#13212f"
  night-2: "#1b2c3d"
  night-line: "#3a4e62"
  night-deep: "#0b131b"
  night-ink: "#f2f4f1"
  night-muted: "#a3b3bf"
  night-rule-soft: "#2a3d50"
  paper: "#e7eae6"
  paper-2: "#f4f5f2"
  ink: "#15202b"
  slate: "#53606a"
  rule: "#b8c0c2"
  rule-soft: "#d3d8d6"
  works: "#f89e2d"
  works-light: "#fbc48a"
  works-deep: "#97580c"
  sign-ink: "#15202b"
  logo-grey: "#77787b"
  logo-orange-deep: "#f67b1c"
  danger: "#b3261e"
  map-fog: "#1d2a3a"
  map-sea: "#2f4a63"
  map-building-face: "#3a4858"
  map-road-national: "#9db1c6"
  map-road-regional: "#b4c4d4"
  map-road: "#e3ebf2"
  map-road-major: "#ffffff"
  map-sky: "#4a6178"
  map-sky-zenith: "#121922"
  map-tree: "#3e5a52"
  map-tree-lit: "#557466"
typography:
  headline:
    fontFamily: "'Ploni Tzar', 'Ploni', system-ui, sans-serif"
    fontSize: "clamp(2.75rem, 1.75rem + 3.4vw, 4.5rem)"
    fontWeight: 700
    lineHeight: 0.92
    letterSpacing: "-0.03em"
  section:
    fontFamily: "'Ploni Tzar', 'Ploni', system-ui, sans-serif"
    fontSize: "clamp(2.25rem, 1.5rem + 2.4vw, 3.5rem)"
    fontWeight: 700
    lineHeight: 0.92
    letterSpacing: "-0.03em"
  row:
    fontFamily: "'Ploni Tzar', 'Ploni', system-ui, sans-serif"
    fontSize: "clamp(2rem, 1.5rem + 1.1vw, 2.5rem)"
    fontWeight: 700
    lineHeight: 1.02
    letterSpacing: "-0.02em"
  subhead:
    fontFamily: "'Ploni Tzar', 'Ploni', system-ui, sans-serif"
    fontSize: "clamp(1.5rem, 1.3rem + .5vw, 1.75rem)"
    fontWeight: 700
    lineHeight: 1.02
    letterSpacing: "-0.02em"
  figure:
    fontFamily: "'Ploni', system-ui, sans-serif"
    fontSize: "clamp(2.75rem, 1.75rem + 3.4vw, 4.5rem)"
    fontWeight: 300
    lineHeight: 1
    letterSpacing: "-0.045em"
    fontFeature: "tnum"
  title:
    fontFamily: "'Ploni Tzar', 'Ploni', system-ui, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 500
    lineHeight: 1.15
    letterSpacing: "-0.01em"
  legend:
    fontFamily: "'Ploni Tzar', 'Ploni', system-ui, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 500
    lineHeight: 1.2
  body:
    fontFamily: "'Ploni', system-ui, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 400
    lineHeight: 1.6
  prose:
    fontFamily: "'Ploni', system-ui, sans-serif"
    fontSize: "1.25rem"
    fontWeight: 400
    lineHeight: 1.7
  label:
    fontFamily: "'Ploni Tzar', 'Ploni', system-ui, sans-serif"
    fontSize: "0.9375rem"
    fontWeight: 500
    lineHeight: 1.4
  caption:
    fontFamily: "'Ploni Tzar', 'Ploni', system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 500
    lineHeight: 1.3
rounded:
  control: "8px"
  surface: "0"
  map-frame: "26px"
spacing:
  line: "2px"
  rule: "1px"
  dot: "12px"
  gutter: "clamp(16px, 4vw, 56px)"
  section: "clamp(56px, 7vw, 108px)"
  header: "84px"
  header-phone: "68px"
  container: "1320px"
components:
  button:
    backgroundColor: "{colors.night}"
    textColor: "{colors.night-ink}"
    typography: "{typography.legend}"
    rounded: "{rounded.control}"
    padding: "0.8em 1.65em"
  button-primary:
    backgroundColor: "{colors.works}"
    textColor: "{colors.sign-ink}"
    typography: "{typography.legend}"
    rounded: "{rounded.control}"
    padding: "0.8em 1.65em"
  button-ghost:
    backgroundColor: "transparent"
    textColor: "{colors.ink}"
    typography: "{typography.legend}"
    rounded: "{rounded.control}"
    padding: "0.8em 1.65em"
  field:
    backgroundColor: "{colors.paper-2}"
    textColor: "{colors.ink}"
    rounded: "{rounded.control}"
    padding: "0.85em 1.1em"
  chip:
    backgroundColor: "transparent"
    textColor: "{colors.slate}"
    rounded: "{rounded.control}"
    padding: "0.6rem 1.25rem"
  chip-active:
    backgroundColor: "{colors.night}"
    textColor: "{colors.night-ink}"
  title-band:
    backgroundColor: "{colors.night}"
    textColor: "{colors.night-ink}"
    typography: "{typography.headline}"
  row:
    textColor: "{colors.ink}"
    typography: "{typography.row}"
    padding: "clamp(1.25rem, 2.2vw, 1.9rem) 0"
  white-sheet:
    backgroundColor: "{colors.night-ink}"
    textColor: "{colors.ink}"
    rounded: "{rounded.surface}"
  night-section:
    backgroundColor: "{colors.night}"
    textColor: "{colors.night-ink}"
---

# Design System: LOG Project Management

## Overview

**The source is the brand book.** The book is a live page at `/brand` (`page-brand.php`, `inc/brand-book.php`, `template-parts/brand/*.php`, `src/brand/`). It has fourteen chapters, and the site is built on them. When the book and this file disagree, the book is right and this file is stale. The site's templates and the book draw the same components (`log_line()`, `log_circle()`, `log_dot()`, `log_icon()`, `log_logo()`) from the same tokens (`src/tokens/tokens.json`), so a change to a rule lands in both.

**The idea: we are the junction.** LOG connects planning, people and execution. The logo's O is a roundabout seen from above, cut into four quarters. Lines are roads: thin, one weight, running to the edge and crossing. One orange dot sits where something happens. A line that turns from grey to orange is a plan leaving the paper.

**Two grounds.** Night (navy, asphalt at night) is for places: the home map, every page's opener, the menu, the control company, the footer. Paper is for people, figures and reading. Orange is spent once.

The book's chapters, and where each lives on the site:

| | Chapter | On the site |
|---|---|---|
| 01 | How to use the book | Six questions before a page is designed: place or person, the one thing, which template, where text starts, which line, what to check. |
| 02 | The idea | The junction: the lane line down every page, the crossing in the footer, the roundabout that opens the home page. |
| 03 | Tone | Copy rules below (Writing). |
| 04 | The logo | `log_logo()` in the header, menu, footer and page cover. |
| 05 | Colour | Colors. |
| 06 | Type and grid | Typography, Layout. |
| 07 | The thin line | The Line System. |
| 08 | Grounds | Night for places, paper for people. |
| 09 | Photography | Photography. |
| 10 | Icons | Icons. |
| 11 | Data | Stage rows, timelines, figures. |
| 12 | Social | Not on the site. |
| 13 | Examples | The footer follows the cover; team cards follow the team slide. |
| 14 | What we don't do | Do's and Don'ts. |

## Colors

One palette for the site and the book (`src/tokens/tokens.json`). Two families, night and day, and one orange.

**No colour outside the tokens.** CSS reads `var(--…)` and makes a shade or a shadow with `color-mix()` between tokens; JS reads `tokens.js` (`color`, `alpha`, `mix`). Shadows and scrims are night-deep at an opacity, never black. `npm run build` runs `src/tokens/lint.mjs` first and fails on any raw #hex, rgb() or 0x colour in `src/`; a mask or a light temperature is marked `colour-ok: <reason>`.

### The pairs the book allows
- **Ink on paper** (13.6:1): text, the default.
- **Night ink on night** (14.8:1): text on dark.
- **Sign ink on orange** (7.7:1): an orange box always carries dark text. Never white on orange.
- **Orange on night:** an accent on dark. A word, a number, a line, the dot.
- **Slate on paper** (5.3:1), **night muted on night** (7.6:1): secondary text.
- **Never:** orange as text on paper (2.0:1), paper as text on orange. Orange that has to be read small on paper is `works-deep` (4.7:1).

### Night and day
The night family is `night-*`; the older `plate-*` and `legend-*` names are aliases in tokens.css.
- **Night** (`night`): the dark ground.
- **Raised night** (`night-2`), **night line** (`night-line`), **night deep** (`night-deep`): one surface up, a line on night, and what lies behind the page.
- **Hairs** (`hair`, `hair-strong`, `hair-ink`): translucent lines. `hair-strong` is the structural line on night; `hair` the rule between rows.
- **Paper** (`paper`, `paper-2`), **ink** (`ink`), **slate** (`slate`), **rule** (`rule`, `rule-soft`): the day family.
- **Sign ink** (`sign-ink`): text on orange and on white sheets. A night world never swaps it.
- **Danger** (`danger`): field errors only.

### Named Rules
**One Orange.** Orange marks one thing on a page: the dot. After that it marks state (the line under the current world, a focused field's line, the stages a project has passed, a hovered arrow) and LOG's work on the map. It is never an ornament and never a second accent beside the dot.

**The Token Swap Rule.** Night is made by reassigning tokens on a container (`paper` becomes `night`, `ink` becomes `night-ink`, `slate` becomes `night-muted`, `rule` becomes `night-line`, `works-deep` becomes `works`). Components follow by themselves. Never write a night variant of a component by hand.

## Typography

**One family.** Ploni Tzar for headlines and labels, Ploni for everything else. Both are licensed and self-hosted.

Three weights, no more: a heavy headline, regular text, light figures.
- **Ploni Tzar Bold 700** (`--font-headline`): headlines only. Two to four words, closed by a period.
- **Ploni Tzar Medium 500** (`--font-display`): subheads and labels: buttons, captions, counts, the page number.
- **Ploni Regular 400** (`--font-text`): reading.
- **Ploni Light 300** (`--font-figure`): big numbers and numbering. Not sentences.

### Hierarchy
One scale, in `tokens.json`: sizes `--fs-*`, weights `--fw-*`, leading `--lh-*`, tracking `--track-*`, and `--type-*` roles in tokens.css to use with `font:`. No stylesheet sets a size outside it. The book gives the site's sizes: headline 56 to 72, subhead 28, body 18, caption 14, chapter label 15. Sizes below run from a 375px phone to a 1440px screen.

- **h1** `--fs-h1` (44 → 72px): page titles, the home headline, the footer's "בואו נדבר". Big figures use the size in Ploni Light.
- **h2** `--fs-h2` (36 → 56px): section titles.
- **h3** `--fs-h3` (32 → 40px): destination rows, lead cards, the build steps on the map.
- **h4** `--fs-h4` (24 → 28px): card titles, steps, values, quotes.
- **Title** `--fs-title` 24px, Ploni Tzar 500.
- **Lead** `--fs-lead` 20px: ledes and prose.
- **Base** `--fs-base` 18px: body and every label on a control.
- **Small** `--fs-small` 15px: captions, meta, field labels.
- **Micro** `--fs-micro` 14px: map labels, table heads. Nothing is smaller, apart from MapLibre's attribution.

### Named Rules
**The Period Rule.** A headline ends in a period, set by `log_title()`; one that ends in ? or ! keeps it. Names (a project, a job, a person) are not headlines and take none. The page's main headline closes on the orange period: that is the page's dot. On the home page every chapter is a screen of its own, as a sheet is in the book, so each chapter's headline closes on the orange period too. The last word and its period are tied (`.title-end`), so a period never wraps alone.

**The Tabular Rule.** Every number is set with tabular numerals. Grid references are isolated left to right inside RTL text.

### Writing
From chapter 03. Open with what is, not what isn't. Write at eye level, exact in numbers and terms, and say what is hard too. Gender with a period: את.ה, מנהל.ת. No words that mark age ("צוות צעיר", "דינמי"). Before publishing: could a competitor sign the sentence almost unchanged? If so, sharpen it. No hyphen before a number in Hebrew: rephrase.

## Layout

RTL throughout; "start" is the right edge, "forward" is left. Every text starts on the same right-hand line: the container is 1320px wide inside a fluid gutter of clamp(16px, 4vw, 56px). Sections breathe at clamp(64px, 8vw, 120px). The header is 84px (68px on phones) and hangs over a navy strip that the page's opener continues.

**Every inner page opens like a chapter of the book** (`template-parts/opener.php`, `src/styles/opener.css`), the same sheet as the home page's first chapter and the menu. One thin line crosses the window. The title stands heavy on it (72px at most); the page's number in the header order is drawn huge in outline behind it and cut by the line; a second line cuts straight through, the dot where they meet. The line is grey from the start side and orange from the crossing. Under the grey stands the text (a lede, tags, the page's actions); under the orange what the page counts: how many projects, items, open jobs or people, a project's grid reference, a job's number. Contact opens on its title alone (spec v6 §15); the offices' phones stand with the offices below. A place behind the sheet (a project's photograph, the control and careers films) is greyed under one even night layer; a picture of people hangs below the sheet into the paper, in colour. A project stands on its place at full window height. One item of a list, the search and a plain page get the same sheet, lower. 404 draws its own number where a page's number stands. On phones the crossing line and its dot are left out and the count moves under the text.

**Below the opener a page is a run of sections, each hanging from one thin line.** On a wide screen (1000px and up) the section's title stands in the start column (4 of 12) and holds its place while the rows beside it pass; the content takes the other eight, so no half of a sheet is left empty. Rows that hang from the section's line give up their own top line. A section whose content needs the whole width (people, pictures, cards, a timeline) is `.section--wide`: the title and its lede share the first row. The home page's chapters are screens of their own and don't follow this.

**The home page is the map**, full viewport. The headline sits on the sea at the start side with no panel behind it; a scrim darkens the start and bottom edges. Under it, on the first screen: the lede, the "לאן?" search as one thin line to write on with a thin arrow at its end, and three filters, each a caption over a value on its own thin line. The live figures stand at the bottom end, each under a thin line. Map controls are small night grounds along the end edge. The resting camera stands far enough back to show a dozen projects around the city, not three; on phones, where the headline sits above the map, it looks at the city itself. A pool of night in the end corner carries the figures over the lit city. A project's card opens on the map's side of its marker, and on a wide screen the list sheet stops short of the search, so neither lies over the legend.

**The drive.** The map stays pinned behind the first chapter and the scroll moves its camera (`src/home/drive.js`): from Gush Dan up to the whole country, every project lighting as a small orange beacon. The drive has numbered chapters, each marked the way the book marks a chapter: the number in orange over how many there are ("01 / 04"), and a headline that closes on the orange period. The first chapter is set like a chapter opener in the book, over the map instead of a photograph. One thin line crosses the window: the motto stands heavy on it (up to 96px, the one headline on the site above 72, as the book's openers are), the chapter's number is drawn huge in outline behind it, rising from under the line, and a second line cuts through, the dot where they meet. The line is grey from the start side and turns orange at the crossing: under the orange stand LOG's two figures, under the grey the text, one paragraph at a time, the motto lighting a sentence with each. The second chapter dives into one real plan and "how we work" plays out on it one step at a time: the boundary draws as a dashed line (not built yet), dimension lines stretch along the site, the buildings inside rise, and a white sheet hangs from the site with the plan's real stage. Along its bottom runs the rail, the book's timeline: the four steps on one line that turns orange as far as the work has come and runs out to the window's edge.

**The road.** After the map the drive goes on along a night road (`src/home/road.js`, CSS 3D). The road waits under the build chapter's last screen, behind its night, so its horizon is at that chapter's edge when it lifts. Each practice area is a sign on a gantry, edged in the thin line. The drive slows under every sign, so the sign stands at reading size for most of its stretch of scroll, then moves on quickly to the next; on a phone the sign is the width of the screen. In the bottom start corner stands the index: the chapter's title over a row for every area (number, name, count), rows between hairs, the area overhead marked in white with an orange number and the thin orange line under it. A row drives to its sign. The index leaves with the last sign, and an exit sign comes up the shoulder and opens into the control company. Its world is a place: LOG's aerial roundabout at night, greyed under one even night layer, with the chapter's number in outline over it, the legend on the start side and the control page's three roles on one timeline line that runs out to the window's edge (`log_control_roles()` feeds both pages). The road, its lane dashes and the gantry steel are a drawing of a road, not interface: they are the one place where a thick shape is allowed. The dashes move by sliding the whole plane, never by repainting it: that is what keeps the road at full frame rate on a slow machine.

**The footer is the last junction**, set like the book's cover. "בואו נדבר." stands on a thin line that bleeds to both edges of the window. The page's lane line comes down the start margin, cuts straight through it and runs to the bottom edge. The dot sits on the crossing. Under it the level-2 pages are six rows between hairs.

**The Lane Line.** The line system's switch line, down the start margin of every page: grey for the road ahead, orange for the road behind the reader. The switch follows the scroll through a short blend; under reduced motion the line stays grey.

Below the map, content is rows, not grids of boxes: a thin ink line on top, grey hairs between. Figures are Light numbers, each under a thin line. Project tiles use an auto-fill grid (min 300px); on a phone a project in a list is a row (a small square picture, then its name, place and stage), so a list reads as a list. Knowledge items are rows too, with or without a picture: what it is and when, the title and a line, the picture, the arrow. The featured one leads above them. Search results are grouped the way sections are: the group's name and its count in the start column, the rows beside it.

Breakpoints: 1180px (the two worlds lose their second line), 900px (header collapses, the two worlds move to a fixed bottom bar), 760px (home: legend on top, tools at the bottom; steps go vertical), 700px and 560px (forms, team circles shrink to 148px, two across).

## Shapes

**One radius.** `--r-control` (8px) for what you press or type in: buttons, fields, chips, tags. The book allows a rounded corner only in a thin line, so nothing is a pill. Photographs, cards, sheets, dialogs and the projects map are square. The home map's frame (`--r-frame`) is the one exception: it is a sheet laid on the table that opens to the full window as the drive begins.

**Filled or outlined, never both.** A control is a ground with no line around it (navy, orange, white), or the thin line with no ground. A filled plate with a keyline inside it is the old language.

**No plates.** Figures, facts, contact topics, offices, quotes and counts are type under a thin line or rows between hairs. Not navy boxes.

### The Line System

The book's thin line (chapter 07; `inc/lines.php`, `src/styles/lines.css`). Every line and circle has one weight, `--line-w` (2px), runs to the edge, and cuts square: lines cross, they never round off. Each kind marks one thing, and a line nobody can name the meaning of doesn't go on the page.

- **Solid** (`log_line()`): what exists or was approved. Page structure, axes, a finished stage.
- **Dashed** (`log_line( 'dashed' )`): only what doesn't exist yet, or a reference line such as an average. Never decoration, never on a photo, never around a person.
- **Switch** (`log_line( 'switch', [ 'at' => 40 ] )`): grey until the point where LOG comes in, orange from there. Once per line, and only when it means something.
- **Dot** (`log_line( 'dot' )`, `log_dot()`): the line ends in a filled orange dot, `--dot` 12px, no ring. One orange dot on a page; the headline's orange period is it. A second dot belongs only where two lines cross on a new screen (the footer), and on a map or a chart, where every dot is data.
- **Cross** (`log_line( 'cross' )`): a second line cuts straight through, the dot on the crossing.

**The hair.** Between the rows of a list or a table the book draws a 1px grey rule (`--rule-w`, in `rule` or `hair`). It is never ink and never a frame. Everything else is `--line-w`.

A circle is a line that closes (`log_circle()`), at the same weight however big it gets:

- **Full:** a counted number in Ploni Light. Steps, map clusters, edge counters.
- **Dashed:** what is planned and hasn't happened. Never around a person.
- **Split:** a person: four quarters cut where the logo's O is cut, orange and grey in turn. Team cards.
- **Halves:** a person in black only.

No filled circle bigger than the dot, no thick circle, no gradient circle, no ring around a dot, no circle inside a circle (except a photo inside a split circle), and no circle as an ornament.

## Photography

Two kinds (chapter 09).

- **Places:** aerial and city pictures. A place that carries text is greyed and takes one even layer, from light paper to night (`grayscale(.85)` under the scrim: the project hero photo, the three city films). No layer without text on it.
- **People:** natural colour, daylight, no layer and no grey. On a team page a person stands in the split circle. No line crosses a face.

Real pictures only: LOG's people and LOG's projects. The drawing that stands in for a project without a photo keeps its orange.

## Icons

Eighteen marks in one family (chapter 10; `inc/icons.php`, `log_icon()`): a 48 box, the thin line at any size, round joins, one orange part, dashes only for what isn't built. A new icon follows the same rules.

The small interface marks are drawn the same way, as masks in tokens.css: the arrow, close, plus, minus, the caret. The arrow points forward (left). Its mask is never scaled, so the line keeps its 2px; the shaft is as long as the box is wide. No filled silhouettes, no second icon family, no emoji.

## Components

### Buttons
One family: at least 52px tall, Ploni Tzar 500 at 18px, 0.8em by 1.65em padding.
- **Everyday (`.btn`):** filled navy, white label. On a night ground it is outlined in white instead.
- **Primary (`.btn--primary`, `.btn--works`):** filled orange, ink label. One per context.
- **Ghost (`.btn--ghost`):** outlined in the thin line, ink label.
- **Hover / focus:** filled buttons turn white with ink; the outlined one turns navy. Arrows step forward.
- **Focus ring:** 3px outline, 3px offset, `--focus`. It is an accessibility mark, not a line of the language.

### The search and its filters (home)
"לאן?" in orange, the input, and a thin arrow, on one line that turns orange while you type. Suggestions drop below as a white sheet. The three filters are captions over values on their own lines; a set filter turns its value orange. On phones they fold into an outlined toggle and open as a bottom sheet.

### Markers
A project on the map is the dot: filled, orange, no ring, breathing slightly, with a soft night shadow to hold it clear of the lit city. Hover turns it white. A cluster is a count: a thin orange full circle with a Light figure. Edge counters are the same circle in grey.

### Rows
Practice areas, jobs, values, roles, footer destinations, contact topics: a thin line on top, hairs between. On hover the row steps forward and its arrow travels. No plate.

### Figures
A label over a Light number, each pair under a thin line: the home figures, a project's numbers, a page's numbers.

### Chips and tags
A chip is outlined in grey and fills navy when it is on. A tag is outlined, never filled.

### Fields
The thin ink line closed around the place to write, 8px corners, light paper inside. Focus shows the ring. Errors turn the line danger red.

### Cookie banner
A raised night sheet in the start corner, the two answers outlined at the same weight. It is the quietest thing on the page, never the brightest. On a phone's home map it stands above the search, the filters and the figures, never over them.

### Navigation
- **Header:** fixed, transparent over the map, navy once the page scrolls. Logo at the start, the two worlds, "בואו נדבר" as a plain link with the orange arrow, and one outlined control: the menu button, the word and two lanes in the thin line. Hover fills it white.
- **The two worlds:** project management and the control company, with the lane split drawn once between their names: a thin line that forks, a branch to each. The branch of the world you are in, or point at, turns orange. On phones a fixed two-cell bar at the bottom, each world with its lane arrow.
- **Menu:** a full-screen night sheet set like a chapter opener. A thin line under the top bar runs edge to edge and crosses the line that parts the destinations from the side column, the dot where they meet; the first is grey from the start side and orange from the crossing. Destinations are rows between hairs: a Light number, the name heavy, what lies down that road, the arrow. The row you point at takes the thin orange line and an orange number; the current page shows "אתה כאן" after an orange dot. The side column stands on one of LOG's places at night (the book's one even layer): the two worlds, the search as a thin line to write on, the offices, and "בואו נדבר" in orange. Opening, the lines draw, the crossing line cuts through and the dot lands last. Its close button is the menu button's twin.

### Stage row and steps
A project's stage is one thin line across four stages: solid orange behind, dashed grey ahead. Numbered steps follow the book's timeline: one grey line runs through them and each number is a Light figure in a thin full circle on it.

### Team
`template-parts/card-person.php`: the photo in a split circle (176px, 148px on phones), the name and the role under it. Without a photo the circle holds initials.

### Motion
GSAP. An opener draws itself: the line from the start side, the crossing line down through it, the dot with a small overshoot, the number up from under the line, the title rising on it; as the page scrolls on, the number sinks slowly back under the line. A section's line draws from the start side as the section arrives. Headlines rise out of line masks and the orange period lands last. Lines draw from the start side, crossing lines cut through after them, the dot lands with a small overshoot; circles close counter-clockwise, the way traffic turns in a roundabout (`src/lines/draw.js`). Pictures unroll from their top edge. Figures count once (`src/fx/count.js`). The lane line turns orange as far as the reader has come. The home page opens on a roundabout that becomes the logo's O (`src/home/roundabout.js`). Lenis smooths the wheel. Reduced motion turns all of it off and shows everything.

What keeps it fast on a slow machine: transforms and opacity only; a piece held back from the first paint (`motion.css`) is released from its safety animation as soon as motion.js sets its opacity, because a finished animation that still fills keeps its element on a layer for the life of the page; a vector drawing that is revealed or moved (`.card__art`) sits on a layer of its own, so it is rastered once.

## Do's and Don'ts

### Do:
- **Do** start from the book: find the sheet that is closest to what you need and follow it.
- **Do** build with `log_line()`, `log_circle()`, `log_dot()`, `log_icon()`, `log_logo()` and the tokens before writing new CSS.
- **Do** give every page one headline that ends in the orange period.
- **Do** make night by swapping tokens on the container.
- **Do** grey a place under its layer, and leave people in colour.
- **Do** set every number in tabular numerals.

### Don't (chapter 14):
- **Don't** add a third colour for variety.
- **Don't** recolour, stretch or redraw the logo, or use it as a graphic element, a frame or a background. The one exception is the home headline, where the logo's O turns between the words (`log_ring_glyph()`): the page opens on the roundabout that becomes that O, so it belongs to the opening. Itay's decision, 19.11.2026.
- **Don't** put more than one orange dot on a page, apart from a map or a chart.
- **Don't** run a line over a face.
- **Don't** draw thick lines or filled frames. A rounded corner only in a thin line.
- **Don't** let text leave its frame.
- **Don't** put a ring around a dot.
- **Don't** stop a line in the middle of the page without a reason.
- **Don't** put a panel behind the home headline or anything that hides the map.
- **Don't** use a second icon family, emoji or glyph characters as icons.

## Open
- `log_route_shield()` and `.shield` keep their old names: they print the page number in the menu and the footer. Page titles no longer carry it (the opener draws the number), and the home page's chapters count themselves in order (`.chapter-n`).
- The about opener counts the team only from eight people up, so a half-entered team doesn't read as a small firm.
- The about page's opening picture is a stand-in until LOG picks a team photo.
