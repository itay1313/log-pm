---
name: LOG Project Management
description: One survey-sheet series of Israel, printed in two editions, day paper and night navy.
colors:
  orange: "#f7931d"
  orange-deep: "#c96f06"
  paper: "#e8ece9"
  paper-2: "#f3f5f3"
  ink: "#1b1f25"
  slate: "#56625e"
  rule: "#8a9590"
  rule-soft: "#c3cbc6"
  night: "#13212f"
  night-2: "#1a2b3c"
  night-line: "#3d5063"
  night-rule-soft: "#2b3e51"
  night-ink: "#e8ece9"
  night-muted: "#9aabb8"
  danger: "#b3261e"
typography:
  display:
    fontFamily: "'Ploni Tzar', 'Ploni', system-ui, sans-serif"
    fontSize: "clamp(2.5rem, 5.4vw, 5.25rem)"
    fontWeight: 700
    lineHeight: 1.08
    letterSpacing: "-0.035em"
  headline:
    fontFamily: "'Ploni Tzar', 'Ploni', system-ui, sans-serif"
    fontSize: "clamp(2rem, 3.6vw, 3.25rem)"
    fontWeight: 500
    lineHeight: 1.08
    letterSpacing: "-0.02em"
  title:
    fontFamily: "'Ploni Tzar', 'Ploni', system-ui, sans-serif"
    fontSize: "1.375rem"
    fontWeight: 500
    lineHeight: 1.08
    letterSpacing: "-0.01em"
  figure:
    fontFamily: "'Ploni Tzar', 'Ploni', system-ui, sans-serif"
    fontSize: "clamp(2.25rem, 4.5vw, 3.75rem)"
    fontWeight: 500
    lineHeight: 1
    letterSpacing: "-0.03em"
    fontFeature: "tnum"
  body:
    fontFamily: "'Ploni', system-ui, sans-serif"
    fontSize: "1.0625rem"
    fontWeight: 400
    lineHeight: 1.6
  prose:
    fontFamily: "'Ploni', system-ui, sans-serif"
    fontSize: "1.1875rem"
    fontWeight: 400
    lineHeight: 1.65
  label:
    fontFamily: "'Ploni Tzar', 'Ploni', system-ui, sans-serif"
    fontSize: "0.8125rem"
    fontWeight: 500
    lineHeight: 1.5
    fontFeature: "tnum"
rounded:
  none: "0"
spacing:
  gutter: "clamp(16px, 4vw, 56px)"
  section: "clamp(44px, 5.6vw, 84px)"
  tick: "72px"
  header: "76px"
  container: "1320px"
components:
  button-primary:
    backgroundColor: "{colors.ink}"
    textColor: "{colors.paper}"
    rounded: "{rounded.none}"
    padding: "0.85em 1.5em"
  button-ghost:
    backgroundColor: "transparent"
    textColor: "{colors.ink}"
    rounded: "{rounded.none}"
    padding: "0.85em 1.5em"
  button-primary-night:
    backgroundColor: "{colors.night-ink}"
    textColor: "{colors.night}"
    rounded: "{rounded.none}"
    padding: "0.85em 1.5em"
  input-field:
    backgroundColor: "{colors.paper-2}"
    textColor: "{colors.ink}"
    rounded: "{rounded.none}"
    padding: "0.75em 0.9em"
  cartouche:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.ink}"
    rounded: "{rounded.none}"
    width: "min(46rem, 48vw)"
  cartouche-head:
    textColor: "{colors.slate}"
    typography: "{typography.label}"
    padding: "0.7rem 1.5rem"
  tab:
    textColor: "{colors.slate}"
    padding: "0.7rem 0"
  tab-active:
    textColor: "{colors.ink}"
  sheet-label:
    textColor: "{colors.slate}"
    typography: "{typography.label}"
  project-tile-meta:
    textColor: "{colors.slate}"
    typography: "{typography.label}"
  night-sheet:
    backgroundColor: "{colors.night}"
    textColor: "{colors.night-ink}"
---

# Design System: LOG Project Management

## Overview

**Creative North Star: "The Survey Sheet Series"**

Every page is a sheet in one series of topographic sheets of Israel. A sheet has a neatline (its border rule), a title block, a sheet number, a legend, and a margin that carries grid references. The site is built from those parts and nothing else: hairline rules instead of boxes, legend tables instead of feature cards, figures and coordinates set in tabular numerals because they are data.

The series is printed in two editions. The day edition is survey paper with ink and slate rules, and it carries most pages. The night edition is navy with faint contour lines; it carries the home map, the control company's whole page, and the footer, which is the sheet's margin band shared by both editions. An edition is chosen per world, never mixed inside one sheet: the control company is a separate world and lives entirely in the night edition.

Orange is LOG's mark on the map. It prints LOG's plans and the 3D city, marks the active state of the interface, and tops the title block. Everything else is ink, slate and paper. Density is that of a printed sheet: tight legend rows, generous margins, one long horizontal rule per section.

**Key Characteristics:**
- Two editions of one sheet: day (paper #e8ece9) and night (navy #13212f with contour lines).
- Square corners everywhere; hairline 1px rules carry all structure.
- Orange only for LOG's plans, the active state, and the title block's top rule.
- Ploni Tzar for display, labels and figures; Ploni for running text; tabular numerals for every number.
- Grid references and ITM coordinates read left to right inside RTL Hebrew.
- One authored motion per sheet: the neatline draws in, the title block settles.

## Colors

A narrow survey palette: paper, ink and slate for the day edition, navy and pale slate for the night edition, one brand orange used sparingly.

### Primary
- **LOG Survey Orange** (orange): LOG's own plans (map plates, sheet-index dots, map-key swatch), the 3D city, and every active or hover state (nav underline, active tab, filter cell focus, button underline wipe, focus ring, pin hover). Also the 4px top rule on title blocks and floating sheets (cartouche, project card, list drawer, apply dialog, cookie banner, map popup).
- **Burnt Orange** (orange-deep): the orange used where it has to read as small marks on paper; arrow hover in legend rows and job rows, map-link hover on tiles.

### Neutral (day edition)
- **Survey Paper** (paper): page ground; also the ground of every title block that sits on the night edition.
- **Field Paper** (paper-2): inputs, hover fill of legend rows, image placeholders, search drawer.
- **Drafting Ink** (ink): text, primary buttons, the heavy rule that opens a fact list or job list.
- **Slate Note** (slate): secondary text, labels, sheet labels, legend captions.
- **Slate Rule** (rule): the neatline and every structural hairline.
- **Faint Rule** (rule-soft): row separators inside tables and lists.

### Neutral (night edition)
- **Night Navy** (night): the night sheet ground; the map plate, control page, footer, project cover.
- **Deep Navy** (night-2): second ground for media wells, control-page field fill, wipe ring band.
- **Contour Slate** (night-line): the neatline on night sheets.
- **Night Faint Rule** (night-rule-soft): row separators on the control page.
- **Night Ink** (night-ink): text on navy, pins at rest.
- **Night Slate** (night-muted): secondary text, margin ticks and coordinates on the map.

### Signal
- **Survey Red** (danger): form errors only.

### Named Rules
**The Plans-and-Pointer Rule.** Orange marks LOG's plans, the 3D city, the active state and the title block's top rule. Nothing else turns orange at rest. Body text and large fills are never orange.

**The One-Edition Rule.** A sheet is day or night, never both. The night edition is applied by swapping tokens (the control page redefines paper, ink, slate and rule to their night values), not by painting individual components.

**The Pinned City Rule.** The 3D city stays flat brand orange because PRODUCT.md binds "navy plate, orange buildings". On the map, LOG's plans are distinguished by their paper-edged plates and the trig-point pins, and the cartouche's map key explains both.

## Typography

**Display Font:** Ploni Tzar (with Ploni, system-ui)
**Body Font:** Ploni (with system-ui)

**Character:** Ploni Tzar is the lettering of the sheet: titles, labels, legend captions, figures, buttons. Ploni is the running text. Both are licensed and self-hosted.

### Hierarchy
- **Display** (700, clamp(2.5rem, 5.4vw, 5.25rem), 1.08, -0.035em): page and sheet titles. Project covers push to clamp(2.75rem, 7vw, 6rem) at 0.98.
- **Headline** (500, clamp(2rem, 3.6vw, 3.25rem), 1.08): section titles, max 18ch.
- **Title** (500, 1.375rem): tile titles, process steps, list items.
- **Figure** (500, clamp(2.25rem, 4.5vw, 3.75rem), 1, tabular): legend numbers in number strips and project facts; the cartouche legend runs at clamp(1.75rem, 2.6vw, 2.5rem).
- **Body** (400, 1.0625rem, 1.6): default text. Prose runs at 1.1875rem / 1.65, max 62ch; ledes at clamp(1.125rem, 1.6vw, 1.375rem), max 52ch, in slate.
- **Label** (Ploni Tzar 500, 0.8125rem, tabular): sheet labels, cartouche head, filter captions, tile meta, table heads. Never uppercase, never letterspaced as a kicker.

### Named Rules
**The Tabular Rule.** Every figure, count, year and coordinate is set with tabular numerals.

**The Surveyor's Direction Rule.** Grid references (easting / northing) and margin coordinates are isolated left to right inside RTL text, as surveyors write them.

## Layout

A centred sheet of max 1320px with a fluid gutter (clamp(16px, 4vw, 56px)). Sections are separated by one full-width hairline at their top edge (the neatline), with vertical padding of clamp(44px, 5.6vw, 84px). Inner layouts are asymmetric two-column splits (7fr / 4fr or 7fr / 5fr) that collapse to one column under 860-900px.

The page header is a title block: title, lede and meta on the start side, the sheet label pinned to the end column, a hairline under the whole block. Legend strips (numbers, values, roles) divide their cells with vertical hairlines at the inline start, not with gaps or cards.

The home sheet is full-viewport: the navy map inside a neatline frame inset by the gutter, its top edge sitting on the header rule, with margin ticks every 72px. The cartouche anchors bottom start at about 48% width; map controls sit in the bottom end margin. On phones the cartouche spans the bottom edge, filters open as a bottom sheet, and the twin doors move to a fixed bar at the bottom.

## Elevation & Depth

Day sheets are flat: structure is hairlines and tonal paper steps. Elevation exists only for paper sheets lying over the night map or over the page: the cartouche, the project card, the list drawer, the apply dialog, the cookie banner and the map popup. All of them use one deep soft shadow cast downward by a single light above. Pins carry a small drop shadow onto the map plate.

### Shadow Vocabulary
- **Sheet lift** (`box-shadow: 0 30px 60px -20px rgba(5,10,16,.65)`): paper sheets over the map (cartouche, drawer). The project card adds a contact shadow `0 4px 12px rgba(5,10,16,.3)`; the dialog runs at 0 40px 80px -20px.
- **Pin drop** (`filter: drop-shadow(0 4px 6px rgba(5,10,16,.55))`): trig-point pins on the map.
- **Active underline** (`box-shadow: inset 0 -3px 0 #f7931d`): focus and active state on fields, view toggles and twin doors. This is a state mark, not elevation.

### Named Rules
**The Paper-Over-Night Rule.** Only paper sheets that float over the night map or the page cast a shadow, and always downward. Day-edition content never lifts.

## Shapes

Square corners throughout (radius 0, also forced on MapLibre controls and popups). Circles are reserved for survey marks: the stage row's "now" dot, the numbered survey station, cluster pins, edge counters, timeline dots, and the placeholder logo ring. Triangles are the trig-point mark: map pins, the map key, the "show on map" link on tiles, and partner bullets. Image wells are framed by a 1px rule outline offset 5-6px, a plate inside its border, which turns orange on hover.

Icons are one authored hairline set (1.4 stroke) used as CSS masks so they take the current color: the forward arrow (points left in RTL) and the caret. Swatches in legend rows are hatched with ink line patterns (diagonal, horizontal, dotted, cross-hatch, vertical, heavy diagonal) in rotation.

## Components

### Buttons
Squared, ruled, quiet until touched.
- **Shape:** square corners (0), 1px border in currentColor.
- **Primary:** ink fill, paper text, Ploni Tzar 500 1rem, padding 0.85em 1.5em. On the night edition the fill inverts to night ink with navy text.
- **Ghost:** transparent with a faint rule border.
- **Hover / Focus:** a 3px orange bar wipes in along the bottom edge from the start side (0.45s, the ease-out curve). Focus ring is 2px orange, offset 3px.
- **Text links with arrow:** display face, an orange 2px underline grows from the start side on hover, and the masked arrow nudges 4px forward.

### Tabs and filter chips
- **Style:** display face 1rem, slate text, no box; a 3px bottom border sits on the row's hairline.
- **State:** active is ink text with an orange 3px bottom border.

### Inputs / Fields
- **Style:** field paper fill, 1px slate rule border, square, label above in display face.
- **Focus:** border goes to ink and an inset 3px orange underline appears; no glow.
- **Error:** border and underline in survey red, hint text below.

### Navigation
Header on the paper ground with a hairline under it, display-face links with a 2px orange underline that wipes in on hover and stays on the current page. Over a cover (home map, project) the header is transparent with night-ink text and a translucent rule. The twin doors (project management / control company) are a two-cell strip divided by a hairline; the active door gets the orange inset underline. On phones the links scroll in a row under the logo and the doors sit fixed at the bottom.

### Neatline
The sheet's border rule. On day pages and night sections it is a plain 1px hairline at the top of each section and under each page header; it draws in once from the start side as the sheet enters. Ticks every 72px and live ITM values appear only on the home map frame, where the top margin reads easting and the side margin reads northing at the camera's ground point, and the corner readout gives the centre's grid reference.

### Cartouche
The title block of the home sheet: paper ground with a 4px orange top rule, a head line in label type (sheet number and name, plus the map key), the display title, the search field, three filters as hairline-divided cells, the live legend of counts, and a full-width "all projects" link row. It settles upward from an already-visible state on load.

### Map key and legend rows
The map key reads the sheet's marks: an ink triangle for a project pin, a translucent orange plate with an ink edge for a LOG plan. Legend rows (practice areas) are full-width rows divided by rules: a hatched ink swatch, the name in display face, one line in slate, and a live count with an arrow. Hover fills the row with field paper, indents it 1rem, and turns the swatch border orange.

### Sheet label
The sheet's number in the title block's end column: a large light numeral (Ploni Tzar 300, 2.25rem, two digits, "—" when unnumbered) over "גיליון · name" in label type, with an optional extra line (a project count, a job number). Home is sheet 00.

### Stage row
Stage shown as one unbroken line of four equal steps with tick marks at each boundary. A 3px orange fill runs to the current step and ends in an 11px orange "now" dot ringed in the ground color; the text label sits below. Completed projects fill the whole line in ink. On the project cover the track turns pale and the done fill goes night ink.

### Survey station
Process steps are numbered survey stations: a 2.25rem circle with a 1px rule and a tabular numeral, sitting on one horizontal rule that runs across the four steps (vertical on phones).

### Trig-point pins
All pins are identical: a night-ink triangle with a navy centre dot and a soft drop shadow. Hover, focus and the active pin scale to 1.25 and turn orange. Clusters are navy circles with a night-ink ring and a tabular count; edge counters are small navy circles with an arrowhead pointing off the sheet.

### Project tile
A plate image in a ruled outline offset 5px (orange on hover, image scales 1.04 over 1s). Above the title, a meta line in label type carries the practice area at the start and the grid reference at the end. Then the title, place, the stage row and a figure in tabular display numerals. Tiles without photography print a night plate.

### Sheet index
An SVG of the national ITM grid at 20km spacing in faint rule, with northing and easting labels, a few reference cities in ink, and every LOG project as an orange dot with an ink edge. Hovering enlarges the dots and turns the frame orange.

### Night sheet
The night edition material: navy ground, the contour-line SVG laid over it, contour-slate neatline, night-ink and night-slate text. Used by the home map, the control section on the home page, the control company's whole page, and the footer.

## Do's and Don'ts

### Do:
- **Do** build structure from 1px hairlines (rule for neatlines, rule-soft for row separators) and keep every corner square.
- **Do** set every figure, count and coordinate in tabular numerals, and isolate grid references left to right.
- **Do** switch a whole world to the night edition by redefining paper, ink, slate and rule, as the control page does.
- **Do** mark active and hover states with orange: an underline, bottom bar, or inset 3px rule.
- **Do** give every project its grid reference on its tile, card and cover.
- **Do** use GSAP with an exponential ease-out (expo.out; CSS cubic-bezier(.16, 1, .3, 1)): neatlines draw in once per sheet, title blocks settle upward 12-28px from an already-visible 0.25-0.35 opacity, figures count up once from 70% of their value. Reduced motion removes movement and never hides content.
- **Do** swap tokens in one place when TwoPointFour's visual language lands: the :root block of src/styles/main.css, the night redefinition on body.page-template-page-control, the C palette in src/hero/scene.js, and the stroke color inside assets/img/contours-night.svg.

### Don't:
- **Don't** use orange for body text, large fills or decoration at rest; it belongs to LOG's plans, the city, the active state and the title block's top rule.
- **Don't** mix the editions inside one sheet, or put the control company's content on the day edition.
- **Don't** add rounded cards, pill buttons or boxed feature grids; legend rows and hairline-divided strips do that job.
- **Don't** put ticks or coordinate values on day-page neatlines; live ticks belong to the home map frame.
- **Don't** add glow to the orange city or the pins; the city is printed flat in brand orange.
- **Don't** cast shadows from day-edition content; only paper sheets over the map or page lift.
- **Don't** add icons outside the authored arrow and caret masks, and don't use text glyphs as icons.
