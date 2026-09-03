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

// Query to fetch cart details based on the username
$stmt = $conn->prepare("
    SELECT cart_id, product_name, cup_size, flavor_name, add_ons, cart_quantity, prod_total_price 
    FROM cart_table 
    WHERE acc_username = ?
");

if ($stmt === false) {
    die("Query preparation failed: " . $conn->error);
}
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// Calculate the total price
$total_price = 0;

$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && $_POST['delete'] === 'true') {
    // Decode the received product IDs from the request
    $productIds = json_decode($_POST['product_ids'], true);

    if (is_array($productIds) && !empty($productIds)) {
        // Prepare placeholders for the query
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));

        // Prepare the SQL query
        $sql = "DELETE FROM cart_table WHERE cart_id IN ($placeholders)";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            die("Query preparation failed: " . $conn->error);
        }

        // Bind the product IDs to the query
        $stmt->bind_param(str_repeat('i', count($productIds)), ...$productIds);

        // Execute the query
        if ($stmt->execute()) {
            echo 'success';
        } else {
            echo 'error: ' . $stmt->error;
        }

        // Close the statement
        $stmt->close();
    } else {
        echo 'error: No valid product IDs provided';
    }

    // Close the database connection
    $conn->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['orderDetails'])) {

    $orderDetails = json_decode($_POST['orderDetails'], true);

    if (!is_array($orderDetails) || empty($orderDetails)) {
        echo 'error: No order details received';
        exit;
    }

    /*

       GENERATE ONE QUEUE NUMBER FOR THIS ENTIRE SUBMISSION

    */

    $queueSql = "
        SELECT COALESCE(MAX(queue_number), 0) + 1 AS next_queue
        FROM order_table
    ";

    $queueResult = $conn->query($queueSql);

    if ($queueResult === false) {
        echo 'error generating queue number: ' . $conn->error;
        exit;
    }

    $queueRow = $queueResult->fetch_assoc();
    $queueNumber = (int)$queueRow['next_queue'];


    /*

       INSERT ORDER INTO order_table

    */

$insertSql = "
    INSERT INTO order_table (
        order_username,
        order_product_name,
        order_cup_size,
        order_flavor,
        order_add_on,
        order_quantity,
        order_date,
        order_total_price,
        queue_number,
        queue_status,
        order_status
    )
    SELECT
        ?,
        product_name,
        cup_size,
        flavor_name,
        add_ons,
        cart_quantity,
        NOW(),
        prod_total_price,
        ?,
        'WAITING',
        'PENDING'
    FROM cart_table
    WHERE cart_id = ?
";

    /*

       DELETE PRODUCT FROM CART AFTER INSERTING ORDER

    */

    $deleteSql = "
        DELETE FROM cart_table
        WHERE cart_id = ?
    ";

    /*

       UPDATE ORDER STATUS TO PENDING

    */

    $updateStatusSql = "
        UPDATE order_table
        SET order_status = 'PENDING'
        WHERE order_username = ?
        AND order_product_name = ?
        AND queue_number = ?
    ";


    /*
    
       PREPARE STATEMENTS
    
    */

    $insertStmt = $conn->prepare($insertSql);
    $deleteStmt = $conn->prepare($deleteSql);
    $updateStatusStmt = $conn->prepare($updateStatusSql);

    if (
        $insertStmt === false ||
        $deleteStmt === false ||
        $updateStatusStmt === false
    ) {
        die("Query preparation failed: " . $conn->error);
    }


    /*
    
       PROCESS EVERY SELECTED PRODUCT
       
       IMPORTANT:
       $queueNumber DOES NOT CHANGE INSIDE THIS LOOP.
       Therefore, all products receive the SAME queue number.
    
    */

foreach ($orderDetails as $order) {

    $cartId = $order['productId'];

    /*
    -----------------------------------------------------
       INSERT PRODUCT INTO ORDER TABLE
       order_status = PENDING
       queue_status = WAITING
    -----------------------------------------------------
    */

    $insertStmt->bind_param(
        "sii",
        $username,
        $queueNumber,
        $cartId
    );

    if (!$insertStmt->execute()) {
        echo 'error inserting order: ' . $insertStmt->error;
        $insertStmt->close();
        $deleteStmt->close();
        exit;
    }


    /* DELETE PRODUCT FROM CART */

    $deleteStmt->bind_param("i", $cartId);

    if (!$deleteStmt->execute()) {
        echo 'error deleting from cart: ' . $deleteStmt->error;
        $insertStmt->close();
        $deleteStmt->close();
        exit;
    }
}


    /* CLOSE STATEMENTS */

    $insertStmt->close();
    $deleteStmt->close();
    $updateStatusStmt->close();


    /*
    =========================================================
       RETURN SUCCESS
    =========================================================
    */

    echo 'success';
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="table-5.css">
  <link rel="stylesheet" href="style.php">
<style>
table th:last-child,
table td:last-child {
    width: 300px;
}

table th[colspan="4"],
table td[colspan="4"] {
    width: 300px;
}

table th:first-child,
table td:first-child {
    width: 200px;
}
</style>
</head>
<body>
  <div class="top-header">
    <a href="index.php?page=menu.php" class="back-button">&larr; Menu </a>
      <h1>🛒 Your Cart</h1>
        <button class="ordered-product"onclick="window.location.href='index.php?page=orders.php'">🛎️ Ordered</button>
</div>
<section class="subsection">
  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th><input type="checkbox" class="cart-checkbox" id="select-all" onclick="toggleSelectAll()"></th>
          <th colspan="4" style="text-align:center;">Product</th>
          <th  style="text-align: center;">Quantity</th>
          <th  style="text-align: left;">Price</th>
        </tr>
      </thead>

      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><input type="checkbox" class="cart-checkbox product-checkbox" data-id="<?php echo $row['cart_id']; ?>"></td>
    
    <td colspan="4" style="text-align: center;">
    <?php echo htmlspecialchars($row['product_name']); ?>

    <?php if (!empty($row['cup_size'])): ?>
        <?php echo htmlspecialchars($row['cup_size']); ?> size
    <?php endif; ?>

    <?php if (!empty($row['flavor_name'])): ?>
        <?php echo htmlspecialchars($row['flavor_name']); ?> flavor
    <?php endif; ?>

    <?php if (!empty($row['add_ons'])): ?>
        with <?php echo htmlspecialchars($row['add_ons']); ?>
    <?php endif; ?>
</td>
          <td  style="text-align: center;"><?php echo htmlspecialchars($row['cart_quantity']); ?></td>
          <td><?php echo number_format($row['prod_total_price'], 2); ?></td>
        </tr>
        <?php $total_price += $row['prod_total_price']; ?>
        <?php endwhile; ?>
        <tr>
          <td colspan="6" style="text-align: right; font-weight: bold;">Total:</td>
          <td style="font-weight: bold;"><?php echo number_format($total_price, 2); ?></td>
        </tr>
      </tbody>
    </table>
  </div>
</section>

<div class="cart-actions">
            <button class="select-delete-product"
      onclick="deleteSelectedProducts()">Remove Products</button></td>
            <button class="submit-product" onclick="submitProducts()">Submit to Order</button> </td>
        </div>

<!-- Delete Confirmation Modal -->
<div id="delete-confirm-modal" class="confirm-modal-overlay">
    <div class="confirm-modal">
        <h3>Confirm Deletion</h3>
        <p>Are you sure you want to remove the selected products from the cart?</p>
        <div class="confirm-modal-buttons">
            <button type="button" class="cancel-confirm">Cancel</button>
            <button type="button" class="confirm-action">Yes, Delete</button>
        </div>
    </div>
</div>


<!-- Place Order Confirmation Modal -->
<div id="placeorder-confirm-modal" class="confirm-modal-overlay">
    <div class="confirm-modal">
        <h3>Confirm Place to Order</h3>
        <p>Are you sure you want to place the selected products to order?</p>
        <div class="confirm-modal-buttons">
            <button type="button" class="cancel-confirm">Cancel</button>
            <button type="button" class="confirm-action">Yes, Place to Order</button>
        </div>
    </div>
</div>
</body>

<script>
/* SELECT ALL PRODUCTS */

function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('select-all');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');

    productCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
}

/* DELETE PRODUCTS */
let selectedProductIds = [];
let selectedProductCheckboxes = [];

function deleteSelectedProducts() {

    selectedProductCheckboxes =
        document.querySelectorAll('.product-checkbox:checked');

    selectedProductIds = [];

    selectedProductCheckboxes.forEach(checkbox => {
        selectedProductIds.push(checkbox.dataset.id);
    });

    // Check if anything was selected
    if (selectedProductIds.length > 0) {

        // Show delete confirmation modal
        document.getElementById('delete-confirm-modal').style.display = 'flex';

    } else {

        alert('No product is selected!');
    }
}


/* DELETE MODAL - CANCEL */
document
    .querySelector('#delete-confirm-modal .cancel-confirm')
    .addEventListener('click', function () {

        document.getElementById('delete-confirm-modal').style.display = 'none';

    });


/* DELETE MODAL - CONFIRM */
document
    .querySelector('#delete-confirm-modal .confirm-action')
    .addEventListener('click', function () {

        const formData = new FormData();

        formData.append('delete', 'true');
        formData.append(
            'product_ids',
            JSON.stringify(selectedProductIds)
        );

        fetch('cart.php', {
            method: 'POST',
            body: formData
        })

        .then(response => response.text())

        .then(data => {

            if (data.trim() === 'success') {

                // Close modal
                document.getElementById('delete-confirm-modal').style.display = 'none';

                // Show success message
                alert('Products removed successfully!');

                // Redirect/reload cart
                window.location.href = 'index.php?page=cart.php';

            } else {

                document.getElementById('delete-confirm-modal').style.display = 'none';

                alert('Error removing products.');
            }

        })

        .catch(error => {

            console.error('Error:', error);

            document.getElementById('delete-confirm-modal').style.display = 'none';

            alert('An error occurred while removing the products.');
        });
    });


/* SUBMIT PRODUCTS / PLACE ORDER */

let selectedOrderDetails = [];

function submitProducts() {

    const selectedCheckboxes =
        document.querySelectorAll('.product-checkbox:checked');

    selectedOrderDetails = [];

    selectedCheckboxes.forEach(checkbox => {

    const row = checkbox.closest('tr');

    const productId = checkbox.dataset.id;
    const productName = row.cells[1].innerText.trim();
    const quantity = row.cells[2].innerText.trim();
    const totalPrice = row.cells[3].innerText.trim();

    selectedOrderDetails.push({

        productId: productId,
        productName: productName,
        quantity: quantity,
        totalPrice: totalPrice

    });
});


    // Check if anything was selected
    if (selectedOrderDetails.length > 0) {

        // Show place-order confirmation modal
        document.getElementById('placeorder-confirm-modal').style.display = 'flex';

    } else {

        alert('No product is selected for the order!');
    }
}


/* PLACE ORDER MODAL - CANCEL */

document
    .querySelector('#placeorder-confirm-modal .cancel-confirm')
    .addEventListener('click', function () {

        document.getElementById('placeorder-confirm-modal').style.display = 'none';

    });


/* PLACE ORDER MODAL - CONFIRM */

document
    .querySelector('#placeorder-confirm-modal .confirm-action')
    .addEventListener('click', function () {

        const formData = new FormData();

        formData.append(
            'orderDetails',
            JSON.stringify(selectedOrderDetails)
        );

        fetch('cart.php', {
            method: 'POST',
            body: formData
        })

        .then(response => response.text())

        .then(data => {

            if (data.trim() === 'success') {

                // Close modal
                document.getElementById('placeorder-confirm-modal').style.display = 'none';

                // Show success message
                alert('Order placed successfully!');

                // Redirect to orders page
                window.location.href = 'index.php?page=orders.php';

            } else {

                document.getElementById('placeorder-confirm-modal').style.display = 'none';

                alert('Error placing order.');
            }

        })

        .catch(error => {

            console.error('Error:', error);

            document.getElementById('placeorder-confirm-modal').style.display = 'none';

            alert('An error occurred while placing the order.');
        });
    });


/* PREVENT MODAL CLICK FROM AFFECTING BACKGROUND */

document
    .querySelectorAll('.confirm-modal')
    .forEach(modal => {

        modal.addEventListener('click', function(event) {
            event.stopPropagation();
        });

    });
</script>
</html>
