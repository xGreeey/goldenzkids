<?php
declare(strict_types=1);

/** Guard inbox: fixed shell, internal scroll only, mobile compose bar. */
function guard_inbox_styles(): void
{
    ?>
        /* Lock outer scroll on guard inbox; flush under topbar (no canvas padding/gap) */
        body.guard-portal.guard-page-inbox .guard-app__scroll,
        body.guard-portal .guard-app__scroll:has(.guard-inbox-page) {
            overflow: hidden !important;
            display: flex;
            flex-direction: column;
            min-height: 0;
            height: 100%;
            flex: 1 1 0;
            padding: 0 !important;
            gap: 0 !important;
        }

        body.guard-portal .guard-inbox-page.guard-section-stack,
        body.guard-portal .guard-section-stack.guard-inbox-page {
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 0;
            margin: 0;
        }

        body.guard-portal .guard-inbox-page .inbox-messaging-solo {
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            margin: 0 !important;
            padding: 0;
        }

        body.guard-portal .guard-inbox-page #messaging-board.messaging-board--split,
        body.guard-portal .guard-inbox-page .messaging-board--split {
            flex: 1 1 auto;
            min-height: 0 !important;
            height: 100%;
            max-height: none;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            margin: 0;
            border-radius: 0;
            border-left: none;
            border-right: none;
            border-top: none;
            border-bottom: none;
            box-shadow: none;
        }

        body.guard-portal .guard-inbox-page .messaging-board--split .messaging-board__layout {
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
        }

        body.guard-portal .guard-inbox-page .messaging-board--split .messaging-board__contacts {
            min-height: 0;
            overflow: hidden;
        }

        body.guard-portal .guard-inbox-page .messaging-board--split .messaging-board__unread-banner {
            margin-top: 0;
            border-top: none;
        }

        body.guard-portal .guard-inbox-page .messaging-board--split .messaging-board__unread-banner:not(.is-hidden) {
            border-bottom: 1px solid var(--guard-ui-border, #e2e8f0);
        }

        body.guard-portal .guard-inbox-page .messaging-board__contacts-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }

        body.guard-portal .guard-inbox-page #messagingThreadPane {
            min-height: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        body.guard-portal .guard-inbox-page #messagingThreadScroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }

        body.guard-portal .guard-inbox-page .messaging-compose {
            flex-shrink: 0;
        }

        /* Compose: [textarea] [attach][send] */
        body.guard-portal .guard-inbox-page .messaging-compose__bar {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }

        body.guard-portal .guard-inbox-page .messaging-compose__input {
            flex: 1 1 auto;
            min-width: 0;
        }

        body.guard-portal .guard-inbox-page .messaging-compose__actions {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        body.guard-portal .guard-inbox-page .messaging-compose__attach {
            flex: 0 0 40px !important;
            width: 40px !important;
            min-width: 40px !important;
            height: 40px !important;
            cursor: pointer !important;
            opacity: 1 !important;
            pointer-events: auto !important;
            color: #334155 !important;
            background: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
        }

        body.guard-portal .guard-inbox-page .messaging-compose__attach-icon,
        body.guard-portal .guard-inbox-page .messaging-compose__attach .messaging-compose__icon-svg {
            display: inline-flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            color: #334155 !important;
        }

        body.guard-portal .guard-inbox-page .messaging-compose__send {
            flex: 0 0 auto !important;
            min-width: 76px !important;
            width: auto !important;
            height: 40px;
            padding: 0 12px !important;
            color: #fff !important;
            background: #023047 !important;
        }

        body.guard-portal .guard-inbox-page .messaging-compose__send-label {
            display: inline !important;
            font-size: 0.8125rem !important;
            font-weight: 600 !important;
            color: #fff !important;
        }

        body.guard-portal .guard-inbox-page .messaging-compose__send i {
            color: #fff;
            font-size: 0.875rem;
        }

        @media (max-width: 719px) {
            body.guard-portal .guard-inbox-page .messaging-compose {
                padding-bottom: max(10px, env(safe-area-inset-bottom, 0px));
            }

            body.guard-portal .guard-inbox-page .messaging-compose__send {
                min-width: 80px !important;
            }
        }
    <?php
}
