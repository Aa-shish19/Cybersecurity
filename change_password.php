<!-- change_password.php -->
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require 'config.php';

$errorMessage = '';
$successMessage = '';

if (isset($_POST['change_password'])) {
    $user_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $errorMessage = "❌ New passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT password FROM user WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        if (password_verify($current_password, $hashed_password)) {
            if (password_verify($new_password, $hashed_password)) {
                $errorMessage = "❌ New password can't be the same as your current password.";
            } else {
                $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);

                $check = $conn->prepare("SELECT password_hash FROM password_history WHERE user_id = ? ORDER BY changed_at DESC LIMIT 5");
                $check->bind_param("i", $user_id);
                $check->execute();
                $result = $check->get_result();

                $reuse = false;
                while ($row = $result->fetch_assoc()) {
                    if (password_verify($new_password, $row['password_hash'])) {
                        $reuse = true;
                        break;
                    }
                }

                if ($reuse) {
                    $errorMessage = "❌ You can't reuse your last 5 passwords.";
                } else {
                    $update = $conn->prepare("UPDATE user SET password = ? WHERE id = ?");
                    $update->bind_param("si", $new_hashed, $user_id);

                    if ($update->execute()) {
                        $log = $conn->prepare("INSERT INTO password_history (user_id, password_hash) VALUES (?, ?)");
                        $log->bind_param("is", $user_id, $new_hashed);
                        $log->execute();

                        $conn->query("
                            DELETE FROM password_history 
                            WHERE user_id = $user_id 
                              AND id NOT IN (
                                  SELECT id FROM (
                                      SELECT id FROM password_history 
                                      WHERE user_id = $user_id 
                                      ORDER BY changed_at DESC 
                                      LIMIT 5
                                  ) as temp
                              )
                        ");

                        $successMessage = "✅ Password changed successfully!";
                        echo "<meta http-equiv='refresh' content='3;url=" . ($_SESSION['role'] === 'admin' ? 'admin_page.php' : 'user_page.php') . "'>";
                    } else {
                        $errorMessage = "❌ Failed to update password.";
                    }
                }
            }
        } else {
            $errorMessage = "❌ Current password is incorrect.";
        }
    }
}
?>



<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="form-box active">
        <h2>Change Password</h2>

        <?php if (!empty($errorMessage)) : ?>
            <p class="error-message"><?= $errorMessage ?></p>
        <?php endif; ?>

        <form method="POST" action="change_password.php">
            <input type="password" name="current_password" placeholder="Current Password" required>
            <input type="password" name="new_password" id="new_password" placeholder="New Password" required>
            <p id="strength-msg" style="margin: 5px 0; font-weight: 500;"></p>
            <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
            <button type="submit" name="change_password">Change Password</button>
        </form>
    </div>
   <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($successMessage)) : ?>
        <div class="success-container">
            <p class="success-message"><?= $successMessage ?></p>
            <p style="text-align:center; color: #333;">
                Redirecting in 3 seconds... or 
                <a href="<?= ($_SESSION['role'] === 'admin') ? 'admin_page.php' : 'user_page.php'; ?>">click here</a>.
            </p>
        </div>
        <?php $successMessage = ''; ?>

    <?php endif; ?>
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
