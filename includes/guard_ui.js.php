<?php
declare(strict_types=1);

function guard_ui_scripts(): void
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

    function initGuardAppDrawer() {
        if (!document.body.classList.contains('guard-portal')) {
            return;
        }
        var btn = document.getElementById('guardAppMenuBtn');
        var drawer = document.getElementById('guardAppDrawer');
        if (!btn || !drawer) {
            return;
        }
        if (btn._guardAppDrawerBound) {
            return;
        }
        btn._guardAppDrawerBound = true;

        function openDrawer() {
            drawer.classList.add('is-open');
            drawer.setAttribute('aria-hidden', 'false');
            btn.classList.add('is-open');
            btn.setAttribute('aria-expanded', 'true');
            btn.setAttribute('aria-label', 'Close navigation menu');
            document.body.classList.add('guard-app-nav-open');
        }

        function closeDrawer() {
            drawer.classList.remove('is-open');
            drawer.setAttribute('aria-hidden', 'true');
            btn.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
            btn.setAttribute('aria-label', 'Open navigation menu');
            document.body.classList.remove('guard-app-nav-open');
        }

        btn.addEventListener('click', function () {
            if (drawer.classList.contains('is-open')) {
                closeDrawer();
            } else {
                openDrawer();
            }
        });

        drawer.querySelectorAll('[data-guard-drawer-close]').forEach(function (el) {
            el.addEventListener('click', closeDrawer);
        });

        drawer.querySelectorAll('.guard-app__drawer-link').forEach(function (link) {
            link.addEventListener('click', closeDrawer);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && drawer.classList.contains('is-open')) {
                closeDrawer();
            }
        });
    }

    function initGuardApp() {
        initGuardAppDrawer();
        if (typeof window.guardInitPortal === 'function') {
            var root = document.querySelector('[data-guard-panel-root]') || document.querySelector('.guard-app__scroll');
            window.guardInitPortal(root);
        }
    }

    document.addEventListener('DOMContentLoaded', initGuardApp);
    window.initGuardApp = initGuardApp;
})();
</script>
    <?php
}
