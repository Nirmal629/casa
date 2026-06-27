<?php

include('dbConnection.php');

if (isset($_POST['manage_user_action'])) {
    header('Content-Type: application/json');

    $action = $_POST['manage_user_action'];
    $response = ['success' => false, 'message' => 'Invalid request'];

    if ($action === 'toggle_status') {
        $userId = intval($_POST['user_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';

        if (($newStatus === 'Y' || $newStatus === 'N') && $userId > 0) {
            $stmt = $conn->prepare("UPDATE ca_users SET LOG_STATUS = ? WHERE ID = ?");
            $stmt->bind_param("si", $newStatus, $userId);
            $response = $stmt->execute()
                ? ['success' => true, 'message' => 'Status updated']
                : ['success' => false, 'message' => 'Status update failed'];
            $stmt->close();
        }
    }

    if ($action === 'delete_user') {
        $userId = intval($_POST['id'] ?? 0);

        if ($userId > 0) {
            $response = $conn->query("UPDATE ca_users SET DEL_STATUS = 'Y' WHERE ID = $userId")
                ? ['success' => true, 'message' => 'User deleted']
                : ['success' => false, 'message' => 'Delete failed'];
        }
    }

    if ($action === 'add_user') {
        $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
        $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
        $password = password_hash($_POST['password'] ?? '123456', PASSWORD_DEFAULT);
        $level = mysqli_real_escape_string($conn, $_POST['level'] ?? 'Intermediate');
        $verified_level = mysqli_real_escape_string($conn, $_POST['verified_level'] ?? ($_POST['level'] ?? 'Intermediate'));
        $whatsapp_number = mysqli_real_escape_string($conn, $_POST['whatsapp_number'] ?? '');
        $dob = mysqli_real_escape_string($conn, $_POST['dob'] ?? date('Y-m-d'));
        $city = mysqli_real_escape_string($conn, $_POST['city'] ?? 'GTA');
        $country = mysqli_real_escape_string($conn, $_POST['country'] ?? 'Canada');
        $province = mysqli_real_escape_string($conn, $_POST['province'] ?? 'Ontario');
        $currency = mysqli_real_escape_string($conn, $_POST['currency'] ?? 'CAD');
        $timezone = mysqli_real_escape_string($conn, $_POST['timezone'] ?? '-05:00');
        $gender = mysqli_real_escape_string($conn, $_POST['gender'] ?? 'Male');
        $usertype = mysqli_real_escape_string($conn, $_POST['usertype'] ?? 'Player');
        $email_permission = (isset($_POST['email_permission']) && $_POST['email_permission'] == 'Y') ? 'Yes' : 'No';
        $call_permission = (isset($_POST['call_permission']) && $_POST['call_permission'] == 'Y') ? 'Yes' : 'No';
        $premium = (isset($_POST['premium']) && $_POST['premium'] == 'Y') ? 'Y' : 'N';

        $emailCheck = mysqli_query($conn, "SELECT COUNT(*) FROM ca_users WHERE EMAIL = '$email'");
        $emailCount = $emailCheck ? mysqli_fetch_row($emailCheck)[0] : 0;

        if ($emailCount > 0) {
            $response = ['success' => false, 'message' => 'This email is already in use.'];
        } else {
            $sql = "INSERT INTO ca_users (
                        NAME, EMAIL, PASSWORD, LEVEL, VERIFIED_LEVEL,
                        EMAIL_PERMISSION, WHATSAPP_NUMBER, CALL_PERMISSION,
                        DOB, GENDER, CITY, COUNTRY, PROVINCE,
                        CURRENCY, TIMEZONE_OFFSET, USERTYPE, PROFILE_IMAGE, PREMIUM
                    ) VALUES (
                        '$name', '$email', '$password', '$level', '$verified_level',
                        '$email_permission', '$whatsapp_number', '$call_permission',
                        '$dob', '$gender', '$city', '$country', '$province',
                        '$currency', '$timezone', '$usertype', '', '$premium'
                    )";

            $response = mysqli_query($conn, $sql)
                ? ['success' => true, 'message' => 'User registered successfully']
                : ['success' => false, 'message' => 'Failed to add user.'];
        }
    }

    if ($action === 'update_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
        $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
        $email_permission = mysqli_real_escape_string($conn, $_POST['EmailPermission'] ?? '');
        $whatsapp_number = mysqli_real_escape_string($conn, $_POST['number'] ?? '');
        $call_permission = mysqli_real_escape_string($conn, $_POST['CallPermission'] ?? '');
        $dob = mysqli_real_escape_string($conn, $_POST['dateofbirth'] ?? '');
        $gender = mysqli_real_escape_string($conn, $_POST['GenderRadioOptions'] ?? '');
        $city = mysqli_real_escape_string($conn, $_POST['City'] ?? '');
        $country = mysqli_real_escape_string($conn, $_POST['Country'] ?? '');
        $province = mysqli_real_escape_string($conn, $_POST['Province'] ?? '');
        $currency = mysqli_real_escape_string($conn, $_POST['currency'] ?? '');
        $level = mysqli_real_escape_string($conn, $_POST['level'] ?? '');
        $vlevel = mysqli_real_escape_string($conn, $_POST['vlevel'] ?? '');
        $password = mysqli_real_escape_string($conn, $_POST['password'] ?? '');
        $timezone_offset = mysqli_real_escape_string($conn, $_POST['timezone_offset'] ?? '');
        $usertype = mysqli_real_escape_string($conn, $_POST['usertype'] ?? '');
        $Premium = mysqli_real_escape_string($conn, $_POST['Premium'] ?? '');

        $emailCheckQuery = "SELECT COUNT(*) FROM ca_users WHERE EMAIL = '$email' AND ID != $user_id";
        $emailCheckResult = mysqli_query($conn, $emailCheckQuery);
        $emailCount = $emailCheckResult ? mysqli_fetch_row($emailCheckResult)[0] : 0;

        if ($emailCount > 0) {
            $response = ['success' => false, 'message' => 'This email is already in use by another user.'];
        } else {
            if ($password !== '') {
                $sql = "UPDATE ca_users
                    SET NAME = '$name', EMAIL = '$email', PASSWORD='$password', EMAIL_PERMISSION = '$email_permission',
                        WHATSAPP_NUMBER = '$whatsapp_number', CALL_PERMISSION = '$call_permission', DOB = '$dob',
                        GENDER = '$gender', CITY = '$city', COUNTRY = '$country', PROVINCE = '$province',
                        CURRENCY = '$currency', LEVEL='$level', VERIFIED_LEVEL='$vlevel',
                        TIMEZONE_OFFSET = '$timezone_offset', USERTYPE = '$usertype', PREMIUM='$Premium'
                    WHERE ID = $user_id";
            } else {
                $sql = "UPDATE ca_users
                    SET NAME = '$name', EMAIL = '$email', EMAIL_PERMISSION = '$email_permission',
                        WHATSAPP_NUMBER = '$whatsapp_number', CALL_PERMISSION = '$call_permission', DOB = '$dob',
                        GENDER = '$gender', CITY = '$city', COUNTRY = '$country', PROVINCE = '$province',
                        CURRENCY = '$currency', LEVEL='$level', VERIFIED_LEVEL='$vlevel',
                        TIMEZONE_OFFSET = '$timezone_offset', USERTYPE = '$usertype', PREMIUM='$Premium'
                    WHERE ID = $user_id";
            }

            $response = mysqli_query($conn, $sql)
                ? ['success' => true, 'message' => 'User updated successfully']
                : ['success' => false, 'message' => 'Failed to update user. Please try again.'];
        }
    }

    echo json_encode($response);
    exit;
}

include('header.php');

include('sidebar.php');

?>



<style>
    /* 1. COMPACT TABLE */

    .table-compact td,
    .table-compact th {

        padding: 4px 8px !important;

        vertical-align: middle !important;

        font-size: 13px;

        white-space: nowrap;

    }



    .custom-table-toolbar,
    .custom-table-footer {

        display: flex;

        align-items: center;

        gap: 15px;
        /* Space between Button, Dropdown, and Search */

        margin-bottom: 15px;

        flex-wrap: wrap;

    }

    .custom-table-length select {
        min-width: 80px;
    }

    .custom-table-search {
        max-width: 320px;
        width: 100%;
    }

    .custom-table-responsive {
        overflow-x: auto;
        width: 100%;
        -webkit-overflow-scrolling: touch;
    }

    #datatable-userlist {
        min-width: 620px;
    }

    .custom-table-footer {
        justify-content: space-between;
        margin-bottom: 0;
        margin-top: 12px;
    }

    .custom-table-pagination {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .custom-page-btn {
        background: #fff;
        border: 1px solid #ccc;
        border-radius: 3px;
        color: #333;
        min-width: 34px;
        padding: 5px 9px;
    }

    .custom-page-btn.active {
        background: #0088cc;
        border-color: #0088cc;
        color: #fff;
    }

    .custom-page-btn:disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }

    .custom-table-empty {
        display: none;
        padding: 18px;
        text-align: center;
    }

    .user-modal-overlay {
        background: rgba(0, 0, 0, 0.55);
        display: none;
        inset: 0;
        padding: 24px;
        position: fixed;
        z-index: 9999;
    }

    .user-modal-overlay.is-open {
        align-items: center;
        display: flex;
        justify-content: center;
    }

    .user-modal-dialog {
        background: #fff;
        border-radius: 6px;
        box-shadow: 0 20px 55px rgba(0, 0, 0, 0.25);
        max-height: 90vh;
        overflow: hidden;
        width: min(760px, 100%);
    }

    .user-modal-header {
        align-items: center;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        padding: 12px 15px;
    }

    .user-modal-header h4 {
        margin: 0;
    }

    .user-modal-close {
        background: transparent;
        border: 0;
        color: #555;
        font-size: 24px;
        line-height: 1;
        padding: 0 4px;
    }

    .user-modal-body {
        max-height: calc(90vh - 58px);
        overflow-y: auto;
        padding: 15px;
    }

    .user-detail-row,
    .user-form-row {
        display: flex;
        gap: 12px;
        margin-bottom: 10px;
    }

    .user-detail-row label,
    .user-form-row label {
        font-weight: 700;
        min-width: 135px;
    }

    .user-form-row input,
    .user-form-row select {
        flex: 1;
    }

    .user-radio-group {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .user-copy-box {
        background: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 10px;
        white-space: pre-wrap;
    }

    body.user-modal-open {
        overflow: hidden;
    }



    /* 3. ACTION BUTTONS (Icons only) */

    .action-btns {

        display: flex;

        gap: 3px;

    }

    .action-btns .btn {

        padding: 2px 6px;

        font-size: 11px;

    }

    @media (max-width: 767px) {
        .custom-table-toolbar,
        .custom-table-footer,
        .user-detail-row,
        .user-form-row {
            align-items: stretch;
            flex-direction: column;
        }

        .custom-table-search,
        .custom-table-length,
        .custom-table-length select {
            width: 100%;
        }

        .user-modal-overlay {
            padding: 10px;
        }
    }
</style>



<section role="main" class="content-body">

    <header class="page-header">
        <h2>List User's</h2>
    </header>



    <section class="panel">

        <!--<header class="panel-heading">-->

        <!-- <div class="panel-actions">

                <a href="#" class="panel-action panel-action-toggle" data-panel-toggle></a>

                <a href="#" class="panel-action panel-action-dismiss" data-panel-dismiss></a>

            </div> -->

        <!--<h2 class="panel-title">List Users</h2>-->

        <!--</header>-->

        <div class="panel-body">



            <div class="custom-table-toolbar">

                <button type="button" class="btn btn-success btn-sm" id="openAddUserModal">

                    <i class="fa fa-plus"></i>

                </button>

                <label class="custom-table-length" for="usersPageSize" style="margin-bottom:0;">
                    <select class="form-control input-sm" id="usersPageSize">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </label>

                <div style="width: 320px;">
                    <input type="search" class="form-control input-sm custom-table-search" id="usersSearch" placeholder="Search users">
                </div>

            </div>



            <div class="custom-table-responsive">

                <table class="table table-bordered table-striped mb-none table-compact" id="datatable-userlist" style="width:100%">

                    <thead>

                        <tr>

                            <th>SL</th>

                            <th>NAME</th>

                            <th>V LEVEL</th>

                            <th>ACTION</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php

                        $sql = "SELECT * FROM ca_users WHERE DEL_STATUS='N' ORDER BY ID DESC";

                        $result = $conn->query($sql);



                        if ($result && $result->num_rows > 0) {

                            $i = 1;

                            while ($row = $result->fetch_assoc()) {

                                $userData = "User id: {$row['EMAIL']}\nPassword: {$row['PASSWORD']}";
                                $userJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');

                                echo "<tr>";

                                echo "<td>$i</td>";

                                echo "<td>" . htmlspecialchars($row['NAME']) . "</td>";

                                echo "<td>" . htmlspecialchars($row['VERIFIED_LEVEL']) . "</td>";

                                echo "<td>";

                                echo "<div class='action-btns'>";



                                // Status Toggle

                                $statusColor = ($row['LOG_STATUS'] == 'N' ? '#0099e6' : '#47a447');

                                $statusIcon = ($row['LOG_STATUS'] == 'N' ? 'fa-toggle-off' : 'fa-toggle-on');

                                echo "<button class='btn btn-primary user-status-btn' data-user='" . $userJson . "' data-id='{$row['ID']}' data-status='{$row['LOG_STATUS']}' style='background-color:$statusColor; border-color:$statusColor;'><i class='fa $statusIcon'></i></button>";



                                // Copy

                                echo "<button class='btn btn-info copy-user' data-user='" . htmlspecialchars($userData, ENT_QUOTES) . "'><i class='fa fa-copy'></i></button>";



                                // View

                                echo "<button class='btn btn-default user-view-btn' data-user='" . $userJson . "'><i class='fa fa-eye'></i></button>";



                                // Edit

                                echo "<button class='btn btn-warning user-edit-btn' data-user='" . $userJson . "'><i class='fa fa-edit'></i></button>";



                                // Delete

                                echo "<button class='btn btn-danger user-delete-btn' data-user='" . $userJson . "' data-id='{$row['ID']}'><i class='fa fa-trash'></i></button>";



                                echo "</div>";

                                echo "</td>";

                                echo "</tr>";

                                $i++;
                            }
                        }

                        ?>

                    </tbody>

                </table>

                <div class="custom-table-empty" id="usersEmptyMessage">No users found.</div>

            </div>

            <div class="custom-table-footer">
                <div id="usersTableInfo"></div>
                <div class="custom-table-pagination" id="usersPagination"></div>
            </div>

        </div>

    </section>

</section>

<div class="user-modal-overlay" id="addUserModal" aria-hidden="true">
    <div class="user-modal-dialog" role="dialog" aria-modal="true">
        <div class="user-modal-header">
            <h4>Add User</h4>
            <button type="button" class="user-modal-close" data-close-user-modal aria-label="Close">&times;</button>
        </div>
        <div class="user-modal-body">
            <form id="samePageAddUserForm">
                <input type="hidden" name="manage_user_action" value="add_user">
                <input type="hidden" name="password" value="123456">
                <input type="hidden" name="usertype" value="Player">
                <input type="hidden" name="premium" value="N">
                <input type="hidden" name="call_permission" value="Y">
                <input type="hidden" name="email_permission" value="Y">

                <div class="user-form-row"><label>Name</label><input type="text" class="form-control" name="name" id="add_name" required></div>
                <div class="user-form-row"><label>Email</label><input type="email" class="form-control" name="email" required></div>
                <div class="user-form-row"><label>Contact</label><input type="number" class="form-control" name="whatsapp_number" required></div>
                <div class="user-form-row"><label>DOB</label><input type="date" class="form-control" name="dob" value="<?= date('Y-m-d') ?>"></div>
                <div class="user-form-row">
                    <label>Gender</label>
                    <div class="user-radio-group">
                        <label><input type="radio" name="gender" value="Male" checked> Male</label>
                        <label><input type="radio" name="gender" value="Female"> Female</label>
                    </div>
                </div>
                <div class="user-form-row">
                    <label>Skill</label>
                    <select class="form-control" name="level">
                        <option>Beginner</option>
                        <option>Amateur</option>
                        <option selected>Intermediate</option>
                        <option>Intermediate +</option>
                        <option>Advance</option>
                    </select>
                </div>
                <input type="hidden" name="verified_level" value="Intermediate">
                <div class="user-form-row">
                    <label>Country</label>
                    <select class="form-control" name="country">
                        <option value="Canada" selected>Canada</option>
                        <option value="USA">USA</option>
                        <option value="India">India</option>
                    </select>
                </div>
                <div class="user-form-row">
                    <label>Province</label>
                    <select class="form-control" name="province">
                        <option value="Ontario" selected>Ontario</option>
                        <option value="Quebec">Quebec</option>
                        <option value="BC">British Columbia</option>
                    </select>
                </div>
                <div class="user-form-row">
                    <label>City</label>
                    <select class="form-control" name="city">
                        <option value="GTA" selected>GTA</option>
                        <option value="Toronto">Toronto</option>
                        <option value="Mississauga">Mississauga</option>
                    </select>
                </div>
                <input type="hidden" name="currency" value="CAD">
                <input type="hidden" name="timezone" value="-05:00">
                <div class="user-form-row"><label>Referral</label><input type="text" class="form-control" name="referral" placeholder="Existing player name, Online, Club name"></div>

                <button type="submit" class="btn btn-primary">Submit Registration</button>
                <button type="button" class="btn btn-default" data-close-user-modal>Cancel</button>
            </form>
        </div>
    </div>
</div>

<div class="user-modal-overlay" id="copyUserModal" aria-hidden="true">
    <div class="user-modal-dialog" role="dialog" aria-modal="true">
        <div class="user-modal-header">
            <h4>Copy User</h4>
            <button type="button" class="user-modal-close" data-close-user-modal aria-label="Close">&times;</button>
        </div>
        <div class="user-modal-body">
            <div class="user-copy-box" id="copyUserText"></div>
            <br>
            <button type="button" class="btn btn-info" id="copyUserConfirm">Copy</button>
            <button type="button" class="btn btn-default" data-close-user-modal>Close</button>
        </div>
    </div>
</div>

<div class="user-modal-overlay" id="viewUserModal" aria-hidden="true">
    <div class="user-modal-dialog" role="dialog" aria-modal="true">
        <div class="user-modal-header">
            <h4>View User</h4>
            <button type="button" class="user-modal-close" data-close-user-modal aria-label="Close">&times;</button>
        </div>
        <div class="user-modal-body" id="viewUserDetails"></div>
    </div>
</div>

<div class="user-modal-overlay" id="statusUserModal" aria-hidden="true">
    <div class="user-modal-dialog" role="dialog" aria-modal="true">
        <div class="user-modal-header">
            <h4>Change Status</h4>
            <button type="button" class="user-modal-close" data-close-user-modal aria-label="Close">&times;</button>
        </div>
        <div class="user-modal-body">
            <p id="statusUserText"></p>
            <input type="hidden" id="statusUserId">
            <input type="hidden" id="statusCurrentValue">
            <button type="button" class="btn btn-primary" id="confirmStatusChange">Change Status</button>
            <button type="button" class="btn btn-default" data-close-user-modal>Cancel</button>
        </div>
    </div>
</div>

<div class="user-modal-overlay" id="deleteUserModal" aria-hidden="true">
    <div class="user-modal-dialog" role="dialog" aria-modal="true">
        <div class="user-modal-header">
            <h4>Delete User</h4>
            <button type="button" class="user-modal-close" data-close-user-modal aria-label="Close">&times;</button>
        </div>
        <div class="user-modal-body">
            <p id="deleteUserText"></p>
            <input type="hidden" id="deleteUserId">
            <button type="button" class="btn btn-danger" id="confirmDeleteUser">Delete</button>
            <button type="button" class="btn btn-default" data-close-user-modal>Cancel</button>
        </div>
    </div>
</div>

<div class="user-modal-overlay" id="editUserModal" aria-hidden="true">
    <div class="user-modal-dialog" role="dialog" aria-modal="true">
        <div class="user-modal-header">
            <h4>Edit User</h4>
            <button type="button" class="user-modal-close" data-close-user-modal aria-label="Close">&times;</button>
        </div>
        <div class="user-modal-body">
            <form id="samePageEditUserForm">
                <input type="hidden" name="manage_user_action" value="update_user">
                <input type="hidden" name="user_id" id="edit_user_id">

                <div class="user-form-row">
                    <label>Premium</label>
                    <div class="user-radio-group">
                        <label><input type="radio" name="Premium" value="Y"> Yes</label>
                        <label><input type="radio" name="Premium" value="N"> No</label>
                    </div>
                </div>
                <div class="user-form-row"><label>Name</label><input type="text" class="form-control" name="name" id="edit_name" required></div>
                <div class="user-form-row"><label>Email</label><input type="email" class="form-control" name="email" id="edit_email" required></div>
                <div class="user-form-row"><label>Password</label><input type="text" class="form-control" name="password" id="edit_password"></div>
                <div class="user-form-row">
                    <label>Email Permission</label>
                    <div class="user-radio-group">
                        <label><input type="radio" name="EmailPermission" value="Yes"> Yes</label>
                        <label><input type="radio" name="EmailPermission" value="No"> No</label>
                    </div>
                </div>
                <div class="user-form-row"><label>Contact Number</label><input type="number" class="form-control" name="number" id="edit_number" required></div>
                <div class="user-form-row">
                    <label>Call Permission</label>
                    <div class="user-radio-group">
                        <label><input type="radio" name="CallPermission" value="Yes"> Yes</label>
                        <label><input type="radio" name="CallPermission" value="No"> No</label>
                    </div>
                </div>
                <div class="user-form-row"><label>Date of Birth</label><input type="date" class="form-control" name="dateofbirth" id="edit_dob" required></div>
                <div class="user-form-row">
                    <label>Gender</label>
                    <div class="user-radio-group">
                        <label><input type="radio" name="GenderRadioOptions" value="Male"> Male</label>
                        <label><input type="radio" name="GenderRadioOptions" value="Female"> Female</label>
                        <label><input type="radio" name="GenderRadioOptions" value="Kid"> Kid</label>
                    </div>
                </div>
                <div class="user-form-row"><label>City</label><input type="text" class="form-control" name="City" id="edit_city" required></div>
                <div class="user-form-row"><label>Country</label><input type="text" class="form-control" name="Country" id="edit_country" required></div>
                <div class="user-form-row"><label>Province</label><input type="text" class="form-control" name="Province" id="edit_province" required></div>
                <div class="user-form-row">
                    <label>Currency</label>
                    <select class="form-control" name="currency" id="edit_currency" required>
                        <option value="INR">INR - Indian Rupee (India)</option>
                        <option value="CAD">CAD - Canadian Dollar (Canada)</option>
                    </select>
                </div>
                <div class="user-form-row">
                    <label>Level</label>
                    <select class="form-control" name="level" id="edit_level" required>
                        <option value="Beginner">Beginner</option>
                        <option value="Amateur">Amateur</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Intermediate +">Intermediate +</option>
                        <option value="Advance">Advance</option>
                    </select>
                </div>
                <div class="user-form-row">
                    <label>Verified Level</label>
                    <select class="form-control" name="vlevel" id="edit_vlevel" required>
                        <option value="Beginner">Beginner</option>
                        <option value="Amateur">Amateur</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Intermediate +">Intermediate +</option>
                        <option value="Advance">Advance</option>
                    </select>
                </div>
                <div class="user-form-row">
                    <label>Time Zone</label>
                    <select class="form-control" name="timezone_offset" id="edit_timezone" required>
                        <option value="-05:00">(GMT -5:00) Eastern Time (Canada)</option>
                        <option value="+05:30">(GMT +5:30) Indian Standard Time (New Delhi)</option>
                    </select>
                </div>
                <div class="user-form-row">
                    <label>Type</label>
                    <select class="form-control" name="usertype" id="edit_usertype" required>
                        <option value="Player">Player</option>
                        <option value="Host">Host</option>
                        <option value="Trainer">Trainer</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Update User</button>
                <button type="button" class="btn btn-default" data-close-user-modal>Cancel</button>
            </form>
        </div>
    </div>
</div>



<?php include('footer.php'); ?>



<script>
    (function() {
        var table = document.getElementById('datatable-userlist');
        var searchInput = document.getElementById('usersSearch');
        var pageSizeSelect = document.getElementById('usersPageSize');
        var info = document.getElementById('usersTableInfo');
        var pagination = document.getElementById('usersPagination');
        var emptyMessage = document.getElementById('usersEmptyMessage');

        if (!table || !searchInput || !pageSizeSelect || !info || !pagination || !emptyMessage) {
            return;
        }

        var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr'));
        var currentPage = 1;

        function getFilteredRows() {
            var search = searchInput.value.trim().toLowerCase();

            if (!search) {
                return rows;
            }

            return rows.filter(function(row) {
                return row.innerText.toLowerCase().indexOf(search) !== -1;
            });
        }

        function buildPageButton(label, page, disabled, active) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'custom-page-btn' + (active ? ' active' : '');
            button.textContent = label;
            button.disabled = disabled;

            if (!disabled && !active) {
                button.addEventListener('click', function() {
                    currentPage = page;
                    renderUsersTable();
                });
            }

            return button;
        }

        function addDots() {
            var dots = document.createElement('span');
            dots.textContent = '...';
            dots.style.padding = '6px 2px';
            pagination.appendChild(dots);
        }

        function renderPagination(totalPages) {
            pagination.innerHTML = '';

            pagination.appendChild(buildPageButton('Prev', currentPage - 1, currentPage === 1, false));

            var startPage = Math.max(1, currentPage - 2);
            var endPage = Math.min(totalPages, currentPage + 2);

            if (startPage > 1) {
                pagination.appendChild(buildPageButton('1', 1, false, currentPage === 1));
            }

            if (startPage > 2) {
                addDots();
            }

            for (var page = startPage; page <= endPage; page++) {
                pagination.appendChild(buildPageButton(String(page), page, false, currentPage === page));
            }

            if (endPage < totalPages - 1) {
                addDots();
            }

            if (endPage < totalPages) {
                pagination.appendChild(buildPageButton(String(totalPages), totalPages, false, currentPage === totalPages));
            }

            pagination.appendChild(buildPageButton('Next', currentPage + 1, currentPage === totalPages, false));
        }

        function renderUsersTable() {
            var pageSize = parseInt(pageSizeSelect.value, 10);
            var filteredRows = getFilteredRows();
            var totalRows = filteredRows.length;
            var totalPages = Math.max(1, Math.ceil(totalRows / pageSize));

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            var startIndex = (currentPage - 1) * pageSize;
            var endIndex = Math.min(startIndex + pageSize, totalRows);
            var visibleRows = filteredRows.slice(startIndex, endIndex);

            rows.forEach(function(row) {
                row.style.display = 'none';
            });

            visibleRows.forEach(function(row) {
                row.style.display = '';
            });

            emptyMessage.style.display = totalRows ? 'none' : 'block';
            info.textContent = totalRows ?
                'Showing ' + (startIndex + 1) + ' to ' + endIndex + ' of ' + totalRows + ' entries' :
                'Showing 0 entries';

            renderPagination(totalPages);
        }

        searchInput.addEventListener('input', function() {
            currentPage = 1;
            renderUsersTable();
        });

        pageSizeSelect.addEventListener('change', function() {
            currentPage = 1;
            renderUsersTable();
        });

        renderUsersTable();
    })();



    (function() {
        var activeModal = null;
        var currentCopyText = '';

        function openModal(id) {
            var modal = document.getElementById(id);
            if (!modal) {
                return;
            }

            activeModal = modal;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('user-modal-open');
        }

        function closeModal() {
            if (!activeModal) {
                return;
            }

            activeModal.classList.remove('is-open');
            activeModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('user-modal-open');
            activeModal = null;
        }

        function getUser(button) {
            try {
                return JSON.parse(button.getAttribute('data-user') || '{}');
            } catch (error) {
                return {};
            }
        }

        function setRadio(form, name, value) {
            var field = form.querySelector('[name="' + name + '"][value="' + value + '"]');
            if (field) {
                field.checked = true;
            }
        }

        function setSelectValue(select, value) {
            if (!select) {
                return;
            }

            var found = Array.prototype.some.call(select.options, function(option) {
                return option.value === value;
            });

            if (!found && value) {
                select.add(new Option(value, value));
            }

            select.value = value || '';
        }

        function detailRow(label, value) {
            return '<div class="user-detail-row"><label>' + label + '</label><div>' + $('<div>').text(value || '-').html() + '</div></div>';
        }

        $('.copy-user').on('click', function() {
            currentCopyText = $(this).data('user') || '';
            $('#copyUserText').text(currentCopyText);
            openModal('copyUserModal');
        });

        $('#openAddUserModal').on('click', function() {
            openModal('addUserModal');
        });

        $('#add_name').on('input', function() {
            this.value = this.value.replace(/\b\w/g, function(letter) {
                return letter.toUpperCase();
            });
        });

        $('#samePageAddUserForm').on('submit', function(event) {
            event.preventDefault();

            $.ajax({
                url: 'manage_user.php',
                type: 'POST',
                dataType: 'json',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        alert('User registered successfully!');
                        window.location.href = 'manage_user.php';
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Server error.');
                }
            });
        });

        $('#copyUserConfirm').on('click', function() {
            navigator.clipboard.writeText(currentCopyText);
            alert('Copied!');
            closeModal();
        });

        $('.user-view-btn').on('click', function() {
            var user = getUser(this);
            var html = '';

            html += detailRow('Premium', user.PREMIUM === 'Y' ? 'Yes' : 'No');
            html += detailRow('Name', user.NAME);
            html += detailRow('Email', user.EMAIL);
            html += detailRow('Password', user.PASSWORD);
            html += detailRow('Email Permission', user.EMAIL_PERMISSION);
            html += detailRow('Contact Number', user.WHATSAPP_NUMBER);
            html += detailRow('Call Permission', user.CALL_PERMISSION);
            html += detailRow('Date of Birth', user.DOB);
            html += detailRow('Gender', user.GENDER);
            html += detailRow('Country', user.COUNTRY);
            html += detailRow('Province', user.PROVINCE);
            html += detailRow('City', user.CITY);
            html += detailRow('Area', user.AREA);
            html += detailRow('Address', user.ADDRESS);
            html += detailRow('Currency', user.CURRENCY);
            html += detailRow('Time Zone', user.TIMEZONE_OFFSET);
            html += detailRow('Games', user.GAMES);
            html += detailRow('Level', user.LEVEL);
            html += detailRow('Verified Level', user.VERIFIED_LEVEL);
            html += detailRow('Referral', user.REFERRAL);
            html += detailRow('User Type', user.USERTYPE);
            html += detailRow('Log Status', user.LOG_STATUS);
            html += detailRow('Current Rank', user.CURRENT_RANKING);
            html += '<button type="button" class="btn btn-default" data-close-user-modal>Close</button>';

            $('#viewUserDetails').html(html);
            openModal('viewUserModal');
        });

        $('.user-edit-btn').on('click', function() {
            var user = getUser(this);
            var form = document.getElementById('samePageEditUserForm');

            $('#edit_user_id').val(user.ID || '');
            $('#edit_name').val(user.NAME || '');
            $('#edit_email').val(user.EMAIL || '');
            $('#edit_password').val(user.PASSWORD || '');
            $('#edit_number').val(user.WHATSAPP_NUMBER || '');
            $('#edit_dob').val(user.DOB || '');
            $('#edit_city').val(user.CITY || '');
            $('#edit_country').val(user.COUNTRY || '');
            $('#edit_province').val(user.PROVINCE || '');

            setRadio(form, 'Premium', user.PREMIUM || 'N');
            setRadio(form, 'EmailPermission', user.EMAIL_PERMISSION || 'No');
            setRadio(form, 'CallPermission', user.CALL_PERMISSION || 'No');
            setRadio(form, 'GenderRadioOptions', user.GENDER || 'Male');
            setSelectValue(document.getElementById('edit_currency'), user.CURRENCY || 'CAD');
            setSelectValue(document.getElementById('edit_level'), user.LEVEL || 'Beginner');
            setSelectValue(document.getElementById('edit_vlevel'), user.VERIFIED_LEVEL || 'Beginner');
            setSelectValue(document.getElementById('edit_timezone'), user.TIMEZONE_OFFSET || '-05:00');
            setSelectValue(document.getElementById('edit_usertype'), user.USERTYPE || 'Player');

            openModal('editUserModal');
        });

        $('#samePageEditUserForm').on('submit', function(event) {
            event.preventDefault();

            $.ajax({
                url: 'manage_user.php',
                type: 'POST',
                dataType: 'json',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        alert('User updated successfully!');
                        window.location.href = 'manage_user.php';
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Something went wrong! Please try again.');
                }
            });
        });

        $('.user-status-btn').on('click', function() {
            var user = getUser(this);
            var current = $(this).data('status');
            var next = current === 'N' ? 'Y' : 'N';
            $('#statusUserId').val(user.ID || $(this).data('id'));
            $('#statusCurrentValue').val(current);
            $('#statusUserText').text('Change status for ' + (user.NAME || 'this user') + ' to ' + (next === 'Y' ? 'active' : 'inactive') + '?');
            openModal('statusUserModal');
        });

        $('#confirmStatusChange').on('click', function() {
            var id = $('#statusUserId').val();
            var current = $('#statusCurrentValue').val();
            var next = current === 'N' ? 'Y' : 'N';

            $.ajax({
                url: 'manage_user.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    manage_user_action: 'toggle_status',
                    user_id: id,
                    new_status: next
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = 'manage_user.php';
                    } else {
                        alert(response.message || 'Status update failed');
                    }
                }
            });
        });

        $('.user-delete-btn').on('click', function() {
            var user = getUser(this);
            $('#deleteUserId').val(user.ID || $(this).data('id'));
            $('#deleteUserText').text('Delete ' + (user.NAME || 'this user') + '?');
            openModal('deleteUserModal');
        });

        $('#confirmDeleteUser').on('click', function() {
            $.ajax({
                url: 'manage_user.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    manage_user_action: 'delete_user',
                    id: $('#deleteUserId').val()
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = 'manage_user.php';
                    } else {
                        alert(response.message || 'Delete failed');
                    }
                }
            });
        });

        $(document).on('click', '[data-close-user-modal]', closeModal);

        $('.user-modal-overlay').on('click', function(event) {
            if (event.target === this) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    })();
</script>
