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

$username = $_SESSION['account_username'];  // Get the username from session

// CHECK IF USER IS LOGGED IN

$sql = "SELECT is_logged 
        FROM user_table 
        WHERE account_username = ?";

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
$stmt = $conn->prepare("
    SELECT account_image 
    FROM user_table 
    WHERE account_username = ?
");

if ($stmt === false) {
    die("Query preparation failed: " . $conn->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $user = $result->fetch_assoc();

    $profile_picture_path = $user['account_image'] ?? '';

} else {

    $profile_picture_path = '';
}

$stmt->close();

// RETRIEVING SLIDESHOW FILES

$query = "
    SELECT slideshow_file 
    FROM slideshow_table 
    WHERE slideshow_code = 1
";

$result = $conn->query($query);
$row = $result->fetch_assoc();

$slideshow1 = $row['slideshow_file'];


$query = "
    SELECT slideshow_file 
    FROM slideshow_table 
    WHERE slideshow_code = 2
";

$result = $conn->query($query);
$row = $result->fetch_assoc();

$slideshow2 = $row['slideshow_file'];


$query = "
    SELECT slideshow_file 
    FROM slideshow_table 
    WHERE slideshow_code = 3
";

$result = $conn->query($query);
$row = $result->fetch_assoc();

$slideshow3 = $row['slideshow_file'];


$query = "
    SELECT slideshow_file 
    FROM slideshow_table 
    WHERE slideshow_code = 4
";

$result = $conn->query($query);
$row = $result->fetch_assoc();

$slideshow4 = $row['slideshow_file'];


$query = "
    SELECT slideshow_file 
    FROM slideshow_table 
    WHERE slideshow_code = 5
";

$result = $conn->query($query);
$row = $result->fetch_assoc();

$slideshow5 = $row['slideshow_file'];

// RETRIEVING HOME IMAGE BUTTONS

$query = "
    SELECT home_image 
    FROM home_image_table 
    WHERE home_image_code = 1
";

$result = $conn->query($query);
$row = $result->fetch_assoc();

$home_image1 = $row['home_image'];


$query = "
    SELECT home_image 
    FROM home_image_table 
    WHERE home_image_code = 2
";

$result = $conn->query($query);
$row = $result->fetch_assoc();

$home_image2 = $row['home_image'];

// RETRIEVING HOME IMAGE DESCRIPTIONS

$query = "
    SELECT home_image_description 
    FROM home_image_table 
    WHERE home_image_code = 1
";

$result = $conn->query($query);
$row = $result->fetch_assoc();

$home_image_description1 = $row['home_image_description'];


$query = "
    SELECT home_image_description 
    FROM home_image_table 
    WHERE home_image_code = 2
";

$result = $conn->query($query);
$row = $result->fetch_assoc();

$home_image_description2 = $row['home_image_description'];


// BEST SELLERS

// This array will contain the products and can be displayed in the 6 carousel slots.
$bestSellers = [];


// GET TOP 6 BEST SELLING PRODUCTS

$bestSellerQuery = "
    SELECT 
        p.product_name,
        p.product_price,
        p.product_image,
        p.category_code,
        SUM(h.order_quantity) AS total_quantity

    FROM product_table p

    JOIN history_table h 
        ON p.product_name = h.order_product_name

    WHERE h.is_cancelled = 'ORDER'

    GROUP BY 
        p.product_name,
        p.product_price,
        p.product_image,
        p.category_code

    ORDER BY total_quantity DESC

    LIMIT 6
";

$bestSellerResult = $conn->query($bestSellerQuery);

if ($bestSellerResult && $bestSellerResult->num_rows > 0) {

    while ($row = $bestSellerResult->fetch_assoc()) {

        $bestSellers[] = $row;
    }
}


// FILL REMAINING SLOTS WITH RANDOM PRODUCTS

$remainingSlots = 6 - count($bestSellers);


// Only fill slots if there are fewer than 6 best sellers.
if ($remainingSlots > 0) {

    // Store names of products already selected

    $bestSellerNames = [];

    foreach ($bestSellers as $product) {

        $bestSellerNames[] = $product['product_name'];
    }


    // SOME BEST SELLERS EXIST

    if (count($bestSellerNames) > 0) {

        // Safely escape product names for NOT IN
        $escapedNames = [];

        foreach ($bestSellerNames as $name) {

            $escapedNames[] = "'" .
                $conn->real_escape_string($name) .
                "'";
        }

        $excludedProducts = implode(',', $escapedNames);


        // Get random products that aren't already
        // in the best-seller list.
        $randomQuery = "
            SELECT 
                product_name,
                product_price,
                product_image,
                category_code

            FROM product_table

            WHERE product_name NOT IN ($excludedProducts)

            ORDER BY RAND()

            LIMIT $remainingSlots
        ";

        $randomResult = $conn->query($randomQuery);

        if ($randomResult) {

            while ($row = $randomResult->fetch_assoc()) {

                $bestSellers[] = $row;
            }
        }


    // NO BEST SELLERS EXIST

    } else {

        // If there are no completed orders at all,
        // simply select random products.
        $randomQuery = "
            SELECT 
                product_name,
                product_price,
                product_image,
                category_code

            FROM product_table

            ORDER BY RAND()

            LIMIT $remainingSlots
        ";

        $randomResult = $conn->query($randomQuery);

        if ($randomResult) {

            while ($row = $randomResult->fetch_assoc()) {

                $bestSellers[] = $row;
            }
        }
    }
}

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
<section class="slideshow">
  <div class="slides">
    <img src="<?php echo $slideshow1; ?>" alt="Slideshow1" class="active">
    <img src="<?php echo $slideshow2; ?>" alt="Slideshow2">
    <img src="<?php echo $slideshow3; ?>" alt="Slideshow3">
    <img src="<?php echo $slideshow4; ?>" alt="Slideshow4">
    <img src="<?php echo $slideshow5; ?>" alt="Slideshow5">
  </div>
  <!-- Arrow buttons -->
  <a class="prev">&#10094;</a>
  <a class="next">&#10095;</a>
</section>

<!-- Product Carousel -->
<section class="carousel-section">
    <h2>Best Sellers</h2>
    <div class="carousel-container">
        <button class="carousel-btn left" type="button">&#10094;</button>
        <div class="carousel-track">
            <?php foreach ($bestSellers as $product): ?>
                <?php
                $productName = htmlspecialchars($product['product_name']);
                $productImage = htmlspecialchars($product['product_image']);
                $categoryCode = htmlspecialchars($product['category_code']);

                $productLink =
                    "index.php?page=product_page.php&product_name="
                    . urlencode($product['product_name'])
                    . "&image="
                    . urlencode($product['product_image'])
                    . "&category_code="
                    . urlencode($product['category_code']);
                ?>

                <div class="carousel-item">
                    <a href="<?php echo $productLink; ?>" style="text-decoration: none; color: inherit;">
                        <div class="product-card">
                            <div class="product-image-container"> <img src="<?php echo $productImage; ?>" alt="<?php echo $productName; ?>"></div>
                            <p class="product-name"><?php echo $productName; ?></p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-btn right" type="button">&#10095;</button></div>

</section>
<!-- Feature / About Food Section -->
<section class="food-features">
    <!-- Feature 1 -->
    <div class="food-feature feature-one">
        <div class="food-image"><img src="<?php echo $home_image1; ?>" alt="Home Image 1"></div>
        <div class="food-text"><p><?php echo $home_image_description1; ?></p></div>
    </div>

<div style="height:20px;"></div>
    <!-- Feature 2 -->
    <div class="food-feature feature-two">
        <div class="food-image"><img src="<?php echo $home_image2; ?>" alt="Home Image 2"></div>
        <div class="food-text"> <p><?php echo $home_image_description2; ?></p></div>
    </div>
</section>
</body>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const slides = document.querySelectorAll('.slides img');
    const prevButton = document.querySelector('.prev');
    const nextButton = document.querySelector('.next');
    let index = 0;

    // Show the first slide by default
    slides[index].classList.add('active');

    // Function to change slide
    function changeSlide(newIndex) {
      slides[index].classList.remove('active');
      index = newIndex;
      slides[index].classList.add('active');
    }

    // Auto-play every 5 seconds
    setInterval(() => {
      let nextIndex = (index + 1) % slides.length;
      changeSlide(nextIndex);
    }, 5000);

    // Handle next and previous navigation
    nextButton.addEventListener('click', () => {
      let nextIndex = (index + 1) % slides.length;
      changeSlide(nextIndex);
    });

    prevButton.addEventListener('click', () => {
      let prevIndex = (index - 1 + slides.length) % slides.length;
      changeSlide(prevIndex);
    });
  });

  // Carousel
const track = document.querySelector('.carousel-track');

document.querySelector('.carousel-btn.right').onclick = () => {
    track.scrollBy({
        left:250,
        behavior:'smooth'
    });
};

document.querySelector('.carousel-btn.left').onclick = () => {
    track.scrollBy({
        left:-250,
        behavior:'smooth'
    });
};

// BEST SELLERS CAROUSEL
document.querySelectorAll('.carousel-section').forEach(function (section) {

    const track = section.querySelector('.carousel-track');
    const leftButton = section.querySelector('.carousel-btn.left');
    const rightButton = section.querySelector('.carousel-btn.right');

    // Make sure all required elements exist
    if (!track || !leftButton || !rightButton) {
        return;
    }

    const scrollAmount = 250;

    // RIGHT BUTTON
    rightButton.addEventListener('click', function () {

        track.scrollBy({
            left: scrollAmount,
            behavior: 'smooth'
        });

    });

    // LEFT BUTTON
    leftButton.addEventListener('click', function () {

        track.scrollBy({
            left: -scrollAmount,
            behavior: 'smooth'
        });

    });

});


// PRODUCT CARD HOVER EFFECT
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
</script>
</html>
