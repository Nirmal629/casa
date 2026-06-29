<?php
include('dbConnection.php');
include('header.php');
include('sidebar.php');

/* ---------------- DELETE ---------------- */
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    mysqli_query($conn,"DELETE FROM ca_product_type WHERE ID=$id");
    echo "<script>alert('Product Type deleted successfully!'); window.location.href='manage_product_type.php';</script>";
    exit;
}

/* ---------------- ADD / UPDATE ---------------- */
if(isset($_POST['submit'])){

    $name = mysqli_real_escape_string($conn,trim($_POST['name']));
    $department_id = intval($_POST['department_id']);

    if(isset($_POST['edit_id']) && $_POST['edit_id']!=''){
        $edit_id = intval($_POST['edit_id']);
        mysqli_query($conn,"UPDATE ca_product_type 
                            SET NAME='$name', DEPARTMENT_ID='$department_id'
                            WHERE ID=$edit_id");
        echo "<script>alert('Product Type updated successfully!'); window.location.href='manage_product_type.php';</script>";
        exit;
    }
    else{
        mysqli_query($conn,"INSERT INTO ca_product_type(DEPARTMENT_ID,NAME) 
                            VALUES('$department_id','$name')");
        echo "<script>alert('Product Type added successfully!'); window.location.href='manage_product_type.php';</script>";
        exit;
    }
}

/* ---------------- FETCH FOR EDIT ---------------- */
$editData = null;
if(isset($_GET['edit'])){
    $id = intval($_GET['edit']);
    $res = mysqli_query($conn,"SELECT * FROM ca_product_type WHERE ID=$id");
    $editData = mysqli_fetch_assoc($res);
}

/* ---------------- FETCH DEPARTMENTS ---------------- */
$departments = mysqli_query($conn,"SELECT * FROM ca_department ORDER BY NAME ASC");

/* ---------------- FETCH PRODUCT TYPES ---------------- */
$productTypes = mysqli_query($conn,"
    SELECT pt.*, d.NAME as DEPT_NAME
    FROM ca_product_type pt
    JOIN ca_department d ON d.ID = pt.DEPARTMENT_ID
    ORDER BY pt.ID DESC
");
?>

<section role="main" class="content-body">
<header class="page-header">
    <h2>Manage Product Type</h2>
</header>

<div class="row">
<div class="col-lg-12">

<!-- ================= ADD PRODUCT TYPE ================= -->
<section class="panel">
<header class="panel-heading">
<h2 class="panel-title">
<?= $editData ? 'Edit Product Type' : 'Add Product Type' ?>
</h2>
</header>

<div class="panel-body">
<form method="POST">

<input type="hidden" name="edit_id" value="<?= $editData['ID'] ?? '' ?>">

<div class="form-group">
<label style="font-weight:bold">Select Department<span>*</span></label>
<select class="form-control" name="department_id" required>
<option value="">Select Department</option>
<?php while($dept=mysqli_fetch_assoc($departments)): ?>
<option value="<?= $dept['ID'] ?>"
<?= ($editData && $editData['DEPARTMENT_ID']==$dept['ID'])?'selected':'' ?>>
<?= htmlspecialchars($dept['NAME']) ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="form-group">
<label style="font-weight:bold">Product Type Name<span>*</span></label>
<input type="text" class="form-control" name="name"
       value="<?= $editData['NAME'] ?? '' ?>"
       placeholder="Enter product type name" required>
</div>

<div class="form-group">
<button type="submit" class="btn btn-primary" name="submit">
<?= $editData ? 'Update' : 'Save' ?>
</button>

<?php if($editData): ?>
<a href="manage_product_type.php" class="btn btn-default">Cancel</a>
<?php endif; ?>
</div>

</form>
</div>
</section>

<!-- ================= VIEW PRODUCT TYPES ================= -->
<section class="panel">
<header class="panel-heading">
<h2 class="panel-title">View Product Types</h2>
</header>

<div class="panel-body">
<table class="table table-bordered table-striped">
<thead>
<tr>
<th width="8%">ID</th>
<th>Department</th>
<th>Product Type</th>
<th width="20%">Action</th>
</tr>
</thead>

<tbody>
<?php if(mysqli_num_rows($productTypes)>0): ?>
<?php while($row=mysqli_fetch_assoc($productTypes)): ?>
<tr>
<td><?= $row['ID'] ?></td>
<td><?= htmlspecialchars($row['DEPT_NAME']) ?></td>
<td><?= htmlspecialchars($row['NAME']) ?></td>
<td>
<a href="manage_product_type.php?edit=<?= $row['ID'] ?>"
   class="btn btn-sm btn-info">Edit</a>

<a href="manage_product_type.php?delete=<?= $row['ID'] ?>"
   class="btn btn-sm btn-danger"
   onclick="return confirm('Are you sure you want to delete this product type?');">
   Delete
</a>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr>
<td colspan="4" class="text-center">No product types found.</td>
</tr>
<?php endif; ?>
</tbody>

</table>
</div>
</section>

</div>
</div>
</section>

<?php include('footer.php'); ?>