<?php
include('dbConnection.php');
include('header.php');
include('sidebar.php');

$id = intval($_GET['id']);
$res = mysqli_query($conn, "SELECT * FROM ca_reviews WHERE ID = $id");
$row = mysqli_fetch_assoc($res);
?>

<section role="main" class="content-body">

<header class="page-header">
    <div class="left-wrapper">
        <ol class="breadcrumbs">
            <li><a href="index.php"><i class="fa fa-home"></i></a></li>
            <li><span>Reviews</span></li>
            <li><span>View</span></li>
        </ol>
    </div>
</header>

<section class="panel">
<div class="panel-body">

<h3>View Review</h3>

<div class="inline-group">
    <label>User ID</label>
    <div><?=$row['USER_ID']?></div>
</div>

<div class="inline-group">
    <label>Role</label>
    <div><?=$row['PLAYER_ROLE']?></div>
</div>

<div class="inline-group">
    <label>Rating</label>
    <div><?php for($i=1;$i<=$row['RATING'];$i++) echo "⭐"; ?></div>
</div>

<div class="inline-group">
    <label>Status</label>
    <div><?=$row['STATUS']?></div>
</div>

<div class="inline-group">
    <label>Message</label>
    <div><?=$row['MESSAGE']?></div>
</div>

<div class="inline-group">
    <label>Date</label>
    <div><?=date('d M Y, h:i A', strtotime($row['DATE_CREATED']))?></div>
</div>

<br>
<a href="aboutus_review.php" class="btn btn-default">Back</a>

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
</style>

<?php
include('footer.php');
?>