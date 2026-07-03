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

function media_page_url($page, $limit, $search, $mediaTypeFilter)
{
    $params = [
        'page' => $page,
        'limit' => $limit,
    ];

    if ($search !== '') {
        $params['search'] = $search;
    }

    if ($mediaTypeFilter !== '') {
        $params['media_type'] = $mediaTypeFilter;
    }

    return 'media_list.php?' . http_build_query($params);
}

$message = '';
$messageType = 'success';
$search = trim($_GET['search'] ?? '');
$mediaTypeFilter = trim($_GET['media_type'] ?? '');
$limit = (int) ($_GET['limit'] ?? 25);
$page = max(1, (int) ($_GET['page'] ?? 1));
$allowedLimits = [10, 25, 50, 100];
$mediaTypeOptions = ['image', 'video', 'poster'];
$showMediaModal = false;

if (!in_array($limit, $allowedLimits, true)) {
    $limit = 25;
}

if (!in_array($mediaTypeFilter, $mediaTypeOptions, true)) {
    $mediaTypeFilter = '';
}

$formData = [
    'id' => '',
    'host_id' => '',
    'media_type' => 'image',
    'title' => '',
    'media_url' => '',
    'thumbnail_url' => '',
    'description' => '',
    'sort_order' => '0',
    'is_active' => '1',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $recordId = (int) ($_POST['id'] ?? 0);

        $formData = [
            'id' => $recordId,
            'host_id' => trim($_POST['host_id'] ?? ''),
            'media_type' => trim($_POST['media_type'] ?? 'image'),
            'title' => trim($_POST['title'] ?? ''),
            'media_url' => trim($_POST['media_url'] ?? ''),
            'thumbnail_url' => trim($_POST['thumbnail_url'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'sort_order' => trim($_POST['sort_order'] ?? '0'),
            'is_active' => trim($_POST['is_active'] ?? '1'),
        ];

        if ($formData['media_type'] === '' || $formData['title'] === '' || $formData['media_url'] === '') {
            $message = 'Media type, title, and media URL are required.';
            $messageType = 'danger';
            $showMediaModal = true;
        } else {
            $hostId = $formData['host_id'] === '' ? null : (int) $formData['host_id'];
            $mediaType = normalize_value($formData['media_type']);
            $title = normalize_value($formData['title']);
            $mediaUrl = normalize_value($formData['media_url']);
            $thumbnailUrl = normalize_value($formData['thumbnail_url']);
            $description = normalize_value($formData['description']);
            $sortOrder = $formData['sort_order'] === '' ? 0 : (int) $formData['sort_order'];
            $isActive = $formData['is_active'] === '' ? 0 : (int) $formData['is_active'];

            if ($recordId > 0) {
                $stmt = $conn->prepare("UPDATE ca_landing_page_media SET host_id = ?, media_type = ?, title = ?, media_url = ?, thumbnail_url = ?, description = ?, sort_order = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param(
                    'isssssiii',
                    $hostId,
                    $mediaType,
                    $title,
                    $mediaUrl,
                    $thumbnailUrl,
                    $description,
                    $sortOrder,
                    $isActive,
                    $recordId
                );

                if ($stmt->execute()) {
                    $stmt->close();
                    echo "<script>alert('Media updated successfully.'); window.location.href='media_list.php';</script>";
                    exit;
                }

                $message = 'Failed to update media: ' . $stmt->error;
                $messageType = 'danger';
                $showMediaModal = true;
                $stmt->close();
            } else {
                $stmt = $conn->prepare("INSERT INTO ca_landing_page_media (host_id, media_type, title, media_url, thumbnail_url, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param(
                    'isssssii',
                    $hostId,
                    $mediaType,
                    $title,
                    $mediaUrl,
                    $thumbnailUrl,
                    $description,
                    $sortOrder,
                    $isActive
                );

                if ($stmt->execute()) {
                    $stmt->close();
                    echo "<script>alert('Media added successfully.'); window.location.href='media_list.php';</script>";
                    exit;
                }

                $message = 'Failed to add media: ' . $stmt->error;
                $messageType = 'danger';
                $showMediaModal = true;
                $stmt->close();
            }
        }
    } elseif ($action === 'delete') {
        $deleteId = (int) ($_POST['delete_id'] ?? 0);

        if ($deleteId > 0) {
            $stmt = $conn->prepare("DELETE FROM ca_landing_page_media WHERE id = ?");
            $stmt->bind_param('i', $deleteId);

            if ($stmt->execute()) {
                $stmt->close();
                echo "<script>alert('Media deleted successfully.'); window.location.href='media_list.php';</script>";
                exit;
            }

            $message = 'Failed to delete media: ' . $stmt->error;
            $messageType = 'danger';
            $stmt->close();
        }
    }
}

$mediaItems = [];
$totalItems = 0;
$totalPages = 1;
$offset = 0;

if ($search !== '' && $mediaTypeFilter !== '') {
    $likeSearch = '%' . $search . '%';
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM ca_landing_page_media WHERE media_type = ? AND (CAST(id AS CHAR) LIKE ? OR CAST(host_id AS CHAR) LIKE ? OR title LIKE ? OR media_url LIKE ? OR thumbnail_url LIKE ? OR description LIKE ? OR CAST(sort_order AS CHAR) LIKE ? OR CAST(is_active AS CHAR) LIKE ? OR CAST(created_at AS CHAR) LIKE ?)");
    $countStmt->bind_param(
        'ssssssssss',
        $mediaTypeFilter,
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

    $stmt = $conn->prepare("SELECT * FROM ca_landing_page_media WHERE media_type = ? AND (CAST(id AS CHAR) LIKE ? OR CAST(host_id AS CHAR) LIKE ? OR title LIKE ? OR media_url LIKE ? OR thumbnail_url LIKE ? OR description LIKE ? OR CAST(sort_order AS CHAR) LIKE ? OR CAST(is_active AS CHAR) LIKE ? OR CAST(created_at AS CHAR) LIKE ?) ORDER BY sort_order ASC, id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param(
        'ssssssssssii',
        $mediaTypeFilter,
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
} elseif ($search !== '') {
    $likeSearch = '%' . $search . '%';
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM ca_landing_page_media WHERE CAST(id AS CHAR) LIKE ? OR CAST(host_id AS CHAR) LIKE ? OR media_type LIKE ? OR title LIKE ? OR media_url LIKE ? OR thumbnail_url LIKE ? OR description LIKE ? OR CAST(sort_order AS CHAR) LIKE ? OR CAST(is_active AS CHAR) LIKE ? OR CAST(created_at AS CHAR) LIKE ?");
    $countStmt->bind_param(
        'ssssssssss',
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

    $stmt = $conn->prepare("SELECT * FROM ca_landing_page_media WHERE CAST(id AS CHAR) LIKE ? OR CAST(host_id AS CHAR) LIKE ? OR media_type LIKE ? OR title LIKE ? OR media_url LIKE ? OR thumbnail_url LIKE ? OR description LIKE ? OR CAST(sort_order AS CHAR) LIKE ? OR CAST(is_active AS CHAR) LIKE ? OR CAST(created_at AS CHAR) LIKE ? ORDER BY sort_order ASC, id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param(
        'ssssssssssii',
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
} elseif ($mediaTypeFilter !== '') {
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM ca_landing_page_media WHERE media_type = ?");
    $countStmt->bind_param('s', $mediaTypeFilter);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalItems = (int) ($countResult->fetch_assoc()['total'] ?? 0);
    $countStmt->close();

    $totalPages = max(1, (int) ceil($totalItems / $limit));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $limit;

    $stmt = $conn->prepare("SELECT * FROM ca_landing_page_media WHERE media_type = ? ORDER BY sort_order ASC, id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('sii', $mediaTypeFilter, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $countResult = $conn->query("SELECT COUNT(*) AS total FROM ca_landing_page_media");
    $totalItems = (int) ($countResult->fetch_assoc()['total'] ?? 0);

    $totalPages = max(1, (int) ceil($totalItems / $limit));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $limit;

    $stmt = $conn->prepare("SELECT * FROM ca_landing_page_media ORDER BY sort_order ASC, id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $mediaItems[] = $row;
    }
}

if (isset($stmt)) {
    $stmt->close();
}

$startItem = $totalItems > 0 ? $offset + 1 : 0;
$endItem = min($offset + count($mediaItems), $totalItems);
?>

<section role="main" class="content-body">
    <header class="page-header">
        <h2>Media Library</h2>

        <!-- <div class="right-wrapper pull-right">
            <ol class="breadcrumbs">
                <li>
                    <a href="index.php">
                        <i class="fa fa-home"></i>
                    </a>
                </li>
                <li><span>Media</span></li>
                <li><span>List</span></li>
            </ol>

            <a class="sidebar-right-toggle" data-open="sidebar-right"><i class="fa fa-chevron-left"></i></a>
        </div> -->
    </header>

    <section class="panel">
        <!-- <header class="panel-heading">
            <h2 class="panel-title">All Landing Page Media</h2>
        </header> -->

        <div class="panel-body">
            <?php if ($message !== '') { ?>
                <div class="alert alert-<?php echo h($messageType); ?>">
                    <?php echo h($message); ?>
                </div>
            <?php } ?>

            <form method="GET" class="media-toolbar">
                <div style="display: flex; align-items: center; gap:5px;">
                <button type="button" class="btn btn-success" id="open-add-media">
                    <i class="fa fa-plus"></i> Add
                </button>

                <div class="media-type-tabs" role="tablist" aria-label="Media Type">
                        <a
                            href="media_list.php?<?php echo h(http_build_query(['limit' => $limit, 'search' => $search])); ?>"
                            class="btn btn-sm <?php echo $mediaTypeFilter === '' ? 'btn-primary' : 'btn-default'; ?>"
                            role="tab"
                            aria-selected="<?php echo $mediaTypeFilter === '' ? 'true' : 'false'; ?>"
                        >All</a>
                        <?php foreach ($mediaTypeOptions as $mediaTypeOption) { ?>
                            <a
                                href="media_list.php?<?php echo h(http_build_query(['limit' => $limit, 'search' => $search, 'media_type' => $mediaTypeOption])); ?>"
                                class="btn btn-sm <?php echo $mediaTypeFilter === $mediaTypeOption ? 'btn-primary' : 'btn-default'; ?>"
                                role="tab"
                                aria-selected="<?php echo $mediaTypeFilter === $mediaTypeOption ? 'true' : 'false'; ?>"
                            ><?php echo h(ucfirst($mediaTypeOption)); ?></a>
                        <?php } ?>
                    </div>

                </div>

                <div class="media-toolbar-controls">
                    <div class="media-limit-control">
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

                    <div class="input-group media-search-control">
                        <input type="hidden" name="media_type" value="<?php echo h($mediaTypeFilter); ?>">
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo h($search); ?>" placeholder="Search">
                        <span class="input-group-btn">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-search"></i>
                            </button>
                            <?php if ($search !== '' || $mediaTypeFilter !== '') { ?>
                                <a href="media_list.php?limit=<?php echo (int) $limit; ?>" class="btn btn-default">Reset</a>
                            <?php } ?>
                        </span>
                    </div>
                </div>
            </form>

            <div style="overflow-x: auto;">
                <table class="table table-bordered table-striped table-hover" id="media-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Host ID</th>
                            <th>Media Type</th>
                            <th>Title</th>
                            <th>Preview</th>
                            <!-- <th>Media URL</th> -->
                            <!-- <th>Thumbnail URL</th> -->
                            <!-- <th>Description</th> -->
                            <th>Sort Order</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($mediaItems)) { ?>
                            <?php foreach ($mediaItems as $row) { ?>
                                <tr>
                                    <td><?php echo h($row['id']); ?></td>
                                    <td><?php echo h($row['host_id']); ?></td>
                                    <td>
                                        <span class="label label-info"><?php echo h(strtoupper($row['media_type'])); ?></span>
                                    </td>
                                    <td><?php echo h($row['title']); ?></td>
                                    <td style="min-width: 140px;">
                                        <?php if ($row['media_type'] === 'video') { ?>
                                            <?php if (!empty($row['thumbnail_url'])) { ?>
                                                <a href="<?php echo h($row['media_url']); ?>" target="_blank">
                                                    <img src="<?php echo h($row['thumbnail_url']); ?>" alt="Video Thumbnail" style="max-width: 120px; max-height: 80px; border-radius: 4px;">
                                                </a>
                                            <?php } else { ?>
                                                <a href="<?php echo h($row['media_url']); ?>" target="_blank" class="btn btn-xs btn-primary">View Video</a>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <?php if (!empty($row['media_url'])) { ?>
                                                <a href="<?php echo h($row['media_url']); ?>" target="_blank">
                                                    <img src="<?php echo h($row['media_url']); ?>" alt="Media Preview" style="max-width: 120px; max-height: 80px; border-radius: 4px;">
                                                </a>
                                            <?php } else { ?>
                                                -
                                            <?php } ?>
                                        <?php } ?>
                                    </td>
                                    <!-- <td>
                                        <//?php if (!empty($row['media_url'])) { ?>
                                            <a href="<//?php echo h($row['media_url']); ?>" target="_blank"><//?php echo h($row['media_url']); ?></a>
                                        <//?php } else { ?>
                                            ---
                                        <//?php } ?>
                                    </td> -->
                                    <!-- <td>
                                        <//?php if (!empty($row['thumbnail_url'])) { ?>
                                            <a href="<//?php echo h($row['thumbnail_url']); ?>" target="_blank"><//?php echo h($row['thumbnail_url']); ?></a>
                                        <//?php } else { ?>
                                            ---
                                        <//?php } ?>
                                    </td> -->
                                    <!-- <td><//?php echo h($row['description']); ?></td> -->
                                    <td><?php echo h($row['sort_order']); ?></td>
                                    <td>
                                        <?php if ((int) $row['is_active'] === 1) { ?>
                                            <span class="label label-success">Active</span>
                                        <?php } else { ?>
                                            <span class="label label-default">Inactive</span>
                                        <?php } ?>
                                    </td>
                                    <td><?php echo h($row['created_at']); ?></td>
                                    <td style="white-space: nowrap;">
                                        <button
                                            type="button"
                                            class="btn btn-primary btn-xs view-media"
                                            data-id="<?php echo h($row['id']); ?>"
                                            data-host_id="<?php echo h($row['host_id']); ?>"
                                            data-media_type="<?php echo h($row['media_type']); ?>"
                                            data-title="<?php echo h($row['title']); ?>"
                                            data-media_url="<?php echo h($row['media_url']); ?>"
                                            data-thumbnail_url="<?php echo h($row['thumbnail_url']); ?>"
                                            data-description="<?php echo h($row['description']); ?>"
                                            data-sort_order="<?php echo h($row['sort_order']); ?>"
                                            data-is_active="<?php echo h($row['is_active']); ?>"
                                            data-created_at="<?php echo h($row['created_at']); ?>"
                                        >
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-info btn-xs edit-media"
                                            data-id="<?php echo h($row['id']); ?>"
                                            data-host_id="<?php echo h($row['host_id']); ?>"
                                            data-media_type="<?php echo h($row['media_type']); ?>"
                                            data-title="<?php echo h($row['title']); ?>"
                                            data-media_url="<?php echo h($row['media_url']); ?>"
                                            data-thumbnail_url="<?php echo h($row['thumbnail_url']); ?>"
                                            data-description="<?php echo h($row['description']); ?>"
                                            data-sort_order="<?php echo h($row['sort_order']); ?>"
                                            data-is_active="<?php echo h($row['is_active']); ?>"
                                        >
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this media item?');">
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
                                <td colspan="12" class="text-center">No media records found.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <div class="media-pagination-row">
                <div class="media-pagination-info">
                    Showing <?php echo (int) $startItem; ?> to <?php echo (int) $endItem; ?> of <?php echo (int) $totalItems; ?> entries
                </div>

                <?php if ($totalPages > 1) { ?>
                    <ul class="pagination media-pagination">
                        <li class="<?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <?php if ($page <= 1) { ?>
                                <span>Previous</span>
                            <?php } else { ?>
                                <a href="<?php echo h(media_page_url($page - 1, $limit, $search, $mediaTypeFilter)); ?>">Previous</a>
                            <?php } ?>
                        </li>

                        <?php
                        $firstPage = max(1, $page - 2);
                        $lastPage = min($totalPages, $page + 2);

                        if ($firstPage > 1) {
                        ?>
                            <li><a href="<?php echo h(media_page_url(1, $limit, $search, $mediaTypeFilter)); ?>">1</a></li>
                            <?php if ($firstPage > 2) { ?>
                                <li class="disabled"><span>...</span></li>
                            <?php } ?>
                        <?php } ?>

                        <?php for ($pageNumber = $firstPage; $pageNumber <= $lastPage; $pageNumber++) { ?>
                            <li class="<?php echo $pageNumber === $page ? 'active' : ''; ?>">
                                <?php if ($pageNumber === $page) { ?>
                                    <span><?php echo (int) $pageNumber; ?></span>
                                <?php } else { ?>
                                    <a href="<?php echo h(media_page_url($pageNumber, $limit, $search, $mediaTypeFilter)); ?>"><?php echo (int) $pageNumber; ?></a>
                                <?php } ?>
                            </li>
                        <?php } ?>

                        <?php if ($lastPage < $totalPages) { ?>
                            <?php if ($lastPage < $totalPages - 1) { ?>
                                <li class="disabled"><span>...</span></li>
                            <?php } ?>
                            <li><a href="<?php echo h(media_page_url($totalPages, $limit, $search, $mediaTypeFilter)); ?>"><?php echo (int) $totalPages; ?></a></li>
                        <?php } ?>

                        <li class="<?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <?php if ($page >= $totalPages) { ?>
                                <span>Next</span>
                            <?php } else { ?>
                                <a href="<?php echo h(media_page_url($page + 1, $limit, $search, $mediaTypeFilter)); ?>">Next</a>
                            <?php } ?>
                        </li>
                    </ul>
                <?php } ?>
            </div>
        </div>
    </section>
</section>

<style>
    .media-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }

    .media-toolbar-controls,
    .media-limit-control {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .media-type-tabs {
        display: flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
    }

    .media-limit-control .form-control {
        width: 82px;
    }

    .media-search-control {
        width: 320px;
    }

    .media-pagination-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        margin-top: 15px;
    }

    .media-pagination-info {
        color: #777;
    }

    .media-pagination {
        margin: 0;
    }

    @media (max-width: 767px) {
        .media-toolbar,
        .media-toolbar-controls,
        .media-type-tabs,
        .media-pagination-row {
            align-items: stretch;
            flex-direction: column;
        }

        .media-search-control {
            width: 100%;
        }
    }

    .media-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        z-index: 1050;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .media-modal-overlay.is-open {
        display: flex;
    }

    .media-modal {
        background: #fff;
        border-radius: 6px;
        width: 100%;
        max-width: 960px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
    }

    .media-modal-header,
    .media-modal-footer {
        padding: 15px 20px;
        border-bottom: 1px solid #e5e5e5;
    }

    .media-modal-footer {
        border-bottom: 0;
        border-top: 1px solid #e5e5e5;
        text-align: right;
    }

    .media-modal-body {
        padding: 20px;
    }

    .media-modal-close {
        float: right;
        font-size: 26px;
        line-height: 1;
        border: 0;
        background: transparent;
        cursor: pointer;
        color: #555;
    }

    .media-preview-image {
        max-width: 180px;
        max-height: 110px;
        display: none;
        margin-top: 10px;
        border-radius: 4px;
    }

    body.media-modal-open {
        overflow: hidden;
    }

    .media-view-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px 18px;
    }

    .media-view-item {
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        min-width: 0;
    }

    .media-view-item strong {
        display: block;
        color: #333;
        margin-bottom: 4px;
    }

    .media-view-value {
        overflow-wrap: anywhere;
    }

    .media-view-image {
        max-width: 180px;
        max-height: 110px;
        border-radius: 4px;
        border: 1px solid #ddd;
        display: block;
    }

    @media (max-width: 767px) {
        .media-view-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="media-modal-overlay" id="mediaModal" aria-labelledby="mediaModalLabel">
    <div class="media-modal" role="dialog" aria-modal="true">
        <div class="modal-content">
            <form method="POST">
                <div class="media-modal-header">
                    <button type="button" class="media-modal-close" id="closeMediaModalTop" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="mediaModalLabel"><?php echo $formData['id'] ? 'Edit Media' : 'Add Media'; ?></h4>
                </div>
                <div class="media-modal-body">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?php echo h($formData['id']); ?>">

                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label for="modal_host_id">Host ID</label>
                            <input type="number" class="form-control" id="modal_host_id" name="host_id" value="<?php echo h($formData['host_id']); ?>">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="modal_media_type">Media Type <span>*</span></label>
                            <select class="form-control" id="modal_media_type" name="media_type" required>
                                <option value="image" <?php echo $formData['media_type'] === 'image' ? 'selected' : ''; ?>>Image</option>
                                <option value="video" <?php echo $formData['media_type'] === 'video' ? 'selected' : ''; ?>>Video</option>
                                <option value="poster" <?php echo $formData['media_type'] === 'poster' ? 'selected' : ''; ?>>Poster</option>
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="modal_sort_order">Sort Order</label>
                            <input type="number" class="form-control" id="modal_sort_order" name="sort_order" value="<?php echo h($formData['sort_order']); ?>">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="modal_is_active">Status</label>
                            <select class="form-control" id="modal_is_active" name="is_active">
                                <option value="1" <?php echo $formData['is_active'] === '1' || $formData['is_active'] === 1 ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo $formData['is_active'] === '0' || $formData['is_active'] === 0 ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-12 form-group">
                            <label for="modal_title">Title <span>*</span></label>
                            <input type="text" class="form-control" id="modal_title" name="title" value="<?php echo h($formData['title']); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="modal_media_url">Media URL <span>*</span></label>
                            <input type="text" class="form-control" id="modal_media_url" name="media_url" value="<?php echo h($formData['media_url']); ?>" placeholder="assets/images/example.jpg or https://..." required>
                            <img src="<?php echo h($formData['media_url']); ?>" alt="Media Preview" id="modal_media_preview" class="media-preview-image"<?php echo $formData['media_url'] !== '' ? ' style="display:block; margin-top:10px;"' : ''; ?>>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="modal_thumbnail_url">Thumbnail URL</label>
                            <input type="text" class="form-control" id="modal_thumbnail_url" name="thumbnail_url" value="<?php echo h($formData['thumbnail_url']); ?>" placeholder="Optional thumbnail for video">
                            <img src="<?php echo h($formData['thumbnail_url']); ?>" alt="Thumbnail Preview" id="modal_thumbnail_preview" class="media-preview-image"<?php echo $formData['thumbnail_url'] !== '' ? ' style="display:block; margin-top:10px;"' : ''; ?>>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 form-group">
                            <label for="modal_description">Description</label>
                            <textarea class="form-control" id="modal_description" name="description" rows="4"><?php echo h($formData['description']); ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="media-modal-footer">
                    <button type="button" class="btn btn-default" id="closeMediaModalBottom">Close</button>
                    <button type="submit" class="btn btn-primary"><?php echo $formData['id'] ? 'Update Media' : 'Add Media'; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="media-modal-overlay" id="mediaViewModal" aria-labelledby="mediaViewModalLabel">
    <div class="media-modal" role="dialog" aria-modal="true">
        <div class="modal-content">
            <div class="media-modal-header">
                <button type="button" class="media-modal-close" id="closeMediaViewModalTop" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="mediaViewModalLabel">View Media</h4>
            </div>
            <div class="media-modal-body">
                <div class="media-view-grid">
                    <div class="media-view-item">
                        <strong>ID</strong>
                        <div class="media-view-value" data-view-field="id"></div>
                    </div>
                    <div class="media-view-item">
                        <strong>Host ID</strong>
                        <div class="media-view-value" data-view-field="host_id"></div>
                    </div>
                    <div class="media-view-item">
                        <strong>Media Type</strong>
                        <div class="media-view-value" data-view-field="media_type"></div>
                    </div>
                    <div class="media-view-item">
                        <strong>Title</strong>
                        <div class="media-view-value" data-view-field="title"></div>
                    </div>
                    <div class="media-view-item">
                        <strong>Media URL</strong>
                        <div class="media-view-value" data-view-field="media_url"></div>
                    </div>
                    <div class="media-view-item">
                        <strong>Thumbnail URL</strong>
                        <div class="media-view-value" data-view-field="thumbnail_url"></div>
                    </div>
                    <div class="media-view-item">
                        <strong>Description</strong>
                        <div class="media-view-value" data-view-field="description"></div>
                    </div>
                    <div class="media-view-item">
                        <strong>Sort Order</strong>
                        <div class="media-view-value" data-view-field="sort_order"></div>
                    </div>
                    <div class="media-view-item">
                        <strong>Status</strong>
                        <div class="media-view-value" data-view-field="is_active"></div>
                    </div>
                    <div class="media-view-item">
                        <strong>Created At</strong>
                        <div class="media-view-value" data-view-field="created_at"></div>
                    </div>
                </div>
            </div>
            <div class="media-modal-footer">
                <button type="button" class="btn btn-default" id="closeMediaViewModalBottom">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('load', function () {
        var $ = window.jQuery;
        var modal = document.getElementById('mediaModal');
        var viewModal = document.getElementById('mediaViewModal');
        var body = document.body;
        var openAddButton = document.getElementById('open-add-media');
        var closeTopButton = document.getElementById('closeMediaModalTop');
        var closeBottomButton = document.getElementById('closeMediaModalBottom');
        var closeViewTopButton = document.getElementById('closeMediaViewModalTop');
        var closeViewBottomButton = document.getElementById('closeMediaViewModalBottom');
        var modalTitle = document.getElementById('mediaModalLabel');
        var modalIdInput = document.querySelector('#mediaModal input[name="id"]');
        var hostIdInput = document.getElementById('modal_host_id');
        var mediaTypeInput = document.getElementById('modal_media_type');
        var titleInput = document.getElementById('modal_title');
        var mediaUrlInput = document.getElementById('modal_media_url');
        var thumbnailUrlInput = document.getElementById('modal_thumbnail_url');
        var descriptionInput = document.getElementById('modal_description');
        var sortOrderInput = document.getElementById('modal_sort_order');
        var isActiveInput = document.getElementById('modal_is_active');
        var mediaPreview = document.getElementById('modal_media_preview');
        var thumbnailPreview = document.getElementById('modal_thumbnail_preview');
        var submitButton = document.querySelector('#mediaModal .btn-primary');
        var limitSelect = document.getElementById('limit');

        function openModal() {
            modal.classList.add('is-open');
            body.classList.add('media-modal-open');
        }

        function closeModal() {
            modal.classList.remove('is-open');
            body.classList.remove('media-modal-open');
        }

        function openViewModal() {
            viewModal.classList.add('is-open');
            body.classList.add('media-modal-open');
        }

        function closeViewModal() {
            viewModal.classList.remove('is-open');
            body.classList.remove('media-modal-open');
        }

        function setFieldValue(element, value) {
            element.value = value || '';
        }

        function updatePreview(element, value) {
            if (value) {
                element.src = value;
                element.style.display = 'block';
            } else {
                element.src = '';
                element.style.display = 'none';
            }
        }

        function setMediaModal(data) {
            modalTitle.textContent = data.id ? 'Edit Media' : 'Add Media';
            setFieldValue(modalIdInput, data.id || '');
            setFieldValue(hostIdInput, data.host_id || '');
            setFieldValue(mediaTypeInput, data.media_type || 'image');
            setFieldValue(titleInput, data.title || '');
            setFieldValue(mediaUrlInput, data.media_url || '');
            setFieldValue(thumbnailUrlInput, data.thumbnail_url || '');
            setFieldValue(descriptionInput, data.description || '');
            setFieldValue(sortOrderInput, data.sort_order || '0');
            setFieldValue(isActiveInput, data.is_active !== undefined ? data.is_active : '1');
            updatePreview(mediaPreview, data.media_url || '');
            updatePreview(thumbnailPreview, data.thumbnail_url || '');
            submitButton.textContent = data.id ? 'Update Media' : 'Add Media';
        }

        function getEmptyMedia() {
            return {
                id: '',
                host_id: '',
                media_type: 'image',
                title: '',
                media_url: '',
                thumbnail_url: '',
                description: '',
                sort_order: '0',
                is_active: '1'
            };
        }

        function setViewValue(field, value, data) {
            var element = viewModal.querySelector('[data-view-field="' + field + '"]');
            var displayValue = value || '-';

            if (!element) {
                return;
            }

            element.textContent = '';

            if ((field === 'media_url' || field === 'thumbnail_url') && value) {
                var link = document.createElement('a');
                link.href = value;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';

                if (field === 'media_url' && data.media_type !== 'video') {
                    var image = document.createElement('img');
                    image.src = value;
                    image.alt = 'Media Preview';
                    image.className = 'media-view-image';
                    link.appendChild(image);
                } else if (field === 'thumbnail_url') {
                    var thumbnail = document.createElement('img');
                    thumbnail.src = value;
                    thumbnail.alt = 'Thumbnail Preview';
                    thumbnail.className = 'media-view-image';
                    link.appendChild(thumbnail);
                } else {
                    link.textContent = value;
                }

                element.appendChild(link);
                return;
            }

            if (field === 'media_type') {
                element.textContent = value ? value.toUpperCase() : '-';
                return;
            }

            if (field === 'is_active') {
                element.textContent = String(value) === '1' ? 'Active' : 'Inactive';
                return;
            }

            element.textContent = displayValue;
        }

        function setMediaView(data) {
            [
                'id',
                'host_id',
                'media_type',
                'title',
                'media_url',
                'thumbnail_url',
                'description',
                'sort_order',
                'is_active',
                'created_at'
            ].forEach(function (field) {
                setViewValue(field, data[field] || '', data);
            });
        }

        if (limitSelect) {
            limitSelect.addEventListener('change', function () {
                limitSelect.form.submit();
            });
        }

        if (openAddButton) {
            openAddButton.addEventListener('click', function () {
                setMediaModal(getEmptyMedia());
                openModal();
            });
        }

        document.querySelectorAll('.edit-media').forEach(function (button) {
            button.addEventListener('click', function () {
                setMediaModal(button.dataset);
                openModal();
            });
        });

        document.querySelectorAll('.view-media').forEach(function (button) {
            button.addEventListener('click', function () {
                setMediaView(button.dataset);
                openViewModal();
            });
        });

        if (mediaUrlInput) {
            mediaUrlInput.addEventListener('input', function () {
                updatePreview(mediaPreview, mediaUrlInput.value.trim());
            });
        }

        if (thumbnailUrlInput) {
            thumbnailUrlInput.addEventListener('input', function () {
                updatePreview(thumbnailPreview, thumbnailUrlInput.value.trim());
            });
        }

        if (closeTopButton) {
            closeTopButton.addEventListener('click', function () {
                closeModal();
                setMediaModal(getEmptyMedia());
            });
        }

        if (closeBottomButton) {
            closeBottomButton.addEventListener('click', function () {
                closeModal();
                setMediaModal(getEmptyMedia());
            });
        }

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
                setMediaModal(getEmptyMedia());
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
                setMediaModal(getEmptyMedia());
            }

            if (event.key === 'Escape' && viewModal.classList.contains('is-open')) {
                closeViewModal();
            }
        });

        <?php if ($showMediaModal) { ?>
        openModal();
        <?php } ?>
    });
</script>

<?php
include('footer.php');
?>
