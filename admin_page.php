<?php 

session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Page</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <div class="box">

        <?php
            // ✅ Show login success message if set
            if (isset($_SESSION['login_success'])) {
                echo "<p class='success-message' id='login-success'>{$_SESSION['login_success']}</p>";
                unset($_SESSION['login_success']);
            }
            ?>

        <h1>Welcome,<span></span></h1>
        <p>This is an <span>admin</span> page</p>

        <div class="button-group">
        <button onclick="window.location.href='change_password.php'">Change Password</button>
        <button onclick="window.location.href='logout.php'">Logout</button>
        </div>

     </div>

     <script>
        const successMsg = document.getElementById('login-success');
        if (successMsg) {
            setTimeout(() => {
                successMsg.style.opacity = '0';
                successMsg.style.transition = 'opacity 0.6s ease';
                setTimeout(() => successMsg.remove(), 600);
            }, 3000); // Hide after 3 seconds
        }
     </script>

</body>
</html>