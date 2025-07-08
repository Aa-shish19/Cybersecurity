<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'config.php'; //  Your DB connection
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';
require_once 'Website_Project.php'; // if you're using logEvent() instead

session_start();

if (isset($_SESSION['otp_email']) && !isset($_POST['send_otp'])) {
    $_POST['email'] = $_SESSION['otp_email'];
    $_POST['send_otp'] = true;
}

if (isset($_POST['send_otp'])) {
    $email = $_POST['email'];

    //  Check if email exists in DB
    $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_email'] = $email;
        $_SESSION['otp_expiry'] = time() + (1 * 60); // 1 minutes

        $ip = ($_SERVER['REMOTE_ADDR'] === '::1') ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'];
        $details = "OTP sent to $email from IP: $ip";

        // Use centralized logger
        logEvent($conn, $email, 'OTP Sent', $details);

        //  Send OTP via PHPMailer
        $mail = new PHPMailer(true);
        // $mail->SMTPDebug = 2; // Uncomment if you want debug info

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'nischalparajuli69@gmail.com';
            $mail->Password = 'zvejvxviozpaaxwc'; //  Your app password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('nischalparajuli69@gmail.com', 'Your App');
            $mail->addAddress($email);
            $mail->Subject = 'Your OTP Code';
            $mail->Body    = "Your OTP code is: $otp\nIt will expire in 5 minutes.";

            $mail->send();
            header("Location: verify_OTP.php");
            exit();
        } catch (Exception $e) {
            echo " Failed to send OTP: {$mail->ErrorInfo}";
        }
    } else {
        echo "<p class='error-message'>Email not found in our records.</p>";
    }
} else {
    header("Location: forgot_password.php");
    exit();
}