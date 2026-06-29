<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
include('dbConnection.php');
include('header.php');
include('sidebar.php');
?>

<!DOCTYPE html>
<html>
<head>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
.table-compact td, .table-compact th {
    padding: 4px 8px !important;
    vertical-align: middle !important;
    font-size: 13px;
    white-space: nowrap;
}
.custom-controls-container {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}
.dataTables_length, .dataTables_filter {
    margin: 0 !important;
    float: none !important;
    display: inline-block !important;
}
.dataTables_filter input.form-control {
    width: 180px !important;
}
.dataTables_length select.form-control {
    min-width: 80px;
}
.action-btns .btn {
    padding: 2px 6px;
    font-size: 11px;
}
.page-header { padding-left: 1cm; }
.left-wrapper { display: flex; align-items: center; }
.breadcrumbs { list-style: none; margin: 0; padding: 0; display: flex; gap: 10px; }
.breadcrumbs li span { font-size: 20px; font-weight: 700; color: #000; }
.breadcrumbs li a { color: #000; font-size: 18px; }
/* Hide "Show X entries" text */
.dataTables_length label {
    font-size: 0 !important;
}

/* Restore only the dropdown */
.dataTables_length select {
    font-size: 14px !important;
}

/* Hide "Search:" label text */
.dataTables_filter label {
    font-size: 0 !important;
}

/* Keep search input visible */
.dataTables_filter input {
    font-size: 14px !important;
}
</style>

</head>

<body>

<section role="main" class="content-body">
    <header class="page-header">
        <div class="left-wrapper">
            <ol class="breadcrumbs">
                <li><a href="index.php"><i class="fa fa-home"></i></a></li>
                <li><span>ContactUs</span></li>
            </ol>
        </div>
    </header>

    <section class="panel">
        <header class="panel-heading">
            <div class="panel-actions">
                <a href="#" class="panel-action panel-action-toggle" data-panel-toggle></a>
                <a href="#" class="panel-action panel-action-dismiss" data-panel-dismiss></a>
            </div>
            <h2 class="panel-title">ContactUs Messages</h2>
        </header>

        <div class="panel-body">
            <div id="left-controls" class="custom-controls-container">
                <a href="contactus_add.php" class="btn btn-success btn-sm">
                    <i class="fa fa-plus"></i> Add
                </a>
            </div>

            <div style="overflow-x: auto;">
                <table class="table table-bordered table-striped table-compact mb-none" id="contactustable" style="width:100%">
                    <thead>
                        <tr>
                            <th>SL NO</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Message</th>
                            <th>Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM ca_contact_messages ORDER BY id DESC";
                        $result = $conn->query($sql);
                        $i = 1;
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $i . "</td>";
                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                                echo "<td>" . nl2br(htmlspecialchars($row['message'])) . "</td>";
                                echo "<td>" . $row['created_at'] . "</td>";
                                echo "<td class='action-btns'>
                                        <button class='btn btn-danger btn-sm' onclick='deleteContact(" . $row['id'] . ")'>
                                            <i class='fa fa-trash'></i>
                                        </button>
                                      </td>";
                                echo "</tr>";
                                $i++;
                            }
                        } else {
                            echo "<tr><td colspan='7'>No contact messages found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</section>


<script>
$(document).ready(function () {

    var table = $('#contactustable').DataTable({
        pageLength: 100,
        lengthMenu: [[100, 150, 200, 250], [100, 150, 200, 250]],
        dom: '<"top"lf>rt<"bottom"ip><"clear">',
        language: {
            search: "",
            searchPlaceholder: "Search..."
        }
    });

    // MOVE AFTER FULL RENDER (IMPORTANT)
    setTimeout(function () {

        // move BOTH controls safely
        var length = $('#contactustable_length');
        var filter = $('#contactustable_filter');

        if (length.length && filter.length) {
            $('#left-controls').append(length, filter);
        }

        // style search box
        $('#contactustable_filter input')
            .css({
                width: '200px',
                padding: '4px 8px'
            })
            .attr("placeholder", "Search...");

        $('#contactustable_length select')
            .css({
                padding: '4px 6px'
            });

    }, 300);

});

function deleteContact(id) {
    if(confirm('Are you sure you want to delete this contact message?')) {
        $.ajax({
            url: 'contactus_delete.php',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                const res = JSON.parse(response);
                if(res.success) {
                    alert('Deleted successfully!');
                    location.reload();
                } else {
                    alert('Error deleting message!');
                }
            },
            error: function() {
                alert('AJAX error occurred!');
            }
        });
    }
}
</script>

</body>
</html>

<?php include('footer.php'); ?>
