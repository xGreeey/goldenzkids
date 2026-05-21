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
        var DAD_TYPE = 'Daily Attendance Document';
        var ocrPreview = qs('[data-guard-ocr-preview]', form);
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
        var submitSubtitle = qs('[data-guard-submit-subtitle]', form.closest('.guard-section-stack') || document);
        var sheetLocationFix = null;
        var evidenceLocationFix = null;
        var ocrDone = false;
        var ocrBusy = false;

        function isDadMode() {
            var sel = qs('[name="report_type"]', form);
            return sel && String(sel.value || '').trim() === DAD_TYPE;
        }

        function syncDadUi() {
            var dad = isDadMode();
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
                    : 'Scan your filled report, add evidence photos, then submit. Document AI reads the form on submit; evidence files are stored encrypted.';
            }
            if (!dad && ocrPreview) {
                ocrPreview.hidden = true;
            }
        }

        function runOcrPreview() {
            if (!isDadMode() || !reportFile || ocrBusy) {
                return;
            }
            ocrBusy = true;
            ocrDone = false;
            if (ocrPreview) ocrPreview.hidden = false;
            if (ocrStatus) ocrStatus.textContent = 'Reading handwritten attendance sheet with Document AI…';
            if (ocrText) ocrText.textContent = '';

            var fd = new FormData();
            fd.append('report_type', DAD_TYPE);
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
                    if (ocrText) ocrText.textContent = data.formatted || data.raw || '(no text detected)';
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
                syncReportTypeSummary();
                syncDadUi();
            });
        }
        syncDadUi();

        qsa('[data-wizard-next]', form).forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = parseInt(btn.getAttribute('data-wizard-next'), 10);
                if (current === 1) {
                    if (!selectedReportType()) {
                        window.guardShowToast('Select a report type first.', 'error');
                        if (reportTypeSelect) reportTypeSelect.focus();
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
        initGuardPeerSelect(root);
        initCornerJump(root);
        initCornerClickables(root);
        initAccordion(root);
        syncCornerTabFromUrl(root);
        syncInboxTabFromUrl(root);
        syncSubmitReportViewFromUrl(root);
        initReportHistoryToggle(root);
        var wizard = qs('[data-guard-report-wizard]', root);
        if (wizard) {
            initReportWizard(wizard);
        }

        var chatScroll = qs('.guard-chat__messages', root);
        if (chatScroll) {
            chatScroll.scrollTop = chatScroll.scrollHeight;
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
