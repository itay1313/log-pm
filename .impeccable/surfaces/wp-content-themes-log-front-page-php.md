---
version: 1
slug: "wp-content-themes-log-front-page-php"
primary_target: "wp-content/themes/log/front-page.php"
related_targets: ["wp-content/themes/log/src/styles/main.css","wp-content/themes/log/archive-log_project.php","wp-content/themes/log/single-log_project.php"]
---

# Surface brief: LOG site (home first, then every template)

Scope: the whole public site, home first. Mode: Persuade on home, projects, control, about, careers; Read on knowledge items and project stories; Operate on search, forms and filters.
Audience: five audiences at equal weight (PRODUCT.md). Action on home: find a project on the map (search, filters, open a project) or step into one of the two worlds.
Constraints: binding Ploni / Ploni Tzar, orange #F7931D, navy 3D map opening, final logo pending (slot). Avoid: consultancy template, SaaS dark-mode glow, flashy motion over credibility, cold empty minimalism. Tokens must stay easy to swap when TwoPointFour's visual language lands.
Unresolved: logo file; real photography; texts file.

## Direction contract

THESIS: The site is one survey-sheet series of Israel. Every page is a sheet in the series, framed by a neatline whose margin carries real ITM grid references, and every project carries its own grid reference. It refuses the category default of a full-bleed render, centred headline and three service cards.

OWN-WORLD: Two editions of one sheet. Night edition (navy #13212f ground, contour lines in cold slate) for the map and the control company; day edition (survey paper #e8ece9, ink #1b1f25, slate rule #8a9590) for everything else. Orange #F7931D only for LOG's plans and the active state in the interface. Translation of a pinned commitment: the 3D map keeps its orange city (PRODUCT.md binds "navy plate, orange buildings"), printed flat in the brand orange with no glow; on the map LOG's plans read by their paper-edged plates and the trig-point pins, and the cartouche carries the key. Hairline neatlines, coordinate ticks, legend tables, scale bars, cartouche title blocks. Ploni Tzar display, Ploni text, tabular numerals for every figure and coordinate.

STORY: The visitor sees LOG's real projects across the country, understands two separate worlds (management and national control), believes the scale because every figure and coordinate is live data, and acts: searches, filters, opens a project, or steps into the control company.

FIRST VIEWPORT: The navy 3D map fills the sheet inside a neatline inset ~24px; the top and side margins carry ITM ticks that track the camera centre. Header sits on the top neatline. The cartouche, a survey-paper title block with an orange top rule, anchors the bottom start at ~44% width: sheet title line, H1 at display size, the search field, the three filters as cells, and the legend of live numbers with "לכל הפרויקטים". Scale bar, zoom, "כל הארץ" and the two list buttons sit in the bottom end margin.

FORM: Survey sheet series (Survey of Israel topographic sheets), position 4 on the ordered list; seed:da965731c5f12b42. Raises: stage as one unbroken four-step row with a "now" marker (drum machine); orange reserved for plans and active state (film); one long horizontal neatline per sheet (indoor sun); each world its own edition, never mixed (arcade); page changes as one orchestrated sheet transition (shader portal).
Signature interaction: live margin coordinates that follow the map camera, and every project's grid reference. Motion grammar: GSAP, exponential ease-out, neatlines draw in once per sheet, cartouche settles from an already-visible state; reduced motion removes movement, never content.

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
