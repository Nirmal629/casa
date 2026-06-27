<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('dbConnection.php');
include('header.php');
include('sidebar.php');

if ($_POST) {

    $user = intval($_POST['USER_ID']);
    $role = $_POST['PLAYER_ROLE'];
    $rating = intval($_POST['RATING']);
    $message = mysqli_real_escape_string($conn, $_POST['MESSAGE']);
    $status = $_POST['STATUS'];

    mysqli_query($conn, "
        INSERT INTO ca_reviews (USER_ID, PLAYER_ROLE, RATING, MESSAGE, STATUS)
        VALUES ($user, '$role', $rating, '$message', '$status')
    ");

    header("Location:aboutus_review.php");
    exit;
}
?>

<section role="main" class="content-body">

<header class="page-header">
    <h2>Add Review</h2>
</header>

<section class="panel">
<div class="panel-body">

<h3>Add Review</h3>

<form method="post">

<div class="inline-group">
    <label>User ID</label>
    <input type="number" name="USER_ID" required>
</div>

<div class="inline-group">
    <label>Role</label>
    <select name="PLAYER_ROLE">
        <option>Player</option>
        <option>Host</option>
        <option>Trainer</option>
    </select>
</div>

<div class="inline-group">
    <label>Rating</label>
    <select name="RATING">
        <option>1</option><option>2</option><option>3</option>
        <option>4</option><option>5</option>
    </select>
</div>

<div class="inline-group">
    <label>Status</label>
    <select name="STATUS">
        <option>Pending</option>
        <option>Active</option>
        <option>Hidden</option>
    </select>
</div>

<div class="inline-group">
    <label>Message</label>
    <textarea name="MESSAGE" required></textarea>
</div>

<br>

<button type="submit" class="btn btn-success">Save</button>
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
