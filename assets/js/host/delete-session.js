//Casa Host - Delete Game Session Module
//Handles session deletion confirmation, AJAX processing, and dynamic UI removal.

(function (window, $) {
    'use strict';

    const getBaseUrl = function () {
        return (typeof window.BASE_URL !== 'undefined' && window.BASE_URL)
            ? (window.BASE_URL.endsWith('/') ? window.BASE_URL : window.BASE_URL + '/')
            : '/';
    };

    const DeleteSessionModule = {
        init: function () {
            this.bindEvents();
        },

        askConfirmation: async function (eventId) {
            if (window.Casa && typeof window.Casa.confirmModal === 'function') {
                return await window.Casa.confirmModal({
                    title: 'Delete Scheduled Game',
                    text: 'Are you sure you want to permanently delete this game session?',
                    confirmText: 'Yes, Delete',
                    cancelText: 'Cancel',
                    icon: 'danger'
                });
            }

            if (window.CasaHost && window.CasaHost.PlayerActions && typeof window.CasaHost.PlayerActions.showConfirm === 'function') {
                return await window.CasaHost.PlayerActions.showConfirm({
                    title: 'Delete Scheduled Game',
                    message: 'Are you sure you want to permanently delete this game session?',
                    yesText: 'Yes, Delete',
                    noText: 'Cancel',
                    type: 'danger'
                });
            }

            if (typeof window.showCustomConfirm === 'function') {
                return await window.showCustomConfirm({
                    title: 'Delete Scheduled Game',
                    message: 'Are you sure you want to permanently delete this game session?',
                    yesText: 'Yes, Delete',
                    noText: 'Cancel',
                    type: 'danger'
                });
            }

            return window.confirm('Are you sure you want to delete this game session?');
        },

        onDeleteClick: async function (e) {
            e.preventDefault();
            if (e.stopImmediatePropagation) e.stopImmediatePropagation();
            const btn = this;
            const $btn = $(btn);
            const eventId = $btn.attr('data-id') || $btn.data('id');

            if (!eventId) {
                console.error('[DeleteSession] Missing event ID.');
                return;
            }

            const confirmed = await DeleteSessionModule.askConfirmation(eventId);
            if (!confirmed) return;

            const $card = $btn.closest('.discoverGames_card, .col');
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

            $.ajax({
                url: getBaseUrl() + 'api/delete_event.php',
                type: 'POST',
                data: { id: eventId },
                dataType: 'json',
                success: function (res) {
                    if (res && res.success) {
                        if (typeof window.showToast === 'function') {
                            window.showToast('success', 'Event deleted successfully!');
                        } else {
                            alert('Event deleted successfully!');
                        }

                        // Smoothly animate out the deleted card
                        if ($card.length > 0) {
                            $card.fadeOut(350, function () {
                                $(this).remove();
                                const wrapper = document.querySelector('#Scheduled .hostWrapper') || document.querySelector('.hostWrapper');
                                if (wrapper && !wrapper.querySelector('.discoverGames_card')) {
                                    DeleteSessionModule.refreshSchedule();
                                }
                            });
                        } else {
                            DeleteSessionModule.refreshSchedule();
                        }
                    } else {
                        $btn.prop('disabled', false).html('<i class="fa-regular fa-trash-can"></i>');
                        const errMsg = (res && res.error) || 'Failed to delete event.';
                        if (typeof window.showToast === 'function') {
                            window.showToast('error', errMsg);
                        } else {
                            alert(errMsg);
                        }
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html('<i class="fa-regular fa-trash-can"></i>');
                    if (typeof window.showToast === 'function') {
                        window.showToast('error', 'An error occurred while deleting the event.');
                    } else {
                        alert('An error occurred while processing your request.');
                    }
                }
            });
        },

        refreshSchedule: function () {
            if (window.CasaHost && window.CasaHost.DashboardFilters && typeof window.CasaHost.DashboardFilters.onScheduleFilterChange === 'function') {
                window.CasaHost.DashboardFilters.onScheduleFilterChange();
            } else if (typeof window.filterSchedule === 'function') {
                window.filterSchedule();
            } else if ($('#month').length > 0) {
                $('#month').trigger('change');
            } else {
                location.reload();
            }
        },

        bindEvents: function () {
            $(document).off('click.deleteSession', '.discoverGames_card .delete_btn, .delete_btn')
                       .on('click.deleteSession', '.discoverGames_card .delete_btn, .delete_btn', this.onDeleteClick);
        }
    };

    window.CasaHostDeleteSession = DeleteSessionModule;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            DeleteSessionModule.init();
        });
    } else {
        DeleteSessionModule.init();
    }
})(window, window.jQuery || window.$);
