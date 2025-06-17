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
    <title>Secure Login & Register - CyberVault</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
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
    background-size: cover;
}

/* Ensure main content sits above the video */
.container {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

/* Optional: fallback background color */
body {
    margin: 0;
    padding: 0;
    font-family: sans-serif;
    color: #f0f0f0;
    background: #000; /* fallback */
    overflow: hidden;
}
body::before {
    content: "";
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.5); /* adjust darkness */
    z-index: 0;
}

    </style>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <video autoplay muted loop id="background-video">
    <source src="video.mp4" type="video/mp4">
    Your browser does not support the video tag.
</video>

    <div class="container">
        <div class="form-box <?=isActiveForm('login', $activeForm); ?>" id="login-form">
            <form action="Website_Project.php" method="post">
                <h2>Login</h2>
                <?= showError($errors['login']); ?>

                <?php if (isset($_SESSION['remaining_time'])): ?>
                    <p id="countdown-message" style="color: red; text-align:center;"></p>
                    <script>
                        let remaining = <?= $_SESSION['remaining_time']; ?>;
                        const countdownEl = document.getElementById('countdown-message');

                        function updateCountdown() {
                            if (remaining <= 0) {
                                countdownEl.textContent = "";
                                return;
                            }

                            let mins = Math.floor(remaining / 60);
                            let secs = remaining % 60;
                            countdownEl.textContent = `⏳ Please wait ${mins}:${secs.toString().padStart(2, '0')} before trying again.`;

                            remaining--;
                            setTimeout(updateCountdown, 1000);
                        }

                        updateCountdown();
                    </script>
                    <?php unset($_SESSION['remaining_time']); ?>

                <?php endif; ?>

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
                <input type="password" name="password" id="password" placeholder="Password" required autocomplete="off">
                <p id="strength-msg" style="margin: 5px 0; font-weight: 500;"></p>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                <small id="confirm-msg" style="color: red; display: none; font-size: 13px; margin-top: -15px; margin-bottom: 10px;">
                Passwords do not match!
                </small>

                <div class="recaptcha-wrapper" style="margin-top: 10px; margin-bottom: 20px;">
                    <div class="g-recaptcha" 
                        data-sitekey="6LeogForAAAAAEyBZtEDQ4Sh75up4QBdVawBxDQd"
                        data-callback="onCaptchaSuccess"
                        data-expired-callback="onCaptchaExpired">

                    </div>
                </div>

                <button type="submit" name="register" id="registerBtn" disabled>Register</button>             
                
                <small id="recaptcha-msg" style="display: none; text-align:center; margin-top:-10px; color:gray;">
                    Please verify the reCAPTCHA to enable the Register button
                </small>

                

                <p>Already have an account? <a href="#" onclick="showForm('login-form')">Login</a></p>
            </form>
        </div>

    </div>
    
    <!-- Password Strength Checker -->
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

<!--  Load after the callbacks -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script src="script.js"></script>

<script>
// Unified Password Match + reCAPTCHA Checker
const passwordInput = document.getElementById('password');
const confirmInput = document.getElementById('confirm_password');
const confirmMsg = document.getElementById('confirm-msg');
const registerBtn = document.getElementById('registerBtn');

// Global reCAPTCHA flag
window.captchaVerified = false;

function checkFormValidity() {
    const pwd = passwordInput.value;
    const confirm = confirmInput.value;

    if (pwd && confirm && pwd === confirm && window.captchaVerified) {
        confirmMsg.style.display = 'none';
        registerBtn.disabled = false;
    } else {
        if (pwd !== confirm && confirm.length > 0) {
            confirmMsg.style.display = 'block';
        } else {
            confirmMsg.style.display = 'none';
        }
        registerBtn.disabled = true;
    }
}

// Attach event listeners
passwordInput.addEventListener('input', checkFormValidity);
confirmInput.addEventListener('input', checkFormValidity);

// Re-run on reCAPTCHA success
function onCaptchaSuccess() {
    window.captchaVerified = true;
    document.getElementById('recaptcha-msg').style.display = 'none';
    checkFormValidity();
}

function onCaptchaExpired() {
    window.captchaVerified = false;
    registerBtn.disabled = true;
}
</script>

<script>
function showForm(formId) {
    document.getElementById('login-form').classList.remove('active');
    document.getElementById('register-form').classList.remove('active');
    document.getElementById(formId).classList.add('active');
}
</script>

</body>
</html>
