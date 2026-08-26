<?php
// DB connection ($conn) is already provided by inner-header.php

$currentDate = date('Y-m-d');
$currentTime = date('H:i');

$currentYear = date('Y');
$currentMonth = date('n'); // 1-12 (no leading zero)

        $check_player = mysqli_query($conn,"select * from ca_users where ID='".$_SESSION['user_id']."'");
        $fetch_player = mysqli_fetch_assoc($check_player);
        $premiumStatus = $fetch_player['PREMIUM'];
        $host_id = isset($host_id) ? $host_id : (isset($_GET['host_id']) ? intval($_GET['host_id']) : ($_SESSION['mapped_host_id'] ?? 0));

?>
<?php if ($premiumStatus != 'Y') { ?>
        <p style="color:red; font-weight:bold;text-align:center">Contact admin to enable the premium account</p>
    <?php } ?>
<!-----player-payment-list------>
<div class="" style="<?php echo ($premiumStatus != 'Y') ? 'opacity:0; pointer-events:none;' : ''; ?>">
<!----player-Complete-game-------->
<div class="mb-4" >
    
                <form>
                    <div class="row">
                        <input type="hidden" id="payhost" value="<?= htmlspecialchars($host_id) ?>" />
                        <!--<div class="col-auto">-->
                        <!--    <select class="form-select" id="payyear" aria-label="Default select example">-->
                        <!--        <option selected>Select the Year</option>-->
                        <!--        <option value="2024">2024</option>-->
                        <!--        <option value="2025">2025</option>-->
                        <!--        <option value="2026">2026</option>-->
                        <!--        <option value="2027">2027</option>-->
                        <!--        <option value="2028">2028</option>-->
                        <!--        <option value="2029">2029</option>-->
                        <!--        <option value="2030">2030</option>-->
                        <!--    </select>-->
                        <!--</div>-->
                        <!--<div class="col-auto">-->
                        <!--    <select class="form-select" id="paymonth" aria-label="Default select example">-->
                        <!--        <option selected>Select the Month</option>-->
                        <!--        <option value="1">January</option>-->
                        <!--        <option value="2">February</option>-->
                        <!--        <option value="3">March</option>-->
                        <!--        <option value="4">April</option>-->
                        <!--        <option value="5">May</option>-->
                        <!--        <option value="6">June</option>-->
                        <!--        <option value="7">July</option>-->
                        <!--        <option value="8">August</option>-->
                        <!--        <option value="9">September</option>-->
                        <!--        <option value="10">October</option>-->
                        <!--        <option value="11">November</option>-->
                        <!--        <option value="12">December</option>-->
                        <!--    </select>-->
                        <!--</div>-->
                        <div class="col-auto">
                            <select class="form-select py-0 px-2" id="payyear" aria-label="Default select example" style="width: 75px; height: 31px; font-size: 0.95rem; background-position: right 0.2rem center; padding-right: 1.5rem !important;">
                                <option value="">Year</option>
                                <?php
                                for ($year = 2024; $year <= 2030; $year++) {
                                    $selected = ($year == $currentYear) ? 'selected' : '';
                                    echo "<option value=\"$year\" $selected>$year</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <select class="form-select py-0 px-2" id="paymonth" aria-label="Default select example" style="width: 70px; height: 31px; font-size: 0.95rem; background-position: right 0.2rem center; padding-right: 1.5rem !important;">
                                <option value="">Month</option>
                                <?php
                                $months = [
                                    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                                    5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
                                    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
                                ];
                                foreach ($months as $num => $name) {
                                    $selected = ($num == $currentMonth) ? 'selected' : '';
                                    echo "<option value=\"$num\" $selected>$name</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-auto ms-auto d-flex gap-1">
                            <button type="button" class="btn btn-primary btn-sm d-flex align-items-center justify-content-center" id="pay_com_filter" title="Submit" style="width: 32px; height: 31px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l2.552 2.55 5.92-5.903z"/>
                                </svg>
                            </button>
                        
                            <button type="button" class="btn btn-danger btn-sm d-flex align-items-center justify-content-center" id="pay_com_reset" title="Reset" style="width: 32px; height: 31px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
                                    <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </form>
        </div>
    <div class="custom_card">
        <div class="d-flex align-items-center justify-content-between">
            <h6 class="card_heading">Payment List</h6>
            <!--<button class="btn btn-primary PayAmountModal_open mb-2">Add Payment</button>-->
        </div>
        <div class="table-responsive patmentTb">
            <table class="table table-success table-striped table-bordered datatable paymentTab">
                <thead>
                    <tr class="table-info">
                        <th scope="col">Sl.</th>
                        <th scope="col">Host</th>
                        <th scope="col">Date & Time</th>
                        <th scope="col">Venue</th>
                        <th scope="col">Event Type</th>
                        <th scope="col">Amount</th>
                        <th scope="col">Payment</th>
                        <th scope="col">Due</th>
                        <th scope="col">View History</th>
                        <th scope="col">Add Payment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // echo "SELECT cg.ID AS GAME_JOIN_ID,cg.USER_ID,cg.GAME_ID,cg.PRICE,cg.CURRENCY,cg.STATUS AS GAME_JOIN_STATUS,cg.CREATED_AT AS GAME_JOIN_CREATED_AT,ce.ID AS EVENT_ID,ce.EVENT_DATE,ce.EVENT_TIME,ce.EVENT_VENUE,ce.EVENT_COST AS EVENT_PRICE,ce.EVENT_CURRENCY AS EVENT_CURRENCY,ce.STATUS AS EVENT_STATUS,ce.CREATED_AT AS EVENT_CREATED_AT FROM ca_gamejoin AS cg INNER JOIN ca_events AS ce ON cg.GAME_ID = ce.ID WHERE cg.USER_ID = '".$_SESSION['user_id']."' AND cg.STATUS = 'Y' AND ce.STATUS = 'Completed'";
                    $select_game = mysqli_query($conn,"SELECT cg.ID AS GAME_JOIN_ID,cg.USER_ID,cg.GAME_ID,cg.PRICE,cg.CURRENCY,cg.STATUS AS GAME_JOIN_STATUS,cg.CREATED_AT AS GAME_JOIN_CREATED_AT,ce.ID AS EVENT_ID,ce.HOST_NAME AS HOST_NAME,ce.EVENT_DATE,ce.EVENT_TIME,ce.EVENT_VENUE,ce.EVENT_COST AS EVENT_PRICE,ce.EVENT_CURRENCY AS EVENT_CURRENCY,ce.STATUS AS EVENT_STATUS,ce.CREATED_AT AS EVENT_CREATED_AT, ce.EVENT_CATEGORY as EVENT_CATEGORY  FROM ca_gamejoin AS cg INNER JOIN ca_events AS ce ON cg.GAME_ID = ce.ID WHERE cg.USER_ID = '".$_SESSION['user_id']."' AND cg.STATUS = 'Y' AND ce.STATUS = 'Completed' AND ce.HOST_ID = '$host_id' AND YEAR(ce.EVENT_DATE) = '$currentYear' AND MONTH(ce.EVENT_DATE) = '$currentMonth'   ORDER BY ce.EVENT_DATE DESC, ce.EVENT_TIME DESC
");
                    $count_game = mysqli_num_rows($select_game);
                    if($count_game>0)
                    {
                        $totalAmount = 0;
                        $totalPayment = 0;
                        $totalDue = 0;
                        $ss = 1;
                    while($fetch_games = mysqli_fetch_assoc($select_game))
                    {
                        // echo "<pre>";
                        // print_r($fetch_games);
                        // echo "select SUM(AMOUNT) as Total from ca_payment where USER_ID='".$_SESSION['user_id']."' and STATUS!='R'";
                        // $fetch_games['PRICE'].'-'.$fetchPayment['Total'];
                        $selectPayment = mysqli_query($conn,"select SUM(AMOUNT) as Total from ca_payment where USER_ID='".$_SESSION['user_id']."' and GAME_ID='".$fetch_games['GAME_ID']."' and STATUS!='R'");
                        $fetchPayment = mysqli_fetch_assoc($selectPayment);
                        $dueAmount = $fetch_games['PRICE'] - $fetchPayment['Total'];
                        $totalAmount = $totalAmount + $fetch_games['PRICE'];
                        $totalPayment = $totalPayment + $fetchPayment['Total'];
                        $totalDue = $totalDue + $dueAmount;
                        
                        $selectPaymentStatus = mysqli_query($conn,"select * from ca_payment where USER_ID='".$_SESSION['user_id']."' and GAME_ID='".$fetch_games['GAME_ID']."'");
                        $countPaymentStatus = mysqli_num_rows($selectPaymentStatus);
                        $fetchPaymentStatus = mysqli_fetch_assoc($selectPaymentStatus);
                        
                        // Default button values
                        $payBtnText = "Pay";
                        $payBtnClass = "PayAmountModal_open btn-primary";
                        $payBtnDisabled = "";
                        
                        // If payment record exists
                        if ($countPaymentStatus > 0) {
                            if ($fetchPaymentStatus['STATUS'] === 'N') {
                                // Paid but not approved
                                $payBtnText = "Pending Approval";
                                $payBtnClass = "btn-warning"; // no modal open class
                            } elseif ($fetchPaymentStatus['STATUS'] === 'Y') {
                                // Approved
                                $payBtnText = "Approved";
                                $payBtnClass = "btn-success"; // different color
                                $payBtnDisabled = "disabled"; // disable completely
                            }
                            elseif ($fetchPaymentStatus['STATUS'] === 'R') {
                                // Approved
                                $payBtnText = "Rejected! Pay Again";
                                $payBtnClass = "PayAmountModal_open btn-danger"; // different color
                            }
                        }

                    ?>
                        <tr>
                            <th scope="row"><?=$ss?></th>
                            <td><?=$fetch_games['HOST_NAME']?></td>
                            <td><?=$fetch_games['EVENT_DATE'].' '.$fetch_games['EVENT_TIME']?></td>
                            <td><?=$fetch_games['EVENT_VENUE']?></td>
                            <td><?=$fetch_games['EVENT_CATEGORY']?></td>
                            <td><?=$fetch_games['CURRENCY'].' '.$fetch_games['PRICE']?></td>
                            <td><?=$fetch_games['CURRENCY'].' '.$fetchPayment['Total']?></td>
                            <td><?=$fetch_games['CURRENCY'].' '.$dueAmount?></td>
                            <td><button class='btn view_btn' data-id="<?=$fetch_games['GAME_ID']?>" data-user-id="<?=$_SESSION['user_id']?>"><i class='fa-regular fa-eye'></i></button></td>
                            <!--<td><button class="btn btn-primary PayAmountModal_open mb-2" data-id="<?=$fetch_games['GAME_ID']?>" data-user-id="<?=$_SESSION['user_id']?>">Pay</button></td>-->
                            <td><button class="btn <?=$payBtnClass?> mb-2"
                                <?=$payBtnDisabled?>
                                data-id="<?=$fetch_games['GAME_ID']?>"
                                data-user-id="<?=$_SESSION['user_id']?>">
                            <?=$payBtnText?>
                        </button></td>
                        </tr>
                    <?php
                        $ss++;
                    }
                    }
                    else
                    {
                    ?>
                        <p>No Record(s)</p>
                    <?php
                    }
                    ?>
                    <!--<tr>-->
                    <!--    <th scope="row">2</th>-->
                    <!--    <td>Sun, 22 Dec 2024, 11:00 AM - 10:00 PM</td>-->
                    <!--    <td>Casa Badminton Club</td>-->
                    <!--    <td>$10</td>-->
                    <!--    <td>$10</td>-->
                    <!--    <td>$0</td>-->
                    <!--    <td><span class="green">Ok</span></td>-->
                    <!--</tr>-->
                    <!--<tr>-->
                    <!--    <th scope="row">3</th>-->
                    <!--    <td>Sun, 22 Dec 2024, 11:00 AM - 10:00 PM</td>-->
                    <!--    <td>Casa Badminton Club</td>-->
                    <!--    <td>$10</td>-->
                    <!--    <td>$10</td>-->
                    <!--    <td>$0</td>-->
                    <!--    <td><span class="green">Ok</span></td>-->
                    <!--</tr>-->
                    <!--<tr>-->
                    <!--    <th scope="row">4</th>-->
                    <!--    <td>Sun, 22 Dec 2024, 11:00 AM - 10:00 PM</td>-->
                    <!--    <td>Casa Badminton Club</td>-->
                    <!--    <td>$10</td>-->
                    <!--    <td>$10</td>-->
                    <!--    <td>$0</td>-->
                    <!--    <td><span class="green">Ok</span></td>-->
                    <!--</tr>-->
                    <!--<tr>-->
                    <!--    <th scope="row">5</th>-->
                    <!--    <td>Sun, 22 Dec 2024, 11:00 AM - 10:00 PM</td>-->
                    <!--    <td>Casa Badminton Club</td>-->
                    <!--    <td>$10</td>-->
                    <!--    <td>$10</td>-->
                    <!--    <td>$0</td>-->
                    <!--    <td><span class="green">Ok</span></td>-->
                    <!--</tr>-->
                    <tr class="table-dark">
                        <th class="text-start" colspan="5">Total:</th>
                        <td><?=$totalAmount?></td>
                        <td><?=$totalPayment?></td>
                        <td><?=$totalDue?></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!------Player-Modal----->
<section class="customModal_wrap PayAmountModal">
    <div class="customModal_body">
        <h6 class="customModal_head">Enter Your Payment Details</h6>
        <button class="customModal_close btn PayAmountModal_close">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="customModal_content">
            <form>
                <div class="d-flex flex-column align-items-center justify-content-end gap-2">
                    <select class="form-control game_dt">
                        <option value=''>Select Game</option>
                    <?php
                    // echo "SELECT * FROM `ca_gamejoin` where USER_ID='".$_SESSION['userid']."'";
                       $select_joined = mysqli_query($conn, "
                                SELECT ce.ID, ce.EVENT_DATE, ce.EVENT_TIME, ce.EVENT_VENUE, ce.HOST_NAME 
                                FROM ca_gamejoin cg
                                INNER JOIN ca_events ce ON cg.GAME_ID = ce.ID
                                WHERE cg.USER_ID = '" . $_SESSION['user_id'] . "' 
                                  AND cg.STATUS = 'Y' 
                                  AND ce.STATUS = 'Completed'
                                  AND ce.HOST_ID = '$host_id'
                                ORDER BY ce.EVENT_DATE DESC, ce.EVENT_TIME DESC
                            ");
                            
                            while ($fetch_events = mysqli_fetch_assoc($select_joined)) {
                                ?>
                                <option value="<?= $fetch_events['ID'] ?>">
                                    <?= $fetch_events['EVENT_DATE'] . ' ' . $fetch_events['EVENT_TIME'] . ' ' . $fetch_events['EVENT_VENUE'] . ' (' . $fetch_events['HOST_NAME'] . ')' ?>
                                </option>
                                <?php
                            }                    
                    ?>
                        
                    </select>
                    <div class="d-flex align-items-center justify-content-end gap-2">
                        <span id="tot_amnt">Total Amount: <strong>$0</strong></span>
                        <span id="due">Due: <strong>$0</strong></span>
                    </div>
                   <input type="hidden" id="user_id" value="<?=$_SESSION['user_id']?>"/>
                   <input type="hidden" id="due_amt" value=""/>
                </div>
                <div class="row">
                    <div class="col-md-6 col-12 mb-3">
                        <label for="Amount" class="form-label">Amount<span>*</span></label>
                        <input type="number" class="form-control" id="Amount" placeholder="Enter Payment Amount">
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="date" class="form-label">Date<span>*</span></label>
                        <input type="date" class="form-control" id="date" placeholder="Enter Payment date" value="<?=$currentDate?>">
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="time" class="form-label">Time<span>*</span></label>
                        <input type="time" class="form-control" id="time" placeholder="Enter Payment time" value="<?=$currentTime?>">
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <label for="paymentType" class="form-label">Payment Type<span>*</span></label>
                        <select class="form-select form-control" id="paymentType" aria-label="">
                            <option value="Interac">Interac</option>
                            <option value="Cash">Cash</option>
                            <!--<option value="Checkbook">Checkbook</option>-->
                        </select>
                    </div>
                    
                    <div class="col-md-12 col-12 mb-3">
                        <label for="Message" class="form-label">Any Payment Details (Optional)<span></span></label>
                        <textarea class="form-control" id="details" rows="1" placeholder="Enter Any Payment Details"></textarea>
                    </div>

                    <div class="col-md-12 col-12 mb-3">
                        <label for="Message" class="form-label">Message (Optional)<span></span></label>
                        <textarea class="form-control" id="Message" rows="1" placeholder="text....."></textarea>
                    </div>

                    <div class="d-flex align-items-center justify-content-center mt-2">
                        <button type="button" class="btn btn-primary save_payment">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<section class="customModal_wrap hostgameview_modal">
    <div class="customModal_body">
        <h6 class="customModal_head">View History</h6>
        <button class="customModal_close btn">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="customModal_content">
            <hr />
            <div class="" id="playerList">
                

            </div>

        </div>
    </div>
</section>