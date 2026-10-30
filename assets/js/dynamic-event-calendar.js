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

    function initDynamicEventCalendar(options) {
        options = options || {};
        var date = document.querySelector(options.date || '#eventDate, #eventDatee, #eventDateee');
        var freezeDate = document.querySelector(options.freezeDate || '#freezeDate, #freezeDatee, #freezeDateee');
        var fromTime = document.querySelector(options.fromTime || '#eventTime, #eventTimee, #eventTimeee');
        var toTime = document.querySelector(options.toTime || '#toTime, #toTimee, #toTimeee');
        var freezeTime = document.querySelector(options.freezeTime || '#freezeTime, #freezeTimee, #freezeTimeee');
        if (!date) return null;

        var today = new Date();
        var todayText = localDateString(today);
        date.min = todayText;
        if (freezeDate) freezeDate.min = todayText;

        var weeklyDay = options.weeklyDay || date.dataset.weeklyDay || '';
        if (weeklyDay && !date.value) {
            var next = nextWeekday(weeklyDay, today);
            if (next) date.value = localDateString(next);
        }

        if (freezeDate && !freezeDate.value && date.value) {
            freezeDate.value = date.value;
        }

        function validate() {
            if (date.value && date.value < todayText) {
                date.setCustomValidity('Event date cannot be in the past.');
            } else {
                date.setCustomValidity('');
            }

            if (freezeDate) {
                freezeDate.min = todayText;
                if (date.value && freezeDate.value && freezeDate.value > date.value) {
                    freezeDate.setCustomValidity('Freeze date must be on or before the event date.');
                } else {
                    freezeDate.setCustomValidity('');
                }
            }

            if (fromTime && toTime && fromTime.value && toTime.value) {
                toTime.setCustomValidity(toTime.value <= fromTime.value
                    ? 'To time must be after the from time.'
                    : '');
            }

            if (freezeTime && fromTime && freezeTime.value && fromTime.value) {
                freezeTime.setCustomValidity(freezeTime.value >= fromTime.value
                    ? 'Freeze time must be before the event start time.'
                    : '');
            }
        }

        [date, freezeDate, fromTime, toTime, freezeTime].forEach(function (field) {
            if (field) field.addEventListener('change', validate);
        });
        validate();

        return { validate: validate, nextWeekday: nextWeekday };
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
    window.CasaCalendar = {
        initDynamicEventCalendar: initDynamicEventCalendar,
        getCurrentPeriod: getCurrentPeriod,
        initSchedulePeriod: initSchedulePeriod,
        MONTH_NAMES: MONTH_NAMES
    };
}(window, document));
