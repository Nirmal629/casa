<!-----Header------>
<?php include "includes/header.php"; ?>

<section class="tournament_page bottomSide_gap">
    <div class="cust_container">
        <div class="battleTournament_sec">

            <!-- PAGE TITLE -->
            <!-- <h2 class="title">The Player Hub → Casa Cup 2026 → Court Dashboard</h2> -->

            <!-- EVENT DETAILS -->
            <div class="card input-box">
                <div class="grid-4">
                    <div class="detail">Club Name: <span>Casa Badminton Club</span></div>
                    <div class="detail">Event Type: <span>Tournament</span></div>
                    <div class="detail">Tag Line: <span>Smash the Game</span></div>
                    <div class="detail">Date: <span>06/02/2026</span></div>
                    <div class="detail">Gender Category: <span>Men</span></div>
                    <div class="detail">Time: <span>9:00 AM</span></div>
                    <div class="detail">Event Category: <span>Doubles Open</span></div>
                    <div class="detail">Venue: <span>Casa Badminton Club</span></div>
                </div>
            </div>

            <!-- LEAGUE STAGE -->
            <div class="card">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="section-title">League Stage</h4>
                    <select class="form-control w-auto">
                        <option>Group-A</option>
                        <option>Group-B</option>
                        <option>Group-C</option>
                        <option>Group-D</option>
                    </select>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Round</th>
                                <th>Block</th>
                                <th>Court</th>
                                <th>Match ID</th>
                                <th>Team A</th>
                                <th>Team B</th>
                                <th>P1 & P2</th>
                                <th>Score A</th>
                                <th>Score B</th>
                                <th>Winner</th>
                                <th>Notes</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>A</td>
                                <td>C1</td>
                                <td>1</td>
                                <td>Apple</td>
                                <td>Ball</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td><a href="#" class="btn">Play</a> <a href="#" class="btn">View</a></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- LEAGUE POINT TABLE -->
            <div class="card">
                <h4 class="section-title">Points Table</h4>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Team</th>
                                <th>MP</th>
                                <th>W</th>
                                <th>L</th>
                                <th>Pts</th>
                                <th>PF</th>
                                <th>PA</th>
                                <th>Notes</th>
                                <th>Rank</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Apple</td>
                                <td>5</td>
                                <td>3</td>
                                <td>2</td>
                                <td>6</td>
                                <td>75</td>
                                <td>60</td>
                                <td>-</td>
                                <td>1</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MATRIX TABLE -->
            <div class="card">
                <h4 class="section-title">Head to Head Matrix</h4>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th></th>
                            <th>Apple</th>
                            <th>Ball</th>
                            <th>Cat</th>
                            <th>Dog</th>
                        </tr>
                        <tr>
                            <td>Apple</td>
                            <td>X</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- QUARTER FINAL -->
            <div class="card">
                <h4 class="section-title">Quarter Final</h4>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Match</th>
                                <th>Team A</th>
                                <th>Team B</th>
                                <th>Score A</th>
                                <th>Score B</th>
                                <th>Winner</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>QF1</td>
                                <td>G1T1</td>
                                <td>G2T2</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td><a href="#" class="btn">Play</a> <a href="#" class="btn">View</a></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SEMI FINAL -->
            <div class="card">
                <h4 class="section-title">Semi Final</h4>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th>Match</th>
                            <th>Team A</th>
                            <th>Team B</th>
                            <th>Winner</th>
                            <th>Action</th>
                        </tr>
                        <tr>
                            <td>SF1</td>
                            <td>W1</td>
                            <td>W2</td>
                            <td>-</td>
                            <td><a href="#" class="btn">Play</a></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- FINAL -->
            <div class="card winner">
                <h4 class="section-title">Championship Final</h4>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th>Match</th>
                            <th>Team A</th>
                            <th>Team B</th>
                            <th>Winner</th>
                            <th>Action</th>
                        </tr>
                        <tr>
                            <td>F1</td>
                            <td>W1</td>
                            <td>W2</td>
                            <td>-</td>
                            <td><a href="#" class="btn">Play</a></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- BRONZE FINAL -->
            <div class="card">
                <h4 class="section-title">Bronze Final</h4>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th>Match</th>
                            <th>Team A</th>
                            <th>Team B</th>
                            <th>Winner</th>
                            <th>Action</th>
                        </tr>
                        <tr>
                            <td>B1</td>
                            <td>L1</td>
                            <td>L2</td>
                            <td>-</td>
                            <td><a href="#" class="btn">Play</a></td>
                        </tr>
                    </table>
                </div>
            </div>

        </div>
    </div>
</section>

<!------footer------>
<?php include "includes/footer.php"; ?>