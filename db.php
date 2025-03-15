<?php
$servername = "127.0.0.1";
$username = "root";
$password = "";
$database = "itisdev";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
