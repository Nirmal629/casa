<?php
include('dbConnection.php');
include('header.php');
include('sidebar.php');
?>

<style>
    /* 1. COMPACT TABLE */
    .table-compact td, .table-compact th {
        padding: 4px 8px !important;
        vertical-align: middle !important;
        font-size: 13px;
        white-space: nowrap;
    }

    /* 2. LEFT SIDE LAYOUT CONTAINER */
    .custom-controls-container {
        display: flex;
        align-items: center;
        gap: 15px; /* Space between Button, Dropdown, and Search */
        margin-bottom: 15px;
        flex-wrap: wrap;
    }

    /* Remove default DataTables positioning */
    .dataTables_length, .dataTables_filter {
        margin: 0 !important;
        float: none !important;
        display: inline-block !important;
    }

    .dataTables_length label, .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-bottom: 0;
        font-weight: normal;
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
    .page-header {
    padding-left: 1cm; /* 👈 creates the left gap */
    }
    
    .left-wrapper {
        display: flex;
        align-items: center;
    }
    
    .breadcrumbs {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        gap: 10px;
    }
    
    .breadcrumbs li span {
        font-size: 20px;
        font-weight: 700; /* 👈 bold */
        color: #000;
    }
    
    .breadcrumbs li a {
        color: #000;
        font-size: 18px;
    }

</style>

<section role="main" class="content-body">
    <header class="page-header">
        <!-- <h2>List User's</h2> -->
        <div class="left-wrapper">
            <ol class="breadcrumbs">
                <li><a href="index.php"><i class="fa fa-home"></i></a></li>
                <li><span>User</span></li>
                <!--<li><span>List User</span></li>-->
                <!--<li><span>Add User</span></li>-->
            </ol>
            <!--<a class="sidebar-right-toggle" data-open="sidebar-right"><i class="fa fa-chevron-left"></i></a>-->
        </div>
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
            
            <!-- CUSTOM CONTAINER FOR ALL LEFT-SIDE CONTROLS -->
            <div id="left-controls" class="custom-controls-container">
                <!-- Manual Add Button (Will always show) -->
                <a href="add_user.php" class="btn btn-success btn-sm">
                    <i class="fa fa-plus"></i>
                </a>
                <!-- DataTables Length and Filter will be moved here by JS -->
            </div>

            <div style="overflow-x: auto;">
                <table class="table table-bordered table-striped mb-none table-compact" id="datatable-userlist" style="width:100%">
                    <thead>
                        <tr>
                            <th width="40">SL</th>
                            <th width="140">ACTION</th>
                            <th>NAME</th>
                            <th width="150">V LEVEL</th>
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
                                echo "<tr>";
                                echo "<td>$i</td>";
                                echo "<td>";
                                echo "<div class='action-btns'>";
                                
                                // Status Toggle
                                $statusColor = ($row['LOG_STATUS'] == 'N' ? '#0099e6' : '#47a447');
                                $statusIcon = ($row['LOG_STATUS'] == 'N' ? 'fa-toggle-off' : 'fa-toggle-on');
                                echo "<button class='btn btn-primary' onclick='toggleStatus(this)' data-id='{$row['ID']}' data-status='{$row['LOG_STATUS']}' style='background-color:$statusColor; border-color:$statusColor;'><i class='fa $statusIcon'></i></button>";

                                // Copy
                                echo "<button class='btn btn-info copy-user' data-user='" . htmlspecialchars($userData, ENT_QUOTES) . "'><i class='fa fa-copy'></i></button>";

                                // View
                                echo "<button class='btn btn-default' onclick='window.location.href=\"view_user.php?user_id={$row['ID']}\"'><i class='fa fa-eye'></i></button>";

                                // Edit
                                echo "<button class='btn btn-warning' onclick='window.location.href=\"edit_user.php?user_id={$row['ID']}\"'><i class='fa fa-edit'></i></button>";

                                // Delete
                                echo "<button class='btn btn-danger' onclick='deleteUser(this)' data-id='{$row['ID']}'><i class='fa fa-trash'></i></button>";

                                echo "</div>";
                                echo "</td>";
                                echo "<td>" . htmlspecialchars($row['NAME']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['VERIFIED_LEVEL']) . "</td>";
                                echo "</tr>";
                                $i++;
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</section>

<?php include('footer.php'); ?>

<script>
$(document).ready(function() {
    var table = $('#datatable-userlist').DataTable({
        "pageLength": 100,
        "lengthMenu": [[100, 150, 200, 250], [100, 150, 200, 250]],
        "dom": 'lfrtip',
        "language": {
            "lengthMenu": "_MENU_",  // REMOVES "Show" AND "entries"
            "search": "",            // REMOVES "Search:"
            "searchPlaceholder": "Search..."
        },
        "initComplete": function() {
            // Append length menu and search box to the left container
            $('#left-controls').append($('.dataTables_length')).append($('.dataTables_filter'));

            // Clean styling for inputs
            $('.dataTables_filter input').addClass('form-control input-sm').css('width', '180px');
            $('.dataTables_length select').addClass('form-control input-sm');
        }
    });
});

// --- HELPER FUNCTIONS ---
function toggleStatus(btn) {
    let id = $(btn).data('id');
    let current = $(btn).data('status');
    let next = (current === 'N') ? 'Y' : 'N';
    $.post("api/toggleStatus.php", { user_id: id, new_status: next }, function() {
        $(btn).data('status', next);
        let color = (next === 'Y') ? '#47a447' : '#0099e6';
        let icon = (next === 'Y') ? 'fa-toggle-on' : 'fa-toggle-off';
        $(btn).css({'background-color': color, 'border-color': color}).find('i').attr('class', 'fa ' + icon);
    });
}

$(document).on('click', '.copy-user', function() {
    navigator.clipboard.writeText($(this).data('user'));
    alert('Copied!');
});

function deleteUser(btn) {
    if(confirm('Delete user?')) {
        $.post('api/delete_user.php', { id: $(btn).data('id') }, function() { location.reload(); });
    }
}
</script>