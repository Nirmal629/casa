<?php
include('header.php');
include('sidebar.php');

$host = 'localhost';
$db = 'casa_test';
$user = 'casa_test';
$pass = 'casa_test123#';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8";
$conn = new PDO($dsn, $user, $pass, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$hosts = $conn->query("SELECT ID, NAME FROM ca_users WHERE USERTYPE = 'Host' AND DEL_STATUS = 'N' ORDER BY NAME ASC")->fetchAll();
$countries = $conn->query("SELECT DISTINCT EVENT_COUNTRY FROM to_tournaments WHERE EVENT_COUNTRY != '' ORDER BY EVENT_COUNTRY ASC")->fetchAll(PDO::FETCH_COLUMN);
$provinces = $conn->query("SELECT DISTINCT EVENT_PROVINCE FROM to_tournaments WHERE EVENT_PROVINCE != '' ORDER BY EVENT_PROVINCE ASC")->fetchAll(PDO::FETCH_COLUMN);
$cities = $conn->query("SELECT DISTINCT EVENT_CITY FROM to_tournaments WHERE EVENT_CITY != '' ORDER BY EVENT_CITY ASC")->fetchAll(PDO::FETCH_COLUMN);
$categories = $conn->query("SELECT DISTINCT EVENT_CATEGORY FROM to_tournaments ORDER BY EVENT_CATEGORY ASC")->fetchAll(PDO::FETCH_COLUMN);
$statuses = $conn->query("SELECT DISTINCT STATUS FROM to_tournaments ORDER BY STATUS ASC")->fetchAll(PDO::FETCH_COLUMN);

$where = [];
$params = [];

foreach (
    [
        'f_host' => 't.HOST_ID',
        'f_country' => 't.EVENT_COUNTRY',
        'f_province' => 't.EVENT_PROVINCE',
        'f_city' => 't.EVENT_CITY',
        'f_date' => 't.EVENT_DATE',
        'f_cat' => 't.EVENT_CATEGORY',
        'f_status' => 't.STATUS',
    ] as $key => $column
) {
    if (!empty($_GET[$key])) {
        $where[] = "$column = ?";
        $params[] = $_GET[$key];
    }
}

$whereSql = $where ? " WHERE " . implode(" AND ", $where) : "";
$sql = "SELECT t.*, b.IMGAE as banner_image
        FROM to_tournaments t
        LEFT JOIN to_tournamet_banners b ON t.ID = b.EVENTS_ID
        $whereSql
        ORDER BY t.EVENT_DATE DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$tournaments = $stmt->fetchAll();

$jsonTournaments = [];
foreach ($tournaments as $row) {
    $jsonTournaments[$row['ID']] = $row;
}

$templatePopup = '<h3>Tournament Registration Confirmation</h3>
<p>Thank you for registering for the <strong>[Tournament Name]</strong>. Your registration has been successfully received.</p>
<p>To confirm your participation, please complete the registration payment as per the details below:</p>
<ul>
    <li><strong>Amount:</strong> [Currency] [Amount]</li>
    <li><strong>Payment Method:</strong> E-transfer</li>
    <li><strong>Payment ID:</strong> [Payment ID]</li>
    <li><strong>Payment Deadline:</strong> [Deadline Date]</li>
</ul>
<h4>Event Details</h4>
<ul>
    <li><strong>Category:</strong> [Category] ([Type])</li>
    <li><strong>Match Date(s):</strong> [Date]</li>
    <li><strong>Venue:</strong> [Venue]</li>
    <li><strong>Reporting Time:</strong> [Reporting Time]</li>
    <li><strong>Match Start Time:</strong> [Match Time]</li>
    <li><strong>Draw Announcement:</strong> [Draw Date]</li>
</ul>
<h4>Match Rules</h4>
<ul>
    <li><strong>Scoring Format:</strong> [Match Format]</li>
    <li><strong>Shuttle Type:</strong> [Shuttle]</li>
</ul>
<p>Casa Games Admin Team</p>';

$templateMail = '<p>Dear Participant,</p>
<p>We have not received your payment yet for <strong>[Tournament Name]</strong>. We are pleased to share your login credentials for casa-games.com:</p>
<p><strong>Website:</strong> https://casa-games.com</p>
<p><strong>Username:</strong> [Username]<br><strong>Temporary Password:</strong> [Password]</p>
<hr>
<p><strong>Payment Instructions:</strong></p>
<ul>
    <li>Amount: [Currency] [Amount]</li>
    <li>Payment ID: [Payment ID]</li>
    <li>Deadline: [Deadline Date]</li>
</ul>
<p>Casa Games Admin Team</p>';
?>

<style>
    #filterForm {
        display: flex;
        align-items: center;
        flex-wrap: nowrap;
        gap: 6px;
        overflow-x: auto;
        margin-bottom: 0;
    }

    #filterForm select {
        max-width: fit-content;
        min-width: fit-content;
    }

    .btn-reset {
        background-color: #fff;
        border: 1px solid #ccc;
        color: #333;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 13px;
        text-decoration: none;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .btn-reset:hover {
        background-color: #f5f5f5;
        color: #000;
        text-decoration: none;
    }

    .modal-xl {
        width: 95%;
        max-width: 1180px;
    }

    .modal-body-scroll {
        max-height: calc(100vh - 190px);
        overflow-y: auto;
        padding-right: 18px;
    }

    .ck-editor__editable_inline {
        min-height: 160px;
    }

    .preview-wrapper {
        position: relative;
        display: none;
        margin-top: 10px;
    }

    .preview-wrapper img {
        width: 200px;
        height: 150px;
        object-fit: cover;
        border: 1px solid #ccc;
        border-radius: 4px;
        padding: 3px;
        background: #fff;
    }

    .remove-btn {
        position: absolute;
        top: -10px;
        right: -10px;
        background-color: #d9534f;
        color: white;
        border: 2px solid white;
        border-radius: 50%;
        width: 25px;
        height: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        cursor: pointer;
        z-index: 10;
    }

    #flash-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
    }

    .view-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .view-item {
        border: 1px solid #e5e5e5;
        border-radius: 4px;
        padding: 10px;
        background: #fafafa;
        min-height: 66px;
    }

    .view-label {
        display: block;
        color: #777;
        font-size: 12px;
        margin-bottom: 4px;
    }

    .view-html {
        border: 1px solid #e5e5e5;
        border-radius: 4px;
        padding: 12px;
        margin-bottom: 12px;
        background: #fff;
    }

    #tournamentsTable img {
        width: 50px;
        height: 35px;
        object-fit: cover;
        border: 1px solid #ddd;
        border-radius: 2px;
    }

    .custom-table-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 12px;
    }

    .custom-table-toolbar .left-tools,
    .custom-table-toolbar .right-tools {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .custom-table-toolbar label {
        margin-bottom: 0;
        font-weight: normal;
    }

    .custom-table-toolbar select {
        width: auto;
        display: inline-block;
    }

    .custom-table-search {
        min-width: 240px;
    }

    .custom-table-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 12px;
    }

    .custom-pagination {
        display: flex;
        align-items: center;
        gap: 4px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .custom-pagination button {
        min-width: 34px;
    }

    .custom-pagination button.active {
        background: #0088cc;
        border-color: #0088cc;
        color: #fff;
    }

    @media (max-width: 767px) {

        .custom-table-toolbar,
        .custom-table-footer {
            align-items: stretch;
            flex-direction: column;
        }

        .custom-table-toolbar .left-tools,
        .custom-table-toolbar .right-tools,
        .custom-table-search {
            width: 100%;
        }
    }
</style>

<div id="flash-container"></div>

<section role="main" class="content-body">
    <header class="page-header">
        <h2>Tournament Management</h2>
    </header>

    <div class="panel">
        <div class="panel-body">
            <form method="GET" id="filterForm" action="" class="form-inline">
                <select name="f_host" class="form-control" onchange="this.form.submit()">
                    <option value="">All Hosts</option>
                    <?php foreach ($hosts as $h): ?>
                        <option value="<?php echo h($h['ID']); ?>" <?php echo (@$_GET['f_host'] == $h['ID']) ? 'selected' : ''; ?>><?php echo h($h['NAME']); ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="f_country" class="form-control" onchange="this.form.submit()">
                    <option value="">All Countries</option>
                    <?php foreach ($countries as $country): ?>
                        <option value="<?php echo h($country); ?>" <?php echo (@$_GET['f_country'] == $country) ? 'selected' : ''; ?>><?php echo h($country); ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="f_province" class="form-control" onchange="this.form.submit()">
                    <option value="">All Provinces</option>
                    <?php foreach ($provinces as $prov): ?>
                        <option value="<?php echo h($prov); ?>" <?php echo (@$_GET['f_province'] == $prov) ? 'selected' : ''; ?>><?php echo h($prov); ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="f_city" class="form-control" onchange="this.form.submit()">
                    <option value="">All Cities</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo h($city); ?>" <?php echo (@$_GET['f_city'] == $city) ? 'selected' : ''; ?>><?php echo h($city); ?></option>
                    <?php endforeach; ?>
                </select>

                <input type="date" name="f_date" class="form-control" value="<?php echo h($_GET['f_date'] ?? ''); ?>" onchange="this.form.submit()">

                <select name="f_cat" class="form-control" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo h($cat); ?>" <?php echo (@$_GET['f_cat'] == $cat) ? 'selected' : ''; ?>><?php echo h($cat); ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="f_status" class="form-control" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <?php foreach ($statuses as $st): ?>
                        <option value="<?php echo h($st); ?>" <?php echo (@$_GET['f_status'] == $st) ? 'selected' : ''; ?>><?php echo h($st); ?></option>
                    <?php endforeach; ?>
                </select>

                <a href="tournaments_list.php" class="btn-reset"><i class="fa fa-refresh"></i> Clear Filters</a>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-body">
            <div class="custom-table-toolbar">
                <div class="left-tools">

                    <button type="button" class="btn btn-primary" title="Add Tournament" onclick="openTournamentModal('add')"><i class="fa fa-plus"></i></button>
                    <a href="enrolled_tournaments.php" class="btn btn-info" title="All Enrolled Users"><i class="fa fa-users"></i></a>

                    <div class="">
                        <!-- <label for="customPageSize">View</label> -->
                        <select id="customPageSize" class="form-control input-sm">
                            <option value="15">15</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <!-- <span>records</span> -->
                    </div>
                    <div class="searchbar">
                        <!-- <label for="customTableSearch">Search</label> -->
                        <input type="text" id="customTableSearch" class="form-control input-sm custom-table-search" placeholder="Search tournaments...">
                    </div>
                </div>
                <!-- <div class="right-tools"></div> -->
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped mb-none" id="tournamentsTable" style="width:100%">
                    <thead>
                        <tr>
                            <th width="3%"><input type="checkbox" id="checkAll"></th>
                            <th>Banner</th>
                            <th>Cup Name</th>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Cost</th>
                            <th>Status</th>
                            <th width="150px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tournaments as $row): ?>
                            <tr id="row-<?php echo h($row['ID']); ?>">
                                <td class="text-center"><input type="checkbox" class="row-checkbox" value="<?php echo h($row['ID']); ?>"></td>
                                <td class="text-center">
                                    <?php if (!empty($row['banner_image'])): ?>
                                        <img src="assets/images/tournaments_banner/<?php echo h($row['banner_image']); ?>" alt="Banner">
                                    <?php else: ?>
                                        <span class="text-muted">No image</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo h($row['CUP_NAME']); ?></strong></td>
                                <td><?php echo !empty($row['EVENT_DATE']) ? h(date('d-M-Y', strtotime($row['EVENT_DATE']))) : ''; ?></td>
                                <td><?php echo h($row['EVENT_CATEGORY']); ?></td>
                                <td><?php echo h($row['EVENT_CURRENCY'] ?: '$'); ?> <?php echo number_format((float) $row['EVENT_COST'], 2); ?></td>
                                <td>
                                    <button onclick="toggleStatus(<?php echo h($row['ID']); ?>, '<?php echo h($row['STATUS']); ?>')"
                                        class="btn btn-xs <?php echo ($row['STATUS'] == 'Active') ? 'btn-success' : 'btn-danger'; ?>"
                                        title="Click to toggle status" style="white-space: nowrap;">
                                        <?php echo h($row['STATUS']); ?>
                                    </button>
                                </td>
                                <td class="actions">
                                    <button type="button" class="btn btn-xs btn-info" title="View" onclick="openViewModal(<?php echo h($row['ID']); ?>)"><i class="fa fa-eye"></i></button>
                                    <button type="button" class="btn btn-xs btn-primary" title="Edit" onclick="openTournamentModal('edit', <?php echo h($row['ID']); ?>)"><i class="fa fa-pencil"></i></button>
                                    <button type="button" onclick="deleteTournament(<?php echo h($row['ID']); ?>)" class="btn btn-xs btn-danger" title="Delete"><i class="fa fa-trash-o"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="custom-table-footer">
                <div id="customTableInfo" class="text-muted"></div>
                <ul id="customPagination" class="custom-pagination"></ul>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="tournamentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form class="form-horizontal" id="tournamentForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="tournamentModalTitle">Add Tournament</h4>
                </div>
                <div class="modal-body modal-body-scroll">
                    <input type="hidden" name="action" id="form_action" value="">
                    <input type="hidden" name="event_id" id="event_id" value="">

                    <div class="form-group">
                        <label class="col-md-3 control-label">Host Name <span>*</span></label>
                        <div class="col-md-6"><input type="text" name="host_name" id="host_name" class="form-control" value="Casa Badminton Club Toronto"></div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">Cup Name <span>*</span></label>
                        <div class="col-md-6"><input type="text" name="cup_name" id="cup_name" class="form-control" value="Casa Cup 2026"></div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">Location Details</label>
                        <div class="col-md-2"><select name="event_country" id="event_country" class="form-control">
                                <option value="Canada">Canada</option>
                            </select></div>
                        <div class="col-md-2"><select name="event_province" id="event_province" class="form-control">
                                <option value="Ontario">Ontario</option>
                            </select></div>
                        <div class="col-md-2"><select name="event_city" id="event_city" class="form-control">
                                <option value="GTA">GTA</option>
                            </select></div>
                        <div class="col-md-2">
                            <select name="event_currency" id="event_currency" class="form-control">
                                <option value="CAD">CAD</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">Event Venue <span>*</span></label>
                        <div class="col-md-6"><input type="text" name="event_venue" id="event_venue" class="form-control" value="Epic"></div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">Tournament Config</label>
                        <div class="col-md-2">
                            <select name="event_category" id="event_category" class="form-control">
                                <option value="Open">Open</option>
                                <option value="30+">30+</option>
                                <option value="35+">35+</option>
                                <option value="40+">40+</option>
                            </select>
                            <span class="help-block">Category</span>
                        </div>
                        <div class="col-md-2">
                            <select name="gender_category" id="gender_category" class="form-control">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Mixed">Mixed</option>
                            </select>
                            <span class="help-block">Gender</span>
                        </div>
                        <div class="col-md-2">
                            <select name="skill_level" id="skill_level" class="form-control">
                                <option value="All">All</option>
                                <option value="Adv">Adv</option>
                                <option value="Int+">Int+</option>
                                <option value="Int">Int</option>
                            </select>
                            <span class="help-block">Skill</span>
                        </div>
                        <div class="col-md-2">
                            <select name="event_type" id="event_type" class="form-control">
                                <option value="Single">Single</option>
                                <option value="Doubles">Doubles</option>
                            </select>
                            <span class="help-block">Type</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">Event Schedule</label>
                        <div class="col-md-2"><input type="date" name="event_date" id="event_date" class="form-control" value="2026-03-14"><span class="help-block">Event Date</span></div>
                        <div class="col-md-2"><input type="time" name="from_time" id="from_time" class="form-control" value="10:00"><span class="help-block">From Time</span></div>
                        <div class="col-md-2"><input type="time" name="to_time" id="to_time" class="form-control" value="14:00"><span class="help-block">To Time</span></div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">Registration Freeze</label>
                        <div class="col-md-3"><input type="date" name="freeze_date" id="freeze_date" class="form-control" value="2026-03-13"></div>
                        <div class="col-md-3"><input type="time" name="freeze_time" id="freeze_time" class="form-control" value="10:00"></div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">Costs & Payment</label>
                        <div class="col-md-2"><input type="text" name="event_cost" id="event_cost" class="form-control" value="80"><span class="help-block">System Cost</span></div>
                        <div class="col-md-2"><input type="number" name="amount" id="amount" class="form-control" value="80"><span class="help-block">Display Amount</span></div>
                        <div class="col-md-3"><input type="text" name="payment_id" id="payment_id" class="form-control" value="casaclubpayment1@gmail.com"><span class="help-block">Payment ID</span></div>
                        <div class="col-md-2"><input type="date" name="payment_deadline" id="payment_deadline" class="form-control" value="2026-03-13"><span class="help-block">Deadline</span></div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">Reporting & Draw</label>
                        <div class="col-md-3"><input type="time" name="reporting_time" id="reporting_time" class="form-control" value="09:00"><span class="help-block">Reporting Time</span></div>
                        <div class="col-md-3"><input type="time" name="match_start_time" id="match_start_time" class="form-control" value="10:00"><span class="help-block">Match Start</span></div>
                        <div class="col-md-3"><input type="date" name="draw_announcement" id="draw_announcement" class="form-control" value="2026-03-12"><span class="help-block">Draw Date</span></div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">Match Details</label>
                        <div class="col-md-3">
                            <select name="shuttle_type" id="shuttle_type" class="form-control">
                                <option value="Feather">Feather</option>
                                <option value="Nylon">Nylon</option>
                            </select>
                            <span class="help-block">Shuttle Type</span>
                        </div>
                        <div class="col-md-6"><input type="text" name="match_format" id="match_format" class="form-control" value="Best of 3 games"><span class="help-block">Match Format</span></div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">Event Description</label>
                        <div class="col-md-9"><textarea name="event_description" id="event_description" class="form-control">I Play Every Day But Don't Have a Trophy</textarea></div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">Event Message</label>
                        <div class="col-md-9"><textarea name="event_message" id="event_message" class="form-control">Men's Badminton Tournament in the GTA featuring competitive players from across the region.</textarea></div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">Registration Popup Message</label>
                        <div class="col-md-9"><textarea name="popup_message" id="popup_message" class="form-control"><?php echo h($templatePopup); ?></textarea></div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">Payment/Login Email Template</label>
                        <div class="col-md-9"><textarea name="payment_mail" id="payment_mail" class="form-control"><?php echo h($templateMail); ?></textarea></div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">Banner Image <span id="bannerRequired">*</span></label>
                        <div class="col-md-6">
                            <input type="file" name="banner" id="banner" class="form-control" accept="image/*" onchange="previewImage(this)">
                            <div class="preview-wrapper" id="previewWrapper">
                                <img id="imagePreview" src="" alt="Banner Preview">
                                <div class="remove-btn" onclick="removeImage()">&#10005;</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" id="submitBtn" class="btn btn-primary"><i class="fa fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewTournamentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="viewTournamentTitle">Tournament Details</h4>
            </div>
            <div class="modal-body modal-body-scroll" id="viewTournamentBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.ckeditor.com/ckeditor5/34.0.0/classic/ckeditor.js"></script>
<?php include('footer.php'); ?>

<script src="assets/vendor/bootstrap/js/bootstrap.js"></script>
<script>
    var tournaments = <?php echo json_encode($jsonTournaments, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var defaults = {
        HOST_NAME: 'Casa Badminton Club Toronto',
        CUP_NAME: 'Casa Cup 2026',
        EVENT_COUNTRY: 'Canada',
        EVENT_PROVINCE: 'Ontario',
        EVENT_CITY: 'GTA',
        EVENT_CURRENCY: 'CAD',
        EVENT_VENUE: 'Epic',
        EVENT_CATEGORY: 'Open',
        GENDER_CATEGORY: 'Male',
        GENDER_SKILL_LEVEL: 'All',
        EVENT_TYPE: 'Single',
        EVENT_DATE: '2026-03-14',
        EVENT_TIME: '10:00',
        TO_TIME: '14:00',
        CANCEL_DATE: '2026-03-13',
        CANCEL_TIME: '10:00',
        EVENT_COST: '80',
        AMOUNT: '80',
        PAYMENT_ID: 'casaclubpayment1@gmail.com',
        PAYMENT_DEADLINE: '2026-03-13',
        REPORTING_TIME: '09:00',
        MATCH_START_TIME: '10:00',
        DRAW_ANNOUNCEMENT: '2026-03-12',
        SHUTTLE_TYPE: 'Feather',
        MATCH_FORMAT: 'Best of 3 games',
        EVENT_DESCRIPTION: "I Play Every Day But Don't Have a Trophy",
        EVENT_MESSAGE: "Men's Badminton Tournament in the GTA featuring competitive players from across the region.",
        POPUP_MESSAGE: <?php echo json_encode($templatePopup); ?>,
        PAYMENT_MAIL: <?php echo json_encode($templateMail); ?>,
        banner_image: ''
    };
    var editors = {};
    var currentMode = 'add';

    function normalizeSkill(value) {
        if (value === 'Advance') return 'Adv';
        if (value === 'Intermediate+') return 'Int+';
        if (value === 'Intermediate') return 'Int';
        return value || 'All';
    }

    function htmlEscape(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    function setEditor(name, value) {
        if (editors[name]) {
            editors[name].setData(value || '');
        } else {
            $('#' + name).val(value || '');
        }
    }

    function replaceToken(source, token, value) {
        return String(source || '').split(token).join(value || token);
    }

    function updateDynamicTemplates() {
        if (currentMode !== 'add') return;

        var popup = defaults.POPUP_MESSAGE;
        var mail = defaults.PAYMENT_MAIL;
        var values = {
            '[Tournament Name]': $('#cup_name').val() || '[Tournament Name]',
            '[Currency]': $('#event_currency').val() || 'CAD',
            '[Amount]': $('#amount').val() || '[Amount]',
            '[Payment ID]': $('#payment_id').val() || '[Payment ID]',
            '[Deadline Date]': $('#payment_deadline').val() || '[Deadline Date]',
            '[Category]': $('#event_category').val() || '[Category]',
            '[Type]': $('#event_type').val() || '[Type]',
            '[Date]': $('#event_date').val() || '[Date]',
            '[Venue]': $('#event_venue').val() || '[Venue]',
            '[Reporting Time]': $('#reporting_time').val() || '[Reporting Time]',
            '[Match Time]': $('#match_start_time').val() || '[Match Time]',
            '[Draw Date]': $('#draw_announcement').val() || '[Draw Date]',
            '[Shuttle]': $('#shuttle_type').val() || '[Shuttle]',
            '[Match Format]': $('#match_format').val() || '[Match Format]'
        };

        Object.keys(values).forEach(function(token) {
            popup = replaceToken(popup, token, values[token]);
            mail = replaceToken(mail, token, values[token]);
        });

        setEditor('popup_message', popup);
        setEditor('payment_mail', mail);
    }

    function syncEditors() {
        Object.keys(editors).forEach(function(name) {
            $('#' + name).val(editors[name].getData());
        });
    }

    function fillForm(data) {
        $('#host_name').val(data.HOST_NAME || '');
        $('#cup_name').val(data.CUP_NAME || '');
        $('#event_country').val(data.EVENT_COUNTRY || 'Canada');
        $('#event_province').val(data.EVENT_PROVINCE || 'Ontario');
        $('#event_city').val(data.EVENT_CITY || 'GTA');
        $('#event_currency').val(data.EVENT_CURRENCY || 'CAD');
        $('#event_venue').val(data.EVENT_VENUE || '');
        $('#event_category').val(data.EVENT_CATEGORY || 'Open');
        $('#gender_category').val(data.GENDER_CATEGORY || 'Male');
        $('#skill_level').val(normalizeSkill(data.GENDER_SKILL_LEVEL));
        $('#event_type').val(data.EVENT_TYPE || 'Single');
        $('#event_date').val(data.EVENT_DATE || '');
        $('#from_time').val(data.EVENT_TIME || '');
        $('#to_time').val(data.TO_TIME || '');
        $('#freeze_date').val(data.CANCEL_DATE || '');
        $('#freeze_time').val(data.CANCEL_TIME || '');
        $('#event_cost').val(data.EVENT_COST || '');
        $('#amount').val(data.AMOUNT || '');
        $('#payment_id').val(data.PAYMENT_ID || '');
        $('#payment_deadline').val(data.PAYMENT_DEADLINE || '');
        $('#reporting_time').val(data.REPORTING_TIME || '');
        $('#match_start_time').val(data.MATCH_START_TIME || '');
        $('#draw_announcement').val(data.DRAW_ANNOUNCEMENT || '');
        $('#shuttle_type').val(data.SHUTTLE_TYPE || 'Feather');
        $('#match_format').val(data.MATCH_FORMAT || '');
        setEditor('event_description', data.EVENT_DESCRIPTION || '');
        setEditor('event_message', data.EVENT_MESSAGE || '');
        setEditor('popup_message', data.POPUP_MESSAGE || defaults.POPUP_MESSAGE);
        setEditor('payment_mail', data.PAYMENT_MAIL || defaults.PAYMENT_MAIL);
        $('#banner').val('');

        if (data.banner_image) {
            $('#imagePreview').attr('src', 'assets/images/tournaments_banner/' + data.banner_image);
            $('#previewWrapper').css('display', 'inline-block');
        } else {
            removeImage();
        }
    }

    function openTournamentModal(mode, id) {
        currentMode = mode;
        $('#tournamentForm')[0].reset();
        $('#form_action').val(mode === 'edit' ? 'update' : '');
        $('#event_id').val(id || '');
        $('#bannerRequired').toggle(mode === 'add');
        $('#tournamentModalTitle').text(mode === 'edit' ? 'Edit Tournament' : 'Add Tournament');
        $('#submitBtn').html(mode === 'edit' ? '<i class="fa fa-save"></i> Save Changes' : '<i class="fa fa-save"></i> Create Event').prop('disabled', false);
        fillForm(mode === 'edit' ? tournaments[id] : defaults);
        $('#tournamentModal').modal('show');
    }

    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').attr('src', e.target.result);
                $('#previewWrapper').css('display', 'inline-block');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function removeImage() {
        $('#banner').val('');
        $('#previewWrapper').hide();
        $('#imagePreview').attr('src', '');
    }

    function showFlash(type, message) {
        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        var html = '<div class="alert ' + alertClass + '"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>' + htmlEscape(message) + '</strong></div>';
        var $msg = $(html).appendTo('#flash-container');
        setTimeout(function() {
            $msg.fadeOut(1000, function() {
                $(this).remove();
            });
        }, 4000);
    }

    function detailItem(label, value) {
        return '<div class="view-item"><span class="view-label">' + htmlEscape(label) + '</span><strong>' + htmlEscape(value || '-') + '</strong></div>';
    }

    function openViewModal(id) {
        var data = tournaments[id];
        if (!data) return;

        $('#viewTournamentTitle').text(data.CUP_NAME || 'Tournament Details');
        var banner = data.banner_image ? '<div style="margin-bottom:10px;"><img src="assets/images/tournaments_banner/' + htmlEscape(data.banner_image) + '" style="max-width:280px;height:auto;border:1px solid #ddd;padding:4px;"></div>' : '';
        var grid = '<div class="view-grid">' +
            detailItem('Host', data.HOST_NAME) +
            detailItem('Country', data.EVENT_COUNTRY) +
            detailItem('Province', data.EVENT_PROVINCE) +
            detailItem('City', data.EVENT_CITY) +
            detailItem('Venue', data.EVENT_VENUE) +
            detailItem('Category', data.EVENT_CATEGORY) +
            detailItem('Gender', data.GENDER_CATEGORY) +
            detailItem('Skill', data.GENDER_SKILL_LEVEL) +
            detailItem('Type', data.EVENT_TYPE) +
            detailItem('Event Date', data.EVENT_DATE) +
            detailItem('Time', (data.EVENT_TIME || '') + ' - ' + (data.TO_TIME || '')) +
            detailItem('Freeze', (data.CANCEL_DATE || '') + ' ' + (data.CANCEL_TIME || '')) +
            detailItem('Cost', (data.EVENT_CURRENCY || '') + ' ' + (data.EVENT_COST || '')) +
            detailItem('Payment ID', data.PAYMENT_ID) +
            detailItem('Payment Deadline', data.PAYMENT_DEADLINE) +
            detailItem('Reporting Time', data.REPORTING_TIME) +
            detailItem('Match Start', data.MATCH_START_TIME) +
            detailItem('Draw Date', data.DRAW_ANNOUNCEMENT) +
            detailItem('Shuttle', data.SHUTTLE_TYPE) +
            detailItem('Match Format', data.MATCH_FORMAT) +
            detailItem('Status', data.STATUS) +
            '</div>';

        $('#viewTournamentBody').html(
            banner + grid +
            '<h4>Description</h4><div class="view-html">' + (data.EVENT_DESCRIPTION || '-') + '</div>' +
            '<h4>Event Message</h4><div class="view-html">' + (data.EVENT_MESSAGE || '-') + '</div>' +
            '<h4>Registration Popup Message</h4><div class="view-html">' + (data.POPUP_MESSAGE || '-') + '</div>' +
            '<h4>Payment/Login Email Template</h4><div class="view-html">' + (data.PAYMENT_MAIL || '-') + '</div>'
        );
        $('#viewTournamentModal').modal('show');
    }

    function toggleStatus(id, currentStatus) {
        var nextStatus = (currentStatus === 'Active') ? 'Inactive' : 'Active';
        if (confirm('Change status to ' + nextStatus + '?')) {
            $.post('api/manage_tournament.php', {
                action: 'toggle_status',
                id: id,
                status: nextStatus
            }, function() {
                location.reload();
            });
        }
    }

    function deleteTournament(id) {
        if (confirm('Are you sure you want to delete this tournament?')) {
            $.post('api/manage_tournament.php', {
                action: 'delete',
                id: id
            }, function(res) {
                var r = (typeof res === 'object') ? res : JSON.parse(res);
                if (r.status === 'success') {
                    location.reload();
                } else {
                    showFlash('error', r.message || 'Delete failed.');
                }
            });
        }
    }

    function deleteSelected() {
        var ids = [];
        $('.row-checkbox:checked').each(function() {
            ids.push($(this).val());
        });
        if (ids.length && confirm('Delete ' + ids.length + ' selected tournaments?')) {
            $.post('api/manage_tournament.php', {
                action: 'bulk_delete',
                ids: ids
            }, function() {
                location.reload();
            });
        }
    }

    var customTable = {
        page: 1,
        pageSize: 25,
        search: '',
        rows: []
    };

    function getRowSearchText($row) {
        return $row.text().replace(/\s+/g, ' ').toLowerCase();
    }

    function initCustomTable() {
        customTable.rows = $('#tournamentsTable tbody tr').map(function() {
            var $row = $(this);
            return {
                el: this,
                searchText: getRowSearchText($row)
            };
        }).get();
        renderCustomTable();
    }

    function getFilteredRows() {
        if (!customTable.search) {
            return customTable.rows;
        }

        return customTable.rows.filter(function(row) {
            return row.searchText.indexOf(customTable.search) !== -1;
        });
    }

    function renderCustomTable() {
        var filtered = getFilteredRows();
        var total = filtered.length;
        var totalPages = Math.max(1, Math.ceil(total / customTable.pageSize));

        if (customTable.page > totalPages) {
            customTable.page = totalPages;
        }

        var start = total ? (customTable.page - 1) * customTable.pageSize : 0;
        var end = Math.min(start + customTable.pageSize, total);

        customTable.rows.forEach(function(row) {
            row.el.style.display = 'none';
        });

        filtered.slice(start, end).forEach(function(row) {
            row.el.style.display = '';
        });

        $('#customTableInfo').text(total ? 'Showing ' + (start + 1) + ' to ' + end + ' of ' + total + ' records' : 'No records found');
        renderCustomPagination(totalPages);
        $('#checkAll').prop('checked', false);
    }

    function pageButton(label, page, disabled, active) {
        return $('<li>').append(
            $('<button type="button" class="btn btn-default btn-sm">')
            .text(label)
            .toggleClass('active', !!active)
            .prop('disabled', !!disabled)
            .on('click', function() {
                if (!disabled) {
                    customTable.page = page;
                    renderCustomTable();
                }
            })
        );
    }

    function renderCustomPagination(totalPages) {
        var $pagination = $('#customPagination').empty();
        var current = customTable.page;
        var startPage = Math.max(1, current - 2);
        var endPage = Math.min(totalPages, current + 2);

        if (current <= 3) {
            endPage = Math.min(totalPages, 5);
        }

        if (current >= totalPages - 2) {
            startPage = Math.max(1, totalPages - 4);
        }

        $pagination.append(pageButton('Prev', Math.max(1, current - 1), current === 1, false));

        if (startPage > 1) {
            $pagination.append(pageButton('1', 1, false, current === 1));
            if (startPage > 2) {
                $pagination.append($('<li><span class="btn btn-default btn-sm disabled">...</span></li>'));
            }
        }

        for (var i = startPage; i <= endPage; i++) {
            $pagination.append(pageButton(String(i), i, false, i === current));
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                $pagination.append($('<li><span class="btn btn-default btn-sm disabled">...</span></li>'));
            }
            $pagination.append(pageButton(String(totalPages), totalPages, false, current === totalPages));
        }

        $pagination.append(pageButton('Next', Math.min(totalPages, current + 1), current === totalPages, false));
    }

    $(function() {
        initCustomTable();

        var cfg = {
            toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo']
        };
        ['event_description', 'event_message', 'popup_message', 'payment_mail'].forEach(function(name) {
            ClassicEditor.create(document.querySelector('#' + name), cfg).then(function(editor) {
                editors[name] = editor;
            });
        });

        $(document).on('input change', '#cup_name, #amount, #event_currency, #payment_id, #payment_deadline, #event_category, #event_type, #event_date, #event_venue, #reporting_time, #match_start_time, #draw_announcement, #shuttle_type, #match_format', function() {
            updateDynamicTemplates();
        });

        $('#checkAll').on('click', function() {
            $('#tournamentsTable tbody tr:visible .row-checkbox').prop('checked', this.checked);
        });

        $('#customPageSize').on('change', function() {
            customTable.pageSize = parseInt(this.value, 10) || 25;
            customTable.page = 1;
            renderCustomTable();
        });

        $('#customTableSearch').on('input', function() {
            customTable.search = $.trim(this.value).toLowerCase();
            customTable.page = 1;
            renderCustomTable();
        });

        $('#tournamentForm').on('submit', function(e) {
            e.preventDefault();
            syncEditors();

            if ($.trim($('#cup_name').val()) === '') {
                showFlash('error', 'Cup Name is required.');
                return;
            }

            if (currentMode === 'add' && $('#banner')[0].files.length === 0) {
                showFlash('error', 'Banner is required.');
                return;
            }

            var formData = new FormData(this);
            var url = currentMode === 'edit' ? 'api/manage_tournament.php' : 'api/add_tournament.php';
            $('#submitBtn').html("<i class='fa fa-spinner fa-spin'></i> Saving...").prop('disabled', true);

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    var res = (typeof response === 'object') ? response : JSON.parse(response);
                    if (res.status === 'success') {
                        showFlash('success', res.message);
                        setTimeout(function() {
                            location.reload();
                        }, 900);
                    } else {
                        showFlash('error', res.message || 'Save failed.');
                        $('#submitBtn').html('<i class="fa fa-save"></i> Save').prop('disabled', false);
                    }
                },
                error: function() {
                    showFlash('error', 'Server Error.');
                    $('#submitBtn').html('<i class="fa fa-save"></i> Save').prop('disabled', false);
                }
            });
        });
    });
</script>