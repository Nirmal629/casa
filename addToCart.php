<?php 
session_start();
include "includes/store-header.php"; 

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Remove item from cart
if (isset($_GET['remove'])) {
    $removeId = (int)$_GET['remove'];
    unset($_SESSION['cart'][$removeId]);
    header("Location: addToCart.php");
    exit();
}

// Update quantity
if (isset($_GET['update']) && isset($_GET['qty'])) {
    $updateId = (int)$_GET['update'];
    $qty = max(1, (int)$_GET['qty']); // prevent 0 or negative
    if (isset($_SESSION['cart'][$updateId])) {
        $_SESSION['cart'][$updateId]['quantity'] = $qty;
    }
    header("Location: addToCart.php");
    exit();
}

// Calculate totals
$total = 0;
$itemsCount = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
    $itemsCount += $item['quantity'];
}
?>

<section class="addtocart_sec bothSide_gap">
    <div class="cust_container">
        <h2 class="heading">Add To Cart (<?= $itemsCount ?>)</h2>
        <div class="row">
            <div class="col-lg-8 col-md-12 col-12">
                <div class="custom_card">
                    <h6 class="card_heading">Products List</h6>
                    <ul class="product_list">
                        <?php if (!empty($_SESSION['cart'])): ?>
                            <?php foreach ($_SESSION['cart'] as $id => $item): ?>
                            <li>
                                <div class="image_wrap">
                                    <img src="<?= $item['image'] ?>" class="img-fluid" alt="image" />
                                </div>
                                <div class="content">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-1">
                                        <div>
                                            <h4 class="name"><?= htmlspecialchars($item['name']) ?></h4>
                                            <h6 class="amount">CAD&nbsp;<?= number_format($item['price'], 2) ?></h6>

                                            <div class="number d-flex align-items-center">
                                                <a href="addToCart.php?update=<?= $id ?>&qty=<?= $item['quantity'] - 1 ?>" class="btn btn-sm btn-light me-1 <?= $item['quantity'] <= 1 ? 'disabled' : '' ?>">-</a>
                                                <input type="text" value="<?= $item['quantity'] ?>" class="form-control text-center" style="width: 50px;" readonly />
                                                <a href="addToCart.php?update=<?= $id ?>&qty=<?= $item['quantity'] + 1 ?>" class="btn btn-sm btn-light ms-1">+</a>
                                            </div>
                                        </div>
                                        <a href="addToCart.php?remove=<?= $id ?>" class="btn btn-danger">Remove</a>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><p class="text-center">Your cart is empty.</p></li>
                        <?php endif; ?>
                    </ul>
                    <?php if (!empty($_SESSION['cart'])): ?>
                        <div class="w-full d-flex align-items-center justify-content-end pt-2">
                            <a href="check-out.php" class="btn placeorder_btn">Check Out</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-4 col-md-12 col-12">
                <div class="custom_card">
                    <h6 class="card_heading">Price details</h6>
                    <hr />
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <p>Price (<?= $itemsCount ?> item<?= $itemsCount > 1 ? 's' : '' ?>)</p>
                        <p>CAD&nbsp;<?= number_format($total, 2) ?></p>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <p>Delivery Charges</p>
                        <p><span class="text-decoration-line-through">CAD 40</span> <span class="text-success">Free</span></p>
                    </div>
                    <hr />
                    <div class="d-flex align-items-center justify-content-between">
                        <p class="text-dark fw-bold">Total Amount:</p>
                        <p class="text-dark fw-bold">CAD <?= number_format($total, 2) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include "includes/footer.php"; ?>
