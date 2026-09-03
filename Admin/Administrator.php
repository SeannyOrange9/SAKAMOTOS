<?php
$page = $_GET['page'] ?? 'home';

$pageTitles = [
    'home' => 'Administrator Dashboard',
    'users.php' => "Users' Account",
    'menu.php' => 'Menu Settings',
    'POS.php' => 'POS (Point of Sales)',
    'fast-slow.php' => 'Fast/Slow Moving Items',
    'reports.php' => 'Reports',
    'inventory.php' => 'Inventory',
    'payment-history.php' => 'Payment History',
    'customer.php' => 'Customer Page',
    'categories.php' => 'Category Settings',
    'flavors.php' => 'Flavor Settings',
    'add-ons.php' => 'Add-ons Settings',
    'cupsizes.php' => 'Cup Sizes Settings',
    'slideshow.php' => 'Slideshow Settings',
    'home-image.php' => 'Home Image Settings',
    'logo.php' => 'Logo Settings',
    'page-color.php' => 'Page Color Settings'

    ];

$currentTitle = $pageTitles[$page] ?? 'Administrator';


$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'sakamoto';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query = "SELECT logo_image FROM logo_table WHERE logo_id = 'logo' LIMIT 1";
$result = $conn->query($query);

if ($result && $row = $result->fetch_assoc()) {
    $logosak = $row['logo_image'];
} else {
    $logosak = '';
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($currentTitle) ?> | Sakamoto's</title>
  <link rel="icon" type="image/x-icon" href="Sakamoto's.png">
  <style>

* {
    font-family: 'Sophia', sans-serif;
}

body {
    margin: 0;
}
    
.navbar * {
      direction: ltr;
}

.navbar a {
    color: white;
    text-decoration: none;
    padding: 10px 15px;
    margin-bottom: 5px;
    display: block;
    background-color: #d22e2e;
}

.navbar a:hover, .navbar a.active {
    background-color: white;
    color: #d22e2e;
}

.navbar a.logout-button {
    background-color: #d22e2e;
    color: white;
}

.navbar a.logout-button:hover {
    background-color: white;
    color: #d22e2e;
}
  
.image {
    width: 200px;
    height: 200px;
    margin-right: 35px;
}

.imagehome {
    width: 350px;
    height: 350px;
    display: block;
    margin-left: auto;
    margin-right: auto;
    width: 50%;
}

.navbar::-webkit-scrollbar {
    display: none;
}

.navbar.animate {
    transition: transform .3s ease;
}

.navbar {
    width: 270px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    background-color: #d22e2e;
    color: white;
    display: flex;
    flex-direction: column;
    padding: 10px;
    overflow-y: scroll;
    direction: rtl;
    transition: none;
    z-index: 1000;
}

.navbar.animate {
    transition: transform 0.3s ease;
}

.navbar.hidden {
    transform: translateX(-100%);
}

.content {
    margin-left: 300px;
    padding: 20px;
    transition: none;
}

.content.animate {
    transition: margin-left .3s ease;
}

.content.full {
    margin-left: 20px;
}

#toggleBtn {
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1100;
    padding: 10px 15px;
    border: none;
    border-radius: 6px;
    background: #d22e2e;
    color: white;
    cursor: pointer;
    font-size: 16px;
}

#toggleBtn:hover {
    background: #a91f1f;
}
</style>
</head>
<body>
  <button id="toggleBtn" onclick="toggleSidebar()">☰</button>
  <div class="navbar" id="sidebar">
    <div class="image">
      <img src="<?php echo $logosak; ?>" alt="Navigation" style="max-width: 100%; height: auto;">
    </div>
    <a href="?page=home" class="menu-item <?= $page === 'home' ? 'active' : '' ?>">Home</a>
    <a href="?page=users.php" class="menu-item <?= $page === 'users.php' ? 'active' : '' ?>">Users' Account</a>
    <a href="?page=menu.php" class="menu-item <?= $page === 'menu.php' ? 'active' : '' ?>">Menu Settings</a>
    <a href="?page=POS.php" class="menu-item <?= $page === 'POS.php' ? 'active' : '' ?>">Point of Sales</a>
    <a href="?page=fast-slow.php" class="menu-item <?= $page === 'fast-slow.php' ? 'active' : '' ?>">Fast/Slow Moving Items</a>
    <a href="?page=reports.php" class="menu-item <?= $page === 'reports.php' ? 'active' : '' ?>">Reports</a>
    <a href="?page=inventory.php" class="menu-item <?= $page === 'inventory.php' ? 'active' : '' ?>">Inventory</a>
    <a href="?page=payment-history.php" class="menu-item <?= $page === 'payment-history.php' ? 'active' : '' ?>">Payment History</a>
    <a href="?page=customer.php" class="menu-item <?= $page === 'customer.php' ? 'active' : '' ?>">Customer Page</a>
    <a href="AdminLog.php" class="logout-button">Log-out</a>
    <div class="space" style="margin-top: 20px;"></div>
  </div>
  <div class="content" id="main-content">
    <?php
      // Load the appropriate content
      if ($page === 'home') {
        echo '<div class="imagehome">
                <img src="Sakamoto\'s.png" alt="Navigation" style="max-width: 100%; height: auto;">
              </div>';
      } else {
        $filePath = __DIR__ . '/' . $page;
        if (file_exists($filePath)) {
          include $filePath;
        } else {
          echo '<p>Error loading content.</p>';
        }
      }
    ?>
  </div>
</body>
    <script>
      const sidebar = document.getElementById("sidebar");
const content = document.getElementById("main-content");

document.addEventListener("DOMContentLoaded", function () {

    if (localStorage.getItem("sidebar") === "open") {
        sidebar.classList.remove("hidden");
        content.classList.remove("full");
    } else {
        sidebar.classList.add("hidden");
        content.classList.add("full");
    }

    // Enable animations only after the initial state has been set
    requestAnimationFrame(() => {
        sidebar.classList.add("animate");
        content.classList.add("animate");
    });
});

function toggleSidebar() {
    sidebar.classList.toggle("hidden");
    content.classList.toggle("full");

    localStorage.setItem(
        "sidebar",
        sidebar.classList.contains("hidden") ? "closed" : "open"
    );
}
</script>
</html>
