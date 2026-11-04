## 0.9.39 — 2026-10-31

- Performed a Joomla coding-standard source audit against the 0.9.38 release.
- Corrected PHP import ordering to follow PSR-12/Joomla style expectations.
- Expanded compact one-line PHP conditionals into standard multi-line blocks for clearer coding-standard compliance.
- Kept runtime behaviour unchanged; PHP syntax validation passes after the audit fixes.

## 0.9.38 — 2026-10-29

- Renamed the product to Site Health Inspector for a clearer, Joomla-trademark-neutral product identity.
- Renamed the component technical identity to `com_sitehealth`.
- Renamed the PHP namespace to `Joomla\Component\SiteHealth`.
- Renamed administrator language keys and component paths to `COM_SITEHEALTH` / `com_sitehealth`.
- Updated release metadata, scanner user-agent, asset versioning, documentation, privacy text, and JED draft to the new product identity.
- This release is a product rename and should be treated as a fresh installation rather than an in-place upgrade from the unpublished previous package.

## 0.9.36 — 2026-10-28
- Release-readiness hardening and source-review cleanup.
- Synchronized remaining runtime release metadata with the component version.
- Updated the vendor update-feed user-agent to 0.9.36.
- Hardened local manifest XML parsing with `LIBXML_NONET` where available and restored the previous libxml error-handling state.
- Prepared the release for the final PHPCS/Joomla coding-standard validation and real Joomla Update System endpoint testing.

## 0.9.35 — 2026-10-28
- Updated the extension manifest creation date to the actual 0.9.35 release date.
- Removed the verbose Migration action plan details list from Workspace Summary to prevent unnecessary page length and scrolling.
- Retained the main Migration Action Plan table as the authoritative detailed view.
- Kept migration progress, notes, checklist, search, filtering, export, print, and workspace functionality unchanged.

## 0.9.34 - 2026-10-27

- Refined Workspace Summary migration action-plan details so template rows are not repeated in the compact details list.
- Removed the hard-coded Cassiopeia position boundary in favor of extension-type filtering.
- Kept the no-match message only as filter feedback when no eligible migration details remain visible.

## 0.9.32 — 2026-10-26

## 0.9.33 — 2026-10-26

- Fixed Joomla 4.4 database compatibility by using `getQuery(true)` when the database driver does not provide `createQuery()`.
- Preserved `createQuery()` usage on Joomla versions that support it.
- Kept the Joomla 5.4.8 and Joomla 6.1.3 working behavior unchanged.

- Fixed the component manifest version so Joomla extension metadata and the Joomla 6 extension compatibility section both report 0.9.32.

## 0.9.31 — 2026-10-26
- Updated the manifest creation date to 2026-10-26 so the installed extension metadata reflects the latest release date.

## 0.9.30
- Refined Workspace Summary migration action-plan details so the duplicated detail list begins only after the Cassiopeia template entry, matching the intended previous presentation.
- Preserved search/risk filtering and saved progress, notes, and checklist state.

## 0.9.29 — 2026-10-25

- Fixed a JavaScript syntax regression in the workspace import confirmation introduced after 0.9.26.
- Restored working search-driven CSV Filtered and Print Filtered controls.
- Restored search/risk-filtered Migration action plan details in Workspace Summary.
- Hardened migration progress, notes, and checklist state restoration for existing localStorage keys and legacy casing.

# Changelog

## 0.9.28 — 2026-10-25
- Disabled CSV Filtered and Print Filtered until a search term is entered, including on initial page load.
- Added a compact Migration action plan details section to Workspace Summary.
- Hardened checklist persistence by restoring matching saved states even when older/local storage keys differ only by case or legacy key form.


## 0.9.27 — 2026-10-25

- Updated the package creation date to 2026-10-25 so the installed extension metadata reflects the current release date.
- Added workspace export source metadata (Site Health Inspector version, Joomla version, and PHP version).
- Improved workspace import confirmation with source-environment information and an explicit warning when an empty `notes` object will clear all migration notes.
- Preserved the 0.9.26 fix where `notes: {}` clears the complete migration-note set and an individual empty note removes only that note.
- Retained the Joomla 4.4.x, 5.x and 6.x compatibility, ACL, language, privacy, documentation, scanner, reporting and release-readiness work from previous releases.


## 0.9.26

### Fixed
- Fixed migration workspace import so `"notes": {}` explicitly clears all existing migration notes instead of merging with and preserving stale notes.
- Importing an individual note with an empty string now removes that note.
- Improved the import confirmation message to explain the empty-notes clearing behavior.

### Release readiness
- Added privacy and data-handling documentation.
- Added a Joomla Extensions Directory description draft.
- Updated release documentation and versioned component metadata to 0.9.26.



## 0.9.25

- Joomla API compatibility review and modernization for the component itself.
- Replaced deprecated `Joomla\\CMS\\Http\\HttpFactory` usage with `Joomla\\Http\\HttpFactory`.
- Updated vendor update-feed response handling to PSR-7 `getStatusCode()` / `getBody()` APIs.
- Replaced deprecated `Factory::getDbo()` usage in the model with `getDatabase()`.
- Replaced legacy database `getQuery(true)` calls with `createQuery()`.
- Replaced deprecated `AbstractView::get()` data retrieval with the model API.
- Updated release/version and scanner user-agent metadata to 0.9.25.

## 0.9.23 - 2026-10-06

### Compatibility and stability
- Expanded the supported Joomla runtime range to Joomla 4.4.x, 5.x and 6.x.
- Applied the PHP support policy: PHP 8.1+ for Joomla 4.4/5.x and PHP 8.3+ for Joomla 6.
- Moved administrator language files to the extension-local Joomla-recommended component language structure and explicitly load them for the administrator component.
- Fixed PCRE2-incompatible namespace scanner patterns that could produce `preg_match()` and `preg_match_all()` warnings on WAMP/local PHP installations.
- Added defensive scanner regex handling so a malformed rule cannot emit a PHP warning into the administrator interface.
- Updated the runtime baseline reporting so Joomla 4/5 sites can be tested while Joomla 6 PHP requirements remain visible as a migration target.

### Preserved
- Existing migration workspace, checklist, reporting, export/import, ACL, update-feed hardening and installer safeguards from 0.9.21.

## 0.9.21 - 2026-10-05

### Fixed
- Reworked installer minimum-version failures to display explicit Joomla/PHP requirements and detected versions instead of exposing untranslated `COM_SITEHEALTH_INSTALL_MIN_*` language keys.
- Installer preflight now reports the failure through Joomla's administrator message system and returns `false` cleanly.

## 0.9.19 - 2026-10-05

- Added Release Readiness checks for package metadata, installer lifecycle, language registration, namespace declaration, entry-point guards, output escaping, update-feed safeguards, and documentation.
- Added package README and changelog documentation.
- Preserved the 0.9.18 vendor update-site hardening in the release branch.

## 0.9.18

- Hardened registered vendor update-site retrieval.
- Restricted update-site requests to HTTP/HTTPS URLs without embedded credentials.
- Added a 2 MB response-size limit.
- Disabled network access during XML parsing when supported by libxml.
- Updated the scanner user-agent.

## 0.9.20 - 2026-10-05
- Expanded administrator UI language-string coverage, including JavaScript-generated report/workspace messages.
- Added component `core.manage` ACL definition and controller-level administrator access enforcement.
- Added installer minimum Joomla 5.4 and PHP 8.1 guards.
- Updated release-readiness documentation and versioned asset/user-agent references.
- Preserved vendor update-site response-size and XML parsing protections from 0.9.18.
