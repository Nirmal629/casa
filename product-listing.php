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

.col-md-3{display:flex}



.product-grid{

    display:flex;

    flex-direction:column;

    height:100%;

    border:1px solid #e6e6e6;

    padding:10px;

    border-radius:10px;

    background:#fff;

    transition:.2s;

}



.product-grid:hover{

    transform:translateY(-4px);

    box-shadow:0 8px 18px rgba(0,0,0,.08);

}



.product-image img{

    width:100%;

    border-radius:8px;

}



.product-content{

    display:flex;

    flex-direction:column;

    flex:1;

}



.product-content .title{

    font-size:15px;

    font-weight:600;

    min-height:42px;

    margin:6px 0;

}



.product-content .price{

    font-size:14px;

    font-weight:700;

}



.size-select,

.name-input{

    font-size:13px;

    padding:6px;

}



.add-btn{

    margin-top:auto;

}

</style>



<section class="productlisting_sec bothSide_gap">

<div class="cust_container">

<h2 class="heading">Player Hub/Products</h2>



<div class="row">



<?php

$res = $conn->query("SELECT * FROM ca_products ORDER BY ID DESC LIMIT 12");



while($row = $res->fetch_assoc()){



$id = $row['ID'];

$name = htmlspecialchars($row['PRODUCT_NAME']);

$price = number_format($row['PRICE'],2);

$image = 'admin/' . $row['IMAGE'];

$stock = (int)$row['QUANTITY'];



$sizes = array_filter(array_map('trim', explode(',', $row['SIZE'] ?? '')));

?>



<div class="col-md-3 col-sm-6">

<div class="product-grid">



<div class="product-image">

<img src="<?= $image ?>">

</div>



<div class="product-content">



<h3 class="title">

<a href="javascript:void(0)" style="cursor:none"><?= $name ?></a>

</h3>



<div class="price">CAD <?= $price ?></div>



<!-- ✅ USER ENTERED NAME -->

<?php if(stripos($row['PRODUCT_NAME'],'t-shirt')!==false || 

         stripos($row['PRODUCT_NAME'],'tshirt')!==false || 

         stripos($row['PRODUCT_NAME'],'t shirt')!==false): ?>



<input type="text"

       class="form-control mb-2 name-input"

       placeholder="Name on T-shirt"

       data-name="<?= $id ?>">



<?php endif; ?>





<?php if(!empty($sizes)): ?>

<select class="form-control mb-2 size-select" data-id="<?= $id ?>">

<option value="">Select Size</option>

<?php foreach($sizes as $sz): ?>

<option value="<?= $sz ?>"><?= $sz ?></option>

<?php endforeach; ?>

</select>

<?php endif; ?>



<?php if(isset($_SESSION['cart'][$id])): ?>



<div class="d-flex align-items-center justify-content-center gap-2 mt-2">

<a href="?action=decrease&id=<?= $id ?>" class="btn btn-sm btn-outline-secondary">-</a>

<span><?= $_SESSION['cart'][$id]['quantity'] ?></span>

<a href="?action=increase&id=<?= $id ?>" class="btn btn-sm btn-outline-secondary">+</a>

</div>



<?php else: ?>



<?php if(!empty($sizes)): ?>



<?php if($loggedIn): ?>

<button onclick="addWithSize(<?= $id ?>)" class="btn btn-sm btn-success add-btn">

Add to Cart

</button>

<?php else: ?>

<button onclick="requireLogin()" class="btn btn-sm btn-success add-btn">

Add to Cart

</button>

<?php endif; ?>



<?php else: ?>



<?php if($loggedIn): ?>

<a href="?action=add&id=<?= $id ?>" class="btn btn-sm btn-success add-btn">

Add to Cart

</a>

<?php else: ?>

<button onclick="requireLogin()" class="btn btn-sm btn-success add-btn">

Add to Cart

</button>

<?php endif; ?>



<?php endif; ?>



<?php endif; ?>



</div>

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

