<?php
session_start();
require 'config.php';

if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    header("Location: forgot_password.php");
    exit();
}

$error = '';
$success = '';

if (isset($_POST['reset_password'])) {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    $email = $_SESSION['otp_email'];

    if ($newPassword !== $confirmPassword) {
        $error = "❌ Passwords do not match.";
    } elseif (strlen($newPassword) < 6) {
        $error = "❌ Password must be at least 6 characters.";
    } else {
        // Get user ID and current password
        $stmt = $conn->prepare("SELECT id, password FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($user_id, $current_hash);
        $stmt->fetch();
        $stmt->close();

        if (password_verify($newPassword, $current_hash)) {
            $error = "❌ New password can't be the same as your current password.";
        } else {
            // Check last 5 passwords
            $check = $conn->prepare("SELECT password_hash FROM password_history WHERE user_id = ? ORDER BY changed_at DESC LIMIT 5");
            $check->bind_param("i", $user_id);
            $check->execute();
            $result = $check->get_result();

            $reuse = false;
            while ($row = $result->fetch_assoc()) {
                if (password_verify($newPassword, $row['password_hash'])) {
                    $reuse = true;
                    break;
                }
            }

            if ($reuse) {
                $error = "❌ You can't reuse your last 5 passwords.";
            } else {
                // Update password
                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE user SET password = ? WHERE id = ?");
                $update->bind_param("si", $hashed, $user_id);

                if ($update->execute()) {
                    // Add to password history
                    $log = $conn->prepare("INSERT INTO password_history (user_id, password_hash) VALUES (?, ?)");
                    $log->bind_param("is", $user_id, $hashed);
                    $log->execute();

                    // Keep only last 5
                    $conn->query("
                        DELETE FROM password_history 
                        WHERE user_id = $user_id 
                          AND id NOT IN (
                              SELECT id FROM (
                                  SELECT id FROM password_history 
                                  WHERE user_id = $user_id 
                                  ORDER BY changed_at DESC 
                                  LIMIT 5
                              ) AS temp
                          )
                    ");

                    $success = "✅ Password has been reset successfully!";
                    session_unset();
                    session_destroy();
                    header("refresh:3;url=index.php");
                } else {
                    $error = "❌ Failed to update password.";
                }
            }
        }
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-box active">
    <h2>Reset Your Password</h2>
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST') : ?>
        <?php if (!empty($error)) : ?>
            <p class="error-message"><?= $error ?></p>
        <?php elseif (!empty($success)) : ?>
            <p class="success-message"><?= $success ?></p>
        <?php endif; ?>
    <?php endif; ?>

    <form method="POST" action="reset_password.php">
        <input type="password" name="new_password" placeholder="New Password" required>
        <p id="strength-msg" style="margin: 5px 0; font-weight: 500;"></p>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit" name="reset_password">Reset Password</button>
    </form>
</div>
</body>

<script>
const passwordField = document.getElementById('new_password');
const strengthMsg = document.getElementById('strength-msg');

passwordField.addEventListener('input', () => {
    const val = passwordField.value;
    let strength = '';
    let color = '';

    if (val.length < 6) {
        strength = '❌ Too short';
        color = 'red';
    } else if (/[a-z]/.test(val) && /[A-Z]/.test(val) && /\d/.test(val) && /[^A-Za-z0-9]/.test(val)) {
        strength = '✅ Strong password';
        color = 'green';
    } else if ((/[a-z]/.test(val) || /[A-Z]/.test(val)) && /\d/.test(val)) {
        strength = '⚠️ Medium strength';
        color = 'orange';
    } else {
        strength = '⚠️ Weak password';
        color = 'red';
    }

    strengthMsg.textContent = strength;
    strengthMsg.style.color = color;
});
</script>

</html>
