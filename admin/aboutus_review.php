<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('dbConnection.php');
include('header.php');
include('sidebar.php');

// TOGGLE STATUS (same file)
if (isset($_GET['toggle_id'])) {
    $id = intval($_GET['toggle_id']);

    $res = mysqli_query($conn, "SELECT STATUS FROM ca_reviews WHERE ID = $id");
    $row = mysqli_fetch_assoc($res);

    $newStatus = 'Active';
    if ($row['STATUS'] == 'Active') $newStatus = 'Hidden';
    elseif ($row['STATUS'] == 'Hidden') $newStatus = 'Pending';

    mysqli_query($conn, "UPDATE ca_reviews SET STATUS = '$newStatus' WHERE ID = $id");

    header("Location:aboutus_review.php");
    exit;
}

// FETCH DATA
$result = mysqli_query($conn, "SELECT * FROM ca_reviews ORDER BY ID DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Reviews</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <style>
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .custom-controls-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        table.dataTable tbody td {
            white-space: nowrap;
        }
    </style>
</head>

<body>

<section role="main" class="content-body">

    <!-- HEADER -->
    <header class="page-header">
        <div class="left-wrapper">
            <ol class="breadcrumbs">
                <li>
                    <a href="index.php">
                        <i class="fa fa-home"></i>
                    </a>
                </li>
                <li><span>Reviews</span></li>
            </ol>
        </div>
    </header>

    <!-- PANEL -->
    <section class="panel">
        <div class="panel-body">

            <!-- LEFT CONTROLS -->
            <div id="left-controls" class="custom-controls-container">
                <a href="aboutus_review_add.php" class="btn btn-success btn-sm">
                    <i class="fa fa-plus"></i>
                </a>
            </div>

            <!-- TABLE -->
            <table id="reviewsTable" class="table table-bordered table-striped table-hover mb-none">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Rating</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th style="width:120px;">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>

                        <td><?=$row['ID']?></td>
                        <td><?=$row['USER_ID']?></td>
                        <td><?=$row['PLAYER_ROLE']?></td>

                        <!-- Rating -->
                        <td>
                            <?php for($i=1;$i<=$row['RATING'];$i++) echo "⭐"; ?>
                        </td>

                        <!-- Status -->
                        <td>
                            <?php
                                $color = 'orange';
                                if($row['STATUS']=='Active') $color='green';
                                if($row['STATUS']=='Hidden') $color='red';
                            ?>
                            <span class="status-dot" style="background:<?=$color?>;"></span>
                        </td>

                        <!-- Date -->
                        <td><?=date('d M Y', strtotime($row['DATE_CREATED']))?></td>

                        <!-- Actions -->
                        <td>

                            <!-- Toggle -->
                            <a href="?toggle_id=<?=$row['ID']?>" title="Toggle">
                                <i class="fa fa-toggle-on text-success"></i>
                            </a>

                            &nbsp;

                            <!-- View -->
                            <a href="aboutus_review_view.php?id=<?=$row['ID']?>" title="View">
                                <i class="fa fa-eye text-dark"></i>
                            </a>

                            &nbsp;

                            <!-- Edit -->
                            <a href="aboutus_review_edit.php?id=<?=$row['ID']?>" title="Edit">
                                <i class="fa fa-pencil text-primary"></i>
                            </a>

                        </td>

                    </tr>
                    <?php } ?>
                </tbody>

            </table>

        </div>
    </section>

</section>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function() {

    // Initialize DataTable
    var table = $('#reviewsTable').DataTable({
        pageLength: 100,  // default 100 rows
        lengthMenu: [ [10, 25, 50, 100], [10, 25, 50, 100] ], // dropdown options
        language: {
            lengthMenu: "_MENU_",  // keep only the dropdown
            search: ""             // remove "Search:" label
        }
    });

    // MOVE search + length to LEFT along with Add button
    $('#reviewsTable_length').appendTo('#left-controls');
    $('#reviewsTable_filter').appendTo('#left-controls');

    // Style controls
    $('#left-controls').css({
        display: 'flex',
        alignItems: 'center',
        gap: '10px',
        flexWrap: 'wrap'
    });

});
</script>

</body>
</html>

<?php
include('footer.php');
?>