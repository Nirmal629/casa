<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('dbConnection.php');
include('header.php');
include('sidebar.php');

$id = intval($_GET['id']);

$res = mysqli_query($conn, "SELECT * FROM ca_reviews WHERE ID = $id");
$row = mysqli_fetch_assoc($res);

if ($_POST) {

    $role = $_POST['PLAYER_ROLE'];
    $rating = intval($_POST['RATING']);
    $message = mysqli_real_escape_string($conn, $_POST['MESSAGE']);
    $status = $_POST['STATUS'];

    mysqli_query($conn, "
        UPDATE ca_reviews SET
            PLAYER_ROLE='$role',
            RATING=$rating,
            MESSAGE='$message',
            STATUS='$status'
        WHERE ID=$id
    ");

    header("Location: aboutus_review.php");
    exit;
}
?>

<section role="main" class="content-body">

<header class="page-header">
    <h2>Edit Review</h2>
</header>

<section class="panel">
<div class="panel-body">

<h3>Edit Review</h3>

<form method="post">

<div class="inline-group">
    <label>User ID</label>
    <input type="text" value="<?=$row['USER_ID']?>" disabled>
</div>

<div class="inline-group">
    <label>Role</label>
    <select name="PLAYER_ROLE">
        <option value="Player" <?=$row['PLAYER_ROLE']=='Player'?'selected':''?>>Player</option>
        <option value="Host" <?=$row['PLAYER_ROLE']=='Host'?'selected':''?>>Host</option>
        <option value="Trainer" <?=$row['PLAYER_ROLE']=='Trainer'?'selected':''?>>Trainer</option>
    </select>
</div>

<div class="inline-group">
    <label>Rating</label>
    <select name="RATING">
        <?php for($i=1;$i<=5;$i++){ ?>
        <option value="<?=$i?>" <?=$row['RATING']==$i?'selected':''?>><?=$i?></option>
        <?php } ?>
    </select>
</div>

<div class="inline-group">
    <label>Status</label>
    <select name="STATUS">
        <option value="Pending" <?=$row['STATUS']=='Pending'?'selected':''?>>Pending</option>
        <option value="Active" <?=$row['STATUS']=='Active'?'selected':''?>>Active</option>
        <option value="Hidden" <?=$row['STATUS']=='Hidden'?'selected':''?>>Hidden</option>
    </select>
</div>

<div class="inline-group">
    <label>Message</label>
    <textarea name="MESSAGE"><?=$row['MESSAGE']?></textarea>
</div>

<br>

<button type="submit" class="btn btn-primary">Update</button>
<a href="aboutus_review.php" class="btn btn-default">Cancel</a>

</form>

</div>
</section>

</section>

<style>
.inline-group {
    display:flex;
    gap:15px;
    margin-bottom:10px;
}
.inline-group label {
    min-width:150px;
    font-weight:bold;
}
.inline-group input,
.inline-group select,
.inline-group textarea {
    flex:1;
}
</style>

<?php
include('footer.php');
?>
