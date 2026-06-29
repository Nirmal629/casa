<?php
session_start();
include "includes/store-header.php";
include "dbConnection.php";

// print_r($_SESSION);exit;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

/* ========= BOOKING NO ========= */
function generateBookingNo(){
    return 'CASA' . date('YmdHis') . rand(100,999);
}

/* ========= PLACE ORDER ========= */
if($_SERVER['REQUEST_METHOD']==='POST'){

    $booking_no = generateBookingNo();

    $name = mysqli_real_escape_string($conn,$_POST['yourName']);
    $phone = mysqli_real_escape_string($conn,$_POST['phoneNumber']);
    $email = mysqli_real_escape_string($conn,$_POST['emailAddress']);
    $address = mysqli_real_escape_string($conn,$_POST['Entermessage']);
    $order_date = date('Y-m-d');

    $delivery_charge = 0;
    $total = 0;

    foreach($_SESSION['cart'] as $item){
        $total += $item['price'] * $item['quantity'];
    }

    $grand_total = $total + $delivery_charge;

    /* ========= INSERT ORDER ========= */

    $stmt = $conn->prepare("
    INSERT INTO ca_orders 
    (BOOKING_NO, CUSTOMER_NAME, PHONE, EMAIL, ADDRESS, ORDER_DATE, TOTAL_AMOUNT, DELIVERY_CHARGE) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssssssdd",
        $booking_no,
        $name,
        $phone,
        $email,
        $address,
        $order_date,
        $grand_total,
        $delivery_charge
    );

    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    /* ========= INSERT ITEMS ========= */

    $stmt_item = $conn->prepare("
    INSERT INTO ca_orders_item 
    (ORDER_ID, BOOKING_NO, PRODUCT_ID, PRODUCT_NAME, PRICE, QUANTITY, SIZE, TNAME, SUBTOTAL)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach($_SESSION['cart'] as $product_id=>$item){

        $subtotal = $item['price'] * $item['quantity'];
        $size  = $item['size'] ?? '';
        $tname = $item['tname'] ?? '';

        $stmt_item->bind_param(
            "isississd",
            $order_id,
            $booking_no,
            $product_id,
            $item['name'],
            $item['price'],
            $item['quantity'],
            $size,
            $tname,
            $subtotal
        );

        $stmt_item->execute();
    }

    $stmt_item->close();

    /* ========= EMAIL ========= */

    $emailBody = "
    <h3>Order Confirmation</h3>
    <p><strong>Booking No:</strong> {$booking_no}</p>
    <p><strong>Name:</strong> {$name}</p>
    <p><strong>Email:</strong> {$email}</p>
    <p><strong>Phone:</strong> {$phone}</p>
    <p><strong>Address:</strong> {$address}</p>
    <p><strong>Order Date:</strong> {$order_date}</p>

    <table border='1' cellpadding='6' cellspacing='0' width='100%'>
    <tr>
        <th>Product</th>
        <th>Size</th>
        <th>Price</th>
        <th>Qty</th>
        <th>Size</th>
        <th>Subtotal</th>
    </tr>";

    foreach($_SESSION['cart'] as $item){
        $sub = $item['price'] * $item['quantity'];
        $emailBody .= "
        <tr>
            <td>{$item['name']}</td>
            <td>".($item['size'] ?? '-')."</td>
            <td>CAD {$item['price']}</td>
            <td>{$item['quantity']}</td>
            <td>{$item['size']}</td>
            <td>CAD {$sub}</td>
        </tr>";
    }

    $emailBody .= "</table>
    <p><strong>Total:</strong> CAD ".number_format($grand_total,2)."</p>";

    $mail = new PHPMailer(true);

    try{
        $mail->isSMTP();
        $mail->Host='localhost';
        $mail->SMTPAuth=true;
        $mail->Username='casainfo@casainfotech.com';
        $mail->Password='C@sainfo24#';
        $mail->Port=25;

        $mail->setFrom('casainfo@casainfotech.com','Casa Info');
        $mail->addAddress('casaclubsportstore@gmail.com','Admin');
        $mail->addAddress($email,$name);

        $mail->isHTML(true);
        $mail->Subject="Order Confirmation - {$booking_no}";
        $mail->Body=$emailBody;

        $mail->send();
    }catch(Exception $e){}

    unset($_SESSION['cart']);

  echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
echo "<script>
Swal.fire({
    icon: 'success',
    title: 'Order Placed Successfully!',
    html: `
        <div style='text-align:left;font-size:15px;line-height:1.6'>
            <strong>Booking Number:</strong> {$booking_no}<br><br>

            Our team will contact you shortly from 
            <strong>casaclubsportsstore@gmail.com</strong> 
            with available delivery options.<br><br>

            An <strong>Interac email payment request</strong> will also be shared 
            from the same email address.<br><br>

            For any questions or updates, please contact 
            <strong>casaclubsportsstore@gmail.com</strong> 
            and include your order number and name for quick assistance.<br><br>

            <strong>Thank you for shopping with us!</strong>
        </div>
    `,
    showCancelButton: true,
    confirmButtonText: 'Close',
    cancelButtonText: 'Download Receipt',
    allowOutsideClick: false
}).then((result) => {

    if (result.dismiss === Swal.DismissReason.cancel) {

        const content = 
`Order Placed Successfully!

Booking Number: {$booking_no}

Our team will contact you shortly from casaclubsportsstore@gmail.com 
with available delivery options.

An Interac email payment request will also be shared from the same email address.

For any questions or updates, please contact casaclubsportsstore@gmail.com 
and include your order number and name for quick assistance.

Thank you for shopping with us!`;

        const blob = new Blob([content], { type: 'text/plain' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'Order_{$booking_no}.txt';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // return false; // stay on popup
        setTimeout(function(){
            window.location.href = 'product-listing.php';
        }, 800);
    }

    if (result.isConfirmed) {
        window.location.href = 'product-listing.php';
    }
});
</script>";


    exit;
}
$selectUser = mysqli_query($conn,"select * from ca_users where ID='".$_SESSION['user_id']."'");
$fetchUser = mysqli_fetch_assoc($selectUser);
?>

<!-- Checkout Form UI -->
<section class="checkOut_sec bothSide_gap">
    <div class="cust_container">
        <h2 class="heading">Check Out</h2>
        <div class="row">
            <div class="col-lg-8 col-md-12 col-12">
                <div class="custom_card">
                    <h6 class="card_heading">Fill out the personal information</h6>
                    <form action="" method="post">
                        <div class="form-group">
                            <label for="yourName">Your Name :</label>
                            <input type="text" name="yourName" class="form-control" id="yourName" required value="<?=$fetchUser['NAME']?>">
                        </div>
                        <div class="form-group">
                            <label for="phoneNumber">Phone Number (WhatsApp):</label>
                            <input type="number" name="phoneNumber" class="form-control" id="phoneNumber" required value="<?=$fetchUser['WHATSAPP_NUMBER']?>">
                        </div>
                        <div class="form-group">
                            <label for="emailAddress">Email address :</label>
                            <input type="email" name="emailAddress" class="form-control" id="emailAddress" required value="<?=$fetchUser['EMAIL']?>">
                        </div>
                        <div class="form-group">
                            <label for="emailAddress">City</label>
                            <input type="text" name="city" class="form-control" id="city"  value="<?=$fetchUser['CITY']?>">
                        </div>
                        <div class="form-group">
                            <label for="emailAddress">Country</label>
                            <input type="text" name="country" class="form-control" id="country"  value="<?=$fetchUser['COUNTRY']?>">
                        </div>
                        <div class="form-group">
                            <label for="emailAddress">Province</label>
                            <input type="text" name="province" class="form-control" id="province"  value="<?=$fetchUser['PROVINCE']?>">
                        </div>
                         <div class="form-group">
                            <label for="emailAddress">Area</label>
                            <input type="text" name="area" class="form-control" id="area"  value="<?=$fetchUser['AREA']?>">
                        </div>
                        <div class="form-group">
                            <label for="Entermessage">Address :</label>
                            <textarea name="Entermessage" class="form-control" id="Entermessage" rows="2" required></textarea>
                        </div>
                        <div class="w-full d-flex align-items-center justify-content-end pt-2">
                            <button class="btn placeorder_btn">Place Order</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4 col-md-12 col-12">
                <div class="custom_card">
                    <h6 class="card_heading">Price details</h6>
                    <hr />
                    <?php
                    $subtotal = 0;
                    foreach ($_SESSION['cart'] as $item) {
                        $subtotal += $item['price'] * $item['quantity'];
                    }
                    ?>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <p>Price (<?php echo count($_SESSION['cart']); ?> items)</p>
                        <p>CAD <?php echo number_format($subtotal, 2); ?></p>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <p>Delivery Charges</p>
                        <p>
                            <span class="text-success">Free</span>
                        </p>
                    </div>
                    <hr />
                    <div class="d-flex align-items-center justify-content-between">
                        <p class="text-dark fw-bold">Total Amount:</p>
                        <p class="text-dark fw-bold">CAD <?php echo number_format($subtotal, 2); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Thank You Modal -->
<section class="customModal_wrap thankyouModal" style="display:none">
    <div class="customModal_body">
        <div class="Successimage_wrap">
            <img src="assets/images/checkmark.gif" class="img-fluid" alt="OK" />
        </div>
        <div class="customModal_content">
            <h1 class="text-center fw-bold" style="font-size: 38px;">Thank You!</h1>
            <div class="bg-info rounded p-4 mb-4">
                <h6 class="text-center text-white">ORDER CONFIRMATION</h6>
                <p class="text-center text-white">Your order has been successfully placed!</p>
                <p class="text-center text-white">You will shortly receive a confirmation email.</p>
            </div>
            <div class="d-flex align-items-center justify-content-center">
                <a href="index.php" class="btn btn-success rounded-pill">Back To Home</a>
            </div>
        </div>
    </div>
</section>

<?php include "includes/footer.php"; ?>