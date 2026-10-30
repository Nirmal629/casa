/**
 * Inner Header & Profile Modal Interactivity
 * Location: app/script/inner-header.js
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Profile Chip Dropdown via CasaDropdown
    const chip    = document.getElementById('navProfileChip');
    const trigger = document.getElementById('navChipTrigger');

    if (chip && trigger) {
        if (window.CasaDropdown && typeof window.CasaDropdown.bind === 'function') {
            window.CasaDropdown.bind(trigger, chip);
        } else {
            // Fallback
            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                const isOpen = chip.classList.toggle('open');
                trigger.setAttribute('aria-expanded', String(isOpen));
            });

            document.addEventListener('click', function (e) {
                if (!chip.contains(e.target)) {
                    chip.classList.remove('open');
                    trigger.setAttribute('aria-expanded', 'false');
                }
            });
        }
    }

    // 2. Navigation Back Button Handler
    const siteBackButton = document.querySelector('.site-back-btn');
    if (siteBackButton) {
        siteBackButton.addEventListener('click', function () {
            if (window.history.length > 1 && document.referrer && document.referrer.indexOf(window.location.host) !== -1) {
                window.history.back();
                return;
            }
            const fallbackUrl = siteBackButton.getAttribute('data-fallback-url') || '/';
            window.location.href = fallbackUrl;
        });
    }
});
