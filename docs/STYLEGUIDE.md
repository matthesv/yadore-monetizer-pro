# Yadore Monetizer Pro Design System (v3.20)

Die Admin-Oberfläche von Yadore Monetizer Pro folgt ab Version 3.16 einem token-basierten Designsystem. Seit v3.20 wurden die Status-Kommunikation, Onboarding-Hilfen und Metrik-Anzeigen weiter verfeinert. Dieses Dokument dient als zentrale Referenz für Entwickler:innen, UX-Designer:innen und QA, um konsistente UI-Entscheidungen zu treffen und Änderungen nachvollziehbar zu dokumentieren.

## 1. Architektur & Dateien

| Bereich | Datei | Beschreibung |
| --- | --- | --- |
| Design Tokens | `assets/css/admin-design-system.css` | Definiert alle Farb-, Typografie-, Spacing-, Radius- und Shadow-Variablen (inkl. Dark-Mode-Anpassungen) innerhalb eines `@layer tokens` Blocks. |
| Komponenten | `assets/css/admin.css` | Nutzt die Tokens innerhalb der Layer `@layer base` und `@layer components`, implementiert Styleguide-spezifische Layouts und Komponenten. |
| Admin Styleguide | `templates/admin-styleguide.php` | Rendert die Styleguide-Unterseite im Backend mit Token-Vorschau, Komponentenbeispielen und Copy-to-Clipboard. |
| Interaktion | `assets/js/admin.js` | Enthält `initStyleguide()`, Kopier-Logik samt Fallback und Feedback für Code-Snippets sowie Token-Resolver auf Basis der CSS-Variablen. |

## 2. Design Tokens

Alle Tokens werden auf `:root` Ebene definiert und innerhalb von `.yadore-admin-wrap` vererbt.

- **Farben**: `--yadore-color-primary-*`, `--yadore-color-success-*`, `--yadore-color-warning-*`, `--yadore-color-danger-*`, `--yadore-color-neutral-*`, `--yadore-color-surface*`, `--yadore-color-code-*`.
- **Typografie**: `--yadore-font-family-base`, `--yadore-font-family-mono`, `--yadore-font-size-*`, `--yadore-font-weight-*`.
- **Spacing**: `--yadore-space-*` (0 bis 8) inkl. halber Schritte (`--yadore-space-1-5`, `--yadore-space-2-5`, `--yadore-space-4-5`).
- **Radii**: `--yadore-radius-*` (xs bis pill).
- **Shadows**: `--yadore-shadow-*` (xs bis lg) und Border-Intensitäten (`--yadore-border-subtle`, `--yadore-border-medium`, `--yadore-border-strong`).
- **Motion**: `--yadore-motion-duration-*` & `--yadore-motion-easing-standard` für animierte Mikrointeraktionen.

> **Dark Mode**: Über `@media (prefers-color-scheme: dark)` erhalten Farben, Schatten und Oberflächenwerte automatische Alternativen. Neue Tokens müssen beide Modi bedienen.

## 3. Layered CSS Guidelines

`assets/css/admin.css` nutzt zwei Layer:

1. `@layer base` – Globale Struktur (Page Title, Buttons, Version Badge).
2. `@layer components` – Layouts, Karten, Styleguide-spezifische Elemente.

**Regeln:**
- Tokens statt statischer Werte verwenden (`color: var(--yadore-color-primary-500)` anstelle von Hex-Werten).
- Neue Komponenten gehören in `@layer components` oder einen eigenen Layer (z. B. `@layer utilities`) – niemals direkt außerhalb von Layern ergänzen.
- Box-Shadows über `--yadore-shadow-*` definieren und keine individuellen RGBA-Werte verwenden.

## 4. Komponentenrichtlinien

Die Styleguide-Seite (`templates/admin-styleguide.php`) zeigt Referenz-HTML für zentrale Bausteine:

- **Stat Cards** (`.stat-card`) – Kennzahlen mit Icon, Zahl und Label.
- **Yadore Cards** (`.yadore-card`) – Standardcontainer für Einstellungen & Inhalte.
- **Status Badges** (`.status-badge`) – Farbige Statusanzeigen, nutzen die primären Feedback-Farben.
- **Onboarding Checklist** (`.yadore-onboarding-card`, `.checklist-item`) – Mehrstufiger Setup-Flow mit Fortschrittsanzeige, Status-Badges und kontextuellen Aktionen.
- **Filter Pills** & **Quick Filter** – Verwenden `--yadore-radius-pill` und `--yadore-space-*` Tokens.

Code-Beispiele können über den Copy-Button (Klasse `.styleguide-copy`) direkt übernommen werden. Das Snippet demonstriert ARIA-Anwendungen (`role`, `aria-labelledby`) und zeigt die erwartete Verwendung der Tokens.

## 5. Responsivität & Accessibility

- **Spacing & Layout**: Grid-Layouts nutzen `repeat(auto-fit, minmax(...))`, um sich automatisch an verfügbare Breiten anzupassen.
- **Typografie**: Titel verwenden `font-weight: var(--yadore-font-weight-semibold)`; Body-Text `var(--yadore-font-weight-regular)`.
- **Fokus & Interaktionen**: Buttons und interaktive Elemente dürfen nur Tokens oder systemeigene Fokus-Stile überschreiben.
- **ARIA**: Komponenten, die Statusänderungen anzeigen, benötigen `aria-live` oder eindeutige Labels; das Styleguide-Template liefert Beispiele in `templates/admin-styleguide.php`.
- **Kontraste**: Primärfarben erfüllen WCAG AA auf hellem und dunklem Hintergrund (getestet mit den hinterlegten Hex-Werten). Neue Farben müssen denselben Standard erreichen.

## 6. Änderungsprozess

1. **Diskussion & Ticket** – Jede Design-Änderung benötigt ein GitHub-Issue mit Kontext (Feature, UX, Bugfix).
2. **Token-Anpassung** – Änderungen an `assets/css/admin-design-system.css` nur nach Review durch Design & Dev Lead.
3. **Komponenten-Update** – Anpassungen in `assets/css/admin.css` müssen Tokens verwenden und Unit-Tests/visuelle Regression berücksichtigen.
4. **Styleguide-Refresh** – `templates/admin-styleguide.php` aktualisieren, wenn neue Komponenten oder Tokens hinzukommen.
5. **Dokumentation** – Dieses Dokument ergänzen (Changelog-Abschnitt) und README aktualisieren.
6. **QA** – Dark-Mode, Responsive Breakpoints und Screenreader-Flow prüfen.

## 7. Governance & Review-Checklist

- [ ] Tokens in `assets/css/admin-design-system.css` dokumentiert und auf Dark-Mode geprüft.
- [ ] Komponenten nutzen ausschließlich Variablen (keine Hardcodes).
- [ ] Styleguide-Vorschau aktualisiert + Copy-Snippets getestet.
- [ ] `assets/js/admin.js` (Clipboard) funktioniert mit und ohne `navigator.clipboard`.
- [ ] README & Changelog gepflegt (Version, Highlights, neue Seiten).

## 8. Kontakt & Ownership

- **Design Lead**: Maintainer des Styleguides, finalisiert Token-Änderungen.
- **Frontend Engineering**: Verantwortlich für `assets/css/admin.css` und `assets/js/admin.js`.
- **Documentation Owner**: Pflegt `docs/STYLEGUIDE.md` und README.

Bitte alle Anpassungen via Pull Request mit Screenshots (falls UI-relevant) und Verweis auf dieses Dokument bereitstellen.
