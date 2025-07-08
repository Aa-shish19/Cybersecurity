<!-- change_password.php -->
<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require 'config.php';
require_once 'Website_Project.php'; //  contains logEvent function

$errorMessage = '';
$successMessage = '';

if (isset($_POST['change_password'])) {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die(" Invalid CSRF token.");
    }

    $user_id = $_SESSION['user_id'];
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($new_password !== $confirm_password) {
        $errorMessage = " New passwords do not match.";
    } else {

        // Enforce strong password policy
        if (
            !preg_match('/[A-Z]/', $new_password) ||      // at least one uppercase
            !preg_match('/[a-z]/', $new_password) ||      // at least one lowercase
            !preg_match('/[0-9]/', $new_password) ||      // at least one digit
            !preg_match('/[^A-Za-z0-9]/', $new_password)  // at least one special character
        ) {
            $errorMessage = " Password must include at least one uppercase letter, one lowercase letter, one number, and one special character.";
        }

        $stmt = $conn->prepare("SELECT password FROM user WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        if (password_verify($current_password, $hashed_password)) {
            if (password_verify($new_password, $hashed_password)) {
                $errorMessage = " New password can't be the same as your current password.";
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
                    $errorMessage = " You can't reuse your last 5 passwords.";
                } else {
                    $update = $conn->prepare("UPDATE user SET password = ? WHERE id = ?");
                    $update->bind_param("si", $new_hashed, $user_id);

                    if ($update->execute()) {
                        $log = $conn->prepare("INSERT INTO password_history (user_id, password_hash) VALUES (?, ?)");
                        $log->bind_param("is", $user_id, $new_hashed);
                        $log->execute();

                        $cleanup_sql = "
                            DELETE FROM password_history 
                            WHERE user_id = ? 
                            AND id NOT IN (
                                SELECT id FROM (
                                    SELECT id FROM password_history 
                                    WHERE user_id = ? 
                                    ORDER BY changed_at DESC 
                                    LIMIT 5
                                ) AS temp
                            )
                        ";
                        $cleanup = $conn->prepare($cleanup_sql);
                        $cleanup->bind_param("ii", $user_id, $user_id);
                        $cleanup->execute();

                        logEvent($conn, $_SESSION['email'], 'password_change', 'User updated their password successfully.');
                        $successMessage = " Password changed successfully!";                       

                        unset($_SESSION['csrf_token']); // Reset CSRF token after successful form use
                        echo "<meta http-equiv='refresh' content='3;url=" . ($_SESSION['role'] === 'admin' ? 'admin_page.php' : 'user_page.php') . "'>";                       
                    } else {
                        $errorMessage = " Failed to update password.";
                    }
                }
            }
        } else {
            $errorMessage = " Current password is incorrect.";
            logEvent($conn, $_SESSION['email'], 'password_change_failed', 'Incorrect current password entered.');
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

            <!-- Password Strength Meter & Checklist -->
            <div id="password-checklist-wrapper">
            <div id="strength-meter">
                <div id="strength-bar"></div>
            </div>
            <p id="strength-label">Strength:</p>
            <ul id="password-checklist">
                <li id="length">At least 6 characters</li>
                <li id="lowercase">One lowercase letter</li>
                <li id="uppercase">One uppercase letter</li>
                <li id="number">One number</li>
                <li id="special">One special character</li>
            </ul>
            </div>

            <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
            <button type="submit" name="change_password">Change Password</button>
            <input type="hidden" name="csrf_token" value="<?= isset($_SESSION['csrf_token']) ? htmlspecialchars($_SESSION['csrf_token']) : '' ?>">
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
const strengthBar = document.getElementById('strength-bar');
const strengthLabel = document.getElementById('strength-label');

const rules = {
  length: document.getElementById('length'),
  lowercase: document.getElementById('lowercase'),
  uppercase: document.getElementById('uppercase'),
  number: document.getElementById('number'),
  special: document.getElementById('special'),
};

passwordField.addEventListener('input', () => {
  const val = passwordField.value;

  const validations = {
    length: val.length >= 6,
    lowercase: /[a-z]/.test(val),
    uppercase: /[A-Z]/.test(val),
    number: /\d/.test(val),
    special: /[^A-Za-z0-9]/.test(val),
  };

  let passed = 0;
  for (let key in validations) {
    if (validations[key]) {
      rules[key].classList.add('valid');
      passed++;
    } else {
      rules[key].classList.remove('valid');
    }
  }

  const percent = (passed / 5) * 100;
  strengthBar.style.width = percent + '%';

  if (passed <= 2) {
    strengthBar.style.backgroundColor = 'red';
    strengthLabel.textContent = 'Strength: Weak';
  } else if (passed === 3 || passed === 4) {
    strengthBar.style.backgroundColor = 'orange';
    strengthLabel.textContent = 'Strength: Moderate';
  } else {
    strengthBar.style.backgroundColor = 'green';
    strengthLabel.textContent = 'Strength: Strong';
  }
});

</script>

</html>
