# Changelog

All notable changes to `lingua` will be documented in this file.

## Rivalex Lingua - 2026-03-18

### Highlights

- Improved test reliability by extending the test workflow timeout to **120 minutes**.
- Cleaned up styling and formatting across several test files for better consistency and readability.
- Minor test updates to align with current imports and formatting conventions.

### Changed

- **CI / Test workflow**
  
  - Increased the GitHub Actions test job timeout to avoid premature failures on longer test runs.
  
- **Tests**
  
  - Fixed formatting and import cleanup in multiple feature tests.
  - Normalized spacing and constructor/import usage in Livewire and command tests.
  - Adjusted the Blade components test setup to use the imported error bag class consistently.
  

### Notes

- This release is focused on **stability, maintenance, and test suite hygiene**.
- No user-facing functional changes were introduced in these commits.

If you want, I can also turn this into a **GitHub Release draft** with:

- a short summary,
- bullet list,
- and a polished “What’s Changed” section.
