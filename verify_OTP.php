<?php
session_start();

// Handle OTP form submission
if (isset($_POST['verify_otp'])) {
    $enteredOtp = $_POST['otp'];
    $correctOtp = $_SESSION['otp'] ?? null;
    $expiryTime = $_SESSION['otp_expiry'] ?? 0;

    if (time() > $expiryTime) {
        $error = "❌ OTP has expired. Please request a new one.";
        session_unset(); // clear expired OTP
    } elseif ($enteredOtp == $correctOtp) {
        $_SESSION['otp_verified'] = true;
        header("Location: reset_password.php"); // ✅ redirect to next step
        exit();
    } else {
        $error = "❌ Invalid OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP</title>
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
    
    <form method="POST" action="verify_OTP.php">
        <div class="otp-card">
            <p id="countdown" style="color:#162D8A; font-weight: 500;">OTP expires in <span id="timer">60</span> seconds</p>
            <h2>Enter OTP</h2>
            <?php if (isset($error)) echo "<p class='error-message'>$error</p>"; ?>
            <div class="otp-input-wrapper">
                <input type="text" maxlength="1" class="otp-input" name="otp1" required>
                <input type="text" maxlength="1" class="otp-input" name="otp2" required>
                <input type="text" maxlength="1" class="otp-input" name="otp3" required>
                <input type="text" maxlength="1" class="otp-input" name="otp4" required>
                <input type="text" maxlength="1" class="otp-input" name="otp5" required>
                <input type="text" maxlength="1" class="otp-input" name="otp6" required>
            </div>
            <input type="hidden" name="otp" id="otp">
            <button type="submit" name="verify_otp" class="verify-btn">VERIFY OTP</button>
            <p id="resend-msg" style="margin-top: 15px; display: none;">
                Didn’t receive the OTP? 
                <a href="send_OTP.php" style="color:#162D8A; font-weight: 500;">Resend OTP</a>
            </p>
        </div>
    </form>

    <script>
    const boxes = document.querySelectorAll(".otp-input"); // ✅ correct class
    const hiddenOtp = document.getElementById("otp");

    boxes.forEach((box, i) => {
    box.addEventListener("input", () => {
        if (box.value.length === 1 && i < boxes.length - 1) {
        boxes[i + 1].focus();
        }

        // Update hidden input value
        hiddenOtp.value = Array.from(boxes).map(b => b.value).join("");
    });

    box.addEventListener("keydown", (e) => {
        if (e.key === "Backspace" && box.value === "" && i > 0) {
        boxes[i - 1].focus();
        }
    });
    });

    // Countdown Timer
    let seconds = 60;
    const timerDisplay = document.getElementById("timer");

    const countdown = setInterval(() => {
        seconds--;
        timerDisplay.textContent = seconds;

        if (seconds <= 0) {
            clearInterval(countdown);
            timerDisplay.textContent = "expired";
            document.querySelector(".verify-btn").disabled = true;
            document.querySelector(".verify-btn").style.opacity = "0.6";
            document.querySelector(".verify-btn").innerText = "OTP Expired";
            document.getElementById("resend-msg").style.display = "block";

        }
    }, 1000);

    boxes[0].focus();
    </script>

    <video autoplay muted loop id="background-video">
        <source src="video.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>


</body>
</html>
