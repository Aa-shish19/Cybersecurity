<?php 
session_start();
$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'register' => $_SESSION['register_error'] ?? ''
];
$activeForm = $_SESSION['active_form'] ?? 'login';


unset($_SESSION['login_error'], $_SESSION['register_error'], $_SESSION['active_form']);


function showError($error) {
    return !empty($error) ? "<p class='error-message'>$error</p>" : '';
}

function isActiveForm($formName, $activeForm) {
    return $formName === $activeForm ? 'active' : '';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="container">
        <div class="form-box <?=isActiveForm('login', $activeForm); ?>" id="login-form">
            <form action="Website_Project.php" method="post">
                <h2>Login</h2>
                <?= showError($errors['login']); ?>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Login</button>
                <p><a href="forgotpassword.php">Forgot Password?</a></p>
                <p>Don't have an account? <a href="#" onclick="showForm('register-form')">Register</a></p>
            </form>
        </div>

         <div class="form-box <?=isActiveForm('register', $activeForm); ?>" id="register-form">
            <form action="Website_Project.php" method="post">
                <h2>Register</h2>
                <?= showError($errors['register']); ?>
                <input type="text" name="name" placeholder="Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" id="password" placeholder="Password" required>
                <p id="strength-msg" style="margin: 5px 0; font-weight: 500;"></p>

                <select name="role" required>
                    <option value="">--Select Role--</option>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit" name="register" id="registerBtn" disabled>Register</button>

                
                <div class="recaptcha-wrapper">
                <div class="g-recaptcha" 
                    data-sitekey="6LeogForAAAAAEyBZtEDQ4Sh75up4QBdVawBxDQd"
                    data-callback="onCaptchaSuccess"
                    data-expired-callback="onCaptchaExpired">
                <small style="display:block; text-align:center; margin-top:-10px; color:gray;">
                Please verify the reCAPTCHA to enable the Register button
                </small>

                </div>
                </div>

                <p>Already have an account? <a href="#" onclick="showForm('login-form')">Login</a></p>
            </form>
        </div>

    </div>
    <script src="script.js"></script>
    <script>
        function onCaptchaSuccess() {
            document.getElementById('registerBtn').disabled = false;
        }

        function onCaptchaExpired() {
            document.getElementById('registerBtn').disabled = true;
        }
        </script>
</body>

<script>
const passwordField = document.getElementById('password');
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