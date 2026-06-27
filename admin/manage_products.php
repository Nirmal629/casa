<?php
include('dbConnection.php');
include('header.php');
include('sidebar.php');

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function normalize_value($value)
{
    $value = trim((string) $value);
    return $value === '' ? null : $value;
}

function product_page_url($page, $limit, $search)
{
    $params = [
        'page' => $page,
        'limit' => $limit,
    ];

    if ($search !== '') {
        $params['search'] = $search;
    }

    return 'manage_products.php?' . http_build_query($params);
}

$message = '';
$messageType = 'success';
$search = trim($_GET['search'] ?? '');
$limit = (int) ($_GET['limit'] ?? 25);
$page = max(1, (int) ($_GET['page'] ?? 1));
$allowedLimits = [10, 25, 50, 100];
$showProductModal = false;

$formData = [
    'id' => '',
    'product_name' => '',
    'department_id' => '',
    'product_type_id' => '',
    'price' => '',
    'quantity' => '',
    'size' => '',
    'image' => '',
];

if (!in_array($limit, $allowedLimits, true)) {
    $limit = 25;
}

$departments = [];
$departmentResult = $conn->query("SELECT ID, NAME FROM ca_department ORDER BY NAME ASC");
if ($departmentResult) {
    while ($row = $departmentResult->fetch_assoc()) {
        $departments[] = $row;
    }
}

$productTypes = [];
$productTypeResult = $conn->query("SELECT ID, NAME, DEPARTMENT_ID FROM ca_product_type ORDER BY NAME ASC");
if ($productTypeResult) {
    while ($row = $productTypeResult->fetch_assoc()) {
        $productTypes[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $recordId = (int) ($_POST['id'] ?? 0);
        $formData = [
            'id' => $recordId,
            'product_name' => trim($_POST['product_name'] ?? ''),
            'department_id' => trim($_POST['department_id'] ?? ''),
            'product_type_id' => trim($_POST['product_type_id'] ?? ''),
            'price' => trim($_POST['price'] ?? ''),
            'quantity' => trim($_POST['quantity'] ?? ''),
            'size' => trim($_POST['size'] ?? ''),
            'image' => trim($_POST['current_image'] ?? ''),
        ];

        if ($formData['product_name'] === '' || $formData['department_id'] === '' || $formData['product_type_id'] === '' || $formData['price'] === '' || $formData['quantity'] === '') {
            $message = 'Product name, department, product type, price, and quantity are required.';
            $messageType = 'danger';
            $showProductModal = true;
        } elseif ($recordId === 0 && empty($_FILES['image']['name'])) {
            $message = 'Product image is required.';
            $messageType = 'danger';
            $showProductModal = true;
        } else {
            $productName = normalize_value($formData['product_name']);
            $departmentId = (int) $formData['department_id'];
            $productTypeId = (int) $formData['product_type_id'];
            $price = (float) $formData['price'];
            $quantity = (int) $formData['quantity'];
            $size = normalize_value($formData['size']);
            $imagePath = normalize_value($formData['image']);

            if (!empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
                $imageName = basename($_FILES['image']['name']);
                $imagePath = 'assets/' . time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $imageName);

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                    $message = 'Image upload failed.';
                    $messageType = 'danger';
                    $showProductModal = true;
                }
            }

            if ($message === '') {
                if ($recordId > 0) {
                    $stmt = $conn->prepare("UPDATE ca_products SET PRODUCT_NAME = ?, DEPARTMENT_ID = ?, PRODUCT_TYPE_ID = ?, PRICE = ?, QUANTITY = ?, SIZE = ?, IMAGE = ? WHERE ID = ?");
                    $stmt->bind_param('siidissi', $productName, $departmentId, $productTypeId, $price, $quantity, $size, $imagePath, $recordId);

                    if ($stmt->execute()) {
                        echo "<script>alert('Product updated successfully.'); window.location.href='manage_products.php';</script>";
                        exit;
                    }

                    $message = 'Failed to update product: ' . $stmt->error;
                    $messageType = 'danger';
                    $showProductModal = true;
                    $stmt->close();
                } else {
                    $stmt = $conn->prepare("INSERT INTO ca_products (PRODUCT_NAME, DEPARTMENT_ID, PRODUCT_TYPE_ID, PRICE, QUANTITY, SIZE, IMAGE) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param('siidiss', $productName, $departmentId, $productTypeId, $price, $quantity, $size, $imagePath);

                    if ($stmt->execute()) {
                        echo "<script>alert('Product added successfully.'); window.location.href='manage_products.php';</script>";
                        exit;
                    }

                    $message = 'Failed to add product: ' . $stmt->error;
                    $messageType = 'danger';
                    $showProductModal = true;
                    $stmt->close();
                }
            }
        }
    }
}

$products = [];
$totalItems = 0;
$totalPages = 1;
$offset = 0;
$baseFromSql = "
    FROM ca_products p
    LEFT JOIN ca_department d ON d.ID = p.DEPARTMENT_ID
    LEFT JOIN ca_product_type pt ON pt.ID = p.PRODUCT_TYPE_ID
";
$searchWhereSql = "
    WHERE CAST(p.ID AS CHAR) LIKE ?
        OR p.PRODUCT_NAME LIKE ?
        OR d.NAME LIKE ?
        OR pt.NAME LIKE ?
        OR CAST(p.PRICE AS CHAR) LIKE ?
        OR CAST(p.QUANTITY AS CHAR) LIKE ?
        OR p.SIZE LIKE ?
        OR p.IMAGE LIKE ?
";

if ($search !== '') {
    $likeSearch = '%' . $search . '%';
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total {$baseFromSql} {$searchWhereSql}");
    $countStmt->bind_param(
        'ssssssss',
        $likeSearch,
        $likeSearch,
        $likeSearch,
        $likeSearch,
        $likeSearch,
        $likeSearch,
        $likeSearch,
        $likeSearch
    );
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalItems = (int) ($countResult->fetch_assoc()['total'] ?? 0);
    $countStmt->close();

    $totalPages = max(1, (int) ceil($totalItems / $limit));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $limit;

    $stmt = $conn->prepare("
        SELECT p.*, d.NAME AS DEPARTMENT_NAME, pt.NAME AS PRODUCT_TYPE_NAME
        {$baseFromSql}
        {$searchWhereSql}
        ORDER BY p.ID DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param(
        'ssssssssii',
        $likeSearch,
        $likeSearch,
        $likeSearch,
        $likeSearch,
        $likeSearch,
        $likeSearch,
        $likeSearch,
        $likeSearch,
        $limit,
        $offset
    );
} else {
    $countResult = $conn->query("SELECT COUNT(*) AS total FROM ca_products");
    $totalItems = (int) ($countResult->fetch_assoc()['total'] ?? 0);

    $totalPages = max(1, (int) ceil($totalItems / $limit));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $limit;

    $stmt = $conn->prepare("
        SELECT p.*, d.NAME AS DEPARTMENT_NAME, pt.NAME AS PRODUCT_TYPE_NAME
        {$baseFromSql}
        ORDER BY p.ID DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param('ii', $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

$stmt->close();

$startItem = $totalItems > 0 ? $offset + 1 : 0;
$endItem = min($offset + count($products), $totalItems);
?>

<section role="main" class="content-body">
    <header class="page-header">
        <h2>Manage Products</h2>
    </header>

    <section class="panel">
        <div class="panel-body">
            <?php if ($message !== '') { ?>
                <div class="alert alert-<?php echo h($messageType); ?>">
                    <?php echo h($message); ?>
                </div>
            <?php } ?>

            <form method="GET" class="products-toolbar">
                <div class="products-toolbar-controls">
                    <button type="button" class="btn btn-success" id="open-add-product">
                        <i class="fa fa-plus"></i>
                    </button>
                    <div class="products-limit-control">
                        <!-- <label for="limit">Show</label> -->
                        <select class="form-control" id="limit" name="limit">
                            <?php foreach ($allowedLimits as $allowedLimit) { ?>
                                <option value="<?php echo (int) $allowedLimit; ?>" <?php echo $limit === $allowedLimit ? 'selected' : ''; ?>>
                                    <?php echo (int) $allowedLimit; ?>
                                </option>
                            <?php } ?>
                        </select>
                        <!-- <span>entries</span> -->
                    </div>

                    <div class="input-group products-search-control">
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo h($search); ?>" placeholder="Search">
                        <span class="input-group-btn">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-search"></i>
                            </button>
                            <?php if ($search !== '') { ?>
                                <a href="manage_products.php?limit=<?php echo (int) $limit; ?>" class="btn btn-default">Reset</a>
                            <?php } ?>
                        </span>
                    </div>
                </div>
            </form>

            <div style="overflow-x: auto;">
                <table class="table table-bordered table-striped table-hover" id="products-table">
                    <thead>
                        <tr>
                            <th>SL NO</th>
                            <th>Product Name</th>
                            <th>Department</th>
                            <th>Product Type</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Size</th>
                            <th>Image</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($products)) { ?>
                            <?php foreach ($products as $index => $row) { ?>
                                <tr>
                                    <td><?php echo (int) ($offset + $index + 1); ?></td>
                                    <td><?php echo h($row['PRODUCT_NAME']); ?></td>
                                    <td><?php echo h($row['DEPARTMENT_NAME'] ?? '-'); ?></td>
                                    <td><?php echo h($row['PRODUCT_TYPE_NAME'] ?? '-'); ?></td>
                                    <td>CAD <?php echo number_format((float) $row['PRICE'], 2); ?></td>
                                    <td><?php echo h($row['QUANTITY']); ?></td>
                                    <td><?php echo h($row['SIZE']); ?></td>
                                    <td>
                                        <?php if (!empty($row['IMAGE'])) { ?>
                                            <a href="<?php echo h($row['IMAGE']); ?>" target="_blank" rel="noopener noreferrer">
                                                <img src="<?php echo h($row['IMAGE']); ?>" alt="Product image" class="product-thumbnail">
                                            </a>
                                        <?php } else { ?>
                                            -
                                        <?php } ?>
                                    </td>
                                    <td style="white-space: nowrap;">
                                        <button
                                            type="button"
                                            class="btn btn-primary btn-xs view-product"
                                            data-id="<?php echo h($row['ID']); ?>"
                                            data-product_name="<?php echo h($row['PRODUCT_NAME']); ?>"
                                            data-department_name="<?php echo h($row['DEPARTMENT_NAME'] ?? '-'); ?>"
                                            data-product_type_name="<?php echo h($row['PRODUCT_TYPE_NAME'] ?? '-'); ?>"
                                            data-price="<?php echo h(number_format((float) $row['PRICE'], 2)); ?>"
                                            data-quantity="<?php echo h($row['QUANTITY']); ?>"
                                            data-size="<?php echo h($row['SIZE']); ?>"
                                            data-image="<?php echo h($row['IMAGE']); ?>">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-info btn-xs edit-product"
                                            data-id="<?php echo h($row['ID']); ?>"
                                            data-product_name="<?php echo h($row['PRODUCT_NAME']); ?>"
                                            data-department_id="<?php echo h($row['DEPARTMENT_ID']); ?>"
                                            data-product_type_id="<?php echo h($row['PRODUCT_TYPE_ID']); ?>"
                                            data-price="<?php echo h($row['PRICE']); ?>"
                                            data-quantity="<?php echo h($row['QUANTITY']); ?>"
                                            data-size="<?php echo h($row['SIZE']); ?>"
                                            data-image="<?php echo h($row['IMAGE']); ?>">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-danger btn-xs delete-product"
                                            data-id="<?php echo (int) $row['ID']; ?>">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="9" class="text-center">No products found.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <div class="products-pagination-row">
                <div class="products-pagination-info">
                    Showing <?php echo (int) $startItem; ?> to <?php echo (int) $endItem; ?> of <?php echo (int) $totalItems; ?> entries
                </div>

                <?php if ($totalPages > 1) { ?>
                    <ul class="pagination products-pagination">
                        <li class="<?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <?php if ($page <= 1) { ?>
                                <span>Previous</span>
                            <?php } else { ?>
                                <a href="<?php echo h(product_page_url($page - 1, $limit, $search)); ?>">Previous</a>
                            <?php } ?>
                        </li>

                        <?php
                        $firstPage = max(1, $page - 2);
                        $lastPage = min($totalPages, $page + 2);

                        if ($firstPage > 1) {
                        ?>
                            <li><a href="<?php echo h(product_page_url(1, $limit, $search)); ?>">1</a></li>
                            <?php if ($firstPage > 2) { ?>
                                <li class="disabled"><span>...</span></li>
                            <?php } ?>
                        <?php } ?>

                        <?php for ($pageNumber = $firstPage; $pageNumber <= $lastPage; $pageNumber++) { ?>
                            <li class="<?php echo $pageNumber === $page ? 'active' : ''; ?>">
                                <?php if ($pageNumber === $page) { ?>
                                    <span><?php echo (int) $pageNumber; ?></span>
                                <?php } else { ?>
                                    <a href="<?php echo h(product_page_url($pageNumber, $limit, $search)); ?>"><?php echo (int) $pageNumber; ?></a>
                                <?php } ?>
                            </li>
                        <?php } ?>

                        <?php if ($lastPage < $totalPages) { ?>
                            <?php if ($lastPage < $totalPages - 1) { ?>
                                <li class="disabled"><span>...</span></li>
                            <?php } ?>
                            <li><a href="<?php echo h(product_page_url($totalPages, $limit, $search)); ?>"><?php echo (int) $totalPages; ?></a></li>
                        <?php } ?>

                        <li class="<?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <?php if ($page >= $totalPages) { ?>
                                <span>Next</span>
                            <?php } else { ?>
                                <a href="<?php echo h(product_page_url($page + 1, $limit, $search)); ?>">Next</a>
                            <?php } ?>
                        </li>
                    </ul>
                <?php } ?>
            </div>
        </div>
    </section>
</section>

<style>
    .products-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 15px;
    }

    .products-toolbar-controls,
    .products-limit-control {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .products-limit-control .form-control {
        width: 82px;
    }

    .products-search-control {
        width: 320px;
    }

    .product-thumbnail {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #ddd;
        background: #f7f7f7;
        display: inline-block;
    }

    .products-pagination-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        margin-top: 15px;
    }

    .products-pagination-info {
        color: #777;
    }

    .products-pagination {
        margin: 0;
    }

    .product-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        z-index: 1050;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .product-modal-overlay.is-open {
        display: flex;
    }

    .product-modal {
        background: #fff;
        border-radius: 6px;
        width: 100%;
        max-width: 760px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
    }

    .product-modal-header,
    .product-modal-footer {
        padding: 15px 20px;
        border-bottom: 1px solid #e5e5e5;
    }

    .product-modal-footer {
        border-bottom: 0;
        border-top: 1px solid #e5e5e5;
        text-align: right;
    }

    .product-modal-body {
        padding: 20px;
    }

    .product-modal-close {
        float: right;
        font-size: 26px;
        line-height: 1;
        border: 0;
        background: transparent;
        cursor: pointer;
        color: #555;
    }

    .product-view-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px 18px;
    }

    .product-view-item {
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        min-width: 0;
    }

    .product-view-item strong {
        display: block;
        color: #333;
        margin-bottom: 4px;
    }

    .product-view-value {
        overflow-wrap: anywhere;
    }

    .product-image-preview {
        max-width: 120px;
        max-height: 90px;
        display: none;
        margin-top: 10px;
        border-radius: 4px;
        border: 1px solid #ddd;
    }

    body.product-modal-open {
        overflow: hidden;
    }

    @media (max-width: 767px) {

        .products-toolbar,
        .products-toolbar-controls,
        .products-pagination-row {
            align-items: stretch;
            flex-direction: column;
        }

        .products-search-control {
            width: 100%;
        }

        .product-view-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="product-modal-overlay" id="productFormModal" aria-labelledby="productFormModalLabel">
    <div class="product-modal" role="dialog" aria-modal="true">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="product-modal-header">
                    <button type="button" class="product-modal-close" id="closeProductFormModalTop" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="productFormModalLabel"><?php echo $formData['id'] ? 'Edit Product' : 'Add Product'; ?></h4>
                </div>
                <div class="product-modal-body">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?php echo h($formData['id']); ?>">
                    <input type="hidden" name="current_image" value="<?php echo h($formData['image']); ?>">

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="modal_product_name">Product Name <span>*</span></label>
                            <input type="text" class="form-control" id="modal_product_name" name="product_name" value="<?php echo h($formData['product_name']); ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="modal_department_id">Department <span>*</span></label>
                            <select class="form-control" id="modal_department_id" name="department_id" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $department) { ?>
                                    <option value="<?php echo h($department['ID']); ?>" <?php echo (string) $formData['department_id'] === (string) $department['ID'] ? 'selected' : ''; ?>>
                                        <?php echo h($department['NAME']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="modal_product_type_id">Product Type <span>*</span></label>
                            <select class="form-control" id="modal_product_type_id" name="product_type_id" required>
                                <option value="">Select Product Type</option>
                                <?php foreach ($productTypes as $productType) { ?>
                                    <option
                                        value="<?php echo h($productType['ID']); ?>"
                                        data-department_id="<?php echo h($productType['DEPARTMENT_ID']); ?>"
                                        <?php echo (string) $formData['product_type_id'] === (string) $productType['ID'] ? 'selected' : ''; ?>>
                                        <?php echo h($productType['NAME']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="modal_price">Price <span>*</span></label>
                            <input type="number" step="0.01" class="form-control" id="modal_price" name="price" value="<?php echo h($formData['price']); ?>" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="modal_quantity">Quantity <span>*</span></label>
                            <input type="number" class="form-control" id="modal_quantity" name="quantity" value="<?php echo h($formData['quantity']); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="modal_size">Size</label>
                            <input type="text" class="form-control" id="modal_size" name="size" value="<?php echo h($formData['size']); ?>">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="modal_image">Product Image <span id="modal_image_required_mark"><?php echo $formData['id'] ? '' : '*'; ?></span></label>
                            <input type="file" class="form-control" id="modal_image" name="image" accept="image/*" <?php echo $formData['id'] ? '' : 'required'; ?>>
                            <img src="<?php echo h($formData['image']); ?>" alt="Product preview" id="modal_product_image_preview" class="product-image-preview" <?php echo $formData['image'] !== '' ? ' style="display:block;"' : ''; ?>>
                        </div>
                    </div>
                </div>
                <div class="product-modal-footer">
                    <button type="button" class="btn btn-default" id="closeProductFormModalBottom">Close</button>
                    <button type="submit" class="btn btn-primary" id="productFormSubmitButton"><?php echo $formData['id'] ? 'Update Product' : 'Add Product'; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="product-modal-overlay" id="productViewModal" aria-labelledby="productViewModalLabel">
    <div class="product-modal" role="dialog" aria-modal="true">
        <div class="modal-content">
            <div class="product-modal-header">
                <button type="button" class="product-modal-close" id="closeProductViewModalTop" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="productViewModalLabel">View Product</h4>
            </div>
            <div class="product-modal-body">
                <div class="product-view-grid">
                    <div class="product-view-item">
                        <strong>ID</strong>
                        <div class="product-view-value" data-view-field="id"></div>
                    </div>
                    <div class="product-view-item">
                        <strong>Product Name</strong>
                        <div class="product-view-value" data-view-field="product_name"></div>
                    </div>
                    <div class="product-view-item">
                        <strong>Department</strong>
                        <div class="product-view-value" data-view-field="department_name"></div>
                    </div>
                    <div class="product-view-item">
                        <strong>Product Type</strong>
                        <div class="product-view-value" data-view-field="product_type_name"></div>
                    </div>
                    <div class="product-view-item">
                        <strong>Price</strong>
                        <div class="product-view-value" data-view-field="price"></div>
                    </div>
                    <div class="product-view-item">
                        <strong>Quantity</strong>
                        <div class="product-view-value" data-view-field="quantity"></div>
                    </div>
                    <div class="product-view-item">
                        <strong>Size</strong>
                        <div class="product-view-value" data-view-field="size"></div>
                    </div>
                    <div class="product-view-item">
                        <strong>Image</strong>
                        <div class="product-view-value" data-view-field="image"></div>
                    </div>
                </div>
            </div>
            <div class="product-modal-footer">
                <button type="button" class="btn btn-default" id="closeProductViewModalBottom">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    var productTypes = <?php echo json_encode($productTypes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    window.addEventListener('load', function() {
        var limitSelect = document.getElementById('limit');
        var formModal = document.getElementById('productFormModal');
        var viewModal = document.getElementById('productViewModal');
        var body = document.body;
        var openAddButton = document.getElementById('open-add-product');
        var closeFormTopButton = document.getElementById('closeProductFormModalTop');
        var closeFormBottomButton = document.getElementById('closeProductFormModalBottom');
        var closeViewTopButton = document.getElementById('closeProductViewModalTop');
        var closeViewBottomButton = document.getElementById('closeProductViewModalBottom');
        var formTitle = document.getElementById('productFormModalLabel');
        var formIdInput = document.querySelector('#productFormModal input[name="id"]');
        var currentImageInput = document.querySelector('#productFormModal input[name="current_image"]');
        var productNameInput = document.getElementById('modal_product_name');
        var departmentInput = document.getElementById('modal_department_id');
        var productTypeInput = document.getElementById('modal_product_type_id');
        var priceInput = document.getElementById('modal_price');
        var quantityInput = document.getElementById('modal_quantity');
        var sizeInput = document.getElementById('modal_size');
        var imageInput = document.getElementById('modal_image');
        var imageRequiredMark = document.getElementById('modal_image_required_mark');
        var imagePreview = document.getElementById('modal_product_image_preview');
        var formSubmitButton = document.getElementById('productFormSubmitButton');

        if (limitSelect) {
            limitSelect.addEventListener('change', function() {
                limitSelect.form.submit();
            });
        }

        function openViewModal() {
            viewModal.classList.add('is-open');
            body.classList.add('product-modal-open');
        }

        function closeViewModal() {
            viewModal.classList.remove('is-open');
            body.classList.remove('product-modal-open');
        }

        function openFormModal() {
            formModal.classList.add('is-open');
            body.classList.add('product-modal-open');
        }

        function closeFormModal() {
            formModal.classList.remove('is-open');
            body.classList.remove('product-modal-open');
        }

        function setFieldValue(element, value) {
            element.value = value || '';
        }

        function updateImagePreview(value) {
            if (value) {
                imagePreview.src = value;
                imagePreview.style.display = 'block';
            } else {
                imagePreview.src = '';
                imagePreview.style.display = 'none';
            }
        }

        function updateProductTypeOptions(departmentId, selectedProductTypeId) {
            var selectedValue = selectedProductTypeId || '';

            productTypeInput.innerHTML = '<option value="">Select Product Type</option>';

            productTypes.forEach(function(productType) {
                if (departmentId && String(productType.DEPARTMENT_ID) !== String(departmentId)) {
                    return;
                }

                var option = document.createElement('option');
                option.value = productType.ID;
                option.textContent = productType.NAME;

                if (String(productType.ID) === String(selectedValue)) {
                    option.selected = true;
                }

                productTypeInput.appendChild(option);
            });
        }

        function setProductForm(data) {
            var isEdit = !!data.id;

            formTitle.textContent = isEdit ? 'Edit Product' : 'Add Product';
            setFieldValue(formIdInput, data.id || '');
            setFieldValue(currentImageInput, data.image || '');
            setFieldValue(productNameInput, data.product_name || '');
            setFieldValue(departmentInput, data.department_id || '');
            updateProductTypeOptions(data.department_id || '', data.product_type_id || '');
            setFieldValue(priceInput, data.price || '');
            setFieldValue(quantityInput, data.quantity || '');
            setFieldValue(sizeInput, data.size || '');
            imageInput.value = '';
            imageInput.required = !isEdit;
            imageRequiredMark.textContent = isEdit ? '' : '*';
            updateImagePreview(data.image || '');
            formSubmitButton.textContent = isEdit ? 'Update Product' : 'Add Product';
        }

        function getEmptyProduct() {
            return {
                id: '',
                product_name: '',
                department_id: '',
                product_type_id: '',
                price: '',
                quantity: '',
                size: '',
                image: ''
            };
        }

        function setViewValue(field, value) {
            var element = viewModal.querySelector('[data-view-field="' + field + '"]');
            var displayValue = value || '-';

            if (!element) {
                return;
            }

            element.textContent = '';

            if (field === 'price' && value) {
                element.textContent = 'CAD ' + value;
                return;
            }

            if (field === 'image' && value) {
                var imageLink = document.createElement('a');
                var image = document.createElement('img');
                imageLink.href = value;
                imageLink.target = '_blank';
                imageLink.rel = 'noopener noreferrer';
                image.src = value;
                image.alt = 'Product image';
                image.className = 'product-thumbnail';
                imageLink.appendChild(image);
                element.appendChild(imageLink);
                return;
            }

            element.textContent = displayValue;
        }

        function setProductView(data) {
            [
                'id',
                'product_name',
                'department_name',
                'product_type_name',
                'price',
                'quantity',
                'size',
                'image'
            ].forEach(function(field) {
                setViewValue(field, data[field] || '');
            });
        }

        document.querySelectorAll('.view-product').forEach(function(button) {
            button.addEventListener('click', function() {
                setProductView(button.dataset);
                openViewModal();
            });
        });

        if (openAddButton) {
            openAddButton.addEventListener('click', function() {
                setProductForm(getEmptyProduct());
                openFormModal();
            });
        }

        document.querySelectorAll('.edit-product').forEach(function(button) {
            button.addEventListener('click', function() {
                setProductForm(button.dataset);
                openFormModal();
            });
        });

        if (departmentInput) {
            departmentInput.addEventListener('change', function() {
                updateProductTypeOptions(departmentInput.value, '');
            });
        }

        if (imageInput) {
            imageInput.addEventListener('change', function() {
                var file = imageInput.files && imageInput.files[0];

                if (file) {
                    updateImagePreview(URL.createObjectURL(file));
                }
            });
        }

        document.querySelectorAll('.delete-product').forEach(function(button) {
            button.addEventListener('click', function() {
                var productId = button.getAttribute('data-id');

                if (!confirm('Are you sure you want to delete this product?')) {
                    return;
                }

                window.jQuery.ajax({
                    url: 'api/delete_product.php',
                    type: 'POST',
                    data: {
                        id: productId
                    },
                    success: function(response) {
                        var data = JSON.parse(response);

                        if (data.success) {
                            alert('Product deleted successfully.');
                            window.location.reload();
                        } else {
                            alert('Error deleting product.');
                        }
                    },
                    error: function() {
                        alert('An unexpected error occurred.');
                    }
                });
            });
        });

        if (closeViewTopButton) {
            closeViewTopButton.addEventListener('click', closeViewModal);
        }

        if (closeViewBottomButton) {
            closeViewBottomButton.addEventListener('click', closeViewModal);
        }

        if (closeFormTopButton) {
            closeFormTopButton.addEventListener('click', closeFormModal);
        }

        if (closeFormBottomButton) {
            closeFormBottomButton.addEventListener('click', closeFormModal);
        }

        formModal.addEventListener('click', function(event) {
            if (event.target === formModal) {
                closeFormModal();
            }
        });

        viewModal.addEventListener('click', function(event) {
            if (event.target === viewModal) {
                closeViewModal();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && viewModal.classList.contains('is-open')) {
                closeViewModal();
            }

            if (event.key === 'Escape' && formModal.classList.contains('is-open')) {
                closeFormModal();
            }
        });

        <?php if ($showProductModal) { ?>
            updateProductTypeOptions(<?php echo json_encode($formData['department_id']); ?>, <?php echo json_encode($formData['product_type_id']); ?>);
            openFormModal();
        <?php } ?>
    });
</script>

<?php include('footer.php'); ?>