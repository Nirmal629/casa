<!-----Header------>
<?php include "includes/inner-header.php";
if($_SESSION['usertype']!='Player')
{
    echo "<script>window.location.href='https://casainfotech.com/index.php';</script>";

}
?>


<!----User-game----->
<section class="bothSide_gap">
    <div class="cust_container">
        <!-- <h6 class="sub_heading">Lorem ipsum</h6> -->
        <h2 class="heading">Player Dashboard</h2>

        <div class="custom_card">
            <!----calender- Date-Picker--->
            <!--<div class="mb-4">-->
            <!--    <form>-->
            <!--        <div class="calendar-box">-->
                        <!-- <h2 class="calendar-title">Select a Date</h2> -->
            <!--            <input type="text" id="dateInput" placeholder="Select a date ">-->
            <!--            <div class="calendar" id="calendar">-->
            <!--                <div class="header">-->
            <!--                    <button id="prevBtn">&lt;</button>-->
            <!--                    <h2 id="monthYear">Month Year</h2>-->
            <!--                    <button id="nextBtn">&gt;</button>-->
            <!--                </div>-->
            <!--                <div class="days" id="daysContainer"></div>-->
            <!--            </div>-->
            <!--        </div>-->
            <!--    </form>-->
            <!--</div>-->


            <!----All-Event-tab-start------->
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="Upcoming-tab" data-bs-toggle="tab" data-bs-target="#Upcoming" type="button" role="tab" aria-controls="Upcoming" aria-selected="true">Upcoming</button>
                </li>
                <!--<li class="nav-item" role="presentation">-->
                <!--    <button class="nav-link" id="Play-Completed-tab" data-bs-toggle="tab" data-bs-target="#Play-Completed" type="button" role="tab" aria-controls="Play-Completed" aria-selected="false">Completed</button>-->
                <!--</li>-->
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="Play-Payment-tab" data-bs-toggle="tab" data-bs-target="#Play-Payment" type="button" role="tab" aria-controls="Play-Payment" aria-selected="false">Payment</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="Play-Monthly-Subscription" data-bs-toggle="tab" data-bs-target="#Monthly-Subscription" type="button" role="tab" aria-controls="Monthly-Subscription" aria-selected="false">Subscribe</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="Play-Play-Rating" data-bs-toggle="tab" data-bs-target="#Play-Rating" type="button" role="tab" aria-controls="Play-Rating" aria-selected="false">Rating</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="play-player-hub" data-bs-toggle="tab" data-bs-target="#player-hub" type="button" role="tab" aria-controls="player-hub" aria-selected="false">Player-hub</button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="Upcoming" role="tabpanel" aria-labelledby="Upcoming-tab">
                    <?php include "player-Upcoming-game.php"; ?>
                </div>
                <div class="tab-pane fade" id="Play-Completed" role="tabpanel" aria-labelledby="Play-Completed-tab">
                    <?php include "player-Complete-game.php"; ?>
                </div>
                <div class="tab-pane fade" id="Play-Payment" role="tabpanel" aria-labelledby="Play-Payment-tab">
                    <?php include "player-payment-list.php"; ?>
                </div>
                <div class="tab-pane fade" id="Monthly-Subscription" role="tabpanel" aria-labelledby="Play-Monthly-Subscription">
                    <?php include "player-monthly-subscription.php"; ?>
                </div>
                <div class="tab-pane fade" id="Play-Rating" role="tabpanel" aria-labelledby="Play-Play-Rating">
                    <?php include "player-rating.php"; ?>
                </div>
                <div class="tab-pane fade" id="player-hub" role="tabpanel" aria-labelledby="play-player-hub">
                    <?php include "player-hub.php"; ?>
                </div>
            </div>
            <!----All-Event-tab-End------->

        </div>
    </div>
</section>




<!------footer------>
<?php include "includes/footer.php"; ?>