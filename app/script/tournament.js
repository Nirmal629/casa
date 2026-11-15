/**
 * Casa Financial Calculator, Tournament & Calendar Scripts
 */

function calculateTotal() {
    const facilityInput = document.getElementById('facilitycost');
    const accessoriesInput = document.getElementById('accessoriesCost');
    const snacksInput = document.getElementById('snackscost');
    const clubCostInput = document.getElementById('clubClost');

    const totalInput = document.getElementById('eventtotalCostt');
    const playersConfirmedInput = document.getElementById('playersConfirmed');
    const eventCostt = document.getElementById('eventCostt');
    const playerCostInput = document.getElementById('eventtotalplayerCostt');
    const profitLossInput = document.getElementById('profitloss');

    if (!totalInput) return;

    var facility = parseFloat(facilityInput?.value) || 0;
    var accessories = parseFloat(accessoriesInput?.value) || 0;
    var snacks = parseFloat(snacksInput?.value) || 0;
    var clubCost = parseFloat(clubCostInput?.value) || 0;

    // RAW TOTAL
    var rawTotal = facility + accessories + snacks + clubCost;

    // FINAL EVENT TOTAL (CEIL ONLY HERE)
    var total = Math.ceil(rawTotal);
    totalInput.value = total;

    // COST PER PLAYER (CEIL FINAL)
    var playersConfirmed = parseInt(playersConfirmedInput?.value) || 0;
    if (eventCostt) {
        if (playersConfirmed > 0) {
            eventCostt.value = Math.ceil(total / playersConfirmed);
        } else {
            eventCostt.value = Math.ceil(total / 4);
        }
    }

    // PROFIT / LOSS (CEIL FINAL)
    if (profitLossInput) {
        var playerTotal = parseFloat(playerCostInput?.value) || 0;
        var profitLoss = Math.ceil(playerTotal - total);
        profitLossInput.value = profitLoss;
        profitLossInput.style.color = profitLoss < 0 ? 'red' : 'green';
    }
}

function calculateHoursAndFacilityCost() {
    const fromTimeInput = document.getElementById('eventTimee');
    const toTimeInput = document.getElementById('toTimee');
    const hoursInput = document.getElementById('hours');
    const facilityPerHourInput = document.getElementById('facilitycostperhour');
    const courtsConfirmed = document.getElementById('courtconfirmed');
    const facilityInput = document.getElementById('facilitycost');

    if (!fromTimeInput?.value || !toTimeInput?.value) return;

    let [fh, fm] = fromTimeInput.value.split(':').map(Number);
    let [th, tm] = toTimeInput.value.split(':').map(Number);

    let diffMinutes = (th * 60 + tm) - (fh * 60 + fm);
    if (diffMinutes < 0) diffMinutes += 1440;

    // KEEP DECIMAL HOURS
    let hoursDecimal = diffMinutes / 60;
    if (hoursInput) hoursInput.value = hoursDecimal.toFixed(2);

    let costPerHour = parseFloat(facilityPerHourInput?.value) || 0;
    const courts = parseInt(courtsConfirmed?.value, 10) || 1;

    // FACILITY COST (NO CEIL HERE)
    if (facilityInput) {
        facilityInput.value = (hoursDecimal * costPerHour * courts).toFixed(2);
    }

    calculateTotal();
}

/* ==========================
   BIRDIE → ACCESSORIES COST
========================== */
function calculateAccessoriesCost() {
    const birdieUsedInput = document.getElementById('birdieUsed');
    const birdiePriceInput = document.getElementById('nobirdieUsed');
    const accessoriesInput = document.getElementById('accessoriesCost');

    let birdieUsed = parseFloat(birdieUsedInput?.value) || 0;
    let birdiePrice = parseFloat(birdiePriceInput?.value) || 0;

    // NO CEIL HERE
    if (accessoriesInput) {
        accessoriesInput.value = (birdieUsed * birdiePrice).toFixed(2);
    }
    calculateTotal();
}

/* ==========================
   EVENT LISTENERS & CALENDAR
========================== */
document.addEventListener('DOMContentLoaded', function () {
    const fromTimeInput = document.getElementById('eventTimee');
    const toTimeInput = document.getElementById('toTimee');
    const facilityPerHourInput = document.getElementById('facilitycostperhour');
    const birdieUsedInput = document.getElementById('birdieUsed');
    const birdiePriceInput = document.getElementById('nobirdieUsed');

    const facilityInput = document.getElementById('facilitycost');
    const accessoriesInput = document.getElementById('accessoriesCost');
    const snacksInput = document.getElementById('snackscost');
    const clubCostInput = document.getElementById('clubClost');
    const playerCostInput = document.getElementById('eventtotalplayerCostt');
    const playersConfirmedInput = document.getElementById('playersConfirmed');

    if (fromTimeInput) {
        ['change', 'input'].forEach(evt => fromTimeInput.addEventListener(evt, calculateHoursAndFacilityCost));
    }
    if (toTimeInput) {
        ['change', 'input'].forEach(evt => toTimeInput.addEventListener(evt, calculateHoursAndFacilityCost));
    }
    if (facilityPerHourInput) {
        facilityPerHourInput.addEventListener('input', calculateHoursAndFacilityCost);
    }

    if (birdieUsedInput) birdieUsedInput.addEventListener('input', calculateAccessoriesCost);
    if (birdiePriceInput) birdiePriceInput.addEventListener('input', calculateAccessoriesCost);

    if (facilityInput) facilityInput.addEventListener('input', calculateTotal);
    if (accessoriesInput) accessoriesInput.addEventListener('input', calculateTotal);
    if (snacksInput) snacksInput.addEventListener('input', calculateTotal);
    if (clubCostInput) clubCostInput.addEventListener('input', calculateTotal);
    if (playerCostInput) playerCostInput.addEventListener('input', calculateTotal);
    if (playersConfirmedInput) playersConfirmedInput.addEventListener('input', calculateTotal);

    // Initial calculation if inputs are present
    calculateTotal();

    // Calendar navigation
    const prevBtn = document.getElementById("prevBtn");
    const nextBtn = document.getElementById("nextBtn");
    const dateInput = document.getElementById("dateInput");
    const calendar = document.getElementById("calendar");

    if (prevBtn) {
        prevBtn.addEventListener("click", () => {
            if (typeof currentDate !== 'undefined' && typeof renderCalendar === 'function') {
                currentDate.setMonth(currentDate.getMonth() - 1);
                renderCalendar();
            }
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener("click", () => {
            if (typeof currentDate !== 'undefined' && typeof renderCalendar === 'function') {
                currentDate.setMonth(currentDate.getMonth() + 1);
                renderCalendar();
            }
        });
    }

    if (dateInput && calendar) {
        dateInput.addEventListener("click", () => {
            calendar.style.display = "block";
            positionCalendar();
        });

        document.addEventListener("click", (event) => {
            if (!dateInput.contains(event.target) && !calendar.contains(event.target)) {
                calendar.style.display = "none";
            }
        });

        function positionCalendar() {
            const inputRect = dateInput.getBoundingClientRect();
            calendar.style.top = inputRect.bottom + "px";
            calendar.style.left = inputRect.left + "px";
        }

        window.addEventListener("resize", positionCalendar);
    }
});
