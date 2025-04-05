<?php
session_start();

include 'db.php';
if (isset($_SESSION['user_id'])) {
  $user_id = $_SESSION['user'];

  // Fetch user details from database
  $stmt = $conn->prepare("SELECT first_name, last_name FROM account WHERE email = ?");
  $stmt->bind_param("s", $userEmail);
  $stmt->execute();
  $stmt->bind_result($userId);
  $stmt->fetch();
  $stmt->close();

  if ($row = $result->fetch_assoc()) {
    $first_name = htmlspecialchars($row['first_name']);
    $last_name = htmlspecialchars($row['last_name']);
  } else {
    $first_name = "Guest";
    $last_name = "";
  }
} else {
  $first_name = "Guest";
  $last_name = "";
}

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch logs for the table
$userEmail = $_SESSION['user']; 

// Retrieve user details based on session email
$stmt = $conn->prepare("SELECT first_name, last_name FROM account WHERE email = ?");
$stmt->bind_param("s", $userEmail);
$stmt->execute();
$stmt->bind_result($firstName, $lastName);
$stmt->fetch();
$stmt->close();

// Extract initials
$firstInitial = strtoupper(substr($firstName, 0, 1));
$lastInitial = strtoupper(substr($lastName, 0, 1));

$displayName = $firstName . " " . $lastInitial . ".";

// Fetch product data for the graph
$sql_products = "SELECT id, name, stocks, criticalQty FROM product";
$result_products = $conn->query($sql_products);

$products = [];
while ($row = $result_products->fetch_assoc()) {
    $products[] = $row;
}

$conn->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
  
  <meta charset="UTF-8">
  <title>Felco Inventory Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/5.0.0/normalize.min.css">
  <link rel="stylesheet" href="./style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

.table {
  display: flex;
  flex-direction: column;
  width: 100%;
  border-collapse: collapse;
  border-radius: 8px;
  overflow: hidden;
  font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
  box-shadow: 0 0 5px rgba(0,0,0,0.1);
}

.table-header, .table-row {
  display: flex;
  background-color: #f6f6f6;
}

.table-header {
  background-color: #333;
  color: white;
  font-weight: bold;
}

.header__item, .table-data {
  flex: 1;
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid #ddd;
}

.table-data {
  background-color: white;
}

.table-row:nth-child(even) .table-data {
  background-color: #f9f9f9;
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
  <!-- partial:index.partial.html -->
  <div class="app-container">
    <div class="sidebar">
      <div class="sidebar-header">
        <div class="app-icon">
          <img src="logo.png?v=1.0" alt="App Logo" style="width: 165px; height: 80px;"/>
        </div>
      </div>
      <ul class="sidebar-list">
        <li class="sidebar-list-item active">
          <a href="#">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-home">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
              <polyline points="9 22 9 12 15 12 15 22" />
            </svg>
            <span>Home</span>
          </a>
        </li>
        <li class="sidebar-list-item ">
          <a href="products.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-shopping-bag">
              <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
              <line x1="3" y1="6" x2="21" y2="6" />
              <path d="M16 10a4 4 0 0 1-8 0" />
            </svg>
            <span>Products</span>
          </a>
        </li>
        <li class="sidebar-list-item">
          <a href="logs.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-pie-chart">
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
      <div style="display: flex; flex-direction: row; align-items: center;">
        <a href="logout.php" class="logout" style="text-decoration: none; color: inherit;">Logout</a>
      </div>
    </div>
    <div class="app-content">
      <div class="container">
        <h1>Hello, <?php echo htmlspecialchars($firstName)?>!</h1>
        <h2>Product Stock Levels</h2>
        <div class="chart-container">
          <canvas id="stockChart"></canvas>
        </div>
      </div>
    </div>

    <!-- partial -->
    <script>
      document.addEventListener("DOMContentLoaded", function() {
          var ctx = document.getElementById('stockChart').getContext('2d');

          var productNames = <?php echo json_encode(array_column($products, 'name')); ?>;
          var productStocks = <?php echo json_encode(array_column($products, 'stocks')); ?>;
          var criticalStocks = <?php echo json_encode(array_column($products, 'criticalQty')); ?>;

          new Chart(ctx, {
              type: 'bar',
              data: {
                  labels: productNames,
                  datasets: [
                      {
                          label: 'Critical Stock Level',
                          data: criticalStocks,
                          backgroundColor: 'rgba(0, 15, 90, 0.95)',
                          borderColor: 'rgba(10, 24, 95, 0.95)',
                          borderWidth: 1
                      },  
                      {
                          label: 'Current Stock Level',
                          data: productStocks,
                          backgroundColor: 'rgba(255, 196, 12, 1)',
                          borderColor: 'rgb(250, 200, 49)',
                          borderWidth: 1
                      }
                  ]
              },
              options: {
                  responsive: true,
                  scales: {
                    x: {
                  ticks: {
                      font: {
                          weight: 'bold' // 👈 Bolds the product names
                      }
                  }
              },
                      y: {
                          beginAtZero: true
                      }
                  }
              }
          });
      });
    </script>
  </div>
</body>


</html>