<div class="battleTournament_sec">
    <!-- <h2 class="title">Winter - Mini Casa Tournament 2025</h2> -->

    <!-- INPUT SUMMARY -->
    <div class="input-box">
        <div class="detail">Club Name: <span>Casa Badminton Club</span></div>
        <div class="detail">Tag Line: <span>lorem Ipsum..........</span></div>
        <div class="detail">Category: <span>Men-Doubles-Open</span></div>
        <div class="detail">Date: <span>06/02/2026</span></div>
        <div class="detail">Time: <span>Feb 21, 2026 at 9:36 AM EST</span></div>
        <div class="detail">Venue: <span>Casa Badminton Club</span></div>
    </div>


    <div class="grid-3">
        <div class="">
            <div class="card parameters-box">
                <div class="collapse-header" onclick="toggleCollapse()">
                    <h4 class="section-title">Tournament Parameter</h4>
                    <span>▼</span>
                </div>
                <div class="collapse-body" id="collapseContent">
                    <!-- Numeric Inputs -->
                    <div class="grid">
                        <div class="field small">
                            <label>Number of teams registered</label>
                            <input type="number" value="24">
                        </div>

                        <div class="field small">
                            <label>Number of Groups Required <span>(Max 6)</span></label>
                            <input type="number" value="4">
                        </div>

                        <div class="field small">
                            <label>Players in each group <span>(Max 8)</span></label>
                            <input type="number" value="6">
                        </div>

                        <div class="field small">
                            <label>Teams from GL to Q (per group) <span>(Max 6)</span></label>
                            <input type="number" value="2">
                        </div>

                        <div class="field small">
                            <label>Teams from Q to Semi <span>(Max 4)</span></label>
                            <input type="number" value="4">
                        </div>
                    </div>

                    <!-- Game Sets -->
                    <h4 class="sub-title">Game Sets</h4>

                    <div class="grid">
                        <div class="field small">
                            <label>Group League</label>
                            <input type="number" value="1">
                        </div>
                        <div class="field small">
                            <label>Quarter Final</label>
                            <input type="number" value="3">
                        </div>
                        <div class="field small">
                            <label>Semi Final</label>
                            <input type="number" value="3">
                        </div>
                        <div class="field small">
                            <label>Winner Final</label>
                            <input type="number" value="3">
                        </div>
                        <div class="field small">
                            <label>Loser Final</label>
                            <input type="number" value="3">
                        </div>
                    </div>

                    <!-- Match Selection -->
                    <h4 class="sub-title">Match Selection Logic</h4>

                    <!-- Quarter Final -->
                    <div class="match-box">
                        <p>Quarter Final – Group Match</p>
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                            <div class="selector">
                                <select>
                                    <option>Select Team</option>
                                    <option>Team A</option>
                                    <option>Team B</option>
                                    <option>Team C</option>
                                    <option>Team D</option>
                                </select>
                                <span>VS</span>
                                <select>
                                    <option>Select Team</option>
                                    <option>Team A</option>
                                    <option>Team B</option>
                                    <option>Team C</option>
                                    <option>Team D</option>
                                </select>
                                <button type="button" class="small-spin-btn badge" data-bs-toggle="modal" 
                                data-bs-target="#spinWheelModal" title="Spin">🎯</button>
                            </div>
                            <div class="selector">
                                <select>
                                    <option>Select Team</option>
                                    <option>Team A</option>
                                    <option>Team B</option>
                                    <option>Team C</option>
                                    <option>Team D</option>
                                </select>
                                <span>VS</span>
                                <select>
                                    <option>Select Team</option>
                                    <option>Team A</option>
                                    <option>Team B</option>
                                    <option>Team C</option>
                                    <option>Team D</option>
                                </select>
                                <button type="button" class="small-spin-btn badge" data-bs-toggle="modal" 
                                data-bs-target="#spinWheelModal" title="Spin">🎯</button>
                            </div>
                            <div class="selector">
                                <select>
                                    <option>Select Team</option>
                                    <option>Team A</option>
                                    <option>Team B</option>
                                    <option>Team C</option>
                                    <option>Team D</option>
                                </select>
                                <span>VS</span>
                                <select>
                                    <option>Select Team</option>
                                    <option>Team A</option>
                                    <option>Team B</option>
                                    <option>Team C</option>
                                    <option>Team D</option>
                                </select>
                                <button type="button" class="small-spin-btn badge" data-bs-toggle="modal" 
                                data-bs-target="#spinWheelModal" title="Spin">🎯</button>
                            </div>
                            <div class="selector">
                                <select>
                                    <option>Select Team</option>
                                    <option>Team A</option>
                                    <option>Team B</option>
                                    <option>Team C</option>
                                    <option>Team D</option>
                                </select>
                                <span>VS</span>
                                <select>
                                    <option>Select Team</option>
                                    <option>Team A</option>
                                    <option>Team B</option>
                                    <option>Team C</option>
                                    <option>Team D</option>
                                </select>
                                <button type="button" class="small-spin-btn badge" data-bs-toggle="modal" 
                                data-bs-target="#spinWheelModal" title="Spin">🎯</button>
                            </div>
                        </div>
                    </div>

                    <!-- Semi Final -->
                    <div class="match-box">
                        <p>Semi Final – Team Match</p>
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                            <div class="selector">
                                <select>
                                    <option>Select Team</option>
                                    <option>Winner QF1</option>
                                    <option>Winner QF2</option>
                                    <option>Winner QF3</option>
                                    <option>Winner QF4</option>
                                </select>
                                <span>VS</span>
                                <select>
                                    <option>Select Team</option>
                                    <option>Winner QF1</option>
                                    <option>Winner QF2</option>
                                    <option>Winner QF3</option>
                                    <option>Winner QF4</option>
                                </select>
                                <button type="button" class="small-spin-btn badge" data-bs-toggle="modal" 
                                data-bs-target="#spinWheelModal" title="Spin">🎯</button>
                            </div>
                            <div class="selector">
                                <select>
                                    <option>Select Team</option>
                                    <option>Winner QF1</option>
                                    <option>Winner QF2</option>
                                    <option>Winner QF3</option>
                                    <option>Winner QF4</option>
                                </select>
                                <span>VS</span>
                                <select>
                                    <option>Select Team</option>
                                    <option>Winner QF1</option>
                                    <option>Winner QF2</option>
                                    <option>Winner QF3</option>
                                    <option>Winner QF4</option>
                                </select>
                                <button type="button" class="small-spin-btn badge" data-bs-toggle="modal" 
                                data-bs-target="#spinWheelModal" title="Spin">🎯</button>
                            </div>
                        </div>
                    </div>

                    <div class="action-area">
                        <!-- <button type="button" class="spin-btn" data-bs-toggle="modal" data-bs-target="#spinWheelModal">🎯 Spin a Wheel</button> -->
                        <button class="save">Save Parameters</button>
                    </div>
                </div>
            </div>
            <div class="registertable">
                <!----Teams Registered--->
                <div class="card">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                        <h4 class="section-title">Teams Registered</h4>
                        <a href="#" class="btn">View All Group</a>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <tr>
                                <th>Team</th>
                                <th>P1</th>
                                <th>P2</th>
                                <th>Group</th>
                                <th>Action</th>
                            </tr>
                            <tr>
                                <td>Rock</td>
                                <td>Anurag</td>
                                <td>Nirmol</td>
                                <td>Group-A</td>
                                <td><select name="sel1">
                                        <option value="group-a">Group-A</option>
                                        <option value="group-b">Group-B</option>
                                        <option value="group-c">Group-C</option>
                                        <option value="group-d">Group-D</option>
                                    </select></td>
                            </tr>
                            <tr>
                                <td>Smash</td>
                                <td>Shiltu</td>
                                <td>Bikram</td>
                                <td>Group-B</td>
                                <td><select name="sel2">
                                        <option value="group-a">Group-A</option>
                                        <option value="group-b">Group-B</option>
                                        <option value="group-c">Group-C</option>
                                        <option value="group-d">Group-D</option>
                                    </select></td>
                            </tr>
                            <tr>
                                <td>Killer</td>
                                <td>Sarajith</td>
                                <td>Adam</td>
                                <td>Group-C</td>
                                <td><select name="sel3">
                                        <option value="group-a">Group-A</option>
                                        <option value="group-b">Group-B</option>
                                        <option value="group-c">Group-C</option>
                                        <option value="group-d">Group-D</option>
                                    </select></td>
                            </tr>
                            <tr>
                                <td>wilder</td>
                                <td>amal</td>
                                <td>vijai</td>
                                <td>Group-D</td>
                                <td><select name="sel4">
                                        <option value="group-a">Group-A</option>
                                        <option value="group-b">Group-B</option>
                                        <option value="group-c">Group-C</option>
                                        <option value="group-d">Group-D</option>
                                    </select></td>
                            </tr>
                            <tr>
                                <td>wilder dsd</td>
                                <td>amal</td>
                                <td>vijai</td>
                                <td>Group-D</td>
                                <td><select name="sel4">
                                        <option value="group-a">Group-A</option>
                                        <option value="group-b">Group-B</option>
                                        <option value="group-c">Group-C</option>
                                        <option value="group-d">Group-D</option>
                                    </select></td>
                            </tr>
                            <tr>
                                <td>wilder assds</td>
                                <td>amal</td>
                                <td>vijai</td>
                                <td>Group-D</td>
                                <td><select name="sel4">
                                        <option value="group-a">Group-A</option>
                                        <option value="group-b">Group-B</option>
                                        <option value="group-c">Group-C</option>
                                        <option value="group-d">Group-D</option>
                                    </select></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="grouptable">

            <!-- All GROUP -->
            <div class="card">
                <!-- GROUP STAGE -->
                <h4 class="section-title">League Stage</h4>

                <div class="grid-4">
                    <!-- GROUP A -->
                    <div class="card">
                        <div class="d-flex align-items-center justify-content-between gap-1">
                            <h6>Group A</h6>
                            <a href="#" class="btn">View</a>
                        </div>
                        <table>
                            <tr>
                                <th>Team Name</th>
                                <th>P1</th>
                                <th>P2</th>
                                <th>Rank</th>
                            </tr>
                            <tr>
                                <td>Rock</td>
                                <td>Anurag</td>
                                <td>Nirmol</td>
                                <td>1</td>
                            </tr>
                            <tr>
                                <td>Rock</td>
                                <td>Anurag</td>
                                <td>Nirmol</td>
                                <td>1</td>
                            </tr>
                        </table>
                    </div>
                    <!-- GROUP B -->
                    <div class="card">
                        <div class="d-flex align-items-center justify-content-between gap-1">
                            <h6>Group B</h6>
                            <a href="#" class="btn">View</a>
                        </div>
                        <table>
                            <tr>
                                <th>Team</th>
                                <th>P1</th>
                                <th>P2</th>
                                <th>Rank</th>
                            </tr>
                            <tr>
                                <td>Smash</td>
                                <td>Shiltu</td>
                                <td>Bikram</td>
                                <td>1</td>
                            </tr>
                            <tr>
                                <td>Smash</td>
                                <td>Shiltu</td>
                                <td>Bikram</td>
                                <td>1</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="grid-4">
                    <!-- GROUP C -->
                    <div class="card">
                        <div class="d-flex align-items-center justify-content-between gap-1">
                            <h6>Group C</h6>
                            <a href="#" class="btn">View</a>
                        </div>
                        <table>
                            <tr>
                                <th>Team</th>
                                <th>P1</th>
                                <th>P2</th>
                                <th>Rank</th>
                            </tr>
                            <tr>
                                <td>Killer</td>
                                <td>Sarajith</td>
                                <td>Adam</td>
                                <td>1</td>
                            </tr>
                            <tr>
                                <td>Killer</td>
                                <td>Sarajith</td>
                                <td>Adam</td>
                                <td>1</td>
                            </tr>
                        </table>
                    </div>
                    <!-- GROUP D -->
                    <div class="card">
                        <div class="d-flex align-items-center justify-content-between gap-1">
                            <h6>Group D</h6>
                            <a href="#" class="btn">View</a>
                        </div>
                        <table>
                            <tr>
                                <th>Team</th>
                                <th>P1</th>
                                <th>P2</th>
                                <th>Rank</th>
                            </tr>
                            <tr>
                                <td>Wilder</td>
                                <td>Amal</td>
                                <td>Vijai</td>
                                <td>1</td>
                            </tr>
                            <tr>
                                <td>Wilder</td>
                                <td>Amal</td>
                                <td>Vijai</td>
                                <td>1</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="grid-4">
                <!-- QUARTER FINAL -->
                <div class="card">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                        <h4 class="section-title">Quarter Final</h4>
                        <a href="court-dashboard.php" class="btn">View</a>
                    </div>
                    <table>
                        <tr>
                            <th>Team</th>
                            <th>P1</th>
                            <th>P2</th>
                            <th>Rank</th>
                        </tr>
                        <tr>
                            <td>Rock</td>
                            <td>Anurag</td>
                            <td>Nirmol</td>
                            <td>1</td>
                        </tr>
                        <tr>
                            <td>Rock</td>
                            <td>Anurag</td>
                            <td>Nirmol</td>
                            <td>1</td>
                        </tr>
                    </table>
                </div>

                <!-- SEMI FINAL -->
                <div class="card">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                        <h4 class="section-title">Semi Final</h4>
                        <a href="#" class="btn">View</a>
                    </div>
                    <table>
                        <tr>
                            <th>Team</th>
                            <th>P1</th>
                            <th>P2</th>
                            <th>Rank</th>
                        </tr>
                        <tr>
                            <td>Rock</td>
                            <td>Anurag</td>
                            <td>Nirmol</td>
                            <td>1</td>
                        </tr>
                         <tr>
                            <td>Rock</td>
                            <td>Anurag</td>
                            <td>Nirmol</td>
                            <td>1</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="grid-4">
                <!-- FINAL -->
                <div class="card winner">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                        <h4 class="section-title">Championship Final</h4>
                        <a href="badminton-scorer.php" class="btn">View</a>
                    </div>
                    <table>
                        <tr>
                            <th>Team</th>
                            <th>P1</th>
                            <th>P2</th>
                            <th>Rank</th>
                        </tr>
                        <tr>
                            <td>Rock</td>
                            <td>Anurag</td>
                            <td>Nirmol</td>
                            <td>1</td>
                        </tr>
                        <tr>
                            <td>Rock</td>
                            <td>Anurag</td>
                            <td>Nirmol</td>
                            <td>1</td>
                        </tr>
                    </table>
                </div>

                <!-- LOOSER FINAL -->
                <div class="card">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                        <h4 class="section-title">Bronze Final</h4>
                        <a href="#" class="btn">View</a>
                    </div>
                    <table>
                        <tr>
                            <th>Team</th>
                            <th>Rank</th>
                        </tr>
                        <tr>
                            <td>L-3</td>
                            <td>3</td>
                        </tr>
                        <tr>
                            <td>L-4</td>
                            <td>4</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- Spin a Wheel Modal start-->
<div class="modal fade" id="spinWheelModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="spinWheelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="spinWheelModalLabel">Modal title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <?php include "spin-wheel.php"; ?>
            </div>
        </div>
    </div>
</div>
<!-- Spin a Wheel Modal End-->

<!---toggleCollapse js---->
<script>
    function toggleCollapse() {
        const header = document.querySelector(".collapse-header");
        const body = document.getElementById("collapseContent");

        header.classList.toggle("active");
        body.classList.toggle("active");
    }
</script>