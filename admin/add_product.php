<?php
include('dbConnection.php');
include('header.php');
include('sidebar.php');

/* ---------- FETCH DEPARTMENTS ---------- */
$departments = mysqli_query($conn,"SELECT * FROM ca_department ORDER BY NAME ASC");

/* ---------- AJAX FETCH PRODUCT TYPES ---------- */
if(isset($_GET['fetch_types'])){
    $dept_id = intval($_GET['fetch_types']);
    $types = mysqli_query($conn,"SELECT * FROM ca_product_type WHERE DEPARTMENT_ID=$dept_id ORDER BY NAME ASC");

    echo "<option value=''>Select Product Type</option>";
    while($t=mysqli_fetch_assoc($types)){
        echo "<option value='{$t['ID']}'>{$t['NAME']}</option>";
    }
    exit;
}

/* ---------- INSERT PRODUCT ---------- */
if(isset($_POST['submit'])) {

    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $price = mysqli_real_escape_string($conn, trim($_POST['price']));
    $quantity = mysqli_real_escape_string($conn, trim($_POST['quantity']));
    $size = mysqli_real_escape_string($conn, trim($_POST['size']));
    $department_id = intval($_POST['department_id']);
    $product_type_id = intval($_POST['product_type_id']);

    $imageName = $_FILES['image']['name'];
    $imageTmp = $_FILES['image']['tmp_name'];
    $imagePath = 'assets/' . basename($imageName);

    if (move_uploaded_file($imageTmp, $imagePath)) {

        $insert = mysqli_query($conn,"
            INSERT INTO ca_products 
            (PRODUCT_NAME, DEPARTMENT_ID, PRODUCT_TYPE_ID, PRICE, QUANTITY, SIZE, IMAGE) 
            VALUES 
            ('$name', '$department_id', '$product_type_id', '$price', '$quantity', '$size', '$imagePath')
        ");

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
</header>

<div class="row">
<div class="col-lg-12">
<section class="panel">
<header class="panel-heading">
<h2 class="panel-title">Add Product</h2>
</header>

<div class="panel-body">
<form method="POST" enctype="multipart/form-data">

<div class="row">

    <!-- Product Name -->
    <div class="col-md-6 form-group">
        <label><strong>Product Name</strong></label>
        <input type="text" class="form-control" name="name" required>
    </div>

    <!-- Department -->
    <div class="col-md-6 form-group">
        <label><strong>Department</strong></label>
        <select class="form-control" name="department_id" id="department" required>
            <option value="">Select Department</option>
            <?php 
            mysqli_data_seek($departments, 0); 
            while($d=mysqli_fetch_assoc($departments)): ?>
            <option value="<?= $d['ID'] ?>">
                <?= htmlspecialchars($d['NAME']) ?>
            </option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- Product Type -->
    <div class="col-md-6 form-group">
        <label><strong>Product Type</strong></label>
        <select class="form-control" name="product_type_id" id="product_type" required>
            <option value="">Select Product Type</option>
        </select>
    </div>

    <!-- Price -->
    <div class="col-md-6 form-group">
        <label><strong>Price</strong></label>
        <input type="number" step="0.01" class="form-control" name="price" required>
    </div>

    <!-- Quantity -->
    <div class="col-md-6 form-group">
        <label><strong>Quantity</strong></label>
        <input type="number" class="form-control" name="quantity" required>
    </div>

    <!-- Size -->
    <div class="col-md-6 form-group">
        <label><strong>Size</strong></label>
        <input type="text" class="form-control" name="size">
    </div>

    <!-- Image -->
    <div class="col-md-6 form-group">
        <label><strong>Product Image</strong></label>
        <input type="file" class="form-control" name="image" accept="image/*" required>
    </div>

</div>

<div class="form-group mt-3">
    <button type="submit" class="btn btn-primary" name="submit">
        Add Product
    </button>
</div>

</form>
</div>
</section>
</div>
</div>
</section>

<script>
document.getElementById('department').addEventListener('change', function(){
    const deptId = this.value;
    const typeSelect = document.getElementById('product_type');

    if(!deptId){
        typeSelect.innerHTML = "<option value=''>Select Product Type</option>";
        return;
    }

    fetch("add_product.php?fetch_types=" + deptId)
    .then(res => res.text())
    .then(data => {
        typeSelect.innerHTML = data;
    });
});
</script>

<?php include('footer.php'); ?>