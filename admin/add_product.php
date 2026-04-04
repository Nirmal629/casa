<?php
include('dbConnection.php');
include('header.php');
include('sidebar.php');

if(isset($_POST['submit'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $price = mysqli_real_escape_string($conn, trim($_POST['price']));
    $quantity = mysqli_real_escape_string($conn, trim($_POST['quantity']));

    // Image Upload Handling
    $imageName = $_FILES['image']['name'];
    $imageTmp = $_FILES['image']['tmp_name'];
    $imagePath = 'assets/' . basename($imageName);

    if (move_uploaded_file($imageTmp, $imagePath)) {
        $insert = mysqli_query($conn, "INSERT INTO ca_products (PRODUCT_NAME, PRICE, QUANTITY, IMAGE) 
                                       VALUES ('$name', '$price', '$quantity', '$imagePath')");
        if ($insert) {
            echo "<script>alert('Product added successfully!'); window.location.href='manage_products.php';</script>";
        } else {
            echo "Database error: " . mysqli_error($conn);
        }
    } else {
        echo "<script>alert('Image upload failed.');</script>";
    }
}
?>

<section role="main" class="content-body">
    <header class="page-header">
        <h2>Add Product</h2>
        <div class="right-wrapper pull-right">
            <ol class="breadcrumbs">
                <li><a href="dashboard.php"><i class="fa fa-home"></i></a></li>
                <li><span>Manage Product</span></li>
                <li><span>Add Product</span></li>
            </ol>
            <a class="sidebar-right-toggle" data-open="sidebar-right"><i class="fa fa-chevron-left"></i></a>
        </div>
    </header>

    <div class="row">
        <div class="col-lg-12">
            <section class="panel">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="#" class="panel-action panel-action-toggle" data-panel-toggle></a>
                        <a href="#" class="panel-action panel-action-dismiss" data-panel-dismiss></a>
                    </div>
                    <h2 class="panel-title">Add Product</h2>
                </header>
                <div class="panel-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="name"><strong>Product Name</strong><span>*</span></label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter product name" required>
                        </div>

                        <div class="form-group">
                            <label for="price"><strong>Price</strong><span>*</span></label>
<input type="number" step="0.01" class="form-control" id="price" name="price" placeholder="Enter price" required>
                        </div>

                        <div class="form-group">
                            <label for="quantity"><strong>Quantity</strong><span>*</span></label>
                            <input type="number" class="form-control" id="quantity" name="quantity" placeholder="Enter quantity" required>
                        </div>

                        <div class="form-group">
                            <label for="image"><strong>Product Image</strong><span>*</span></label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" name="submit">Add Product</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
</section>

<?php include('footer.php'); ?>
