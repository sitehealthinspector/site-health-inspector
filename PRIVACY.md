# Site Health Inspector Privacy and Data Handling

Site Health Inspector is an administrator-only diagnostic component. It does not intentionally collect or transmit personal information to a Site Health Inspector service.

## Data processed

- Installed extension metadata is read from the local Joomla installation to build diagnostic results.
- Registered vendor update-site URLs and their XML responses may be requested when compatibility metadata is available.
- Migration progress, migration notes, and extension test checklists are stored in the administrator browser's local storage.
- Workspace export creates a local JSON file containing migration progress, notes, and checklist state.

## Network requests

Site Health Inspector can retrieve registered extension update XML over HTTP or HTTPS. URLs containing embedded credentials are rejected, response bodies are limited to 2 MB, and XML parsing disables external network access when supported by the PHP XML library.

Site Health Inspector does not intentionally send diagnostic results, workspace data, account information, or site credentials to a Site Health Inspector-operated server.

## User control

Administrators control which vendor update sites are enabled in Joomla. Workspace data can be removed by clearing the relevant browser local-storage data or by using the workspace import/export workflow.

## Scope

This document describes Site Health Inspector's intended data handling. It is not a substitute for the site operator's own privacy policy, vendor privacy notices, hosting-provider policies, or applicable legal requirements.
