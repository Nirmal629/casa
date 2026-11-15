(function (window, document) {
    'use strict';

    if (window.CasaPagination) return;

    var CasaPagination = {
        /**
         * Helper to slice an array and compute pagination meta.
         *
         * @param {Array} items
         * @param {number} page
         * @param {number} perPage 0 or >= 9999 means all items
         * @returns {Object} { total, totalPages, page, perPage, startIdx, endIdx, from, to, items }
         */
        paginate: function (items, page, perPage) {
            items = Array.isArray(items) ? items : [];
            var total = items.length;
            perPage = (perPage !== undefined && perPage !== null) ? parseInt(perPage, 10) : 10;
            if (isNaN(perPage)) perPage = 10;

            if (perPage <= 0 || perPage >= 9999) {
                return {
                    total: total,
                    totalPages: 1,
                    page: 1,
                    perPage: 0,
                    startIdx: 0,
                    endIdx: total,
                    from: total > 0 ? 1 : 0,
                    to: total,
                    items: items
                };
            }

            var totalPages = total > 0 ? Math.ceil(total / perPage) : 1;
            page = parseInt(page, 10) || 1;
            if (page > totalPages) page = totalPages;
            if (page < 1) page = 1;

            var startIdx = (page - 1) * perPage;
            var endIdx = Math.min(startIdx + perPage, total);

            return {
                total: total,
                totalPages: totalPages,
                page: page,
                perPage: perPage,
                startIdx: startIdx,
                endIdx: endIdx,
                from: total > 0 ? startIdx + 1 : 0,
                to: endIdx,
                items: items.slice(startIdx, endIdx)
            };
        },

        getPageRange: function (currentPage, totalPages, onEachSide) {
            onEachSide = Math.max(1, onEachSide || 2);
            var windowSize = 1 + (onEachSide * 2) + 2; // e.g. 7

            if (totalPages <= windowSize) {
                var all = [];
                for (var i = 1; i <= totalPages; i++) all.push(i);
                return all;
            }

            var pages = [1];
            var start = Math.max(2, currentPage - onEachSide);
            var end = Math.min(totalPages - 1, currentPage + onEachSide);

            if (start > 2) pages.push('...');
            for (var j = start; j <= end; j++) pages.push(j);
            if (end < totalPages - 1) pages.push('...');
            pages.push(totalPages);

            return pages;
        },

        getPerPageSelectHtml: function (currentLimit, options) {
            options = options || {};
            var selectId = options.perPageSelectId || 'perPageSelect';
            var allowed = options.perPageOptions || [10, 25, 50, 100, 0];
            var cur = parseInt(currentLimit, 10);
            if (isNaN(cur)) cur = 10;

            var optsHtml = '';
            allowed.forEach(function (val) {
                var label = (val === 0 || val >= 9999) ? 'All' : String(val);
                var isSelected = (val === 0 || val >= 9999)
                    ? (cur === 0 || cur >= 9999)
                    : (cur === val);
                optsHtml += '<option value="' + val + '" ' + (isSelected ? 'selected' : '') + '>' + label + '</option>';
            });

            return '<label class="me-1 mb-0 fw-semibold" for="' + selectId + '">Per page:</label>' +
                   '<select id="' + selectId + '" class="form-select form-select-sm border-secondary-subtle casa-per-page-select" style="width: 80px; height: 32px;">' +
                   optsHtml +
                   '</select>';
        },

        getHtml: function (opts) {
            opts = opts || {};

            // Auto-unwrap nested PHP Pagination object response
            if (opts.pagination && typeof opts.pagination === 'object') {
                opts.total = opts.pagination.total !== undefined ? opts.pagination.total : opts.total;
                opts.perPage = opts.pagination.per_page !== undefined ? opts.pagination.per_page : (opts.pagination.perPage !== undefined ? opts.pagination.perPage : opts.perPage);
                opts.page = opts.pagination.current_page !== undefined ? opts.pagination.current_page : (opts.pagination.page !== undefined ? opts.pagination.page : opts.page);
            }

            var total = Math.max(0, parseInt(opts.total, 10) || 0);
            var limit = parseInt(opts.perPage !== undefined ? opts.perPage : opts.limit, 10);
            if (isNaN(limit)) limit = 10;
            var page = parseInt(opts.page, 10) || 1;
            var itemName = opts.itemName || opts.item_name || 'entries';
            var showPerPage = opts.showPerPage !== false && opts.show_per_page !== false;
            var onEachSide = opts.onEachSide || opts.on_each_side || 2;
            var prevText = opts.prevText || opts.prev_text || '‹ Prev';
            var nextText = opts.nextText || opts.next_text || 'Next ›';
            var ulClass = opts.ulClass || opts.ul_class ? (' ' + (opts.ulClass || opts.ul_class)) : '';
            var btnClass = opts.btnClass || opts.btn_class ? (' ' + (opts.btnClass || opts.btn_class)) : '';
            var wrapperClass = opts.wrapperClass || opts.wrapper_class || 'd-flex align-items-center justify-content-between flex-wrap gap-2 p-3 border-top bg-light text-secondary small';

            var extraDataAttrs = '';
            var dataAttrs = opts.dataAttributes || opts.data_attributes;
            if (dataAttrs && typeof dataAttrs === 'object') {
                for (var key in dataAttrs) {
                    if (Object.prototype.hasOwnProperty.call(dataAttrs, key)) {
                        extraDataAttrs += ' data-' + key + '="' + String(dataAttrs[key]).replace(/"/g, '&quot;') + '"';
                    }
                }
            }

            var perPageHtml = showPerPage ? this.getPerPageSelectHtml(limit, opts) : '';

            // Case 1: 0 items
            if (total === 0) {
                return '<div class="' + wrapperClass + '">' +
                       '  <div>Showing 0 ' + itemName + '</div>' +
                       '  <div class="d-flex align-items-center gap-2">' + perPageHtml + '</div>' +
                       '</div>';
            }

            // Case 2: Show all items (limit <= 0)
            if (limit <= 0 || limit >= 9999) {
                return '<div class="' + wrapperClass + '">' +
                       '  <div>Showing all <strong>' + total + '</strong> ' + itemName + '</div>' +
                       '  <div class="d-flex align-items-center gap-2">' + perPageHtml + '</div>' +
                       '</div>';
            }

            // Case 3: Paginated items
            var totalPages = Math.ceil(total / limit) || 1;
            if (page > totalPages) page = totalPages;
            if (page < 1) page = 1;

            var startIdx = (page - 1) * limit + 1;
            var endIdx = Math.min(page * limit, total);

            var navHtml = '<nav class="mb-0" aria-label="Pagination Navigation">' +
                          '  <ul class="pagination pagination-sm mb-0' + ulClass + ' gap-1">';

            // Prev button
            var prevDisabled = page <= 1;
            navHtml += '<li class="page-item ' + (prevDisabled ? 'disabled' : '') + '">' +
                       '  <button type="button" class="page-link py-1 px-2 rounded-2 pg-btn' + btnClass + '" data-page="' + (page - 1) + '"' + extraDataAttrs + ' ' + (prevDisabled ? 'tabindex="-1" aria-disabled="true"' : '') + '>' + prevText + '</button>' +
                       '</li>';

            // Page numbers
            var range = this.getPageRange(page, totalPages, onEachSide);
            for (var k = 0; k < range.length; k++) {
                var item = range[k];
                if (item === '...') {
                    navHtml += '<li class="page-item disabled"><span class="page-link py-1 px-2">…</span></li>';
                } else {
                    var pNum = parseInt(item, 10);
                    var isActive = pNum === page;
                    navHtml += '<li class="page-item ' + (isActive ? 'active' : '') + '" ' + (isActive ? 'aria-current="page"' : '') + '>' +
                               '  <button type="button" class="page-link py-1 px-2 rounded-2 pg-btn' + btnClass + '" data-page="' + pNum + '"' + extraDataAttrs + '>' + pNum + '</button>' +
                               '</li>';
                }
            }

            // Next button
            var nextDisabled = page >= totalPages;
            navHtml += '<li class="page-item ' + (nextDisabled ? 'disabled' : '') + '">' +
                       '  <button type="button" class="page-link py-1 px-2 rounded-2 pg-btn' + btnClass + '" data-page="' + (page + 1) + '"' + extraDataAttrs + ' ' + (nextDisabled ? 'tabindex="-1" aria-disabled="true"' : '') + '>' + nextText + '</button>' +
                       '</li>';

            navHtml += '  </ul>' +
                       '</nav>';

            return '<div class="' + wrapperClass + '">' +
                   '  <div>Showing <strong>' + startIdx + '–' + endIdx + '</strong> of <strong>' + total + '</strong> ' + itemName + '</div>' +
                   '  <div class="d-flex align-items-center gap-2">' +
                   perPageHtml +
                   navHtml +
                   '  </div>' +
                   '</div>';
        },

        bind: function (container, onPageChange, onPerPageChange) {
            var el = typeof container === 'string' ? document.querySelector(container) : container;
            if (!el) return null;

            if (!el._casaPaginationBound) {
                el._casaPaginationBound = true;

                el.addEventListener('click', function (e) {
                    var btn = e.target.closest('.pg-btn') || e.target.closest('.page-link[data-page]');
                    if (!btn || btn.parentElement.classList.contains('disabled')) return;
                    e.preventDefault();

                    var targetPage = parseInt(btn.dataset.page, 10);
                    if (targetPage > 0 && typeof onPageChange === 'function') {
                        onPageChange(targetPage, btn);
                    }
                    el.dispatchEvent(new CustomEvent('page-change', { detail: { page: targetPage, target: btn } }));
                });

                el.addEventListener('change', function (e) {
                    var select = e.target.closest('.casa-per-page-select') || e.target.closest('select[id*="pagesize"], select[id*="perPage"]');
                    if (!select) return;

                    var newLimit = parseInt(select.value, 10);
                    if (typeof onPerPageChange === 'function') {
                        onPerPageChange(newLimit, select);
                    }
                    el.dispatchEvent(new CustomEvent('per-page-change', { detail: { perPage: newLimit, target: select } }));
                });
            }
            return el;
        },

        render: function (container, options) {
            var el = typeof container === 'string' ? document.querySelector(container) : container;
            if (!el) return null;

            options = options || {};
            el.innerHTML = this.getHtml(options);
            el._casaPaginationOptions = options;

            this.bind(el, function (targetPage, btn) {
                var opts = el._casaPaginationOptions || {};
                if (typeof opts.onPageChange === 'function') {
                    opts.onPageChange(targetPage, btn);
                }
            }, function (newLimit, select) {
                var opts = el._casaPaginationOptions || {};
                if (typeof opts.onPerPageChange === 'function') {
                    opts.onPerPageChange(newLimit, select);
                }
            });

            return el;
        }
    };

    window.CasaPagination = CasaPagination;
    window.Casa = window.Casa || {};
    window.Casa.Pagination = CasaPagination;
    window.Pagination = window.Pagination || CasaPagination;

})(window, document);
