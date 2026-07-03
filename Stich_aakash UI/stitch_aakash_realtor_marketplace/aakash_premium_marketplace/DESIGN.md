---
name: Aakash Premium Marketplace
colors:
  surface: '#fef9f1'
  surface-dim: '#ded9d2'
  surface-bright: '#fef9f1'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f8f3eb'
  surface-container: '#f2ede5'
  surface-container-high: '#ece8e0'
  surface-container-highest: '#e7e2da'
  on-surface: '#1d1c17'
  on-surface-variant: '#4d4540'
  inverse-surface: '#32302b'
  inverse-on-surface: '#f5f0e8'
  outline: '#7e7570'
  outline-variant: '#d0c4be'
  surface-tint: '#625d5b'
  primary: '#000000'
  on-primary: '#ffffff'
  primary-container: '#1e1b19'
  on-primary-container: '#89837f'
  inverse-primary: '#ccc5c1'
  secondary: '#775928'
  on-secondary: '#ffffff'
  secondary-container: '#ffd79b'
  on-secondary-container: '#7a5c2b'
  tertiary: '#000000'
  on-tertiary: '#ffffff'
  tertiary-container: '#042018'
  on-tertiary-container: '#6d8a7e'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#e9e1dd'
  primary-fixed-dim: '#ccc5c1'
  on-primary-fixed: '#1e1b19'
  on-primary-fixed-variant: '#4a4643'
  secondary-fixed: '#ffdeae'
  secondary-fixed-dim: '#e8c086'
  on-secondary-fixed: '#281800'
  on-secondary-fixed-variant: '#5d4213'
  tertiary-fixed: '#cbe9dc'
  tertiary-fixed-dim: '#afcdc0'
  on-tertiary-fixed: '#042018'
  on-tertiary-fixed-variant: '#314c42'
  background: '#fef9f1'
  on-background: '#1d1c17'
  surface-variant: '#e7e2da'
typography:
  display-lg:
    fontFamily: ebGaramond
    fontSize: 64px
    fontWeight: '400'
    lineHeight: 72px
    letterSpacing: -0.02em
  display-lg-mobile:
    fontFamily: ebGaramond
    fontSize: 40px
    fontWeight: '400'
    lineHeight: 48px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: ebGaramond
    fontSize: 32px
    fontWeight: '500'
    lineHeight: 40px
  headline-sm:
    fontFamily: ebGaramond
    fontSize: 24px
    fontWeight: '500'
    lineHeight: 32px
  body-lg:
    fontFamily: hankenGrotesk
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: hankenGrotesk
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-caps:
    fontFamily: hankenGrotesk
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.1em
  technical-data:
    fontFamily: hankenGrotesk
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
spacing:
  base: 8px
  section-gap: 80px
  content-gap: 32px
  gutter: 24px
  margin-desktop: 64px
  margin-mobile: 20px
---

## Brand & Style

The brand identity is built on the philosophy of "Trust as Luxury." This design system eschews the loud, decorative tropes of traditional luxury in favor of an editorial, restrained aesthetic reminiscent of high-end real estate journals. The target audience comprises high-net-worth individuals and institutional investors seeking clarity, discretion, and heritage.

The visual style is **Minimalist with a Corporate backbone**. It prioritizes high-fidelity architectural photography and structural order. Every element serves to facilitate a calm, confident browsing experience where whitespace is used as a deliberate design tool to signal premium positioning. The interface should feel like a physical printed catalog—tactile in its layout logic but digital in its efficiency.

## Colors

The palette is grounded in warm, organic tones that evoke the natural materials of high-end architecture—stone, wood, and metal.

- **Backgrounds:** The primary canvas is a warm off-white (#F6F1E9), providing a softer, more sophisticated contrast than pure white. Deep ink-navy (#0E1720) is reserved for immersive, full-width "dark mode" sections or footer containers to ground the layout.
- **Typography:** Charcoal (#161311) is the core text color, ensuring high legibility without the harshness of pure black.
- **Accents:** Muted Brass (#B08D57) is used exclusively for functional "Verified" markers and active selection states. Deep Forest Green (#2F4A40) is utilized for status indicators and trust-building technical details (e.g., "Clear Title").
- **Structure:** Hairlines (#DED5C6) provide subtle boundaries between content blocks, maintaining the grid without adding visual weight.

## Typography

The typographic hierarchy relies on the contrast between an intellectual, classical serif and a sharp, contemporary grotesque.

**Headlines & Display:** `ebGaramond` is the voice of the brand. It should be used for property titles, editorial section headers, and quotes. Its high-contrast strokes and elegant apertures provide an immediate sense of luxury and history.

**Body & Technical Data:** `hankenGrotesk` provides a functional counterpoint. Its clean, open apertures make it ideal for reading property descriptions and technical land specifications. 

**Bilingual Usage:** For Nepali script (Devanagari), use the closest serif equivalent to `ebGaramond` to maintain the editorial tone. All English labels for units like *Ropani*, *Aana*, *Paisa*, and *Dam* should use the `technical-data` style in `hankenGrotesk` to emphasize precision.

## Layout & Spacing

This design system utilizes a **Fixed Grid** model for desktop to ensure a controlled editorial experience. 

- **Desktop (1440px+):** A 12-column grid with a 1120px max-width container. 64px outer margins and 24px gutters.
- **Mobile (375px+):** A 4-column grid with 20px outer margins and 16px gutters.

The spacing rhythm is "generous." Section gaps of 80px (10 units) are used to separate major content themes, preventing the interface from feeling crowded. Property cards and technical data tables use a tighter 32px (4 units) gap to maintain proximity between related details. Vertical rhythm is strictly enforced via the 8px base unit.

## Elevation & Depth

Depth is conveyed through **Tonal Layers** and **Low-contrast Outlines** rather than shadows. 

- **Level 0 (Surface):** The primary off-white background (#F6F1E9).
- **Level 1 (Cards/Containers):** Elements are defined by a 1px solid hairline (#DED5C6). No box-shadows are used.
- **Interaction:** On hover, a container may shift its background color slightly to a deeper cream or present a 1px solid charcoal border to indicate focus. 

This flat, structured approach ensures that the focus remains on the property images and technical data, reinforcing the "trust" aspect of the brand by being transparent and unadorned.

## Shapes

The shape language is **Sharp (0px)**. 

Every UI element—from primary buttons to property image containers—uses 90-degree corners. This architectural sharpness communicates precision, stability, and high-end construction. Avoid all rounded corners (radius: 0) to maintain the editorial aesthetic. The only exception is for circular icons where a geometric primitive is required (e.g., status dots).

## Components

**Buttons:** 
- *Primary:* Solid Charcoal (#161311) with Off-white text. Sharp corners. Label-caps style.
- *Secondary:* Outlined Brass (#B08D57) with 1px border. 
- *Text-only:* Charcoal with a simple 1px underline that appears on hover.

**Property Cards:** 
Minimalist containers with no shadow. The image is the hero (16:9 or 4:3 aspect ratio). Below the image, the property title (Serif) is followed by a technical grid showing Land Area (e.g., 1-2-0-0 Ropani), Price, and Road Access in a structured table format using `hankenGrotesk`.

**Technical Specs (Nepal-Native):** 
A dedicated "Data Table" component for land measurement. It must clearly separate Ropani-Aana-Paisa-Dam using vertical hairlines. Road access must be categorized by type (Blacktopped, Graveled, Soil) and width (in feet) using high-contrast typography.

**Inputs:** 
Underlined fields rather than boxed ones. A 1px hairline bottom border that turns Charcoal when active. This mimics the appearance of a high-end registration form.

**Chips/Tags:** 
Used for "BS Date" (Bikram Sambat) and "Status." These are flat rectangles with a light warm-gray background (#DED5C6) and technical-data typography.