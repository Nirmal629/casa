<?php

// error_reporting(E_ALL);

// ini_set('display_errors', 1);

include('dbConnection.php');

if (isset($_POST['delete_contact'])) {

    header('Content-Type: application/json');

    $id = intval($_POST['id']);

    $response = ['success' => false];

    if ($conn->query("DELETE FROM ca_contact_messages WHERE id=$id")) {

        $response['success'] = true;

    }

    echo json_encode($response);

    exit;

}

include('header.php');

include('sidebar.php');

if (isset($_POST['add_contact'])) {

    $name = $conn->real_escape_string($_POST['name']);

    $email = $conn->real_escape_string($_POST['email']);

    $phone = $conn->real_escape_string($_POST['phone']);

    $message = $conn->real_escape_string($_POST['message']);

    $sql = "INSERT INTO ca_contact_messages (name, email, phone, message, created_at)
            VALUES ('$name', '$email', '$phone', '$message', NOW())";

    if ($conn->query($sql)) {

        header("Location: contact_list.php?contact_added=1");

        exit;

    }

    $contactAddError = $conn->error;
}

?>



<!DOCTYPE html>

<html>

<head>



    <!-- jQuery -->

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>



    <style>
        .table-compact td,
        .table-compact th {

            padding: 4px 8px !important;

            vertical-align: middle !important;

            font-size: 13px;

            white-space: nowrap;

        }

        .custom-table-toolbar,
        .custom-table-footer {

            display: flex;

            align-items: center;

            gap: 10px;

            margin-bottom: 15px;

            flex-wrap: wrap;

        }

        .custom-table-length select {

            min-width: 80px;

        }

        .custom-table-search {
            max-width: 320px;
            width: 100%;
        }

        .custom-table-responsive {
            overflow-x: auto;
            width: 100%;
            -webkit-overflow-scrolling: touch;
        }

        #contactustable {
            min-width: 900px;
        }

        .custom-table-footer {
            justify-content: space-between;
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

        .contact-modal-overlay {
            background: rgba(0, 0, 0, 0.55);
            display: none;
            inset: 0;
            padding: 24px;
            position: fixed;
            z-index: 9999;
        }

        .contact-modal-overlay.is-open {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        .contact-modal-dialog {
            background: #fff;
            border-radius: 6px;
            box-shadow: 0 20px 55px rgba(0, 0, 0, 0.25);
            max-height: 90vh;
            overflow: hidden;
            width: min(650px, 100%);
        }

        .contact-modal-header {
            align-items: center;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            padding: 12px 15px;
        }

        .contact-modal-header h4 {
            margin: 0;
        }

        .contact-modal-close {
            background: transparent;
            border: 0;
            color: #555;
            font-size: 24px;
            line-height: 1;
            padding: 0 4px;
        }

        .contact-modal-body {
            max-height: calc(90vh - 58px);
            overflow-y: auto;
            padding: 15px;
        }

        .contact-detail-row {
            display: flex;
            gap: 15px;
            margin-bottom: 12px;
        }

        .contact-detail-row label {
            font-weight: bold;
            min-width: 95px;
        }

        .contact-message-box {
            white-space: pre-wrap;
            word-break: break-word;
        }

        .contact-form-row {
            margin-bottom: 12px;
        }

        .contact-form-row label {
            font-weight: bold;
        }

        .contact-form-row textarea {
            min-height: 110px;
        }

        body.contact-modal-open {
            overflow: hidden;
        }

        .action-btns .btn {

            padding: 2px 6px;

            font-size: 11px;

        }

        .page-header {
            padding-left: 1cm;
        }

        .left-wrapper {
            display: flex;
            align-items: center;
        }

        .breadcrumbs {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 10px;
        }

        .breadcrumbs li span {
            font-size: 20px;
            font-weight: 700;
            color: #000;
        }

        .breadcrumbs li a {
            color: #000;
            font-size: 18px;
        }

        @media (max-width: 767px) {
            .custom-table-toolbar,
            .custom-table-footer {
                align-items: stretch;
                flex-direction: column;
            }

            .custom-table-search,
            .custom-table-length,
            .custom-table-length select {
                width: 100%;
            }

            .contact-modal-overlay {
                padding: 10px;
            }

            .contact-detail-row {
                align-items: stretch;
                flex-direction: column;
            }
        }
    </style>



</head>



<body>



    <section role="main" class="content-body">

        <header class="page-header">
            <h2>ContactUs</h2>
        </header>



        <section class="panel">

            <!-- <header class="panel-heading">

                <div class="panel-actions">

                    <a href="#" class="panel-action panel-action-toggle" data-panel-toggle></a>

                    <a href="#" class="panel-action panel-action-dismiss" data-panel-dismiss></a>

                </div>

                <h2 class="panel-title">ContactUs Messages</h2>

            </header> -->



            <div class="panel-body">

                <div class="custom-table-toolbar">

                    <button type="button" class="btn btn-success btn-sm" id="openContactAddModal">
                        <i class="fa fa-plus"></i>
                    </button>

                    <label class="custom-table-length" for="contactsPageSize" style="margin-bottom:0;">
                        <select class="form-control" id="contactsPageSize">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </label>

                    <div style="width: 320px;">
                        <input type="search" class="form-control custom-table-search" id="contactsSearch" placeholder="Search contacts">
                    </div>

                </div>



                <div class="custom-table-responsive">

                    <table class="table table-bordered table-striped table-compact mb-none" id="contactustable" style="width:100%">

                        <thead>

                            <tr>

                                <th>SL NO</th>

                                <th>Name</th>

                                <th>Email</th>

                                <th>Phone</th>

                                <th>Message</th>

                                <th>Time</th>

                                <th>Action</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php

                            $sql = "SELECT * FROM ca_contact_messages ORDER BY id DESC";

                            $result = $conn->query($sql);

                            $i = 1;

                            if ($result->num_rows > 0) {

                                while ($row = $result->fetch_assoc()) {

                                    $contactJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');

                                    echo "<tr>";

                                    echo "<td>" . $i . "</td>";

                                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";

                                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";

                                    echo "<td>" . htmlspecialchars($row['phone']) . "</td>";

                                    echo "<td>" . nl2br(htmlspecialchars($row['message'])) . "</td>";

                                    echo "<td>" . $row['created_at'] . "</td>";

                                    echo "<td class='action-btns'>

                                        <button type='button' class='btn btn-info btn-sm contact-view-btn' data-contact='" . $contactJson . "'>

                                            <i class='fa fa-eye'></i>

                                        </button>

                                        <button class='btn btn-danger btn-sm' onclick='deleteContact(" . $row['id'] . ")'>

                                            <i class='fa fa-trash'></i>

                                        </button>

                                      </td>";

                                    echo "</tr>";

                                    $i++;
                                }
                            }

                            ?>

                        </tbody>

                    </table>

                    <div class="custom-table-empty" id="contactsEmptyMessage">No contact messages found.</div>

                </div>

                <div class="custom-table-footer">
                    <div id="contactsTableInfo"></div>
                    <div class="custom-table-pagination" id="contactsPagination"></div>
                </div>

            </div>

        </section>

    </section>


    <div class="contact-modal-overlay" id="contactAddModal" aria-hidden="true">
        <div class="contact-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="contactAddModalTitle">
            <div class="contact-modal-header">
                <h4 id="contactAddModalTitle">Add Contact Message</h4>
                <button type="button" class="contact-modal-close" data-close-contact-modal aria-label="Close">&times;</button>
            </div>
            <div class="contact-modal-body">
                <?php if (!empty($contactAddError)): ?>
                    <div class="alert alert-danger">Error: <?= htmlspecialchars($contactAddError) ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="contact-form-row">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="contact-form-row">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="contact-form-row">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>

                    <div class="contact-form-row">
                        <label>Message</label>
                        <textarea name="message" class="form-control" required></textarea>
                    </div>

                    <button type="submit" name="add_contact" value="1" class="btn btn-success">Save</button>
                    <button type="button" class="btn btn-default" data-close-contact-modal>Cancel</button>
                </form>
            </div>
        </div>
    </div>

    <div class="contact-modal-overlay" id="contactViewModal" aria-hidden="true">
        <div class="contact-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="contactViewModalTitle">
            <div class="contact-modal-header">
                <h4 id="contactViewModalTitle">Contact Details</h4>
                <button type="button" class="contact-modal-close" id="closeContactViewModal" data-close-contact-modal aria-label="Close">&times;</button>
            </div>
            <div class="contact-modal-body">
                <div class="contact-detail-row">
                    <label>Name</label>
                    <div id="contactViewName"></div>
                </div>
                <div class="contact-detail-row">
                    <label>Email</label>
                    <div id="contactViewEmail"></div>
                </div>
                <div class="contact-detail-row">
                    <label>Phone</label>
                    <div id="contactViewPhone"></div>
                </div>
                <div class="contact-detail-row">
                    <label>Time</label>
                    <div id="contactViewTime"></div>
                </div>
                <div class="contact-detail-row">
                    <label>Message</label>
                    <div class="contact-message-box" id="contactViewMessage"></div>
                </div>

                <button type="button" class="btn btn-default" data-close-contact-modal>Close</button>
            </div>
        </div>
    </div>




    <script>
        (function() {
            var addModal = document.getElementById('contactAddModal');
            var viewModal = document.getElementById('contactViewModal');
            var openAddButton = document.getElementById('openContactAddModal');
            var activeModal = null;

            function setText(id, value) {
                var element = document.getElementById(id);
                if (element) {
                    element.textContent = value || '-';
                }
            }

            function openModal(contact) {
                setText('contactViewName', contact.name);
                setText('contactViewEmail', contact.email);
                setText('contactViewPhone', contact.phone);
                setText('contactViewTime', contact.created_at);
                setText('contactViewMessage', contact.message);

                openContactModal(viewModal);
            }

            function openContactModal(modal) {
                if (!modal) {
                    return;
                }

                activeModal = modal;
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('contact-modal-open');
            }

            function closeModal() {
                if (!activeModal) {
                    return;
                }

                activeModal.classList.remove('is-open');
                activeModal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('contact-modal-open');
                activeModal = null;
            }

            if (openAddButton) {
                openAddButton.addEventListener('click', function() {
                    openContactModal(addModal);
                });
            }

            document.querySelectorAll('.contact-view-btn').forEach(function(button) {
                button.addEventListener('click', function() {
                    try {
                        openModal(JSON.parse(button.getAttribute('data-contact') || '{}'));
                    } catch (error) {
                        openModal({});
                    }
                });
            });

            document.querySelectorAll('[data-close-contact-modal]').forEach(function(button) {
                button.addEventListener('click', closeModal);
            });

            document.querySelectorAll('.contact-modal-overlay').forEach(function(modal) {
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

            <?php if (!empty($contactAddError)): ?>
                openContactModal(addModal);
            <?php endif; ?>
        })();

        (function() {
            var table = document.getElementById('contactustable');
            var searchInput = document.getElementById('contactsSearch');
            var pageSizeSelect = document.getElementById('contactsPageSize');
            var info = document.getElementById('contactsTableInfo');
            var pagination = document.getElementById('contactsPagination');
            var emptyMessage = document.getElementById('contactsEmptyMessage');

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
                        renderContactsTable();
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

            function renderContactsTable() {
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
                renderContactsTable();
            });

            pageSizeSelect.addEventListener('change', function() {
                currentPage = 1;
                renderContactsTable();
            });

            renderContactsTable();
        })();

        <?php if (($_GET['contact_added'] ?? '') == '1'): ?>
            alert('Contact message added successfully');
        <?php endif; ?>



        function deleteContact(id) {

            if (confirm('Are you sure you want to delete this contact message?')) {

                $.ajax({

                    url: 'contact_list.php',

                    type: 'POST',

                    dataType: 'json',

                    data: {
                        id: id,
                        delete_contact: 1
                    },

                    success: function(response) {

                        if (response.success) {

                            alert('Deleted successfully!');

                            window.location.href = 'contact_list.php';

                        } else {

                            alert('Error deleting message!');

                        }

                    },

                    error: function() {

                        alert('AJAX error occurred!');

                    }

                });

            }

        }
    </script>



</body>

</html>



<?php include('footer.php'); ?>
