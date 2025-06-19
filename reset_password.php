<?php
session_start();
require 'config.php';
require_once 'Website_Project.php'; // for logEvent()

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    header("Location: forgot_password.php");
    exit();
}

$error = '';
$success = '';

if (isset($_POST['reset_password'])) {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die(" Invalid CSRF token.");
    }

    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    $email = $_SESSION['otp_email'];

    if ($newPassword !== $confirmPassword) {
        $error = " Passwords do not match.";
    } elseif (strlen($newPassword) < 6) {
        $error = " Password must be at least 6 characters.";
    } elseif (
        !preg_match('/[A-Z]/', $newPassword) ||      // uppercase
        !preg_match('/[a-z]/', $newPassword) ||      // lowercase
        !preg_match('/[0-9]/', $newPassword) ||      // digit
        !preg_match('/[^A-Za-z0-9]/', $newPassword)  // special char
    ) {
        $error = " Password must include uppercase, lowercase, a number, and a special character.";
    } else {
        // Get user ID and current password
        $stmt = $conn->prepare("SELECT id, password FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($user_id, $current_hash);
        $stmt->fetch();
        $stmt->close();

        if (password_verify($newPassword, $current_hash)) {
            $error = " New password can't be the same as your current password.";
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
                $error = " You can't reuse your last 5 passwords.";
                logEvent($conn, $email, 'Password Reset Failed', 'Attempted to reuse a recent password.');
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

                    logEvent($conn, $email, 'Password Reset', 'User successfully reset password after OTP verification.');
                    $success = " Password has been reset successfully! Redirecting...";
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'index.php';
                        }, 3000);
                    </script>";
                    session_unset();
                    session_destroy();
                } else {
                    $error = " Failed to update password.";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Fullscreen Video Background */
        #background-video {
            position: fixed;
            top: 0;
            left: 0;
            min-width: 100vw;
            min-height: 100vh;
            width: auto;
            height: auto;
            z-index: -1;
            object-fit: cover;
        }

        /* Dark overlay for readability */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            z-index: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: sans-serif;
            color: #f0f0f0;
            background: #000;
        }

        .container {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .inside {
            background: #1e1a2e;
            padding: 60px 60px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            text-align: center;
            width: 400px; /* Increased from 300px */
        }

        .inside h2 {
            margin-bottom: 20px;
            color: #ffffff;
        }

        .inside input[type="email"] {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .inside button {
            padding: 10px 20px;
            background: #4caf50;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .inside button:hover {
            background: #45a049;
        }
        .inside h2 {
        font-size: 24px;
        margin-bottom: 25px;
        }

        .inside input[type="email"] {
            font-size: 16px;
            padding: 12px;
        }

        .inside button {
            font-size: 16px;
            padding: 12px 24px;
        }
        
        .form-box.active {
        position: relative;
        z-index: 2;
    }

    </style>
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
        <div style="position: relative;">
            <input type="password" name="new_password" id="new_password" placeholder="New Password" required style="padding-right: 40px;">
            <i class="fa-solid fa-eye" id="toggle-password" style="
                position: absolute;
                right: 12px;
                top: 35%;
                transform: translateY(-50%);
                font-size: 18px;
                color: #ccc;
                cursor: pointer;
            "></i>
        </div>

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

        <div style="position: relative;">
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required style="padding-right: 40px;">
            <i class="fa-solid fa-eye" id="toggle-confirm" style="
                position: absolute;
                right: 12px;
                top: 35%;
                transform: translateY(-50%);
                font-size: 18px;
                color: #ccc;
                cursor: pointer;
            "></i>
        </div>
        <button type="submit" name="reset_password">Reset Password</button>
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    </form>
</div>

<!-- Toggle icon -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('toggle-password');
    const passwordField = document.getElementById('new_password');

    toggleBtn.addEventListener('click', () => {
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);

        toggleBtn.classList.toggle('fa-eye');
        toggleBtn.classList.toggle('fa-eye-slash');
    });

    // Confirm password toggle
    const confirmPassword = document.getElementById('confirm_password');
    const toggleConfirm = document.getElementById('toggle-confirm');

    toggleConfirm.addEventListener('click', () => {
        const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPassword.setAttribute('type', type);
        toggleConfirm.classList.toggle('fa-eye');
        toggleConfirm.classList.toggle('fa-eye-slash');
    });
});
</script>
    <video autoplay muted loop id="background-video">
        <source src="video.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>

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
