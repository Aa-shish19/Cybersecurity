<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch logs
$sql = "SELECT * FROM logs ORDER BY event_time DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logs - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="dashboard-body">

<!-- Optional: Include your top navbar and sidebar here if needed -->

<div class="container mt-5">
    <h3 class="mb-4 text-white">System Logs</h3>
    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white text-dark">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>User Email</th>
                    <th>Event Type</th>
                    <th>Details</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($log = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $log['id'] ?></td>
                            <td><?= htmlspecialchars($log['user_email']) ?></td>
                            <td><span class="badge bg-secondary"><?= $log['event_type'] ?></span></td>
                            <td><?= htmlspecialchars($log['details']) ?></td>
                            <td><?= $log['event_time'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center">No logs found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
