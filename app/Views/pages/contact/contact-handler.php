require_once __DIR__ . '/../../../../init.php';

$conn = \App\Config\Database::getMysqli();

$response = ['success' => false, 'message' => ''];

// Rate Limiting — Max 5 messages per 5 minutes per IP
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (!checkRateLimit('contact_' . md5($ip), 5, 300)) {
    echo json_encode(['success' => false, 'message' => 'Too many contact requests. Please wait a few minutes before trying again.']);
    exit;
}

if (isset($_POST['name'], $_POST['email'], $_POST['phone'], $_POST['message'])) {
    $name    = trim((string)$_POST['name']);
    $email   = trim((string)$_POST['email']);
    $phone   = trim((string)$_POST['phone']);
    $message = trim((string)$_POST['message']);

    if (empty($name) || empty($email) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        exit;
    }

    // Store in DB using prepared statement
    $stmt = $conn->prepare("INSERT INTO ca_contact_messages (name, email, phone, message) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssss", $name, $email, $phone, $message);
        $stmt->execute();
        $stmt->close();
    }

    $subject = 'New Contact Inquiry | CASA Games';
    $htmlBody = '
    <div style="font-family: Arial, sans-serif; background-color:#f4f6f8; padding:20px;">
        <div style="max-width:600px; margin:auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.05);">
            <div style="background:#0f172a; color:#ffffff; padding:15px 20px;">
                <h2 style="margin:0; font-size:20px;">📩 New Contact Inquiry</h2>
                <p style="margin:5px 0 0; font-size:13px; opacity:0.8;">Casa Games Admin Notification</p>
            </div>
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
                <div style="margin-top:20px; padding:15px; background:#f9fafb; border-left:4px solid #2563eb; border-radius:4px;">
                    <p style="margin:0; font-weight:bold; color:#111;">Message</p>
                    <p style="margin:8px 0 0; white-space:pre-line; color:#444;">' . nl2br(htmlspecialchars($message)) . '</p>
                </div>
                <div style="text-align:center; margin-top:25px;">
                    <a href="https://www.casa-games.com/admin/manage_contact.php" style="background:#2563eb; color:#fff; padding:10px 20px; text-decoration:none; border-radius:5px; font-size:14px;">View in Admin Panel</a>
                </div>
            </div>
            <div style="background:#f1f5f9; padding:15px; text-align:center; font-size:12px; color:#666;">
                <p style="margin:0;">This message was sent from Casa Games Contact Form</p>
                <p style="margin:5px 0 0;">© ' . date("Y") . ' Casa Games</p>
            </div>
        </div>
    </div>';

    $sent = sendEmail('info.casagames@gmail.com', $subject, $htmlBody, [
        'replyTo' => $email,
        'replyToName' => $name,
    ]);

    if ($sent) {
        $response['success'] = true;
        $response['message'] = 'Your message has been sent successfully.';
    } else {
        $response['success'] = false;
        $response['message'] = 'Message saved, but email notification failed to send.';
    }
}

echo json_encode($response);
