<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$h = $this->health;
$total = max(1, $h['passed'] + $h['critical'] + $h['warningCount']);
$score = (int) round($h['passed'] / $total * 100);
$cc = $h['compatibilityCounts'];
$rc = $h['riskCounts'];
?>
<div class="jh-wrap">
    <h1><?= Text::_('COM_SITEHEALTH_TITLE') ?> <small><?= htmlspecialchars((string) ($h['version'] ?? '0.9.39'), ENT_QUOTES, 'UTF-8') ?></small></h1>

    <div class="jh-card jh-score">
        <div>
            <span><?= Text::_('COM_SITEHEALTH_DIAGNOSTIC_SCORE') ?></span>
            <strong><?= $score ?>%</strong>
        </div>
        <div><?= Text::sprintf('COM_SITEHEALTH_SCORE_SUMMARY', (int) $h['passed'], (int) $h['warningCount'], (int) $h['critical']) ?></div>
    </div>

    <div class="jh-grid">
        <div class="jh-card">
            <h2><?= Text::_('COM_SITEHEALTH_SYSTEM') ?></h2>
            <dl>
                <dt><?= Text::_('COM_SITEHEALTH_JOOMLA') ?></dt><dd><?= htmlspecialchars($h['joomla'], ENT_QUOTES, 'UTF-8') ?></dd>
                <dt><?= Text::_('COM_SITEHEALTH_PHP') ?></dt><dd><?= htmlspecialchars($h['php'], ENT_QUOTES, 'UTF-8') ?></dd>
                <dt><?= Text::_('COM_SITEHEALTH_DATABASE') ?></dt><dd><?= htmlspecialchars((string) $h['db'], ENT_QUOTES, 'UTF-8') ?></dd>
                <dt><?= Text::_('COM_SITEHEALTH_MEMORY') ?></dt><dd><?= htmlspecialchars($h['memory'], ENT_QUOTES, 'UTF-8') ?></dd>
                <dt><?= Text::_('COM_SITEHEALTH_HTTPS') ?></dt><dd><?= $h['https'] ? Text::_('COM_SITEHEALTH_DETECTED') : Text::_('COM_SITEHEALTH_NOT_CONFIRMED') ?></dd>
                <dt><?= Text::_('COM_SITEHEALTH_DEBUG') ?></dt><dd><?= $h['debug'] ? Text::_('COM_SITEHEALTH_ENABLED') : Text::_('COM_SITEHEALTH_DISABLED') ?></dd>
                <dt><?= Text::_('COM_SITEHEALTH_SEF') ?></dt><dd><?= $h['sef'] ? Text::_('COM_SITEHEALTH_ENABLED') : Text::_('COM_SITEHEALTH_DISABLED') ?></dd>
            </dl>
        </div>

        <div class="jh-card">
            <h2><?= Text::_('COM_SITEHEALTH_JOOMLA6_READINESS') ?></h2>
            <div class="jh-status-grid">
                <div><strong><?= (int) $cc['compatible'] ?></strong><span><?= Text::_('COM_SITEHEALTH_JOOMLA6_DECLARED') ?></span></div>
                <div><strong><?= (int) $cc['review'] ?></strong><span><?= Text::_('COM_SITEHEALTH_NEEDS_REVIEW') ?></span></div>
                <div><strong><?= (int) $cc['critical'] ?></strong><span><?= Text::_('COM_SITEHEALTH_CRITICAL') ?></span></div>
                <div><strong><?= (int) $cc['unknown'] ?></strong><span><?= Text::_('COM_SITEHEALTH_UNKNOWN') ?></span></div>
            </div>
            <p class="jh-note"><?= Text::_('COM_SITEHEALTH_COMPATIBILITY_METADATA_NOTE') ?></p>
        </div>
    </div>


    <?php $audit = $h['releaseAudit'] ?? ['checks' => [], 'passed' => 0, 'total' => 0, 'status' => 'review']; ?>
    <div class="jh-card">
        <h2><?= Text::_('COM_SITEHEALTH_RELEASE_READINESS') ?></h2>
        <div class="jh-release-summary">
            <strong><?= (int) $audit['passed'] ?>/<?= (int) $audit['total'] ?></strong> <?= Text::_('COM_SITEHEALTH_CHECKS_PASSED') ?>
            <span class="jh-badge <?= $audit['status'] === 'ready' ? 'jh-badge-compatible' : 'jh-badge-review' ?>"><?= htmlspecialchars(ucfirst($audit['status']), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <p class="jh-note"><?= Text::_('COM_SITEHEALTH_RELEASE_READINESS_NOTE') ?></p>
        <div class="jh-table-wrap">
            <table class="table jh-table jh-release-table">
                <thead><tr><th><?= Text::_('COM_SITEHEALTH_CHECK') ?></th><th><?= Text::_('COM_SITEHEALTH_STATUS') ?></th><th><?= Text::_('COM_SITEHEALTH_DETAILS') ?></th></tr></thead>
                <tbody>
                <?php foreach ($audit['checks'] as $check) : ?>
                    <?php $auditClass = $check['status'] === 'pass' ? 'jh-badge-compatible' : 'jh-badge-review'; ?>
                    <tr>
                        <td><?= htmlspecialchars($check['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="jh-badge <?= $auditClass ?>"><?= htmlspecialchars(ucfirst($check['status']), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><?= htmlspecialchars($check['detail'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="jh-card">
        <h2><?= Text::_('COM_SITEHEALTH_EXTENSION_MIGRATION_RISK') ?></h2>
        <div class="jh-status-grid">
            <div><strong><?= (int) $rc['critical'] ?></strong><span><?= Text::_('COM_SITEHEALTH_CRITICAL') ?></span></div>
            <div><strong><?= (int) $rc['high'] ?></strong><span><?= Text::_('COM_SITEHEALTH_HIGH') ?></span></div>
            <div><strong><?= (int) $rc['review'] ?></strong><span><?= Text::_('COM_SITEHEALTH_REVIEW') ?></span></div>
            <div><strong><?= (int) $rc['unknown'] ?></strong><span><?= Text::_('COM_SITEHEALTH_UNKNOWN') ?></span></div>
            <div><strong><?= (int) $rc['low'] ?></strong><span><?= Text::_('COM_SITEHEALTH_LOW') ?></span></div>
        </div>
        <p class="jh-note"><?= Text::_('COM_SITEHEALTH_RISK_NOTE') ?></p>
    </div>

    <div class="jh-card">
        <h2><?= Text::_('COM_SITEHEALTH_PREFLIGHT') ?></h2>
        <p class="jh-note"><?= Text::_('COM_SITEHEALTH_PREFLIGHT_NOTE') ?></p>
        <div class="jh-table-wrap">
            <table class="table jh-table">
                <thead><tr><th><?= Text::_('COM_SITEHEALTH_CHECK') ?></th><th><?= Text::_('COM_SITEHEALTH_CURRENT') ?></th><th><?= Text::_('COM_SITEHEALTH_REQUIREMENT') ?></th><th><?= Text::_('COM_SITEHEALTH_STATUS') ?></th></tr></thead>
                <tbody>
                <?php foreach ($h['systemChecks'] as $check) : ?>
                    <?php $checkClass = 'jh-badge-' . ($check['status'] === 'critical' ? 'critical' : ($check['status'] === 'warning' ? 'review' : ($check['status'] === 'pass' ? 'compatible' : 'unknown'))); ?>
                    <tr>
                        <td><?= htmlspecialchars($check['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($check['current'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($check['required'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="jh-badge <?= $checkClass ?>"><?= htmlspecialchars(ucfirst($check['status']), ENT_QUOTES, 'UTF-8') ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="jh-card">
        <h2><?= Text::_('COM_SITEHEALTH_FINDINGS') ?></h2>
        <?php foreach ($h['warnings'] as $item) : ?>
            <div class="jh-finding jh-<?= htmlspecialchars($item['level'], ENT_QUOTES, 'UTF-8') ?>">
                <strong><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                <p><?= htmlspecialchars($item['detail'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php endforeach; ?>
        <?php if (!$h['warnings']) : ?>
            <p><?= Text::_('COM_SITEHEALTH_NO_FINDINGS') ?></p>
        <?php endif; ?>
    </div>

    <div class="jh-card">
        <h2><?= Text::_('COM_SITEHEALTH_EXTENSION_COMPATIBILITY') ?></h2>
        <p class="jh-note"><?= Text::_('COM_SITEHEALTH_EXTENSION_COMPATIBILITY_NOTE') ?></p>
        <div class="jh-table-wrap">
            <table class="table jh-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Version</th>
                        <th>Date</th>
                        <th>Author</th>
                        <th>Joomla target</th>
                        <th>PHP min.</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($h['extensions'] as $extension) : ?>
                    <?php
                    $badgeClass = 'jh-badge-' . preg_replace('/[^a-z0-9_-]/i', '', (string) $extension['status']);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($extension['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($extension['type'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($extension['version'] !== '' ? $extension['version'] : 'Not available', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($extension['creationDate'] !== '' ? $extension['creationDate'] : 'Not available', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($extension['author'] !== '' ? $extension['author'] : 'Not available', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($extension['target'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($extension['phpMinimum'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="jh-badge <?= $badgeClass ?>"><?= htmlspecialchars($extension['statusLabel'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="jh-card jh-migration-center">
        <h2><?= Text::_('COM_SITEHEALTH_MIGRATION_CENTER') ?></h2>
        <p class="jh-note"><?= Text::_('COM_SITEHEALTH_MIGRATION_CENTER_NOTE') ?></p>
        <div class="jh-migration-tools">
            <label><?= Text::_('COM_SITEHEALTH_SEARCH') ?> <input type="search" id="jh-action-search" placeholder="<?= htmlspecialchars(Text::_('COM_SITEHEALTH_EXTENSION_NAME_PLACEHOLDER'), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Risk <select id="jh-risk-filter"><option value="all"><?= Text::_('COM_SITEHEALTH_ALL') ?></option><option value="critical"><?= Text::_('COM_SITEHEALTH_CRITICAL') ?></option><option value="high"><?= Text::_('COM_SITEHEALTH_HIGH') ?></option><option value="review"><?= Text::_('COM_SITEHEALTH_REVIEW') ?></option><option value="low"><?= Text::_('COM_SITEHEALTH_LOW') ?></option></select></label>
            <button type="button" class="btn btn-secondary" id="jh-export-action-plan" disabled title="<?= htmlspecialchars(Text::_('COM_SITEHEALTH_EXPORT_FILTERED_CSV'), ENT_QUOTES, 'UTF-8') ?>"><?= Text::_('COM_SITEHEALTH_CSV_FILTERED') ?></button>
            <button type="button" class="btn btn-secondary" id="jh-export-all-action-plan" title="<?= htmlspecialchars(Text::_('COM_SITEHEALTH_EXPORT_ALL_CSV'), ENT_QUOTES, 'UTF-8') ?>"><?= Text::_('COM_SITEHEALTH_CSV_ALL') ?></button>
            <button type="button" class="btn btn-secondary" id="jh-print-report" disabled title="<?= htmlspecialchars(Text::_('COM_SITEHEALTH_PRINT_FILTERED'), ENT_QUOTES, 'UTF-8') ?>"><?= Text::_('COM_SITEHEALTH_PRINT_FILTERED_SHORT') ?></button>
            <button type="button" class="btn btn-secondary" id="jh-print-all-report" title="<?= htmlspecialchars(Text::_('COM_SITEHEALTH_PRINT_ALL'), ENT_QUOTES, 'UTF-8') ?>"><?= Text::_('COM_SITEHEALTH_PRINT_ALL_SHORT') ?></button>
            <button type="button" class="btn btn-primary" id="jh-client-report" title="<?= htmlspecialchars(Text::_('COM_SITEHEALTH_CLIENT_REPORT'), ENT_QUOTES, 'UTF-8') ?>"><?= Text::_('COM_SITEHEALTH_CLIENT_REPORT_SHORT') ?></button>
        </div>
    </div>

    <div class="jh-card jh-workspace-card" aria-label="<?= htmlspecialchars(Text::_('COM_SITEHEALTH_WORKSPACE_ARIA'), ENT_QUOTES, 'UTF-8') ?>">
        <div class="jh-workspace-heading">
            <div>
                <h2><?= Text::_('COM_SITEHEALTH_WORKSPACE_BACKUP') ?></h2>
                <p class="jh-note"><?= Text::_('COM_SITEHEALTH_WORKSPACE_NOTE') ?></p>
            </div>
            <div class="jh-workspace-actions">
                <button type="button" class="btn btn-primary" id="jh-export-workspace"><span aria-hidden="true">&#8681;</span> <?= Text::_('COM_SITEHEALTH_EXPORT_WORKSPACE') ?></button>
                <button type="button" class="btn btn-outline-secondary" id="jh-import-workspace"><span aria-hidden="true">&#8682;</span> <?= Text::_('COM_SITEHEALTH_IMPORT_WORKSPACE') ?></button>
                <input type="file" id="jh-workspace-file" accept=".json,application/json" hidden>
            </div>
        </div>
        <p class="jh-workspace-hint"><strong><?= Text::_('COM_SITEHEALTH_EXPORT') ?></strong> <?= Text::_('COM_SITEHEALTH_EXPORT_NOTE') ?> <strong><?= Text::_('COM_SITEHEALTH_IMPORT') ?></strong> <?= Text::_('COM_SITEHEALTH_IMPORT_NOTE') ?></p>
    </div>

    <div class="jh-card" id="jh-action-plan-card">
        <h2><?= Text::_('COM_SITEHEALTH_ACTION_PLAN') ?></h2>
        <p class="jh-note">A focused checklist of extensions that need attention before a Joomla 6 migration. <strong><?= Text::_('COM_SITEHEALTH_REVIEW') ?></strong> <?= Text::_('COM_SITEHEALTH_REVIEW_NOTE') ?></p>
        <div class="jh-progress-summary" id="jh-progress-summary" aria-live="polite"><strong><?= Text::_('COM_SITEHEALTH_PROGRESS') ?>:</strong> <span id="jh-progress-pending">0 <?= Text::_('COM_SITEHEALTH_PENDING') ?></span> · <span id="jh-progress-active">0 <?= Text::_('COM_SITEHEALTH_IN_PROGRESS') ?></span> · <span id="jh-progress-completed">0 <?= Text::_('COM_SITEHEALTH_COMPLETED') ?></span></div>
        <p class="jh-note"><?= Text::_('COM_SITEHEALTH_PROGRESS_NOTE') ?></p>
        <div class="jh-workspace-summary" id="jh-workspace-summary" aria-live="polite" aria-label="<?= htmlspecialchars(Text::_('COM_SITEHEALTH_WORKSPACE_SUMMARY'), ENT_QUOTES, 'UTF-8') ?>">
            <div class="jh-workspace-summary-head"><strong><?= Text::_('COM_SITEHEALTH_WORKSPACE_SUMMARY') ?></strong><span id="jh-workspace-completion">0% complete</span></div>
            <div class="jh-workspace-summary-grid">
                <div><strong id="jh-workspace-total">0</strong><span><?= Text::_('COM_SITEHEALTH_TOTAL_ACTIONS') ?></span></div>
                <div><strong id="jh-workspace-completed">0</strong><span><?= Text::_('COM_SITEHEALTH_COMPLETED') ?></span></div>
                <div><strong id="jh-workspace-active">0</strong><span><?= Text::_('COM_SITEHEALTH_IN_PROGRESS') ?></span></div>
                <div><strong id="jh-workspace-pending">0</strong><span><?= Text::_('COM_SITEHEALTH_PENDING') ?></span></div>
                <div><strong id="jh-workspace-notes">0</strong><span><?= Text::_('COM_SITEHEALTH_WITH_NOTES') ?></span></div>
            </div>
            
        </div>
        <div class="jh-table-wrap">
            <table class="table jh-table jh-action-table">
                <thead>
                    <tr><th><?= Text::_('COM_SITEHEALTH_EXTENSION') ?></th><th><?= Text::_('COM_SITEHEALTH_RISK') ?></th><th><?= Text::_('COM_SITEHEALTH_COMPATIBILITY') ?></th><th><?= Text::_('COM_SITEHEALTH_CODE_SCAN') ?></th><th><?= Text::_('COM_SITEHEALTH_NEXT_ACTION') ?></th><th><?= Text::_('COM_SITEHEALTH_PROGRESS') ?></th><th><?= Text::_('COM_SITEHEALTH_MIGRATION_NOTES') ?></th></tr>
                </thead>
                <tbody id="jh-action-plan-body">
                <?php $actionRows = 0; ?>
                <?php foreach ($h['extensions'] as $extension) : ?>
                    <?php
                    if (empty($extension['actions'])) {
                        continue;
                    }
                    ?>
                    <?php $actionRows++; ?>
                    <?php
                    $riskClass = 'jh-badge-' . ($extension['risk'] === 'critical' ? 'critical' : ($extension['risk'] === 'high' ? 'review' : ($extension['risk'] === 'low' ? 'compatible' : 'unknown')));
                    $compatClass = 'jh-badge-' . ($extension['status'] === 'critical' ? 'critical' : ($extension['status'] === 'compatible' ? 'compatible' : ($extension['status'] === 'review' ? 'review' : 'unknown')));
                    $scan = $extension['codeScan'];
                    $scanLabel = $scan['critical'] > 0 ? Text::_('COM_SITEHEALTH_CRITICAL_FINDINGS') : ($scan['warnings'] > 0 ? $scan['warnings'] . ' ' . Text::_($scan['warnings'] === 1 ? 'COM_SITEHEALTH_REVIEW_ITEM' : 'COM_SITEHEALTH_REVIEW_ITEMS') : ($scan['status'] === 'clean' ? Text::_('COM_SITEHEALTH_NO_MATCHES') : ($scan['status'] === 'core' ? Text::_('COM_SITEHEALTH_JOOMLA_CORE') : Text::_('COM_SITEHEALTH_NOT_SCANNED'))));
                    $scanClass = $scan['critical'] > 0 ? 'critical' : ($scan['warnings'] > 0 ? 'review' : 'compatible');
                    ?>
                    <tr class="jh-action-row" data-risk="<?= htmlspecialchars((string) $extension['risk'], ENT_QUOTES, 'UTF-8') ?>" data-extension="<?= htmlspecialchars(strtolower((string) $extension['name']), ENT_QUOTES, 'UTF-8') ?>" data-extension-type="<?= htmlspecialchars(strtolower((string) $extension['type']), ENT_QUOTES, 'UTF-8') ?>" data-progress-key="<?= htmlspecialchars(strtolower((string) $extension['type'] . '|' . $extension['name']), ENT_QUOTES, 'UTF-8') ?>">
                        <td><strong><?= htmlspecialchars($extension['name'], ENT_QUOTES, 'UTF-8') ?></strong><br><small><?= htmlspecialchars($extension['type'], ENT_QUOTES, 'UTF-8') ?></small></td>
                        <td><span class="jh-badge <?= $riskClass ?>"><?= htmlspecialchars(ucfirst($extension['risk']), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><span class="jh-badge <?= $compatClass ?>"><?= htmlspecialchars($extension['statusLabel'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><span class="jh-badge jh-badge-<?= $scanClass ?>"><?= htmlspecialchars($scanLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td>
                            <ul class="jh-action-list"><?php foreach ($extension['actions'] as $action) : ?><li><?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul>
                            <details class="jh-test-checklist">
                                <summary style="display:inline-flex!important;flex-flow:row nowrap!important;align-items:center!important;white-space:nowrap!important;width:max-content;max-width:100%;gap:.4rem"><span class="jh-checklist-summary-title" style="display:inline-block!important;flex:0 0 auto!important;white-space:nowrap!important"><?= Text::_('COM_SITEHEALTH_CHECKS') ?></span><span class="jh-checklist-count" aria-live="polite" style="display:inline-block!important;flex:0 0 auto!important;white-space:nowrap!important">0/5</span><span class="jh-checklist-chevron" aria-hidden="true" style="flex:0 0 6px"></span></summary>
                                <div class="jh-checklist-panel"><div class="jh-checklist-items" role="list" style="display:flex!important;flex-direction:column!important;flex-wrap:nowrap!important;align-items:stretch!important;gap:.35rem!important;width:100%!important;margin:0!important">
                                    <label role="listitem" style="display:flex!important;flex:0 0 auto!important;flex-direction:row!important;align-items:center!important;width:100%!important;min-width:0!important;box-sizing:border-box!important;grid-column:auto!important;white-space:normal!important"><input type="checkbox" value="backup"><span><?= Text::_('COM_SITEHEALTH_BACKUP_CONFIRMED') ?></span></label>
                                    <label role="listitem" style="display:flex!important;flex:0 0 auto!important;flex-direction:row!important;align-items:center!important;width:100%!important;min-width:0!important;box-sizing:border-box!important;grid-column:auto!important;white-space:normal!important"><input type="checkbox" value="staging"><span><?= Text::_('COM_SITEHEALTH_STAGING_DONE') ?></span></label>
                                    <label role="listitem" style="display:flex!important;flex:0 0 auto!important;flex-direction:row!important;align-items:center!important;width:100%!important;min-width:0!important;box-sizing:border-box!important;grid-column:auto!important;white-space:normal!important"><input type="checkbox" value="vendor"><span><?= Text::_('COM_SITEHEALTH_VENDOR_CHECKED') ?></span></label>
                                    <label role="listitem" style="display:flex!important;flex:0 0 auto!important;flex-direction:row!important;align-items:center!important;width:100%!important;min-width:0!important;box-sizing:border-box!important;grid-column:auto!important;white-space:normal!important"><input type="checkbox" value="features"><span><?= Text::_('COM_SITEHEALTH_FEATURES_TESTED') ?></span></label>
                                    <label role="listitem" style="display:flex!important;flex:0 0 auto!important;flex-direction:row!important;align-items:center!important;width:100%!important;min-width:0!important;box-sizing:border-box!important;grid-column:auto!important;white-space:normal!important"><input type="checkbox" value="postmigration"><span><?= Text::_('COM_SITEHEALTH_J6_RETEST') ?></span></label>
                                </div></div>
                            </details>
                        </td>
                        <td><select class="jh-progress-select" aria-label="Migration progress for <?= htmlspecialchars($extension['name'], ENT_QUOTES, 'UTF-8') ?>"><option value="pending"><?= Text::_('COM_SITEHEALTH_PENDING') ?></option><option value="in_progress"><?= Text::_('COM_SITEHEALTH_IN_PROGRESS') ?></option><option value="completed"><?= Text::_('COM_SITEHEALTH_COMPLETED') ?></option></select></td>
                        <td><textarea class="jh-migration-note" rows="2" maxlength="2000" aria-label="Migration notes for <?= htmlspecialchars($extension['name'], ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars(Text::_('COM_SITEHEALTH_ADD_NOTES'), ENT_QUOTES, 'UTF-8') ?>"></textarea></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($actionRows === 0) : ?>
                    <tr><td colspan="7"><strong><?= Text::_('COM_SITEHEALTH_NO_MIGRATION_ACTIONS') ?></strong> <?= Text::_('COM_SITEHEALTH_NO_MIGRATION_ACTIONS_NOTE') ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="jh-card">
        <h2><?= Text::_('COM_SITEHEALTH_CODE_COMPATIBILITY_SCAN') ?></h2>
        <p class="jh-note"><?= Text::_('COM_SITEHEALTH_CODE_SCAN_NOTE') ?></p>
        <div class="jh-table-wrap">
            <table class="table jh-table">
                <thead>
                    <tr><th><?= Text::_('COM_SITEHEALTH_EXTENSION') ?></th><th><?= Text::_('COM_SITEHEALTH_FILES_SCANNED') ?></th><th><?= Text::_('COM_SITEHEALTH_RULES') ?></th><th><?= Text::_('COM_SITEHEALTH_CRITICAL') ?></th><th><?= Text::_('COM_SITEHEALTH_REVIEW') ?></th><th><?= Text::_('COM_SITEHEALTH_CODE_STATUS') ?></th></tr>
                </thead>
                <tbody>
                <?php foreach ($h['extensions'] as $extension) : ?>
                    <?php $scan = $extension['codeScan']; $scanClass = 'jh-badge-' . ($scan['critical'] > 0 ? 'critical' : ($scan['warnings'] > 0 ? 'review' : 'compatible'));
                    $scanStatus = $scan['status'] === 'core' ? Text::_('COM_SITEHEALTH_JOOMLA_CORE') : ($scan['status'] === 'clean' ? Text::_('COM_SITEHEALTH_NO_MATCHES') : ($scan['status'] === 'issues' ? 'Issues found' : ($scan['status'] === 'review' ? 'Review' : Text::_('COM_SITEHEALTH_NOT_SCANNED')))); ?>
                    <tr>
                        <td><?= htmlspecialchars($extension['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) $scan['files'] ?><?php if (!empty($scan['skippedFiles'])) : ?><br><small><?= (int) $scan['skippedFiles'] ?> skipped</small><?php endif; ?></td>
                        <td><?= (int) ($scan['rulesChecked'] ?? 0) ?></td>
                        <td><?= (int) $scan['critical'] ?></td>
                        <td><?= (int) $scan['warnings'] ?></td>
                        <td><span class="jh-badge <?= $scanClass ?>"><?= $scanStatus ?></span></td>
                    </tr>
                    <?php if (!empty($scan['findings'])) : ?>
                        <?php foreach ($scan['findings'] as $finding) : ?>
                            <tr class="jh-code-finding">
                                <td colspan="6"><strong><?= htmlspecialchars($finding['title'], ENT_QUOTES, 'UTF-8') ?></strong> — <?= htmlspecialchars($finding['file'], ENT_QUOTES, 'UTF-8') ?>:<?= (int) $finding['line'] ?> — <?= htmlspecialchars($finding['detail'], ENT_QUOTES, 'UTF-8') ?> <span class="jh-finding-meta">Rule: <?= htmlspecialchars((string) ($finding['rule'] ?? ''), ENT_QUOTES, 'UTF-8') ?> · Context: <?= htmlspecialchars((string) ($finding['context'] ?? 'source usage'), ENT_QUOTES, 'UTF-8') ?> · Confidence: <?= htmlspecialchars((string) ($finding['confidence'] ?? 'High'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="jh-card"><p class="jh-note">Scan generated: <?= htmlspecialchars((string) ($h['scanTime'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p></div>

    <div class="jh-card jh-important">
        <h2><?= Text::_('COM_SITEHEALTH_IMPORTANT_NOTES') ?></h2>
        <ul>
            <?php foreach ($h['importantNotes'] as $note) : ?>
                <li><?= htmlspecialchars($note, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>


<script>
    var SiteHealthLang = <?php echo json_encode(['COM_SITEHEALTH_CSV_EXTENSION' => Text::_('COM_SITEHEALTH_CSV_EXTENSION'),'COM_SITEHEALTH_CSV_RISK' => Text::_('COM_SITEHEALTH_CSV_RISK'),'COM_SITEHEALTH_CSV_COMPATIBILITY' => Text::_('COM_SITEHEALTH_CSV_COMPATIBILITY'),'COM_SITEHEALTH_CSV_CODE_SCAN' => Text::_('COM_SITEHEALTH_CSV_CODE_SCAN'),'COM_SITEHEALTH_CSV_NEXT_ACTION' => Text::_('COM_SITEHEALTH_CSV_NEXT_ACTION'),'COM_SITEHEALTH_CSV_PROGRESS' => Text::_('COM_SITEHEALTH_CSV_PROGRESS'),'COM_SITEHEALTH_CSV_MIGRATION_NOTES' => Text::_('COM_SITEHEALTH_CSV_MIGRATION_NOTES'),'COM_SITEHEALTH_BACKUP_TOO_LARGE' => Text::_('COM_SITEHEALTH_BACKUP_TOO_LARGE'),'COM_SITEHEALTH_INVALID_BACKUP' => Text::_('COM_SITEHEALTH_INVALID_BACKUP'),'COM_SITEHEALTH_IMPORT_CONFIRM' => Text::_('COM_SITEHEALTH_IMPORT_CONFIRM'),'COM_SITEHEALTH_STORAGE_FAIL' => Text::_('COM_SITEHEALTH_STORAGE_FAIL'),'COM_SITEHEALTH_IMPORT_SUCCESS' => Text::_('COM_SITEHEALTH_IMPORT_SUCCESS'),'COM_SITEHEALTH_IMPORT_READ_FAIL' => Text::_('COM_SITEHEALTH_IMPORT_READ_FAIL'),'COM_SITEHEALTH_FILE_READ_FAIL' => Text::_('COM_SITEHEALTH_FILE_READ_FAIL'),'COM_SITEHEALTH_FILTER_SEARCH_REQUIRED' => Text::_('COM_SITEHEALTH_FILTER_SEARCH_REQUIRED'),'COM_SITEHEALTH_PRINT_TITLE' => Text::_('COM_SITEHEALTH_PRINT_TITLE'),'COM_SITEHEALTH_PRINT_ALL_DESC' => Text::_('COM_SITEHEALTH_PRINT_ALL_DESC'),'COM_SITEHEALTH_PRINT_FILTER_DESC' => Text::_('COM_SITEHEALTH_PRINT_FILTER_DESC'),'COM_SITEHEALTH_PRINT_FRAME_TITLE' => Text::_('COM_SITEHEALTH_PRINT_FRAME_TITLE'),'COM_SITEHEALTH_CLIENT_FRAME_TITLE' => Text::_('COM_SITEHEALTH_CLIENT_FRAME_TITLE'),'COM_SITEHEALTH_CLIENT_REPORT_TITLE' => Text::_('COM_SITEHEALTH_CLIENT_REPORT_TITLE'),'COM_SITEHEALTH_CLIENT_REPORT_SUBTITLE' => Text::_('COM_SITEHEALTH_CLIENT_REPORT_SUBTITLE'),'COM_SITEHEALTH_REPORT_GENERATED' => Text::_('COM_SITEHEALTH_REPORT_GENERATED'),'COM_SITEHEALTH_INTERPRETATION' => Text::_('COM_SITEHEALTH_INTERPRETATION'),'COM_SITEHEALTH_INTERPRETATION_TEXT' => Text::_('COM_SITEHEALTH_INTERPRETATION_TEXT'),'COM_SITEHEALTH_REPORT_ACTION_PLAN' => Text::_('COM_SITEHEALTH_REPORT_ACTION_PLAN'),'COM_SITEHEALTH_REPORT_ACTION_PLAN_NOTE' => Text::_('COM_SITEHEALTH_REPORT_ACTION_PLAN_NOTE'),'COM_SITEHEALTH_RECOMMENDED_ACTION' => Text::_('COM_SITEHEALTH_RECOMMENDED_ACTION'),'COM_SITEHEALTH_VERIFICATION_CHECKLIST' => Text::_('COM_SITEHEALTH_VERIFICATION_CHECKLIST'),'COM_SITEHEALTH_REPORT_J6_SCAN' => Text::_('COM_SITEHEALTH_REPORT_J6_SCAN')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
<script>
(function () {
    'use strict';
    var jhLang = SiteHealthLang;
    document.documentElement.classList.add('jh-no-horizontal-scroll');
    if (document.body) document.body.classList.add('jh-no-horizontal-scroll');
    // Joomla administrator templates can place the component inside a wider
    // layout container. Constrain that container as well so the component
    // cannot create a page-level horizontal scrollbar.
    var jhRoot = document.querySelector('.jh-wrap');
    if (jhRoot) {
        var jhAncestor = jhRoot.parentElement;
        while (jhAncestor) {
            if (jhAncestor.matches && jhAncestor.matches('.container-main, main, #content, .content')) {
                jhAncestor.classList.add('jh-admin-no-horizontal-scroll');
                break;
            }
            jhAncestor = jhAncestor.parentElement;
        }
    }

    var search = document.getElementById('jh-action-search');
    var risk = document.getElementById('jh-risk-filter');
    var body = document.getElementById('jh-action-plan-body');
    var rows = body ? Array.prototype.slice.call(body.querySelectorAll('.jh-action-row')) : [];
    var progressStorageKey = 'sitehealth:migration-progress:v1';
    var notesStorageKey = 'sitehealth:migration-notes:v1';
    var checklistStorageKey = 'sitehealth:migration-checklist:v1';
    var notesState = {};
    try { notesState = JSON.parse(window.localStorage.getItem(notesStorageKey) || '{}') || {}; } catch (notesReadError) { notesState = {}; }
    var progressState = {};
    try { progressState = JSON.parse(window.localStorage.getItem(progressStorageKey) || '{}') || {}; } catch (storageReadError) { progressState = {}; }
    var checklistState = {};
    try { checklistState = JSON.parse(window.localStorage.getItem(checklistStorageKey) || '{}') || {}; } catch (checklistReadError) { checklistState = {}; }

    function getRowStateKey(row) {
        return row.getAttribute('data-progress-key') || row.getAttribute('data-extension') || '';
    }

    function findSavedState(state, key) {
        if (!state || typeof state !== 'object') return null;
        if (Object.prototype.hasOwnProperty.call(state, key)) return state[key];
        var lowerKey = String(key).toLowerCase();
        var keys = Object.keys(state);
        for (var i = 0; i < keys.length; i++) {
            if (String(keys[i]).toLowerCase() === lowerKey) return state[keys[i]];
        }
        var extensionOnly = lowerKey.indexOf('|') !== -1 ? lowerKey.split('|').pop() : lowerKey;
        for (var j = 0; j < keys.length; j++) {
            var candidate = String(keys[j]).toLowerCase();
            if (candidate === extensionOnly || candidate.split('|').pop() === extensionOnly) return state[keys[j]];
        }
        return null;
    }

    function updateProgressSummary() {
        var counts = {pending: 0, in_progress: 0, completed: 0};
        rows.forEach(function (row) {
            var control = row.querySelector('.jh-progress-select');
            var status = control && counts.hasOwnProperty(control.value) ? control.value : 'pending';
            counts[status]++;
        });
        var pending = document.getElementById('jh-progress-pending');
        var active = document.getElementById('jh-progress-active');
        var completed = document.getElementById('jh-progress-completed');
        if (pending) pending.textContent = counts.pending + ' pending';
        if (active) active.textContent = counts.in_progress + ' in progress';
        if (completed) completed.textContent = counts.completed + ' completed';

        var total = rows.length;
        var noteCount = 0;
        rows.forEach(function (row) {
            var note = row.querySelector('.jh-migration-note');
            if (note && note.value.trim() !== '') noteCount++;
        });
        var completion = total ? Math.round((counts.completed / total) * 100) : 0;
        var totalEl = document.getElementById('jh-workspace-total');
        var completedEl = document.getElementById('jh-workspace-completed');
        var activeEl = document.getElementById('jh-workspace-active');
        var pendingEl = document.getElementById('jh-workspace-pending');
        var notesEl = document.getElementById('jh-workspace-notes');
        var completionEl = document.getElementById('jh-workspace-completion');
        if (totalEl) totalEl.textContent = total;
        if (completedEl) completedEl.textContent = counts.completed;
        if (activeEl) activeEl.textContent = counts.in_progress;
        if (pendingEl) pendingEl.textContent = counts.pending;
        if (notesEl) notesEl.textContent = noteCount;
        if (completionEl) completionEl.textContent = completion + '% complete';
    }

    function updateChecklistDisplay(row) {
        var details = row.querySelector('.jh-test-checklist');
        if (!details) return;
        var checks = Array.prototype.slice.call(details.querySelectorAll('input[type=checkbox]'));
        var done = checks.filter(function (item) { return item.checked; }).length;
        var count = details.querySelector('.jh-checklist-count');
        if (count) count.textContent = done + '/' + checks.length;
    }

    rows.forEach(function (row) {
        var key = getRowStateKey(row);
        var savedChecks = findSavedState(checklistState, key) || {};
        var checklist = row.querySelector('.jh-test-checklist');
        if (checklist) {
            Array.prototype.forEach.call(checklist.querySelectorAll('input[type=checkbox]'), function (item) {
                item.checked = savedChecks[item.value] === true;
                item.addEventListener('change', function () {
                    if (!checklistState[key] || typeof checklistState[key] !== 'object') checklistState[key] = {};
                    checklistState[key][item.value] = item.checked;
                    try { window.localStorage.setItem(checklistStorageKey, JSON.stringify(checklistState)); } catch (checklistWriteError) { /* Current-page checklist remains usable. */ }
                    updateChecklistDisplay(row);
                });
            });
            updateChecklistDisplay(row);
        }
    });

    rows.forEach(function (row) {
        var control = row.querySelector('.jh-progress-select');
        if (!control) return;
        var key = getRowStateKey(row);
        var savedProgress = findSavedState(progressState, key);
        control.value = typeof savedProgress === 'string' ? savedProgress : 'pending';
        control.addEventListener('change', function () {
            progressState[key] = control.value;
            try { window.localStorage.setItem(progressStorageKey, JSON.stringify(progressState)); } catch (storageWriteError) { /* Keep the current-page control usable if storage is unavailable. */ }
            updateProgressSummary();
        });
        var notesControl = row.querySelector('.jh-migration-note');
        if (notesControl) {
            var savedNote = findSavedState(notesState, key);
            notesControl.value = typeof savedNote === 'string' ? savedNote : '';
            notesControl.addEventListener('input', function () {
                notesState[key] = notesControl.value;
                updateProgressSummary();
                try { window.localStorage.setItem(notesStorageKey, JSON.stringify(notesState)); } catch (notesWriteError) { /* Keep the current-page editor usable if storage is unavailable. */ }
                updateProgressSummary();
            });
        }
    });
    updateProgressSummary();

    // Portable migration workspace backup. Only user-entered progress and notes
    // are exported; no site URL, account information, or diagnostic data is included.
    function workspacePayload() {
        var cleanProgress = {};
        var cleanNotes = {};
        var cleanChecklist = {};
        // Export every visible Migration Action Plan item, including untouched
        // rows whose current status is still Pending.
        rows.forEach(function (row) {
            var key = row.getAttribute('data-progress-key') || row.getAttribute('data-extension') || '';
            if (!key) return;
            var progressControl = row.querySelector('.jh-progress-select');
            var notesControl = row.querySelector('.jh-migration-note');
            var status = progressControl ? progressControl.value : (progressState[key] || 'pending');
            cleanProgress[key] = ['pending', 'in_progress', 'completed'].indexOf(status) !== -1 ? status : 'pending';
            var note = notesControl ? notesControl.value : notesState[key];
            if (typeof note === 'string' && note.trim() !== '') cleanNotes[key] = note.slice(0, 2000);
            var checks = {};
            var checklist = row.querySelector('.jh-test-checklist');
            if (checklist) Array.prototype.forEach.call(checklist.querySelectorAll('input[type=checkbox]'), function (item) { checks[item.value] = item.checked === true; });
            if (Object.keys(checks).length) cleanChecklist[key] = checks;
        });
        return {
            product: 'Site Health Inspector',
            format: 'migration-workspace',
            schemaVersion: 1,
            exportedAt: new Date().toISOString(),
            actionPlanItems: rows.length,
            progress: cleanProgress,
            notes: cleanNotes,
            checklist: cleanChecklist
        };
    }

    var exportWorkspaceButton = document.getElementById('jh-export-workspace');
    if (exportWorkspaceButton) exportWorkspaceButton.addEventListener('click', function () {
        var blob = new Blob([JSON.stringify(workspacePayload(), null, 2)], {type: 'application/json;charset=utf-8'});
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = 'site-health-inspector-migration-workspace.json';
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
    });

    var importWorkspaceButton = document.getElementById('jh-import-workspace');
    var workspaceFile = document.getElementById('jh-workspace-file');
    if (importWorkspaceButton && workspaceFile) {
        importWorkspaceButton.addEventListener('click', function () { workspaceFile.click(); });
        workspaceFile.addEventListener('change', function () {
            var file = workspaceFile.files && workspaceFile.files[0];
            if (!file) return;
            if (file.size > 1024 * 1024) {
                window.alert(jhLang.COM_SITEHEALTH_BACKUP_TOO_LARGE);
                workspaceFile.value = '';
                return;
            }
            var reader = new FileReader();
            reader.onload = function () {
                try {
                    var payload = JSON.parse(String(reader.result || ''));
                    if (!payload || payload.product !== 'Site Health Inspector' || payload.format !== 'migration-workspace' || payload.schemaVersion !== 1 || typeof payload.progress !== 'object' || typeof payload.notes !== 'object' || !payload.progress || !payload.notes || Array.isArray(payload.progress) || Array.isArray(payload.notes)) {
                        throw new Error(jhLang.COM_SITEHEALTH_INVALID_BACKUP);
                    }
                    var importedProgress = {};
                    var importedNotes = {};
                    var importedChecklist = {};
                    if (payload.checklist && typeof payload.checklist === 'object' && !Array.isArray(payload.checklist)) {
                        Object.keys(payload.checklist).slice(0, 5000).forEach(function (key) {
                            var entry = payload.checklist[key];
                            if (typeof key !== 'string' || key.length > 512 || !entry || typeof entry !== 'object' || Array.isArray(entry)) return;
                            var allowed = ['backup', 'staging', 'vendor', 'features', 'postmigration'];
                            var clean = {};
                            allowed.forEach(function (item) {
                                // Preserve both checked=true and explicitly unchecked=false states.
                                // Older backups may omit unchecked items; those remain false.
                                clean[item] = entry[item] === true;
                            });
                            importedChecklist[key] = clean;
                        });
                    }
                    Object.keys(payload.progress).slice(0, 5000).forEach(function (key) {
                        var value = payload.progress[key];
                        if (typeof key === 'string' && key.length <= 512 && ['pending', 'in_progress', 'completed'].indexOf(value) !== -1) importedProgress[key] = value;
                    });
                    Object.keys(payload.notes).slice(0, 5000).forEach(function (key) {
                        var value = payload.notes[key];
                        if (typeof key === 'string' && key.length <= 512 && typeof value === 'string') importedNotes[key] = value.slice(0, 2000);
                    });
                    var clearAllNotes = Object.keys(payload.notes).length === 0;
                    var sourceSummary = payload.source && typeof payload.source === 'object' ? ('\nSource: Site Health Inspector ' + (payload.source.version || 'unknown') + (payload.source.joomla ? ' / Joomla ' + payload.source.joomla : '') + (payload.source.php ? ' / PHP ' + payload.source.php : '')) : '';
                    var noteSummary = clearAllNotes ? '\nWARNING: The imported notes object is empty. All existing migration notes will be cleared.' : '';
                    if (!window.confirm('Import ' + Object.keys(importedProgress).length + ' progress entries, ' + Object.keys(importedNotes).length + ' migration notes and ' + Object.keys(importedChecklist).length + ' checklists?' + sourceSummary + noteSummary + '\n\nImported entries update matching records; unrelated progress and checklist entries are preserved.')) return;
                    var mergedProgress = Object.assign({}, progressState, importedProgress);
                    var mergedNotes = clearAllNotes ? {} : Object.assign({}, notesState, importedNotes);
                    Object.keys(importedNotes).forEach(function (key) {
                        if (importedNotes[key].trim() === '') delete mergedNotes[key];
                    });
                    var mergedChecklist = Object.assign({}, checklistState, importedChecklist);
                    try {
                        window.localStorage.setItem(progressStorageKey, JSON.stringify(mergedProgress));
                        window.localStorage.setItem(notesStorageKey, JSON.stringify(mergedNotes));
                        window.localStorage.setItem(checklistStorageKey, JSON.stringify(mergedChecklist));
                    } catch (storageError) {
                        window.alert(jhLang.COM_SITEHEALTH_STORAGE_FAIL);
                        return;
                    }
                    progressState = mergedProgress;
                    notesState = mergedNotes;
                    checklistState = mergedChecklist;
                    rows.forEach(function (row) {
                        var key = getRowStateKey(row);
                        var progressControl = row.querySelector('.jh-progress-select');
                        var notesControl = row.querySelector('.jh-migration-note');
                        if (progressControl) {
                            var importedProgressValue = findSavedState(progressState, key);
                            progressControl.value = typeof importedProgressValue === 'string' ? importedProgressValue : 'pending';
                        }
                        if (notesControl) var savedNote = findSavedState(notesState, key);
            notesControl.value = typeof savedNote === 'string' ? savedNote : '';
                        var checklist = row.querySelector('.jh-test-checklist');
                        if (checklist) {
                            var importedChecks = findSavedState(checklistState, key) || {};
                            Array.prototype.forEach.call(checklist.querySelectorAll('input[type=checkbox]'), function (item) { item.checked = importedChecks[item.value] === true; });
                            updateChecklistDisplay(row);
                        }
                    });
                    updateProgressSummary();
                    window.alert(jhLang.COM_SITEHEALTH_IMPORT_SUCCESS);
                } catch (importError) {
                    window.alert(importError.message || jhLang.COM_SITEHEALTH_IMPORT_READ_FAIL);
                } finally {
                    workspaceFile.value = '';
                }
            };
            reader.onerror = function () { window.alert(jhLang.COM_SITEHEALTH_FILE_READ_FAIL); workspaceFile.value = ''; };
            reader.readAsText(file);
        });
    }

    function applyFilter() {
        var term = search ? search.value.trim().toLowerCase() : '';
        var selected = risk ? risk.value : 'all';
        rows.forEach(function (row) {
            var matchesName = !term || (row.getAttribute('data-extension') || '').indexOf(term) !== -1;
            var matchesRisk = selected === 'all' || row.getAttribute('data-risk') === selected;
            row.style.display = matchesName && matchesRisk ? '' : 'none';
        });
    }
    if (search) search.addEventListener('input', function () { applyFilter(); updateFilteredActionState(); updateWorkspaceActionDetails(); });
    if (risk) risk.addEventListener('change', function () { applyFilter(); updateFilteredActionState(); updateWorkspaceActionDetails(); });

    function buildCsv(sourceRows) {
        var csv = [[jhLang.COM_SITEHEALTH_CSV_EXTENSION, jhLang.COM_SITEHEALTH_CSV_RISK, jhLang.COM_SITEHEALTH_CSV_COMPATIBILITY, jhLang.COM_SITEHEALTH_CSV_CODE_SCAN, jhLang.COM_SITEHEALTH_CSV_NEXT_ACTION, jhLang.COM_SITEHEALTH_CSV_PROGRESS, jhLang.COM_SITEHEALTH_CSV_MIGRATION_NOTES]];
        sourceRows.forEach(function (row) {
            var cells = row.querySelectorAll('td');
            var values = [];
            for (var i = 0; i < cells.length; i++) {
                var progressControl = cells[i].querySelector('.jh-progress-select');
                var notesControl = cells[i].querySelector('.jh-migration-note');
                values.push(progressControl ? progressControl.options[progressControl.selectedIndex].text : (notesControl ? notesControl.value : cells[i].innerText.replace(/\s+/g, ' ').trim()));
            }
            csv.push(values);
        });
        return csv.map(function (line) {
            return line.map(function (value) { return '"' + value.replace(/"/g, '""') + '"'; }).join(',');
        }).join('\r\n');
    }

    function downloadCsv(sourceRows, filename) {
        if (!sourceRows.length) return;
        var blob = new Blob([buildCsv(sourceRows)], {type: 'text/csv;charset=utf-8;'});
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(function () { URL.revokeObjectURL(url); }, 0);
    }

    function filteredRowsRequireSearch() {
        return search && search.value.trim() !== '';
    }

    function getFilteredRows() {
        if (!filteredRowsRequireSearch()) return [];
        return rows.filter(function (row) { return row.style.display !== 'none'; });
    }

    function updateFilteredActionState() {
        var enabled = filteredRowsRequireSearch() && getFilteredRows().length > 0;
        var filteredButtons = [
            document.getElementById('jh-export-action-plan'),
            document.getElementById('jh-print-report')
        ];
        filteredButtons.forEach(function (button) {
            if (!button) return;
            button.disabled = !enabled;
            button.title = enabled ? '' : jhLang.COM_SITEHEALTH_FILTER_SEARCH_REQUIRED;
        });
    }

    var exportButton = document.getElementById('jh-export-action-plan');
    if (exportButton) exportButton.addEventListener('click', function () {
        var selectedRows = getFilteredRows();
        if (!selectedRows.length) return;
        downloadCsv(selectedRows, 'site-health-inspector-migration-action-plan-filtered.csv');
    });

    var exportAllButton = document.getElementById('jh-export-all-action-plan');
    if (exportAllButton) exportAllButton.addEventListener('click', function () {
        downloadCsv(rows, 'site-health-inspector-migration-action-plan.csv');
    });

    function buildPrintDocument(sourceRows, title) {
        var table = document.querySelector('#jh-action-plan-card table');
        if (!table) return '';

        // Build the print table from the explicitly requested sourceRows instead of
        // cloning the live table and trying to infer selection from row indexes.
        // This guarantees that Print All always uses every action-plan row, even
        // when the live table is currently filtered by the search box.
        var printTable = document.createElement('table');
        printTable.className = 'jh-print-table';
        var sourceHead = table.querySelector('thead');
        if (sourceHead) printTable.appendChild(sourceHead.cloneNode(true));
        var printBody = document.createElement('tbody');
        sourceRows.forEach(function (sourceRow) {
            var clonedRow = sourceRow.cloneNode(true);
            var liveProgress = sourceRow.querySelector('.jh-progress-select');
            var clonedProgress = clonedRow.querySelector('.jh-progress-select');
            var liveNotes = sourceRow.querySelector('.jh-migration-note');
            var clonedNotes = clonedRow.querySelector('.jh-migration-note');
            var liveChecklist = sourceRow.querySelector('.jh-test-checklist');
            var clonedChecklist = clonedRow.querySelector('.jh-test-checklist');
            if (liveChecklist && clonedChecklist) {
                // Do not print the interactive <details>/<input> controls directly.
                // A cloned checkbox does not reliably carry its live .checked property,
                // and a closed <details> element can hide its checklist in the print frame.
                // Build a static checklist from the live controls, matching the Client report.
                var liveChecks = Array.prototype.slice.call(liveChecklist.querySelectorAll('input[type=checkbox]'));
                var completedChecks = liveChecks.filter(function (input) { return input.checked === true; }).length;
                var staticChecklist = document.createElement('div');
                staticChecklist.className = 'jh-print-checklist';
                var checklistHeading = document.createElement('strong');
                checklistHeading.className = 'jh-print-checklist-heading';
                checklistHeading.textContent = 'Checks ' + completedChecks + '/' + liveChecks.length;
                staticChecklist.appendChild(checklistHeading);
                liveChecks.forEach(function (input) {
                    var label = input.closest('label');
                    var labelText = label ? label.textContent.trim() : input.value;
                    var line = document.createElement('div');
                    line.className = 'jh-print-checklist-item';
                    var mark = document.createElement('span');
                    mark.className = 'jh-print-checklist-mark';
                    mark.textContent = input.checked ? '☑' : '☐';
                    mark.setAttribute('aria-label', input.checked ? 'Checked' : 'Not checked');
                    var text = document.createElement('span');
                    text.textContent = labelText;
                    line.appendChild(mark);
                    line.appendChild(text);
                    staticChecklist.appendChild(line);
                });
                clonedChecklist.parentNode.replaceChild(staticChecklist, clonedChecklist);
            }
            if (liveProgress && clonedProgress) {
                var progressText = liveProgress.options[liveProgress.selectedIndex].text;
                var progressCell = clonedProgress.parentNode;
                progressCell.textContent = progressText;
            }
            if (liveNotes && clonedNotes) {
                var notesCell = clonedNotes.parentNode;
                notesCell.textContent = liveNotes.value.trim() || '—';
            }
            // The live table uses inline display:none for search/risk filtering.
            // Print All must ignore that UI state and always render every row.
            clonedRow.style.removeProperty('display');
            clonedRow.classList.remove('jh-hidden');
            printBody.appendChild(clonedRow);
        });
        printTable.appendChild(printBody);

        return '<!doctype html><html><head><meta charset="utf-8"><title>Site Health Inspector — Migration Action Plan</title>' +
            '<style>' +
            '@page{size:auto;margin:12mm}' +
            'html,body{margin:0;padding:0;background:#fff;color:#222}' +
            'body{font-family:Arial,Helvetica,sans-serif;font-size:11px}' +
            'h1{font-size:20px;margin:0 0 4px}p{color:#666;font-size:10px;margin:0 0 12px}' +
            'table{width:100%;border-collapse:collapse;table-layout:fixed}' +
            'th,td{border:1px solid #bbb;padding:6px;vertical-align:top;overflow-wrap:anywhere;word-break:break-word}' +
            'th{background:#f0f0f0;text-align:left;font-size:10px}' +
            '.jh-progress-summary{font-size:12px;margin:.6rem 0;padding:.6rem .75rem;background:#f5f7fa;border:1px solid #e1e5ea;border-radius:5px}' +
            '.jh-print-table th:nth-child(7),.jh-print-table td:nth-child(7){width:20%}' +
            '.jh-print-checklist{display:flex;flex-direction:column;gap:3px;margin:2px 0 0;font-size:9px;line-height:1.3}' +
            '.jh-print-checklist-heading{display:block;margin-bottom:2px;font-size:9px;white-space:nowrap}' +
            '.jh-print-checklist-item{display:flex;align-items:flex-start;gap:5px;break-inside:avoid;page-break-inside:avoid}' +
            '.jh-print-checklist-mark{display:inline-block;flex:0 0 12px;width:12px;font-family:Arial,"DejaVu Sans",sans-serif;font-size:12px;line-height:1.1}' +
            'td{font-size:10px;line-height:1.35}' +
            'ul{margin:0;padding-left:16px}' +
            '.jh-badge{display:inline-block;padding:3px 6px;border:1px solid #bbb;border-radius:12px;font-size:9px;font-weight:700}' +
            '</style></head><body><h1>Site Health Inspector — Migration Action Plan</h1>' +
            '<p>' + title + '</p>' + printTable.outerHTML + '</body></html>';
    }

    function printActionPlan(allRows) {
        var selectedRows = allRows ? rows.slice() : getFilteredRows();
        if (!selectedRows.length) return;
        var html = buildPrintDocument(selectedRows, allRows ? jhLang.COM_SITEHEALTH_PRINT_ALL_DESC : jhLang.COM_SITEHEALTH_PRINT_FILTER_DESC);
        if (!html) return;

        var printButtons = [
            document.getElementById('jh-print-report'),
            document.getElementById('jh-print-all-report')
        ];
        printButtons.forEach(function (button) { if (button) button.disabled = true; });

        // Use a same-origin print frame. Keep it rendered (but off-screen) while
        // the native print dialog is open; display:none/visibility:hidden frames
        // are handled inconsistently by some browsers.
        var frame = document.createElement('iframe');
        frame.setAttribute('title', jhLang.COM_SITEHEALTH_PRINT_FRAME_TITLE);
        frame.setAttribute('aria-hidden', 'true');
        frame.style.position = 'fixed';
        frame.style.left = '-10000px';
        frame.style.top = '0';
        frame.style.width = '900px';
        frame.style.height = '700px';
        frame.style.border = '0';
        frame.style.opacity = '0';
        frame.style.pointerEvents = 'none';
        document.body.appendChild(frame);

        var cleaned = false;
        var fallbackTimer = null;
        function cleanup() {
            if (cleaned) return;
            cleaned = true;
            if (fallbackTimer) window.clearTimeout(fallbackTimer);
            if (frame && frame.parentNode) frame.parentNode.removeChild(frame);
            var printAllButton = document.getElementById('jh-print-all-report');
            if (printAllButton) printAllButton.disabled = false;
            // Restore the filtered action's state from the search field, never
            // unconditionally enable it after Print All.
            updateFilteredActionState();
        }

        try {
            var printWindow = frame.contentWindow;
            var printDocument = printWindow.document;
            printDocument.open();
            printDocument.write(html);
            printDocument.close();

            var started = false;
            function triggerPrint() {
                if (started || cleaned) return;
                started = true;
                try {
                    // Allow layout and font measurement to complete before print().
                    printWindow.focus();
                    printWindow.print();
                    // afterprint is the primary cleanup path. The long fallback
                    // handles browsers that do not dispatch afterprint reliably.
                    fallbackTimer = window.setTimeout(cleanup, 120000);
                } catch (printError) {
                    cleanup();
                }
            }

            printWindow.addEventListener('afterprint', cleanup, { once: true });
            printDocument.addEventListener('readystatechange', function () {
                if (printDocument.readyState === 'complete') {
                    window.requestAnimationFrame(function () {
                        window.requestAnimationFrame(triggerPrint);
                    });
                }
            });

            // document.write() may already have completed before the listener
            // above is observed, so also check the state after the current task.
            window.setTimeout(function () {
                if (printDocument.readyState === 'complete') {
                    window.requestAnimationFrame(function () {
                        window.requestAnimationFrame(triggerPrint);
                    });
                }
            }, 50);
        } catch (e) {
            cleanup();
        }
    }

    var printButton = document.getElementById('jh-print-report');
    if (printButton) printButton.addEventListener('click', function () { printActionPlan(false); });

    var printAllButton = document.getElementById('jh-print-all-report');
    if (printAllButton) printAllButton.addEventListener('click', function () { printActionPlan(true); });

    // Build a complete client-facing report from the current diagnostic page and
    // live workspace controls. This is deliberately separate from the JSON
    // workspace backup and includes diagnostic results as well as user progress.
    function reportEscape(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[character];
        });
    }

    function buildClientReport() {
        var actionRows = [];
        rows.forEach(function (row) {
            var cells = row.cells;
            if (!cells || cells.length < 7) return;
            var progress = row.querySelector('.jh-progress-select');
            var notes = row.querySelector('.jh-migration-note');
            var checklist = row.querySelector('.jh-test-checklist');
            var checks = [];
            if (checklist) {
                checklist.querySelectorAll('input[type=checkbox]').forEach(function (input) {
                    var label = input.closest('label');
                    checks.push((input.checked ? '&#9745; ' : '&#9744; ') + reportEscape(label ? label.textContent.trim() : input.value));
                });
            }
            var nextActionCell = cells[4].cloneNode(true);
            nextActionCell.querySelectorAll('details').forEach(function (details) { details.remove(); });
            actionRows.push('<tr>' +
                '<td>' + reportEscape(cells[0].innerText.replace(/\s+/g, ' ').trim()) + '</td>' +
                '<td>' + reportEscape(cells[1].innerText.trim()) + '</td>' +
                '<td>' + reportEscape(cells[2].innerText.trim()) + '</td>' +
                '<td>' + reportEscape(cells[3].innerText.trim()) + '</td>' +
                '<td>' + reportEscape(nextActionCell.innerText.replace(/\s+/g, ' ').trim()) + '</td>' +
                '<td>' + reportEscape(progress ? progress.options[progress.selectedIndex].text : 'Pending') + '</td>' +
                '<td>' + (checks.length ? checks.join('<br>') : 'Not available') + '</td>' +
                '<td>' + reportEscape(notes && notes.value.trim() ? notes.value.trim() : '—').replace(/\n/g, '<br>') + '</td>' +
                '</tr>');
        });

        var wrap = document.querySelector('.jh-wrap');
        var dashboard = '';
        if (wrap) {
            Array.prototype.forEach.call(wrap.children, function (child) {
                if (child.classList.contains('jh-migration-center') || child.classList.contains('jh-workspace-card') || child.id === 'jh-action-plan-card') return;
                if (child.classList.contains('jh-card') || child.classList.contains('jh-grid')) {
                    var childHeading = child.querySelector('h2');
                    if (childHeading && childHeading.textContent.indexOf('code compatibility scan') !== -1) return;
                    if (child.textContent.indexOf('Scan generated:') !== -1) return;
                    var clone = child.cloneNode(true);
                    clone.querySelectorAll('button,input,select,textarea').forEach(function (control) { control.remove(); });
                    dashboard += clone.outerHTML;
                }
            });
        }

        var actionPlan = '<section class="report-section"><h2>Migration action plan and project tracking</h2>' +
            '<p class="muted">Includes every action-plan extension, regardless of the current on-screen search or risk filter. Progress, verification, and notes reflect the values currently saved in this browser.</p>' +
            '<table><thead><tr><th>Extension</th><th>Risk</th><th>Compatibility</th><th>Code scan</th><th>Recommended action</th><th>Progress</th><th>Verification checklist</th><th>Migration notes</th></tr></thead><tbody>' +
            (actionRows.length ? actionRows.join('') : '<tr><td colspan="8">No migration actions identified.</td></tr>') +
            '</tbody></table></section>';

        // Select the code-scan section by its heading rather than relying on
        // card order, which may change as future dashboard panels are added.
        var scanSection = '';
        Array.prototype.forEach.call(document.querySelectorAll('.jh-wrap > .jh-card'), function (card) {
            var heading = card.querySelector('h2');
            if (heading && heading.textContent.indexOf('code compatibility scan') !== -1) {
                var clone = card.cloneNode(true);
                clone.querySelectorAll('button,input,select,textarea').forEach(function (control) { control.remove(); });
                scanSection = clone.outerHTML;
            }
        });
        if (!scanSection) scanSection = '';

        var scanStamp = '';
        Array.prototype.forEach.call(document.querySelectorAll('.jh-wrap > .jh-card'), function (card) {
            if (card.textContent.indexOf('Scan generated:') !== -1) scanStamp = card.textContent.trim();
        });
        var title = document.querySelector('.jh-wrap > h1');
        var generated = new Date().toLocaleString();
        var joomlaHealthVersion = title ? title.textContent.replace(/^Site Health Inspector\s*/, '').trim() : '';
        var summary = '<header class="report-header"><div class="eyebrow">SITE HEALTH INSPECTOR · MIGRATION READINESS</div>' +
            '<h1>Joomla 6 Migration Assessment</h1>' +
            '<p>Client and project report</p><div class="report-meta"><span><b>Site Health Inspector</b> ' + reportEscape(joomlaHealthVersion) + '</span><span><b>Report generated</b> ' + reportEscape(generated) + '</span>' +
            (scanStamp ? '<span><b>' + reportEscape(scanStamp) + '</b></span>' : '') + '</div></header>';
        var disclaimer = '<section class="report-disclaimer"><strong>Interpretation:</strong> Compatibility metadata and static source-code matches are indicators for review, not guarantees of runtime compatibility. Final migration readiness must be confirmed through vendor verification and testing on a staging environment.</section>';

        return '<!doctype html><html><head><meta charset="utf-8"><title>Site Health Inspector - Joomla 6 Migration Assessment</title><style>' +
            '@page{size:A4 landscape;margin:12mm}*{box-sizing:border-box}html,body{margin:0;padding:0;background:#fff;color:#202833}body{font:10px/1.45 Arial,Helvetica,sans-serif}' +
            '.report-header{padding:0 0 14px;margin:0 0 16px;border-bottom:3px solid #263b53}.eyebrow{font-size:9px;font-weight:bold;letter-spacing:1.4px;color:#53677d}.report-header h1{font-size:25px;margin:5px 0 2px;color:#182b40}.report-header p{font-size:12px;color:#53677d;margin:0 0 9px}.report-meta{display:flex;flex-wrap:wrap;gap:6px 18px;color:#53677d;font-size:9px}.report-section{margin:16px 0;break-inside:auto}.report-section h2,.jh-card h2{font-size:15px;color:#20364d;margin:0 0 8px}.jh-card,.jh-grid>.jh-card{border:1px solid #d9e0e8;border-radius:5px;padding:10px;margin:10px 0;break-inside:avoid}.jh-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.jh-score{display:flex;justify-content:space-between;gap:12px;align-items:center;background:#f4f7fa}.jh-score strong{font-size:22px}.jh-status-grid{display:flex;flex-wrap:wrap;gap:12px}.jh-status-grid>div{min-width:75px}.jh-status-grid strong{display:block;font-size:16px}.jh-status-grid span{font-size:9px;color:#53677d}.jh-note,.muted{font-size:9px;color:#596879}.jh-finding{padding:7px 9px;border-left:3px solid #9aa8b7;background:#f6f8fa;margin:6px 0}.jh-finding p{margin:3px 0}.jh-table-wrap{width:100%;overflow:visible}table{width:100%;border-collapse:collapse;table-layout:auto;margin:7px 0 12px}th,td{border:1px solid #cbd3dc;padding:5px 6px;text-align:left;vertical-align:top;overflow-wrap:anywhere}th{background:#edf1f5;color:#26384b;font-size:9px}td{font-size:9px}ul{margin:2px 0;padding-left:16px}.jh-badge{display:inline-block;padding:2px 5px;border:1px solid #b9c4cf;border-radius:10px;font-size:8px}.jh-progress-summary,.jh-workspace-summary{border:1px solid #d9e0e8;padding:8px;margin:7px 0}.jh-workspace-summary-grid{display:flex;flex-wrap:wrap;gap:15px}.jh-workspace-summary-grid>div strong{display:block;font-size:15px}.report-disclaimer{padding:9px 11px;border:1px solid #cbd3dc;background:#f5f7f9;margin:16px 0;font-size:9px}.jh-important li{margin:4px 0}.jh-code-finding td{background:#fafbfc}.jh-action-list{margin:0;padding-left:14px}' +
            '@media print{a{color:inherit;text-decoration:none}.jh-card,.report-section{break-inside:avoid-page}.jh-table-wrap{overflow:visible}}' +
            '</style></head><body>' + summary + dashboard + actionPlan + scanSection + disclaimer + '</body></html>';
    }

    function printClientReport() {
        var button = document.getElementById('jh-client-report');
        if (button) button.disabled = true;
        var frame = document.createElement('iframe');
        frame.setAttribute('title', jhLang.COM_SITEHEALTH_CLIENT_FRAME_TITLE);
        frame.setAttribute('aria-hidden', 'true');
        frame.style.position = 'fixed'; frame.style.left = '-10000px'; frame.style.top = '0';
        frame.style.width = '1000px'; frame.style.height = '800px'; frame.style.border = '0'; frame.style.opacity = '0';
        document.body.appendChild(frame);
        var cleaned = false;
        var timer = null;
        function cleanup() {
            if (cleaned) return;
            cleaned = true;
            if (timer) window.clearTimeout(timer);
            if (frame.parentNode) frame.parentNode.removeChild(frame);
            if (button) button.disabled = false;
        }
        try {
            var win = frame.contentWindow;
            var doc = win.document;
            doc.open(); doc.write(buildClientReport()); doc.close();
            var started = false;
            function trigger() {
                if (started || cleaned) return;
                started = true;
                try { win.focus(); win.print(); timer = window.setTimeout(cleanup, 120000); }
                catch (error) { cleanup(); }
            }
            win.addEventListener('afterprint', cleanup, {once:true});
            doc.addEventListener('readystatechange', function () {
                if (doc.readyState === 'complete') window.requestAnimationFrame(function () { window.requestAnimationFrame(trigger); });
            });
            window.setTimeout(function () {
                if (doc.readyState === 'complete') window.requestAnimationFrame(function () { window.requestAnimationFrame(trigger); });
            }, 50);
        } catch (error) { cleanup(); }
    }

    var clientReportButton = document.getElementById('jh-client-report');
    if (clientReportButton) clientReportButton.addEventListener('click', printClientReport);

    applyFilter();
    updateProgressSummary();
    updateFilteredActionState();
})();
</script>
</div>
