<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to the database
$servername = "localhost"; // Change this if needed
$username = "root"; // Change this to your actual DB username
$password = ""; // Change this to your actual DB password
$database = "itisdev"; // Change this to your actual DB name

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Validate password match
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!');</script>";
    } else {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Insert into database
        $stmt = $conn->prepare("INSERT INTO account (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $first_name, $last_name, $email, $hashed_password);

        if ($stmt->execute()) {
            echo "<script>alert('Registration successful!'); window.location='index.php';</script>";
        } else {
            echo "<script>alert('Error: " . $stmt->error . "');</script>";
        }

        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/5.0.0/normalize.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap">
    <style>
        * { font-family: "Poppins"; box-sizing: border-box; }
        body {
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background: url('bg.png') no-repeat center center fixed;
        background-size: cover;
        position: relative;
        }

        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: inherit;
            filter: blur(10px);
            z-index: -1;
        }
        .screen-1 {
            width: 90%;
            max-width: 400px;
            background: #f1f7fe;
            padding: 1.5em 2em;
            border-radius: 20px;
            box-shadow: 0 0 1.5em #e6e9f9;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .logo {
            display: block;
            margin: 0 auto;
            width: 100%;
            max-width: 200px;
        }
        label { font-weight: 500; color: #101827; }
        input {
            width: 100%;
            padding: 0.5em;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-top: 0.5em;
            font-size: 1em;
        }
        .register {
            margin-top: 20px;
            width: 100%;
            padding: 0.75em;
            background: #101827;
            color: white;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            opacity: 0.5;
            pointer-events: none;
        }
        .footer {
            text-align: center;
            font-size: 0.8em;
            margin-top: 1em;
        }
        .footer a {
            text-decoration: none;
            color: #101827;
        }
        .name-container {
            display: flex;
            gap: 10px;
        }
        .name-container div {
            flex: 1;
        }
        small { color: red; display: none; }
    </style>
</head>
<body>
    <div class="screen-1">
        <div style="text-align: center;">
            <img src="logo.png" alt="FELCO Logo" class="logo">
            <br>
            <b>Register</b><br>
        </div>

        <form action="" method="POST" oninput="validateForm()">
            <div class="name-container">
                <div>
                <br><label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required />
                </div>
                <div>
                <br><label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required />
                </div>
            </div>
            <div>
            <br><label for="email">Email Address</label>
                <input type="email" id="email" name="email" required />
            </div>
            <div>
                <br><label for="password">Password</label>
                <input type="password" id="password" name="password" required />
            </div>
            <div>
                <br><label for="confirm_password">Re-enter Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required />
                <small id="passwordMismatch">Passwords do not match!</small>
            </div>
            <button type="submit" class="register" id="registerButton">Register</button>
        </form>
        <div class="footer">
            <a href="index.php">Already have an account? <b>Log in</b></a>
        </div>
    </div>
    <script>
    function validateForm() {
        var firstName = document.getElementById("first_name").value.trim();
        var lastName = document.getElementById("last_name").value.trim();
        var email = document.getElementById("email").value.trim();
        var password = document.getElementById("password").value;
        var confirmPassword = document.getElementById("confirm_password").value;
        var message = document.getElementById("passwordMismatch");
        var registerButton = document.getElementById("registerButton");
    
        if (password !== confirmPassword && confirmPassword !== "") {
            message.style.display = "block";
        } else {
            message.style.display = "none";
        }
    
        if (firstName && lastName && email && password && confirmPassword && password === confirmPassword) {
            registerButton.style.opacity = "1";
            registerButton.style.pointerEvents = "auto";
        } else {
            registerButton.style.opacity = "0.5";
            registerButton.style.pointerEvents = "none";
        }
    }
    </script>
</body>
</html>
