<?php
include('dbConnection.php');
include('header.php');

include('sidebar.php');

function generateBookingNo(){
    return 'CASA'.date('YmdHis').rand(100,999);
}

/* ========= PLACE ORDER ========= */
if(isset($_POST['submit'])){

    $booking_no = generateBookingNo();

    $name = mysqli_real_escape_string($conn,$_POST['name']);
    $phone = mysqli_real_escape_string($conn,$_POST['phone']);
    $email = mysqli_real_escape_string($conn,$_POST['email']);
    $address = mysqli_real_escape_string($conn,$_POST['address']);
    $created_by = intval($_POST['created_by']);
    $order_date = date('Y-m-d');

    $total = 0;
    foreach($_POST['price'] as $i=>$p){
        $total += $p * $_POST['qty'][$i];
    }

    mysqli_query($conn,"
    INSERT INTO ca_orders 
    (BOOKING_NO,CUSTOMER_NAME,PHONE,EMAIL,ADDRESS,ORDER_DATE,TOTAL_AMOUNT,DELIVERY_CHARGE,FULFILLEDBY)
    VALUES ('$booking_no','$name','$phone','$email','$address','$order_date','$total',0,'$created_by')
    ");

    $order_id = mysqli_insert_id($conn);

    foreach($_POST['product_id'] as $i=>$pid){

        $qty = $_POST['qty'][$i];
        if($qty<=0) continue;

        $subtotal = $_POST['price'][$i] * $qty;

        $pname = $_POST['product_name'][$i];
        $size  = $_POST['size'][$i];

        /* ✅ MANUAL ENTERED NAME */
        $tname = mysqli_real_escape_string($conn,$_POST['tname'][$i]);

        mysqli_query($conn,"
        INSERT INTO ca_orders_item
        (ORDER_ID,BOOKING_NO,PRODUCT_ID,PRODUCT_NAME,PRICE,QUANTITY,SIZE,TNAME,SUBTOTAL,FULFILLEDBY)
        VALUES ('$order_id','$booking_no','$pid','$pname','{$_POST['price'][$i]}',
                '$qty','$size','$tname','$subtotal','$created_by')
        ");
    }

    echo "<script>alert('Manual order placed! Booking No: $booking_no');location.href='manual_order.php';</script>";
    exit;
}

$products = mysqli_query($conn,"SELECT * FROM ca_products ORDER BY PRODUCT_NAME ASC");
?>

<section role="main" class="content-body">

<header class="page-header">
<h2>Manual Order</h2>
</header>

<div class="row">
<div class="col-lg-12">

<section class="panel">
<header class="panel-heading">
<h2 class="panel-title">Create Manual Order</h2>
</header>

<div class="panel-body">

<form method="post">

<div class="form-group">
<label><strong>Customer Name</strong></label>
<input class="form-control" name="name" required>
</div>

<div class="form-group">
<label><strong>Phone</strong></label>
<input class="form-control" name="phone" required>
</div>

<div class="form-group">
<label><strong>Email</strong></label>
<input class="form-control" name="email">
</div>

<div class="form-group">
<label><strong>Address</strong></label>
<textarea class="form-control" name="address" required></textarea>
</div>

<div class="form-group">
<label><strong>Order Created By</strong></label>
<select class="form-control" name="created_by" required>
<option value="">Select Staff</option>
<option value="1">Anurag</option>
<option value="6">Aryan</option>
</select>
</div>

<hr>

<h4>Select Products</h4>

<div style="max-height:400px; overflow-y:auto; border:1px solid #ddd; padding:10px;">

<table class="table table-bordered">
<tr>
<th>Product</th>
<th>T-Shirt Name</th>
<th>Size</th>
<th>Price</th>
<th>Qty</th>
</tr>

<?php while($p=mysqli_fetch_assoc($products)):
$sizes = array_filter(array_map('trim',explode(',',$p['SIZE'] ?? '')));
?>

<tr>
<td>
<?= $p['PRODUCT_NAME'] ?>
<input type="hidden" name="product_id[]" value="<?= $p['ID'] ?>">
<input type="hidden" name="product_name[]" value="<?= $p['PRODUCT_NAME'] ?>">
</td>

<!-- ✅ MANUAL ENTRY FIELD -->
<td>
<input type="text"
       class="form-control"
       name="tname[]"
       placeholder="Enter name to print"
       maxlength="20">
</td>

<td>
<?php if($sizes): ?>
<select class="form-control" name="size[]">
<option value="">--</option>
<?php foreach($sizes as $s): ?>
<option value="<?= $s ?>"><?= $s ?></option>
<?php endforeach; ?>
</select>
<?php else: ?>
<input type="hidden" name="size[]" value="">
—
<?php endif; ?>
</td>

<td>
<?= $p['PRICE'] ?>
<input type="hidden" name="price[]" value="<?= $p['PRICE'] ?>">
</td>

<td>
<input type="number" class="form-control" name="qty[]" value="0" min="0" onchange="calcTotal()">
</td>
</tr>

<?php endwhile; ?>

</table>
</div>

<div class="text-right mt-3">
<strong>Total: CAD <span id="total">0</span></strong>
</div>

<br>

<button class="btn btn-primary" name="submit">Place Manual Order</button>

</form>

</div>
</section>
</div>
</div>
</section>

<script>
function calcTotal(){
let prices=document.querySelectorAll('[name="price[]"]');
let qtys=document.querySelectorAll('[name="qty[]"]');
let total=0;
prices.forEach((p,i)=>{
 total+=parseFloat(p.value)*parseInt(qtys[i].value||0);
});
document.getElementById("total").innerText=total.toFixed(2);
}
</script>

<?php include('footer.php'); ?>
