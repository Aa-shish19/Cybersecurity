<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['email'];
$name = htmlspecialchars(ucfirst($_SESSION['name'] ?? 'User'));
$role = htmlspecialchars($_SESSION['role'] ?? 'Standard User');
$ip = ($_SERVER['REMOTE_ADDR'] === '::1') ? '127.0.0.1 (Localhost)' : $_SERVER['REMOTE_ADDR'];

mysqli_query($conn, "UPDATE user SET last_seen = NOW() WHERE email = '" . mysqli_real_escape_string($conn, $email) . "'");

// Simulated breach check
$breachData = [];
if (file_exists("mock_breach_data.json")) {
    $json = file_get_contents("mock_breach_data.json");
    $breachData = json_decode($json, true);
}
$emailBreach = isset($breachData['compromised_emails']) && in_array($email, $breachData['compromised_emails']);
$ipBreach = isset($breachData['breached_ips']) && in_array($_SERVER["REMOTE_ADDR"], $breachData["breached_ips"]);
$accountStatus = ($emailBreach || $ipBreach) ? "Warning" : "Good Standing";

$securityScore = 85;
$passwordStrength = "Strong";
$has2FA = true;
$recentActivity = ["Password changed", "Login from Nepal"];
$alerts = [
    ["type" => "High", "msg" => "Multiple failed login attempts detected"],
    ["type" => "Medium", "msg" => "Login from unrecognized device"],
    ["type" => "Low", "msg" => "Password updated recently"]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | CyberVault</title>
    <style>
        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: Poppins, sans-serif;
            background: #0f051d;
            color: #f1f5f9;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 240px;
            height: 100vh;
            background: #1a162f;
            padding: 20px;
            box-sizing: border-box;
        }

        .sidebar img {
            height: 120px;
            display: block;
            margin: 0 auto 15px auto;
        }

        .sidebar h2 {
            font-size: 18px;
            color: #7f9cf5;
        }

        .sidebar nav a {
            display: block;
            color: #ccc;
            margin: 10px 0;
            text-decoration: none;
        }

        .sidebar nav a:hover {
            color: #7f9cf5;
        }

        .submenu {
            display: none;
            margin-left: 15px;
            margin-top: 5px;
        }

        .submenu a {
            font-size: 14px;
        }

        .main {
            margin-left: 260px;
            padding: 20px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #1e1a2e;
            padding: 12px 20px;
            border-radius: 8px;
        }

        .card {
            background: #1e293b;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .alert {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .alert.High { background-color: #7f1d1d; }
        .alert.Medium { background-color: #92400e; }
        .alert.Low { background-color: #166534; }
        .alert span { font-weight: bold; }
        ul { padding-left: 20px; }

        .success-message {
            background: #2d3748;
            padding: 10px;
            margin-top: 10px;
            border-radius: 6px;
            color: #a3f7bf;
        }

    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <img src="image/Aashish%20logo.png" alt="Logo">
    <h2>CyberVault</h2>
    <nav>
        <a href="user_page.php">Dashboard</a>
        <a href="#alerts">Alerts</a>
        <a href="#devices">Devices</a>
        <a href="javascript:void(0)" onclick="toggleSettings()">Settings ▸</a>
        <div id="settings-submenu" class="submenu">
            <a href="change_password.php">Change Password</a>
            <a href="javascript:void(0)" onclick="alert('2FA setup coming soon')">Enable 2FA</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>
</div>

<!-- Main Content -->
<div class="main">
    <div class="top-bar">
        <div>Welcome, <?= $name ?> (<?= $role ?>)</div>
        <div><span id="clock"></span> | IP: <?= $ip ?></div>
    </div>

    <?php if (isset($_SESSION['login_success'])): ?>
        <div class="success-message" id="login-success"><?= $_SESSION['login_success']; ?></div>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const msg = document.getElementById('login-success');
                if (msg) setTimeout(() => msg.remove(), 1000);
            });
        </script>
        <?php unset($_SESSION['login_success']); ?>
    <?php endif; ?>

    <div class="card">
        <h3>🔐 Security Overview</h3>
        <p><strong>Score:</strong> <?= $securityScore ?>/100</p>
        <p><strong>Password Strength:</strong> <?= $passwordStrength ?></p>
        <p><strong>2FA:</strong> <?= $has2FA ? "Enabled" : "Disabled" ?></p>
        <p><strong>Last Login IP:</strong> <?= $ip ?></p>
        <p><strong>Breach Status:</strong> <?= ($emailBreach || $ipBreach) ? "⚠️ Potential Exposure" : "✅ Safe" ?></p>
        <p><strong>Account Status:</strong> <?= $accountStatus ?></p>
    </div>

    <div class="card">
        <h3>📋 Recent Activity</h3>
        <ul>
            <?php foreach ($recentActivity as $act): ?>
                <li><?= $act ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="card" id="alerts">
        <h3>🚨 Active Security Alerts</h3>
        <?php foreach ($alerts as $a): ?>
            <div class="alert <?= $a['type'] ?>">
                <span>[<?= $a['type'] ?>]</span> <?= $a['msg'] ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card" id="devices">
        <h3>🖥️ Logged-in Devices</h3>
        <ul>
            <li>Chrome (Windows 11)</li>
        </ul>
    </div>

</div>

<!-- JavaScript -->
<script>
    function updateClock() {
        const now = new Date();
        document.getElementById('clock').textContent = now.toLocaleTimeString();
    }
    setInterval(updateClock, 1000);
    updateClock();

    function toggleSettings() {
        const menu = document.getElementById("settings-submenu");
        menu.style.display = (menu.style.display === "block") ? "none" : "block";
    }
</script>

</body>
</html>
