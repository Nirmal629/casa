<?php
include('dbConnection.php');
include('header.php');
include('sidebar.php');

// =======================
// IMAGE UPLOAD
// =======================
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM ca_gallery WHERE ID = $delete_id");
    echo "<script>window.location.href='" . $_SERVER['PHP_SELF'] . "';</script>";
}

if (isset($_POST['upload_image'])) {
    $mainFlag = intval($_POST['main_flag']); // 1 = main, 0 = small

    if (isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] === 0) {
        $imageName = $_FILES['gallery_image']['name'];
        $imageTmp = $_FILES['gallery_image']['tmp_name'];
        $imagePath = 'assets/' . basename($imageName);

        if (move_uploaded_file($imageTmp, $imagePath)) {
            // If uploading main image, replace existing
            if ($mainFlag == 1) {
                mysqli_query($conn, "DELETE FROM ca_gallery WHERE MAIN = 1");
            }

            

            mysqli_query($conn, "INSERT INTO ca_gallery (IMAGE, MAIN) VALUES ('$imagePath', '$mainFlag')");
            echo "<script>alert('Image uploaded successfully!'); window.location.href='" . $_SERVER['PHP_SELF'] . "';</script>";
        } else {
            echo "<script>alert('Image upload failed.');</script>";
        }
    }
}

// Fetch main image
$main_image = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM ca_gallery WHERE MAIN = 1 LIMIT 1"));

// Fetch other images
$other_images = mysqli_query($conn, "SELECT * FROM ca_gallery WHERE MAIN = 0");
?>

<section role="main" class="content-body">
    <header class="page-header">
        <h2>Manage Gallery</h2>
    </header>

    <div class="row">
        <div class="col-lg-6">
            <!-- Main Image Upload -->
            <section class="panel">
                <header class="panel-heading">
                    <h2 class="panel-title">Main Image</h2>
                </header>
                <div class="panel-body text-center">
                    <?php if ($main_image) { ?>
                        <img src="<?php echo $main_image['IMAGE']; ?>" style="max-width:100%; height:auto; margin-bottom:10px;">
                        <br>
                        <a href="?delete_id=<?php echo $main_image['ID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete main image?')">Delete</a>
                    <?php } else { ?>
                        <p>No main image uploaded</p>
                    <?php } ?>
                    <form method="POST" enctype="multipart/form-data" style="margin-top:10px;">
                        <input type="file" name="gallery_image" required>
                        <input type="hidden" name="main_flag" value="1">
                        <button type="submit" class="btn btn-success" name="upload_image">Upload Main Image</button>
                    </form>
                </div>
            </section>
        </div>

        <div class="col-lg-6">
            <!-- Small Images Upload -->
            <section class="panel">
                <header class="panel-heading">
                    <h2 class="panel-title">Other Images</h2>
                </header>
                <div class="panel-body">
                    <div class="row">
                        <?php while ($img = mysqli_fetch_assoc($other_images)) { ?>
                            <div class="col-xs-6 col-sm-3 text-center" style="margin-bottom:10px;">
                                <img src="<?php echo $img['IMAGE']; ?>" style="width:100%; height:auto;">
                                <br>
                                <a href="?delete_id=<?php echo $img['ID']; ?>" class="btn btn-danger btn-xs" onclick="return confirm('Delete this image?')">Delete</a>
                            </div>
                        <?php } ?>
                    </div>
                    <form method="POST" enctype="multipart/form-data" style="margin-top:10px;">
                        <input type="file" name="gallery_image" required>
                        <input type="hidden" name="main_flag" value="0">
                        <button type="submit" class="btn btn-primary" name="upload_image">Upload Small Image</button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</section>

<?php
include('footer.php');
?>
