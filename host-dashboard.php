<!-----Header------>
<?php 
include "includes/inner-header.php"; 
// print_r($_SESSION);
// exit;
if(trim($_SESSION['usertype'])!='Host' && trim($_SESSION['usertype'])!='Trainer')
{
// echo "ok";
    header('location:index.php');
    exit;
}
?>
<!----Dashboard------>
<section class="bothSide_gap">
    <div class="cust_container">
        <!-- <h6 class="sub_heading">host</h6> -->
        <h2 class="heading"><?=trim($_SESSION['usertype'])=='Host'?'Host Dashboard':'Trainer Dashboard'?></h2>

        <div class="custom_card">

            <!-----select year and month from----->
            <!--<div class="mb-4">-->
            <!--    <form>-->
            <!--        <div class="row">-->
            <!--            <div class="col-auto">-->
            <!--                <select class="form-select" aria-label="Default select example">-->
            <!--                    <option selected>Select the Year</option>-->
            <!--                    <option value="2024">2024</option>-->
            <!--                    <option value="2025">2025</option>-->
            <!--                    <option value="2026">2026</option>-->
            <!--                    <option value="2027">2027</option>-->
            <!--                    <option value="2028">2028</option>-->
            <!--                    <option value="2029">2029</option>-->
            <!--                    <option value="2030">2030</option>-->
            <!--                </select>-->
            <!--            </div>-->
            <!--            <div class="col-auto">-->
            <!--                <select class="form-select" aria-label="Default select example">-->
            <!--                    <option selected>Select the Month</option>-->
            <!--                    <option value="January">January</option>-->
            <!--                    <option value="February">February</option>-->
            <!--                    <option value="March">March</option>-->
            <!--                    <option value="April">April</option>-->
            <!--                    <option value="May">May</option>-->
            <!--                    <option value="July">July</option>-->
            <!--                    <option value="August">August</option>-->
            <!--                    <option value="September">September</option>-->
            <!--                    <option value="October">October</option>-->
            <!--                    <option value="November">November</option>-->
            <!--                    <option value="December">December</option>-->
            <!--                </select>-->
            <!--            </div>-->

            <!--            <div class="col-auto">-->
            <!--                <button type="button" class="btn btn-primary">Submit</button>-->
            <!--            </div>-->
            <!--        </div>-->
            <!--    </form>-->
            <!--</div>-->

            <!----All-Event-tab-start------->
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="Scheduled-tab" data-bs-toggle="tab" data-bs-target="#Scheduled" type="button" role="tab" aria-controls="Scheduled" aria-selected="true">Scheduled</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="Completed-tab" data-bs-toggle="tab" data-bs-target="#Completed" type="button" role="tab" aria-controls="Completed" aria-selected="false">Completed</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="hostgame-tab" data-bs-toggle="tab" data-bs-target="#hostgame" type="button" role="tab" aria-controls="hostgame" aria-selected="false">+ New Event</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="Payment-tab" data-bs-toggle="tab" data-bs-target="#Payment" type="button" role="tab" aria-controls="Payment" aria-selected="false">Payment</button>
                </li>
                <?php
                if($_SESSION['username'] == 'casaclubtoronto@gmail.com')
                {
                ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="Subscription-tab" data-bs-toggle="tab" data-bs-target="#Subscription" type="button" role="tab" aria-controls="Subscription" aria-selected="false">Monthly Subscription</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="Rating-tab" data-bs-toggle="tab" data-bs-target="#Rating" type="button" role="tab" aria-controls="Rating" aria-selected="false">Rating</button>
                </li>
                <?php
                }
                ?>
            </ul>

            <div class="tab-content" id="myTabContent">
                
                <div class="tab-pane fade show active" id="Scheduled" role="tabpanel" aria-labelledby="Scheduled-tab">
                    <?php include "host-scheduled-game.php"; ?>
                </div>
                <div class="tab-pane fade" id="Completed" role="tabpanel" aria-labelledby="Completed-tab">
                    <?php include "host-complete-game.php"; ?>
                </div>
                <div class="tab-pane fade" id="hostgame" role="tabpanel" aria-labelledby="hostgame-tab">
                    <?php include "host-creat-game.php"; ?>
                </div>
                <div class="tab-pane fade" id="Payment" role="tabpanel" aria-labelledby="Payment-tab">
                    <?php include "host-payment-list.php"; ?>
                </div>
                <div class="tab-pane fade" id="Subscription" role="tabpanel" aria-labelledby="Subscription-tab">
                    <?php include "subscription-list.php"; ?>
                </div>
                <div class="tab-pane fade" id="Rating" role="tabpanel" aria-labelledby="Rating-tab">
                    <?php include "host-rating.php"; ?>
                </div>
            </div>
            <!----All-Event-tab-End------->


        </div>
    </div>
</section>


<!------footer------>
<?php include "includes/footer.php"; ?>