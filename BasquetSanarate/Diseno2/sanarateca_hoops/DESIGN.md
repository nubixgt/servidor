---
name: Sanarateca Hoops
colors:
  surface: '#f8f9fa'
  surface-dim: '#d9dadb'
  surface-bright: '#f8f9fa'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f3f4f5'
  surface-container: '#edeeef'
  surface-container-high: '#e7e8e9'
  surface-container-highest: '#e1e3e4'
  on-surface: '#191c1d'
  on-surface-variant: '#444933'
  inverse-surface: '#2e3132'
  inverse-on-surface: '#f0f1f2'
  outline: '#747a60'
  outline-variant: '#c4c9ac'
  surface-tint: '#506600'
  primary: '#506600'
  on-primary: '#ffffff'
  primary-container: '#ccff00'
  on-primary-container: '#5b7300'
  inverse-primary: '#abd600'
  secondary: '#565e74'
  on-secondary: '#ffffff'
  secondary-container: '#dae2fd'
  on-secondary-container: '#5c647a'
  tertiary: '#416900'
  on-tertiary: '#ffffff'
  tertiary-container: '#c2ff75'
  on-tertiary-container: '#4a7700'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#c3f400'
  primary-fixed-dim: '#abd600'
  on-primary-fixed: '#161e00'
  on-primary-fixed-variant: '#3c4d00'
  secondary-fixed: '#dae2fd'
  secondary-fixed-dim: '#bec6e0'
  on-secondary-fixed: '#131b2e'
  on-secondary-fixed-variant: '#3f465c'
  tertiary-fixed: '#acf847'
  tertiary-fixed-dim: '#91db2a'
  on-tertiary-fixed: '#102000'
  on-tertiary-fixed-variant: '#304f00'
  background: '#f8f9fa'
  on-background: '#191c1d'
  surface-variant: '#e1e3e4'
typography:
  display-hero:
    fontFamily: oswald
    fontSize: 56px
    fontWeight: '700'
    lineHeight: 60px
    letterSpacing: -0.02em
  display-hero-mobile:
    fontFamily: oswald
    fontSize: 40px
    fontWeight: '700'
    lineHeight: 44px
    letterSpacing: -0.01em
  headline-xl:
    fontFamily: oswald
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 42px
    letterSpacing: -0.01em
  headline-lg:
    fontFamily: oswald
    fontSize: 28px
    fontWeight: '600'
    lineHeight: 34px
    letterSpacing: 0em
  headline-md:
    fontFamily: oswald
    fontSize: 22px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: 0em
  stat-display:
    fontFamily: oswald
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 48px
    letterSpacing: -0.02em
  body-lg:
    fontFamily: plusJakartaSans
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: plusJakartaSans
    fontSize: 15px
    fontWeight: '400'
    lineHeight: 22px
  body-sm:
    fontFamily: plusJakartaSans
    fontSize: 13px
    fontWeight: '500'
    lineHeight: 18px
  label-pill:
    fontFamily: plusJakartaSans
    fontSize: 13px
    fontWeight: '700'
    lineHeight: 16px
    letterSpacing: 0.02em
  label-meta:
    fontFamily: plusJakartaSans
    fontSize: 11px
    fontWeight: '600'
    lineHeight: 14px
    letterSpacing: 0.05em
rounded:
  sm: 0.5rem
  DEFAULT: 1rem
  md: 1.5rem
  lg: 2rem
  xl: 3rem
  full: 9999px
spacing:
  space-2xs: 0.25rem
  space-xs: 0.5rem
  space-sm: 0.75rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
  space-2xl: 3rem
  space-3xl: 4.5rem
  gutter-mobile: 1rem
  gutter-desktop: 1.5rem
  container-max: 1280px
---

## Brand & Style

This design system drives a modern, high-energy basketball league platform tailored for amateur athletes, fans, and local communities. It bridges the raw, authentic grit of grassroots streetball with the clean, elite aesthetic of premier sportswear brands.

The visual direction combines crisp, light minimalism with electric sportswear accents:
- **Atmosphere:** Clean, aspirational, athletic, and accessible.
- **Audience:** Players, coaches, scouts, and community supporters who seek quick access to game fixtures, box scores, rosters, standings, and highlights.
- **Core Aesthetic:** Light-dominant surfaces grounded by deep athletic charcoal, punched through with hyper-saturated neon lime/volt accents (#D4FF32). Large cutout athlete photography, bold condensed typography, pill-shaped tags, and deeply rounded organic containers define the experience.

## Colors

The palette leverages high-contrast sport dynamics. Bright energetic volt anchors focal points while deep obsidian provides weight and contrast.

### Palette Architecture
- **Primary (Volt / Neon Lime - `#CCFF00` / `#D4FF32`):** Reserved for primary interactive triggers (CTA buttons, active filter capsules, live badges, and highlight markers).
- **Secondary (Dark Sport Obsidian - `#0F172A` / `#111827`):** Powers dense navigation bars, primary headlines, high-impact score headers, and contrast containers.
- **Tertiary (Electric Green - `#84CC16`):** Supporting sports accent for state shifts, success indicators, and secondary metrics.
- **Neutral Foundations:**
  - Base Background: Soft Canvas `#F8F9FA`
  - Elevated Container Surface: Frost White `#FFFFFF` and Muted Cloud `#F1F3F5`
  - Borders & Dividers: Subtle Slate `#E2E8F0`
  - Neutral Body Text: Neutral Slate `#475569`
  - High Contrast Text: Ink Black `#0B0F19`

### Dark Contrast Anchors
The top navigation header and key hero banners deploy `#0F172A` with `#CCFF00` detailing to establish athletic authority and visual balance against the light canvas.

## Typography

The type system blends athletic power with UI clarity:
- **Display & Headlines (`oswald`):** Condensed, bold, architectural proportions engineered for game scores, player names, and bold banner claims. Uppercase styling is recommended for primary category tags, match status (e.g. `LIVE`, `FINAL`), and giant background watermark numbers.
- **Body & Controls (`plusJakartaSans`):** Modern geometric sans offering open counters and friendly clarity. Ensures schedules, player bios, box score data tables, and forms remain readable across all device viewports.
- **Statistical Hierarchy:** Numbers take center stage using `stat-display`, paired with uppercase, low-contrast `label-meta` sub-captions (e.g., `PPG`, `WIN RATE`, `REB`).

## Layout & Spacing

The layout model implements a structured 12-column responsive fluid grid inside a 1280px container boundary, coupled with generous internal card padding to mimic the soft, spacious mobile cards in the design reference.

### Breakpoints & Adaptive Rules
- **Mobile (0 - 639px):** 4-column layout, 16px margins, 12px gutters. Full-width horizontal swipe decks for live scores, active tournaments, and roster highlights.
- **Tablet (640px - 1023px):** 8-column layout, 24px margins, 16px gutters. Dual-card grids for match previews and split player profile headers.
- **Desktop (1024px+):** 12-column layout, 32px margins, 24px gutters. Asymmetric cards allowing hero cutouts to overlap headers and stat tiles.

### Rhythm Principles
- Use 16px-24px internal card padding to provide a spacious stadium feel.
- Negative margins and absolute positioning allow player cutouts to gracefully bleed outside card frames, reinforcing athletic energy.

## Elevation & Depth

Depth is soft, airy, and tactile. Instead of harsh corporate drop shadows, this design system uses ambient multi-stop blurs with slight warm undertones to float rounded cards above the `#F8F9FA` ground.

### Layering Rules
- **Base Level (`Surface-0`):** Soft Canvas `#F8F9FA` with occasional giant, low-opacity typographic watermarks (`#00000008` or `#D4FF3215`).
- **Standard Card Surface (`Surface-1`):** Solid `#FFFFFF` or `#F1F3F5` with ambient shadow: `0px 10px 30px -4px rgba(15, 23, 42, 0.05), 0px 4px 10px -2px rgba(15, 23, 42, 0.02)`.
- **Floating Controls & Action Buttons (`Surface-2`):** Primary action buttons and pill controls feature a gentle luminous lift: `0px 8px 24px -4px rgba(204, 255, 0, 0.45)`.
- **Top Bar / Header Overlay (`Surface-Inverse`):** `#0F172A` with `backdrop-filter: blur(12px)` and 90% opacity, framed with a razor-thin border `rgba(255, 255, 255, 0.08)`.

## Shapes

The design system embraces an ultra-rounded, friendly, yet high-tech pill-and-capsule geometry. 

- **Primary Cards & Containers:** Standardized with `rounded-3xl` (24px to 32px corner radius), producing organic, pebble-like surfaces.
- **Buttons, Tags, and Tabs:** Pure full-radius capsules (`rounded-full` / 9999px).
- **Stat Blocks & Mini Cards:** `rounded-2xl` (16px to 20px) to balance high information density with playful geometry.
- **Player Cutout Avatars:** Framed in continuous squircle or pill contours with generous internal padding.

## Components

### Buttons & Interactive Controls
- **Primary CTA:** Capsule pill (`rounded-full`), filled with `#CCFF00`, bold dark charcoal text (`#0F172A`), active glow hover, and uppercase/bold label typography.
- **Secondary Button:** Solid `#0F172A` background with crisp white or volt text.
- **Ghost Pill:** Light gray surface (`#F1F3F5`), slate text, smooth transition to volt fill on hover or selection.
- **Icon Actions:** 44px round or squircle circular buttons with soft off-white background and charcoal iconography.

### Filter Pills & Chips
- Horizontal scrolling segmented chips.
- Unselected: Light gray `#F1F3F5` background, `#475569` text.
- Active: Bright volt `#CCFF00` fill with solid `#0F172A` text, no border.

### Match & Score Cards
- Deeply rounded corners (28px - 32px), off-white `#FFFFFF` or `#F1F3F5` surface.
- Dual team badge arrangement with a central volt pill containing the match clock or status (`LIVE`, `18:30 PM`, `FINAL`).
- Team names in condensed uppercase `oswald` with bold numeric scores.

### Player & Team Profile Cards
- Soft neutral background containing dynamic player photo cutouts extending above the card frame.
- Embedded stat tiles (`Surface-1`) displaying key metrics (e.g. 84% Win Rate, 24.5 PPG) with huge `oswald` numbers and muted secondary labels.
- Pill badge for position and team classification.

### Form Inputs & Search
- Completely rounded capsule text fields (`rounded-full`) filled with `#F1F3F5`, subtle slate border on focus, with volt accent focus ring.

### Header / Navbar
- Dark sport obsidian (`#0F172A`) header with sharp neon lime indicator dots, high-contrast white navigational links, and a prominent volt "Registrarse / Unirse" pill button.