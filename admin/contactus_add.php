<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('dbConnection.php');
include('header.php');
include('sidebar.php');

if(isset($_POST['submit'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $message = $conn->real_escape_string($_POST['message']);

    $sql = "INSERT INTO ca_contact_messages (name, email, phone, message, created_at)
            VALUES ('$name', '$email', '$phone', '$message', NOW())";

    if($conn->query($sql)) {
        echo "<script>alert('Contact message added successfully'); window.location='contact_list.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error: ".$conn->error."');</script>";
    }
}
?>

<section role="main" class="content-body">
    <header class="page-header">
        <div class="left-wrapper">
            <ol class="breadcrumbs">
                <li><a href="contact_list.php"><i class="fa fa-home"></i></a></li>
                <li><span>Add Contact</span></li>
            </ol>
        </div>
    </header>

    <section class="panel">
        <header class="panel-heading">
            <h2 class="panel-title">Add Contact Message</h2>
        </header>
        <div class="panel-body">
            <form method="post" action="">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="message" class="form-control" rows="4" required></textarea>
                </div>
                <button type="submit" name="submit" class="btn btn-success">Save</button>
                <a href="contact_list.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </section>
</section>

<?php
include('footer.php');
?>