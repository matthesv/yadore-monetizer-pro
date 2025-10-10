# Changelog

# 3.48.0 - 2025-11-07
- Added a seven-day rolling optimizer sync that fetches daily click and conversion reports, stores them idempotently, and links them by `clickId` with statistical fallbacks for reporting.
- Created dedicated reporting tables for raw clicks, conversions, and resolved matches, complete with loggable import metrics and historical sync summaries.
- Scheduled the new reconciliation routine for 03:30 UTC and bumped the plugin bundle metadata to 3.48.0.

## 3.47.40 - 2025-11-06
- Added currency code propagation to the analytics report so the admin dashboard knows when Euro-only revenue summaries are available.
- Filtered analytics revenue aggregations to ignore non-EUR conversions and return the Euro code for consistent formatting in charts and metrics.
- Updated the admin dashboard formatter to respect dynamic currency codes (defaulting to EUR) and refreshed bundle metadata to 3.47.40.

## 3.47.39 - 2025-11-05
- Harmonized the market list loader with the status checker by merging `markets` and wrapped `data` responses before caching, normalizing ISO codes, and preferring API names.
- Purged the cached `yadore_available_markets` list on deploy so the refreshed normalization can populate immediately after upgrades.
- Bumped the plugin metadata and bundled admin asset banners to 3.47.39 to capture the market availability fixes.

## 3.47.38 - 2025-11-04
- Rebuilt the Tools screen cards with unified headers, descriptions, and call-to-action footers so export, import, maintenance, and utility workflows stay consistent from 360px through desktop breakpoints.
- Refactored the configuration sections onto shared tool-option components, aligning reset, migration, and optimization actions with tokenized spacing while keeping inputs full-width on touch devices.
- Bumped the plugin metadata and bundled admin asset banners to 3.47.38 to capture the responsive Tools layout overhaul.

## 3.47.37 - 2025-11-03
- Broadened the admin hook detection so the tools assets initialize on every `yadore-tools` screen variant and continue to wire AJAX actions.

## 3.47.36 - 2025-11-03
- Converted the Tools cards to a responsive layout grid so action panels align in columns with consistent gaps on any screen width.
- Replaced margin-based spacing inside each tool section with tokenized grid gaps, clipped shadows, and hover elevations that stop overlapping neighbours.
- Bumped the plugin metadata and bundled asset banners to 3.47.36 to capture the Tools spacing and clipping fixes.

## 3.47.35 - 2025-11-02
- Left-aligned every Tools action group so export, import, maintenance, and cleanup buttons line up consistently while keeping their wrap behaviour on narrow screens.
- Updated the button flex rules to preserve tokenized gaps, prevent stretched maintenance controls, and keep focus indicators visible when controls stack.
- Bumped the plugin metadata and bundled asset banners to 3.47.35 to capture the Tools action alignment polish.

## 3.47.34 - 2025-11-01
- Restricted the admin tooling script to the Tools screen, namespaced its event handlers, and reset bindings before re-adding them so export, import, and maintenance actions fire exactly once per interaction.
- Hardened the import drop zone by guarding click/keyboard triggers, limiting file handling to real change/drop events, and converting action buttons to explicit `type="button"` controls to avoid implicit form submissions.
- Bumped the plugin metadata and bundled asset banners to 3.47.34 to capture the Tools request de-duplication fix.

## 3.47.33 - 2025-10-31
- Refactored the Tools page layout to run on the shared card grid utilities so export, import, maintenance, and utility sections stay visually separated with consistent headings and touch-friendly CTAs from 360px through widescreen breakpoints.
- Styled the import drop zone, option cards, and results panels with design-token spacing and borders, eliminating inline visibility hacks via a reusable JavaScript helper that toggles hidden states without breaking animations.
- Bumped the plugin metadata and bundled asset banners to 3.47.33 to record the responsive Tools experience overhaul.

## 3.47.32 - 2025-10-30
- Prevented the import drop zone click handler from re-triggering the hidden file input so the browser opens the picker without hitting a recursive jQuery stack overflow when Upload File is pressed.
- Guarded keyboard activation on the drop zone to avoid re-entering the handler when the native file input fires key events while keeping Enter/Space support intact.
- Bumped the plugin metadata and admin tooling banner to 3.47.32 to capture the uploader stability fix.

## 3.47.31 - 2025-10-29
- Hardened the admin bootstrap so the localized configuration object is always defined, preventing the tools script from aborting before wiring up UI handlers when translations or enqueue timing fail.
- Added runtime validation with explicit console feedback whenever the AJAX endpoint data is missing to make diagnosing enqueue issues straightforward.
- Bumped the plugin metadata and admin JavaScript banner to 3.47.31 to record the configuration safeguard.

## 3.47.30 - 2025-10-28
- Replaced the hidden import input with an accessible visually-hidden control so browsers can open the native file dialog on click.
- Added reusable styling for the hidden file input to keep it off-screen while remaining focusable for accessibility.
- Bumped the plugin metadata and bundled asset banners to 3.47.30 to capture the import dialog reliability fix.

## 3.47.29 - 2025-10-27
- Added a dedicated import trigger button so touch devices can reliably open the file picker without depending on drag-and-drop gestures.
- Updated the admin tooling script to wire the new control into the existing upload flow for consistent validation and feedback.
- Bumped the plugin metadata and bundled asset banners to 3.47.29 to document the mobile import accessibility fix.

## 3.47.28 - 2025-10-26
- Enabled keyboard and assistive technology access to the import drop zone by giving the uploader a focusable button role, descriptive labels, and instructions tied to the supported format list.
- Guarded the import handlers so the hidden file input only binds when the drop zone exists while allowing Enter/Space activation for accessible file selection.
- Bumped the plugin metadata and admin tooling banner to 3.47.28 to capture the import accessibility improvement.

## 3.47.27 - 2025-10-25
- Enabled the Tools → Import workflow to accept every exportable format by sourcing the supported extension list from the core configuration service.
- Synced the admin interface, uploader hints, and validation messaging with the shared format list so JSON, CSV, XML (and future additions) stay aligned without manual updates.
- Bumped the plugin metadata and admin tooling logic to 3.47.27 to record the import compatibility improvements.

## 3.47.23 - 2025-10-24
- Organized the maintenance tool statistic sections into spaced column layouts so cache, database, log, and cleanup metrics read cleanly on all devices.
- Styled individual maintenance tool statistic rows with padded, bordered flex wrappers to keep labels and values aligned and legible.
- Bumped the plugin metadata and admin stylesheet banner to 3.47.23 to record the maintenance stats layout polish.

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
