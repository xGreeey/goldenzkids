<?php
require_once __DIR__ . '/php/bootstrap.php';

auth_require_permission('guard.inbox.view');

$company_id = $_SESSION['company_id'];

$guards_result = db_query($conn, 'SELECT Company_ID, First_Name, Last_Name, Middle_Name FROM guards');
$guard_dict = [];
if ($guards_result && $guards_result->num_rows > 0) {
    while ($g = $guards_result->fetch_assoc()) {
        $guard_dict[$g['Company_ID']] = $g['Last_Name'] . ', ' . $g['First_Name'];
    }
}

$reports_result = db_query(
    $conn,
    'SELECT Company_ID, Establishment, Template_Path, Template, Time_of_Report, Status, iv FROM dgd WHERE Company_ID = ? ORDER BY Time_of_Report DESC',
    's',
    [$company_id]
);

guard_head('Inbox', 'guard-portal guard-inbox');
guard_layout_header_back();
?>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">ALERT CENTER</h1>
        </div>

        <div class="notif-list" id="alert-feed">
            <?php if ($reports_result && $reports_result->num_rows > 0): ?>
                <?php while ($row = $reports_result->fetch_assoc()):
                    $iv = base64_decode($row['iv']);
                    $decrypted_est = openssl_decrypt($row['Establishment'], $cipher_algo, $master_key, 0, $iv) ?: '[Decryption Failed - Check Key]';
                    $decrypted_template = openssl_decrypt($row['Template_Path'], $cipher_algo, $master_key, 0, $iv) ?: '[Decryption Failed - Check Key]';

                    $guard_id = $row['Company_ID'];
                    $guard_name = $guard_dict[$guard_id] ?? 'Unknown User';
                    $time_sent = $row['Time_of_Report'];
                    $status = $row['Status'];

                    $status_text = strtoupper($status);
                    if ($status_text === 'PENDING') {
                        $badge_bg = 'var(--accent-gold)';
                        $badge_color = 'var(--color-primary)';
                    } elseif ($status_text === 'APPROVED') {
                        $badge_bg = 'var(--success-green)';
                        $badge_color = '#ffffff';
                    } elseif ($status_text === 'NTE' || $status_text === 'FOR CLARIFICATION') {
                        $badge_bg = 'var(--alert-red)';
                        $badge_color = '#ffffff';
                    } else {
                        $badge_bg = 'var(--info-blue)';
                        $badge_color = '#ffffff';
                    }
                ?>
            <div class="notif-card" onclick="openReportModal(this)"
                 data-guard="<?= e($guard_name) ?>"
                 data-id="<?= e($guard_id) ?>"
                 data-est="<?= e($decrypted_est) ?>"
                 data-time="<?= e($time_sent) ?>"
                 data-template="<?= e($decrypted_template) ?>"
                 data-status="<?= e($status) ?>">
                <div class="icon-box" aria-hidden="true"><i class="fa-solid fa-file-lines"></i></div>
                <div class="content-box">
                    <div class="notif-title">DGD LOGGED</div>
                    <div class="notif-desc">Submitted for <?= e($decrypted_est) ?>.</div>
                    <div class="timestamp">
                        <span>SENT BY: <?= e($guard_id) ?></span> | <span><?= e($time_sent) ?></span>
                        <span class="status-badge" style="background-color: <?= e($badge_bg) ?>; color: <?= e($badge_color) ?>;">
                            <?= e($status_text) ?>
                        </span>
                    </div>
                </div>
                <button class="btn-dismiss" onclick="dismiss(event, this)" type="button" aria-label="Dismiss"<?= ui_tooltip('Dismiss from list') ?>>&times;</button>
            </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <div id="empty-msg" class="empty-state">
            <div class="empty-state-icon" aria-hidden="true"><i class="fa-solid fa-shield-halved"></i></div>
            ALL CLEAR. NO NEW ALERTS.<br>
            <span style="font-size: 0.8rem;">SYSTEM SECURE</span>
        </div>
    </div>

    <div id="reportModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()" role="button" tabindex="0" aria-label="Close"<?= ui_tooltip('Close') ?>>&times;</span>
            <div class="modal-header">
                <h2 id="modalTitle">REPORT DETAILS</h2>
                <div id="modalTimestamp" class="modal-subtitle">TIMESTAMP</div>
            </div>
            <div class="modal-body">
                <div style="text-align: center;">
                    <p style="font-family: var(--font-body-family); font-size: 0.8rem; color: var(--accent-gold); margin-bottom: 5px;">FILLED OUT FORM (CLICK TO ZOOM)</p>
                    <img id="imgTemp" src="" alt="Template Scan" onclick="openImageViewer(this.src)" style="cursor: zoom-in; max-width: 100%; border: 1px solid var(--accent-gold); border-radius: 4px;"
                         onerror="this.src='https://via.placeholder.com/300x400/110d24/ff3333?text=Image+Not+Found'">
                </div>
                <div class="modal-info" id="modalInfo" style="margin-top: 20px;"></div>
            </div>
        </div>
    </div>

    <div id="imageViewer" class="image-viewer-overlay" onclick="closeImageViewer()">
        <span class="close-viewer" aria-hidden="true">&times;</span>
        <img id="fullScreenImg" src="" alt="Full screen scan">
    </div>
<?php
guard_footer();
