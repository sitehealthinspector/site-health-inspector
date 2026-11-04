<?php
declare(strict_types=1);

namespace Joomla\Component\SiteHealth\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Http\HttpFactory;

class HealthModel extends BaseDatabaseModel
{
    private const JOOMLA_6_BASELINE = '6.0.0';
    private const JOOMLA_6_TARGETS = ['6.0.0', '6.1.0', '6.2.0'];
    private const JOOMLA_HEALTH_ELEMENT = 'com_sitehealth';
    private const JOOMLA_HEALTH_TARGET = '4.4.x / 5.x / 6.x';
    private const JOOMLA_HEALTH_PHP_MINIMUM = '8.1';
    private const VERSION = '0.9.39';
    private const UPDATE_XML_MAX_BYTES = 2_000_000;
    private const CODE_SCAN_MAX_FILES = 500;
    private const CODE_SCAN_MAX_FILE_BYTES = 2_000_000;
    private const CODE_SCAN_RULESET = 'Joomla 5.4 → 6.0';
    private array $updateSiteCache = [];

    public function getHealthData(): array
    {
        $app = Factory::getApplication();
        $db  = $this->getDatabase();

        $findings = [];
        $extensions = [];
        $passed = 0;
        $critical = 0;

        $query = $this->createDatabaseQuery($db)
            ->select('*')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('protected') . ' = 0')
            ->order($db->quoteName('type') . ' ASC, ' . $db->quoteName('name') . ' ASC');

        $db->setQuery($query);
        $rows = $db->loadObjectList() ?: [];

        foreach ($rows as $row) {
            $manifestCache = json_decode((string) ($row->manifest_cache ?? ''), true);
            $manifestPath = $this->findManifestPath($row);
            $manifest = $this->readManifest($manifestPath);
            $updateMetadata = $this->getUpdateMetadata($row);
            $compatibility = $this->analyseCompatibility($row, $manifest, $updateMetadata);
            $codeScan = $this->scanExtensionSource($row);

            $extensions[] = [
                'name' => (string) $row->name,
                'type' => (string) $row->type,
                'version' => $this->getManifestValue($manifest, 'version', $manifestCache['version'] ?? ''),
                'creationDate' => $this->getManifestValue($manifest, 'creationDate', $manifestCache['creationDate'] ?? ''),
                'author' => $this->getManifestValue($manifest, 'author', $manifestCache['author'] ?? ''),
                'client_id' => (int) $row->client_id,
                'enabled' => (int) $row->enabled === 1,
                'status' => $compatibility['status'],
                'statusLabel' => $compatibility['statusLabel'],
                'detail' => $compatibility['detail'],
                'target' => $compatibility['target'],
                'phpMinimum' => $compatibility['phpMinimum'],
                'metadataSource' => $compatibility['metadataSource'],
                'manifestFound' => $compatibility['manifestFound'],
                'updateSiteState' => $compatibility['updateSiteState'],
                'codeScan' => $codeScan,
                'risk' => $this->getExtensionRisk($compatibility, $codeScan),
                'actions' => $this->getExtensionActions($compatibility, $codeScan),
            ];

            if ($codeScan['critical'] > 0) {
                $critical++;
                $findings[] = [
                    'level' => 'critical',
                    'title' => (string) $row->name . ' has Joomla 6 code compatibility issues',
                    'detail' => $codeScan['critical'] . ' high-confidence Joomla 5→6 issue(s) found in ' . $codeScan['files'] . ' scanned PHP file(s).',
                ];
            } elseif ($codeScan['warnings'] > 0) {
                $findings[] = [
                    'level' => 'warning',
                    'title' => (string) $row->name . ' has Joomla 6 code review items',
                    'detail' => $codeScan['warnings'] . ' Joomla 5→6 code pattern(s) need review in ' . $codeScan['files'] . ' scanned PHP file(s).',
                ];
            }

            if ($compatibility['status'] === 'critical') {
                $critical++;
                $findings[] = [
                    'level' => 'critical',
                    'title' => (string) $row->name . ' requires attention',
                    'detail' => $compatibility['detail'],
                ];
            } elseif ($compatibility['status'] === 'review') {
                $findings[] = [
                    'level' => 'warning',
                    'title' => (string) $row->name . ' needs Joomla 6 review',
                    'detail' => $compatibility['detail'],
                ];
            } elseif ($compatibility['status'] === 'compatible') {
                $passed++;
            }
        }

        if (version_compare(JVERSION, self::JOOMLA_6_BASELINE, '>=')) {
            $passed++;
        } else {
            $findings[] = [
                'level' => 'info',
                'title' => 'Joomla 6 compatibility scan',
                'detail' => 'The current site is running Joomla ' . JVERSION . '. This release focuses on Joomla 5 to Joomla 6 readiness.',
            ];
        }

        $runtimePhpMinimum = version_compare(JVERSION, '6.0.0', '>=') ? '8.3.0' : '8.1.0';

        if (version_compare(PHP_VERSION, $runtimePhpMinimum, '>=')) {
            $passed++;
        } else {
            $critical++;
            $findings[] = [
                'level' => 'critical',
                'title' => 'PHP version below Site Health Inspector runtime baseline',
                'detail' => 'Detected PHP ' . PHP_VERSION . '. Site Health Inspector requires PHP ' . $runtimePhpMinimum . ' or newer on the current Joomla branch.',
            ];
        }

        if (version_compare(PHP_VERSION, '8.3.0', '<') && version_compare(JVERSION, '6.0.0', '<')) {
            $findings[] = [
                'level' => 'info',
                'title' => 'Joomla 6 PHP target',
                'detail' => 'Site Health Inspector is running on a Joomla 4/5 branch. Joomla 6 requires PHP 8.3.0 or newer when this site is migrated.',
            ];
        }

        $https = (bool) $app->get('force_ssl', 0)
            || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        if ($https) {
            $passed++;
        } else {
            $findings[] = [
                'level' => 'warning',
                'title' => 'HTTPS could not be confirmed',
                'detail' => 'The current administrator request did not confirm HTTPS. This is not proof that the site is insecure.',
            ];
        }

        $debug = (bool) $app->get('debug');
        if (!$debug) {
            $passed++;
        } else {
            $findings[] = [
                'level' => 'warning',
                'title' => 'Debug mode is enabled',
                'detail' => 'Consider disabling debug mode on production sites.',
            ];
        }

        $sef = (bool) $app->get('sef');
        if ($sef) {
            $passed++;
        } else {
            $findings[] = [
                'level' => 'info',
                'title' => 'SEF URLs are disabled',
                'detail' => 'Informational setting; not a Joomla 6 compatibility failure.',
            ];
        }

        $systemChecks = $this->getSystemReadinessChecks($db);
        foreach ($systemChecks['findings'] as $systemFinding) {
            $findings[] = $systemFinding;
        }
        foreach ($systemChecks['checks'] as $systemCheck) {
            if ($systemCheck['status'] === 'critical') {
                $critical++;
            } elseif ($systemCheck['status'] === 'pass') {
                $passed++;
            }
        }

        $warningCount = count(array_filter($findings, static fn(array $item): bool => $item['level'] === 'warning'));
        $infoCount = count(array_filter($findings, static fn(array $item): bool => $item['level'] === 'info'));

        $riskCounts = [
            'critical' => count(array_filter($extensions, static fn(array $item): bool => $item['risk'] === 'critical')),
            'high' => count(array_filter($extensions, static fn(array $item): bool => $item['risk'] === 'high')),
            'review' => count(array_filter($extensions, static fn(array $item): bool => $item['risk'] === 'review')),
            'low' => count(array_filter($extensions, static fn(array $item): bool => $item['risk'] === 'low')),
            'unknown' => count(array_filter($extensions, static fn(array $item): bool => $item['risk'] === 'unknown')),
        ];

        $compatibilityCounts = [
            'compatible' => count(array_filter($extensions, static fn(array $item): bool => $item['status'] === 'compatible')),
            'review' => count(array_filter($extensions, static fn(array $item): bool => $item['status'] === 'review')),
            'critical' => count(array_filter($extensions, static fn(array $item): bool => $item['status'] === 'critical')),
            'unknown' => count(array_filter($extensions, static fn(array $item): bool => $item['status'] === 'unknown')),
        ];

        $metadataAvailable = count(array_filter($extensions, static fn(array $item): bool => $item['metadataSource'] !== 'none'));
        $unknown = $compatibilityCounts['unknown'];
        $disabledUpdateSites = count(array_filter($extensions, static fn(array $item): bool => $item['updateSiteState'] === 'disabled'));
        $noUpdateSites = count(array_filter($extensions, static fn(array $item): bool => $item['updateSiteState'] === 'none'));

        $releaseAudit = $this->getReleaseReadinessAudit();

        $importantNotes = [
            'Site Health Inspector 0.9.39 is a release-readiness hardening release for Joomla 4.4.x, 5.x, and 6.x, including source/security review improvements and release metadata consistency safeguards. Results are readiness indicators, not JED approval.',
            'Site Health Inspector 0.9.18 hardens vendor update-site retrieval with HTTP(S)-only URL validation, a 2 MB response limit, network-disabled XML parsing, and a release-specific scanner user-agent.',
            'Site Health Inspector 0.9.17 adds registered en-GB administrator language files for component metadata and menu labels, and updates the administrator stylesheet asset version to match the component release.',
            'Site Health Inspector 0.9.16 makes Print Filtered and Print All render static verification checklists from the live checkbox values, including checked and unchecked symbols, matching the Client report.',
            'Site Health Inspector 0.9.15 adds a client-ready migration report that combines the current diagnostic overview, compatibility inventory, code-scan results, action-plan progress, verification checklists, and migration notes in a print-ready document.',
            'Site Health Inspector 0.9.14 renders the five extension checklist items as explicit full-width rows using inline layout styles, independent of cached or conflicting stylesheet rules; the Checks 0/5 summary is kept as one unbreakable label.',
            'Site Health Inspector 0.9.13 attempted to force the checklist into a single vertical column and keep the Checks counter on one line.',
            'Site Health Inspector 0.9.12 attempted to display all pre-migration checklist items in a single vertical column for easier reading on desktop, mobile, and printed reports.',
            'Site Health Inspector 0.9.11 redesigns per-extension pre-migration checks as a compact floating checklist panel, keeping the Migration Action Plan table rows short until a checklist is opened.',
            'Site Health Inspector 0.9.10 preserves explicit true/false values for every extension checklist item in workspace export/import, so unchecked items are restored as false instead of being omitted.',
            'Site Health Inspector 0.9.9 redesigns the extension verification checklist as a compact, two-column set of concise checks to reduce visual clutter in the Migration Action Plan.',
            'Site Health Inspector 0.9.8 updates the extension migration checklist presentation and manifest creation date to 2026-10-02.',
            'Site Health Inspector 0.9.7 adds an extension-level migration test checklist. Checklist items are saved in this browser and included in workspace JSON backups; older 0.9.x workspace backups remain supported.',
            'Site Health Inspector 0.9.6 adds a live Migration Workspace Summary with action totals, completion percentage, status counts, and note count.',
            'Site Health Inspector 0.9.5 slightly increases Migration Center action button labels to improve readability while retaining responsive wrapping.',
            'Site Health Inspector 0.9.3 improves migration workspace backup controls and exports every Migration Action Plan item, including untouched items with Pending status, together with any saved migration notes.',
            'Progress statuses and migration notes remain stored locally in this browser. Use Migration Center → Migration workspace backup → Export workspace and Import workspace to transfer or restore this workspace. CSV exports and printed action plans continue to include progress and notes.',
            'The 0.9.3 update preserves the Joomla 5.4 → 6.0 source scanner, migration preflight, compatibility analysis, risk classification, progress tracker, migration notes, and existing filtered/all CSV and print behavior. Workspace JSON remains a backup of action-plan progress and notes, not a full diagnostic report.',
            'Site Health Inspector 0.8.0 reads compatibility information directly from registered Joomla extension update sites. Joomla removes targetplatform and php_minimum from the #__updates database record after processing, so those values cannot reliably be read from #__updates.',
            'An extension can be fully up to date and still have useful compatibility metadata. Site Health Inspector therefore checks the extension update XML itself rather than requiring a newer update to exist.',
            'A Joomla 6 declared result means an update XML entry declares a target platform matching Joomla 6. It is a metadata signal, not a guarantee that every feature works correctly on this website.',
            'Unknown is not the same as incompatible. It means no usable Joomla 6 compatibility declaration was found in an enabled update site.',
            'If an extension has no registered update site, or all its update sites are disabled, Site Health Inspector cannot retrieve vendor compatibility metadata automatically.',
            'Joomla 6 requires PHP 8.3.0 or newer; PHP 8.4 is recommended. Minimum database versions are MySQL 8.0.13, MariaDB 10.4, or PostgreSQL 12.0. Joomla recommends at least 256MB PHP memory.',
            'A migration preflight finding describes the current environment. It does not prove that an extension will work at runtime.',
        ];

        if ($metadataAvailable === 0 && count($extensions) > 0) {
            $findings[] = [
                'level' => 'info',
                'title' => 'No usable extension compatibility metadata was found',
                'detail' => 'Site Health Inspector could not retrieve compatibility metadata from enabled update sites. This can happen with extensions that have no update site or whose update site is disabled.',
            ];
        } elseif ($unknown > 0) {
            $findings[] = [
                'level' => 'info',
                'title' => $unknown . ' extension(s) need manual verification',
                'detail' => 'No Joomla 6 declaration was found for these extensions. Check the vendor documentation or test them on a staging site.',
            ];
        }

        if ($disabledUpdateSites > 0) {
            $findings[] = [
                'level' => 'info',
                'title' => $disabledUpdateSites . ' extension(s) have disabled update sites',
                'detail' => 'Enable the relevant update site in System → Update → Update Sites if you want Site Health Inspector to retrieve vendor compatibility metadata automatically.',
            ];
        }

        if ($noUpdateSites > 0) {
            $findings[] = [
                'level' => 'info',
                'title' => $noUpdateSites . ' extension(s) have no registered update site',
                'detail' => 'No vendor update feed is registered for these extensions. Their compatibility must be verified manually until an update site is available.',
            ];
        }

        // Recalculate after metadata findings have been appended.
        $warningCount = count(array_filter($findings, static fn(array $item): bool => $item['level'] === 'warning'));
        $infoCount = count(array_filter($findings, static fn(array $item): bool => $item['level'] === 'info'));

        return [
            'joomla' => JVERSION,
            'php' => PHP_VERSION,
            'memory' => ini_get('memory_limit') ?: 'Unknown',
            'db' => $this->getDatabaseSummary($db),
            'debug' => $debug,
            'sef' => $sef,
            'https' => $https,
            'extensions' => $extensions,
            'extensionCount' => count($extensions),
            'warnings' => $findings,
            'passed' => $passed,
            'critical' => $critical,
            'warningCount' => $warningCount,
            'infoCount' => $infoCount,
            'compatibilityCounts' => $compatibilityCounts,
            'riskCounts' => $riskCounts,
            'scanTime' => gmdate('c'),
            'version' => self::VERSION,
            'importantNotes' => $importantNotes,
            'systemChecks' => $systemChecks['checks'],
            'releaseAudit' => $releaseAudit,
        ];
    }

    private function getExtensionRisk(array $compatibility, array $codeScan): string
    {
        if ($compatibility['status'] === 'critical' || $codeScan['critical'] > 0) {
            return 'critical';
        }

        if ($compatibility['status'] === 'review' || $codeScan['warnings'] > 0) {
            return 'high';
        }

        // Unknown compatibility metadata is not itself a risk level.
        // It means the vendor declaration could not be verified automatically,
        // so the extension belongs in the manual-review queue.
        if ($compatibility['status'] === 'unknown') {
            return 'review';
        }

        if ($compatibility['status'] === 'compatible' && $codeScan['status'] === 'clean') {
            return 'low';
        }

        return 'review';
    }

    private function getExtensionActions(array $compatibility, array $codeScan): array
    {
        $actions = [];

        if ($compatibility['status'] === 'critical') {
            $actions[] = 'Resolve the Joomla 6 compatibility blocker before migration.';
        } elseif ($compatibility['status'] === 'review') {
            $actions[] = 'Review the vendor compatibility declaration and test the extension on Joomla 6.';
        } elseif ($compatibility['status'] === 'unknown') {
            $actions[] = 'Check the vendor documentation or support channel for Joomla 6 compatibility.';
        }

        if ($codeScan['critical'] > 0) {
            $actions[] = 'Fix the reported removed Joomla APIs before migration.';
        } elseif ($codeScan['warnings'] > 0) {
            $actions[] = 'Review the reported deprecated Joomla API usage and update where appropriate.';
        }

        if (!$actions && $compatibility['status'] === 'compatible' && $codeScan['status'] === 'clean') {
            $actions[] = 'No migration action identified by Site Health Inspector; still test on staging.';
        }

        return array_values(array_unique($actions));
    }

    private function getDatabaseInfo($db): array
    {
        $serverType = '';
        $serverVersion = '';

        try {
            if (method_exists($db, 'getServerType')) {
                $serverType = strtolower(trim((string) $db->getServerType()));
            }

            // Joomla Database drivers expose getVersion(), not getServerVersion().
            // getVersion() is implemented by the MySQLi/PDO drivers used by Joomla 6.
            if (method_exists($db, 'getVersion')) {
                $serverVersion = trim((string) $db->getVersion());
            }
        } catch (\Throwable $e) {
            $serverVersion = '';
        }

        return [$serverType, $serverVersion];
    }

    private function getDatabaseSummary($db): string
    {
        [$serverType, $serverVersion] = $this->getDatabaseInfo($db);

        return trim($serverType . ' ' . $serverVersion);
    }

    private function getReleaseReadinessAudit(): array
    {
        $administratorRoot = dirname(__DIR__, 4);
        $componentRoot = $administratorRoot . '/components/com_sitehealth';
        $manifest = $componentRoot . '/sitehealth.xml';
        $language = $componentRoot . '/language/en-GB/com_sitehealth.ini';
        $languageSys = $componentRoot . '/language/en-GB/com_sitehealth.sys.ini';
        $access = $componentRoot . '/access.xml';
        $readme = dirname($administratorRoot) . '/README.md';
        $changelog = dirname($administratorRoot) . '/CHANGELOG.md';
        $privacy = dirname($administratorRoot) . '/PRIVACY.md';
        $jedDescription = dirname($administratorRoot) . '/JED_DESCRIPTION.md';
        $view = __DIR__ . '/../View/Health/HtmlView.php';
        $template = __DIR__ . '/../../tmpl/health/default.php';
        $controller = __DIR__ . '/../Controller/DisplayController.php';
        $model = __FILE__;
        $checks = [
            ['name' => 'Manifest metadata', 'status' => is_file($manifest) ? 'pass' : 'review', 'detail' => 'Component manifest presence is checked.'],
            ['name' => 'Installer lifecycle script', 'status' => 'review', 'detail' => 'Installer lifecycle hooks are reviewed in the release package; Joomla does not require the source script to remain in the installed component directory.'],
            ['name' => 'ACL definition and enforcement', 'status' => (is_file($access) && str_contains((string) @file_get_contents($access), 'core.manage') && is_file($controller) && str_contains((string) @file_get_contents($controller), "authorise('core.manage', 'com_sitehealth')")) ? 'pass' : 'review', 'detail' => 'The component core.manage permission and controller-level administrator access check are verified.'],
            ['name' => 'Administrator language registration', 'status' => (is_file($language) && is_file($languageSys)) ? 'pass' : 'review', 'detail' => 'Registered en-GB administrator language files are checked.'],
            ['name' => 'Namespace declaration', 'status' => (is_file($manifest) && str_contains((string) @file_get_contents($manifest), '<namespace path="src">')) ? 'pass' : 'review', 'detail' => 'Modern Joomla namespace declaration is checked.'],
            ['name' => 'Protected administrator entry points', 'status' => $this->filesContainJexec([$model, $view, $template]) ? 'pass' : 'review', 'detail' => 'Core administrator PHP entry points are checked for the Joomla direct-access guard.'],
            ['name' => 'Escaped template output', 'status' => is_file($template) && str_contains((string) @file_get_contents($template), 'htmlspecialchars(') ? 'pass' : 'review', 'detail' => 'Rendered diagnostic values are checked for escaped output.'],
            ['name' => 'Vendor update-feed safeguards', 'status' => self::UPDATE_XML_MAX_BYTES === 2_000_000 ? 'pass' : 'review', 'detail' => 'Update-site URL, response-size, and XML parsing safeguards are checked.'],
            ['name' => 'Documentation package', 'status' => (is_file($readme) && is_file($changelog) && is_file($privacy) && is_file($jedDescription)) ? 'pass' : 'review', 'detail' => 'README, changelog, privacy/data-handling documentation, and JED description draft are checked when the release package is reviewed; they are not required to remain in the installed Joomla root.'],
            ['name' => 'Installed component structure', 'status' => (is_file($manifest) && is_file($access) && is_dir($componentRoot . '/src') && is_dir($componentRoot . '/tmpl')) ? 'pass' : 'review', 'detail' => 'Installed component directories, manifest, ACL definition, source and template directories are checked.'],
        ];
        $passed = count(array_filter($checks, static fn(array $check): bool => $check['status'] === 'pass'));
        return ['checks' => $checks, 'passed' => $passed, 'total' => count($checks), 'status' => $passed === count($checks) ? 'ready' : 'review'];
    }

    private function filesContainJexec(array $files): bool
    {
        foreach ($files as $file) {
            if (!is_file($file) || !str_contains((string) @file_get_contents($file), "defined('_JEXEC') or die;")) {
                return false;
            }
        }
        return true;
    }

    private function getSystemReadinessChecks($db): array
    {
        $checks = [];
        $findings = [];

        $checks[] = [
            'name' => 'PHP',
            'current' => PHP_VERSION,
            'required' => version_compare(JVERSION, '6.0.0', '>=') ? '8.3.0+' : '8.1.0+',
            'status' => version_compare(PHP_VERSION, version_compare(JVERSION, '6.0.0', '>=') ? '8.3.0' : '8.1.0', '>=') ? 'pass' : 'critical',
        ];

        [$serverType, $serverVersion] = $this->getDatabaseInfo($db);
        $dbStatus = 'unknown';
        $dbRequired = 'MySQL 8.0.13+ / MariaDB 10.4+ / PostgreSQL 12.0+';

        if (strpos(strtolower($serverVersion), 'mariadb') !== false || strpos($serverType, 'mariadb') !== false) {
            $dbStatus = version_compare($this->normaliseVersion($serverVersion), '10.4.0', '>=') ? 'pass' : 'critical';
        } elseif (strpos($serverType, 'mysql') !== false) {
            $dbStatus = version_compare($this->normaliseVersion($serverVersion), '8.0.13', '>=') ? 'pass' : 'critical';
        } elseif (strpos($serverType, 'pgsql') !== false || strpos($serverType, 'postgres') !== false) {
            $dbStatus = version_compare($this->normaliseVersion($serverVersion), '12.0.0', '>=') ? 'pass' : 'critical';
        }

        if ($dbStatus === 'critical') {
            $findings[] = [
                'level' => 'critical',
                'title' => 'Database version is below the Joomla 6 minimum',
                'detail' => 'Detected ' . trim($serverType . ' ' . $serverVersion) . '. Joomla 6 requires MySQL 8.0.13+, MariaDB 10.4+, or PostgreSQL 12.0+, depending on the database platform.',
            ];
        } elseif ($dbStatus === 'unknown') {
            $findings[] = [
                'level' => 'info',
                'title' => 'Database version could not be classified',
                'detail' => 'Site Health Inspector could not match the detected database platform to the Joomla 6 minimum database matrix.',
            ];
        }

        $checks[] = [
            'name' => 'Database',
            'current' => trim($serverType . ' ' . $serverVersion),
            'required' => $dbRequired,
            'status' => $dbStatus,
        ];

        $requiredExtensions = ['json', 'simplexml', 'dom', 'zlib', 'gd'];
        foreach ($requiredExtensions as $extension) {
            $loaded = extension_loaded($extension);
            $checks[] = [
                'name' => 'PHP extension: ' . $extension,
                'current' => $loaded ? 'Loaded' : 'Missing',
                'required' => 'Required by Joomla 6',
                'status' => $loaded ? 'pass' : 'critical',
            ];
            if (!$loaded) {
                $findings[] = [
                    'level' => 'critical',
                    'title' => 'Required PHP extension missing: ' . $extension,
                    'detail' => 'Joomla 6 lists the ' . $extension . ' PHP extension as required.',
                ];
            }
        }

        $databaseDrivers = extension_loaded('mysqlnd') || extension_loaded('pdo_mysql') || extension_loaded('pdo_pgsql');
        $checks[] = [
            'name' => 'Database PHP driver',
            'current' => $databaseDrivers ? 'Available' : 'Missing',
            'required' => 'mysqlnd or pdo_mysql or pdo_pgsql',
            'status' => $databaseDrivers ? 'pass' : 'critical',
        ];
        if (!$databaseDrivers) {
            $findings[] = [
                'level' => 'critical',
                'title' => 'Required PHP database driver missing',
                'detail' => 'Joomla 6 requires mysqlnd, pdo_mysql, or pdo_pgsql for database connectivity.',
            ];
        }

        $memory = $this->memoryLimitBytes((string) ini_get('memory_limit'));
        $memoryStatus = $memory === null ? 'unknown' : ($memory >= 256 * 1024 * 1024 ? 'pass' : 'warning');
        $checks[] = [
            'name' => 'PHP memory limit',
            'current' => ini_get('memory_limit') ?: 'Unknown',
            'required' => '256M recommended',
            'status' => $memoryStatus,
        ];
        if ($memoryStatus === 'warning') {
            $findings[] = [
                'level' => 'warning',
                'title' => 'PHP memory limit is below Joomla recommendation',
                'detail' => 'The current PHP memory_limit is ' . ((string) ini_get('memory_limit')) . '. Joomla recommends at least 256MB.',
            ];
        }

        $compat = $this->getCompatibilityPluginState($db);
        foreach ($compat['checks'] as $check) {
            $checks[] = $check;
        }
        foreach ($compat['findings'] as $finding) {
            $findings[] = $finding;
        }

        return ['checks' => $checks, 'findings' => $findings];
    }

    private function getCompatibilityPluginState($db): array
    {
        $checks = [];
        $findings = [];
        $rows = [];

        try {
            $query = $this->createDatabaseQuery($db)
                ->select([
                    $db->quoteName('element'),
                    $db->quoteName('folder'),
                    $db->quoteName('enabled'),
                    $db->quoteName('name'),
                ])
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote('behaviour'))
                ->where($db->quoteName('element') . ' IN (' . $db->quote('compat') . ',' . $db->quote('compat6') . ')');
            $db->setQuery($query);
            $rows = $db->loadObjectList() ?: [];
        } catch (\Throwable $e) {
            $rows = [];
        }

        $state = [];
        foreach ($rows as $row) {
            $state[(string) $row->element] = (int) $row->enabled === 1;
        }

        if (version_compare(JVERSION, '6.0.0', '<')) {
            $compat5Installed = array_key_exists('compat', $state);
            $compat6Installed = array_key_exists('compat6', $state);
            $compat5Enabled = $state['compat'] ?? false;
            $compat6Enabled = $state['compat6'] ?? false;

            $checks[] = [
                'name' => 'Joomla 5 Backward Compatibility plugin',
                'current' => !$compat5Installed ? 'Not found' : ($compat5Enabled ? 'Enabled' : 'Disabled'),
                'required' => 'Disabled before Joomla 5.4 → 6 upgrade',
                'status' => !$compat5Installed || !$compat5Enabled ? 'pass' : 'warning',
            ];
            if ($compat5Installed && $compat5Enabled) {
                $findings[] = [
                    'level' => 'warning',
                    'title' => 'Joomla 5 Backward Compatibility plugin is enabled',
                    'detail' => 'Joomla documents disabling the Joomla 5 Backward Compatibility plugin before the Joomla 5.4 → 6 upgrade. Test this on a staging copy first.',
                ];
            }

            $checks[] = [
                'name' => 'Joomla 6 Backward Compatibility 6 plugin',
                'current' => !$compat6Installed ? 'Not found' : ($compat6Enabled ? 'Enabled' : 'Disabled'),
                'required' => 'Installed and enabled for the Joomla 5.4 → 6 migration',
                'status' => ($compat6Installed && $compat6Enabled) ? 'pass' : 'warning',
            ];
            if (!$compat6Installed || !$compat6Enabled) {
                $findings[] = [
                    'level' => 'warning',
                    'title' => 'Joomla 6 Backward Compatibility 6 plugin is not enabled',
                    'detail' => 'Joomla documents this plugin as required and enabled for the Joomla 5.4 → 6 migration path.',
                ];
            }
        } else {
            $compat6Installed = array_key_exists('compat6', $state);
            $compat6Enabled = $state['compat6'] ?? false;
            $checks[] = [
                'name' => 'Joomla 6 Backward Compatibility 6 plugin',
                'current' => !$compat6Installed ? 'Not found' : ($compat6Enabled ? 'Enabled' : 'Disabled'),
                'required' => 'Compatibility mode; extensions should ultimately run without it',
                'status' => $compat6Installed ? 'pass' : 'warning',
            ];
            if (!$compat6Installed) {
                $findings[] = [
                    'level' => 'warning',
                    'title' => 'Joomla 6 Backward Compatibility 6 plugin was not found',
                    'detail' => 'The plugin is part of Joomla 6 compatibility infrastructure. A missing plugin can indicate an unusual or incomplete installation.',
                ];
            }
        }

        return ['checks' => $checks, 'findings' => $findings];
    }

    private function normaliseVersion(string $version): string
    {
        if (preg_match('/\d+(?:\.\d+){1,3}/', $version, $match)) {
            return $match[0];
        }
        return '0.0.0';
    }

    private function memoryLimitBytes(string $value): ?int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return $value === '-1' ? PHP_INT_MAX : null;
        }
        if (!preg_match('/^([0-9]+(?:\.[0-9]+)?)\s*([kmgt]?)/i', $value, $match)) {
            return null;
        }
        $number = (float) $match[1];
        $unit = strtolower($match[2]);
        $multipliers = ['' => 1, 'k' => 1024, 'm' => 1048576, 'g' => 1073741824, 't' => 1099511627776];
        return (int) round($number * ($multipliers[$unit] ?? 1));
    }

    /**
     * Static source scan for documented Joomla 5.4 -> 6.0 incompatibilities.
     * This is intentionally conservative: a match is reported as a code pattern
     * to review, not as proof that the extension will fail at runtime.
     */
    private function scanExtensionSource(object $row): array
    {
        $result = [
            'status' => 'not_scanned',
            'files' => 0,
            'critical' => 0,
            'warnings' => 0,
            'findings' => [],
            'rulesChecked' => 0,
            'skippedFiles' => 0,
            'unreadableFiles' => 0,
            'largeFiles' => 0,
            'ruleSet' => self::CODE_SCAN_RULESET,
        ];

        if ((string) $row->element === self::JOOMLA_HEALTH_ELEMENT) {
            $result['status'] = 'clean';
            return $result;
        }

        // Joomla's own bundled extensions are maintained and shipped as part of
        // the CMS release. Their source can legitimately contain migration
        // patterns that are not meaningful as third-party extension findings.
        // Scan third-party extensions instead of turning Joomla's own codebase
        // into a stream of false-positive migration warnings.
        if ($this->isJoomlaCoreExtension($row)) {
            $result['status'] = 'core';
            return $result;
        }

        $root = $this->findExtensionRoot($row);
        if ($root === null || !is_dir($root)) {
            $result['status'] = 'unavailable';
            return $result;
        }

        $rules = [
            ['id' => 'JPATH_PLATFORM', 'severity' => 'critical', 'pattern' => '/\bJPATH_PLATFORM\b/', 'title' => 'JPATH_PLATFORM constant', 'detail' => 'JPATH_PLATFORM is no longer globally defined in Joomla 6 unless the backward compatibility plugin is enabled. Replace it with _JEXEC for extension entry-point guards, or remove the dependency for runtime code.'],
            ['id' => 'CMS_INPUT', 'severity' => 'critical', 'pattern' => '~\bJoomla\\\\CMS\\\\Input\\\\~', 'title' => 'Joomla CMS Input namespace', 'detail' => 'Joomla\CMS\Input was removed from the normal Joomla 6 code path. Use the Joomla Framework Joomla\Input namespace.'],
            ['id' => 'CMS_FILESYSTEM', 'severity' => 'warning', 'pattern' => '~\bJoomla\\\\CMS\\\\Filesystem\\\\~', 'title' => 'Joomla CMS Filesystem namespace', 'detail' => 'Joomla\CMS\Filesystem was moved to the Joomla 6 backward compatibility plugin. Prefer Joomla\Filesystem for migration-ready code.'],
            ['id' => 'CMS_HTTP', 'severity' => 'warning', 'pattern' => '~\bJoomla\\\\CMS\\\\Http(?:\\\\|\b)~', 'title' => 'Deprecated Joomla CMS HTTP namespace', 'detail' => 'Joomla CMS HTTP is deprecated in Joomla 6. Prefer the Joomla Framework Joomla\Http package for migration-ready code.'],
            ['id' => 'BASE_APPLICATION', 'severity' => 'critical', 'pattern' => '~\bJoomla\\\\CMS\\\\Application\\\\(?:BaseApplication|CLI|CliApplication)\b~i', 'title' => 'Removed legacy application class', 'detail' => 'Legacy Joomla CMS BaseApplication/CLI classes were removed in Joomla 6. Use the framework/application equivalents documented by Joomla.'],
            ['id' => 'ADAPTER_CLASSES', 'severity' => 'critical', 'pattern' => '~\bJoomla\\\\CMS\\\\Adapter\\\\(?:Adapter|AdapterInstance)\b~', 'title' => 'Removed CMS Adapter class', 'detail' => 'Joomla\CMS\Adapter\Adapter and AdapterInstance were removed in Joomla 6 without replacement.'],
            ['id' => 'FETCH_EXTENSION_COMPATIBILITY', 'severity' => 'critical', 'pattern' => '/\bfetchExtensionCompatibility\s*\(/', 'title' => 'Removed fetchExtensionCompatibility method', 'detail' => 'Joomla 6 removed fetchExtensionCompatibility from UpdateController. Joomla documents batchextensioncompatibility as the replacement.'],
            ['id' => 'IS_CLI', 'severity' => 'critical', 'pattern' => '/(?:->|::)isCli\s*\(/', 'title' => 'Removed isCli() method', 'detail' => 'The deprecated isCli() method was removed from Joomla application classes in Joomla 6. Use an application instanceof check instead.'],
            ['id' => 'IMAGE_CREATETHUMBS', 'severity' => 'critical', 'pattern' => '/->createThumbs\s*\(/', 'title' => 'Removed createThumbs() method', 'detail' => 'Joomla 6 removed Image::createThumbs(). Use Image::createThumbnails() instead.'],
            ['id' => 'TABLE_GETINSTANCE', 'severity' => 'warning', 'pattern' => '/\bTable\s*::\s*getInstance\s*\(/', 'title' => 'Legacy Table::getInstance() usage', 'detail' => 'Joomla 6 core code now instantiates table classes directly. Review Table::getInstance() usage and migrate to explicit table classes.'],
            ['id' => 'APP_PROPERTY', 'severity' => 'warning', 'pattern' => '/\$this\s*->\s*app\b/', 'title' => 'Deprecated plugin $app property usage', 'detail' => 'For plugin classes, Joomla documents getApplication() as the replacement for the deprecated $this->app property.', 'pluginOnly' => true],
            ['id' => 'APPLICATION_INPUT_PROPERTY', 'severity' => 'warning', 'pattern' => '/Factory\s*::\s*getApplication\s*\(\)\s*->\s*input\b/', 'title' => 'Deprecated application input property', 'detail' => 'Joomla documents getInput() as the replacement for direct application input property access.'],
            ['id' => 'JLOADER_REGISTER', 'severity' => 'warning', 'pattern' => '/\bJLoader\s*::\s*register\s*\(/', 'title' => 'Deprecated JLoader::register()', 'detail' => 'JLoader::register() remains supported beyond Joomla 6 but is deprecated. Joomla 5.4 documentation says removal is planned for Joomla 7.'],
            ['id' => 'JCOMPAT_UNICODE_PROPERTIES', 'severity' => 'critical', 'pattern' => '/\bJCOMPAT_UNICODE_PROPERTIES\b/', 'title' => 'Removed JCOMPAT_UNICODE_PROPERTIES constant', 'detail' => 'Joomla 6 removed the JCOMPAT_UNICODE_PROPERTIES constant from FormRule. Extensions referencing it need to remove or replace that dependency.'],
        ];

        $result['rulesChecked'] = count($rules);
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($result['files'] >= self::CODE_SCAN_MAX_FILES) {
                break;
            }
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }
            $path = $file->getPathname();
            if ($this->isExcludedScanPath($path)) {
                continue;
            }
            if ($file->getSize() > self::CODE_SCAN_MAX_FILE_BYTES) {
                $result['skippedFiles']++;
                $result['largeFiles']++;
                continue;
            }
            if (!is_readable($path)) {
                $result['skippedFiles']++;
                $result['unreadableFiles']++;
                continue;
            }

            $contents = @file_get_contents($path);
            if ($contents === false) {
                $result['skippedFiles']++;
                $result['unreadableFiles']++;
                continue;
            }
            $result['files']++;

            $code = $this->maskPhpNonCode($contents);
            $relative = ltrim(str_replace($root, '', $path), '/\\');
            $pluginContext = $this->isPluginContext($row, $relative, $contents);
            $pluginAppPropertyContext = $this->hasPluginAppPropertyContext($row, $relative, $contents);

            // Only treat $variable->input as an application-input finding when the
            // variable is demonstrably obtained from a Joomla application getter.
            // A bare $app->input is intentionally not reported because $app may be
            // any application-defined object/parameter and static regex matching
            // cannot establish its type safely.
            $applicationVariables = [];
            if ($this->safePregMatchAll('/\$([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(?:\\\\?Joomla\\\\CMS\\\\Factory|Factory|JFactory)\s*::\s*getApplication\s*\(\s*\)\s*;/i', $code, $varMatches) > 0) {
                foreach ($varMatches[1] as $variable) {
                    $applicationVariables[] = $variable;
                }
            }
            if ($pluginContext && $this->safePregMatchAll('/\$([A-Za-z_][A-Za-z0-9_]*)\s*=\s*\$this\s*->\s*getApplication\s*\(\s*\)\s*;/i', $code, $varMatches) > 0) {
                foreach ($varMatches[1] as $variable) {
                    $applicationVariables[] = $variable;
                }
            }
            $applicationVariables = array_values(array_unique($applicationVariables));

            foreach ($rules as $rule) {
                if (!empty($rule['pluginOnly']) && !$pluginAppPropertyContext) {
                    continue;
                }
                if ($rule['id'] === 'APPLICATION_INPUT_PROPERTY') {
                    $hasKnownVariableInput = false;
                    foreach ($applicationVariables as $variable) {
                        if ($this->safePregMatch('/\$' . preg_quote($variable, '/') . '\s*->\s*input\b/', $code)) {
                            $hasKnownVariableInput = true;
                            break;
                        }
                    }
                    if (!$hasKnownVariableInput && !$this->safePregMatch($rule['pattern'], $code)) {
                        continue;
                    }
                } elseif (!$this->safePregMatch($rule['pattern'], $code)) {
                    continue;
                }

                $matches = [];
                if ($rule['id'] === 'APPLICATION_INPUT_PROPERTY') {
                    $this->safePregMatchAll($rule['pattern'], $code, $matches, PREG_OFFSET_CAPTURE);
                    foreach ($applicationVariables as $variable) {
                        $variablePattern = '/\$' . preg_quote($variable, '/') . '\s*->\s*input\b/';
                        $variableMatches = [];
                        $this->safePregMatchAll($variablePattern, $code, $variableMatches, PREG_OFFSET_CAPTURE);
                        if (!empty($variableMatches[0])) {
                            $matches[0] = array_merge($matches[0] ?? [], $variableMatches[0]);
                        }
                    }
                    if (!empty($matches[0])) {
                        usort($matches[0], static fn($a, $b) => $a[1] <=> $b[1]);
                    }
                } else {
                    $this->safePregMatchAll($rule['pattern'], $code, $matches, PREG_OFFSET_CAPTURE);
                }
                $offset = $matches[0][0][1] ?? 0;
                $line = substr_count(substr($code, 0, (int) $offset), "\n") + 1;
                $lineText = $this->getSourceLine($contents, $line);
                $context = 'source usage';
                $confidence = 'High';
                $detail = $rule['detail'];

                if ($rule['id'] === 'JPATH_PLATFORM') {
                    if ($this->safePregMatch('/defined\s*\(\s*[\'\"]JPATH_PLATFORM[\'\"]\s*\)/i', $lineText)) {
                        $context = 'entry-point guard';
                        $detail = "JPATH_PLATFORM is used as an entry-point guard. Joomla 6 removed the global constant; replace this guard with defined('_JEXEC') or die;.";
                    } else {
                        $context = 'runtime/reference usage';
                        $detail = 'JPATH_PLATFORM is referenced outside a simple entry-point guard. Joomla 6 no longer defines it globally unless the backward compatibility plugin is enabled; migrate the runtime dependency.';
                    }
                } elseif ($rule['id'] === 'APP_PROPERTY') {
                    $context = 'plugin class';
                } elseif ($rule['id'] === 'APPLICATION_INPUT_PROPERTY') {
                    $context = 'application input access';
                } elseif ($rule['id'] === 'CMS_INPUT' || $rule['id'] === 'CMS_FILESYSTEM') {
                    $context = 'namespace/import';
                }

                $result['findings'][] = [
                    'id' => $rule['id'],
                    'severity' => $rule['severity'],
                    'title' => $rule['title'],
                    'detail' => $detail,
                    'file' => $relative,
                    'line' => $line,
                    'context' => $context,
                    'confidence' => $confidence,
                    'rule' => $rule['id'],
                    'ruleSet' => self::CODE_SCAN_RULESET,
                ];

                if ($rule['severity'] === 'critical') {
                    $result['critical']++;
                } else {
                    $result['warnings']++;
                }
            }
        }

        $result['status'] = $result['critical'] > 0 ? 'issues' : ($result['warnings'] > 0 ? 'review' : 'clean');
        return $result;
    }

    private function isJoomlaCoreExtension(object $row): bool
    {
        // Core Joomla extensions normally belong to the files_joomla package.
        // Use that relationship first, then fall back to the manifest-cache
        // author marker for installations where package relationships are
        // missing or were created by an older Joomla installation.
        $packageId = (int) ($row->package_id ?? 0);

        if ($packageId > 0) {
            try {
                $db = $this->getDatabase();
                $query = $this->createDatabaseQuery($db)
                    ->select($db->quoteName('extension_id'))
                    ->from($db->quoteName('#__extensions'))
                    ->where($db->quoteName('element') . ' = ' . $db->quote('files_joomla'))
                    ->where($db->quoteName('type') . ' = ' . $db->quote('file'))
                    ->setLimit(1);
                $db->setQuery($query);
                $filesJoomlaId = (int) $db->loadResult();

                if ($filesJoomlaId > 0 && $packageId === $filesJoomlaId) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Fall through to the manifest-cache check.
            }
        }

        $cache = json_decode((string) ($row->manifest_cache ?? ''), true);
        $author = trim((string) ($cache['author'] ?? ''));
        $authorUrl = strtolower(trim((string) ($cache['authorUrl'] ?? '')));

        return $author === 'Joomla! Project'
            || str_contains($authorUrl, 'joomla.org')
            || str_contains($authorUrl, 'opensourcematters.org');
    }

    /**
     * Determine whether $this->app occurs inside a class that is actually a
     * Joomla plugin class. Extension-wide plugin detection is too broad because
     * plugin extensions can contain helpers, layouts, fields and other PHP files
     * where $this->app has an unrelated meaning.
     */
    private function hasPluginAppPropertyContext(object $row, string $relativePath, string $contents): bool
    {
        if (strtolower((string) $row->type) !== 'plugin') {
            return false;
        }

        // Tokenize the file so a $this->app occurrence is tied to a class body.
        $tokens = token_get_all($contents);
        $classDepth = 0;
        $braceDepth = 0;
        $pluginClassDepth = null;
        $pendingPluginClass = false;

        for ($i = 0, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];
            if (is_array($token)) {
                if ($token[0] === T_CLASS) {
                    $pendingPluginClass = false;
                    $extendsPlugin = false;
                    for ($j = $i + 1; $j < $count && $j < $i + 12; $j++) {
                        $next = $tokens[$j];
                        if (is_string($next) && $next === '{') {
                            break;
                        }
                        if (is_array($next) && $next[0] === T_EXTENDS) {
                            for ($k = $j + 1; $k < $count && $k < $j + 10; $k++) {
                                $part = $tokens[$k];
                                if (is_array($part) && $part[0] === T_STRING && preg_match('/^(?:CMSPlugin|ActionLogPlugin|FieldsPlugin|PrivacyPlugin)$/i', $part[1])) {
                                    $extendsPlugin = true;
                                    break;
                                }
                                if (is_string($part) && $part === '{') {
                                    break;
                                }
                            }
                            break;
                        }
                    }
                    $pendingPluginClass = $extendsPlugin;
                }

                if ($token[0] === T_VARIABLE && $token[1] === '$this' && $classDepth > 0) {
                    for ($j = $i + 1; $j < $count && $j < $i + 4; $j++) {
                        $next = $tokens[$j];
                        if (is_string($next) && $next === '->') {
                            $prop = $tokens[$j + 1] ?? null;
                            if (is_array($prop) && $prop[0] === T_STRING && $prop[1] === 'app' && $pluginClassDepth !== null) {
                                return true;
                            }
                            break;
                        }
                    }
                }
            } elseif ($token === '{') {
                $braceDepth++;
                if ($pendingPluginClass) {
                    $pluginClassDepth = $braceDepth;
                    $pendingPluginClass = false;
                }
                $classDepth = $braceDepth;
            } elseif ($token === '}') {
                if ($pluginClassDepth !== null && $braceDepth === $pluginClassDepth) {
                    $pluginClassDepth = null;
                }
                $braceDepth--;
                $classDepth = max(0, $braceDepth);
            }
        }

        return false;
    }

    private function isPluginContext(object $row, string $relativePath, string $contents): bool
    {
        if (strtolower((string) $row->type) === 'plugin') {
            return true;
        }

        $normalized = '/' . str_replace('\\', '/', $relativePath) . '/';
        if (strpos($normalized, '/src/Plugin/') !== false || strpos($normalized, '/plugin/') !== false) {
            return true;
        }

        return preg_match('/\bclass\s+\w+\s+extends\s+(?:CMSPlugin|ActionLogPlugin|FieldsPlugin|PrivacyPlugin)\b/i', $contents) === 1;
    }

    private function maskPhpNonCode(string $contents): string
    {
        $tokens = token_get_all($contents);
        $masked = '';

        foreach ($tokens as $token) {
            if (is_array($token)) {
                $text = $token[1];
                if (in_array($token[0], [T_COMMENT, T_DOC_COMMENT, T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)) {
                    $masked .= preg_replace('/[^\n]/', ' ', $text);
                } else {
                    $masked .= $text;
                }
            } else {
                $masked .= $token;
            }
        }

        return $masked;
    }

    private function getSourceLine(string $contents, int $line): string
    {
        $lines = preg_split('/\R/', $contents);
        return (string) ($lines[$line - 1] ?? '');
    }

    private function findExtensionRoot(object $row): ?string
    {
        $element = trim((string) $row->element);
        if ($element === '') {
            return null;
        }
        $isAdmin = (int) $row->client_id === 1;
        $type = strtolower((string) $row->type);
        $folder = trim((string) ($row->folder ?? ''));

        switch ($type) {
            case 'component':
                return ($isAdmin ? JPATH_ADMINISTRATOR : JPATH_ROOT) . '/components/' . $element;
            case 'module':
                return ($isAdmin ? JPATH_ADMINISTRATOR : JPATH_ROOT) . '/modules/' . $element;
            case 'plugin':
                return $folder !== '' ? JPATH_ROOT . '/plugins/' . $folder . '/' . $element : null;
            case 'template':
                return ($isAdmin ? JPATH_ADMINISTRATOR : JPATH_ROOT) . '/templates/' . $element;
            case 'library':
                return JPATH_LIBRARIES . '/' . $element;
            default:
                return null;
        }
    }

    private function safePregMatch(string $pattern, string $subject): bool
    {
        return @preg_match($pattern, $subject) === 1;
    }

    /**
     * Create a database query across Joomla 4.4, Joomla 5 and Joomla 6.
     * Joomla 4.4 database drivers do not provide createQuery().
     */
    private function createDatabaseQuery($db)
    {
        if (method_exists($db, 'createQuery')) {
            return $db->createQuery();
        }

        return $db->getQuery(true);
    }

    private function safePregMatchAll(string $pattern, string $subject, ?array &$matches = null, int $flags = 0): int
    {
        $matches = [];
        $result = @preg_match_all($pattern, $subject, $matches, $flags);

        return $result === false ? 0 : $result;
    }

    private function isExcludedScanPath(string $path): bool
    {
        $normalized = str_replace('\\', '/', $path);
        foreach (['/vendor/', '/node_modules/', '/cache/', '/tmp/', '/media/'] as $part) {
            if (strpos($normalized, $part) !== false) {
                return true;
            }
        }
        return false;
    }

    private function analyseCompatibility(object $row, ?\SimpleXMLElement $manifest, array $metadata): array
    {
        $result = [
            'status' => 'unknown',
            'statusLabel' => 'Unknown',
            'detail' => 'No usable Joomla 6 compatibility metadata was found.',
            'target' => 'Not available',
            'phpMinimum' => 'Not available',
            'metadataSource' => 'none',
            'manifestFound' => $manifest !== null,
            'updateSiteState' => $metadata['siteState'],
        ];

        // Site Health Inspector is its own compatibility scanner. It does not need a
        // vendor update site to describe the compatibility baseline of the
        // currently installed scanner itself.
        if ((string) $row->element === self::JOOMLA_HEALTH_ELEMENT) {
            $runtimePhpMinimum = version_compare(JVERSION, '6.0.0', '>=') ? '8.3' : self::JOOMLA_HEALTH_PHP_MINIMUM;
            $result['target'] = self::JOOMLA_HEALTH_TARGET;
            $result['phpMinimum'] = $runtimePhpMinimum;
            $result['metadataSource'] = 'Built-in Site Health Inspector declaration';
            $result['updateSiteState'] = 'built-in';

            if (version_compare(PHP_VERSION, $runtimePhpMinimum, '<')) {
                $result['status'] = 'critical';
                $result['statusLabel'] = 'PHP requirement not met';
                $result['detail'] = 'Site Health Inspector requires PHP ' . $runtimePhpMinimum . ' or newer on the current Joomla branch.';
            } else {
                $result['status'] = 'compatible';
                $result['statusLabel'] = 'Joomla 6 declared';
                $result['detail'] = 'Site Health Inspector includes a built-in compatibility declaration for Joomla 5.4.x and Joomla 6.x.';
            }

            return $result;
        }

        if ($metadata['siteState'] === 'none') {
            $result['statusLabel'] = 'No update site';
            $result['detail'] = 'No update site is registered for this extension, so vendor compatibility metadata cannot be retrieved automatically.';
            return $result;
        }

        if ($metadata['siteState'] === 'disabled') {
            $result['statusLabel'] = 'Update site disabled';
            $result['detail'] = 'All registered update sites for this extension are disabled. Enable the relevant update site to retrieve compatibility metadata.';
            return $result;
        }

        if ($metadata['joomla6'] !== null) {
            $target = $metadata['joomla6']['target'];
            $phpMinimum = $metadata['joomla6']['phpMinimum'];
            $result['target'] = $target !== '' ? $target : 'Not declared';
            $result['phpMinimum'] = $phpMinimum !== '' ? $phpMinimum : 'Not declared';
            $result['metadataSource'] = 'Extension update XML';

            if ($phpMinimum !== '' && version_compare(PHP_VERSION, $phpMinimum, '<')) {
                $result['status'] = 'critical';
                $result['statusLabel'] = 'PHP requirement not met';
                $result['detail'] = 'The Joomla 6 update metadata declares PHP ' . $phpMinimum . ' or newer, while this site is running PHP ' . PHP_VERSION . '.';
                return $result;
            }

            $result['status'] = 'compatible';
            $result['statusLabel'] = 'Joomla 6 declared';
            $result['detail'] = 'An enabled extension update site contains metadata targeting Joomla 6. This is a metadata check, not a guarantee of full compatibility.';
            return $result;
        }

        if ($metadata['joomla5'] !== null) {
            $result['target'] = $metadata['joomla5']['target'] !== '' ? $metadata['joomla5']['target'] : 'Not declared';
            $result['phpMinimum'] = $metadata['joomla5']['phpMinimum'] !== '' ? $metadata['joomla5']['phpMinimum'] : 'Not declared';
            $result['metadataSource'] = 'Extension update XML';
            $result['status'] = 'review';
            $result['statusLabel'] = 'Joomla 5 target';
            $result['detail'] = 'The available vendor update metadata contains a Joomla 5 target but no Joomla 6 target was found. Check for a Joomla 6-compatible release before migrating.';
            return $result;
        }

        if ($metadata['latest'] !== null) {
            $result['target'] = $metadata['latest']['target'] !== '' ? $metadata['latest']['target'] : 'Not declared';
            $result['phpMinimum'] = $metadata['latest']['phpMinimum'] !== '' ? $metadata['latest']['phpMinimum'] : 'Not declared';
            $result['metadataSource'] = 'Extension update XML';
            $result['statusLabel'] = 'Target review';
            $result['status'] = 'review';
            $result['detail'] = 'The vendor update metadata was retrieved, but no Joomla 6 target was identified. Review the vendor compatibility documentation before migrating.';
            return $result;
        }

        $result['detail'] = 'The registered update site could not provide a usable update XML entry for this extension.';
        return $result;
    }

    /**
     * Retrieve vendor update metadata from registered update sites.
     * Joomla removes targetplatform/php_minimum from #__updates after parsing,
     * so reading the original XML is necessary for this diagnostic feature.
     */
    private function getUpdateMetadata(object $row): array
    {
        $db = $this->getDatabase();
        $result = [
            'siteState' => 'none',
            'joomla6' => null,
            'joomla5' => null,
            'latest' => null,
        ];

        $query = $this->createDatabaseQuery($db)
            ->select([
                $db->quoteName('us.update_site_id'),
                $db->quoteName('us.name'),
                $db->quoteName('us.location'),
                $db->quoteName('us.enabled'),
            ])
            ->from($db->quoteName('#__update_sites', 'us'))
            ->join('INNER', $db->quoteName('#__update_sites_extensions', 'use') . ' ON ' . $db->quoteName('use.update_site_id') . ' = ' . $db->quoteName('us.update_site_id'))
            ->where($db->quoteName('use.extension_id') . ' = ' . (int) $row->extension_id)
            ->order($db->quoteName('us.update_site_id') . ' ASC');

        $db->setQuery($query);
        $sites = $db->loadObjectList() ?: [];

        if (!$sites) {
            return $result;
        }

        $enabledSites = array_values(array_filter($sites, static fn(object $site): bool => (int) $site->enabled === 1));
        if (!$enabledSites) {
            $result['siteState'] = 'disabled';
            return $result;
        }

        $result['siteState'] = 'enabled';

        foreach ($enabledSites as $site) {
            $url = trim((string) $site->location);
            if ($url === '') {
                continue;
            }

            foreach ($this->fetchUpdateEntries($url, (string) $row->element, (string) $row->type, (int) $row->client_id, (string) ($row->folder ?? '')) as $entry) {
                if ($entry['target'] === '') {
                    $result['latest'] = $this->pickNewer($result['latest'], $entry);
                    continue;
                }

                if ($this->targetMatchesMajor($entry['target'], 6)) {
                    $result['joomla6'] = $this->pickNewer($result['joomla6'], $entry);
                } elseif ($this->targetMatchesMajor($entry['target'], 5)) {
                    $result['joomla5'] = $this->pickNewer($result['joomla5'], $entry);
                }

                $result['latest'] = $this->pickNewer($result['latest'], $entry);
            }
        }

        return $result;
    }

    private function fetchUpdateEntries(string $url, string $element, string $type, int $clientId, string $folder): array
    {
        if (isset($this->updateSiteCache[$url])) {
            return $this->filterUpdateEntries($this->updateSiteCache[$url], $element, $type, $clientId, $folder);
        }

        $this->updateSiteCache[$url] = [];

        if (!$this->isSafeUpdateSiteUrl($url)) {
            return [];
        }

        try {
            $http = (new HttpFactory())->getHttp([
                'timeout' => 5,
                'userAgent' => 'Site Health Inspector/0.9.39; Joomla extension compatibility scanner',
            ]);
            $response = $http->get($url);
            $body = (string) $response->getBody();
            if ($response->getStatusCode() !== 200 || trim($body) === '' || strlen($body) > self::UPDATE_XML_MAX_BYTES) {
                return [];
            }

            $previousLibxmlErrors = libxml_use_internal_errors(true);
            try {
                $flags = defined('LIBXML_NONET') ? LIBXML_NONET : 0;
                $xml = simplexml_load_string($body, 'SimpleXMLElement', $flags);
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previousLibxmlErrors);
            }

            if (!$xml instanceof \SimpleXMLElement) {
                return [];
            }

            $nodes = $xml->xpath('//update') ?: [];
            foreach ($nodes as $node) {
                $entry = [
                    'element' => trim((string) ($node->element ?? '')),
                    'type' => strtolower(trim((string) ($node->type ?? ''))),
                    'client' => strtolower(trim((string) ($node->client ?? ''))),
                    'folder' => trim((string) ($node->folder ?? '')),
                    'version' => trim((string) ($node->version ?? '')),
                    'target' => '',
                    'phpMinimum' => trim((string) ($node->php_minimum ?? '')),
                ];

                if (isset($node->targetplatform)) {
                    $attributes = $node->targetplatform->attributes();
                    $entry['target'] = trim((string) ($attributes['version'] ?? ''));
                }

                $this->updateSiteCache[$url][] = $entry;
            }
        } catch (\Throwable $e) {
            // A vendor update site being unreachable must not break the health dashboard.
            return [];
        }

        return $this->filterUpdateEntries($this->updateSiteCache[$url], $element, $type, $clientId, $folder);
    }

    private function isSafeUpdateSiteUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return false;
        }
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = (string) ($parts['host'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            return false;
        }
        return !isset($parts['user'], $parts['pass']);
    }

    private function filterUpdateEntries(array $entries, string $element, string $type, int $clientId, string $folder): array
    {
        $client = $clientId === 1 ? 'administrator' : 'site';

        return array_values(array_filter($entries, static function (array $entry) use ($element, $type, $client, $folder): bool {
            if ($entry['element'] !== '' && $entry['element'] !== $element) {
                return false;
            }
            if ($entry['type'] !== '' && $entry['type'] !== $type) {
                return false;
            }
            if ($entry['client'] !== '' && $entry['client'] !== $client) {
                return false;
            }
            if ($type === 'plugin' && $entry['folder'] !== '' && $entry['folder'] !== $folder) {
                return false;
            }
            return true;
        }));
    }

    private function pickNewer(?array $current, array $candidate): array
    {
        if ($current === null || version_compare((string) $candidate['version'], (string) $current['version'], '>')) {
            return $candidate;
        }

        return $current;
    }

    private function targetMatchesMajor(string $target, int $major): bool
    {
        $target = trim($target);
        if ($target === '') {
            return false;
        }

        // Joomla update feeds define targetplatform@version as a regular
        // expression. Handle the documented regex form first, then common
        // literal/wildcard forms used by extension vendors.
        $candidates = [
            $major === 6 ? '6.0.0' : '5.0.0',
            $major === 6 ? '6.1.0' : '5.1.0',
            $major === 6 ? '6.2.0' : '5.2.0',
            $major === 6 ? '6.3.0' : '5.3.0',
            $major === 6 ? '6.4.0' : '5.4.0',
        ];

        foreach ($candidates as $version) {
            if ($this->targetPatternMatches($target, $version)) {
                return true;
            }
        }

        $normalized = strtolower(str_replace(['*', 'x', 'X'], '.*', $target));
        $normalized = trim($normalized);

        if (preg_match('/^' . $major . '(?:\\.\\d+)?(?:\\..*)?$/', $normalized)) {
            return true;
        }

        return false;
    }

    private function targetPatternMatches(string $target, string $version): bool
    {
        $pattern = '~^' . str_replace('~', '\~', $target) . '~';
        return @preg_match($pattern, $version) === 1;
    }

    private function getManifestValue(?\SimpleXMLElement $manifest, string $field, mixed $fallback = ''): string
    {
        if ($manifest !== null && isset($manifest->{$field})) {
            $value = trim((string) $manifest->{$field});
            if ($value !== '') {
                return $value;
            }
        }

        if (is_scalar($fallback)) {
            return trim((string) $fallback);
        }

        return '';
    }

    private function findManifestPath(object $row): ?string
    {
        $element = trim((string) $row->element);
        if ($element === '') {
            return null;
        }

        $isAdmin = (int) $row->client_id === 1;
        $type = strtolower((string) $row->type);
        $folder = trim((string) ($row->folder ?? ''));
        $candidates = [];

        switch ($type) {
            case 'component':
                $root = $isAdmin ? JPATH_ADMINISTRATOR . '/components/' . $element : JPATH_ROOT . '/components/' . $element;
                $short = preg_replace('/^com_/', '', $element);
                $candidates = [
                    $root . '/' . $element . '.xml',
                    $root . '/' . $short . '.xml',
                ];
                break;

            case 'module':
                $root = ($isAdmin ? JPATH_ADMINISTRATOR : JPATH_ROOT) . '/modules/' . $element;
                $candidates = [$root . '/' . $element . '.xml'];
                break;

            case 'plugin':
                if ($folder !== '') {
                    $root = JPATH_ROOT . '/plugins/' . $folder . '/' . $element;
                    $candidates = [$root . '/' . $element . '.xml'];
                }
                break;

            case 'template':
                $root = ($isAdmin ? JPATH_ADMINISTRATOR : JPATH_ROOT) . '/templates/' . $element;
                $candidates = [$root . '/templateDetails.xml'];
                break;

            case 'library':
                $root = JPATH_LIBRARIES . '/' . $element;
                $candidates = [$root . '/' . $element . '.xml', $root . '/manifest.xml'];
                break;
        }

        foreach (array_unique($candidates) as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function readManifest(?string $path): ?\SimpleXMLElement
    {
        if (!$path) {
            return null;
        }

        $previousLibxmlErrors = libxml_use_internal_errors(true);
        try {
            $flags = defined('LIBXML_NONET') ? LIBXML_NONET : 0;
            $xml = simplexml_load_file($path, 'SimpleXMLElement', $flags);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousLibxmlErrors);
        }

        return $xml instanceof \SimpleXMLElement ? $xml : null;
    }
}
