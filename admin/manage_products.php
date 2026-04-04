<?php
include('dbConnection.php');
include('header.php');
include('sidebar.php');
?>

<section role="main" class="content-body">
<header class="page-header">
    <h2>Manage Products</h2>
</header>

<section class="panel">
<header class="panel-heading">
<h2 class="panel-title">Product List</h2>
</header>

<div class="panel-body">
<div style="overflow-x: auto;">

<table class="table table-bordered table-striped mb-none" id="datatable-default">
<thead>
<tr>
<th>SL NO</th>
<th>Product Name</th>
<th>Department</th>
<th>Product Type</th>
<th>Price</th>
<th>Quantity</th>
<th>Size</th>
<th>Image</th>
<th>Action</th>
</tr>
</thead>

<tbody>
<?php
$sql = "
SELECT p.*, 
       d.NAME AS DEPARTMENT_NAME, 
       pt.NAME AS PRODUCT_TYPE_NAME
FROM ca_products p
LEFT JOIN ca_department d ON d.ID = p.DEPARTMENT_ID
LEFT JOIN ca_product_type pt ON pt.ID = p.PRODUCT_TYPE_ID
ORDER BY p.ID DESC
";

$result = $conn->query($sql);
$i = 1;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        echo "<tr>";
        echo "<td>".$i."</td>";
        echo "<td>".htmlspecialchars($row['PRODUCT_NAME'])."</td>";
        echo "<td>".htmlspecialchars($row['DEPARTMENT_NAME'] ?? '-')."</td>";
        echo "<td>".htmlspecialchars($row['PRODUCT_TYPE_NAME'] ?? '-')."</td>";
        echo "<td>CAD ".number_format($row['PRICE'], 2)."</td>";
        echo "<td>".$row['QUANTITY']."</td>";
        echo "<td>".$row['SIZE']."</td>";
        echo "<td><img src='".$row['IMAGE']."' width='80' height='60'></td>";
        echo "<td>
            <a href='edit_products.php?id=".$row['ID']."' class='btn btn-warning btn-sm'>
                <i class='fa fa-pencil'></i> Edit
            </a>
            <button class='btn btn-danger btn-sm delete-product'
                data-id='".$row['ID']."'
                onclick='deleteProduct(this)'>
                <i class='fa fa-trash'></i> Delete
            </button>
        </td>";
        echo "</tr>";

        $i++;
    }
} else {
    echo "<tr><td colspan='9' class='text-center'>No products found</td></tr>";
}
?>
</tbody>
</table>

</div>
</div>
</section>
</section>

<script>
function deleteProduct(button) {
    const productId = $(button).data('id');
    if (confirm('Are you sure you want to delete this product?')) {
        $.ajax({
            url: 'api/delete_product.php',
            type: 'POST',
            data: { id: productId },
            success: function (response) {
                const data = JSON.parse(response);
                if (data.success) {
                    alert('Product deleted successfully.');
                    location.reload();
                } else {
                    alert('Error deleting product.');
                }
            },
            error: function () {
                alert('An unexpected error occurred.');
            }
        });
    }
}
</script>

<?php include('footer.php'); ?>