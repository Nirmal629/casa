/**
 * Generic User Activity Tracking Script - Location: app/script/activity-tracker.js
 * Automatically tracks tab, sub-tab, modal, store, cart, and payment actions across the platform.
 * Emits non-blocking logs to /api/log_tab_activity.php for ca_player_logs.
 */
(function () {
    'use strict';

    if (window.__casaActivityTrackerInitialized) {
        return;
    }
    window.__casaActivityTrackerInitialized = true;
    var lastLoggedKey = '';
    var lastLoggedTime = 0;
    var DEBOUNCE_MS = 500;

    function getApiEndpoint() {
        var baseUrl = (typeof BASE_URL !== 'undefined' && BASE_URL) ? BASE_URL : '';
        if (baseUrl && !baseUrl.endsWith('/')) {
            baseUrl += '/';
        }
        return baseUrl + 'api/log_tab_activity.php';
    }

    function cleanText(raw) {
        if (!raw) return '';
        var text = raw.replace(/[\r\n\t]+/g, ' ').replace(/\s+/g, ' ').trim();
        return text.length > 80 ? text.substring(0, 80) : text;
    }

    function getQueryParam(param) {
        try {
            var urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(param) || '';
        } catch (e) {
            return '';
        }
    }

    function sendActivityLog(data) {
        var page = window.location.pathname.split('/').pop() || 'index.php';
        data.page = page;

        var key = (data.action_type || '') + '|' + (data.main_tab || '') + '|' + (data.nested_tab || '') + '|' + (data.game_id || '') + '|' + (data.entity_name || '') + '|' + page;
        var now = Date.now();

        if (key === lastLoggedKey && (now - lastLoggedTime) < DEBOUNCE_MS) {
            return;
        }
        lastLoggedKey = key;
        lastLoggedTime = now;

        var endpoint = getApiEndpoint();
        var formData = new FormData();
        for (var k in data) {
            if (data[k] !== null && data[k] !== undefined && data[k] !== '') {
                formData.append(k, data[k]);
            }
        }

        if (navigator.sendBeacon) {
            try {
                navigator.sendBeacon(endpoint, formData);
                return;
            } catch (e) {}
        }

        if (window.fetch) {
            fetch(endpoint, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            }).catch(function () {});
        }
    }

    function extractElementLabel(el) {
        if (!el) return '';
        if (el.dataset && el.dataset.tabName) return cleanText(el.dataset.tabName);
        if (el.dataset && el.dataset.tab) return cleanText(el.dataset.tab);

        var span = el.querySelector('span');
        if (span && span.textContent.trim()) {
            return cleanText(span.textContent);
        }

        if (el.getAttribute('aria-label')) return cleanText(el.getAttribute('aria-label'));
        if (el.getAttribute('title')) return cleanText(el.getAttribute('title'));

        var clone = el.cloneNode(true);
        var svgs = clone.querySelectorAll('svg, i, img');
        svgs.forEach(function (s) { s.remove(); });
        return cleanText(clone.textContent);
    }

    function findParentTabName(el) {
        if (!el) return '';
        var parentPane = el.closest('.tab-pane, [role="tabpanel"], [data-main-tab]');
        if (!parentPane) return '';

        if (parentPane.dataset && parentPane.dataset.mainTab) {
            return cleanText(parentPane.dataset.mainTab);
        }

        var paneId = parentPane.id;
        if (paneId) {
            var triggerBtn = document.querySelector('[data-bs-target="#' + paneId + '"], [data-target="#' + paneId + '"], [aria-controls="' + paneId + '"], #' + paneId + '-tab');
            if (triggerBtn) {
                return extractElementLabel(triggerBtn);
            }
            return cleanText(paneId);
        }

        return '';
    }

    function handleLedgerClick(el) {
        var playerId = (el.dataset && (el.dataset.id || el.dataset.userId || el.dataset.user)) || '';
        var year = (document.getElementById('hpyear') ? document.getElementById('hpyear').value : '') || '';
        var month = (document.getElementById('hpmonth') ? document.getElementById('hpmonth').value : '') || '';

        var row = el.closest('tr');
        var playerName = '';
        if (row) {
            var nameElem = row.querySelector('strong, .player-name');
            if (nameElem) playerName = cleanText(nameElem.textContent);
        }

        var mainTab = 'Payment';
        var nestedTab = el.classList.contains('pay-ledger-full') ? 'Full Ledger' : 'Monthly View Ledger';
        var entityName = playerName ? ('Player: ' + playerName) : (playerId ? ('Player ID: ' + playerId) : '');
        if (year || month) {
            entityName += (entityName ? ' ' : '') + '(Year: ' + (year || 'All') + ', Month: ' + (month || 'All') + ')';
        }

        sendActivityLog({
            main_tab: mainTab,
            nested_tab: nestedTab,
            action_type: 'MODAL_VIEW',
            game_id: '',
            host_id: getQueryParam('host_id'),
            entity_name: entityName
        });
    }

    function handlePaymentButtonClick(el) {
        var actionType = 'PAYMENT_ACTION';
        var nestedTab = 'Payment Action';
        var pId = (el.dataset && (el.dataset.id || el.dataset.paymentId || el.dataset.user)) || '';
        var gId = (el.dataset && el.dataset.id) || '';
        var year = (el.dataset && el.dataset.year) || (document.getElementById('hpyear') ? document.getElementById('hpyear').value : '');
        var month = (el.dataset && el.dataset.month) || (document.getElementById('hpmonth') ? document.getElementById('hpmonth').value : '');
        var due = (el.dataset && el.dataset.due) || '';

        if (el.classList.contains('reinitiateBtnnn')) {
            actionType = 'REINITIATE_CLICKED';
            nestedTab = 'Re-initiate Payment';
        } else if (el.classList.contains('payBtnnn') || el.classList.contains('pay-action-pay')) {
            actionType = 'PAYMENT_CLICKED';
            nestedTab = 'Pay / Repay Button';
        } else if (el.classList.contains('approveBtnnn')) {
            actionType = 'APPROVE_CLICKED';
            nestedTab = 'Approve Payment';
        } else if (el.classList.contains('rejectBtnnn')) {
            actionType = 'REJECT_CLICKED';
            nestedTab = 'Reject Payment';
        } else if (el.classList.contains('rollbackBtnnn') || el.classList.contains('pay-action-rollback-carry')) {
            actionType = 'ROLLBACK_CLICKED';
            nestedTab = 'Rollback Payment';
        } else if (el.classList.contains('pay-payment-cancel') || el.classList.contains('pay-expense-cancel') || el.classList.contains('playPaymentModal_close')) {
            actionType = 'PAYMENT_CANCELLED';
            nestedTab = 'Cancel / Close Payment';
        }

        var details = [];
        if (gId) details.push('Game ID: ' + gId);
        if (pId) details.push('Player/Payment ID: ' + pId);
        if (due) details.push('Due: $' + due);
        if (year || month) details.push('Month: ' + (month || 'All') + '/' + (year || 'All'));

        sendActivityLog({
            main_tab: 'Payment',
            nested_tab: nestedTab,
            action_type: actionType,
            game_id: gId,
            host_id: getQueryParam('host_id'),
            entity_name: details.join(', ')
        });
    }

    function handleStoreProductClick(el) {
        var prodCard = el.closest('.pl-card, .storeproduct_card');
        var name = '';
        var price = '';
        if (prodCard) {
            var nameElem = prodCard.querySelector('.pl-name, .name, h3, h4');
            if (nameElem) name = cleanText(nameElem.textContent);
            var priceElem = prodCard.querySelector('.pl-price-current, .amount, .price');
            if (priceElem) price = cleanText(priceElem.textContent);
        }

        var isAddBtn = el.classList.contains('pl-add-btn') || el.classList.contains('addtocart_btn');
        var actionType = isAddBtn ? 'ADD_TO_CART_CLICKED' : 'PRODUCT_VIEW_CLICKED';
        var nestedTab = isAddBtn ? 'Add to Cart Button' : 'Product Card View';

        sendActivityLog({
            main_tab: 'Store',
            nested_tab: nestedTab,
            action_type: actionType,
            entity_name: name ? (name + (price ? ' (' + price + ')' : '')) : 'Product Card'
        });
    }

    function handleTabClick(el, isBsTabEvent) {
        if (!el) return;

        if (el.classList.contains('playPaymentModal_open') || el.classList.contains('pay-ledger-full')) {
            handleLedgerClick(el);
            return;
        }

        if (el.classList.contains('reinitiateBtnnn') || el.classList.contains('payBtnnn') ||
            el.classList.contains('approveBtnnn') || el.classList.contains('rejectBtnnn') ||
            el.classList.contains('rollbackBtnnn') || el.classList.contains('pay-action-pay') ||
            el.classList.contains('pay-action-rollback-carry') || el.classList.contains('pay-payment-cancel') ||
            el.classList.contains('playPaymentModal_close')) {
            handlePaymentButtonClick(el);
            return;
        }

        if (el.classList.contains('pl-card') || el.classList.contains('storeproduct_card') ||
            el.classList.contains('pl-add-btn') || el.closest('.pl-card, .storeproduct_card')) {
            handleStoreProductClick(el);
            return;
        }

        var label = extractElementLabel(el);
        if (!label) return;

        var parentTab = findParentTabName(el);
        var mainTab = '';
        var nestedTab = '';

        if (parentTab && parentTab.toLowerCase() !== label.toLowerCase()) {
            mainTab = parentTab;
            nestedTab = label;
        } else {
            mainTab = label;
            nestedTab = '';
        }

        var gameId = (el.dataset && (el.dataset.gameId || el.dataset.gid || el.dataset.id)) || '';
        if (!gameId) {
            var closestGame = el.closest('[data-game-id], [data-gid]');
            if (closestGame) {
                gameId = closestGame.dataset.gameId || closestGame.dataset.gid || '';
            }
        }
        if (!gameId) {
            gameId = getQueryParam('game_id') || getQueryParam('gid') || getQueryParam('id');
        }

        var hostId = (el.dataset && el.dataset.hostId) || '';
        if (!hostId) {
            var closestHost = el.closest('[data-host-id]');
            if (closestHost) hostId = closestHost.dataset.hostId || '';
        }
        if (!hostId) {
            hostId = getQueryParam('host_id');
        }

        var entityName = (el.dataset && (el.dataset.clubName || el.dataset.entityName || el.dataset.title)) || '';
        var actionType = isBsTabEvent ? (nestedTab ? 'SUB_TAB_CLICK' : 'TAB_CLICK') : '';
        if (el.dataset && (el.dataset.bsToggle === 'modal' || el.dataset.toggle === 'modal')) {
            actionType = 'MODAL_VIEW';
        }

        sendActivityLog({
            main_tab: mainTab,
            nested_tab: nestedTab,
            action_type: actionType,
            game_id: gameId,
            host_id: hostId,
            entity_name: entityName
        });
    }

    document.addEventListener('shown.bs.tab', function (e) {
        if (e.target) {
            handleTabClick(e.target, true);
        }
    });

    document.addEventListener('change', function (e) {
        var target = e.target;
        if (!target) return;

        if (target.classList.contains('pay-ledger-year') || target.classList.contains('pay-ledger-month')) {
            var yearSelect = target.closest('.pay-ledger-filter')?.querySelector('.pay-ledger-year');
            var monthSelect = target.closest('.pay-ledger-filter')?.querySelector('.pay-ledger-month');
            var yr = yearSelect ? yearSelect.value : '';
            var mo = monthSelect ? monthSelect.value : '';
            var uId = (target.dataset && target.dataset.user) || '';

            sendActivityLog({
                main_tab: 'Payment',
                nested_tab: 'Monthly Ledger Filter',
                action_type: 'SUB_TAB_CLICK',
                entity_name: 'Filter: Year=' + (yr || 'All') + ', Month=' + (mo || 'All') + (uId ? ', Player=' + uId : '')
            });
        }
    });

    document.addEventListener('click', function (e) {
        var target = e.target;
        if (!target) return;

        var tabElem = target.closest(
            '[data-bs-toggle="tab"], [data-toggle="tab"], [role="tab"], ' +
            '.nav-stacked-pills .nav-link, .nav-pills .nav-link, .nav-tabs .nav-link, ' +
            '.btn-stacked-synced, .club-info-btn, .club-cost-btn, .club-schedule-btn, .action-view, ' +
            '.playPaymentModal_open, .pay-ledger-full, #hpfilter, ' +
            '.reinitiateBtnnn, .payBtnnn, .approveBtnnn, .rejectBtnnn, .rollbackBtnnn, ' +
            '.pay-action-pay, .pay-action-rollback-carry, .pay-payment-cancel, .playPaymentModal_close, ' +
            '.pl-card, .storeproduct_card, .pl-add-btn, ' +
            '[data-bs-toggle="modal"], [data-toggle="modal"], [data-tab-name]'
        );

        if (tabElem) {
            handleTabClick(tabElem, false);
        }
    }, true);

})();
