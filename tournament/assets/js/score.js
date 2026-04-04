function incrementScore(scoreSide) {
    if(!startGame) {
        modalShowHide(`#game-settings`, "show");
        return;
    }

    // disable the button until all have been executed
    disableElement(`.left-scorer`);
    disableElement(`.right-scorer`);
    setBackgroundColor(`.left-scorer`, "gray");
    setBackgroundColor(`.right-scorer`, "gray");

    if (gameMatchType == 1) {
        setElementValue(`#team-a-player-1`, getElementValue(`#team-a-player-2`));
        setElementSrc(`.team-a-player-1-img`, getElementSrc(`.team-a-player-2-img`));
        setElementValue(`#team-b-player-1`, getElementValue(`#team-b-player-2`));
        setElementSrc(`.team-b-player-1-img`, getElementSrc(`.team-b-player-2-img`));
    }
    if (scoreSide == 'left') {
        teamAScore++;
        if (teamAScore % 2 == 0) {
            showHideShuttle("main", `${scoreSide}`, 'right');
            if (gameMatchType == 1) {
                evenScoreShowHidePlayers();
            }
        } else {
            showHideShuttle("main", `${scoreSide}`, 'left');
            if (gameMatchType == 1) {
                oddScoreShowHidePlayers();
            }
        }
        if (scoreSide == 'left' && serviceOver == 'left') {
            speakThisMsg("Service  Over");
        } else {
            switchPlayer(`${scoreSide}`);
        }
        speakThisMsg("Score");
        if (teamAScore == teamBScore) {
            speakThisMsg(teamAScore);   
            speakThisMsg("all");
        } else {
            speakThisMsg(teamAScore);   
            speakThisMsg(teamBScore);
        }
        serviceOver = 'right';
    } else if (scoreSide == 'right') {
        teamBScore++;
        if (teamBScore % 2 == 0) {
            showHideShuttle("main", `${scoreSide}`, 'right');
            if (gameMatchType == 1) {
                evenScoreShowHidePlayers();
            }
        } else {
            showHideShuttle("main", `${scoreSide}`, 'left');
            if (gameMatchType == 1) {
                oddScoreShowHidePlayers();
            }
        }
        if (scoreSide == 'right' && serviceOver == 'right') {
            speakThisMsg("Service  Over");
        } else {
            switchPlayer(`${scoreSide}`);
        }
        speakThisMsg("Score");
        if (teamBScore == teamAScore) {
            speakThisMsg(teamBScore);   
            speakThisMsg("all");
        } else {
            //Here to put logic to end game set base on team score
            speakThisMsg(teamBScore);   
            speakThisMsg(teamAScore);
        }
        serviceOver = 'left';
    }

    // Reflect the score on the Score Tally Board
    switch(gameSet) {
        case 1:
            setElementInnerHTML(`#team-a-set-one`, teamAScore);
            setElementInnerHTML(`#team-b-set-one`, teamBScore);
            // For the Tally Result Report
            setElementInnerHTML(`#tally-team-a-set-one`, teamAScore);
            setElementInnerHTML(`#tally-team-b-set-one`, teamBScore);
            
            appendChild(`#score-board-headings`, `<td class="rally-points">-</td>`);
            appendChild(`#main-tally-set-1-a`, `<td class="rally-points">${scoreSide == 'left' ? "X" : "0"}</td>`);
            appendChild(`#main-tally-set-1-b`, `<td class="rally-points">${scoreSide == 'left' ? "0" : "X"}</td>`);
            break;
        case 2:
            setElementInnerHTML(`#team-a-set-two`, teamBScore);
            setElementInnerHTML(`#team-b-set-two`, teamAScore);
            // For the Tally Result Report
            setElementInnerHTML(`#tally-team-a-set-two`, teamBScore);
            setElementInnerHTML(`#tally-team-b-set-two`, teamAScore);

            appendChild(`#main-tally-set-2-a`, `<td class="rally-points">${scoreSide == 'left' ? "0" : "X"}</td>`);
            appendChild(`#main-tally-set-2-b`, `<td class="rally-points">${scoreSide == 'left' ? "X" : "0"}</td>`);
            break;
        case 3:
            switch(blnMidBreak) {
                case false:
                    setElementInnerHTML(`#team-a-set-three`, teamAScore);
                    setElementInnerHTML(`#team-b-set-three`, teamBScore);
                    // For the Tally Result Report
                    setElementInnerHTML(`#tally-team-a-set-three`, teamAScore);
                    setElementInnerHTML(`#tally-team-b-set-three`, teamBScore);

                    appendChild(`#main-tally-set-3-a`, `<td class="rally-points">${scoreSide == 'left' ? "X" : "0"}</td>`);
                    appendChild(`#main-tally-set-3-b`, `<td class="rally-points">${scoreSide == 'left' ? "0" : "X"}</td>`);
                    break;
                case true:
                    setElementInnerHTML(`#team-a-set-three`, teamBScore);
                    setElementInnerHTML(`#team-b-set-three`, teamAScore);
                    // For the Tally Result Report
                    setElementInnerHTML(`#tally-team-a-set-three`, teamBScore);
                    setElementInnerHTML(`#tally-team-b-set-three`, teamAScore);

                    appendChild(`#main-tally-set-3-a`, `<td class="rally-points">${scoreSide == 'left' ? "0" : "X"}</td>`);
                    appendChild(`#main-tally-set-3-b`, `<td class="rally-points">${scoreSide == 'left' ? "X" : "0"}</td>`);
                    break;
            }
    }

    // let's put some delay (2secs) here if voice over is enabled
    voiceTimer = 2;
    if (blnVoiceOver) {
        delayForVoice(voiceTimer);
    } else {
        enableElement(`.left-scorer`);
        enableElement(`.right-scorer`);
    }

    // This will enable the mid-game interval timer when a team reached 11 points first
    if (((teamAScore == 11 && teamBScore < 11) || (teamBScore == 11 && teamAScore < 11)) && blnMidBreak == false)  {
        setElementInnerHTML(`#interval-timer`, "...");
        blnMidBreak = true;
        if(gameSet == 3 && ((teamAScore == 11 && teamBScore < 11) || (teamBScore == 11 && teamAScore < 11))) {
            switchCourt((teamAScore > teamBScore) ? "right" : "left");
        }
        modalShowHide('#game-interval', 'show');
        hideElement(`#close-interval`);
        hideElement(`#start-new-set`);
        if (teamAGameWin < 2 && teamBGameWin < 2) {
            if (intMidIntervalBreak > 0){
                intervalCountdown(intMidIntervalBreak);
            } else {
                modalShowHide('#game-interval', 'hide');
            }
        }
    }

    // This will enable the full-game interval timer if one of the following condition is met
    // 1. If a team score 21 points first and with a lead points of 2 or more (i.e. 21:19 or 19:21, or 21:7 etc)
    // 2. if a team score is greater than 21 points with a lead points of 2 (i.e. 22:20, 20:22, 23:21 etc.)
    // 3. if a team reaches 30 points first (i.e. 30:29 or 29:30)
    if ((((teamAScore == 21 && teamBScore <= 19) || (teamBScore == 21 && teamAScore <= 19))
        || ((teamAScore > 21 && (teamAScore-teamBScore) >= 2) || (teamBScore > 21 && teamBScore - teamAScore >= 2))
        || (teamAScore == 30 || teamBScore == 30)
        ) && gameSet <= 3) {
        blnMidBreak = false;
        setElementInnerHTML(`#interval-timer`, "...");
        modalShowHide('#game-interval', 'show');
        hideElement(`#close-interval`);
        if (teamAGameWin < 2 && teamBGameWin < 2) {
            if (intFullIntervalBreak > 0) {
                hideElement(`#start-new-set`);
                intervalCountdown(intFullIntervalBreak);
            } else {
                modalShowHide('#game-interval', 'hide');
            }
        }
        // Set Team Number of win here
        switch(gameSet) {
            case 1:
                (teamAScore > teamBScore) ? teamAGameWin++ : teamBGameWin++;
                break;
            case 2:
            case 3:
                (teamAScore > teamBScore) ? teamBGameWin++ : teamAGameWin++;
                break;
        }

        //if (teamAScore > teamBScore)
        gameSet++;
        switchCourt(teamAScore > teamBScore ? 'right' : 'left');
        teamAScore = 0;
        teamBScore = 0;
    }
    if (teamAGameWin == 2 || teamBGameWin == 2) {
        //End of Game Match Here show Match Result Tally Score Board
        setElementInnerHTML(`#game-set`, "End of Game Match!");
        hideElement(`#cancel-game`);
        modalShowHide('#end-game-set', 'show');
        modalShowHide('#end-game-result-dialog', 'hide');
        hideElement(`#game-interval`);
        hideElement(`#end-match`);
        showElement(`#new-match`);
    }
}

function enableNewGame() {
    hideElement(`#end-match`);
    showElement(`#new-match`);
    modalShowHide(`#end-game-result`, 'hide');
    $('.modal-backdrop').remove();
    initializeGameCourt();
    showAllPlayers();
}

function intervalCountdown(seconds) {
    if (seconds <= 0) {
        modalShowHide(`#game-interval`, 'hide');
        return;
    }
    let counter = seconds;
    const interval = setInterval(() => {
        setElementInnerHTML(`#interval-timer`, counter);
        counter--;
        if (counter < 0 ) {
            clearInterval(interval);
            modalShowHide(`#game-interval`, 'hide');
        }
    }, 1000);
}

function delayForVoice(seconds) {
    if (seconds <= 0) {
        return;
    }
    let counter = seconds;
    const interval = setInterval(() => {
        counter--;
        if (counter <= 0 ) {
            clearInterval(interval);
            enableElement(`.left-scorer`);
            enableElement(`.right-scorer`);
        }
    }, 1000);
}

// function to call for set timeout
function switchPlayer(scoreSide) {
    var tempName = "";
    var tempColor = "";
    if (scoreSide == 'left') {
        tempName = getElementValue(`#team-a-player-1`);
        tempColor = getElementSrc(`.team-a-player-1-img`);
        setElementValue(`#team-a-player-1`, getElementValue(`#team-a-player-2`));
        setElementSrc(`.team-a-player-1-img`, getElementSrc(`.team-a-player-2-img`));
        setElementValue(`#team-a-player-2`, tempName);
        setElementSrc(`.team-a-player-2-img`, tempColor);
    } else if (scoreSide == 'right') {
        tempName = getElementValue(`#team-b-player-1`);
        tempColor = getElementSrc(`.team-b-player-1-img`);
        setElementValue(`#team-b-player-1`, getElementValue(`#team-b-player-2`));
        setElementSrc(`.team-b-player-1-img`, getElementSrc(`.team-b-player-2-img`));
        setElementValue(`#team-b-player-2`, tempName);
        setElementSrc(`.team-b-player-2-img`, tempColor);
    }
}

function evenScoreShowHidePlayers() {
    showElement(`.team-a-player-2-img`);
    showElement(`#team-a-player-2`);
    hideElement(`.team-a-player-1-img`);
    hideElement(`#team-a-player-1`);
    showElement(`.team-b-player-2-img`);
    showElement(`#team-b-player-2`);
    hideElement(`.team-b-player-1-img`);
    hideElement(`#team-b-player-1`);
}

function oddScoreShowHidePlayers() {
    hideElement(`.team-a-player-2-img`);
    hideElement(`#team-a-player-2`);
    showElement(`.team-a-player-1-img`);
    showElement(`#team-a-player-1`);
    hideElement(`.team-b-player-2-img`);
    hideElement(`#team-b-player-2`);
    showElement(`.team-b-player-1-img`);
    showElement(`#team-b-player-1`);
}


//Player js start===========================>
    // This function will listen to event when user click the player image and select a color from the dropdown list. It will set each player's color
$('.player-color').click(function() {
    $(this).parent().parent().prev().children('img').attr("src", `assets/images/${$(this).text().toLowerCase()}-player.png`);
    var classNames = $(this).parent().parent().parent().parent().attr("class").split(" ");
    if ($(`.${classNames[1]}`).attr("class").indexOf("left-court-left-player-container") != -1 ) {
        //Player A set src attribute
        playerA.src = `assets/images/${$(this).text().toLowerCase()}-player.png`;
    } else if ($(`.${classNames[1]}`).attr("class").indexOf("left-court-right-player-container") != -1 ) {
        //Player B set src attribute
        playerB.src = `assets/images/${$(this).text().toLowerCase()}-player.png`;
    } else if ($(`.${classNames[1]}`).attr("class").indexOf("right-court-left-player-container") != -1 ) {
        //Player C set src attribute
        playerC.src = `assets/images/${$(this).text().toLowerCase()}-player.png`;
    } else if ($(`.${classNames[1]}`).attr("class").indexOf("right-court-right-player-container") != -1 ) {
        //Player D set src attribute
        playerD.src = `assets/images/${$(this).text().toLowerCase()}-player.png`;
    }
});

// This will show/hide players depending on matchtype etc.
function showHidePlayers(courtType, option, matchType) {
    if(option == 'hide') {
        hideElement(`.team-a-player-1-img`);
        hideElement(`#team-a-player-1`);
        hideElement(`.team-b-player-1-img`);
        hideElement(`#team-b-player-1`);
        if (courtType == "mini") {
            hideElement(`.mini-left-court-left-player-container`);
            hideElement(`#mini-team-a-player-1`);
            hideElement(`.mini-right-court-left-player-container`);
            hideElement(`#mini-team-b-player-1`);
        }
    } else {
        showElement(`.team-a-player-1-img`);
        showElement(`#team-a-player-1`);
        showElement(`.team-b-player-1-img`);
        showElement(`#team-b-player-1`);
        if (courtType == "mini") {
            showElement(`.mini-left-court-left-player-container`);
            showElement(`#mini-team-a-player-1`);
            showElement(`.mini-right-court-left-player-container`);
            showElement(`#mini-team-b-player-1`);
        }
    }
    gameMatchType = matchType;
}

function showAllPlayers() {
    showElement(`.team-a-player-1-img`);
    showElement(`#team-a-player-1`);
    showElement(`.team-b-player-1-img`);
    showElement(`#team-b-player-1`);
    showElement(`.team-a-player-2-img`);
    showElement(`#team-a-player-2`);
    showElement(`.team-b-player-2-img`);
    showElement(`#team-b-player-2`);
}

// Create Player object 
function player (name, src, isVisible) {
    this.name = name;
    this.src = src;
    this.isVisible = isVisible;
}

$('#mini-team-a-player-1').on("change", function() { 
    playerA.name = getElementValue(`#mini-team-a-player-1`);
    setElementValue(`#team-a-player-1`, playerA.name);
});

$('#mini-team-a-player-2').on("change", function() { 
    playerB.name = getElementValue(`#mini-team-a-player-2`);
    setElementValue(`#team-a-player-2`, playerB.name);
});

$('#mini-team-b-player-1').on("change", function() { 
    playerC.name = getElementValue(`#mini-team-b-player-1`);
    setElementValue(`#team-b-player-1`, playerC.name);
});

$('#mini-team-b-player-2').on("change", function() { 
    playerD.name = getElementValue(`#mini-team-b-player-2`);
    setElementValue(`#team-b-player-2`, playerD.name);
});

function switchCourt(courtServe) {
    if (gameSet % 2 == 0) {
        setElementValue(`#team-a-player-1`, playerC.name);
        setElementSrc(`.team-a-player-1-img`, playerC.src);
        setElementValue(`#team-a-player-2`, playerD.name);
        setElementSrc(`.team-a-player-2-img`, playerD.src);
        setElementValue(`#team-b-player-1`, playerA.name);
        setElementSrc(`.team-b-player-1-img`, playerA.src);
        setElementValue(`#team-b-player-2`, playerB.name);
        setElementSrc(`.team-b-player-2-img`, playerB.src);
        if (gameMatchType == 1) {
            switchPlayerSide("a");
            switchPlayerSide("b");
        }
        showHideShuttle("main", courtServe, "right");
    } else {
        if (gameSet == 3 && blnMidBreak == true) {
            setElementValue(`#team-a-player-1`, playerC.name);
            setElementSrc(`.team-a-player-1-img`, playerC.src);
            setElementValue(`#team-a-player-2`, playerD.name);
            setElementSrc(`.team-a-player-2-img`, playerD.src);
            setElementValue(`#team-b-player-1`, playerB.name);
            setElementSrc(`.team-b-player-1-img`, playerB.src);
            setElementValue(`#team-b-player-2`, playerA.name);
            setElementSrc(`.team-b-player-2-img`, playerA.src);
            if (teamAScore == 11 && teamBScore < 11) {
                showHideShuttle("main", "right", "left");
            } else if (teamBScore == 11 && teamAScore < 11) {
                showHideShuttle("main", "left", "left");
            }
            var tempScore = teamAScore;
            teamAScore = teamBScore;
            teamBScore = tempScore;
        } else {
            setElementValue(`#team-a-player-1`, playerA.name);
            setElementSrc(`.team-a-player-1-img`, playerA.src);
            setElementValue(`#team-a-player-2`, playerB.name);
            setElementSrc(`.team-a-player-2-img`, playerB.src);
            setElementValue(`#team-b-player-1`, playerC.name);
            setElementSrc(`.team-b-player-1-img`, playerC.src);
            setElementValue(`#team-b-player-2`, playerD.name);
            setElementSrc(`.team-b-player-2-img`, playerD.src);
            if (gameMatchType == 1) {
                switchPlayerSide("a");
                switchPlayerSide("b");
            }
            showHideShuttle("main", courtServe, "right");
        }
    }
}

function switchPlayerSide(side) {
    let tempPlayer = new player();
    tempPlayer.name = getElementValue(`#team-${side}-player-1`);
    tempPlayer.src = getElementSrc(`.team-${side}-player-1-img`);

    setElementValue(`#team-${side}-player-1`, getElementValue(`#team-${side}-player-2`));
    setElementSrc(`.team-${side}-player-1-img`, getElementSrc(`.team-${side}-player-2-img`));
    setElementValue(`#team-${side}-player-2`, tempPlayer.name);
    setElementSrc(`.team-${side}-player-2-img`, tempPlayer.src);

    hideElement(`.team-${side}-player-1-img`);
    hideElement(`#team-${side}-player-1`);
    showElement(`.team-${side}-player-2-img`);
    showElement(`#team-${side}-player-2`);
}

//Court js Start=======================>
    $("document").ready(function() {
    // ----- Call Initialize function Mini-Court Area on Game Settings Modal and Menu settings -----
    initializeGameCourt();
});

var intMidIntervalBreak = 0;
var intFullIntervalBreak = 0;
var blnVoiceOver = true;
var gameMatchType = 1; //variable to hold the Match Type 1-Singles Match/2-Dubles Match
var teamAScore = 0;
var teamBScore = 0;
var teamAGameWin = 0;
var teamBGameWin = 0;
var serviceOver = "";
var startGame = false;
var gameSet = 1;
var voiceTimer = 3;
var blnMidBreak = false;

// This will show the game match settings
function startNewMatch() {
    if (window.matchMedia("(orientation: portrait)").matches) {
        modalShowHide(`#change-orientation`, "show");
    } else {
        modalShowHide('#game-settings', 'show');
        setPropertyValue(`#single-match`, `checked`, true);
        setPropertyValue(`#left-service`, `checked`, true);
        setPropertyValue(`#mid-interval-break`, `checked`, true);
        setPropertyValue(`#mid-interval-break`, `checked`, true);
        setPropertyValue(`#full-interval-break`, `checked`, true);
        setPropertyValue(`#voice-over`, "checked", true);
        setElementValue(`#mid-interval-seconds`, "60");
        setElementValue(`#full-interval-seconds`, "120");
    }
}

$(window).on("orientationchange",function() {
    //if (window.matchMedia("(orientation: landscape)").matches) {
    if(window.orientation == 0) {
        modalShowHide(`#change-orientation`, "show");
    } else {
        modalShowHide(`#change-orientation`, "hide");
    }
});
    
function initializeGameCourt() {
    hideElement(`#end-match`);
    addClass(`#team-a-yellow-card`, "not-active");
    addClass(`#team-a-red-card`, "not-active");
    addClass(`#team-a-black-card`, "not-active");
    addClass(`#team-b-yellow-card`, "not-active");
    addClass(`#team-b-red-card`, "not-active");
    addClass(`#team-b-black-card`, "not-active");
    addStyle(`#team-a-yellow-card`, "opacity", "0.5");
    addStyle(`#team-a-red-card`, "opacity", "0.5");
    addStyle(`#team-a-black-card`, "opacity", "0.5");
    addStyle(`#team-b-yellow-card`, "opacity", "0.5");
    addStyle(`#team-b-red-card`, "opacity", "0.5");
    addStyle(`#team-b-black-card`, "opacity", "0.5");
    addStyle(`#team-a-yellow-card`, "text-shadow", "gray 1px 1px 1px");
    addStyle(`#team-a-red-card`, "text-shadow", "gray 1px 1px 1px");
    addStyle(`#team-a-black-card`, "text-shadow", "gray 1px 1px 1px");
    addStyle(`#team-b-yellow-card`, "text-shadow", "gray 1px 1px 1px");
    addStyle(`#team-b-red-card`, "text-shadow", "gray 1px 1px 1px");
    addStyle(`#team-b-black-card`, "text-shadow", "gray 1px 1px 1px");

    // -- Set Tally score to zero
    setElementInnerHTML(`#team-a-set-one`, 0);
    setElementInnerHTML(`#team-b-set-one`, 0);
    setElementInnerHTML(`#team-a-set-two`, 0);
    setElementInnerHTML(`#team-b-set-two`, 0);
    setElementInnerHTML(`#team-a-set-three`, 0);
    setElementInnerHTML(`#team-b-set-three`, 0);


    // --- Hide shuttles ---
    showHideShuttle("main", "left", "right");
    showHideShuttle("mini", "left", "right");
    
    // --- Hide Players ---
    showHidePlayers("main", "hide", "1");
    showHidePlayers("mini", "hide", "1");

    // Main Court Area
    disableElement(`#team-a-player-1`);
    disableElement(`#team-a-player-2`);
    disableElement(`#team-b-player-1`);
    disableElement(`#team-b-player-2`);

    // Set Player Name Value to default
    //setElementValue(`#mini-team-a-player-1`, "ENTER PLAYER NAME HERE")
    //setElementValue(`#mini-team-a-player-2`, "ENTER PLAYER NAME HERE")
    //setElementValue(`#mini-team-b-player-1`, "ENTER PLAYER NAME HERE")
    //setElementValue(`#mini-team-b-player-2`, "ENTER PLAYER NAME HERE")

    setElementValue(`#team-a-player-1`, "PLAYER NAME");
    setElementValue(`#team-a-player-2`, "PLAYER NAME");
    setElementValue(`#team-b-player-1`, "PLAYER NAME");
    setElementValue(`#team-b-player-2`, "PLAYER NAME");

    setElementInnerHTML(`#team-a-names`, 'PLAYER NAME' + "<br>" + 'PLAYER NAME');
    setElementInnerHTML(`#team-b-names`, 'PLAYER NAME' + "<br>" + 'PLAYER NAME');

    playerA = new player("PLAYER NAME", "assets/images/blue-player.png", false); //Variable to hold the Player A object
    playerB = new player("PLAYER NAME", "assets/images/yellow-player.png", true); //Variable to hold the Player B object
    playerC = new player("PLAYER NAME", "assets/images/green-player.png", false); //Variable to hold the Player C object
    playerD = new player("PLAYER NAME", "assets/images/red-player.png", true); //Variable to hold the Player D object
}

// This will start the Badminton Scorer and initialize everything
function gameStart() {
    // Initialize variables on new game match
    teamAScore = 0;
    teamBScore = 0;
    teamAGameWin = 0;
    teamBGameWin = 0;
    serviceOver = "";
    gameSet = 1;
    blnMidBreak = false;

    showElement(`#end-match`);
    hideElement(`#new-match`);
    disableElement(`#team-a-player-1`);
    disableElement(`#team-a-player-2`);
    disableElement(`#team-b-player-1`);
    disableElement(`#team-b-player-2`);
    removeClass(`#team-a-yellow-card`, "not-active");
    removeClass(`#team-b-yellow-card`, "not-active");
    removeStyle(`#team-a-yellow-card`);
    removeStyle(`#team-b-yellow-card`);

    // set Player's Image color and Name
    setElementSrc(`.team-a-player-1-img`, playerA.src);
    setElementSrc(`.team-a-player-2-img`, playerB.src);
    setElementSrc(`.team-b-player-1-img`, playerC.src);
    setElementSrc(`.team-b-player-2-img`, playerD.src);
    setElementValue(`#team-a-player-1`, playerA.name);
    setElementValue(`#team-a-player-2`, playerB.name);
    setElementValue(`#team-b-player-1`, playerC.name);
    setElementValue(`#team-b-player-2`, playerD.name);
    if (gameMatchType == 1) {
        setElementSrc(`.team-a-player-1-img`, playerB.src);
        setElementSrc(`.team-b-player-1-img`, playerD.src);
        setElementValue(`#team-a-player-1`, playerB.name);
        setElementValue(`#team-b-player-1`, playerD.name);
        playerA.name = playerB.name;
        playerA.src = playerB.src;
        playerC.name = playerD.name;
        playerC.src = playerD.src;
    }

    // set ScoreBoards Player Names and all Score default to zero
    setElementInnerHTML(`#team-a-names`, gameMatchType == 2 ? playerA.name + "<br>" + playerB.name: playerB.name);
    setElementInnerHTML(`#team-b-names`, gameMatchType == 2 ? playerC.name + "<br>" + playerD.name: playerD.name);
    setElementInnerHTML(`.tally-team-a-players`, gameMatchType == 2 ? playerA.name + "/" + playerB.name : playerB.name);
    setElementInnerHTML(`.tally-team-b-players`, gameMatchType == 2 ? playerC.name + "/" + playerD.name : playerD.name);

    setElementInnerHTML(`#team-a-set-one`, "0");
    setElementInnerHTML(`#team-b-set-one`, "0");
    setElementInnerHTML(`#team-a-set-two`, "0");
    setElementInnerHTML(`#team-b-set-two`, "0");
    setElementInnerHTML(`#team-a-set-three`, "0");
    setElementInnerHTML(`#team-b-set-three`, "0");

    $(`#mid-interval-break`).is(':checked') ? intMidIntervalBreak = getElementValue(`#mid-interval-seconds`) : intMidIntervalBreak = 0;
    $(`#full-interval-break`).is(':checked') ? intFullIntervalBreak = getElementValue(`#full-interval-seconds`) : intFullIntervalBreak = 0;

    // Set Warnings Cards
    addStyle(`#team-a-red-card`, "text-shadow", "gray 1px 1px 1px");
    addStyle(`#team-a-black-card`, "text-shadow", "gray 1px 1px 1px");
    addStyle(`#team-b-red-card`, "text-shadow", "gray 1px 1px 1px");
    addStyle(`#team-b-black-card`, "text-shadow", "gray 1px 1px 1px");
    addClass(`#team-a-red-card`, "not-active");
    addClass(`#team-a-black-card`, "not-active");
    addClass(`#team-b-red-card`, "not-active");
    addClass(`#team-b-black-card`, "not-active");

    startGame = true;

    if (teamAScore == 0 && teamBScore == 0) {
        $(`#left-service`).is(':checked') ? speakThisMsg(playerB.name + " to " + playerD.name): speakThisMsg(playerD.name + " to " + playerB.name);
        speakThisMsg("love all");
        speakThisMsg("play");
    }
}

// Start Source https://stackoverflow.com/questions/469357/html-text-input-allow-only-numeric-input
function validateKeyPress(interval, evt) {
  var theEvent = evt || window.event;
  var key = "";
  // Handle paste on input text of numeric type
  if (theEvent.type === 'paste') {
      key = event.clipboardData.getData('text/plain');
  } else {
  // Handle key press
      key = theEvent.keyCode || theEvent.which;
      key = String.fromCharCode(key);
  }
  var regex = /[0-9]|\./;
  if( !regex.test(key) ) {
    theEvent.returnValue = false;
    if(theEvent.preventDefault) theEvent.preventDefault();
  }
}
// End Source https://stackoverflow.com/questions/469357/html-text-input-allow-only-numeric-input

// This will caught keyup function on the mid-interval timer input text 
$("#mid-interval-seconds").keyup(function() {
  if($('#mid-interval-seconds').val()>60 ){
      $("#mid-interval-seconds" ).val("");
  }
});

// This will caught keyup function on the full-interval timer input text 
$("#full-interval-seconds").keyup(function() {
    if($('#full-interval-seconds').val()>120 ){
        $("#full-interval-seconds" ).val("");
    }
});

// This will caught hover event on left scorer button 
$(`.left-scorer`).hover(function() {
    startGame ? setBackgroundColor(`.left-scorer`, "green"): setBackgroundColor(`.left-scorer`, "gray");
});

// This will caught mouseleave event on left scorer button 
$(`.left-scorer`).mouseleave(function() {
    setBackgroundColor(`.left-scorer`, "gray");
});

// This will caught hover event on right scorer button 
$(`.right-scorer`).hover(function() {
    startGame ? setBackgroundColor(`.right-scorer`, "green"): setBackgroundColor(`.right-scorer`, "gray");
});

// This will caught mpuseleave event on right scorer button 
$(`.right-scorer`).mouseleave(function() {
    setBackgroundColor(`.right-scorer`, "gray");
});

// This will enable/disable mid-interval input text when the mid-interval checkbox changed
function enableMidIntervalInput() {
    if($(`#mid-interval-break`).is(':checked')) {
        enableElement(`#mid-interval-seconds`);
    } else {
        disableElement(`#mid-interval-seconds`);
        intMidIntervalBreak = 0;
    }
}

// This will enable/disable full-interval input text when the mid-interval checkbox changed
function enableFullIntervalInput() {
    if($(`#full-interval-break`).is(':checked')) {
        enableElement(`#full-interval-seconds`);
    } else {
        disableElement(`#full-interval-seconds`);
        intFullIntervalBreak = 0;
    }
}

// This will hold the variable whether voice-over will be enabled/disable when checkbox changed
function enableVoiceOver() {
    if($(`#voice-over`).is(':checked')) {
        setPropertyValue(`#in-game-voice-over`, "checked", true);
        blnVoiceOver = true;
    } else {
        setPropertyValue(`#in-game-voice-over`, "checked", false);
        blnVoiceOver = false;
    }
}

// This will hold the variable whether voice-over will be enabled/disable when checkbox changed on in-game settings
function enableInGameVoiceOver() {
    $(`#in-game-voice-over`).is(':checked') ? blnVoiceOver = true : blnVoiceOver = false;
}

// This will end the current match with input notes and will show the Match Result Tally Score Board after. 
function endMatch() {
    //Show modal for End-Game Reason
    modalShowHide('#game-interval', 'hide');
    modalShowHide('#end-game-set', 'show');
}

function showGameResult() {
    startGame = false;
    hideElement(`#end-match`);
    showElement(`#new-match`);
    //Show modal Match Result Tally Score Board
    appendChild(`#umpire-notes`, `<p style="margin-left:1vw;">${getElementValue("#additional-notes")}</p>`);
    modalShowHide('#end-game-set', 'hide');
    modalShowHide('#end-game-result', 'show');
}

// This will issue a yellow card to the team base on passed parameter
function issueYellowCard(side) {
    var issuedCard = 0; 
    (side == 'left') ? issuedLeftCard++ : issuedRightCard++ ;
    (side == 'left') ? issuedCard = issuedLeftCard : issuedCard = issuedRightCard;
    issueProperYellowCard(side, issuedCard);
}

function issueProperYellowCard(side, noOfIssue) {
    if(noOfIssue == 1) {
        setBackgroundColor(`#team-${(side == 'left'? 'a':'b')}-first-yellow-warning`, "yellow");
        setElementInnerHTML(`#team-${(side == 'left'? 'a':'b')}-first-warning`, noOfIssue);
        // For Match Results
        setBackgroundColor(`#team-${(side == 'left'? 'a':'b')}-yellow-one`, "yellow");
    } else if(noOfIssue == 2) {
        setBackgroundColor(`#team-${(side == 'left'? 'a':'b')}-second-yellow-warning`, "yellow");
        setElementInnerHTML(`#team-${(side == 'left'? 'a':'b')}-second-warning`, noOfIssue);
        addClass(`#team-${(side == 'left'? 'a':'b')}-yellow-card`, "not-active");
        addStyle(`#team-${(side == 'left'? 'a':'b')}-yellow-card`, "opacity", "0.5");
        addStyle(`#team-${(side == 'left'? 'a':'b')}-yellow-card`, "text-shadow", "gray 1px 1px 1px");
        removeClass(`#team-${(side == 'left'? 'a':'b')}-red-card`, "not-active");
        removeStyle(`#team-${(side == 'left'? 'a':'b')}-red-card`);
        // For Match Results
        setBackgroundColor(`#team-${(side == 'left'? 'a':'b')}-yellow-two`, "yellow");
    }
}

// This will issue a Red card to the team base on passed parameter
function issueRedCard(side) {
    setBackgroundColor(`#team-${(side == 'left'? 'a':'b')}-red-warning`, "red");
    addClass(`#team-${(side == 'left'? 'a':'b')}-red-card`, "not-active");
    addStyle(`#team-${(side == 'left'? 'a':'b')}-red-card`, "opacity", "0.5");
    addStyle(`#team-${(side == 'left'? 'a':'b')}-red-card`, "text-shadow", "gray 1px 1px 1px");
    removeClass(`#team-${(side == 'left'? 'a':'b')}-black-card`, "not-active");
    removeStyle(`#team-${(side == 'left'? 'a':'b')}-black-card`);
    // For Match Results
    setBackgroundColor(`#team-${(side == 'left'? 'a':'b')}-red`, "red");
}

// This will issue a Red card to the team base on passed parameter
function issueBlackCard(side) {
    setBackgroundColor(`#team-${(side == 'left'? 'a':'b')}-black-warning`, "black");
    addClass(`#team-${(side == 'left'? 'a':'b')}-black-card`, "not-active");
    addStyle(`#team-${(side == 'left'? 'a':'b')}-black-card`, "opacity", "0.5");
    addStyle(`#team-${(side == 'left'? 'a':'b')}-black-card`, "text-shadow", "gray 1px 1px 1px");
    // For Match Results
    setBackgroundColor(`#team-${(side == 'left'? 'a':'b')}-black`, "black");
}

// This function will add style to an element
function addStyle(selector, style, value){
    $(`${selector}`).css(style, value);
}

// This function will disable element passed 
function disableElement(selector) {
    $(`${selector}`).attr("disabled", true);
}

// This function will enable element passed 
function enableElement(selector) {
    $(`${selector}`).attr("disabled", false);
}

// This function will hide element passed 
function hideElement(selector) {
    $(`${selector}`).hide();
}

// This function will show element passed 
function showElement(selector) {
    $(`${selector}`).show();
}

// This function will set element background color 
function setBackgroundColor(selector, color) {
    $(`${selector}`).css("background-color", color);
}

// This function will get element's value 
function getElementValue(selector) {
    return $(`${selector}`).val();
}

// This function will set element's value 
function setElementValue(selector, value) {
    $(`${selector}`).val(value);
}

// This function will set image element's src attribute 
function setElementSrc(selector, source) {
    $(`${selector}`).attr("src", source);
}

// This function will set image element's src attribute 
function getElementSrc(selector, source) {
    return $(`${selector}`).attr("src");
}

// This function will set element's innerText
function setElementInnerHTML(selector, innerHTML) {
    $(`${selector}`).html(innerHTML);
}

// This function will append child to a parent
function appendChild(selector, htmlText) {
    $(`${selector}`).append(htmlText);
}

// This function will add class to an element
function addClass(selector, className) {
    $(`${selector}`).addClass(className);
}

// This function will remove class to an element
function removeClass(selector, className) {
    $(`${selector}`).removeClass(className);
}

// This function will remove css style to an element
function removeStyle(selector) {
    $(`${selector}`).removeAttr("style");
}

// This function will show/hide modal
function modalShowHide(selector, option) {
    $(`${selector}`).modal(option);
}

// This function will set element property value
function setPropertyValue(selector, property, value) {
    $(`${selector}`).prop(property, value);
}

// Voice-Over synthesizer for text input
function speakThisMsg(message) {
    if(blnVoiceOver) {
        let thisMsg = new SpeechSynthesisUtterance();
        thisMsg = message;
        window.speechSynthesis.speak(new SpeechSynthesisUtterance(thisMsg));
    }
}

function sendMail() {
    if (getElementValue(`#sender-name`) != "" && getElementValue(`#sender-email`) != "" && getElementValue(`#subject`) != "" && getElementValue(`#message`) != "") {
        setElementInnerHTML(`#modal-title-contact`, "Thank you for contacting us!");
        setElementValue(`#sender-name`, "");
        setElementValue(`#sender-email`, "");
        setElementValue(`#subject`, "");
        setElementValue(`#message`, "");
    }
}

function validateEmail() {
    const mailformat = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;
    const textinput = getElementValue(`#sender-email`);
    if(textinput.match(mailformat)) {
        setElementInnerHTML(`#sender-email`, "Your email");
        addStyle(`#sender-email`, "color", "white");
        return true;
    } else {
        if (($("#contact-me").data('bs.modal') || {})._isShown) {
            setElementInnerHTML(`#sender-email`, "You have entered an invalid email address");
            $(`#sender-email`).focus();
            addStyle(`#sender-email`, "color", "red");
            return false;
        }
    }
}


//Shuttle js =========================>

    function showHideShuttle(courtType, courtSide, serviceSide) {
    // Hide all the shuttlecock image first
    hideElement(`#left-court-left-side-shuttle`);
    hideElement(`#left-court-right-side-shuttle`);
    hideElement(`#right-court-left-side-shuttle`);
    hideElement(`#right-court-right-side-shuttle`);
    hideElement(`#mini-left-court-left-side-shuttle`);
    hideElement(`#mini-left-court-right-side-shuttle`);
    hideElement(`#mini-right-court-left-side-shuttle`);
    hideElement(`#mini-right-court-right-side-shuttle`);

    // then show only the shuttle on the side of the player who will serve
    if(courtType == "mini") {
        showElement(`#${courtType}-${courtSide}-court-${serviceSide}-side-shuttle`);
    }
    showElement(`#${courtSide}-court-${serviceSide}-side-shuttle`);
}

