<?php
echo "ok";exit;
ini_set('display_errors', 1);
error_reporting(E_ALL);

$name='nirmal';
$email='nir.multi2018@gmail.com';
$phone='616161616';
$message='sdfdsfsdf';

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


$mail = new PHPMailer(true);
$mail->SMTPDebug = 2;
$mail->Debugoutput = 'html';
try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'localhost';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'casainfo@casainfotech.com'; // your email
    $mail->Password   = 'C@sainfo24#';              // your email password
    $mail->Port = 25; // or 587
$mail->SMTPSecure = false; // disable encryption
    $mail->Timeout = 15;

    // Recipients
    $mail->setFrom('casainfo@casainfotech.com', 'Casa Info');
    $mail->addAddress('nir.multi2018@gmail.com', 'Nirmal');

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'SMTP Test Email';
    $mail->Body    = "
            <strong>Name:</strong> " . htmlspecialchars($name) . "<br>
            <strong>Email:</strong> " . htmlspecialchars($email) . "<br>
            <strong>Phone:</strong> " . htmlspecialchars($phone) . "<br><br>
            <strong>Message:</strong><br>" . nl2br(htmlspecialchars($message));

    $mail->send();
    echo 'Email sent successfully.';
} catch (Exception $e) {
    echo "Mailer Error: {$mail->ErrorInfo}";
}
?>