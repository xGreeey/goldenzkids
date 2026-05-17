<?php
declare(strict_types=1);
?>
        document.addEventListener('DOMContentLoaded', function () {
            const themeToggleBtn = document.getElementById('themeToggle');
            const themeIcon = themeToggleBtn?.querySelector('i');
            const body = document.body;
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');

            function closeSidebar() {
                body.classList.remove('sidebar-open');
                sidebarToggle?.setAttribute('aria-expanded', 'false');
            }

            function openSidebar() {
                body.classList.add('sidebar-open');
                sidebarToggle?.setAttribute('aria-expanded', 'true');
            }

            sidebarToggle?.addEventListener('click', () => {
                if (body.classList.contains('sidebar-open')) closeSidebar();
                else openSidebar();
            });

            sidebarBackdrop?.addEventListener('click', closeSidebar);

            document.querySelectorAll('.sidebar-link').forEach((link) => {
                link.addEventListener('click', () => {
                    if (window.innerWidth <= 900) closeSidebar();
                });
            });

            if (!themeToggleBtn || !themeIcon) return;

            const savedTheme = localStorage.getItem('abc_theme');
            if (savedTheme === 'dark') {
                body.classList.remove('light-mode');
                themeIcon.classList.replace('fa-moon', 'fa-sun');
                themeToggleBtn.title = 'Switch to light mode';
            } else {
                body.classList.add('light-mode');
                themeToggleBtn.title = 'Switch to dark mode';
            }

            themeToggleBtn.addEventListener('click', () => {
                body.classList.toggle('light-mode');
                if (body.classList.contains('light-mode')) {
                    localStorage.setItem('abc_theme', 'light');
                    themeIcon.classList.replace('fa-sun', 'fa-moon');
                    themeToggleBtn.title = 'Switch to dark mode';
                } else {
                    localStorage.setItem('abc_theme', 'dark');
                    themeIcon.classList.replace('fa-moon', 'fa-sun');
                    themeToggleBtn.title = 'Switch to light mode';
                }
            });
        });
