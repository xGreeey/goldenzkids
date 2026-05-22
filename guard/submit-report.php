<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/guard_layout.php';
require_once __DIR__ . '/../includes/guard_portal.php';
require_once __DIR__ . '/../includes/guard_ui_icons.php';

auth_require_permission('guard.reports.submit');

$companyId = (string) $_SESSION['company_id'];
$reportTypes = guard_portal_report_types();
$reports = guard_portal_user_reports($conn, $companyId);
$showHistory = (($_GET['view'] ?? '') === 'history');
$guardNavActive = 'submit';
guard_layout_head('Submit report');
?>
        <div class="guard-section-stack guard-submit-page">
        <p class="visually-hidden" data-guard-submit-subtitle>Scan your filled report, add evidence photos, then submit. Document AI reads handwritten text on the attendance sheet; evidence is location-stamped.</p>

        <section class="guard-card guard-submit-card<?= $showHistory ? ' is-history-open' : '' ?>" data-guard-submit-card>
            <div class="guard-card__head">
                <h2 id="guard-submit-card-heading" class="panel-title" data-guard-submit-card-heading><?= $showHistory ? 'Report history' : 'Report submission' ?></h2>
                <button
                    type="button"
                    class="btn-ghost guard-report-history-toggle"
                    data-guard-report-history-toggle
                    aria-expanded="<?= $showHistory ? 'true' : 'false' ?>"
                ><?= $showHistory ? 'Back to submission' : 'Report history' ?></button>
            </div>
            <div
                class="guard-report-history"
                data-guard-report-history
                <?= $showHistory ? '' : 'hidden' ?>
                aria-labelledby="guard-submit-card-heading"
            >
                <?php guard_portal_report_history_markup($reports); ?>
            </div>
            <form
                class="guard-wizard"
                data-guard-report-wizard
                method="POST"
                action="api/report-submit.php"
                enctype="multipart/form-data"
                autocomplete="off"
                <?= $showHistory ? 'hidden' : '' ?>
            >
                <?= csrf_field() ?>

                <div class="guard-wizard__steps" role="tablist" aria-label="Report steps">
                    <div class="guard-wizard__step is-active" data-wizard-step="1" role="tab" aria-selected="true">
                        <span class="guard-wizard__step-num">1</span>
                        <span>Filled report</span>
                    </div>
                    <div class="guard-wizard__step" data-wizard-step="2" role="tab">
                        <span class="guard-wizard__step-num">2</span>
                        <span data-guard-step2-label>Evidences</span>
                    </div>
                    <div class="guard-wizard__step" data-wizard-step="3" role="tab">
                        <span class="guard-wizard__step-num">3</span>
                        <span>Submit</span>
                    </div>
                </div>

                <div class="guard-wizard__pane is-active" data-wizard-pane="1">
                    <h3 class="panel-title guard-wizard__pane-title">Step 1 — Insert filled report</h3>
                    <div class="form-field guard-wizard__report-type">
                        <label for="report_type">Report type</label>
                        <div class="guard-select">
                            <select id="report_type" name="report_type" class="guard-select__native" required>
                                <option value="" disabled selected>Select report type…</option>
                                <?php foreach ($reportTypes as $type): ?>
                                    <option value="<?= e($type) ?>"><?= e($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="guard-daily-activity" data-guard-daily-activity hidden>
                        <p class="form-hint guard-daily-activity__intro">Log today&apos;s shift activity for your post.</p>
                        <fieldset class="guard-daily-activity__modes">
                            <legend class="visually-hidden">Daily activity mode</legend>
                            <label class="guard-daily-activity__mode">
                                <input type="radio" name="daily_activity_mode" value="normal" data-guard-daily-mode>
                                <span class="guard-daily-activity__mode-label">Normal Operation</span>
                                <span class="form-hint">Can be submitted immediately without additional input.</span>
                            </label>
                            <label class="guard-daily-activity__mode">
                                <input type="radio" name="daily_activity_mode" value="event" data-guard-daily-mode>
                                <span class="guard-daily-activity__mode-label">With Event / Activity</span>
                                <span class="form-hint">Requires activity details and at least one photo (up to 5).</span>
                            </label>
                        </fieldset>
                        <input type="hidden" name="daily_activity_details" data-guard-daily-details-input value="">
                        <div class="guard-daily-activity__actions">
                            <button type="button" class="btn-primary guard-daily-activity__submit" data-guard-daily-submit hidden>
                                <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Submit report
                            </button>
                        </div>
                    </div>
                    <div data-guard-scan-flow>
                    <div class="guard-scanner" data-guard-scanner>
                        <video class="guard-scanner__video" data-guard-scanner-video playsinline muted aria-label="Camera preview"></video>
                        <img class="guard-scanner__preview" data-guard-scanner-preview alt="Captured report">
                        <button
                            type="button"
                            class="guard-scanner__torch"
                            data-guard-scanner-torch
                            aria-label="Toggle flashlight"
                            aria-pressed="false"
                            hidden
                        ><?= guard_ui_icon('flashlight', 20) ?></button>
                        <div class="guard-scanner__frame" aria-hidden="true"></div>
                        <p class="guard-scanner__hint" data-guard-scanner-hint>Tap Smart scan to open the camera.</p>
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
                    <div class="guard-location-stamp guard-location-stamp--sheet" data-guard-sheet-location hidden>
                        <h4 class="guard-location-stamp__title"><i class="fa-solid fa-file-lines" aria-hidden="true"></i> Sheet location stamp (step 1)</h4>
                        <p class="form-hint" data-guard-sheet-location-status>Stamped when you capture or upload the attendance sheet.</p>
                        <p class="guard-location-stamp__coords mono" data-guard-sheet-location-coords hidden></p>
                        <p class="guard-location-stamp__address" data-guard-sheet-location-address hidden></p>
                    </div>
                    <input type="hidden" name="sheet_latitude" data-guard-sheet-lat-input value="">
                    <input type="hidden" name="sheet_longitude" data-guard-sheet-lng-input value="">
                    <input type="hidden" name="sheet_accuracy_m" data-guard-sheet-acc-input value="">
                    <input type="hidden" name="sheet_location_label" data-guard-sheet-location-label-input value="">
                    <div class="guard-ocr-preview" data-guard-ocr-preview hidden>
                        <h4 class="guard-ocr-preview__title"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Document AI — handwritten text</h4>
                        <p class="form-hint guard-ocr-preview__status" data-guard-ocr-status>Reading form handwriting…</p>
                        <div class="guard-ocr-preview__as-is" data-guard-ocr-as-is hidden></div>
                        <pre class="guard-ocr-preview__text" data-guard-ocr-text></pre>
                    </div>
                    <button type="button" class="btn-primary" style="width:100%;margin-top:10px;" data-wizard-next="2" data-guard-step1-next>
                        Continue <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </button>
                    </div>
                </div>

                <div class="guard-wizard__pane" data-wizard-pane="2">
                    <h3 class="panel-title guard-wizard__pane-title" data-guard-step2-title>Step 2 — Insert evidences</h3>
                    <div class="guard-dad-sheet-preview" data-guard-dad-sheet-preview hidden>
                        <h4 class="guard-dad-sheet-preview__label">Attendance sheet preview</h4>
                        <img src="" alt="Attendance sheet" data-guard-dad-sheet-img>
                    </div>
                    <div class="guard-location-stamp guard-location-stamp--evidence" data-guard-evidence-location>
                        <h4 class="guard-location-stamp__title"><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i> Evidence location stamp (step 2)</h4>
                        <p class="form-hint" data-guard-evidence-location-status>Acquiring GPS at the site when you add photos…</p>
                        <p class="guard-location-stamp__coords mono" data-guard-evidence-location-coords hidden></p>
                        <p class="guard-location-stamp__address" data-guard-evidence-location-address hidden></p>
                    </div>
                    <input type="hidden" name="evidence_latitude" data-guard-evidence-lat-input value="">
                    <input type="hidden" name="evidence_longitude" data-guard-evidence-lng-input value="">
                    <input type="hidden" name="evidence_accuracy_m" data-guard-evidence-acc-input value="">
                    <input type="hidden" name="evidence_location_label" data-guard-evidence-location-label-input value="">
                    <p class="form-hint" data-guard-step2-hint style="margin-bottom:8px;">Photos are tagged with device date/time and GPS when available.</p>
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
                    <p class="form-hint guard-wizard__review-type">
                        <strong>Report type:</strong>
                        <span data-guard-report-type-summary>—</span>
                    </p>
                    <p class="form-hint">Your report will appear as <strong>Pending</strong> until reviewed on the admin dashboard.</p>
                    <button type="button" class="btn-ghost" data-wizard-back="2"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back</button>
                    <button type="submit" class="btn-primary guard-wizard__submit" data-guard-submit>
                        <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Submit report
                    </button>
                </div>
            </form>
            <div
                class="guard-daily-activity-modal"
                data-guard-daily-activity-modal
                hidden
                role="dialog"
                aria-modal="true"
                aria-labelledby="guard-daily-activity-modal-title"
            >
                <div class="guard-daily-activity-modal__backdrop" data-guard-daily-activity-modal-close tabindex="-1"></div>
                <div class="guard-daily-activity-modal__dialog">
                    <header class="guard-daily-activity-modal__head">
                        <h3 id="guard-daily-activity-modal-title" class="guard-daily-activity-modal__title">Event / activity details</h3>
                        <button type="button" class="guard-daily-activity-modal__close" data-guard-daily-activity-modal-close aria-label="Close">
                            <span class="guard-daily-activity-modal__close-glyph" aria-hidden="true">×</span>
                        </button>
                    </header>
                    <div class="guard-daily-activity-modal__body">
                        <div class="form-field">
                            <label for="guard_daily_activity_details">Activity details <span class="form-required">*</span></label>
                            <textarea
                                id="guard_daily_activity_details"
                                class="guard-daily-activity-modal__textarea"
                                rows="4"
                                data-guard-daily-activity-details
                                placeholder="Describe the event or activity at your post…"
                                required
                            ></textarea>
                        </div>
                        <div class="form-field">
                            <label class="guard-daily-activity-modal__photos-label">Supporting photos <span class="form-required">*</span></label>
                            <p class="form-hint">At least 1 image, up to 5 maximum.</p>
                            <label class="btn-ghost" style="display:inline-flex;cursor:pointer;margin-top:6px;">
                                <i class="fa-solid fa-camera" aria-hidden="true"></i> Add photos
                                <input
                                    type="file"
                                    class="visually-hidden"
                                    data-guard-daily-activity-photos
                                    accept="image/*"
                                    multiple
                                    capture="environment"
                                >
                            </label>
                            <p class="form-hint guard-daily-activity-modal__photo-error" data-guard-daily-activity-photo-error hidden></p>
                            <div class="guard-daily-activity-photo-list" data-guard-daily-activity-photo-preview></div>
                        </div>
                    </div>
                    <footer class="guard-daily-activity-modal__foot">
                        <button type="button" class="btn-ghost" data-guard-daily-activity-modal-close>Cancel</button>
                        <button type="button" class="btn-primary" data-guard-daily-activity-modal-save>
                            Done
                        </button>
                    </footer>
                </div>
            </div>
        </section>
        </div>
<?php
guard_layout_end();
