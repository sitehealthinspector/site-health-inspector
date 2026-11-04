# Joomla Extensions Directory Description

## Short description

Site Health Inspector for Joomla is an administrator diagnostic and Joomla 5 → 6 migration-readiness component for Joomla 4.4.x, 5.x and 6.x.

## Description

Site Health Inspector helps administrators assess installed extensions before a Joomla migration. It combines migration preflight checks, extension compatibility metadata, conservative static PHP scanning, risk classification, a migration action plan, progress tracking, migration notes, extension-level verification checklists, and client-ready reports.

The scanner reports static-code findings as migration signals for administrator review. A finding is not presented as proof of a runtime failure or as a guarantee of vendor compatibility.

Workspace progress, notes, and checklists can be exported to and imported from a portable JSON workspace file. An empty `notes` object in an imported workspace intentionally clears all existing migration notes.

## Compatibility

- Joomla 4.4.x
- Joomla 5.x
- Joomla 6.x
- PHP 8.1+ on Joomla 4.4.x and 5.x
- PHP 8.3+ on Joomla 6.x

## Requirements

Site Health Inspector is administrator-only and requires the `core.manage` permission for `com_sitehealth`.

## License

GNU General Public License version 2 or later.
