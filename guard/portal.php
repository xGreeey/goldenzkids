<?php
require_once __DIR__ . '/php/bootstrap.php';

auth_require_permission('guard.portal.access');
require GUARD_PHP . '/submit-report.php';

guard_head('Guard Portal', 'guard-portal guard-portal-home');
guard_layout_marquee($marquee_text ?? null);
guard_layout_header_nav();
?>
    <div class="main-container">
        <form action="" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="portal-section">
                <span class="section-tag">[HAKBANG 01 / STEP 01]</span>
                <h1 class="section-header-text">PUMILI NG ESTABLISHMENT<br><span style="font-size: 1.2rem; color: var(--text-gray);">(SELECT ESTABLISHMENT)</span></h1>

                <div class="form-group">
                    <label class="form-label">LUGAR NG DUTY</label>
                    <span class="form-label-sub">POST / ESTABLISHMENT</span>
                    <select class="form-control" name="Establishment" id="Establishment" required>
                        <option value="" disabled selected>-- PUMILI DITO (SELECT HERE) --</option>
                        <option value="Post-1">Post 1</option>
                        <option value="Post-2">Post 2</option>
                        <option value="Post-3">Post 3</option>
                    </select>
                </div>
            </div>

            <div class="portal-section" style="border-top: 2px solid var(--alert-red);">
                <span class="section-tag" style="color: var(--alert-red);">[HAKBANG 02 / STEP 02] // DGD UPLOAD</span>
                <h1 class="section-header-text">MGA EBIDENSYA<br><span style="font-size: 1.2rem; color: var(--text-gray);">(EVIDENCE & MEDIA ATTACHMENTS)</span></h1>

                <div class="upload-btn-wrapper">
                    <div class="upload-btn">
                        <i class="fa-solid fa-camera"></i> 1. LITRATO NG DGD TEMPLATE<br>
                        <span style="font-size: 0.8rem; font-family: var(--font-body-family); opacity: 0.7;">(TAKE PIC OF DGD TEMPLATE)</span>
                    </div>
                    <input type="file" id="report_scan" class="report_scan" name="report_scan" accept="image/*" capture="environment" required onchange="updateFileName(this)">
                </div>
            </div>

            <div class="portal-section" style="background: transparent; border: none; padding: 0; box-shadow: none;">
                <button type="submit" class="submit-btn"<?= ui_tooltip('Submit report with secure hash to admin') ?>>
                    I-SUBMIT SA ADMIN (BUMUO NG HASH)<br>
                    <span style="font-size: 0.9rem; font-family: var(--font-body-family); font-weight: normal;">SUBMIT TO ADMIN WITH HASH & END OPERATION</span>
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($file_error)): ?>
    <p class="upload-file-error" role="alert"><?= e($file_error) ?></p>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
    <div class="guard-form-error"><?= e($error) ?></div>
    <?php endif; ?>

<?php
guard_footer();
