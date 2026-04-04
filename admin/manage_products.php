<?php
include('dbConnection.php');
include('header.php');
include('sidebar.php');
?>
<section role="main" class="content-body">
    <header class="page-header">
        <h2>Manage Products</h2>
        <div class="right-wrapper pull-right">
            <ol class="breadcrumbs">
                <li><a href="index.php"><i class="fa fa-home"></i></a></li>
                <li><span>Manage Products</span></li>
                <li><span>List Products</span></li>
            </ol>
            <a class="sidebar-right-toggle" data-open="sidebar-right"><i class="fa fa-chevron-left"></i></a>
        </div>
    </header>

    <!-- start: page -->
    <section class="panel">
        <header class="panel-heading">
            <div class="panel-actions">
                <a href="#" class="panel-action panel-action-toggle" data-panel-toggle></a>
                <a href="#" class="panel-action panel-action-dismiss" data-panel-dismiss></a>
            </div>
            <h2 class="panel-title">Product List</h2>
        </header>
        <div class="panel-body">
            <div style="overflow-x: auto;">
                <table class="table table-bordered table-striped mb-none" id="datatable-default">
                    <thead>
                        <tr>
                            <th>SL NO</th>
                            <th>Product Name</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Image</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM ca_products ORDER BY ID DESC";
                        $result = $conn->query($sql);
                        $i = 1;
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $i . "</td>";
                                echo "<td>" . htmlspecialchars($row['PRODUCT_NAME']) . "</td>";
                                echo "<td>CAD " . number_format($row['PRICE'], 2) . "</td>";
                                echo "<td>" . $row['QUANTITY'] . "</td>";
                                echo "<td><img src='" . $row['IMAGE'] . "' width='80' height='60'></td>";
                                echo "<td>
        <a href='edit_products.php?id=" . $row['ID'] . "' class='btn btn-warning'>
            <i class='fa fa-pencil'></i> Edit
        </a>
        <button class='btn btn-danger delete-product' 
            data-id='" . $row['ID'] . "' 
            onclick='deleteProduct(this)'>
            <i class='fa fa-trash'></i> Delete
        </button>
      </td>";

                                echo "</tr>";
                                $i++;
                            }
                        } else {
                            echo "<tr><td colspan='6'>No products found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <!-- end: page -->
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
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('An unexpected error occurred.');
            }
        });
    }
}
</script>

<?php include('footer.php'); ?>
