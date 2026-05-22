<?php
declare(strict_types=1);
?>
<div class="guard-policy-modal" data-policy-modal hidden aria-hidden="true">
    <div class="guard-policy-modal__backdrop" data-policy-modal-close tabindex="-1" aria-hidden="true"></div>
    <div
        class="guard-policy-modal__dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="guardPolicyModalTitle"
    >
        <header class="guard-policy-modal__head">
            <h3 id="guardPolicyModalTitle" class="guard-policy-modal__title" data-policy-modal-title></h3>
            <button
                type="button"
                class="guard-policy-modal__close"
                data-policy-modal-close
                aria-label="Close"
            >
                <span class="guard-policy-modal__close-glyph" aria-hidden="true">×</span>
            </button>
        </header>
        <div class="guard-policy-modal__scroll" data-policy-modal-scroll tabindex="0"></div>
    </div>
</div>
