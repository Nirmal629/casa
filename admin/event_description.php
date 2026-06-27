<?php
include('dbConnection.php');
include('header.php');
include('sidebar.php');


    $result = mysqli_query($conn, "SELECT DESCRIPTION FROM ca_description WHERE 1");
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $desc = $row['DESCRIPTION'];
    }


// Update description
if (isset($_POST['submit'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $update = mysqli_query($conn, "UPDATE ca_description SET DESCRIPTION = '$name'");
    $updatee = mysqli_query($conn, "UPDATE ca_events_default SET EVENT_DESCRIPTION = '$name'");
    if ($update) {
        echo "<script>alert('Description updated successfully!'); window.location.href='event_description.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<section role="main" class="content-body">
    <header class="page-header">
        <h2>Edit Description</h2>
        <div class="right-wrapper pull-right">
            <ol class="breadcrumbs">
                <li>
                    <a href="dashboard.php">
                        <i class="fa fa-home"></i>
                    </a>
                </li>
                <li><span>Manage Description</span></li>
                <li><span>Edit Description</span></li>
            </ol>
            <a class="sidebar-right-toggle" data-open="sidebar-right"><i class="fa fa-chevron-left"></i></a>
        </div>
    </header>

    <div class="row">
        <div class="col-lg-12">
            <section class="panel">
                <header class="panel-heading">
                    <h2 class="panel-title">Update Description</h2>
                </header>
                <div class="panel-body">
                    <form method="POST">
                        <div class="form-group">
                            <label style="font-weight:bold" for="name">Event Description<span>*</span></label>
                                    <textarea class="form-control" id="name" name="name" rows="20" placeholder="Enter full description" required><?php echo htmlspecialchars($desc); ?></textarea>

                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" name="submit">Save</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
</section>

<?php
include('footer.php');
?>
