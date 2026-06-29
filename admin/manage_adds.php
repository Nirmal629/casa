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

function advertisement_page_url($page, $limit, $search)
{
    $params = [
        'page' => $page,
        'limit' => $limit,
    ];

    if ($search !== '') {
        $params['search'] = $search;
    }

    return 'manage_adds.php?' . http_build_query($params);
}

$message = '';
$messageType = 'success';
$search = trim($_GET['search'] ?? '');
$limit = (int) ($_GET['limit'] ?? 25);
$page = max(1, (int) ($_GET['page'] ?? 1));
$allowedLimits = [10, 25, 50, 100];
$paymentStatusOptions = ['PENDING', 'PAID', 'FAILED', 'REFUNDED'];
$advertisementStatusOptions = ['ACTIVE', 'INACTIVE', 'EXPIRED'];

if (!in_array($limit, $allowedLimits, true)) {
    $limit = 25;
}

$editId = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
$showAdvertisementModal = false;

$formData = [
    'id' => '',
    'player_id' => '',
    'short_text' => '',
    'main_image' => '',
    'redirect_url' => '',
    'start_date' => '',
    'end_date' => '',
    'amount' => '',
    'payment_status' => '',
    'status' => '',
    'priority' => '',
    'impressions_count' => '0',
    'click_count' => '0',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $recordId = (int) ($_POST['id'] ?? 0);

        $formData = [
            'id' => $recordId,
            'player_id' => trim($_POST['player_id'] ?? ''),
            'short_text' => trim($_POST['short_text'] ?? ''),
            'main_image' => trim($_POST['main_image'] ?? ''),
            'redirect_url' => trim($_POST['redirect_url'] ?? ''),
            'start_date' => trim($_POST['start_date'] ?? ''),
            'end_date' => trim($_POST['end_date'] ?? ''),
            'amount' => trim($_POST['amount'] ?? ''),
            'payment_status' => trim($_POST['payment_status'] ?? ''),
            'status' => trim($_POST['status'] ?? ''),
            'priority' => trim($_POST['priority'] ?? ''),
            'impressions_count' => trim($_POST['impressions_count'] ?? '0'),
            'click_count' => trim($_POST['click_count'] ?? '0'),
        ];

        if ($formData['player_id'] === '' || $formData['short_text'] === '') {
            $message = 'Player ID and short text are required.';
            $messageType = 'danger';
            $showAdvertisementModal = true;
        } else {
            $playerId = (int) $formData['player_id'];
            $shortText = normalize_value($formData['short_text']);
            $mainImage = normalize_value($formData['main_image']);
            $redirectUrl = normalize_value($formData['redirect_url']);
            $startDate = normalize_value($formData['start_date']);
            $endDate = normalize_value($formData['end_date']);
            $amount = normalize_value($formData['amount']);
            $paymentStatus = normalize_value($formData['payment_status']);
            $status = normalize_value($formData['status']);
            $priority = $formData['priority'] === '' ? 0 : (int) $formData['priority'];
            $impressionsCount = $formData['impressions_count'] === '' ? 0 : (int) $formData['impressions_count'];
            $clickCount = $formData['click_count'] === '' ? 0 : (int) $formData['click_count'];

            if ($recordId > 0) {
                $stmt = $conn->prepare("UPDATE ca_advertisements SET player_id = ?, short_text = ?, main_image = ?, redirect_url = ?, start_date = ?, end_date = ?, amount = ?, payment_status = ?, status = ?, priority = ?, impressions_count = ?, click_count = ? WHERE id = ?");
                $stmt->bind_param(
                    'issssssssiiii',
                    $playerId,
                    $shortText,
                    $mainImage,
                    $redirectUrl,
                    $startDate,
                    $endDate,
                    $amount,
                    $paymentStatus,
                    $status,
                    $priority,
                    $impressionsCount,
                    $clickCount,
                    $recordId
                );

                if ($stmt->execute()) {
                    echo "<script>alert('Advertisement updated successfully.'); window.location.href='manage_adds.php';</script>";
                    exit;
                }

                $message = 'Failed to update advertisement: ' . $stmt->error;
                $messageType = 'danger';
                $showAdvertisementModal = true;
                $stmt->close();
            } else {
                $stmt = $conn->prepare("INSERT INTO ca_advertisements (player_id, short_text, main_image, redirect_url, start_date, end_date, amount, payment_status, status, priority, impressions_count, click_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param(
                    'issssssssiii',
                    $playerId,
                    $shortText,
                    $mainImage,
                    $redirectUrl,
                    $startDate,
                    $endDate,
                    $amount,
                    $paymentStatus,
                    $status,
                    $priority,
                    $impressionsCount,
                    $clickCount
                );

                if ($stmt->execute()) {
                    echo "<script>alert('Advertisement added successfully.'); window.location.href='manage_adds.php';</script>";
                    exit;
                }

                $message = 'Failed to add advertisement: ' . $stmt->error;
                $messageType = 'danger';
                $showAdvertisementModal = true;
                $stmt->close();
            }
        }
    } elseif ($action === 'delete') {
        $deleteId = (int) ($_POST['delete_id'] ?? 0);

        if ($deleteId > 0) {
            $stmt = $conn->prepare("DELETE FROM ca_advertisements WHERE id = ?");
            $stmt->bind_param('i', $deleteId);

            if ($stmt->execute()) {
                echo "<script>alert('Advertisement deleted successfully.'); window.location.href='manage_adds.php';</script>";
                exit;
            }

            $message = 'Failed to delete advertisement: ' . $stmt->error;
            $messageType = 'danger';
            $stmt->close();
        }
    }
}

if ($editId > 0) {
    $showAdvertisementModal = true;
    $stmt = $conn->prepare("SELECT * FROM ca_advertisements WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editResult = $stmt->get_result();

    if ($editRow = $editResult->fetch_assoc()) {
        $formData = [
            'id' => $editRow['id'],
            'player_id' => $editRow['player_id'],
            'short_text' => $editRow['short_text'],
            'main_image' => $editRow['main_image'],
            'redirect_url' => $editRow['redirect_url'],
            'start_date' => $editRow['start_date'],
            'end_date' => $editRow['end_date'],
            'amount' => $editRow['amount'],
            'payment_status' => $editRow['payment_status'],
            'status' => $editRow['status'],
            'priority' => $editRow['priority'],
            'impressions_count' => $editRow['impressions_count'],
            'click_count' => $editRow['click_count'],
        ];
    } else {
        $message = 'Selected advertisement was not found.';
        $messageType = 'warning';
    }

    $stmt->close();
}

$currentPaymentStatusIsCustom = $formData['payment_status'] !== '' && !in_array($formData['payment_status'], $paymentStatusOptions, true);
$currentAdvertisementStatusIsCustom = $formData['status'] !== '' && !in_array($formData['status'], $advertisementStatusOptions, true);

$advertisements = [];
$totalItems = 0;
$totalPages = 1;
$offset = 0;

if ($search !== '') {
    $likeSearch = '%' . $search . '%';
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM ca_advertisements WHERE CAST(id AS CHAR) LIKE ? OR CAST(player_id AS CHAR) LIKE ? OR short_text LIKE ? OR main_image LIKE ? OR redirect_url LIKE ? OR payment_status LIKE ? OR status LIKE ? OR CAST(priority AS CHAR) LIKE ? OR CAST(impressions_count AS CHAR) LIKE ? OR CAST(click_count AS CHAR) LIKE ? OR CAST(amount AS CHAR) LIKE ? OR CAST(start_date AS CHAR) LIKE ? OR CAST(end_date AS CHAR) LIKE ? OR CAST(created_at AS CHAR) LIKE ? OR CAST(updated_at AS CHAR) LIKE ?");
    $countStmt->bind_param(
        'sssssssssssssss',
        $likeSearch,
        $likeSearch,
        $likeSearch,
        $likeSearch,
        $likeSearch,
        $likeSearch,
        $likeSearch,
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

    $stmt = $conn->prepare("SELECT * FROM ca_advertisements WHERE CAST(id AS CHAR) LIKE ? OR CAST(player_id AS CHAR) LIKE ? OR short_text LIKE ? OR main_image LIKE ? OR redirect_url LIKE ? OR payment_status LIKE ? OR status LIKE ? OR CAST(priority AS CHAR) LIKE ? OR CAST(impressions_count AS CHAR) LIKE ? OR CAST(click_count AS CHAR) LIKE ? OR CAST(amount AS CHAR) LIKE ? OR CAST(start_date AS CHAR) LIKE ? OR CAST(end_date AS CHAR) LIKE ? OR CAST(created_at AS CHAR) LIKE ? OR CAST(updated_at AS CHAR) LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param(
        'sssssssssssssssii',
        $likeSearch,
        $likeSearch,
        $likeSearch,
        $likeSearch,
        $likeSearch,
        $likeSearch,
        $likeSearch,
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
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $countResult = $conn->query("SELECT COUNT(*) AS total FROM ca_advertisements");
    $totalItems = (int) ($countResult->fetch_assoc()['total'] ?? 0);

    $totalPages = max(1, (int) ceil($totalItems / $limit));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $limit;

    $stmt = $conn->prepare("SELECT * FROM ca_advertisements ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $advertisements[] = $row;
    }
}

if (isset($stmt)) {
    $stmt->close();
}

$startItem = $totalItems > 0 ? $offset + 1 : 0;
$endItem = min($offset + count($advertisements), $totalItems);
?>

<section role="main" class="content-body">
    <header class="page-header">
        <h2>Manage Advertisements</h2>

        <!-- <div class="right-wrapper pull-right">
            <ol class="breadcrumbs">
                <li>
                    <a href="index.php">
                        <i class="fa fa-home"></i>
                    </a>
                </li>
                <li><span>Manage Ads</span></li>
                <li><span>Advertisements</span></li>
            </ol>

            <a class="sidebar-right-toggle" data-open="sidebar-right"><i class="fa fa-chevron-left"></i></a>
        </div> -->
    </header>

    <section class="panel">
        <!-- <header class="panel-heading"> -->
            <!-- <div class="panel-actions">
                <a href="#" class="panel-action panel-action-toggle" data-panel-toggle></a>
                <a href="#" class="panel-action panel-action-dismiss" data-panel-dismiss></a>
            </div> -->
            <!-- <h2 class="panel-title">All Advertisements</h2> -->
        <!-- </header> -->
        <div class="panel-body">
            <?php if ($message !== '') { ?>
                <div class="alert alert-<?php echo h($messageType); ?>">
                    <?php echo h($message); ?>
                </div>
            <?php } ?>

            <form method="GET" class="advertisements-toolbar">
                <button type="button" class="btn btn-success" id="open-add-advertisement">
                    <i class="fa fa-plus"></i> Add
                </button>

                <div class="advertisements-toolbar-controls">
                    <div class="advertisements-limit-control">
                        <label for="limit">Show</label>
                        <select class="form-control" id="limit" name="limit">
                            <?php foreach ($allowedLimits as $allowedLimit) { ?>
                                <option value="<?php echo (int) $allowedLimit; ?>" <?php echo $limit === $allowedLimit ? 'selected' : ''; ?>>
                                    <?php echo (int) $allowedLimit; ?>
                                </option>
                            <?php } ?>
                        </select>
                        <span>entries</span>
                    </div>

                    <div class="input-group advertisements-search-control">
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo h($search); ?>" placeholder="Search">
                        <span class="input-group-btn">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-search"></i>
                            </button>
                            <?php if ($search !== '') { ?>
                                <a href="manage_adds.php?limit=<?php echo (int) $limit; ?>" class="btn btn-default">Reset</a>
                            <?php } ?>
                        </span>
                    </div>
                </div>
            </form>

            <div style="overflow-x: auto;">
                <table class="table table-bordered table-striped table-hover" id="advertisements-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Player ID</th>
                            <th>Short Text</th>
                            <th>Image</th>
                            <!-- <th>Redirect URL</th> -->
                            <th>Start Date</th>
                            <th>End Date</th>
                            <!-- <th>Amount</th> -->
                            <th>Payment Status</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <!-- <th>Impressions</th> -->
                            <!-- <th>Clicks</th> -->
                            <th>Created At</th>
                            <!-- <th>Updated At</th> -->
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($advertisements)) { ?>
                            <?php foreach ($advertisements as $row) { ?>
                                <tr>
                                    <td><?php echo h($row['id']); ?></td>
                                    <td><?php echo h($row['player_id']); ?></td>
                                    <td><?php echo h($row['short_text']); ?></td>
                                    <td>
                                        <?php if (!empty($row['main_image'])) { ?>
                                            <a href="<?php echo h($row['main_image']); ?>" target="_blank" rel="noopener noreferrer">
                                                <img src="<?php echo h($row['main_image']); ?>" alt="Advertisement image" class="advertisement-thumbnail">
                                            </a>
                                        <?php } else { ?>
                                            ---
                                        <?php } ?>
                                    </td>
                                    <!-- <td>
                                        <//?php if (!empty($row['redirect_url'])) { ?>
                                            <a href="<//?php echo h($row['redirect_url']); ?>" target="_blank"><?php echo h($row['redirect_url']); ?></a>
                                        <//?php } else { ?>
                                            ----
                                        <//?php } ?>
                                    </td> -->
                                    <td><?php echo h($row['start_date']); ?></td>
                                    <td><?php echo h($row['end_date']); ?></td>
                                    <!-- <td><//?php echo h($row['amount']); ?></td> -->
                                    <td><?php echo h($row['payment_status']); ?></td>
                                    <td><?php echo h($row['status']); ?></td>
                                    <td><?php echo h($row['priority']); ?></td>
                                    <!-- <td><//?php echo h($row['impressions_count']); ?></td> -->
                                    <!-- <td><//?php echo h($row['click_count']); ?></td> -->
                                    <td><?php echo h($row['created_at']); ?></td>
                                    <!-- <td><//?php echo h($row['updated_at']); ?></td> -->
                                    <td style="white-space: nowrap;">
                                        <button
                                            type="button"
                                            class="btn btn-primary btn-xs view-advertisement"
                                            data-id="<?php echo h($row['id']); ?>"
                                            data-player_id="<?php echo h($row['player_id']); ?>"
                                            data-short_text="<?php echo h($row['short_text']); ?>"
                                            data-main_image="<?php echo h($row['main_image']); ?>"
                                            data-redirect_url="<?php echo h($row['redirect_url']); ?>"
                                            data-start_date="<?php echo h($row['start_date']); ?>"
                                            data-end_date="<?php echo h($row['end_date']); ?>"
                                            data-amount="<?php echo h($row['amount']); ?>"
                                            data-payment_status="<?php echo h($row['payment_status']); ?>"
                                            data-status="<?php echo h($row['status']); ?>"
                                            data-priority="<?php echo h($row['priority']); ?>"
                                            data-impressions_count="<?php echo h($row['impressions_count']); ?>"
                                            data-click_count="<?php echo h($row['click_count']); ?>"
                                            data-created_at="<?php echo h($row['created_at']); ?>"
                                            data-updated_at="<?php echo h($row['updated_at']); ?>"
                                        >
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-info btn-xs edit-advertisement"
                                            data-id="<?php echo h($row['id']); ?>"
                                            data-player_id="<?php echo h($row['player_id']); ?>"
                                            data-short_text="<?php echo h($row['short_text']); ?>"
                                            data-main_image="<?php echo h($row['main_image']); ?>"
                                            data-redirect_url="<?php echo h($row['redirect_url']); ?>"
                                            data-start_date="<?php echo h($row['start_date']); ?>"
                                            data-end_date="<?php echo h($row['end_date']); ?>"
                                            data-amount="<?php echo h($row['amount']); ?>"
                                            data-payment_status="<?php echo h($row['payment_status']); ?>"
                                            data-status="<?php echo h($row['status']); ?>"
                                            data-priority="<?php echo h($row['priority']); ?>"
                                            data-impressions_count="<?php echo h($row['impressions_count']); ?>"
                                            data-click_count="<?php echo h($row['click_count']); ?>"
                                        >
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this advertisement?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="delete_id" value="<?php echo (int) $row['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-xs">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="16" class="text-center">No advertisements found.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <div class="advertisements-pagination-row">
                <div class="advertisements-pagination-info">
                    Showing <?php echo (int) $startItem; ?> to <?php echo (int) $endItem; ?> of <?php echo (int) $totalItems; ?> entries
                </div>

                <?php if ($totalPages > 1) { ?>
                    <ul class="pagination advertisements-pagination">
                        <li class="<?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <?php if ($page <= 1) { ?>
                                <span>Previous</span>
                            <?php } else { ?>
                                <a href="<?php echo h(advertisement_page_url($page - 1, $limit, $search)); ?>">Previous</a>
                            <?php } ?>
                        </li>

                        <?php
                        $firstPage = max(1, $page - 2);
                        $lastPage = min($totalPages, $page + 2);

                        if ($firstPage > 1) {
                        ?>
                            <li><a href="<?php echo h(advertisement_page_url(1, $limit, $search)); ?>">1</a></li>
                            <?php if ($firstPage > 2) { ?>
                                <li class="disabled"><span>...</span></li>
                            <?php } ?>
                        <?php } ?>

                        <?php for ($pageNumber = $firstPage; $pageNumber <= $lastPage; $pageNumber++) { ?>
                            <li class="<?php echo $pageNumber === $page ? 'active' : ''; ?>">
                                <?php if ($pageNumber === $page) { ?>
                                    <span><?php echo (int) $pageNumber; ?></span>
                                <?php } else { ?>
                                    <a href="<?php echo h(advertisement_page_url($pageNumber, $limit, $search)); ?>"><?php echo (int) $pageNumber; ?></a>
                                <?php } ?>
                            </li>
                        <?php } ?>

                        <?php if ($lastPage < $totalPages) { ?>
                            <?php if ($lastPage < $totalPages - 1) { ?>
                                <li class="disabled"><span>...</span></li>
                            <?php } ?>
                            <li><a href="<?php echo h(advertisement_page_url($totalPages, $limit, $search)); ?>"><?php echo (int) $totalPages; ?></a></li>
                        <?php } ?>

                        <li class="<?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <?php if ($page >= $totalPages) { ?>
                                <span>Next</span>
                            <?php } else { ?>
                                <a href="<?php echo h(advertisement_page_url($page + 1, $limit, $search)); ?>">Next</a>
                            <?php } ?>
                        </li>
                    </ul>
                <?php } ?>
            </div>
        </div>
    </section>
</section>

<style>
    .advertisements-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 15px;
    }

    .advertisements-toolbar-controls,
    .advertisements-limit-control {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .advertisements-limit-control .form-control {
        width: 82px;
    }

    .advertisements-search-control {
        width: 320px;
    }

    .advertisements-pagination-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        margin-top: 15px;
    }

    .advertisements-pagination-info {
        color: #777;
    }

    .advertisements-pagination {
        margin: 0;
    }

    @media (max-width: 767px) {
        .advertisements-toolbar,
        .advertisements-toolbar-controls,
        .advertisements-pagination-row {
            align-items: stretch;
            flex-direction: column;
        }

        .advertisements-search-control {
            width: 100%;
        }
    }

    .advertisement-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        z-index: 1050;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .advertisement-modal-overlay.is-open {
        display: flex;
    }

    .advertisement-modal {
        background: #fff;
        border-radius: 6px;
        width: 100%;
        max-width: 980px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
    }

    .advertisement-modal-header,
    .advertisement-modal-footer {
        padding: 15px 20px;
        border-bottom: 1px solid #e5e5e5;
    }

    .advertisement-modal-footer {
        border-bottom: 0;
        border-top: 1px solid #e5e5e5;
        text-align: right;
    }

    .advertisement-modal-body {
        padding: 20px;
    }

    .advertisement-modal-close {
        float: right;
        font-size: 26px;
        line-height: 1;
        border: 0;
        background: transparent;
        cursor: pointer;
        color: #555;
    }

    body.advertisement-modal-open {
        overflow: hidden;
    }

    .advertisement-thumbnail {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #ddd;
        background: #f7f7f7;
        display: inline-block;
    }

    .advertisement-view-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px 18px;
    }

    .advertisement-view-item {
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        min-width: 0;
    }

    .advertisement-view-item strong {
        display: block;
        color: #333;
        margin-bottom: 4px;
    }

    .advertisement-view-value {
        overflow-wrap: anywhere;
    }

    @media (max-width: 767px) {
        .advertisement-view-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="advertisement-modal-overlay" id="advertisementModal" aria-labelledby="advertisementModalLabel">
    <div class="advertisement-modal" role="dialog" aria-modal="true">
        <div class="modal-content">
            <form method="POST">
                <div class="advertisement-modal-header">
                    <button type="button" class="advertisement-modal-close" id="closeAdvertisementModalTop" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="advertisementModalLabel"><?php echo $formData['id'] ? 'Edit Advertisement' : 'Add Advertisement'; ?></h4>
                </div>
                <div class="advertisement-modal-body">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?php echo h($formData['id']); ?>">

                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label for="modal_player_id">Player ID <span>*</span></label>
                            <input type="number" class="form-control" id="modal_player_id" name="player_id" value="<?php echo h($formData['player_id']); ?>" required>
                        </div>
                        <div class="col-md-5 form-group">
                            <label for="modal_short_text">Short Text <span>*</span></label>
                            <input type="text" class="form-control" id="modal_short_text" name="short_text" value="<?php echo h($formData['short_text']); ?>" required>
                        </div>
                        <div class="col-md-2 form-group">
                            <label for="modal_amount">Amount</label>
                            <input type="text" class="form-control" id="modal_amount" name="amount" value="<?php echo h($formData['amount']); ?>">
                        </div>
                        <div class="col-md-2 form-group">
                            <label for="modal_priority">Priority</label>
                            <input type="number" class="form-control" id="modal_priority" name="priority" value="<?php echo h($formData['priority']); ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label for="modal_main_image">Image</label>
                            <input type="text" class="form-control" id="modal_main_image" name="main_image" value="<?php echo h($formData['main_image']); ?>" placeholder="Image path or URL">
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="modal_redirect_url">Redirect URL</label>
                            <input type="text" class="form-control" id="modal_redirect_url" name="redirect_url" value="<?php echo h($formData['redirect_url']); ?>" placeholder="https://example.com">
                        </div>
                        <div class="col-md-2 form-group">
                            <label for="modal_payment_status">Payment Status</label>
                            <select class="form-control" id="modal_payment_status" name="payment_status">
                                <option value="">Select</option>
                                <?php if ($currentPaymentStatusIsCustom) { ?>
                                    <option value="<?php echo h($formData['payment_status']); ?>" selected>
                                        <?php echo h($formData['payment_status']); ?>
                                    </option>
                                <?php } ?>
                                <?php foreach ($paymentStatusOptions as $paymentStatusOption) { ?>
                                    <option value="<?php echo h($paymentStatusOption); ?>" <?php echo $formData['payment_status'] === $paymentStatusOption ? 'selected' : ''; ?>>
                                        <?php echo h($paymentStatusOption); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-2 form-group">
                            <label for="modal_status">Status</label>
                            <select class="form-control" id="modal_status" name="status">
                                <option value="">Select</option>
                                <?php if ($currentAdvertisementStatusIsCustom) { ?>
                                    <option value="<?php echo h($formData['status']); ?>" selected>
                                        <?php echo h($formData['status']); ?>
                                    </option>
                                <?php } ?>
                                <?php foreach ($advertisementStatusOptions as $advertisementStatusOption) { ?>
                                    <option value="<?php echo h($advertisementStatusOption); ?>" <?php echo $formData['status'] === $advertisementStatusOption ? 'selected' : ''; ?>>
                                        <?php echo h($advertisementStatusOption); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label for="modal_start_date">Start Date</label>
                            <input type="date" class="form-control" id="modal_start_date" name="start_date" value="<?php echo h($formData['start_date']); ?>">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="modal_end_date">End Date</label>
                            <input type="date" class="form-control" id="modal_end_date" name="end_date" value="<?php echo h($formData['end_date']); ?>">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="modal_impressions_count">Impressions Count</label>
                            <input type="number" class="form-control" id="modal_impressions_count" name="impressions_count" value="<?php echo h($formData['impressions_count']); ?>">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="modal_click_count">Click Count</label>
                            <input type="number" class="form-control" id="modal_click_count" name="click_count" value="<?php echo h($formData['click_count']); ?>">
                        </div>
                    </div>
                </div>
                <div class="advertisement-modal-footer">
                    <a href="manage_adds.php" class="btn btn-default">Clear</a>
                    <button type="button" class="btn btn-default" id="closeAdvertisementModalBottom">Close</button>
                    <button type="submit" class="btn btn-primary"><?php echo $formData['id'] ? 'Update Advertisement' : 'Add Advertisement'; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="advertisement-modal-overlay" id="advertisementViewModal" aria-labelledby="advertisementViewModalLabel">
    <div class="advertisement-modal" role="dialog" aria-modal="true">
        <div class="modal-content">
            <div class="advertisement-modal-header">
                <button type="button" class="advertisement-modal-close" id="closeAdvertisementViewModalTop" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="advertisementViewModalLabel">View Advertisement</h4>
            </div>
            <div class="advertisement-modal-body">
                <div class="advertisement-view-grid">
                    <div class="advertisement-view-item">
                        <strong>ID</strong>
                        <div class="advertisement-view-value" data-view-field="id"></div>
                    </div>
                    <div class="advertisement-view-item">
                        <strong>Player ID</strong>
                        <div class="advertisement-view-value" data-view-field="player_id"></div>
                    </div>
                    <div class="advertisement-view-item">
                        <strong>Short Text</strong>
                        <div class="advertisement-view-value" data-view-field="short_text"></div>
                    </div>
                    <div class="advertisement-view-item">
                        <strong>Image</strong>
                        <div class="advertisement-view-value" data-view-field="main_image"></div>
                    </div>
                    <div class="advertisement-view-item">
                        <strong>Redirect URL</strong>
                        <div class="advertisement-view-value" data-view-field="redirect_url"></div>
                    </div>
                    <div class="advertisement-view-item">
                        <strong>Start Date</strong>
                        <div class="advertisement-view-value" data-view-field="start_date"></div>
                    </div>
                    <div class="advertisement-view-item">
                        <strong>End Date</strong>
                        <div class="advertisement-view-value" data-view-field="end_date"></div>
                    </div>
                    <div class="advertisement-view-item">
                        <strong>Amount</strong>
                        <div class="advertisement-view-value" data-view-field="amount"></div>
                    </div>
                    <div class="advertisement-view-item">
                        <strong>Payment Status</strong>
                        <div class="advertisement-view-value" data-view-field="payment_status"></div>
                    </div>
                    <div class="advertisement-view-item">
                        <strong>Status</strong>
                        <div class="advertisement-view-value" data-view-field="status"></div>
                    </div>
                    <div class="advertisement-view-item">
                        <strong>Priority</strong>
                        <div class="advertisement-view-value" data-view-field="priority"></div>
                    </div>
                    <div class="advertisement-view-item">
                        <strong>Impressions</strong>
                        <div class="advertisement-view-value" data-view-field="impressions_count"></div>
                    </div>
                    <div class="advertisement-view-item">
                        <strong>Clicks</strong>
                        <div class="advertisement-view-value" data-view-field="click_count"></div>
                    </div>
                    <div class="advertisement-view-item">
                        <strong>Created At</strong>
                        <div class="advertisement-view-value" data-view-field="created_at"></div>
                    </div>
                    <div class="advertisement-view-item">
                        <strong>Updated At</strong>
                        <div class="advertisement-view-value" data-view-field="updated_at"></div>
                    </div>
                </div>
            </div>
            <div class="advertisement-modal-footer">
                <button type="button" class="btn btn-default" id="closeAdvertisementViewModalBottom">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('load', function () {
        var $ = window.jQuery;
        var modal = document.getElementById('advertisementModal');
        var viewModal = document.getElementById('advertisementViewModal');
        var body = document.body;
        var openAddButton = document.getElementById('open-add-advertisement');
        var closeTopButton = document.getElementById('closeAdvertisementModalTop');
        var closeBottomButton = document.getElementById('closeAdvertisementModalBottom');
        var closeViewTopButton = document.getElementById('closeAdvertisementViewModalTop');
        var closeViewBottomButton = document.getElementById('closeAdvertisementViewModalBottom');
        var modalTitle = document.getElementById('advertisementModalLabel');
        var modalIdInput = document.querySelector('#advertisementModal input[name="id"]');
        var playerIdInput = document.getElementById('modal_player_id');
        var shortTextInput = document.getElementById('modal_short_text');
        var mainImageInput = document.getElementById('modal_main_image');
        var redirectUrlInput = document.getElementById('modal_redirect_url');
        var startDateInput = document.getElementById('modal_start_date');
        var endDateInput = document.getElementById('modal_end_date');
        var amountInput = document.getElementById('modal_amount');
        var paymentStatusInput = document.getElementById('modal_payment_status');
        var statusInput = document.getElementById('modal_status');
        var priorityInput = document.getElementById('modal_priority');
        var impressionsCountInput = document.getElementById('modal_impressions_count');
        var clickCountInput = document.getElementById('modal_click_count');
        var submitButton = document.querySelector('#advertisementModal .btn-primary');
        var limitSelect = document.getElementById('limit');

        if (limitSelect) {
            limitSelect.addEventListener('change', function () {
                limitSelect.form.submit();
            });
        }

        function openModal() {
            modal.classList.add('is-open');
            body.classList.add('advertisement-modal-open');
        }

        function closeModal() {
            modal.classList.remove('is-open');
            body.classList.remove('advertisement-modal-open');
        }

        function openViewModal() {
            viewModal.classList.add('is-open');
            body.classList.add('advertisement-modal-open');
        }

        function closeViewModal() {
            viewModal.classList.remove('is-open');
            body.classList.remove('advertisement-modal-open');
        }

        function setFieldValue(element, value) {
            element.value = value || '';
        }

        function setSelectValue(element, value) {
            var hasOption = false;
            var normalizedValue = value || '';

            Array.prototype.forEach.call(element.options, function (option) {
                if (option.value === normalizedValue) {
                    hasOption = true;
                }
            });

            if (normalizedValue !== '' && !hasOption) {
                var option = new Option(normalizedValue, normalizedValue, true, true);
                element.add(option);
            }

            element.value = normalizedValue;
        }

        function setAdvertisementModal(data) {
            modalTitle.textContent = data.id ? 'Edit Advertisement' : 'Add Advertisement';
            setFieldValue(modalIdInput, data.id || '');
            setFieldValue(playerIdInput, data.player_id || '');
            setFieldValue(shortTextInput, data.short_text || '');
            setFieldValue(mainImageInput, data.main_image || '');
            setFieldValue(redirectUrlInput, data.redirect_url || '');
            setFieldValue(startDateInput, data.start_date || '');
            setFieldValue(endDateInput, data.end_date || '');
            setFieldValue(amountInput, data.amount || '');
            setSelectValue(paymentStatusInput, data.payment_status || '');
            setSelectValue(statusInput, data.status || '');
            setFieldValue(priorityInput, data.priority || '');
            setFieldValue(impressionsCountInput, data.impressions_count || '0');
            setFieldValue(clickCountInput, data.click_count || '0');
            submitButton.textContent = data.id ? 'Update Advertisement' : 'Add Advertisement';
        }

        function getEmptyAdvertisement() {
            return {
                id: '',
                player_id: '',
                short_text: '',
                main_image: '',
                redirect_url: '',
                start_date: '',
                end_date: '',
                amount: '',
                payment_status: '',
                status: '',
                priority: '',
                impressions_count: '0',
                click_count: '0'
            };
        }

        function setViewValue(field, value) {
            var element = viewModal.querySelector('[data-view-field="' + field + '"]');
            var displayValue = value || '-';

            if (!element) {
                return;
            }

            element.textContent = '';

            if (field === 'main_image' && value) {
                var imageLink = document.createElement('a');
                var image = document.createElement('img');
                imageLink.href = value;
                imageLink.target = '_blank';
                imageLink.rel = 'noopener noreferrer';
                image.src = value;
                image.alt = 'Advertisement image';
                image.className = 'advertisement-thumbnail';
                imageLink.appendChild(image);
                element.appendChild(imageLink);
                return;
            }

            if (field === 'redirect_url' && value) {
                var link = document.createElement('a');
                link.href = value;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.textContent = value;
                element.appendChild(link);
                return;
            }

            element.textContent = displayValue;
        }

        function setAdvertisementView(data) {
            [
                'id',
                'player_id',
                'short_text',
                'main_image',
                'redirect_url',
                'start_date',
                'end_date',
                'amount',
                'payment_status',
                'status',
                'priority',
                'impressions_count',
                'click_count',
                'created_at',
                'updated_at'
            ].forEach(function (field) {
                setViewValue(field, data[field] || '');
            });
        }

        if (openAddButton) {
            openAddButton.addEventListener('click', function () {
                setAdvertisementModal(getEmptyAdvertisement());
                openModal();
            });
        }

        document.querySelectorAll('.edit-advertisement').forEach(function (button) {
            button.addEventListener('click', function () {
                setAdvertisementModal(button.dataset);
                openModal();
            });
        });

        document.querySelectorAll('.view-advertisement').forEach(function (button) {
            button.addEventListener('click', function () {
                setAdvertisementView(button.dataset);
                openViewModal();
            });
        });

        if (closeTopButton) {
            closeTopButton.addEventListener('click', function () {
                closeModal();
                setAdvertisementModal(getEmptyAdvertisement());
            });
        }

        if (closeBottomButton) {
            closeBottomButton.addEventListener('click', function () {
                closeModal();
                setAdvertisementModal(getEmptyAdvertisement());
            });
        }

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
                setAdvertisementModal(getEmptyAdvertisement());
            }
        });

        if (closeViewTopButton) {
            closeViewTopButton.addEventListener('click', closeViewModal);
        }

        if (closeViewBottomButton) {
            closeViewBottomButton.addEventListener('click', closeViewModal);
        }

        viewModal.addEventListener('click', function (event) {
            if (event.target === viewModal) {
                closeViewModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal();
                setAdvertisementModal(getEmptyAdvertisement());
            }

            if (event.key === 'Escape' && viewModal.classList.contains('is-open')) {
                closeViewModal();
            }
        });

        <?php if ($showAdvertisementModal) { ?>
        openModal();
        <?php } ?>
    });
</script>

<?php
include('footer.php');
?>
