<?php
declare(strict_types=1);

/**
 * Session flash + in-app notification modal (replaces window.alert on redirects).
 */

function flash_set(string $type, string $message, string $title = ''): void
{
    $type = match ($type) {
        'success', 'error', 'warning', 'info' => $type,
        default => 'info',
    };

    $_SESSION['_app_flash'] = [
        'type' => $type,
        'message' => $message,
        'title' => $title !== '' ? $title : match ($type) {
            'success' => 'Success',
            'error' => 'Action required',
            'warning' => 'Please review',
            default => 'Notice',
        },
    ];
}

/**
 * @return array{type:string,title:string,message:string}|null
 */
function flash_pull(): ?array
{
    if (!isset($_SESSION['_app_flash']) || !is_array($_SESSION['_app_flash'])) {
        return null;
    }

    $flash = $_SESSION['_app_flash'];
    unset($_SESSION['_app_flash']);

    $type = (string) ($flash['type'] ?? 'info');
    if (!in_array($type, ['success', 'error', 'warning', 'info'], true)) {
        $type = 'info';
    }

    return [
        'type' => $type,
        'message' => (string) ($flash['message'] ?? ''),
        'title' => (string) ($flash['title'] ?? 'Notice'),
    ];
}

function flash_guess_type(string $message): string
{
    $lower = strtolower($message);

    foreach (['could not', 'failed', 'invalid', 'unable', 'error'] as $needle) {
        if (str_contains($lower, $needle)) {
            return 'error';
        }
    }

    foreach (['enter ', 'select ', 'complete ', 'choose ', 'required', 'please '] as $needle) {
        if (str_contains($lower, $needle)) {
            return 'warning';
        }
    }

    return 'success';
}

function app_notify_modal_markup(): void
{
    static $rendered = false;
    if ($rendered) {
        return;
    }
    $rendered = true;
    ?>
<div class="app-modal app-notify-modal" id="appNotifyModal" role="presentation" hidden aria-hidden="true">
    <div class="app-modal__backdrop" data-notify-close tabindex="-1"></div>
    <div class="app-modal__panel app-notify-modal__panel" role="alertdialog" aria-modal="true" aria-labelledby="appNotifyModalTitle" aria-describedby="appNotifyModalMessage">
        <div class="app-notify-modal__icon" id="appNotifyModalIcon" aria-hidden="true">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <h2 class="app-notify-modal__title" id="appNotifyModalTitle">Notice</h2>
        <p class="app-notify-modal__message" id="appNotifyModalMessage"></p>
        <div class="app-notify-modal__actions">
            <button type="button" class="app-notify-modal__btn app-notify-modal__btn--ghost" id="appNotifyModalCancel" hidden>Cancel</button>
            <button type="button" class="app-notify-modal__btn" id="appNotifyModalOk" data-notify-close>OK</button>
        </div>
    </div>
</div>
    <?php
}

function app_notify_modal_styles(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    ?>
<style>
.app-modal {
    position: fixed;
    inset: 0;
    z-index: 2200;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    pointer-events: none;
}
.app-modal[hidden] {
    display: none;
}
.app-modal.is-open {
    pointer-events: auto;
}
.app-modal__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    opacity: 0;
    transition: opacity 0.2s ease;
}
.app-modal.is-open .app-modal__backdrop {
    opacity: 1;
}
.app-modal__panel {
    position: relative;
    z-index: 1;
    opacity: 0;
    transform: translateY(8px);
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.app-modal.is-open .app-modal__panel {
    opacity: 1;
    transform: translateY(0);
}
.app-notify-modal {
    z-index: 2200;
}
.app-notify-modal__panel {
    width: min(420px, calc(100vw - 32px));
    padding: 24px 22px 20px;
    text-align: center;
    border-radius: var(--radius-lg, 12px);
    background: var(--app-card-bg, #fff);
    border: 1px solid var(--border, var(--app-border));
    box-shadow: var(--shadow-lg, 0 12px 40px rgba(0, 0, 0, 0.18));
}
.app-notify-modal__icon {
    font-size: 2.5rem;
    margin-bottom: 12px;
    color: var(--accent-blue, #219ebc);
}
.app-notify-modal.is-success .app-notify-modal__icon { color: #2e7d32; }
.app-notify-modal.is-error .app-notify-modal__icon { color: #c62828; }
.app-notify-modal.is-warning .app-notify-modal__icon { color: #ed6c02; }
.app-notify-modal__title {
    margin: 0 0 10px;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--app-ink-deep, #023047);
}
.app-notify-modal__message {
    margin: 0 0 20px;
    font-size: var(--font-body-size-sm, 0.9375rem);
    line-height: 1.5;
    color: var(--app-ink-muted, #5c6b7d);
}
.app-notify-modal__actions {
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
}
.app-notify-modal__btn--ghost {
    background: transparent;
    color: var(--app-ink-muted, #5c6b7d);
    border: 1px solid var(--border, #d0d7de);
}
.app-notify-modal__btn--ghost:hover {
    filter: none;
    background: var(--bg-elevated, #f5f7fa);
}
.app-notify-modal__btn {
    min-width: 7rem;
    padding: 10px 20px;
    border: none;
    border-radius: var(--radius-sm, 8px);
    background: var(--gradient-primary-btn, linear-gradient(135deg, #219ebc, #023047));
    color: var(--color-white, #fff);
    font-family: inherit;
    font-size: var(--font-body-size-sm, 0.9375rem);
    font-weight: 700;
    cursor: pointer;
}
.app-notify-modal__btn:hover {
    filter: brightness(1.06);
}
body.app-modal-open {
    overflow: hidden;
}
</style>
    <?php
}

function app_notify_modal_script(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $flash = flash_pull();
    $flashJson = $flash !== null
        ? json_encode($flash, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)
        : 'null';

    app_notify_modal_markup();
    ?>
<script>
(function () {
    var modal = document.getElementById('appNotifyModal');
    if (!modal || modal.getAttribute('data-notify-bound') === '1') {
        return;
    }
    modal.setAttribute('data-notify-bound', '1');
    if (modal.parentNode !== document.body) {
        document.body.appendChild(modal);
    }

    var titleEl = document.getElementById('appNotifyModalTitle');
    var messageEl = document.getElementById('appNotifyModalMessage');
    var iconEl = document.getElementById('appNotifyModalIcon');
    var okBtn = document.getElementById('appNotifyModalOk');
    var cancelBtn = document.getElementById('appNotifyModalCancel');
    var onCloseCallback = null;
    var onCancelCallback = null;
    var confirmMode = false;

    var icons = {
        success: 'fa-circle-check',
        error: 'fa-circle-xmark',
        warning: 'fa-triangle-exclamation',
        info: 'fa-circle-info',
    };

    function setType(type) {
        modal.classList.remove('is-success', 'is-error', 'is-warning', 'is-info');
        modal.classList.add('is-' + (type || 'info'));
        var iconClass = icons[type] || icons.info;
        if (iconEl) {
            iconEl.innerHTML = '<i class="fa-solid ' + iconClass + '" aria-hidden="true"></i>';
        }
    }

    function finishClose(runCloseCallback) {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('app-modal-open');
        window.setTimeout(function () {
            modal.setAttribute('hidden', '');
            confirmMode = false;
            if (cancelBtn) {
                cancelBtn.setAttribute('hidden', '');
            }
            if (okBtn) {
                okBtn.textContent = 'OK';
                okBtn.setAttribute('data-notify-close', '');
            }
            if (runCloseCallback && typeof onCloseCallback === 'function') {
                var cb = onCloseCallback;
                onCloseCallback = null;
                cb();
            } else {
                onCloseCallback = null;
            }
            onCancelCallback = null;
        }, 220);
    }

    function closeNotify() {
        if (!modal.classList.contains('is-open')) {
            return;
        }
        if (confirmMode && typeof onCancelCallback === 'function') {
            var cancelCb = onCancelCallback;
            onCancelCallback = null;
            onCloseCallback = null;
            finishClose(false);
            cancelCb();
            return;
        }
        finishClose(true);
    }

    function openNotify(opts) {
        opts = opts || {};
        confirmMode = false;
        setType(opts.type || 'info');
        if (titleEl) {
            titleEl.textContent = opts.title || 'Notice';
        }
        if (messageEl) {
            messageEl.textContent = opts.message || '';
        }
        onCloseCallback = typeof opts.onClose === 'function' ? opts.onClose : null;
        onCancelCallback = null;
        if (cancelBtn) {
            cancelBtn.setAttribute('hidden', '');
        }
        if (okBtn) {
            okBtn.textContent = 'OK';
            okBtn.setAttribute('data-notify-close', '');
        }
        modal.removeAttribute('hidden');
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('is-open');
        document.body.classList.add('app-modal-open');
        if (okBtn) {
            okBtn.focus();
        }
    }

    function confirmNotify(opts) {
        opts = opts || {};
        confirmMode = true;
        setType(opts.type || 'warning');
        if (titleEl) {
            titleEl.textContent = opts.title || 'Confirm';
        }
        if (messageEl) {
            messageEl.textContent = opts.message || '';
        }
        onCloseCallback = typeof opts.onConfirm === 'function' ? opts.onConfirm : null;
        onCancelCallback = typeof opts.onCancel === 'function' ? opts.onCancel : null;
        if (cancelBtn) {
            cancelBtn.removeAttribute('hidden');
            cancelBtn.textContent = opts.cancelLabel || 'Cancel';
        }
        if (okBtn) {
            okBtn.textContent = opts.confirmLabel || 'Confirm';
            okBtn.removeAttribute('data-notify-close');
        }
        modal.removeAttribute('hidden');
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('is-open');
        document.body.classList.add('app-modal-open');
        if (cancelBtn) {
            cancelBtn.focus();
        } else if (okBtn) {
            okBtn.focus();
        }
    }

    if (okBtn) {
        okBtn.addEventListener('click', function () {
            if (!modal.classList.contains('is-open')) {
                return;
            }
            if (confirmMode) {
                finishClose(true);
                return;
            }
            closeNotify();
        });
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeNotify);
    }
    modal.querySelectorAll('[data-notify-close]').forEach(function (el) {
        if (el.id === 'appNotifyModalOk') {
            return;
        }
        el.addEventListener('click', closeNotify);
    });
    modal.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('app-modal__backdrop')) {
            closeNotify();
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) {
            closeNotify();
        }
    });

    window.appNotify = {
        open: openNotify,
        close: closeNotify,
        confirm: confirmNotify,
        success: function (message) {
            openNotify({ type: 'success', title: 'Success', message: message || '' });
        },
        error: function (message) {
            openNotify({ type: 'error', title: 'Error', message: message || '' });
        },
    };

    window.adminConfirmAction = function (opts) {
        opts = opts || {};
        if (window.appNotify && typeof window.appNotify.confirm === 'function') {
            window.appNotify.confirm(opts);
            return;
        }
        if (opts.message && window.confirm(opts.message)) {
            if (typeof opts.onConfirm === 'function') {
                opts.onConfirm();
            }
        }
    };

    var flash = <?= $flashJson ?>;
    if (flash && flash.message) {
        document.addEventListener('DOMContentLoaded', function () {
            openNotify(flash);
        });
    }
})();
</script>
    <?php
}

function app_notify_footer(): void
{
    app_notify_modal_styles();
    app_notify_modal_script();
}
