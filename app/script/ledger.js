/**
 * Casa Financial Ledger Client
 * Shared controller for Host and Player ledger UI, viewer endpoints, and action endpoints.
 */
(function (window, $) {
    'use strict';

    if (!$) {
        return;
    }

    if (window.CasaLedger && window.CasaLedger.initialized) {
        return;
    }

    const CasaLedger = {
        initialized: false,
        activeRequestId: 0,
        latestRenderedSeq: 0,
        inFlightAction: false,

        // Core URL helper
        getUrl: function (endpoint) {
            const clean = endpoint.replace(/^\/+/, '');
            if (typeof window.BASE_URL !== 'undefined' && window.BASE_URL !== '') {
                return window.BASE_URL.endsWith('/') ? window.BASE_URL + clean : window.BASE_URL + '/' + clean;
            }
            if (typeof window.ABSOLUTE_BASE_URL !== 'undefined' && window.ABSOLUTE_BASE_URL !== '') {
                return window.ABSOLUTE_BASE_URL.endsWith('/') ? window.ABSOLUTE_BASE_URL + clean : window.ABSOLUTE_BASE_URL + '/' + clean;
            }
            const scripts = document.getElementsByTagName('script');
            for (let i = 0; i < scripts.length; i++) {
                const src = scripts[i]?.src || '';
                if (src.includes('app/script/')) {
                    const base = src.substring(0, src.indexOf('app/script/'));
                    window.BASE_URL = base;
                    return base.endsWith('/') ? base + clean : base + '/' + clean;
                }
            }
            return '/' + clean;
        },

        // Toast or notification fallback without native alert
        notify: function (message, type) {
            type = type || 'info';
            if (typeof window.showToast === 'function') {
                window.showToast(message, type);
            } else if (window.toastr && typeof window.toastr[type] === 'function') {
                window.toastr[type](message);
            } else if (typeof window.showAlert === 'function') {
                window.showAlert(message, type);
            }
        },

        // Modern confirm modal helper
        confirm: function (options) {
            if (window.Casa && typeof window.Casa.confirmModal === 'function') {
                return window.Casa.confirmModal(options);
            }
            if (typeof window.confirmAction === 'function') {
                return window.confirmAction(options);
            }
            return Promise.resolve(true);
        },

        // Safe JSON parsing
        parseJson: function (data) {
            if (typeof data === 'object' && data !== null) return data;
            try {
                return JSON.parse(data);
            } catch (e) {
                return null;
            }
        },

        // Button state management to prevent double submissions
        setButtonLoading: function ($btn, loading, text) {
            if (!$btn || !$btn.length) return;
            if (loading) {
                $btn.data('orig-html', $btn.html());
                $btn.prop('disabled', true).addClass('disabled');
                if (text) $btn.html(text);
            } else {
                $btn.prop('disabled', false).removeClass('disabled');
                const orig = $btn.data('orig-html');
                if (orig) $btn.html(orig);
            }
        },

        // Locate ledger container safely across host modals and player dashboard cards
        getContainer: function ($elem) {
            if ($elem && $elem.length) {
                const $modal = $elem.closest('.playPaymentModal, .customModal_wrap');
                if ($modal.length) {
                    const $modalContent = $modal.find('.customModal_content .patmentTb, .patmentTb').first();
                    if ($modalContent.length) return $modalContent;
                }
                const $card = $elem.closest('.monthly-ledger-card');
                if ($card.length) {
                    const $cardTb = $card.find('.patmentTb').first();
                    if ($cardTb.length) return $cardTb;
                }
            }
            const $openModal = $('.playPaymentModal.open, .playPaymentModal');
            if ($openModal.length && $openModal.is(':visible')) {
                const $modalTb = $openModal.find('.customModal_content .patmentTb, .patmentTb').first();
                if ($modalTb.length) return $modalTb;
            }
            if ($('#modalContainer').length && !$('#modalContainer').hasClass('hidden') && $('#modalBody').is(':visible')) {
                return $('#modalBody');
            }
            const $playerTb = $('.monthly-ledger-card .patmentTb').first();
            if ($playerTb.length) {
                return $playerTb;
            }
            return $('.customModal_content .patmentTb, .patmentTb').first();
        },

        // Context extractor (playerId, hostId, year, month)
        getContext: function ($elem) {
            const $wrap = $elem ? $elem.closest('.pay-history-wrapper, .monthly-ledger-card, .playPaymentModal, .PayAmountModal') : null;
            
            let userId = ($elem && ($elem.attr('data-user') || $elem.data('user')))
                || ($wrap && ($wrap.find('#user_id').val() || $wrap.find('input[name="user_id"]').val()))
                || $('#user_id').val()
                || $('#hhost').val()
                || '';

            let hostId = ($elem && ($elem.attr('data-host') || $elem.data('host')))
                || ($wrap && ($wrap.find('#payhost').val() || $wrap.find('input[name="host_id"]').val()))
                || $('#payhost').val()
                || $('#hhost').val()
                || '';

            let year = ($elem && ($elem.attr('data-year') || $elem.data('year')))
                || ($wrap && ($wrap.find('.pay-ledger-year').val() || $wrap.find('#directLedgerYear').val() || $wrap.find('#payyear').val()))
                || $('#directLedgerYear').val()
                || $('#payyear').val()
                || $('#hpyear').val()
                || new Date().getFullYear();

            let month = ($elem && ($elem.attr('data-month') || $elem.data('month')))
                || ($wrap && ($wrap.find('.pay-ledger-month').val() || $wrap.find('#directLedgerMonth').val() || $wrap.find('#paymonth').val()))
                || $('#directLedgerMonth').val()
                || $('#paymonth').val()
                || $('#hpmonth').val()
                || (new Date().getMonth() + 1);

            return {
                user_id: parseInt(userId, 10) || 0,
                host_id: parseInt(hostId, 10) || 0,
                year: parseInt(year, 10) || new Date().getFullYear(),
                month: parseInt(month, 10) || (new Date().getMonth() + 1)
            };
        },

        // Shared Monthly Ledger Fetch with Stale-Response Protection
        loadMonthlyLedger: function (playerId, year, month, callback, $triggerElem) {
            const thisReqId = ++CasaLedger.activeRequestId;
            const $container = CasaLedger.getContainer($triggerElem);

            CasaLedger.currentView = 'monthly';
            if (playerId) {
                CasaLedger.currentContext = {
                    playerId: parseInt(playerId, 10),
                    year: parseInt(year, 10),
                    month: parseInt(month, 10)
                };
            }

            if ($container.length) {
                $container.html('<div class="d-flex align-items-center justify-content-center py-5"><div class="spinner-border text-teal-600" role="status"><span class="visually-hidden">Loading...</span></div></div>');
            }

            const hostId = $('#payhost').val() || $('#hhost').val() || '';
            const isPlayerSide = ($('#directLedgerMonth').length || $('.monthly-ledger-card').length) ? '1' : '';
            const url = CasaLedger.getUrl('api/ledger/monthly') + `?player_id=${encodeURIComponent(playerId || '')}&host_id=${encodeURIComponent(hostId)}&year=${encodeURIComponent(year)}&month=${encodeURIComponent(month)}&is_player_side=${isPlayerSide}`;

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                headers: { 'Accept': 'application/json' }
            }).done(function (res) {
                // Drop stale response if a newer request was dispatched or completed
                if (thisReqId !== CasaLedger.activeRequestId || thisReqId < CasaLedger.latestRenderedSeq) {
                    return;
                }

                // Verify response strictly matches current active UI context if trigger element is still in DOM
                if ($triggerElem && $triggerElem.length && $.contains(document, $triggerElem[0])) {
                    const currentCtx = CasaLedger.getContext($triggerElem);
                    const reqYear = parseInt(year, 10);
                    const reqMonth = parseInt(month, 10);
                    const reqPlayerId = parseInt(playerId, 10) || 0;

                    const resYear = (res && res.data && res.data.year) ? parseInt(res.data.year, 10) : reqYear;
                    const resMonth = (res && res.data && res.data.month) ? parseInt(res.data.month, 10) : reqMonth;
                    const resPlayerId = (res && res.data && res.data.player_id) ? parseInt(res.data.player_id, 10) : reqPlayerId;

                    if (currentCtx.year !== resYear || currentCtx.month !== resMonth) {
                        return;
                    }
                    if (resPlayerId > 0 && currentCtx.user_id > 0 && currentCtx.user_id !== resPlayerId) {
                        return;
                    }
                }

                CasaLedger.latestRenderedSeq = thisReqId;

                if (res && res.success) {
                    CasaLedger.currentView = 'monthly';
                    $('.playPaymentModal_close, .payment-history-modal-close, .customModal_close')
                        .removeClass('pay-modal-is-back')
                        .attr('title', 'Close')
                        .html('<i class="fa-solid fa-xmark"></i>');
                    if (res.html && $container.length) {
                        $container.html(res.html);
                        if (window.CasaDropdown && typeof window.CasaDropdown.initAll === 'function') {
                            window.CasaDropdown.initAll($container[0]);
                        }
                    }
                    if (typeof callback === 'function') {
                        callback(null, res);
                    }
                } else {
                    const msg = (res && res.message) || 'Failed to load ledger.';
                    if ($container.length) {
                        $container.html(`<div class="text-center py-5 text-danger font-medium"><i class="fa-solid fa-triangle-exclamation me-2"></i> ${msg}</div>`);
                    }
                    if (typeof callback === 'function') {
                        callback(new Error(msg));
                    }
                }
            }).fail(function (xhr) {
                if (thisReqId !== CasaLedger.activeRequestId || thisReqId < CasaLedger.latestRenderedSeq) {
                    return;
                }

                if ($triggerElem && $triggerElem.length && $.contains(document, $triggerElem[0])) {
                    const currentCtx = CasaLedger.getContext($triggerElem);
                    const reqYear = parseInt(year, 10);
                    const reqMonth = parseInt(month, 10);
                    const reqPlayerId = parseInt(playerId, 10) || 0;

                    if (currentCtx.year !== reqYear || currentCtx.month !== reqMonth) {
                        return;
                    }
                    if (reqPlayerId > 0 && currentCtx.user_id > 0 && currentCtx.user_id !== reqPlayerId) {
                        return;
                    }
                }

                CasaLedger.latestRenderedSeq = thisReqId;
                const errData = CasaLedger.parseJson(xhr.responseText);
                const msg = (errData && errData.message) || 'Failed to load ledger from server.';
                if ($container.length) {
                    $container.html(`<div class="text-center py-5 text-danger font-medium"><i class="fa-solid fa-triangle-exclamation me-2"></i> ${msg}</div>`);
                }
                if (typeof callback === 'function') {
                    callback(new Error(msg));
                }
            });
        },

        // Complete Continuous Chronological Ledger History Fetch
        loadFullLedger: function (playerId, page, pageSize, callback, $triggerElem) {
            const thisReqId = ++CasaLedger.activeRequestId;
            const $container = CasaLedger.getContainer($triggerElem);

            if ($container.length) {
                $container.html('<div class="d-flex align-items-center justify-content-center py-5"><div class="spinner-border text-teal-600" role="status"><span class="visually-hidden">Loading full ledger...</span></div></div>');
            }

            CasaLedger.currentView = 'full';
            if (playerId) {
                if (!CasaLedger.currentContext) CasaLedger.currentContext = {};
                CasaLedger.currentContext.playerId = parseInt(playerId, 10);
            }

            const hostId = $('#payhost').val() || $('#hhost').val() || '';
            const url = CasaLedger.getUrl('api/ledger/history');

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                data: {
                    player_id: playerId || (CasaLedger.currentContext ? CasaLedger.currentContext.playerId : 0),
                    host_id: hostId,
                    page: page || 1,
                    pageSize: pageSize || 20,
                    per_page: pageSize || 20
                },
                headers: { 'Accept': 'application/json' }
            }).done(function (res) {
                if (thisReqId !== CasaLedger.activeRequestId || thisReqId < CasaLedger.latestRenderedSeq) {
                    return;
                }
                CasaLedger.latestRenderedSeq = thisReqId;

                if (res && res.success) {
                    CasaLedger.currentView = 'full';
                    if (res.html && $container.length) {
                        $container.html(res.html);
                    }
                    if (res.data && res.data.player_name) {
                        $('.customModal_head').text('View History - ' + res.data.player_name);
                    }
                    // In place of the cross icon, show Back button!
                    $('.playPaymentModal_close, .payment-history-modal-close, .customModal_close')
                        .addClass('pay-modal-is-back')
                        .attr('title', 'Back to Financial Ledger')
                        .html('<i class="fa-solid fa-arrow-left"></i>');
                    if (typeof callback === 'function') {
                        callback(null, res);
                    }
                } else {
                    const msg = (res && res.message) || 'Failed to load transaction history.';
                    if ($container.length) {
                        $container.html(`<div class="text-center py-5 text-danger font-medium"><i class="fa-solid fa-triangle-exclamation me-2"></i> ${msg}</div>`);
                    }
                    CasaLedger.notify(msg, 'error');
                    if (typeof callback === 'function') {
                        callback(new Error(msg));
                    }
                }
            }).fail(function (xhr) {
                const err = CasaLedger.parseJson(xhr.responseText);
                const msg = (err && err.message) || 'Error loading transaction history.';
                if ($container.length) {
                    $container.html(`<div class="text-center py-5 text-danger font-medium"><i class="fa-solid fa-triangle-exclamation me-2"></i> ${msg}</div>`);
                }
                CasaLedger.notify(msg, 'error');
                if (typeof callback === 'function') {
                    callback(err || new Error(msg));
                }
            });
        },

        // Helper to refresh current visible ledger
        refreshCurrentLedger: function (context, $triggerElem) {
            context = context || CasaLedger.getContext($triggerElem);
            CasaLedger.loadMonthlyLedger(context.user_id, context.year, context.month, null, $triggerElem);
        },

        // Safely apply action response to visible ledger without period mismatch or stale overwrites
        applyActionResponse: function (res, ctx, defaultMsg, $triggerElem, reqSeq) {
            if (!res || !res.success) {
                const msg = (res && res.message) || defaultMsg || 'Action failed.';
                CasaLedger.notify(msg, 'error');
                return;
            }

            CasaLedger.hostListDirty = true;
            CasaLedger.notify(res.message || defaultMsg || 'Action completed successfully.', 'success');

            // Sequence check: ignore stale action responses that arrived out of order
            if (reqSeq && reqSeq < CasaLedger.latestRenderedSeq) {
                return;
            }
            if (reqSeq) {
                CasaLedger.latestRenderedSeq = reqSeq;
            }

            const currentCtx = CasaLedger.getContext($triggerElem);
            const isMatchingPeriod = (
                (!ctx.user_id || !currentCtx.user_id || ctx.user_id === currentCtx.user_id) &&
                ctx.year === currentCtx.year &&
                ctx.month === currentCtx.month
            );

            if (isMatchingPeriod) {
                const $container = CasaLedger.getContainer($triggerElem);
                if (res.html && $container.length) {
                    $container.html(res.html);
                    if (window.CasaDropdown && typeof window.CasaDropdown.initAll === 'function') {
                        window.CasaDropdown.initAll($container[0]);
                    }
                } else {
                    CasaLedger.refreshCurrentLedger(currentCtx, $triggerElem);
                }
            } else {
                // The user changed period while request was in-flight; refresh current visible period instead of overwriting with stale period
                CasaLedger.refreshCurrentLedger(currentCtx, $triggerElem);
            }
        },

        // Bind all event delegations
        bindEvents: function () {
            // Unbind existing ledger-namespaced events to prevent duplicate registrations
            $(document).off('.casaledger');

            // --- 0. View Ledger Modal (Host & Player) ---
            $(document).on('click.casaledger', '.playPaymentModal_open', function (e) {
                e.preventDefault();
                const $btn = $(this);
                const playerId = $btn.attr('data-id') || $btn.data('id') || $btn.closest('tr').find('[data-user-id]').attr('data-user-id') || 0;
                let year = $("#hpyear").val() || $btn.attr('data-year') || new Date().getFullYear();
                let month = $("#hpmonth").val() || $btn.attr('data-month') || (new Date().getMonth() + 1);

                if (!playerId) {
                    CasaLedger.notify('Could not identify player ID.', 'error');
                    return;
                }

                $('.playPaymentModal').addClass('open');
                $('.customModal_head').text('View History');

                const $container = CasaLedger.getContainer();
                if ($container.length) {
                    $container.html('<div class="d-flex align-items-center justify-content-center py-5"><div class="spinner-border text-teal-600" role="status"><span class="visually-hidden">Loading...</span></div></div>');
                }

                CasaLedger.loadMonthlyLedger(playerId, year, month, function (err, res) {
                    if (!err && res && res.data && res.data.player_name) {
                        $('.customModal_head').text('View History - ' + res.data.player_name);
                    }
                });
            });

            $(document).on('click.casaledger', '.playPaymentModal_close, .payment-history-modal-close', function (e) {
                e.preventDefault();
                // If user is currently viewing the Full Ledger (1st screen), back returns directly to Financial Ledger (2nd screen)!
                if (CasaLedger.currentView === 'full') {
                    const ctx = CasaLedger.currentContext || {};
                    const playerId = ctx.playerId || $(this).data('user') || 0;
                    const year = ctx.year || new Date().getFullYear();
                    const month = ctx.month || (new Date().getMonth() + 1);
                    if (playerId) {
                        CasaLedger.loadMonthlyLedger(playerId, year, month, function (err, res) {
                            if (!err && res && res.data && res.data.player_name) {
                                $('.customModal_head').text('View History - ' + res.data.player_name);
                            }
                        });
                        return;
                    }
                }

                // If on monthly ledger (2nd screen), close the modal completely!
                $('.playPaymentModal, .hostgameview_modal_payment').removeClass('open');
                $('.customModal_head').text('View History');
                $('.playPaymentModal_close, .payment-history-modal-close, .customModal_close')
                    .removeClass('pay-modal-is-back')
                    .attr('title', 'Close')
                    .html('<i class="fa-solid fa-xmark"></i>');
                CasaLedger.currentView = 'closed';
                if (CasaLedger.hostListDirty && window.CasaHost && window.CasaHost.DashboardFilters) {
                    CasaLedger.hostListDirty = false;
                    const filters = window.CasaHost.DashboardFilters;
                    filters.loadHostPaymentTable(filters._paymentTablePage || 1);
                }
            });

            // --- 0B. Payment History / Logs Modal (Host & Player) ---
            $(document).on('click.casaledger', '.pay-history-detail-btn, .pay-reinitiated-badge', function (e) {
                e.preventDefault();
                const $elem = $(this).closest('.pay-reinitiated-badge, .pay-history-detail-btn');
                const paymentId = $elem.attr('data-payment-id') || $elem.data('payment-id');
                const ctx = CasaLedger.getContext($elem);
                const userId = $elem.attr('data-user') || $elem.data('user') || ctx.user_id || $("#user_id").val() || '';

                if (!paymentId) return;

                const $modalPayment = $('.hostgameview_modal_payment');
                if ($modalPayment.length > 0) {
                    $modalPayment.find('#playerList_payment').html('<div class="d-flex align-items-center justify-content-center py-5"><div class="spinner-border text-teal-600" role="status"><span class="visually-hidden">Loading...</span></div></div>');
                    $modalPayment.addClass('open');
                }

                $.ajax({
                    type: "POST",
                    url: CasaLedger.getUrl('api/view_player_paymentHis.php'),
                    data: { user_id: userId, payment_id: paymentId },
                    success: function (response) {
                        if ($("#modalContainer").length > 0) {
                            if ($("#modalTitle").length) $("#modalTitle").text("Payment & Approval History");
                            if ($("#modalBody").length) $("#modalBody").html(response);
                            $("#modalContainer").removeClass("hidden");
                        } else if ($(".hostgameview_modal_payment").length > 0) {
                            $(".hostgameview_modal_payment #playerList_payment").html(response);
                            $('.hostgameview_modal_payment').addClass('open');
                        } else {
                            $('#payHistoryModalShell').remove();
                            $('body').append('<div id="payHistoryModalShell" class="modal fade show" style="display:block; background:rgba(15,23,42,0.65); backdrop-filter:blur(4px); z-index:9999;"><div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1050px; width: 95%;"><div class="modal-content border-0" style="border-radius:16px; overflow:hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);"><div class="modal-header d-flex justify-content-between align-items-center py-3 px-4" style="background:#f8fafc; border-bottom:1px solid #e2e8f0;"><h5 class="modal-title m-0 font-bold" style="color:#0f172a; font-size:1.15rem; display:flex; align-items:center; gap:8px;"><i class="fa-solid fa-clock-rotate-left text-teal-600"></i> Payment & Approval History</h5><button type="button" class="btn p-0 border-0 shadow-none bg-transparent" onclick="$(\'#payHistoryModalShell\').remove()" style="font-size:1.25rem; color:#64748b; line-height:1; opacity:0.8;"><i class="fa-solid fa-xmark"></i></button></div><div class="modal-body p-4" id="payHistoryModalShellBody"></div></div></div></div>');
                            $('#payHistoryModalShellBody').html(response);
                        }
                    },
                    error: function (xhr) {
                        const errMsg = 'Failed to load payment history.';
                        if ($(".hostgameview_modal_payment").length > 0) {
                            $(".hostgameview_modal_payment #playerList_payment").html('<div class="text-danger p-3 text-center">' + errMsg + '</div>');
                        } else {
                            CasaLedger.notify(errMsg, 'error');
                        }
                    }
                });
            });

            // Close Payment & Approval History Modal
            $(document).on('click.casaledger', '.payment-history-modal-close, .hostgameview_modal_payment .customModal_close', function (e) {
                e.preventDefault();
                $('.hostgameview_modal_payment').removeClass('open');
            });

            $(document).on('click.casaledger', '.hostgameview_modal_payment', function (e) {
                if ($(e.target).hasClass('hostgameview_modal_payment')) {
                    $(this).removeClass('open');
                }
            });

            // --- 1. Approve Payment ---
            $(document).on('click.casaledger', '.approveBtnnn', function (e) {
                e.preventDefault();
                const $btn = $(this);
                if ($btn.prop('disabled')) return;

                const paymentId = $btn.attr('data-payment-id') || $btn.data('payment-id') || 0;
                const gameId = $btn.data('id') || 0;
                const ctx = CasaLedger.getContext($btn);

                const reqSeq = ++CasaLedger.activeRequestId;
                CasaLedger.setButtonLoading($btn, true, '<i class="fa-solid fa-spinner fa-spin"></i>');

                const endpoint = paymentId > 0 
                    ? CasaLedger.getUrl(`api/payments/${paymentId}/approve`)
                    : CasaLedger.getUrl('api/payment_action.php');

                $.ajax({
                    url: endpoint,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'approve',
                        payment_id: paymentId,
                        game_id: gameId,
                        user_id: ctx.user_id,
                        year: ctx.year,
                        month: ctx.month
                    }
                }).done(function (res) {
                    CasaLedger.setButtonLoading($btn, false);
                    CasaLedger.applyActionResponse(res, ctx, 'Payment approved successfully.', $btn, reqSeq);
                }).fail(function (xhr) {
                    CasaLedger.setButtonLoading($btn, false);
                    const err = CasaLedger.parseJson(xhr.responseText);
                    CasaLedger.notify((err && err.message) || 'Error approving payment.', 'error');
                });
            });

            // --- 2. Reject Payment (Modal) ---
            $(document).on('click.casaledger', '.rejectBtnnn', function (e) {
                e.preventDefault();
                const $btn = $(this);
                const paymentId = $btn.attr('data-payment-id') || $btn.data('payment-id') || 0;
                const gameId = $btn.data('id') || 0;
                const ctx = CasaLedger.getContext($btn);

                $('#rejectPaymentModalShell').remove();
                $('body').append(`
                    <div id="rejectPaymentModalShell" class="modal fade show" style="display:block; background:rgba(15,23,42,0.65); backdrop-filter:blur(4px); z-index:9999;">
                      <div class="modal-dialog modal-dialog-centered" style="max-width: 480px; width: 95%;">
                        <div class="modal-content border-0" style="border-radius:16px; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
                          <div class="modal-header d-flex justify-content-between align-items-center py-3 px-4" style="background:#f8fafc; border-bottom:1px solid #e2e8f0;">
                            <h5 class="modal-title m-0 font-bold" style="color:#0f172a; font-size:1.1rem; display:flex; align-items:center; gap:8px;">
                                <i class="fa-solid fa-circle-xmark text-rose-600"></i> Reject Payment
                            </h5>
                            <button type="button" class="btn p-0 border-0 shadow-none bg-transparent closeRejectModalBtn" style="font-size:1.25rem; color:#64748b; line-height:1;"><i class="fa-solid fa-xmark"></i></button>
                          </div>
                          <div class="modal-body p-4" style="background:#ffffff;">
                            <div class="form-group mb-0">
                              <textarea id="rejectReasonInput" class="form-control" rows="3" placeholder="Enter rejection reason..." style="border-radius:10px; border:1px solid #cbd5e1; padding:12px; font-size:0.9rem; outline:none; box-shadow:none;"></textarea>
                              <div id="rejectReasonError" class="text-danger mt-1.5 small" style="display:none; font-size:0.82rem; font-weight:500;"><i class="fa-solid fa-circle-exclamation me-1"></i>Please enter a rejection reason.</div>
                            </div>
                          </div>
                          <div class="modal-footer py-3 px-4 d-flex justify-content-end gap-2" style="background:#f8fafc; border-top:1px solid #e2e8f0;">
                            <button type="button" class="btn btn-secondary closeRejectModalBtn" style="border-radius:8px; font-weight:600; font-size:0.88rem; padding:8px 18px; background:#64748b; border:none;">Cancel</button>
                            <button type="button" id="confirmRejectActionBtn" class="btn btn-danger" style="border-radius:8px; font-weight:600; font-size:0.88rem; padding:8px 20px; background:#e11d48; border-color:#e11d48;">Reject</button>
                          </div>
                        </div>
                      </div>
                    </div>
                `);

                $('.closeRejectModalBtn').on('click', function () {
                    $('#rejectPaymentModalShell').remove();
                });

                $('#confirmRejectActionBtn').on('click', function () {
                    const reason = $('#rejectReasonInput').val().trim();
                    if (!reason) {
                        $('#rejectReasonError').show();
                        return;
                    }
                    $('#rejectReasonError').hide();

                    const $confirmBtn = $(this);
                    CasaLedger.setButtonLoading($confirmBtn, true, 'Rejecting...');

                    const reqSeq = ++CasaLedger.activeRequestId;
                    const endpoint = paymentId > 0
                        ? CasaLedger.getUrl(`api/payments/${paymentId}/reject`)
                        : CasaLedger.getUrl('api/payment_action.php');

                    $.ajax({
                        url: endpoint,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'reject',
                            payment_id: paymentId,
                            game_id: gameId,
                            user_id: ctx.user_id,
                            reason: reason,
                            year: ctx.year,
                            month: ctx.month
                        }
                    }).done(function (res) {
                        $('#rejectPaymentModalShell').remove();
                        CasaLedger.applyActionResponse(res, ctx, 'Payment rejected successfully.', $btn, reqSeq);
                    }).fail(function (xhr) {
                        CasaLedger.setButtonLoading($confirmBtn, false);
                        const err = CasaLedger.parseJson(xhr.responseText);
                        CasaLedger.notify((err && err.message) || 'Error rejecting payment.', 'error');
                    });
                });
            });

            // --- 3. Re-initiate Payment (Modal) ---
            $(document).on('click.casaledger', '.reinitiateBtnnn', function (e) {
                e.preventDefault();
                const $btn = $(this);
                const paymentId = $btn.attr('data-payment-id') || $btn.data('payment-id') || 0;
                const ctx = CasaLedger.getContext($btn);

                if (!paymentId) {
                    CasaLedger.notify('Invalid payment ID for re-initiation.', 'error');
                    return;
                }

                $('#reinitiatePaymentModalShell').remove();
                $('body').append(`
                    <div id="reinitiatePaymentModalShell" class="modal fade show" style="display:block; background:rgba(15,23,42,0.65); backdrop-filter:blur(4px); z-index:9999;">
                      <div class="modal-dialog modal-dialog-centered" style="max-width: 480px; width: 95%;">
                        <div class="modal-content border-0" style="border-radius:16px; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
                          <div class="modal-header d-flex justify-content-between align-items-center py-3 px-4" style="background:#f8fafc; border-bottom:1px solid #e2e8f0;">
                            <h5 class="modal-title m-0 font-bold" style="color:#0f172a; font-size:1.1rem; display:flex; align-items:center; gap:8px;">
                                <i class="fa-solid fa-redo text-warning"></i> Re-initiate Payment
                            </h5>
                            <button type="button" class="btn p-0 border-0 shadow-none bg-transparent closeReinitModalBtn" style="font-size:1.25rem; color:#64748b; line-height:1;"><i class="fa-solid fa-xmark"></i></button>
                          </div>
                          <div class="modal-body p-4" style="background:#ffffff;">
                            <div class="form-group mb-0">
                              <label class="form-label small text-muted mb-1">Details / Note for Host:</label>
                              <textarea id="reinitiateDetailsInput" class="form-control" rows="3" placeholder="Provide note or corrected reference..." style="border-radius:10px; border:1px solid #cbd5e1; padding:12px; font-size:0.9rem; outline:none; box-shadow:none;"></textarea>
                            </div>
                          </div>
                          <div class="modal-footer py-3 px-4 d-flex justify-content-end gap-2" style="background:#f8fafc; border-top:1px solid #e2e8f0;">
                            <button type="button" class="btn btn-secondary closeReinitModalBtn" style="border-radius:8px; font-weight:600; font-size:0.88rem; padding:8px 18px; background:#64748b; border:none;">Cancel</button>
                            <button type="button" id="confirmReinitiateActionBtn" class="btn btn-warning" style="border-radius:8px; font-weight:600; font-size:0.88rem; padding:8px 20px;">Submit</button>
                          </div>
                        </div>
                      </div>
                    </div>
                `);

                $('.closeReinitModalBtn').on('click', function () {
                    $('#reinitiatePaymentModalShell').remove();
                });

                $('#confirmReinitiateActionBtn').on('click', function () {
                    const details = $('#reinitiateDetailsInput').val().trim();
                    const $confirmBtn = $(this);
                    CasaLedger.setButtonLoading($confirmBtn, true, 'Submitting...');

                    const reqSeq = ++CasaLedger.activeRequestId;
                    const endpoint = CasaLedger.getUrl(`api/payments/${paymentId}/reinitiate`);

                    $.ajax({
                        url: endpoint,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            payment_id: paymentId,
                            details: details,
                            year: ctx.year,
                            month: ctx.month,
                            host_id: ctx.host_id
                        }
                    }).done(function (res) {
                        $('#reinitiatePaymentModalShell').remove();
                        CasaLedger.applyActionResponse(res, ctx, 'Payment re-initiated successfully.', $btn, reqSeq);
                    }).fail(function (xhr) {
                        CasaLedger.setButtonLoading($confirmBtn, false);
                        const err = CasaLedger.parseJson(xhr.responseText);
                        CasaLedger.notify((err && err.message) || 'Error re-initiating payment.', 'error');
                    });
                });
            });

            // --- 4. Rollback Payment (Sends EXACT payment ID) ---
            $(document).on('click.casaledger', '.rollbackBtnnn', function (e) {
                e.preventDefault();
                const $btn = $(this);
                if ($btn.prop('disabled')) return;

                const paymentId = $btn.attr('data-payment-id') || $btn.data('payment-id') || 0;
                const gameId = $btn.data('id') || 0;
                const amount = $btn.data('amount') || '';
                const ctx = CasaLedger.getContext($btn);

                if (!paymentId && !gameId) {
                    CasaLedger.notify('Payment identifier not found.', 'error');
                    return;
                }

                let formattedAmount = '';
                if (amount !== '' && amount !== null && amount !== undefined) {
                    const num = parseFloat(amount);
                    formattedAmount = !isNaN(num) ? '$' + num.toFixed(2) : String(amount);
                }

                CasaLedger.confirm({
                    title: 'Rollback Payment',
                    text: `Are you sure you want to rollback this payment ${formattedAmount ? 'of <span class="casa-confirm-amt-pill">' + formattedAmount + '</span>' : ''}?`,
                    icon: 'danger',
                    confirmText: 'Yes, Rollback',
                    confirmBtnClass: 'btn-danger',
                    onConfirm: function () {
                        const reqSeq = ++CasaLedger.activeRequestId;
                        CasaLedger.setButtonLoading($btn, true, '<i class="fa-solid fa-spinner fa-spin"></i>');

                        const endpoint = paymentId > 0
                            ? CasaLedger.getUrl(`api/payments/${paymentId}/reverse`)
                            : CasaLedger.getUrl('api/rollback_payment.php');

                        $.ajax({
                            url: endpoint,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                payment_id: paymentId,
                                game_id: gameId,
                                user_id: ctx.user_id,
                                year: ctx.year,
                                month: ctx.month
                            }
                        }).done(function (res) {
                            CasaLedger.setButtonLoading($btn, false);
                            CasaLedger.applyActionResponse(res, ctx, 'Payment rolled back.', $btn, reqSeq);
                        }).fail(function (xhr) {
                            CasaLedger.setButtonLoading($btn, false);
                            const err = CasaLedger.parseJson(xhr.responseText);
                            CasaLedger.notify((err && err.message) || 'Error rolling back payment.', 'error');
                        });
                    }
                });
            });

            // --- 4.1 Pay Due Button (Direct Line-Item Payment) ---
            $(document).on('click.casaledger', '.payBtnnn', function (e) {
                e.preventDefault();
                const $btn = $(this);
                if ($btn.prop('disabled')) return;

                const gameId = parseInt($btn.attr('data-id') || $btn.data('id') || 0, 10);
                const amount = parseFloat($btn.attr('data-due') || $btn.data('due') || 0);
                const ctx = CasaLedger.getContext($btn);

                if (amount <= 0.001) {
                    CasaLedger.notify('No due balance to pay.', 'info');
                    return;
                }

                CasaLedger.confirm({
                    title: 'Record Payment',
                    text: `Are you sure you want to record payment for due amount <span class="casa-confirm-amt-pill" style="background:#ecfdf5; color:#059669; border-color:rgba(16,185,129,0.25);">$${amount.toFixed(2)}</span>?`,
                    icon: 'money',
                    confirmText: 'Pay Now',
                    confirmBtnClass: 'btn-success',
                    onConfirm: function () {
                        const reqSeq = ++CasaLedger.activeRequestId;
                        CasaLedger.setButtonLoading($btn, true, '<i class="fa-solid fa-spinner fa-spin"></i>');

                        $.ajax({
                            url: CasaLedger.getUrl('api/payments'),
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                user_id: ctx.user_id,
                                host_id: ctx.host_id,
                                game_id: gameId,
                                amount: amount,
                                payment_type: 'Interac',
                                year: ctx.year,
                                month: ctx.month
                            }
                        }).done(function (res) {
                            CasaLedger.setButtonLoading($btn, false);
                            CasaLedger.applyActionResponse(res, ctx, 'Payment recorded successfully.', $btn, reqSeq);
                        }).fail(function (xhr) {
                            CasaLedger.setButtonLoading($btn, false);
                            const err = CasaLedger.parseJson(xhr.responseText);
                            CasaLedger.notify((err && err.message) || 'Error processing payment.', 'error');
                        });
                    }
                });
            });

            // --- 5. Carry Forward ---
            $(document).on('click.casaledger', '.pay-action-carry', function (e) {
                e.preventDefault();
                const $btn = $(this);
                if ($btn.prop('disabled')) return;

                const ctx = CasaLedger.getContext($btn);
                if (!ctx.user_id) {
                    CasaLedger.notify('Player ID not found.', 'error');
                    return;
                }

                const reqSeq = ++CasaLedger.activeRequestId;
                CasaLedger.setButtonLoading($btn, true, '<i class="fa-solid fa-spinner fa-spin"></i> Processing...');

                $.ajax({
                    url: CasaLedger.getUrl('api/ledger/carry'),
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        user_id: ctx.user_id,
                        player_id: ctx.user_id,
                        year: ctx.year,
                        month: ctx.month
                    }
                }).done(function (res) {
                    CasaLedger.setButtonLoading($btn, false);
                    CasaLedger.applyActionResponse(res, ctx, 'Balance carried forward successfully.', $btn, reqSeq);
                }).fail(function (xhr) {
                    CasaLedger.setButtonLoading($btn, false);
                    const err = CasaLedger.parseJson(xhr.responseText);
                    CasaLedger.notify((err && err.message) || 'Error carrying forward balance.', 'error');
                });
            });

            // --- 6. Rollback Carry Forward ---
            $(document).on('click.casaledger', '.pay-action-rollback-carry', function (e) {
                e.preventDefault();
                const $btn = $(this);
                if ($btn.prop('disabled')) return;

                CasaLedger.confirm({
                    title: 'Rollback Carry Forward',
                    text: 'Are you sure you want to roll back the carry forward opening balance for this month?',
                    icon: 'warning',
                    confirmText: 'Rollback Balance',
                    confirmBtnClass: 'btn-warning',
                    onConfirm: function () {
                        const ctx = CasaLedger.getContext($btn);
                        const reqSeq = ++CasaLedger.activeRequestId;
                        CasaLedger.setButtonLoading($btn, true, '<i class="fa-solid fa-spinner fa-spin"></i>');

                        $.ajax({
                            url: CasaLedger.getUrl('api/ledger/carry/reverse'),
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                user_id: ctx.user_id,
                                player_id: ctx.user_id,
                                year: ctx.year,
                                month: ctx.month
                            }
                        }).done(function (res) {
                            CasaLedger.setButtonLoading($btn, false);
                            CasaLedger.applyActionResponse(res, ctx, 'Carry forward rolled back successfully.', $btn, reqSeq);
                        }).fail(function (xhr) {
                            CasaLedger.setButtonLoading($btn, false);
                            const err = CasaLedger.parseJson(xhr.responseText);
                            CasaLedger.notify((err && err.message) || 'Error rolling back carry forward.', 'error');
                        });
                    }
                });
            });

            // --- 7. Toggle Month Lock Override (Host only, UI updates ONLY on server success) ---
            $(document).on('click.casaledger', '.pay-action-override', function (e) {
                e.preventDefault();
                const $btn = $(this);
                if ($btn.prop('disabled')) return;

                const ctx = CasaLedger.getContext($btn);
                const isCurrentlyOff = ($btn.attr('data-override') || $btn.data('override') || 'off') === 'off';
                const newState = isCurrentlyOff ? 'on' : 'off';

                CasaLedger.setButtonLoading($btn, true);

                $.ajax({
                    url: CasaLedger.getUrl('api/ledger/override'),
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        user_id: ctx.user_id,
                        year: ctx.year,
                        month: ctx.month,
                        state: newState
                    }
                }).done(function (res) {
                    CasaLedger.setButtonLoading($btn, false);
                    if (res && res.success) {
                        if (res.html) {
                            CasaLedger.applyActionResponse(res, ctx, `Override mode turned ${res.state.toUpperCase()}`, $btn);
                        } else {
                            // Server approved override state transition fallback
                            const isUnlocked = (res.state === 'on');
                            $btn.attr('data-override', res.state).data('override', res.state);
                            
                            const $btns = $btn.closest('.pay-history-actionbar-btns');
                            const $chip = $btn.closest('.pay-history-actionbar').find('.pay-status-chip');

                            if (isUnlocked) {
                                $btn.addClass('pay-override-on')
                                    .attr('title', 'Override is Unlocked — click to restore original state');
                                $btn.find('i').removeClass('fa-lock').addClass('fa-lock-open');

                                // Override enables expenses and payments for corrections/testing
                                $btns.find('.pay-action-expense, .pay-action-pay')
                                    .prop('disabled', false).removeAttr('disabled');

                                // Carry forward is NOT unlocked if it was disabled due to non-override business rules (current month, already carried, downstream locked)
                                $btns.find('.pay-action-carry').each(function () {
                                    const orig = $(this).attr('data-orig-disabled');
                                    if (orig !== 'disabled') {
                                        $(this).prop('disabled', false).removeAttr('disabled');
                                    }
                                });

                                if ($chip.length) {
                                    $chip.html('🔓 UNLOCKED').removeClass('pay-status-locked pay-status-pending').addClass('pay-status-active');
                                }
                            } else {
                                $btn.removeClass('pay-override-on')
                                    .attr('title', 'Override is Locked — click to unlock action buttons (testing only)');
                                $btn.find('i').removeClass('fa-lock-open').addClass('fa-lock');

                                $btns.find('.pay-action-carry, .pay-action-expense, .pay-action-pay').each(function () {
                                    const orig = $(this).attr('data-orig-disabled');
                                    if (orig === 'disabled') {
                                        $(this).prop('disabled', true).attr('disabled', 'disabled');
                                    } else {
                                        $(this).prop('disabled', false).removeAttr('disabled');
                                    }
                                });

                                if ($chip.length) {
                                    $chip.html('🔒 LOCKED').removeClass('pay-status-active').addClass('pay-status-locked');
                                }
                            }

                            CasaLedger.notify(`Override mode turned ${res.state.toUpperCase()}`, 'info');
                        }
                    } else {
                        CasaLedger.notify((res && res.message) || 'Failed to toggle override.', 'error');
                    }
                }).fail(function (xhr) {
                    CasaLedger.setButtonLoading($btn, false);
                    const err = CasaLedger.parseJson(xhr.responseText);
                    CasaLedger.notify((err && err.message) || 'Error toggling override mode.', 'error');
                });
            });

            // --- 8. Month & Year Selectors ---
            $(document).on('change.casaledger', '#directLedgerMonth, #directLedgerYear, .pay-ledger-month, .pay-ledger-year', function () {
                const $el = $(this);
                if ($el.is('#directLedgerMonth, #directLedgerYear')) {
                    if (typeof window.changeDirectMonthlyLedger === 'function') {
                        window.changeDirectMonthlyLedger();
                        return;
                    }
                }
                const ctx = CasaLedger.getContext($el);

                // Update title if present
                const monthNames = ["", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                const $title = $('#directLedgerTitle');
                if ($title.length) {
                    $title.text(`Monthly Ledger — ${monthNames[ctx.month] || ''} ${ctx.year}`);
                }

                // Update URL params
                const url = new URL(window.location.href);
                url.searchParams.set('month', ctx.month);
                url.searchParams.set('year', ctx.year);
                window.history.replaceState({}, '', url.toString());

                // Trigger fresh fetch
                CasaLedger.loadMonthlyLedger(ctx.user_id, ctx.year, ctx.month, null, $el);
            });

            // --- 9. Full History View & Pagination ---
            $(document).on('click.casaledger', '.pay-ledger-full', function (e) {
                e.preventDefault();
                const $btn = $(this);
                const ctx = CasaLedger.getContext($btn);
                const playerId = ctx.user_id || $btn.data('user') || $btn.attr('data-user') || (CasaLedger.currentContext ? CasaLedger.currentContext.playerId : 0);
                const pageSize = $('.pay-ledger-pagesize').val() || 20;

                if (ctx.year && ctx.month) {
                    CasaLedger.currentContext = {
                        playerId: parseInt(playerId, 10),
                        year: parseInt(ctx.year, 10),
                        month: parseInt(ctx.month, 10)
                    };
                }

                CasaLedger.loadFullLedger(playerId, 1, pageSize, null, $btn);
            });

            $(document).on('click.casaledger', '.pay-ledger-back-to-financial', function (e) {
                e.preventDefault();
                const ctx = CasaLedger.currentContext || {};
                const playerId = $(this).data('user') || ctx.playerId || 0;
                const year = ctx.year || new Date().getFullYear();
                const month = ctx.month || (new Date().getMonth() + 1);
                CasaLedger.loadMonthlyLedger(playerId, year, month, function (err, res) {
                    if (!err && res && res.data && res.data.player_name) {
                        $('.customModal_head').text('View History - ' + res.data.player_name);
                    }
                });
            });

            $(document).on('click.casaledger', '.pay-ledger-page', function (e) {
                e.preventDefault();
                const $btn = $(this);
                const page = $btn.attr('data-page') || $btn.data('page') || 1;
                const playerId = $btn.data('user') || $btn.attr('data-user') || (CasaLedger.currentContext ? CasaLedger.currentContext.playerId : 0);
                const pageSize = $('.pay-ledger-pagesize').val() || 20;
                CasaLedger.loadFullLedger(playerId, page, pageSize, null, $btn);
            });

            $(document).on('change.casaledger', '.pay-ledger-pagesize', function (e) {
                const $sel = $(this);
                const pageSize = $sel.val() || 20;
                const playerId = $sel.data('user') || $sel.attr('data-user') || (CasaLedger.currentContext ? CasaLedger.currentContext.playerId : 0);
                CasaLedger.loadFullLedger(playerId, 1, pageSize, null, $sel);
            });

            // Subform toggles (Add Expense & Add Payment)
            $(document).on('click.casaledger', '.pay-action-expense', function (e) {
                e.preventDefault();
                if ($(this).prop('disabled') || $(this).attr('disabled')) return;
                $('#payPaymentFormWrap').slideUp(180);
                $('#payExpenseFormWrap').stop(true, true).slideToggle(180);
            });

            $(document).on('click.casaledger', '.pay-action-pay', function (e) {
                e.preventDefault();
                if ($(this).prop('disabled') || $(this).attr('disabled')) return;
                $('#payExpenseFormWrap').slideUp(180);
                $('#payPaymentFormWrap').stop(true, true).slideToggle(180);
            });

            $(document).on('click.casaledger', '.pay-expense-cancel, #btnCancelExpense', function (e) {
                e.preventDefault();
                $('#payExpenseFormWrap').slideUp(180);
            });

            $(document).on('click.casaledger', '.pay-payment-cancel, #btnCancelPayment', function (e) {
                e.preventDefault();
                $('#payPaymentFormWrap').slideUp(180);
            });

            // Submit Add Expense Form
            $(document).on('submit.casaledger', '#payExpenseForm', function (e) {
                e.preventDefault();
                const $form = $(this);
                const $btn = $form.find('#btnSubmitExpense, button[type="submit"]');

                const userId = parseInt($form.find('input[name="user_id"]').val(), 10) || 0;
                const hostId = parseInt($form.find('input[name="host_id"]').val(), 10) || 0;
                const year = parseInt($form.find('input[name="year"]').val(), 10) || new Date().getFullYear();
                const month = parseInt($form.find('input[name="month"]').val(), 10) || (new Date().getMonth() + 1);
                const venue = $form.find('select[name="venue"], input[name="venue"]').val() || 'Casa';
                const type = $form.find('input[name="type"]').val() || 'Expense';
                const amount = parseFloat($form.find('input[name="amount"]').val()) || 0;
                const expenseDatetime = $form.find('input[name="expense_datetime"]').val() || '';

                if (amount <= 0.001) {
                    CasaLedger.notify('Please enter a valid expense amount greater than zero.', 'error');
                    return;
                }
                if (!expenseDatetime) {
                    CasaLedger.notify('Please select the expense date and time.', 'error');
                    return;
                }

                const ctx = { user_id: userId, host_id: hostId, year: year, month: month };
                const reqSeq = ++CasaLedger.activeRequestId;
                CasaLedger.setButtonLoading($btn, true, '<i class="fa-solid fa-spinner fa-spin"></i> Saving...');

                $.ajax({
                    url: CasaLedger.getUrl('api/expenses'),
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        user_id: userId,
                        host_id: hostId,
                        venue: venue,
                        type: type,
                        amount: amount,
                        expense_datetime: expenseDatetime,
                        year: year,
                        month: month
                    }
                }).done(function (res) {
                    CasaLedger.setButtonLoading($btn, false);
                    $('#payExpenseFormWrap').slideUp(180);
                    $form[0].reset();
                    CasaLedger.applyActionResponse(res, ctx, 'Expense added successfully.', $btn, reqSeq);
                }).fail(function (xhr) {
                    CasaLedger.setButtonLoading($btn, false);
                    const err = CasaLedger.parseJson(xhr.responseText);
                    CasaLedger.notify((err && err.message) || 'Error saving expense.', 'error');
                });
            });

            // Submit Direct Payment Form
            $(document).on('submit.casaledger', '#payPaymentForm', function (e) {
                e.preventDefault();
                const $form = $(this);
                const $btn = $form.find('#btnSubmitPayment, button[type="submit"]');

                const userId = parseInt($form.find('input[name="user_id"]').val(), 10) || 0;
                const hostId = parseInt($form.find('input[name="host_id"]').val(), 10) || 0;
                const year = parseInt($form.find('input[name="year"]').val(), 10) || new Date().getFullYear();
                const month = parseInt($form.find('input[name="month"]').val(), 10) || (new Date().getMonth() + 1);
                const venue = $form.find('select[name="venue"], input[name="venue"]').val() || 'Casa';
                const paymentType = $form.find('input[name="payment_type"]').val() || 'Interac';
                const amount = parseFloat($form.find('input[name="amount"]').val()) || 0;
                const paymentDatetime = $form.find('input[name="payment_datetime"]').val() || '';
                const isPlayerSide = parseInt($form.find('input[name="is_player_side"]').val(), 10) || 0;

                if (amount <= 0.001) {
                    CasaLedger.notify('Please enter a valid payment amount greater than zero.', 'error');
                    return;
                }
                if (!paymentDatetime) {
                    CasaLedger.notify('Please select the payment date and time.', 'error');
                    return;
                }

                const ctx = { user_id: userId, host_id: hostId, year: year, month: month };
                const reqSeq = ++CasaLedger.activeRequestId;
                CasaLedger.setButtonLoading($btn, true, '<i class="fa-solid fa-spinner fa-spin"></i> Saving...');

                $.ajax({
                    url: CasaLedger.getUrl('api/payments'),
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        user_id: userId,
                        host_id: hostId,
                        game_id: 0,
                        details: venue,
                        payment_type: paymentType,
                        amount: amount,
                        payment_datetime: paymentDatetime,
                        year: year,
                        month: month,
                        is_player_side: isPlayerSide
                    }
                }).done(function (res) {
                    CasaLedger.setButtonLoading($btn, false);
                    $('#payPaymentFormWrap').slideUp(180);
                    $form[0].reset();
                    CasaLedger.applyActionResponse(res, ctx, 'Payment recorded successfully.', $btn, reqSeq);
                }).fail(function (xhr) {
                    CasaLedger.setButtonLoading($btn, false);
                    const err = CasaLedger.parseJson(xhr.responseText);
                    CasaLedger.notify((err && err.message) || 'Error saving payment.', 'error');
                });
            });
        },

        init: function () {
            if (CasaLedger.initialized) return;
            CasaLedger.initialized = true;
            CasaLedger.bindEvents();
            if (window.CasaDropdown && typeof window.CasaDropdown.initAll === 'function') {
                window.CasaDropdown.initAll();
            }
        }
    };

    window.CasaLedger = CasaLedger;

    // Auto-init on DOM ready
    $(document).ready(function () {
        CasaLedger.init();
    });

})(window, window.jQuery || window.$);
