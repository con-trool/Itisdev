<!DOCTYPE html>
<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$servername = "127.0.0.1";
$username = "root";
$password = "";
$database = "itisdev";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    $error_message = "Database connection failed: " . $conn->connect_error;
}

// Initialize an error message variable
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['password'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error_message = "Please fill in both fields.";
    } else {
        // Fetch user password from database
        $stmt = $conn->prepare("SELECT password FROM account WHERE email = ?");
        if (!$stmt) {
            $error_message = "SQL Error: " . $conn->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if ($password === $row['password']) { // Direct comparison (no hashing)
                    $_SESSION['user'] = $email;
                    header("Location: dashboard.html");
                    exit;
                } else {
                    $error_message = "Incorrect password.";
                }
            } else {
                $error_message = "Email not found.";
            }

            $stmt->close();
        }
    }

    $conn->close();
}
?>


<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Log In</title>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/5.0.0/normalize.min.css">
  <link rel='stylesheet' href='https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&amp;display=swap'>
  <link rel="stylesheet" href="./style.css">
 
 <style>

    * {
      font-family: "Poppins";
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background-image: url('html/GeneralIMG/StandardBG.png');
      background-size: cover;
      background-repeat: no-repeat;
      background-attachment: fixed;
      background-position: center;
    }

    .screen-1 {
      width: 90%;
      max-width: 400px;
      background: #f1f7fe;
      padding: 2em 2em 7em;
      border-radius: 30px;
      box-shadow: 0 0 2em #e6e9f9;
      gap: 2em;
      position: fixed;
    }

    .logo {
      display: block;
      margin: 0 auto;
      width: 100%;
      max-width: 200px;
      margin-top: 1em;
    }

    .email,
    .password,
    .form-group {
      margin-top: 2em;
      margin-bottom: 1.5em;
    }

    label {
      font-weight: 500;
      color: #101827;
    }

    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 0.5em;
      border: 1px solid #ddd;
      border-radius: 5px;
      margin-top: 0.5em;
      font-size: 1em;
      box-shadow: none;
      outline: none;
    }

    .form-group input[type="checkbox"] {
      margin-right: 0.5em;
    }

    .container form h1 {
      font-size: 1.2em;
      color: #101827;
      margin-bottom: 0.5em;
    }

    .container label {
      display: block;
      cursor: pointer;
      margin-bottom: 0.5em;
    }

    .container label input {
      position: absolute;
      left: -9999px;
    }

    .container label span {
      display: flex;
      align-items: center;
      padding: 0.375em 0.75em;
      border-radius: 20px;
      transition: background-color 0.25s ease;
      color: #101827;
      cursor: pointer;
    }

    .container label input:checked + span {
      background-color: #6db282;
      color: white;
    }

    .login {
      margin-top: 50px;
      width: 100%;
      padding: 0.75em;
      background: #101827;
      color: white;
      border: none;
      border-radius: 30px;
      font-weight: 600;
      cursor: pointer;
    }

    .footer {
      text-align: center;
      font-size: 0.8em;
      color: #101827;
      margin-top: 1em;
    }

    .footer a {
      text-decoration: none;
      color: #101827;
    }
    
    .password-container {
      position: relative;
      display: flex;
      align-items: center;
    }
    
    .password-container input {
      width: 100%;
      padding-right: 2.5em;
    }

    .error-message {
      color: red;
      font-size: 0.9em;
      margin-top: 0.5em;
      display: none;
    }

    .toggle-password {
      position: absolute;
      right: 0.5em;
      cursor: pointer;
    }

    .toggle-password-active {
      padding: 0.5em;
      border: 1px solid #ddd;
      border-radius: 5px;
      margin-top: 0.5em;
      font-size: 1em;
      box-shadow: none;
      outline: none;
    }
  </style>
</head>
<>

    <div class="screen-1">
        <div style="text-align: center;">
            <b>Felco Inventory Dashboard</b>
        </div> 
        
        <!-- Error Message Display -->
        <?php if (!empty($error_message)) : ?>
            <div style="color: red; background: #ffe0e0; padding: 10px; margin: 10px 0; text-align: center; border-radius: 5px;">
                <?= $error_message; ?>
            </div>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <div class="email">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="username@dlsu.edu.ph" required />
            </div>
            <div class="password">
                <label for="password">Password</label>
                <div class="password-container">
                    <input class="pas" type="password" id="password" name="password" placeholder="············" required/>
                </div>
            </div>
            <button type="submit" class="login">Login</button>
        </form>
    </div>


  <script>
  document.getElementById('login').addEventListener('click', function(event) {
        event.preventDefault();
        
        var email = document.getElementById('email').value.trim();
        var password = document.getElementById('password').value.trim();
        var errorMessage = document.getElementById('error-message');

        errorMessage.style.display = "none";
        errorMessage.innerText = "";

        if (email === "" || password === "") {
            errorMessage.innerText = "Please fill in both fields.";
            errorMessage.style.display = "block";
            return;
        }

        var formData = new FormData();
        formData.append('email', email);
        formData.append('password', password);

        fetch('index.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text()) // Get raw text first
        .then(text => {
            console.log("Raw response:", text); // Debugging: See actual response
            return JSON.parse(text);  // Try parsing JSON
        })
        .then(data => {
            console.log("Parsed response:", data);
            if (data.status === "success") {
                window.location.href = 'dashboard.html';
            } else {
                errorMessage.innerText = data.message || "Invalid email or password.";
                errorMessage.style.display = "block";
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorMessage.innerText = "An error occurred. Please try again later.";
            errorMessage.style.display = "block";
        });
    });
  </script>
</body>
</html>
