<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/dbConnection.php';
include "includes/store-header.php"; 

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* ---------- REMOVE ITEM ---------- */
if (isset($_GET['remove'])) {
    $remId = (int)$_GET['remove'];
    $itemRemName = $_SESSION['cart'][$remId]['name'] ?? 'Product #' . $remId;
    if (isset($_SESSION['user_id'])) {
        logPlayerActivity(
            $conn,
            $_SESSION['user_id'],
            'REMOVE_FROM_CART',
            'Removed product "' . $itemRemName . '" from cart',
            null,
            null,
            ['product_id' => $remId, 'product_name' => $itemRemName]
        );
    }
    unset($_SESSION['cart'][$remId]);
    header("Location: addToCart.php");
    exit;
}

/* ---------- UPDATE QTY ---------- */
if (isset($_GET['update'], $_GET['qty'])) {

    $id = (int)$_GET['update'];
    $qty = max(1,(int)$_GET['qty']);

    if(isset($_SESSION['cart'][$id])){
        $_SESSION['cart'][$id]['quantity']=$qty;
        if (isset($_SESSION['user_id'])) {
            logPlayerActivity(
                $conn,
                $_SESSION['user_id'],
                'CART_QTY_UPDATED',
                'Updated quantity to ' . $qty . ' for product "' . ($_SESSION['cart'][$id]['name'] ?? '') . '"',
                null,
                null,
                ['product_id' => $id, 'quantity' => $qty]
            );
        }
    }

    header("Location: addToCart.php");
    exit;
}

/* ---------- TOTALS ---------- */
$total=0;
$itemsCount=0;
$cartSummaryItems = [];

foreach($_SESSION['cart'] as $cId => $item){
    $total += $item['price'] * $item['quantity'];
    $itemsCount += $item['quantity'];
    $cartSummaryItems[] = [
        'id' => $cId,
        'name' => $item['name'] ?? '',
        'price' => $item['price'] ?? 0,
        'quantity' => $item['quantity'] ?? 1
    ];
}

// Log cart view
if (!isset($_GET['remove']) && !isset($_GET['update']) && isset($_SESSION['user_id'])) {
    logPlayerActivity(
        $conn,
        $_SESSION['user_id'],
        'CART_VIEWED',
        'Viewed cart (' . $itemsCount . ' item' . ($itemsCount === 1 ? '' : 's') . ', Total: $' . number_format($total, 2) . ')',
        null,
        null,
        ['items_count' => $itemsCount, 'total_price' => $total, 'items' => $cartSummaryItems]
    );
}
?>

<section class="addtocart_sec bothSide_gap">
<div class="cust_container">

<h2 class="heading">Add To Cart (<?= $itemsCount ?>)</h2>

<div class="row">

<div class="col-lg-8 col-md-12">

<div class="custom_card">
<h6 class="card_heading">Products List</h6>

<ul class="product_list">

<?php if(!empty($_SESSION['cart'])): ?>

<?php foreach($_SESSION['cart'] as $id=>$item): ?>

<li>
<div class="image_wrap">
<img src="<?= $item['image'] ?>" class="img-fluid">
</div>

<div class="content">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-1">

<div>
<h4 class="name"><?= htmlspecialchars($item['name']) ?></h4>

<?php if(!empty($item['size'])): ?>
<div class="text-muted small mb-1">
Size: <strong><?= htmlspecialchars($item['size']) ?></strong>
</div>
<?php endif; ?>

<h6 class="amount">CAD <?= number_format($item['price'],2) ?></h6>

<div class="number d-flex align-items-center">

<a href="?update=<?= $id ?>&qty=<?= $item['quantity']-1 ?>" 
class="btn btn-sm btn-light <?= $item['quantity']<=1?'disabled':'' ?>">-</a>

<input value="<?= $item['quantity'] ?>" readonly class="form-control text-center mx-1" style="width:50px">

<a href="?update=<?= $id ?>&qty=<?= $item['quantity']+1 ?>" class="btn btn-sm btn-light">+</a>

</div>
</div>

<a href="?remove=<?= $id ?>" class="btn btn-danger">Remove</a>

</div>
</div>
</li>

<?php endforeach; ?>

<?php else: ?>

<li class="text-center">Your cart is empty.</li>

<?php endif; ?>

</ul>

<?php if(!empty($_SESSION['cart'])): ?>
<div class="d-flex justify-content-end pt-2">
<a href="check-out.php" class="btn placeorder_btn">Check Out</a>
</div>
<?php endif; ?>

</div>
</div>

<div class="col-lg-4 col-md-12">

<div class="custom_card">
<h6 class="card_heading">Price details</h6>
<hr>

<div class="d-flex justify-content-between mb-2">
<p>Price (<?= $itemsCount ?> items)</p>
<p>CAD <?= number_format($total,2) ?></p>
</div>

<div class="d-flex justify-content-between mb-2">
<p>Delivery Charges</p>
<p> <span class="text-success">Free</span></p>
</div>

<hr>

<div class="d-flex justify-content-between fw-bold">
<p>Total Amount</p>
<p>CAD <?= number_format($total,2) ?></p>
</div>

</div>
</div>

</div>
</div>
</section>

<?php include "includes/footer.php"; ?>
