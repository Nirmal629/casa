/**
 * Casa Dropdown - Generic & Dynamic Dropdown Component
 * Handles both native <select> elements and custom dropdown containers across the entire app.
 */
(function (window, document) {
    'use strict';

    if (window.CasaDropdown) return;

    var CHEVRON_SVG = '<svg width="10" height="10" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/></svg>';
    var CHECK_SVG = '<svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/></svg>';

    var customDropdowns = [];

    // Remove vertical scrollbar ("side bar") on dropdown menus
    function ensureNoScrollbarStyle() {
        if (typeof document !== 'undefined' && !document.getElementById('casa-dropdown-no-scrollbar')) {
            var s = document.createElement('style');
            s.id = 'casa-dropdown-no-scrollbar';
            s.textContent =
                '.casa-dropdown-menu-list { scrollbar-width: none !important; -ms-overflow-style: none !important; }' +
                '.casa-dropdown-menu-list::-webkit-scrollbar { display: none !important; width: 0 !important; height: 0 !important; }';
            (document.head || document.documentElement).appendChild(s);
        }
    }
    ensureNoScrollbarStyle();

    function closeAllDropdowns() {
        document.querySelectorAll('.casa-dropdown-wrap.open').forEach(function (w) {
            w.classList.remove('open');
        });
        customDropdowns.forEach(function (item) {
            if (item.wrap && item.wrap.classList.contains('open')) {
                item.wrap.classList.remove('open');
                if (item.trigger) item.trigger.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Global outside click & Escape close
    document.addEventListener('click', function (e) {
        var inWrap = e.target.closest('.casa-dropdown-wrap');
        var inCustom = customDropdowns.some(function (item) {
            return item.wrap && item.wrap.contains(e.target);
        });
        if (!inWrap && !inCustom) {
            closeAllDropdowns();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAllDropdowns();
    });

    //Bind any custom trigger and wrapper (e.g. Header profile chip)
    
    function bindCustom(triggerTarget, wrapTarget) {
        var trigger = typeof triggerTarget === 'string' ? document.querySelector(triggerTarget) : triggerTarget;
        var wrap = typeof wrapTarget === 'string' ? document.querySelector(wrapTarget) : (wrapTarget || (trigger ? trigger.parentElement : null));
        if (!trigger || !wrap || wrap._casaDropdownBound) return;
        wrap._casaDropdownBound = true;

        customDropdowns.push({ trigger: trigger, wrap: wrap });

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            var isOpen = wrap.classList.contains('open');
            closeAllDropdowns();
            if (!isOpen) {
                wrap.classList.add('open');
                trigger.setAttribute('aria-expanded', 'true');
            }
        });
    }

    //Initialize/Enhance a <select> element into a CasaDropdown
     
    function initDropdown(target) {
        var select = typeof target === 'string' ? document.querySelector(target) : target;
        if (!select || select._casaDropdown) return select ? select.casaDropdown : null;
        select._casaDropdown = true;
        select.style.display = 'none';

        var size = select.dataset.size || 'md';
        var align = select.dataset.align === 'right' ? ' align-right' : '';
        var iconHtml = select.dataset.icon ? '<span class="trigger-icon"><i class="' + select.dataset.icon + '"></i></span>' : '';

        // Build wrapper container
        var wrap = document.createElement('div');
        var isFullWidth = select.dataset.width === '100%' || select.classList.contains('w-100') || select.classList.contains('form-control');
        wrap.className = 'casa-dropdown-wrap size-' + size + align + (isFullWidth ? ' w-100' : '');
        if (select.dataset.width) wrap.style.width = select.dataset.width;

        var selectedOpt = select.selectedOptions[0] || select.options[0];
        var initialLabel = selectedOpt ? selectedOpt.textContent.trim() : (select.dataset.placeholder || 'Select');

        wrap.innerHTML =
            '<button type="button" class="casa-dropdown-trigger"' + (select.disabled ? ' disabled' : '') + '>' +
                '<span class="trigger-content">' +
                    iconHtml +
                    '<span class="trigger-label">' + initialLabel + '</span>' +
                '</span>' +
                '<span class="trigger-chevron">' + CHEVRON_SVG + '</span>' +
            '</button>' +
            '<div class="casa-dropdown-menu"><div class="casa-dropdown-menu-list"></div></div>';

        var trigger = wrap.querySelector('.casa-dropdown-trigger');
        var labelSpan = wrap.querySelector('.trigger-label');
        var list = wrap.querySelector('.casa-dropdown-menu-list');
        if (list) {
            list.style.scrollbarWidth = 'none';
            list.style.msOverflowStyle = 'none';
        }

        function renderOptions() {
            list.innerHTML = '';
            Array.prototype.forEach.call(select.options, function (opt) {
                var isSelected = opt.value === select.value;
                var isDisabled = opt.disabled;
                var item = document.createElement('button');
                item.type = 'button';
                item.className = 'casa-dropdown-item' + (isSelected ? ' is-selected' : '') + (isDisabled ? ' is-disabled' : '');
                if (isDisabled) item.disabled = true;

                item.innerHTML =
                    '<span class="item-left"><span class="item-text">' + opt.textContent + '</span></span>' +
                    '<span class="item-right"><span class="item-check">' + CHECK_SVG + '</span></span>';

                item.addEventListener('click', function (e) {
                    if (isDisabled) return;
                    e.stopPropagation();
                    select.value = opt.value;
                    labelSpan.textContent = opt.textContent.trim();
                    wrap.classList.remove('open');
                    renderOptions();
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                });

                list.appendChild(item);
            });
        }

        renderOptions();

        trigger.addEventListener('click', function (e) {
            if (select.disabled) return;
            e.stopPropagation();
            var isOpen = wrap.classList.contains('open');
            closeAllDropdowns();
            if (!isOpen) {
                wrap.classList.add('open');
            }
        });

        // Two-way sync: when select.value is modified
        select.addEventListener('change', function () {
            var opt = select.selectedOptions[0];
            if (opt) {
                labelSpan.textContent = opt.textContent.trim();
                renderOptions();
            }
        });

        // Auto-observe dynamic options insertion/deletion (e.g. AJAX populating courts or categories)
        if (window.MutationObserver) {
            var observer = new MutationObserver(function () {
                var opt = select.selectedOptions[0] || select.options[0];
                if (opt) labelSpan.textContent = opt.textContent.trim();
                trigger.disabled = select.disabled;
                renderOptions();
            });
            observer.observe(select, { childList: true, attributes: true, attributeFilter: ['disabled', 'value'] });
        }

        select.parentNode.insertBefore(wrap, select.nextSibling);

        // Instance API for programmatic control
        var instance = {
            element: select,
            wrap: wrap,
            trigger: trigger,
            refresh: function () {
                var opt = select.selectedOptions[0] || select.options[0];
                if (opt) labelSpan.textContent = opt.textContent.trim();
                renderOptions();
            },
            setValue: function (val) {
                select.value = val;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            },
            destroy: function () {
                if (observer) observer.disconnect();
                select.style.display = '';
                delete select._casaDropdown;
                delete select.casaDropdown;
                wrap.remove();
            }
        };

        select.casaDropdown = instance;

        // Auto-sync when property value is set programmatically (e.g. $el.val(...) or el.value = ...)
        try {
            var originalValueDesc = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
            if (originalValueDesc && originalValueDesc.set) {
                Object.defineProperty(select, 'value', {
                    get: function () {
                        return originalValueDesc.get.call(this);
                    },
                    set: function (newVal) {
                        originalValueDesc.set.call(this, newVal);
                        instance.refresh();
                    },
                    configurable: true
                });
            }
        } catch (e) {
            // Fallback gracefully if property cannot be redefined
        }

        return instance;
    }

    /**
     * Auto-init all <select data-casa-dropdown> in the whole page or inside a specific container (e.g. modal)
     * @param {HTMLElement|string} root Optional parent element or selector
     */
    function initAll(root) {
        var context = (typeof root === 'string' ? document.querySelector(root) : root) || document;
        if (!context) return;
        context.querySelectorAll('select[data-casa-dropdown]').forEach(initDropdown);

        // Automatically bind inner-header chip if present
        if (document.getElementById('navChipTrigger') && document.getElementById('navProfileChip')) {
            bindCustom('#navChipTrigger', '#navProfileChip');
        }
    }

    // Auto-init on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(); });
    } else {
        initAll();
    }

    // Public Generic API
    window.CasaDropdown = {
        init: initDropdown,
        enhance: initDropdown,
        bind: bindCustom,
        initAll: initAll,
        closeAll: closeAllDropdowns
    };

})(window, document);
