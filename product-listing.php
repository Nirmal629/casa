<?php

session_start();

include('dbConnection.php');

$loggedIn = isset($_SESSION['user_id']);



if (!isset($_SESSION['cart'])) {

    $_SESSION['cart'] = [];

}



/* ---------------- CART ACTIONS ---------------- */

if (isset($_GET['action'], $_GET['id'])) {



    $productId = (int)$_GET['id'];

    $action = $_GET['action'];

    $selectedSize = $_GET['size'] ?? '';

    $tname = $_GET['tname'] ?? '';



    $stmt = $conn->prepare("SELECT PRODUCT_NAME, PRICE, IMAGE, QUANTITY, SIZE FROM ca_products WHERE ID=?");

    $stmt->bind_param("i", $productId);

    $stmt->execute();

    $product = $stmt->get_result()->fetch_assoc();

    $stmt->close();



    if ($product) {



        $stock = (int)$product['QUANTITY'];



        if ($action === 'add') {



            if (!empty($product['SIZE']) && empty($selectedSize)) {

                header("Location: product-listing.php");

                exit;

            }



            if ($stock > 0) {



                if (isset($_SESSION['cart'][$productId])) {

                    if ($_SESSION['cart'][$productId]['quantity'] < $stock) {

                        $_SESSION['cart'][$productId]['quantity']++;

                    }

                } else {

                    $_SESSION['cart'][$productId] = [

                        'name' => $product['PRODUCT_NAME'],

                        'price' => $product['PRICE'],

                        'image' => 'admin/' . $product['IMAGE'],

                        'quantity' => 1,

                        'size' => $selectedSize,

                        'tname' => $tname

                    ];

                }

            }

        }



        if ($action === 'increase' && isset($_SESSION['cart'][$productId])) {

            if ($_SESSION['cart'][$productId]['quantity'] < $stock) {

                $_SESSION['cart'][$productId]['quantity']++;

            }

        }



        if ($action === 'decrease' && isset($_SESSION['cart'][$productId])) {

            $_SESSION['cart'][$productId]['quantity']--;

            if ($_SESSION['cart'][$productId]['quantity'] <= 0) {

                unset($_SESSION['cart'][$productId]);

            }

        }

    }



    header("Location: product-listing.php");

    exit;

}

?>



<?php include "includes/inner-header.php"; ?>



<style>
/* ═══════════════════════════════════════════════
   FLIPKART-STYLE PRODUCT LISTING
   ═══════════════════════════════════════════════ */
.productlisting_sec {
    background: #f1f3f6;
    padding: 20px 0;
    min-height: 60vh;
}
.productlisting_sec .cust_container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 16px;
}

/* ── Header Bar ── */
.pl-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fff;
    padding: 14px 24px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}
.pl-header h2 {
    font-size: 1.2rem;
    font-weight: 700;
    color: #212121;
    margin: 0;
}
.pl-header h2 small {
    font-size: 0.75rem;
    font-weight: 400;
    color: #878787;
    margin-left: 8px;
}
.pl-header .pl-count {
    font-size: 0.8rem;
    color: #878787;
    font-weight: 500;
}

/* ── Product Grid ── */
.pl-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}
@media(max-width:992px){ .pl-grid { grid-template-columns: repeat(3, 1fr); } }
@media(max-width:576px){ .pl-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; } }

/* ── Product Card ── */
.pl-card {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    transition: box-shadow 0.2s, transform 0.2s;
    display: flex;
    flex-direction: column;
    position: relative;
}
.pl-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

/* ── Image Section ── */
.pl-card-image {
    background: #fafafa;
    padding: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 1 / 1;
    position: relative;
    overflow: hidden;
}
.pl-card-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    transition: transform 0.3s;
}
.pl-card:hover .pl-card-image img {
    transform: scale(1.05);
}

/* ── Content Section ── */
.pl-card-body {
    padding: 12px 14px 14px;
    display: flex;
    flex-direction: column;
    flex: 1;
    gap: 4px;
}
.pl-card-body .pl-name {
    font-size: 0.82rem;
    font-weight: 600;
    color: #212121;
    margin: 0;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2.2em;
}
.pl-card-body .pl-name a {
    color: inherit;
    text-decoration: none;
}
.pl-card-body .pl-name a:hover { color: #2874f0; }

/* ── Spacer pushes button to bottom ── */
.pl-spacer { flex: 1; min-height: 4px; }

/* ── Price Row ── */
.pl-price-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 2px;
    flex-wrap: wrap;
}
.pl-price-current {
    font-size: 1rem;
    font-weight: 700;
    color: #212121;
}

/* ── Size Selector ── */
.pl-size-select {
    width: 100%;
    font-size: 0.75rem;
    padding: 5px 8px;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    background: #fff;
    color: #212121;
    margin-top: 4px;
    outline: none;
    cursor: pointer;
}
.pl-size-select:focus { border-color: #2874f0; }

/* ── Name Input ── */
.pl-name-input {
    width: 100%;
    font-size: 0.75rem;
    padding: 5px 8px;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    background: #fff;
    color: #212121;
    margin-top: 4px;
    outline: none;
}
.pl-name-input:focus { border-color: #2874f0; }
.pl-name-input::placeholder { color: #bdbdbd; font-size: 0.7rem; }

/* ── Add to Cart Button ── */
.pl-add-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    width: 100%;
    margin-top: 8px;
    padding: 8px 12px;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #fff;
    background: #ff9f00;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
    text-decoration: none;
}
.pl-add-btn:hover {
    background: #fb8c00;
    color: #fff;
    text-decoration: none;
}
.pl-add-btn i { font-size: 0.75rem; }

/* ── Quantity Controls ── */
.pl-qty {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-top: 8px;
    padding: 6px 0;
}
.pl-qty a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #2874f0;
    color: #fff;
    font-size: 0.85rem;
    font-weight: 700;
    text-decoration: none;
    transition: background 0.2s;
}
.pl-qty a:hover { background: #1a5dc7; color: #fff; }
.pl-qty span {
    font-size: 0.95rem;
    font-weight: 700;
    color: #212121;
    min-width: 20px;
    text-align: center;
}

/* ── Out of Stock ── */
.pl-oos {
    font-size: 0.72rem;
    font-weight: 600;
    color: #ff6161;
    text-align: center;
    margin-top: 6px;
}

/* ── Responsive ── */
@media(max-width:768px){
    .pl-header { padding: 12px 16px; flex-direction: column; gap: 4px; text-align: center; }
    .pl-header h2 { font-size: 1rem; }
    .pl-card-body { padding: 10px; }
    .pl-price-current { font-size: 0.9rem; }
    .pl-add-btn { font-size: 0.72rem; padding: 7px 10px; }
}
</style>

<section class="productlisting_sec">
<div class="cust_container">

    <!-- Header Bar -->
    <div class="pl-header">
        <h2>Casa Store <small>Sports &amp; Equipment</small></h2>
        <span class="pl-count">Showing 1–12 Products</span>
    </div>

    <!-- Product Grid -->
    <div class="pl-grid">

    <?php
    $res = $conn->query("SELECT * FROM ca_products ORDER BY ID DESC LIMIT 12");
    while($row = $res->fetch_assoc()){
        $id = $row['ID'];
        $name = htmlspecialchars($row['PRODUCT_NAME']);
        $price = number_format($row['PRICE'],2);
        $image = 'admin/' . $row['IMAGE'];
        $stock = (int)$row['QUANTITY'];
        $sizes = array_filter(array_map('trim', explode(',', $row['SIZE'] ?? '')));
        $isTshirt = stripos($row['PRODUCT_NAME'],'t-shirt')!==false ||
                    stripos($row['PRODUCT_NAME'],'tshirt')!==false ||
                    stripos($row['PRODUCT_NAME'],'t shirt')!==false;
    ?>

    <div class="pl-card">
        <div class="pl-card-image">
            <img src="<?= $image ?>" alt="<?= $name ?>" loading="lazy">
        </div>
        <div class="pl-card-body">
            <h3 class="pl-name"><a href="javascript:void(0)"><?= $name ?></a></h3>

            <!-- Price -->
            <div class="pl-price-row">
                <span class="pl-price-current">$<?= $price ?></span>
            </div>

            <!-- T-Shirt Name Input -->
            <?php if($isTshirt): ?>
            <input type="text" class="pl-name-input" placeholder="Name on T-shirt" data-name="<?= $id ?>">
            <?php endif; ?>

            <!-- Size Selector -->
            <?php if(!empty($sizes)): ?>
            <select class="pl-size-select" data-id="<?= $id ?>">
                <option value="">Select Size</option>
                <?php foreach($sizes as $sz): ?>
                <option value="<?= $sz ?>"><?= $sz ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <!-- Spacer to push button to bottom -->
            <div class="pl-spacer"></div>

            <!-- Stock / Cart Actions -->
            <?php if($stock <= 0): ?>
                <div class="pl-oos">Out of Stock</div>
            <?php elseif(isset($_SESSION['cart'][$id])): ?>
                <div class="pl-qty">
                    <a href="?action=decrease&id=<?= $id ?>">−</a>
                    <span><?= $_SESSION['cart'][$id]['quantity'] ?></span>
                    <a href="?action=increase&id=<?= $id ?>">+</a>
                </div>
            <?php elseif(!empty($sizes)): ?>
                <?php if($loggedIn): ?>
                <button onclick="addWithSize(<?= $id ?>)" class="pl-add-btn">
                    <i class="fa-solid fa-cart-plus"></i> Add to Cart
                </button>
                <?php else: ?>
                <button onclick="requireLogin()" class="pl-add-btn">
                    <i class="fa-solid fa-cart-plus"></i> Add to Cart
                </button>
                <?php endif; ?>
            <?php else: ?>
                <?php if($loggedIn): ?>
                <a href="?action=add&id=<?= $id ?>" class="pl-add-btn">
                    <i class="fa-solid fa-cart-plus"></i> Add to Cart
                </a>
                <?php else: ?>
                <button onclick="requireLogin()" class="pl-add-btn">
                    <i class="fa-solid fa-cart-plus"></i> Add to Cart
                </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php } ?>
    </div>

</div>
</section>



<script>

function addWithSize(id){



const size=document.querySelector('.size-select[data-id="'+id+'"]').value;

const name=document.querySelector('.name-input[data-name="'+id+'"]').value;



if(!size){

 alert("Please select size");

 return;

}



location.href="?action=add&id="+id+"&size="+size+"&tname="+encodeURIComponent(name);

}



function requireLogin(){

 alert("Please login to add items to cart");

 window.location.href="index.php";

}

</script>



<?php include "includes/footer.php"; ?>

