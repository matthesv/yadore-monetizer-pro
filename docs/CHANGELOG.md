# Changelog

## 3.47.12 - 2025-10-13
- Expanded the tools container and `.regular-text` input styling so every field stretches fluidly with its card, preventing overflow and keeping the layout responsive down to 400 px wide viewports.
- Verified the WordPress admin Tools screen at mobile widths to confirm cards remain within the viewport without horizontal scrolling.
- Bumped the plugin metadata and bundled CSS banner to 3.47.12 to track the responsive fix release.

## 3.47.11 - 2025-10-12
- Harmonised the admin hero headline/grid behaviour so icons and long titles wrap gracefully on tablets and phones instead of forcing cramped two-column layouts.
- Tightened the hero meta cards and CTA alignment to remove uneven spacing and keep action rows vertically centred.
- Reflowed the scanner onboarding block with responsive stacking, consistent gaps, and stretch alignment for cards, preventing clipped content on smaller breakpoints.

## 3.47.10 - 2025-10-11
- Added `box-sizing: border-box` and enforced full-width layout on `.yadore-admin-wrap` to keep new inline paddings inside the viewport and preserve equal gutters for hero/meta cards down to 400 px.
- Rebuilt admin CSS/JS bundles so dashboards immediately consume the refreshed spacing tokens for this release.

## 3.47.9 - 2025-10-10
- Adjusted the hero meta grid to clamp card column widths on narrow viewports, keeping dashboard cards consistent on mobile.
- Updated bundled assets to report version 3.47.9 across the admin UI resources.

## 3.47.8 - 2025-10-09
- Added the `.yadore-card-grid` utility class with shared CSS variables for spacing, padding, and responsive minimum widths.
- Refactored admin templates to adopt the shared grid utility and ensure consistent card layouts across dashboard, analytics, scanner, debug, tools, and settings pages.
- Verified responsive behaviour for desktop, tablet, and mobile breakpoints to keep card alignments stable regardless of content.
