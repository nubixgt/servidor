---
name: Horus Premier Finance
colors:
  surface: '#131313'
  surface-dim: '#131313'
  surface-bright: '#393939'
  surface-container-lowest: '#0e0e0e'
  surface-container-low: '#1c1b1b'
  surface-container: '#20201f'
  surface-container-high: '#2a2a2a'
  surface-container-highest: '#353535'
  on-surface: '#e5e2e1'
  on-surface-variant: '#d1c5b4'
  inverse-surface: '#e5e2e1'
  inverse-on-surface: '#313030'
  outline: '#9a8f80'
  outline-variant: '#4e4639'
  surface-tint: '#e9c176'
  primary: '#e9c176'
  on-primary: '#412d00'
  primary-container: '#c5a059'
  on-primary-container: '#4e3700'
  inverse-primary: '#775a19'
  secondary: '#ebc078'
  on-secondary: '#422c00'
  secondary-container: '#614405'
  on-secondary-container: '#dbb26c'
  tertiary: '#b0c6f9'
  on-tertiary: '#173059'
  tertiary-container: '#8fa5d6'
  on-tertiary-container: '#233a65'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#ffdea5'
  primary-fixed-dim: '#e9c176'
  on-primary-fixed: '#261900'
  on-primary-fixed-variant: '#5d4201'
  secondary-fixed: '#ffdeaa'
  secondary-fixed-dim: '#ebc078'
  on-secondary-fixed: '#271900'
  on-secondary-fixed-variant: '#5e4203'
  tertiary-fixed: '#d8e2ff'
  tertiary-fixed-dim: '#b0c6f9'
  on-tertiary-fixed: '#001a41'
  on-tertiary-fixed-variant: '#304671'
  background: '#131313'
  on-background: '#e5e2e1'
  surface-variant: '#353535'
typography:
  display-lg:
    fontFamily: Libre Caslon Text
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Libre Caslon Text
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
  headline-lg-mobile:
    fontFamily: Libre Caslon Text
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  title-md:
    fontFamily: Hanken Grotesk
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: 0.01em
  body-lg:
    fontFamily: Hanken Grotesk
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-sm:
    fontFamily: Hanken Grotesk
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-caps:
    fontFamily: Hanken Grotesk
    fontSize: 12px
    fontWeight: '700'
    lineHeight: 16px
    letterSpacing: 0.1em
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  base: 8px
  container-padding-desktop: 40px
  container-padding-mobile: 20px
  gutter: 24px
  section-gap: 64px
---

## Brand & Style

The design system is engineered for a high-tier corporate financial ecosystem. It targets executive decision-makers and business owners who demand precision, security, and an aura of established prestige. The visual narrative combines the weight of traditional institutional finance with the agility of modern fintech.

The aesthetic follows a **Corporate / Modern** direction with **Minimalist** restraint. It leverages high-contrast elements—rich dark backgrounds against metallic accents—to create a "Black Label" experience. The atmosphere is quiet, authoritative, and sophisticated, avoiding unnecessary decorative elements to focus entirely on financial clarity and data integrity.

- **Tone:** Professional, elite, secure, and insightful.
- **Visual Influence:** Luxury automotive interfaces and premium banking portals.
- **Key Motifs:** Sharp typography, subtle metallic gradients, and structured data grids.

## Colors

The palette is rooted in a deep, nocturnal foundation to emphasize the metallic accents. 

- **Primary & Secondary:** A duo of Golds. "Ochre Gold" (#C5A059) acts as the primary interactive hit, while "Deep Bronze" (#8C6A2B) is used for secondary accents and subtle borders.
- **Neutrals:** The background uses "Midnight Onyx" (#0D0D0D), with "Carbon Gray" (#1A1A1A) and "Steel" (#333333) providing the necessary layers for containers and cards.
- **Semantic States:** Colors are desaturated and darkened to maintain the sophisticated mood:
    - **Success:** Emerald Green (#006450) – deep and calm.
    - **Error:** Terracotta Red (#B24C3D) – earthy and serious, avoiding "plastic" bright reds.
    - **Warning:** Amber (#D4A017) – a golden-yellow that harmonizes with the brand gold.

## Typography

This design system utilizes a sophisticated typographic pairing to balance heritage and utility.

- **Headlines:** **Libre Caslon Text** provides an editorial, trustworthy feel. It is used for page titles, large stats, and section headers. Its high-contrast serifs reflect the "Horus" wordmark's elegance.
- **Interface & Body:** **Hanken Grotesk** is chosen for its geometric precision and exceptional legibility in data-dense environments. It remains neutral, allowing the financial figures to be the focus.
- **Micro-copy:** Use `label-caps` for table headers and small metadata to maintain a structured, organized look.

## Layout & Spacing

The layout philosophy relies on a **Fixed Grid** for desktop to maintain a cinematic, centered feel, transitioning to a fluid model for tablet and mobile.

- **Desktop (1440px+):** 12-column grid with 40px margins and 24px gutters. Content is capped at 1280px width.
- **Tablet (768px - 1024px):** 8-column fluid grid with 32px margins.
- **Mobile (<768px):** 4-column fluid grid with 20px margins.

The spacing rhythm is generous. By utilizing a "base 8" system, the design system ensures plenty of whitespace between sections (`section-gap`), preventing the dark interface from feeling cramped or overwhelming.

## Elevation & Depth

Visual hierarchy in this design system is achieved through **Tonal Layers** and **Subtle Ambient Shadows**. 

1. **Base Level:** Midnight Onyx (#0D0D0D) serves as the "floor" of the application.
2. **Surface Level:** Cards and navigation rails use Carbon Gray (#1A1A1A).
3. **Elevated Level:** Modals and dropdowns use a slightly lighter gray (#252525) with a soft, diffused shadow (0px 8px 24px rgba(0,0,0,0.5)).
4. **Interactive Depth:** Buttons and active states utilize a subtle inner-glow effect or a 1px "Gold" top-border to simulate physical depth without looking skeuomorphic.

Avoid heavy blurs; maintain crisp edges to reinforce the feeling of "financial precision."

## Shapes

The shape language is disciplined. A **Soft (1)** roundedness level is applied to create a modern feel that isn't overly organic or "bubbly."

- **Primary Elements:** Buttons, input fields, and cards use a 4px (0.25rem) radius.
- **Large Containers:** Dashboard widgets and main content areas may use up to 8px (0.5rem) to soften the perimeter.
- **Icons:** Use sharp or slightly softened 2px corner icons to match the Hanken Grotesk font character.

## Components

- **Buttons:** 
    - *Primary:* Ochre Gold background with Carbon Gray text. No border.
    - *Secondary:* Transparent with 1px Ochre Gold border.
    - *Ghost:* Subtle Steel text, no border, turns Gold on hover.
- **Input Fields:** Dark background (#1A1A1A) with 1px Steel border. On focus, the border transitions to Ochre Gold with a very faint outer glow.
- **Cards:** No border, solid Carbon Gray background. Use 1px "hairline" dividers in Steel to separate internal card content.
- **Chips/Status:** Use a "tinted" approach. A Success chip has a dark Emerald background with a slightly lighter Emerald text. This maintains the "Dark Mode" integrity.
- **Data Visualization:** Charts should use a palette of Golds, Silvers, and the Semantic colors. Use thin line weights (1.5px) for sparklines and progress bars.
- **Lists:** High-density lists for transactions. Use alternating row subtle tints or 1px dividers to ensure horizontal tracking is effortless.