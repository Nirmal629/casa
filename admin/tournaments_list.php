<style>
    /* Add this to your <style> section if you want a specific "Inactive" look */
    .btn-inactive {
        background-color: #d9534f;
        /* Red */
        color: white;
        border: 1px solid #d43f3a;

        /* Professional Clear Filter Button */
        .btn-reset {
            background-color: #fff;
            border: 1px solid #ccc;
            color: #333;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
        }

        .btn-reset:hover {
            background-color: #f5f5f5;
            border-color: #adadad;
            color: #000;
            text-decoration: none;
        }

        .btn-reset i {
            margin-right: 5px;
            color: #d9534f;
            /* Subtle red for the icon */
        }
    }
</style>

<?php
include('header.php');
include('sidebar.php');

// --- 1. Database Connection ---
$host    = 'localhost';
$db      = 'casa_test';
$user    = 'casa_test';
$pass    = 'casa_test123#';
$dsn     = "mysql:host=$host;dbname=$db;charset=utf8";
$conn    = new PDO($dsn, $user, $pass, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

// --- 2. Fetch Dynamic Filter Data ---
// Hosts from ca_users
$hosts      = $conn->query("SELECT ID, NAME FROM ca_users WHERE USERTYPE = 'Host' AND DEL_STATUS = 'N' ORDER BY NAME ASC")->fetchAll();

// Location filters from to_tournaments
$countries  = $conn->query("SELECT DISTINCT EVENT_COUNTRY FROM to_tournaments WHERE EVENT_COUNTRY != '' ORDER BY EVENT_COUNTRY ASC")->fetchAll(PDO::FETCH_COLUMN);
$provinces  = $conn->query("SELECT DISTINCT EVENT_PROVINCE FROM to_tournaments WHERE EVENT_PROVINCE != '' ORDER BY EVENT_PROVINCE ASC")->fetchAll(PDO::FETCH_COLUMN);
$cities     = $conn->query("SELECT DISTINCT EVENT_CITY FROM to_tournaments WHERE EVENT_CITY != '' ORDER BY EVENT_CITY ASC")->fetchAll(PDO::FETCH_COLUMN);

$categories = $conn->query("SELECT DISTINCT EVENT_CATEGORY FROM to_tournaments ORDER BY EVENT_CATEGORY ASC")->fetchAll(PDO::FETCH_COLUMN);
$statuses   = $conn->query("SELECT DISTINCT STATUS FROM to_tournaments ORDER BY STATUS ASC")->fetchAll(PDO::FETCH_COLUMN);

// --- 3. Build the Filter Logic ---
$where = [];
$params = [];

if (!empty($_GET['f_host'])) {
    $where[] = "t.HOST_ID = ?";
    $params[] = $_GET['f_host'];
}
if (!empty($_GET['f_country'])) {
    $where[] = "t.EVENT_COUNTRY = ?";
    $params[] = $_GET['f_country'];
}
if (!empty($_GET['f_province'])) {
    $where[] = "t.EVENT_PROVINCE = ?";
    $params[] = $_GET['f_province'];
}
if (!empty($_GET['f_city'])) {
    $where[] = "t.EVENT_CITY = ?";
    $params[] = $_GET['f_city'];
}
if (!empty($_GET['f_date'])) {
    $where[] = "t.EVENT_DATE = ?";
    $params[] = $_GET['f_date'];
}
if (!empty($_GET['f_cat'])) {
    $where[] = "t.EVENT_CATEGORY = ?";
    $params[] = $_GET['f_cat'];
}
if (!empty($_GET['f_status'])) {
    $where[] = "t.STATUS = ?";
    $params[] = $_GET['f_status'];
}

$whereSql = count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT t.*, b.IMGAE as banner_image 
        FROM to_tournaments t 
        LEFT JOIN to_tournamet_banners b ON t.ID = b.EVENTS_ID 
        $whereSql
        ORDER BY t.EVENT_DATE DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$tournaments = $stmt->fetchAll();
?>

<section role="main" class="content-body">
    <header class="page-header">
        <h2>Tournament Management</h2>
    </header>

    <!-- Top Navigation Action Buttons (Icons Only) -->
    <div class="row mb-md">
        <div class="col-sm-12">
            <a href="add_event.php" class="btn btn-primary" title="Add Tournament"><i class="fa fa-plus"></i></a>
            <a href="enrolled_tournaments.php" class="btn btn-info" title="All Enrolled Users"><i class="fa fa-users"></i></a>
        </div>
    </div>

    <!-- Filter Bar -->
  <section class="panel">
    <div class="panel-body">
        <form method="GET" id="filterForm" action="" class="form-inline">
            
            <!-- Host Filter -->
            <select name="f_host" class="form-control mr-xs" onchange="this.form.submit()">
                <option value="">All Hosts</option>
                <?php foreach($hosts as $h): ?>
                    <option value="<?php echo $h['ID']; ?>" <?php echo (@$_GET['f_host'] == $h['ID']) ? 'selected':''; ?>><?php echo htmlspecialchars($h['NAME']); ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Country Filter -->
            <select name="f_country" class="form-control mr-xs" onchange="this.form.submit()">
                <option value="">All Countries</option>
                <?php foreach($countries as $country): ?>
                    <option value="<?php echo $country; ?>" <?php echo (@$_GET['f_country'] == $country) ? 'selected':''; ?>><?php echo $country; ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Province Filter -->
            <select name="f_province" class="form-control mr-xs" onchange="this.form.submit()">
                <option value="">All Provinces</option>
                <?php foreach($provinces as $prov): ?>
                    <option value="<?php echo $prov; ?>" <?php echo (@$_GET['f_province'] == $prov) ? 'selected':''; ?>><?php echo $prov; ?></option>
                <?php endforeach; ?>
            </select>

            <!-- City Filter -->
            <select name="f_city" class="form-control mr-xs" onchange="this.form.submit()">
                <option value="">All Cities</option>
                <?php foreach($cities as $city): ?>
                    <option value="<?php echo $city; ?>" <?php echo (@$_GET['f_city'] == $city) ? 'selected':''; ?>><?php echo $city; ?></option>
                <?php endforeach; ?>
            </select>

            <input type="date" name="f_date" class="form-control mr-xs" value="<?php echo $_GET['f_date'] ?? ''; ?>" onchange="this.form.submit()">

            <select name="f_cat" class="form-control mr-xs" onchange="this.form.submit()">
                <option value="">All Categories</option>
                <?php foreach($categories as $cat): ?>
                    <option value="<?php echo $cat; ?>" <?php echo (@$_GET['f_cat'] == $cat) ? 'selected':''; ?>><?php echo $cat; ?></option>
                <?php endforeach; ?>
            </select>

            <select name="f_status" class="form-control mr-xs" onchange="this.form.submit()">
                <option value="">All Status</option>
                <?php foreach($statuses as $st): ?>
                    <option value="<?php echo $st; ?>" <?php echo (@$_GET['f_status'] == $st) ? 'selected':''; ?>><?php echo $st; ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Professional Reset Button -->
            <a href="tournaments_list.php" class="btn-reset">
                <i class="fa fa-refresh"></i> Clear Filters
            </a>
        </form>
    </div>
</section>

    <div class="row">
        <div class="col-lg-12">
            <section class="panel">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <button id="bulkDeleteBtn" class="btn btn-danger btn-xs" style="display:none;" onclick="deleteSelected()">
                            <i class="fa fa-trash-o"></i> Delete Selected
                        </button>
                    </div>
                    <h2 class="panel-title">Tournaments List (<?php echo count($tournaments); ?>)</h2>
                </header>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-none">
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
                                <?php if (empty($tournaments)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No tournaments found.</td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($tournaments as $row): ?>
                                    <tr id="row-<?php echo $row['ID']; ?>">
                                        <td class="text-center"><input type="checkbox" class="row-checkbox" value="<?php echo $row['ID']; ?>"></td>
                                        <td class="text-center">
                                            <img src="assets/images/tournaments_banner/<?php echo $row['banner_image']; ?>" style="width: 50px; height: 35px; object-fit: cover; border: 1px solid #ddd; border-radius: 2px;">
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($row['CUP_NAME']); ?></strong></td>
                                        <td><?php echo date('d-M-Y', strtotime($row['EVENT_DATE'])); ?></td>
                                        <td><?php echo $row['EVENT_CATEGORY']; ?></td>
                                        <td>$<?php echo number_format($row['EVENT_COST'], 2); ?></td>
                                        <td>
                                            <!-- Status Toggle Button -->
                                            <button onclick="toggleStatus(<?php echo $row['ID']; ?>, '<?php echo $row['STATUS']; ?>')"
                                                class="btn btn-xs <?php echo ($row['STATUS'] == 'Active') ? 'btn-success' : 'btn-danger'; ?>"
                                                title="Click to toggle status">
                                                <?php echo $row['STATUS']; ?>
                                            </button>
                                        </td>
                                        <td class="actions">
                                            <!-- View Details -->
                                            <a href="view_tournament.php?id=<?php echo $row['ID']; ?>" class="btn btn-xs btn-info" title="View"><i class="fa fa-eye"></i></a>
                                            <!-- Edit -->
                                            <a href="edit_tournament.php?id=<?php echo $row['ID']; ?>" class="btn btn-xs btn-primary"><i class="fa fa-pencil"></i></a>
                                            <!-- Delete -->
                                            <button onclick="deleteTournament(<?php echo $row['ID']; ?>)" class="btn btn-xs btn-danger"><i class="fa fa-trash-o"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>

<?php include('footer.php'); ?>

<script>
    // 1. Toggle Active/Inactive Status
    function toggleStatus(id, currentStatus) {
        var nextStatus = (currentStatus === 'Active') ? 'Inactive' : 'Active';
        if (confirm('Change status to ' + nextStatus + '?')) {
            $.post('api/manage_tournament.php', {
                action: 'toggle_status',
                id: id,
                status: nextStatus
            }, function(response) {
                location.reload();
            });
        }
    }

    // 2. Bulk Checkbox Logic
    $('#checkAll').click(function() {
        $('.row-checkbox').prop('checked', this.checked);
        toggleBulkBtn();
    });

    $('.row-checkbox').change(function() {
        toggleBulkBtn();
    });

    function toggleBulkBtn() {
        if ($('.row-checkbox:checked').length > 0) {
            $('#bulkDeleteBtn').fadeIn();
        } else {
            $('#bulkDeleteBtn').fadeOut();
        }
    }

    // 3. Single Delete
    function deleteTournament(id) {
        if (confirm('Are you sure you want to delete this tournament?')) {
            $.post('api/manage_tournament.php', {
                action: 'delete',
                id: id
            }, function(res) {
                var r = JSON.parse(res);
                if (r.status === 'success') {
                    $('#row-' + id).fadeOut(400, function() {
                        $(this).remove();
                    });
                }
            });
        }
    }

    // 4. Bulk Delete
    function deleteSelected() {
        var ids = [];
        $('.row-checkbox:checked').each(function() {
            ids.push($(this).val());
        });
        if (confirm('Delete ' + ids.length + ' selected tournaments?')) {
            $.post('api/manage_tournament.php', {
                action: 'bulk_delete',
                ids: ids
            }, function(res) {
                location.reload();
            });
        }
    }
</script>