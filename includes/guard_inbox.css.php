<?php
declare(strict_types=1);

/** Guard inbox: fixed shell, internal scroll only, compact light/dark messaging UI. */
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
            border: none;
            box-shadow: none;
            color: var(--guard-ui-primary);
            --messaging-navy: #023047;
            --messaging-navy-soft: #e8f4fa;
            --messaging-panel-bg: #f1f5f9;
            --messaging-border: var(--guard-ui-border);
        }

        body.guard-portal:not(.light-mode) .guard-inbox-page #messaging-board.messaging-board--split {
            --messaging-navy-soft: rgba(96, 165, 250, 0.12);
            --messaging-panel-bg: #0f172a;
            --messaging-border: var(--guard-ui-border);
        }

        body.guard-portal .guard-inbox-page .messaging-board--split .messaging-board__layout {
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
            grid-template-columns: minmax(108px, 32%) minmax(0, 68%);
        }

        body.guard-portal .guard-inbox-page .messaging-board--split .messaging-board__contacts {
            min-height: 0;
            overflow: hidden;
            background: var(--messaging-panel-bg);
            border-right-color: var(--guard-ui-border);
        }

        body.guard-portal .guard-inbox-page .messaging-board--split .messaging-board__thread,
        body.guard-portal .guard-inbox-page #messagingThreadPane.messaging-board__thread {
            flex: 1 1 0;
            min-height: 0;
            max-height: 100%;
            height: 100%;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            background: var(--guard-ui-surface);
            color: var(--guard-ui-primary);
        }

        body.guard-portal .guard-inbox-page .messaging-board--split .messaging-board__unread-banner {
            margin: 0 6px 6px;
            padding: 8px 10px;
            font-size: 0.6875rem;
            border-color: var(--guard-ui-border);
            color: var(--guard-ui-primary);
        }

        body.guard-portal .guard-inbox-page .messaging-board--split .messaging-board__unread-banner:not(.is-hidden) {
            border-bottom: 1px solid var(--guard-ui-border);
        }

        body.guard-portal:not(.light-mode) .guard-inbox-page .messaging-board--split .messaging-board__unread-banner {
            background: rgba(59, 130, 246, 0.12);
            border-color: var(--guard-ui-border);
        }

        /* Contacts list — compact */
        body.guard-portal .guard-inbox-page .messaging-board__contacts-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }

        body.guard-portal .guard-inbox-page .messaging-board--split .messaging-board__section {
            padding: 4px 4px 6px;
        }

        body.guard-portal .guard-inbox-page .messaging-board--split .messaging-board__section-title {
            padding: 2px 6px 4px;
            margin: 0;
            font-size: 0.625rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--guard-ui-subtle);
        }

        body.guard-portal .guard-inbox-page .messaging-board--split .messaging-contact-list {
            padding: 2px 4px;
        }

        body.guard-portal .guard-inbox-page .messaging-board--split .messaging-contact {
            gap: 6px;
            padding: 7px 6px;
            border-radius: 6px;
            color: var(--guard-ui-primary);
        }

        body.guard-portal .guard-inbox-page .messaging-board--split .messaging-contact:hover {
            background: rgba(2, 48, 71, 0.06);
        }

        body.guard-portal:not(.light-mode) .guard-inbox-page .messaging-board--split .messaging-contact:hover {
            background: rgba(255, 255, 255, 0.06);
        }

        body.guard-portal .guard-inbox-page .messaging-board--split .messaging-contact.is-active {
            background: var(--messaging-navy-soft);
            box-shadow: inset 2px 0 0 var(--messaging-navy);
        }

        body.guard-portal:not(.light-mode) .guard-inbox-page .messaging-board--split .messaging-contact.is-active {
            box-shadow: inset 2px 0 0 #60a5fa;
        }

        body.guard-portal .guard-inbox-page .messaging-board--split .messaging-avatar--sm {
            width: 30px;
            height: 30px;
            font-size: 0.625rem;
        }

        body.guard-portal .guard-inbox-page .messaging-board--split .messaging-contact__label {
            font-size: 0.6875rem;
            font-weight: 600;
            color: var(--guard-ui-primary);
        }

        body.guard-portal .guard-inbox-page .messaging-board--split .messaging-contact__id {
            display: none;
        }

        body.guard-portal .guard-inbox-page .messaging-board--split .messaging-contact__badge {
            min-width: 1rem;
            padding: 1px 5px;
            font-size: 0.6rem;
        }

        /* Thread pane — fixed column; only the message list scrolls */
        body.guard-portal .guard-inbox-page #messagingThreadPane {
            flex: 1 1 0;
            min-height: 0;
            max-height: 100%;
            height: 100%;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        body.guard-portal .guard-inbox-page .messaging-thread__header {
            flex-shrink: 0;
            flex-wrap: nowrap;
            align-items: center;
            gap: 6px;
            padding: 8px 10px;
            background: var(--guard-ui-surface);
            border-bottom: 1px solid var(--guard-ui-border);
        }

        body.guard-portal .guard-inbox-page .messaging-thread__header-profile {
            gap: 8px;
            min-width: 0;
        }

        body.guard-portal .guard-inbox-page .messaging-thread__header-info {
            gap: 1px;
        }

        body.guard-portal .guard-inbox-page .messaging-avatar--lg {
            width: 32px;
            height: 32px;
            font-size: 0.6875rem;
        }

        body.guard-portal .guard-inbox-page .messaging-thread__name {
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--guard-ui-primary);
        }

        body.guard-portal .guard-inbox-page .messaging-thread__status {
            font-size: 0.6875rem;
            color: var(--guard-ui-subtle);
        }

        body.guard-portal .guard-inbox-page .messaging-thread__actions {
            flex-shrink: 0;
            flex-wrap: nowrap;
            gap: 4px;
        }

        body.guard-portal .guard-inbox-page .messaging-thread__action {
            padding: 4px 8px;
            font-size: 0.6875rem;
            line-height: 1.2;
            border-radius: 6px;
            border: 1px solid var(--guard-ui-border);
            background: var(--guard-ui-surface);
            color: var(--guard-ui-subtle);
        }

        body.guard-portal .guard-inbox-page .messaging-thread__action:hover {
            border-color: #94a3b8;
            color: var(--guard-ui-primary);
            background: var(--messaging-panel-bg);
        }

        body.guard-portal:not(.light-mode) .guard-inbox-page .messaging-thread__action:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: #64748b;
        }

        body.guard-portal .guard-inbox-page .messaging-thread__action--danger {
            border-color: #fecaca;
            color: #b42318;
            background: #fff5f5;
        }

        body.guard-portal:not(.light-mode) .guard-inbox-page .messaging-thread__action--danger {
            border-color: rgba(248, 113, 113, 0.4);
            color: #fca5a5;
            background: rgba(127, 29, 29, 0.25);
        }

        body.guard-portal .guard-inbox-page #messagingThreadScroll,
        body.guard-portal .guard-inbox-page .messaging-thread__messages {
            flex: 1 1 0;
            min-height: 0;
            max-height: 100%;
            overflow-y: auto;
            overflow-x: hidden;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
            overflow-anchor: none;
            padding: 10px 10px 8px;
            gap: 8px;
            background: var(--messaging-panel-bg);
        }

        body.guard-portal:not(.light-mode) .guard-inbox-page .messaging-board--split .messaging-board__thread,
        body.guard-portal:not(.light-mode) .guard-inbox-page #messagingThreadPane {
            background: var(--guard-ui-surface);
            color: var(--guard-ui-primary);
        }

        body.guard-portal:not(.light-mode) .guard-inbox-page .messaging-thread__header {
            background: var(--guard-ui-surface);
            border-bottom-color: var(--guard-ui-border);
        }

        body.guard-portal:not(.light-mode) .guard-inbox-page .messaging-thread__name {
            color: var(--guard-ui-primary);
        }

        body.guard-portal:not(.light-mode) .guard-inbox-page .messaging-thread__status {
            color: var(--guard-ui-subtle);
        }

        body.guard-portal:not(.light-mode) .guard-inbox-page #messagingThreadScroll,
        body.guard-portal:not(.light-mode) .guard-inbox-page .messaging-thread__messages {
            background: var(--messaging-panel-bg);
        }

        body.guard-portal:not(.light-mode) .guard-inbox-page .messaging-compose {
            background: var(--guard-ui-surface);
            border-top-color: var(--guard-ui-border);
        }

        body.guard-portal:not(.light-mode) .guard-inbox-page .messaging-compose__attach {
            background: rgba(15, 23, 42, 0.65);
            border-color: var(--guard-ui-border);
            color: var(--guard-ui-primary);
        }

        body.guard-portal:not(.light-mode) .guard-inbox-page .messaging-board__placeholder,
        body.guard-portal:not(.light-mode) .guard-inbox-page .messaging-board__idle,
        body.guard-portal:not(.light-mode) .guard-inbox-page .messaging-board__notice {
            color: var(--guard-ui-subtle);
            background: transparent;
        }

        body.guard-portal:not(.light-mode) .guard-inbox-page .messaging-bubble--theirs,
        body.guard-portal:not(.light-mode) .guard-inbox-page .messaging-bubble:not(.messaging-bubble--mine) {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--guard-ui-border);
            color: var(--guard-ui-primary);
        }

        body.guard-portal:not(.light-mode) .guard-inbox-page .messaging-bubble__sender {
            color: var(--guard-ui-subtle);
        }

        body.guard-portal .guard-inbox-page .messaging-bubble {
            max-width: 90%;
            padding: 7px 10px;
            border-radius: 10px;
            background: var(--guard-ui-surface);
            border: 1px solid var(--guard-ui-border);
            color: var(--guard-ui-primary);
        }

        body.guard-portal:not(.light-mode) .guard-inbox-page .messaging-bubble {
            background: rgba(255, 255, 255, 0.06);
            border-color: var(--guard-ui-border);
        }

        body.guard-portal .guard-inbox-page .messaging-bubble--mine {
            background: var(--messaging-navy);
            border-color: var(--messaging-navy);
            color: #ffffff;
        }

        body.guard-portal:not(.light-mode) .guard-inbox-page .messaging-bubble--mine {
            background: #0369a1;
            border-color: #0369a1;
        }

        body.guard-portal .guard-inbox-page .messaging-bubble__text {
            margin: 0 0 4px;
            font-size: 0.8125rem;
            line-height: 1.4;
            color: inherit;
        }

        body.guard-portal .guard-inbox-page .messaging-bubble--mine .messaging-bubble__text {
            color: #ffffff;
        }

        body.guard-portal .guard-inbox-page .messaging-bubble__time {
            font-size: 0.625rem;
            color: var(--guard-ui-subtle);
        }

        body.guard-portal .guard-inbox-page .messaging-bubble--mine .messaging-bubble__time {
            color: rgba(255, 255, 255, 0.78);
        }

        body.guard-portal .guard-inbox-page .messaging-board__placeholder {
            padding: 12px;
            font-size: 0.75rem;
            color: var(--guard-ui-subtle);
        }

        /* Compose — theme-aware, compact */
        body.guard-portal .guard-inbox-page .messaging-compose {
            flex-shrink: 0;
            padding: 8px 10px max(8px, env(safe-area-inset-bottom, 0px));
            border-top: 1px solid var(--guard-ui-border);
            background: var(--guard-ui-surface);
        }

        body.guard-portal .guard-inbox-page .messaging-compose__bar {
            display: flex;
            align-items: flex-end;
            gap: 6px;
            width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }

        body.guard-portal .guard-inbox-page .messaging-compose__input {
            flex: 1 1 auto;
            min-width: 0;
            min-height: 36px;
            max-height: 72px;
            padding: 8px 10px;
            font-size: 0.8125rem;
            line-height: 1.35;
            color: var(--guard-ui-primary);
            background: var(--messaging-panel-bg);
            border: 1px solid var(--guard-ui-border);
            border-radius: 8px;
        }

        body.guard-portal .guard-inbox-page .messaging-compose__input::placeholder {
            color: var(--guard-ui-faint);
        }

        body.guard-portal .guard-inbox-page .messaging-compose__input:focus {
            outline: none;
            border-color: #64748b;
            box-shadow: 0 0 0 2px rgba(2, 48, 71, 0.12);
        }

        body.guard-portal:not(.light-mode) .guard-inbox-page .messaging-compose__input:focus {
            border-color: #94a3b8;
            box-shadow: 0 0 0 2px rgba(148, 163, 184, 0.2);
        }

        body.guard-portal .guard-inbox-page .messaging-compose__actions {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        body.guard-portal .guard-inbox-page .messaging-compose__attach {
            position: relative;
            flex: 0 0 36px;
            width: 36px;
            min-width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            cursor: pointer;
            color: var(--guard-ui-primary);
            background: var(--messaging-panel-bg);
            border: 1px solid var(--guard-ui-border);
            border-radius: 8px;
        }

        body.guard-portal .guard-inbox-page .messaging-compose__file-input {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            border: 0;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }

        body.guard-portal .guard-inbox-page .messaging-compose__attach-icon,
        body.guard-portal .guard-inbox-page .messaging-compose__attach .messaging-compose__icon-svg {
            display: inline-flex;
            position: relative;
            z-index: 1;
            pointer-events: none;
            color: var(--guard-ui-primary);
        }

        body.guard-portal .guard-inbox-page .messaging-compose__send {
            flex: 0 0 auto;
            min-width: 72px;
            height: 36px;
            padding: 0 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #ffffff;
            background: var(--messaging-navy);
            border: 1px solid var(--messaging-navy);
            border-radius: 8px;
        }

        body.guard-portal:not(.light-mode) .guard-inbox-page .messaging-compose__send {
            background: #0369a1;
            border-color: #0369a1;
        }

        body.guard-portal .guard-inbox-page .messaging-compose__send:hover {
            filter: brightness(1.08);
        }

        body.guard-portal .guard-inbox-page .messaging-compose__send-label {
            display: inline;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #ffffff;
        }

        body.guard-portal .guard-inbox-page .messaging-compose__send .messaging-compose__icon-svg {
            color: #ffffff;
        }

        body.guard-portal .guard-inbox-page .messaging-compose__file-chip {
            margin: 6px 0 0;
            font-size: 0.6875rem;
            color: var(--guard-ui-subtle);
        }

        @media (max-width: 719px) {
            body.guard-portal .guard-inbox-page .messaging-board--split .messaging-board__layout {
                grid-template-columns: minmax(100px, 34%) minmax(0, 66%);
            }

            body.guard-portal .guard-inbox-page .messaging-thread__header {
                padding: 7px 8px;
            }

            body.guard-portal .guard-inbox-page .messaging-thread__action {
                padding: 4px 6px;
                font-size: 0.625rem;
            }

            body.guard-portal .guard-inbox-page .messaging-compose__send {
                min-width: 68px;
            }
        }
    <?php
}
