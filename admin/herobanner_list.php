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

function herobanner_page_url($page, $limit, $search)
{
    $params = [
        'page' => $page,
        'limit' => $limit,
    ];

    if ($search !== '') {
        $params['search'] = $search;
    }

    return 'herobanner_list.php?' . http_build_query($params);
}

$message = '';
$messageType = 'success';
$search = trim($_GET['search'] ?? '');
$limit = (int) ($_GET['limit'] ?? 25);
$page = max(1, (int) ($_GET['page'] ?? 1));
$allowedLimits = [10, 25, 50, 100];
$showHeroBannerModal = false;

if (!in_array($limit, $allowedLimits, true)) {
    $limit = 25;
}

$formData = [
    'id' => '',
    'sub_heading' => '',
    'heading' => '',
    'highlight_text' => '',
    'description1' => '',
    'description2' => '',
    'image' => '',
    'status' => '1',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $recordId = (int) ($_POST['id'] ?? 0);

        $formData = [
            'id' => $recordId,
            'sub_heading' => trim($_POST['sub_heading'] ?? ''),
            'heading' => trim($_POST['heading'] ?? ''),
            'highlight_text' => trim($_POST['highlight_text'] ?? ''),
            'description1' => trim($_POST['description1'] ?? ''),
            'description2' => trim($_POST['description2'] ?? ''),
            'image' => trim($_POST['image'] ?? ''),
            'status' => trim($_POST['status'] ?? '1'),
        ];

        if ($formData['sub_heading'] === '' || $formData['heading'] === '' || $formData['description1'] === '' || $formData['image'] === '') {
            $message = 'Sub heading, heading, description 1, and image are required.';
            $messageType = 'danger';
            $showHeroBannerModal = true;
        } else {
            $subHeading = normalize_value($formData['sub_heading']);
            $heading = normalize_value($formData['heading']);
            $highlightText = normalize_value($formData['highlight_text']);
            $description1 = normalize_value($formData['description1']);
            $description2 = normalize_value($formData['description2']);
            $image = normalize_value($formData['image']);
            $status = $formData['status'] === '' ? 0 : (int) $formData['status'];

            if ($recordId > 0) {
                $stmt = $conn->prepare("UPDATE ca_herobanners SET sub_heading = ?, heading = ?, highlight_text = ?, description1 = ?, description2 = ?, image = ?, status = ? WHERE id = ?");
                $stmt->bind_param(
                    'ssssssii',
                    $subHeading,
                    $heading,
                    $highlightText,
                    $description1,
                    $description2,
                    $image,
                    $status,
                    $recordId
                );

                if ($stmt->execute()) {
                    $stmt->close();
                    echo "<script>alert('Hero banner updated successfully.'); window.location.href='herobanner_list.php';</script>";
                    exit;
                }

                $message = 'Failed to update hero banner: ' . $stmt->error;
                $messageType = 'danger';
                $showHeroBannerModal = true;
                $stmt->close();
            } else {
                $stmt = $conn->prepare("INSERT INTO ca_herobanners (sub_heading, heading, highlight_text, description1, description2, image, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param(
                    'ssssssi',
                    $subHeading,
                    $heading,
                    $highlightText,
                    $description1,
                    $description2,
                    $image,
                    $status
                );

                if ($stmt->execute()) {
                    $stmt->close();
                    echo "<script>alert('Hero banner added successfully.'); window.location.href='herobanner_list.php';</script>";
                    exit;
                }

                $message = 'Failed to add hero banner: ' . $stmt->error;
                $messageType = 'danger';
                $showHeroBannerModal = true;
                $stmt->close();
            }
        }
    } elseif ($action === 'delete') {
        $deleteId = (int) ($_POST['delete_id'] ?? 0);

        if ($deleteId > 0) {
            $stmt = $conn->prepare("DELETE FROM ca_herobanners WHERE id = ?");
            $stmt->bind_param('i', $deleteId);

            if ($stmt->execute()) {
                $stmt->close();
                echo "<script>alert('Hero banner deleted successfully.'); window.location.href='herobanner_list.php';</script>";
                exit;
            }

            $message = 'Failed to delete hero banner: ' . $stmt->error;
            $messageType = 'danger';
            $stmt->close();
        }
    }
}

$heroBanners = [];
$totalItems = 0;
$totalPages = 1;
$offset = 0;

if ($search !== '') {
    $likeSearch = '%' . $search . '%';
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM ca_herobanners WHERE CAST(id AS CHAR) LIKE ? OR sub_heading LIKE ? OR heading LIKE ? OR highlight_text LIKE ? OR description1 LIKE ? OR description2 LIKE ? OR image LIKE ? OR CAST(status AS CHAR) LIKE ? OR CAST(created_at AS CHAR) LIKE ?");
    $countStmt->bind_param(
        'sssssssss',
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

    $stmt = $conn->prepare("SELECT * FROM ca_herobanners WHERE CAST(id AS CHAR) LIKE ? OR sub_heading LIKE ? OR heading LIKE ? OR highlight_text LIKE ? OR description1 LIKE ? OR description2 LIKE ? OR image LIKE ? OR CAST(status AS CHAR) LIKE ? OR CAST(created_at AS CHAR) LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param(
        'sssssssssii',
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
    $countResult = $conn->query("SELECT COUNT(*) AS total FROM ca_herobanners");
    $totalItems = (int) ($countResult->fetch_assoc()['total'] ?? 0);

    $totalPages = max(1, (int) ceil($totalItems / $limit));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $limit;

    $stmt = $conn->prepare("SELECT * FROM ca_herobanners ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $heroBanners[] = $row;
    }
}

if (isset($stmt)) {
    $stmt->close();
}

$startItem = $totalItems > 0 ? $offset + 1 : 0;
$endItem = min($offset + count($heroBanners), $totalItems);
?>

<section role="main" class="content-body">
    <header class="page-header">
        <h2>Manage Hero Banners</h2>

        <!-- <div class="right-wrapper pull-right">
            <ol class="breadcrumbs">
                <li>
                    <a href="index.php">
                        <i class="fa fa-home"></i>
                    </a>
                </li>
                <li><span>Hero Banner</span></li>
                <li><span>List</span></li>
            </ol>

            <a class="sidebar-right-toggle" data-open="sidebar-right"><i class="fa fa-chevron-left"></i></a>
        </div> -->
    </header>

    <section class="panel">
        <!-- <header class="panel-heading">
            <h2 class="panel-title">All Hero Banners</h2>
        </header> -->
        <div class="panel-body">
            <?php if ($message !== '') { ?>
                <div class="alert alert-<?php echo h($messageType); ?>">
                    <?php echo h($message); ?>
                </div>
            <?php } ?>

            <form method="GET" class="herobanner-toolbar">
                <button type="button" class="btn btn-success" id="open-add-herobanner">
                    <i class="fa fa-plus"></i> Add
                </button>

                <div class="herobanner-toolbar-controls">
                    <div class="herobanner-limit-control">
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

                    <div class="input-group herobanner-search-control">
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo h($search); ?>" placeholder="Search">
                        <span class="input-group-btn">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-search"></i>
                            </button>
                            <?php if ($search !== '') { ?>
                                <a href="herobanner_list.php?limit=<?php echo (int) $limit; ?>" class="btn btn-default">Reset</a>
                            <?php } ?>
                        </span>
                    </div>
                </div>
            </form>

            <div style="overflow-x: auto;">
                <table class="table table-bordered table-striped table-hover" id="herobanners-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Sub Heading</th>
                            <th>Heading</th>
                            <!-- <th>Highlight Text</th> -->
                            <!-- <th>Description 1</th> -->
                            <!-- <th>Description 2</th> -->
                            <th>Image</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($heroBanners)) { ?>
                            <?php foreach ($heroBanners as $row) { ?>
                                <tr>
                                    <td><?php echo h($row['id']); ?></td>
                                    <td><?php echo h($row['sub_heading']); ?></td>
                                    <td><?php echo h($row['heading']); ?></td>
                                    <!-- <td><//?php echo h($row['highlight_text']); ?></td> -->
                                    <!-- <td><//?php echo h($row['description1']); ?></td> -->
                                    <!-- <td><//?php echo h($row['description2']); ?></td> -->
                                    <td style="min-width: 120px;">
                                        <?php if (!empty($row['image'])) { ?>
                                            <div style="margin-bottom: 8px;">
                                                <img src="<?php echo h($row['image']); ?>" alt="Hero Banner" style="max-width: 120px; max-height: 70px; border-radius: 4px;">
                                            </div>
                                            <a href="<?php echo h($row['image']); ?>" target="_blank"><?php echo h($row['image']); ?></a>
                                        <?php } else { ?>
                                            --
                                        <?php } ?>
                                    </td>
                                    <td><?php echo (int) $row['status'] === 1 ? 'Active' : 'Inactive'; ?></td>
                                    <td><?php echo h($row['created_at']); ?></td>
                                    <td style="white-space: nowrap;">
                                        <button
                                            type="button"
                                            class="btn btn-primary btn-xs view-herobanner"
                                            data-id="<?php echo h($row['id']); ?>"
                                            data-sub_heading="<?php echo h($row['sub_heading']); ?>"
                                            data-heading="<?php echo h($row['heading']); ?>"
                                            data-highlight_text="<?php echo h($row['highlight_text']); ?>"
                                            data-description1="<?php echo h($row['description1']); ?>"
                                            data-description2="<?php echo h($row['description2']); ?>"
                                            data-image="<?php echo h($row['image']); ?>"
                                            data-status="<?php echo h($row['status']); ?>"
                                            data-created_at="<?php echo h($row['created_at']); ?>"
                                        >
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-info btn-xs edit-herobanner"
                                            data-id="<?php echo h($row['id']); ?>"
                                            data-sub_heading="<?php echo h($row['sub_heading']); ?>"
                                            data-heading="<?php echo h($row['heading']); ?>"
                                            data-highlight_text="<?php echo h($row['highlight_text']); ?>"
                                            data-description1="<?php echo h($row['description1']); ?>"
                                            data-description2="<?php echo h($row['description2']); ?>"
                                            data-image="<?php echo h($row['image']); ?>"
                                            data-status="<?php echo h($row['status']); ?>"
                                        >
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this hero banner?');">
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
                                <td colspan="10" class="text-center">No hero banners found.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <div class="herobanner-pagination-row">
                <div class="herobanner-pagination-info">
                    Showing <?php echo (int) $startItem; ?> to <?php echo (int) $endItem; ?> of <?php echo (int) $totalItems; ?> entries
                </div>

                <?php if ($totalPages > 1) { ?>
                    <ul class="pagination herobanner-pagination">
                        <li class="<?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <?php if ($page <= 1) { ?>
                                <span>Previous</span>
                            <?php } else { ?>
                                <a href="<?php echo h(herobanner_page_url($page - 1, $limit, $search)); ?>">Previous</a>
                            <?php } ?>
                        </li>

                        <?php
                        $firstPage = max(1, $page - 2);
                        $lastPage = min($totalPages, $page + 2);

                        if ($firstPage > 1) {
                        ?>
                            <li><a href="<?php echo h(herobanner_page_url(1, $limit, $search)); ?>">1</a></li>
                            <?php if ($firstPage > 2) { ?>
                                <li class="disabled"><span>...</span></li>
                            <?php } ?>
                        <?php } ?>

                        <?php for ($pageNumber = $firstPage; $pageNumber <= $lastPage; $pageNumber++) { ?>
                            <li class="<?php echo $pageNumber === $page ? 'active' : ''; ?>">
                                <?php if ($pageNumber === $page) { ?>
                                    <span><?php echo (int) $pageNumber; ?></span>
                                <?php } else { ?>
                                    <a href="<?php echo h(herobanner_page_url($pageNumber, $limit, $search)); ?>"><?php echo (int) $pageNumber; ?></a>
                                <?php } ?>
                            </li>
                        <?php } ?>

                        <?php if ($lastPage < $totalPages) { ?>
                            <?php if ($lastPage < $totalPages - 1) { ?>
                                <li class="disabled"><span>...</span></li>
                            <?php } ?>
                            <li><a href="<?php echo h(herobanner_page_url($totalPages, $limit, $search)); ?>"><?php echo (int) $totalPages; ?></a></li>
                        <?php } ?>

                        <li class="<?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <?php if ($page >= $totalPages) { ?>
                                <span>Next</span>
                            <?php } else { ?>
                                <a href="<?php echo h(herobanner_page_url($page + 1, $limit, $search)); ?>">Next</a>
                            <?php } ?>
                        </li>
                    </ul>
                <?php } ?>
            </div>
        </div>
    </section>
</section>

<style>
    .herobanner-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 15px;
    }

    .herobanner-toolbar-controls,
    .herobanner-limit-control {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .herobanner-limit-control .form-control {
        width: 82px;
    }

    .herobanner-search-control {
        width: 320px;
    }

    .herobanner-pagination-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        margin-top: 15px;
    }

    .herobanner-pagination-info {
        color: #777;
    }

    .herobanner-pagination {
        margin: 0;
    }

    @media (max-width: 767px) {
        .herobanner-toolbar,
        .herobanner-toolbar-controls,
        .herobanner-pagination-row {
            align-items: stretch;
            flex-direction: column;
        }

        .herobanner-search-control {
            width: 100%;
        }
    }

    .herobanner-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        z-index: 1050;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .herobanner-modal-overlay.is-open {
        display: flex;
    }

    .herobanner-modal {
        background: #fff;
        border-radius: 6px;
        width: 100%;
        max-width: 900px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
    }

    .herobanner-modal-header,
    .herobanner-modal-footer {
        padding: 15px 20px;
        border-bottom: 1px solid #e5e5e5;
    }

    .herobanner-modal-footer {
        border-bottom: 0;
        border-top: 1px solid #e5e5e5;
        text-align: right;
    }

    .herobanner-modal-body {
        padding: 20px;
    }

    .herobanner-modal-close {
        float: right;
        font-size: 26px;
        line-height: 1;
        border: 0;
        background: transparent;
        cursor: pointer;
        color: #555;
    }

    .herobanner-image-preview {
        max-width: 180px;
        max-height: 100px;
        display: none;
        margin-top: 10px;
        border-radius: 4px;
    }

    body.herobanner-modal-open {
        overflow: hidden;
    }

    .herobanner-view-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px 18px;
    }

    .herobanner-view-item {
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        min-width: 0;
    }

    .herobanner-view-item strong {
        display: block;
        color: #333;
        margin-bottom: 4px;
    }

    .herobanner-view-value {
        overflow-wrap: anywhere;
    }

    .herobanner-view-image {
        max-width: 180px;
        max-height: 100px;
        border-radius: 4px;
        border: 1px solid #ddd;
        display: block;
    }

    @media (max-width: 767px) {
        .herobanner-view-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="herobanner-modal-overlay" id="herobannerModal" aria-labelledby="herobannerModalLabel">
    <div class="herobanner-modal" role="dialog" aria-modal="true">
        <div class="modal-content">
            <form method="POST">
                <div class="herobanner-modal-header">
                    <button type="button" class="herobanner-modal-close" id="closeHeroBannerModalTop" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="herobannerModalLabel"><?php echo $formData['id'] ? 'Edit Hero Banner' : 'Add Hero Banner'; ?></h4>
                </div>
                <div class="herobanner-modal-body">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?php echo h($formData['id']); ?>">

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="modal_sub_heading">Sub Heading <span>*</span></label>
                            <input type="text" class="form-control" id="modal_sub_heading" name="sub_heading" value="<?php echo h($formData['sub_heading']); ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="modal_heading">Heading <span>*</span></label>
                            <input type="text" class="form-control" id="modal_heading" name="heading" value="<?php echo h($formData['heading']); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label for="modal_highlight_text">Highlight Text</label>
                            <input type="text" class="form-control" id="modal_highlight_text" name="highlight_text" value="<?php echo h($formData['highlight_text']); ?>">
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="modal_status">Status</label>
                            <select class="form-control" id="modal_status" name="status">
                                <option value="1" <?php echo $formData['status'] === '1' || $formData['status'] === 1 ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo $formData['status'] === '0' || $formData['status'] === 0 ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 form-group">
                            <label for="modal_description1">Description 1 <span>*</span></label>
                            <textarea class="form-control" id="modal_description1" name="description1" rows="3" required><?php echo h($formData['description1']); ?></textarea>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 form-group">
                            <label for="modal_description2">Description 2</label>
                            <textarea class="form-control" id="modal_description2" name="description2" rows="3"><?php echo h($formData['description2']); ?></textarea>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 form-group">
                            <label for="modal_image">Image Path / URL <span>*</span></label>
                            <input type="text" class="form-control" id="modal_image" name="image" value="<?php echo h($formData['image']); ?>" placeholder="assets/images/banner.jpg" required>
                            <img src="<?php echo h($formData['image']); ?>" alt="Preview" id="modal_image_preview" class="herobanner-image-preview"<?php echo $formData['image'] !== '' ? ' style="display:block; margin-top:10px;"' : ''; ?>>
                        </div>
                    </div>
                </div>
                <div class="herobanner-modal-footer">
                    <button type="button" class="btn btn-default" id="closeHeroBannerModalBottom">Close</button>
                    <button type="submit" class="btn btn-primary"><?php echo $formData['id'] ? 'Update Hero Banner' : 'Add Hero Banner'; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="herobanner-modal-overlay" id="herobannerViewModal" aria-labelledby="herobannerViewModalLabel">
    <div class="herobanner-modal" role="dialog" aria-modal="true">
        <div class="modal-content">
            <div class="herobanner-modal-header">
                <button type="button" class="herobanner-modal-close" id="closeHeroBannerViewModalTop" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="herobannerViewModalLabel">View Hero Banner</h4>
            </div>
            <div class="herobanner-modal-body">
                <div class="herobanner-view-grid">
                    <div class="herobanner-view-item">
                        <strong>ID</strong>
                        <div class="herobanner-view-value" data-view-field="id"></div>
                    </div>
                    <div class="herobanner-view-item">
                        <strong>Sub Heading</strong>
                        <div class="herobanner-view-value" data-view-field="sub_heading"></div>
                    </div>
                    <div class="herobanner-view-item">
                        <strong>Heading</strong>
                        <div class="herobanner-view-value" data-view-field="heading"></div>
                    </div>
                    <div class="herobanner-view-item">
                        <strong>Highlight Text</strong>
                        <div class="herobanner-view-value" data-view-field="highlight_text"></div>
                    </div>
                    <div class="herobanner-view-item">
                        <strong>Description 1</strong>
                        <div class="herobanner-view-value" data-view-field="description1"></div>
                    </div>
                    <div class="herobanner-view-item">
                        <strong>Description 2</strong>
                        <div class="herobanner-view-value" data-view-field="description2"></div>
                    </div>
                    <div class="herobanner-view-item">
                        <strong>Image</strong>
                        <div class="herobanner-view-value" data-view-field="image"></div>
                    </div>
                    <div class="herobanner-view-item">
                        <strong>Status</strong>
                        <div class="herobanner-view-value" data-view-field="status"></div>
                    </div>
                    <div class="herobanner-view-item">
                        <strong>Created At</strong>
                        <div class="herobanner-view-value" data-view-field="created_at"></div>
                    </div>
                </div>
            </div>
            <div class="herobanner-modal-footer">
                <button type="button" class="btn btn-default" id="closeHeroBannerViewModalBottom">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('load', function () {
        var $ = window.jQuery;
        var modal = document.getElementById('herobannerModal');
        var viewModal = document.getElementById('herobannerViewModal');
        var body = document.body;
        var openAddButton = document.getElementById('open-add-herobanner');
        var closeTopButton = document.getElementById('closeHeroBannerModalTop');
        var closeBottomButton = document.getElementById('closeHeroBannerModalBottom');
        var closeViewTopButton = document.getElementById('closeHeroBannerViewModalTop');
        var closeViewBottomButton = document.getElementById('closeHeroBannerViewModalBottom');
        var modalTitle = document.getElementById('herobannerModalLabel');
        var modalIdInput = document.querySelector('#herobannerModal input[name="id"]');
        var subHeadingInput = document.getElementById('modal_sub_heading');
        var headingInput = document.getElementById('modal_heading');
        var highlightTextInput = document.getElementById('modal_highlight_text');
        var description1Input = document.getElementById('modal_description1');
        var description2Input = document.getElementById('modal_description2');
        var imageInput = document.getElementById('modal_image');
        var imagePreview = document.getElementById('modal_image_preview');
        var statusInput = document.getElementById('modal_status');
        var submitButton = document.querySelector('#herobannerModal .btn-primary');
        var limitSelect = document.getElementById('limit');

        if (limitSelect) {
            limitSelect.addEventListener('change', function () {
                limitSelect.form.submit();
            });
        }

        function openModal() {
            modal.classList.add('is-open');
            body.classList.add('herobanner-modal-open');
        }

        function closeModal() {
            modal.classList.remove('is-open');
            body.classList.remove('herobanner-modal-open');
        }

        function openViewModal() {
            viewModal.classList.add('is-open');
            body.classList.add('herobanner-modal-open');
        }

        function closeViewModal() {
            viewModal.classList.remove('is-open');
            body.classList.remove('herobanner-modal-open');
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

        function setHeroBannerModal(data) {
            modalTitle.textContent = data.id ? 'Edit Hero Banner' : 'Add Hero Banner';
            setFieldValue(modalIdInput, data.id || '');
            setFieldValue(subHeadingInput, data.sub_heading || '');
            setFieldValue(headingInput, data.heading || '');
            setFieldValue(highlightTextInput, data.highlight_text || '');
            setFieldValue(description1Input, data.description1 || '');
            setFieldValue(description2Input, data.description2 || '');
            setFieldValue(imageInput, data.image || '');
            setFieldValue(statusInput, data.status !== undefined ? data.status : '1');
            updateImagePreview(data.image || '');
            submitButton.textContent = data.id ? 'Update Hero Banner' : 'Add Hero Banner';
        }

        function getEmptyHeroBanner() {
            return {
                id: '',
                sub_heading: '',
                heading: '',
                highlight_text: '',
                description1: '',
                description2: '',
                image: '',
                status: '1'
            };
        }

        function setViewValue(field, value) {
            var element = viewModal.querySelector('[data-view-field="' + field + '"]');
            var displayValue = value || '-';

            if (!element) {
                return;
            }

            element.textContent = '';

            if (field === 'image' && value) {
                var imageLink = document.createElement('a');
                var image = document.createElement('img');
                imageLink.href = value;
                imageLink.target = '_blank';
                imageLink.rel = 'noopener noreferrer';
                image.src = value;
                image.alt = 'Hero Banner';
                image.className = 'herobanner-view-image';
                imageLink.appendChild(image);
                element.appendChild(imageLink);
                return;
            }

            if (field === 'status') {
                element.textContent = String(value) === '1' ? 'Active' : 'Inactive';
                return;
            }

            element.textContent = displayValue;
        }

        function setHeroBannerView(data) {
            [
                'id',
                'sub_heading',
                'heading',
                'highlight_text',
                'description1',
                'description2',
                'image',
                'status',
                'created_at'
            ].forEach(function (field) {
                setViewValue(field, data[field] || '');
            });
        }

        if (openAddButton) {
            openAddButton.addEventListener('click', function () {
                setHeroBannerModal(getEmptyHeroBanner());
                openModal();
            });
        }

        document.querySelectorAll('.edit-herobanner').forEach(function (button) {
            button.addEventListener('click', function () {
                setHeroBannerModal(button.dataset);
                openModal();
            });
        });

        document.querySelectorAll('.view-herobanner').forEach(function (button) {
            button.addEventListener('click', function () {
                setHeroBannerView(button.dataset);
                openViewModal();
            });
        });

        if (imageInput) {
            imageInput.addEventListener('input', function () {
                updateImagePreview(imageInput.value.trim());
            });
        }

        if (closeTopButton) {
            closeTopButton.addEventListener('click', function () {
                closeModal();
                setHeroBannerModal(getEmptyHeroBanner());
            });
        }

        if (closeBottomButton) {
            closeBottomButton.addEventListener('click', function () {
                closeModal();
                setHeroBannerModal(getEmptyHeroBanner());
            });
        }

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
                setHeroBannerModal(getEmptyHeroBanner());
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
                setHeroBannerModal(getEmptyHeroBanner());
            }

            if (event.key === 'Escape' && viewModal.classList.contains('is-open')) {
                closeViewModal();
            }
        });

        <?php if ($showHeroBannerModal) { ?>
        openModal();
        <?php } ?>
    });
</script>

<?php
include('footer.php');
?>
