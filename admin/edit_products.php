<?php
include('dbConnection.php');
include('header.php');
include('sidebar.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('Invalid product ID'); window.location.href='manage_products.php';</script>";
    exit;
}

$id = intval($_GET['id']);
$product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM ca_products WHERE ID = $id"));

if (!$product) {
    echo "<script>alert('Product not found'); window.location.href='manage_products.php';</script>";
    exit;
}

if (isset($_POST['update'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['product_name']));
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);

    // Handle optional image upload
    if (!empty($_FILES['image']['name'])) {
        $imgName = basename($_FILES["image"]["name"]);
        $targetDir = "assets/";
        $targetFile = $targetDir . time() . "_" . $imgName;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
            // Delete old image if exists
            if (file_exists($product['IMAGE'])) {
                unlink($product['IMAGE']);
            }
            $imagePath = $targetFile;
        } else {
            echo "<script>alert('Image upload failed');</script>";
            $imagePath = $product['IMAGE'];
        }
    } else {
        $imagePath = $product['IMAGE'];
    }

    $update = mysqli_query($conn, "UPDATE ca_products SET 
        PRODUCT_NAME = '$name',
        PRICE = $price,
        QUANTITY = $quantity,
        IMAGE = '$imagePath'
        WHERE ID = $id
    ");

    if ($update) {
        echo "<script>alert('Product updated successfully'); window.location.href='manage_products.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<section role="main" class="content-body">
    <header class="page-header">
        <h2>Edit Product</h2>
        <div class="right-wrapper pull-right">
            <ol class="breadcrumbs">
                <li><a href="index.php"><i class="fa fa-home"></i></a></li>
                <li><span>Manage Products</span></li>
                <li><span>Edit Product</span></li>
            </ol>
        </div>
    </header>

    <section class="panel">
        <header class="panel-heading">
            <h2 class="panel-title">Edit Product</h2>
        </header>
        <div class="panel-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Product Name <span>*</span></label>
                    <input type="text" name="product_name" class="form-control" value="<?= htmlspecialchars($product['PRODUCT_NAME']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Price <span>*</span></label>
                    <input type="number" name="price" step="0.01" class="form-control" value="<?= $product['PRICE'] ?>" required>
                </div>
                <div class="form-group">
                    <label>Quantity <span>*</span></label>
                    <input type="number" name="quantity" class="form-control" value="<?= $product['QUANTITY'] ?>" required>
                </div>
                <div class="form-group">
                    <label>Current Image</label><br>
                    <img src="<?= $product['IMAGE'] ?>" width="100" height="80" alt="Product Image">
                </div>
                <div class="form-group">
                    <label>Change Image (optional)</label>
                    <input type="file" name="image" class="form-control">
                </div>
                <div class="form-group">
                    <button type="submit" name="update" class="btn btn-success">Update Product</button>
                    <a href="manage_products.php" class="btn btn-secondary">Back</a>
                </div>
            </form>
        </div>
    </section>
</section>

<?php include('footer.php'); ?>
