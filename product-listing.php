<?php
session_start();
include "includes/store-header.php";
include('dbConnection.php');

// Initialize cart session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $productId = (int)$_GET['id'];
    $action = $_GET['action'];

    $stmt = $conn->prepare("SELECT PRODUCT_NAME, PRICE, IMAGE, QUANTITY FROM ca_products WHERE ID = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($product = $result->fetch_assoc()) {
        $stock = (int)$product['QUANTITY'];

        if ($action === 'add') {
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
                        'quantity' => 1
                    ];
                }
            }
        } elseif ($action === 'increase') {
            if (isset($_SESSION['cart'][$productId]) && $_SESSION['cart'][$productId]['quantity'] < $stock) {
                $_SESSION['cart'][$productId]['quantity']++;
            }
        } elseif ($action === 'decrease') {
            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId]['quantity']--;
                if ($_SESSION['cart'][$productId]['quantity'] <= 0) {
                    unset($_SESSION['cart'][$productId]);
                }
            }
        }
    }
    $stmt->close();
    header("Location: product-listing.php");
    exit();
}
?>

<section class="productlisting_sec bothSide_gap">
    <div class="cust_container">
        <h2 class="heading">Popular products</h2>

        <div class="row">
        <?php
        $sql = "SELECT * FROM ca_products ORDER BY ID DESC LIMIT 12";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $id = $row['ID'];
                $name = htmlspecialchars($row['PRODUCT_NAME']);
                $price = number_format($row['PRICE'], 2);
                $stock = (int)$row['QUANTITY'];
                $image1 = 'admin/' . $row['IMAGE'];
                $image2 = 'admin/' . $row['IMAGE'];
                
                $soldQuery = "SELECT SUM(oi.QUANTITY) AS sold_qty
                      FROM ca_orders_item oi
                      INNER JOIN ca_orders o ON o.ORDER_ID = oi.ORDER_ID
                      WHERE oi.PRODUCT_ID = $id AND o.ORDER_STATUS = 'Completed'";
        $soldResult = $conn->query($soldQuery);
        $soldRow = $soldResult->fetch_assoc();
        $soldQty = $soldRow['sold_qty'] ?? 0;

        $availableStock = max(0, $stock - $soldQty); // prevent negative

                echo '
                <div class="col-md-3 col-sm-6">
                    <div class="product-grid">
                        <div class="product-image">
                            <a href="javascript:void(0)" class="image">
                                <img class="pic-1" src="' . $image1 . '" alt="Product">
                                <img class="pic-2" src="' . $image2 . '" alt="Hover">
                            </a>
                        </div>
                        <div class="product-content">
                            <h3 class="title"><a href="product-details.php?id=' . $id . '">' . $name . '</a></h3>
                            <div class="price">CAD ' . $price . '</div>
                            <div class="text-muted mb-1">Available: ' . $availableStock . '</div>';

                // Cart logic
                if ($stock <= 0) {
                    echo '<div class="text-danger fw-bold" style="padding: 10px 15px;">Out of Stock</div>';
                } elseif (isset($_SESSION['cart'][$id])) {
                    $cartQty = $_SESSION['cart'][$id]['quantity'];
                    echo '
                        <div class="d-flex align-items-center justify-content-center gap-2 mt-3">
                            <a href="product-listing.php?action=decrease&id=' . $id . '" class="btn btn-sm btn-outline-secondary">-</a>
                            <span style="border: 1px solid #999696; padding: 2px 6px;">' . $cartQty . '</span>
                            <a href="product-listing.php?action=increase&id=' . $id . '" class="btn btn-sm btn-outline-secondary">+</a>
                        </div>';
                } else {
                    echo '<a href="product-listing.php?action=add&id=' . $id . '" class="btn btn-sm btn-success mt-2">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                          </a>';
                }

                echo '
                        </div>
                    </div>
                </div>';
            }
        } else {
            echo "<div class='col-12 text-center'><p>No products available.</p></div>";
        }
        ?>
        </div>
    </div>
</section>

<?php include "includes/footer.php"; ?>
