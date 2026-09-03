<?php
// Get the product details from the URL
$product_name = isset($_GET['product_name']) ? $_GET['product_name'] : '';
$image = isset($_GET['image']) ? $_GET['image'] : '';
$category_code = isset($_GET['category_code']) ? $_GET['category_code'] : '';

// Database connection (reuse the connection setup as in your original code)
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'sakamoto';

$conn = new mysqli($host, $username, $password, $dbname);

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

$stmt->close();


// Query to get more product details (optional, for more specific info based on category or product name)
$sql = "SELECT product_name, product_price, minutes FROM product_table WHERE product_name = ? AND category_code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $product_name, $category_code);
$stmt->execute();
$stmt->bind_result($product_name_db, $product_price, $minutes);
$stmt->fetch();
$stmt->close();


$cups_result = $conn->query("
    SELECT 
        cup_key, 
        cup_type, 
        cup_plus_price
    FROM cups_table
    WHERE cup_status = 'Available' AND cup_quantity > 0
");

$cups = [];
if ($cups_result->num_rows > 0) {
    while ($row = $cups_result->fetch_assoc()) {
        $cups[] = $row; // Store cups data in an array
    }
}


$flavor_result = $conn->query("SELECT flavor_key, flavor_name, flavor_price FROM flavor_table
WHERE flavor_status = 'Available' AND flavor_qty > 0"); // Assuming 'flavor' is the table name
$flavors = [];
if ($flavor_result->num_rows > 0) {
    while ($row = $flavor_result->fetch_assoc()) {
        $flavors[] = $row; // Store cups data in an array
    }
}

$add_on_result = $conn->query("SELECT add_on_key, add_on_name, add_on_price FROM add_on_table
WHERE add_on_status = 'Available' AND add_on_qty > 0"); // Assuming 'flavor' is the table name
$add_ons = [];
if ($add_on_result->num_rows > 0) {
    while ($row = $add_on_result->fetch_assoc()) {
        $add_ons[] = $row; // Store cups data in an array
    }
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the posted data
    $quantity = $_POST['cart_quantity'];
    $cup_code = $_POST['cup_size'] ?? null;
    $flavor_code = $_POST['flavor_code'] ?? null;
    $addon_code = $_POST['addon_code'] ?? null;
    
    // Fetch cup, flavor, and addon prices based on selected options
    $cup_price = 0;
    $flavor_price = 0;
    $addon_price = 0;
    $cup_size = '';
    $flavor_name = '';
    $add_ons = '';
    
    $quantity = isset($_POST['cart_quantity']) ? $_POST['cart_quantity'] : 0; // Default to 0 if not set
    
    // If no cup is selected, set a default value
    if (empty($cup_size)) {
        $cup_size = ' ';  // Set a default value for cup_size
    }

    // Initialize flags for insufficient stock
    $insufficient_stock = false;
    $insufficient_message = '';

    // Fetch cup details if selected
    if ($cup_code) {
        $stmt = $conn->prepare("SELECT cup_plus_price, cup_type, cup_quantity FROM cups_table WHERE cup_key = ?");
        $stmt->bind_param("s", $cup_code);
        $stmt->execute();
        $stmt->bind_result($cup_price, $cup_size, $cup_quantity);
        $stmt->fetch();
        $stmt->close();
        
        // Check if the selected quantity is greater than available cup quantity
        if ($quantity > $cup_quantity) {
            $insufficient_stock = true;
            $insufficient_message .= "Not enough cups available. Only $cup_quantity left.";
        }
    }

    // Fetch flavor details if selected
    if ($flavor_code) {
        $stmt = $conn->prepare("SELECT flavor_price, flavor_name, flavor_qty FROM flavor_table WHERE flavor_key = ?");
        $stmt->bind_param("s", $flavor_code);
        $stmt->execute();
        $stmt->bind_result($flavor_price, $flavor_name, $flavor_qty);
        $stmt->fetch();
        $stmt->close();
        
        // Check if the selected quantity is greater than available flavor quantity
        if ($quantity > $flavor_qty) {
            $insufficient_stock = true;
            $insufficient_message .= "Not enough flavors available. Only $flavor_qty left.";
        }
    }

    // Fetch add-on details if selected
    if ($addon_code) {
        $stmt = $conn->prepare("SELECT add_on_price, add_on_name, add_on_qty FROM add_on_table WHERE add_on_key = ?");
        $stmt->bind_param("s", $addon_code);
        $stmt->execute();
        $stmt->bind_result($addon_price, $add_ons, $add_on_qty);
        $stmt->fetch();
        $stmt->close();
        
        // Check if the selected quantity is greater than available add-on quantity
        if ($quantity > $add_on_qty) {
            $insufficient_stock = true;
            $insufficient_message .= "Not enough add-ons available. Only $add_on_qty left.";
        }
    }

    // If insufficient stock, display alert and stop further execution
    if ($insufficient_stock) {
        echo "<script type='text/javascript'>alert('$insufficient_message');</script>";
    } else {
        // Calculate total price (including product price, cup, flavor, and addons)
        $total_price = ($product_price + $cup_price + $flavor_price + $addon_price) * $quantity;

        // Insert product into cart_table
        $stmt = $conn->prepare("INSERT INTO cart_table (acc_username, product_name, cart_quantity, cup_size, flavor_name, add_ons, prod_total_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisssi", $username, $product_name, $quantity, $cup_size, $flavor_name, $add_ons, $total_price);
        if ($stmt->execute()) {
            // If the product is successfully added, output JavaScript alert
            echo "<script>alert('Product successfully added to the cart!');</script>";
            header("Location: index.php?page=menu.php");;
            exit(); // Make sure to call exit() to stop script execution after redirect
        } else {
            // If something went wrong, output JavaScript alert
            echo "<script type='text/javascript'>alert('Failed to add product to the cart.');</script>";
        }
        $stmt->close();
    }
}

$query = "SELECT logo_image FROM logo_table WHERE logo_id = 'logo'";
$result = $conn->query($query);
$row = $result->fetch_assoc();

$logosak = $row['logo_image'];

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
html,
body {
    margin: 0;
    padding: 0;
}

.product-details-container {
    display: flex;
    align-items: flex-start;
    justify-content: center;
    gap: 40px;
    margin-top: 20px;
    margin-left: -75px;
    margin-bottom: 0;
    padding-bottom: 0;
}

.product-image img {
    margin-top: 0;
    max-width: 500px;
    height: auto;
}

.product-info {
    flex: 1;
    max-width: 530px;
    margin-top: 0;
}

.product-info h1 {
    color: #d01e2b;
    text-align: center;
}

.desc-price {
    text-align: center;
}

.product-info form {
    margin: 0;
}

.product-info label {
    display: block;
    margin-top: 10px;
    line-height: 1.2;
    font-size: 1rem;
    color: #d01e2b;
}

.product-info select {
    display: block;
    width: 100%;
    height: 38px;
    margin-top: 10px;
    padding: 8px 10px;
    box-sizing: border-box;
    font-size: 1rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: white;
}

.product-info input {
    width: 100%;
    padding: 10px;
    font-size: 1rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
    outline: none;
    background-color: white;
}

.product-info input:focus {
    border-color: #d22e2e;
}


/* QUANTITY BUTTONS */
.number-input-container {
    display: flex;
    align-items: center;
    justify-content: center;
}

.number-input-container .button {
    font-size: 1.5rem;
    width: 50px;
    height: 50px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 5px;
    background-color: #d01e2b;
    color: white;
    border: none;
    cursor: pointer;
}

.number-input {
    width: 60px !important;
    text-align: center;
    font-size: 20px !important;
    border: 5px solid #d01e2b;
    padding: 5px !important;
    margin: 0 10px;
    height: 50px;
    box-sizing: border-box;
    border-radius: 5px;
}


/* COFFEE OPTIONS GRID */

.coffee-options-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    width: 100%;
    margin-top: 20px;
}

.coffee-option {
    display: flex;
    flex-direction: column;
}

.coffee-option label {
    margin-top: 0;
    margin-bottom: 8px;
}

.coffee-option select {
    width: 100%;
    margin-top: 0;
}


/* ACTION BUTTONS */

.product-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    width: 100%;
    margin-top: 20px;
}

.product-actions button {
    margin-top: 0;
}


/* ADD TO CART */
.add-product-button {
    background-color: #d22e2e;
    color: white;
    font-size: 1rem;
    padding: 10px;
    width: 100%;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.add-product-button:hover {
    background-color: #b02525;
}


/* CLOSE BUTTON */
.close-button {
    background-color: black;
    color: white;
    font-size: 1rem;
    padding: 10px;
    width: 100%;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
</style>
  <link rel="stylesheet" href="style.php">
  <link rel="icon" href="<?php echo $logosak; ?>" type="image/x-icon">
</head>
<body>
  <div class="product-details-container">
    <!-- LEFT SIDE: PRODUCT IMAGE -->
    <div class="product-image">
    <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($product_name); ?>">
    </div>
    <!-- RIGHT SIDE: PRODUCT INFORMATION -->

   <div class="product-info">
      <h1> <?php echo htmlspecialchars($product_name); ?></h1>
      <div class="desc-price">
        <p>Savor the richness and sweetness of <?php echo htmlspecialchars($product_name); ?></p>
        <!-- LIVE PRICE -->
        <p>
          Price: ₱
          <span id="display-price">
            <?php echo number_format((float)$product_price, 2); ?>
          </span>
        </p>
      </div>


      <!-- ONE FORM FOR BOTH COFFEE AND NON-COFFEE -->
      <form method="POST">
        <!-- QUANTITY -->
        <label> Quantity </label>
        <div class="number-input-container">
          <button type="button" class="button" id="decrement-coffee">-</button>
          <input type="number" id="numberInput-coffee" class="number-input" name="cart_quantity" value="1" min="1"/>
          <button type="button" class="button" id="increment-coffee"> + </button>
        </div>

        <?php if (stripos($category_code, 'coffee') !== false): ?>
          <!-- COFFEE OPTIONS -->
          <div class="coffee-options-grid">
            <!-- CUP -->
            <div class="coffee-option">
              <label for="cups-code">Choose Cup:</label>
              <select id="cups-code" name="cup_size" required>
                <option value="">Select Cup</option>

                <?php foreach ($cups as $cup): ?>
                  <?php 
                    // Additional price of the cup
                    $cup_price = (float)$cup['cup_plus_price'];

                    // Product price + cup price
                    $total_price = 
                        (float)$product_price + $cup_price;
                  ?>
                  <option value="<?php echo htmlspecialchars($cup['cup_key']); ?>" data-price="<?php echo $cup_price; ?>">
                    <?php 
                      echo htmlspecialchars($cup['cup_type']) 
                           . " - ₱" 
                           . number_format($total_price, 2); 
                    ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <!-- FLAVOR -->
            <div class="coffee-option">
              <label for="flavor-code">Choose Flavor:</label>
              <select id="flavor-code" name="flavor_code">
              <option value="">Select Flavor</option>

                <?php foreach ($flavors as $flavor): ?>
                  <?php 
                    // Additional flavor price
                    $flavor_price = 
                        (float)$flavor['flavor_price'];
                  ?>
                  <option value="<?php echo htmlspecialchars($flavor['flavor_key']); ?>" data-price="<?php echo $flavor_price; ?>">

                    <?php 
                      echo htmlspecialchars($flavor['flavor_name']) 
                           . " + ₱" 
                           . number_format($flavor_price, 2); 
                    ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- ADD-ONS -->
            <div class="coffee-option">
              <label for="addon-code"> Choose Add-ons:</label>
              <select id="addon-code" name="addon_code">
                <option value=""> Select Add-ons </option>

                <?php foreach ($add_ons as $add_on): ?>
                  <?php 
                    // Additional add-on price
                    $add_on_price = 
                        (float)$add_on['add_on_price'];
                  ?>


                  <option value="<?php echo htmlspecialchars($add_on['add_on_key']); ?>" data-price="<?php echo $add_on_price; ?>">

                    <?php 
                      echo htmlspecialchars($add_on['add_on_name']) 
                           . " + ₱" 
                           . number_format($add_on_price, 2); 
                    ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        <?php endif; ?>

        <!-- TWO BUTTONS -->
        <div class="product-actions">
          <!-- ADD TO CART -->
          <button type="submit" class="add-product-button">Add to Cart</button>

          <!-- CLOSE -->
          <button type="button" class="close-button" onclick="window.location.href='index.php?page=menu.php'">Close</button>
        </div>
      </form>
    </div>
  </div>


<script>

  /* QUANTITY */

  const quantityInput = document.getElementById("numberInput-coffee");
  const incrementButton = document.getElementById("increment-coffee");
  const decrementButton = document.getElementById("decrement-coffee");


  /* LIVE PRICE PREVIEW */

  // Base product price from PHP
  const basePrice = <?php echo json_encode((float)$product_price); ?>;


  // Get the select elements
  const cupSelect = document.getElementById("cups-code");
  const flavorSelect = document.getElementById("flavor-code");
  const addonSelect = document.getElementById("addon-code");


  // Get the price display
  const displayPrice = document.getElementById("display-price");


  /*  CALCULATE CURRENT PRICE */

  function updatePrice() {

    /* Start with the original */
    let unitPrice = basePrice;

    // CUP PRICE
    if (cupSelect && cupSelect.value !== "") {

      const selectedCup =
        cupSelect.options[cupSelect.selectedIndex];

      unitPrice +=
        parseFloat(selectedCup.dataset.price) || 0;

    }

    // FLAVOR PRICE
    if (flavorSelect && flavorSelect.value !== "") {

      const selectedFlavor =
        flavorSelect.options[flavorSelect.selectedIndex];

      unitPrice +=
        parseFloat(selectedFlavor.dataset.price) || 0;

    }

    // ADD-ON PRICE
    if (addonSelect && addonSelect.value !== "") {

      const selectedAddon =
        addonSelect.options[addonSelect.selectedIndex];

      unitPrice +=
        parseFloat(selectedAddon.dataset.price) || 0;

    }


    /* QUANTITY */

    let quantity =
      parseInt(quantityInput.value) || 1;


    // Prevent quantity from going below 1
    if (quantity < 1) {
      quantity = 1;
      quantityInput.value = 1;
    }


    /* FINAL PRICE + Customized price × quantity */
    const totalPrice =
      unitPrice * quantity;


    /* DISPLAY FINAL PRICE */
    displayPrice.textContent =
      totalPrice.toFixed(2);

  }


  /* INCREMENT QUANTITY */
  incrementButton.addEventListener("click", function() {

    let currentValue =
      parseInt(quantityInput.value) || 1;

    quantityInput.value =
      currentValue + 1;

    updatePrice();

  });


  /* DECREMENT QUANTITY */
  decrementButton.addEventListener("click", function() {

    let currentValue =
      parseInt(quantityInput.value) || 1;

    if (currentValue > 1) {

      quantityInput.value =
        currentValue - 1;

      updatePrice();

    }

  });


  /* MANUAL QUANTITY INPUT */

  quantityInput.addEventListener("input", function() {

    updatePrice();

  });


  /* UPDATE WHEN OPTIONS CHANGE */

  if (cupSelect) {

    cupSelect.addEventListener(
      "change",
      updatePrice
    );

  }


  if (flavorSelect) {

    flavorSelect.addEventListener(
      "change",
      updatePrice
    );

  }


  if (addonSelect) {

    addonSelect.addEventListener(
      "change",
      updatePrice
    );

  }


  /* INITIAL PRICE */

  updatePrice();
</script>
</body>
</html>