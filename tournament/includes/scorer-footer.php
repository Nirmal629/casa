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
  // Read-only guard: only the tournament organizer (canManage) may trigger scoring AJAX.
  // For players/viewers this is false, so scoring actions below no-op (no request is sent).
  function scorerCanManage() {
    return !!(window.initialMatchData && window.initialMatchData.canManage);
  }

  function getConfiguredMatchRules(initialMatchData) {
    const stage = String(initialMatchData.stage || 'GROUP');
    const defaultsByStage = {
      GROUP: { sets: 1, deuce: false, deuceLimit: 'NA' },
      QUARTER_FINAL: { sets: 3, deuce: true, deuceLimit: '25' },
      SEMI_FINAL: { sets: 3, deuce: true, deuceLimit: '30' },
      FINAL: { sets: 3, deuce: true, deuceLimit: '30' },
      BRONZE_FINAL: { sets: 3, deuce: true, deuceLimit: '30' }
    };
    const defaults = defaultsByStage[stage] || { sets: parseInt(initialMatchData.defaultSetLimit || 3, 10) === 1 ? 1 : 3, deuce: true, deuceLimit: '30' };

    try {
      const config = initialMatchData.matchRules || {};
      const storedRules = config[stage];
      const rules = storedRules && typeof storedRules === 'object' ? storedRules : {};
      const storedSets = typeof storedRules === 'number' ? storedRules : rules.sets;
      const deuce = typeof rules.deuce === 'boolean' ? rules.deuce : defaults.deuce;
      const deuceLimit = ['25', '30'].includes(String(rules.deuceLimit)) ? String(rules.deuceLimit) : defaults.deuceLimit;

      return {
        sets: parseInt(storedSets || defaults.sets, 10) === 1 ? 1 : 3,
        deuce: deuce,
        deuceLimit: deuce ? parseInt(deuceLimit, 10) : 0
      };
    } catch (error) {
      return { sets: defaults.sets, deuce: defaults.deuce, deuceLimit: defaults.deuce ? parseInt(defaults.deuceLimit, 10) : 0 };
    }
  }

  function setsNeededToWin(setLimit) {
    return setLimit === 1 ? 1 : 2;
  }

  function applySetLimitVisibility(setLimit) {
    $('[data-set-row]').each(function () {
      const rowSet = parseInt($(this).data('set-row') || 0, 10);
      $(this).toggle(rowSet > 0 && rowSet <= setLimit);
    });
  }

  $(function () {
    if (!window.initialMatchData) {
      return;
    }

    const configuredMatchRules = getConfiguredMatchRules(window.initialMatchData);
    const configuredSetLimit = configuredMatchRules.sets;
    const restoredSetScores = (window.initialMatchData.initialSetScores || [{a: window.initialMatchData.initialScoreA || 0, b: window.initialMatchData.initialScoreB || 0}, {a: 0, b: 0}, {a: 0, b: 0}]).map(function (score) {
      return {a: parseInt(score.a || 0, 10), b: parseInt(score.b || 0, 10)};
    });
    const restoredSetNo = parseInt(window.initialMatchData.initialSetNo || 1, 10);
    const restoredCurrentScore = restoredSetScores[restoredSetNo - 1] || {a: 0, b: 0};
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
      setNo: restoredSetNo,
      scoreA: restoredCurrentScore.a,
      scoreB: restoredCurrentScore.b,
      setsA: parseInt(window.initialMatchData.initialSetsA || 0, 10),
      setsB: parseInt(window.initialMatchData.initialSetsB || 0, 10),
      setScores: restoredSetScores,
      completedSets: [],
      manualWinners: {},
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
      setLimit: configuredSetLimit,
      deuceEnabled: configuredMatchRules.deuce,
      deuceLimit: configuredMatchRules.deuceLimit,
      isStarted: (window.initialMatchData.matchStatus || '') === 'RUNNING',
      isSaving: false,
      isCompleted: (window.initialMatchData.matchStatus || '') === 'COMPLETED'
    };

    applySetLimitVisibility(configuredSetLimit);
    syncMatchConfigFromState();
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
      syncMatchConfigFromState();
      updateCourtSwapButton();
      speakScore('Court swapped. ' + buildServeAnnouncement());
    });
    $('.match-config-swap-team-a').on('click', function () {
      swapPlayersOnDisplayedCourtSide('left');
    });
    $('.match-config-swap-team-b').on('click', function () {
      swapPlayersOnDisplayedCourtSide('right');
    });
    $('#setScoreBoard').on('shown.bs.modal', function () {
      renderSetScoreBoard(true);
    });
    $('#set-board-edit-a').on('click', function () {
      enableSetBoardEdit('A');
    });
    $('#set-board-edit-b').on('click', function () {
      enableSetBoardEdit('B');
    });
    $('#set-board-save-a, #set-board-save-b, #set-board-save-all').on('click', saveSetBoardManualScore);
    $('#set-board-plus-a').on('click', function () {
      addSetBoardPoint('A');
    });
    $('#set-board-plus-b').on('click', function () {
      addSetBoardPoint('B');
    });
    $('#set-board-score-a, #set-board-score-b').on('input', updateSetBoardLiveScore);
    $('#undo-point, #set-board-undo-point').on('click', undoLastPoint);
    $('#matchResult').on('shown.bs.modal', renderMatchResult);
    $('#match-result-set-breakdown').on('click', '[data-setup-action]', handleMatchSetupAction);
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
      .attr('title', 'Voice ' + (enabled ? 'on' : 'off'))
      .html('<i class="fa-solid ' + (enabled ? 'fa-volume-high' : 'fa-volume-xmark') + ($('#config-voice-toggle').hasClass('match-config-icon-btn') ? '' : ' mr-1') + '"></i>' + ($('#config-voice-toggle').hasClass('match-config-icon-btn') ? '' : ' VOICE ' + (enabled ? 'ON' : 'OFF')));
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
      .attr('title', swapped ? 'Court swapped' : 'Swap court')
      .html('<i class="fa-solid fa-right-left' + ($('#config-court-swap').hasClass('match-config-icon-btn') ? '' : ' mr-1') + '"></i>' + ($('#config-court-swap').hasClass('match-config-icon-btn') ? '' : ' ' + (swapped ? 'COURT SWAPPED' : 'SWAP COURT')));
  }

  function syncMatchConfigFromState() {
    const state = window.liveMatchState;
    if (!state) { return; }
    const leftTeam = teamOnCourtSide('left');
    const rightTeam = teamOnCourtSide('right');
    $('#t1_name').val(getTeamName(leftTeam));
    $('#t2_name').val(getTeamName(rightTeam));
    $('#t1_p1').val(state.players[leftTeam][state.positions[leftTeam].odd].name);
    $('#t1_p2').val(state.players[leftTeam][state.positions[leftTeam].even].name);
    $('#t2_p1').val(state.players[rightTeam][state.positions[rightTeam].odd].name);
    $('#t2_p2').val(state.players[rightTeam][state.positions[rightTeam].even].name);
  }

  function swapPlayersOnDisplayedCourtSide(side) {
    const teamKey = teamOnCourtSide(side);
    swapServingPair(teamKey);
    renderDoublesCourt();
    syncMatchConfigFromState();
  }

  function applyMatchConfigToState() {
    const state = window.liveMatchState;
    if (!state) {
      return;
    }
    const leftTeam = teamOnCourtSide('left');
    const rightTeam = teamOnCourtSide('right');
    const leftName = String($('#t1_name').val() || '').trim() || 'Team 1';
    const rightName = String($('#t2_name').val() || '').trim() || 'Team 2';
    const leftPlayer1 = String($('#t1_p1').val() || '').trim() || 'PLAYER NAME';
    const leftPlayer2 = String($('#t1_p2').val() || '').trim() || 'PLAYER NAME';
    const rightPlayer1 = String($('#t2_p1').val() || '').trim() || 'PLAYER NAME';
    const rightPlayer2 = String($('#t2_p2').val() || '').trim() || 'PLAYER NAME';
    const team1Name = leftTeam === 'A' ? leftName : rightName;
    const team2Name = leftTeam === 'B' ? leftName : rightName;
    const teamAPlayer1 = leftTeam === 'A' ? leftPlayer1 : rightPlayer1;
    const teamAPlayer2 = leftTeam === 'A' ? leftPlayer2 : rightPlayer2;
    const teamBPlayer1 = leftTeam === 'B' ? leftPlayer1 : rightPlayer1;
    const teamBPlayer2 = leftTeam === 'B' ? leftPlayer2 : rightPlayer2;

    window.initialMatchData.team1Name = team1Name;
    window.initialMatchData.team2Name = team2Name;
    window.initialMatchData.teamA = [teamAPlayer1, teamAPlayer2];
    window.initialMatchData.teamB = [teamBPlayer1, teamBPlayer2];
    state.players.A[0].name = teamAPlayer1;
    state.players.A[1].name = teamAPlayer2;
    state.players.B[0].name = teamBPlayer1;
    state.players.B[1].name = teamBPlayer2;

    $('#team-a-player-1, #mini-team-a-player-1').val(teamAPlayer1);
    $('#team-a-player-2, #mini-team-a-player-2').val(teamAPlayer2);
    $('#team-b-player-1, #mini-team-b-player-1').val(teamBPlayer1);
    $('#team-b-player-2, #mini-team-b-player-2').val(teamBPlayer2);
    $('#team-a-names').html(teamAPlayer1 + '<br>' + teamAPlayer2);
    $('#team-b-names').html(teamBPlayer1 + '<br>' + teamBPlayer2);
    $('#left-court-team-name').text(team1Name);
    $('#right-court-team-name').text(team2Name);
    renderPlayableScore();
    renderDoublesCourt();
    renderMatchResult();
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
    if (!scorerCanManage()) { return; }
    const state = window.liveMatchState;
    if (!state || !state.matchId) {
      alert('No match selected. Open this page from a Play button.');
      return;
    }

    if (state.isSaving) {
      return;
    }

    applyMatchConfigToState();

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

  function openSetupChildModal(selector) {
    $('#matchResult').one('hidden.bs.modal', function () {
      $(selector).modal('show');
    }).modal('hide');
  }

  function saveMatchPause(paused) {
    const formData = new FormData();
    formData.append('action', paused ? 'pause_match' : 'start_match');
    formData.append('match_id', window.liveMatchState.matchId);
    return fetch('api-match-score.php', {method: 'POST', body: formData})
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (!data.success) { throw new Error(data.message || 'Match status could not be saved.'); }
        return data;
      });
  }

  function resetSet(setIndex) {
    const state = window.liveMatchState;
    if (!state || state.isSaving) { return; }
    if (!window.confirm('Reset Set ' + (setIndex + 1) + '? This clears its score.')) { return; }
    state.isSaving = true;
    const formData = new FormData();
    formData.append('action', 'reset_set');
    formData.append('match_id', state.matchId);
    formData.append('set_no', setIndex + 1);
    fetch('api-match-score.php', {method: 'POST', body: formData})
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (!data.success) { throw new Error(data.message || 'Set reset failed.'); }
        state.setScores[setIndex] = {a: 0, b: 0};
        delete state.manualWinners[setIndex];
        if (setIndex === state.setNo - 1) {
          state.scoreA = 0;
          state.scoreB = 0;
          state.undoStack = [];
        }
        state.completedSets = state.completedSets.filter(function (_, index) { return index !== setIndex; });
        state.setsA = state.completedSets.filter(function (score) { return score.a > score.b; }).length;
        state.setsB = state.completedSets.filter(function (score) { return score.b > score.a; }).length;
        renderPlayableScore();
        renderDoublesCourt();
        renderMatchResult();
      }).catch(function (error) { alert(error.message); }).finally(function () { state.isSaving = false; });
  }

  function handleMatchSetupAction(event) {
    const button = $(event.currentTarget);
    const action = button.data('setup-action');
    const setIndex = parseInt(button.data('set-index'), 10);
    const state = window.liveMatchState;
    if (!state) { return; }
    if (action === 'position') {
      if (state.isStarted) {
        alert('Player positions are locked after the match starts. Pause or reset the match before changing positions.');
        return;
      }
      $('#matchResult').one('hidden.bs.modal', function () {
        syncMatchConfigFromState();
        $('#matchConfig').modal('show');
      }).modal('hide');
    } else if (action === 'pause') {
      if (!scorerCanManage() || state.isSaving) { return; }
      state.isSaving = true;
      const willPause = state.isStarted;
      saveMatchPause(willPause).then(function () {
        state.isStarted = !willPause;
        setScoreButtonsEnabled(state.isStarted);
        renderMatchResult();
      }).catch(function (error) { alert(error.message); }).finally(function () { state.isSaving = false; });
    } else if (action === 'edit') {
      state.editingSetNo = setIndex + 1;
      state.scoreA = state.setScores[setIndex].a;
      state.scoreB = state.setScores[setIndex].b;
      openSetupChildModal('#setScoreBoard');
    } else if (action === 'reset') {
      resetSet(setIndex);
    } else if (action === 'log') {
      openSetupChildModal('#matchLog');
    }
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
    const winner = getCompletedMatchWinner();
    $('#team-a-winner-crown').toggle(winner === 'A');
    $('#team-b-winner-crown').toggle(winner === 'B');
    applySetLimitVisibility(state.setLimit || 3);
    renderSetScoreBoard();
  }

  function renderSetScoreBoard(resetEditMode) {
    const state = window.liveMatchState;
    if (!state) {
      return;
    }

    if (resetEditMode) {
      setSetBoardReadOnly(true);
    }

    if (!setBoardIsEditing('A')) {
      const selectedScore = state.setScores[(state.editingSetNo || state.setNo) - 1] || {a: state.scoreA, b: state.scoreB};
      $('#set-board-score-a').val(selectedScore.a);
    }
    if (!setBoardIsEditing('B')) {
      const selectedScore = state.setScores[(state.editingSetNo || state.setNo) - 1] || {a: state.scoreA, b: state.scoreB};
      $('#set-board-score-b').val(selectedScore.b);
    }
    updateSetBoardLiveScore();
    $('#set-board-team-a-name').text(getTeamName('A'));
    $('#set-board-team-b-name').text(getTeamName('B'));
    $('#set-board-score-a-label').text(getTeamName('A'));
    $('#set-board-score-b-label').text(getTeamName('B'));
    $('#set-board-team-a-players').html(state.players.A[0].name + '<br>' + state.players.A[1].name);
    $('#set-board-team-b-players').html(state.players.B[0].name + '<br>' + state.players.B[1].name);
    const displayedScore = state.setScores[(state.editingSetNo || state.setNo) - 1] || {a: 0, b: 0};
    $('#set-board-winner').val(displayedScore.a > displayedScore.b ? 'A' : (displayedScore.b > displayedScore.a ? 'B' : ''));
    updateSetBoardEditingUi();
  }

  function enableSetBoardEdit(teamKey) {
    const selector = teamKey === 'A' ? '#set-board-score-a' : '#set-board-score-b';
    $(selector).prop('readonly', false).focus().select();
    updateSetBoardEditingUi();
  }

  function setSetBoardReadOnly(readOnly) {
    $('#set-board-score-a, #set-board-score-b').prop('readonly', readOnly);
    updateSetBoardEditingUi();
  }

  function setBoardIsEditing(teamKey) {
    if (teamKey === 'A') {
      return !$('#set-board-score-a').prop('readonly');
    }
    if (teamKey === 'B') {
      return !$('#set-board-score-b').prop('readonly');
    }
    return setBoardIsEditing('A') || setBoardIsEditing('B');
  }

  function updateSetBoardLiveScore() {
    $('#set-board-live-score').text(readSetBoardScore('#set-board-score-a') + ' - ' + readSetBoardScore('#set-board-score-b'));
  }

  function updateSetBoardEditingUi() {
    ['A', 'B'].forEach(function (teamKey) {
      const suffix = teamKey.toLowerCase();
      const editing = setBoardIsEditing(teamKey);
      $('#set-board-plus-' + suffix).prop('disabled', !editing).toggleClass('is-disabled', !editing);
      $('#set-board-save-' + suffix).prop('disabled', !editing).toggleClass('is-disabled', !editing);
      $('#set-board-edit-' + suffix).toggleClass('is-active', editing);
    });
    $('#set-board-save-all').prop('disabled', !setBoardIsEditing()).toggleClass('is-disabled', !setBoardIsEditing());
  }

  function readSetBoardScore(selector) {
    const value = parseInt($(selector).val(), 10);
    if (Number.isNaN(value) || value < 0) {
      return 0;
    }
    return Math.min(value, 99);
  }

  function addSetBoardPoint(teamKey) {
    if (setBoardIsEditing(teamKey)) {
      const selector = teamKey === 'A' ? '#set-board-score-a' : '#set-board-score-b';
      $(selector).val(Math.min(readSetBoardScore(selector) + 1, 99));
      updateSetBoardLiveScore();
      return;
    }
  }

  function saveSetBoardManualScore() {
    if (!scorerCanManage()) { return; }
    const state = window.liveMatchState;
    if (!state || state.isSaving) {
      return;
    }

    const scoreA = readSetBoardScore('#set-board-score-a');
    const scoreB = readSetBoardScore('#set-board-score-b');
    const setIndex = (state.editingSetNo || state.setNo) - 1;
    const naturalWinner = isBadmintonSetWon(scoreA, scoreB) ? (scoreA > scoreB ? 'A' : 'B') : '';
    const winner = naturalWinner || String($('#set-board-winner').val() || '');
    if (!winner) {
      alert('Choose the winner of this set before saving the manual score.');
      return;
    }
    state.completedSets[setIndex] = {a: scoreA, b: scoreB};
    state.manualWinners[setIndex] = winner;
    state.setsA = state.completedSets.filter(function (score, index) { return score && (state.manualWinners[index] || (score.a > score.b ? 'A' : 'B')) === 'A'; }).length;
    state.setsB = state.completedSets.filter(function (score, index) { return score && (state.manualWinners[index] || (score.b > score.a ? 'B' : 'A')) === 'B'; }).length;
    const matchCompleted = state.setsA === setsNeededToWin(state.setLimit || 3) || state.setsB === setsNeededToWin(state.setLimit || 3);
    state.isSaving = true;
    saveManualSetScore(scoreA, scoreB, winner, state.setsA, state.setsB, matchCompleted).then(function () {
      state.setScores[setIndex] = {a: scoreA, b: scoreB};
      if (setIndex === state.setNo - 1) {
        state.scoreA = scoreA;
        state.scoreB = scoreB;
      }
      state.undoStack = [];
      state.isStarted = true;
      state.isCompleted = matchCompleted;
      startGame = true;
      setSetBoardReadOnly(true);
      renderPlayableScore();
      renderDoublesCourt();
      renderMatchResult();
      setScoreButtonsEnabled(!matchCompleted);
      state.editingSetNo = null;
      speakScore('Manual score saved. ' + buildScoreAnnouncement());
      if (matchCompleted) {
        $('#setScoreBoard').one('hidden.bs.modal', function () { $('#matchResult').modal('show'); }).modal('hide');
      }
    }).catch(function (error) {
      alert(error.message);
    }).finally(function () {
      state.isSaving = false;
    });
  }

  function saveManualSetScore(scoreA, scoreB, winner, setsA, setsB, completed) {
    const state = window.liveMatchState;
    const formData = new FormData();
    formData.append('action', 'set_score_board');
    formData.append('match_id', state.matchId);
    formData.append('team_1_score', scoreA);
    formData.append('team_2_score', scoreB);
    formData.append('set_no', state.editingSetNo || state.setNo);
    formData.append('winner', winner);
    formData.append('team_1_sets', setsA);
    formData.append('team_2_sets', setsB);
    formData.append('completed', completed ? '1' : '0');

    return fetch('api-match-score.php', {
      method: 'POST',
      body: formData
    }).then(function (response) {
      return response.json();
    }).then(function (data) {
      if (!data.success) {
        throw new Error(data.message || 'Manual score save failed.');
      }
      return data;
    });
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
    if ((state.setLimit || 3) === 3 && state.setNo === 3 && !state.thirdGameIntervalSwitched && (state.scoreA === 11 || state.scoreB === 11)) {
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

    const winnerTeam = getCompletedMatchWinner() || (state.setsA > state.setsB ? 'A' : (state.setsB > state.setsA ? 'B' : ''));
    $('#match-result-winner').html('<i class="fa-solid fa-trophy mr-2 text-warning"></i> WINNER: ' + (winnerTeam ? getTeamName(winnerTeam) : '-'));
    $('#match-result-team-a-name').text(getTeamName('A'));
    $('#match-result-team-b-name').text(getTeamName('B'));
    $('#match-result-team-a-sets').text(state.setsA);
    $('#match-result-team-b-sets').text(state.setsB);
    $('#match-result-team-a-winner-name').text(getTeamName('A'));
    $('#match-result-team-b-winner-name').text(getTeamName('B'));
    $('#match-result-team-a-crown').toggle(winnerTeam === 'A');
    $('#match-result-team-b-crown').toggle(winnerTeam === 'B');
    $('#match-result-set-indicator').text('Set - ' + state.setNo + '/' + (state.setLimit || 3));
    $('#match-setup-deuce').text(state.deuceEnabled ? 'Deuce On' : 'Deuce Off');

    const rows = state.setScores.slice(0, state.setLimit || 3).map(function (score, index) {
      const played = index < state.completedSets.length || index === state.setNo - 1;
      const selectedWinner = state.manualWinners[index] || '';
      const aWon = selectedWinner ? selectedWinner === 'A' : score.a > score.b;
      const bWon = selectedWinner ? selectedWinner === 'B' : score.b > score.a;
      const completed = index < state.completedSets.length;
      const current = index === state.setNo - 1 && !completed;
      const manageable = scorerCanManage();
      const disabled = manageable ? '' : ' disabled';
      const pauseLabel = state.isStarted ? 'Pause' : (played ? 'Resume' : 'Start');
      const actions = '<button type="button" data-setup-action="position" data-set-index="' + index + '" title="Position"' + disabled + '><i class="fa-solid fa-right-left"></i></button>'
        + '<button type="button" data-setup-action="pause" data-set-index="' + index + '" title="' + pauseLabel + '"' + disabled + '><i class="fa-solid ' + (state.isStarted ? 'fa-pause' : 'fa-play') + '"></i> ' + pauseLabel + '</button>'
        + '<button type="button" data-setup-action="edit" data-set-index="' + index + '" title="Edit set score"' + disabled + '><i class="fa-solid fa-pen"></i></button>'
        + '<button type="button" data-setup-action="reset" data-set-index="' + index + '" title="Reset set"' + disabled + '><i class="fa-solid fa-rotate-left"></i></button>'
        + '<button type="button" data-setup-action="log" data-set-index="' + index + '" title="Match log"><i class="fa-solid fa-list-ul"></i></button>'
        + '<button type="button" disabled title="Coming soon"><i class="fa-solid fa-check"></i></button>';
      return '<div class="match-result-set-row">'
        + '<span class="match-result-set-label">SET ' + (index + 1) + '</span>'
        + '<div class="match-result-set-score"><span class="' + (aWon ? 'match-result-set-winner' : '') + '">' + score.a + '</span>'
        + '<span class="match-result-divider">|</span>'
        + '<span class="' + (bWon ? 'match-result-set-winner' : '') + '">' + score.b + '</span></div>'
        + '<div class="match-result-set-actions">' + actions + '</div>'
        + '<i class="fa-solid match-result-set-icon ' + (played && (aWon || bWon) ? 'fa-check-circle' : 'fa-hourglass-half') + '"></i>'
        + '</div>';
    }).join('');
    $('#match-result-set-breakdown').html(rows || '<div class="match-result-empty">Match not started</div>');
  }

  function getCompletedMatchWinner() {
    const state = window.liveMatchState;
    if (!state || !state.isCompleted) { return ''; }
    const savedWinnerId = parseInt(window.initialMatchData.winnerTeamId || 0, 10);
    if (savedWinnerId === getTeamId('A')) { return 'A'; }
    if (savedWinnerId === getTeamId('B')) { return 'B'; }
    return state.setsA > state.setsB ? 'A' : (state.setsB > state.setsA ? 'B' : '');
  }

  function clonePlayableState() {
    const state = window.liveMatchState;
    return {
      scoreA: state.scoreA,
      scoreB: state.scoreB,
      setsA: state.setsA,
      setsB: state.setsB,
      setNo: state.setNo,
      setLimit: state.setLimit,
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
    state.setLimit = snapshot.setLimit || state.setLimit || 3;
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
    if (!scorerCanManage()) { return; }
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
    const state = window.liveMatchState;
    if (!state || !state.deuceEnabled) {
      return scoreA >= 21 || scoreB >= 21;
    }

    const deuceLimit = state.deuceLimit === 25 ? 25 : 30;
    if (scoreA >= deuceLimit || scoreB >= deuceLimit) {
      return true;
    }

    return (scoreA >= 21 || scoreB >= 21) && Math.abs(scoreA - scoreB) >= 2;
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
    if (!scorerCanManage()) { return; }
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

      completed = state.setsA === setsNeededToWin(state.setLimit || 3) || state.setsB === setsNeededToWin(state.setLimit || 3);
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
