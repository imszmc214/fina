<?php
session_start();
include('../connection.php');

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

header('Content-Type: application/json');

if (!isset($_SESSION['users_username'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

$username = $_SESSION['users_username'];

// Fetch user details
$sql = "SELECT email, gname, surname FROM userss WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

$user = $result->fetch_assoc();
$email = $user['email'];
$fullname = $user['gname'] . ' ' . $user['surname'];

// Generate 6-digit OTP
$otp_code = sprintf("%06d", mt_rand(1, 999999));
$otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Store OTP in database
$update_sql = "UPDATE userss SET otp_code = ?, otp_expiry = ? WHERE username = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("sss", $otp_code, $otp_expiry, $username);

if (!$update_stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to generate OTP.']);
    exit;
}

// Send OTP using PHPMailer
$mail = new PHPMailer(true);

try {
    // Server settings (Optimized: Port 465 is often faster for SMTPS)
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'viahalefinancials@gmail.com';
    $mail->Password   = 'lave czxg trib uqwq';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Explicit SSL
    $mail->Port       = 465;

    // Recipients
    $mail->setFrom('noreply@viahale.com', 'ViaHale Financials');
    $mail->addAddress($email, $fullname);
    $mail->addReplyTo('viahalefinancials@gmail.com', 'ViaHale Support');

    // Embed logo image
    $logo_path = __DIR__ . '/../logo.png';
    if (file_exists($logo_path)) {
        $mail->addEmbeddedImage($logo_path, 'logo', 'logo.png');
        $logo_cid = 'cid:logo';
    } else {
        $logo_cid = '';
    }

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Audit Export Authorization Code';
    
    $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Arial', sans-serif; background-color: #f4f4f4; padding: 20px; margin: 0; }
                .container { background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
                .logo-container { text-align: center; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 2px solid #4311A5; }
                .logo { max-width: 150px; height: auto; }
                .otp-code { font-size: 32px; font-weight: bold; color: #4311A5; text-align: center; letter-spacing: 5px; margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; border: 2px dashed #4311A5; }
                .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 12px; text-align: center; }
                .warning { background: #fff3cd; color: #856404; padding: 12px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #ffc107; }
                .content { color: #333; line-height: 1.6; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='logo-container'>
                    " . ($logo_cid ? "<img src='$logo_cid' alt='ViaHale Financials Logo' class='logo'>" : "<h1>ViaHale Financials</h1>") . "
                </div>
                
                <div class='content'>
                    <h2 style='color: #4311A5; text-align: center;'>Data Export Authorization</h2>
                    <p>Hello <strong>$fullname</strong>,</p>
                    <p>You have requested to export sensitive audit report data. Please enter the following One-Time Password (OTP) to authorize the download:</p>
                    
                    <div class='otp-code'>$otp_code</div>
                    
                    <div class='warning'>
                        <strong>⚠️ Security Notice:</strong> This OTP will expire in <strong>10 minutes</strong>.
                        If you did not initiate this export, please contact security immediately.
                    </div>
                </div>
                
                <div class='footer'>
                    <p><strong>ViaHale Financials Team</strong></p>
                    <p>© 2025 ViaHale Financials. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
    ";
    
    $mail->AltBody = "Your data export authorization code is: $otp_code. This code will expire in 10 minutes.";

    // If possible, respond to the browser immediately so it doesn't wait for Gmail's SMTP latency
    if (function_exists('fastcgi_finish_request')) {
        echo json_encode(['success' => true]);
        session_write_close();
        fastcgi_finish_request();
    }

    $mail->send();
    
    if (!function_exists('fastcgi_finish_request')) {
        echo json_encode(['success' => true]);
    }
} catch (Exception $e) {
    if (!function_exists('fastcgi_finish_request')) {
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP code. Please try again.']);
    }
}
?>
