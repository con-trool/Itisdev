<!DOCTYPE html>
<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "127.0.0.1";
$username = "root";
$password = "";
$database = "itisdev";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$sql = "
    SELECT 
        logs.datetime, 
        account.first_name, 
        account.last_name, 
        product.name AS product_name, 
        logs.description 
    FROM logs
    LEFT JOIN account ON logs.userID = account.id
    LEFT JOIN product ON logs.productID = product.id
    ORDER BY logs.datetime DESC
";

$result = $conn->query($sql);

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

?>
<html lang="en" >
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
      background-color: #ffc40c;
      color: black;
      font-weight: bold;
    }

    .header__item, .table-data {
      flex: 1;
      padding: 12px 15px;
      text-align: center;
      border-bottom: 1px solid #ddd;
    }

    .table-data {
      background-color: white;
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
      <li class="sidebar-list-item">
        <a href="dashboard.php">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-home"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          <span>Home</span>
        </a>
      </li>
      <li class="sidebar-list-item">
        <a href="products.php">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-shopping-bag"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          <span>Products</span>
        </a>
      </li>
      <li class="sidebar-list-item active">
        <a href="#">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-pie-chart"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
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
   
    <div class="container">
       
    <h2>Logs</h2>

    <div class="products-area-wrapper tableView">
    <div class="table">
    <div class="table-header">
      <div class="header__item" data-column="0"><a class="filter__link">Date <span class="sort-icon">&#8597;</span></a></div>
      <div class="header__item" data-column="1"><a class="filter__link">Time</a></div>
      <div class="header__item" data-column="2"><a class="filter__link">User</a></div>
      <div class="header__item" data-column="3"><a class="filter__link">Product</a></div>
      <div class="header__item" data-column="4"><a class="filter__link">Description</a></div>
    </div>

    <div class="table-content">
      <?php
      if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
            $timestamp = new DateTime($row['datetime']);
            $timestamp->modify('6 hours');

            $date = $timestamp->format("Y-m-d");
            $time = $timestamp->format("H:i:s");

              echo "<div class='table-row'>
                      <div class='table-data'>{$date}</div>
                      <div class='table-data'>{$time}</div>
                      <div class='table-data'>{$row['first_name']} {$row['last_name']}</div>
                      <div class='table-data'>{$row['product_name']}</div>
                      <div class='table-data'>{$row['description']}</div>
                    </div>";
          }
      } else {
          echo "<div class='table-row'><div class='table-data' colspan='5'>No logs found.</div></div>";
      }
      ?>
    </div>
  </div>
</div>

<!-- partial -->
  <script src="./script.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const modeSwitch = document.querySelector(".mode-switch");
      const body = document.body;

      const currentTheme = localStorage.getItem("theme");

      if (currentTheme === "dark") {
          body.classList.add("dark-mode");
      } else {
          body.classList.remove("dark-mode");
      }

      if (modeSwitch) {
        modeSwitch.addEventListener("click", function () {
            body.classList.toggle("dark-mode");
            localStorage.setItem("theme", body.classList.contains("dark-mode") ? "dark" : "light");
        });
      }

      // Sorting functionality
      const headers = document.querySelectorAll(".table-header .header__item");
      const tableContent = document.querySelector(".table-content");

      headers.forEach((header, index) => {
  let ascending = true;

  header.addEventListener("click", function () {
    // Skip sorting for the Time column (index 1)
    if (index === 1) return;

    const rows = Array.from(document.querySelectorAll(".table-row"));

    rows.sort((a, b) => {
      const cellA = a.children[index].textContent.trim();
      const cellB = b.children[index].textContent.trim();

      const aVal = isNaN(Date.parse(cellA)) ? cellA.toLowerCase() : new Date(cellA);
      const bVal = isNaN(Date.parse(cellB)) ? cellB.toLowerCase() : new Date(cellB);

      if (aVal < bVal) return ascending ? -1 : 1;
      if (aVal > bVal) return ascending ? 1 : -1;
      return 0;
    });

    ascending = !ascending;
    tableContent.innerHTML = "";
    rows.forEach(row => tableContent.appendChild(row));
  });
});

    });
  </script>
</body>
</html>
