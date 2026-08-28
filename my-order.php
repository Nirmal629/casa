<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// print_r($_SESSION);exit;
include_once __DIR__ . '/dbConnection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$currentUserId = intval($_SESSION['user_id']);
$userEmail = '';
$userResult = $conn->query("SELECT EMAIL FROM ca_users WHERE ID = $currentUserId LIMIT 1");
if ($userResult && $userResult->num_rows > 0) {
    $userRow = $userResult->fetch_assoc();
    $userEmail = $conn->real_escape_string($userRow['EMAIL']);
}
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

$orders = false;
if ($userEmail !== '') {
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
WHERE o.EMAIL = '$userEmail'
ORDER BY o.ORDER_DATE DESC
");
}
?>

<?php include "includes/inner-header.php"; ?>

<style>
/* ===== FLIPKART-INSPIRED MY ORDERS ===== */

.myorders_wrap {
    max-width: 860px;
    margin: 0 auto;
    padding: 10px 0 40px;
}

/* --- Order Card --- */
.fk-order-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,.08);
    margin-bottom: 18px;
    overflow: hidden;
    transition: box-shadow .2s;
}
.fk-order-card:hover {
    box-shadow: 0 2px 12px rgba(0,0,0,.12);
}

/* --- Order Header --- */
.fk-order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #f0f0f0;
    flex-wrap: wrap;
    gap: 8px;
}
.fk-order-id {
    font-size: 14px;
    font-weight: 600;
    color: #212121;
}
.fk-order-id small {
    font-weight: 400;
    color: #878787;
    font-size: 12px;
    margin-left: 10px;
}
.fk-order-date {
    font-size: 12px;
    color: #878787;
    margin-top: 2px;
}

/* --- Status Badges --- */
.fk-status {
    display: inline-block;
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: .3px;
}
.fk-status-completed,
.fk-status-delivered {
    background: #e8f5e9;
    color: #2e7d32;
}
.fk-status-pending {
    background: #fff3e0;
    color: #e65100;
}
.fk-status-cancelled {
    background: #ffebee;
    color: #c62828;
}
.fk-status-paid {
    background: #e3f2fd;
    color: #1565c0;
}

/* --- Order Items --- */
.fk-order-body {
    padding: 6px 0;
}
.fk-order-item {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    border-bottom: 1px solid #f5f5f5;
    gap: 14px;
}
.fk-order-item:last-child {
    border-bottom: none;
}

/* Item Image Placeholder */
.fk-item-img {
    width: 60px;
    height: 60px;
    border-radius: 6px;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: #aaa;
    flex-shrink: 0;
    border: 1px solid #eee;
}

.fk-item-info {
    flex: 1;
    min-width: 0;
}
.fk-item-name {
    font-size: 14px;
    font-weight: 500;
    color: #212121;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.fk-item-meta {
    font-size: 12px;
    color: #878787;
    margin-top: 4px;
}
.fk-item-price {
    text-align: right;
    flex-shrink: 0;
}
.fk-item-price .amount {
    font-size: 15px;
    font-weight: 600;
    color: #212121;
    white-space: nowrap;
}
.fk-item-badges {
    display: flex;
    gap: 6px;
    margin-top: 6px;
    justify-content: flex-end;
    flex-wrap: wrap;
}

/* --- Empty State --- */
.fk-empty {
    text-align: center;
    padding: 60px 20px;
}
.fk-empty-icon {
    font-size: 56px;
    color: #ddd;
    margin-bottom: 16px;
}
.fk-empty h4 {
    font-size: 18px;
    color: #212121;
    margin-bottom: 6px;
}
.fk-empty p {
    font-size: 13px;
    color: #878787;
    margin-bottom: 20px;
}
.fk-empty .btn-shop {
    display: inline-block;
    padding: 10px 32px;
    background: #2874f0;
    color: #fff;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: background .2s;
}
.fk-empty .btn-shop:hover {
    background: #1a5dc7;
    color: #fff;
}

/* --- Responsive --- */
@media (max-width: 576px) {
    .fk-order-header {
        padding: 12px 14px;
    }
    .fk-order-item {
        padding: 10px 14px;
        flex-wrap: wrap;
    }
    .fk-item-img {
        width: 48px;
        height: 48px;
        font-size: 18px;
    }
    .fk-item-price {
        width: 100%;
        text-align: left;
        padding-left: 62px;
    }
    .fk-item-badges {
        justify-content: flex-start;
    }
}
</style>

<section class="productlisting_sec bothSide_gap">
<div class="cust_container">

<h2 class="heading">My Orders</h2>

<div class="myorders_wrap">

<?php if($orders->num_rows == 0): ?>

<div class="fk-empty">
    <div class="fk-empty-icon">📦</div>
    <h4>No orders yet</h4>
    <p>Looks like you haven't placed any orders. Start shopping!</p>
    <a href="product-listing.php" class="btn-shop">Continue Shopping</a>
</div>

<?php else: ?>

<?php
$currentBooking = '';
while($row = $orders->fetch_assoc()):

if($currentBooking != $row['BOOKING_NO']):
    if($currentBooking != '') echo "</div></div>";

    $currentBooking = $row['BOOKING_NO'];

    // Status badge class
    $statusClass = 'fk-status-pending';
    $statusLabel = $row['STATUS'] ?: 'Pending';
    if($row['STATUS']=='Completed') { $statusClass='fk-status-completed'; }
    elseif($row['STATUS']=='Cancelled') { $statusClass='fk-status-cancelled'; }
    elseif($row['STATUS']=='Delivered') { $statusClass='fk-status-delivered'; }
?>

<div class="fk-order-card">
    <div class="fk-order-header">
        <div>
            <div class="fk-order-id">
                Order #<?= $row['BOOKING_NO'] ?>
                <small><?= date('d M Y', strtotime($row['ORDER_DATE'])) ?></small>
            </div>
        </div>
        <span class="fk-status <?= $statusClass ?>"><?= $statusLabel ?></span>
    </div>
    <div class="fk-order-body">

<?php endif; ?>

        <div class="fk-order-item">
            <div class="fk-item-img">🛍️</div>
            <div class="fk-item-info">
                <div class="fk-item-name"><?= htmlspecialchars($row['PRODUCT_NAME']) ?></div>
                <div class="fk-item-meta">Qty: <?= $row['QUANTITY'] ?> × CAD <?= number_format($row['PRICE'],2) ?></div>
            </div>
            <div class="fk-item-price">
                <div class="amount">CAD <?= number_format($row['QUANTITY'] * $row['PRICE'],2) ?></div>
                <div class="fk-item-badges">
                    <?php if($row['FULFILLED_STATUS']): ?>
                        <span class="fk-status fk-status-delivered"><?= $row['FULFILLED_STATUS'] ?></span>
                    <?php endif; ?>
                    <?php if($row['PAYMENT_STATUS']=='PAID'): ?>
                        <span class="fk-status fk-status-paid">Paid</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

<?php endwhile; ?>

</div>
</div>

<?php endif; ?>

</div>
</div>
</section>

<?php include "includes/footer.php"; ?>