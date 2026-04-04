<?php
header('Content-Type: application/json');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';
include('dbConnection.php');

$response = ['success' => false, 'message' => ''];

if (isset($_POST['name'], $_POST['email'], $_POST['phone'], $_POST['message'])) {
    $name    = mysqli_real_escape_string($conn, $_POST['name']);
    $email   = mysqli_real_escape_string($conn, $_POST['email']);
    $phone   = mysqli_real_escape_string($conn, $_POST['phone']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    // Store in DB
    $insert = "INSERT INTO ca_contact_messages (name, email, phone, message) 
               VALUES ('$name', '$email', '$phone', '$message')";
    mysqli_query($conn, $insert);

    // Send Email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'localhost';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'casainfo@casainfotech.com';
        $mail->Password   = 'C@sainfo24#';
        $mail->Port       = 25;
        $mail->SMTPSecure = false;
        $mail->Timeout    = 15;

        $mail->setFrom('casainfo@casainfotech.com', 'Casa Info');
        $mail->addAddress('casaclubtoronto@gmail.com', 'CasaClub');

        $mail->isHTML(true);
        $mail->Subject = 'New Contact Inquiry from Casa Info Website';
        $mail->Body    = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #dedede; padding: 20px; background-color: #f9f9f9;">
        <h2 style="color: #2c3e50; text-align: center;">📬 New Contact Form Submission</h2>
        <table style="width: 100%; font-size: 16px; color: #333;">
            <tr>
                <td style="padding: 8px; font-weight: bold; width: 120px;">Name:</td>
                <td style="padding: 8px;">' . htmlspecialchars($name) . '</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Email:</td>
                <td style="padding: 8px;">' . htmlspecialchars($email) . '</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Phone:</td>
                <td style="padding: 8px;">' . htmlspecialchars($phone) . '</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold; vertical-align: top;">Message:</td>
                <td style="padding: 8px; white-space: pre-line;">' . nl2br(htmlspecialchars($message)) . '</td>
            </tr>
        </table>
        <div style="text-align: center; margin-top: 20px;">
            <p style="font-size: 13px; color: #888;">This message was sent from the Casa Info website contact form.</p>
        </div>
    </div>';

        $mail->send();
        $response['success'] = true;
        $response['message'] = 'Your message has been sent successfully.';
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = 'Message saved, but email failed to send.';
    }
}

echo json_encode($response);
