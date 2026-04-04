<?php
session_start();
// print_r($_SESSION);exit;
include('dbConnection.php');


if(!isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit;
}

$userId = intval($_SESSION['email']);
// echo "
// SELECT o.ORDER_ID,
//       o.BOOKING_NO,
//       o.ORDER_DATE,
//       i.PRODUCT_NAME,
//       i.QUANTITY,
//       i.PRICE,
//       i.STATUS,
//       i.STATUS_TIME,
//       i.FULFILLED_STATUS,
//       i.PAYMENT_STATUS
// FROM ca_orders o
// JOIN ca_orders_item i ON i.ORDER_ID=o.ORDER_ID
// WHERE o.EMAIL = $userId
// ORDER BY o.ORDER_DATE DESC
// ";

/* =============================
   FETCH USER ORDERS
============================= */

$orders = $conn->query("
SELECT o.ORDER_ID,
       o.BOOKING_NO,
       o.ORDER_DATE,
       i.PRODUCT_NAME,
       i.QUANTITY,
       i.PRICE,
       i.STATUS,
       i.STATUS_TIME,
       i.FULFILLED_STATUS,
       i.PAYMENT_STATUS
FROM ca_orders o
JOIN ca_orders_item i ON i.ORDER_ID=o.ORDER_ID
WHERE o.EMAIL = $userId
ORDER BY o.ORDER_DATE DESC
");
?>

<?php include "includes/store-header.php"; ?>

<style>
.order-card{
    background:#fff;
    border:1px solid #e5e5e5;
    border-radius:8px;
    padding:15px;
    margin-bottom:15px;
    box-shadow:0 2px 6px rgba(0,0,0,.05);
}

.order-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:10px;
    border-bottom:1px solid #eee;
    padding-bottom:8px;
}

.badge-status{
    padding:6px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
    color:#fff;
}

.badge-pending{ background:#f0ad4e; }
.badge-completed{ background:#28a745; }
.badge-cancelled{ background:#dc3545; }

.badge-delivered{ background:#007bff; }
.badge-paid{ background:#28a745; }

.item-row{
    display:flex;
    justify-content:space-between;
    padding:6px 0;
    border-bottom:1px dashed #eee;
    font-size:14px;
}

.item-row:last-child{
    border-bottom:none;
}

.order-date{
    font-size:12px;
    color:#777;
}
</style>

<section class="productlisting_sec bothSide_gap">
<div class="cust_container">

<h2 class="heading">My Orders</h2>

<?php if($orders->num_rows == 0): ?>

<div style="text-align:center;padding:40px 0;">
    <h4>No orders found</h4>
    <a href="product-listing.php" class="btn btn-primary mt-2">
        Continue Shopping
    </a>
</div>

<?php else: ?>

<?php
$currentBooking = '';
while($row = $orders->fetch_assoc()):

if($currentBooking != $row['BOOKING_NO']):
    if($currentBooking != '') echo "</div></div>";

    $currentBooking = $row['BOOKING_NO'];

    // Status badge class
    $statusClass = 'badge-pending';
    if($row['STATUS']=='Completed') $statusClass='badge-completed';
    if($row['STATUS']=='Cancelled') $statusClass='badge-cancelled';
?>

<div class="order-card">
    <div class="order-header">
        <div>
            <strong>Booking #<?= $row['BOOKING_NO'] ?></strong>
            <div class="order-date">
                <?= date('d M Y', strtotime($row['ORDER_DATE'])) ?>
            </div>
        </div>

        <div>
            <span class="badge-status <?= $statusClass ?>">
                <?= $row['STATUS'] ?: 'Pending' ?>
            </span>
        </div>
    </div>

    <div class="order-items">

<?php endif; ?>

        <div class="item-row">
            <div>
                <?= htmlspecialchars($row['PRODUCT_NAME']) ?>
                <br>
                <small>
                    Qty: <?= $row['QUANTITY'] ?> |
                    Price: CAD <?= number_format($row['PRICE'],2) ?>
                </small>
            </div>

            <div style="text-align:right;">
                <strong>
                    CAD <?= number_format($row['QUANTITY'] * $row['PRICE'],2) ?>
                </strong>
                <br>

                <?php if($row['FULFILLED_STATUS']): ?>
                    <span class="badge-status badge-delivered">
                        <?= $row['FULFILLED_STATUS'] ?>
                    </span>
                <?php endif; ?>

                <?php if($row['PAYMENT_STATUS']=='PAID'): ?>
                    <span class="badge-status badge-paid">
                        Paid
                    </span>
                <?php endif; ?>
            </div>
        </div>

<?php endwhile; ?>

</div>
</div>

<?php endif; ?>

</div>
</section>

<?php include "includes/footer.php"; ?>