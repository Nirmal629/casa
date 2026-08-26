<div class="Spinbox_wrap">

    <div class="d-flex align-items-center gap-1 mt-3">
        <input type="text" id="teamInput" placeholder="Enter Name">
        <button type="button" onclick="addTeam()">Add</button>
    </div>

    <div class="wheel-container">
        <div class="pointer"></div>
        <div id="wheel">
            <div class="center-circle">SPIN</div>
        </div>
    </div>

    <div class="d-flex align-items-center gap-1 mt-3">
        <button type="button" id="spinBtn" onclick="spin()">Spin</button>
        <button type="button" onclick="resetWheel()">Reset</button>
    </div>

    <div class="result" id="result"></div>

</div>

<script>
    let teams = [];
    let winners = [];
    let currentRotation = 0;
    let isSpinning = false;
    let spinSelectionCallback = null;
    let pendingRemoval = "";
    let removalTimer = null;
    let colorMap = {};
    let selectedLabels = {};
    const colors = ["#ff4757", "#ffa502", "#2ed573", "#1e90ff",
        "#e84393", "#00cec9", "#fdcb6e", "#6c5ce7"
    ];

    function itemValue(item) {
        return item && typeof item === "object" ? String(item.value) : String(item);
    }

    function itemLabel(item) {
        return item && typeof item === "object" ? String(item.label) : String(item);
    }

    function rememberItemColor(item) {
        const value = itemValue(item);
        if (!colorMap[value]) {
            colorMap[value] = colors[Object.keys(colorMap).length % colors.length];
        }
        return colorMap[value];
    }

    function addTeam() {
        const input = document.getElementById("teamInput");
        const name = input.value.trim();
        if (name === "") return;

        const item = { value: name, label: name };
        teams.push(item);
        rememberItemColor(item);
        input.value = "";
        createWheel();
    }

    function createWheel() {
        const wheel = document.getElementById("wheel");
        wheel.innerHTML = '<div class="center-circle">SPIN</div>';
        wheel.style.background = "";

        if (teams.length === 0) return;

        const sliceAngle = 360 / teams.length;

        let gradient = "conic-gradient(";

        teams.forEach((team, index) => {
            gradient += `${rememberItemColor(team)} ${index * sliceAngle}deg ${(index + 1) * sliceAngle}deg`;
            if (index < teams.length - 1) gradient += ",";
        });

        gradient += ")";
        wheel.style.background = gradient;

        teams.forEach((team, index) => {
            const label = document.createElement("div");
            label.className = "label";
            label.innerText = itemLabel(team);

            const angle = index * sliceAngle + sliceAngle / 2;
            label.style.transform =
                `rotate(${angle}deg) translate(94px) rotate(-${angle}deg) translate(-50%, -50%)`;

            wheel.appendChild(label);
        });
    }

    function removePendingWinnerBeforeNextSpin() {
        if (!pendingRemoval) return;

        teams = teams.filter((team) => itemValue(team) !== pendingRemoval);
        pendingRemoval = "";
        currentRotation = 0;

        const wheel = document.getElementById("wheel");
        wheel.style.transition = "none";
        wheel.style.transform = "rotate(0deg)";
        createWheel();
        wheel.offsetHeight;
        wheel.style.transition = "";
    }

    function schedulePendingWinnerRemoval() {
        clearTimeout(removalTimer);
        removalTimer = setTimeout(() => {
            removePendingWinnerBeforeNextSpin();
            isSpinning = false;
            document.getElementById("spinBtn").disabled = teams.length === 0;
        }, 1000);
    }

    function spin() {

        if (isSpinning || teams.length === 0) return;

        removePendingWinnerBeforeNextSpin();
        if (teams.length === 0) {
            document.getElementById("spinBtn").disabled = true;
            return;
        }

        isSpinning = true;
        document.getElementById("spinBtn").disabled = true;

        const sliceAngle = 360 / teams.length;
        const randomDeg = Math.floor(Math.random() * 360);
        const extraSpins = 360 * 6;

        const finalRotation = currentRotation + extraSpins + randomDeg;
        const wheel = document.getElementById("wheel");

        wheel.style.transform = `rotate(${finalRotation}deg)`;
        currentRotation = finalRotation;

        setTimeout(() => {

            const actualDeg = finalRotation % 360;
            const pointerDeg = (270 - actualDeg + 360) % 360;
            const selectedIndex = Math.floor(pointerDeg / sliceAngle);

            const winner = teams[selectedIndex];
            const winnerValue = itemValue(winner);

            winners.push(winnerValue);
            selectedLabels[winnerValue] = itemLabel(winner);
            pendingRemoval = winnerValue;

            showResults();
            if (typeof spinSelectionCallback === "function") {
                spinSelectionCallback(
                    winners.slice(),
                    teams.filter((team) => itemValue(team) !== winnerValue).map(itemValue),
                    winnerValue
                );
            }

            schedulePendingWinnerRemoval();

        }, 4000);
    }

    function showResults() {

        const resultDiv = document.getElementById("result");
        resultDiv.innerHTML = "";

        winners.forEach((team, index) => {
            let position;

            if (index === 0) position = "1st";
            else if (index === 1) position = "2nd";
            else if (index === 2) position = "3rd";
            else position = `${index + 1}th`;

            const resultRow = document.createElement("div");
            resultRow.append(document.createTextNode(position + ": "));
            const resultName = document.createElement("b");
            resultName.textContent = selectedLabels[team] || team;
            resultRow.append(resultName);
            resultDiv.append(resultRow);
        });
    }

    function resetWheel() {
        teams = [];
        winners = [];
        currentRotation = 0;
        isSpinning = false;
        pendingRemoval = "";
        clearTimeout(removalTimer);
        removalTimer = null;
        colorMap = {};
        selectedLabels = {};

        const wheel = document.getElementById("wheel");
        wheel.style.transition = "";
        wheel.style.transform = "rotate(0deg)";
        wheel.style.background = "";
        wheel.innerHTML = '<div class="center-circle">SPIN</div>';
        document.getElementById("result").innerHTML = "";
        document.getElementById("spinBtn").disabled = false;
    }

    window.loadSpinItems = function (items, callback) {
        teams = Array.isArray(items) ? items.map((item) => {
            if (item && typeof item === "object") {
                return { value: String(item.value), label: String(item.label) };
            }
            return { value: String(item), label: String(item) };
        }) : [];
        winners = [];
        currentRotation = 0;
        isSpinning = false;
        pendingRemoval = "";
        clearTimeout(removalTimer);
        removalTimer = null;
        colorMap = {};
        selectedLabels = {};
        teams.forEach(rememberItemColor);
        spinSelectionCallback = typeof callback === "function" ? callback : null;

        const wheel = document.getElementById("wheel");
        wheel.style.transition = "";
        wheel.style.transform = "rotate(0deg)";
        document.getElementById("result").innerHTML = "";
        document.getElementById("teamInput").value = "";
        document.getElementById("spinBtn").disabled = teams.length === 0;
        createWheel();
    };
</script>
