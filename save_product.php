<?php
session_start(); // Start session to access user data

$servername = "localhost";
$username = "root";
$password = "";
$database = "itisdev"; 

header('Content-Type: application/json');

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database Connection Failed: " . $conn->connect_error]);
    exit;
}

// Get current user ID from session
$userEmail = $_SESSION['user'] ?? null;
if (!$userEmail) {
    echo json_encode(["success" => false, "message" => "User not logged in."]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM account WHERE email = ?");
$stmt->bind_param("s", $userEmail);
$stmt->execute();
$stmt->bind_result($userId);
$stmt->fetch();
$stmt->close();

if (!$userId) {
    echo json_encode(["success" => false, "message" => "User ID not found."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $stocks = $_POST["stocks"];
    $criticalQty = $_POST["criticalQty"];
    $price = $_POST["price"];
    $timestamp = date('Y-m-d H:i:s');

    // Handle file upload
    if (isset($_FILES["picture"]) && $_FILES["picture"]["error"] == 0) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES["picture"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        
        if (move_uploaded_file($_FILES["picture"]["tmp_name"], $targetFilePath)) {
            $picturePath = $targetFilePath;
        } else {
            echo json_encode(["success" => false, "message" => "Failed to upload picture."]);
            exit;
        }
    } else {
        $picturePath = null;
    }

    // Insert into product table
    $stmt = $conn->prepare("INSERT INTO product (name, stocks, criticalQty, price, picture) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("siiis", $name, $stocks, $criticalQty, $price, $picturePath);

    if ($stmt->execute()) {
        $productId = $stmt->insert_id;
        $stmt->close();

        // Insert into logs
        $description = "Added new product: $name";
        $stmt = $conn->prepare("INSERT INTO logs (userID, productID, description, datetime) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $userId, $productId, $description, $timestamp);
        $stmt->execute();
        $stmt->close();

        echo json_encode(["success" => true, "message" => "Product added successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: " . $stmt->error]);
        $stmt->close();
    }
}

$conn->close();
?>
