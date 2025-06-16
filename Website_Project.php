<?php
session_start();
require_once 'config.php';


// ========================= REGISTER =========================
if (isset($_POST['register'])) {
    $recaptchaSecret = "6LeogForAAAAAJSJXc_FiWjT2ogONySKcXq9QiOB";
    $recaptchaResponse = $_POST['g-recaptcha-response'];

    $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$recaptchaResponse}");
    $response = json_decode($verify);

    if (!$response->success) {
        $_SESSION['register_error'] = "❌ Please verify that you're not a robot.";
        $_SESSION['active_form'] = 'register';
        header("Location: index.php");
        exit();
    }

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = $conn->prepare("SELECT email FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['register_error'] = 'Email is already registered!';
        $_SESSION['active_form'] = 'register';
    } else {
        $stmt = $conn->prepare("INSERT INTO user (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $password, $role);
        if ($stmt->execute()) {
            $_SESSION['register_success'] = '✅ Registration successful! Please login.';
            $_SESSION['active_form'] = 'login';
        } else {
            $_SESSION['register_error'] = 'Error during registration.';
            $_SESSION['active_form'] = 'register';
        }
    }

    header("Location: index.php");
    exit();
}

// ========================= LOGIN =========================
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Set defaults
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt_time'] = null;
    }

    if ($_SESSION['login_attempts'] >= 3) {
        $block_time = 5 * 60; // 5 minutes in seconds
        $time_since_last = time() - $_SESSION['last_attempt_time'];

        if ($time_since_last < $block_time) {
            $remaining = $block_time - $time_since_last;
            $_SESSION['remaining_time'] = $remaining;
            $_SESSION['login_error'] = "Too many failed attempts. Try again in " . ceil($remaining / 60) . " minute(s).";
            $_SESSION['active_form'] = 'login';
            header("Location: index.php");
            exit();
        } else {
            // Reset if time passed
            $_SESSION['login_attempts'] = 0;
            $_SESSION['last_attempt_time'] = null;
            unset($_SESSION['remaining_time']);
        }
    }
    
    $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            $_SESSION['login_attempts'] = 0;
            $_SESSION['last_attempt_time'] = null;

            
            $_SESSION['user_id'] = $user['Id'];
            $_SESSION['name'] = $user['Name'];
            $_SESSION['email'] = $user['Email'];
            $_SESSION['role'] = $user['Role'];
            $_SESSION['login_success'] = '✅ Login successful! Welcome back.';

            if ($user['role'] === 'admin') {
                header("Location: admin_page.php");
            } else {
                header("Location: user_page.php");
            }
            exit();
        }
    }

    // If login fails (wrong password or user not found)
    $_SESSION['login_attempts'] += 1;
    $_SESSION['last_attempt_time'] = time();
    $_SESSION['login_error'] = "Incorrect email or password.";
    $_SESSION['active_form'] = 'login';
    header("Location: index.php");
    exit();
   
}
?>
