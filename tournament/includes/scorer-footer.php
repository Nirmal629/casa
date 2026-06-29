<!-------Botom Footer----->
<section class="bottom_footer">
    <div class="cust_container">
        <p class="text text-center py-2">Copyright © <?php echo date("Y"); ?> Casa Tournaments - All Rights Reserved.</p>
    </div>
</section>


<!---jquery-CDN------>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!----bootstrap script----->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.3/dist/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>


<!----main-js----->
<script src="assets/js/score.js?v=1.1"></script>
<script>
  $(function () {
    if (!window.initialMatchData) {
      return;
    }

    const teamA = window.initialMatchData.teamA || [];
    const teamB = window.initialMatchData.teamB || [];
    const teamAPlayers = normalizeDoublesPlayers(window.initialMatchData.teamAPlayers, teamA);
    const teamBPlayers = normalizeDoublesPlayers(window.initialMatchData.teamBPlayers, teamB);
    assignPlayerImages(teamAPlayers, teamBPlayers);
    $('#team-a-player-1, #mini-team-a-player-1').val(teamAPlayers[0].name);
    $('#team-a-player-2, #mini-team-a-player-2').val(teamAPlayers[1].name);
    $('#team-b-player-1, #mini-team-b-player-1').val(teamBPlayers[0].name);
    $('#team-b-player-2, #mini-team-b-player-2').val(teamBPlayers[1].name);
    $('#t1_p1').val(teamAPlayers[0].name);
    $('#t1_p2').val(teamAPlayers[1].name);
    $('#t2_p1').val(teamBPlayers[0].name);
    $('#t2_p2').val(teamBPlayers[1].name);
    $('#team-a-names').html(teamAPlayers[0].name + '<br>' + teamAPlayers[1].name);
    $('#team-b-names').html(teamBPlayers[0].name + '<br>' + teamBPlayers[1].name);

    if (typeof player !== 'undefined') {
      playerA = new player(teamAPlayers[0].name, 'assets/images/blue-player.png', false);
      playerB = new player(teamAPlayers[1].name, 'assets/images/yellow-player.png', true);
      playerC = new player(teamBPlayers[0].name, 'assets/images/green-player.png', false);
      playerD = new player(teamBPlayers[1].name, 'assets/images/red-player.png', true);
    }

    window.liveMatchState = {
      matchId: parseInt(window.initialMatchData.matchId || 0, 10),
      setNo: 1,
      scoreA: parseInt(window.initialMatchData.initialScoreA || 0, 10),
      scoreB: parseInt(window.initialMatchData.initialScoreB || 0, 10),
      setsA: 0,
      setsB: 0,
      setScores: [
        {a: parseInt(window.initialMatchData.initialScoreA || 0, 10), b: parseInt(window.initialMatchData.initialScoreB || 0, 10)},
        {a: 0, b: 0},
        {a: 0, b: 0}
      ],
      completedSets: [],
      players: {
        A: teamAPlayers,
        B: teamBPlayers
      },
      positions: {
        A: {even: 0, odd: 1},
        B: {even: 0, odd: 1}
      },
      servingTeam: 'A',
      serverIndex: 0,
      voiceEnabled: false,
      courtSwapped: false,
      undoStack: [],
      sidesSwitched: false,
      thirdGameIntervalSwitched: false,
      isStarted: (window.initialMatchData.matchStatus || '') === 'RUNNING',
      isSaving: false,
      isCompleted: false
    };

    renderPlayableScore();
    renderDoublesCourt();
    renderMatchResult();
    setScoreButtonsEnabled(window.liveMatchState.isStarted);
    setTimeout(renderDoublesCourt, 100);
    setTimeout(renderDoublesCourt, 500);
    updateVoiceButton();
    updateCourtSwapButton();
    $('#voice-toggle, #config-voice-toggle').on('click', toggleVoice);
    $('#config-court-swap').on('click', function () {
      window.liveMatchState.courtSwapped = !window.liveMatchState.courtSwapped;
      renderDoublesCourt();
      updateCourtSwapButton();
      speakScore('Court swapped. ' + buildServeAnnouncement());
    });
    $('#undo-point, #set-board-undo-point').on('click', undoLastPoint);
    $('#matchConfig').modal('show');
  });

  function normalizeDoublesPlayers(playerRows, names) {
    const rows = Array.isArray(playerRows) ? playerRows : [];
    return [0, 1].map(function (index) {
      const row = rows[index] || {};
      const rowName = String(row.name || '').trim();
      const fallbackName = String(names[index] || '').trim();
      return {
        id: parseInt(row.id || 0, 10),
        name: rowName || fallbackName || 'PLAYER NAME'
      };
    });
  }

  function assignPlayerImages(teamAPlayers, teamBPlayers) {
    teamAPlayers[0].src = 'assets/images/Player/man1.png';
    teamAPlayers[1].src = 'assets/images/Player/man2.png';
    teamBPlayers[0].src = 'assets/images/Player/man4.png';
    teamBPlayers[1].src = 'assets/images/Player/man3.png';
  }

  function updateVoiceButton() {
    const enabled = !window.liveMatchState || window.liveMatchState.voiceEnabled;
    $('#voice-toggle')
      .toggleClass('btn-info', enabled)
      .toggleClass('btn-outline-info', !enabled)
      .attr('aria-pressed', enabled ? 'true' : 'false')
      .html('<i class="fa-solid ' + (enabled ? 'fa-volume-high' : 'fa-volume-xmark') + '"></i>');
    $('#config-voice-toggle')
      .toggleClass('btn-info', enabled)
      .toggleClass('btn-outline-info', !enabled)
      .html('<i class="fa-solid ' + (enabled ? 'fa-volume-high' : 'fa-volume-xmark') + ' mr-1"></i> VOICE ' + (enabled ? 'ON' : 'OFF'));
  }

  function toggleVoice() {
    window.liveMatchState.voiceEnabled = !window.liveMatchState.voiceEnabled;
    updateVoiceButton();
    if (window.liveMatchState.voiceEnabled) {
      speakScore('Voice on. ' + buildServeAnnouncement());
    } else if ('speechSynthesis' in window) {
      window.speechSynthesis.cancel();
    }
  }

  function updateCourtSwapButton() {
    const swapped = !!(window.liveMatchState && window.liveMatchState.courtSwapped);
    $('#config-court-swap')
      .toggleClass('btn-info', swapped)
      .toggleClass('btn-outline-info', !swapped)
      .html('<i class="fa-solid fa-right-left mr-1"></i> ' + (swapped ? 'COURT SWAPPED' : 'SWAP COURT'));
  }

  function speakScore(text) {
    if (!window.liveMatchState || !window.liveMatchState.voiceEnabled || !('speechSynthesis' in window)) {
      return;
    }

    window.speechSynthesis.cancel();
    const message = new SpeechSynthesisUtterance(text);
    message.rate = 0.95;
    message.pitch = 1;
    window.speechSynthesis.speak(message);
  }

  function setScoreButtonsEnabled(enabled) {
    $('.left-scorer, .right-scorer').prop('disabled', !enabled);
  }

  function saveStartMatch() {
    const formData = new FormData();
    formData.append('action', 'start_match');
    formData.append('match_id', window.liveMatchState.matchId);

    return fetch('api-match-score.php', {
      method: 'POST',
      body: formData
    }).then(function (response) {
      return response.json();
    }).then(function (data) {
      if (!data.success) {
        throw new Error(data.message || 'Start match failed.');
      }
      return data;
    });
  }

  function startPlayableMatch() {
    const state = window.liveMatchState;
    if (!state || !state.matchId) {
      alert('No match selected. Open this page from a Play button.');
      return;
    }

    if (state.isSaving) {
      return;
    }

    if (state.isStarted) {
      $('#matchConfig').modal('hide');
      setScoreButtonsEnabled(true);
      return;
    }

    state.isSaving = true;
    saveStartMatch().then(function () {
      state.isStarted = true;
      state.isCompleted = false;
      startGame = true;
      setScoreButtonsEnabled(true);
      renderDoublesCourt();
      $('#matchConfig').modal('hide');
      speakScore('Match started. ' + buildServeAnnouncement());
    }).catch(function (error) {
      alert(error.message);
    }).finally(function () {
      state.isSaving = false;
    });
  }

  function renderPlayableScore() {
    if (!window.liveMatchState) {
      return;
    }

    const state = window.liveMatchState;
    $('#team-a-match-score').text(state.setsA);
    $('#team-b-match-score').text(state.setsB);
    $('#team-a-set-one').text(state.setScores[0].a);
    $('#team-b-set-one').text(state.setScores[0].b);
    $('#team-a-set-two').text(state.setScores[1].a);
    $('#team-b-set-two').text(state.setScores[1].b);
    $('#team-a-set-three').text(state.setScores[2].a);
    $('#team-b-set-three').text(state.setScores[2].b);
  }

  function renderDoublesCourt() {
    const state = window.liveMatchState;
    if (!state) {
      return;
    }

    const leftTeam = teamOnCourtSide('left');
    const rightTeam = teamOnCourtSide('right');
    renderPhysicalCourtSide('left', leftTeam);
    renderPhysicalCourtSide('right', rightTeam);
    $('#left-court-team-name').text(getTeamName(leftTeam));
    $('#right-court-team-name').text(getTeamName(rightTeam));
    $('#team-a-names').html(state.players.A[0].name + '<br>' + state.players.A[1].name);
    $('#team-b-names').html(state.players.B[0].name + '<br>' + state.players.B[1].name);
    $('#team-a-player-1, #team-a-player-2, #team-b-player-1, #team-b-player-2').show();
    $('.team-a-player-1-img, .team-a-player-2-img, .team-b-player-1-img, .team-b-player-2-img').show();

    $('.left-court-shuttles, .right-court-shuttles').hide();
    const servingCourt = serviceCourtForScore(state.servingTeam, state.servingTeam === 'A' ? state.scoreA : state.scoreB);
    const servingSide = courtSideForTeam(state.servingTeam);
    if (servingSide === 'left') {
      $(servingCourt === 'even' ? '#left-court-right-side-shuttle' : '#left-court-left-side-shuttle').show();
    } else {
      $(servingCourt === 'even' ? '#right-court-right-side-shuttle' : '#right-court-left-side-shuttle').show();
    }
  }

  function teamOnCourtSide(side) {
    if (side === 'left') {
      return window.liveMatchState.courtSwapped ? 'B' : 'A';
    }
    return window.liveMatchState.courtSwapped ? 'A' : 'B';
  }

  function courtSideForTeam(teamKey) {
    if (teamKey === 'A') {
      return window.liveMatchState.courtSwapped ? 'right' : 'left';
    }
    return window.liveMatchState.courtSwapped ? 'left' : 'right';
  }

  function renderPhysicalCourtSide(side, teamKey) {
    const state = window.liveMatchState;
    const evenPlayer = state.players[teamKey][state.positions[teamKey].even];
    const oddPlayer = state.players[teamKey][state.positions[teamKey].odd];
    if (side === 'left') {
      $('#team-a-player-1').val(oddPlayer.name);
      $('#team-a-player-2').val(evenPlayer.name);
      $('.team-a-player-1-img').attr('src', oddPlayer.src);
      $('.team-a-player-2-img').attr('src', evenPlayer.src);
    } else {
      $('#team-b-player-1').val(oddPlayer.name);
      $('#team-b-player-2').val(evenPlayer.name);
      $('.team-b-player-1-img').attr('src', oddPlayer.src);
      $('.team-b-player-2-img').attr('src', evenPlayer.src);
    }
  }

  function serviceCourtForScore(teamKey, score) {
    return score % 2 === 0 ? 'even' : 'odd';
  }

  function serviceCourtLabel(court) {
    return court === 'even' ? 'right' : 'left';
  }

  function playerIndexInServiceCourt(teamKey, score) {
    const court = serviceCourtForScore(teamKey, score);
    return window.liveMatchState.positions[teamKey][court];
  }

  function currentServer() {
    const state = window.liveMatchState;
    return state.players[state.servingTeam][state.serverIndex];
  }

  function buildServeAnnouncement() {
    const state = window.liveMatchState;
    const servingScore = getTeamScore(state.servingTeam);
    const servingCourt = serviceCourtForScore(state.servingTeam, servingScore);
    return currentServer().name + ' serving from ' + serviceCourtLabel(servingCourt) + '.';
  }

  function buildScoreAnnouncement() {
    const state = window.liveMatchState;
    return getTeamName('A') + ' ' + state.scoreA + ', ' + getTeamName('B') + ' ' + state.scoreB + '. ' + buildServeAnnouncement();
  }

  function swapServingPair(teamKey) {
    const positions = window.liveMatchState.positions[teamKey];
    const oldEven = positions.even;
    positions.even = positions.odd;
    positions.odd = oldEven;
  }

  function getTeamScore(teamKey) {
    return teamKey === 'A' ? window.liveMatchState.scoreA : window.liveMatchState.scoreB;
  }

  function getTeamId(teamKey) {
    return teamKey === 'A'
      ? parseInt(window.initialMatchData.team1Id || 0, 10)
      : parseInt(window.initialMatchData.team2Id || 0, 10);
  }

  function getTeamName(teamKey) {
    return teamKey === 'A'
      ? (window.initialMatchData.team1Name || 'Team 1')
      : (window.initialMatchData.team2Name || 'Team 2');
  }

  function maybeSwitchSidesAfterPoint() {
    const state = window.liveMatchState;
    if (state.setNo === 3 && !state.thirdGameIntervalSwitched && (state.scoreA === 11 || state.scoreB === 11)) {
      state.thirdGameIntervalSwitched = true;
      state.sidesSwitched = !state.sidesSwitched;
      return 'Players switched sides at 11 points in the third game.';
    }
    return '';
  }

  function renderMatchResult() {
    const state = window.liveMatchState;
    if (!state) {
      return;
    }

    const winnerTeam = state.setsA > state.setsB ? 'A' : (state.setsB > state.setsA ? 'B' : '');
    $('#match-result-winner').html('<i class="fa-solid fa-trophy mr-2 text-warning"></i> WINNER: ' + (winnerTeam ? getTeamName(winnerTeam) : '-'));
    $('#match-result-team-a-name').text(getTeamName('A'));
    $('#match-result-team-b-name').text(getTeamName('B'));
    $('#match-result-team-a-sets').text(state.setsA);
    $('#match-result-team-b-sets').text(state.setsB);

    const rows = state.setScores.map(function (score, index) {
      const played = index < state.completedSets.length || index === state.setNo - 1;
      if (!played && score.a === 0 && score.b === 0) {
        return '';
      }
      const aWon = score.a > score.b;
      const bWon = score.b > score.a;
      return '<div class="d-flex justify-content-between align-items-center p-2 rounded-3 bg-dark border border-secondary" style="font-size: 0.85rem;">'
        + '<span class="opacity-50 fw-bold">SET ' + (index + 1) + '</span>'
        + '<div class="fw-bold"><span class="' + (aWon ? 'text-info' : '') + '">' + score.a + '</span>'
        + ' <span class="mx-2 opacity-25">|</span> '
        + '<span class="' + (bWon ? 'text-info' : '') + '">' + score.b + '</span></div>'
        + '<i class="fa-solid ' + (played && (aWon || bWon) ? 'fa-check-circle text-success' : 'fa-hourglass-half text-secondary') + '"></i>'
        + '</div>';
    }).join('');
    $('#match-result-set-breakdown').html(rows || '<div class="text-center small opacity-50">Match not started</div>');
  }

  function clonePlayableState() {
    const state = window.liveMatchState;
    return {
      scoreA: state.scoreA,
      scoreB: state.scoreB,
      setsA: state.setsA,
      setsB: state.setsB,
      setNo: state.setNo,
      setScores: state.setScores.map(function (score) { return {a: score.a, b: score.b}; }),
      completedSets: state.completedSets.map(function (score) { return score ? {a: score.a, b: score.b} : score; }),
      positions: {
        A: {even: state.positions.A.even, odd: state.positions.A.odd},
        B: {even: state.positions.B.even, odd: state.positions.B.odd}
      },
      servingTeam: state.servingTeam,
      serverIndex: state.serverIndex,
      courtSwapped: state.courtSwapped,
      sidesSwitched: state.sidesSwitched,
      thirdGameIntervalSwitched: state.thirdGameIntervalSwitched,
      isCompleted: state.isCompleted,
      isStarted: state.isStarted
    };
  }

  function restorePlayableState(snapshot) {
    const state = window.liveMatchState;
    state.scoreA = snapshot.scoreA;
    state.scoreB = snapshot.scoreB;
    state.setsA = snapshot.setsA;
    state.setsB = snapshot.setsB;
    state.setNo = snapshot.setNo;
    state.setScores = snapshot.setScores.map(function (score) { return {a: score.a, b: score.b}; });
    state.completedSets = snapshot.completedSets.map(function (score) { return score ? {a: score.a, b: score.b} : score; });
    state.positions = {
      A: {even: snapshot.positions.A.even, odd: snapshot.positions.A.odd},
      B: {even: snapshot.positions.B.even, odd: snapshot.positions.B.odd}
    };
    state.servingTeam = snapshot.servingTeam;
    state.serverIndex = snapshot.serverIndex;
    state.courtSwapped = snapshot.courtSwapped;
    state.sidesSwitched = snapshot.sidesSwitched;
    state.thirdGameIntervalSwitched = snapshot.thirdGameIntervalSwitched;
    state.isCompleted = snapshot.isCompleted;
    state.isStarted = snapshot.isStarted;
  }

  function saveUndoPoint(snapshot) {
    const formData = new FormData();
    formData.append('action', 'undo_point');
    formData.append('match_id', window.liveMatchState.matchId);
    formData.append('team_1_score', snapshot.scoreA);
    formData.append('team_2_score', snapshot.scoreB);
    formData.append('team_1_sets', snapshot.setsA);
    formData.append('team_2_sets', snapshot.setsB);

    return fetch('api-match-score.php', {
      method: 'POST',
      body: formData
    }).then(function (response) {
      return response.json();
    }).then(function (data) {
      if (!data.success) {
        throw new Error(data.message || 'Undo failed.');
      }
      return data;
    });
  }

  function undoLastPoint() {
    const state = window.liveMatchState;
    if (!state || state.isSaving) {
      return;
    }

    const snapshot = state.undoStack.pop();
    if (!snapshot) {
      alert('No point to undo.');
      return;
    }

    state.isSaving = true;
    saveUndoPoint(snapshot).then(function () {
      restorePlayableState(snapshot);
      $('#matchResult').modal('hide');
      startGame = snapshot.isStarted;
      renderPlayableScore();
      renderDoublesCourt();
      renderMatchResult();
      speakScore('Undo. ' + buildScoreAnnouncement());
    }).catch(function (error) {
      state.undoStack.push(snapshot);
      alert(error.message);
    }).finally(function () {
      state.isSaving = false;
    });
  }

  function isBadmintonSetWon(scoreA, scoreB) {
    if ((scoreA === 21 && scoreB <= 19) || (scoreB === 21 && scoreA <= 19)) {
      return true;
    }

    if ((scoreA > 21 || scoreB > 21) && Math.abs(scoreA - scoreB) >= 2) {
      return true;
    }

    return scoreA === 30 || scoreB === 30;
  }

  function savePlayablePoint(pointData, completed) {
    const state = window.liveMatchState;
    const formData = new FormData();
    formData.append('action', 'record_point');
    formData.append('match_id', state.matchId);
    formData.append('score_side', pointData.winnerTeam === 'A' ? 'left' : 'right');
    formData.append('set_no', state.setNo);
    formData.append('team_1_score', state.scoreA);
    formData.append('team_2_score', state.scoreB);
    formData.append('team_1_sets', state.setsA);
    formData.append('team_2_sets', state.setsB);
    formData.append('serving_team_id', pointData.servingTeamId);
    formData.append('server_user_id', pointData.serverUserId);
    formData.append('server_name', pointData.serverName);
    formData.append('court_side', pointData.serviceCourt === 'even' ? 'RIGHT' : 'LEFT');
    formData.append('notes', pointData.notes);
    formData.append('completed', completed ? '1' : '0');

    return fetch('api-match-score.php', {
      method: 'POST',
      body: formData
    }).then(function (response) {
      return response.json();
    }).then(function (data) {
      if (!data.success) {
        throw new Error(data.message || 'Score save failed.');
      }
      return data;
    });
  }

  function incrementScore(scoreSide) {
    const state = window.liveMatchState;
    if (!state || !state.matchId) {
      alert('No match selected. Open this page from a Play button.');
      return;
    }

    if (state.isSaving || state.isCompleted) {
      return;
    }

    if (!state.isStarted) {
      alert('Click Start Match first.');
      return;
    }

    state.isSaving = true;
    const previousState = clonePlayableState();
    const winnerTeam = teamOnCourtSide(scoreSide);
    const servingTeamBeforePoint = state.servingTeam;
    const serverBeforePoint = state.players[servingTeamBeforePoint][state.serverIndex];
    const serviceCourtBeforePoint = serviceCourtForScore(servingTeamBeforePoint, getTeamScore(servingTeamBeforePoint));
    const notes = [];

    if (winnerTeam === 'A') {
      state.scoreA++;
    } else {
      state.scoreB++;
    }
    state.setScores[state.setNo - 1] = {a: state.scoreA, b: state.scoreB};

    if (winnerTeam === servingTeamBeforePoint) {
      swapServingPair(winnerTeam);
      notes.push('Serving side won rally and continued serving.');
    } else {
      state.servingTeam = winnerTeam;
      state.serverIndex = playerIndexInServiceCourt(winnerTeam, getTeamScore(winnerTeam));
      notes.push('Service changed to ' + getTeamName(winnerTeam) + '.');
    }

    const sideSwitchNote = maybeSwitchSidesAfterPoint();
    if (sideSwitchNote) {
      notes.push(sideSwitchNote);
    }

    let completed = false;
    let setFinished = false;
    if (isBadmintonSetWon(state.scoreA, state.scoreB)) {
      setFinished = true;
      if (state.scoreA > state.scoreB) {
        state.setsA++;
      } else {
        state.setsB++;
      }
      state.completedSets[state.setNo - 1] = {a: state.scoreA, b: state.scoreB};
      notes.push('Set ' + state.setNo + ' completed.');
      notes.push('Players switch sides after the game.');

      completed = state.setsA === 2 || state.setsB === 2;
    }

    renderPlayableScore();
    renderDoublesCourt();
    renderMatchResult();

    const pointData = {
      winnerTeam: winnerTeam,
      servingTeamId: getTeamId(servingTeamBeforePoint),
      serverUserId: serverBeforePoint.id || 0,
      serverName: serverBeforePoint.name,
      serviceCourt: serviceCourtBeforePoint,
      notes: notes.join(' ')
    };

    savePlayablePoint(pointData, completed).then(function () {
      state.undoStack = [previousState];
      if (completed) {
        state.isCompleted = true;
        startGame = false;
        renderMatchResult();
        speakScore('Match completed. Winner ' + getTeamName(state.setsA > state.setsB ? 'A' : 'B') + '.');
        $('#matchResult').modal('show');
        return;
      }

      if (setFinished) {
        state.sidesSwitched = !state.sidesSwitched;
        state.setNo++;
        state.scoreA = 0;
        state.scoreB = 0;
        state.thirdGameIntervalSwitched = false;
        state.servingTeam = winnerTeam;
        state.serverIndex = playerIndexInServiceCourt(winnerTeam, 0);
        renderPlayableScore();
        renderDoublesCourt();
        speakScore('Set ' + (state.setNo - 1) + ' completed. Next set. ' + buildServeAnnouncement());
      } else {
        speakScore(buildScoreAnnouncement());
      }
    }).catch(function (error) {
      state.scoreA = previousState.scoreA;
      state.scoreB = previousState.scoreB;
      state.setsA = previousState.setsA;
      state.setsB = previousState.setsB;
      state.setNo = previousState.setNo;
      state.setScores = previousState.setScores;
      state.completedSets = previousState.completedSets;
      state.positions = previousState.positions;
      state.servingTeam = previousState.servingTeam;
      state.serverIndex = previousState.serverIndex;
      state.courtSwapped = previousState.courtSwapped;
      state.sidesSwitched = previousState.sidesSwitched;
      state.thirdGameIntervalSwitched = previousState.thirdGameIntervalSwitched;
      renderPlayableScore();
      renderDoublesCourt();
      renderMatchResult();
      alert(error.message);
    }).finally(function () {
      state.isSaving = false;
    });
  }
</script>

</body>

</html>
