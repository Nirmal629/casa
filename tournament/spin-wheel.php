<div class="Spinbox_wrap">

    <div class="d-flex align-items-center gap-1 mt-3">
        <input type="text" id="teamInput" placeholder="Enter Team Name">
        <button onclick="addTeam()">Add Team</button>
    </div>

    <div class="wheel-container">
        <div class="pointer"></div>
        <div id="wheel">
            <div class="center-circle">SPIN</div>
        </div>
    </div>

    <div class="d-flex align-items-center gap-1 mt-3">
        <button id="spinBtn" onclick="spin()">Spin</button>
        <button onclick="resetWheel()">Reset</button>
    </div>

    <div class="result" id="result"></div>

</div>

<script>
    let teams = [];
    let winners = [];
    let currentRotation = 0;
    let isSpinning = false;

    function addTeam() {
        const input = document.getElementById("teamInput");
        const name = input.value.trim();
        if (name === "") return;

        teams.push(name);
        input.value = "";
        createWheel();
    }

    function createWheel() {
        const wheel = document.getElementById("wheel");
        wheel.innerHTML = '<div class="center-circle">SPIN</div>';

        if (teams.length === 0) return;

        const sliceAngle = 360 / teams.length;
        const colors = ["#ff4757", "#ffa502", "#2ed573", "#1e90ff",
            "#e84393", "#00cec9", "#fdcb6e", "#6c5ce7"
        ];

        let gradient = "conic-gradient(";

        teams.forEach((team, index) => {
            gradient += `${colors[index % colors.length]} ${index * sliceAngle}deg ${(index + 1) * sliceAngle}deg`;
            if (index < teams.length - 1) gradient += ",";
        });

        gradient += ")";
        wheel.style.background = gradient;

        teams.forEach((team, index) => {
            const label = document.createElement("div");
            label.className = "label";
            label.innerText = team;

            const angle = index * sliceAngle + sliceAngle / 2;
            label.style.transform =
                `rotate(${angle}deg) translate(130px) rotate(-${angle}deg)`;

            wheel.appendChild(label);
        });
    }

    function spin() {

        if (isSpinning || teams.length === 0) return;

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
            const pointerDeg = (360 - actualDeg) % 360;
            const selectedIndex = Math.floor(pointerDeg / sliceAngle);

            const winner = teams[selectedIndex];

            winners.push(winner);

            teams.splice(selectedIndex, 1);
            createWheel();

            showResults();

            isSpinning = false;
            document.getElementById("spinBtn").disabled = false;

            if (teams.length === 0) {
                document.getElementById("spinBtn").disabled = true;
            }

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

            resultDiv.innerHTML += `${position}: <b>${team}</b><br>`;
        });
    }

    function resetWheel() {
        teams = [];
        winners = [];
        currentRotation = 0;
        isSpinning = false;

        const wheel = document.getElementById("wheel");
        wheel.style.transform = "rotate(0deg)";
        wheel.innerHTML = '<div class="center-circle">SPIN</div>';
        document.getElementById("result").innerHTML = "";
        document.getElementById("spinBtn").disabled = false;
    }
</script>