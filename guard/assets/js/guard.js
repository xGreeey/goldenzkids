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
        btnDiv.style.backgroundColor = 'rgba(254, 189, 89, 0.2)';
        btnDiv.style.borderColor = '#00ff00';
        if (!btnDiv.dataset.originalHtml) {
            btnDiv.dataset.originalHtml = btnDiv.innerHTML;
        }
        const safeName = document.createElement('div');
        safeName.textContent = fileName;
        btnDiv.innerHTML =
            'Uploaded (NA-UPLOAD NA):<br><span style="font-family: var(--font-mono, monospace); font-size: 0.8rem; color: #00ff00;">' +
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
        if (list && msg) {
            msg.style.display = list.children.length === 0 ? 'block' : 'none';
        }
    }

    window.checkEmpty = checkEmpty;

    document.addEventListener('DOMContentLoaded', checkEmpty);
})();
