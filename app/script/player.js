// Casa Player Dashboard, Financial Ledger & Payment Scripts


$(document).ready(function () {
    'use strict';

    // 1. PLAYER PAY AMOUNT MODAL
    $(document).on('click', '.PayAmountModal_open', function () {
        const gameId = $(this).attr('data-id');
        const userId = $(this).attr('data-user-id');

        $('.PayAmountModal').addClass('open');
        $('.game_dt').val(gameId);

        if (gameId !== "") {
            $.ajax({
                url: BASE_URL + "api/fetch_payment.php",
                type: "POST",
                data: { game_id: gameId, user_id: userId },
                success: function (response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        const curr = (data.currency && data.currency !== 'CAD') ? data.currency : '$';
                        const prefix = curr.endsWith('$') ? curr : (curr + ' ');
                        $("#tot_amnt strong").text(prefix + data.total_amount);
                        $("#due strong").text(prefix + data.due);
                        $("#due_amt").val(data.due);
                        $("#Amount").val(data.due);
                    } else {
                        $("#tot_amnt strong, #due strong").text("$0");
                        $("#due_amt, #Amount").val(0);
                    }
                },
                error: function () {
                    alert("Error fetching payment details.");
                }
            });
        } else {
            $("#tot_amnt strong, #due strong").text("$0");
        }
    });

    $(document).on('click', '.PayAmountModal_close', function () {
        $('.PayAmountModal').removeClass('open');
    });

    // 1C. PAYMENT HISTORY / LOGS MODAL
    $(document).on('click', '.pay-history-detail-btn, .pay-reinitiated-badge', function (e) {
        e.preventDefault();
        const $elem = $(this).closest('.pay-reinitiated-badge, .pay-history-detail-btn');
        const paymentId = $elem.attr('data-payment-id') || $elem.data('payment-id');
        const userId = $elem.attr('data-user') || $elem.data('user') || $("#user_id").val() || '';

        if (!paymentId) return;

        const $modal = $('.hostgameview_modal_payment');
        if ($modal.length > 0) {
            $modal.find('#playerList_payment').html('<div class="d-flex align-items-center justify-content-center py-5"><div class="spinner-border text-teal-600" role="status"><span class="visually-hidden">Loading...</span></div></div>');
            $modal.addClass('open');
        }

        const baseUrl = getPlayerBaseUrl();
        $.ajax({
            url: baseUrl + "api/view_player_paymentHis.php",
            type: "POST",
            data: { user_id: userId, payment_id: paymentId },
            success: function (response) {
                if ($modal.length > 0) {
                    $modal.find("#playerList_payment").html(response);
                    $modal.addClass('open');
                } else if ($("#modalContainer").length > 0) {
                    $("#modalTitle").text("Payment & Approval History");
                    $("#modalBody").html(response);
                    $("#modalContainer").removeClass("hidden");
                }
            },
            error: function () {
                if ($modal.length > 0) {
                    $modal.find("#playerList_payment").html('<div class="text-danger p-3 text-center">Failed to load payment history.</div>');
                } else {
                    notifyPlayer("Failed to load payment history.", "error");
                }
            }
        });
    });

    // 1B. PLAYER EVENT JOIN, CANCEL, ACCEPT & VIEW PLAYERS

    const getPlayerBaseUrl = function () {
        if (typeof BASE_URL !== 'undefined' && BASE_URL) {
            return BASE_URL;
        }
        if (window.BASE_URL) {
            return window.BASE_URL;
        }
        return '/';
    };

    const parseJsonSafe = function (str) {
        if (!str) return {};
        if (typeof str === 'object') return str;
        try {
            return JSON.parse(str);
        } catch (e) {
            return {};
        }
    };

    const notifyPlayer = function (message, type) {
        type = type || 'success';
        if (typeof toastr !== 'undefined' && typeof toastr.clear === 'function') {
            toastr.clear();
        }
        if (window.Casa && typeof window.Casa.showToast === 'function') {
            window.Casa.showToast(message, type);
        } else if (typeof showToast === 'function') {
            showToast(message, type);
        } else if (typeof toastr !== 'undefined' && typeof toastr[type] === 'function') {
            toastr[type](message);
        } else {
            alert(message);
        }
    };

    // 1. Join Event (.actionJC / .btn-join)
    $(document).on('click', '.actionJC, .session-rsvp-btn.btn-join', function (e) {
        if (e && e.preventDefault) e.preventDefault();
        const $btn = $(this);
        if ($btn.prop('disabled') || $btn.hasClass('loading')) return;

        const id = $btn.attr('data-id');
        const data = $('#data_' + id).val();
        if (!data) return;

        const parseData = parseJsonSafe(data);
        const userId = $("#user_" + id).val() || parseData.USER_ID || '';
        const originalHtml = $btn.html();

        $btn.prop('disabled', true).addClass('loading').html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Joining...');

        $.ajax({
            url: getPlayerBaseUrl() + 'api/join-event',
            type: 'POST',
            data: { ...parseData, USER_ID: userId, ID: id, game_id: id },
            success: function (response) {
                const res = parseJsonSafe(response);
                if (res && (res.status === 'error' || res.success === false)) {
                    $btn.prop('disabled', false).removeClass('loading').html(originalHtml);
                    notifyPlayer(res.message || 'Failed to join event.', 'error');
                    return;
                }

                // Direct toggle: Join -> Cancel
                $btn.removeClass('actionJC btn-join loading')
                    .addClass('actionC btn-cancel')
                    .prop('disabled', false)
                    .html('<i class="fa-solid fa-xmark me-1"></i>Cancel');

                // Update card stats in-place using shared helper
                if (typeof updateSchedulerCardStats === 'function') {
                    updateSchedulerCardStats($btn.closest('.discoverGames_card, .player_cards, .session-card'), res, id);
                }


                notifyPlayer(res.message || 'Event joined successfully.', 'success');
            },
            error: function (xhr) {
                $btn.prop('disabled', false).removeClass('loading').html(originalHtml);
                const res = xhr.responseJSON || parseJsonSafe(xhr.responseText);
                const errMsg = (res && res.message) ? res.message : 'An error occurred while joining the event.';
                notifyPlayer(errMsg, 'error');
            }
        });
    });

    // 2. Cancel Participation (.actionC / .btn-cancel)
    $(document).on('click', '.actionC, .session-rsvp-btn.btn-cancel', function (e) {
        if (e && e.preventDefault) e.preventDefault();
        const $btn = $(this);
        if ($btn.prop('disabled') || $btn.hasClass('loading')) return;

        const id = $btn.attr('data-id');
        const data = $('#data_' + id).val();
        if (!data) return;

        const parseData = parseJsonSafe(data);
        const userId = $("#user_" + id).val() || parseData.USER_ID || '';
        const originalHtml = $btn.html();

        $btn.prop('disabled', true).addClass('loading').html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Cancelling...');

        $.ajax({
            url: getPlayerBaseUrl() + 'api/cancel-event',
            type: 'POST',
            data: { ...parseData, USER_ID: userId, ID: id, game_id: id },
            success: function (response) {
                const res = parseJsonSafe(response);
                if (res && (res.status === 'error' || res.success === false)) {
                    $btn.prop('disabled', false).removeClass('loading').html(originalHtml);
                    notifyPlayer(res.message || 'Failed to cancel event.', 'error');
                    return;
                }

                // Direct toggle: Cancel -> Join
                $btn.removeClass('actionC btn-cancel loading')
                    .addClass('actionJC btn-join')
                    .prop('disabled', false)
                    .html('<i class="fa-solid fa-circle-plus me-1"></i>Join Game');

                // Update card stats in-place using shared helper
                if (typeof updateSchedulerCardStats === 'function') {
                    updateSchedulerCardStats($btn.closest('.discoverGames_card, .player_cards, .session-card'), res, id);
                }


                notifyPlayer(res.message || 'Participation cancelled successfully.', 'success');
            },
            error: function (xhr) {
                $btn.prop('disabled', false).removeClass('loading').html(originalHtml);
                const res = xhr.responseJSON || parseJsonSafe(xhr.responseText);
                const errMsg = (res && res.message) ? res.message : 'An error occurred while cancelling the event.';
                notifyPlayer(errMsg, 'error');
            }
        });
    });

    // 3. Accept Private Event Invite (.actionAC / .btn-accept)
    $(document).on('click', '.actionAC, .session-rsvp-btn.btn-accept', function (e) {
        if (e && e.preventDefault) e.preventDefault();
        const $btn = $(this);
        if ($btn.prop('disabled') || $btn.hasClass('loading')) return;

        const id = $btn.attr('data-id');
        const data = $('#data_' + id).val();
        if (!data) return;

        const parseData = parseJsonSafe(data);
        const userId = $("#user_" + id).val() || parseData.USER_ID || '';
        const originalHtml = $btn.html();

        $btn.prop('disabled', true).addClass('loading').html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Accepting...');

        $.ajax({
            url: getPlayerBaseUrl() + 'api/update_inviteJoin.php',
            type: 'POST',
            data: { ...parseData, USER_ID: userId, ID: id, game_id: id },
            success: function (response) {
                const res = parseJsonSafe(response);
                if (res && (res.status === 'error' || res.success === false)) {
                    $btn.prop('disabled', false).removeClass('loading').html(originalHtml);
                    notifyPlayer(res.message || 'Failed to accept invitation.', 'error');
                    return;
                }

                $btn.removeClass('actionAC btn-accept loading')
                    .addClass('actionC btn-cancel')
                    .prop('disabled', false)
                    .html('<i class="fa-solid fa-xmark me-1"></i>Cancel');

                // Update card stats in-place using shared helper
                if (typeof updateSchedulerCardStats === 'function') {
                    updateSchedulerCardStats($btn.closest('.discoverGames_card, .player_cards, .session-card'), res, id);
                }


                notifyPlayer('Invitation accepted successfully.', 'success');
            },
            error: function (xhr) {
                $btn.prop('disabled', false).removeClass('loading').html(originalHtml);
                const res = xhr.responseJSON || parseJsonSafe(xhr.responseText);
                const errMsg = (res && res.message) ? res.message : 'An error occurred while accepting invite.';
                notifyPlayer(errMsg, 'error');
            }
        });
    });

    // 4. View Joined Players Modal (.view_btnn)
    $(document).on('click', '.view_btnn', function (e) {
        if (e && e.preventDefault) e.preventDefault();
        const $btn = $(this);
        const id = $btn.attr('data-id');
        const data = $('#data_' + id).val();
        const parseData = parseJsonSafe(data);
        const gameId = parseData?.ID || id;
        if (!gameId) return;

        // Ensure modal HTML exists in DOM
        if ($('.hostgameview_modal_upcoming').length === 0) {
            $('body').append(`
                <section class="customModal_wrap hostgameview_modal hostgameview_modal_upcoming">
                    <div class="customModal_body">
                        <h6 class="customModal_head">View Players</h6>
                        <button class="customModal_close btn" type="button">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                        <hr />
                        <div class="customModal_content">
                            <div class="playerList-container" id="playerList_upcoming"></div>
                        </div>
                    </div>
                </section>
            `);
        }

        $('#playerList_upcoming').html(`
            <div class="text-center py-4">
                <i class="fa-solid fa-spinner fa-spin fa-2x" style="color: #0284c7;"></i>
                <p class="mt-2 text-muted" style="font-size: 0.9rem;">Loading joined players...</p>
            </div>
        `);
        $('.hostgameview_modal_upcoming').addClass('open');

        $.ajax({
            type: "POST",
            url: getPlayerBaseUrl() + 'api/search-game-players',
            data: Object.assign({}, parseData, {
                game_id: gameId,
                view: 'joined',
                joined_only: 1,
                format: 'html'
            }),
            success: function (response) {
                $("#playerList_upcoming").html(response);
            },
            error: function (xhr) {
                let errMsg = 'Failed to load players list.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                $("#playerList_upcoming").html(`<div class="alert alert-danger my-3 text-center" style="font-size: 0.85rem;">${errMsg}</div>`);
            }
        });
    });

    // 5. Unified Modal Close Handlers for Player View
    $(document).on('click', '.hostgameview_modal_upcoming .customModal_close, .customModal_wrap .customModal_close', function () {
        $(this).closest('.customModal_wrap').removeClass('open');
    });

    $(document).on('click', '.hostgameview_modal_upcoming, .customModal_wrap', function (e) {
        if ($(e.target).hasClass('customModal_wrap')) {
            $(this).removeClass('open');
        }
    });

    // 6. Subjoin button for subscription games
    $(document).on('click', '.discoverGames_card_sub .join_btn', function (e) {
        if (e && e.preventDefault) e.preventDefault();
        const $btn = $(this);
        if ($btn.prop('disabled') || $btn.hasClass('loading')) return;

        const eventId = $btn.attr('data-id');
        const isJoined = $btn.attr('data-joined') === '1';

        if (!isJoined && !confirm("Are you sure you want to join this event?")) return;

        const data = $('#data_' + eventId).val();
        const parseData = parseJsonSafe(data);
        const userId = $("#user_" + eventId).val();
        const url = isJoined ? 'api/cancel-event-default' : 'api/join-event-default';

        $btn.prop('disabled', true).addClass('loading');

        $.ajax({
            url: getPlayerBaseUrl() + url,
            type: 'POST',
            data: { ...parseData, USER_ID: userId, HOST_ID: parseData.HOST_ID || $("#playdt_subscription").attr("data-host-id") || "" },
            success: function (response) {
                $btn.prop('disabled', false).removeClass('loading');
                if (response && typeof response === 'string' && response.includes('custom_card')) {
                    $(".outputHtml").html(response);
                } else {
                    if (isJoined) {
                        $btn.removeClass('btn-danger').addClass('btn-primary').text('Join').attr('data-joined', '0');
                    } else {
                        $btn.removeClass('btn-primary').addClass('btn-danger').text('Cancel').attr('data-joined', '1');
                    }
                }
            },
            error: function () {
                $btn.prop('disabled', false).removeClass('loading');
                notifyPlayer('An error occurred while processing your request.', 'error');
            }
        });
    });

    // 2. CUSTOM PLAYER PAYMENT MODAL

    function formatLedgerCurrency(amt) {
        const val = parseFloat(amt) || 0;
        return '$' + val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escapeLedgerHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderDetailedLedgerHtml(data) {
        if (data && data.html && typeof data.html === 'string' && data.html.trim() !== '') {
            return data.html;
        }

        const playerId = data.player_id;
        const selectedYear = parseInt(data.year, 10);
        const selectedMonth = parseInt(data.month, 10);
        const opening = data.opening_balance;
        const costs = data.event_costs || [];
        const payments = data.payments || [];
        const totals = data.totals || {};
        const currentYear = new Date().getFullYear();

        const monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        let html = '';

        // 1. Filter Bar
        html += '<div class="pay-ledger-filter">';
        html += '<span class="pay-ledger-filter-title"><i class="fa-solid fa-book-open"></i> Financial Ledger</span>';

        // Year select
        html += '<select class="form-select form-select-sm pay-ledger-year" data-casa-dropdown data-size="sm" data-icon="fa-regular fa-calendar" data-user="' + playerId + '" title="Filter by year">';
        for (let y = 2024; y <= Math.max(currentYear + 2, 2030); y++) {
            const sel = (y === selectedYear) ? ' selected' : '';
            html += '<option value="' + y + '"' + sel + '>' + y + '</option>';
        }
        html += '</select>';

        // Month select
        html += '<select class="form-select form-select-sm pay-ledger-month" data-casa-dropdown data-size="sm" data-icon="fa-regular fa-calendar-days" data-user="' + playerId + '" title="Filter by month">';
        for (let m = 1; m <= 12; m++) {
            const sel = (m === selectedMonth) ? ' selected' : '';
            html += '<option value="' + m + '"' + sel + '>' + monthNames[m - 1] + '</option>';
        }
        html += '</select>';

        html += '</div>';

        // 2. Unified Table
        html += '<div class="pay-section pay-section-unified">';
        html += '<table class="table table-striped table-bordered pay-table">';
        html += '<thead>';
        html += '<tr class="pay-table-header">';
        html += '<th>Sl.</th>';
        html += '<th>Date &amp; Time</th>';
        html += '<th>Venue</th>';
        html += '<th>Type</th>';
        html += '<th>Amount</th>';
        html += '<th>Action</th>';
        html += '<th style="text-align: center; width: 65px;">Logs</th>';
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';

        // Section Title: EVENT COST
        html += '<tr class="pay-table-section-title">';
        html += '<th colspan="7">Event Cost</th>';
        html += '</tr>';

        // Opening Balance (Carry Forward)
        if (opening && parseFloat(opening.opening_balance) > 0) {
            html += '<tr class="pay-table-section-sub">';
            html += '<th colspan="7">Opening Balance (Carry Forward)</th>';
            html += '</tr>';
            html += '<tr class="pay-row-carry">';
            html += '<td>1</td>';
            html += '<td>' + escapeLedgerHtml(opening.date_time) + '</td>';
            html += '<td>' + escapeLedgerHtml(opening.venue || 'Casa') + '</td>';
            html += '<td>' + escapeLedgerHtml(opening.item_type) + '</td>';
            html += '<td>' + formatLedgerCurrency(opening.opening_balance) + '</td>';
            html += '<td>' + (opening.action_html || ('<span class="badge pay-badge-opening">' + escapeLedgerHtml(opening.action_status || 'Opening') + '</span>')) + '</td>';
            html += '<td style="text-align: center; color: #94a3b8;">—</td>';
            html += '</tr>';
        }

        // Event Costs (Games & Expenses)
        if (costs.length === 0 && (!opening || parseFloat(opening.opening_balance) <= 0)) {
            html += '<tr><td colspan="7" class="text-center text-muted" style="font-size: 0.76rem; padding: 6px;">No pending event costs.</td></tr>';
        } else {
            costs.forEach(function (c) {
                const status = (c.action_status || '').trim();
                let rowClass = 'pay-row-cost-unpaid';
                let actionBadge = c.action_html || '';

                if (!actionBadge) {
                    if (status === 'Paid') {
                        rowClass = 'pay-row-cost-paid';
                        actionBadge = '<span class="badge pay-badge-paid">Paid</span>';
                    } else if (status === 'Pending Approval') {
                        actionBadge = '<span class="badge pay-badge-pending">Pending Approval</span>';
                    } else if (status === 'Rejected') {
                        actionBadge = '<span class="badge bg-danger text-white">Rejected</span>';
                    }
                } else if (status === 'Paid') {
                    rowClass = 'pay-row-cost-paid';
                }

                html += '<tr class="' + rowClass + '">';
                html += '<td>' + c.sl + '</td>';
                html += '<td>' + escapeLedgerHtml(c.date_time) + '</td>';
                html += '<td>' + escapeLedgerHtml(c.venue || '—') + '</td>';
                html += '<td>' + escapeLedgerHtml(c.item_type || '—') + '</td>';
                html += '<td>' + formatLedgerCurrency(c.amount) + '</td>';
                html += '<td>' + actionBadge + '</td>';
                html += '<td style="text-align: center; color: #94a3b8;">' + (c.logs || '—') + '</td>';
                html += '</tr>';
            });
        }

        // Total Event Cost Footer
        html += '<tr class="pay-table-footer">';
        html += '<th colspan="4">Total Event Cost</th>';
        html += '<td>' + formatLedgerCurrency(totals.total_event_cost || 0) + '</td>';
        html += '<td></td>';
        html += '<td></td>';
        html += '</tr>';

        // Section Title: PAYMENTS
        html += '<tr class="pay-table-section-title">';
        html += '<th colspan="7">Payments</th>';
        html += '</tr>';

        if (payments.length === 0) {
            html += '<tr><td colspan="7" class="text-center text-muted" style="padding: 10px;">No payments recorded for this month.</td></tr>';
        } else {
            payments.forEach(function (p) {
                const status = (p.action_status || '').trim();
                let actionBadge = p.action_html || '';

                if (!actionBadge) {
                    if (status === 'Paid') {
                        actionBadge = '<span class="badge pay-badge-paid">Paid</span>';
                    } else if (status === 'Pending Approval') {
                        actionBadge = '<span class="badge bg-warning text-dark">Pending Approval</span>';
                    } else if (status === 'Rejected') {
                        actionBadge = '<span class="badge bg-danger text-white">Rejected</span>';
                    } else {
                        actionBadge = status ? '<span class="badge bg-secondary">' + escapeLedgerHtml(status) + '</span>' : '';
                    }
                }

                html += '<tr class="pay-row-payment">';
                html += '<td>' + p.sl + '</td>';
                html += '<td>' + escapeLedgerHtml(p.date_time) + '</td>';
                html += '<td>' + escapeLedgerHtml(p.venue || '—') + '</td>';
                html += '<td>' + escapeLedgerHtml(p.item_type || 'Payment') + '</td>';
                html += '<td>' + formatLedgerCurrency(p.amount) + '</td>';
                html += '<td>' + actionBadge + '</td>';
                html += '<td style="text-align: center; vertical-align: middle;">' + (p.logs_html || '') + '</td>';
                html += '</tr>';
            });
        }

        // Total Payments Footer
        html += '<tr class="pay-table-footer">';
        html += '<th colspan="4">Total Payments</th>';
        html += '<td>' + formatLedgerCurrency(totals.total_payments || 0) + '</td>';
        html += '<td></td>';
        html += '<td></td>';
        html += '</tr>';

        html += '</tbody>';
        html += '</table>';
        html += '</div>';

        // 3. Final Balance Card
        const closingBal = parseFloat(totals.closing_balance || 0);
        const balClass = (closingBal > 0) ? 'pay-balance-due' : 'pay-balance-paid';

        html += '<div class="pay-section pay-section-balance">';
        html += '<div class="pay-balance-row ' + balClass + '">';
        html += '<span class="pay-balance-label">Final Balance</span>';
        html += '<span class="pay-balance-value">' + formatLedgerCurrency(closingBal) + '</span>';
        html += '</div>';
        html += '</div>';

        return html;
    }

    function loadPlayerDetailedLedger(playerId, year, month) {
        $('.playPaymentModal').addClass('open');
        $('.patmentTb').html(
            '<div class="text-center py-5">' +
            '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>' +
            '<p class="text-muted mt-2 mb-0" style="font-size:0.85rem;">Loading financial ledger...</p>' +
            '</div>'
        );

        $.ajax({
            url: BASE_URL + 'api/get-player-detailed-ledger?format=json',
            type: 'POST',
            data: {
                player_id: playerId,
                user_id: playerId,
                year: year,
                month: month,
                format: 'json'
            },
            dataType: 'json',
            success: function (res) {
                if (res && res.success && res.data) {
                    if (res.data.player_name) {
                        $('.customModal_head').text('View History - ' + res.data.player_name);
                    }
                    const html = (res.data.html && res.data.html.trim() !== '') ? res.data.html : renderDetailedLedgerHtml(res.data);
                    setSafeModalContent($(".patmentTb")[0], html);
                } else {
                    $('.patmentTb').html('<p class="text-center text-danger py-4">Failed to load ledger data.</p>');
                }
            },
            error: function () {
                $('.patmentTb').html('<p class="text-center text-danger py-4">Error loading ledger data.</p>');
            }
        });
    }

    $(document).on('click', '.playPaymentModal_open', function () {
        if (window.CasaLedger) return;
        const player_id = $(this).attr('data-id');
        let year = $("#hpyear").val();
        let month = $("#hpmonth").val();

        if (!year) year = new Date().getFullYear();
        if (!month) month = new Date().getMonth() + 1;

        loadPlayerDetailedLedger(player_id, year, month);
    });

    $(document).on('click', '.playPaymentModal_close', function () {
        if (window.CasaLedger && window.CasaLedger.currentView === 'full') return;
        $('.playPaymentModal').removeClass('open');
        $('.customModal_head').text('View History');
        const year = $("#hpyear").val();
        const month = $("#hpmonth").val();
        const host = $("#hhost").val();

        $.ajax({
            type: "POST",
            url: (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + 'api/host/payment-list',
            data: { year: year, month: month, player: host, type: 'filter' },
            success: function (response) {
                const html = (typeof response === 'object' && response !== null && response.html) ? response.html : response;
                setSafeModalContent($(".host_payment")[0], html);
            }
        });
    });

    // 3. PLAYER FINANCIAL LEDGER & PAGINATION

    $(document).on('change', '.pay-ledger-year, .pay-ledger-month', function () {
        if (window.CasaLedger) return;
        const $bar = $(this).closest('.pay-ledger-filter');
        const user_id = $bar.find('.pay-ledger-year').data('user');
        const year = $bar.find('.pay-ledger-year').val();
        const month = $bar.find('.pay-ledger-month').val();

        if (user_id === undefined || user_id === '') return;
        loadPlayerDetailedLedger(user_id, year, month);
    });

    $(document).on('click', '.pay-ledger-full', function () {
        if (window.CasaLedger) return;
        const user_id = $(this).data('user');
        const pageSize = $('.pay-ledger-pagesize').val() || 20;

        if (user_id === undefined || user_id === '') return;

        $.ajax({
            url: BASE_URL + "api/view_player_ledger.php",
            type: "POST",
            data: { user_id: user_id, pageSize: pageSize, page: 1, full_ledger: 1 },
            success: function (response) {
                setSafeModalContent($(".patmentTb")[0], response);
            },
            error: function () {
                alert("Error saving data.");
            }
        });
    });

    $(document).on('change', '.pay-ledger-pagesize', function () {
        const user_id = $(this).data('user');
        const pageSize = $(this).val();
        const $bar = $(this).closest('.patmentTb').find('.pay-ledger-filter');
        const year = $bar.find('.pay-ledger-year').val() || '';
        const month = $bar.find('.pay-ledger-month').val() || '';

        if (user_id === undefined || user_id === '') return;

        $.ajax({
            url: BASE_URL + "api/view_player_ledger.php",
            type: "POST",
            data: { user_id: user_id, page: 1, pageSize: pageSize, year: year, month: month },
            success: function (response) {
                setSafeModalContent($(".patmentTb")[0], response);
            },
            error: function () {
                alert("Error saving data.");
            }
        });
    });

    $(document).on('click', '.pay-ledger-page', function () {
        const user_id = $(this).data('user');
        const page = $(this).data('page');
        const pageSize = $('.pay-ledger-pagesize').val() || 20;
        const $bar = $(this).closest('.patmentTb').find('.pay-ledger-filter');
        const year = $bar.find('.pay-ledger-year').val() || '';
        const month = $bar.find('.pay-ledger-month').val() || '';

        if (user_id === undefined || user_id === '') return;

        $.ajax({
            url: BASE_URL + "api/view_player_ledger.php",
            type: "POST",
            data: { user_id: user_id, page: page, pageSize: pageSize, year: year, month: month },
            success: function (response) {
                setSafeModalContent($(".patmentTb")[0], response);
            },
            error: function () {
                alert("Error saving data.");
            }
        });
    });

    // 4. MATCH VIEW, CART & THANK YOU MODALS

    $(document).on('click', '.matchviewmodal_close', function () {
        $('.matchviewmodal').removeClass('open');
    });

    $(document).on('click', '.minus', function () {
        const $input = $(this).parent().find('input');
        let count = parseInt($input.val()) - 1;
        count = count < 1 ? 1 : count;
        $input.val(count).change();
        return false;
    });

    $(document).on('click', '.plus', function () {
        const $input = $(this).parent().find('input');
        $input.val(parseInt($input.val()) + 1).change();
        return false;
    });

    $(document).on('click', '.thankyouModal_click', function () {
        $('.thankyouModal').addClass('open');
    });

    $(document).on('click', '.thankyouModal_close', function () {
        $('.thankyouModal').removeClass('open');
    });

    // Product Details Gallery Slider
    const imgs = document.querySelectorAll('.img-select a');
    let imgId = 1;

    imgs.forEach(function (imgItem) {
        imgItem.addEventListener('click', function (event) {
            event.preventDefault();
            imgId = imgItem.dataset.id;
            slideImage();
        });
    });

    function slideImage() {
        const showcaseImg = document.querySelector('.img-showcase img:first-child');
        const showcase = document.querySelector('.img-showcase');
        if (!showcaseImg || !showcase) return;
        const displayWidth = showcaseImg.clientWidth;
        const currentImgId = (typeof imgId !== 'undefined') ? imgId : 1;
        showcase.style.transform = `translateX(${- (currentImgId - 1) * displayWidth}px)`;
    }

    window.addEventListener('resize', slideImage);

    // 5. PLAYER DASHBOARD FILTERS
    let _playerScheduleFilterXhr = null;
    let _playerScheduleFilterSeq = 0;

    $("#event_category, #year, #month").on("change", function () {
        const year = $("#year").val() || '';
        const month = $("#month").val() || '';
        const host = $("#host").val() || '';
        const event_category = $("#event_category").val() || '';
        const wrapper = $("#Upcoming .plyerGame_wrapper");
        if (!wrapper.length) return;

        const currentSeq = ++_playerScheduleFilterSeq;

        if (_playerScheduleFilterXhr && typeof _playerScheduleFilterXhr.abort === "function") {
            _playerScheduleFilterXhr.abort();
        }

        wrapper.attr("aria-busy", "true").css({ opacity: "0.5", pointerEvents: "none" });

        _playerScheduleFilterXhr = $.ajax({
            type: "POST",
            url: BASE_URL + 'api/filter_schedule.php',
            data: {
                year: year,
                month: month,
                host: host,
                event_category: event_category,
                type: 'filter'
            },
            success: function (response) {
                if (currentSeq === _playerScheduleFilterSeq) {
                    wrapper.html(response);
                    if (typeof initPopovers === 'function') initPopovers();
                }
            },
            error: function (xhr, status, error) {
                if (status !== "abort") {
                    console.error('[PlayerFilter] Schedule filter error:', error);
                    notifyPlayer('Failed to load upcoming games. Please try again.', 'error');
                }
            },
            complete: function () {
                if (currentSeq === _playerScheduleFilterSeq) {
                    wrapper.removeAttr("aria-busy").css({ opacity: "1", pointerEvents: "" });
                    _playerScheduleFilterXhr = null;
                }
            }
        });
    });

    let _playerCompleteFilterXhr = null;
    let _playerCompleteFilterSeq = 0;

    $("#comyear, #commonth").on("change", function () {
        $("#play_com_filter").click();
    });

    $("#play_com_filter").click(function () {
        const year = $("#comyear").val() || '';
        const month = $("#commonth").val() || '';
        const host = $("#host").val() || '';
        const wrapper = $(".playCom");
        if (!wrapper.length) return;

        const currentSeq = ++_playerCompleteFilterSeq;
        if (_playerCompleteFilterXhr && typeof _playerCompleteFilterXhr.abort === "function") {
            _playerCompleteFilterXhr.abort();
        }

        wrapper.attr("aria-busy", "true").css({ opacity: "0.5", pointerEvents: "none" });

        _playerCompleteFilterXhr = $.ajax({
            type: "POST",
            url: BASE_URL + 'api/player_filter_complete.php',
            data: { year: year, month: month, host: host, type: 'filter' },
            success: function (response) {
                if (currentSeq === _playerCompleteFilterSeq) {
                    wrapper.html(response);
                }
            },
            error: function (xhr, status, error) {
                if (status !== "abort") {
                    console.error('[PlayerFilter] Completed filter error:', error);
                    notifyPlayer('Failed to load completed games. Please try again.', 'error');
                }
            },
            complete: function () {
                if (currentSeq === _playerCompleteFilterSeq) {
                    wrapper.removeAttr("aria-busy").css({ opacity: "1", pointerEvents: "" });
                    _playerCompleteFilterXhr = null;
                }
            }
        });
    });

    $("#play_com_reset").click(function () {
        const wrapper = $(".playCom");
        if (!wrapper.length) return;

        const currentSeq = ++_playerCompleteFilterSeq;
        if (_playerCompleteFilterXhr && typeof _playerCompleteFilterXhr.abort === "function") {
            _playerCompleteFilterXhr.abort();
        }

        wrapper.attr("aria-busy", "true").css({ opacity: "0.5", pointerEvents: "none" });

        const host = $("#host").val() || '';

        _playerCompleteFilterXhr = $.ajax({
            type: "POST",
            url: BASE_URL + 'api/player_filter_complete.php',
            data: { host: host, type: 'reset' },
            success: function (response) {
                if (currentSeq === _playerCompleteFilterSeq) {
                    wrapper.html(response);
                    const now = new Date();
                    $("#comyear").val(now.getFullYear());
                    $("#commonth").val(now.getMonth() + 1);
                    // Do NOT clear #host: it preserves the player's scoped host/club ID context!
                }
            },
            error: function (xhr, status, error) {
                if (status !== "abort") {
                    console.error('[PlayerFilter] Reset completed filter error:', error);
                    notifyPlayer('Failed to reset completed games. Please try again.', 'error');
                }
            },
            complete: function () {
                if (currentSeq === _playerCompleteFilterSeq) {
                    wrapper.removeAttr("aria-busy").css({ opacity: "1", pointerEvents: "" });
                    _playerCompleteFilterXhr = null;
                }
            }
        });
    });

    let _playerPayFilterXhr = null;
    let _playerPayFilterSeq = 0;

    $("#pay_comyear, #pay_commonth").change(function () {
        $("#pay_com_filter").click();
    });

    $("#pay_com_filter").click(function () {
        const host = $("#payhost").val() || '';
        const year = $("#payyear").val() || '';
        const month = $("#paymonth").val() || '';
        const wrapper = $(".patmentTb");
        if (!wrapper.length) return;

        const currentSeq = ++_playerPayFilterSeq;
        if (_playerPayFilterXhr && typeof _playerPayFilterXhr.abort === "function") {
            _playerPayFilterXhr.abort();
        }

        wrapper.attr("aria-busy", "true").css({ opacity: "0.5", pointerEvents: "none" });

        _playerPayFilterXhr = $.ajax({
            type: "POST",
            url: BASE_URL + 'api/pay_filter_complete.php',
            data: { year: year, month: month, host: host, type: 'filter' },
            success: function (response) {
                if (currentSeq === _playerPayFilterSeq) {
                    setSafeModalContent(wrapper[0], response);
                }
            },
            error: function (xhr, status, error) {
                if (status !== "abort") {
                    console.error('[PlayerPayment] Filter error:', error);
                    notifyPlayer('Failed to load payment history. Please try again.', 'error');
                }
            },
            complete: function () {
                if (currentSeq === _playerPayFilterSeq) {
                    wrapper.removeAttr("aria-busy").css({ opacity: "1", pointerEvents: "" });
                    _playerPayFilterXhr = null;
                }
            }
        });
    });

    $("#pay_com_reset").click(function () {
        const wrapper = $(".patmentTb");
        if (!wrapper.length) return;

        const currentSeq = ++_playerPayFilterSeq;
        if (_playerPayFilterXhr && typeof _playerPayFilterXhr.abort === "function") {
            _playerPayFilterXhr.abort();
        }

        wrapper.attr("aria-busy", "true").css({ opacity: "0.5", pointerEvents: "none" });

        _playerPayFilterXhr = $.ajax({
            type: "POST",
            url: BASE_URL + 'api/pay_filter_complete.php',
            data: { type: 'reset' },
            success: function (response) {
                if (currentSeq === _playerPayFilterSeq) {
                    wrapper.html(response);
                    const now = new Date();
                    $("#payyear").val(now.getFullYear());
                    $("#paymonth").val(now.getMonth() + 1);
                    // Do NOT clear #host: preserves the scoped host/club ID context!
                }
            },
            error: function (xhr, status, error) {
                if (status !== "abort") {
                    console.error('[PlayerPayment] Reset error:', error);
                    notifyPlayer('Failed to reset payment history. Please try again.', 'error');
                }
            },
            complete: function () {
                if (currentSeq === _playerPayFilterSeq) {
                    wrapper.removeAttr("aria-busy").css({ opacity: "1", pointerEvents: "" });
                    _playerPayFilterXhr = null;
                }
            }
        });
    });

    // 6. USER PROFILE NAVIGATION & CALENDAR

    const menuToggle = document.querySelector('.menu-toggle');
    const navigation = document.querySelector('.navigation');

    if (menuToggle && navigation) {
        menuToggle.onclick = function () {
            navigation.classList.toggle('active');
        };
    }

    const daysContainer = document.getElementById("daysContainer");
    const monthYear = document.getElementById("monthYear");
    const dateInput = document.getElementById("dateInput");
    const calendar = document.getElementById("calendar");

    if (daysContainer && monthYear) {
        let currentDate = new Date();
        let selectedDate = null;

        function handleDayClick(day) {
            selectedDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
            if (dateInput) dateInput.value = selectedDate.toLocaleDateString("en-US");
            if (calendar) calendar.style.display = "none";
            renderCalendar();
        }

        function createDayElement(day) {
            const date = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
            const dayElement = document.createElement("div");
            dayElement.classList.add("day");

            if (date.toDateString() === new Date().toDateString()) {
                dayElement.classList.add("current");
            }
            if (selectedDate && date.toDateString() === selectedDate.toDateString()) {
                dayElement.classList.add("selected");
            }

            dayElement.textContent = day;
            dayElement.addEventListener("click", function () {
                handleDayClick(day);
            });
            daysContainer.appendChild(dayElement);
        }

        function renderCalendar() {
            daysContainer.innerHTML = "";
            const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);

            monthYear.textContent = `${currentDate.toLocaleString("default", { month: "long" })} ${currentDate.getFullYear()}`;

            for (let day = 1; day <= lastDay.getDate(); day++) {
                createDayElement(day);
            }
        }
    }
});
