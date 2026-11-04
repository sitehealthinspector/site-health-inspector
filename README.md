# Site Health Inspector for Joomla

Site Health Inspector for Joomla is an administrator component for Joomla 4.4, 5.x and 6.x diagnostics and Joomla 5 → 6 migration readiness.

## Scope

- Joomla 5.4 → 6 compatibility metadata analysis
- Static PHP source compatibility scanning
- Migration preflight checks
- Migration action plan and workspace tracking
- Client-ready migration reporting
- Registered vendor update-site compatibility metadata retrieval

## Data handling

Site Health Inspector reads installed-extension metadata and registered update-site XML from the Joomla site. Migration progress, notes, and checklists are stored in the browser's local storage. Vendor update XML is processed transiently for the diagnostic scan and is not intentionally persisted by Site Health Inspector.

## Compatibility

This release supports Joomla 4.4.x, 5.x and 6.x. Site Health Inspector requires PHP 8.1+ on Joomla 4.4/5.x and PHP 8.3+ on Joomla 6.

## Workspace import behavior

Workspace exports include the Site Health Inspector, Joomla, and PHP source versions. Workspace imports validate the workspace structure before applying it, merge progress and checklist entries with the current browser workspace, and show a source/environment preview in the confirmation prompt. Migration notes are also merged when present; an imported `notes` object with no entries (`"notes": {}`) explicitly clears all existing migration notes. An imported note with an empty string removes that individual note.

## Release validation status

The release package is intended for final Joomla coding-standard, security/source, update-server, and JED validation before publication. A real public update XML endpoint is deliberately not embedded until the project has an actual public distribution URL; placeholder or example update-server URLs are not used.

## License

GNU General Public License version 2 or later.

## Administrator access

Site Health Inspector is an administrator-only diagnostic component. Access is controlled through the component `core.manage` permission. The controller also enforces this permission for direct administrator requests.

## Installation compatibility guard

The installer accepts Joomla 4.4.0 or newer within the supported Joomla 4.4.x, 5.x and 6.x branches. PHP 8.1 or newer is required on Joomla 4.4/5.x, and PHP 8.3 or newer is required on Joomla 6.

## Localization

The administrator interface, workspace controls, exported/printed report UI strings, and JavaScript-generated user messages are backed by `en-GB` language constants. Additional translations can be added using the same constants.


### Installation requirements
Site Health Inspector requires Joomla 4.4.0 or newer and supports Joomla 4.4.x, 5.x and 6.x. PHP 8.1.0 or newer is required on Joomla 4.4/5.x; PHP 8.3.0 or newer is required on Joomla 6. If an environment does not meet these requirements, the installer reports the detected Joomla and PHP versions directly in the administrator message area.
