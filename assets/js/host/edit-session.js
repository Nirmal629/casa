/**
 * Casa Host - Edit Game Session Module
 * Handles opening, populating, calculating, validating, saving, and automating scheduled game sessions.
 */
(function (window, $) {
    'use strict';

    const getBaseUrl = function () {
        return (typeof window.BASE_URL !== 'undefined' && window.BASE_URL)
            ? (window.BASE_URL.endsWith('/') ? window.BASE_URL : window.BASE_URL + '/')
            : '/';
    };

    const EditSessionModule = {
        init: function () {
            this.initTimeDropdowns();
            this.initCalendarValidation();
            this.bindEvents();
        },

        initTimeDropdowns: function () {
            const timeIds = ['eventTimee', 'toTimee', 'freezeTimee'];
            timeIds.forEach(function (id) {
                const select = document.getElementById(id);
                if (!select || select.options.length > 1) return;

                select.innerHTML = '';
                for (let h = 0; h < 24; h++) {
                    for (let m = 0; m < 60; m += 30) {
                        const hour = h < 10 ? '0' + h : String(h);
                        const minute = m < 10 ? '0' + m : String(m);
                        const val = hour + ':' + minute;
                        select.appendChild(new Option(val, val));
                    }
                }
            });
        },

        initCalendarValidation: function () {
            if (typeof window.initDynamicEventCalendar === 'function') {
                window.initDynamicEventCalendar({
                    date: '#eventDatee',
                    freezeDate: '#freezeDatee',
                    fromTime: '#eventTimee',
                    toTime: '#toTimee',
                    freezeTime: '#freezeTimee'
                });
            }
        },

        calculateHours: function () {
            const fromTime = $('#eventTimee').val();
            const toTime = $('#toTimee').val();
            if (!fromTime || !toTime) return;

            const fromParts = fromTime.split(':').map(Number);
            const toParts = toTime.split(':').map(Number);
            let diffMinutes = (toParts[0] * 60 + toParts[1]) - (fromParts[0] * 60 + fromParts[1]);
            if (diffMinutes < 0) diffMinutes += 1440;

            const decimalHours = (diffMinutes / 60).toFixed(2);
            $('#hours').val(decimalHours);
        },

        parseJson: function (str) {
            if (typeof str !== 'string') return str;
            try { return JSON.parse(str); } catch (e) { return null; }
        },

        onEditClick: function (e) {
            e.preventDefault();
            if (e.stopImmediatePropagation) e.stopImmediatePropagation();
            const id = $(this).attr('data-id') || $(this).data('id');
            const dataEl = document.getElementById('data_' + id);
            if (!dataEl || !dataEl.value) return;

            const data = EditSessionModule.parseJson(dataEl.value) || {};

            $('#EVENT_IDD').val(id);
            $('#evnt_id').val(data.ID || id);
            $('#host-namee').val(data.HOST_NAME || '');
            $('#eventCountryy').val(data.EVENT_COUNTRY || '');
            $('#eventProvincee').val(data.EVENT_PROVINCE || '');
            $('#eventCityy').val(data.EVENT_CITY || '');
            $('#eventCurrencyy').val(data.EVENT_CURRENCY || 'INR');
            $('#eventVenuee').val(data.EVENT_VENUE || '');
            $('#eventCategoryy').val(data.EVENT_CATEGORY || '');
            $('#genderCategoryy').val(data.GENDER_CATEGORY || 'Male');
            $('#genderSkillLevell').val(data.GENDER_SKILL_LEVEL || 'Beginner');
            $('#eventTypee').val(data.EVENT_TYPE || 'Public');
            $('#statusevent').val(data.STATUS || 'Active');
            $('#eventDatee').val(data.EVENT_DATE || '');
            $('#freezeDatee').val(data.CANCEL_DATE || '');

            const fromTime = (data.EVENT_TIME || '').slice(0, 5);
            const toTime = (data.TO_TIME || '').slice(0, 5);
            const cancelTime = (data.CANCEL_TIME || '').slice(0, 5);

            $('#eventTimee').val(fromTime);
            $('#toTimee').val(toTime);
            $('#freezeTimee').val(cancelTime);

            EditSessionModule.calculateHours();

            $('#eventCostt').val(data.EVENT_COST || '');
            $('#eventDiscountt').val(data.EVENT_DISCOUNT || '');
            $('#eventDescriptionn').val(data.EVENT_DESCRIPTION || '');
            $('#eventMessagee').val(data.EVENT_MESSAGE || '');
            $('#facilitycost').val(data.FACILITY_COST || '');
            $('#accessoriesCost').val(data.ACCESSORIES_COST || '');
            $('#snackscost').val(data.SNACKS_COST || '');
            $('#eventtotalCostt').val(data.TOTAL_EVENT_COST || '');
            $('#eventtotalplayerCostt').val(data.TOTAL_PLAYER_COST || '');
            $('#profitloss').val(data.PROFIT_LOSS || '');
            $('#birdieUsed').val(data.BIRDIE_USED || '');
            $('#clubClost').val(data.CLUB_COST || '');
            $('#nobirdieUsed').val(data.BIRDIE_PRICE || '');
            $('#courtconfirmed').val(data.COURTS_CONFIRMED || '');

            $('#autoConfirm').prop('checked', data.AUTOMATION === 'Y');
            $('.hostgameupdate_modal').addClass('open');

            // Fetch live player & court metrics
            $.ajax({
                url: getBaseUrl() + 'api/get_player_cost.php',
                method: 'POST',
                data: { event_id: id },
                dataType: 'json',
                success: function (res) {
                    if (res && res.success) {
                        if (res.total_player_cost !== undefined) $('#eventtotalplayerCostt').val(res.total_player_cost);
                        if (res.court_cost !== undefined) $('#facilitycostperhour').val(res.court_cost);
                        if (res.confirmed_players !== undefined) $('#playersConfirmed').val(res.confirmed_players);
                        if (res.joined_players !== undefined) $('#playersJoined').val(res.joined_players);
                    }
                },
                error: function (err) {
                    console.warn('[EditSession] Live metrics fetch warning:', err);
                }
            });
        },

        validateForm: function (data) {
            const errors = [];
            if (!data.id) errors.push('Missing Event ID.');
            if (!data.event_city) errors.push('Event city is required.');
            if (!data.event_currency) errors.push('Event currency is required.');
            if (!data.event_venue) errors.push('Event venue is required.');
            if (!data.event_category) errors.push('Event category is required.');
            if (!data.event_date) errors.push('Event date is required.');
            if (!data.event_time) errors.push('Event start time is required.');
            if (!data.to_time) errors.push('Event end time is required.');
            if (!data.event_cost || isNaN(data.event_cost)) errors.push('Valid player cost is required.');
            return errors;
        },

        onSaveEdit: function (e) {
            e.preventDefault();
            if (e.stopImmediatePropagation) e.stopImmediatePropagation();
            const formData = {
                id: $('#evnt_id').val() || $('#EVENT_IDD').val(),
                event_city: $('#eventCityy').val(),
                event_currency: $('#eventCurrencyy').val(),
                event_venue: $('#eventVenuee').val(),
                event_category: $('#eventCategoryy').val(),
                gender_category: $('#genderCategoryy').val(),
                gender_skill_level: $('#genderSkillLevell').val(),
                event_type: $('#eventTypee').val(),
                event_date: $('#eventDatee').val(),
                event_time: $('#eventTimee').val(),
                to_time: $('#toTimee').val(),
                freeze_date: $('#freezeDatee').val(),
                freeze_time: $('#freezeTimee').val(),
                event_cost: $('#eventCostt').val(),
                event_discount: $('#eventDiscountt').val(),
                event_description: $('#eventDescriptionn').val(),
                event_message: $('#eventMessagee').val(),
                status: $('#statusevent').val(),
                facilitycost: $('#facilitycost').val(),
                accessoriesCost: $('#accessoriesCost').val(),
                snackscost: $('#snackscost').val(),
                eventtotalCostt: $('#eventtotalCostt').val(),
                eventtotalplayerCostt: $('#eventtotalplayerCostt').val(),
                profitloss: $('#profitloss').val(),
                birdieUsed: $('#birdieUsed').val(),
                clubClost: $('#clubClost').val(),
                updatePlayerPrice: $('#updatePlayerPrice').is(':checked') ? 'Y' : 'N',
                courtconfirmed: $('#courtconfirmed').val()
            };

            const errors = EditSessionModule.validateForm(formData);
            if (errors.length > 0) {
                if (typeof window.showToast === 'function') {
                    window.showToast('warning', errors.join('\n'));
                } else {
                    alert(errors.join('\n'));
                }
                return;
            }

            const $btn = $('#save_btn');
            $btn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: getBaseUrl() + 'api/update_event.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (res) {
                    $btn.prop('disabled', false).text('Submit');
                    if (res && res.success) {
                        if (typeof window.showToast === 'function') {
                            window.showToast('success', 'Event updated successfully!');
                        } else {
                            alert('Event updated successfully!');
                        }
                        $('.hostgameupdate_modal').removeClass('open');
                        EditSessionModule.refreshSchedule();
                    } else {
                        const errMsg = (res && res.error) || 'Failed to update event.';
                        if (typeof window.showToast === 'function') {
                            window.showToast('error', errMsg);
                        } else {
                            alert(errMsg);
                        }
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).text('Submit');
                    if (typeof window.showToast === 'function') {
                        window.showToast('error', 'An error occurred while updating the event.');
                    } else {
                        alert('An error occurred while updating the event.');
                    }
                }
            });
        },

        onCopyGame: function (e) {
            e.preventDefault();
            if (e.stopImmediatePropagation) e.stopImmediatePropagation();
            const formData = {
                id: $('#evnt_id').val() || $('#EVENT_IDD').val(),
                host_name: $('#host-namee').val(),
                event_country: $('#eventCountryy').val(),
                event_province: $('#eventProvincee').val(),
                event_city: $('#eventCityy').val(),
                event_currency: $('#eventCurrencyy').val(),
                event_venue: $('#eventVenuee').val(),
                event_category: $('#eventCategoryy').val(),
                gender_category: $('#genderCategoryy').val(),
                gender_skill_level: $('#genderSkillLevell').val(),
                event_type: $('#eventTypee').val(),
                event_date: $('#eventDatee').val(),
                event_time: $('#eventTimee').val(),
                to_time: $('#toTimee').val(),
                freeze_date: $('#freezeDatee').val(),
                freeze_time: $('#freezeTimee').val(),
                event_cost: $('#eventCostt').val(),
                event_discount: $('#eventDiscountt').val(),
                event_description: $('#eventDescriptionn').val(),
                event_message: $('#eventMessagee').val(),
                status: $('#statusevent').val()
            };

            const $btn = $('#copy_btn');
            $btn.prop('disabled', true).text('Copying...');

            $.ajax({
                url: getBaseUrl() + 'api/copy_event.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (res) {
                    $btn.prop('disabled', false).text('Copy Game');
                    if (res && res.success) {
                        if (typeof window.showToast === 'function') {
                            window.showToast('success', 'Event Copied & Created successfully!');
                        } else {
                            alert('Event Created successfully!');
                        }
                        $('.hostgameupdate_modal').removeClass('open');
                        EditSessionModule.refreshSchedule();
                    } else {
                        const errMsg = (res && res.error) || 'Failed to copy event.';
                        if (typeof window.showToast === 'function') {
                            window.showToast('error', errMsg);
                        } else {
                            alert(errMsg);
                        }
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).text('Copy Game');
                    if (typeof window.showToast === 'function') {
                        window.showToast('error', 'An error occurred while copying the event.');
                    } else {
                        alert('An error occurred while copying the event.');
                    }
                }
            });
        },

        onAutoConfirmChange: function () {
            const automationValue = this.checked ? 'Y' : 'N';
            const eventID = $('#EVENT_IDD').val() || $('#evnt_id').val();
            if (!eventID) return;

            $.ajax({
                url: getBaseUrl() + 'api/update-automation.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ AUTOMATION: automationValue, eventID: eventID }),
                dataType: 'json',
                success: function (res) {
                    if (typeof window.showToast === 'function') {
                        window.showToast('info', 'Automation setting updated (' + automationValue + ').');
                    }
                },
                error: function (err) {
                    console.error('[EditSession] Automation update error:', err);
                }
            });
        },

        refreshSchedule: function () {
            if (window.CasaHost && window.CasaHost.DashboardFilters && typeof window.CasaHost.DashboardFilters.onScheduleFilterChange === 'function') {
                window.CasaHost.DashboardFilters.onScheduleFilterChange();
            } else if (typeof window.filterSchedule === 'function') {
                window.filterSchedule();
            } else {
                const year = $('#year').val() || '';
                const month = $('#month').val() || '';
                $.ajax({
                    type: 'POST',
                    url: getBaseUrl() + 'api/filter_schedule.php',
                    data: { year: year, month: month, type: 'filter' },
                    success: function (htmlRes) {
                        $('#Scheduled .hostWrapper').html(htmlRes);
                        if (typeof window.initPopovers === 'function') window.initPopovers();
                    },
                    error: function (xhr, status, error) {
                        console.error('[EditSession] Schedule filter error:', error);
                        if (typeof window.showToast === 'function') {
                            window.showToast('error', 'Failed to refresh schedule.');
                        }
                    }
                });
            }
        },

        bindEvents: function () {
            // Edit Game click
            $(document).off('click.editSession', '.discoverGames_card .edit_btn, .edit_btn')
                       .on('click.editSession', '.discoverGames_card .edit_btn, .edit_btn', this.onEditClick);

            // Close Edit Modal
            $(document).off('click.closeEditModal', '.hostgameupdate_modal .customModal_close')
                       .on('click.closeEditModal', '.hostgameupdate_modal .customModal_close', function () {
                           $('.hostgameupdate_modal').removeClass('open');
                       });

            // Recalculate hours on time changes
            $(document).off('change.calcHours', '#eventTimee, #toTimee')
                       .on('change.calcHours', '#eventTimee, #toTimee', this.calculateHours);

            // Save Edit Form
            $(document).off('click.saveEdit', '.hostgameupdate_modal #save_btn')
                       .on('click.saveEdit', '.hostgameupdate_modal #save_btn', this.onSaveEdit);

            // Copy Event Button
            $(document).off('click.copyGame', '.hostgameupdate_modal #copy_btn')
                       .on('click.copyGame', '.hostgameupdate_modal #copy_btn', this.onCopyGame);

            // Automation Checkbox
            $(document).off('change.autoConfirm', '#autoConfirm')
                       .on('change.autoConfirm', '#autoConfirm', this.onAutoConfirmChange);
        }
    };

    window.CasaHostEditSession = EditSessionModule;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            EditSessionModule.init();
        });
    } else {
        EditSessionModule.init();
    }
})(window, window.jQuery || window.$);
