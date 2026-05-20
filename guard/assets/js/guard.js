(function () {
    'use strict';

    const uploadsUrl = document.body.dataset.uploadsUrl || '';

    function updateFileName(input) {
        if (!input.files || !input.files[0]) {
            return;
        }
        const fileName = input.files[0].name;
        const btnDiv = input.previousElementSibling;
        if (!btnDiv) {
            return;
        }
        btnDiv.style.backgroundColor = 'rgba(254, 189, 89, 0.15)';
        btnDiv.style.borderColor = 'rgba(var(--color-secondary-text-rgb), 0.55)';
        if (!btnDiv.dataset.originalHtml) {
            btnDiv.dataset.originalHtml = btnDiv.innerHTML;
        }
        const safeName = document.createElement('div');
        safeName.textContent = fileName;
        btnDiv.innerHTML =
            '<span style="font-weight:600;">Uploaded</span><br><span style="font-size:0.8125rem;color:var(--color-secondary-text);">' +
            safeName.innerHTML +
            '</span>';
    }

    window.updateFileName = updateFileName;

    function cleanUploadPath(path) {
        if (!path) {
            return '';
        }
        if (path.includes('uploads/')) {
            return uploadsUrl + path.split('uploads/').pop();
        }
        return path;
    }

    function openImageViewer(imageSrc) {
        const viewer = document.getElementById('imageViewer');
        const fullImg = document.getElementById('fullScreenImg');
        if (!viewer || !fullImg) {
            return;
        }
        fullImg.src = imageSrc;
        viewer.style.display = 'flex';
    }

    function closeImageViewer() {
        const viewer = document.getElementById('imageViewer');
        if (viewer) {
            viewer.style.display = 'none';
        }
    }

    window.openImageViewer = openImageViewer;
    window.closeImageViewer = closeImageViewer;

    function openReportModal(card) {
        const guard = card.getAttribute('data-guard');
        const guardId = card.getAttribute('data-id');
        const est = card.getAttribute('data-est');
        const time = card.getAttribute('data-time');
        const status = card.getAttribute('data-status');
        const tempPath = cleanUploadPath(card.getAttribute('data-template'));

        const modal = document.getElementById('reportModal');
        if (!modal) {
            return;
        }

        document.getElementById('modalTitle').innerText = 'DGD sent from ' + est;
        document.getElementById('modalTimestamp').innerText = 'SYSTEM LOGGED: ' + time;
        document.getElementById('imgTemp').src = tempPath;
        document.getElementById('modalInfo').innerHTML =
            '<p><strong>GUARD ID:</strong> ' +
            guardId +
            '</p>' +
            '<p><strong>PERSONNEL:</strong> ' +
            guard +
            '</p>' +
            '<p><strong>ASSIGNMENT/LOCATION:</strong> ' +
            est +
            '</p>' +
            '<p><strong>REPORT STATUS:</strong> ' +
            status +
            '</p>' +
            '<p style="color: var(--success-green); margin-top: 15px; border-top: 1px dashed rgba(255,255,255,0.2); padding-top: 10px;">' +
            '[OK] AES-256 DECRYPTION SUCCESSFUL' +
            '</p>';
        modal.style.display = 'flex';
    }

    function closeModal() {
        const modal = document.getElementById('reportModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    window.openReportModal = openReportModal;
    window.closeModal = closeModal;

    window.onclick = function (event) {
        const modal = document.getElementById('reportModal');
        if (event.target === modal) {
            closeModal();
        }
    };

    function dismiss(event, btn) {
        event.stopPropagation();
        const card = btn.closest('.notif-card');
        if (!card) {
            return;
        }
        card.style.opacity = '0';
        card.style.transform = 'translateX(50px)';
        setTimeout(function () {
            card.remove();
            checkEmpty();
        }, 300);
    }

    window.dismiss = dismiss;

    function checkEmpty() {
        const list = document.getElementById('alert-feed');
        const msg = document.getElementById('empty-msg');
        if (!list || !msg) {
            return;
        }
        const cards = [...list.children].filter(function (c) {
            return c.classList && c.classList.contains('notif-card');
        });
        if (cards.length === 0) {
            msg.style.display = 'block';
            return;
        }
        const anyVisible = cards.some(function (c) {
            return !c.style.display || c.style.display !== 'none';
        });
        msg.style.display = anyVisible ? 'none' : 'block';
    }

    window.checkEmpty = checkEmpty;

    document.addEventListener('DOMContentLoaded', function () {
        checkEmpty();

        function setChipLocationLabel(label) {
            var chipLabelEl = document.getElementById('guardLocationChipLabel');
            if (chipLabelEl && typeof label === 'string' && label.trim() !== '') {
                chipLabelEl.textContent = label.trim();
            }
        }

        function formatFallbackLabel(lat, lon) {
            return lat.toFixed(3) + ', ' + lon.toFixed(3);
        }

        function resolveLocationLabelFromCoords(lat, lon) {
            return fetch(
                'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' +
                    encodeURIComponent(String(lat)) +
                    '&lon=' +
                    encodeURIComponent(String(lon))
            )
                .then(function (res) {
                    if (!res.ok) {
                        throw new Error('reverse geocode failed');
                    }
                    return res.json();
                })
                .then(function (json) {
                    var addr = (json && json.address) || {};
                    var locality =
                        addr.city ||
                        addr.town ||
                        addr.village ||
                        addr.suburb ||
                        addr.county ||
                        '';
                    var region = addr.state || addr.region || addr.country || '';
                    return locality && region ? locality + ', ' + region : locality || region || formatFallbackLabel(lat, lon);
                });
        }

        function maybeResolveUserLocation(forceRefresh) {
            var chipLabelEl = document.getElementById('guardLocationChipLabel');
            if (!chipLabelEl || !navigator.geolocation) {
                return Promise.resolve('');
            }

            var cachedLabel = localStorage.getItem('guardGeoLabel') || '';
            var cachedAt = parseInt(localStorage.getItem('guardGeoAt') || '0', 10);
            if (!forceRefresh && cachedLabel && Number.isFinite(cachedAt) && Date.now() - cachedAt < 30 * 60 * 1000) {
                setChipLocationLabel(cachedLabel);
                return Promise.resolve(cachedLabel);
            }

            return new Promise(function (resolve, reject) {
                navigator.geolocation.getCurrentPosition(
                    function (pos) {
                        var lat = pos.coords.latitude;
                        var lon = pos.coords.longitude;
                        resolveLocationLabelFromCoords(lat, lon)
                            .then(function (label) {
                                setChipLocationLabel(label);
                                localStorage.setItem('guardGeoLabel', label);
                                localStorage.setItem('guardGeoAt', String(Date.now()));
                                resolve(label);
                            })
                            .catch(function () {
                                var fallback = formatFallbackLabel(lat, lon);
                                setChipLocationLabel(fallback);
                                localStorage.setItem('guardGeoLabel', fallback);
                                localStorage.setItem('guardGeoAt', String(Date.now()));
                                resolve(fallback);
                            });
                    },
                    function () {
                        reject(new Error('geolocation denied/unavailable'));
                    },
                    { enableHighAccuracy: false, timeout: 7000, maximumAge: 300000 }
                );
            });
        }

        var selectedLabel = localStorage.getItem('guardSelectedLocationLabel') || '';
        if (selectedLabel) {
            setChipLocationLabel(selectedLabel);
        } else {
            maybeResolveUserLocation(false).catch(function () {
                /* keep server-side default label */
            });
        }

        // Reset any stale open state from prior renders.
        document.querySelectorAll('.guard-mobile-fab-wrap').forEach(function (w) {
            w.classList.remove('is-open');
            var btn = w.querySelector('.guard-mobile-fab');
            if (btn) {
                btn.setAttribute('aria-expanded', 'false');
            }
        });

        var inboxSearch = document.getElementById('guardInboxSearch');
        var alertFeed = document.getElementById('alert-feed');
        if (inboxSearch && alertFeed) {
            inboxSearch.addEventListener('input', function () {
                var q = inboxSearch.value.trim().toLowerCase();
                alertFeed.querySelectorAll('.notif-card').forEach(function (card) {
                    card.style.display = q === '' || card.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
                });
                checkEmpty();
            });
        }

        var locationTrigger = document.getElementById('guardLocationSelectorTrigger');
        var locationPicker = document.getElementById('guardLocationPickerPanel');
        var useCurrentBtn = document.getElementById('guardUseCurrentLocationBtn');
        var useAssignedBtn = document.getElementById('guardUseAssignedLocationBtn');
        var manualInput = document.getElementById('guardLocationManualInput');
        var applyBtn = document.getElementById('guardApplyLocationBtn');
        var searchResults = document.getElementById('guardLocationSearchResults');
        var searchDebounceTimer = null;

        function closeLocationPicker() {
            if (!locationTrigger || !locationPicker) {
                return;
            }
            locationTrigger.setAttribute('aria-expanded', 'false');
            locationPicker.hidden = true;
        }

        function renderLocationSearchResults(items) {
            if (!searchResults) {
                return;
            }
            if (!Array.isArray(items) || items.length === 0) {
                searchResults.innerHTML = '';
                searchResults.hidden = true;
                return;
            }

            searchResults.hidden = false;
            searchResults.innerHTML = '';
            items.forEach(function (item) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'guard-location-picker__result-item';
                btn.setAttribute('role', 'option');
                btn.textContent = item;
                btn.addEventListener('click', function () {
                    setChipLocationLabel(item);
                    localStorage.setItem('guardSelectedLocationLabel', item);
                    if (manualInput) {
                        manualInput.value = item;
                    }
                    closeLocationPicker();
                });
                searchResults.appendChild(btn);
            });
        }

        function searchLocationSuggestions(query) {
            if (query.length < 3) {
                renderLocationSearchResults([]);
                return;
            }

            fetch(
                'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=5&countrycodes=ph&q=' +
                    encodeURIComponent(query)
            )
                .then(function (res) {
                    if (!res.ok) {
                        throw new Error('location search failed');
                    }
                    return res.json();
                })
                .then(function (rows) {
                    var labels = (Array.isArray(rows) ? rows : [])
                        .map(function (row) {
                            return (row && row.display_name) || '';
                        })
                        .filter(function (v) {
                            return v && v.trim() !== '';
                        });
                    renderLocationSearchResults(labels);
                })
                .catch(function () {
                    renderLocationSearchResults([]);
                });
        }

        if (locationTrigger && locationPicker) {
            locationTrigger.addEventListener('click', function () {
                var expanded = locationTrigger.getAttribute('aria-expanded') === 'true';
                locationTrigger.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                locationPicker.hidden = expanded;
            });

            document.addEventListener('click', function (event) {
                if (!event.target.closest('.guard-location-selector')) {
                    renderLocationSearchResults([]);
                    closeLocationPicker();
                }
            });
        }

        if (useCurrentBtn) {
            useCurrentBtn.addEventListener('click', function () {
                maybeResolveUserLocation(true)
                    .then(function (label) {
                        localStorage.setItem('guardSelectedLocationLabel', label);
                    })
                    .catch(function () {
                        /* keep existing label */
                    })
                    .finally(closeLocationPicker);
            });
        }

        if (useAssignedBtn) {
            useAssignedBtn.addEventListener('click', function () {
                var assigned = (useAssignedBtn.dataset.location || '').trim();
                if (assigned) {
                    setChipLocationLabel(assigned);
                    localStorage.setItem('guardSelectedLocationLabel', assigned);
                }
                closeLocationPicker();
            });
        }

        if (applyBtn && manualInput) {
            applyBtn.addEventListener('click', function () {
                var typed = manualInput.value.trim();
                if (!typed) {
                    return;
                }
                setChipLocationLabel(typed);
                localStorage.setItem('guardSelectedLocationLabel', typed);
                renderLocationSearchResults([]);
                closeLocationPicker();
            });

            manualInput.addEventListener('input', function () {
                var q = manualInput.value.trim();
                if (searchDebounceTimer) {
                    clearTimeout(searchDebounceTimer);
                }
                searchDebounceTimer = setTimeout(function () {
                    searchLocationSuggestions(q);
                }, 260);
            });
        }

        document.querySelectorAll('.guard-mobile-fab').forEach(function (fabBtn) {
            fabBtn.addEventListener('click', function (e) {
                e.preventDefault();
                var wrap = fabBtn.closest('.guard-mobile-fab-wrap');
                if (!wrap) {
                    return;
                }
                var willOpen = !wrap.classList.contains('is-open');
                document.querySelectorAll('.guard-mobile-fab-wrap.is-open').forEach(function (w) {
                    w.classList.remove('is-open');
                    var btn = w.querySelector('.guard-mobile-fab');
                    if (btn) {
                        btn.setAttribute('aria-expanded', 'false');
                    }
                });
                if (willOpen) {
                    wrap.classList.add('is-open');
                    fabBtn.setAttribute('aria-expanded', 'true');
                } else {
                    fabBtn.setAttribute('aria-expanded', 'false');
                }
            });
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.guard-mobile-fab-wrap')) {
                document.querySelectorAll('.guard-mobile-fab-wrap.is-open').forEach(function (w) {
                    w.classList.remove('is-open');
                    var btn = w.querySelector('.guard-mobile-fab');
                    if (btn) {
                        btn.setAttribute('aria-expanded', 'false');
                    }
                });
            }
        });

        document.querySelectorAll('.guard-mobile-fab-sheet__item, .guard-mobile-fab-sheet a').forEach(function (node) {
            node.addEventListener('click', function () {
                document.querySelectorAll('.guard-mobile-fab-wrap.is-open').forEach(function (w) {
                    w.classList.remove('is-open');
                    var btn = w.querySelector('.guard-mobile-fab');
                    if (btn) {
                        btn.setAttribute('aria-expanded', 'false');
                    }
                });
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') {
                return;
            }
            document.querySelectorAll('.guard-mobile-fab-wrap.is-open').forEach(function (w) {
                w.classList.remove('is-open');
                var btn = w.querySelector('.guard-mobile-fab');
                if (btn) {
                    btn.setAttribute('aria-expanded', 'false');
                }
            });
        });
    });
})();
