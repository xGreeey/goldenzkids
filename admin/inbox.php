<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

auth_require_permission('admin.inbox.manage');

$company_id = (string) $_SESSION['company_id'];

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remark'])) {
    csrf_verify();

    $remark = (string) $_POST['remark'];
    $r_time = (string) $_POST['report_time'];
    $r_guard = (string) $_POST['guard_id'];

    $sql = 'UPDATE dgd SET Status = ? WHERE Time_of_Report = ? AND Company_ID = ?';
    $stmt = $conn->prepare($sql);

    if ($stmt && $stmt->bind_param('sss', $remark, $r_time, $r_guard) && $stmt->execute()) {
        redirect_with_alert('Status updated successfully.', 'inbox.php');
    }

    $error = 'Could not update status. Please try again.';
}

$guards_result = $conn->query('SELECT Company_ID, First_Name, Last_Name FROM guards');
$guard_dict = [];
if ($guards_result && $guards_result->num_rows > 0) {
    while ($g = $guards_result->fetch_assoc()) {
        $guard_dict[(string) $g['Company_ID']] = $g['Last_Name'] . ', ' . $g['First_Name'];
    }
}

$reports_result = $conn->query('SELECT Company_ID, Establishment, Template_Path, Template, Time_of_Report, Status, AI_Extracted_Text, iv FROM dgd ORDER BY Time_of_Report DESC');

$adminNavActive = 'inbox';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= mobile_meta_tags() ?>
    <title><?= e(app_agency_name()) ?> | Report Inbox</title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <?= app_fonts_link() ?>
    <style>
<?php admin_shell_styles(); ?>

        .notif-list { display: flex; flex-direction: column; gap: 12px; }
        .notif-card {
            background: var(--bg-panel);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 18px 20px;
            display: flex;
            gap: 16px;
            position: relative;
            cursor: pointer;
            text-align: left;
            width: 100%;
            font-family: inherit;
            color: inherit;
            transition: transform var(--transition), box-shadow var(--transition), border-color var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .notif-card:hover {
            transform: translateY(-1px);
            border-color: var(--border-strong);
            box-shadow: var(--shadow-md);
        }

        .icon-box {
            width: 48px; height: 48px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; border-radius: var(--radius-sm);
            flex-shrink: 0;
            background: var(--accent-blue-soft);
            color: var(--accent-blue);
        }

        .content-box { flex: 1; min-width: 0; }
        .notif-title {
            font-size: 1.0625rem; font-weight: 700;
            margin-bottom: 6px; color: var(--app-ink-deep);
            display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
            line-height: 1.35;
        }

        .status-badge {
            font-family: var(--font-body-family);
            font-size: var(--font-body-size-xs);
            padding: 4px 10px;
            border-radius: 999px;
            font-weight: 700;
            letter-spacing: 0.03em;
        }

        .notif-desc {
            font-size: var(--font-body-size-sm);
            color: var(--app-ink);
            line-height: var(--font-body-line-relaxed);
            margin-bottom: 8px;
        }
        .timestamp {
            font-family: var(--font-body-family);
            font-size: var(--font-body-size-xs);
            font-weight: 500;
            color: var(--app-ink-muted);
            display: flex; flex-wrap: wrap; gap: 8px;
        }

        .btn-dismiss {
            position: absolute; top: 12px; right: 12px;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            color: var(--text-tertiary);
            cursor: pointer;
            font-size: 1.1rem;
            width: 32px; height: 32px;
            border-radius: var(--radius-sm);
            z-index: 2;
            transition: color var(--transition), background var(--transition);
        }

        .btn-dismiss:hover { color: var(--danger); background: var(--danger-soft); }

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: var(--app-ink-muted);
            font-size: var(--font-body-size-sm);
            line-height: var(--font-body-line-relaxed);
            display: none;
            background: var(--bg-panel);
            border: 1px dashed var(--border);
            border-radius: var(--radius-md);
        }

        .alert-error {
            background: var(--danger-soft);
            border: 1px solid var(--danger-soft);
            color: var(--danger);
            padding: 14px 16px;
            border-radius: var(--radius-sm);
            font-size: var(--font-body-size-sm);
            margin-bottom: 20px;
            line-height: var(--font-body-line-relaxed);
        }

        .modal-overlay {
            display: none;
            position: fixed; z-index: 2000;
            inset: 0;
            background: rgba(45, 55, 72, 0.45);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-content {
            background: var(--bg-panel);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 560px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            padding: 24px;
            box-shadow: var(--shadow-lg);
        }

        .close-modal {
            color: var(--text-tertiary);
            position: absolute; top: 16px; right: 18px;
            font-size: 1.5rem; font-weight: 700;
            cursor: pointer;
            background: none; border: none;
            line-height: 1;
        }

        .close-modal:hover { color: var(--danger); }

        .modal-header {
            border-bottom: 1px solid var(--border);
            padding-bottom: 14px;
            margin-bottom: 18px;
            padding-right: 28px;
        }

        .modal-header h2 {
            font-size: 1.375rem;
            font-weight: 700;
            color: var(--app-ink-deep);
            margin-bottom: 4px;
            line-height: 1.3;
        }
        .modal-subtitle {
            font-family: var(--font-body-family);
            font-size: var(--font-body-size-xs);
            font-weight: 600;
            color: var(--app-ink-muted);
        }

        .modal-info {
            font-size: var(--font-body-size-sm);
            color: var(--app-ink);
            background: var(--bg-elevated);
            padding: 14px;
            border-left: 3px solid var(--accent-blue);
            border-radius: var(--radius-sm);
            font-family: var(--font-body-family);
            line-height: var(--font-body-line-relaxed);
            margin-top: 14px;
        }

        .modal-scan-label {
            font-family: var(--font-body-family);
            font-size: var(--font-body-size-xs);
            font-weight: 600;
            color: var(--app-ink-muted);
            margin-bottom: 8px;
            text-align: center;
        }

        .modal-scan-img {
            display: block;
            max-width: 100%;
            margin: 0 auto;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            cursor: zoom-in;
        }

        .ai-text-box {
            margin-top: 14px;
            padding: 14px;
            background: var(--bg-elevated);
            border-left: 3px solid var(--brand-accent);
            border-radius: var(--radius-sm);
        }

        .ai-text-header {
            font-size: var(--font-body-size-sm);
            font-weight: 700;
            color: var(--brand-accent);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ai-text-content {
            font-family: var(--font-body-family);
            font-size: var(--font-body-size-sm);
            line-height: var(--font-body-line-relaxed);
            color: var(--app-ink);
            max-height: 200px;
            overflow-y: auto;
            white-space: pre-wrap;
        }

        .form-group { margin-bottom: 14px; }
        .form-label {
            display: block;
            font-size: var(--font-body-size-sm);
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--app-ink);
        }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            background: var(--bg-elevated);
            border: 1px solid var(--border-strong);
            color: var(--app-ink);
            font-family: inherit;
            font-size: var(--font-body-size-sm);
            border-radius: var(--radius-sm);
            outline: none;
        }

        .form-control:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px var(--accent-blue-soft);
        }

        .submit-btn {
            width: 100%;
            background: var(--gradient-primary-btn);
            color: var(--color-white);
            border: none;
            padding: 14px;
            font-family: inherit;
            font-weight: 700;
            font-size: var(--font-body-size-sm);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: background var(--transition);
        }

        .submit-btn:hover { filter: brightness(1.04); }

        .modal-form-divider {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        .image-viewer-overlay {
            display: none;
            position: fixed;
            z-index: 9999;
            inset: 0;
            background: rgba(30, 36, 48, 0.92);
            align-items: center;
            justify-content: center;
            cursor: zoom-out;
        }

        .image-viewer-overlay img {
            max-width: 95vw;
            max-height: 95vh;
            object-fit: contain;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
        }

        .close-viewer {
            position: absolute;
            top: 20px;
            right: 24px;
            color: var(--text-secondary);
            font-size: 2rem;
            font-weight: 700;
            cursor: pointer;
            background: none;
            border: none;
        }

        .close-viewer:hover { color: var(--danger); }

        body:not(.light-mode) .notif-card,
        body:not(.light-mode) .modal-content,
        body:not(.light-mode) .empty-state {
            background: var(--app-card-bg);
            border-color: var(--app-border-on-dark);
            color: var(--app-ink-on-dark);
        }

        body:not(.light-mode) .notif-title,
        body:not(.light-mode) .modal-header h2 {
            color: var(--app-ink-on-dark);
        }

        body:not(.light-mode) .notif-desc,
        body:not(.light-mode) .modal-info,
        body:not(.light-mode) .ai-text-content {
            color: var(--app-ink-muted-on-dark);
        }

        body:not(.light-mode) .timestamp,
        body:not(.light-mode) .modal-subtitle {
            color: var(--app-ink-soft-on-dark);
        }

        body:not(.light-mode) .form-control {
            color: var(--app-ink-on-dark);
            background: rgba(0, 0, 0, 0.2);
            border-color: var(--app-border-on-dark);
        }
    </style>
</head>
<body class="light-mode">

<?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="app-main">
        <header class="page-header">
            <h1 class="page-title">Report Inbox</h1>
            <p class="page-subtitle">Review daily guard reports, update status, and view scanned forms. Select a report to open details.</p>
        </header>

        <?php if ($error !== null): ?>
            <div class="alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="notif-list" id="alert-feed">
            <?php
            if ($reports_result && $reports_result->num_rows > 0) {
                while ($row = $reports_result->fetch_assoc()) {
                    $iv = base64_decode((string) $row['iv'], true) ?: '';
                    $decrypted_est = $iv !== ''
                        ? (openssl_decrypt((string) $row['Establishment'], $cipher_algo, $master_key, 0, $iv) ?: '[Decryption failed]')
                        : '[Missing IV]';
                    $decrypted_template = $iv !== ''
                        ? (openssl_decrypt((string) $row['Template_Path'], $cipher_algo, $master_key, 0, $iv) ?: '')
                        : '';

                    $encrypted_ai = $row['AI_Extracted_Text'] ?? '';
                    $decrypted_ai = '';
                    if ($encrypted_ai !== '' && $iv !== '') {
                        $decrypted_ai = openssl_decrypt((string) $encrypted_ai, $cipher_algo, $master_key, 0, $iv) ?: '';
                    }

                    $guard_id = (string) $row['Company_ID'];
                    $guard_name = $guard_dict[$guard_id] ?? 'Unknown personnel';
                    $time_sent = (string) $row['Time_of_Report'];
                    $status = (string) $row['Status'];
                    $status_text = strtoupper($status);

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
                     data-status="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"
                     data-aitext="<?= htmlspecialchars($decrypted_ai, ENT_QUOTES, 'UTF-8') ?>">
                <div class="icon-box" aria-hidden="true"><i class="fa-solid fa-file-lines"></i></div>
                <div class="content-box">
                    <div class="notif-title">
                        Daily guard report
                        <span class="status-badge" style="background:<?= $badge_bg ?>;color:<?= $badge_color ?>;"><?= htmlspecialchars($status_text) ?></span>
                    </div>
                    <p class="notif-desc">Submitted for <?= htmlspecialchars($decrypted_est) ?>.</p>
                    <div class="timestamp">
                        <span>Employee ID: <?= htmlspecialchars($guard_id) ?></span>
                        <span><?= htmlspecialchars($time_sent) ?></span>
                    </div>
                </div>
                <button type="button" class="btn-dismiss" aria-label="Dismiss from list"<?= ui_tooltip('Dismiss from list') ?> onclick="dismiss(event, this)">×</button>
            </article>
                    <?php
                }
            } else {
                echo '<script>document.addEventListener("DOMContentLoaded", checkEmpty);</script>';
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

<div id="reportModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-content" onclick="event.stopPropagation()">
        <button type="button" class="close-modal" onclick="closeModal()" aria-label="Close"<?= ui_tooltip('Close') ?>>&times;</button>
        <div class="modal-header">
            <h2 id="modalTitle">Report details</h2>
            <div id="modalTimestamp" class="modal-subtitle"></div>
        </div>
        <div class="modal-body">
            <p class="modal-scan-label">Scanned form (click to enlarge)</p>
            <img id="imgTemp" class="modal-scan-img" src="" alt="Report scan" role="presentation">
            <div class="modal-info" id="modalInfo"></div>
            <div id="aiTextContainer" class="ai-text-box" style="display:none;">
                <div class="ai-text-header"><i class="fa-solid fa-robot" aria-hidden="true"></i> Extracted text</div>
                <div id="modalAiText" class="ai-text-content"></div>
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

<script>
    function cleanTemplatePath(path) {
        if (!path) return '';
        if (path.includes('uploads/')) {
            return '<?= UPLOADS_URL ?>' + path.split('uploads/').pop();
        }
        return path;
    }

    function openImageViewer(event, imageSrc) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        document.getElementById('fullScreenImg').src = imageSrc;
        document.getElementById('imageViewer').style.display = 'flex';
    }

    function closeImageViewer(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        document.getElementById('imageViewer').style.display = 'none';
    }

    function openReportModal(card) {
        const guard = card.getAttribute('data-guard');
        const guardId = card.getAttribute('data-id');
        const est = card.getAttribute('data-est');
        const time = card.getAttribute('data-time');
        const status = card.getAttribute('data-status');
        const tempPath = cleanTemplatePath(card.getAttribute('data-template'));
        const aiText = card.getAttribute('data-aitext') || '';

        document.getElementById('modalTitle').textContent = 'Report from ' + est;
        document.getElementById('modalTimestamp').textContent = 'Logged: ' + time;

        const img = document.getElementById('imgTemp');
        img.src = tempPath || '';
        img.onerror = function () {
            this.onerror = null;
            this.src = 'https://via.placeholder.com/300x400/e8ebf0/6b7a8f?text=Image+not+found';
        };
        img.onclick = function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (img.src) openImageViewer(e, img.src);
        };

        document.getElementById('modalInfo').innerHTML =
            '<p><strong>Employee ID:</strong> ' + guardId + '</p>' +
            '<p><strong>Personnel:</strong> ' + guard + '</p>' +
            '<p><strong>Status:</strong> ' + status + '</p>';

        const aiContainer = document.getElementById('aiTextContainer');
        const aiTextDisplay = document.getElementById('modalAiText');
        if (aiText.trim() !== '') {
            aiTextDisplay.textContent = aiText;
            aiTextDisplay.innerHTML = aiTextDisplay.innerHTML.replace(/\n/g, '<br>');
            aiContainer.style.display = 'block';
        } else {
            aiContainer.style.display = 'none';
        }

        document.getElementById('formTime').value = time;
        document.getElementById('formGuardId').value = guardId;
        document.getElementById('reportModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('reportModal').style.display = 'none';
    }

    document.getElementById('reportModal').addEventListener('click', function (event) {
        if (event.target === this) closeModal();
    });

    document.getElementById('imageViewer').addEventListener('click', function (event) {
        if (event.target === this) closeImageViewer(event);
    });

    document.querySelectorAll('.notif-card').forEach(function (card) {
        card.addEventListener('click', function (event) {
            if (event.target.closest('.btn-dismiss')) return;
            event.preventDefault();
            openReportModal(card);
        });
        card.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openReportModal(card);
            }
        });
    });

    function dismiss(event, btn) {
        event.preventDefault();
        event.stopPropagation();
        const card = btn.closest('.notif-card');
        card.style.opacity = '0';
        card.style.transform = 'translateX(12px)';
        setTimeout(function () {
            card.remove();
            checkEmpty();
        }, 280);
    }

    function checkEmpty() {
        const list = document.getElementById('alert-feed');
        const msg = document.getElementById('empty-msg');
        msg.style.display = list.children.length === 0 ? 'block' : 'none';
    }

    document.addEventListener('DOMContentLoaded', checkEmpty);
</script>
<?php admin_shell_scripts(); ?>

<?php require_once __DIR__ . '/../includes/global-alerts.php'; ?>
</body>
</html>
