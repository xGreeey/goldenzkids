<?php
declare(strict_types=1);

/**
 * In-app sidebar panel navigation (fetch + instant swap, no full page reload).
 * Used by admin_shell_styles() and admin_shell_scripts().
 */

function panel_navigation_styles(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    ?>
        .app-main {
            position: relative;
        }

        .app-main.is-panel-busy {
            pointer-events: none;
        }

        .app-main__stage {
            min-height: 1px;
        }
    <?php
}

function panel_navigation_script(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var main = document.querySelector('.app-main');
    var nav = document.querySelector('.sidebar-nav');
    if (!main || !nav) {
        return;
    }

    var isBusy = false;
    var loadGeneration = 0;
    var abortController = null;

    var stage = main.querySelector('.app-main__stage');
    if (!stage) {
        stage = document.createElement('div');
        stage.className = 'app-main__stage';
        var nodes = [];
        while (main.firstChild) {
            nodes.push(main.removeChild(main.firstChild));
        }
        main.appendChild(stage);
        nodes.forEach(function (n) {
            if (n.id === 'createAccountModal') {
                main.appendChild(n);
            } else {
                stage.appendChild(n);
            }
        });
    } else {
        var orphanModal = main.querySelector('#createAccountModal');
        if (orphanModal && stage.contains(orphanModal)) {
            main.appendChild(orphanModal);
        }
    }

    function panelFiles() {
        return Array.prototype.map.call(
            nav.querySelectorAll('a.sidebar-link[href]'),
            function (link) {
                var href = link.getAttribute('href') || '';
                return href.split('?')[0].split('#')[0];
            }
        );
    }

    function resolveUrl(href) {
        return new URL(href, window.location.href).href;
    }

    function isPanelNavUrl(url) {
        var target = new URL(url, window.location.href);
        if (target.origin !== window.location.origin) {
            return false;
        }
        var currentDir = window.location.pathname.replace(/[^/]+$/, '');
        if (target.pathname.indexOf(currentDir) !== 0) {
            return false;
        }
        var file = target.pathname.slice(currentDir.length).split('?')[0];
        return panelFiles().indexOf(file) !== -1;
    }

    function setActiveSidebarLink(activeLink) {
        nav.querySelectorAll('a.sidebar-link').forEach(function (item) {
            var isActive = item === activeLink;
            item.classList.toggle('active', isActive);
            if (isActive) {
                item.setAttribute('aria-current', 'page');
            } else {
                item.removeAttribute('aria-current');
            }
        });
    }

    function filenameFromHref(href) {
        if (!href || href === '#' || href.indexOf('javascript:') === 0) {
            return '';
        }
        try {
            var path = new URL(href, window.location.href).pathname;
        } catch (e) {
            return '';
        }
        var base = path.split('/').pop() || '';
        return base.split('?')[0].split('#')[0];
    }

    function setActiveFromUrl(url) {
        var file;
        try {
            file = new URL(url, window.location.href).pathname.split('/').pop() || '';
            file = file.split('?')[0].split('#')[0];
        } catch (e) {
            return;
        }
        if (!file) {
            return;
        }
        var links = nav.querySelectorAll('a.sidebar-link[href]');
        var target = null;
        links.forEach(function (a) {
            if (filenameFromHref(a.getAttribute('href') || '') === file) {
                target = a;
            }
        });
        if (target) {
            setActiveSidebarLink(target);
        }
    }

    function lockMainHeight() {
        main.style.minHeight = main.offsetHeight + 'px';
    }

    function unlockMainHeight() {
        main.style.minHeight = '';
    }

    async function fetchPanelHtml(absUrl) {
        abortController && abortController.abort();
        abortController = new AbortController();
        var response = await fetch(absUrl, {
            credentials: 'same-origin',
            signal: abortController.signal,
            headers: { 'X-Panel-Navigation': '1' }
        });
        if (!response.ok) {
            throw new Error('Panel fetch failed');
        }
        return response.text();
    }

    function applyPanelHtml(html, absUrl, options) {
        options = options || {};
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var newMain = doc.querySelector('.app-main');
        if (!newMain) {
            throw new Error('Panel content missing');
        }

        document.body.classList.remove('app-modal-open');

        stage.innerHTML = '';
        Array.prototype.forEach.call(newMain.children, function (child) {
            if (child.id === 'createAccountModal') {
                return;
            }
            stage.appendChild(document.importNode(child, true));
        });

        var newModal = doc.getElementById('createAccountModal');
        var curModal = document.getElementById('createAccountModal');
        if (newModal) {
            var importedModal = document.importNode(newModal, true);
            importedModal.removeAttribute('data-sa-modal-bound');
            if (curModal) {
                curModal.replaceWith(importedModal);
            } else {
                main.appendChild(importedModal);
            }
        } else if (curModal) {
            curModal.remove();
            if (typeof window.__saCreateAccountModalClose !== 'undefined') {
                window.__saCreateAccountModalClose = null;
            }
        }

        if (doc.title) {
            document.title = doc.title;
        }
        if (!options.skipPush) {
            history.pushState({ panelNav: true, url: absUrl }, '', absUrl);
        }
        window.scrollTo(0, 0);
        if (!options.skipActive) {
            setActiveFromUrl(absUrl);
        }
        if (typeof window.superadminInitCreateAccountModal === 'function') {
            window.superadminInitCreateAccountModal();
        }
    }

    async function loadPanel(url, options) {
        options = options || {};
        if (options.skipActive === undefined) {
            options.skipActive = false;
        }
        var absUrl = resolveUrl(url);
        if (!isPanelNavUrl(absUrl)) {
            window.location.href = absUrl;
            return;
        }

        var generation = ++loadGeneration;
        abortController && abortController.abort();

        if (!options.skipActive) {
            setActiveFromUrl(absUrl);
        }

        isBusy = true;
        main.classList.add('is-panel-busy');
        lockMainHeight();

        try {
            var html = await fetchPanelHtml(absUrl);
            if (generation !== loadGeneration) {
                return;
            }
            applyPanelHtml(html, absUrl, options);
        } catch (err) {
            if (err && err.name === 'AbortError') {
                return;
            }
            if (generation === loadGeneration) {
                window.location.href = absUrl;
            }
            return;
        } finally {
            if (generation === loadGeneration) {
                unlockMainHeight();
                main.classList.remove('is-panel-busy');
                isBusy = false;
            }
        }
    }

    function onPanelLinkClick(event, link) {
        var href = link.getAttribute('href');
        if (!href || href === '#' || link.target === '_blank') {
            return;
        }
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
            return;
        }
        var absUrl = resolveUrl(href);
        if (!isPanelNavUrl(absUrl)) {
            return;
        }
        if (link.classList.contains('active') && !href.includes('?')) {
            event.preventDefault();
            return;
        }
        event.preventDefault();
        if (link.classList.contains('sidebar-link')) {
            setActiveSidebarLink(link);
        } else {
            setActiveFromUrl(absUrl);
        }
        loadPanel(absUrl);
    }

    nav.addEventListener('click', function (event) {
        var link = event.target.closest('a.sidebar-link[href]');
        if (!link) {
            return;
        }
        onPanelLinkClick(event, link);
    });

    document.querySelectorAll('.sidebar-footer-icon[href]').forEach(function (icon) {
        icon.addEventListener('click', function (event) {
            onPanelLinkClick(event, icon);
        });
    });

    main.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');
        if (!link || link.closest('.sidebar-nav')) {
            return;
        }
        onPanelLinkClick(event, link);
    });

    window.addEventListener('popstate', function (event) {
        if (event.state && event.state.panelNav && event.state.url) {
            loadPanel(event.state.url, { skipPush: true });
            return;
        }
        window.location.reload();
    });

    if (!history.state || !history.state.panelNav) {
        history.replaceState({ panelNav: true, url: window.location.href }, '', window.location.href);
    }
});
</script>
    <?php
}
