/**
 * Admin report PDF export — US Legal portrait (8.5in × 14in).
 * Branded header: logo (left) + company name (center).
 */
(function (global) {
    'use strict';

    const DEFAULT_COMPANY_NAME = 'Golden Z-5 Security & Investigation, Inc.';

    /** @type {string} */
    const PAGE_STYLES =
        '@page{size:8.5in 14in;margin:0.75in;}' +
        '@page{size:legal portrait;margin:0.75in;}' +
        'html,body{margin:0;padding:0;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
        'body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;font-size:11pt;line-height:1.45;color:#111;background:#fff;}' +
        '.report-print-sheet{padding:0.5in 0.75in 0.75in;max-width:8.5in;box-sizing:border-box;margin:0 auto;background:#fff;color:#111;}' +
        '.report-print-header{display:grid;grid-template-columns:minmax(0,1.35in) minmax(0,1fr) minmax(0,1.35in);align-items:center;gap:10px;' +
        'padding-bottom:12px;margin-bottom:16px;border-bottom:2px solid #b8860b;}' +
        '.report-print-header__logo-wrap{display:flex;align-items:center;justify-content:flex-start;}' +
        '.report-print-header__logo{display:block;height:46px;width:auto;max-width:1.25in;object-fit:contain;}' +
        '.report-print-header__title{grid-column:2;margin:0;font-size:11pt;font-weight:700;line-height:1.35;text-align:center;color:#1a1a1a;}' +
        '.report-print-header__spacer{grid-column:3;}' +
        '.report-print-body{min-width:0;}' +
        'h1{font-size:16pt;font-weight:700;margin:0 0 4px;line-height:1.2;}' +
        '.meta{color:#444;font-size:9pt;margin:0 0 14px;}' +
        '.print-hint{color:#555;font-size:8.5pt;margin:0 0 16px;padding:8px 10px;background:#f6f6f6;border:1px solid #ddd;border-radius:4px;}' +
        'dl{display:grid;grid-template-columns:9rem 1fr;gap:5px 12px;margin:0 0 14px;}' +
        'dt{font-weight:700;margin:0;}' +
        'dd{margin:0;}' +
        'h2{font-size:11pt;font-weight:700;margin:16px 0 6px;}' +
        '.narrative,.summary{white-space:pre-wrap;margin:0;word-break:break-word;}' +
        'table{width:100%;border-collapse:collapse;margin:10px 0;font-size:10pt;}' +
        'th,td{border:1px solid #ccc;padding:6px 8px;text-align:left;vertical-align:top;}' +
        'th{background:#f4f4f4;font-size:8.5pt;text-transform:uppercase;letter-spacing:.04em;}' +
        '@media print{.print-hint{display:none!important;}.report-print-sheet{padding:0;max-width:none;}}';

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function absoluteUrl(path) {
        const raw = String(path || '').trim();
        if (raw === '') {
            return '';
        }
        try {
            if (/^https?:\/\//i.test(raw)) {
                return raw;
            }
            return new URL(raw, global.location.href).href;
        } catch (e) {
            return raw;
        }
    }

    function brandConfig() {
        const cfg = global.__reportPrintBrand || {};
        let logoUrl = String(cfg.logoUrl || '').trim();
        if (logoUrl === '') {
            logoUrl = '/assets/images/goldenz_logo.png';
        }
        return {
            logoUrl: absoluteUrl(logoUrl),
            companyName: String(cfg.companyName || DEFAULT_COMPANY_NAME).trim() || DEFAULT_COMPANY_NAME,
        };
    }

    function buildHeaderHtml() {
        const brand = brandConfig();
        return (
            '<header class="report-print-header">' +
            '<div class="report-print-header__logo-wrap">' +
            '<img class="report-print-header__logo" src="' +
            escapeHtml(brand.logoUrl) +
            '" alt="' +
            escapeHtml(brand.companyName) +
            '">' +
            '</div>' +
            '<p class="report-print-header__title">' +
            escapeHtml(brand.companyName) +
            '</p>' +
            '<div class="report-print-header__spacer" aria-hidden="true"></div>' +
            '</header>'
        );
    }

    function wrapBodyHtml(bodyHtml) {
        return buildHeaderHtml() + '<div class="report-print-body">' + String(bodyHtml || '') + '</div>';
    }

    function pdfFilename(title) {
        const base = String(title || 'report')
            .trim()
            .replace(/[^\w\s\-]+/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
        return (base !== '' ? base : 'report') + '.pdf';
    }

    /**
     * @param {HTMLElement} root
     * @returns {Promise<void>}
     */
    function waitForImages(root) {
        const imgs = Array.from(root.querySelectorAll('img'));
        if (imgs.length === 0) {
            return Promise.resolve();
        }
        return Promise.all(
            imgs.map(
                (img) =>
                    new Promise((resolve) => {
                        if (img.complete && img.naturalWidth > 0) {
                            resolve();
                            return;
                        }
                        img.onload = () => resolve();
                        img.onerror = () => resolve();
                    })
            )
        );
    }

    /**
     * @param {{ title?: string, bodyHtml: string, includeHint?: boolean }} opts
     * @returns {string}
     */
    function buildDocument(opts) {
        const title = escapeHtml(opts.title || 'Report');
        const hint =
            opts.includeHint === false
                ? ''
                : '<p class="print-hint" role="note">Exported ' +
                  escapeHtml(new Date().toLocaleString()) +
                  ' · US Legal portrait</p>';

        return (
            '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">' +
            '<title>' +
            title +
            '</title><style>' +
            PAGE_STYLES +
            '</style></head><body><div class="report-print-sheet">' +
            wrapBodyHtml(hint + String(opts.bodyHtml || '')) +
            '</div></body></html>'
        );
    }

    /**
     * @param {{ title?: string, bodyHtml: string }} opts
     * @returns {HTMLElement}
     */
    function buildPrintElement(opts) {
        const host = document.createElement('div');
        host.setAttribute('aria-hidden', 'true');
        host.style.cssText =
            'position:fixed;left:0;top:0;width:8.5in;max-width:8.5in;' +
            'opacity:0.01;pointer-events:none;z-index:-1;overflow:hidden;' +
            'background:#fff;color:#111;';

        const style = document.createElement('style');
        style.textContent = PAGE_STYLES;
        host.appendChild(style);

        const sheet = document.createElement('div');
        sheet.className = 'report-print-sheet';
        sheet.innerHTML = wrapBodyHtml(opts.bodyHtml || '');
        host.appendChild(sheet);

        return host;
    }

    /**
     * @param {{ title?: string, bodyHtml: string }|string} docOrOpts
     */
    function openPrintDialog(docOrOpts) {
        const html =
            typeof docOrOpts === 'string'
                ? docOrOpts
                : buildDocument(
                      typeof docOrOpts === 'object' && docOrOpts !== null
                          ? { ...docOrOpts, includeHint: true }
                          : { bodyHtml: '', includeHint: true }
                  );

        const iframe = document.createElement('iframe');
        iframe.setAttribute('title', 'Print report');
        iframe.style.cssText =
            'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden;';
        document.body.appendChild(iframe);

        const frameWin = iframe.contentWindow;
        const frameDoc = frameWin && frameWin.document;
        if (!frameDoc) {
            iframe.remove();
            window.alert('Could not open the print view. Try allowing pop-ups for this site.');
            return;
        }

        frameDoc.open();
        frameDoc.write(html);
        frameDoc.close();

        const cleanup = function () {
            setTimeout(function () {
                iframe.remove();
            }, 1500);
        };

        const triggerPrint = function () {
            try {
                frameWin.focus();
                frameWin.print();
            } catch (e) {
                window.alert('Could not open the print dialog.');
            }
            cleanup();
        };

        if (frameDoc.readyState === 'complete') {
            setTimeout(triggerPrint, 450);
        } else {
            iframe.onload = function () {
                setTimeout(triggerPrint, 450);
            };
        }
    }

    /**
     * @param {{ title?: string, bodyHtml: string }} opts
     * @returns {Promise<void>}
     */
    function savePdf(opts) {
        const title = String(opts.title || 'Report');
        const filename = pdfFilename(title);

        if (typeof global.html2pdf !== 'function') {
            return Promise.reject(new Error('html2pdf not loaded'));
        }

        const host = buildPrintElement(opts);
        document.body.appendChild(host);
        const sheet = host.querySelector('.report-print-sheet');
        if (!sheet) {
            host.remove();
            return Promise.reject(new Error('Print layout missing'));
        }

        return waitForImages(host)
            .then(function () {
                return global
                    .html2pdf()
                    .set({
                        margin: [0.75, 0.75, 0.75, 0.75],
                        filename: filename,
                        image: { type: 'jpeg', quality: 0.96 },
                        html2canvas: {
                            scale: 2,
                            useCORS: true,
                            allowTaint: true,
                            logging: false,
                            backgroundColor: '#ffffff',
                        },
                        jsPDF: {
                            unit: 'in',
                            format: 'legal',
                            orientation: 'portrait',
                        },
                        pagebreak: { mode: ['css', 'legacy'] },
                    })
                    .from(sheet)
                    .save();
            })
            .then(function () {
                host.remove();
            })
            .catch(function (err) {
                host.remove();
                throw err;
            });
    }

    /**
     * Download PDF (preferred) or open print dialog as fallback.
     * @param {{ title?: string, bodyHtml: string }|string} docOrOpts
     */
    function printDocument(docOrOpts) {
        const opts =
            typeof docOrOpts === 'string'
                ? { title: 'Report', bodyHtml: docOrOpts }
                : {
                      title: docOrOpts.title || 'Report',
                      bodyHtml: docOrOpts.bodyHtml || '',
                  };

        if (typeof global.html2pdf === 'function') {
            savePdf(opts).catch(function () {
                openPrintDialog(buildDocument({ ...opts, includeHint: true }));
            });
            return;
        }

        openPrintDialog(buildDocument({ ...opts, includeHint: true }));
    }

    global.ReportPrint = {
        pageStyles: PAGE_STYLES,
        escapeHtml: escapeHtml,
        buildHeaderHtml: buildHeaderHtml,
        wrapBodyHtml: wrapBodyHtml,
        buildDocument: buildDocument,
        savePdf: savePdf,
        printDialog: openPrintDialog,
        print: printDocument,
    };
})(typeof window !== 'undefined' ? window : globalThis);
