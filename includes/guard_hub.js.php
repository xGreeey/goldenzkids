<?php
declare(strict_types=1);

function guard_hub_scripts(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    ?>
<script>
(function () {
    'use strict';

    function qs(sel, root) {
        return (root || document).querySelector(sel);
    }
    function qsa(sel, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(sel));
    }

    /* Toast */
    var toastEl = null;
    function ensureToast() {
        if (!toastEl) {
            toastEl = document.createElement('div');
            toastEl.className = 'guard-toast';
            toastEl.setAttribute('role', 'status');
            toastEl.setAttribute('aria-live', 'polite');
            document.body.appendChild(toastEl);
        }
        return toastEl;
    }
    window.guardShowToast = function (message, type) {
        var t = ensureToast();
        t.textContent = message;
        t.className = 'guard-toast is-visible' + (type ? ' guard-toast--' + type : '');
        clearTimeout(t._hideTimer);
        t._hideTimer = setTimeout(function () {
            t.classList.remove('is-visible');
        }, 3200);
    };

    var GUARD_STORAGE_KEY = 'guardPortalHref';

    function persistGuardLocation(href) {
        if (!document.body.classList.contains('guard-portal')) {
            return;
        }
        try {
            sessionStorage.setItem(GUARD_STORAGE_KEY, href || window.location.href);
        } catch (e) {
            /* ignore */
        }
    }

    window.persistGuardPortalLocation = persistGuardLocation;

    function updateHubTabUrl(bar, tabId) {
        var path = window.location.pathname.split('/').pop() || '';
        if (path !== 'corner.php' && path !== 'inbox.php') {
            return;
        }
        try {
            var url = new URL(window.location.href);
            if (path === 'corner.php' && tabId === 'announce') {
                url.searchParams.delete('tab');
            } else if (path === 'inbox.php' && tabId === 'memos') {
                url.searchParams.delete('tab');
            } else {
                url.searchParams.set('tab', tabId);
            }
            history.replaceState({ panelNav: true, url: url.href }, '', url.href);
            persistGuardLocation(url.href);
        } catch (e) {
            /* ignore */
        }
    }

    function guardRestoreOnReload() {
        if (!document.body.classList.contains('guard-portal')) {
            return;
        }
        var nav = performance.getEntriesByType('navigation')[0];
        if (!nav || nav.type !== 'reload') {
            return;
        }
        var stored = sessionStorage.getItem(GUARD_STORAGE_KEY);
        if (!stored) {
            return;
        }
        var current = window.location.href;
        if (stored === current) {
            return;
        }
        try {
            var storedUrl = new URL(stored, current);
            var currentUrl = new URL(current);
            var storedFile = storedUrl.pathname.split('/').pop() || '';
            var currentFile = currentUrl.pathname.split('/').pop() || '';
            var panelPages = ['dashboard.php', 'submit-report.php', 'inbox.php', 'corner.php'];
            if (panelPages.indexOf(storedFile) === -1) {
                return;
            }
            if (storedFile !== currentFile) {
                window.location.replace(stored);
                return;
            }
            if (storedUrl.search !== currentUrl.search || storedUrl.hash !== currentUrl.hash) {
                history.replaceState({ panelNav: true, url: stored }, '', stored);
            }
        } catch (e) {
            /* ignore */
        }
    }

    function hubPanelsForBar(bar) {
        var wrap = bar.nextElementSibling;
        if (wrap && wrap.classList.contains('guard-hub-panels')) {
            return qsa('[data-guard-hub-panel]', wrap);
        }
        var parent = bar.parentElement;
        return parent ? qsa('[data-guard-hub-panel]', parent) : [];
    }

    /* Hub tabs — fade/slide between panels (scoped per tab bar) */
    function initHubTabs(root) {
        qsa('[data-guard-hub-tabs]', root).forEach(function (bar) {
            if (bar._guardHubTabsBound) {
                return;
            }
            bar._guardHubTabsBound = true;

            var buttons = qsa('[data-guard-hub-tab]', bar);
            var panels = hubPanelsForBar(bar);
            var activeId = null;
            var leaveTimer = null;

            panels.forEach(function (p) {
                var on = p.classList.contains('is-active');
                p.setAttribute('aria-hidden', on ? 'false' : 'true');
                if (on) {
                    activeId = p.getAttribute('data-guard-hub-panel');
                }
            });

            function activateTab(id, btn) {
                if (activeId === id) {
                    return;
                }
                if (leaveTimer) {
                    clearTimeout(leaveTimer);
                    leaveTimer = null;
                }
                var prev = panels.filter(function (p) {
                    return p.getAttribute('data-guard-hub-panel') === activeId;
                })[0];
                var cornerPage = bar.closest('.guard-corner-page');
                if (cornerPage && activeId === 'social' && id !== 'social') {
                    stopGuardLiveFeeds(cornerPage);
                }

                function showNext() {
                    buttons.forEach(function (b) {
                        var on = b === btn || (btn && b.getAttribute('data-guard-hub-tab') === id);
                        b.classList.toggle('is-active', on);
                        b.setAttribute('aria-selected', on ? 'true' : 'false');
                    });
                    panels.forEach(function (p) {
                        var on = p.getAttribute('data-guard-hub-panel') === id;
                        p.classList.toggle('is-active', on);
                        p.setAttribute('aria-hidden', on ? 'false' : 'true');
                        if (!on) {
                            p.classList.remove('is-leaving');
                        }
                    });
                    activeId = id;
                    updateHubTabUrl(bar, id);
                    if (cornerPage) {
                        if (id === 'social') {
                            initGuardLiveFeeds(cornerPage);
                        } else {
                            stopGuardLiveFeeds(cornerPage);
                        }
                        if (id !== 'policies') {
                            var policyModal = guardPolicyModalElement();
                            if (policyModal && typeof policyModal._guardPolicyCloseFn === 'function') {
                                policyModal._guardPolicyCloseFn();
                            }
                        }
                    }
                }

                if (prev) {
                    prev.classList.add('is-leaving');
                    prev.classList.remove('is-active');
                    leaveTimer = setTimeout(function () {
                        leaveTimer = null;
                        prev.classList.remove('is-leaving');
                        showNext();
                    }, 140);
                } else {
                    showNext();
                }
            }

            buttons.forEach(function (tabBtn) {
                tabBtn.addEventListener('click', function () {
                    activateTab(tabBtn.getAttribute('data-guard-hub-tab'), tabBtn);
                });
            });

            bar._guardHubActivateTab = activateTab;
        });
    }

    var guardLiveFeedRefreshMs = 180000;
    var guardLiveFeedViewportHeight = 400;
    var guardLiveFeedCropTop = 88;
    var guardLiveFeedLoadTimeoutMs = 18000;

    function guardLiveFeedWidth(scrollEl) {
        var w = scrollEl && scrollEl.clientWidth ? scrollEl.clientWidth : 320;
        return Math.max(280, Math.min(560, Math.floor(w) || 320));
    }

    function guardLiveFeedVisibleHeight(feed) {
        if (typeof window.matchMedia === 'function' && window.matchMedia('(max-width: 639px)').matches) {
            return 360;
        }
        var scroll = qs('[data-guard-live-feed-scroll]', feed);
        var attr = scroll ? scroll.getAttribute('data-guard-live-feed-viewport-h') : '';
        var parsed = attr ? parseInt(attr, 10) : 0;
        if (!isNaN(parsed) && parsed >= 280) {
            return parsed;
        }
        return guardLiveFeedViewportHeight;
    }

    function guardLiveFeedIframeHeight(feed) {
        return guardLiveFeedVisibleHeight(feed) + guardLiveFeedCropTop;
    }

    function buildGuardLiveFeedSrc(pageUrl, width, height, bustCache) {
        var params = new URLSearchParams({
            href: pageUrl,
            tabs: 'timeline',
            width: String(width),
            height: String(height),
            small_header: 'true',
            hide_cover: 'true',
            show_facepile: 'false',
            adapt_container_width: 'true'
        });
        var src = 'https://www.facebook.com/plugins/page.php?' + params.toString();
        if (bustCache) {
            src += '&_=' + String(Date.now());
        }
        return src;
    }

    function formatLiveFeedUpdated() {
        try {
            return 'Auto-refresh · ' + new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        } catch (e) {
            return 'Auto-refresh · just now';
        }
    }

    function setLiveFeedsRefresh(root) {
        var el = qs('[data-guard-live-feeds-refresh]', root);
        if (el) {
            el.textContent = formatLiveFeedUpdated();
        }
    }

    function applyGuardLiveFeedCrop(feed, frame, width) {
        var scroll = qs('[data-guard-live-feed-scroll]', feed);
        var viewport = qs('[data-guard-live-feed-viewport]', feed);
        if (!scroll || !frame) {
            return;
        }
        var visibleH = guardLiveFeedVisibleHeight(feed);
        var iframeH = guardLiveFeedIframeHeight(feed);
        scroll.style.height = visibleH + 'px';
        scroll.style.minHeight = visibleH + 'px';
        scroll.style.maxHeight = visibleH + 'px';
        if (viewport) {
            viewport.style.height = visibleH + 'px';
        }
        feed.style.setProperty('--guard-live-feed-crop', guardLiveFeedCropTop + 'px');
        frame.style.width = width + 'px';
        frame.style.maxWidth = '100%';
        frame.style.marginTop = '-' + guardLiveFeedCropTop + 'px';
        frame.style.height = iframeH + 'px';
        frame.setAttribute('width', String(width));
        frame.setAttribute('height', String(iframeH));
    }

    function showLiveFeedLoading(feed, on) {
        var loading = qs('[data-guard-live-feed-loading]', feed);
        if (loading) {
            loading.classList.toggle('is-hidden', !on);
        }
    }

    function showLiveFeedFallback(feed, on) {
        var fallback = qs('[data-guard-live-feed-fallback]', feed);
        var frame = qs('[data-guard-live-feed-frame]', feed);
        if (fallback) {
            if (on) {
                fallback.removeAttribute('hidden');
            } else {
                fallback.setAttribute('hidden', '');
            }
        }
        if (frame) {
            frame.classList.toggle('is-hidden', !!on);
        }
    }

    function mountGuardLiveFeed(feed, bustCache, root) {
        var pageUrl = feed.getAttribute('data-page-url');
        var scroll = qs('[data-guard-live-feed-scroll]', feed);
        var frame = qs('[data-guard-live-feed-frame]', feed);
        if (!pageUrl || !scroll || !frame) {
            return;
        }
        showLiveFeedFallback(feed, false);
        showLiveFeedLoading(feed, true);
        if (feed._guardLiveLoadTimer) {
            clearTimeout(feed._guardLiveLoadTimer);
        }
        var width = guardLiveFeedWidth(scroll);
        var iframeH = guardLiveFeedIframeHeight(feed);
        applyGuardLiveFeedCrop(feed, frame, width);
        frame.classList.remove('is-hidden');
        frame.onload = function () {
            if (feed._guardLiveLoadTimer) {
                clearTimeout(feed._guardLiveLoadTimer);
                feed._guardLiveLoadTimer = null;
            }
            showLiveFeedLoading(feed, false);
            showLiveFeedFallback(feed, false);
            applyGuardLiveFeedCrop(feed, frame, guardLiveFeedWidth(scroll));
            if (root) {
                setLiveFeedsRefresh(root);
            }
        };
        frame.onerror = function () {
            showLiveFeedLoading(feed, false);
            showLiveFeedFallback(feed, true);
        };
        feed._guardLiveLoadTimer = setTimeout(function () {
            feed._guardLiveLoadTimer = null;
            if (frame.getAttribute('src') && !frame.classList.contains('is-hidden')) {
                showLiveFeedLoading(feed, false);
                showLiveFeedFallback(feed, true);
            }
        }, guardLiveFeedLoadTimeoutMs);
        frame.setAttribute('src', buildGuardLiveFeedSrc(pageUrl, width, iframeH, !!bustCache));
    }

    function layoutGuardLiveFeeds(wrap, bustCache) {
        var root = wrap.closest('.guard-card--live-feeds') || wrap.closest('.guard-corner-page') || document;
        qsa('[data-guard-live-feed]', wrap).forEach(function (feed) {
            mountGuardLiveFeed(feed, !!bustCache, root);
        });
        setLiveFeedsRefresh(root);
    }

    function bindGuardLiveFeedResize(wrap) {
        if (!wrap || wrap._guardLiveResizeBound) {
            return;
        }
        wrap._guardLiveResizeBound = true;
        var timer;
        function relayout() {
            if (timer) {
                clearTimeout(timer);
            }
            timer = setTimeout(function () {
                timer = null;
                layoutGuardLiveFeeds(wrap, false);
            }, 150);
        }
        if (typeof ResizeObserver !== 'undefined') {
            var ro = new ResizeObserver(relayout);
            ro.observe(wrap);
            wrap._guardLiveResizeObserver = ro;
        }
        window.addEventListener('resize', relayout);
    }

    function refreshGuardLiveFeeds(wrap) {
        layoutGuardLiveFeeds(wrap, true);
    }

    function unloadGuardLiveFeeds(root) {
        var wrap = qs('[data-guard-live-feeds]', root);
        if (!wrap) {
            return;
        }
        if (wrap._guardLiveResizeObserver) {
            wrap._guardLiveResizeObserver.disconnect();
            wrap._guardLiveResizeObserver = null;
            wrap._guardLiveResizeBound = false;
        }
        qsa('[data-guard-live-feed]', wrap).forEach(function (feed) {
            if (feed._guardLiveLoadTimer) {
                clearTimeout(feed._guardLiveLoadTimer);
                feed._guardLiveLoadTimer = null;
            }
            var frame = qs('[data-guard-live-feed-frame]', feed);
            if (frame) {
                frame.removeAttribute('src');
                frame.onload = null;
                frame.onerror = null;
            }
        });
    }

    function stopGuardLiveFeeds(root) {
        var wrap = qs('[data-guard-live-feeds]', root);
        if (!wrap) {
            return;
        }
        if (wrap._guardLiveTimer) {
            clearInterval(wrap._guardLiveTimer);
            wrap._guardLiveTimer = null;
        }
        unloadGuardLiveFeeds(root);
    }

    function startGuardLiveFeeds(wrap) {
        stopGuardLiveFeeds(wrap);
        wrap._guardLiveTimer = setInterval(function () {
            refreshGuardLiveFeeds(wrap);
        }, guardLiveFeedRefreshMs);
    }

    function initGuardLiveFeeds(root) {
        var wrap = qs('[data-guard-live-feeds]', root);
        if (!wrap) {
            return;
        }
        bindGuardLiveFeedResize(wrap);
        layoutGuardLiveFeeds(wrap, false);
        setTimeout(function () {
            layoutGuardLiveFeeds(wrap, false);
        }, 350);
        startGuardLiveFeeds(wrap);
    }

    /* Policies — modal popup (blur backdrop, scrollable body) */
    function guardPolicyModalElement() {
        return qs('[data-policy-modal]', document.body) || qs('[data-policy-modal]');
    }

    function guardPolicyEscapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function guardPolicyNumberedItems(text) {
        var lines = String(text || '').split(/\r\n|\r|\n/);
        var items = [];
        var current = '';
        lines.forEach(function (line) {
            line = line.trim();
            if (!line) {
                return;
            }
            var match = line.match(/^(\d+)\.\s*(.*)$/);
            if (match) {
                if (current) {
                    items.push(current.trim());
                }
                current = (match[2] || '').trim();
                return;
            }
            if (current) {
                current += ' ' + line;
            }
        });
        if (current) {
            items.push(current.trim());
        }
        return items.filter(function (item) {
            return item !== '';
        });
    }

    function guardPolicyBodyHtml(text) {
        text = String(text || '').trim();
        if (!text) {
            return '';
        }
        var items = guardPolicyNumberedItems(text);
        if (items.length < 2) {
            return guardPolicyEscapeHtml(text).replace(/\n/g, '<br>');
        }
        return '<ul class="guard-policy-modal__list">' + items.map(function (item) {
            return '<li>' + guardPolicyEscapeHtml(item) + '</li>';
        }).join('') + '</ul>';
    }

    function guardPolicyRawFromSource(sourceId) {
        if (!sourceId) {
            return '';
        }
        var el = document.getElementById(sourceId);
        if (!el) {
            return '';
        }
        if (el.tagName === 'TEXTAREA') {
            return el.value || '';
        }
        return el.textContent || el.innerText || '';
    }

    function initPolicyModals(root) {
        root = root || document;
        var modal = guardPolicyModalElement();
        if (!modal) {
            return;
        }

        var titleEl = qs('[data-policy-modal-title]', modal);
        var scrollEl = qs('[data-policy-modal-scroll]', modal);
        var dialog = qs('.guard-policy-modal__dialog', modal);
        var lastTrigger = null;

        function openModal(btn) {
            var sourceId = btn.getAttribute('data-policy-source');
            if (!sourceId || !titleEl || !scrollEl) {
                return;
            }
            lastTrigger = btn;
            titleEl.textContent = btn.getAttribute('data-policy-title') || '';
            scrollEl.innerHTML = guardPolicyBodyHtml(guardPolicyRawFromSource(sourceId));
            scrollEl.scrollTop = 0;
            modal.classList.add('is-open');
            modal.removeAttribute('hidden');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('guard-policy-modal-open');
            requestAnimationFrame(function () {
                var closeBtn = qs('.guard-policy-modal__close', modal);
                if (closeBtn) {
                    closeBtn.focus();
                }
            });
        }

        function closeModal() {
            if (!modal.classList.contains('is-open')) {
                return;
            }
            modal.classList.remove('is-open');
            modal.setAttribute('hidden', '');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('guard-policy-modal-open');
            if (scrollEl) {
                scrollEl.innerHTML = '';
            }
            if (lastTrigger) {
                lastTrigger.focus();
                lastTrigger = null;
            }
        }

        if (!modal._guardPolicyModalBound) {
            modal._guardPolicyModalBound = true;

            qsa('[data-policy-modal-close]', modal).forEach(function (el) {
                el.addEventListener('click', function (e) {
                    e.preventDefault();
                    closeModal();
                });
            });

            if (!document._guardPolicyModalEscapeBound) {
                document._guardPolicyModalEscapeBound = true;
                document.addEventListener('keydown', function (e) {
                    var active = guardPolicyModalElement();
                    if (!active || !active.classList.contains('is-open')) {
                        return;
                    }
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        if (typeof active._guardPolicyCloseFn === 'function') {
                            active._guardPolicyCloseFn();
                        }
                    }
                });
            }

            if (dialog) {
                dialog.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
            }
        }

        modal._guardPolicyCloseFn = closeModal;

        qsa('[data-policy-trigger]', root).forEach(function (btn) {
            if (btn._guardPolicyTriggerBound) {
                btn.removeEventListener('click', btn._guardPolicyTriggerHandler);
            }
            btn._guardPolicyTriggerHandler = function () {
                openModal(btn);
            };
            btn._guardPolicyTriggerBound = true;
            btn.addEventListener('click', btn._guardPolicyTriggerHandler);
        });
    }

    /* Report wizard */
    function initReportWizard(form) {
        if (!form || form._guardWizardBound) return;
        form._guardWizardBound = true;

        var steps = qsa('[data-wizard-step]', form);
        var panes = qsa('[data-wizard-pane]', form);
        var current = 1;
        var reportFile = null;
        var evidences = [];
        var DAD_TYPE = 'Daily Attendance Document';
        var INCIDENT_TYPE = 'Post incident';
        var DAILY_ACTIVITY_TYPE = 'Daily Activity';
        var DAILY_ACTIVITY_MAX_PHOTOS = 5;
        var ocrPreview = qs('[data-guard-ocr-preview]', form);
        var ocrAsIs = qs('[data-guard-ocr-as-is]', form);
        var ocrStatus = qs('[data-guard-ocr-status]', form);
        var ocrText = qs('[data-guard-ocr-text]', form);
        var dadSheetPreview = qs('[data-guard-dad-sheet-preview]', form);
        var dadSheetImg = qs('[data-guard-dad-sheet-img]', form);
        var sheetLocPanel = qs('[data-guard-sheet-location]', form);
        var sheetLocStatus = qs('[data-guard-sheet-location-status]', form);
        var sheetLocCoords = qs('[data-guard-sheet-location-coords]', form);
        var sheetLocAddress = qs('[data-guard-sheet-location-address]', form);
        var sheetLatInput = qs('[data-guard-sheet-lat-input]', form);
        var sheetLngInput = qs('[data-guard-sheet-lng-input]', form);
        var sheetAccInput = qs('[data-guard-sheet-acc-input]', form);
        var sheetLabelInput = qs('[data-guard-sheet-location-label-input]', form);
        var evidenceLocStatus = qs('[data-guard-evidence-location-status]', form);
        var evidenceLocCoords = qs('[data-guard-evidence-location-coords]', form);
        var evidenceLocAddress = qs('[data-guard-evidence-location-address]', form);
        var evidenceLatInput = qs('[data-guard-evidence-lat-input]', form);
        var evidenceLngInput = qs('[data-guard-evidence-lng-input]', form);
        var evidenceAccInput = qs('[data-guard-evidence-acc-input]', form);
        var evidenceLabelInput = qs('[data-guard-evidence-location-label-input]', form);
        var step2Label = qs('[data-guard-step2-label]', form);
        var step2Title = qs('[data-guard-step2-title]', form);
        var step2Hint = qs('[data-guard-step2-hint]', form);
        var step1Next = qs('[data-guard-step1-next]', form);
        var scanFlow = qs('[data-guard-scan-flow]', form);
        var dailyPanel = qs('[data-guard-daily-activity]', form);
        var dailyDetailsInput = qs('[data-guard-daily-details-input]', form);
        var dailySubmitNormal = qs('[data-guard-daily-submit-normal]', form);
        var dailyOpenEvent = qs('[data-guard-daily-open-event]', form);
        var dailySubmitEvent = qs('[data-guard-daily-submit-event]', form);
        var dailyEventReady = qs('[data-guard-daily-event-ready]', form);
        var dailyModal = qs('[data-guard-daily-activity-modal]', document);
        var dailyModalDetails = qs('[data-guard-daily-activity-details]', document);
        var dailyModalPhotos = qs('[data-guard-daily-activity-photos]', document);
        var dailyModalPreview = qs('[data-guard-daily-activity-photo-preview]', document);
        var dailyModalPhotoError = qs('[data-guard-daily-activity-photo-error]', document);
        var wizardStepsBar = qs('.guard-wizard__steps', form);
        var submitSubtitle = qs('[data-guard-submit-subtitle]', form.closest('.guard-section-stack') || document);
        var dailyActivityEvent = null;
        var dailyActivityPhotos = [];
        var dailyActivitySubmitting = false;
        var sheetLocationFix = null;
        var evidenceLocationFix = null;
        var ocrDone = false;
        var ocrBusy = false;

        function isDadMode() {
            var sel = qs('[name="report_type"]', form);
            return sel && String(sel.value || '').trim() === DAD_TYPE;
        }

        function isIncidentMode() {
            var sel = qs('[name="report_type"]', form);
            return sel && String(sel.value || '').trim() === INCIDENT_TYPE;
        }

        function isDailyActivityMode() {
            var sel = qs('[name="report_type"]', form);
            return sel && String(sel.value || '').trim() === DAILY_ACTIVITY_TYPE;
        }

        function getDailyActivityMode() {
            var checked = qs('[name="daily_activity_mode"]:checked', form);
            return checked ? String(checked.value || '').trim() : '';
        }

        function usesOcrPreview() {
            return isDadMode() || isIncidentMode();
        }

        function escapeOcrHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function renderOcrPreview(data) {
            var structured = data && data.structured && typeof data.structured === 'object' ? data.structured : {};
            if (isIncidentMode() && structured.template === 'incident_report') {
                if (ocrText) {
                    ocrText.hidden = true;
                    ocrText.textContent = '';
                }
                if (ocrAsIs) {
                    ocrAsIs.hidden = false;
                    var desc = escapeOcrHtml(structured.incident_description || '').replace(/\n/g, '<br>');
                    var action = escapeOcrHtml(structured.action_taken || '').replace(/\n/g, '<br>');
                    var meta = '';
                    if (structured.name) {
                        meta += '<p class="form-hint" style="margin:0 0 8px;">Subject: ' + escapeOcrHtml(structured.name) + '</p>';
                    }
                    if (structured.date) {
                        meta += '<p class="form-hint" style="margin:0 0 8px;">Date: ' + escapeOcrHtml(structured.date) + '</p>';
                    }
                    ocrAsIs.innerHTML =
                        meta +
                        '<div class="guard-ocr-preview__col"><span class="guard-ocr-preview__col-label">Incident description</span>' +
                        '<div class="guard-ocr-preview__col-body">' +
                        (desc || '—') +
                        '</div></div>' +
                        '<div class="guard-ocr-preview__col"><span class="guard-ocr-preview__col-label">Action taken</span>' +
                        '<div class="guard-ocr-preview__col-body">' +
                        (action || '—') +
                        '</div></div>';
                }
                return;
            }
            if (ocrAsIs) {
                ocrAsIs.hidden = true;
                ocrAsIs.innerHTML = '';
            }
            if (ocrText) {
                ocrText.hidden = false;
                ocrText.textContent = (data && (data.formatted || data.raw)) || '(no text detected)';
            }
        }

        function syncDadUi() {
            var dad = isDadMode();
            var incident = isIncidentMode();
            if (step2Label) step2Label.textContent = dad ? 'Site photos' : 'Evidences';
            if (step2Title) step2Title.textContent = dad ? 'Step 2 — Site photos & location' : 'Step 2 — Insert evidences';
            if (step2Hint) {
                step2Hint.textContent = dad
                    ? 'Add on-site photos. Step 1 stamped the sheet location; step 2 stamps evidence location (both sent to admin DAD).'
                    : 'Photos are tagged with device date/time and GPS when available.';
            }
            if (sheetLocPanel) sheetLocPanel.hidden = !dad;
            if (step1Next) {
                step1Next.innerHTML = (dad ? 'Continue to site photos' : 'Continue to evidences')
                    + ' <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>';
            }
            if (submitSubtitle) {
                submitSubtitle.textContent = dad
                    ? 'Daily attendance: GPS is stamped when you scan the sheet (step 1) and again at the site with photos (step 2). Document AI reads handwriting on the sheet.'
                    : incident
                      ? 'Post incident: scan the filled form. Document AI shows handwritten incident description (left) and action taken (right), without printed template text.'
                      : 'Scan your filled report, add evidence photos, then submit. Document AI reads the form on submit; evidence files are stored encrypted.';
            }
            if (!usesOcrPreview() && ocrPreview) {
                ocrPreview.hidden = true;
            }
            syncDailyActivityUi();
        }

        function clearDailyActivityPhotos() {
            dailyActivityPhotos.forEach(function (item) {
                if (item.url) URL.revokeObjectURL(item.url);
            });
            dailyActivityPhotos = [];
            renderDailyActivityPhotoPreview();
        }

        function renderDailyActivityPhotoPreview() {
            if (!dailyModalPreview) return;
            dailyModalPreview.innerHTML = '';
            dailyActivityPhotos.forEach(function (item, idx) {
                var cell = document.createElement('div');
                cell.className = 'guard-evidence-grid__item';
                var img = document.createElement('img');
                img.src = item.url;
                img.alt = 'Activity photo ' + (idx + 1);
                var rm = document.createElement('button');
                rm.type = 'button';
                rm.className = 'guard-evidence-grid__remove';
                rm.setAttribute('aria-label', 'Remove photo');
                rm.innerHTML = '<i class="fa-solid fa-xmark" aria-hidden="true"></i>';
                rm.addEventListener('click', function () {
                    URL.revokeObjectURL(item.url);
                    dailyActivityPhotos.splice(idx, 1);
                    renderDailyActivityPhotoPreview();
                });
                cell.appendChild(img);
                cell.appendChild(rm);
                dailyModalPreview.appendChild(cell);
            });
        }

        function syncDailyActivityUi() {
            var da = isDailyActivityMode();
            form.classList.toggle('is-daily-activity', da);
            if (dailyPanel) dailyPanel.hidden = !da;
            if (scanFlow) scanFlow.hidden = da;
            if (wizardStepsBar) wizardStepsBar.hidden = da;
            qsa('[data-wizard-pane="2"], [data-wizard-pane="3"]', form).forEach(function (pane) {
                if (da) pane.classList.remove('is-active');
            });
            if (da) {
                goStep(1);
                stopCamera();
            }
            var mode = getDailyActivityMode();
            if (dailySubmitNormal) dailySubmitNormal.hidden = !da || mode !== 'normal';
            if (dailyOpenEvent) dailyOpenEvent.hidden = !da || mode !== 'event';
            if (dailySubmitEvent) dailySubmitEvent.hidden = !da || mode !== 'event' || !dailyActivityEvent;
            if (dailyEventReady) dailyEventReady.hidden = !da || mode !== 'event' || !dailyActivityEvent;
            if (submitSubtitle) {
                if (da) {
                    submitSubtitle.textContent = 'Daily activity: choose normal operation for a quick log, or add event details with photos when something happened at your post.';
                }
            }
        }

        function openDailyActivityModal() {
            if (!dailyModal) return;
            if (dailyModalDetails && dailyActivityEvent) {
                dailyModalDetails.value = dailyActivityEvent.details || '';
            }
            if (dailyModalPhotoError) dailyModalPhotoError.hidden = true;
            dailyModal.hidden = false;
            dailyModal.classList.add('is-open');
            document.body.classList.add('guard-daily-activity-modal-open');
            if (dailyModalDetails) dailyModalDetails.focus();
        }

        function closeDailyActivityModal() {
            if (!dailyModal) return;
            dailyModal.classList.remove('is-open');
            dailyModal.hidden = true;
            document.body.classList.remove('guard-daily-activity-modal-open');
        }

        function saveDailyActivityModal() {
            var details = dailyModalDetails ? String(dailyModalDetails.value || '').trim() : '';
            if (details === '') {
                window.guardShowToast('Enter activity details before continuing.', 'error');
                if (dailyModalDetails) dailyModalDetails.focus();
                return;
            }
            if (dailyActivityPhotos.length < 1) {
                if (dailyModalPhotoError) {
                    dailyModalPhotoError.hidden = false;
                    dailyModalPhotoError.textContent = 'Add at least one photo (up to 5).';
                }
                window.guardShowToast('Add at least one supporting photo.', 'error');
                return;
            }
            if (dailyActivityPhotos.length > DAILY_ACTIVITY_MAX_PHOTOS) {
                window.guardShowToast('You can upload at most 5 photos.', 'error');
                return;
            }
            if (dailyModalPhotoError) dailyModalPhotoError.hidden = true;
            dailyActivityEvent = {
                details: details,
                files: dailyActivityPhotos.map(function (item) { return item.file; })
            };
            if (dailyDetailsInput) dailyDetailsInput.value = details;
            closeDailyActivityModal();
            syncDailyActivityUi();
            window.guardShowToast('Event details saved. Submit when ready.', 'success');
        }

        function resetDailyActivityState() {
            dailyActivityEvent = null;
            if (dailyDetailsInput) dailyDetailsInput.value = '';
            if (dailyModalDetails) dailyModalDetails.value = '';
            clearDailyActivityPhotos();
            qsa('[name="daily_activity_mode"]', form).forEach(function (radio) {
                radio.checked = false;
            });
            closeDailyActivityModal();
            syncDailyActivityUi();
        }

        function submitDailyActivity() {
            if (dailyActivitySubmitting) return;
            var reportType = DAILY_ACTIVITY_TYPE;
            var mode = getDailyActivityMode();
            if (!reportType || !isDailyActivityMode()) {
                window.guardShowToast('Select Daily Activity as the report type.', 'error');
                return;
            }
            if (!mode) {
                window.guardShowToast('Choose Normal Operation or With Event / Activity.', 'error');
                return;
            }
            if (mode === 'event') {
                if (!dailyActivityEvent || !dailyActivityEvent.details || !dailyActivityEvent.files.length) {
                    window.guardShowToast('Add event details and at least one photo.', 'error');
                    openDailyActivityModal();
                    return;
                }
                if (dailyActivityEvent.files.length > DAILY_ACTIVITY_MAX_PHOTOS) {
                    window.guardShowToast('You can upload at most 5 photos.', 'error');
                    return;
                }
            }

            dailyActivitySubmitting = true;
            var activeBtn = mode === 'normal' ? dailySubmitNormal : dailySubmitEvent;
            if (activeBtn) {
                activeBtn.classList.add('is-loading');
                activeBtn.disabled = true;
            }

            function finishSubmit(geo) {
                var fd = new FormData();
                fd.append('report_type', reportType);
                fd.append('daily_activity_mode', mode);
                if (mode === 'event' && dailyActivityEvent) {
                    fd.append('daily_activity_details', dailyActivityEvent.details);
                    dailyActivityEvent.files.forEach(function (file, i) {
                        fd.append('daily_activity_photos[]', file, file.name || ('activity-' + i + '.jpg'));
                    });
                }
                if (geo && geo.lat != null && geo.lng != null) {
                    fd.append('submit_latitude', String(geo.lat));
                    fd.append('submit_longitude', String(geo.lng));
                    if (geo.accuracy != null) fd.append('submit_accuracy_m', String(geo.accuracy));
                    if (geo.label) fd.append('location_label', geo.label);
                }
                var csrfInput = qs('input[name="_csrf"]', form);
                var csrfValue = csrfInput ? csrfInput.value : '';
                if (csrfValue) fd.append('_csrf', csrfValue);

                var fetchHeaders = { 'X-Requested-With': 'XMLHttpRequest' };
                if (csrfValue) fetchHeaders['X-CSRF-Token'] = csrfValue;

                fetch(form.getAttribute('action') || 'api/report-submit.php', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin',
                    headers: fetchHeaders
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.ok) {
                            window.guardShowToast(data.message || 'Report submitted successfully.', 'success');
                            resetDailyActivityState();
                            form.reset();
                            if (reportTypeSelect) reportTypeSelect.value = '';
                            syncReportTypeSummary();
                            syncDadUi();
                            setTimeout(function () {
                                window.location.href = data.redirect || 'submit-report.php?view=history';
                            }, 1200);
                        } else {
                            window.guardShowToast(data.error || 'Submission failed.', 'error');
                        }
                    })
                    .catch(function () {
                        window.guardShowToast('Network error. Try again.', 'error');
                    })
                    .finally(function () {
                        dailyActivitySubmitting = false;
                        if (activeBtn) {
                            activeBtn.classList.remove('is-loading');
                            activeBtn.disabled = false;
                        }
                    });
            }

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function (pos) {
                        finishSubmit({
                            lat: pos.coords.latitude,
                            lng: pos.coords.longitude,
                            accuracy: pos.coords.accuracy,
                            label: ''
                        });
                    },
                    function () { finishSubmit(null); },
                    { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
                );
            } else {
                finishSubmit(null);
            }
        }

        function runOcrPreview() {
            if (isDailyActivityMode() || !usesOcrPreview() || !reportFile || ocrBusy) {
                return;
            }
            ocrBusy = true;
            ocrDone = false;
            if (ocrPreview) ocrPreview.hidden = false;
            if (ocrStatus) {
                ocrStatus.textContent = isIncidentMode()
                    ? 'Reading handwritten incident form with Document AI…'
                    : 'Reading handwritten attendance sheet with Document AI…';
            }
            if (ocrText) ocrText.textContent = '';
            if (ocrAsIs) {
                ocrAsIs.hidden = true;
                ocrAsIs.innerHTML = '';
            }

            var fd = new FormData();
            fd.append('report_type', isIncidentMode() ? INCIDENT_TYPE : DAD_TYPE);
            fd.append('report_scan', reportFile, reportFile.name || 'scan.jpg');
            var csrfInput = qs('input[name="_csrf"]', form);
            if (csrfInput && csrfInput.value) fd.append('_csrf', csrfInput.value);

            var headers = { 'X-Requested-With': 'XMLHttpRequest' };
            if (csrfInput && csrfInput.value) headers['X-CSRF-Token'] = csrfInput.value;

            fetch('api/report-ocr-preview.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: headers
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    ocrBusy = false;
                    if (!data.ok) {
                        if (ocrStatus) ocrStatus.textContent = data.error || 'OCR preview failed.';
                        return;
                    }
                    ocrDone = true;
                    if (ocrStatus) ocrStatus.textContent = 'Handwriting detected — review below before continuing.';
                    renderOcrPreview(data);
                })
                .catch(function () {
                    ocrBusy = false;
                    if (ocrStatus) ocrStatus.textContent = 'Could not reach Document AI. You may still submit; OCR runs again on save.';
                });
        }

        function updateLocationUi(kind, lat, lng, accuracy, label, statusText) {
            var coordsEl = kind === 'sheet' ? sheetLocCoords : evidenceLocCoords;
            var addressEl = kind === 'sheet' ? sheetLocAddress : evidenceLocAddress;
            var statusEl = kind === 'sheet' ? sheetLocStatus : evidenceLocStatus;
            var latIn = kind === 'sheet' ? sheetLatInput : evidenceLatInput;
            var lngIn = kind === 'sheet' ? sheetLngInput : evidenceLngInput;
            var accIn = kind === 'sheet' ? sheetAccInput : evidenceAccInput;
            var labelIn = kind === 'sheet' ? sheetLabelInput : evidenceLabelInput;

            if (latIn) latIn.value = lat != null ? String(lat) : '';
            if (lngIn) lngIn.value = lng != null ? String(lng) : '';
            if (accIn) accIn.value = accuracy != null ? String(accuracy) : '';
            if (labelIn) labelIn.value = label || '';
            if (coordsEl && lat != null && lng != null) {
                var accTxt = accuracy != null ? ' (±' + Math.round(accuracy) + ' m)' : '';
                coordsEl.textContent = lat.toFixed(6) + ', ' + lng.toFixed(6) + accTxt;
                coordsEl.hidden = false;
            }
            if (addressEl && label) {
                addressEl.textContent = label;
                addressEl.hidden = false;
            }
            if (statusEl && statusText) statusEl.textContent = statusText;
        }

        function reverseGeocode(lat, lng) {
            return fetch('api/geocode.php?lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng), {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.ok && data.label) return String(data.label);
                    return 'Coordinates ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
                })
                .catch(function () {
                    return 'Coordinates ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
                });
        }

        function stampLocation(kind) {
            if (!navigator.geolocation) {
                var statusEl = kind === 'sheet' ? sheetLocStatus : evidenceLocStatus;
                if (statusEl) statusEl.textContent = 'GPS not supported on this device.';
                return;
            }
            var statusEl = kind === 'sheet' ? sheetLocStatus : evidenceLocStatus;
            var pending = kind === 'sheet'
                ? 'Stamping sheet location from this device…'
                : 'Stamping evidence location at the site…';
            if (statusEl) statusEl.textContent = pending;

            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    var lat = pos.coords.latitude;
                    var lng = pos.coords.longitude;
                    var acc = pos.coords.accuracy;
                    var fix = { lat: lat, lng: lng, accuracy: acc, label: '' };
                    if (kind === 'sheet') {
                        sheetLocationFix = fix;
                    } else {
                        evidenceLocationFix = fix;
                    }
                    updateLocationUi(kind, lat, lng, acc, '', 'Resolving address…');
                    reverseGeocode(lat, lng).then(function (address) {
                        fix.label = address;
                        if (kind === 'sheet') sheetLocationFix = fix;
                        else evidenceLocationFix = fix;
                        updateLocationUi(
                            kind,
                            lat,
                            lng,
                            acc,
                            address,
                            kind === 'sheet'
                                ? 'Sheet location stamped — continue when ready.'
                                : 'Evidence location stamped — add photos or continue.'
                        );
                    });
                },
                function (err) {
                    if (statusEl) {
                        statusEl.textContent = err.code === 1
                            ? 'Location denied. Enable GPS in browser settings.'
                            : 'Could not acquire GPS. Move outdoors and retry.';
                    }
                },
                { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 }
            );
        }

        function syncDadSheetPreview() {
            if (!dadSheetPreview || !dadSheetImg || !reportFile) return;
            if (isDadMode()) {
                dadSheetImg.src = URL.createObjectURL(reportFile);
                dadSheetPreview.hidden = false;
            } else {
                dadSheetPreview.hidden = true;
                dadSheetImg.removeAttribute('src');
            }
        }

        var scanner = qs('[data-guard-scanner]', form);
        var video = qs('[data-guard-scanner-video]', form);
        var preview = qs('[data-guard-scanner-preview]', form);
        var hint = qs('[data-guard-scanner-hint]', form);
        var torchBtn = qs('[data-guard-scanner-torch]', form);
        var stream = null;
        var torchOn = false;
        var torchSupported = false;

        function getVideoTrack() {
            if (!stream) {
                return null;
            }
            var tracks = stream.getVideoTracks();
            return tracks.length ? tracks[0] : null;
        }

        function syncTorchButton() {
            if (!torchBtn) {
                return;
            }
            var show = torchSupported && stream && scanner
                && scanner.classList.contains('is-live')
                && !scanner.classList.contains('has-capture');
            torchBtn.hidden = !show;
            torchBtn.disabled = !show;
            torchBtn.classList.toggle('is-on', torchOn);
            torchBtn.setAttribute('aria-pressed', torchOn ? 'true' : 'false');
            torchBtn.setAttribute('aria-label', torchOn ? 'Turn flashlight off' : 'Turn flashlight on');
        }

        function detectTorchSupport(track) {
            if (!track || typeof track.getCapabilities !== 'function') {
                return false;
            }
            var caps = track.getCapabilities();
            return caps && caps.torch === true;
        }

        function setTorch(enabled) {
            var track = getVideoTrack();
            if (!track || !torchSupported) {
                return Promise.resolve(false);
            }
            var constraints = { advanced: [{ torch: enabled }] };
            return track.applyConstraints(constraints).then(function () {
                torchOn = enabled;
                syncTorchButton();
                return true;
            }).catch(function () {
                return track.applyConstraints({ torch: enabled }).then(function () {
                    torchOn = enabled;
                    syncTorchButton();
                    return true;
                }).catch(function () {
                    return false;
                });
            });
        }

        function goStep(n) {
            current = n;
            steps.forEach(function (s) {
                var num = parseInt(s.getAttribute('data-wizard-step'), 10);
                s.classList.toggle('is-active', num === n);
                s.classList.toggle('is-done', num < n);
            });
            panes.forEach(function (p) {
                p.classList.toggle('is-active', parseInt(p.getAttribute('data-wizard-pane'), 10) === n);
            });
            if (n === 2 && isDadMode()) {
                syncDadSheetPreview();
                stampLocation('evidence');
            }
        }

        function isMobileScanner() {
            return window.matchMedia('(max-width: 639px)').matches;
        }

        function getCameraVideoConstraints() {
            if (isMobileScanner()) {
                return {
                    facingMode: 'environment',
                    width: { ideal: 1080 },
                    height: { ideal: 1920 },
                    aspectRatio: { ideal: 9 / 16 }
                };
            }
            return { facingMode: 'environment' };
        }

        function isScannerFullscreen() {
            return document.body.classList.contains('guard-scan-fullscreen');
        }

        function setScannerFullscreen(on) {
            document.body.classList.toggle('guard-scan-fullscreen', !!on);
        }

        function layoutScannerFromVideo() {
            if (!scanner) {
                return;
            }
            if (scanner.classList.contains('is-live') && isScannerFullscreen()) {
                scanner.style.aspectRatio = '';
                return;
            }
            if (preview && scanner.classList.contains('has-capture') && preview.naturalWidth && preview.naturalHeight) {
                scanner.style.aspectRatio = preview.naturalWidth + ' / ' + preview.naturalHeight;
                return;
            }
            if (video && video.videoWidth && video.videoHeight) {
                scanner.style.aspectRatio = video.videoWidth + ' / ' + video.videoHeight;
            }
        }

        function bindScannerLayoutEvents() {
            if (!video || video._guardLayoutBound) {
                return;
            }
            video._guardLayoutBound = true;
            video.addEventListener('loadedmetadata', layoutScannerFromVideo);
            if (preview) {
                preview.addEventListener('load', layoutScannerFromVideo);
            }
            window.addEventListener('resize', layoutScannerFromVideo);
            if (window.visualViewport) {
                window.visualViewport.addEventListener('resize', layoutScannerFromVideo);
            }
        }

        function stopCamera() {
            torchOn = false;
            torchSupported = false;
            syncTorchButton();
            if (stream) {
                stream.getTracks().forEach(function (t) { t.stop(); });
                stream = null;
            }
            if (scanner) {
                scanner.style.aspectRatio = '';
            }
            setScannerFullscreen(false);
        }

        function startCamera() {
            bindScannerLayoutEvents();
            if (!navigator.mediaDevices || !video) {
                return Promise.reject(new Error('no-camera'));
            }
            stopCamera();
            return navigator.mediaDevices.getUserMedia({
                video: getCameraVideoConstraints(),
                audio: false
            })
                .then(function (s) {
                    stream = s;
                    video.srcObject = s;
                    video.playsInline = true;
                    var track = getVideoTrack();
                    torchSupported = detectTorchSupport(track);
                    torchOn = false;
                    syncTorchButton();
                    return video.play();
                })
                .then(function () {
                    layoutScannerFromVideo();
                })
                .catch(function () {
                    torchSupported = false;
                    syncTorchButton();
                    if (hint) hint.textContent = 'Camera unavailable — use upload instead.';
                    throw new Error('camera-failed');
                });
        }

        function runCaptureCountdown() {
            if (!scanner || !video) {
                return;
            }
            if (!video.videoWidth) {
                window.guardShowToast('Camera not ready. Try again.', 'error');
                return;
            }
            scanner.classList.add('is-capturing');
            var count = 3;
            if (hint) hint.textContent = 'Align document inside frame…';
            var tick = setInterval(function () {
                if (hint) hint.textContent = 'Capturing in ' + count + 's…';
                count--;
                if (count < 0) {
                    clearInterval(tick);
                    scanner.classList.remove('is-capturing');
                    var canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0);
                    canvas.toBlob(function (blob) {
                        if (!blob) return;
                        reportFile = new File([blob], 'scan-' + Date.now() + '.jpg', { type: 'image/jpeg' });
                        if (preview) {
                            preview.src = URL.createObjectURL(blob);
                            preview.onload = layoutScannerFromVideo;
                            layoutScannerFromVideo();
                        }
                        scanner.classList.remove('is-live');
                        setScannerFullscreen(false);
                        scanner.classList.add('has-capture');
                        if (hint) hint.textContent = isDadMode() ? 'Sheet captured. Reading handwriting…' : 'Report captured. Continue to evidences.';
                        syncTorchButton();
                        stopCamera();
                        runOcrPreview();
                        syncDadSheetPreview();
                        if (isDadMode()) stampLocation('sheet');
                    }, 'image/jpeg', 0.88);
                }
            }, 700);
        }

        function openSmartScan() {
            if (!scanner) {
                return;
            }
            scanner.classList.remove('has-capture');
            scanner.classList.add('is-live');
            setScannerFullscreen(true);
            if (hint) hint.textContent = 'Starting camera…';
            if (stream && video.videoWidth) {
                runCaptureCountdown();
                return;
            }
            startCamera()
                .then(function () {
                    runCaptureCountdown();
                })
                .catch(function () {
                    scanner.classList.remove('is-live');
                    setScannerFullscreen(false);
                    window.guardShowToast('Camera unavailable — use upload instead.', 'error');
                    if (hint) hint.textContent = 'Tap Smart scan to open the camera.';
                });
        }

        if (torchBtn) {
            torchBtn.addEventListener('click', function () {
                if (!torchSupported || !stream) {
                    window.guardShowToast('Flashlight is not available on this device.', 'error');
                    return;
                }
                setTorch(!torchOn).then(function (ok) {
                    if (!ok) {
                        window.guardShowToast('Could not toggle flashlight.', 'error');
                    }
                });
            });
        }

        var captureBtn = qs('[data-guard-scan-capture]', form);
        if (captureBtn && scanner) {
            captureBtn.addEventListener('click', function () {
                openSmartScan();
            });
        }

        var uploadReport = qs('[data-guard-report-upload]', form);
        if (uploadReport) {
            uploadReport.addEventListener('change', function () {
                var f = uploadReport.files && uploadReport.files[0];
                if (!f) return;
                reportFile = f;
                if (preview && scanner) {
                    preview.src = URL.createObjectURL(f);
                    scanner.classList.remove('is-live');
                    scanner.classList.add('has-capture');
                    preview.onload = layoutScannerFromVideo;
                    layoutScannerFromVideo();
                }
                if (hint) hint.textContent = 'File ready: ' + f.name;
                stopCamera();
                runOcrPreview();
                syncDadSheetPreview();
                if (isDadMode()) stampLocation('sheet');
            });
        }

        var retake = qs('[data-guard-scan-retake]', form);
        if (retake) {
            retake.addEventListener('click', function () {
                reportFile = null;
                if (scanner) {
                    scanner.classList.remove('has-capture', 'is-live', 'is-capturing');
                }
                setScannerFullscreen(false);
                if (preview) preview.removeAttribute('src');
                stopCamera();
                if (hint) hint.textContent = 'Tap Smart scan to open the camera.';
                ocrDone = false;
                sheetLocationFix = null;
                if (ocrPreview) ocrPreview.hidden = true;
                if (ocrText) ocrText.textContent = '';
                if (dadSheetPreview) dadSheetPreview.hidden = true;
                if (sheetLocPanel) sheetLocPanel.hidden = true;
                updateLocationUi('sheet', null, null, null, '', 'Stamped when you capture or upload the attendance sheet.');
            });
        }

        var evidenceInput = qs('[data-guard-evidence-input]', form);
        var evidenceGrid = qs('[data-guard-evidence-grid]', form);

        function renderEvidence() {
            if (!evidenceGrid) return;
            evidenceGrid.innerHTML = '';
            evidences.forEach(function (item, idx) {
                var card = document.createElement('div');
                card.className = 'guard-evidence-card';
                card.innerHTML = '<button type="button" class="guard-evidence-card__remove" aria-label="Remove"><i class="fa-solid fa-xmark"></i></button>' +
                    '<img alt="">' +
                    '<div class="guard-evidence-card__meta"></div>';
                card.querySelector('img').src = item.url;
                card.querySelector('.guard-evidence-card__meta').textContent = item.meta;
                card.querySelector('.guard-evidence-card__remove').addEventListener('click', function () {
                    URL.revokeObjectURL(item.url);
                    evidences.splice(idx, 1);
                    renderEvidence();
                });
                evidenceGrid.appendChild(card);
            });
        }

        function addEvidenceFile(file, gps) {
            var meta = new Date().toLocaleString();
            if (gps && gps.lat != null) {
                meta += ' · GPS ' + gps.lat.toFixed(6) + ', ' + gps.lng.toFixed(6);
                if (gps.accuracy != null) meta += ' ±' + Math.round(gps.accuracy) + 'm';
            } else {
                meta += ' · GPS unavailable';
            }
            if (evidenceLocationFix && evidenceLocationFix.label) {
                meta += ' · Evidence site: ' + evidenceLocationFix.label;
            } else if (sheetLocationFix && sheetLocationFix.label) {
                meta += ' · Sheet: ' + sheetLocationFix.label;
            }
            evidences.push({ file: file, url: URL.createObjectURL(file), meta: meta });
            renderEvidence();
        }

        if (evidenceInput) {
            evidenceInput.addEventListener('change', function () {
                var files = evidenceInput.files;
                if (!files || !files.length) return;
                var done = function (gps) {
                    Array.prototype.forEach.call(files, function (f) { addEvidenceFile(f, gps); });
                    evidenceInput.value = '';
                };
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function (pos) {
                            var gps = {
                                lat: pos.coords.latitude,
                                lng: pos.coords.longitude,
                                accuracy: pos.coords.accuracy
                            };
                            done(gps);
                        },
                        function () { done(null); },
                        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
                    );
                } else {
                    done(null);
                }
            });
        }

        var reportTypeSelect = qs('[name="report_type"]', form);
        var reportTypeSummary = qs('[data-guard-report-type-summary]', form);

        function selectedReportType() {
            return reportTypeSelect ? String(reportTypeSelect.value || '').trim() : '';
        }

        function syncReportTypeSummary() {
            if (reportTypeSummary) {
                reportTypeSummary.textContent = selectedReportType() || '—';
            }
        }

        if (reportTypeSelect) {
            reportTypeSelect.addEventListener('change', function () {
                if (!isDailyActivityMode()) {
                    resetDailyActivityState();
                } else {
                    dailyActivityEvent = null;
                    syncDailyActivityUi();
                }
                syncReportTypeSummary();
                syncDadUi();
            });
        }
        syncDadUi();

        qsa('[data-guard-daily-mode]', form).forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (radio.value === 'normal' && radio.checked) {
                    dailyActivityEvent = null;
                    if (dailyDetailsInput) dailyDetailsInput.value = '';
                    closeDailyActivityModal();
                }
                if (radio.value === 'event' && radio.checked && !dailyActivityEvent) {
                    openDailyActivityModal();
                }
                syncDailyActivityUi();
            });
        });

        if (dailyOpenEvent) {
            dailyOpenEvent.addEventListener('click', function () {
                openDailyActivityModal();
            });
        }
        if (dailySubmitNormal) {
            dailySubmitNormal.addEventListener('click', function () {
                submitDailyActivity();
            });
        }
        if (dailySubmitEvent) {
            dailySubmitEvent.addEventListener('click', function () {
                submitDailyActivity();
            });
        }

        qsa('[data-guard-daily-activity-modal-close]', document).forEach(function (btn) {
            btn.addEventListener('click', closeDailyActivityModal);
        });
        var dailyModalSave = qs('[data-guard-daily-activity-modal-save]', document);
        if (dailyModalSave) {
            dailyModalSave.addEventListener('click', saveDailyActivityModal);
        }
        if (dailyModalPhotos) {
            dailyModalPhotos.addEventListener('change', function () {
                var files = dailyModalPhotos.files;
                if (!files || !files.length) return;
                var remaining = DAILY_ACTIVITY_MAX_PHOTOS - dailyActivityPhotos.length;
                if (remaining <= 0) {
                    window.guardShowToast('Maximum of 5 photos reached.', 'error');
                    dailyModalPhotos.value = '';
                    return;
                }
                Array.prototype.forEach.call(files, function (file, i) {
                    if (i >= remaining) return;
                    if (!file.type || file.type.indexOf('image/') !== 0) return;
                    dailyActivityPhotos.push({
                        file: file,
                        url: URL.createObjectURL(file)
                    });
                });
                dailyModalPhotos.value = '';
                if (dailyModalPhotoError) dailyModalPhotoError.hidden = true;
                renderDailyActivityPhotoPreview();
                if (dailyActivityPhotos.length > DAILY_ACTIVITY_MAX_PHOTOS) {
                    while (dailyActivityPhotos.length > DAILY_ACTIVITY_MAX_PHOTOS) {
                        var removed = dailyActivityPhotos.pop();
                        if (removed && removed.url) URL.revokeObjectURL(removed.url);
                    }
                    window.guardShowToast('Only the first 5 photos were kept.', 'error');
                    renderDailyActivityPhotoPreview();
                }
            });
        }

        qsa('[data-wizard-next]', form).forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = parseInt(btn.getAttribute('data-wizard-next'), 10);
                if (current === 1) {
                    if (!selectedReportType()) {
                        window.guardShowToast('Select a report type first.', 'error');
                        if (reportTypeSelect) reportTypeSelect.focus();
                        return;
                    }
                    if (isDailyActivityMode()) {
                        window.guardShowToast('Use the Daily Activity buttons on this step to submit.', 'error');
                        return;
                    }
                    if (!reportFile) {
                        window.guardShowToast('Add a report scan or upload first.', 'error');
                        return;
                    }
                    if (isDadMode() && ocrBusy) {
                        window.guardShowToast('Document AI is still reading the sheet. Wait a moment.', 'error');
                        return;
                    }
                }
                if (target === 2 && isDadMode() && !sheetLocationFix) {
                    window.guardShowToast('Wait for the sheet location stamp (step 1) before continuing.', 'error');
                    return;
                }
                if (target === 3 && isDadMode() && !evidenceLocationFix) {
                    window.guardShowToast('Allow location access and wait for the evidence GPS stamp (step 2).', 'error');
                    return;
                }
                goStep(target);
                if (target !== 1) {
                    stopCamera();
                }
                if (target === 3) syncReportTypeSummary();
            });
        });

        qsa('[data-wizard-back]', form).forEach(function (btn) {
            btn.addEventListener('click', function () {
                goStep(parseInt(btn.getAttribute('data-wizard-back'), 10));
            });
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (isDailyActivityMode()) {
                submitDailyActivity();
                return;
            }
            var reportType = selectedReportType();
            if (!reportType) {
                window.guardShowToast('Select a report type first.', 'error');
                goStep(1);
                if (reportTypeSelect) reportTypeSelect.focus();
                return;
            }
            if (!reportFile) {
                window.guardShowToast('Report image is required.', 'error');
                return;
            }
            if (isDadMode()) {
                if (!sheetLocationFix) {
                    window.guardShowToast('Sheet location (step 1) is required. Capture or upload the attendance sheet again.', 'error');
                    goStep(1);
                    return;
                }
                if (!evidenceLocationFix) {
                    window.guardShowToast('Evidence location (step 2) is required. Return to step 2 and allow GPS.', 'error');
                    goStep(2);
                    return;
                }
            }
            var submitBtn = qs('[data-guard-submit]', form);
            if (submitBtn) {
                submitBtn.classList.add('is-loading');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Submitting…';
            }
            var fd = new FormData();
            fd.append('report_type', reportType);
            var scanName = reportFile.name || ('report-scan-' + Date.now() + '.jpg');
            fd.append('report_scan', reportFile, scanName);
            if (isDadMode() && sheetLocationFix) {
                fd.append('sheet_latitude', String(sheetLocationFix.lat));
                fd.append('sheet_longitude', String(sheetLocationFix.lng));
                if (sheetLocationFix.accuracy != null) fd.append('sheet_accuracy_m', String(sheetLocationFix.accuracy));
                if (sheetLocationFix.label) fd.append('sheet_location_label', sheetLocationFix.label);
            }
            if (isDadMode() && evidenceLocationFix) {
                fd.append('evidence_latitude', String(evidenceLocationFix.lat));
                fd.append('evidence_longitude', String(evidenceLocationFix.lng));
                if (evidenceLocationFix.accuracy != null) fd.append('evidence_accuracy_m', String(evidenceLocationFix.accuracy));
                if (evidenceLocationFix.label) fd.append('evidence_location_label', evidenceLocationFix.label);
            }
            evidences.forEach(function (ev, i) {
                var evName = ev.file.name || ('evidence-' + i + '.jpg');
                fd.append('evidence[]', ev.file, evName);
                fd.append('evidence_meta[]', ev.meta || '');
            });
            var csrfInput = qs('input[name="_csrf"]', form);
            var csrfValue = csrfInput ? csrfInput.value : '';
            if (csrfValue) {
                fd.append('_csrf', csrfValue);
            }

            var fetchHeaders = { 'X-Requested-With': 'XMLHttpRequest' };
            if (csrfValue) {
                fetchHeaders['X-CSRF-Token'] = csrfValue;
            }

            fetch(form.getAttribute('action') || 'api/report-submit.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: fetchHeaders
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.ok) {
                        window.guardShowToast(data.message || 'Report submitted successfully.', 'success');
                        reportFile = null;
                        evidences.forEach(function (ev) { URL.revokeObjectURL(ev.url); });
                        evidences = [];
                        renderEvidence();
                        form.reset();
                        if (scanner) {
                            scanner.classList.remove('has-capture', 'is-live', 'is-capturing');
                        }
                        setScannerFullscreen(false);
                        if (hint) hint.textContent = 'Tap Smart scan to open the camera.';
                        goStep(1);
                        setTimeout(function () {
                            window.location.href = data.redirect || 'submit-report.php?view=history';
                        }, 1200);
                    } else {
                        window.guardShowToast(data.error || 'Submission failed.', 'error');
                    }
                })
                .catch(function () {
                    window.guardShowToast('Network error. Try again.', 'error');
                })
                .finally(function () {
                    if (submitBtn) {
                        submitBtn.classList.remove('is-loading');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Submit report';
                    }
                });
        });

        bindScannerLayoutEvents();
        goStep(1);
    }

    function initCornerClickables(root) {
        qsa('[data-guard-corner-click]', root).forEach(function (btn) {
            if (btn._guardCornerClickBound) {
                return;
            }
            btn._guardCornerClickBound = true;
            btn.addEventListener('click', function () {
                var body = btn.querySelector('.guard-corner-click__body');
                if (!body) {
                    var wrap = btn.closest('.guard-report-list__wrap');
                    body = wrap ? qs('.guard-corner-click__body', wrap) : null;
                }
                if (!body) {
                    return;
                }
                var open = btn.getAttribute('aria-expanded') === 'true';
                btn.setAttribute('aria-expanded', open ? 'false' : 'true');
                btn.classList.toggle('is-open', !open);
                if (open) {
                    body.setAttribute('hidden', '');
                } else {
                    body.removeAttribute('hidden');
                }
            });
        });
    }

    function initCornerJump(root) {
        var page = qs('.guard-corner-page', root);
        if (!page) {
            return;
        }
        qsa('[data-guard-corner-jump]', page).forEach(function (tile) {
            if (tile._guardCornerJumpBound) {
                return;
            }
            tile._guardCornerJumpBound = true;
            tile.addEventListener('click', function () {
                var id = tile.getAttribute('data-guard-corner-jump');
                qsa('[data-guard-corner-jump]', page).forEach(function (t) {
                    t.classList.toggle('is-active', t.getAttribute('data-guard-corner-jump') === id);
                });
                var bar = qs('[data-guard-hub-tabs]', page);
                var tabBtn = bar ? qs('[data-guard-hub-tab="' + id + '"]', bar) : null;
                if (tabBtn) {
                    if (bar._guardHubActivateTab) {
                        bar._guardHubActivateTab(id, tabBtn);
                    } else {
                        tabBtn.click();
                    }
                }
            });
        });
        var bar = qs('[data-guard-hub-tabs]', page);
        if (bar && !bar._guardCornerSyncBound) {
            bar._guardCornerSyncBound = true;
            qsa('[data-guard-hub-tab]', bar).forEach(function (tabBtn) {
                tabBtn.addEventListener('click', function () {
                    var id = tabBtn.getAttribute('data-guard-hub-tab');
                    qsa('[data-guard-corner-jump]', page).forEach(function (t) {
                        t.classList.toggle('is-active', t.getAttribute('data-guard-corner-jump') === id);
                    });
                });
            });
        }
    }

    function syncCornerTabFromUrl(root) {
        var page = qs('.guard-corner-page', root);
        if (!page) {
            return;
        }
        var tab = '';
        try {
            tab = new URL(window.location.href).searchParams.get('tab') || '';
        } catch (e) {
            return;
        }
        if (!tab) {
            return;
        }
        if (tab === 'chat') {
            tab = 'announce';
        }
        var bar = qs('[data-guard-hub-tabs]', page);
        var tabBtn = bar ? qs('[data-guard-hub-tab="' + tab + '"]', bar) : null;
        if (!tabBtn || tabBtn.classList.contains('is-active')) {
            return;
        }
        qsa('[data-guard-corner-jump]', page).forEach(function (t) {
            t.classList.toggle('is-active', t.getAttribute('data-guard-corner-jump') === tab);
        });
        if (bar && bar._guardHubActivateTab) {
            bar._guardHubActivateTab(tab, tabBtn);
        } else {
            tabBtn.click();
        }
    }

    function applySubmitReportView(root, open) {
        var card = qs('[data-guard-submit-card]', root);
        var toggle = qs('[data-guard-report-history-toggle]', root);
        var history = qs('[data-guard-report-history]', root);
        var wizard = qs('[data-guard-report-wizard]', root);
        var heading = qs('[data-guard-submit-card-heading]', root);
        if (!card || !toggle || !history || !wizard) {
            return;
        }
        card.classList.toggle('is-history-open', open);
        history.hidden = !open;
        wizard.hidden = open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.textContent = open ? 'Back to submission' : 'Report history';
        if (heading) {
            heading.textContent = open ? 'Report history' : 'Report submission';
        }
    }

    function initReportHistoryToggle(root) {
        var card = qs('[data-guard-submit-card]', root);
        var toggle = qs('[data-guard-report-history-toggle]', root);
        if (!card || !toggle) {
            return;
        }
        if (toggle._guardHistoryBound) {
            return;
        }
        toggle._guardHistoryBound = true;

        toggle.addEventListener('click', function () {
            var open = !card.classList.contains('is-history-open');
            applySubmitReportView(root, open);
            try {
                var url = new URL(window.location.href);
                if (open) {
                    url.searchParams.set('view', 'history');
                } else {
                    url.searchParams.delete('view');
                }
                history.replaceState({ panelNav: true, url: url.href }, '', url.href);
                persistGuardLocation(url.href);
            } catch (e) {
                /* ignore */
            }
        });
    }

    function syncSubmitReportViewFromUrl(root) {
        if (!qs('[data-guard-submit-card]', root)) {
            return;
        }
        var view = '';
        try {
            view = new URL(window.location.href).searchParams.get('view') || '';
        } catch (e) {
            return;
        }
        applySubmitReportView(root, view === 'history');
    }

    function syncInboxTabFromUrl(root) {
        var tab = '';
        try {
            tab = new URL(window.location.href).searchParams.get('tab') || '';
        } catch (e) {
            return;
        }
        if (tab !== 'reports' && tab !== 'memos') {
            return;
        }
        var bar = qs('[data-guard-hub-tabs]', root);
        if (!bar) {
            return;
        }
        var tabBtn = qs('[data-guard-hub-tab="' + tab + '"]', bar);
        if (!tabBtn || tabBtn.classList.contains('is-active')) {
            return;
        }
        if (bar._guardHubActivateTab) {
            bar._guardHubActivateTab(tab, tabBtn);
        } else {
            tabBtn.click();
        }
    }

    function flattenGuardStage() {
        var main = document.querySelector('.app-main');
        if (!main) {
            return;
        }
        var targetStage = main.querySelector('[data-guard-panel-root]') || main.querySelector('.guard-app__scroll');
        if (!targetStage) {
            return;
        }
        var nested = targetStage.querySelector(':scope > [data-guard-panel-root], :scope > .guard-app__scroll, :scope > .app-main__stage');
        while (nested) {
            targetStage.className = nested.className;
            if (nested.hasAttribute('data-guard-panel-root')) {
                targetStage.setAttribute('data-guard-panel-root', '');
            }
            targetStage.innerHTML = nested.innerHTML;
            nested = targetStage.querySelector(':scope > [data-guard-panel-root], :scope > .guard-app__scroll, :scope > .app-main__stage');
        }
    }

    function initGuardPortal(root) {
        flattenGuardStage();
        var stage = document.querySelector('[data-guard-panel-root]') || document.querySelector('.guard-app__scroll');
        root = root || stage || document;
        initHubTabs(root);
        var cornerPage = qs('.guard-corner-page', root);
        if (cornerPage && qs('[data-guard-hub-panel="social"].is-active', cornerPage)) {
            initGuardLiveFeeds(cornerPage);
        }
        initCornerJump(root);
        initCornerClickables(root);
        initPolicyModals(root);
        syncCornerTabFromUrl(root);
        syncInboxTabFromUrl(root);
        syncSubmitReportViewFromUrl(root);
        initReportHistoryToggle(root);
        var wizard = qs('[data-guard-report-wizard]', root);
        if (wizard) {
            initReportWizard(wizard);
        }

        if (typeof window.initMessagingBoard === 'function' && document.getElementById('messaging-board')) {
            window.initMessagingBoard();
        }
    }

    window.addEventListener('beforeunload', function () {
        persistGuardLocation();
    });

    document.addEventListener('DOMContentLoaded', function () {
        if (!document.body.classList.contains('guard-portal')) {
            return;
        }
        guardRestoreOnReload();
        persistGuardLocation();
        initGuardPortal(document.querySelector('[data-guard-panel-root]') || document.querySelector('.guard-app__scroll'));
    });

    window.guardInitPortal = initGuardPortal;
})();
</script>
    <?php
}
