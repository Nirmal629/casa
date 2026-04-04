<?php
// Handle status update FIRST before any HTML
include('dbConnection.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $updateSQL = "UPDATE ca_orders SET ORDER_STATUS = '$status' WHERE ORDER_ID = $order_id";
    if ($conn->query($updateSQL)) {
        header("Location: manage_order.php");
        exit;
    } else {
        die("Failed to update order status: " . $conn->error);
    }
}

// Now load UI
include('header.php');
include('sidebar.php');
?>

<section role="main" class="content-body">
    <header class="page-header">
        <h2>Manage Orders</h2>
        <div class="right-wrapper pull-right">
            <ol class="breadcrumbs">
                <li><a href="index.php"><i class="fa fa-home"></i></a></li>
                <li><span>Orders</span></li>
            </ol>
        </div>
    </header>

    <section class="panel">
        <header class="panel-heading">
            <h2 class="panel-title">Order List</h2>
        </header>
        <div class="panel-body">
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
                <div class="alert alert-success">Order status updated successfully.</div>
            <?php endif; ?>

            <div style="overflow-x: auto;">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>SL NO</th>
                            <th>Order Date</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM ca_orders ORDER BY ORDER_ID DESC";
                        $result = $conn->query($sql);
                        $i = 1;

                        while ($order = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>{$i}</td>";
                            echo "<td><b>" . date('d M Y', strtotime($order['ORDER_DATE'] ?? 'now')) . "</b></td>";
                            echo "<td>" . htmlspecialchars($order['CUSTOMER_NAME']) . "</td>";
                            echo "<td>{$order['PHONE']}<br>{$order['EMAIL']}</td>";
                            echo "<td>" . nl2br(htmlspecialchars($order['ADDRESS'])) . "</td>";

                            $itemsSQL = "SELECT * FROM ca_orders_item WHERE ORDER_ID = {$order['ORDER_ID']}";
                            $itemsRes = $conn->query($itemsSQL);
                            $itemDetails = '';
                            while ($item = $itemsRes->fetch_assoc()) {
                                $itemDetails .= "<strong>{$item['PRODUCT_NAME']}</strong> ({$item['QUANTITY']} x CAD " . number_format($item['PRICE'], 2) . ")<br>";
                            }
                            echo "<td>$itemDetails</td>";

                            echo "<td>CAD " . number_format($order['TOTAL_AMOUNT'], 2) . "</td>";

                            echo "<td>
                                <form method='post' action=''>
                                    <input type='hidden' name='order_id' value='{$order['ORDER_ID']}'>
                                    <select name='status' class='form-control' onchange='this.form.submit()'>
                                        <option value='Pending' " . ($order['ORDER_STATUS'] === 'Pending' ? 'selected' : '') . ">Pending</option>
                                        <option value='Completed' " . ($order['ORDER_STATUS'] === 'Completed' ? 'selected' : '') . ">Completed</option>
                                        <option value='Cancelled' " . ($order['ORDER_STATUS'] === 'Cancelled' ? 'selected' : '') . ">Cancelled</option>
                                    </select>
                                </form>
                            </td>";



                            echo "</tr>";
                            $i++;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</section>

<?php include('footer.php'); ?>
