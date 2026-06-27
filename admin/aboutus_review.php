<?php

ob_start();

include('dbConnection.php');

include('header.php');

include('sidebar.php');



// UPDATE STATUS (same file)

if (isset($_POST['update_status'])) {

    $id = intval($_POST['review_id']);

    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $allowedStatuses = ['Active', 'Hidden', 'Pending'];

    if (in_array($status, $allowedStatuses, true)) {

        mysqli_query($conn, "UPDATE ca_reviews SET STATUS = '$status' WHERE ID = $id");

    }

    header("Location:aboutus_review.php");

    exit;

}

if (isset($_POST['add_review'])) {

    $user = intval($_POST['USER_ID']);

    $role = mysqli_real_escape_string($conn, $_POST['PLAYER_ROLE']);

    $rating = intval($_POST['RATING']);

    $message = mysqli_real_escape_string($conn, $_POST['MESSAGE']);

    $status = mysqli_real_escape_string($conn, $_POST['STATUS']);

    mysqli_query($conn, "
        INSERT INTO ca_reviews (USER_ID, PLAYER_ROLE, RATING, MESSAGE, STATUS)
        VALUES ($user, '$role', $rating, '$message', '$status')
    ");

    header("Location:aboutus_review.php");

    exit;

}

if (isset($_POST['edit_review'])) {

    $id = intval($_POST['review_id']);

    $role = mysqli_real_escape_string($conn, $_POST['PLAYER_ROLE']);

    $rating = intval($_POST['RATING']);

    $message = mysqli_real_escape_string($conn, $_POST['MESSAGE']);

    $status = mysqli_real_escape_string($conn, $_POST['STATUS']);

    mysqli_query($conn, "
        UPDATE ca_reviews SET
            PLAYER_ROLE='$role',
            RATING=$rating,
            MESSAGE='$message',
            STATUS='$status'
        WHERE ID=$id
    ");

    header("Location:aboutus_review.php");

    exit;

}

// FETCH DATA

$result = mysqli_query($conn, "SELECT * FROM ca_reviews ORDER BY ID DESC");

?>


<style>
    .review-status-select {
        border: 0;
        border-radius: 999px;
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        min-width: 105px;
        padding: 5px 10px;
        height: 30px;
    }

    .review-status-select.status-active {
        background: #28a745;
    }

    .review-status-select.status-hidden {
        background: #dc3545;
    }

    .review-status-select.status-pending {
        background: #f0ad4e;
    }

    .review-status-select option {
        background: #fff;
        color: #333;
    }



    .custom-table-toolbar {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

      .custom-table-footer {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .custom-table-actions,
    .custom-table-length {
        align-items: center;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .custom-table-search {
        max-width: 320px;
        width: 100%;
    }

    .custom-table-length select {
        width: 90px;
    }

    .custom-table-responsive {
        overflow-x: auto;
        width: 100%;
        -webkit-overflow-scrolling: touch;
    }

    #reviewsTable {
        min-width: 760px;
    }

    #reviewsTable tbody td {
        white-space: nowrap;
        vertical-align: middle;
    }

    .custom-table-footer {
        margin-bottom: 0;
        margin-top: 12px;
    }

    .custom-table-pagination {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .custom-page-btn {
        background: #fff;
        border: 1px solid #ccc;
        border-radius: 3px;
        color: #333;
        min-width: 34px;
        padding: 5px 9px;
    }

    .custom-page-btn.active {
        background: #0088cc;
        border-color: #0088cc;
        color: #fff;
    }

    .custom-page-btn:disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }

    .custom-table-empty {
        display: none;
        padding: 18px;
        text-align: center;
    }

    .review-modal-overlay {
        background: rgba(0, 0, 0, 0.55);
        display: none;
        inset: 0;
        padding: 24px;
        position: fixed;
        z-index: 9999;
    }

    .review-modal-overlay.is-open {
        align-items: center;
        display: flex;
        justify-content: center;
    }

    .review-modal-dialog {
        background: #fff;
        border-radius: 6px;
        box-shadow: 0 20px 55px rgba(0, 0, 0, 0.25);
        max-height: 90vh;
        overflow: hidden;
        width: min(720px, 100%);
    }

    .review-modal-header {
        align-items: center;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        padding: 12px 15px;
    }

    .review-modal-header h4 {
        margin: 0;
    }

    .review-modal-close {
        background: transparent;
        border: 0;
        color: #555;
        font-size: 24px;
        line-height: 1;
        padding: 0 4px;
    }

    .review-modal-body {
        max-height: calc(90vh - 58px);
        overflow-y: auto;
        padding: 15px;
    }

    .review-form-row,
    .review-detail-row {
        display: flex;
        gap: 15px;
        margin-bottom: 12px;
    }

    .review-form-row label,
    .review-detail-row label {
        font-weight: bold;
        min-width: 130px;
    }

    .review-form-row input,
    .review-form-row select,
    .review-form-row textarea {
        flex: 1;
    }

    .review-form-row textarea {
        min-height: 95px;
    }

    body.review-modal-open {
        overflow: hidden;
    }

    @media (max-width: 767px) {
        .custom-table-footer,
        .custom-table-actions,
        .custom-table-length,
        .review-form-row,
        .review-detail-row {
            align-items: stretch;
            flex-direction: column;
        }

        .review-modal-overlay {
            padding: 10px;
        }

    }
</style>


<section role="main" class="content-body">

    <!-- HEADER -->
    <header class="page-header">
        <h2>Reviews</h2>
    </header>


    <!-- PANEL -->
    <div class="panel">
        <div class="panel-body">

            <div class="custom-table-toolbar">
                <button type="button" class="btn btn-success btn-sm" id="openAddReviewModal">
                    <i class="fa fa-plus"></i>
                </button>

                <label class="custom-table-length" for="reviewsPageSize" style="margin-bottom: 0;">
                    <!-- Show -->
                    <select class="form-control" id="reviewsPageSize">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <!-- entries -->
                </label>

                <div style="width: 320px;">
                    <input type="search" class="form-control custom-table-search" id="reviewsSearch" placeholder="Search reviews">
                </div>
            </div>



            <!-- TABLE -->

            <div class="custom-table-responsive">

                <table id="reviewsTable" class="table table-bordered table-striped table-hover mb-none">



                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>User</th>

                            <th>Role</th>

                            <th>Rating</th>

                            <th>Status</th>

                            <th>Date</th>

                            <th style="width:120px;">Actions</th>

                        </tr>

                    </thead>



                    <tbody>

                        <?php while ($row = mysqli_fetch_assoc($result)) {
                            $reviewJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                        ?>

                            <tr>



                                <td><?= $row['ID'] ?></td>

                                <td><?= $row['USER_ID'] ?></td>

                                <td><?= $row['PLAYER_ROLE'] ?></td>



                                <!-- Rating -->

                                <td>

                                    <?php for ($i = 1; $i <= $row['RATING']; $i++) echo "⭐"; ?>

                                </td>



                                <!-- Status -->

                                <td>

                                    <?php

                                    $statusClass = 'status-pending';

                                    if ($row['STATUS'] == 'Active') $statusClass = 'status-active';

                                    if ($row['STATUS'] == 'Hidden') $statusClass = 'status-hidden';

                                    ?>

                                    <form method="post" style="margin:0;">
                                        <input type="hidden" name="review_id" value="<?= $row['ID'] ?>">
                                        <select name="status"
                                            class="review-status-select <?= $statusClass ?>"
                                            onchange="this.form.submit()">
                                            <option value="Active" <?= ($row['STATUS'] == 'Active') ? 'selected' : '' ?>>Active</option>
                                            <option value="Hidden" <?= ($row['STATUS'] == 'Hidden') ? 'selected' : '' ?>>Hidden</option>
                                            <option value="Pending" <?= ($row['STATUS'] == 'Pending') ? 'selected' : '' ?>>Pending</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>

                                </td>



                                <!-- Date -->

                                <td><?= date('d M Y', strtotime($row['DATE_CREATED'])) ?></td>



                                <!-- Actions -->

                                <td>



                                    <!-- View -->

                                    <button type="button"
                                        class="btn btn-link btn-xs review-view-btn"
                                        data-review='<?= $reviewJson ?>'
                                        title="View">

                                        <i class="fa fa-eye text-dark"></i>

                                    </button>



                                    &nbsp;



                                    <!-- Edit -->

                                    <button type="button"
                                        class="btn btn-link btn-xs review-edit-btn"
                                        data-review='<?= $reviewJson ?>'
                                        title="Edit">

                                        <i class="fa fa-pencil text-primary"></i>

                                    </button>



                                </td>



                            </tr>

                        <?php } ?>

                    </tbody>



                </table>

                <div class="custom-table-empty" id="reviewsEmptyMessage">No matching reviews found.</div>

            </div>

            <div class="custom-table-footer">

                <div id="reviewsTableInfo"></div>

                <div class="custom-table-pagination" id="reviewsPagination"></div>

            </div>



        </div>
    </div>

</section>

<div class="review-modal-overlay" id="addReviewModal" aria-hidden="true">
    <div class="review-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="addReviewModalTitle">
        <div class="review-modal-header">
            <h4 id="addReviewModalTitle">Add Review</h4>
            <button type="button" class="review-modal-close" data-close-review-modal aria-label="Close">&times;</button>
        </div>
        <div class="review-modal-body">
            <form method="post">
                <div class="review-form-row">
                    <label>User ID</label>
                    <input type="number" class="form-control" name="USER_ID" required>
                </div>

                <div class="review-form-row">
                    <label>Role</label>
                    <select class="form-control" name="PLAYER_ROLE">
                        <option>Player</option>
                        <option>Host</option>
                        <option>Trainer</option>
                    </select>
                </div>

                <div class="review-form-row">
                    <label>Rating</label>
                    <select class="form-control" name="RATING">
                        <option>1</option>
                        <option>2</option>
                        <option>3</option>
                        <option>4</option>
                        <option>5</option>
                    </select>
                </div>

                <div class="review-form-row">
                    <label>Status</label>
                    <select class="form-control" name="STATUS">
                        <option>Pending</option>
                        <option>Active</option>
                        <option>Hidden</option>
                    </select>
                </div>

                <div class="review-form-row">
                    <label>Message</label>
                    <textarea class="form-control" name="MESSAGE" required></textarea>
                </div>

                <button type="submit" class="btn btn-success" name="add_review" value="1">Save</button>
                <button type="button" class="btn btn-default" data-close-review-modal>Cancel</button>
            </form>
        </div>
    </div>
</div>

<div class="review-modal-overlay" id="editReviewModal" aria-hidden="true">
    <div class="review-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="editReviewModalTitle">
        <div class="review-modal-header">
            <h4 id="editReviewModalTitle">Edit Review</h4>
            <button type="button" class="review-modal-close" data-close-review-modal aria-label="Close">&times;</button>
        </div>
        <div class="review-modal-body">
            <form method="post" id="editReviewForm">
                <input type="hidden" name="review_id" id="edit_review_id">

                <div class="review-form-row">
                    <label>User ID</label>
                    <input type="text" class="form-control" id="edit_user_id" disabled>
                </div>

                <div class="review-form-row">
                    <label>Role</label>
                    <select class="form-control" name="PLAYER_ROLE" id="edit_player_role">
                        <option value="Player">Player</option>
                        <option value="Host">Host</option>
                        <option value="Trainer">Trainer</option>
                    </select>
                </div>

                <div class="review-form-row">
                    <label>Rating</label>
                    <select class="form-control" name="RATING" id="edit_rating">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                </div>

                <div class="review-form-row">
                    <label>Status</label>
                    <select class="form-control" name="STATUS" id="edit_status">
                        <option value="Pending">Pending</option>
                        <option value="Active">Active</option>
                        <option value="Hidden">Hidden</option>
                    </select>
                </div>

                <div class="review-form-row">
                    <label>Message</label>
                    <textarea class="form-control" name="MESSAGE" id="edit_message" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary" name="edit_review" value="1">Update</button>
                <button type="button" class="btn btn-default" data-close-review-modal>Cancel</button>
            </form>
        </div>
    </div>
</div>

<div class="review-modal-overlay" id="viewReviewModal" aria-hidden="true">
    <div class="review-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="viewReviewModalTitle">
        <div class="review-modal-header">
            <h4 id="viewReviewModalTitle">View Review</h4>
            <button type="button" class="review-modal-close" data-close-review-modal aria-label="Close">&times;</button>
        </div>
        <div class="review-modal-body">
            <div class="review-detail-row">
                <label>User ID</label>
                <div id="view_user_id"></div>
            </div>
            <div class="review-detail-row">
                <label>Role</label>
                <div id="view_player_role"></div>
            </div>
            <div class="review-detail-row">
                <label>Rating</label>
                <div id="view_rating"></div>
            </div>
            <div class="review-detail-row">
                <label>Status</label>
                <div id="view_status"></div>
            </div>
            <div class="review-detail-row">
                <label>Message</label>
                <div id="view_message"></div>
            </div>
            <div class="review-detail-row">
                <label>Date</label>
                <div id="view_date"></div>
            </div>

            <button type="button" class="btn btn-default" data-close-review-modal>Close</button>
        </div>
    </div>
</div>


<script>
    (function() {
        var addModal = document.getElementById('addReviewModal');
        var editModal = document.getElementById('editReviewModal');
        var viewModal = document.getElementById('viewReviewModal');
        var openAddButton = document.getElementById('openAddReviewModal');
        var activeModal = null;

        function openModal(modal) {
            if (!modal) {
                return;
            }

            activeModal = modal;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('review-modal-open');
        }

        function closeModal() {
            if (!activeModal) {
                return;
            }

            activeModal.classList.remove('is-open');
            activeModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('review-modal-open');
            activeModal = null;
        }

        function getReview(button) {
            try {
                return JSON.parse(button.getAttribute('data-review') || '{}');
            } catch (error) {
                return {};
            }
        }

        function setText(id, value) {
            var element = document.getElementById(id);
            if (element) {
                element.textContent = value || '-';
            }
        }

        function formatDate(value) {
            if (!value) {
                return '-';
            }

            var date = new Date(String(value).replace(' ', 'T'));
            if (Number.isNaN(date.getTime())) {
                return value;
            }

            return date.toLocaleString('en-US', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function ratingStars(rating) {
            var count = parseInt(rating || 0, 10);
            return new Array(Math.max(0, count) + 1).join(String.fromCharCode(9733));
        }

        if (openAddButton) {
            openAddButton.addEventListener('click', function() {
                openModal(addModal);
            });
        }

        document.querySelectorAll('.review-edit-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                var review = getReview(button);

                document.getElementById('edit_review_id').value = review.ID || '';
                document.getElementById('edit_user_id').value = review.USER_ID || '';
                document.getElementById('edit_player_role').value = review.PLAYER_ROLE || 'Player';
                document.getElementById('edit_rating').value = review.RATING || '1';
                document.getElementById('edit_status').value = review.STATUS || 'Pending';
                document.getElementById('edit_message').value = review.MESSAGE || '';

                openModal(editModal);
            });
        });

        document.querySelectorAll('.review-view-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                var review = getReview(button);

                setText('view_user_id', review.USER_ID);
                setText('view_player_role', review.PLAYER_ROLE);
                setText('view_rating', ratingStars(review.RATING));
                setText('view_status', review.STATUS);
                setText('view_message', review.MESSAGE);
                setText('view_date', formatDate(review.DATE_CREATED));

                openModal(viewModal);
            });
        });

        document.querySelectorAll('[data-close-review-modal]').forEach(function(button) {
            button.addEventListener('click', closeModal);
        });

        document.querySelectorAll('.review-modal-overlay').forEach(function(modal) {
            modal.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    })();

    document.querySelectorAll('.review-status-select').forEach(function(select) {
        select.addEventListener('change', function() {
            select.classList.remove('status-active', 'status-hidden', 'status-pending');
            select.classList.add('status-' + select.value.toLowerCase());
        });
    });

    (function() {
        var table = document.getElementById('reviewsTable');
        var searchInput = document.getElementById('reviewsSearch');
        var pageSizeSelect = document.getElementById('reviewsPageSize');
        var info = document.getElementById('reviewsTableInfo');
        var pagination = document.getElementById('reviewsPagination');
        var emptyMessage = document.getElementById('reviewsEmptyMessage');

        if (!table || !searchInput || !pageSizeSelect || !info || !pagination || !emptyMessage) {
            return;
        }

        var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr'));
        var currentPage = 1;

        function getFilteredRows() {
            var search = searchInput.value.trim().toLowerCase();

            if (!search) {
                return rows;
            }

            return rows.filter(function(row) {
                return row.innerText.toLowerCase().indexOf(search) !== -1;
            });
        }

        function buildPageButton(label, page, disabled, active) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'custom-page-btn' + (active ? ' active' : '');
            button.textContent = label;
            button.disabled = disabled;

            if (!disabled && !active) {
                button.addEventListener('click', function() {
                    currentPage = page;
                    renderReviewsTable();
                });
            }

            return button;
        }

        function addDots() {
            var dots = document.createElement('span');
            dots.textContent = '...';
            dots.style.padding = '6px 2px';
            pagination.appendChild(dots);
        }

        function renderPagination(totalPages) {
            pagination.innerHTML = '';

            pagination.appendChild(buildPageButton('Prev', currentPage - 1, currentPage === 1, false));

            var startPage = Math.max(1, currentPage - 2);
            var endPage = Math.min(totalPages, currentPage + 2);

            if (startPage > 1) {
                pagination.appendChild(buildPageButton('1', 1, false, currentPage === 1));
            }

            if (startPage > 2) {
                addDots();
            }

            for (var page = startPage; page <= endPage; page++) {
                pagination.appendChild(buildPageButton(String(page), page, false, currentPage === page));
            }

            if (endPage < totalPages - 1) {
                addDots();
            }

            if (endPage < totalPages) {
                pagination.appendChild(buildPageButton(String(totalPages), totalPages, false, currentPage === totalPages));
            }

            pagination.appendChild(buildPageButton('Next', currentPage + 1, currentPage === totalPages, false));
        }

        function renderReviewsTable() {
            var pageSize = parseInt(pageSizeSelect.value, 10);
            var filteredRows = getFilteredRows();
            var totalRows = filteredRows.length;
            var totalPages = Math.max(1, Math.ceil(totalRows / pageSize));

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            var startIndex = (currentPage - 1) * pageSize;
            var endIndex = Math.min(startIndex + pageSize, totalRows);
            var visibleRows = filteredRows.slice(startIndex, endIndex);

            rows.forEach(function(row) {
                row.style.display = 'none';
            });

            visibleRows.forEach(function(row) {
                row.style.display = '';
            });

            emptyMessage.style.display = totalRows ? 'none' : 'block';
            info.textContent = totalRows ?
                'Showing ' + (startIndex + 1) + ' to ' + endIndex + ' of ' + totalRows + ' entries' :
                'Showing 0 entries';

            renderPagination(totalPages);
        }

        searchInput.addEventListener('input', function() {
            currentPage = 1;
            renderReviewsTable();
        });

        pageSizeSelect.addEventListener('change', function() {
            currentPage = 1;
            renderReviewsTable();
        });

        renderReviewsTable();
    })();
</script>

<?php

include('footer.php');

?>
