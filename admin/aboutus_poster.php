<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
include('dbConnection.php');
include('header.php');
include('sidebar.php');
?>

<section role="main" class="content-body">

<header class="page-header">
    <div class="left-wrapper">
        <ol class="breadcrumbs">
            <li><a href="index.php"><i class="fa fa-home"></i></a></li>
            <li><span>Media Library</span></li>
        </ol>
    </div>
</header>

<section class="panel">

<header class="panel-heading">
    <h2 class="panel-title">Media Manager</h2>
</header>

<div class="panel-body">

<!-- FILTERS -->
<div style="margin-bottom:10px; display:flex; gap:5px; flex-wrap:wrap;">

    <button class="btn btn-default btn-sm filter" data-type="all">All</button>
    <button class="btn btn-primary btn-sm filter" data-type="image">Image</button>
    <button class="btn btn-danger btn-sm filter" data-type="video">Video</button>
    <button class="btn btn-success btn-sm filter" data-type="poster">Poster</button>

    <!-- ADD ICON ONLY -->
    <a href="poster_add.php" class="btn btn-success btn-sm">
        <i class="fa fa-plus"></i>
    </a>

</div>

<table id="posterTable" class="table table-bordered table-striped">

<thead>
<tr>
    <th>SEQ</th>
    <th>Type</th>
    <th>Title</th>
    <th>Preview</th>
    <th>Status</th>
    <th>Date</th>
    <th>Action</th>
</tr>
</thead>

<tbody>

<?php
$sql = "SELECT * FROM ca_landing_page_media ORDER BY id DESC";
$result = $conn->query($sql);

$seq = 1;

while ($row = $result->fetch_assoc()) {
?>

<tr data-type="<?= $row['media_type'] ?>">

<td><?= $seq++ ?></td>

<td>
    <span class="label label-info">
        <?= strtoupper($row['media_type']) ?>
    </span>
</td>

<!-- INLINE EDIT TITLE -->
<td>
<span contenteditable="true"
      class="inline-edit"
      data-id="<?= $row['id'] ?>"
      data-field="title">
    <?= $row['title'] ?>
</span>
</td>

<!-- PREVIEW -->
<td>

<?php if ($row['media_type'] == 'video') { ?>
    <i class="fa fa-play-circle preview-video"
       data-url="<?= $row['media_url'] ?>"
       style="font-size:20px;color:red;cursor:pointer;"></i>
<?php } else { ?>
    <img src="<?= $row['media_url'] ?>"
         class="preview-img"
         data-url="<?= $row['media_url'] ?>"
         style="width:60px;height:60px;object-fit:cover;border-radius:6px;cursor:pointer;">
<?php } ?>

</td>

<!-- STATUS TOGGLE -->
<td>
<label class="switch">
    <input type="checkbox"
           onchange="toggleStatus(<?= $row['id'] ?>, this)"
           <?= $row['is_active'] ? 'checked' : '' ?>>
    <span class="slider"></span>
</label>
</td>

<td><?= $row['created_at'] ?></td>

<!-- ACTION ICONS -->
<td style="white-space:nowrap;">

<!-- VIEW -->
<i class="fa fa-eye view"
   data-url="<?= $row['media_url'] ?>"
   data-type="<?= $row['media_type'] ?>"
   style="color:#3498db;cursor:pointer;margin-right:8px;"></i>

<!-- EDIT -->
<a href="poster_edit.php?id=<?= $row['id'] ?>">
    <i class="fa fa-edit" style="color:#f39c12;margin-right:8px;"></i>
</a>

<!-- DELETE -->
<i class="fa fa-trash delete"
   onclick="deleteMedia(<?= $row['id'] ?>)"
   style="color:#e74c3c;cursor:pointer;"></i>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>
</section>
</section>

<!-- ================= MODAL ================= -->
<div id="previewModal"
     style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;
     background:rgba(0,0,0,0.8);z-index:9999;justify-content:center;align-items:center;">

<div style="background:#fff;padding:10px;border-radius:8px;max-width:80%;">

<span onclick="$('#previewModal').hide()"
      style="float:right;cursor:pointer;">✖</span>

<div id="previewContent"></div>

</div>
</div>

<!-- ================= JS ================= -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function () {

    var table = $('#posterTable').DataTable({
        pageLength: 100,
        lengthMenu: [[100,150,200,250],[100,150,200,250]],
        dom: '<"top"lf>rt<"bottom"ip><"clear">',

        language: {
            lengthMenu: "",
            search: ""
        }
    });

    // REMOVE TEXT FROM SEARCH + LENGTH
    setTimeout(function () {

        $('#posterTable_length label').contents().filter(function () {
            return this.nodeType === 3;
        }).remove();

        $('#posterTable_filter label').contents().filter(function () {
            return this.nodeType === 3;
        }).remove();

    }, 200);

    // FILTER BUTTONS
    $('.filter').click(function () {

        var type = $(this).data('type');

        if (type === 'all') {
            $('#posterTable tbody tr').show();
        } else {
            $('#posterTable tbody tr').hide();
            $('#posterTable tbody tr[data-type="'+type+'"]').show();
        }

    });

});
</script>

<!-- VIEW MODAL -->
<script>
$(document).on('click', '.view, .preview-img, .preview-video', function () {

    var url = $(this).data('url');

    if (!url) return;

    var html = '';

    if ($(this).hasClass('preview-video')) {
        html = '<video src="'+url+'" controls style="max-width:100%;max-height:80vh;"></video>';
    } else {
        html = '<img src="'+url+'" style="max-width:100%;max-height:80vh;">';
    }

    $('#previewContent').html(html);
    $('#previewModal').show();

});
</script>

<!-- INLINE EDIT -->
<script>
$(document).on('blur', '.inline-edit', function () {

    $.post("poster_inline_update.php", {
        id: $(this).data('id'),
        field: $(this).data('field'),
        value: $(this).text()
    });

});
</script>

<!-- STATUS -->
<script>
function toggleStatus(id, el) {

    $.post("poster_toggle.php", {
        id: id,
        status: el.checked ? 1 : 0
    });

}
</script>

<!-- DELETE -->
<script>
function deleteMedia(id) {

    if (!confirm("Delete this item?")) return;

    $.post("poster_delete.php", { id: id }, function () {
        location.reload();
    });

}
</script>

<style>
.switch {
  position: relative;
  display: inline-block;
  width: 38px;
  height: 20px;
}
.switch input { display:none; }

.slider {
  position:absolute;
  cursor:pointer;
  top:0; left:0; right:0; bottom:0;
  background:#ccc;
  border-radius:20px;
}

.slider:before {
  position:absolute;
  content:"";
  height:14px; width:14px;
  left:3px; bottom:3px;
  background:white;
  border-radius:50%;
  transition:.3s;
}

input:checked + .slider { background:#2ecc71; }
input:checked + .slider:before { transform:translateX(18px); }

.inline-edit {
    padding:2px 5px;
    border-radius:4px;
}

.inline-edit:focus {
    outline:1px solid #3498db;
    background:#f5fbff;
}
</style>

<?php include('footer.php'); ?>
