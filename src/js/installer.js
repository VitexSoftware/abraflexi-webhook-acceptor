/*
 * Installer UI behaviour: light/dark toggle (shared look with vitexsoftware.cz).
 */
(function () {
    'use strict';

    var root = document.documentElement;

    function setTheme(theme) {
        root.setAttribute('data-theme', theme);
        root.setAttribute('data-bs-theme', theme);
        try { localStorage.setItem('vsTheme', theme); } catch (e) { /* private mode */ }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.theme-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                setTheme(root.getAttribute('data-theme') === 'light' ? 'dark' : 'light');
            });
        });
    });
}());
