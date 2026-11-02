/**
 * Casa Host Dashboard, Roster, Game Management & Payment Verification Scripts
 * Modular Namespace Architecture (Optimized, clean & DRY)
 */

window.CasaHost = window.CasaHost || {};

(function (window, $, CasaHost) {
    'use strict';

    CasaHost.Utils = {
        parseJson: function (data) {
            if (typeof data !== 'string') return data;
            try { return JSON.parse(data); } catch (e) { return null; }
        },
        getActionUrl: function (endpoint) {
            endpoint = endpoint || 'api/payment_action.php';
            return (typeof BASE_URL !== 'undefined' && BASE_URL)
                ? (BASE_URL.endsWith('/') ? BASE_URL + endpoint : BASE_URL + '/' + endpoint)
                : '/' + endpoint;
        }
    };

    CasaHost.GameManager = {
        init: function () {
            this.initTimeDropdowns();
            this.bindEvents();
        },

        initTimeDropdowns: function () {
            const dropdownIds = ["eventTime", "toTime", "freezeTime", "eventTimee", "toTimee", "freezeTimee"];
            dropdownIds.forEach(this.populateTimeDropdown);
        },

        populateTimeDropdown: function (selectId) {
            const select = document.getElementById(selectId);
            if (!select) return;

            for (let h = 0; h < 24; h++) {
                for (let m = 0; m < 60; m += 30) {
                    const hour = h < 10 ? "0" + h : h;
                    const minute = m < 10 ? "0" + m : m;
                    const timeValue = `${hour}:${minute}`;
                    select.appendChild(new Option(timeValue, timeValue));
                }
            }
        },

        validateEventForm: function (formData, requireHostName) {
            const errors = [];
            if (requireHostName && !formData.host_name) errors.push("Host name is required.");
            if (!formData.event_country && requireHostName) errors.push("Event country is required.");
            if (!formData.event_province && requireHostName) errors.push("Event province is required.");
            if (!formData.event_city) errors.push("Event city is required.");
            if (!formData.event_currency) errors.push("Event currency is required.");
            if (!formData.event_venue) errors.push("Event venue is required.");
            if (!formData.event_category) errors.push("Event category is required.");
            if (!formData.gender_category) errors.push("Gender category is required.");
            if (!formData.gender_skill_level) errors.push("Gender skill level is required.");
            if (!formData.event_type) errors.push("Event type is required.");
            if (!formData.event_date) errors.push("Event date is required.");
            if (!formData.event_time) errors.push("Event time is required.");
            if (!formData.to_time) errors.push("To time is required.");
            if (!formData.freeze_date) errors.push("Freeze date is required.");
            if (!formData.freeze_time) errors.push("Freeze time is required.");

            if (formData.event_date && formData.event_time && formData.freeze_date && formData.freeze_time) {
                var check = (typeof window.validateFreezeDateTime === 'function')
                    ? window.validateFreezeDateTime(formData.event_date, formData.event_time, formData.freeze_date, formData.freeze_time)
                    : null;
                if (check && !check.valid) {
                    errors.push(check.error);
                } else if (!check) {
                    var dtStart = new Date(formData.event_date + 'T' + formData.event_time);
                    var dtFreeze = new Date(formData.freeze_date + 'T' + formData.freeze_time);
                    if (!isNaN(dtStart.getTime()) && !isNaN(dtFreeze.getTime()) && dtFreeze.getTime() > dtStart.getTime()) {
                        errors.push("Booking freeze date & time cannot be later than event start date & time.");
                    }
                }
            }
            if (!formData.event_cost || isNaN(formData.event_cost)) errors.push("Event cost must be a valid number.");
            if (formData.event_discount && isNaN(formData.event_discount)) errors.push("Event discount must be a valid number.");
            if (!formData.event_description) errors.push("Event description is required.");
            if (!formData.event_message) errors.push("Event message is required.");
            if (!formData.status) errors.push("Status is required.");
            return errors;
        },

        bindEvents: function () {
            // Edit Game Modal Trigger
            $(document).on('click', '.discoverGames_card .edit_btn', this.onEditClick);

            // Close Edit Modal
            $('.hostgameupdate_modal .customModal_close').on('click', function () {
                $('.hostgameupdate_modal').removeClass('open');
            });

            // Save Event Edit
            $('.hostgameupdate_modal #save_btn').on('click', this.onSaveEdit);

            // Save Subscription Event Edit
            $('.hostgameupdate_modal #save_btn_subs').on('click', this.onSaveSubs);

            // Create Subscription Event from card button
            $(document).on('click', '.discoverGames_card_sub #create_btn, .discoverGames_card_sub .btn-success', this.onCreateSubCard);

            // Copy Game
            $('.hostgameupdate_modal #copy_btn').on('click', this.onCopyEvent);

            // Delete Game
            $(document).on('click', '.discoverGames_card .delete_btn, .session-card .delete_btn, .dock-btn-delete, .delete_btn', this.onDeleteEvent);

            // New Game Form Submission
            $("#eventFormm").on('submit', this.onNewGameSubmit);
        },

        onEditClick: function () {
            const id = $(this).attr('data-id');
            const data = $('#data_' + id).val();
            if (!data) return;

            const parseData = CasaHost.Utils.parseJson(data) || {};

            $('#EVENT_IDD').val(id);
            $('#host-namee').val(parseData.HOST_NAME);
            $('#eventCountryy').val(parseData.EVENT_COUNTRY);
            $('#eventProvincee').val(parseData.EVENT_PROVINCE);
            $('#eventCityy').val(parseData.EVENT_CITY);
            $('#eventCurrencyy').val(parseData.EVENT_CURRENCY);
            $('#eventVenuee').val(parseData.EVENT_VENUE);
            $('#eventCategoryy').val(parseData.EVENT_CATEGORY);
            $('#genderCategoryy').val(parseData.GENDER_CATEGORY);
            $('#genderSkillLevell').val(parseData.GENDER_SKILL_LEVEL);
            $('#eventTypee').val(parseData.EVENT_TYPE);
            $('#eventDatee').val(parseData.EVENT_DATE);

            setTimeout(function () {
                const fromTime = (parseData.EVENT_TIME || '').slice(0, 5); // HH:mm
                const toTime = (parseData.TO_TIME || '').slice(0, 5);      // HH:mm

                const freezeTime = (parseData.CANCEL_TIME || '').slice(0, 5);
                $('#eventTimee').val(fromTime).change();
                $('#toTimee').val(toTime).change();
                $('#freezeDatee').val(parseData.CANCEL_DATE);
                $('#freezeTimee').val(freezeTime).change();

                const isUntilStart = (!parseData.CANCEL_DATE || parseData.CANCEL_DATE === parseData.EVENT_DATE) &&
                                     (!freezeTime || freezeTime === fromTime);
                $('#freezeTypee').val(isUntilStart ? 'until_start' : 'custom').trigger('change');

                const [fromH, fromM] = fromTime.split(':').map(Number);
                const [toH, toM] = toTime.split(':').map(Number);

                let diffMinutes = (toH * 60 + toM) - (fromH * 60 + fromM);
                if (diffMinutes < 0) diffMinutes += 1440;

                const decimalHours = (diffMinutes / 60).toFixed(2);
                $("#hours").val(decimalHours);
            }, 1000);

            $('#eventCostt').val(parseData.EVENT_COST);
            $('#eventDiscountt').val(parseData.EVENT_DISCOUNT);
            $('#eventDescriptionn').val(parseData.EVENT_DESCRIPTION);
            $('#eventMessagee').val(parseData.EVENT_MESSAGE);
            $('#statusevent').val(parseData.STATUS);
            $('#evnt_id').val(parseData.ID);
            $('#facilitycost').val(parseData.FACILITY_COST);
            $('#accessoriesCost').val(parseData.ACCESSORIES_COST);
            $('#snackscost').val(parseData.SNACKS_COST);
            $('#eventtotalCostt').val(parseData.TOTAL_EVENT_COST);
            $('#eventtotalplayerCostt').val(parseData.TOTAL_PLAYER_COST);
            $('#profitloss').val(parseData.PROFIT_LOSS);
            $('#birdieUsed').val(parseData.BIRDIE_USED);
            $('#clubClost').val(parseData.CLUB_COST);
            $('#nobirdieUsed').val(parseData.BIRDIE_PRICE);
            $('#courtconfirmed').val(parseData.COURTS_CONFIRMED);

            $('#autoConfirm').prop('checked', parseData.AUTOMATION === 'Y');
            $('.hostgameupdate_modal').addClass('open');

            $.ajax({
                url: BASE_URL + 'api/get_player_cost.php',
                method: 'POST',
                data: { event_id: id },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        $('#eventtotalplayerCostt').val(response.total_player_cost);
                        $('#facilitycostperhour').val(response.court_cost);
                        $('#playersConfirmed').val(response.confirmed_players);
                        $('#playersJoined').val(response.joined_players);
                    } else {
                        console.warn('[GameManager] Failed to load player cost:', response.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('[GameManager AJAX error]', error);
                }
            });
        },

        onSaveEdit: function () {
            const formData = {
                id: $('#evnt_id').val(),
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
                freeze_type: $('#freezeTypee').val(),
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

            const errors = CasaHost.GameManager.validateEventForm(formData, false);
            if (errors.length > 0) {
                alert(errors.join('\n'));
                return;
            }

            $.ajax({
                url: BASE_URL + 'api/update_event.php',
                type: 'POST',
                data: formData,
                success: function (response) {
                    const res = CasaHost.Utils.parseJson(response) || response;
                    if (res && res.success) {
                        alert('Event updated successfully!');
                        $('.hostgameupdate_modal').removeClass('open');
                        if (CasaHost.DashboardFilters && typeof CasaHost.DashboardFilters.onScheduleFilterChange === 'function') {
                            CasaHost.DashboardFilters.onScheduleFilterChange();
                        }
                        if (CasaHost.DashboardFilters && typeof CasaHost.DashboardFilters.filterCompletedSchedule === 'function') {
                            if (formData.event_date && (formData.status === 'Completed' || formData.status === 'Cancelled')) {
                                const dateParts = String(formData.event_date).split('-');
                                if (dateParts.length >= 2) {
                                    const evYear = parseInt(dateParts[0], 10);
                                    const evMonth = parseInt(dateParts[1], 10);
                                    if (evYear && evMonth) {
                                        $('#com_year').val(evYear);
                                        $('#com_month').val(evMonth);
                                    }
                                }
                            }
                            CasaHost.DashboardFilters.filterCompletedSchedule();
                        }
                    } else {
                        alert('Failed to update event. ' + ((res && res.error) || ''));
                    }
                },
                error: function () {
                    alert('An error occurred while updating the event.');
                }
            });
        },

        onSaveSubs: function () {
            const eventDate = $('#eventDateee').val();
            const eventTime = $('#eventTimeee').val();

            if (!eventDate) {
                alert('Please select an event date.');
                $('#eventDateee').focus();
                return;
            }

            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const todayStr = `${year}-${month}-${day}`;

            if (eventDate < todayStr) {
                alert('Past dates are not allowed. Please select today or a future date.');
                $('#eventDateee').focus();
                return;
            }

            if (eventDate === todayStr && eventTime) {
                const parts = eventTime.split(':');
                const startH = parseInt(parts[0], 10) || 0;
                const startM = parseInt(parts[1], 10) || 0;
                const curH = now.getHours();
                const curM = now.getMinutes();

                if (curH > startH || (curH === startH && curM >= startM)) {
                    alert('Game start time has already passed for today. Please select a future date or adjust the start time.');
                    return;
                }
            }

            const freezeDate = $('#freezeDateee').val();
            if (freezeDate && eventDate && freezeDate > eventDate) {
                alert('Freeze date cannot be later than event date.');
                return;
            }

            const csrfToken = $('#csrf_token_sub').val() ||
                              (window.Casa && typeof window.Casa.csrfToken === 'function' ? window.Casa.csrfToken() : '') ||
                              $('input[name="csrf_token"]').val() || '';

            const formData = {
                csrf_token: csrfToken,
                id: $('#event_sub_id').val(),
                host_name: $('#host-nameee').val(),
                event_country: $('#eventCountryyy').val(),
                event_province: $('#eventProvinceee').val(),
                event_city: $('#eventCityyy').val(),
                event_currency: $('#eventCurrencyyy').val(),
                event_venue: $('#eventVenueee').val(),
                event_category: $('#eventCategoryyy').val(),
                gender_category: $('#genderCategoryyy').val(),
                gender_skill_level: $('#genderSkillLevelll').val(),
                event_type: $('#eventTypeee').val(),
                event_date: eventDate,
                event_time: eventTime,
                to_time: $('#toTimeee').val(),
                freeze_type: $('#freezeTypeee').val(),
                freeze_date: $('#freezeDateee').val(),
                freeze_time: $('#freezeTimeee').val(),
                event_cost: $('#eventCosttt').val(),
                event_discount: $('#eventDiscounttt').val(),
                event_description: $('#eventDescriptionnn').val(),
                event_message: $('#eventMessageee').val(),
                status: $('#statuseventt').val(),
                facilitycost: $('#facilitycostt').val(),
                accessoriesCost: $('#accessoriesCostt').val(),
                snackscost: $('#snackscostt').val(),
                eventtotalCostt: $('#eventtotalCosttt').val(),
                eventtotalplayerCostt: $('#eventtotalplayerCosttt').val(),
                profitloss: $('#profitlosss').val(),
            };

            const $btn = $('#save_btn_subs');
            $btn.prop('disabled', true).text('Creating Game...');

            $.ajax({
                url: CasaHost.Utils.getActionUrl('api/create_event_sub.php'),
                type: 'POST',
                data: formData,
                headers: csrfToken ? { 'X-CSRF-Token': csrfToken } : {},
                success: function (response) {
                    const res = CasaHost.Utils.parseJson(response) || response;
                    if (res && res.success) {
                        alert('Event Created successfully!');
                        $('.hostgameupdate_modal').removeClass('open');
                        window.location.href = 'host-dashboard.php';
                    } else {
                        alert('Failed to create event. ' + ((res && (res.error || res.message)) || ''));
                    }
                },
                error: function (xhr) {
                    const res = xhr.responseJSON || CasaHost.Utils.parseJson(xhr.responseText);
                    const errMsg = (res && (res.error || res.message)) ? (res.error || res.message) : 'An error occurred while creating the event.';
                    alert(errMsg);
                },
                complete: function () {
                    $btn.prop('disabled', false).text('Create Game');
                }
            });
        },

        onCreateSubCard: function (e) {
            if (e && e.preventDefault) e.preventDefault();
            const id = $(this).attr('data-id');
            if (typeof window.openCreateSubGameModal === 'function') {
                window.openCreateSubGameModal(id);
                return;
            }

            const data = $('#data_' + id).val();
            if (!data) return;

            const parseData = CasaHost.Utils.parseJson(data) || {};

            const eventTimeFormatted = (parseData.EVENT_TIME || '').slice(0, 5);
            const toTimeFormatted = (parseData.TO_TIME || '').slice(0, 5);
            const freezeTimeFormatted = (parseData.CANCEL_TIME || '').slice(0, 5);

            const dayName = parseData.DAY ? parseData.DAY.toUpperCase().trim() : "";
            const dayIndexMap = {
                "SUNDAY": 0, "MONDAY": 1, "TUESDAY": 2, "WEDNESDAY": 3,
                "THURSDAY": 4, "FRIDAY": 5, "SATURDAY": 6
            };

            const now = new Date();
            let eventDate = '';
            let freezeDate = '';

            if (dayIndexMap.hasOwnProperty(dayName)) {
                const targetDay = dayIndexMap[dayName];
                const currentDay = now.getDay();
                let diff = (targetDay - currentDay + 7) % 7;

                if (diff === 0 && eventTimeFormatted) {
                    const parts = eventTimeFormatted.split(':');
                    const startH = parseInt(parts[0], 10) || 0;
                    const startM = parseInt(parts[1], 10) || 0;
                    if (now.getHours() > startH || (now.getHours() === startH && now.getMinutes() >= startM)) {
                        diff = 7;
                    }
                }

                const evObj = new Date(now.getFullYear(), now.getMonth(), now.getDate() + diff);
                const ey = evObj.getFullYear();
                const em = String(evObj.getMonth() + 1).padStart(2, '0');
                const ed = String(evObj.getDate()).padStart(2, '0');
                eventDate = `${ey}-${em}-${ed}`;

                const frObj = new Date(evObj);
                if (targetDay === 6) {
                    frObj.setDate(frObj.getDate() - 1);
                }
                const fy = frObj.getFullYear();
                const fm = String(frObj.getMonth() + 1).padStart(2, '0');
                const fd = String(frObj.getDate()).padStart(2, '0');
                freezeDate = `${fy}-${fm}-${fd}`;
            } else {
                const ey = now.getFullYear();
                const em = String(now.getMonth() + 1).padStart(2, '0');
                const ed = String(now.getDate()).padStart(2, '0');
                eventDate = `${ey}-${em}-${ed}`;
                freezeDate = eventDate;
            }

            const csrfToken = (window.Casa && typeof window.Casa.csrfToken === 'function' ? window.Casa.csrfToken() : '') ||
                              $('input[name="csrf_token"]').val() || '';

            const formData = {
                csrf_token: csrfToken,
                id: id,
                host_id: parseData.HOST_ID || '',
                host_name: parseData.HOST_NAME,
                event_country: parseData.EVENT_COUNTRY,
                event_province: parseData.EVENT_PROVINCE,
                event_city: parseData.EVENT_CITY,
                event_currency: parseData.EVENT_CURRENCY,
                event_venue: parseData.EVENT_VENUE,
                event_category: parseData.EVENT_CATEGORY,
                gender_category: parseData.GENDER_CATEGORY,
                gender_skill_level: parseData.GENDER_SKILL_LEVEL,
                event_type: parseData.EVENT_TYPE,
                event_date: eventDate,
                event_time: eventTimeFormatted,
                to_time: toTimeFormatted,
                freeze_time: freezeTimeFormatted,
                freeze_date: freezeDate,
                event_cost: parseData.EVENT_COST,
                event_discount: parseData.EVENT_DISCOUNT,
                event_description: parseData.EVENT_DESCRIPTION,
                event_message: parseData.EVENT_MESSAGE,
                status: parseData.STATUS,
                facilitycost: parseData.FACILITY_COST,
                accessoriesCost: parseData.ACCESSORIES_COST,
                snackscost: parseData.SNACKS_COST,
                eventtotalCostt: parseData.TOTAL_EVENT_COST,
                eventtotalplayerCostt: parseData.TOTAL_PLAYER_COST,
                profitloss: parseData.PROFIT_LOSS
            };

            $.ajax({
                url: CasaHost.Utils.getActionUrl('api/create_event_sub.php'),
                type: 'POST',
                data: formData,
                headers: csrfToken ? { 'X-CSRF-Token': csrfToken } : {},
                success: function (response) {
                    const res = CasaHost.Utils.parseJson(response) || response;
                    if (res && res.success) {
                        alert('Event Created successfully!');
                        window.location.href = 'host-dashboard.php?tab=Subscription';
                    } else {
                        alert('Failed to create event: ' + ((res && (res.error || res.message)) || ''));
                    }
                },
                error: function (xhr) {
                    const res = xhr.responseJSON || CasaHost.Utils.parseJson(xhr.responseText);
                    const errMsg = (res && (res.error || res.message)) ? (res.error || res.message) : 'An error occurred while creating the event.';
                    alert(errMsg);
                }
            });
        },

        onCopyEvent: function () {
            const formData = {
                id: $('#evnt_id').val(),
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
                freeze_type: $('#freezeTypee').val(),
                freeze_date: $('#freezeDatee').val(),
                freeze_time: $('#freezeTimee').val(),
                event_cost: $('#eventCostt').val(),
                event_discount: $('#eventDiscountt').val(),
                event_description: $('#eventDescriptionn').val(),
                event_message: $('#eventMessagee').val(),
                status: $('#statusevent').val(),
            };

            const errors = CasaHost.GameManager.validateEventForm(formData, true);
            if (errors.length > 0) {
                alert(errors.join('\n'));
                return;
            }

            $.ajax({
                url: BASE_URL + 'api/copy_event.php',
                type: 'POST',
                data: formData,
                success: function (response) {
                    const res = CasaHost.Utils.parseJson(response) || response;
                    if (res && res.success) {
                        alert('Event Created successfully!');
                        $('.hostgameupdate_modal').removeClass('open');
                        if (CasaHost.DashboardFilters && typeof CasaHost.DashboardFilters.onScheduleFilterChange === 'function') {
                            CasaHost.DashboardFilters.onScheduleFilterChange();
                        }
                    } else {
                        alert('Failed to update event. ' + ((res && res.error) || ''));
                    }
                },
                error: function () {
                    alert('An error occurred while updating the event.');
                }
            });
        },

        onDeleteEvent: async function (e) {
            if (e && e.preventDefault) e.preventDefault();
            if (e && e.stopPropagation) e.stopPropagation();

            const $btn = $(this);
            const eventId = $btn.attr('data-id') || $btn.data('id');
            if (!eventId) {
                console.error('[Host] Missing event ID for deletion.');
                return;
            }

            const confirmed = await CasaHost.PlayerActions.showConfirm({
                title: 'Delete Scheduled Game',
                message: 'Are you sure you want to permanently delete this game session?',
                yesText: 'Yes, Delete',
                noText: 'Cancel',
                type: 'danger'
            });
            if (!confirmed) return;

            const $card = $btn.closest('.discoverGames_card, .session-card, .col');
            const originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

            const apiUrl = (typeof BASE_URL !== 'undefined' && BASE_URL ? (BASE_URL.endsWith('/') ? BASE_URL : BASE_URL + '/') : '/') + 'api/delete-event';

            $.ajax({
                url: apiUrl,
                type: 'POST',
                data: { id: eventId },
                dataType: 'json',
                success: function (response) {
                    const res = CasaHost.Utils.parseJson(response) || response;
                    if (res && (res.success || res.status === 'success')) {
                        if (typeof window.showToast === 'function') {
                            window.showToast('success', 'Event deleted successfully!');
                        } else if (window.Casa && typeof window.Casa.showToast === 'function') {
                            window.Casa.showToast('Event deleted successfully!', 'success');
                        }

                        if ($card.length > 0) {
                            $card.fadeOut(350, function () {
                                $(this).remove();
                                if (typeof window.filterSchedule === 'function') {
                                    window.filterSchedule();
                                } else if ($("#month").length > 0) {
                                    $("#month").trigger("change");
                                }
                            });
                        } else {
                            if (typeof window.filterSchedule === 'function') {
                                window.filterSchedule();
                            } else if ($("#month").length > 0) {
                                $("#month").trigger("change");
                            } else {
                                location.reload();
                            }
                        }
                    } else {
                        $btn.prop('disabled', false).html(originalHtml);
                        const errMsg = (res && (res.error || res.message)) || 'Failed to delete event.';
                        if (typeof window.showToast === 'function') {
                            window.showToast('error', errMsg);
                        } else {
                            alert(errMsg);
                        }
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html(originalHtml);
                    const errMsg = (xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message)) || 'An error occurred while deleting the event.';
                    if (typeof window.showToast === 'function') {
                        window.showToast('error', errMsg);
                    } else {
                        alert(errMsg);
                    }
                }
            });
        },

        onNewGameSubmit: function (e) {
            e.preventDefault();
            const formEl = this;
            const $form = $(this);

            function triggerToast(type, msg) {
                if (typeof window.showToast === 'function') {
                    window.showToast(type, msg);
                } else if (window.Casa && typeof window.Casa.showToast === 'function') {
                    window.Casa.showToast(msg, type);
                } else if (typeof toastr !== 'undefined' && typeof toastr[type] === 'function') {
                    toastr[type](msg);
                } else {
                    alert(msg);
                }
            }

            const eventDate = $form.find('#eventDate').val();
            const eventTime = $form.find('#eventTime').val();
            const freezeDate = $form.find('#freezeDate').val();
            const freezeTime = $form.find('#freezeTime').val();

            if (eventDate && eventTime && freezeDate && freezeTime) {
                var check = (typeof window.validateFreezeDateTime === 'function')
                    ? window.validateFreezeDateTime(eventDate, eventTime, freezeDate, freezeTime)
                    : null;
                if (check && !check.valid) {
                    triggerToast("error", check.error);
                    const freezeTimeEl = document.getElementById('freezeTime');
                    if (freezeTimeEl) {
                        freezeTimeEl.setCustomValidity(check.error);
                        if (typeof freezeTimeEl.reportValidity === 'function') {
                            freezeTimeEl.reportValidity();
                        }
                    }
                    return;
                }
            }

            if (typeof formEl.checkValidity === 'function' && !formEl.checkValidity()) {
                if (typeof formEl.reportValidity === 'function') {
                    formEl.reportValidity();
                }
                return;
            }

            const $submitBtn = $form.find('button[type="submit"]');
            const originalBtnHtml = $submitBtn.html();
            $submitBtn.prop('disabled', true);

            const formData = $form.serialize();

            $.ajax({
                type: "POST",
                url: BASE_URL + "api/new-game-host.php",
                data: formData,
                dataType: "json",
                success: function (response) {
                    $submitBtn.prop('disabled', false).html(originalBtnHtml);
                    if (response && response.success) {
                        triggerToast("success", "New Event Created Successfully.");
                        $form[0].reset();
                        setTimeout(function () {
                            const scheduleTab = document.querySelector('#Scheduled-tab');
                            if (scheduleTab && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
                                const tab = new bootstrap.Tab(scheduleTab);
                                tab.show();
                            }
                            location.reload();
                        }, 1200);
                    } else {
                        const errMsg = (response && response.message) ? response.message : "Failed to create event.";
                        triggerToast("error", errMsg);
                    }
                },
                error: function (xhr) {
                    $submitBtn.prop('disabled', false).html(originalBtnHtml);
                    let errMsg = "Something went wrong! Please try again.";
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    }
                    triggerToast("error", errMsg);
                }
            });
        }
    };

    // 2. PLAYER ACTIONS (DIRECT JOIN & CANCEL FROM CARD - NO MODAL)

    CasaHost.PlayerActions = {
        init: function () {
            this.bindEvents();
        },

        showConfirm: function (options) {
            // Lightweight fallback without modal HTML
            const message = (options && options.message) ? options.message : 'Are you sure you want to proceed?';
            return Promise.resolve(window.confirm(message));
        },

        bindEvents: function () {
            $(document).on('click', '.actionJC', this.onJoinClick);
            $(document).on('click', '.discoverGames_card_sub .join_btn', this.onSubJoinClick);
            $(document).on('click', '.actionC', this.onCancelClick);
            $(document).on('click', '.actionAC', this.onAcceptInviteClick);
        },

        onJoinClick: function (e) {
            if (e && e.preventDefault) e.preventDefault();
            const $btn = $(this);
            if ($btn.prop('disabled') || $btn.hasClass('loading')) return;

            const id = $btn.attr('data-id');
            const data = $('#data_' + id).val();
            if (!data) return;

            const parseData = CasaHost.Utils.parseJson(data) || {};
            const userId = $("#user_" + id).val();
            const originalHtml = $btn.html();

            // Set loading state on button
            $btn.prop('disabled', true).addClass('loading').html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Joining...');

            $.ajax({
                url: BASE_URL + 'api/join-event',
                type: 'POST',
                data: { ...parseData, USER_ID: userId },
                success: function (response) {
                    const res = CasaHost.Utils.parseJson(response) || response;
                    if (res && (res.status === 'error' || res.success === false)) {
                        $btn.prop('disabled', false).removeClass('loading').html(originalHtml);
                        const errMsg = res.message || 'Failed to join event.';
                        if (window.Casa && typeof window.Casa.showToast === 'function') {
                            window.Casa.showToast(errMsg, 'error');
                        } else {
                            alert(errMsg);
                        }
                        return;
                    }

                    // Direct toggle: Join -> Cancel
                    $btn.removeClass('actionJC btn-join loading')
                        .addClass('actionC btn-cancel')
                        .prop('disabled', false)
                        .html('<i class="fa-solid fa-xmark me-1"></i>Cancel');

                    // Update card stats in-place using shared helper
                    updateSchedulerCardStats($btn.closest('.discoverGames_card, .player_cards, .session-card'), res, id);

                    if (window.Casa && typeof window.Casa.showToast === 'function') {
                        window.Casa.showToast(res.message || 'Event joined successfully.', 'success');
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).removeClass('loading').html(originalHtml);
                    const res = xhr.responseJSON || CasaHost.Utils.parseJson(xhr.responseText);
                    const errMsg = (res && res.message) ? res.message : 'An error occurred while joining the event.';
                    if (window.Casa && typeof window.Casa.showToast === 'function') {
                        window.Casa.showToast(errMsg, 'error');
                    } else {
                        alert(errMsg);
                    }
                }
            });
        },

        onSubJoinClick: function (e) {
            if (e && e.preventDefault) e.preventDefault();
            const $btn = $(this);
            if ($btn.prop('disabled') || $btn.hasClass('loading')) return;

            const eventId = $btn.attr('data-id');
            const isJoined = $btn.attr('data-joined') === '1';

            const data = $('#data_' + eventId).val();
            const parseData = CasaHost.Utils.parseJson(data) || {};
            const userId = $("#user_" + eventId).val();
            const url = isJoined ? 'api/cancel-event-default' : 'api/join-event-default';

            $btn.prop('disabled', true).addClass('loading');

            $.ajax({
                url: BASE_URL + url,
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
                    if (window.Casa && typeof window.Casa.showToast === 'function') {
                        window.Casa.showToast('An error occurred while processing your request.', 'error');
                    } else {
                        alert('An error occurred while processing your request.');
                    }
                }
            });
        },

        onCancelClick: function (e) {
            if (e && e.preventDefault) e.preventDefault();
            const $btn = $(this);
            if ($btn.prop('disabled') || $btn.hasClass('loading')) return;

            const id = $btn.attr('data-id');
            const data = $('#data_' + id).val();
            if (!data) return;

            const parseData = CasaHost.Utils.parseJson(data) || {};
            const userId = $("#user_" + id).val();
            const originalHtml = $btn.html();

            // Set loading state on button
            $btn.prop('disabled', true).addClass('loading').html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Cancelling...');

            $.ajax({
                url: BASE_URL + 'api/cancel-event',
                type: 'POST',
                data: { ...parseData, USER_ID: userId },
                success: function (response) {
                    const res = CasaHost.Utils.parseJson(response) || response;
                    if (res && (res.status === 'error' || res.success === false)) {
                        $btn.prop('disabled', false).removeClass('loading').html(originalHtml);
                        const errMsg = res.message || 'Failed to cancel event.';
                        if (window.Casa && typeof window.Casa.showToast === 'function') {
                            window.Casa.showToast(errMsg, 'error');
                        } else {
                            alert(errMsg);
                        }
                        return;
                    }

                    // Direct toggle: Cancel -> Join
                    $btn.removeClass('actionC btn-cancel loading')
                        .addClass('actionJC btn-join')
                        .prop('disabled', false)
                        .html('<i class="fa-solid fa-circle-plus me-1"></i>Join Game');

                    // Update card stats in-place using shared helper
                    updateSchedulerCardStats($btn.closest('.discoverGames_card, .player_cards, .session-card'), res, id);

                    if (window.Casa && typeof window.Casa.showToast === 'function') {
                        window.Casa.showToast(res.message || 'Participation cancelled successfully.', 'success');
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).removeClass('loading').html(originalHtml);
                    const res = xhr.responseJSON || CasaHost.Utils.parseJson(xhr.responseText);
                    const errMsg = (res && res.message) ? res.message : 'An error occurred while processing your request.';
                    if (window.Casa && typeof window.Casa.showToast === 'function') {
                        window.Casa.showToast(errMsg, 'error');
                    } else {
                        alert(errMsg);
                    }
                }
            });
        },

        onAcceptInviteClick: function (e) {
            if (e && e.preventDefault) e.preventDefault();
            const $btn = $(this);
            if ($btn.prop('disabled') || $btn.hasClass('loading')) return;

            const id = $btn.attr('data-id');
            const data = $('#data_' + id).val();
            if (!data) return;

            const parseData = CasaHost.Utils.parseJson(data) || {};
            const userId = $("#user_" + id).val();
            const originalHtml = $btn.html();

            // Set loading state on button
            $btn.prop('disabled', true).addClass('loading').html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Accepting...');

            $.ajax({
                url: BASE_URL + 'api/update-invite-join',
                type: 'POST',
                data: { ...parseData, USER_ID: userId },
                success: function (response) {
                    const res = CasaHost.Utils.parseJson(response) || response;
                    if (res && (res.status === 'success' || res.success)) {
                        $btn.removeClass('actionAC btn-accept loading')
                            .addClass('actionC btn-cancel')
                            .prop('disabled', false)
                            .html('<i class="fa-solid fa-xmark me-1"></i>Cancel');

                        const $card = $btn.closest('.discoverGames_card, .player_cards, .session-card');
                        if ($card.length > 0) {
                            if (res.total_confirmed !== undefined) {
                                $card.find('.session-stat-confirm .stat-value').text(res.total_confirmed);
                                $card.find('.session-stat-confirm').attr('data-bs-content', 'Total Confirmed: ' + res.total_confirmed);
                            }
                            if (res.court_status_text) {
                                $card.find('.session-court-status-text').text(res.court_status_text);
                            }
                            if (res.court_status_badge) {
                                $card.find('.session-court-badge').text(res.court_status_badge);
                            }
                            if (res.formatted_cost) {
                                const costText = String(res.formatted_cost).replace(/^CAD\s*/i, '$ ');
                                $card.find('.session-badge-price span').text(costText);
                            }
                            if (res.event_court !== undefined) {
                                $card.find('.session-badge-court span').text('Court: ' + res.event_court);
                            }
                            $card.css('background-color', '#1987543b');
                        }

                        if (window.Casa && typeof window.Casa.showToast === 'function') {
                            window.Casa.showToast(res.message || 'Invitation accepted successfully.', 'success');
                        }
                    } else {
                        $btn.prop('disabled', false).removeClass('loading').html(originalHtml);
                        const errMsg = 'Failed to process event invite. ' + ((res && (res.message || res.error)) || '');
                        if (window.Casa && typeof window.Casa.showToast === 'function') {
                            window.Casa.showToast(errMsg, 'error');
                        } else {
                            alert(errMsg);
                        }
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).removeClass('loading').html(originalHtml);
                    const res = xhr.responseJSON || CasaHost.Utils.parseJson(xhr.responseText);
                    const errMsg = (res && res.message) ? res.message : 'An error occurred while processing your request.';
                    if (window.Casa && typeof window.Casa.showToast === 'function') {
                        window.Casa.showToast(errMsg, 'error');
                    } else {
                        alert(errMsg);
                    }
                }
            });
        }
    };

    // 3. MODALS & ROSTER VIEWS (Host Game View, Joined Players, Match Modals)
    CasaHost.Modals = {
        init: function () {
            this.bindEvents();
        },

        bindEvents: function () {
            // View Joined & Invited Players Modal
            $(document).on('click', '.discoverGames_card .view_btn', this.onViewJoinedInvited);

            // View All Joined Players Modal (Scheduled)
            $(document).on('click', '.discoverGames_card .joined_btn', this.onViewJoinedAll);

            // View All Joined Players Modal (Subscription)
            $(document).on('click', '.discoverGames_card_sub .joined_btn', this.onViewJoinedAllSub);

            // View upcoming player modal
            $(document).on('click', '.view_btnn', this.onViewUpcomingPlayer);

            // Host Pay Button Action
            $(document).on('click', '.action-btn-pay', this.onHostPayAction);

            // Match view modal open & close
            $(document).on('click', '.matchviewmodal_open', this.onMatchViewModalOpen);
            $(document).on('click', '.hostgamevieww_modal .customModal_close, #unifiedGameCompleteModal .customModal_close', this.onMatchViewModalClose);

            // Player view modal open
            $(document).on('click', '.playerviewmodal_open', this.onPlayerViewModalOpen);

            // Rollback event button
            $(document).on('click', '.rollback_btn', this.onRollbackEvent);

            // Payment approval/rejection quick action
            $(document).on('click', '.action-btn', this.onActionBtnPayment);

            // Unified Completed Game Modal (Tab Switching & Refresh)
            $(document).on('click', '.unified-game-tab-btn', function () {
                const tab = $(this).attr('data-tab');
                CasaHost.Modals.switchUnifiedTab(tab);
            });
            $(document).on('click', '#unifiedModalRefreshBtn', function () {
                CasaHost.Modals.refreshUnifiedModal();
            });

            // Unified Modal Close handlers
            $(document).on('click', '.customModal_close, .hostgameviewjoined_modal .customModal_close', function () {
                $(this).closest('.customModal_wrap, .hostgameviewjoined_modal, .hostgameview_modal, .unified_game_modal').removeClass('open');
            });
            $(document).on('click', '.customModal_wrap', function (e) {
                if ($(e.target).hasClass('customModal_wrap')) {
                    $(this).removeClass('open');
                    if ($(this).attr('id') === 'unifiedGameCompleteModal') {
                        CasaHost.Modals.onMatchViewModalClose();
                    }
                }
            });
            $(document).on('click', '[data-bs-toggle="tab"]', function () {
                $('.customModal_wrap').removeClass('open');
            });
            $(document).on('keydown', function (e) {
                if (e.key === 'Escape' && $('#unifiedGameCompleteModal').hasClass('open')) {
                    CasaHost.Modals.onMatchViewModalClose();
                }
            });
        },

        onViewJoinedInvited: function () {
            const id = $(this).attr('data-id');
            const data = $('#data_' + id).val();
            if (!data) return;

            const parseData = CasaHost.Utils.parseJson(data) || {};
            $("#sgenderCategoryy").val(parseData?.GENDER_CATEGORY);
            $("#sgenderSkillLevell").val(parseData?.GENDER_SKILL_LEVEL);
            document.getElementById('sgenderCategoryy')?.casaDropdown?.refresh();
            document.getElementById('sgenderSkillLevell')?.casaDropdown?.refresh();
            $("#playdt").val(parseData?.ID).data('game-id', parseData?.ID).data('host-id', parseData?.HOST_ID).attr('data-game-id', parseData?.ID).attr('data-host-id', parseData?.HOST_ID);

            $.ajax({
                type: "POST",
                url: BASE_URL + 'api/search-game-players',
                data: Object.assign({}, parseData, {
                    game_id: parseData?.ID,
                    view: 'all',
                    format: 'html'
                }),
                success: function (response) {
                    $("#playerList").html(response);
                    $('.hostgameview_modal').addClass('open');
                }
            });
        },

        onViewJoinedAll: function () {
            const id = $(this).attr('data-id');
            const data = $('#data_' + id).val();
            if (!data) return;

            const parseData = CasaHost.Utils.parseJson(data) || {};
            $("#sgenderCategoryy").val(parseData?.GENDER_CATEGORY);
            $("#sgenderSkillLevell").val(parseData?.GENDER_SKILL_LEVEL);

            $.ajax({
                type: "POST",
                url: BASE_URL + 'api/search-game-players',
                data: Object.assign({}, parseData, {
                    game_id: parseData?.ID,
                    view: 'joined',
                    joined_only: 1,
                    format: 'html'
                }),
                success: function (response) {
                    $(".hostgameviewjoined_modal #playerList_joined").html(response);
                    $('.hostgameviewjoined_modal').addClass('open');
                }
            });
        },

        onViewJoinedAllSub: function () {
            const id = $(this).attr('data-id');
            const data = $('#data_' + id).val();
            if (!data) return;

            const parseData = CasaHost.Utils.parseJson(data) || {};
            $("#sgenderCategoryy").val(parseData?.GENDER_CATEGORY);
            $("#sgenderSkillLevell").val(parseData?.GENDER_SKILL_LEVEL);

            $.ajax({
                type: "POST",
                url: BASE_URL + 'api/search-game-players',
                data: Object.assign({}, parseData, {
                    game_id: parseData?.ID,
                    view: 'joined',
                    joined_only: 1,
                    format: 'html'
                }),
                success: function (response) {
                    $(".hostgameviewjoined_modal #playerList_joined").html(response);
                    $('.hostgameviewjoined_modal').addClass('open');
                }
            });
        },

        onViewUpcomingPlayer: function () {
            const id = $(this).attr('data-id');
            const data = $('#data_' + id).val();
            const parseData = (data ? CasaHost.Utils.parseJson(data) : {}) || {};
            const gameId = parseData?.ID || id;
            if (!gameId) return;

            $.ajax({
                type: "POST",
                url: BASE_URL + 'api/search-game-players',
                data: Object.assign({}, parseData, {
                    game_id: gameId,
                    view: 'joined',
                    joined_only: 1,
                    format: 'html'
                }),
                success: function (response) {
                    $("#playerList_upcoming").html(response);
                    $('.hostgameview_modal_upcoming').addClass('open');
                }
            });
        },

        activeUnifiedGameId: null,
        activeUnifiedUserId: null,
        activeUnifiedTab: 'players',

        openUnifiedModal: function (gameId, userId, targetTab) {
            this.activeUnifiedGameId = gameId;
            this.activeUnifiedUserId = userId;
            this.activeUnifiedTab = targetTab || 'players';

            const $modal = $('#unifiedGameCompleteModal');
            if ($modal.length === 0) {
                return false;
            }

            $('.unified-game-id-badge').text('Game #' + gameId).show();
            this.switchUnifiedTab(this.activeUnifiedTab);

            $modal.addClass('open');

            // Load both datasets in parallel
            this.loadUnifiedPlayers();
            this.loadUnifiedHistory();
            return true;
        },

        switchUnifiedTab: function (tab) {
            this.activeUnifiedTab = tab;
            $('.unified-game-tab-btn').removeClass('active');
            $('.unified-game-tab-btn[data-tab="' + tab + '"]').addClass('active');

            if (tab === 'history') {
                $('#panePlayers').hide();
                $('#paneHistory').show();
                $('#unifiedModalTitle').text('View History');
            } else {
                $('#paneHistory').hide();
                $('#panePlayers').show();
                $('#unifiedModalTitle').text('View Player');
            }
        },

        loadUnifiedPlayers: function (callback) {
            const gameId = this.activeUnifiedGameId;
            const userId = this.activeUnifiedUserId;
            if (!gameId) return;

            const $container = $('#unifiedPlayerList');
            $container.html('<div class="text-center py-4 text-muted"><i class="fa-solid fa-spinner fa-spin fa-2x mb-2 text-primary"></i><div>Loading players...</div></div>');

            $.ajax({
                type: "POST",
                url: BASE_URL + 'api/view_game_player.php',
                data: { game_id: gameId, user_id: userId },
                success: function (response) {
                    $container.html(response);
                    $(".playergameview_modal #playerList").html(response);

                    const rowCount = $container.find('tbody tr').length;
                    if (rowCount > 0) {
                        $('.player-count-badge').text(rowCount).show();
                    } else {
                        $('.player-count-badge').hide();
                    }
                    if (typeof callback === 'function') callback(response);
                },
                error: function () {
                    $container.html('<div class="alert alert-danger my-3">Failed to load player list. Please try again.</div>');
                }
            });
        },

        loadUnifiedHistory: function (callback) {
            const gameId = this.activeUnifiedGameId;
            const userId = this.activeUnifiedUserId;
            if (!gameId) return;

            const $container = $('#unifiedHistoryList');
            $container.html('<div class="text-center py-4 text-muted"><i class="fa-solid fa-spinner fa-spin fa-2x mb-2 text-primary"></i><div>Loading payment history...</div></div>');

            $.ajax({
                type: "POST",
                url: BASE_URL + 'api/view_player_paylist.php',
                data: { game_id: gameId, user_id: userId },
                success: function (response) {
                    $container.html(response);
                    $(".hostgamevieww_modal #playerList, .hostgameview_modal #playerList").html(response);

                    const rowCount = $container.find('tbody tr').length;
                    if (rowCount > 0) {
                        $('.history-count-badge').text(rowCount).show();
                    } else {
                        $('.history-count-badge').hide();
                    }
                    if (typeof callback === 'function') callback(response);
                },
                error: function () {
                    $container.html('<div class="alert alert-danger my-3">Failed to load payment history. Please try again.</div>');
                }
            });
        },

        refreshUnifiedModal: function () {
            this.loadUnifiedPlayers();
            this.loadUnifiedHistory();
        },

        onHostPayAction: function () {
            const user_id = $(this).attr('data-id');
            const game_id = $(this).attr('data-game-id');
            const amount = $(this).attr('data-pay-amount');

            const $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                type: "POST",
                url: BASE_URL + 'api/same_paymentByhost.php',
                data: { user_id: user_id, game_id: game_id, amount: amount },
                success: function (response) {
                    alert('Payment successfull');
                    if ($('#unifiedPlayerList').length) {
                        $('#unifiedPlayerList').html(response);
                        CasaHost.Modals.loadUnifiedHistory();
                    }
                    $(".playergameview_modal #playerList").html(response);
                },
                complete: function () {
                    $btn.prop('disabled', false);
                }
            });
        },

        onMatchViewModalOpen: function () {
            const id = $(this).attr('data-id');
            const user_id = $(this).attr('data-user-id');

            if ($('#unifiedGameCompleteModal').length) {
                CasaHost.Modals.openUnifiedModal(id, user_id, 'history');
                return;
            }

            $.ajax({
                type: "POST",
                url: BASE_URL + 'api/view_player_paylist.php',
                data: { game_id: id, user_id: user_id },
                success: function (response) {
                    $(".hostgamevieww_modal #playerList").html(response);
                    $('.hostgamevieww_modal').addClass('open');
                }
            });
        },

        onMatchViewModalClose: function () {
            $('#unifiedGameCompleteModal, .hostgamevieww_modal, .playergameview_modal').removeClass('open');
            if (CasaHost.DashboardFilters && typeof CasaHost.DashboardFilters.filterCompletedSchedule === 'function') {
                CasaHost.DashboardFilters.filterCompletedSchedule();
            }
        },

        onPlayerViewModalOpen: function () {
            const id = $(this).attr('data-id');
            const user_id = $(this).attr('data-user-id');

            if ($('#unifiedGameCompleteModal').length) {
                CasaHost.Modals.openUnifiedModal(id, user_id, 'players');
                return;
            }

            $.ajax({
                type: "POST",
                url: BASE_URL + 'api/view_game_player.php',
                data: { game_id: id, user_id: user_id },
                success: function (response) {
                    $(".playergameview_modal #playerList").html(response);
                    $('.playergameview_modal').addClass('open');
                }
            });
        },

        onRollbackEvent: function () {
            const eventId = $(this).attr('data-id');
            const userId = $(this).attr('data-user-id');

            if (!eventId || !userId) {
                alert('Missing event or user ID');
                return;
            }

            if (confirm('Do you want to proceed?')) {
                $.ajax({
                    type: 'POST',
                    url: BASE_URL + 'api/rollback_event.php',
                    data: { event_id: eventId, host_id: userId, action: 'rollback' },
                    success: function () {
                        window.location.href = 'host-dashboard.php';
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX error:', error);
                        alert('An error occurred while processing your request.');
                    }
                });
            }
        },

        onActionBtnPayment: function () {
            const payid = $(this).attr('data-id');
            const status = $(this).attr('data-status');
            const game_id = $(this).attr('data-game-id');

            const $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                type: "POST",
                url: BASE_URL + 'api/update_paymentHis.php',
                data: { payid: payid, status: status, game_id: game_id },
                success: function (response) {
                    alert(status === 'R' ? 'Payment Rejected' : 'Payment Approved');
                    if ($('#unifiedHistoryList').length) {
                        $('#unifiedHistoryList').html(response);
                        CasaHost.Modals.loadUnifiedPlayers();
                    }
                    $(".hostgameview_modal #playerList, .hostgamevieww_modal #playerList").html(response);
                    $('.hostgameview_modal, .hostgamevieww_modal').addClass('open');
                },
                complete: function () {
                    $btn.prop('disabled', false);
                }
            });
        }
    };

    // 4. PLAYER INVITATION & SEARCH HANDLERS (Modal Player List & Auto-Confirm)
    CasaHost.Invitations = {
        init: function () {
            this.bindEvents();
        },

        bindEvents: function () {
            // Invite / Add Player Checkbox
            $(document).on('click', '.invite-checkbox', this.onInviteToggle);

            // Manual Confirm Checkbox
            $(document).on('click', '.invite-checkboxx', this.onConfirmToggle);

            // Save individual player price
            $(document).on("click", ".save-price", this.onSavePrice);

            // Live-sync player price input into row data-price-id attributes
            $(document).on('input change', '.player-price-input', function () {
                const val = $(this).val();
                const row = $(this).closest('tr');
                row.find('.invite-checkbox, .invite-checkboxx').attr('data-price-id', val);
            });

            // Enter key on player-price-input triggers price save if button exists
            $(document).on('keypress', '.player-price-input', function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    const row = $(this).closest('tr');
                    const saveBtn = row.find('.save-price');
                    if (saveBtn.length) {
                        saveBtn.trigger('click');
                    } else {
                        $(this).blur();
                    }
                }
            });

            // Search inputs with live debounce/length check
            $('#search').on('keyup', function () {
                if ($(this).val().length >= 2 || $(this).val().length === 0) {
                    CasaHost.Invitations.fetchPlayers();
                }
            });

            $('#search_subscription').on('keyup', function () {
                if ($(this).val().length >= 2 || $(this).val().length === 0) {
                    CasaHost.Invitations.fetchPlayersSubscription();
                }
            });

            // Filter dropdowns change
            $('#sgenderCategoryy, #sgenderSkillLevell').on('change', function () {
                CasaHost.Invitations.fetchPlayers();
            });

            $('#sgenderCategoryy_subscription, #sgenderSkillLevell_subscription').on('change', function () {
                CasaHost.Invitations.fetchPlayersSubscription();
            });
        },

        _refreshScheduleTimer: null,

        refreshActiveLists: function () {
            clearTimeout(this._refreshScheduleTimer);
            this._refreshScheduleTimer = setTimeout(() => {
                $("#month").trigger("change");
            }, 300);

            if ($("#playerList").length) {
                CasaHost.Invitations.fetchPlayers();
            }
            if ($("#playerList_subscription").length) {
                CasaHost.Invitations.fetchPlayersSubscription();
            }
        },

        onInviteToggle: function (e) {
            e.stopPropagation();

            const $this = $(this);
            const row = $this.closest('tr');
            const priceInput = row.find('.player-price-input');
            const rowPrice = (priceInput.length && priceInput.val() !== '') ? priceInput.val() : $this.attr('data-price-id');

            const userId = $this.attr('data-user-id');
            const gameId = $this.attr('data-game-id');
            const hostId = $this.attr('data-host-id');
            const currency = $this.attr('data-currency-id');
            const price = rowPrice;
            const dtype = $this.attr('data-type');

            if ($this.is(':checked')) {
                $.ajax({
                    url: BASE_URL + 'api/save_invitation.php',
                    type: 'POST',
                    data: {
                        type: 'checked',
                        user_id: userId,
                        game_id: gameId,
                        host_id: hostId,
                        currency: currency,
                        price: price,
                        dtype: dtype
                    },
                    success: function () {
                        CasaHost.Invitations.refreshActiveLists();
                    },
                    error: function (xhr, status, error) {
                        console.error('[Invitations] Error sending invitation:', error);
                        alert('Error sending invitation. Please try again.');
                    }
                });
            } else {
                const confirmCheckbox = row.find('.invite-checkboxx');
                if (confirmCheckbox.is(':checked')) {
                    confirmCheckbox.prop('checked', false);
                    confirmCheckbox.next('i').removeClass('fa-check-double text-primary').addClass('fa-check text-secondary');
                }

                $.ajax({
                    url: BASE_URL + 'api/save_invitation.php',
                    type: 'POST',
                    data: {
                        user_id: userId,
                        game_id: gameId,
                        host_id: hostId,
                        currency: currency,
                        price: price,
                        type: 'unchecked'
                    },
                    success: function () {
                        CasaHost.Invitations.refreshActiveLists();
                    },
                    error: function (xhr, status, error) {
                        console.error('[Invitations] Error removing invitation:', error);
                        alert('Error sending invitation. Please try again.');
                    }
                });
            }
        },

        onConfirmToggle: function () {
            const $this = $(this);
            const row = $this.closest('tr');
            const priceInput = row.find('.player-price-input');
            const rowPrice = (priceInput.length && priceInput.val() !== '') ? priceInput.val() : $this.attr('data-price-id');

            const userId = $this.attr('data-user-id');
            const gameId = $this.attr('data-game-id');
            const hostId = $this.attr('data-host-id');
            const currency = $this.attr('data-currency-id');
            const price = rowPrice;
            const dtype = $this.attr('data-type');

            if ($this.is(':checked')) {
                const addCheckbox = row.find('.invite-checkbox');
                if (!addCheckbox.is(':checked')) {
                    addCheckbox.prop('checked', true);
                    addCheckbox.next('i').removeClass('fa-user-plus text-secondary').addClass('fa-user-check text-success');
                }

                $.ajax({
                    url: BASE_URL + 'api/save_confirm.php',
                    type: 'POST',
                    data: {
                        type: 'checked',
                        user_id: userId,
                        game_id: gameId,
                        host_id: hostId,
                        currency: currency,
                        price: price,
                        dtype: dtype
                    },
                    success: function () {
                        CasaHost.Invitations.refreshActiveLists();
                    },
                    error: function (xhr, status, error) {
                        console.error('[Invitations] Error confirming player:', error);
                        alert('Error sending invitation. Please try again.');
                    }
                });
            } else {
                $.ajax({
                    url: BASE_URL + 'api/save_confirm.php',
                    type: 'POST',
                    data: {
                        user_id: userId,
                        game_id: gameId,
                        host_id: hostId,
                        currency: currency,
                        price: price,
                        type: 'unchecked'
                    },
                    success: function () {
                        CasaHost.Invitations.refreshActiveLists();
                    },
                    error: function (xhr, status, error) {
                        console.error('[Invitations] Error unconfirming player:', error);
                        alert('Error sending invitation. Please try again.');
                    }
                });
            }
        },

        onSavePrice: function () {
            const $btn = $(this);
            const userId = $btn.data("user-id");
            const gameId = $btn.data("game-id");
            const price = $("#price_" + userId).val();

            $.ajax({
                url: BASE_URL + "api/update_price.php",
                type: "POST",
                data: { user_id: userId, game_id: gameId, price: price },
                success: function (response) {
                    if ((response || '').trim() === "success") {
                        $btn.removeClass('btn-outline-success').addClass('btn-success text-white');
                        setTimeout(function () {
                            $btn.removeClass('btn-success text-white').addClass('btn-outline-success');
                        }, 1200);
                    } else {
                        alert("Failed to update price!");
                    }
                },
                error: function (xhr, status, error) {
                    console.error('[Invitations] Price update error:', error);
                    alert("Error in AJAX request.");
                }
            });
        },

        _fetchPlayersInternal: function (suffix, callback) {
            const searchText = ($('#search' + suffix).val() || '').trim();
            const gender = $('#sgenderCategoryy' + suffix).val();
            const skillLevel = $('#sgenderSkillLevell' + suffix).val();
            const $container = $('#playerList' + suffix);
            const firstCheckbox = $container.find('.invite-checkbox').first();
            const $dt = $('#playdt' + suffix);

            const gameId = $dt.data('game-id') || $dt.attr('data-game-id') || firstCheckbox.data('game-id');
            const hostId = $dt.data('host-id') || $dt.attr('data-host-id') || firstCheckbox.data('host-id');

            if (gameId) $dt.data('game-id', gameId).attr('data-game-id', gameId);
            if (hostId) $dt.data('host-id', hostId).attr('data-host-id', hostId);

            const scrollPos = $container.scrollTop() || ($container.find('.table-responsive').scrollTop()) || 0;

            $.ajax({
                type: "POST",
                url: BASE_URL + "api/search-game-players",
                data: {
                    query: searchText,
                    search: searchText,
                    game_id: gameId,
                    ID: gameId,
                    HOST_ID: hostId,
                    gender: gender,
                    skill_level: skillLevel,
                    view: 'all',
                    format: 'html'
                },
                success: function (response) {
                    $container.html(response);
                    if (scrollPos > 0) {
                        $container.scrollTop(scrollPos);
                        $container.find('.table-responsive').scrollTop(scrollPos);
                    }
                    if (typeof callback === 'function') {
                        callback(response);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('[Invitations] fetchPlayers error:', error);
                }
            });
        },

        fetchPlayers: function (callback) {
            CasaHost.Invitations._fetchPlayersInternal('', callback);
        },

        fetchPlayersSubscription: function (callback) {
            CasaHost.Invitations._fetchPlayersInternal('_subscription', callback);
        }
    };

    // 5. PAYMENT LEDGER, ACTIONS & OVERRIDE
    CasaHost.PaymentLedger = {
        init: function () {
            this.bindEvents();
        },

        checkOverrideAndLock: function ($elem) {
            const $wrapper = $elem.closest('.pay-history-wrapper');
            const isOverrideVal = $wrapper.find('.pay-action-override').attr('data-override') === 'on' || $wrapper.find('.pay-action-override').data('override') === 'on';

            if (!isOverrideVal) {
                if ($wrapper.attr('data-locked') === '1') {
                    window.showToast("This month has already been carried forward and is locked.", 'warning');
                    return null;
                }
                if ($wrapper.attr('data-state') === 'pending') {
                    window.showToast("This month is pending carry forward. Only the Carry Forward action is allowed.", 'warning');
                    return null;
                }
            }
            return { isOverride: isOverrideVal ? 1 : 0, $wrapper: $wrapper };
        },

        getPeriodContext: function ($elem) {
            const year = $elem.attr('data-year') || $elem.closest('.patmentTb').find('.pay-ledger-year').val() || $("#hpyear").val();
            const month = $elem.attr('data-month') || $elem.closest('.patmentTb').find('.pay-ledger-month').val() || $("#hpmonth").val();
            return { year: year, month: month };
        },

        updateModalContent: function (html) {
            let $container = $(".patmentTb");
            if ($("#modalContainer").length > 0 && !$("#modalContainer").hasClass("hidden") && $("#modalBody").length > 0 && $("#modalBody").is(":visible")) {
                $container = $("#modalBody");
            }
            const $filter = $container.find('.pay-ledger-filter').detach();
            $container.html(html);
            if ($filter.length > 0) {
                $container.prepend($filter);
            }
        },

        bindEvents: function () {
            // Save Payment Modal action
            $(".save_payment").on('click', this.onSavePayment);

            // Fetch payment due when game dropdown changes
            $(".game_dt").on('change', this.onGameDtChange);

            // View payment history modal
            $(document).on('click', '.custom_card .patmentTb .paymentTab .view_btn', this.onViewPaymentHistory);

            // Reinitiated badge / history detail click
            $(document).on('click', '.pay-reinitiated-badge, .pay-history-detail-btn', this.onDetailHistoryClick);

            // Close payment history modals
            $(document).on('click', '#closeModalBtn, .closeModalBtn, #modalBackdrop, .btn-close, [data-bs-dismiss="modal"]', this.onClosePaymentModals);

            // Open and close Player Detailed Ledger Modal (View Ledger) - handled by CasaLedger if present
            if (!window.CasaLedger) {
                $(document).on('click', '.playPaymentModal_open', this.onOpenPlayPaymentModal);
                $(document).on('click', '.playPaymentModal_close', this.onClosePlayPaymentModal);
            }
        },

        onOpenPlayPaymentModal: function (e) {
            e.preventDefault();
            const $btn = $(this);
            const playerId = $btn.attr('data-id') || $btn.data('id') || $btn.closest('tr').find('[data-user-id]').attr('data-user-id') || 0;
            let year = $("#hpyear").val() || $btn.attr('data-year') || new Date().getFullYear();
            let month = $("#hpmonth").val() || $btn.attr('data-month') || (new Date().getMonth() + 1);

            if (!playerId) return;

            $('.playPaymentModal').addClass('open');
            $('.customModal_head').text('View History');

            if (window.CasaLedger && typeof window.CasaLedger.loadMonthlyLedger === 'function') {
                window.CasaLedger.loadMonthlyLedger(playerId, year, month, function (err, res) {
                    if (!err && res && res.data && res.data.player_name) {
                        $('.customModal_head').text('View History - ' + res.data.player_name);
                    }
                });
            } else {
                let $container = $(".patmentTb");
                $container.html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="text-muted mt-2 mb-0" style="font-size:0.85rem;">Loading financial ledger...</p></div>');

                const base = (typeof BASE_URL !== 'undefined') ? BASE_URL : '';
                const url = (base ? (base.endsWith('/') ? base : base + '/') : '') + 'api/get-player-detailed-ledger?format=json';
                $.ajax({
                    url: url,
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
                            if (res.data.html) {
                                $container.html(res.data.html);
                            }
                        } else {
                            $container.html('<p class="text-center text-danger py-4">Failed to load ledger data.</p>');
                        }
                    },
                    error: function () {
                        $container.html('<p class="text-center text-danger py-4">Error loading ledger data.</p>');
                    }
                });
            }
        },

        onClosePlayPaymentModal: function (e) {
            e.preventDefault();
            if (window.CasaLedger && window.CasaLedger.currentView === 'full') {
                return;
            }
            $('.playPaymentModal').removeClass('open');
            $('.customModal_head').text('View History');
            // Closing a read-only ledger must preserve the payment list.
        },

        onSavePayment: function () {
            const userId = $("#user_id").val();
            const gameId = $(".game_dt").val() || $("select.form-control").val() || 0;
            const amount = $("#Amount").val();
            const paymentDate = $("#date").val();
            const paymentTime = $("#time").val();
            const paymentType = $("#paymentType").val();
            const details = $("#details").val();
            const message = $("#Message").val();
            const dueamt = $("#due_amt").val();
            const host = $("#payhost").val() || $("#hhost").val() || "";
            const year = $("#payyear").val() || $("#directLedgerYear").val() || "";
            const month = $("#paymonth").val() || $("#directLedgerMonth").val() || "";

            if (!amount || parseFloat(amount) <= 0 || !paymentDate || !paymentTime || !paymentType) {
                alert("Please fill all required fields.");
                return;
            }

            if (dueamt !== "" && dueamt !== null && !isNaN(dueamt) && Number(dueamt) > 0) {
                if (Number(amount) > Number(dueamt)) {
                    alert("Amount Exceeds Due Amount");
                    return;
                }
            }

            $.ajax({
                url: BASE_URL + "api/save_payment.php",
                type: "POST",
                data: {
                    user_id: userId,
                    game_id: gameId,
                    amount: amount,
                    payment_date: paymentDate,
                    payment_time: paymentTime,
                    payment_type: paymentType,
                    details: details,
                    message: message,
                    year: year,
                    month: month,
                    host: host,
                },
                success: function (response) {
                    $("#Amount, #date, #time, #paymentType, #details, #Message, #due_amt, select.form-control").val('');
                    $("#tot_amnt strong, #due strong").text("$0");

                    $('.PayAmountModal').removeClass('open');

                    const res = CasaHost.Utils.parseJson(response) || response;
                    if (typeof changeDirectMonthlyLedger === 'function') {
                        changeDirectMonthlyLedger();
                    } else if (res && res.html) {
                        CasaHost.PaymentLedger.updateModalContent(res.html);
                    }
                },
                error: function (xhr) {
                    alert(xhr.responseText || "Error saving data.");
                }
            });
        },

        onGameDtChange: function () {
            const gameId = $(this).val();
            const userId = $("#user_id").val();

            if (gameId !== "") {
                $.ajax({
                    url: BASE_URL + "api/fetch_payment.php",
                    type: "POST",
                    data: { game_id: gameId, user_id: userId },
                    success: function (response) {
                        const data = CasaHost.Utils.parseJson(response) || {};
                        if (data.success) {
                            const curr = (data.currency && data.currency !== 'CAD') ? data.currency : '$';
                            const prefix = curr.endsWith('$') ? curr : (curr + ' ');
                            $("#tot_amnt strong").text(prefix + data.total_amount);
                            $("#due strong").text(prefix + data.due);
                            $("#due_amt").val(data.due);
                        } else {
                            $("#tot_amnt strong, #due strong").text("$0");
                            $("#due_amt").val(0);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('[PaymentLedger] Error fetching payment:', error);
                        alert("Error fetching payment details.");
                    }
                });
            } else {
                $("#tot_amnt strong, #due strong").text("$0");
            }
        },

        onViewPaymentHistory: function () {
            const gameJoinId = $(this).attr('data-id');
            const userId = $(this).attr('data-user-id');

            $.ajax({
                type: "POST",
                url: BASE_URL + 'api/view_player_paymentHis.php',
                data: { user_id: userId, game_id: gameJoinId },
                success: function (response) {
                    $(".hostgameview_modal_payment #playerList_payment").html(response);
                    $('.hostgameview_modal_payment').addClass('open');
                }
            });
        },

        onDetailHistoryClick: function (e) {
            e.preventDefault();
            const $elem = $(this).closest('.pay-reinitiated-badge, .pay-history-detail-btn');
            const paymentId = $elem.attr('data-payment-id') || $elem.data('payment-id');
            const userId = $elem.attr('data-user') || $elem.data('user') || $("#user_id").val() || '';

            if (!paymentId) return;

            $.ajax({
                type: "POST",
                url: CasaHost.Utils.getActionUrl('api/view_player_paymentHis.php'),
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
                        $('body').append('<div id="payHistoryModalShell" class="modal fade show" style="display:block; background:rgba(15,23,42,0.65); backdrop-filter:blur(4px); z-index:9999;"><div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1150px; width: 95%;"><div class="modal-content border-0" style="border-radius:16px; overflow:hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);"><div class="modal-header d-flex justify-content-between align-items-center py-3 px-4" style="background:#f8fafc; border-bottom:1px solid #e2e8f0;"><h5 class="modal-title m-0 font-bold" style="color:#0f172a; font-size:1.15rem; display:flex; align-items:center; gap:8px;"><i class="fa-solid fa-clock-rotate-left text-teal-600"></i> Payment & Approval History</h5><button type="button" class="btn p-0 border-0 shadow-none bg-transparent" onclick="$(\'#payHistoryModalShell\').remove()" style="font-size:1.25rem; color:#64748b; line-height:1; opacity:0.8;"><i class="fa-solid fa-xmark"></i></button></div><div class="modal-body p-4" id="payHistoryModalShellBody"></div></div></div></div>');
                        $('#payHistoryModalShellBody').html(response);
                    }
                }
            });
        },

        onClosePaymentModals: function (e) {
            e.preventDefault();
            if ($('#modalContainer').length) $('#modalContainer').addClass('hidden');
            $('#payHistoryModalShell, #rejectPaymentModalShell, #reinitiatePaymentModalShell').remove();
            if ($('.hostgameview_modal_payment').length) $('.hostgameview_modal_payment').removeClass('open');
            if (typeof closeModal === 'function') {
                try { closeModal(); } catch (err) { }
            }
        },

        onApprovePayment: function () {
            const check = CasaHost.PaymentLedger.checkOverrideAndLock($(this));
            if (!check) return;

            const game_id = $(this).data('id');
            const user_id = $(this).data('user');
            const payment_id = $(this).attr('data-payment-id') || $(this).data('payment-id') || 0;
            const ctx = CasaHost.PaymentLedger.getPeriodContext($(this));

            $.post(CasaHost.Utils.getActionUrl('api/payment_action.php'), {
                action: 'approve',
                game_id: game_id,
                user_id: user_id,
                payment_id: payment_id,
                year: ctx.year,
                month: ctx.month,
                override: check.isOverride
            }, function (res) {
                alert(res.message);
                CasaHost.PaymentLedger.updateModalContent(res.html);
            }, 'json');
        },

        onRejectPaymentModalOpen: function () {
            const check = CasaHost.PaymentLedger.checkOverrideAndLock($(this));
            if (!check) return;

            const game_id = $(this).data('id');
            const user_id = $(this).data('user');
            const payment_id = $(this).attr('data-payment-id') || $(this).data('payment-id') || 0;
            const ctx = CasaHost.PaymentLedger.getPeriodContext($(this));

            $('#rejectPaymentModalShell').remove();
            $('body').append(`
                <div id="rejectPaymentModalShell" class="modal fade show" style="display:block; background:rgba(15,23,42,0.65); backdrop-filter:blur(4px); z-index:9999;">
                  <div class="modal-dialog modal-dialog-centered" style="max-width: 480px; width: 95%;">
                    <div class="modal-content border-0" style="border-radius:16px; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
                      <div class="modal-header d-flex justify-content-between align-items-center py-3 px-4" style="background:#f8fafc; border-bottom:1px solid #e2e8f0;">
                        <h5 class="modal-title m-0 font-bold" style="color:#0f172a; font-size:1.1rem; display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-circle-xmark text-rose-600"></i> Reject Payment
                        </h5>
                        <button type="button" class="btn p-0 border-0 shadow-none bg-transparent closeModalBtn" style="font-size:1.25rem; color:#64748b; line-height:1; opacity:0.8;"><i class="fa-solid fa-xmark"></i></button>
                      </div>
                      <div class="modal-body p-4" style="background:#ffffff;">
                        <div class="form-group mb-0">
                          <textarea id="rejectReasonInput" class="form-control" rows="3" placeholder="Enter rejection reason..." style="border-radius:10px; border:1px solid #cbd5e1; padding:12px; font-size:0.9rem; outline:none; box-shadow:none;"></textarea>
                          <div id="rejectReasonError" class="text-danger mt-1.5 small" style="display:none; font-size:0.82rem; font-weight:500;"><i class="fa-solid fa-circle-exclamation me-1"></i>Please enter a rejection reason.</div>
                        </div>
                      </div>
                      <div class="modal-footer py-3 px-4 d-flex justify-content-end gap-2" style="background:#f8fafc; border-top:1px solid #e2e8f0;">
                        <button type="button" class="btn btn-secondary closeModalBtn" style="border-radius:8px; font-weight:600; font-size:0.88rem; padding:8px 18px; background:#64748b; border:none;">Cancel</button>
                        <button type="button" id="confirmRejectBtn" class="btn btn-danger" style="border-radius:8px; font-weight:600; font-size:0.88rem; padding:8px 20px; background:#e11d48; border-color:#e11d48;">Reject</button>
                      </div>
                    </div>
                  </div>
                </div>
            `);

            $('#confirmRejectBtn').off('click').on('click', function () {
                const reason = $('#rejectReasonInput').val().trim();
                if (!reason) {
                    $('#rejectReasonError').show();
                    return;
                }
                $('#rejectReasonError').hide();

                $.post(CasaHost.Utils.getActionUrl('api/payment_action.php'), {
                    action: 'reject',
                    game_id: game_id,
                    user_id: user_id,
                    payment_id: payment_id,
                    reason: reason,
                    year: ctx.year,
                    month: ctx.month,
                    override: check.isOverride
                }, function (res) {
                    $('#rejectPaymentModalShell').remove();
                    alert(res.message);
                    if (res.success) {
                        CasaHost.PaymentLedger.updateModalContent(res.html);
                    }
                }, 'json');
            });
        },

        onPayDue: function () {
            const check = CasaHost.PaymentLedger.checkOverrideAndLock($(this));
            if (!check) return;

            const game_id = $(this).data('id');
            const user_id = $(this).data('user');
            const amount = parseFloat($(this).data('due'));
            const payment_type = 'Interac';

            const ctx = CasaHost.PaymentLedger.getPeriodContext($(this));
            const host_id = $("#payhost").val() || $("#hhost").val();

            if (amount > 0) {
                $.post(CasaHost.Utils.getActionUrl('api/payment_action.php'), {
                    action: 'pay',
                    game_id: game_id,
                    user_id: user_id,
                    amount: amount,
                    payment_type: payment_type,
                    host_id: host_id,
                    year: ctx.year,
                    month: ctx.month,
                    override: check.isOverride
                }, function (res) {
                    window.showToast(res.message, res.success ? 'success' : 'info');
                    CasaHost.PaymentLedger.updateModalContent(res.html);
                }, 'json');
            } else {
                window.showToast("No due to pay.", 'info');
            }
        },

        onRollbackPayment: function () {
            const check = CasaHost.PaymentLedger.checkOverrideAndLock($(this));
            if (!check) return;

            const gameId = $(this).data('id');
            const userId = $(this).data('user');
            const amount = $(this).data('amount');
            const ctx = CasaHost.PaymentLedger.getPeriodContext($(this));

            let formattedAmount = '';
            if (amount !== '' && amount !== null && amount !== undefined) {
                const num = parseFloat(amount);
                formattedAmount = !isNaN(num) ? '$' + num.toFixed(2) : String(amount);
            }

            window.Casa.confirmAction({
                title: 'Rollback Payment',
                text: `Are you sure you want to rollback payment ${formattedAmount ? 'of <span class="casa-confirm-amt-pill">' + formattedAmount + '</span>' : ''}?`,
                icon: 'danger',
                confirmButtonText: 'Yes, Rollback',
                confirmBtnClass: 'btn-danger',
                onConfirm: function () {
                    $.post(CasaHost.Utils.getActionUrl('api/rollback_payment.php'), {
                        game_id: gameId,
                        user_id: userId,
                        year: ctx.year,
                        month: ctx.month,
                        override: check.isOverride
                    }, function (response) {
                        CasaHost.PaymentLedger.updateModalContent(response);
                        window.showToast('Payment rolled back successfully.', 'success');
                    }).fail(function () {
                        window.showToast('Error rolling back payment.', 'error');
                    });
                }
            });
        },

        onCarryForward: function () {
            const isOverride = $(this).siblings('.pay-action-override').data('override') === 'on';

            if (!isOverride) {
                if ($(this).closest('.pay-history-wrapper').attr('data-locked') === '1') {
                    window.showToast("This month has already been carried forward and is locked.", 'warning');
                    return;
                }
                if ($(this).prop('disabled')) {
                    window.showToast("The current month cannot be carried forward until it ends.", 'warning');
                    return;
                }
            }

            const userId = $(this).attr('data-user') || $(this).data('user');
            const year = $(this).attr('data-year') || $(this).data('year');
            const month = $(this).attr('data-month') || $(this).data('month');

            if (!userId) {
                window.showToast("User ID not found on carry button.", 'error');
                return;
            }

            $.post(CasaHost.Utils.getActionUrl('api/carry_forward.php'), {
                user_id: userId,
                year: year,
                month: month
            }, function (res) {
                window.showToast(res.message, res.success ? 'success' : 'error');
                if (res.success) {
                    CasaHost.PaymentLedger.updateModalContent(res.html);
                }
            }, 'json').fail(function (xhr, status, error) {
                console.error("[PaymentLedger] AJAX error:", status, error);
                window.showToast("Failed to carry forward: Server or network error.", 'error');
            });
        },

        onToggleOverride: function () {
            const $btn = $(this);
            if ($btn.prop('disabled') || $btn.attr('disabled')) return;

            const $btns = $btn.closest('.pay-history-actionbar-btns');
            const isOff = $btn.attr('data-override') === 'off' || $btn.data('override') === 'off';
            const $chip = $btn.closest('.pay-history-actionbar').find('.pay-status-chip');

            const userId = $btn.attr('data-user') || $btn.data('user');
            const year = $btn.attr('data-year') || $btn.data('year');
            const month = $btn.attr('data-month') || $btn.data('month');

            $.post(CasaHost.Utils.getActionUrl('api/toggle_override.php'), {
                user_id: userId,
                year: year,
                month: month,
                state: isOff ? 'on' : 'off'
            }, function (res) {
                if (res && res.success && res.html) {
                    CasaHost.PaymentLedger.updateModalContent(res.html);
                    return;
                }
            }, 'json');

            if (isOff) {
                $btn.attr('data-override', 'on').data('override', 'on')
                    .attr('title', 'Override is Unlocked â€” click to restore the original state')
                    .addClass('pay-override-on');
                $btn.find('i').removeClass('fa-lock').addClass('fa-lock-open');

                // Enable actions permitted by override (Expenses and Payments)
                $btns.find('.pay-action-expense, .pay-action-pay')
                    .prop('disabled', false)
                    .removeAttr('disabled');

                // Carry forward is NOT unlocked if it was disabled due to non-override rules (current month, already carried, downstream locked)
                $btns.find('.pay-action-carry').each(function () {
                    const orig = $(this).attr('data-orig-disabled');
                    if (orig !== 'disabled') {
                        $(this).prop('disabled', false).removeAttr('disabled');
                    }
                });

                if ($chip.hasClass('pay-status-locked')) {
                    $chip.html('ðŸ”“ UNLOCKED').removeClass('pay-status-locked').addClass('pay-status-active');
                } else if ($chip.hasClass('pay-status-pending')) {
                    $chip.html('ðŸ”“ UNLOCKED').removeClass('pay-status-pending').addClass('pay-status-active');
                }
            } else {
                $btn.attr('data-override', 'off').data('override', 'off')
                    .attr('title', 'Override is Locked â€” click to unlock all action buttons (testing only)')
                    .removeClass('pay-override-on');
                $btn.find('i').removeClass('fa-lock-open').addClass('fa-lock');

                $btns.find('.pay-action-carry, .pay-action-expense, .pay-action-pay').each(function () {
                    const orig = $(this).attr('data-orig-disabled');
                    if (orig === 'disabled') {
                        $(this).prop('disabled', true).attr('disabled', 'disabled');
                    } else {
                        $(this).prop('disabled', false).removeAttr('disabled');
                    }
                });

                const isCarryDisabled = $btns.find('.pay-action-carry').attr('data-orig-disabled') === 'disabled';
                if ($chip.hasClass('pay-status-active') && $chip.text().trim() === 'ðŸ”“ UNLOCKED') {
                    if (isCarryDisabled) {
                        $chip.html('ðŸ”’ LOCKED').removeClass('pay-status-active').addClass('pay-status-locked');
                    } else {
                        $chip.html('<i class="fa-solid fa-hourglass-half"></i> Pending Carry').removeClass('pay-status-active').addClass('pay-status-pending');
                    }
                }
            }
        },

        onRollbackCarry: function () {
            const $btn = $(this);
            const user_id = $btn.attr('data-user') || $btn.data('user');
            const year = $btn.attr('data-year') || $btn.data('year');
            const month = $btn.attr('data-month') || $btn.data('month');

            if (!user_id) {
                window.showToast("User ID not found for rollback action.", 'error');
                return;
            }

            window.Casa.confirmAction({
                title: 'Rollback Carry Forward',
                text: 'Are you sure you want to roll back the carry forward opening balance for this month?',
                icon: 'warning',
                confirmButtonText: 'Rollback Balance',
                confirmBtnClass: 'btn-warning',
                onConfirm: function () {
                    $.post(CasaHost.Utils.getActionUrl('api/rollback_carry_forward.php'), {
                        user_id: user_id,
                        year: year,
                        month: month
                    }, function (res) {
                        window.showToast(res.message, res.success ? 'success' : 'error');
                        if (res.success) {
                            CasaHost.PaymentLedger.updateModalContent(res.html);
                        }
                    }, 'json').fail(function () {
                        window.showToast("Failed to roll back carry forward: Server or network error.", 'error');
                    });
                }
            });
        },

        onToggleExpenseForm: function () {
            const check = CasaHost.PaymentLedger.checkOverrideAndLock($(this));
            if (!check) return;

            $('#payPaymentFormWrap').slideUp(180);
            $('#payExpenseFormWrap').slideToggle(180);
        },

        onTogglePayForm: function () {
            const check = CasaHost.PaymentLedger.checkOverrideAndLock($(this));
            if (!check) return;

            $('#payExpenseFormWrap').slideUp(180);
            $('#payPaymentFormWrap').slideToggle(180);
        },

        onSubmitExpense: function (e) {
            e.preventDefault();

            const check = CasaHost.PaymentLedger.checkOverrideAndLock($(this));
            if (!check) return;

            const user_id = $('input[name="user_id"]', this).val();
            const venue = $('select[name="venue"]', this).val();
            const type = $('input[name="type"]', this).val();
            const amount = $('input[name="amount"]', this).val();
            const expense_datetime = $('input[name="expense_datetime"]', this).val();
            const year = $('input[name="year"]', this).val() || $("#hpyear").val();
            const month = $('input[name="month"]', this).val() || $("#hpmonth").val();
            const host = $('input[name="host_id"]', this).val() || $("#payhost").val() || $("#hhost").val() || '';

            if (!venue || !amount || !expense_datetime) {
                window.showToast("Please fill Venue, Amount and Date & Time.", 'error');
                return;
            }

            $.post(CasaHost.Utils.getActionUrl('api/add_expense.php'), {
                user_id: user_id,
                host_id: host,
                venue: venue,
                type: type,
                amount: amount,
                expense_datetime: expense_datetime,
                year: year,
                month: month,
                override: check.isOverride
            }, function (res) {
                window.showToast(res.message, res.success ? 'success' : 'error');
                if (res.success) {
                    CasaHost.PaymentLedger.updateModalContent(res.html);
                }
            }, 'json');
        },

        onSubmitPlayerPay: function (e) {
            e.preventDefault();

            const check = CasaHost.PaymentLedger.checkOverrideAndLock($(this));
            if (!check) return;

            const user_id = $('input[name="user_id"]', this).val();
            const venue = $('select[name="venue"]', this).val();
            const type = $('input[name="type"]', this).val();
            const amount = $('input[name="amount"]', this).val();
            const payment_datetime = $('input[name="payment_datetime"]', this).val();
            const year = $('input[name="year"]', this).val() || $("#hpyear").val();
            const month = $('input[name="month"]', this).val() || $("#hpmonth").val();
            const host = $('input[name="host_id"]', this).val() || $("#payhost").val() || $("#hhost").val() || '';

            if (!amount || !payment_datetime) {
                window.showToast("Please fill Amount and Date & Time.", 'error');
                return;
            }

            const $submitBtn = $(this).find('button[type="submit"]');
            $submitBtn.prop('disabled', true).text('Saving...');

            $.post(CasaHost.Utils.getActionUrl('api/payment_action.php'), {
                action: 'pay',
                user_id: user_id,
                game_id: 0,
                details: venue,
                payment_type: type,
                amount: amount,
                payment_datetime: payment_datetime,
                host_id: host,
                year: year,
                month: month,
                is_player_side: 1,
                override: check.isOverride
            }, function (res) {
                $submitBtn.prop('disabled', false).text('Save');
                if (res.success || res.status === 'success') {
                    $('#payPaymentFormWrap').slideUp(180);
                    $('.PayAmountModal').removeClass('open');
                    if ($('#payPaymentForm').length) {
                        $('#payPaymentForm')[0].reset();
                    }
                    window.showToast(res.message || 'Payment saved successfully.', 'success');
                    if (typeof changeDirectMonthlyLedger === 'function') {
                        changeDirectMonthlyLedger();
                    } else if (res.html) {
                        CasaHost.PaymentLedger.updateModalContent(res.html);
                    }
                } else {
                    window.showToast(res.message || 'Failed to save payment.', 'error');
                }
            }, 'json').fail(function (xhr) {
                $submitBtn.prop('disabled', false).text('Save');
                const errMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : (xhr.responseText && xhr.responseText.length < 200 ? xhr.responseText : 'Failed to save payment. Please try again.');
                window.showToast(errMsg, 'error');
            });
        },

        onReinitiateModalOpen: function () {
            const check = CasaHost.PaymentLedger.checkOverrideAndLock($(this));
            if (!check) return;

            const payment_id = $(this).attr('data-payment-id') || $(this).data('payment-id') || 0;
            const user_id = $(this).data('user');
            const ctx = CasaHost.PaymentLedger.getPeriodContext($(this));
            const host = $(this).attr('data-host') || $(this).data('host') || $("#payhost").val() || $("#hhost").val() || new URLSearchParams(window.location.search).get('host_id') || '';

            $('#reinitiatePaymentModalShell').remove();
            $('body').append(`
                <div id="reinitiatePaymentModalShell" class="modal fade show" style="display:flex; align-items:center; justify-content:center; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.6); backdrop-filter:blur(4px); z-index:9999;">
                  <div style="background:#ffffff; width:90%; max-width:440px; border-radius:16px; padding:24px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); position:relative; border:1px solid #e2e8f0;">
                    
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px;">
                      <h5 style="font-size:1.1rem; font-weight:700; color:#0f172a; margin:0; letter-spacing:-0.01em;">Re-initiate Payment</h5>
                      <button type="button" class="closeModalBtn" style="background:#f1f5f9; border:none; color:#64748b; width:32px; height:32px; border-radius:50%; font-size:0.95rem; cursor:display; display:flex; align-items:center; justify-content:center; transition:all 0.2s;" onmouseover="this.style.background='#e2e8f0'; this.style.color='#0f172a'" onmouseout="this.style.background='#f1f5f9'; this.style.color='#64748b'">
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                    </div>

                    <div style="margin-bottom:20px;">
                      <textarea id="reinitiateDetailsInput" style="width:100%; height:110px; padding:12px 14px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:10px; font-size:0.875rem; color:#334155; outline:none; resize:none; transition:all 0.2s;" placeholder="Enter details or reason..." onfocus="this.style.borderColor='#0d6efd'; this.style.backgroundColor='#ffffff'; this.style.boxShadow='0 0 0 3px rgba(13,110,253,0.12)'" onblur="this.style.borderColor='#cbd5e1'; this.style.backgroundColor='#f8fafc'; this.style.boxShadow='none'"></textarea>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:10px;">
                      <button type="button" class="closeModalBtn" style="padding:9px 20px; border-radius:8px; background:#f1f5f9; color:#64748b; border:none; font-weight:600; font-size:0.85rem; cursor:pointer; transition:all 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">Cancel</button>
                      <button type="button" id="confirmReinitiateBtn" style="padding:9px 22px; border-radius:8px; background:#0d6efd; color:#ffffff; border:none; font-weight:600; font-size:0.85rem; cursor:pointer; transition:all 0.2s; box-shadow:0 4px 12px rgba(13,110,253,0.25);" onmouseover="this.style.background='#0b5ed7'" onmouseout="this.style.background='#0d6efd'">Submit</button>
                    </div>

                  </div>
                </div>
            `);

            $('#confirmReinitiateBtn').off('click').on('click', function () {
                const details = $('#reinitiateDetailsInput').val().trim();
                $.post(CasaHost.Utils.getActionUrl('api/payment_action.php'), {
                    action: 'reinitiate',
                    payment_id: payment_id,
                    user_id: user_id,
                    host_id: host,
                    details: details,
                    year: ctx.year,
                    month: ctx.month,
                    override: check.isOverride
                }, function (res) {
                    $('#reinitiatePaymentModalShell').remove();
                    window.showToast(res.message, res.success ? 'success' : 'error');
                    if (res.success) {
                        CasaHost.PaymentLedger.updateModalContent(res.html);
                    }
                }, 'json');
            });
        }
    };

    // 6. DASHBOARD FILTERS & TABLE PAGINATION
    CasaHost.DashboardFilters = {
        init: function () {
            this.bindEvents();
            if ($(".host_payment").length) {
                this.loadHostPaymentTable(1);
            }
        },

        bindEvents: function () {
            // Main schedule month/year dropdown change & filter button
            $(document).on("change", "#year, #month", () => this.onScheduleFilterChange());
            $(document).on("click", "#filter", () => this.onScheduleFilterChange());

            // Host payment table filter button (delegated)
            $(document).on("click", "#hpfilter", () => this.loadHostPaymentTable(1));

            // Auto-filter when Year, Month, or Player dropdown changes
            $(document).on("change", "#hpyear, #hpmonth, #hhost", () => this.loadHostPaymentTable(1));

            // Host payment table page size dropdown
            $(document).on("change", "#hp-pagesize", () => this.loadHostPaymentTable(1));

            // Host payment search on Enter key
            $(document).on("keydown", "#hpsearch", (e) => {
                if (e.key === "Enter") {
                    e.preventDefault();
                    this.loadHostPaymentTable(1);
                }
            });

            // Host payment sort direction toggle
            $(document).on("click", "#hpsortdir", this.onSortDirToggle);

            // Host payment column sort header
            $(document).on("click", ".hp-sort-col", this.onSortColClick);

            // Host payment pagination click
            $(document).on("click", ".hp-pagination .page-link", this.onPaginationClick);

            // Host payment list refresh button
            $(document).on("click", ".hostpaymentlist_sec #refreshBtn, .playarPayment_game #refreshBtn", this.onPaymentRefresh);

            // Host dashboard Payment tab switch listener
            $(document).on("shown.bs.tab", "#Payment-tab, [data-bs-target='#Payment']", () => this.loadHostPaymentTable(1));

            // Host dashboard Complete tab switch listener
            $(document).on("shown.bs.tab", "#Completed-tab, [data-bs-target='#Completed']", () => this.filterCompletedSchedule());

            // Completed games schedule filter
            $(document).on("change", "#com_year, #com_month", () => this.filterCompletedSchedule());
            $(document).on("click", ".hostcomplete_game #refreshBtn, #com_filter", (e) => {
                e.preventDefault();
                this.filterCompletedSchedule();
            });
            $("#com_reset").on("click", () => this.onResetCompletedSchedule());
        },

        _scheduleFilterXhr: null,
        _scheduleFilterSeq: 0,

        onScheduleFilterChange: function () {
            const year = $("#year").val() || '';
            const month = $("#month").val() || '';
            const hostId = $("#filterScheduleForm").data("host-id") || $("#host_id").val() || '';
            const wrapper = $("#Scheduled .hostWrapper");
            if (!wrapper.length) return;

            // Invalidate previous sequence FIRST before aborting, so that any synchronous
            // abort callback from previous request will see an outdated sequence and won't clear loading state!
            const currentSeq = ++this._scheduleFilterSeq;

            // Abort previous in-flight request to prevent race conditions
            if (this._scheduleFilterXhr && typeof this._scheduleFilterXhr.abort === "function") {
                this._scheduleFilterXhr.abort();
            }

            // Visual loading state & accessible busy flag: only dim opacity if modal is NOT open
            const isModalOpen = $('.customModal_wrap.open').length > 0;
            if (!isModalOpen) {
                wrapper.attr("aria-busy", "true").css({ opacity: "0.5", pointerEvents: "none" });
            } else {
                wrapper.attr("aria-busy", "true");
            }

            this._scheduleFilterXhr = $.ajax({
                type: "POST",
                url: BASE_URL + 'api/filter_schedule.php',
                data: {
                    year: year,
                    month: month,
                    host_id: hostId,
                    type: 'filter'
                },
                success: (response) => {
                    // Only update DOM if this is still the latest request
                    if (currentSeq === this._scheduleFilterSeq) {
                        wrapper.html(response);
                        if (typeof initPopovers === 'function') {
                            initPopovers();
                        }
                    }
                },
                error: (xhr, status, error) => {
                    if (status !== "abort") {
                        console.error('[DashboardFilters] Schedule filter error:', error);
                        if (typeof showToast === 'function') {
                            showToast('error', 'Failed to load scheduled games. Please try again.');
                        } else if (typeof alert === 'function') {
                            alert('Failed to load scheduled games. Please try again.');
                        }
                    }
                },
                complete: (xhr, status) => {
                    if (currentSeq === this._scheduleFilterSeq) {
                        wrapper.removeAttr("aria-busy").css({ opacity: "1", pointerEvents: "" });
                        this._scheduleFilterXhr = null;
                    }
                }
            });
        },

        _paymentTableXhr: null,
        _paymentTableSeq: 0,

        loadHostPaymentTable: function (page) {
            page = page || 1;
            this._paymentTablePage = page;
            const year = $("#hpyear").val() || '';
            const month = $("#hpmonth").val() || '';
            const host = $("#hhost").val() || '';
            const search = $("#hpsearch").val() || '';
            const sortBy = $("#hpsortby").val() || 'name';
            const sortDir = $("#hpsortdir").data("dir") || 'asc';
            const pageSize = $("#hp-pagesize").val() || 10;

            const currentSeq = ++this._paymentTableSeq;
            if (this._paymentTableXhr && typeof this._paymentTableXhr.abort === "function") {
                this._paymentTableXhr.abort();
            }

            $(".host_payment").html(
                '<div class="text-center py-4">'
                + '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
                + '<p class="text-muted mt-2 mb-0" style="font-size:0.85rem;">Loading players...</p>'
                + '</div>'
            );

            const paymentUrl = (typeof CasaHost !== 'undefined' && CasaHost.Utils && typeof CasaHost.Utils.getActionUrl === 'function')
                ? CasaHost.Utils.getActionUrl('api/host/payment-list')
                : ((typeof BASE_URL !== 'undefined' && BASE_URL ? (BASE_URL.endsWith('/') ? BASE_URL : BASE_URL + '/') : '/') + 'api/host/payment-list');

            this._paymentTableXhr = $.ajax({
                type: "POST",
                url: paymentUrl,
                data: {
                    year: year,
                    month: month,
                    player: host,
                    search: search,
                    sort_by: sortBy,
                    sort_dir: sortDir,
                    page: page,
                    pageSize: pageSize,
                    type: 'filter'
                },
                success: (response) => {
                    if (currentSeq === this._paymentTableSeq) {
                        const html = (typeof response === 'object' && response !== null && response.html) ? response.html : response;
                        $(".host_payment").html(html);
                        if (window.CasaDropdown) {
                            window.CasaDropdown.initAll($(".host_payment")[0]);
                        }
                        // Payment pagination uses the delegated handlers registered in init().
                    }
                },

                error: (xhr, status, error) => {
                    if (status !== "abort") {
                        console.error('[DashboardFilters] Error loading host payment table:', error);
                        $(".host_payment").html(
                            '<div class="text-center py-4 text-muted">'
                            + '<i class="fa-solid fa-triangle-exclamation text-warning fa-2x mb-2"></i>'
                            + '<p class="mb-2" style="font-size:0.88rem;">Unable to load payment list. Please try again.</p>'
                            + '<button type="button" class="btn btn-sm btn-primary" onclick="if(typeof window.loadHostPaymentTable===\'function\'){window.loadHostPaymentTable(1);}">Retry</button>'
                            + '</div>'
                        );
                        if (typeof showToast === 'function') {
                            showToast('error', 'Failed to load payment table. Please try again.');
                        }
                    }
                },
                complete: () => {
                    if (currentSeq === this._paymentTableSeq) {
                        this._paymentTableXhr = null;
                    }
                }
            });
        },

        onSortDirToggle: function () {
            const current = $(this).data("dir");
            const next = current === "asc" ? "desc" : "asc";
            $(this).data("dir", next);
            $("#hpsortdir-icon").text(next === "asc" ? "â–²" : "â–¼");
            $("#hpsortdir-label").text(next === "asc" ? "ASC" : "DESC");
            CasaHost.DashboardFilters.loadHostPaymentTable(1);
        },

        onSortColClick: function () {
            const col = $(this).data("sort-by");
            const currentSortBy = $("#hpsortby").val();
            const currentSortDir = $("#hpsortdir").data("dir") || "asc";

            if (col === currentSortBy) {
                const next = currentSortDir === "asc" ? "desc" : "asc";
                $("#hpsortdir").data("dir", next);
                $("#hpsortdir-icon").text(next === "asc" ? "â–²" : "â–¼");
                $("#hpsortdir-label").text(next === "asc" ? "ASC" : "DESC");
            } else {
                $("#hpsortby").val(col);
                $("#hpsortdir").data("dir", "asc");
                $("#hpsortdir-icon").text("â–²");
                $("#hpsortdir-label").text("ASC");
            }
            CasaHost.DashboardFilters.loadHostPaymentTable(1);
        },

        onPaginationClick: function (e) {
            e.preventDefault();
            const pg = $(this).data("page");
            const sortBy = $(this).data("sort-by");
            const sortDir = $(this).data("sort-dir");

            if (pg && !$(this).closest(".page-item").hasClass("disabled")) {
                if (sortBy) $("#hpsortby").val(sortBy);
                if (sortDir) {
                    $("#hpsortdir").data("dir", sortDir);
                    $("#hpsortdir-icon").text(sortDir === "asc" ? "â–²" : "â–¼");
                    $("#hpsortdir-label").text(sortDir === "asc" ? "ASC" : "DESC");
                }
                CasaHost.DashboardFilters.loadHostPaymentTable(pg);
            }
        },

        onPaymentRefresh: function () {
            $("#hpyear, #hpmonth, #hhost, #hpsearch").val('');
            $("#hpsortby").val('name');
            $("#hpsortdir").data("dir", "asc");
            $("#hpsortdir-icon").text("â–²");
            $("#hpsortdir-label").text("ASC");
            CasaHost.DashboardFilters.loadHostPaymentTable(1);
        },

        _completedFilterXhr: null,
        _completedFilterSeq: 0,

        filterCompletedSchedule: function () {
            const year = $("#com_year").val() || '';
            const month = $("#com_month").val() || '';
            const wrapper = $("#comp_game");
            if (!wrapper.length) return;

            const currentSeq = ++this._completedFilterSeq;
            if (this._completedFilterXhr && typeof this._completedFilterXhr.abort === "function") {
                this._completedFilterXhr.abort();
            }

            wrapper.attr("aria-busy", "true").css({ opacity: "0.5", pointerEvents: "none" });

            const filterUrl = (typeof CasaHost !== 'undefined' && CasaHost.Utils && typeof CasaHost.Utils.getActionUrl === 'function')
                ? CasaHost.Utils.getActionUrl('api/com_filter_schedule.php')
                : ((typeof BASE_URL !== 'undefined' && BASE_URL ? (BASE_URL.endsWith('/') ? BASE_URL : BASE_URL + '/') : '/') + 'api/com_filter_schedule.php');

            this._completedFilterXhr = $.ajax({
                type: "POST",
                url: filterUrl,
                data: { year: year, month: month, type: 'filter' },
                success: (response) => {
                    if (currentSeq === this._completedFilterSeq) {
                        wrapper.html(response);
                    }
                },
                error: (xhr, status, error) => {
                    if (status !== "abort") {
                        console.error('[DashboardFilters] Completed schedule filter error:', error);
                        if (typeof showToast === 'function') {
                            showToast('error', 'Failed to load completed games. Please try again.');
                        }
                    }
                },
                complete: () => {
                    if (currentSeq === this._completedFilterSeq) {
                        wrapper.removeAttr("aria-busy").css({ opacity: "1", pointerEvents: "" });
                        this._completedFilterXhr = null;
                    }
                }
            });
        },

        onResetCompletedSchedule: function () {
            const wrapper = $("#comp_game");
            const currentSeq = ++this._completedFilterSeq;
            if (this._completedFilterXhr && typeof this._completedFilterXhr.abort === "function") {
                this._completedFilterXhr.abort();
            }

            if (wrapper.length) {
                wrapper.attr("aria-busy", "true").css({ opacity: "0.5", pointerEvents: "none" });
            }

            const resetUrl = (typeof CasaHost !== 'undefined' && CasaHost.Utils && typeof CasaHost.Utils.getActionUrl === 'function')
                ? CasaHost.Utils.getActionUrl('api/com_filter_schedule.php')
                : ((typeof BASE_URL !== 'undefined' && BASE_URL ? (BASE_URL.endsWith('/') ? BASE_URL : BASE_URL + '/') : '/') + 'api/com_filter_schedule.php');

            this._completedFilterXhr = $.ajax({
                type: "POST",
                url: resetUrl,
                data: { type: 'reset' },
                success: (response) => {
                    if (currentSeq === this._completedFilterSeq) {
                        const now = new Date();
                        $("#com_year").val(now.getFullYear());
                        $("#com_month").val(now.getMonth() + 1);
                        if (wrapper.length) {
                            wrapper.html(response);
                        }
                    }
                },
                error: (xhr, status, error) => {
                    if (status !== "abort") {
                        console.error('[DashboardFilters] Reset completed schedule error:', error);
                        if (typeof showToast === 'function') {
                            showToast('error', 'Failed to reset completed games. Please try again.');
                        }
                    }
                },
                complete: () => {
                    if (currentSeq === this._completedFilterSeq) {
                        if (wrapper.length) {
                            wrapper.removeAttr("aria-busy").css({ opacity: "1", pointerEvents: "" });
                        }
                        this._completedFilterXhr = null;
                    }
                }
            });
        }
    };

    // INITIALIZATION (Document Ready)
    $(document).ready(function () {
        CasaHost.GameManager.init();
        CasaHost.PlayerActions.init();
        CasaHost.Modals.init();
        CasaHost.Invitations.init();
        CasaHost.PaymentLedger.init();
        CasaHost.DashboardFilters.init();
    });

    // Backwards-compatible Global Aliases
    window.showCustomConfirm = CasaHost.PlayerActions.showConfirm;
    window.fetchPlayers = CasaHost.Invitations.fetchPlayers;
    window.fetchPlayersSubscription = CasaHost.Invitations.fetchPlayersSubscription;
    window.loadHostPaymentTable = CasaHost.DashboardFilters.loadHostPaymentTable;
    window.filterSchedule = function () {
        if (CasaHost.DashboardFilters && typeof CasaHost.DashboardFilters.onScheduleFilterChange === 'function') {
            CasaHost.DashboardFilters.onScheduleFilterChange();
        }
    };
    window.filterCompletedSchedule = CasaHost.DashboardFilters.filterCompletedSchedule;

})(window, jQuery, window.CasaHost);
