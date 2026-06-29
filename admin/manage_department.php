<?php
include('dbConnection.php');
include('header.php');
include('sidebar.php');

/* ---------------- DELETE ---------------- */
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    mysqli_query($conn,"DELETE FROM ca_department WHERE ID=$id");
    echo "<script>alert('Department deleted successfully!'); window.location.href='manage_department.php';</script>";
    exit;
}

/* ---------------- ADD / UPDATE ---------------- */
if(isset($_POST['submit'])){

    $name = mysqli_real_escape_string($conn,trim($_POST['name']));

    if(isset($_POST['edit_id']) && $_POST['edit_id']!=''){
        $edit_id = intval($_POST['edit_id']);
        mysqli_query($conn,"UPDATE ca_department SET NAME='$name' WHERE ID=$edit_id");
        echo "<script>alert('Department updated successfully!'); window.location.href='manage_department.php';</script>";
        exit;
    }
    else{
        mysqli_query($conn,"INSERT INTO ca_department(NAME) VALUES('$name')");
        echo "<script>alert('Department added successfully!'); window.location.href='manage_department.php';</script>";
        exit;
    }
}

/* ---------------- FETCH FOR EDIT ---------------- */
$editData = null;
if(isset($_GET['edit'])){
    $id = intval($_GET['edit']);
    $res = mysqli_query($conn,"SELECT * FROM ca_department WHERE ID=$id");
    $editData = mysqli_fetch_assoc($res);
}

/* ---------------- FETCH ALL ---------------- */
$departments = mysqli_query($conn,"SELECT * FROM ca_department ORDER BY ID DESC");
?>

<section role="main" class="content-body">
<header class="page-header">
    <h2>Manage Department</h2>
</header>

<div class="row">
<div class="col-lg-12">

<!-- ================= ADD DEPARTMENT ================= -->
<section class="panel">
<header class="panel-heading">
<h2 class="panel-title">
<?= $editData ? 'Edit Department' : 'Add Department' ?>
</h2>
</header>

<div class="panel-body">
<form method="POST">

<input type="hidden" name="edit_id" value="<?= $editData['ID'] ?? '' ?>">

<div class="form-group">
<label style="font-weight:bold">Department Name<span>*</span></label>
<input type="text" class="form-control" name="name"
       value="<?= $editData['NAME'] ?? '' ?>"
       placeholder="Enter department name" required>
</div>

<div class="form-group">
<button type="submit" class="btn btn-primary" name="submit">
<?= $editData ? 'Update' : 'Save' ?>
</button>

<?php if($editData): ?>
<a href="manage_department.php" class="btn btn-default">Cancel</a>
<?php endif; ?>
</div>

</form>
</div>
</section>

<!-- ================= VIEW DEPARTMENT ================= -->
<section class="panel">
<header class="panel-heading">
<h2 class="panel-title">View Departments</h2>
</header>

<div class="panel-body">
<table class="table table-bordered table-striped">
<thead>
<tr>
<th width="10%">ID</th>
<th>Department Name</th>
<th width="20%">Action</th>
</tr>
</thead>

<tbody>
<?php if(mysqli_num_rows($departments)>0): ?>
<?php while($row=mysqli_fetch_assoc($departments)): ?>
<tr>
<td><?= $row['ID'] ?></td>
<td><?= htmlspecialchars($row['NAME']) ?></td>
<td>
<a href="manage_department.php?edit=<?= $row['ID'] ?>"
   class="btn btn-sm btn-info">Edit</a>

<a href="manage_department.php?delete=<?= $row['ID'] ?>"
   class="btn btn-sm btn-danger"
   onclick="return confirm('Are you sure you want to delete this department?');">
   Delete
</a>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr>
<td colspan="3" class="text-center">No departments found.</td>
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