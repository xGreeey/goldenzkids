<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/guard_layout.php';

auth_require_permission('guard.reports.submit');

$guardNavActive = 'submit';
guard_layout_head('Submit Report');
?>
        <div class="guard-section-stack">
        <header class="page-header">
            <h1 class="page-title">Submit report</h1>
            <p class="page-subtitle">Three-step submission: scan your filled report, attach evidences, then submit for admin review.</p>
        </header>

        <section class="guard-card">
            <div class="guard-card__head">
                <h2 class="panel-title">Report submission</h2>
            </div>
            <form class="guard-wizard" data-guard-report-wizard method="POST" action="api/report-submit.php" enctype="multipart/form-data" autocomplete="off">
                <?= csrf_field() ?>

                <div class="guard-wizard__steps" role="tablist" aria-label="Report steps">
                    <div class="guard-wizard__step is-active" data-wizard-step="1" role="tab" aria-selected="true">
                        <span class="guard-wizard__step-num">1</span>
                        <span>Filled report</span>
                    </div>
                    <div class="guard-wizard__step" data-wizard-step="2" role="tab">
                        <span class="guard-wizard__step-num">2</span>
                        <span>Evidences</span>
                    </div>
                    <div class="guard-wizard__step" data-wizard-step="3" role="tab">
                        <span class="guard-wizard__step-num">3</span>
                        <span>Submit</span>
                    </div>
                </div>

                <div class="guard-wizard__pane is-active" data-wizard-pane="1">
                    <h3 class="panel-title guard-wizard__pane-title">Step 1 — Insert filled report</h3>
                    <div class="guard-scanner" data-guard-scanner>
                        <video class="guard-scanner__video" data-guard-scanner-video playsinline muted autoplay aria-label="Camera preview"></video>
                        <img class="guard-scanner__preview" data-guard-scanner-preview alt="Captured report">
                        <div class="guard-scanner__frame" aria-hidden="true"></div>
                        <p class="guard-scanner__hint" data-guard-scanner-hint>Align document inside frame…</p>
                    </div>
                    <div class="guard-scanner__actions">
                        <button type="button" class="btn-primary" data-guard-scan-capture>
                            <i class="fa-solid fa-camera" aria-hidden="true"></i> Smart scan
                        </button>
                        <button type="button" class="btn-ghost" data-guard-scan-retake>
                            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Retake
                        </button>
                        <label class="btn-ghost" style="cursor:pointer;margin:0;">
                            <i class="fa-solid fa-upload" aria-hidden="true"></i> Upload
                            <input type="file" class="visually-hidden" data-guard-report-upload accept="image/*" capture="environment">
                        </label>
                    </div>
                    <button type="button" class="btn-primary" style="width:100%;margin-top:10px;" data-wizard-next="2">
                        Continue to evidences <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="guard-wizard__pane" data-wizard-pane="2">
                    <h3 class="panel-title guard-wizard__pane-title">Step 2 — Insert evidences</h3>
                    <p class="form-hint" style="margin-bottom:8px;">Photos are tagged with device date/time and GPS when available.</p>
                    <label class="btn-ghost" style="display:inline-flex;cursor:pointer;">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i> Add photos
                        <input type="file" class="visually-hidden" data-guard-evidence-input accept="image/*" multiple capture="environment">
                    </label>
                    <div class="guard-evidence-grid" data-guard-evidence-grid></div>
                    <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">
                        <button type="button" class="btn-ghost" data-wizard-back="1"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back</button>
                        <button type="button" class="btn-primary" data-wizard-next="3">Review &amp; submit <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                    </div>
                </div>

                <div class="guard-wizard__pane" data-wizard-pane="3">
                    <h3 class="panel-title guard-wizard__pane-title">Step 3 — Submit</h3>
                    <div class="form-field">
                        <label for="template_name" class="label-with-icon"><i class="fa-solid fa-file" aria-hidden="true"></i> Template name</label>
                        <input type="text" id="template_name" name="template_name" value="Daily guard report" required>
                    </div>
                    <p class="form-hint">Your report will appear as <strong>Pending</strong> until reviewed on the admin dashboard.</p>
                    <button type="button" class="btn-ghost" data-wizard-back="2"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back</button>
                    <button type="submit" class="btn-primary guard-wizard__submit" data-guard-submit>
                        <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Submit report
                    </button>
                </div>
            </form>
        </section>
        </div>
<?php
guard_layout_end();
