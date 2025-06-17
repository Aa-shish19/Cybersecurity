<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="style.css">
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
        

    </style>
</head>
<body>

<!--  Background Video -->
<video autoplay muted loop id="background-video">
    <source src="video.mp4" type="video/mp4">
    Your browser does not support the video tag.
</video>

<!--  Form Container -->
<div class="container">
    <div class="inside">
        <h2>Forgot Password</h2>
        <form method="POST" action="send_otp.php">
            <input type="email" name="email" placeholder="Enter your registered email" required>
            <button type="submit" name="send_otp">Send OTP</button>
        </form>
    </div>
</div>

</body>
</html>
