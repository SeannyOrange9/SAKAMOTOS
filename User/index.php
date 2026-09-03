
<?php
ob_start();

// Determine the requested page
$page = $_GET['page'] ?? 'home.php';

$pageTitles = [
    'home.php' => 'Home',
    'user_profile.php' => 'Account Profile',
    'menu.php' => 'Menu',
    'cart.php' => 'Your Cart',
    'orders.php' => 'Your Order List',
    'notifications.php' => 'Notifications',
];

if (isset($_GET['product_name']) && !empty($_GET['product_name'])) {
    $currentTitle = $_GET['product_name'];
} else {
    $currentTitle = $pageTitles[$page] ?? 'Home';
}


$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'sakamoto';

// Create a connection to the database
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();

// Check if the username session is set
if (!isset($_SESSION['account_username'])) {
    header("Location: Log-in.php");
    exit();
}

$username = $_SESSION['account_username'];

// COUNT UNREAD NOTIFICATIONS

$notification_count = 0;

$notification_count_stmt = $conn->prepare("
    SELECT COUNT(*) AS unread_count
    FROM notifications n
    INNER JOIN user_table u
        ON n.account_id = u.account_id
    WHERE u.account_username = ?
      AND n.is_read = 0
");

if ($notification_count_stmt !== false) {

    $notification_count_stmt->bind_param(
        "s",
        $username
    );

    $notification_count_stmt->execute();

    $notification_count_result =
        $notification_count_stmt->get_result();

    if ($notification_count_result->num_rows > 0) {

        $notification_count_row =
            $notification_count_result->fetch_assoc();

        $notification_count =
            (int)$notification_count_row['unread_count'];
    }

    $notification_count_stmt->close();
}


// QUERY TO CHECK IF USER IS LOGGED IN

$sql = "SELECT is_logged FROM user_table WHERE account_username = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Query preparation failed: " . $conn->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($isLogged);
$stmt->fetch();
$stmt->close();

// If the user is not logged in
if ($isLogged !== 'YES') {
    header("Location: Log-in.php");
    exit();
}


// RETRIEVE PROFILE PICTURE

$stmt = $conn->prepare(
    "SELECT account_image FROM user_table WHERE account_username = ?"
);

if ($stmt === false) {
    die("Query preparation failed: " . $conn->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// Checking user existence
if ($result->num_rows > 0) {

    $user = $result->fetch_assoc();

    $profile_picture_path =
        $user['account_image'] ?? '';

} else {

    $profile_picture_path = '';

}


// RETRIEVE LOGO

$query = "SELECT logo_image FROM logo_table WHERE logo_id = 'logo'";

$result = $conn->query($query);

$row = $result->fetch_assoc();

$logosak = $row['logo_image'] ?? '';

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($currentTitle) ?> | Sakamoto's</title>
  <link rel="icon" type="image/x-icon" href="<?php echo $logosak; ?>">
  <style>

* {
  font-family: 'Sophia', sans-serif;
  box-sizing: border-box;
}

body {
  margin: 0;
}

/* TOP NAVIGATION BAR */
.top-navbar {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 50px;
  background-color: white;
  border-bottom: 2px solid #d22e2e;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 0 25px;
  z-index: 900;
}

.top-nav-right {
  display: flex;
  align-items: center;
  gap: 15px;
}

.top-nav-button {
  color: #d22e2e;
  text-decoration: none;
  padding: 10px 15px;
  border-radius: 6px;
  font-size: 16px;
  background-color: white;
  transition: 0.2s;
  display: flex;
  align-items: center;
  gap: 5px;
}

.top-nav-button:hover {
  background-color: #d22e2e;
  color: white;
}


/* NOTIFICATION BUTTON */
.notification-button strong {
  font-weight: 700;
}

/* PROFILE */
.profile-button {
  display: flex;
  align-items: center;
  gap: 8px;
}


/* LEFT SIDEBAR */

.navbar * {
  direction: ltr;
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

.navbar a {
  color: white; 
  text-decoration: none;
  padding: 10px 15px;
  margin-bottom: 5px;
  display: block;
  background-color: #d22e2e;
}

.navbar a:hover,
.navbar a.active {
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

/* LOGO INSIDE SIDEBAR */
.profile-container {
  width: 170px;
  height: 170px;
  margin: 42px auto 10px auto;
  border-radius: 50%;
  overflow: hidden;
  background-color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.profile-container img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.profile-name {
  text-align: center;
  color: white;
  font-size: 18px;
  font-weight: bold;
  margin: 0 auto 25px auto;
  max-width: 230px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* SIDEBAR TOGGLE BUTTON */
#toggleBtn {
position: fixed;
  margin-top: -12px;
  top: 15px;
  left: 15px;
  z-index: 1100;
  width: 42px;
  height: 42px;
  padding: 0;
  border: none;
  border-radius: 6px;
  background: #d22e2e;
  color: white;
  cursor: pointer;
  font-size: 20px;
}

#toggleBtn:hover {
  background: #a91f1f;
}

/* PAGE WRAPPER */
.page-wrapper {
  margin-left: 270px;
  transition: none;
}

.page-wrapper.animate {
  transition: margin-left 0.3s ease;
}

.page-wrapper.full {
  margin-left: 0;
}


/* MAIN CONTENT */
.content {
  padding: 50px 0 0 0;
  margin-bottom: 0;
}

#main-content {
  margin-bottom: 0 !important;
  padding-bottom: 0 !important;
  min-height: 0 !important;
  height: auto !important;
}

/* HOME IMAGE */
.imagehome {
  width: 350px;
  height: 350px;
  display: block;
  margin-left: auto;
  margin-right: auto;
  width: 50%;
}

/* SCROLLBAR */
.navbar::-webkit-scrollbar {
  display: none;
}

/* TOP NAV HOME */
.top-nav-home {
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
}

.top-nav-home img {
  width: 42px;
  height: 42px;
  object-fit: contain;
  transition: transform 0.2s ease;
}

.top-nav-home:hover img {
  transform: scale(1.08);
}

/*  FOOTER  */
.site-footer {
  width: 100%;
  background: #d22e2e;
  color: white;
  margin-top: 0;
  margin-bottom: 0;
}

/* MAIN FOOTER AREA */
.footer-content {
  width: 90%;
  max-width: 1200px;
  margin: auto;
  padding: 55px 0;
  display: grid;
  grid-template-columns: 2fr 1fr 1.5fr 1fr;
  gap: 50px;
  margin-bottom: 0;
}

/* FOOTER COLUMNS */
.footer-column {
  display: flex;
  flex-direction: column;
}

/* FOOTER LOGO */
.footer-brand img {
  width: 150px;
  max-width: 100%;
  height: auto;
  object-fit: contain;
  margin-bottom: 15px;
}

/* BRAND DESCRIPTION */
.footer-brand p {
  max-width: 280px;
  font-size: 13px;
  line-height: 1.6;
  margin: 0;
}

/* COLUMN HEADINGS */
.footer-column h3 {
  font-size: 18px;
  margin: 0 0 18px 0;
  font-weight: bold;
}

/* FOOTER LINKS */
.footer-column a {
  color: white;
  text-decoration: none;
  font-size: 13px;
  margin-bottom: 10px;
  transition: 0.2s ease;
}

.footer-column a:hover {
  color: #ffd400;
  padding-left: 5px;
}

/* CONTACT TEXT */
.footer-column p {
  font-size: 13px;
  line-height: 1.5;
  margin: 0 0 10px 0;
}


/* SOCIAL MEDIA */
.footer-socials {
  display: flex;
  flex-direction: column;
}


/* BOTTOM COPYRIGHT */
.footer-bottom {
  border-top: 1px solid rgba(255,255,255,0.3);
  text-align: center;
  padding: 18px 0;
  margin-bottom: 0;
}

.footer-bottom p {
  margin: 0;
  font-size: 12px;
}

/* MOBILE FOOTER */
@media (max-width: 700px) {
  .footer-content {
    width: 85%;
    grid-template-columns: 1fr;
    gap: 35px;
    padding: 45px 0;
}

.footer-brand img {
    width: 130px;
  }

.footer-column h3 {
    margin-bottom: 12px;
  }
}
</style>
</head>

<body>
<!-- TOP NAVIGATION BAR -->
<div class="top-navbar">
  <!-- CENTER HOME LOGO -->
  <a href="?page=home.php" class="top-nav-home">
    <img src="<?php echo $logosak; ?>" alt="Sakamoto's Home"></a>
  <!-- RIGHT SIDE -->
  <div class="top-nav-right">
    <!-- CART -->
    <a href="?page=cart.php" class="top-nav-button">🛒 Cart</a>
    <!-- PROFILE -->
    <a href="?page=user_profile.php" class="top-nav-button profile-button" data-target="?page=user_profile.php" target="_self" style=" display: flex; align-items: center; text-decoration: none;">
      <img src="<?php echo htmlspecialchars($profile_picture_path); ?>" alt="Profile Picture" style="width: 28px; height: 28px; border-radius: 50%; margin-right: 10px;">
      <span> <?php echo htmlspecialchars($username);?></span>
    </a>

    <!-- NOTIFICATIONS -->
    <a href="?page=notifications.php" class="top-nav-button notification-button">🔔
    <?php if ($notification_count > 0): ?>
    <strong>
    Notification (<?= $notification_count ?>)
    </strong>
    <?php else: ?>
    Notifications
    <?php endif; ?>
    </a>
  </div>
</div>

<!-- SIDEBAR TOGGLE BUTTON -->
<button id="toggleBtn" onclick="toggleSidebar()">☰</button>

<!-- LEFT SIDEBAR -->
<div class="navbar" id="sidebar">
  <div class="profile-section">
    <div class="profile-container">
      <img src="<?= htmlspecialchars($profile_picture_path ?: "Sakamoto's.png") ?>" alt="Profile Picture">
    </div>
    <div class="profile-name">
      <a href="index.php?page=user_profile.php">
        <?= htmlspecialchars($username) ?>
      </a>
    </div>
  </div>

  <!-- MENU -->
  <a href="?page=menu.php" class="menu-item <?= $page === 'menu.php' ? 'active' : '' ?>">Menu</a>
  <!-- ORDERS -->
  <a href="?page=orders.php" class="menu-item <?= $page === 'orders.php' ? 'active' : '' ?>">Orders</a>
  <!-- LOGOUT -->
  <a href="Log-in.php" class="logout-button">Log-out</a>
  <div class="space" style="margin-top: 20px;"></div>
</div>


<!-- PAGE WRAPPER -->
<div class="page-wrapper" id="page-wrapper">
<!-- MAIN CONTENT -->
<div class="content" id="main-content">

    <?php
      $filePath = __DIR__ . '/' . $page;
      if (file_exists($filePath)) {
          include $filePath;
      } else {
          echo '<p>Error loading content.</p>';
      }
    ?>
  </div>



  <!-- FOOTER -->
  <footer class="site-footer">
    <div class="footer-content">
      <!-- Brand -->
      <div class="footer-column footer-brand">
        <img src="<?php echo $logosak; ?>" alt="Sakamoto's Logo">
        <p>
          Simmer the flavorful aroma of coffee
          made with care and served fresh.
        </p>
      </div>

      <!-- Quick Links -->
      <div class="footer-column">
        <h3>Quick Links</h3>
        <a href="index.php?page=home.php">Home</a>
        <a href="index.php?page=menu.php">Menu</a>
        <a href="index.php?page=home.php">About Us</a>
        <a href="index.php?page=home.php">Contact Us</a>
      </div>

      <!-- Contact -->
      <div class="footer-column">
        <h3>Contact Us </h3>
        <p>📍 Sakamoto's </p>
        <p> 📞 +63 XXX XXX XXXX </p>
        <p> ✉ sakamotos@example.com </p>
      </div>

      <!-- Social Media -->
      <div class="footer-column">
        <h3> Follow Us </h3>
        <div class="footer-socials">
          <a href="https://www.facebook.com/">Facebook</a>
          <a href="https://instagram.com/">Instagram
          </a><a href="https://tiktok.com/">TikTok</a>
        </div>
      </div>
    </div>

    <!-- Bottom Footer -->
    <div class="footer-bottom">
      <p> © 2026 Sakamoto's. All Rights Reserved.</p>
    </div>
  </footer>
</div>

<!-- JAVASCRIPT -->
<script>
  const sidebar =
    document.getElementById("sidebar");

  const pageWrapper =
    document.getElementById("page-wrapper");

  document.addEventListener(
    "DOMContentLoaded",
    function () {
      /* Restore sidebar state */

      if (
        localStorage.getItem("sidebar")
        === "open"
      ) {

        sidebar.classList.remove("hidden");

        pageWrapper.classList.remove("full");

      } else {

        sidebar.classList.add("hidden");

        pageWrapper.classList.add("full");

      }


      /* Enable animation AFTER the initial position has been established.*/

      requestAnimationFrame(() => {
        sidebar.classList.add("animate");
        pageWrapper.classList.add("animate");
      });
    }
  );

  function toggleSidebar() {
    /* Toggle sidebar */
    sidebar.classList.toggle("hidden");
    /* Toggle the ENTIRE page wrapper. */
    pageWrapper.classList.toggle("full");
    /* Save sidebar state*/

    localStorage.setItem(
      "sidebar",
      sidebar.classList.contains("hidden")
        ? "closed"
        : "open"
    );

  }

</script>
</body>
</html>
