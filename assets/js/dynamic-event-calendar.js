(function (window, document) {
    'use strict';

    function pad(value) {
        return String(value).padStart(2, '0');
    }

    function localDateString(date) {
        return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
    }

    function nextWeekday(dayName, fromDate) {
        var days = {
            SUNDAY: 0, MONDAY: 1, TUESDAY: 2, WEDNESDAY: 3,
            THURSDAY: 4, FRIDAY: 5, SATURDAY: 6
        };
        var target = days[String(dayName || '').toUpperCase()];
        if (target === undefined) return null;

        var date = new Date(fromDate.getFullYear(), fromDate.getMonth(), fromDate.getDate());
        var delta = (target - date.getDay() + 7) % 7;
        date.setDate(date.getDate() + delta);
        return date;
    }

    function parseDateTime(dateVal, timeVal) {
        if (!dateVal || !timeVal) return null;
        dateVal = String(dateVal).trim();
        timeVal = String(timeVal).trim();

        var dParts;
        if (dateVal.indexOf('-') !== -1) {
            dParts = dateVal.split('-');
        } else if (dateVal.indexOf('/') !== -1) {
            dParts = dateVal.split('/');
        } else {
            return null;
        }

        var year, month, day;
        if (dParts[0].length === 4) {
            // YYYY-MM-DD
            year = parseInt(dParts[0], 10);
            month = parseInt(dParts[1], 10) - 1;
            day = parseInt(dParts[2], 10);
        } else if (dParts.length >= 3 && dParts[2].length === 4) {
            // DD-MM-YYYY or MM-DD-YYYY
            day = parseInt(dParts[0], 10);
            month = parseInt(dParts[1], 10) - 1;
            year = parseInt(dParts[2], 10);
        } else {
            return null;
        }

        var tParts = timeVal.split(':');
        var hours = parseInt(tParts[0], 10);
        var minutes = parseInt(tParts[1] || '0', 10);
        var seconds = parseInt(tParts[2] || '0', 10);

        if (isNaN(year) || isNaN(month) || isNaN(day) || isNaN(hours) || isNaN(minutes)) {
            return null;
        }

        return new Date(year, month, day, hours, minutes, seconds);
    }

    function validateFreezeDateTime(eventDate, eventTime, freezeDate, freezeTime) {
        var startDt = parseDateTime(eventDate, eventTime);
        var freezeDt = parseDateTime(freezeDate, freezeTime);

        if (!startDt || !freezeDt) {
            return { valid: true, error: null };
        }

        if (freezeDt.getTime() > startDt.getTime()) {
            return {
                valid: false,
                error: 'Freeze date & time cannot be later than event start date & time.'
            };
        }

        return { valid: true, error: null };
    }

    function initDynamicEventCalendar(options) {
        options = options || {};
        var date = document.querySelector(options.date || '#eventDate, #eventDatee, #eventDateee');
        var freezeDate = document.querySelector(options.freezeDate || '#freezeDate, #freezeDatee, #freezeDateee');
        var fromTime = document.querySelector(options.fromTime || '#eventTime, #eventTimee, #eventTimeee');
        var toTime = document.querySelector(options.toTime || '#toTime, #toTimee, #toTimeee');
        var freezeTime = document.querySelector(options.freezeTime || '#freezeTime, #freezeTimee, #freezeTimeee');
        var freezeType = document.querySelector(options.freezeType || '#freezeType, #freezeTypee, #freezeTypeee');
        if (!date) return null;

        var today = new Date();
        var todayText = localDateString(today);
        date.min = todayText;
        if (freezeDate) {
            freezeDate.min = todayText;
            freezeDate.readOnly = false;
        }
        if (freezeTime) {
            freezeTime.style.pointerEvents = '';
        }

        var weeklyDay = options.weeklyDay || date.dataset.weeklyDay || '';
        if (weeklyDay && !date.value) {
            var next = nextWeekday(weeklyDay, today);
            if (next) date.value = localDateString(next);
        }

        // Initialize default freeze DateTime to event start DateTime if empty
        if (freezeDate && !freezeDate.value && date.value) {
            freezeDate.value = date.value;
        }
        if (freezeDate && date.value) {
            freezeDate.max = date.value;
        }
        if (freezeTime && !freezeTime.value && fromTime && fromTime.value) {
            freezeTime.value = fromTime.value;
        }

        function syncBounds() {
            if (freezeDate) {
                freezeDate.readOnly = false;
                freezeDate.min = todayText;
                if (date && date.value) {
                    freezeDate.max = date.value;
                }
            }
            if (freezeTime) {
                freezeTime.style.pointerEvents = '';
            }
        }

        function validate() {
            syncBounds();

            if (date.value && date.value < todayText) {
                date.setCustomValidity('Event date cannot be in the past.');
            } else {
                date.setCustomValidity('');
            }

            if (freezeDate) {
                if (freezeDate.value && freezeDate.value < todayText) {
                    freezeDate.setCustomValidity('Freeze date cannot be in the past.');
                } else if (date.value && freezeDate.value && freezeDate.value > date.value) {
                    freezeDate.setCustomValidity('Freeze date cannot be later than event date.');
                } else {
                    freezeDate.setCustomValidity('');
                }
            }

            if (freezeTime) {
                freezeTime.setCustomValidity('');
            }

            // Complete DateTime validation between Event Start and Freeze
            if (date.value && fromTime && fromTime.value && freezeDate && freezeDate.value && freezeTime && freezeTime.value) {
                var check = validateFreezeDateTime(date.value, fromTime.value, freezeDate.value, freezeTime.value);
                if (!check.valid) {
                    freezeTime.setCustomValidity(check.error);
                } else {
                    freezeTime.setCustomValidity('');
                }
            }

            // Duration validation (To Time)
            if (fromTime && toTime && fromTime.value && toTime.value) {
                if (toTime.value === fromTime.value) {
                    toTime.setCustomValidity('To time must be different from start time.');
                } else {
                    toTime.setCustomValidity('');
                }
            }
        }

        function onDateChange() {
            if (date && date.value) {
                if (freezeDate) {
                    freezeDate.max = date.value;
                    if (!freezeDate.value || freezeDate.value > date.value) {
                        freezeDate.value = date.value;
                    }
                }
            }
            validate();
        }

        function onFromTimeChange() {
            if (fromTime && fromTime.value) {
                if (freezeTime && !freezeTime.value) {
                    freezeTime.value = fromTime.value;
                }
            }
            validate();
        }

        if (date) {
            date.addEventListener('change', onDateChange);
            date.addEventListener('input', onDateChange);
        }
        if (fromTime) {
            fromTime.addEventListener('change', onFromTimeChange);
            fromTime.addEventListener('input', onFromTimeChange);
        }
        if (toTime) {
            toTime.addEventListener('change', validate);
            toTime.addEventListener('input', validate);
        }
        if (freezeDate) {
            freezeDate.addEventListener('change', validate);
            freezeDate.addEventListener('input', validate);
        }
        if (freezeTime) {
            freezeTime.addEventListener('change', validate);
            freezeTime.addEventListener('input', validate);
        }
        if (freezeType) {
            freezeType.addEventListener('change', validate);
        }

        // Attach form submit validation to report errors clearly
        var form = (date && date.form) || (freezeDate && freezeDate.form) || document.querySelector(options.form || '#eventFormm');
        if (form) {
            form.addEventListener('submit', function (e) {
                validate();
                if (!form.checkValidity()) {
                    e.preventDefault();
                    if (typeof form.reportValidity === 'function') {
                        form.reportValidity();
                    }
                }
            });
        }

        syncBounds();
        validate();

        return {
            validate: validate,
            nextWeekday: nextWeekday,
            parseDateTime: parseDateTime,
            validateFreezeDateTime: validateFreezeDateTime
        };
    }

    // Shared calendar period values for schedule filters. Keeping this in one
    // asset avoids duplicating year/month calculations across PHP views.
    var MONTH_NAMES = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    function getCurrentPeriod() {
        var now = new Date();
        return { year: now.getFullYear(), month: now.getMonth() + 1 };
    }

    function initSchedulePeriod(options) {
        options = options || {};
        var year = document.querySelector(options.year || '#year');
        var month = document.querySelector(options.month || '#month');
        var period = getCurrentPeriod();

        // Populate years if empty (previous year and current year)
        if (year && year.options.length === 0) {
            var startYear = options.startYear || (period.year - 1);
            var endYear = options.endYear || (period.year + 1);
            for (var y = startYear; y <= endYear; y++) {
                var optY = document.createElement('option');
                optY.value = String(y);
                optY.textContent = String(y);
                if (y === period.year) optY.selected = true;
                year.appendChild(optY);
            }
        }

        // Populate months if empty (Jan to Dec)
        if (month && month.options.length === 0) {
            MONTH_NAMES.forEach(function (name, index) {
                var m = index + 1;
                var optM = document.createElement('option');
                optM.value = String(m);
                optM.textContent = name;
                if (m === period.month) optM.selected = true;
                month.appendChild(optM);
            });
        }

        if (year && !year.value) year.value = String(period.year);
        if (month && !month.value) month.value = String(period.month);

        if (year && year.casaDropdown && typeof year.casaDropdown.refresh === 'function') {
            year.casaDropdown.refresh();
        }
        if (month && month.casaDropdown && typeof month.casaDropdown.refresh === 'function') {
            month.casaDropdown.refresh();
        }

        return period;
    }

    // Auto-populate & validate when document is ready
    function autoInit() {
        initSchedulePeriod();
        initDynamicEventCalendar();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInit);
    } else {
        autoInit();
    }

    window.initDynamicEventCalendar = initDynamicEventCalendar;
    window.getCurrentPeriod = getCurrentPeriod;
    window.initSchedulePeriod = initSchedulePeriod;
    window.MONTH_NAMES = MONTH_NAMES;
    window.parseDateTime = parseDateTime;
    window.validateFreezeDateTime = validateFreezeDateTime;
    window.CasaCalendar = {
        initDynamicEventCalendar: initDynamicEventCalendar,
        getCurrentPeriod: getCurrentPeriod,
        initSchedulePeriod: initSchedulePeriod,
        MONTH_NAMES: MONTH_NAMES,
        parseDateTime: parseDateTime,
        validateFreezeDateTime: validateFreezeDateTime
    };
}(window, document));
