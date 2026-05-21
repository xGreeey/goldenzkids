<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/guard_portal.php';
require_once __DIR__ . '/../includes/document_ai.php';

auth_require_permission('admin.reports.view');

$documentAiReady = document_ai_is_configured();
$ocrApiUrl = app_url('admin/api/report-ocr.php');

$company_id = (string) $_SESSION['company_id'];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remark'])) {
    csrf_verify();

    $remark = (string) $_POST['remark'];
    $r_time = (string) $_POST['report_time'];
    $r_guard = (string) $_POST['guard_id'];

    if (db_execute($conn, 'UPDATE dgd SET Status = ? WHERE Time_of_Report = ? AND Company_ID = ?', 'sss', [$remark, $r_time, $r_guard])) {
        redirect_with_alert('Status updated successfully.', 'reports.php');
    }

    $error = 'Could not update status. Please try again.';
}

$guard_dict = [];
foreach (db_fetch_all($conn, 'SELECT Company_ID, First_Name, Last_Name FROM guards') as $g) {
    $guard_dict[(string) $g['Company_ID']] = $g['Last_Name'] . ', ' . $g['First_Name'];
}

$reports_rows = db_fetch_all(
    $conn,
    'SELECT Company_ID, Establishment, Template_Path, Template, Time_of_Report, Status, AI_Extracted_Text, iv
     FROM dgd ORDER BY Time_of_Report DESC'
);

$adminNavActive = 'reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= mobile_meta_tags() ?>
    <title><?= e(app_agency_name()) ?> | Reports</title>
    <meta name="csrf-token" content="<?= e_attr(csrf_token()) ?>">
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <?= app_fonts_link() ?>
    <style>
<?php admin_shell_styles(); ?>
<?php readfile(__DIR__ . '/assets/css/dashboard.css'); ?>
<?php readfile(__DIR__ . '/assets/css/inbox.css'); ?>
    </style>
</head>
<body class="light-mode">

<?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="app-main">
        <header class="page-header">
            <h1 class="page-title">Reports</h1>
            <p class="page-subtitle">Review post-incident and daily attendance documents from guards, update status, and view scanned forms.</p>
        </header>

        <?php if ($error !== null): ?>
            <div class="alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="notif-list" id="alert-feed" data-uploads-base="<?= e(UPLOADS_URL) ?>">
            <?php
            if ($reports_rows !== []) {
                foreach ($reports_rows as $row) {
                    $iv = base64_decode((string) $row['iv'], true) ?: '';
                    $decrypted_est = $iv !== ''
                        ? (openssl_decrypt((string) $row['Establishment'], $cipher_algo, $master_key, 0, $iv) ?: '[Decryption failed]')
                        : '[Missing IV]';
                    $decrypted_template = $iv !== ''
                        ? (openssl_decrypt((string) $row['Template_Path'], $cipher_algo, $master_key, 0, $iv) ?: '')
                        : '';

                    $guard_id = (string) $row['Company_ID'];
                    $guard_name = $guard_dict[$guard_id] ?? 'Unknown personnel';
                    $time_sent = (string) $row['Time_of_Report'];
                    $status = (string) $row['Status'];
                    $status_text = strtoupper($status);
                    $report_type_label = guard_portal_report_type_label((string) ($row['Template'] ?? ''));
                    $report_type_icon = guard_portal_report_type_icon($report_type_label);

                    $encrypted_ai = $row['AI_Extracted_Text'] ?? '';
                    $decrypted_ai = '';
                    $ocr_formatted = '';
                    if ($encrypted_ai !== '' && $iv !== '') {
                        $decrypted_ai = openssl_decrypt((string) $encrypted_ai, $cipher_algo, $master_key, 0, $iv) ?: '';
                        $ocr_formatted = document_ai_decode_stored($decrypted_ai)['formatted'];
                    }
                    $reference_img = document_ai_reference_image_url($report_type_label);

                    $badge_bg = 'var(--accent-blue-soft)';
                    $badge_color = 'var(--accent-blue)';
                    if ($status_text === 'PENDING') {
                        $badge_bg = 'var(--warning-soft)';
                        $badge_color = 'var(--warning)';
                    } elseif ($status_text === 'APPROVED') {
                        $badge_bg = 'var(--success-soft)';
                        $badge_color = 'var(--success)';
                    } elseif ($status_text === 'FOR CLARIFICATION' || $status_text === 'NTE') {
                        $badge_bg = 'var(--danger-soft)';
                        $badge_color = 'var(--danger)';
                    }
                    ?>
            <article class="notif-card" role="button" tabindex="0"<?= ui_tooltip('Open report details') ?>
                     data-guard="<?= htmlspecialchars($guard_name, ENT_QUOTES, 'UTF-8') ?>"
                     data-id="<?= htmlspecialchars($guard_id, ENT_QUOTES, 'UTF-8') ?>"
                     data-est="<?= htmlspecialchars($decrypted_est, ENT_QUOTES, 'UTF-8') ?>"
                     data-time="<?= htmlspecialchars($time_sent, ENT_QUOTES, 'UTF-8') ?>"
                     data-template="<?= htmlspecialchars($decrypted_template, ENT_QUOTES, 'UTF-8') ?>"
                     data-report-type="<?= htmlspecialchars($report_type_label, ENT_QUOTES, 'UTF-8') ?>"
                     data-status="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"
                     data-aitext="<?= htmlspecialchars($ocr_formatted !== '' ? $ocr_formatted : $decrypted_ai, ENT_QUOTES, 'UTF-8') ?>"
                     data-reference-img="<?= htmlspecialchars($reference_img, ENT_QUOTES, 'UTF-8') ?>"
                     data-has-ocr="<?= $ocr_formatted !== '' ? '1' : '0' ?>">
                <div class="icon-box" aria-hidden="true"><i class="fa-solid <?= e($report_type_icon) ?>"></i></div>
                <div class="content-box">
                    <div class="notif-title">
                        <?= htmlspecialchars($report_type_label) ?>
                        <span class="status-badge" style="background:<?= $badge_bg ?>;color:<?= $badge_color ?>;"><?= htmlspecialchars($status_text) ?></span>
                    </div>
                    <p class="notif-desc"><?= htmlspecialchars($guard_name) ?> · <?= htmlspecialchars($decrypted_est) ?></p>
                    <div class="timestamp">
                        <span>Employee ID: <?= htmlspecialchars($guard_id) ?></span>
                        <span><?= htmlspecialchars($time_sent) ?></span>
                    </div>
                </div>
                <button type="button" class="btn-dismiss" aria-label="Dismiss from list"<?= ui_tooltip('Dismiss from list') ?> onclick="dismiss(event, this)">×</button>
            </article>
                    <?php
                }
            }
            ?>
        </div>

        <div id="empty-msg" class="empty-state">
            <div style="font-size:2rem;margin-bottom:10px;" aria-hidden="true">✓</div>
            <p style="font-weight:600;color:var(--text-secondary);">All clear</p>
            <p style="font-size:0.875rem;margin-top:6px;">No reports awaiting review.</p>
        </div>
    </main>
</div>

<div id="reportModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle"
     data-ocr-api="<?= e($ocrApiUrl) ?>"
     data-ocr-enabled="<?= $documentAiReady ? '1' : '0' ?>">
    <div class="modal-content modal-content--report" onclick="event.stopPropagation()">
        <button type="button" class="close-modal" onclick="closeModal()" aria-label="Close"<?= ui_tooltip('Close') ?>>&times;</button>
        <div class="modal-header">
            <h2 id="modalTitle">Report details</h2>
            <div id="modalTimestamp" class="modal-subtitle"></div>
        </div>
        <div class="modal-body">
            <div class="modal-report-layout">
                <section class="modal-report-scan" aria-label="Submitted scan">
                    <p class="modal-scan-label">Submitted scan (click to enlarge)</p>
                    <img id="imgTemp" class="modal-scan-img" src="" alt="Report scan" role="presentation">
                    <div class="modal-info" id="modalInfo"></div>
                </section>
                <aside class="modal-report-ocr" aria-label="OCR extraction">
                    <div class="ocr-panel-header">
                        <span class="ocr-panel-title"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> OCR extraction</span>
                        <button type="button" class="ocr-run-btn" id="btnRunOcr"<?= ui_tooltip('Run Google Document AI on this scan') ?>>Run OCR</button>
                    </div>
                    <p class="ocr-panel-hint">Reads handwritten fields from the Incident Report or Daily Attendance Document (DAD) template.</p>
                    <?php if (!$documentAiReady): ?>
                        <p class="ocr-panel-warning" role="status">Document AI is not configured. Copy <code>config/google-document-ai.json.example</code> to <code>config/google-document-ai.json</code>.</p>
                    <?php endif; ?>
                    <figure class="ocr-reference" id="ocrReference" hidden>
                        <figcaption>Reference form</figcaption>
                        <img id="ocrReferenceImg" src="" alt="" loading="lazy">
                    </figure>
                    <div id="ocrStatus" class="ocr-status" aria-live="polite"></div>
                    <div id="aiTextContainer" class="ai-text-box ocr-output-box">
                        <div class="ai-text-header"><i class="fa-solid fa-file-lines" aria-hidden="true"></i> Extracted fields</div>
                        <div id="modalAiText" class="ai-text-content">Open a report and run OCR to extract Name, Date, incident notes, or attendance rows.</div>
                    </div>
                </aside>
            </div>
            <form method="POST" id="remarking" class="modal-form-divider">
                <?= csrf_field() ?>
                <input type="hidden" name="report_time" id="formTime">
                <input type="hidden" name="guard_id" id="formGuardId">
                <div class="form-group">
                    <label class="form-label" for="remark">Update report status</label>
                    <select class="form-control" name="remark" id="remark" required>
                        <option value="" disabled selected>Select status…</option>
                        <option value="Pending">Pending</option>
                        <option value="For Clarification">For clarification</option>
                        <option value="Approved">Approved</option>
                    </select>
                </div>
                <button type="submit" class="submit-btn"<?= ui_tooltip('Save report status') ?>>Save status</button>
            </form>
        </div>
    </div>
</div>

<div id="imageViewer" class="image-viewer-overlay" role="dialog" aria-label="Enlarged scan">
    <button type="button" class="close-viewer" onclick="closeImageViewer(event)" aria-label="Close"<?= ui_tooltip('Close image viewer') ?>>&times;</button>
    <img id="fullScreenImg" src="" alt="Enlarged report scan">
</div>

<?php admin_shell_scripts(); ?>

<?php require_once __DIR__ . '/../includes/global-alerts.php'; ?>
</body>
</html>
