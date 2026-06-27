<?php
date_default_timezone_set('America/Toronto');
const DATABASE_NAME='casa_test';
        const USERNAME="casa_test";
        const PASSWORD="casa_test123#";
        
        // Database configuration
        $host = "localhost"; // Database host (e.g., localhost)
        
        // Create connection
        $conn = new mysqli($host, USERNAME, PASSWORD, DATABASE_NAME);
        
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
$currentDate = date('Y-m-d');
$currentTime = date('H:i');

$currentYear = date('Y');
$currentMonth = date('n'); // 1-12 (no leading zero)

        $select_host = mysqli_query($conn,"select * from ca_users where USERTYPE!='Player' and LOG_STATUS='Y' and DEL_STATUS='N'");
        $check_player = mysqli_query($conn,"select * from ca_users where ID='".$_SESSION['user_id']."'");
        $fetch_player = mysqli_fetch_assoc($check_player);
        $premiumStatus = $fetch_player['PREMIUM'];

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
                        <div class="col-auto">
                            <select class="form-select" id="payhost" aria-label="Default select example">
                                <option value="">All</option>
                                <?php
                                while ($fetchUser = mysqli_fetch_assoc($select_host)) {
                                    $selected = ($fetchUser['ID'] == 21) ? 'selected' : ''; // Check if ID is 7
                                ?>
                                    <option value="<?= $fetchUser['ID'] ?>"><?= $fetchUser['NAME'] ?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
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
                            <select class="form-select" id="payyear" aria-label="Default select example">
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
                            <select class="form-select" id="paymonth" aria-label="Default select example">
                                <option value="">Month</option>
                                <?php
                                $months = [
                                    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                ];
                                foreach ($months as $num => $name) {
                                    $selected = ($num == $currentMonth) ? 'selected' : '';
                                    echo "<option value=\"$num\" $selected>$name</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-auto">
                            <button type="button" class="btn btn-primary" id="pay_com_filter">Submit</button>
                            <button type="button" class="btn btn-danger" id="pay_com_reset">Reset</button>
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
                    $select_game = mysqli_query($conn,"SELECT cg.ID AS GAME_JOIN_ID,cg.USER_ID,cg.GAME_ID,cg.PRICE,cg.CURRENCY,cg.STATUS AS GAME_JOIN_STATUS,cg.CREATED_AT AS GAME_JOIN_CREATED_AT,ce.ID AS EVENT_ID,ce.HOST_NAME AS HOST_NAME,ce.EVENT_DATE,ce.EVENT_TIME,ce.EVENT_VENUE,ce.EVENT_COST AS EVENT_PRICE,ce.EVENT_CURRENCY AS EVENT_CURRENCY,ce.STATUS AS EVENT_STATUS,ce.CREATED_AT AS EVENT_CREATED_AT, ce.EVENT_CATEGORY as EVENT_CATEGORY  FROM ca_gamejoin AS cg INNER JOIN ca_events AS ce ON cg.GAME_ID = ce.ID WHERE cg.USER_ID = '".$_SESSION['user_id']."' AND cg.STATUS = 'Y' AND ce.STATUS = 'Completed' AND YEAR(ce.EVENT_DATE) = '$currentYear' AND MONTH(ce.EVENT_DATE) = '$currentMonth'   ORDER BY ce.EVENT_DATE DESC, ce.EVENT_TIME DESC
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