/**
 * Player Hub JavaScript Interactivity
 * File: app/script/player-hub.js
 */
(() => {
    'use strict';

    /** Helper for safe modal HTML rendering (delegates to shared window.Casa.setSafeModalContent). */
    const renderSafeModalContent = (container, html) => {
        if (window.Casa && typeof window.Casa.setSafeModalContent === 'function') {
            window.Casa.setSafeModalContent(container, html);
        } else if (typeof window.setSafeModalContent === 'function') {
            window.setSafeModalContent(container, html);
        } else if (container) {
            container.innerHTML = html || '';
        }
    };

document.addEventListener('DOMContentLoaded', () => {
    /** Prevent Bootstrap modal scrollbar layout shift & padding-right jump. */
    document.addEventListener('show.bs.modal', () => { document.body.style.paddingRight = '0px'; });
    document.addEventListener('hidden.bs.modal', () => { document.body.style.paddingRight = '0px'; });

    /** 1. Direct event delegation for Club Modals (Info, Costing, Schedule). */
    document.addEventListener('click', (e) => {
        const infoBtn = e.target.closest('.club-info-btn');
        if (infoBtn) {
            const name = infoBtn.getAttribute('data-club-name') || 'Club Info';
            const info = infoBtn.getAttribute('data-club-info') || '';
            const titleEl = document.getElementById('InfomodalLabel');
            const bodyEl = document.getElementById('InfomodalBody');
            if (titleEl) titleEl.textContent = name;
            renderSafeModalContent(bodyEl, info);
        }

        const costBtn = e.target.closest('.club-cost-btn');
        if (costBtn) {
            const name = costBtn.getAttribute('data-club-name') || 'Game Costing';
            const cost = costBtn.getAttribute('data-club-cost') || '';
            const titleEl = document.getElementById('CostingmodalLabel');
            const bodyEl = document.getElementById('CostingmodalBody');
            if (titleEl) titleEl.textContent = name;
            renderSafeModalContent(bodyEl, cost);
        }

        const schedBtn = e.target.closest('.club-schedule-btn');
        if (schedBtn) {
            const name = schedBtn.getAttribute('data-club-name') || 'Game Schedule';
            const schedule = schedBtn.getAttribute('data-club-schedule') || '';
            const titleEl = document.getElementById('GameSchedulemodalLabel');
            const bodyEl = document.getElementById('GameSchedulemodalBody');
            if (titleEl) titleEl.textContent = name;
            renderSafeModalContent(bodyEl, schedule);
        }
    });

    /** 2. Payment Modal Logic. */
    const paymentModalEl = document.getElementById('PaymentModal');
    const paymentModal = (paymentModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal)
        ? new bootstrap.Modal(paymentModalEl)
        : null;

    if (paymentModalEl) {
        paymentModalEl.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget || document.activeElement;
            if (button && button.getAttribute) {
                const modalId = document.getElementById('modalTournamentId');
                const modalName = document.getElementById('modalTournamentName');
                const modalAmt = document.getElementById('modalTournamentAmountDisplay');
                const errEl = document.getElementById('paymentError');

                if (modalId) modalId.value = button.getAttribute('data-id') || '';
                if (modalName) modalName.textContent = button.getAttribute('data-name') || '';
                if (modalAmt) modalAmt.textContent = `CAD ${button.getAttribute('data-amount') || '0.00'}`;
                if (errEl) errEl.classList.add('d-none');
            }
        });
    }

    const paymentForm = document.getElementById('paymentForm');
    if (paymentForm && typeof jQuery !== 'undefined') {
        jQuery(paymentForm).on('submit', function (e) {
            e.preventDefault();
            const btn = jQuery('#submitBtn');
            btn.prop('disabled', true).text('Processing...');

            const apiUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + 'api/player_payment.php';
            jQuery.ajax({
                url: apiUrl,
                type: 'POST',
                data: jQuery(this).serialize(),
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        if (paymentModal) {
                            paymentModal.hide();
                        } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal && paymentModalEl) {
                            const m = bootstrap.Modal.getInstance(paymentModalEl) || new bootstrap.Modal(paymentModalEl);
                            m.hide();
                        } else if (window.jQuery && jQuery('#PaymentModal').modal) {
                            jQuery('#PaymentModal').modal('hide');
                        }
                        jQuery('#dispAmt').text(response.paid_amount);
                        jQuery('#successOverlay').css('display', 'flex').hide().fadeIn();
                    } else {
                        jQuery('#paymentError').text(response.message || 'Payment submission failed.').removeClass('d-none');
                        btn.prop('disabled', false).text('Submit Payment');
                    }
                },
                error: () => {
                    jQuery('#paymentError').text('Server error. Please try again.').removeClass('d-none');
                    btn.prop('disabled', false).text('Submit Payment');
                }
            });
        });
    }

    /** Set default payment date and time inputs. */
    const dateInput = document.getElementById('payment_date');
    const timeInput = document.getElementById('payment_time');
    if (dateInput && timeInput) {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        dateInput.value = `${year}-${month}-${day}`;

        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        timeInput.value = `${hours}:${minutes}`;
    }

    /** 3. Join Club AJAX Handler. */
    if (typeof jQuery !== 'undefined') {
        const defaultJoinHtml = '<svg viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm.5-5v1h1a.5.5 0 0 1 0 1h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0Zm-2-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path d="M2 13c0 1 1 1 1 1h5.256A4.493 4.493 0 0 1 8 12.5a4.49 4.49 0 0 1 1.544-3.393C9.077 9.038 8.564 9 8 9c-5 0-6 3-6 4Z"/></svg><span>Join</span>';

        jQuery(document).on('click', '.join-club-btn', function (e) {
            e.preventDefault();
            const btn = jQuery(this);
            const clubId = btn.data('club-id');

            if (btn.prop('disabled')) return;
            btn.prop('disabled', true).text('Joining...');

            jQuery.ajax({
                url: 'api/join_club.php',
                type: 'POST',
                data: {
                    club_id: clubId,
                    csrf_token: jQuery('input[name="csrf_token"]').val() || ''
                },
                dataType: 'json',
                success: (response) => {
                    if (response.status === 'success' || response.success) {
                        const msg = response.message || 'Joined club successfully!';
                        if (typeof showToast === 'function') {
                            showToast('success', msg);
                        } else if (typeof toastr !== 'undefined') {
                            toastr.success(msg);
                        }
                        setTimeout(() => { location.reload(); }, 1200);
                    } else {
                        const errMsg = response.message || 'Error occurred while joining.';
                        if (typeof showToast === 'function') {
                            showToast('error', errMsg);
                        } else if (typeof toastr !== 'undefined') {
                            toastr.error(errMsg);
                        } else {
                            alert(errMsg);
                        }
                        btn.prop('disabled', false).html(defaultJoinHtml);
                    }
                },
                error: () => {
                    if (typeof showToast === 'function') {
                        showToast('error', 'Server error while joining. Please try again.');
                    } else if (typeof toastr !== 'undefined') {
                        toastr.error('Server error while joining. Please try again.');
                    }
                    btn.prop('disabled', false).html(defaultJoinHtml);
                }
            });
        });
    }
});
})();
