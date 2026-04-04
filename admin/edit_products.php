<?php
include('dbConnection.php');
include('header.php');
include('sidebar.php');

/* -------- VALIDATE ID -------- */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('Invalid product ID'); window.location.href='manage_products.php';</script>";
    exit;
}

$id = intval($_GET['id']);

/* -------- FETCH PRODUCT -------- */
$product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM ca_products WHERE ID = $id"));

if (!$product) {
    echo "<script>alert('Product not found'); window.location.href='manage_products.php';</script>";
    exit;
}

/* -------- FETCH DEPARTMENTS -------- */
$departments = mysqli_query($conn,"SELECT * FROM ca_department ORDER BY NAME ASC");

/* -------- FETCH PRODUCT TYPES (FOR EDIT LOAD) -------- */
$productTypes = mysqli_query($conn,"
    SELECT * FROM ca_product_type 
    WHERE DEPARTMENT_ID = {$product['DEPARTMENT_ID']}
");

/* -------- UPDATE -------- */
if (isset($_POST['update'])) {

    $name = mysqli_real_escape_string($conn, trim($_POST['product_name']));
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $size = mysqli_real_escape_string($conn, trim($_POST['size']));
    $department_id = intval($_POST['department_id']);
    $product_type_id = intval($_POST['product_type_id']);

    /* IMAGE */
    if (!empty($_FILES['image']['name'])) {

        $imgName = basename($_FILES["image"]["name"]);
        $targetDir = "assets/";
        $targetFile = $targetDir . time() . "_" . $imgName;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {

            if (!empty($product['IMAGE']) && file_exists($product['IMAGE'])) {
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

    $update = mysqli_query($conn, "
        UPDATE ca_products SET
        PRODUCT_NAME = '$name',
        DEPARTMENT_ID = '$department_id',
        PRODUCT_TYPE_ID = '$product_type_id',
        PRICE = $price,
        QUANTITY = $quantity,
        SIZE = '$size',
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
</header>

<section class="panel">
<header class="panel-heading">
<h2 class="panel-title">Edit Product</h2>
</header>

<div class="panel-body">
<form method="POST" enctype="multipart/form-data">

<div class="row">

<div class="col-md-6 form-group">
<label>Product Name *</label>
<input type="text" name="product_name" class="form-control"
value="<?= htmlspecialchars($product['PRODUCT_NAME']) ?>" required>
</div>

<div class="col-md-6 form-group">
<label>Department *</label>
<select name="department_id" id="department" class="form-control" required>
<option value="">Select Department</option>
<?php while($d=mysqli_fetch_assoc($departments)): ?>
<option value="<?= $d['ID'] ?>"
<?= ($product['DEPARTMENT_ID']==$d['ID'])?'selected':'' ?>>
<?= htmlspecialchars($d['NAME']) ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="col-md-6 form-group">
<label>Product Type *</label>
<select name="product_type_id" id="product_type" class="form-control" required>
<option value="">Select Product Type</option>
<?php while($pt=mysqli_fetch_assoc($productTypes)): ?>
<option value="<?= $pt['ID'] ?>"
<?= ($product['PRODUCT_TYPE_ID']==$pt['ID'])?'selected':'' ?>>
<?= htmlspecialchars($pt['NAME']) ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="col-md-6 form-group">
<label>Price *</label>
<input type="number" name="price" step="0.01" class="form-control"
value="<?= $product['PRICE'] ?>" required>
</div>

<div class="col-md-6 form-group">
<label>Quantity *</label>
<input type="number" name="quantity" class="form-control"
value="<?= $product['QUANTITY'] ?>" required>
</div>

<div class="col-md-6 form-group">
<label>Size</label>
<input type="text" name="size" class="form-control"
value="<?= htmlspecialchars($product['SIZE']) ?>">
</div>

</div>

<div class="form-group">
<label>Current Image</label><br>
<img src="<?= $product['IMAGE'] ?>" width="100">
</div>

<div class="form-group">
<label>Change Image (optional)</label>
<input type="file" name="image" class="form-control">
</div>

<div class="form-group">
<button type="submit" name="update" class="btn btn-success">
Update Product
</button>
<a href="manage_products.php" class="btn btn-default">Back</a>
</div>

</form>
</div>
</section>
</section>

<script>
document.getElementById('department').addEventListener('change', function(){
    const deptId = this.value;
    const typeSelect = document.getElementById('product_type');

    fetch('add_product.php?fetch_types=' + deptId)
    .then(res => res.text())
    .then(data => {
        typeSelect.innerHTML = data;
    });
});
</script>

<?php include('footer.php'); ?>