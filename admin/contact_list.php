<?php
include('dbConnection.php');
include('header.php');
include('sidebar.php');
?>
<section role="main" class="content-body">
    <header class="page-header">
        <h2>Contact Messages</h2>
        <div class="right-wrapper pull-right">
            <ol class="breadcrumbs">
                <li><a href="index.php"><i class="fa fa-home"></i></a></li>
                <li><span>Manage Contact</span></li>
                <li><span>List Messages</span></li>
            </ol>
            <a class="sidebar-right-toggle" data-open="sidebar-right"><i class="fa fa-chevron-left"></i></a>
        </div>
    </header>

    <!-- start: page -->
    <section class="panel">
        <header class="panel-heading">
            <div class="panel-actions">
                <a href="#" class="panel-action panel-action-toggle" data-panel-toggle></a>
                <a href="#" class="panel-action panel-action-dismiss" data-panel-dismiss></a>
            </div>
            <h2 class="panel-title">Contact Messages</h2>
        </header>
        <div class="panel-body">
            <div style="overflow-x: auto;">
                <table class="table table-bordered table-striped mb-none" id="datatable-default">
                    <thead>
                        <tr>
                            <th>SL NO</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Message</th>
                            <th>Time</th>
                            <!--<th>Action</th>-->
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
                                echo "</tr>";
                                $i++;
                            }
                        } else {
                            echo "<tr><td colspan='6'>No contact messages found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <!-- end: page -->
</section>

<script>
function deleteContact(button) {
    const id = $(button).data('id');

    if (confirm('Are you sure you want to delete this contact message?')) {
        $.ajax({
            url: 'api/delete_contact.php',
            type: 'POST',
            data: { id: id },
            success: function (response) {
                const data = JSON.parse(response);
                if (data.success) {
                    alert('Contact message deleted successfully.');
                    location.reload();
                } else {
                    alert('Error deleting contact message.');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('An unexpected error occurred.');
            }
        });
    }
}
</script>
<?php include('footer.php'); ?>
