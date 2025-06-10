<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-box active">
    <h2>Forgot Password</h2>
    <form method="POST" action="send_otp.php">
        <input type="email" name="email" placeholder="Enter your registered email" required>
        <button type="submit" name="send_otp">Send OTP</button>
    </form>
</div>
</body>
</html>
