(function () {
    'use strict';

    var uploadsBase = '';
    var inboxBound = false;

    function getUploadsBase() {
        if (uploadsBase) {
            return uploadsBase;
        }
        var feed = document.getElementById('alert-feed');
        if (feed && feed.dataset.uploadsBase) {
            uploadsBase = feed.dataset.uploadsBase;
        }
        return uploadsBase;
    }

    function cleanTemplatePath(path) {
        if (!path) {
            return '';
        }
        var base = getUploadsBase();
        if (path.includes('uploads/') && base) {
            return base + path.split('uploads/').pop();
        }
        return path;
    }

    function openImageViewer(event, imageSrc) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        var img = document.getElementById('fullScreenImg');
        var viewer = document.getElementById('imageViewer');
        if (!img || !viewer) {
            return;
        }
        img.src = imageSrc;
        viewer.style.display = 'flex';
    }

    function closeImageViewer(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        var viewer = document.getElementById('imageViewer');
        if (viewer) {
            viewer.style.display = 'none';
        }
    }

    function openReportModal(card) {
        var guard = card.getAttribute('data-guard');
        var guardId = card.getAttribute('data-id');
        var est = card.getAttribute('data-est');
        var time = card.getAttribute('data-time');
        var status = card.getAttribute('data-status');
        var tempPath = cleanTemplatePath(card.getAttribute('data-template'));
        var aiText = card.getAttribute('data-aitext') || '';

        document.getElementById('modalTitle').textContent = 'Report from ' + est;
        document.getElementById('modalTimestamp').textContent = 'Logged: ' + time;

        var img = document.getElementById('imgTemp');
        img.src = tempPath || '';
        img.onerror = function () {
            this.onerror = null;
            this.src = 'https://via.placeholder.com/300x400/e8ebf0/6b7a8f?text=Image+not+found';
        };
        img.onclick = function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (img.src) {
                openImageViewer(e, img.src);
            }
        };

        document.getElementById('modalInfo').innerHTML =
            '<p><strong>Employee ID:</strong> ' + guardId + '</p>' +
            '<p><strong>Personnel:</strong> ' + guard + '</p>' +
            '<p><strong>Status:</strong> ' + status + '</p>';

        var aiContainer = document.getElementById('aiTextContainer');
        var aiTextDisplay = document.getElementById('modalAiText');
        if (aiText.trim() !== '') {
            aiTextDisplay.textContent = aiText;
            aiTextDisplay.innerHTML = aiTextDisplay.innerHTML.replace(/\n/g, '<br>');
            aiContainer.style.display = 'block';
        } else {
            aiContainer.style.display = 'none';
        }

        document.getElementById('formTime').value = time;
        document.getElementById('formGuardId').value = guardId;
        document.getElementById('reportModal').style.display = 'flex';
    }

    function closeModal() {
        var modal = document.getElementById('reportModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    function dismiss(event, btn) {
        event.preventDefault();
        event.stopPropagation();
        var card = btn.closest('.notif-card');
        card.style.opacity = '0';
        card.style.transform = 'translateX(12px)';
        setTimeout(function () {
            card.remove();
            checkEmpty();
        }, 280);
    }

    function checkEmpty() {
        var list = document.getElementById('alert-feed');
        var msg = document.getElementById('empty-msg');
        if (!list || !msg) {
            return;
        }
        msg.style.display = list.children.length === 0 ? 'block' : 'none';
    }

    function setMemoProtocol(type) {
        var distTypeInput = document.getElementById('distTypeValue');
        var detailsContainer = document.getElementById('memoDetailsContainer');
        var targetContainer = document.getElementById('targetGuardContainer');
        var targetInput = document.getElementById('targetGuardInput');
        var btnBroadcast = document.getElementById('btnBroadcast');
        var btnTargeted = document.getElementById('btnTargeted');
        if (!distTypeInput || !detailsContainer) {
            return;
        }

        distTypeInput.value = type;
        detailsContainer.classList.add('is-visible');

        if (type === 'broadcast') {
            if (btnBroadcast) {
                btnBroadcast.classList.add('active');
            }
            if (btnTargeted) {
                btnTargeted.classList.remove('active');
            }
            if (targetContainer) {
                targetContainer.classList.remove('is-visible');
            }
            if (targetInput) {
                targetInput.value = '';
            }
        } else if (type === 'targeted') {
            if (btnTargeted) {
                btnTargeted.classList.add('active');
            }
            if (btnBroadcast) {
                btnBroadcast.classList.remove('active');
            }
            if (targetContainer) {
                targetContainer.classList.add('is-visible');
            }
        }
    }

    function bindReportModal() {
        var modal = document.getElementById('reportModal');
        if (!modal || modal.dataset.inboxBound === '1') {
            return;
        }
        modal.dataset.inboxBound = '1';
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    function bindImageViewer() {
        var viewer = document.getElementById('imageViewer');
        if (!viewer || viewer.dataset.inboxBound === '1') {
            return;
        }
        viewer.dataset.inboxBound = '1';
        viewer.addEventListener('click', function (event) {
            if (event.target === viewer) {
                closeImageViewer(event);
            }
        });
    }

    function bindNotifCards() {
        document.querySelectorAll('.notif-card').forEach(function (card) {
            if (card.dataset.inboxBound === '1') {
                return;
            }
            card.dataset.inboxBound = '1';
            card.addEventListener('click', function (event) {
                if (event.target.closest('.btn-dismiss')) {
                    return;
                }
                event.preventDefault();
                openReportModal(card);
            });
            card.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openReportModal(card);
                }
            });
        });
    }

    function bindMemoForm() {
        var memoForm = document.getElementById('memoForm');
        if (!memoForm || memoForm.dataset.inboxBound === '1') {
            return;
        }
        memoForm.dataset.inboxBound = '1';

        var btnBroadcast = document.getElementById('btnBroadcast');
        var btnTargeted = document.getElementById('btnTargeted');
        if (btnBroadcast) {
            btnBroadcast.addEventListener('click', function () {
                setMemoProtocol('broadcast');
            });
        }
        if (btnTargeted) {
            btnTargeted.addEventListener('click', function () {
                setMemoProtocol('targeted');
            });
        }
        memoForm.addEventListener('submit', function (event) {
            var errors = [];
            var distType = document.getElementById('distTypeValue');
            var memoType = document.getElementById('memoTypeInput');
            var content = document.getElementById('memoContentInput');
            var distVal = distType ? distType.value : '';
            var memoVal = memoType ? memoType.value : '';
            var contentVal = content ? content.value.trim() : '';

            if (!distVal) {
                errors.push('Delivery scope (company-wide or individual)');
            } else if (distVal === 'targeted') {
                var target = document.getElementById('targetGuardInput');
                if (!target || !target.value) {
                    errors.push('Recipient employee');
                }
            }
            if (!memoVal) {
                errors.push('Message category');
            }
            if (contentVal === '') {
                errors.push('Memo body');
            }

            if (errors.length > 0) {
                event.preventDefault();
                alert('Please complete the required fields before publishing:\n\n• ' + errors.join('\n• '));
            }
        });
    }

    function scrollMessagingThread() {
        var thread = document.getElementById('messagingThreadScroll');
        if (thread) {
            thread.scrollTop = thread.scrollHeight;
        }
    }

    window.cleanTemplatePath = cleanTemplatePath;
    window.openImageViewer = openImageViewer;
    window.closeImageViewer = closeImageViewer;
    window.openReportModal = openReportModal;
    window.closeModal = closeModal;
    window.dismiss = dismiss;
    window.checkEmpty = checkEmpty;
    window.setMemoProtocol = setMemoProtocol;

    window.initAdminInboxPage = function () {
        if (!document.getElementById('alert-feed') && !document.getElementById('memoForm')) {
            return;
        }
        uploadsBase = '';
        bindReportModal();
        bindImageViewer();
        bindNotifCards();
        bindMemoForm();
        if (document.getElementById('memoForm')) {
            setMemoProtocol('broadcast');
        }
        checkEmpty();
        scrollMessagingThread();
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (!inboxBound) {
            inboxBound = true;
            window.initAdminInboxPage();
        }
    });
})();
