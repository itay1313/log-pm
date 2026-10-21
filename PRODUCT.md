# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Five audiences, given equal weight (confirmed by Itay, 2026-10-21):

- **Local authorities and planning bodies** checking relevant experience, scale and reliability. They look at the map, projects, the control company, clients and partners.
- **Government ministries** checking who manages the national urban-renewal knowledge and how control works. They go to the control company.
- **Developers and landowners** looking for similar projects and how LOG works. They go to projects, project pages and contact.
- **Job candidates**, who want culture, people, open jobs and the hiring process. They go to careers, job pages and about. A job page is often reached from a direct link (WhatsApp, LinkedIn, Google Jobs).
- **Press and professionals** looking for updates, data and knowledge. They go to the knowledge center and contact.

## Product Purpose

The corporate website of לוג ניהול פרויקטים בע"מ (LOG Project Management). LOG manages and advances urban-planning processes at every scale, and is also the control company of the government authority for urban renewal (הרשות הממשלתית להתחדשות עירונית). The site should show this at a glance and give every visitor a short path to what they came for. Success: reputation (real projects, approved data, clients, named people), industry recognition (map and knowledge center), and recruitment (careers page and a sendable page per job).

## Positioning

Two separate worlds under one name: project management at the authority and compound level, and national control on behalf of the government authority. Each world has its own place, their data never mix, and switching between them is available from every page (the two twin buttons). The interactive map of LOG's own projects, built from LOG's ArcGIS data, is the opening of the site.

## Operating Context

- Hebrew only at launch, RTL. Templates and data are ready for English later (every text field supports Hebrew and English; templates don't assume a fixed direction).
- Launch: mid-December 2026.
- Spec: docs/spec-v6.pdf (TwoPointFour, version 6.0). It defines structure and behavior only; TwoPointFour will deliver the visual language separately, and the current redesign is a strong proposal expected to be adjusted to it later, so visual tokens must stay easy to swap.
- Content is managed by LOG in WordPress (custom theme `log`, plugin `log-core`, ACF). Project data syncs weekly from LOG's ArcGIS; publication is approved by Kobi.
- All page copy comes in a separate texts file from LOG for approval; current copy is fallback.

## Capabilities and Constraints

- Site tree: home (map first), projects list, project page (only for projects with a full page), control company, about, knowledge center and items, careers and job pages, contact, search, 404, legal pages.
- The map is a full first screen on the home page and an alternative view of the projects list, with the same behavior. Project popup, overlap picker, national-plans list, list view as an accessible alternative, Ctrl/⌘ + wheel zoom, edge counters.
- State lives in the address (search, filters, view, open popup), and back links return to the exact state the visitor left.
- Stage is shown as text: "שלב 2 מתוך 4 · תכנון סטטוטורי" or "הושלם".
- Applications only from a job page, in a dialog. Contact topics route to separate emails.
- Cookie consent with equal accept/decline; measurement only after consent.
- Performance: the first screen must appear fast; the map loads progressively; video loads only when on screen.

## Brand Commitments

Binding (confirmed by Itay, 2026-10-21):

- Fonts: Ploni (text) and Ploni Tzar (display), licensed, self-hosted in the theme.
- LOG orange #F7931D as the brand color.
- The dark navy 3D map as the home page opening (navy plate, orange buildings, white pins), with marseille.laphase5.com as the reference bar.
- The final LOG logo (file still to be supplied; the theme currently uses a placeholder ring wordmark).
- Tagline in use: "מתכננים. מחברים. מובילים."

## Evidence on Hand

- 99 real plans from LOG's ArcGIS layer (94 with geometry), with authority, stage, housing units and area. Square-meter data is not in the layer yet.
- No project photography, team photos, client logos, videos or approved texts yet. Do not fabricate testimonials, clients, numbers or quotes; leave clearly marked slots.

## Product Principles

- Map first: the work is the first thing a visitor sees.
- Two doors, always open: project management and the control company are reachable from everywhere and never blend.
- Shallow and no dead ends: three levels, every page leads somewhere, every item reachable from the home page or its area page.
- Return to where I left: state in the address, exact return.
- A link for everything: every project, job and knowledge item has an address worth sending.

## Accessibility & Inclusion

Israeli standard 5568 at level AA and the law: full keyboard use, screen readers, alt text, video captions, respect for reduced motion, and a list view as the alternative to the map.
