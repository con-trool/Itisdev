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

// Handle deletion if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_product_id'])) {
  $deleteId = intval($_POST['delete_product_id']);
  $stmt = $conn->prepare("DELETE FROM product WHERE id = ?");
  $stmt->bind_param("i", $deleteId);
  $stmt->execute();
  header("Location: products.php?msg=deleted");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST['stock_in_product_id'])) {
    $productId = intval($_POST['stock_in_product_id']);
    $stockInQty = intval($_POST['stock_in_qty']);
    $stmt = $conn->prepare("UPDATE product SET stocks = stocks + ? WHERE id = ?");
    $stmt->bind_param("ii", $stockInQty, $productId);
    $stmt->execute();
    header("Location: products.php?msg=stock_in");
    exit();
  }
  
  if (isset($_POST['stock_out_product_id'])) {
    $productId = intval($_POST['stock_out_product_id']);
    $stockOutQty = intval($_POST['stock_out_qty']);
    
    // Subtract from stock and add to sales
    $stmt = $conn->prepare("UPDATE product SET stocks= stocks - ?, sales = sales + ? WHERE id = ?");
    $stmt->bind_param("iii", $stockOutQty, $stockOutQty, $productId);
    $stmt->execute();
    header("Location: products.php?msg=stock_out");
    exit();
  }
}
$userEmail = $_SESSION['user']; 

// Retrieve the user ID based on the session email
$stmt = $conn->prepare("SELECT id FROM account WHERE email = ?");
$stmt->bind_param("s", $userEmail);
$stmt->execute();
$stmt->bind_result($userId);
$stmt->fetch();
$stmt->close();

if (!$userId) {
    die("User ID not found.");
}

if (isset($_POST['stock_adjust_product_id'])) {
    $productId = intval($_POST['stock_adjust_product_id']);
    $newStock = intval($_POST['stock_adjust_qty']);
    $reason = $_POST['adjust_reason'];
    $timestamp = date('Y-m-d H:i:s');

    // Update product stock
    $stmt = $conn->prepare("UPDATE product SET stocks = ? WHERE id = ?");
    $stmt->bind_param("ii", $newStock, $productId);
    $stmt->execute();
    $stmt->close();

    // Log the stock adjustment
    $stmt = $conn->prepare("INSERT INTO logs (userID, productID, description, datetime) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $userId, $productId, $reason, $timestamp);
    $stmt->execute();
    $stmt->close();

    header("Location: products.php?msg=stock_adjusted");
    exit();
}
// Handle editing if form is submitted (Edit Product Details feature)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_product_id'])) {
  $editId = intval($_POST['edit_product_id']);
  $editName = $_POST['edit_name'];
  $editStatus = $_POST['edit_status'];
  $editCriticalQty = intval($_POST['edit_criticalQty']);
  $editPrice = floatval($_POST['edit_price']);

  // Retain old picture unless a new one is uploaded
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
  header("Location: products.php?msg=edited");
  exit();
}

/************************************************
 * [FILTER FEATURE] Build SQL Query with Filters
 ************************************************/
$whereClauses = [];

// Search filter (case-insensitive, partial match)
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
      width: 50px;
      height: 50px;
      object-fit: cover;
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
  </style>
</head>

<body>
  <div class="app-container">
    <div class="sidebar">
      <div class="sidebar-header">
        <div class="app-icon">
          <svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
            <path fill="currentColor" d="M507.606 371.054a187.217 187.217 0 00-23.051-19.606c-17.316 19.999-37.648 36.808-60.572 50.041-35.508 20.505-75.893 31.452-116.875 31.711 21.762 8.776 45.224 13.38 69.396 13.38 49.524 0 96.084-19.286 131.103-54.305a15 15 0 004.394-10.606 15.028 15.028 0 00-4.395-10.615zM27.445 351.448a187.392 187.392 0 00-23.051 19.606C1.581 373.868 0 377.691 0 381.669s1.581 7.793 4.394 10.606c35.019 35.019 81.579 54.305 131.103 54.305 24.172 0 47.634-4.604 69.396-13.38-40.985-.259-81.367-11.206-116.879-31.713-22.922-13.231-43.254-30.04-60.569-50.039zM103.015 375.508c24.937 14.4 53.928 24.056 84.837 26.854-53.409-29.561-82.274-70.602-95.861-94.135-14.942-25.878-25.041-53.917-30.063-83.421-14.921.64-29.775 2.868-44.227 6.709-6.6 1.576-11.507 7.517-11.507 14.599 0 1.312.172 2.618.512 3.885 15.32 57.142 52.726 100.35 96.309 125.509zM324.148 402.362c30.908-2.799 59.9-12.454 84.837-26.854 43.583-25.159 80.989-68.367 96.31-125.508.34-1.267.512-2.573.512-3.885 0-7.082-4.907-13.023-11.507-14.599-14.452-3.841-29.306-6.07-44.227-6.709-5.022 29.504-15.121 57.543-30.063 83.421-13.588 23.533-42.419 64.554-95.862 94.134zM187.301 366.948c-15.157-24.483-38.696-71.48-38.696-135.903 0-32.646 6.043-64.401 17.945-94.529-16.394-9.351-33.972-16.623-52.273-21.525-8.004-2.142-16.225 2.604-18.37 10.605-16.372 61.078-4.825 121.063 22.064 167.631 16.325 28.275 39.769 54.111 69.33 73.721zM324.684 366.957c29.568-19.611 53.017-45.451 69.344-73.73 26.889-46.569 38.436-106.553 22.064-167.631-2.145-8.001-10.366-12.748-18.37-10.605-18.304 4.902-35.883 12.176-52.279 21.529 11.9 30.126 17.943 61.88 17.943 94.525.001 64.478-23.58 111.488-38.702 135.912zM266.606 69.813c-2.813-2.813-6.637-4.394-10.615-4.394a15 15 0 00-10.606 4.394c-39.289 39.289-66.78 96.005-66.78 161.231 0 65.256 27.522 121.974 66.78 161.231 2.813 2.813 6.637 4.394 10.615 4.394s7.793-1.581 10.606-4.394c39.248-39.247 66.78-95.96 66.78-161.231.001-65.256-27.511-121.964-66.78-161.231z" />
          </svg>
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
        <div class="account-info-picture">
          <img src="https://images.unsplash.com/photo-1527736947477-2790e28f3443?..." alt="Account">
        </div>
        <div class="account-info-name">Monica G.</div>
        <button class="account-info-more">
          <!-- 'more' icon code -->
        </button>
      </div>
    </div>

    <div class="app-content">
      <div class="app-content-header">
        <h1 class="app-content-headerText">Products</h1>
        <button class="mode-switch" title="Switch Theme">
          <svg class="moon" fill="none" stroke="currentColor" stroke-linecap="round"
            stroke-linejoin="round" stroke-width="2" width="24" height="24" viewBox="0 0 24 24">
            <defs></defs>
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
          </svg>
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
          <button class="action-button list active" title="List View">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
              viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round" class="feather feather-list">
              <line x1="8" y1="6" x2="21" y2="6" />
              <line x1="8" y1="12" x2="21" y2="12" />
              <line x1="8" y1="18" x2="21" y2="18" />
              <line x1="3" y1="6" x2="3.01" y2="6" />
              <line x1="3" y1="12" x2="3.01" y2="12" />
              <line x1="3" y1="18" x2="3.01" y2="18" />
            </svg>
          </button>
          <button class="action-button grid" title="Grid View">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
              viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round" class="feather feather-grid">
              <rect x="3" y="3" width="7" height="7" />
              <rect x="14" y="3" width="7" height="7" />
              <rect x="14" y="14" width="7" height="7" />
              <rect x="3" y="14" width="7" height="7" />
            </svg>
          </button>
        </div>
      </div>

      <div class="products-area-wrapper tableView">
        <table>
          <thead>
            <tr>
              <th>Image</th>
              <th>Name</th>
              <th>Status</th>
              <th>Sales</th>
              <th>Stocks</th>
              <th>Critical Qty</th>
              <th>Price</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if ($result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td><img src='{$row['picture']}' alt='Product Image'></td>
                        <td>{$row['name']}</td>
                        <td>{$row['status']}</td>
                        <td>{$row['sales']}</td>
                        <td>{$row['stocks']}</td>
                        <td>{$row['criticalQty']}</td>
                        <td>₱" . number_format($row['price'], 2) . "</td>
                        <td>
                          <form action='products.php' method='POST' style='display:inline;'
                                onsubmit=\"return confirm('Are you sure you want to delete this product?');\">
                            <input type='hidden' name='delete_product_id' value='{$row['id']}'>
                            <button type='submit'>Delete</button>
                          </form>
                          <button type='button'
                                  onclick=\"openEditModal(
                                    {$row['id']},
                                    '{$row['name']}',
                                    '{$row['status']}',
                                    {$row['criticalQty']},
                                    {$row['price']},
                                    '{$row['picture']}'
                                  )\">
                            Edit
                          </button>
                          <button type='button'
                                onclick=\"openStockInModal({$row['id']}, '{$row['name']}', {$row['stocks']})\">
                                Stock In
                              </button>
                              <button type='button'
                                onclick=\"openStockOutModal({$row['id']}, '{$row['name']}', {$row['stocks']})\">
                                Stock Out
                              </button>
                              <button type='button'
                              onclick=\"openStockAdjustModal({$row['id']}, '{$row['name']}', {$row['stocks']})\">
                              Stock Adjustment
                            </button>
                        </td>
                      </tr>";
              }
            } else {
              echo "<tr><td colspan='8'>No products found</td></tr>";
            }
            $conn->close();
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script src="./script.js"></script>

  <!-- Add Product Modal -->
  <div id="productModal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
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
      <label for="stock_in_qty">Quantity</label><br>
      <input type="number" name="stock_in_qty" id="stock_in_qty" required><br>
      <button type="submit">Save</button>
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
      <label for="stock_out_qty">Quantity</label><br>
      <input type="number" name="stock_out_qty" id="stock_out_qty" required><br>
      <button type="submit">Save</button>
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
      <input type="number" id="current_stock" disabled><br>
      <label for="stock_adjust_qty">New Quantity</label><br>
      <input type="number" name="stock_adjust_qty" id="stock_adjust_qty" required><br>
      <label for="adjust_reason">Reason</label><br>
      <input type="text" name="adjust_reason" id="adjust_reason" required><br>
      <button type="submit">Save</button>
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

    function openStockInModal(id, name, stocks) {
    document.getElementById('stock_in_product_id').value = id;
    document.getElementById('stockInModal').style.display = 'block';
  }

  function openStockOutModal(id, name, stocks) {
    document.getElementById('stock_out_product_id').value = id;
    document.getElementById('stockOutModal').style.display = 'block';
  }

  function openStockAdjustModal(id, name, stockQty) {
    document.getElementById('stock_adjust_product_id').value = id;
    document.getElementById('current_stock').value = stockQty;
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

    // Close Edit Modal
    document.getElementById('editClose').onclick = function() {
      document.getElementById('editModal').style.display = 'none';
    };

    // Open Add Product Modal
    document.getElementById("openModal").onclick = function() {
      document.getElementById("productModal").style.display = "block";
    };

    // Close Add Product Modal
    document.querySelector(".close").onclick = function() {
      document.getElementById("productModal").style.display = "none";
    };

    document.querySelector(".close").onclick = function() {
      document.getElementById("stockInModal").style.display = "none";
    };
    document.querySelector(".close").onclick = function() {
      document.getElementById("stockOutModal").style.display = "none";
    };
    document.querySelector(".close").onclick = function() {
      document.getElementById("stockAdjustModal").style.display = "none";
    };
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
          document.getElementById("productModal").style.display = "none";
        })
        .catch(error => console.error("Error:", error));
    }
  </script>
</body>

</html>