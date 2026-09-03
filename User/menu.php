<?php
// Database connection parameters
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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the username session is set
if (!isset($_SESSION['account_username'])) {
    header("Location: Log-in.php");  // Redirect to login if not logged in
    exit();
}

$username = $_SESSION['account_username'];  // Get the username from session

// Query to check if the user is logged in
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

// If the user is not logged in (is_logged is not 'YES')
if ($isLogged !== 'YES') {
    header("Location: Log-in.php");
    exit();
}

// Prepare and execute the query to retrieve the profile picture
$stmt = $conn->prepare("SELECT account_image FROM user_table WHERE account_username = ?");
if ($stmt === false) {
    die("Query preparation failed: " . $conn->error);
}
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// Check if the user exists and retrieve the profile picture
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $profile_picture_path = $user['account_image'] ?? ''; // Default to empty string if not found
} else {
    $profile_picture_path = ''; // Handle case where no user found
}


// Retrieve available categories
$sql = "
    SELECT category_code, category_name
    FROM category_table
    WHERE category_status = 'Available'
    ORDER BY 
        CASE 
            -- COFFEE is ALWAYS first
            WHEN LOWER(category_name) = 'coffee' THEN 0

            -- HOT COFFEE comes before ICED COFFEE
            WHEN LOWER(category_name) = 'hot coffee' THEN 1
            WHEN LOWER(category_name) = 'iced coffee' THEN 2

            -- Other prioritized categories
            WHEN LOWER(category_name) IN ('pastry', 'pastries') THEN 3
            WHEN LOWER(category_name) = 'breakfast' THEN 4
            WHEN LOWER(category_name) IN ('dessert', 'desserts') THEN 5
            WHEN LOWER(category_name) = 'others' THEN 6
            WHEN LOWER(category_name) IN ('beverage', 'beverages') THEN 7

            -- Everything else
            ELSE 8
        END,
        category_name ASC
";

$categoryResult = $conn->query($sql);

$categories = [];

if ($categoryResult && $categoryResult->num_rows > 0) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

$bestSellerQuery = "
    SELECT p.product_name, p.product_price, p.product_image, SUM(h.order_quantity) AS total_quantity
    FROM product_table p
    JOIN history_table h ON p.product_name = h.order_product_name
    WHERE h.is_cancelled = 'ORDER'
    GROUP BY p.product_name
    ORDER BY total_quantity DESC
    LIMIT 1
";
$bestSellerResult = $conn->query($bestSellerQuery);

// Fetch best seller data
if ($bestSellerResult->num_rows > 0) {
    $bestSeller = $bestSellerResult->fetch_assoc();
    $bestSellerName = $bestSeller['product_name'];
    $bestSellerPrice = $bestSeller['product_price'];
    $bestSellerImage = $bestSeller['product_image'];
} else {
    $bestSellerName = $bestSellerPrice = $bestSellerImage = 'N/A';
}

$stmt->close();

$logoquery = "SELECT logo_image FROM logo_table WHERE logo_id = 'logo'";
$result = $conn->query($logoquery);
$row = $result->fetch_assoc();

$logosak = $row['logo_image'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="user-style.css">
  <link rel="stylesheet" href="style.php">
</head>
<body>


<!-- Product Carousel -->
<!-- Product Carousels by Category -->

<?php foreach ($categories as $category): ?>

    <?php
    // Get available products for this category
    $productQuery = "
        SELECT product_name, product_price, product_image, minutes
        FROM product_table
        WHERE category_code = ?
        AND product_status = 'Available'
        ORDER BY product_name ASC
    ";

    $productStmt = $conn->prepare($productQuery);
    $productStmt->bind_param("s", $category['category_code']);
    $productStmt->execute();

    $productResult = $productStmt->get_result();

    // Skip this category if it has no available products
    if ($productResult->num_rows === 0) {
        $productStmt->close();
        continue;
    }
    ?>

    <section class="carousel-section"
             id="category-<?php echo htmlspecialchars($category['category_code']); ?>">
        <h1 style="text-align:center;">
            <?php echo htmlspecialchars($category['category_name']); ?>
        </h1>
        <div class="carousel-container">
            <button class="carousel-btn left" type="button">&#10094;</button>

            <div class="carousel-track">

                <?php while ($product = $productResult->fetch_assoc()): ?>

                    <?php
                    $imagePath = htmlspecialchars($product['product_image']);
                    $productName = htmlspecialchars($product['product_name']);
                    $productPrice = htmlspecialchars($product['product_price']);
                    $minutes = htmlspecialchars($product['minutes']);
                    $categoryCode = htmlspecialchars($category['category_code']);
                    ?>

                    <div class="carousel-item">
                        <a href="index.php?page=product_page.php&product_name=<?php echo urlencode($product['product_name']); ?>&image=<?php echo urlencode($product['product_image']); ?>&category_code=<?php echo urlencode($category['category_code']); ?>" style="text-decoration: none; color: inherit;">
                            <div class="product-card">
                                <div class="product-image-container">
                                    <img src="<?php echo $imagePath; ?>" alt="<?php echo $productName; ?>">
                                </div>
                                <p class="product-name">
                                    <?php echo $productName; ?>
                                </p>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
            <button class="carousel-btn right" type="button"> &#10095;</button>
        </div>
    </section>
    <?php $productStmt->close();?>
<?php endforeach; ?>


<script>
document.addEventListener('DOMContentLoaded', function () {

    /*  CATEGORY CAROUSELS

     * Each .carousel-section gets its own:
     * - carousel-track
     * - left button
     * - right button
     * So clicking Coffee's arrows will NOT
     * move the Milk Tea or other categories.

    */

    document.querySelectorAll('.carousel-section').forEach(function (section) {

        const track = section.querySelector('.carousel-track');
        const leftButton = section.querySelector('.carousel-btn.left');
        const rightButton = section.querySelector('.carousel-btn.right');

        // Make sure all required elements exist
        if (!track || !leftButton || !rightButton) {
            return;
        }

        // Amount to move the carousel
        const scrollAmount = 250;


        /* RIGHT BUTTON */

        rightButton.addEventListener('click', function () {

            track.scrollBy({
                left: scrollAmount,
                behavior: 'smooth'
            });

        });


        /* LEFT BUTTON */

        leftButton.addEventListener('click', function () {

            track.scrollBy({
                left: -scrollAmount,
                behavior: 'smooth'
            });

        });

    });


    /* PRODUCT CARD HOVER EFFECT */

    document.querySelectorAll('.product-card').forEach(function (card) {

        card.addEventListener('mouseenter', function () {

            card.style.transform = 'scale(1.05)';
            card.style.opacity = '0.8';
            card.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.2)';

        });


        card.addEventListener('mouseleave', function () {

            card.style.transform = 'scale(1)';
            card.style.opacity = '1';
            card.style.boxShadow = 'none';

        });

    });

});
</script>
</body>
</html>
