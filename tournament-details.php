<style>
    .popup-message-content h3 {

        color: #198754;

        /* Success Green */

        font-weight: 700;

        margin-bottom: 20px;

    }



    .popup-message-content h4 {

        color: #333;

        font-weight: 600;

        margin-top: 25px;

        border-bottom: 1px solid #eee;

        padding-bottom: 5px;

    }



    .popup-message-content ul {

        list-style: none;

        padding-left: 0;

    }



    .popup-message-content ul li {

        padding: 5px 0;

        position: relative;

        padding-left: 20px;

    }



    .popup-message-content ul li::before {

        content: "•";

        color: #0d6efd;

        /* Blue bullet */

        font-weight: bold;

        position: absolute;

        left: 0;

    }



    /* Style for the Payment details if inside a list */

    .popup-message-content strong {

        color: #555;

    }



    /* Custom styling to match your industrial form design */

    .tournamentdet_modal .modal-content {

        border-radius: 12px;

        border: none;

        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);

    }



    .tournamentdet_modal .modal-header {

        border-bottom: 2px solid #eee;

        padding: 20px 30px;

    }



    .tournamentdet_modal .modal-title {

        font-weight: bold;

        font-size: 1.3rem;

    }



    .tournamentdet_modal .info-box {

        background: #f8f9fa;

        border-radius: 6px;

        padding: 15px;

        font-size: 0.82rem;

        color: #444;

    }



    .form-label-custom {

        font-size: 0.85rem;

        font-weight: bold;

        color: #333;

    }
</style>

<?php

session_start();

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}



// 1. Include Database Connection

include "dbConnection_PDO.php";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [

    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

    PDO::ATTR_EMULATE_PREPARES   => false,

];



$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;



if ($event_id <= 0) {

    header("Location: index.php");

    exit();
}



try {

    $pdo = new PDO($dsn, $user, $pass, $options);




    // --- FETCH TOURNAMENT DETAILS ---
    $sql = "SELECT e.*, b.IMGAE, 
            (SELECT COUNT(ID) FROM to_teams WHERE TOURNAMENT_ID = e.ID) as joined_count 
            FROM to_tournaments e 
            LEFT JOIN to_tournamet_banners b ON e.ID = b.EVENTS_ID 
            WHERE e.ID = :id AND e.STATUS = 'Active' LIMIT 1";



    $stmt = $pdo->prepare($sql);

    $stmt->execute(['id' => $event_id]);

    $event = $stmt->fetch();



    if (!$event) {

        die("Tournament not found.");
    }



    // --- NEW: FETCH DYNAMIC MODAL MESSAGES FROM to_tournamnt_message ---

    $msgStmt = $pdo->prepare("SELECT * FROM to_tournamnt_message WHERE TOURNAMENT_ID = :tid");

    $msgStmt->execute(['tid' => $event_id]);

    $msg = $msgStmt->fetch();



    // Prepare Modal Display Variables with Fallbacks

    $dispAmt      = !empty($msg['AMOUNT']) ? $msg['AMOUNT'] : number_format($event['EVENT_COST'], 2);

    $dispPayId    = !empty($msg['PAYMENT_ID']) ? $msg['PAYMENT_ID'] : 'casaclubpayment1@gmail.com';

    $dispDeadline = !empty($msg['PAYMENT_DEADLINE']) ? date("l, d F, Y", strtotime($msg['PAYMENT_DEADLINE'])) : date("l, d F, Y", strtotime($event['EVENT_DATE'] . ' -1 days'));

    $dispReport   = !empty($msg['REPORTING_TIME']) ? date("h:i A", strtotime($msg['REPORTING_TIME'])) : date("h:i A", strtotime($event['EVENT_TIME']));

    $dispStart    = !empty($msg['MATCH_START_TIME']) ? date("h:i A", strtotime($msg['MATCH_START_TIME'])) : "30 mins after reporting";

    $dispDraw     = !empty($msg['DRAW_ANNOUNCEMENT']) ? date("l, d F, Y", strtotime($msg['DRAW_ANNOUNCEMENT'])) : "Shared via email";

    $dispShuttle  = !empty($msg['SHUTTLE_TYPE']) ? $msg['SHUTTLE_TYPE'] : "Feather";

    $dispFormat   = !empty($msg['MATCH_FORMAT']) ? $msg['MATCH_FORMAT'] : "Best of 3 games format";



    // --- FETCH LOGGED IN USER DETAILS ---

    $isLoggedIn = isset($_SESSION['user_id']);

    $uData = [

        'name' => '',

        'email' => '',

        'phone' => '',

        'dob' => date('Y-m-d'),

        'gender' => 'Male',

        'skill' => 'Beginner',

        'city' => 'GTA',

        'country' => 'Canada',   // Default

        'province' => 'Ontario', // Default

        'area' => ''             // Default

    ];



    if ($isLoggedIn) {

        $uStmt = $pdo->prepare("SELECT * FROM ca_users WHERE ID = :uid LIMIT 1");

        $uStmt->execute(['uid' => $_SESSION['user_id']]);

        $userRow = $uStmt->fetch();

        if ($userRow) {

            $uData['name']   = $userRow['NAME'] ?? $userRow['full_name'] ?? '';

            $uData['email']  = $userRow['EMAIL'] ?? '';

            $uData['phone']  = $userRow['WHATSAPP_NUMBER'] ?? '';

            $uData['dob']    = !empty($userRow['DOB']) ? $userRow['DOB'] : date('Y-m-d');

            $uData['gender'] = $userRow['GENDER'] ?? 'Male';

            $uData['skill']  = $userRow['LEVEL'] ?? 'Beginner'; // Note: Check if column is LEVEL or SKILL_LEVEL

            $uData['city']   = $userRow['CITY'] ?? 'GTA';

            // ADD THESE THREE LINES:

            $uData['country']  = $userRow['COUNTRY'] ?? 'Canada';

            $uData['province'] = $userRow['PROVINCE'] ?? 'Ontario';

            $uData['area']     = $userRow['AREA'] ?? '';
        }
    }

    $formattedDate = date("l, d F, Y", strtotime($event['EVENT_DATE']));

    $formattedTime = date("h:i A", strtotime($event['EVENT_TIME']));

    $bannerPath = !empty($event['IMGAE']) ? "admin/assets/images/tournaments_banner/" . $event['IMGAE'] : "assets/images/default-banner.jpg";



    $eventType = strtolower($event['EVENT_TYPE']);

    $isDoubles = (strpos($eventType, 'double') !== false);

    $genderCat = strtolower($event['GENDER_CATEGORY']);

    $defaultGender = (strpos($genderCat, 'women') !== false || strpos($genderCat, 'female') !== false) ? "Female" : "Male";

    $today = date('Y-m-d');
} catch (PDOException $e) {

    die("Database Error: " . $e->getMessage());
}



include "includes/header.php";

?>
<?php
// Registration Open/Closed Logic 
$isRegistrationOpen = true;
if (!empty($event['CANCEL_DATE'])) {
    try {
        $nowEst = new DateTime('now', new DateTimeZone('America/New_York'));
        $cTime = !empty($event['CANCEL_TIME']) ? $event['CANCEL_TIME'] : '10:00:00';
        $cancelEst = new DateTime($event['CANCEL_DATE'] . ' ' . $cTime, new DateTimeZone('America/New_York'));

        if ($nowEst >= $cancelEst) {
            $isRegistrationOpen = false;
        }
    } catch (Exception $e) {
        $isRegistrationOpen = true;
    }
}
?>


<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>



<section class="tournament_Details bothSide_gap">

    <div class="cust_container">

        <div class="row">

            <div class="col-lg-6 col-md-12 col-12">

                <div class="image">

                    <img src="<?php echo $bannerPath; ?>" class="img" alt="tournament image" />

                </div>

                <?php if ($isRegistrationOpen): ?>
                    <div class="openBtn btn-info rounded text-white">
                        <span>Registration open</span>
                    </div>
                <?php else: ?>
                    <div class="openBtn rounded" style="background: #475569 !important; color: #cbd5e1 !important; cursor: not-allowed !important; opacity: 0.8;">
                        <span>Registration Closed</span>
                    </div>
                <?php endif; ?>

            </div>

            <div class="col-lg-6 col-md-12 col-12">

                <div class="content">

                    <h4 class="name"><?php echo htmlspecialchars($event['CUP_NAME'] ?: $event['HOST_NAME']); ?></h4>

                    <div class="desc subHead"><?php echo ($event['EVENT_DESCRIPTION']); ?></div>

                    <div class="desc category-line">
                        <div class="tournamentCardCol">
                            <div class="tournamentCardIcon">
                                <i class="fa-solid fa-user-group"></i>
                            </div>
                            <div class="tournamentCardTxt">
                                <span>
                                    <?php
                                    // Removes any existing "'s" and formats as Male-Doubles-Open
                                    $gender = str_replace("'s", "", $event['GENDER_CATEGORY']);
                                    echo $gender . '-' . $event['EVENT_TYPE'] . '-' . $event['EVENT_CATEGORY'];
                                    ?>
                                </span>
                            </div>
                        </div>

                    </div>

                    <div class="datelocation tournamentCardFlex" style="display: flex; align-items: center; gap: 8px; column-gap: 20px; margin-bottom: 15px;">
                        <div class="tournamentCardCol">
                            <div class="tournamentCardIcon">
                                <i class="fa fa-calendar-alt" style="color: #0056b3; width: 16px;"></i>
                            </div>
                            <div class="tournamentCardTxt">
                                <span><?php echo $formattedDate; ?></span>
                            </div>
                        </div>

                        <div class="tournamentCardCol">
                            <div class="tournamentCardIcon">
                                <i class="fa fa-clock" style="color: #0056b3; width: 16px;"></i>
                            </div>
                            <div class="tournamentCardTxt">
                                <span><?php echo $formattedTime; ?></span>
                            </div>
                        </div>

                    </div>

                    <div class="datelocation">
                        <div class="tournamentCardCol">
                            <div class="tournamentCardIcon">
                                <i class="fa fa-map-marker-alt"></i>
                            </div>
                            <div class="tournamentCardTxt">
                                <span><?php echo htmlspecialchars($event['EVENT_VENUE']); ?>, <?php echo $event['EVENT_CITY']; ?> <?php echo $event['EVENT_COUNTRY']; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="amount tournamentCardFlex" style="display: flex; align-items: center; gap: 8px; column-gap: 20px; margin-bottom: 15px;">
                        <div class="tournamentCardCol">
                            <div class="tournamentCardIcon">
                                <i class="fa-solid fa-comment-dollar"></i>
                            </div>
                            <div class="tournamentCardTxt">
                                <strong><?php echo number_format($event['EVENT_COST'], 2); ?></strong>
                                <span>Per Player</span>
                            </div>
                        </div>

                        <div class="tournamentCardCol">
                            <div class="tournamentCardIcon">
                                <i class="fa-solid fa-feather-pointed"></i>
                            </div>
                            <div class="tournamentCardTxt">
                                <strong>Birdie:</strong>
                                <span> Feather</span>
                            </div>
                        </div>

                    </div>


                    <div class="joined-status desc">
                        <div class="tournamentCardCol">
                            <div class="tournamentCardIcon">
                                <i class="fa fa-check-circle"></i>
                            </div>
                            <div class="tournamentCardTxt">
                                <span><?php echo $event['joined_count']; ?> teams joined</span>
                            </div>
                        </div>
                    </div>

<?php if ($isRegistrationOpen): ?>
    <!-- Active Button -->
    <button type="button" data-bs-toggle="modal" data-bs-target="#tournamentRegis" class="btn btn-outline-secondary login-btn w-100 mb-3">
        Confirm My Spot
    </button>
<?php else: ?>
    <!-- Disabled Button -->
    <button type="button" class="btn btn-secondary w-100 mb-3" disabled style="cursor: not-allowed !important; background-color: #6c757d !important; border-color: #6c757d !important; color: #fff !important;">
        Registration Closed
    </button>
<?php endif; ?>
                    <h4 class="amount" style="text-decoration: underline;">Overview</h4>

                    <div class="desc" style="line-height: 1.6; color: #555;">

                        <?php echo ($event['EVENT_MESSAGE']); ?>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>





<!-- tournament-registration-modal -->

<div class="modal fade tournamentdet_modal" id="tournamentRegis" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">

    <div class="modal-dialog <?php echo $isDoubles ? 'modal-xl' : 'modal-lg'; ?> modal-dialog-scrollable">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">Register Request Now</h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" style="font-size: 0.8rem;"></button>

            </div>

            <div class="modal-body p-4">

                <form id="regForm">

                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">

                    <input type="hidden" name="is_doubles" value="<?php echo $isDoubles ? '1' : '0'; ?>">





                    <!-- New Hidden DB Fields -->

                    <input type="hidden" name="EMAIL_PERMISSION" value="Yes">

                    <input type="hidden" name="CALL_PERMISSION" value="Yes">

                    <input type="hidden" name="GAMES" value="Badminton">

                    <input type="hidden" name="ADDRESS" value="N/A">

                    <input type="hidden" name="CURRENCY" value="CAD">

                    <input type="hidden" name="TIMEZONE_OFFSET" value="GMT -5:00 EST">

                    <input type="hidden" name="USERTYPE" value="Player">

                    <input type="hidden" name="REFFERAL_SOURCE" value="<?php echo $event['HOST_ID']; ?>">

                    <div style="display:none;"><input type="text" name="b_username"></div>



                    <!-- Team Name Row -->

                    <div class="row mb-4 align-items-center bg-light p-2 rounded">

                        <label class="col-sm-4 form-label-custom">Team Name / Group*</label>

                        <div class="col-sm-8">

                            <input type="text" name="team_name" id="team_name" class="form-control" placeholder="Enter your team name" required>

                        </div>

                    </div>



                    <div class="row g-4">

                        <!-- Player 1 Column -->

                        <div class="<?php echo $isDoubles ? 'col-md-6 border-end' : 'col-md-12'; ?>">

                            <h6 class="fw-bold mb-3 text-primary border-bottom pb-2" style="font-size: 1rem;">

                                Player 1 Details <?php echo $isLoggedIn ? '(Profile Loaded)' : ''; ?>

                            </h6>



                            <div class="row mb-2 align-items-center">

                                <label class="col-sm-4 small fw-bold">Name*</label>

                                <div class="col-sm-8"><input type="text" name="p1_name" class="form-control form-control-sm p1-req" value="<?php echo htmlspecialchars($uData['name']); ?>" <?php echo $isLoggedIn ? 'readonly' : 'required'; ?>></div>

                            </div>



                            <div class="row mb-2 align-items-center">

                                <label class="col-sm-4 small fw-bold">Contact*</label>

                                <div class="col-sm-8">

                                    <div class="input-group input-group-sm">

                                        <span class="input-group-text bg-success text-white border-success"><i class="fab fa-whatsapp"></i></span>

                                        <input type="tel" name="p1_contact" class="form-control p1-req" value="<?php echo htmlspecialchars($uData['phone']); ?>" <?php echo $isLoggedIn ? 'readonly' : 'required'; ?>>

                                    </div>

                                </div>

                            </div>



                            <div class="row mb-2 align-items-center">

                                <label class="col-sm-4 small fw-bold">Email*</label>

                                <div class="col-sm-8"><input type="email" name="p1_email" class="form-control form-control-sm p1-req" value="<?php echo htmlspecialchars($uData['email']); ?>" <?php echo $isLoggedIn ? 'readonly' : 'required'; ?>></div>

                            </div>



                            <div class="row mb-2 align-items-center">

                                <label class="col-sm-4 small fw-bold">DOB</label>

                                <div class="col-sm-8"><input type="date" name="p1_dob" class="form-control form-control-sm" value="<?php echo $uData['dob']; ?>" <?php echo $isLoggedIn ? 'readonly' : ''; ?>></div>

                            </div>



                            <div class="row mb-2 align-items-center">

                                <label class="col-sm-4 small fw-bold">Gender*</label>

                                <div class="col-sm-8">

                                    <?php if ($isLoggedIn): ?>

                                        <input type="text" name="p1_gender" class="form-control form-control-sm" value="<?php echo $uData['gender']; ?>" readonly>

                                    <?php else: ?>

                                        <div class="d-flex gap-3">

                                            <div class="form-check"><input class="form-check-input" type="radio" name="p1_gender" value="Male" <?php echo ($defaultGender == 'Male') ? 'checked' : ''; ?>><label class="form-check-label small">Male</label></div>

                                            <div class="form-check"><input class="form-check-input" type="radio" name="p1_gender" value="Female" <?php echo ($defaultGender == 'Female') ? 'checked' : ''; ?>><label class="form-check-label small">Female</label></div>

                                        </div>

                                    <?php endif; ?>

                                </div>

                            </div>



                            <div class="row mb-2 align-items-center">

                                <label class="col-sm-4 small fw-bold">Skill Level*</label>

                                <div class="col-sm-8">

                                    <?php if ($isLoggedIn): ?>

                                        <input type="text" name="p1_skill" class="form-control form-control-sm" value="<?php echo $uData['skill']; ?>" readonly>

                                    <?php else: ?>

                                        <select name="p1_skill" class="form-select form-select-sm">

                                            <option value="Beginner" <?php echo $uData['skill'] == 'Beginner' ? 'selected' : ''; ?>>Beginner</option>

                                            <option value="Intermediate" <?php echo $uData['skill'] == 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>

                                            <option value="Advanced" <?php echo $uData['skill'] == 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
                                            <option value="Intermediate +" <?php echo $uData['skill'] == 'Intermediate +' ? 'selected' : ''; ?>>Intermediate +</option> <!-- Added and made default -->


                                        </select>

                                    <?php endif; ?>

                                </div>

                            </div>

                            <!-- START NEW LOCATION FIELDS -->







                            <!-- Country Field -->

                            <div class="row mb-2 align-items-center">

                                <label class="col-sm-4 small fw-bold">Country</label>

                                <div class="col-sm-8">

                                    <select name="p1_country" class="form-select form-select-sm">

                                        <option value="Canada" <?php echo ($uData['country'] == 'Canada') ? 'selected' : ''; ?>>Canada</option>

                                    </select>

                                </div>

                            </div>



                            <!-- Province Field -->

                            <div class="row mb-2 align-items-center">

                                <label class="col-sm-4 small fw-bold">Province</label>

                                <div class="col-sm-8">

                                    <select name="p1_province" class="form-select form-select-sm">

                                        <option value="Ontario" <?php echo ($uData['province'] == 'Ontario') ? 'selected' : ''; ?>>Ontario</option>

                                    </select>

                                </div>

                            </div>

                            <!-- City is now ABOVE Area and is a Dropdown -->
                            <div class="row mb-2 align-items-center">
                                <label class="col-sm-4 small fw-bold">City</label>
                                <div class="col-sm-8">
                                    <select name="p1_city" class="form-select form-select-sm">
                                        <option value="GTA" <?php echo ($uData['city'] == 'GTA' || empty($uData['city'])) ? 'selected' : ''; ?>>GTA</option>
                                        <?php if (!empty($uData['city']) && $uData['city'] !== 'GTA'): ?>
                                            <option value="<?php echo htmlspecialchars($uData['city']); ?>" selected><?php echo htmlspecialchars($uData['city']); ?></option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>


                            <!-- Area Field -->

                            <!-- Area Field -->

                            <div class="row mb-2 align-items-center">

                                <label class="col-sm-4 small fw-bold">Area*</label>

                                <div class="col-sm-8">

                                    <select name="p1_area" class="form-select form-select-sm p1-req" required>

                                        <option value="">-- Select Area --</option>

                                        <?php

                                        $areas = [

                                            "Toronto Districts" => ["Downtown Toronto", "North York", "Scarborough", "Etobicoke", "East York", "York", "Midtown Toronto", "Beaches", "Liberty Village", "Leslieville"],

                                            "Peel Region" => ["Mississauga", "Brampton", "Caledon"],

                                            "York Region" => ["Markham", "Vaughan", "Richmond Hill", "Aurora", "Newmarket", "Whitchurch-Stouffville", "East Gwillimbury", "King City", "Georgina"],

                                            "Durham Region" => ["Pickering", "Ajax", "Whitby", "Oshawa", "Clarington", "Uxbridge", "Scugog", "Brock"],

                                            "Halton Region" => ["Burlington", "Oakville", "Milton", "Halton Hills"]

                                        ];



                                        foreach ($areas as $region => $options) {

                                            echo "<optgroup label=\"$region\">";

                                            foreach ($options as $opt) {

                                                // This comparison will now work because $uData['area'] is populated

                                                $sel = (trim($uData['area']) == trim($opt)) ? 'selected' : '';

                                                echo "<option value=\"$opt\" $sel>$opt</option>";
                                            }

                                            echo "</optgroup>";
                                        }

                                        ?>

                                    </select>

                                </div>

                            </div>

                            <!-- END NEW LOCATION FIELDS -->
                            <!-- 
                            <div class="row mb-2 align-items-center">

                                <label class="col-sm-4 small fw-bold">City</label>

                                <div class="col-sm-8"><input type="text" name="p1_city" class="form-control form-control-sm" value="<?php echo htmlspecialchars($uData['city']); ?>" <?php echo $isLoggedIn ? 'readonly' : ''; ?>></div>

                            </div> -->





                            <div class="form-check mt-3">

                                <input class="form-check-input" type="checkbox" name="p1_exist" value="Y" id="p1_exist" <?php echo $isLoggedIn ? 'checked onclick="return false;"' : ''; ?>>

                                <label class="form-check-label small text-muted" for="p1_exist">

                                    <?php echo $isLoggedIn ? 'Existing Member (Profile Auto-loaded)' : 'Existing Member'; ?>

                                </label>

                            </div>







                        </div>



                        <!-- Player 2 Column (Optional) -->

                        <?php if ($isDoubles): ?>

                            <div class="col-md-6">

                                <h6 class="fw-bold mb-3 text-primary border-bottom pb-2" style="font-size: 1rem;">Player 2 Details (Partner)</h6>



                                <div class="row mb-2 align-items-center">

                                    <label class="col-sm-4 small fw-bold">Name*</label>

                                    <div class="col-sm-8"><input type="text" name="p2_name" class="form-control form-control-sm p2-req" required></div>

                                </div>



                                <div class="row mb-2 align-items-center">

                                    <label class="col-sm-4 small fw-bold">Contact*</label>

                                    <div class="col-sm-8">

                                        <div class="input-group input-group-sm">

                                            <span class="input-group-text bg-success text-white border-success"><i class="fab fa-whatsapp"></i></span>

                                            <input type="tel" name="p2_contact" class="form-control p2-req" required>

                                        </div>

                                    </div>

                                </div>



                                <div class="row mb-2 align-items-center">

                                    <label class="col-sm-4 small fw-bold">Email*</label>

                                    <div class="col-sm-8"><input type="email" name="p2_email" class="form-control form-control-sm p2-req" required></div>

                                </div>



                                <div class="row mb-2 align-items-center">

                                    <label class="col-sm-4 small fw-bold">DOB</label>

                                    <div class="col-sm-8"><input type="date" name="p2_dob" class="form-control form-control-sm" value="<?php echo $today; ?>"></div>

                                </div>



                                <div class="row mb-2 align-items-center">

                                    <label class="col-sm-4 small fw-bold">Gender*</label>

                                    <div class="col-sm-8 d-flex gap-3">

                                        <div class="form-check"><input class="form-check-input" type="radio" name="p2_gender" value="Male" <?php echo ($defaultGender == 'Male') ? 'checked' : ''; ?>><label class="form-check-label small">Male</label></div>

                                        <div class="form-check"><input class="form-check-input" type="radio" name="p2_gender" value="Female" <?php echo ($defaultGender == 'Female') ? 'checked' : ''; ?>><label class="form-check-label small">Female</label></div>

                                    </div>

                                </div>



                                <div class="row mb-2 align-items-center">

                                    <label class="col-sm-4 small fw-bold">Skill Level*</label>

                                    <div class="col-sm-8">

                                        <select name="p2_skill" class="form-select form-select-sm">

                                            <option value="Beginner">Beginner</option>

                                            <option value="Intermediate">Intermediate</option>

                                            <option value="Advanced">Advanced</option>
                                            <option value="Intermediate +" selected>Intermediate +</option> <!-- Added and made default -->


                                        </select>

                                    </div>

                                </div>

                                <!-- START NEW LOCATION FIELDS PLAYER 2 -->

                                <div class="row mb-2 align-items-center">

                                    <label class="col-sm-4 small fw-bold">Country</label>

                                    <div class="col-sm-8">

                                        <select name="p2_country" class="form-select form-select-sm">

                                            <option value="Canada">Canada</option>

                                        </select>

                                    </div>

                                </div>

                                <div class="row mb-2 align-items-center">

                                    <label class="col-sm-4 small fw-bold">Province</label>

                                    <div class="col-sm-8">

                                        <select name="p2_province" class="form-select form-select-sm">

                                            <option value="Ontario">Ontario</option>

                                        </select>

                                    </div>

                                </div>
                                <!-- City is now ABOVE Area and is a Dropdown -->
                                <div class="row mb-2 align-items-center">
                                    <label class="col-sm-4 small fw-bold">City</label>
                                    <div class="col-sm-8">
                                        <select name="p2_city" class="form-select form-select-sm">
                                            <option value="GTA" selected>GTA</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-2 align-items-center">

                                    <label class="col-sm-4 small fw-bold">Area*</label>

                                    <div class="col-sm-8">

                                        <select name="p2_area" class="form-select form-select-sm p2-req" required>

                                            <option value="">-- Select Area --</option>

                                            <optgroup label="Toronto Districts">

                                                <option>Downtown Toronto</option>
                                                <option>North York</option>
                                                <option>Scarborough</option>

                                                <option>Etobicoke</option>
                                                <option>East York</option>
                                                <option>York</option>

                                                <option>Midtown Toronto</option>
                                                <option>Beaches</option>
                                                <option>Liberty Village</option>
                                                <option>Leslieville</option>

                                            </optgroup>

                                            <!-- ... (Rest of optgroups copied same as P1) ... -->

                                            <optgroup label="Peel Region">

                                                <option>Mississauga</option>
                                                <option>Brampton</option>
                                                <option>Caledon</option>

                                            </optgroup>

                                            <optgroup label="York Region">

                                                <option>Markham</option>
                                                <option>Vaughan</option>
                                                <option>Richmond Hill</option>

                                            </optgroup>

                                        </select>

                                    </div>

                                </div>

                                <!-- END NEW LOCATION FIELDS PLAYER 2 -->
                                <!-- 
                                <div class="row mb-2 align-items-center">

                                    <label class="col-sm-4 small fw-bold">City</label>

                                    <div class="col-sm-8"><input type="text" name="p2_city" class="form-control form-control-sm" value="GTA"></div>

                                </div> -->



                                <div class="form-check mt-3">

                                    <input class="form-check-input" type="checkbox" name="p2_exist" value="Y" id="p2_exist">

                                    <label class="form-check-label small text-muted" for="p2_exist">Existing Member</label>

                                </div>

                            </div>

                        <?php endif; ?>

                    </div>



                    <!-- Important Information Box (From your design) -->

                    <!-- <div class="info-box border-top mt-4 pt-3">

                        <p class="fw-bold mb-1">Important Registration Information</p>

                        <p class="mb-2">Ensure all details are correct. Administrator will contact you with login credentials for <strong>casa-games.com</strong> after payment verification.</p>

                        <p class="mb-0">After logging in, players must go to <strong>Preferences Settings</strong> and select their club. This step is required to view and join hosted games and events.</p>

                    </div> -->

                </form>

            </div>

            <div class="modal-footer border-0 p-4">

                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>

                <button type="button" id="btnSubmitRegistration" class="btn btn-primary px-5 fw-bold py-2">Submit Registration</button>

            </div>

        </div>

    </div>

</div>



<!-- Dynamic Thank You / Confirmation Modal -->

<!-- Dynamic Thank You / Confirmation Modal -->

<div class="modal fade" id="tournaRegis_thankyou" data-bs-backdrop="static" tabindex="-1">

    <div class="modal-dialog modal-fullscreen">

        <div class="modal-content">

            <div class="modal-header border-0">

                <h5 class="modal-title text-success fw-bold">Registration Successful!</h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

            </div>

            <div class="modal-body p-0">

                <!-- Keep the ID for PDF Generation -->

                <div class="container py-4" id="printableConfirmation">



                    <div class="row justify-content-center">

                        <div class="col-lg-10 col-md-12">

                            <!-- This is where the Admin-generated content is displayed -->

                            <div class="popup-message-content">

                                <?php

                                if (!empty($event['POPUP_MESSAGE'])) {

                                    // Display the HTML content from the database

                                    echo $event['POPUP_MESSAGE'];
                                } else {

                                    // Fallback if the admin hasn't set a message yet

                                    echo "<h3>Registration Successful!</h3><p>Thank you for registering for " . htmlspecialchars($event['CUP_NAME'] ?: $event['HOST_NAME']) . ".</p>";
                                }

                                ?>

                            </div>



                            <!-- Footer line as per your style -->

                            <div class="text-center mt-5 pt-4 border-top">

                                <p class="fw-bold mb-0">— Casa Games Admin Team 🏸</p>

                            </div>

                        </div>

                    </div>



                </div>

            </div>

            <div class="modal-footer justify-content-center bg-white border-top">

                <button type="button" id="btnDownloadPDF" class="btn btn-danger px-4 me-2">

                    <i class="fa-solid fa-file-pdf"></i> Download PDF

                </button>

                <button type="button" class="btn btn-secondary px-4" onclick="location.href='player-hub.php'">

                    Close

                </button>

            </div>

        </div>

    </div>

</div>

<?php include "includes/footer.php"; ?>



<script>
    document.getElementById('btnSubmitRegistration').addEventListener('click', function() {

        const isDoubles = <?php echo $isDoubles ? 'true' : 'false'; ?>;

        const form = document.getElementById('regForm');

        const submitBtn = this;

        let isValid = true;

        document.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));

        if (document.getElementById('team_name').value.trim() === "") {

            document.getElementById('team_name').classList.add('is-invalid');

            isValid = false;

        }

        document.querySelectorAll('.p1-req').forEach(el => {

            if (el.value.trim() === "") {

                el.classList.add('is-invalid');

                isValid = false;

            }

        });

        if (isDoubles) {

            document.querySelectorAll('.p2-req').forEach(el => {

                if (el.value.trim() === "") {

                    el.classList.add('is-invalid');

                    isValid = false;

                }

            });

        }

        if (!isValid) return alert("Please fill all required fields.");



        submitBtn.disabled = true;

        submitBtn.innerText = "Processing...";



        fetch('api/save_registration.php', {

                method: 'POST',

                body: new FormData(form)

            })

            .then(response => response.json())

            .then(data => {

                if (data.success) {

                    const regModal = bootstrap.Modal.getInstance(document.getElementById('tournamentRegis'));

                    if (regModal) regModal.hide();

                    setTimeout(() => {

                        (new bootstrap.Modal(document.getElementById('tournaRegis_thankyou'))).show();

                    }, 500);

                } else {

                    alert(data.message);

                    submitBtn.disabled = false;

                    submitBtn.innerText = "Submit Registration";

                }

            }).catch(error => {

                alert("Error: " + error.message);

                submitBtn.disabled = false;

                submitBtn.innerText = "Submit Registration";

            });

    });



    document.getElementById('btnDownloadPDF').addEventListener('click', function() {

        const element = document.getElementById('printableConfirmation');

        const opt = {

            margin: 0.5,

            filename: 'Tournament_Registration.pdf',

            image: {

                type: 'jpeg',

                quality: 0.98

            },

            html2canvas: {

                scale: 2

            },

            jsPDF: {

                unit: 'in',

                format: 'letter',

                orientation: 'portrait'

            }

        };

        html2pdf().set(opt).from(element).save();

    });
</script>