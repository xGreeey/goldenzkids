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
                if (p.classList.contains('is-active')) {
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

                function showNext() {
                    buttons.forEach(function (b) {
                        var on = b === btn || (btn && b.getAttribute('data-guard-hub-tab') === id);
                        b.classList.toggle('is-active', on);
                        b.setAttribute('aria-selected', on ? 'true' : 'false');
                    });
                    panels.forEach(function (p) {
                        var on = p.getAttribute('data-guard-hub-panel') === id;
                        p.classList.toggle('is-active', on);
                        if (!on) {
                            p.classList.remove('is-leaving');
                        }
                    });
                    activeId = id;
                    updateHubTabUrl(bar, id);
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

    /* Accordion */
    function initAccordion(root) {
        qsa('.guard-accordion__trigger', root).forEach(function (btn) {
            if (btn._guardAccordionBound) {
                return;
            }
            btn._guardAccordionBound = true;
            btn.addEventListener('click', function () {
                var item = btn.closest('.guard-accordion__item');
                if (!item) return;
                var open = item.classList.contains('is-open');
                qsa('.guard-accordion__item', root).forEach(function (i) { i.classList.remove('is-open'); });
                if (!open) item.classList.add('is-open');
            });
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

        var scanner = qs('[data-guard-scanner]', form);
        var video = qs('[data-guard-scanner-video]', form);
        var preview = qs('[data-guard-scanner-preview]', form);
        var hint = qs('[data-guard-scanner-hint]', form);
        var stream = null;

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
        }

        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(function (t) { t.stop(); });
                stream = null;
            }
        }

        function startCamera() {
            if (!navigator.mediaDevices || !video) return;
            stopCamera();
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false })
                .then(function (s) {
                    stream = s;
                    video.srcObject = s;
                    video.play();
                })
                .catch(function () {
                    if (hint) hint.textContent = 'Camera unavailable — use upload instead.';
                });
        }

        var captureBtn = qs('[data-guard-scan-capture]', form);
        if (captureBtn && scanner) {
            captureBtn.addEventListener('click', function () {
                if (!video || !video.videoWidth) {
                    window.guardShowToast('Start camera or upload a photo first.', 'error');
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
                            }
                            scanner.classList.add('has-capture');
                            if (hint) hint.textContent = 'Report captured. Continue to evidences.';
                            stopCamera();
                        }, 'image/jpeg', 0.88);
                    }
                }, 700);
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
                    scanner.classList.add('has-capture');
                }
                if (hint) hint.textContent = 'File ready: ' + f.name;
                stopCamera();
            });
        }

        var retake = qs('[data-guard-scan-retake]', form);
        if (retake) {
            retake.addEventListener('click', function () {
                reportFile = null;
                if (scanner) scanner.classList.remove('has-capture');
                if (preview) preview.removeAttribute('src');
                startCamera();
                if (hint) hint.textContent = 'Align document inside frame…';
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
                meta += ' · GPS ' + gps.lat.toFixed(5) + ', ' + gps.lng.toFixed(5);
            } else {
                meta += ' · GPS unavailable';
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
                            done({ lat: pos.coords.latitude, lng: pos.coords.longitude });
                        },
                        function () { done(null); },
                        { enableHighAccuracy: true, timeout: 8000, maximumAge: 60000 }
                    );
                } else {
                    done(null);
                }
            });
        }

        qsa('[data-wizard-next]', form).forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = parseInt(btn.getAttribute('data-wizard-next'), 10);
                if (current === 1 && !reportFile) {
                    window.guardShowToast('Add a report scan or upload first.', 'error');
                    return;
                }
                goStep(target);
                if (target === 1) startCamera();
                else stopCamera();
            });
        });

        qsa('[data-wizard-back]', form).forEach(function (btn) {
            btn.addEventListener('click', function () {
                goStep(parseInt(btn.getAttribute('data-wizard-back'), 10));
            });
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var template = (qs('[name="template_name"]', form) || {}).value || 'Guard report';
            if (!reportFile) {
                window.guardShowToast('Report image is required.', 'error');
                return;
            }
            var submitBtn = qs('[data-guard-submit]', form);
            if (submitBtn) {
                submitBtn.classList.add('is-loading');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Submitting…';
            }
            var fd = new FormData();
            fd.append('template_name', template.trim());
            fd.append('report_scan', reportFile);
            evidences.forEach(function (ev, i) {
                fd.append('evidence[]', ev.file);
                fd.append('evidence_meta[]', ev.meta);
            });
            var token = qs('input[name="csrf_token"]', form);
            if (token) fd.append('csrf_token', token.value);

            fetch(form.getAttribute('action') || 'api/report-submit.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
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
                        if (scanner) scanner.classList.remove('has-capture');
                        goStep(1);
                        setTimeout(function () {
                            window.location.href = 'inbox.php?tab=reports';
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

        goStep(1);
        if (scanner && video) startCamera();
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

    function initGuardPeerSelect(root) {
        qsa('[data-guard-peer-select]', root).forEach(function (sel) {
            if (sel._guardPeerBound) {
                return;
            }
            sel._guardPeerBound = true;
            sel.addEventListener('change', function () {
                var peer = sel.value;
                if (!peer) {
                    return;
                }
                try {
                    var url = new URL(window.location.href);
                    url.searchParams.set('tab', 'chat');
                    url.searchParams.set('peer', peer);
                    url.hash = 'guard-chat';
                    var href = url.href;
                    persistGuardLocation(href);
                    if (typeof window.loadGuardPanel === 'function') {
                        window.loadGuardPanel(href);
                    } else {
                        window.location.href = href;
                    }
                } catch (e) {
                    window.location.href = 'corner.php?tab=chat&peer=' + encodeURIComponent(peer) + '#guard-chat';
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
        initGuardPeerSelect(root);
        initCornerJump(root);
        initCornerClickables(root);
        initAccordion(root);
        syncCornerTabFromUrl(root);
        syncInboxTabFromUrl(root);
        var wizard = qs('[data-guard-report-wizard]', root);
        if (wizard) {
            initReportWizard(wizard);
        }

        var chatScroll = qs('.guard-chat__messages', root);
        if (chatScroll) {
            chatScroll.scrollTop = chatScroll.scrollHeight;
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
