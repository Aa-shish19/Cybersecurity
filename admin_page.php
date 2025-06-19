<?php 
session_start();
require_once 'config.php';
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

//  Total Users Query
$userCountResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM user");
$userCount = mysqli_fetch_assoc($userCountResult)['total'];

//  Active Sessions Query (within last 10 minutes)
$activeQuery = "SELECT COUNT(DISTINCT user_email) AS active_users 
                FROM logs 
                WHERE event_type = 'login_success' 
                AND event_time > NOW() - INTERVAL 10 MINUTE";
$activeResult = mysqli_query($conn, $activeQuery);
$activeUsers = mysqli_fetch_assoc($activeResult)['active_users'];

// Login activity for chart (last 7 days)
$loginData = [];
$loginLabels = [];

$loginQuery = "SELECT DATE(event_time) AS day, COUNT(*) AS logins
               FROM logs
               WHERE event_type = 'login_success'
               GROUP BY day
               ORDER BY day DESC
               LIMIT 7";

$loginResult = $conn->query($loginQuery);

while ($row = $loginResult->fetch_assoc()) {
    $loginLabels[] = $row['day'];
    $loginData[] = $row['logins'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - AASHISH</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<style>
    body {
        display: block !important;
        align-items: initial !important;
        justify-content: initial !important;
    }
</style>

<!--  Top Navbar -->
    <nav class="navbar navbar-expand-lg px-4" style="margin-left: 220px; background: linear-gradient(135deg, #0f051d, #1a103d);">
        <div class="ms-auto d-flex align-items-center">
            <input class="form-control me-2" type="search" placeholder="Search">
        </div>
    </nav>

<!--  Sidebar + Content -->
<div class="d-flex" style="min-height: 100vh;">
    <!-- Sidebar -->
    <div class="bg-dark text-white p-3 position-fixed" style="width: 240px; height: 100vh; top: 0; left: 0;">
        <div class="text-center mb-3">
            <img src="image/Aashish logo.png" alt="Logo" width="140" height="140" style="border-radius: 10px;">
        </div>
        <h5 class="mb-4">Menu</h5>
        <ul class="nav flex-column">
            <li class="nav-item"><a href="admin_page.php" class="nav-link text-white">Dashboard</a></li>
            <li class="nav-item"><a href="users.php" class="nav-link text-white">Users</a></li>
            <li class="nav-item"><a href="logs.php" class="nav-link text-white">Logs</a></li>
            <li class="nav-item">
                <a class="nav-link text-white" data-bs-toggle="collapse" href="#settingsMenu" role="button" aria-expanded="false" aria-controls="settingsMenu">
                    Settings
                </a>
                <div class="collapse" id="settingsMenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item"><a href="change_password.php" class="nav-link text-white">Change Password</a></li>
                        <li class="nav-item"><a href="logout.php" class="nav-link text-white">Logout</a></li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>

    

    <!-- Main Content -->
    <div class="p-4" style="margin-left: 240px; flex-grow: 1;">
        <!-- Login success message -->
        <?php
        if (isset($_SESSION['login_success'])) {
            echo "<p class='alert alert-success' id='login-success'>{$_SESSION['login_success']}</p>";
            unset($_SESSION['login_success']);
        }
        ?>

        <h2 class="mb-4">Welcome, <?= htmlspecialchars(ucfirst($_SESSION['name'] ?? 'User')) ?></h2>
        <p>This is an <strong>admin dashboard</strong> for monitoring and management.</p>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Total Users</h5>
                        <p class="card-text"><?= $userCount ?></p> <!--  Dynamic total -->
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Active Sessions</h5>
                        <p class="card-text"><?= $activeUsers ?></p> <!--  Dynamic active -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">User Login Activity</h5>
                <canvas id="loginChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('loginChart').getContext('2d');
const loginChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_reverse($loginLabels)) ?>,
        datasets: [{
            label: 'Logins',
            data: <?= json_encode(array_reverse($loginData)) ?>,
            borderWidth: 2,
            borderColor: 'rgba(54, 162, 235, 1)',     // Line color
            backgroundColor: 'rgba(54, 162, 235, 0.2)', // Area under line
            fill: true,
            tension: 0.4 // Smooth curves
        }]
    },
    options: {
        responsive: true,
        scales: {
            x: {
                title: {
                    display: true,
                    text: 'Date'
                },
                grid: {
                    display: true
                }
            },
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Login Count'
                },
                grid: {
                    display: true
                }
            }
        },
        plugins: {
            legend: {
                display: true,
                labels: {
                    color: '#000',
                    font: {
                        size: 14
                    }
                }
            },
            tooltip: {
                mode: 'index',
                intersect: false
            }
        }
    }
});

const successMsg = document.getElementById('login-success');
if (successMsg) {
    setTimeout(() => {
        successMsg.style.opacity = '0';
        successMsg.style.transition = 'opacity 0.6s ease';
        setTimeout(() => successMsg.remove(), 600);
    }, 3000);
}
</script>

</body>
</html>
