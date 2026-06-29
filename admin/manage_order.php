<?php
include('dbConnection.php');

function generateBookingNo()
{
    return 'CASA' . date('YmdHis') . rand(100, 999);
}

/* ============================
   HANDLE MANUAL ORDER
============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_order_submit'])) {
    $booking_no = generateBookingNo();

    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $created_by = intval($_POST['created_by']);
    $order_date = date('Y-m-d');

    $total = 0;
    foreach ($_POST['price'] as $i => $p) {
        $total += floatval($p) * intval($_POST['qty'][$i]);
    }

    mysqli_query($conn, "
        INSERT INTO ca_orders
        (BOOKING_NO,CUSTOMER_NAME,PHONE,EMAIL,ADDRESS,ORDER_DATE,TOTAL_AMOUNT,DELIVERY_CHARGE,FULFILLEDBY)
        VALUES ('$booking_no','$name','$phone','$email','$address','$order_date','$total',0,'$created_by')
    ");

    $order_id = mysqli_insert_id($conn);

    foreach ($_POST['product_id'] as $i => $pid) {
        $qty = intval($_POST['qty'][$i]);
        if ($qty <= 0) {
            continue;
        }

        $price = floatval($_POST['price'][$i]);
        $subtotal = $price * $qty;
        $product_id = intval($pid);
        $pname = mysqli_real_escape_string($conn, $_POST['product_name'][$i]);
        $size = mysqli_real_escape_string($conn, $_POST['size'][$i] ?? '');
        $tname = mysqli_real_escape_string($conn, $_POST['tname'][$i] ?? '');

        mysqli_query($conn, "
            INSERT INTO ca_orders_item
            (ORDER_ID,BOOKING_NO,PRODUCT_ID,PRODUCT_NAME,PRICE,QUANTITY,SIZE,TNAME,SUBTOTAL,FULFILLEDBY)
            VALUES ('$order_id','$booking_no','$product_id','$pname','$price','$qty','$size','$tname','$subtotal','$created_by')
        ");
    }

    header("Location: manage_order.php?manual_order=success&booking_no=" . urlencode($booking_no));
    exit;
}

/* ============================
   HANDLE AJAX UPDATE
============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $item_id = intval($_POST['item_id']);
    $field   = $_POST['field'];
    $value   = mysqli_real_escape_string($conn,$_POST['value']);
    $time    = date('Y-m-d H:i:s');

    switch ($field) {

        case 'STATUS':

            if($value == 'Cancelled'){
                $sql="UPDATE ca_orders_item SET
                STATUS='Cancelled',
                STATUS_TIME='$time',
                FULFILLED_STATUS=NULL,
                FULFILLED_STATUS_TIME=NULL,
                PAYMENT_TYPE=NULL,
                PAYMENT_TYPE_TIME=NULL,
                PAYMENT_STATUS=NULL,
                PAYMENT_STATUS_TIME=NULL,
                FULFILLEDBY=NULL,
                FULFILLEDBY_TIME=NULL
                WHERE ITEM_ID=$item_id";
            } else {
                $sql="UPDATE ca_orders_item SET
                STATUS='$value',
                STATUS_TIME='$time'
                WHERE ITEM_ID=$item_id";
            }
        break;

        case 'FULFILLEDBY':
            $sql="UPDATE ca_orders_item SET
            FULFILLEDBY='$value',
            FULFILLEDBY_TIME='$time'
            WHERE ITEM_ID=$item_id";
        break;

        case 'FULFILLED_STATUS':
            $sql="UPDATE ca_orders_item SET
            FULFILLED_STATUS='$value',
            FULFILLED_STATUS_TIME='$time'
            WHERE ITEM_ID=$item_id";
        break;

        case 'PAYMENT_TYPE':
            $sql="UPDATE ca_orders_item SET
            PAYMENT_TYPE='$value',
            PAYMENT_TYPE_TIME='$time'
            WHERE ITEM_ID=$item_id";
        break;

        case 'PAYMENT_STATUS':
            $sql="UPDATE ca_orders_item SET
            PAYMENT_STATUS='$value',
            PAYMENT_STATUS_TIME='$time'
            WHERE ITEM_ID=$item_id";
        break;

        default: exit;
    }

    $conn->query($sql);

    /* AUTO COMPLETE */
    $conn->query("
        UPDATE ca_orders_item
        SET STATUS='Completed',
            STATUS_TIME='$time'
        WHERE ITEM_ID=$item_id
        AND FULFILLED_STATUS='DELIVERED'
        AND PAYMENT_STATUS='PAID'
    ");

    exit;
}

/* ============================
   FILTER LOGIC
============================ */

$where = [];

if(!empty($_GET['customer'])){
    $customer = mysqli_real_escape_string($conn,$_GET['customer']);
    $where[] = "o.CUSTOMER_NAME LIKE '%$customer%'";
}

if(!empty($_GET['item'])){
    $item = mysqli_real_escape_string($conn,$_GET['item']);
    $where[] = "i.PRODUCT_NAME LIKE '%$item%'";
}

if(!empty($_GET['status'])){
    $status = mysqli_real_escape_string($conn,$_GET['status']);
    $where[] = "i.STATUS='$status'";
}

if(!empty($_GET['fulfilledby'])){
    $fulfilledby = intval($_GET['fulfilledby']);
    $where[] = "i.FULFILLEDBY='$fulfilledby'";
}

if(!empty($_GET['payment_type'])){
    $ptype = mysqli_real_escape_string($conn,$_GET['payment_type']);
    $where[] = "i.PAYMENT_TYPE='$ptype'";
}

if(!empty($_GET['payment_status'])){
    $pstatus = mysqli_real_escape_string($conn,$_GET['payment_status']);
    $where[] = "i.PAYMENT_STATUS='$pstatus'";
}

$whereSql = count($where) ? "WHERE ".implode(" AND ",$where) : "";

$q=$conn->query("
SELECT o.BOOKING_NO,o.ORDER_DATE,o.CUSTOMER_NAME,o.PHONE,o.EMAIL,o.ADDRESS,d.NAME AS DEPARTMENT_NAME,
pt.NAME AS PRODUCT_TYPE_NAME,
i.*
FROM ca_orders_item i
JOIN ca_orders o ON o.ORDER_ID=i.ORDER_ID
LEFT JOIN ca_products p ON p.ID=i.PRODUCT_ID
LEFT JOIN ca_department d ON d.ID=p.DEPARTMENT_ID
LEFT JOIN ca_product_type pt ON pt.ID=p.PRODUCT_TYPE_ID
$whereSql
ORDER BY o.ORDER_DATE DESC");

$totalSummaryQuery = $conn->query("
SELECT 
SUM(i.QUANTITY) AS total_qty,
SUM(i.QUANTITY * i.PRICE) AS grand_total
FROM ca_orders_item i
JOIN ca_orders o ON o.ORDER_ID=i.ORDER_ID
$whereSql
");

$summary = $totalSummaryQuery->fetch_assoc();

$totalQty = $summary['total_qty'] ?? 0;
$grandTotal = $summary['grand_total'] ?? 0;
$manualProducts = mysqli_query($conn, "SELECT * FROM ca_products ORDER BY PRODUCT_NAME ASC");
?>

<?php
include('header.php');
include('sidebar.php');
?>

<style>
    .table-scroll {
        overflow-x: auto
    }

    .table-scroll table {
        min-width: 1700px
    }

    .badge-time {
        font-size: 11px;
        color: #777
    }

    .top-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: nowrap;
        overflow-x: auto;
        white-space: nowrap;
        padding: 10px;
    }

    .top-bar input,
    .top-bar select {
        height: 30px;
        font-size: 12px;
    }

    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        color: #fff;
        display: inline-block;
        white-space: nowrap;
    }

    /* Completed = Green */
    .status-completed {
        background: #28a745;
    }

    /* Cancelled = Red */
    .status-cancelled {
        background: #dc3545;
    }

    /* Pending = Orange */
    .status-pending {
        background: #f0ad4e;
    }

    /* FIXED HEADER + SUMMARY */
    .fixed-top-section {
        /* position: sticky; */
        /* top: 0; */
        background: #fff;
        /* z-index: 1000; */
        padding: 10px;
        border-bottom: 5px solid #ddd;
    }

    /* FILTER BAR */
    .top-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: nowrap;
        overflow-x: auto;
        white-space: nowrap;
        margin-bottom: 8px;
    }

    /* SUMMARY */
    .summary-bar {
        display: flex;
        justify-content: space-between;
        font-weight: 600;
        background: #f8f9fa;
        padding: 8px 10px;
        border-radius: 4px;
    }

    /* ONLY TABLE SCROLLS */
    .table-scroll-area {
        height: 70vh;
        overflow-y: auto;
    }

    .panel {
        margin: 0;
        max-width: 100%;
    }

    .table-scroll-area {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .custom-table-toolbar,
    .custom-table-footer {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: space-between;
        padding: 10px 0;
    }

    .custom-table-search {
        max-width: 320px;
        width: 100%;
    }

    .custom-table-length {
        align-items: center;
        display: flex;
        gap: 8px;
        white-space: nowrap;
    }

    .custom-table-length select {
        width: 90px;
    }

    .custom-table-pagination {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .custom-page-btn {
        background: #fff;
        border: 1px solid #ccc;
        border-radius: 3px;
        color: #333;
        min-width: 34px;
        padding: 5px 9px;
    }

    .custom-page-btn.active {
        background: #0088cc;
        border-color: #0088cc;
        color: #fff;
    }

    .custom-page-btn:disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }

    .custom-table-empty {
        display: none;
        padding: 18px;
        text-align: center;
    }

    .products-toolbar-controls,
    .products-limit-control {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    @media (max-width:991px) {
        .fixed-top-section {
            position: relative;
        }

        .top-bar {
            align-items: stretch;
            flex-wrap: wrap;
            overflow-x: visible;
            white-space: normal;
        }

        .top-bar h3 {
            flex: 1 0 100%;
            margin: 0 0 8px !important;
        }

        .top-bar input,
        .top-bar select {
            max-width: 100%;
        }

        .summary-bar {
            align-items: flex-start;
            flex-direction: column;
            gap: 6px;
        }
    }

    @media (max-width:767px) {
        .fixed-top-section {
            padding: 10px;
        }

        .top-bar .btn {
            flex: 1 1 44px;
        }

        .top-bar input,
        .top-bar select {
            flex: 1 1 145px;
            width: 100%;
        }

        .table-scroll-area {
            height: auto;
            max-height: 65vh;
        }

        .custom-table-toolbar,
        .custom-table-footer {
            align-items: stretch;
            flex-direction: column;
        }

        .custom-table-search,
        .custom-table-length,
        .custom-table-length select {
            width: 100%;
        }
    }

    .manual-order-modal {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: rgba(0, 0, 0, 0.55);
        padding: 24px;
    }

    .manual-order-modal.is-open {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .manual-order-dialog {
        width: min(1100px, 100%);
        height: min(88vh, 850px);
        background: #fff;
        border-radius: 6px;
        box-shadow: 0 20px 55px rgba(0, 0, 0, 0.25);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .manual-order-modal-header {
        align-items: center;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        padding: 12px 15px;
    }

    .manual-order-modal-header h4 {
        margin: 0;
    }

    .manual-order-close {
        background: transparent;
        border: 0;
        color: #555;
        font-size: 24px;
        line-height: 1;
        padding: 0 4px;
    }

    .manual-order-modal-body {
        flex: 1;
        overflow-y: auto;
        padding: 15px;
    }

    .manual-order-products {
        border: 1px solid #ddd;
        max-height: 400px;
        overflow-y: auto;
        padding: 10px;
    }

    body.manual-order-modal-open {
        overflow: hidden;
    }

    @media (max-width:767px) {
        .manual-order-modal {
            padding: 10px;
        }

        .manual-order-dialog {
            height: 92vh;
        }
    }
</style>

<section role="main" class="content-body">
    <header class="page-header">
        <h2>Manage Orders</h2>
    </header>

    <section class="panel manage-orders-panel">

        <!-- ================= FILTER BAR ================= -->
        <div class="fixed-top-section">
            <form method="GET" class="top-bar">

                <!-- <a href="dashboard.php" class="btn btn-sm btn-default">
                    <i class="fa fa-arrow-left"></i>
                </a> -->

                <input type="text" name="customer" class="form-control"
                    style="width:140px;" placeholder="Customer"
                    value="<?= $_GET['customer'] ?? '' ?>">

                <input type="text" name="item" class="form-control"
                    style="width:140px;" placeholder="Item"
                    value="<?= $_GET['item'] ?? '' ?>">

                <select name="status" class="form-control" style="width:120px;">
                    <option value="">Status</option>
                    <option <?= (($_GET['status'] ?? '') == 'Pending') ? 'selected' : '' ?>>Pending</option>
                    <option <?= (($_GET['status'] ?? '') == 'Completed') ? 'selected' : '' ?>>Completed</option>
                    <option <?= (($_GET['status'] ?? '') == 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                </select>

                <select name="fulfilledby" class="form-control" style="width:140px;">
                    <option value="">Fulfilled By</option>
                    <option value="1" <?= (($_GET['fulfilledby'] ?? '') == '1') ? 'selected' : '' ?>>Anurag</option>
                    <option value="6" <?= (($_GET['fulfilledby'] ?? '') == '6') ? 'selected' : '' ?>>Ariyan</option>
                </select>

                <select name="payment_type" class="form-control" style="width:130px;">
                    <option value="">Payment Type</option>
                    <option <?= (($_GET['payment_type'] ?? '') == 'CASH') ? 'selected' : '' ?>>CASH</option>
                    <option <?= (($_GET['payment_type'] ?? '') == 'INTERACT') ? 'selected' : '' ?>>INTERACT</option>
                </select>

                <select name="payment_status" class="form-control" style="width:140px;">
                    <option value="">Payment Status</option>
                    <option <?= (($_GET['payment_status'] ?? '') == 'PAID') ? 'selected' : '' ?>>PAID</option>
                    <option <?= (($_GET['payment_status'] ?? '') == 'CREDIT') ? 'selected' : '' ?>>CREDIT</option>
                    <option <?= (($_GET['payment_status'] ?? '') == 'UNPAID') ? 'selected' : '' ?>>UNPAID</option>
                </select>

                <button type="submit" class="btn btn-info btn-sm">
                    <i class="fa fa-search"></i>
                </button>

                <a href="manage_order.php" class="btn btn-sm btn-primary">
                    <i class="fa fa-refresh"></i>
                </a>
            </form>

            <div class="summary-bar">
                <div>
                    Total Quantity: <?= $totalQty ?>
                </div>

                <div>
                    Grand Total: CAD <?= number_format($grandTotal, 2) ?>
                </div>
            </div>

        </div>



        <!-- ================= TABLE ================= -->
        <div class="panel-body">

            <div class="products-toolbar-controls" style="margin: 15px 0px;">
                <button type="button" class="btn btn-sm btn-success" id="openManualOrderModal">
                    <i class="fa fa-plus"></i>
                </button>
                <label class="custom-table-length" for="ordersPageSize" style="margin-bottom: 0;">
                    <!-- Show -->
                    <select class="form-control" id="ordersPageSize">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <!-- entries -->
                </label>
                <div style="width: 320px;">
                    <input type="search" class="form-control custom-table-search" id="ordersSearch" placeholder="Search orders">
                </div>
            </div>

            <div style="overflow-x: auto;">
                <div class="admin-table-scroll">
                    <table id="ordersTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Booking</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Status</th>
                                <th>Fulfilled By</th>
                                <th>Fulfillment</th>
                                <th>Payment Type</th>
                                <th>Payment Status</th>
                                <th>View</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php while ($r = $q->fetch_assoc()): ?>

                                <tr>
                                    <td><?= $r['BOOKING_NO'] ?></td>
                                    <td><?= date('d M Y', strtotime($r['ORDER_DATE'])) ?></td>
                                    <td><?= htmlspecialchars($r['CUSTOMER_NAME']) ?></td>
                                    <td><?= htmlspecialchars($r['PRODUCT_NAME']) ?></td>
                                    <td><?= $r['QUANTITY'] ?></td>

                                    <td>
                                        <?php
                                        $statusClass = '';

                                        if ($r['STATUS'] == 'Completed') {
                                            $statusClass = 'status-completed';
                                        } elseif ($r['STATUS'] == 'Cancelled') {
                                            $statusClass = 'status-cancelled';
                                        } elseif ($r['STATUS'] == 'Pending') {
                                            $statusClass = 'status-pending';
                                        }
                                        ?>

                                        <?php if ($r['STATUS']): ?>
                                            <div class="status-badge <?= $statusClass ?>">
                                                <?= $r['STATUS'] ?>
                                            </div>

                                            <div class="badge-time">
                                                <?= $r['STATUS_TIME'] ? date('d M Y h:i A', strtotime($r['STATUS_TIME'])) : '' ?>
                                            </div>

                                        <?php else: ?>

                                            <select onchange="updateItem(<?= $r['ITEM_ID'] ?>,'STATUS',this.value)" class="form-control">
                                                <option value="">--</option>
                                                <option>Pending</option>
                                                <option>Completed</option>
                                                <option>Cancelled</option>
                                            </select>

                                        <?php endif; ?>
                                    </td>

                                    <?php if ($r['STATUS'] != 'Cancelled'): ?>

                                        <td>
                                            <?php if ($r['FULFILLEDBY']): ?>
                                                <div><?= ($r['FULFILLEDBY'] == 1) ? 'Anurag' : 'Ariyan' ?></div>
                                                <div class="badge-time"><?= date('d M Y h:i A', strtotime($r['FULFILLEDBY_TIME'])) ?></div>
                                            <?php else: ?>
                                                <select onchange="updateItem(<?= $r['ITEM_ID'] ?>,'FULFILLEDBY',this.value)" class="form-control">
                                                    <option value="">--</option>
                                                    <option value="1">Anurag</option>
                                                    <option value="6">Ariyan</option>
                                                </select>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if ($r['FULFILLED_STATUS']): ?>
                                                <div><?= $r['FULFILLED_STATUS'] ?></div>
                                                <div class="badge-time"><?= date('d M Y h:i A', strtotime($r['FULFILLED_STATUS_TIME'])) ?></div>
                                            <?php else: ?>
                                                <select onchange="updateItem(<?= $r['ITEM_ID'] ?>,'FULFILLED_STATUS',this.value)" class="form-control">
                                                    <option value="">--</option>
                                                    <option>PICKED UP</option>
                                                    <option>DELIVERED</option>
                                                </select>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if ($r['PAYMENT_TYPE']): ?>
                                                <div><?= $r['PAYMENT_TYPE'] ?></div>
                                                <div class="badge-time"><?= date('d M Y h:i A', strtotime($r['PAYMENT_TYPE_TIME'])) ?></div>
                                            <?php else: ?>
                                                <select onchange="updateItem(<?= $r['ITEM_ID'] ?>,'PAYMENT_TYPE',this.value)" class="form-control">
                                                    <option value="">--</option>
                                                    <option>CASH</option>
                                                    <option>INTERACT</option>
                                                </select>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if ($r['PAYMENT_STATUS']): ?>
                                                <div><?= $r['PAYMENT_STATUS'] ?></div>
                                                <div class="badge-time"><?= date('d M Y h:i A', strtotime($r['PAYMENT_STATUS_TIME'])) ?></div>
                                            <?php else: ?>
                                                <select onchange="updateItem(<?= $r['ITEM_ID'] ?>,'PAYMENT_STATUS',this.value)" class="form-control">
                                                    <option value="">--</option>
                                                    <option>PAID</option>
                                                    <option>CREDIT</option>
                                                    <option>UNPAID</option>
                                                </select>
                                            <?php endif; ?>
                                        </td>

                                    <?php else: ?>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    <?php endif; ?>

                                    <td>
                                        <button class="btn btn-info btn-sm"
                                            onclick="viewFullDetails(
'<?= htmlspecialchars($r['CUSTOMER_NAME']) ?>',
'<?= htmlspecialchars($r['PHONE']) ?>',
'<?= htmlspecialchars($r['EMAIL']) ?>',
'<?= htmlspecialchars($r['ADDRESS']) ?>',

'<?= htmlspecialchars($r['DEPARTMENT_NAME']) ?>',
'<?= htmlspecialchars($r['PRODUCT_TYPE_NAME']) ?>',
'<?= htmlspecialchars($r['IMAGE']) ?>',
'<?= htmlspecialchars($r['PRODUCT_NAME']) ?>',
'<?= $r['QUANTITY'] ?>',
'<?= number_format($r['PRICE'], 2) ?>',
'<?= number_format($r['PRICE'] * $r['QUANTITY'], 2) ?>',
'<?= htmlspecialchars($r['SIZE'] ?? '') ?>',
'<?= htmlspecialchars($r['COLOR'] ?? '') ?>',
'<?= htmlspecialchars($r['TNAME'] ?? '') ?>',
''
)">
<i class="fa fa-eye"></i>
</button>
</td>

</tr>
<?php endwhile; ?>

</tbody>
</table>
</div>
</div>
</section>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- Export Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function updateItem(itemId, field, value){
let fd=new FormData();
fd.append('item_id',itemId);
fd.append('field',field);
fd.append('value',value);
fetch('',{method:'POST',body:fd})
.then(()=>location.reload());
}

function viewCustomer(name, phone, email, address){
Swal.fire({
title:'Customer Details',
html:`
<div style="text-align:left;line-height:1.8;">
<strong>Name:</strong> ${name}<br>
<strong>Contact:</strong> ${phone}<br>
<strong>Email:</strong> ${email}<br>
<strong>Address:</strong> ${address}<br>
<strong>Country:</strong> Canada<br>
<strong>Province:</strong> Ontario<br>
<strong>City:</strong> Toronto<br>
<strong>Area:</strong> GTA
</div>
`,
confirmButtonText:'Close'
});
}

function viewFullDetails(
name, phone, email, address,
dept, type, image, product,
qty, price, total,
size, color, tname, number
){

let sizeField = size ? `
<div style="margin-bottom:6px;">
<strong>Size:</strong> ${size}
</div>` : '';

let colorField = color ? `
<div style="margin-bottom:6px;">
<strong>Color:</strong> ${color}
</div>` : '';

let nameField = tname ? `
<div style="margin-bottom:6px;">
<strong>Name:</strong> ${tname}
</div>` : '';

let numberField = number ? `
<div style="margin-bottom:6px;">
<strong>Number:</strong> ${number}
</div>` : '';

Swal.fire({
title:'Order Details',
width:700,
html:`
<div style="text-align:left;">

<!-- CUSTOMER SECTION -->
<h5 style="margin-bottom:6px;">Customer Information</h5>
<strong>Name:</strong> ${name}<br>
<strong>Phone:</strong> ${phone}<br>
<strong>Email:</strong> ${email}<br>
<strong>Address:</strong> ${address}<br><br>

<hr>

<!-- PRODUCT SECTION -->
<h5 style="margin-bottom:6px;">Product Information</h5>

<div style="text-align:center;margin-bottom:10px;">
<img src="${image}" style="max-width:120px;border-radius:6px;">
</div>

<strong>Department:</strong> ${dept || '-'}<br>
<strong>Type:</strong> ${type || '-'}<br>
<strong>Product:</strong> ${product}<br><br>

<strong>Quantity:</strong> ${qty}<br>
<strong>Unit Price:</strong> CAD ${price}<br>
<strong>Total Price:</strong> CAD ${total}<br><br>

${sizeField}
${colorField}
${nameField}
${numberField}

</div>
`,
confirmButtonText:'Close'
});
}
$(document).ready(function() {

    $('#ordersTable').DataTable({
        pageLength: 20,
        lengthMenu: [10, 20, 50, 100],
order: [[0, "desc"]], // Sort by first column (Booking)        searching: true,
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            'excel',
            'csv',
            'print'
        ]
    });

});
</script>

</body>
</html>
