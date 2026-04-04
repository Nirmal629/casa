<?php
session_start();
include "includes/store-header.php";
include "dbConnection.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['yourName']);
    $phone = mysqli_real_escape_string($conn, $_POST['phoneNumber']);
    $email = mysqli_real_escape_string($conn, $_POST['emailAddress']);
    $address = mysqli_real_escape_string($conn, $_POST['Entermessage']);
    $order_date = date('Y-m-d');

    $delivery_charge = 0;
    $total = 0;

    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    $grand_total = $total + $delivery_charge;

    $stmt = $conn->prepare("INSERT INTO ca_orders (CUSTOMER_NAME, PHONE, EMAIL, ADDRESS, ORDER_DATE, TOTAL_AMOUNT, DELIVERY_CHARGE) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssdd", $name, $phone, $email, $address, $order_date, $grand_total, $delivery_charge);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    $stmt_item = $conn->prepare("INSERT INTO ca_orders_item (ORDER_ID, PRODUCT_ID, PRODUCT_NAME, PRICE, QUANTITY, SUBTOTAL) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($_SESSION['cart'] as $product_id => $item) {
        $subtotal = $item['price'] * $item['quantity'];
        $stmt_item->bind_param("iisddi", $order_id, $product_id, $item['name'], $item['price'], $item['quantity'], $subtotal);
        $stmt_item->execute();
    }
    $stmt_item->close();

    $emailBody = "
        <h3>Order Confirmation</h3>
        <p><strong>Name:</strong> {$name}</p>
        <p><strong>Email:</strong> {$email}</p>
        <p><strong>Phone:</strong> {$phone}</p>
        <p><strong>Address:</strong> {$address}</p>
        <p><strong>Order Date:</strong> {$order_date}</p>
        <hr>
        <h4>Order Details</h4>
        <table border='1' cellpadding='6' cellspacing='0' width='100%'>
            <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr></thead>
            <tbody>";

    foreach ($_SESSION['cart'] as $item) {
        $sub = $item['price'] * $item['quantity'];
        $emailBody .= "<tr><td>{$item['name']}</td><td>CAD {$item['price']}</td><td>{$item['quantity']}</td><td>₹{$sub}</td></tr>";
    }

    $emailBody .= "</tbody></table><p><strong>Total:</strong> CAD " . number_format($grand_total, 2) . "</p>";

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'localhost';
        $mail->SMTPAuth = true;
        $mail->Username = 'casainfo@casainfotech.com';
        $mail->Password = 'C@sainfo24#';
        $mail->Port = 25;
        $mail->SMTPSecure = false;

        $mail->setFrom('casainfo@casainfotech.com', 'Casa Info');
        $mail->addAddress('casaclubtoronto@gmail.com', 'Casa Admin');
        $mail->addAddress($email, $name);

        $mail->isHTML(true);
        $mail->Subject = "Order Confirmation - Casa Info";
        $mail->Body = $emailBody;

        $mail->send();
    } catch (Exception $e) {
        // Log email error if needed
    }

    unset($_SESSION['cart']);

    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
    echo "<script>
        Swal.fire({
            icon: 'success',
            title: 'Thank You!',
            text: 'Your order has been placed successfully. Admin will reach out to you for payment and delivery soon!',
            showConfirmButton: false,
            timer: 2500
        });
        setTimeout(() => { window.location.href = 'product-listing.php'; }, 2600);
    </script>";
    exit;
}
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
                            <input type="text" name="yourName" class="form-control" id="yourName" required>
                        </div>
                        <div class="form-group">
                            <label for="phoneNumber">Phone Number (WhatsApp):</label>
                            <input type="number" name="phoneNumber" class="form-control" id="phoneNumber" required>
                        </div>
                        <div class="form-group">
                            <label for="emailAddress">Email address :</label>
                            <input type="email" name="emailAddress" class="form-control" id="emailAddress" required>
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
                        <p><span class="text-decoration-line-through">CAD 20</span>
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