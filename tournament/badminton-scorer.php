<!-----Header------>
<?php include "includes/scorer-header.php"; ?>

<!-----body-top---->
<section style="position: relative; display: flex; align-items: center; justify-content: center; padding: 0 20px; gap: 20px;">
    <a href="https://casainfotech.com/staging/tournament" class="scorerback_btn"><i class="fa-solid fa-arrow-left mr-1"></i>Back</a>
    <div class="container-fluid score-board-container">
        <div class="row container-fluid player-score-board-container">
            <div class="col container-fluid team-a-players">
                <p id="team-a-names" class="m-0 pt-1">PLAYER NAME<br>PLAYER NAME</p>
            </div>
            <!-- <div class="col container-fluid warning-cards">
                <div class="row team-a-warning" id="team-a-second-yellow-warning">
                    <p id="team-a-second-warning"></p>
                </div>
                <div class="row team-a-warning" id="team-a-black-warning"></div>
            </div>
            <div class="col container-fluid warning-cards">
                <div class="row team-a-warning" id="team-a-first-yellow-warning">
                    <p id="team-a-first-warning"></p>
                </div>
                <div class="row team-a-warning" id="team-a-red-warning"></div>
            </div> -->
            <div class="col container-fluid set-one">
                <div class="row team-a-set-one">
                    <p id="team-a-set-one">21</p>
                </div>
                <div class="row team-b-set-one">
                    <p id="team-b-set-one">21</p>
                </div>
            </div>
            <div class="col container-fluid set-two">
                <div class="row team-a-set-two">
                    <p id="team-a-set-two">21</p>
                </div>
                <div class="row team-b-set-two">
                    <p id="team-b-set-two">21</p>
                </div>
            </div>
            <div class="col container-fluid set-three">
                <div class="row team-a-set-three">
                    <p id="team-a-set-three">21</p>
                </div>
                <div class="row team-b-set-three">
                    <p id="team-b-set-three">21</p>
                </div>
            </div>
            <!-- <div class="col container-fluid warning-cards">
                <div class="row team-b-warning" id="team-b-first-yellow-warning">
                    <p id="team-b-first-warning"></p>
                </div>
                <div class="row team-b-warning" id="team-b-red-warning">
                    <p></p>
                </div>
            </div>
            <div class="col container-fluid warning-cards">
                <div class="row team-b-warning" id="team-b-second-yellow-warning">
                    <p id="team-b-second-warning"></p>
                </div>
                <div class="row team-b-warning" id="team-b-black-warning">
                    <p></p>
                </div>
            </div> -->
            <div class="col container-fluid team-b-players">
                <p id="team-b-names" class="m-0 pt-1">PLAYER NAME<br>PLAYER NAME</p>
            </div>
        </div>
    </div>
    <div class="container-fluid setup-menu-container">
        <nav class="navbar navbar-light navbar-expand-sm header-navbar">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link navbar-toggler-icon" href="#" id="navSetupMenuDropdownMenuLink" role="button"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></a>
                    <div class="dropdown-menu dropdown-menu-right" id="menu-dropdown-list" aria-labelledby="navSetupMenuDropdownMenuLink">
                        <a class="dropdown-item" data-toggle="modal" data-target="#matchConfig">Match Configuration</a>
                        <a class="dropdown-item" data-toggle="modal" data-target="#matchResult">Match Result</a>
                        <a class="dropdown-item" data-toggle="modal" data-target="#setScoreBoard">Set Score Board</a>
                    </div>
                </li>
            </ul>
        </nav>
    </div>
</section>

<!-----main-body----->
<section class="mainscorer_board">
    <div class="container-fluid master-container">
        <div class="row container-row">
            <div class="col scorer-col">
                <button class="score-style left-scorer" onclick="incrementScore('left')">+1</button>
            </div>
            <div class="row court-row">
                <div class="col top-sideline">
                    <span>Team 1 name</span>
                    <span>Undo</span>
                    <span>Team 2 name</span>
                </div>
                <div class="col left-court-area">
                    <div class="col left-court-long-service-line">
                    </div>
                    <div class="row left-court-left-service-area">
                        <img src="assets/images/Player/man1.png" alt="dropdown image"
                            class="img-responsive team-a-player-1-img">
                        <img id="left-court-left-side-shuttle" class="left-court-shuttles" alt="left-court-shuttles"
                            src="assets/images/left-shuttle.png">
                        <input type="text" class="form-control control-border team-player left-team-player"
                            name="team-a-player-1" data-form-field="team-a-player-1" id="team-a-player-1"
                            value="PLAYER NAME" />
                    </div>
                    <div class="row left-court-right-service-area">
                        <img src="assets/images/Player/man2.png" alt="dropdown image"
                            class="img-responsive team-a-player-2-img">
                        <img id="left-court-right-side-shuttle" class="left-court-shuttles"
                            alt="left-court-shuttles" src="assets/images/left-shuttle.png">
                        <input type="text" class="form-control control-border team-player left-team-player"
                            name="team-a-player-2" data-form-field="team-a-player-2" id="team-a-player-2"
                            value="PLAYER NAME" />
                    </div>
                </div>
                <div class="col left-net-area">
                </div>
                <div class="col right-net-area">
                </div>
                <div class="col right-court-area">
                    <div class="row right-court-right-service-area">
                        <img src="assets/images/Player/man3.png" alt="dropdown image"
                            class="img-responsive team-b-player-2-img">
                        <img id="right-court-right-side-shuttle" class="right-court-shuttles"
                            alt="right-court-shuttles" src="assets/images/right-shuttle.png">
                        <input type="text" class="form-control control-border team-player right-team-player"
                            name="team-b-player-2" data-form-field="team-b-player-2" id="team-b-player-2"
                            value="PLAYER NAME" />
                    </div>
                    <div class="row right-court-left-service-area">
                        <img src="assets/images/Player/man4.png" alt="dropdown image"
                            class="img-responsive team-b-player-1-img">
                        <img id="right-court-left-side-shuttle" class="right-court-shuttles"
                            alt="right-court-shuttles" src="assets/images/right-shuttle.png">
                        <input type="text" class="form-control control-border team-player right-team-player"
                            name="team-b-player-1" data-form-field="team-b-player-1" id="team-b-player-1"
                            value="PLAYER NAME" />
                    </div>
                    <div class="col right-court-long-service-line">
                    </div>
                </div>
                <div class="col bottom-sideline">
                    <span>Left</span>
                    <span>Refree</span>
                    <span>Right</span>
                </div>
            </div>
            <div class="col scorer-col">
                <button class="score-style right-scorer" onclick="incrementScore('right')">+1</button>
            </div>
        </div>
    </div>
</section>

<!-- **** Modal for Match Configuration **** -->
<div class="modal fade" id="matchConfig" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="matchConfigLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title text-info fw-bold text-uppercase small" id="matchConfigLabel"><i class="fa-solid fa-gears mr-2"></i>Match Configuration</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="court-box mx-auto p-3 shadow-lg" style="border-radius: 20px; border: 1px solid rgba(13, 202, 240, 0.2); color: white;">

                    <div class="p-3 rounded-3 mb-2" style="background: rgba(255,255,255,0.05);">
                        <input type="text" id="t1_name" class="form-control form-control-sm bg-dark text-white border-0 mb-2" placeholder="Team 1 Name">
                        <div class="row gap-2">
                            <div class="col-6"><input type="text" id="t1_p1" class="form-control form-control-sm bg-black text-white-50 border-0" placeholder="Player 1"></div>
                            <div class="col-6"><input type="text" id="t1_p2" class="form-control form-control-sm bg-black text-white-50 border-0" placeholder="Player 2"></div>
                        </div>
                    </div>

                    <div class="p-3 rounded-3 mb-3" style="background: rgba(255,255,255,0.05);">
                        <input type="text" id="t2_name" class="form-control form-control-sm bg-dark text-white border-0 mb-2" placeholder="Team 2 Name">
                        <div class="row g-2">
                            <div class="col-6"><input type="text" id="t2_p1" class="form-control form-control-sm bg-black text-white-50 border-0" placeholder="Player 1"></div>
                            <div class="col-6"><input type="text" id="t2_p2" class="form-control form-control-sm bg-black text-white-50 border-0" placeholder="Player 2"></div>
                        </div>
                    </div>

                    <div class="d-flex g-2 mb-4">
                        <select id="match_type" class="form-select form-select-sm bg-dark text-white border-secondary">
                            <option value="doubles">Doubles</option>
                            <option value="singles">Singles</option>
                        </select>
                        <select id="deuce_type" class="form-select form-select-sm bg-dark text-white border-secondary">
                            <option value="deuce">Deuce On</option>
                            <option value="no-deuce">No Deuce</option>
                        </select>
                    </div>

                    <button class="btn btn-info w-100 fw-bold py-2 mb-2 rounded-pill shadow" data-dismiss="modal">
                        START MATCH <i class="fa-solid fa-play ms-2"></i>
                    </button>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- **** Modal for Match Result **** -->
<div class="modal fade" id="matchResult" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="matchResultLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title text-info fw-bold text-uppercase small" id="matchResultLabel"><i class="fa-solid fa-trophy mr-2"></i>Match Result</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="court-box mx-auto p-3 shadow-lg" style="border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.1); color: white;">

                    <div class="text-center mb-3 pb-3 border-bottom border-secondary border-opacity-25">
                        <!-- <div class="small text-uppercase opacity-50 fw-bold mb-1" style="letter-spacing: 2px;">Match Result</div> -->
                        <h5 class="fw-bold text-success mb-0">
                            <i class="fa-solid fa-trophy mr-2 text-warning"></i> WINNER: SMASH KINGS
                        </h5>
                    </div>

                    <div class="row g-0 align-items-center mb-4 bg-black rounded-4 p-3 shadow-inner">
                        <div class="col-5 text-center">
                            <div class="p-1 rounded-circle bg-success d-inline-block mb-2" style="width: 8px; height: 8px;"></div>
                            <h6 class="fw-bold text-white mb-0">Smash Kings</h6>
                            <div class="display-4 fw-bold text-info">2</div>
                        </div>

                        <div class="col-2 text-center opacity-25">
                            <div class="h4 mb-0">-</div>
                        </div>

                        <div class="col-5 text-center opacity-75">
                            <div class="p-1 rounded-circle bg-secondary d-inline-block mb-2" style="width: 8px; height: 8px;"></div>
                            <h6 class="fw-bold mb-0">The Aces</h6>
                            <div class="display-4 fw-bold">1</div>
                        </div>
                    </div>

                    <div class="set-breakdown vstack gap-2">
                        <div class="d-flex justify-content-between align-items-center p-2 rounded-3 bg-dark border border-secondary" style="font-size: 0.85rem;">
                            <span class="opacity-50 fw-bold">SET 1</span>
                            <div class="fw-bold">
                                <span class="text-info">21</span> <span class="mx-2 opacity-25">|</span> 15
                            </div>
                            <i class="fa-solid fa-check-circle text-success"></i>
                        </div>

                        <div class="d-flex justify-content-between align-items-center p-2 rounded-3 bg-dark border border-secondary" style="font-size: 0.85rem;">
                            <span class="opacity-50 fw-bold">SET 2</span>
                            <div class="fw-bold">
                                19 <span class="mx-2 opacity-25">|</span> <span class="text-info">21</span>
                            </div>
                            <i class="fa-solid fa-check-circle text-info"></i>
                        </div>

                        <div class="d-flex justify-content-between align-items-center p-2 rounded-3 bg-dark border border-secondary" style="font-size: 0.85rem;">
                            <span class="opacity-50 fw-bold">SET 3</span>
                            <div class="fw-bold">
                                <span class="text-info">21</span> <span class="mx-2 opacity-25">|</span> 18
                            </div>
                            <i class="fa-solid fa-check-circle text-success"></i>
                        </div>
                    </div>

                    <div class="mt-4 row g-2">
                        <div class="col-6">
                            <button class="btn btn-outline-light w-100 btn-sm rounded-pill fw-bold py-2">
                                <i class="fa-solid fa-share-nodes mr-2"></i>SHARE
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-info w-100 btn-sm rounded-pill fw-bold py-2 shadow" data-dismiss="modal">
                                NEW MATCH
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- **** Modal for Set Score Board **** -->
<div class="modal fade" id="setScoreBoard" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="setScoreBoardLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title text-info fw-bold text-uppercase small" id="setScoreBoardLabel"><i class="fa-regular fa-clipboard mr-2"></i>Set Score Board</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="court-container mx-auto p-3 shadow-lg" style="border-radius: 24px; border: 2px solid #0ea5e9; color: white;">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <button class="btn btn-sm btn-outline-warning border-secondary text-warning fw-bold px-3">
                            <i class="fa-solid fa-rotate-left me-1"></i> UNDO
                        </button>
                        <div class="text-center">
                            <h6 class="mb-0 fw-bold" style="font-size: 0.8rem; letter-spacing: 1px;">SET 1 LIVE</h6>
                            <span class="text-info small fw-bold">21 - 18</span>
                        </div>
                        <button class="btn btn-sm btn-outline-danger border-secondary text-danger fw-bold">FINISH</button>
                    </div>

                    <div class="court-grid position-relative rounded-3 mb-4" style="background: #1e40af; border: 4px solid #fff; height: 320px; display: grid; grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr; gap: 2px;">

                        <div class="position-absolute start-50 top-0 bottom-0 border-start border-white border-3 opacity-75" style="z-index: 10; transform: translateX(-50%);"></div>
                        <div class="position-absolute start-0 end-0 top-50 border-top border-white border-1 opacity-25" style="z-index: 5;"></div>

                        <div class="d-flex flex-column align-items-center justify-content-center border border-white border-opacity-25 p-2">
                            <div class="player-circle bg-white text-dark fw-bold rounded-circle shadow-sm mb-1 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">AZ</div>
                            <span class="small opacity-75" style="font-size: 0.65rem;">Azhar</span>
                            <div class="birdie-marker mt-2 text-warning animate-pulse"><i class="fa-solid fa-circle fa-xs"></i></div>
                        </div>

                        <div class="d-flex flex-column align-items-center justify-content-center border border-white border-opacity-25 p-2" style="background: rgba(0,0,0,0.15);">
                            <div class="player-circle border border-white text-white fw-bold rounded-circle mb-1 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">SM</div>
                            <span class="small opacity-75" style="font-size: 0.65rem;">Sam</span>
                            <div class="birdie-marker mt-2 text-warning opacity-0"><i class="fa-solid fa-circle fa-xs"></i></div>
                        </div>

                        <div class="d-flex flex-column align-items-center justify-content-center border border-white border-opacity-25 p-2">
                            <div class="player-circle border border-white text-white opacity-50 rounded-circle mb-1 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">ZD</div>
                            <span class="small opacity-50" style="font-size: 0.65rem;">Zaid</span>
                            <div class="birdie-marker mt-2 text-warning opacity-0"><i class="fa-solid fa-circle fa-xs"></i></div>
                        </div>

                        <div class="d-flex flex-column align-items-center justify-content-center border border-white border-opacity-25 p-2" style="background: rgba(0,0,0,0.15);">
                            <div class="player-circle border border-white text-white opacity-50 rounded-circle mb-1 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">RY</div>
                            <span class="small opacity-50" style="font-size: 0.65rem;">Ray</span>
                            <div class="birdie-marker mt-2 text-warning opacity-0"><i class="fa-solid fa-circle fa-xs"></i></div>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <button class="btn btn-info w-100 py-3 rounded-4 d-flex flex-column align-items-center shadow">
                                <span class="small fw-bold opacity-75">TEAM 1</span>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-plus"></i>
                                    <span class="h2 mb-0 fw-bold">1</span>
                                </div>
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-outline-info w-100 py-3 rounded-4 d-flex flex-column align-items-center">
                                <span class="small fw-bold opacity-50">TEAM 2</span>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-plus"></i>
                                    <span class="h2 mb-0 fw-bold">1</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-----Footer------>
<?php include "includes/scorer-footer.php"; ?>