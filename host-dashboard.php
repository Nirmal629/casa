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

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    /* Ultra-compact stacked pills */
    .nav-stacked-pills .nav-link {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 6px 4px;
        font-size: 0.65rem;
        font-weight: 600;
        text-transform: uppercase;
        min-width: 68px; /* Balanced width for mobile */
        color: #6c757d;
        border: none;
        background: none;
    }

    /* SVG sizing */
    .nav-stacked-pills .nav-link svg {
        margin-bottom: 3px;
    }

    /* Active state */
    .nav-stacked-pills .nav-link.active {
        background-color: #0d6efd !important;
        color: white !important;
    }

    /* Swipeable container */
    .tab-scroll-container {
        display: flex;
        overflow-x: auto;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        padding: 5px 0;
        border-bottom: 1px solid #dee2e6;
    }
    .tab-scroll-container::-webkit-scrollbar { display: none; }
</style>

<!-- Bootstrap 5 JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

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
            <div class="tab-scroll-container mb-3">
                <ul class="nav nav-pills nav-stacked-pills flex-nowrap" id="myTab" role="tablist">
                    
                    <li class="nav-item">
                        <button class="nav-link active" id="Scheduled-tab" data-bs-toggle="tab" data-bs-target="#Scheduled" type="button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857V3.857z"/>
                                <path d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
                            </svg>
                            <span>Schedule</span>
                        </button>
                    </li>
            
                    <li class="nav-item">
                        <button class="nav-link" id="Completed-tab" data-bs-toggle="tab" data-bs-target="#Completed" type="button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                            </svg>
                            <span>Complete</span>
                        </button>
                    </li>
            
                    <li class="nav-item">
                        <button class="nav-link" id="hostgame-tab" data-bs-toggle="tab" data-bs-target="#hostgame" type="button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                                <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                            </svg>
                            <span>New</span>
                        </button>
                    </li>
            
                    <li class="nav-item">
                        <button class="nav-link" id="Payment-tab" data-bs-toggle="tab" data-bs-target="#Payment" type="button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M1 3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1H1zm7 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>
                                <path d="M0 5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V5zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V7a2 2 0 0 1-2-2H3z"/>
                            </svg>
                            <span>Payment</span>
                        </button>
                    </li>
            
                    <li class="nav-item">
                        <button class="nav-link" id="ClubSettings-tab" data-bs-toggle="tab" data-bs-target="#ClubSettings" type="button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M5.338 1.59a61.44 61.44 0 0 0-2.837.856.481.481 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.725 10.725 0 0 0 2.287 2.233c.346.244.652.42.893.533.12.057.218.095.293.118a.55.55 0 0 0 .101.025.615.615 0 0 0 .1-.025c.076-.023.174-.061.294-.118.24-.113.547-.29.893-.533a10.726 10.726 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.456 62.456 0 0 1 5.072.56z"/>
                                <path d="M10.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0z"/>
                            </svg>
                            <span>My Club</span>
                        </button>
                    </li>

                    <!--
                    <li class="nav-item">
                        <button class="nav-link" id="playerstats-tab" data-bs-toggle="tab" data-bs-target="#playerstats" type="button" role="tab" aria-controls="playerstats" aria-selected="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M0 0h1v15h15v1H0V0zm10 10h2v5h-2v-5zm-4-3h2v8H6V7zm-4 5h2v3H2v-3zm8-7h2v10h-2V5z"/>
                            </svg>
                            <span>Players</span>
                        </button>
                    </li>
                    -->

                    <li class="nav-item">
                        <button class="nav-link" id="roster-tab" data-bs-toggle="tab" data-bs-target="#Roster" type="button" role="tab" aria-controls="Roster" aria-selected="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8Zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002a.274.274 0 0 1-.014.002H7.022ZM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816ZM8.444 5.25a3 3 0 1 0-5.888 0 3 3 0 0 0 5.888 0Z"/>
                            </svg>
                            <span>List</span>
                        </button>
                    </li>

                    <?php if($_SESSION['username'] == 'casaclubtoronto@gmail.com'): ?>
                        <li class="nav-item">
                            <button class="nav-link" id="Subscription-tab" data-bs-toggle="tab" data-bs-target="#Subscription" type="button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M14.5 3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h13zm-13-1A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-13z"/>
                                    <path d="M3 5.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 8a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 10.5a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5z"/>
                                </svg>
                                <span>Subscribe</span>
                            </button>
                        </li>
            
                        <li class="nav-item">
                            <button class="nav-link" id="Rating-tab" data-bs-toggle="tab" data-bs-target="#Rating" type="button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
                                </svg>
                                <span>Rating</span>
                            </button>
                        </li>
                    <?php endif; ?>
                    
                </ul>
            </div>

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
                <!-- Player Stats Tab -->
                <div class="tab-pane fade" id="playerstats" role="tabpanel" aria-labelledby="playerstats-tab">
                    <?php include 'host-player-stats.php'; ?>
                </div>
                <!-- Roster Tab -->
                <div class="tab-pane fade" id="Roster" role="tabpanel" aria-labelledby="roster-tab">
                    <?php include 'host-roster.php'; ?>
                </div>
                <!-- Club Settings Tab -->
                <div class="tab-pane fade" id="ClubSettings" role="tabpanel" aria-labelledby="ClubSettings-tab">
                    <?php include 'host-club-settings.php'; ?>
                </div>
            </div>
            <!----All-Event-tab-End------->


        </div>
    </div>
</section>


<!------footer------>
<?php include "includes/footer.php"; ?>