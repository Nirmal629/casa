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

        $mail->setFrom('casainfo@casainfotech.com', 'casa-games.com');
        $mail->addAddress('info.casagames@gmail.com', 'casa-games-admin');

        // $mail->isHTML(true);
        // $mail->Subject = 'New contactus inquiry from casa-games.com';
        // $mail->Body    = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #dedede; padding: 20px; background-color: #f9f9f9;">
        // <h2 style="color: #2c3e50; text-align: center;">ðŸ“¬ New Contact Form Submission</h2>
        // <table style="width: 100%; font-size: 16px; color: #333;">
        //     <tr>
        //         <td style="padding: 8px; font-weight: bold; width: 120px;">Name:</td>
        //         <td style="padding: 8px;">' . htmlspecialchars($name) . '</td>
        //     </tr>
        //     <tr>
        //         <td style="padding: 8px; font-weight: bold;">Email:</td>
        //         <td style="padding: 8px;">' . htmlspecialchars($email) . '</td>
        //     </tr>
        //     <tr>
        //         <td style="padding: 8px; font-weight: bold;">Phone:</td>
        //         <td style="padding: 8px;">' . htmlspecialchars($phone) . '</td>
        //     </tr>
        //     <tr>
        //         <td style="padding: 8px; font-weight: bold; vertical-align: top;">Message:</td>
        //         <td style="padding: 8px; white-space: pre-line;">' . nl2br(htmlspecialchars($message)) . '</td>
        //     </tr>
        // </table>
        // <div style="text-align: center; margin-top: 20px;">
        //     <p style="font-size: 13px; color: #888;">This message was sent from the Casa Info website contact form.</p>
        // </div>
        // </div>';

        $mail->isHTML(true);
        $mail->Subject = 'New Contact Inquiry | CASA Games';
        
        $mail->Body = '
        <div style="font-family: Arial, sans-serif; background-color:#f4f6f8; padding:20px;">
            
            <div style="max-width:600px; margin:auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.05);">
                
                <!-- Header -->
                <div style="background:#0f172a; color:#ffffff; padding:15px 20px;">
                    <h2 style="margin:0; font-size:20px;">📩 New Contact Inquiry</h2>
                    <p style="margin:5px 0 0; font-size:13px; opacity:0.8;">Casa Games Admin Notification</p>
                </div>
        
                <!-- Body -->
                <div style="padding:20px;">
                    
                    <table style="width:100%; font-size:15px; color:#333;">
                        <tr>
                            <td style="padding:8px 0; font-weight:bold; width:120px;">Name</td>
                            <td>' . htmlspecialchars($name) . '</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0; font-weight:bold;">Email</td>
                            <td>' . htmlspecialchars($email) . '</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0; font-weight:bold;">Phone</td>
                            <td>' . htmlspecialchars($phone) . '</td>
                        </tr>
                    </table>
        
                    <!-- Message Box -->
                    <div style="margin-top:20px; padding:15px; background:#f9fafb; border-left:4px solid #2563eb; border-radius:4px;">
                        <p style="margin:0; font-weight:bold; color:#111;">Message</p>
                        <p style="margin:8px 0 0; white-space:pre-line; color:#444;">
                            ' . nl2br(htmlspecialchars($message)) . '
                        </p>
                    </div>
        
                    <!-- CTA Button -->
                    <div style="text-align:center; margin-top:25px;">
                        <a href="https://www.casa-games.com/admin/manage_contact.php" 
                           style="background:#2563eb; color:#fff; padding:10px 20px; text-decoration:none; border-radius:5px; font-size:14px;">
                           View in Admin Panel
                        </a>
                    </div>
        
                </div>
        
                <!-- Footer -->
                <div style="background:#f1f5f9; padding:15px; text-align:center; font-size:12px; color:#666;">
                    <p style="margin:0;">This message was sent from Casa Games Contact Form</p>
                    <p style="margin:5px 0 0;">© ' . date("Y") . ' Casa Games</p>
                </div>
        
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
