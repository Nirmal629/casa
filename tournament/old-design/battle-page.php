<!-----Header------>
<?php include "includes/header.php"; ?>

<section class="tournament_page bothSide_gap">
    <div class="cust_container">
        <h2 class="heading white">Winter - Mini Casa Tournament 2025</h2>

        <!----announcement-card----->
        <div class="announcement_card">
            <div class="info-left">
                <div class="info-row">
                    <div class="label">Players</div>
                    <div class="value">7</div>
                </div>
                <div class="info-row">
                    <div class="label">Format</div>
                    <div class="value"><a href="#" class="link">Double Elimination</a></div>
                </div>
                <div class="info-row">
                    <div class="label">Game</div>
                    <div class="value">Badminton</div>
                </div>
                <div class="info-row">
                    <div class="label">Start Time</div>
                    <div class="value">December 21, 2024 at 9:36 AM EST</div>
                </div>
            </div>
            <div class="info-right Organized_by">
                <img src="./assets/images/logo/Final-Logo.png" alt="Organizer" class="org-logo">
                <div class="org-text">
                    Organized by <span>Casa Tournaments</span>
                </div>
            </div>
        </div>

        <div class="customtab_wrap">
            <div class="nav nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                <button class="nav-link active" id="v-pills-announcements-tab" data-bs-toggle="pill" data-bs-target="#v-pills-announcements" type="button" role="tab" aria-controls="v-pills-announcements" aria-selected="true">Battle Broadcast</button>
                <button class="nav-link" id="v-pills-bracket-tab" data-bs-toggle="pill" data-bs-target="#v-pills-bracket" type="button" role="tab" aria-controls="v-pills-bracket" aria-selected="false">Battle Live</button>
                <button class="nav-link" id="v-pills-standings-tab" data-bs-toggle="pill" data-bs-target="#v-pills-standings" type="button" role="tab" aria-controls="v-pills-standings" aria-selected="false">Battle Board</button>
                <button class="nav-link" id="v-pills-log-tab" data-bs-toggle="pill" data-bs-target="#v-pills-log" type="button" role="tab" aria-controls="v-pills-log" aria-selected="false">Log (5)</button>
            </div>

            <div class="tab-content" id="v-pills-tabContent">
                <div class="tab-pane fade show active" id="v-pills-announcements" role="tabpanel" aria-labelledby="v-pills-announcements-tab">

                    <?php include "battle-broadcast.php"; ?>

                </div>
                <div class="tab-pane fade" id="v-pills-bracket" role="tabpanel" aria-labelledby="v-pills-bracket-tab">

                    <?php include "battle-live.php"; ?>

                </div>
                <div class="tab-pane fade" id="v-pills-standings" role="tabpanel" aria-labelledby="v-pills-standings-tab">

                    <?php include "battle-board.php"; ?>

                </div>

                <div class="tab-pane fade" id="v-pills-log" role="tabpanel" aria-labelledby="v-pills-log-tab">

                    <?php include "log.php"; ?>

                </div>
            </div>
        </div>

    </div>
</section>

<!------footer------>
<?php include "includes/footer.php"; ?>