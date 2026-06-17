# Product

## Register

product

## Users

Mixed team — two distinct audiences sharing one interface:

- **Laravel developers** (occasional users): install Lingua, visit the panel to sync translations between file and database, configure settings, fix missing strings before a release. Comfortable with technical keys and locale codes.
- **Translators / content editors** (frequent users): live in this panel daily across multiple locales. Non-technical. Need human-readable labels, clear status at a glance, and a flow that never demands context-switching.

Both use the same surface; the UI must not sacrifice one for the other.

## Product Purpose

Rivalex/Lingua is a Laravel composer package that embeds a multilingual management panel directly into any Laravel application. It lets teams manage translation strings across locales, sync file ↔ database storage, manage languages and their sort order, run coverage statistics, and configure driver and UI settings — all without leaving the host application.

Success looks like: a translator can find, fix, and confirm a missing string in under 10 seconds; a developer can sync 25 locales and review coverage without reading documentation.

## Brand Personality

Precise · Reliable · Clean

Voice: developer-grade tool that non-technical users can navigate without a manual. Confident, no-nonsense, never chatty. Actions confirm clearly; errors are direct.

Reference: Linear — compact, keyboard-friendly, information without clutter. Every element earns its place through function, not decoration.

## Anti-references

- **Plain Tailwind starter** — gray-on-white with default components and no distinct visual identity. Lingua has a committed brand (crimson/red) and must look like it.
- **Over-designed dark-mode tool** — heavy glass effects, neon accents, "premium dark" aesthetics that prioritize visual drama over work clarity.

## Design Principles

1. **Density earns its keep.** Compact by default. Every row, column, and control has a job. Padding is structure, not decoration.
2. **Workflow first.** The translator's loop (scan → find missing → edit → confirm) must complete in ≤2 interactions per string. The developer's loop (sync → verify → done) must be one button away.
3. **Confident brand, quiet chrome.** The crimson/red brand accent is intentional and owned. Surrounding UI chrome stays restrained so the work — the strings, the locales, the status — surfaces forward.
4. **Both audiences, one interface.** Technical keys and locale codes stay visible for developers. Human labels and completion status stay prominent for translators. No mode-switching, no dumbing down.
5. **Reliable feedback.** Every action confirms or fails visibly and immediately. No ambiguous loading states, no silent errors.

## Accessibility & Inclusion

WCAG 2.1 AA. Keyboard navigation throughout (tab order, focus rings, escape to dismiss). Sufficient contrast on all text including badges and table cells. Support for `prefers-reduced-motion` on any transitions added. Screen-reader-compatible form labels (Flux Pro handles baseline; custom components must match).
