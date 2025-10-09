# Changelog

## 3.47.22 - 2025-10-23
- Tightened the Configuration Tools reset card grid to use a shrinkable single column on mobile while stretching content within the card.
- Ensured the widescreen breakpoint still places the reset button beside its description by updating the responsive grid override.
- Added explicit overflow wrapping for reset option labels so long translations break cleanly within the card on narrow viewports.
- Bumped the plugin metadata and admin stylesheet banner to 3.47.22 to record the responsive reset card refinement.

## 3.47.21 - 2025-10-22
- Updated the reset option checkbox label layout to use a wrapping flex container so long translations stay inside the card on narrow viewports.
- Let the checkbox description span flex within the label to wrap naturally without overlapping or clipping adjacent controls.
- Bumped the plugin metadata and admin stylesheet banner to 3.47.21 to record the responsive Configuration Tools refinement.

## 3.47.20 - 2025-10-21
- Expanded the Configuration Tools cards so reset, migration, and optimization boxes retain full-width content until widescreen breakpoints, preventing text and buttons from clipping on medium displays.
- Enlarged the migration action column with a responsive clamp so URLs, inputs, and buttons have room to render without wrapping labels.
- Bumped the plugin metadata and admin stylesheet banner to 3.47.20 to document the layout widening.

## 3.47.19 - 2025-10-20
- Rebuilt the Configuration Tools cards with responsive grid layouts so text, buttons, and checkboxes stack cleanly on phones while stretching controls to full width.
- Added adaptive desktop breakpoints that restore side-by-side alignment for reset and optimization actions without sacrificing the improved mobile flow.
- Bumped the plugin metadata and admin stylesheet banner to 3.47.19 to document the responsive Configuration Tools polish.

## 3.47.18 - 2025-10-19
- Standardised admin action buttons with shared padding across default, small, and large sizes so controls align with equal heights in the scanner.
- Updated the quick filter pills and secondary buttons to keep labels centred while enforcing a consistent minimum height.
- Restored accessible contrast for primary buttons by forcing white text on the blue gradient background and documenting the visual polish release.

## 3.47.17 - 2025-10-18
- Stacked dashboard card action controls vertically on ≤782 px viewports so dropdowns and buttons span the available width without clipping.
- Reduced the mobile control height and padding to deliver compact, thumb-friendly tap targets inside circular action styles.
- Bumped the plugin metadata and admin stylesheet banner to 3.47.17 to document the responsive layout refinement.

## 3.47.16 - 2025-10-17
- Normalised the quick filter button group with consistent flex sizing so pills share equal widths and wrap cleanly across breakpoints.
- Increased the pill padding and centred labels to maintain a comfortable tap target within the new fixed sizing.
- Bumped the plugin metadata and admin stylesheet banner to 3.47.16 to document the responsive filter polish.

## 3.47.15 - 2025-10-16
- Reworked the scanner action rows on phones so filters and export controls stack vertically with full-width alignment and equal spacing.
- Converted the quick filter pill group to a responsive grid at ≤782 px to ensure buttons wrap evenly without clipping.
- Bumped the plugin metadata and admin stylesheet banner to 3.47.15 for the responsive action layout release.

## 3.47.14 - 2025-10-15
- Added horizontal scrolling support to the scan results table with min-width guards so columns stay readable on narrow viewports.
- Tuned table spacing and focus outlines to improve accessibility when navigating the scan results via keyboard or touch on mobile devices.
- Bumped the plugin metadata and admin stylesheet banner to 3.47.14 to capture the responsive enhancement release.

## 3.47.13 - 2025-10-14
- Trimmed the mobile padding and gaps inside the Data Management tools card so the export/import columns collapse without huge whitespace on phones.
- Forced the export and import option groups to stack as a grid on mobile while stretching selects and date/time inputs to full width for consistent visibility.
- Bumped the plugin metadata and admin stylesheet banner to 3.47.13 to document the responsive polish.

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
