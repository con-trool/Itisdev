<?php
session_start();

/************************************************
 * Database Connection & Edit/Delete Handlers
 ************************************************/
$servername = "localhost";
$username = "root";
$password = "";
$database = "itisdev";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$userEmail = $_SESSION['user']; 

// Retrieve user details based on session email
$stmt = $conn->prepare("SELECT id, first_name, last_name FROM account WHERE email = ?");
$stmt->bind_param("s", $userEmail);
$stmt->execute();
$stmt->bind_result($userId, $firstName, $lastName);
$stmt->fetch();
$stmt->close();

if (!$userId) {
  die("User not found.");
}

$timestamp = date('Y-m-d H:i:s');

// Extract initials
$firstInitial = strtoupper(substr($firstName, 0, 1));
$lastInitial = strtoupper(substr($lastName, 0, 1));
$displayName = $firstName . " " . $lastInitial . ".";

// Handle deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_product_id'])) {
  $deleteId = intval($_POST['delete_product_id']);

  // Get product name before deletion for logging
  $stmt = $conn->prepare("SELECT name FROM product WHERE id = ?");
  $stmt->bind_param("i", $deleteId);
  $stmt->execute();
  $stmt->bind_result($deletedProductName);
  $stmt->fetch();
  $stmt->close();

  $stmt = $conn->prepare("DELETE FROM product WHERE id = ?");
  $stmt->bind_param("i", $deleteId);
  $stmt->execute();
  $stmt->close();

  // Log deletion
  $description = "Deleted product: $deletedProductName";
  $productId = null; // Create a variable instead of using literal null
  $stmt = $conn->prepare("INSERT INTO logs (userID, productID, description, datetime) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("iiss", $userId, $productId, $description, $timestamp);
  $stmt->execute();
  $stmt->close();

  header("Location: products.php?msg=deleted");
  exit();
}

// Stock In
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['stock_in_product_id'])) {
  $productId = intval($_POST['stock_in_product_id']);
  $stockInQty = intval($_POST['stock_in_qty']);

  $stmt = $conn->prepare("UPDATE product SET stocks = stocks + ? WHERE id = ?");
  $stmt->bind_param("ii", $stockInQty, $productId);
  $stmt->execute();
  $stmt->close();

  // Log stock in
  $description = "Stocked in $stockInQty units.";
  $stmt = $conn->prepare("INSERT INTO logs (userID, productID, description, datetime) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("iiss", $userId, $productId, $description, $timestamp);
  $stmt->execute();
  $stmt->close();

  header("Location: products.php?msg=stock_in");
  exit();
}

// Stock Out
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['stock_out_product_id'])) {
  $productId = intval($_POST['stock_out_product_id']);
  $stockOutQty = intval($_POST['stock_out_qty']);

  $stmt = $conn->prepare("UPDATE product SET stocks = stocks - ?, sales = sales + ? WHERE id = ?");
  $stmt->bind_param("iii", $stockOutQty, $stockOutQty, $productId);
  $stmt->execute();
  $stmt->close();

  // Log stock out
  $description = "Stocked out $stockOutQty units.";
  $stmt = $conn->prepare("INSERT INTO logs (userID, productID, description, datetime) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("iiss", $userId, $productId, $description, $timestamp);
  $stmt->execute();
  $stmt->close();

  header("Location: products.php?msg=stock_out");
  exit();
}

// Stock Adjustment
if (isset($_POST['stock_adjust_product_id'])) {
  $productId = intval($_POST['stock_adjust_product_id']);
  $newStock = intval($_POST['stock_adjust_qty']);
  $reason = $_POST['adjust_reason'];

  $stmt = $conn->prepare("UPDATE product SET stocks = ? WHERE id = ?");
  $stmt->bind_param("ii", $newStock, $productId);
  $stmt->execute();
  $stmt->close();

  // Log adjustment
  $stmt = $conn->prepare("INSERT INTO logs (userID, productID, description, datetime) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("iiss", $userId, $productId, $reason, $timestamp);
  $stmt->execute();
  $stmt->close();

  header("Location: products.php?msg=stock_adjusted");
  exit();
}

// Edit Product
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_product_id'])) {
  $editId = intval($_POST['edit_product_id']);
  $editName = $_POST['edit_name'];
  $editStatus = $_POST['edit_status'];
  $editCriticalQty = intval($_POST['edit_criticalQty']);
  $editPrice = floatval($_POST['edit_price']);
  $oldPicture = $_POST['old_picture'];
  $newPicturePath = $oldPicture;

  if (isset($_FILES['edit_picture']) && $_FILES['edit_picture']['error'] === 0) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
      mkdir($targetDir, 0777, true);
    }
    $fileName = time() . "_" . basename($_FILES["edit_picture"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    if (move_uploaded_file($_FILES["edit_picture"]["tmp_name"], $targetFilePath)) {
      $newPicturePath = $targetFilePath;
    }
  }

  $stmt = $conn->prepare("UPDATE product SET name = ?, status = ?, criticalQty = ?, price = ?, picture = ? WHERE id = ?");
  $stmt->bind_param("ssidsi", $editName, $editStatus, $editCriticalQty, $editPrice, $newPicturePath, $editId);
  $stmt->execute();
  $stmt->close();

  // Log product edit
  $description = "Edited product details: $editName";
  $stmt = $conn->prepare("INSERT INTO logs (userID, productID, description, datetime) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("iiss", $userId, $editId, $description, $timestamp);
  $stmt->execute();
  $stmt->close();

  header("Location: products.php?msg=edited");
  exit();
}

/************************************************
 * [FILTER FEATURE] Build SQL Query with Filters
 ************************************************/
$whereClauses = [];

// Search filter
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
  $searchTerm = $conn->real_escape_string($_GET['search']);
  $whereClauses[] = "LOWER(name) LIKE LOWER('%$searchTerm%')";
}

// Category filter
if (isset($_GET['category']) && $_GET['category'] !== 'all') {
  if ($_GET['category'] === 'critical') {
    $whereClauses[] = "stocks <= criticalQty";
  } elseif ($_GET['category'] === 'not_critical') {
    $whereClauses[] = "stocks > criticalQty";
  }
}

// Status filter
if (isset($_GET['status']) && $_GET['status'] !== 'all') {
  $statusVal = $conn->real_escape_string($_GET['status']);
  $whereClauses[] = "status = '$statusVal'";
}

$sql = "SELECT id, picture, name, status, sales, stocks, criticalQty, price FROM product";
if (count($whereClauses) > 0) {
  $sql .= " WHERE " . implode(" AND ", $whereClauses);
}
$result = $conn->query($sql);
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Felco Inventory Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/5.0.0/normalize.min.css">
  <link rel="stylesheet" href="./style.css">
  <style>
    .logout{  
  align-items: center;
  background-color: #ffc40c;
  border: 0;
  border-radius: 100px;
  box-sizing: border-box;
  color: #000;  
  cursor: pointer;
  display: inline-flex;
  font-family: -apple-system, system-ui, system-ui, "Segoe UI", Roboto, "Helvetica Neue", "Fira Sans", Ubuntu, Oxygen, "Oxygen Sans", Cantarell, "Droid Sans", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Lucida Grande", Helvetica, Arial, sans-serif;
  font-size: 16px;
  font-weight: 600;
  justify-content: center;
  line-height: 20px;
  max-width: 90px;
  min-height: 30px;
  min-width: 0px;
  overflow: hidden;
  padding: 0px;
  padding-left: 20px;
  padding-right: 20px;
  text-align: center;
  touch-action: manipulation;
  transition: background-color 0.167s cubic-bezier(0.4, 0, 0.2, 1) 0s, box-shadow 0.167s cubic-bezier(0.4, 0, 0.2, 1) 0s, color 0.167s cubic-bezier(0.4, 0, 0.2, 1) 0s;
  user-select: none;
  -webkit-user-select: none;
  align-self: center;
  margin-left: 40px;
  margin-bottom: 10px ;
}

.logout:hover,
.logout:focus { 
  background-color: #16437E;
  color: #ffffff;
}

.logout:active {
  background: #09223b;
  color: rgb(255, 255, 255, .7);
}

.logout:disabled { 
  cursor: not-allowed;
  background: rgba(0, 0, 0, .08);
  color: rgba(0, 0, 0, .3);
}
    
    .modal {
      display: none;
      position: fixed;
      z-index: 1;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
      background-color: white;
      margin: 10% auto;
      padding: 20px;
      border-radius: 8px;
      width: 30%;
      text-align: center;
    }

    .close {
      float: right;
      font-size: 20px;
      cursor: pointer;
    }

    input {
      margin: 5px;
      padding: 10px;
      width: 80%;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    table,
    th,
    td {
      border: 1px solid black;
    }

    th,
    td {
      padding: 10px;
      text-align: left;
    }

    th {
      background-color: #f4f4f4;
    }

    img {
      width: 300px;
      height: 300px;
      border-radius: 5px;
    }

    .modal-content form label {
      font-weight: bold;
      display: block;
      margin-top: 20px;
      margin-bottom: 6px;
      text-align: left;
      width: 80%;
      margin-left: auto;
      margin-right: auto;
    }

    .modal-content form input,
    .modal-content form select {
      margin-bottom: 10px;
      padding: 10px;
      width: 80%;
      display: block;
      margin-left: auto;
      margin-right: auto;
      box-sizing: border-box;
    }
    .table {
      display: flex;
      flex-direction: column;
      width: 100%;
      border-collapse: collapse;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }

    .table-header, .table-row {    
      display: flex;
      background-color: #f6f6f6;
    }
    .table-row{
      max-height: 110px;
    }
    .table-header {
      
      background-color: #ffc40c;
      color: black;
      font-weight: bold;
    }

    .header__item, .table-data {
      
      display: flex;
      flex: 1;
      padding: 12px 15px;
      text-align: center;
      border-bottom: 1px solid #ddd;
    }

      .table-data {
      padding: 12px 15px;
      text-align: center;
      border-bottom: 1px solid #ddd;
      background-color: white;
      white-space: nowrap; /* Prevent line wrap */
      overflow: auto;
    }

    .filter__link {
      color: inherit;
      text-decoration: none;
      cursor: pointer;
      
    }

    button {
      margin: 2px;
      padding: 4px 8px;
      font-size: 0.9em;
      cursor: pointer;
    }
  </style>
</head>

<body>
  <div class="app-container">
    <div class="sidebar">
      <div class="sidebar-header">
        <div class="app-icon">
        <img src="logo.png?v=1.0" alt="App Logo" style="width: 165px; height: 80px;"/>
        </div>
      </div>
      <ul class="sidebar-list">
        <li class="sidebar-list-item">
          <a href="dashboard.php">
            <!-- Home icon -->
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
              class="feather feather-home">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
              <polyline points="9 22 9 12 15 12 15 22" />
            </svg>
            <span>Home</span>
          </a>
        </li>
        <li class="sidebar-list-item active">
          <a href="products.php">
            <!-- Products icon -->
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
              class="feather feather-shopping-bag">
              <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
              <line x1="3" y1="6" x2="21" y2="6" />
              <path d="M16 10a4 4 0 0 1-8 0" />
            </svg>
            <span>Products</span>
          </a>
        </li>
        <li class="sidebar-list-item">
          <a href="logs.php">
            <!-- Logs icon -->
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
              class="feather feather-pie-chart">
              <path d="M21.21 15.89A10 10 0 1 1 8 2.83" />
              <path d="M22 12A10 10 0 0 0 12 2v10z" />
            </svg>
            <span>Logs</span>
          </a>
        </li>
      </ul>
      <div class="account-info">  
    <div class="account-info-name">
      <b><?php echo htmlspecialchars($displayName); ?></b>
    </div>
</div>
<div style="display: flex;
            flex-direction: row;   
            align-items: center;">
      <a href="logout.php" class="logout" style="text-decoration: none; color: inherit;">Logout</a>
    </div>
    </div>

    <div class="app-content">
      <div class="app-content-header">
        <h1 class="app-content-headerText">Products</h1>
        <button class="mode-switch" title="Switch Theme">
          
        </button>
        <button id="openModal" class="app-content-headerButton">Add Product</button>
      </div>

      <div class="app-content-actions">
        <!-- Search function -->
        <form method="GET" action="products.php" style="display: flex; align-items: center; gap: 10px;">
          <input
            class="search-bar"
            type="text"
            name="search"
            placeholder="Search by name..."
            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
          <button type="submit" class="action-button">Search</button>
        </form>

        <div class="app-content-actions-wrapper">
          <div class="filter-button-wrapper">
            <!-- Filter button -->
            <button class="action-button filter jsFilter">
              <span>Filter</span>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" class="feather feather-filter">
                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
              </svg>
            </button>
            <form method="GET" action="products.php" class="filter-menu">
              <label>Category</label>
              <select name="category">
                <option value="all" <?php if (isset($_GET['category']) && $_GET['category'] == 'all') echo 'selected'; ?>>All</option>
                <option value="critical" <?php if (isset($_GET['category']) && $_GET['category'] == 'critical') echo 'selected'; ?>>Critical</option>
                <option value="not_critical" <?php if (isset($_GET['category']) && $_GET['category'] == 'not_critical') echo 'selected'; ?>>Not Critical</option>
              </select>
              <label>Status</label>
              <select name="status">
                <option value="all" <?php if (isset($_GET['status']) && $_GET['status'] == 'all') echo 'selected'; ?>>All Status</option>
                <option value="active" <?php if (isset($_GET['status']) && $_GET['status'] == 'active') echo 'selected'; ?>>Active</option>
                <option value="disabled" <?php if (isset($_GET['status']) && $_GET['status'] == 'disabled') echo 'selected'; ?>>Disabled</option>
              </select>
              <div class="filter-menu-buttons">
                <a href="products.php" class="filter-button reset">Reset</a>
                <button type="submit" class="filter-button apply">Apply</button>
              </div>
            </form>
          </div>
         
        </div>
      </div>

      <div class="products-area-wrapper tableView">
        <div class="table">
        <div class="table-header">
        <div class="header__item">
        <a class="filter__link">Image</a>
      </div>
      <div class="header__item"> 
        <a class="filter__link" data-column="name">Name <span class="sort-icon">&#8597;</span></a>
      </div>
      <div class="header__item">
        <a class="filter__link" data-column="status">Status <span class="sort-icon">&#8597;</span></a>
      </div>
      <div class="header__item">
        <a class="filter__link" data-column="sales">Sales <span class="sort-icon">&#8597;</span></a>
      </div>
      <div class="header__item">
        <a class="filter__link" data-column="stocks">Stocks <span class="sort-icon">&#8597;</span></a>
      </div>
      <div class="header__item">
        <a class="filter__link">Critical Qty</a>
      </div>
      <div class="header__item">
        <a class="filter__link" data-column="price">Price <span class="sort-icon">&#8597;</span></a>
      </div>
      <div class="header__item" style="margin-right:230px;">
        <a class="filter__link">Actions</a>
        </div>
      </div>

      <div class="table-content">
  <?php
  if ($result->num_rows > 0) {
    // Start the table container outside of the loop
    echo "<div class='table'>"; 
    while ($row = $result->fetch_assoc()) {
      echo "<div class='table-row'>
              <div class='table-data'><img src='{$row['picture']}' alt='Product Image' style='max-width: 150px; height: auto;'></div>
              <div class='table-data'>{$row['name']}</div>
              <div class='table-data'>{$row['status']}</div>
              <div class='table-data'>{$row['sales']}</div>
              <div class='table-data'>{$row['stocks']}</div>
              <div class='table-data'>{$row['criticalQty']}</div>
              <div class='table-data'>₱" . number_format($row['price'], 2) . "</div>
              <form action='products.php' method='POST' style='display: flex; margin-bottom: 80px;' onsubmit=\"return confirm('Are you sure you want to delete this product?');\">
                <input type='hidden' name='delete_product_id' value='{$row['id']}'>
                <button type='submit' style='background-color:white;'>Delete</button>
              </form>
              <button type='button' style='display: flex; margin-bottom: 80px; background-color:white;' onclick=\"openEditModal(
                {$row['id']},
                '{$row['name']}',
                '{$row['status']}',
                {$row['criticalQty']},
                {$row['price']},
                '{$row['picture']}'
              )\">Edit</button>
              <button type='button' style='display: flex; margin-bottom: 80px; background-color:white;' onclick=\"openStockInModal({$row['id']}, '{$row['name']}', {$row['stocks']})\">Stock In</button>
              <button type='button' style='display: flex; margin-bottom: 80px; background-color:white;' onclick=\"openStockOutModal({$row['id']}, '{$row['name']}', {$row['stocks']})\">Stock Out</button>
              <button type='button' style='display: flex; margin-bottom: 80px; background-color:white;' onclick=\"openStockAdjustModal({$row['id']}, '{$row['name']}', {$row['stocks']})\">Stock Adjustment</button>
            </div>";
    }
    // Close the table container after the loop
    echo "</div>";
  } else {
    // If no results, still keep the table structure
    echo "<div class='table-row'><div class='table-data' colspan='8'>No products found</div></div>";
  }
  ?>
</div>

    </div>
  </div>

  <script src="./script.js"></script>

  <!-- Add Product Modal -->
  <div id="productModal" class="modal">
    <div class="modal-content">
      <span class="close" id="addClose">&times;</span>
      <h2>Add New Product</h2>
      <form id="productForm">
        <input type="text" id="name" placeholder="Product Name" required><br>
        <input type="number" id="stocks" placeholder="Stocks" required><br>
        <input type="number" id="criticalQty" placeholder="Critical Quantity" required><br>
        <input type="number" id="price" placeholder="Price" required><br>
        <input type="file" id="picture" accept="image/*"><br>
        <button type="button" onclick="saveProduct()">Save Product</button>
      </form>
    </div>
  </div>

  <!-- Edit Product Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <span class="close" id="editClose">&times;</span>
      <h2>Edit Product</h2>
      <form id="editForm" action="products.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="edit_product_id" id="edit_product_id">
        <input type="hidden" name="old_picture" id="old_picture">
        <label for="edit_name">Product Name</label><br>
        <input type="text" name="edit_name" id="edit_name" required><br>

        <label for="edit_status">Status</label><br>
        <select name="edit_status" id="edit_status" required>
          <option value="active">Active</option>
          <option value="disabled">Disabled</option>
        </select><br>

        <label for="edit_criticalQty">Critical Quantity</label><br>
        <input type="number" name="edit_criticalQty" id="edit_criticalQty" required><br>

        <label for="edit_price">Price</label><br>
        <input type="number" step="0.01" name="edit_price" id="edit_price" required><br>

        <label for="edit_picture">Product Image</label><br>
        <input type="file" name="edit_picture" id="edit_picture" accept="image/*"><br>

        <button type="submit">Save Changes</button>
      </form>
    </div>
  </div>
<!-- Stock In Modal -->
<div id="stockInModal" class="modal">
  <div class="modal-content">
    <span class="close" id="stockInClose">&times;</span>
    <h2>Stock In</h2>
    <form id="stockInForm" action="products.php" method="POST">
      <input type="hidden" name="stock_in_product_id" id="stock_in_product_id">

      <label>Current Stock</label><br>
      <input type="number" id="stock_in_current_stock" disabled><br>

      <label for="stock_in_qty">Quantity</label><br>
      <input type="number" name="stock_in_qty" id="stock_in_qty" required><br>

      <div id="stock-in-error-message" style="color: red; display: none;">
        Quantity cannot be zero or negative.
      </div>

      <button type="submit" id="stock_in_submit_button" disabled>Save</button>
    </form>
  </div>
</div>


<!-- Stock Out Modal -->
<div id="stockOutModal" class="modal">
  <div class="modal-content">
    <span class="close" id="stockOutClose">&times;</span>
    <h2>Stock Out</h2>
    <form id="stockOutForm" action="products.php" method="POST">
      <input type="hidden" name="stock_out_product_id" id="stock_out_product_id">
      <label>Current Stock</label><br>
      <input type="number" id="current_stock" disabled><br> <!-- Display current stock -->
      <label for="stock_out_qty">Quantity</label><br>
      <input type="number" name="stock_out_qty" id="stock_out_qty" required><br>
      <div id="error-message" style="color: red; display: none;">Quantity cannot be zero, negative, or greater than current stock number.</div> <!-- Error message -->
      <button type="submit" id="submitButton" disabled>Save</button> <!-- Initially disabled -->
    </form>
  </div>
</div>

<!-- Stock Adjustment Modal -->
<div id="stockAdjustModal" class="modal">
  <div class="modal-content">
    <span class="close" id="stockAdjustClose">&times;</span>
    <h2>Stock Adjustment</h2>
    <form id="stockAdjustForm" action="products.php" method="POST">
      <input type="hidden" name="stock_adjust_product_id" id="stock_adjust_product_id">
      <label>Current Stock</label><br>
      <input type="number" id="adjust_current_stock" disabled>
      <label for="stock_adjust_qty">New Quantity</label><br>
      <input type="number" name="stock_adjust_qty" id="stock_adjust_qty" required><br>
      <div id="adjust-error-message" style="color: red; display: none;">Quantity cannot be negative.</div> <!-- Error message -->
      <label for="adjust_reason">Reason</label><br>
      <input type="text" name="adjust_reason" id="adjust_reason" required><br>
      <button type="submit" id="adjustsubmitButton" disabled>Save</button>
    </form>
  </div>
</div>
 
 <script>
document.addEventListener("DOMContentLoaded", function() {
    let userId = localStorage.getItem("user_id");

    if (!userId) {
        console.log("Stored user_id in localStorage:", localStorage.getItem("user_id")); // Debugging
    }
});

    // Attach user ID to all stock-related forms before submission
    document.querySelectorAll("form").forEach(form => {
        form.addEventListener("submit", function(event) {
            let hiddenInput = document.createElement("input");
            hiddenInput.type = "hidden";
            hiddenInput.name = "user_id";
            hiddenInput.value = userId;
            form.appendChild(hiddenInput);
        });
    });

// Listen for input on the stock in quantity field
document.getElementById('stock_in_qty').addEventListener('input', function () {
    var quantity = parseInt(this.value);
    var submitButton = document.getElementById('stock_in_submit_button');
    var errorMessage = document.getElementById('stock-in-error-message');

    if (quantity <= 0 || isNaN(quantity)) {
        errorMessage.style.display = 'block';
        submitButton.disabled = true;
    } else {
        errorMessage.style.display = 'none';
        submitButton.disabled = false;
    }
});

// Function to open the Stock In modal and populate values
function openStockInModal(id, name, stocks) {
    document.getElementById('stock_in_product_id').value = id;
    document.getElementById('stock_in_current_stock').value = stocks;
    document.getElementById('stock_in_qty').value = '';
    document.getElementById('stock-in-error-message').style.display = 'none';
    document.getElementById('stock_in_submit_button').disabled = true;
    document.getElementById('stockInModal').style.display = 'block';
}


  document.getElementById('stock_out_qty').addEventListener('input', function() {
    var quantity = parseInt(this.value);
    var submitButton = document.getElementById('submitButton');
    var errorMessage = document.getElementById('error-message');
    var currentStock = parseInt(document.getElementById('current_stock').value);

    // If quantity is negative or greater than current stock, show error message and disable submit button
    if (quantity < 1 || quantity > currentStock) {
        errorMessage.style.display = 'block'; // Show error message
        submitButton.disabled = true; // Disable submit button
    } else {
        errorMessage.style.display = 'none'; // Hide error message
        submitButton.disabled = false; // Enable submit button
    }
});

function openStockOutModal(id, name, stocks) {
    // Set the hidden product ID
    document.getElementById('stock_out_product_id').value = id;

    // Set the current stock in the modal
    document.getElementById('current_stock').value = stocks;

    // Open the modal
    document.getElementById('stockOutModal').style.display = 'block';
}


  document.getElementById('stock_adjust_qty').addEventListener('input', function() {
    var newStockQty = this.value;
    var submitButton = document.getElementById('adjustsubmitButton');
    var errorMessage = document.getElementById('adjust-error-message');

    // Check if the value is negative
    if (newStockQty < 0) {
      submitButton.disabled = true;
      errorMessage.style.display = 'block'; // Show error message
    } else {
      submitButton.disabled = false;
      errorMessage.style.display = 'none'; // Hide error message
    }
  });
  
  // Function to open the modal and set the initial values
  function openStockAdjustModal(id, name, stockQty) {
    document.getElementById('stock_adjust_product_id').value = id;
    document.getElementById('adjust_current_stock').value = stockQty;
    document.getElementById('stockAdjustModal').style.display = 'block';
  }
    // Edit feature: Open Edit Modal
    function openEditModal(id, name, status, criticalQty, price, picture) {
      document.getElementById('edit_product_id').value = id;
      document.getElementById('old_picture').value = picture;
      document.getElementById('edit_name').value = name;
      document.getElementById('edit_status').value = status;
      document.getElementById('edit_criticalQty').value = criticalQty;
      document.getElementById('edit_price').value = price;
      document.getElementById('editModal').style.display = 'block';
    }

    document.addEventListener("DOMContentLoaded", function () {
    document.getElementById('addClose').onclick = function () {
      document.getElementById("productModal").style.display = "none";
    };
  });
    // Close Edit Modal
    document.getElementById('editClose').onclick = function() {
      document.getElementById('editModal').style.display = 'none';
    };

    // Open Add Product Modal
    document.getElementById('openModal').onclick = function() {
      document.getElementById('productModal').style.display = 'block';
    };

    // Close Add Product Modal
   

    document.addEventListener("DOMContentLoaded", function () {
    document.getElementById('stockInClose').onclick = function () {
      document.getElementById("stockInModal").style.display = "none";
    };
  });
  document.addEventListener("DOMContentLoaded", function () {
    document.getElementById('stockOutClose').onclick = function () {
      document.getElementById("stockOutModal").style.display = "none";
    };
  });
  document.addEventListener("DOMContentLoaded", function () {
    document.getElementById('stockAdjustClose').onclick = function () {
      document.getElementById("stockAdjustModal").style.display = "none";
    };
  });
    // Save Product function
    function saveProduct() {
      const name = document.getElementById("name").value;
      const stocks = document.getElementById("stocks").value;
      const criticalQty = document.getElementById("criticalQty").value;
      const price = document.getElementById("price").value;
      const picture = document.getElementById("picture").files[0];
      const formData = new FormData();
      formData.append("name", name);
      formData.append("stocks", stocks);
      formData.append("criticalQty", criticalQty);
      formData.append("price", price);
      formData.append("picture", picture);
      fetch("save_product.php", {
          method: "POST",
          body: formData,
        })
        .then(response => response.json())
        .then(data => {
          alert(data.message);
          if (data.success) {
        location.reload(); // 🔄 Reloads the page
      }
          document.getElementById("productModal").style.display = "none";
        })
        .catch(error => console.error("Error:", error));
    }

    document.addEventListener("DOMContentLoaded", function () {
  const headers = document.querySelectorAll(".table-header .header__item");
  const tableContent = document.querySelector(".table-content");
  const originalRows = Array.from(document.querySelectorAll(".table-row")); // Store the initial order of rows

  headers.forEach((header, index) => {
    let sortState = 0; // 0: normal, 1: ascending, 2: descending

    header.addEventListener("click", function () {
      const rows = Array.from(document.querySelectorAll(".table-row"));

      // Toggle the sorting state
      sortState = (sortState + 1) % 3; // 0 -> 1 -> 2 -> 0 (normal -> ascending -> descending -> normal)

      if (sortState === 0) {
        // Reset to the original order
        rows.forEach((row, i) => {
          tableContent.appendChild(originalRows[i]); // Re-append the rows in their original order
        });
      } else {
        // Sorting based on the column index
        rows.sort((a, b) => {
          const cellA = a.children[index].textContent.trim();
          const cellB = b.children[index].textContent.trim();

          // Sorting for "Name" column (Alphabetical Order)
          if (index === 1) {
            if (cellA < cellB) return sortState === 1 ? -1 : 1;
            if (cellA > cellB) return sortState === 1 ? 1 : -1;
            return 0;
          }

          // Sorting for "Price" column (Numeric sorting)
          if (index === 6 || index === 4 || index === 3) {
            // Remove any non-numeric characters (such as $ or ,) before converting to float
            const aVal = parseFloat(cellA.replace(/[^\d.-]/g, ''));
            const bVal = parseFloat(cellB.replace(/[^\d.-]/g, ''));
            
            return sortState === 1 ? aVal - bVal : bVal - aVal;
          }

          // Default lexicographical sorting for other columns (like Status, Sales, etc.)
          if (cellA < cellB) return sortState === 1 ? -1 : 1;
          if (cellA > cellB) return sortState === 1 ? 1 : -1;
          return 0;
        });

        rows.forEach(row => tableContent.appendChild(row)); // Append sorted rows
      }
    });
  });
});

  </script>

</body>

</html>