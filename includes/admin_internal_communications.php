<?php
declare(strict_types=1);

/**
 * Secured memo compose (company-wide or targeted guard memos).
 *
 * @var mysqli_result|false|null $memo_guards_query
 */
?>
<section class="panel panel--compose panel--inbox" aria-labelledby="compose-heading">
    <div class="panel-head">
        <h2 id="compose-heading" class="panel-title">
            <i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i>
            Internal communications
        </h2>
    </div>
    <div class="panel-body">
        <form action="<?= e(app_url('admin/send-memo.php')) ?>" method="POST" id="memoForm" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="distribution_type" id="distTypeValue" value="broadcast">

            <span class="form-section-label">Delivery scope<span class="required-mark">*</span></span>
            <div class="delivery-options" role="group" aria-label="Delivery scope">
                <button type="button" class="delivery-btn active" id="btnBroadcast" data-protocol="broadcast"<?= ui_tooltip('Send to all personnel on roster') ?>>
                    <i class="fa-solid fa-bullhorn" aria-hidden="true"></i>
                    <span class="delivery-btn-title">Company-wide</span>
                    <span class="delivery-btn-desc">Send to all personnel on roster</span>
                </button>
                <button type="button" class="delivery-btn" id="btnTargeted" data-protocol="targeted"<?= ui_tooltip('Send to one selected employee') ?>>
                    <i class="fa-solid fa-user-pen" aria-hidden="true"></i>
                    <span class="delivery-btn-title">Individual recipient</span>
                    <span class="delivery-btn-desc">Directed memo, including notice to explain</span>
                </button>
            </div>

            <div id="memoDetailsContainer" class="form-details is-visible">
                <div id="targetGuardContainer" class="recipient-block">
                    <div class="field">
                        <label for="targetGuardInput" class="field-label field-label--alert">Select recipient<span class="required-mark">*</span></label>
                        <select name="target_guard" id="targetGuardInput" class="field-select">
                            <option value="" disabled selected>Choose an employee…</option>
                            <?php
                            if (isset($memo_guards_query) && $memo_guards_query && $memo_guards_query->num_rows > 0) {
                                $memo_guards_query->data_seek(0);
                                while ($row = $memo_guards_query->fetch_assoc()) {
                                    $label = (string) $row['Last_Name']
                                        . ', ' . (string) $row['First_Name']
                                        . ' (ID: ' . (string) $row['Company_ID'] . ')';
                                    echo '<option value="' . e((string) $row['Company_ID']) . '">' . e($label) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="deadlineDate" class="field-label field-label--alert">Response due date</label>
                        <input type="date" name="deadline_date" id="deadlineDate" class="field-input">
                        <p class="field-hint">Optional — required for compliance-related notices</p>
                    </div>
                </div>

                <div class="field">
                    <label for="memoTypeInput" class="field-label">Message category<span class="required-mark">*</span></label>
                    <select name="memo_type" id="memoTypeInput" class="field-select" required>
                        <option value="" disabled selected>Select a category…</option>
                        <option value="DIRECTIVE">Policy directive — rules and procedure updates</option>
                        <option value="NOTICE">General notice — informational updates</option>
                        <option value="NTE">Notice to explain — formal compliance request</option>
                        <option value="BOLO">Security advisory — threat or watch notice</option>
                    </select>
                </div>

                <div class="field">
                    <label for="memoContentInput" class="field-label">Memo body<span class="required-mark">*</span></label>
                    <textarea name="content" id="memoContentInput" class="field-textarea" rows="8"></textarea>
                </div>

                <button type="submit" name="generate_memo" class="btn-primary"<?= ui_tooltip('Encrypt and publish secured memo') ?>>
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    Publish secured memo
                </button>
            </div>
        </form>
    </div>
</section>
