<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "itisdev"; 

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["message" => "Database Connection Failed: " . $conn->connect_error]));
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $stocks = $_POST["stocks"];
    $criticalQty = $_POST["criticalQty"];
    $price = $_POST["price"];

    // Handle file upload
    if (isset($_FILES["picture"]) && $_FILES["picture"]["error"] == 0) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true); // Create uploads directory if not exists
        }

        $fileName = time() . "_" . basename($_FILES["picture"]["name"]); // Unique file name
        $targetFilePath = $targetDir . $fileName;
        
        if (move_uploaded_file($_FILES["picture"]["tmp_name"], $targetFilePath)) {
            $picturePath = $targetFilePath;
        } else {
            die(json_encode(["message" => "Failed to upload picture."]));
        }
    } else {
        $picturePath = null;
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO product (name, stocks, criticalQty, price, picture) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("siiis", $name, $stocks, $criticalQty, $price, $picturePath);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Product added successfully!"]);
    } else {
        echo json_encode(["message" => "Error: " . $stmt->error]);
    }

    $stmt->close();
}

$conn->close();
?>
