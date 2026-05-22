<?php
declare(strict_types=1);

/**
 * Shared password policy checklist and client-side validation for auth forms.
 */
function auth_password_policy_styles(): string
{
    return <<<'CSS'
.password-requirements-block {
    margin: 10px 0 0;
}
.password-requirements {
    margin: 0;
    padding: 0;
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.password-requirements__title {
    margin: 0 0 2px;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #000;
}
.password-requirements__item {
    font-size: 0.8125rem;
    line-height: 1.45;
    color: #000;
    transition: color 0.15s ease;
}
.password-requirements__item.is-met {
    color: #16a34a;
}
body.auth-shell.auth-sign-in.dark-mode .login-card .password-requirements__title,
body.auth-shell.auth-sign-in.dark-mode .login-card .password-requirements__item:not(.is-met),
body.auth-shell.dark-mode .forced-modal .password-requirements__title,
body.auth-shell.dark-mode .forced-modal .password-requirements__item:not(.is-met) {
    color: var(--login-card-muted, rgba(241, 245, 249, 0.72));
}
body.auth-shell.dark-mode .login-card .password-requirements__item.is-met,
body.auth-shell.dark-mode .forced-modal .password-requirements__item.is-met {
    color: #16a34a;
}
.password-match-hint {
    margin: 8px 0 0;
    font-size: 0.8125rem;
    line-height: 1.45;
    color: #000;
}
.password-match-hint.is-met { color: #16a34a; }
.password-match-hint.is-mismatch { color: #dc2626; }
CSS;
}

function auth_password_policy_requirements_markup(): void
{
    ?>
                    <div class="password-requirements-block" id="passwordRequirements" aria-live="polite">
                        <p class="password-requirements__title">Your password must include:</p>
                        <ul class="password-requirements" role="list">
                            <li class="password-requirements__item" id="reqLength" data-rule="length">At least 8 characters (maximum 64)</li>
                            <li class="password-requirements__item" id="reqLower" data-rule="lower">One lowercase letter</li>
                            <li class="password-requirements__item" id="reqUpper" data-rule="upper">One uppercase letter</li>
                            <li class="password-requirements__item" id="reqNumber" data-rule="number">One number</li>
                            <li class="password-requirements__item" id="reqSymbol" data-rule="symbol">One symbol</li>
                        </ul>
                    </div>
    <?php
}

function auth_password_policy_match_hint_markup(): void
{
    ?>
                    <p class="password-match-hint" id="passwordMatchHint" aria-live="polite">Passwords must match.</p>
    <?php
}

function auth_password_policy_validation_script(string $submitButtonId): void
{
    $submitButtonId = preg_replace('/[^a-zA-Z0-9_-]/', '', $submitButtonId) ?? '';
    ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var newPassword = document.getElementById('new_password');
    var confirmPassword = document.getElementById('confirm_password');
    var submitBtn = document.getElementById('<?= e($submitButtonId) ?>');
    var fill = document.getElementById('strengthFill');
    var label = document.getElementById('strengthLabel');
    var matchHint = document.getElementById('passwordMatchHint');
    if (!newPassword || !confirmPassword || !submitBtn) {
        return;
    }

    var rules = {
        length: { el: document.getElementById('reqLength'), test: function (v) { return v.length >= 8 && v.length <= 64; } },
        lower: { el: document.getElementById('reqLower'), test: function (v) { return /[a-z]/.test(v); } },
        upper: { el: document.getElementById('reqUpper'), test: function (v) { return /[A-Z]/.test(v); } },
        number: { el: document.getElementById('reqNumber'), test: function (v) { return /\d/.test(v); } },
        symbol: { el: document.getElementById('reqSymbol'), test: function (v) { return /[^A-Za-z\d]/.test(v); } }
    };

    function scorePassword(value) {
        var score = 0;
        if (value.length >= 8) score++;
        if (/[a-z]/.test(value)) score++;
        if (/[A-Z]/.test(value)) score++;
        if (/\d/.test(value)) score++;
        if (/[^A-Za-z\d]/.test(value)) score++;
        return score;
    }

    function isPolicyMet(value) {
        return rules.length.test(value)
            && rules.lower.test(value)
            && rules.upper.test(value)
            && rules.number.test(value)
            && rules.symbol.test(value);
    }

    function setRuleState(rule, met) {
        if (!rule.el) return;
        rule.el.classList.toggle('is-met', met);
        rule.el.setAttribute('aria-label', rule.el.textContent + (met ? ' (met)' : ' (not met)'));
    }

    function renderRequirements(val) {
        Object.keys(rules).forEach(function (key) {
            setRuleState(rules[key], rules[key].test(val));
        });
    }

    function renderMatchHint(val, confirmVal) {
        if (!matchHint) return;
        if (confirmVal === '') {
            matchHint.textContent = 'Passwords must match.';
            matchHint.classList.remove('is-met', 'is-mismatch');
            return;
        }
        if (confirmVal === val && val !== '') {
            matchHint.textContent = 'Passwords match.';
            matchHint.classList.add('is-met');
            matchHint.classList.remove('is-mismatch');
            return;
        }
        matchHint.textContent = 'Passwords do not match.';
        matchHint.classList.remove('is-met');
        matchHint.classList.add('is-mismatch');
    }

    function renderStrength() {
        var val = newPassword.value || '';
        var confirmVal = confirmPassword.value || '';
        renderRequirements(val);

        if (fill && label) {
            var score = scorePassword(val);
            var pct = Math.min(100, score * 20);
            fill.style.width = pct + '%';

            if (score <= 2) {
                fill.style.background = '#ef4444';
                label.textContent = 'Password strength: weak';
            } else if (score === 3 || score === 4) {
                fill.style.background = '#f59e0b';
                label.textContent = 'Password strength: medium';
            } else {
                fill.style.background = '#22c55e';
                label.textContent = 'Password strength: strong';
            }
        }

        renderMatchHint(val, confirmVal);

        var valid = isPolicyMet(val) && confirmVal !== '' && confirmVal === val;
        submitBtn.disabled = !valid;
    }

    newPassword.addEventListener('input', renderStrength);
    confirmPassword.addEventListener('input', renderStrength);
    renderStrength();
});
</script>
    <?php
}
