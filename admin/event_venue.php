<?php
include('dbConnection.php');
include('header.php');
include('sidebar.php');

// Delete venue
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM ca_venue WHERE ID = $delete_id");
    echo "<script>window.location.href='" . $_SERVER['PHP_SELF'] . "';</script>";
}

// Insert venue
if (isset($_POST['submit'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $insert_venue = mysqli_query($conn, "INSERT INTO ca_venue (NAME) VALUES ('$name')");

    if ($insert_venue) {
        echo "<script>window.location.href='" . $_SERVER['PHP_SELF'] . "';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

// Fetch venues
$list_query = mysqli_query($conn, "SELECT * FROM ca_venue ORDER BY ID DESC");
?>

<section role="main" class="content-body">
    <header class="page-header">
        <h2>Manage Event Venue</h2>
        <div class="right-wrapper pull-right">
            <ol class="breadcrumbs">
                <li><a href="dashboard.php"><i class="fa fa-home"></i></a></li>
                <li><span>Event Venue</span></li>
                <li><span>Add / Manage</span></li>
            </ol>
        </div>
    </header>

    <div class="row">
        <div class="col-lg-12">

            <!-- Add Venue Form -->
            <section class="panel">
                <header class="panel-heading">
                    <h2 class="panel-title">Add Venue</h2>
                </header>
                <div class="panel-body">
                    <form method="POST">
                        <div class="form-group">
                            <label style="font-weight:bold" for="name">Venue Name <span>*</span></label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter venue name" required>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" name="submit">Save</button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Venue List -->
            <section class="panel">
                <header class="panel-heading">
                    <h2 class="panel-title">Venue List</h2>
                </header>
                <div class="panel-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Venue ID</th>
                                <th>Venue Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($list_query)) { ?>
                                <tr>
                                    <td><?php echo $row['ID']; ?></td>
                                    <td><?php echo htmlspecialchars($row['NAME']); ?></td>
                                    <td>
                                        <a href="?delete_id=<?php echo $row['ID']; ?>" 
                                           onclick="return confirm('Are you sure you want to delete this venue?')" 
                                           class="btn btn-danger btn-sm">Delete</a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </div>
</section>

<?php
include('footer.php');
?>
