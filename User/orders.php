
<?php

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

if (!isset($_SESSION['account_username'])) {
    header("Location: Log-in.php");
    exit();
}

$username = $_SESSION['account_username'];


// CHECK IF USER IS LOGGED IN
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

if ($isLogged !== 'YES') {
    header("Location: Log-in.php");
    exit();
}



// GET LOGO
$logoquery = "
    SELECT logo_image
    FROM logo_table
    WHERE logo_id = 'logo'
";

$result = $conn->query($logoquery);

$logosak = '';

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $logosak = $row['logo_image'];
}


// CANCEL ORDER
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['cancel_order'])
) {

    $stmt = $conn->prepare("
        SELECT is_cancelled, order_status
        FROM order_table
        WHERE order_username = ?
        LIMIT 1
    ");

    if ($stmt === false) {
        echo 'error';
        exit();
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {

        $stmt->close();

        echo 'no_orders';
        exit();
    }

    $stmt->bind_result($isCancelled, $orderStatus);
    $stmt->fetch();
    $stmt->close();


    if ($isCancelled === 'CANCEL') {

        echo 'already_cancelled';
        exit();
    }


    $stmt = $conn->prepare("
        UPDATE order_table
        SET
            is_cancelled = 'CANCEL',
            updated_at = NOW()
        WHERE order_username = ?
    ");

    if ($stmt === false) {
        echo 'error';
        exit();
    }

    $stmt->bind_param("s", $username);

    if (!$stmt->execute()) {
        $stmt->close();

        echo 'error';
        exit();
    }

    $stmt->close();


    if ($orderStatus === 'APPROVED') {

        $stmt = $conn->prepare("
            UPDATE order_table
            SET
                order_status = 'PENDING',
                updated_at = NOW()
            WHERE order_username = ?
        ");

        if ($stmt === false) {
            echo 'error';
            exit();
        }

        $stmt->bind_param("s", $username);

        if (!$stmt->execute()) {
            $stmt->close();

            echo 'error';
            exit();
        }

        $stmt->close();
    }


    echo 'success';
    exit();
}


// SUBMIT PAYMENT
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['submit_payment'])
) {

    $payment_method = trim($_POST['payment_method'] ?? '');
    $account_number = trim($_POST['account_number'] ?? '');
    $order_type     = trim($_POST['order_type'] ?? '');

    // CHECK ORDER TYPE
    if ($order_type === '') {

        echo 'no_order_type';
        exit();
    }


    // CHECK PAYMENT METHOD
    if ($payment_method === '') {

        echo 'no_payment';
        exit();
    }

    // CHECK VALID APPROVED ORDER
    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM order_table
        WHERE order_username = ?
        AND order_status = 'APPROVED'
        AND is_cancelled = 'ORDER'
    ");

    if ($stmt === false) {

        error_log(
            "Order count prepare failed: "
            . $conn->error
        );

        echo 'error';
        exit();
    }

    $stmt->bind_param(
        "s",
        $username
    );

    $stmt->execute();

    $stmt->bind_result(
        $paymentOrderCount
    );

    $stmt->fetch();

    $stmt->close();


    if ($paymentOrderCount == 0) {

        echo 'no_orders';
        exit();
    }


    // CASH
    if (
        strtolower($payment_method) === 'cash'
    ) {

        $account_number = '';

    }


    // NON-CASH
    else {

        if ($account_number === '') {

            echo 'no_account_number';
            exit();
        }

    }


    // UPDATE PAYMENT + ORDER TYPE
    $update_payment_query = "
        UPDATE order_table
        SET
            mode_payment = ?,
            account_number = ?,
            order_type = ?,
            updated_at = NOW()
        WHERE order_username = ?
        AND order_status = 'APPROVED'
        AND is_cancelled = 'ORDER'
    ";

    $update_payment_stmt =
        $conn->prepare(
            $update_payment_query
        );


    if ($update_payment_stmt === false) {

        error_log(
            "Payment UPDATE prepare failed: "
            . $conn->error
        );

        echo 'error';
        exit();
    }


    $update_payment_stmt->bind_param(
        "ssss",
        $payment_method,
        $account_number,
        $order_type,
        $username
    );


    // EXECUTE UPDATE
    if (
        !$update_payment_stmt->execute()
    ) {

        error_log(
            "Payment UPDATE failed: "
            . $update_payment_stmt->error
        );

        $update_payment_stmt->close();

        echo 'error';
        exit();
    }


    // CHECK THAT THE UPDATE ACTUALLY RAN
    if (
        $update_payment_stmt->affected_rows > 0
        || $update_payment_stmt->affected_rows === 0
    ) {

        $update_payment_stmt->close();

        echo 'success';
        exit();

    }


    $update_payment_stmt->close();

    echo 'error';
    exit();
}


// ORDER RECEIVED
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['receive_orders'])
) {

    // CHECK IF THERE ARE ORDERS
    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM order_table
        WHERE order_username = ?
        AND is_cancelled = 'ORDER'
    ");

    if ($stmt === false) {
        error_log(
            "Order count prepare failed: " . $conn->error
        );

        echo 'error';
        exit();
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($orderCount);
    $stmt->fetch();
    $stmt->close();


    if ($orderCount == 0) {

        echo 'no_orders';
        exit();
    }


    // COPY ORDER TO HISTORY
    $stmt = $conn->prepare("
        INSERT INTO history_table (
            order_username,
            order_product_name,
            order_flavor,
            order_cup_size,
            order_add_on,
            order_quantity,
            order_date,
            order_total_price,
            order_status,
            is_cancelled,
            cancellation_date,
            mode_payment,
            order_type,
            queue_number
        )
        SELECT
            order_username,
            order_product_name,
            order_flavor,
            order_cup_size,
            order_add_on,
            order_quantity,
            order_date,
            order_total_price,
            order_status,
            is_cancelled,
            cancellation_date,
            mode_payment,
            order_type,
            queue_number
        FROM order_table
        WHERE order_username = ?
        AND is_cancelled = 'ORDER'
    ");

    if ($stmt === false) {

        error_log(
            "History INSERT prepare failed: "
            . $conn->error
        );

        echo 'error';
        exit();
    }


    $stmt->bind_param("s", $username);


    // EXECUTE HISTORY INSERT
    if (!$stmt->execute()) {

        error_log(
            "History INSERT failed: "
            . $stmt->error
        );

        $stmt->close();

        echo 'error';
        exit();
    }


    // MAKE SURE SOMETHING WAS ACTUALLY RECORDED
    $historyInserted = $stmt->affected_rows;

    $stmt->close();


    if ($historyInserted <= 0) {

        error_log(
            "History INSERT completed but inserted 0 rows "
            . "for username: " . $username
        );

        echo 'history_not_recorded';
        exit();
    }


    // DELETE ONLY AFTER HISTORY WAS SUCCESSFULLY RECORDED
    $stmt = $conn->prepare("
        DELETE FROM order_table
        WHERE order_username = ?
        AND is_cancelled = 'ORDER'
    ");

    if ($stmt === false) {

        error_log(
            "Order DELETE prepare failed: "
            . $conn->error
        );

        echo 'error';
        exit();
    }


    $stmt->bind_param("s", $username);


    if (!$stmt->execute()) {

        error_log(
            "Order DELETE failed: "
            . $stmt->error
        );

        $stmt->close();

        echo 'error';
        exit();
    }


    $deletedOrders = $stmt->affected_rows;

    $stmt->close();


    // FINAL SUCCESS
    if ($deletedOrders > 0) {

        echo 'success';
        exit();
    }


    echo 'error';
    exit();
}


// CLEAR CANCELLED ORDERS
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['clear_cancelled_orders'])
) {

    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM order_table
        WHERE order_username = ?
        AND is_cancelled = 'CANCEL'
    ");

    if ($stmt === false) {
        echo 'error';
        exit();
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($cancelledOrderCount);
    $stmt->fetch();
    $stmt->close();


    if ($cancelledOrderCount == 0) {

        echo 'no_orders';
        exit();
    }


    $stmt = $conn->prepare("
        INSERT INTO history_table (
            order_username,
            order_product_name,
            order_flavor,
            order_cup_size,
            order_add_on,
            order_quantity,
            order_date,
            order_total_price,
            order_status,
            is_cancelled,
            cancellation_date,
            mode_payment,
            order_type
        )
        SELECT
            order_username,
            order_product_name,
            order_flavor,
            order_cup_size,
            order_add_on,
            order_quantity,
            order_date,
            order_total_price,
            order_status,
            is_cancelled,
            cancellation_date,
            mode_payment,
            order_type
        FROM order_table
        WHERE order_username = ?
        AND is_cancelled = 'CANCEL'
    ");

    if ($stmt === false) {
        echo 'error';
        exit();
    }

    $stmt->bind_param("s", $username);

    if (!$stmt->execute()) {
        $stmt->close();

        echo 'error';
        exit();
    }

    $stmt->close();


    $stmt = $conn->prepare("
        DELETE FROM order_table
        WHERE order_username = ?
        AND is_cancelled = 'CANCEL'
    ");

    if ($stmt === false) {
        echo 'error';
        exit();
    }

    $stmt->bind_param("s", $username);

    if (!$stmt->execute()) {
        $stmt->close();

        echo 'error';
        exit();
    }

    $stmt->close();


    echo 'success';
    exit();
}


// GET PROFILE PICTURE
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

    $profile_picture_path =
        $user['account_image'] ?? '';

} else {

    $profile_picture_path = '';
}

$stmt->close();


// GET ORDERS
$stmt = $conn->prepare("
    SELECT
        order_product_name,
        order_cup_size,
        order_flavor,
        order_add_on,
        order_quantity,
        order_total_price,
        order_status,
        is_cancelled,
        queue_number,
        mode_payment,
        account_number,
        order_type
    FROM order_table
    WHERE order_username = ?
");

if ($stmt === false) {
    die("Query preparation failed: " . $conn->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();

$has_orders = ($result->num_rows > 0);

$total_price = 0;

$status = '';
$cancelled = '';
$queue_number = '';
$order_type = '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="table-5.css">
    <link rel="stylesheet" href="style.php">
    <style>
        table th:nth-child(1),
        table td:nth-child(1) {
            width: 650px;
        }

        table th:nth-child(6),
        table td:nth-child(6) {
            width: 300px;
        }
    </style>
</head>

<body>


<!-- TOP HEADER -->
<div class="top-header">
    <a href="index.php?page=menu.php" class="back-button">&larr; Menu</a>
    <h1>🛎️ Your Order</h1>
    <button class="ordered-product" onclick="window.location.href='index.php?page=cart.php'">🛒 Cart</button>
</div>

<!-- ORDER TABLE -->
<section class="subsection">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th colspan="4" style="text-align:center;"> Product </th>
                    <th> Quantity </th>
                    <th> Price </th>
                </tr>
            </thead>
            <tbody>

<?php if ($has_orders): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td colspan="4" style="text-align:center;">
                <?php
                echo htmlspecialchars(
                    $row['order_product_name']
                );
                ?>
                <?php if (!empty($row['order_cup_size'])): ?>
                    <?php
                    echo htmlspecialchars(
                        $row['order_cup_size']
                    );
                    ?>
                    size
                <?php endif; ?>

                <?php if (!empty($row['order_flavor'])): ?>
                    <?php
                    echo htmlspecialchars(
                        $row['order_flavor']
                    );
                    ?>
                    flavor

                <?php endif; ?>

                <?php if (!empty($row['order_add_on'])): ?>
                    with
                    <?php
                    echo htmlspecialchars(
                        $row['order_add_on']
                    );
                    ?>
                <?php endif; ?>
            </td>


            <td>
                <?php
                echo htmlspecialchars(
                    $row['order_quantity']
                );
                ?>
            </td>


            <td>
                <?php
                echo number_format(
                    $row['order_total_price'],
                    2
                );
                ?>
            </td>
        </tr>

        <?php

    $total_price +=
        $row['order_total_price'];

    $status =
        $row['order_status'];

    $cancelled =
        $row['is_cancelled'];

    $queue_number =
        $row['queue_number'];
        
    $queue_number =
        $row['queue_number'];

    $mode_payment =
        $row['mode_payment'];

    $account_number =
        $row['account_number'];

    $order_type = 
        $row['order_type'];
        ?>
    <?php endwhile; ?>
<?php endif; ?>


<!-- TOTAL -->
<tr>
    <td colspan="5" style="text-align:right;">Total:</td>
    <td style="font-weight:bold;">
        <?php
        echo number_format(
            $total_price,
            2
        );
        ?>

    </td>

</tr>


<!-- STATUS -->
<tr>
    <td colspan="5" style="text-align:right;">Status:</td>
    <td style="font-weight:bold;">
        <?php
        echo htmlspecialchars(
            $status ?? ''
        );
        ?>

        <?php
        echo htmlspecialchars(
            $cancelled ?? ''
        );
        ?>
    </td>
</tr>

<!-- PAYMENT SECTION -->

<?php if (
    $status === 'APPROVED'
    && $cancelled === 'ORDER'
): ?>

<?php
    // Check whether payment has already been submitted
    $payment_submitted = !empty($mode_payment);
?>

<?php if (!$payment_submitted): ?>

    <!-- PAYMENT SELECTION ROW SHOW ONLY BEFORE PAYMENT SUBMISSION -->
    <tr id="payment-selection-row">

        <td colspan="5" style="text-align:right;">Choose Payment Method:</td>
        <td>
            <select id="payment-method" name="payment_method">

                <option value=""> Select Payment Method</option>
                <option value="Cash"> Cash</option>
                <option value="Credit Card"> Credit Card</option>
                <option value="Debit Card"> Debit Card</option>
                <option value="Bank Transfer"> Bank Transfer</option>
                <option value="Mobile Payment"> Mobile Payment</option>
                <option value="E-Wallet"> E-Wallet</option>
            </select>
        </td>
    </tr>

    <!-- ACCOUNT NUMBER INPUT ONLY FOR CASHLESS PAYMENT -->
    <tr id="account-number-input-row" style="display:none;">
        <td colspan="5" style="text-align:right;">Account Number:</td>
        <td> <input type="text" class="account-number-input" id="account-number" placeholder="Enter account number" autocomplete="off"></td>
    </tr>

<tr id="order-type-selection-row">
    <td colspan="5" style="text-align:right;">Order Type:</td>
    <td>
        <select id="order-type" name="order_type" class="payment-option" style="min-width: 295px;">
            <option value=""> Select Order Type</option>
            <option value="Dine In"> Dine In</option>
            <option value="Take Out"> Take Out</option>
        </select>
    </td>
</tr>
<?php else: ?>

    <!-- SUBMITTED MODE OF PAYMENT SHOW AFTER PAYMENT SUBMISSION -->
    <tr id="payment-selected-row">
        <td colspan="5" style="text-align:right;">Mode of Payment:</td>
        <td id="selected-payment">
            <?php
                echo htmlspecialchars($mode_payment);
            ?>
        </td>
    </tr>

<!-- SUBMITTED ACCOUNT NUMBER SHOW ONLY FOR CASHLESS PAYMENT -->

    <?php if (
        strtolower(trim($mode_payment)) !== 'cash'
        && !empty($account_number)
    ): ?>

        <tr id="account-number-display-row">
            <td colspan="5" style="text-align:right;">Account Number:</td>
            <td id="selected-account-number">
                <?php
                    echo htmlspecialchars($account_number);
                ?>
            </td>
        </tr>
    <?php endif; ?>

<!-- SUBMITTED ORDER TYPE SHOW AFTER PAYMENT SUBMISSION -->

<tr id="order-type-display-row">
    <td colspan="5" style="text-align:right;">Order Type:</td>
    <td id="selected-order-type">
        <?php 
            echo htmlspecialchars($order_type ?? '');
        ?>
    </td>
</tr>

<!-- QUEUE NUMBER SHOW ONLY AFTER PAYMENT SUBMISSION -->
    <tr id="queue-number-row">
        <td colspan="5" style="text-align:right;">QUEUE NUMBER:</td>
        <td id="selected-queue-number" style="font-weight:bold;">
            <?php
                echo '#' . str_pad(
                    $queue_number,
                    3,
                    '0',
                    STR_PAD_LEFT
                );
            ?>
        </td>
    </tr>
<?php endif; ?>
<?php endif; ?>
        </tbody>
    </table>
    </div>

<!-- ACTION BUTTONS -->

<div class="cart-actions">
    <button class="submit-payment" type="button" id="submit-payment-button">Submit Payment</button>
    <button class="cancel-order" type="button" id="cancel-order-button">Cancel Order</button>
    <button class="received-order" type="button" id="received-order-button">Order Received</button>
    <button class="clear-cancel" type="button" id="clear-cancelled-button" style="margin-left:2px;">Clear Cancelled Orders</button>
</div>
</section>
<!-- CANCEL ORDER MODAL-->

<div id="cancel-order-confirm-modal" class="confirm-modal-overlay">
    <div class="confirm-modal">
        <h3> Confirm Cancel Order</h3>
        <p> Are you sure you want to cancel the order?</p>
        <div class="confirm-modal-buttons">
            <button type="button" class="cancel-confirm">Cancel</button>
            <button type="button" class="confirm-action">Yes, Cancel Order</button>
        </div>
    </div>
</div>

<!-- SUBMIT PAYMENT MODAL -->

<div id="submitPAY-confirm-modal" class="confirm-modal-overlay">
    <div class="confirm-modal">
        <h3> Confirm Payment Submission </h3>
        <p> Are you sure you want to submit payment?</p>
        <div class="confirm-modal-buttons">
            <button type="button" class="cancel-confirm">Cancel</button>
            <button type="button" class="confirm-action" style="background-color:green;">Yes, Submit Payment</button>
        </div>
    </div>
</div>

<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {

    // PHP → JAVASCRIPT
    const hasOrders =
        <?php echo $has_orders ? 'true' : 'false'; ?>;

    const orderStatus =
        <?php echo json_encode($status ?? ''); ?>;

    // ELEMENTS
    const cancelButton =
        document.getElementById(
            'cancel-order-button'
        );

    const submitPaymentButton =
        document.getElementById(
            'submit-payment-button'
        );

    const receivedButton =
        document.getElementById(
            'received-order-button'
        );

    const clearCancelledButton =
        document.getElementById(
            'clear-cancelled-button'
        );


    const cancelModal =
        document.getElementById(
            'cancel-order-confirm-modal'
        );

    const submitPaymentModal =
        document.getElementById(
            'submitPAY-confirm-modal'
        );
     
    // PAYMENT VARIABLES
    let selectedPaymentMethod = '';

    let enteredAccountNumber = '';

    let selectedOrderType = '';

    // PAYMENT ELEMENTS
    const paymentMethod =
        document.getElementById(
            'payment-method'
        );

    const accountNumberInputRow =
        document.getElementById(
            'account-number-input-row'
        );

    const accountInput =
        document.getElementById(
            'account-number'
        );

    const orderType =
        document.getElementById(
            'order-type'
        );


     
    // ORDER TYPE SELECTION
    if (orderType) {

        orderType.addEventListener(
            'change',
            function () {
                selectedOrderType =
                    this.value;
            }
        );
    }

    // PAYMENT METHOD SELECTION
    if (paymentMethod) {
        paymentMethod.addEventListener(
            'change',
            function () {
                const selectedValue =
                    this.value;
                
                // NOTHING SELECTED
                if (selectedValue === '') {

                    selectedPaymentMethod = '';
                    enteredAccountNumber = '';

                    if (accountNumberInputRow) {
                        accountNumberInputRow.style.display =
                            'none';
                    }


                    if (accountInput) {
                        accountInput.value = '';
                    }
                    return;
                }

                // SAVE SELECTED PAYMENT METHOD
                selectedPaymentMethod =
                    selectedValue;

                // CASH
                if (
                    selectedValue
                        .trim()
                        .toLowerCase()
                    === 'cash'
                ) {

                    enteredAccountNumber = '';

                    if (accountInput) {
                        accountInput.value = '';
                    }

                    if (accountNumberInputRow) {
                        accountNumberInputRow.style.display =
                            'none';
                    }
                    return;
                }

                // NON-CASH
                if (accountNumberInputRow) {
                    accountNumberInputRow.style.display =
                        'table-row';
                }

                if (accountInput) {
                    accountInput.focus();
                }
            }
        );
    }

    // SEND REQUEST
    function sendOrderRequest(
        formData,
        successMessage
    ) {

        fetch(
            'orders.php',
            {
                method: 'POST',
                body: formData
            }
        )

        .then(
            response =>
                response.text()
        )

        .then(
            data => {

                data = data.trim();

                if (data === 'success') {
                    alert(successMessage);
                    window.location.href =
                        'index.php?page=orders.php';
                    return;
                }

                if (data === 'no_orders') {

                    alert('You did not have any orders');
                    return;
                }

                if (data === 'already_cancelled') {
                    alert('Order already cancelled. Please wait for the approval of the Administrator');
                    return;
                }

                if (data === 'no_payment') {

                    alert('Please select a payment method.');

                    return;
                }

                if (data === 'no_account_number') {
                    alert('Please enter your account number.');
                    return;
                }

                if (data === 'no_order_type') {

                    alert('Please select an order type.');

                    return;
                }

                alert('An error occurred while processing your request.');

                console.error(
                    'Server response:',
                    data
                );

            }
        )

        .catch(
            error => {

                console.error(
                    'Fetch error:',
                    error
                );
                alert('An error occurred while processing your request.');
            }
        );

    }


     
    // CANCEL ORDER BUTTON
    cancelButton.addEventListener(
        'click',
        function () {

            if (!hasOrders) {
                alert('You did not have any orders');
                return;
            }

            cancelModal.style.display =
                'flex';
        }
    );

    // CANCEL MODAL
    cancelModal
        .querySelector('.cancel-confirm')
        .addEventListener(
            'click',
            function () {
                cancelModal.style.display =
                    'none';
            }
        );

    cancelModal
        .querySelector('.confirm-action')
        .addEventListener(
            'click',
            function () {
                cancelModal.style.display =
                    'none';

                const formData =
                    new FormData();

                formData.append(
                    'cancel_order',
                    'true'
                );

                sendOrderRequest(
                    formData,
                    'Order cancelled successfully. Please wait for the approval of the Administrator.'
                );
            }
        );

    // SUBMIT PAYMENT
    submitPaymentButton.addEventListener(
        'click',
        function () {

            if (!hasOrders) {
                alert('You did not have any orders');
                return;
            }

            // PENDING
            if (orderStatus === 'PENDING') {
                alert("WAIT for the Administrator's APPROVAL of ORDER for payment submission");
                return;
            }

            // PAYMENT REQUIRED
            if (selectedPaymentMethod === '') {
                alert('Please select a payment method.');
                return;
            }

            // ORDER TYPE REQUIRED
            if (selectedOrderType === '') {
                alert('Please select an order type.');
                if (orderType) {
                    orderType.focus();
                }
                return;
            }
            
            // CASH
            if (
                selectedPaymentMethod
                    .trim()
                    .toLowerCase()
                === 'cash'
            ) {

                enteredAccountNumber = '';

                const formData =
                    new FormData();

                formData.append(
                    'submit_payment',
                    'true'
                );

                formData.append(
                    'payment_method',
                    selectedPaymentMethod
                );

                formData.append(
                    'account_number',
                    ''
                );

                // IMPORTANT:
                // ADD ORDER TYPE BEFORE SENDING
                formData.append(
                    'order_type',
                    selectedOrderType
                );

                sendOrderRequest(
                    formData,
                    'Payment method updated successfully.'
                );

                return;
            }
            
            // NON-CASH
            if (!accountInput) {
                alert('Please enter your account number.');
                return;
            }

            enteredAccountNumber =
                accountInput.value.trim();

            if (enteredAccountNumber === '') {
                alert('Please enter your account number.');
                accountInput.focus();
                return;
            }


            
            // SHOW CONFIRMATION MODAL
            submitPaymentModal.style.display =
                'flex';

        }
    );


     
    // PAYMENT MODAL - CANCEL
    submitPaymentModal
        .querySelector('.cancel-confirm')
        .addEventListener(
            'click',
            function () {
                submitPaymentModal.style.display =
                    'none';
            }
        );


    // PAYMENT MODAL - CONFIRM
    submitPaymentModal
        .querySelector('.confirm-action')
        .addEventListener(
            'click',
            function () {

                submitPaymentModal.style.display =
                    'none';


                
                // PAYMENT METHOD CHECK
                if (selectedPaymentMethod === '') {
                    alert(
                        'Please select a payment method.'
                    );

                    return;
                }

                // ORDER TYPE CHECK
                if (selectedOrderType === '') {

                    alert(
                        'Please select an order type.'
                    );

                    if (orderType) {

                        orderType.focus();

                    }

                    return;
                }


                
                // CASH
                if (
                    selectedPaymentMethod
                        .trim()
                        .toLowerCase()
                    === 'cash'
                ) {

                    enteredAccountNumber = '';


                    const formData =
                        new FormData();


                    formData.append(
                        'submit_payment',
                        'true'
                    );


                    formData.append(
                        'payment_method',
                        selectedPaymentMethod
                    );


                    formData.append(
                        'account_number',
                        ''
                    );


                    // IMPORTANT:
                    // ADD ORDER TYPE BEFORE SEND

                    formData.append(
                        'order_type',
                        selectedOrderType
                    );


                    sendOrderRequest(
                        formData,
                        'Payment method updated successfully.'
                    );


                    return;
                }


                
                // NON-CASH
                if (!accountInput) {

                    alert(
                        'Please enter your account number.'
                    );

                    return;
                }


                enteredAccountNumber =
                    accountInput.value.trim();


                if (enteredAccountNumber === '') {

                    alert(
                        'Please enter your account number.'
                    );

                    accountInput.focus();

                    return;
                }


                
                // SEND PAYMENT
                const formData =
                    new FormData();


                formData.append(
                    'submit_payment',
                    'true'
                );


                formData.append(
                    'payment_method',
                    selectedPaymentMethod
                );


                formData.append(
                    'account_number',
                    enteredAccountNumber
                );


                // IMPORTANT:
                // ADD ORDER TYPE BEFORE SEND
                formData.append(
                    'order_type',
                    selectedOrderType
                );


                sendOrderRequest(
                    formData,
                    'Payment method updated successfully.'
                );

            }
        );


     
    // ORDER RECEIVED
    receivedButton.addEventListener(
        'click',
        function () {

            if (!hasOrders) {

                alert(
                    'You did not have any orders'
                );

                return;
            }


            const formData =
                new FormData();


            formData.append(
                'receive_orders',
                'true'
            );


            sendOrderRequest(
                formData,
                'Orders cleared successfully.'
            );

        }
    );


     
    // CLEAR CANCELLED ORDERS
    clearCancelledButton.addEventListener(
        'click',
        function () {

            if (!hasOrders) {

                alert(
                    'You did not have any orders'
                );

                return;
            }


            const formData =
                new FormData();


            formData.append(
                'clear_cancelled_orders',
                'true'
            );


            sendOrderRequest(
                formData,
                'Cancelled orders cleared successfully.'
            );

        }
    );


     
    // PREVENT MODAL CLICK
    document
        .querySelectorAll('.confirm-modal')
        .forEach(
            function (modal) {

                modal.addEventListener(
                    'click',
                    function (event) {

                        event.stopPropagation();

                    }
                );

            }
        );

});

</script>

<!-- DATABASE CHANGE CHECK -->

<script>

let lastChange = null;

function checkForChanges() {

    fetch(
        'check_changes.php',
        {
            cache: 'no-store'
        }
    )

    .then(
        response => response.json()
    )

    .then(
        data => {

            const latestChange =
                data.latest_change;

            // First check
            if (lastChange === null) {

                lastChange =
                    latestChange;

                return;
            }

            // Database changed
            if (
                latestChange !== lastChange
            ) {

                console.log(
                    'Order updated:',
                    lastChange,
                    '→',
                    latestChange
                );

                lastChange =
                    latestChange;

                window.location.reload();

            }

        }
    )

    .catch(
        error => {

            console.error(
                'Database change check failed:',
                error
            );

        }
    );
}


// Initial check
checkForChanges();


// Check every 2 seconds
setInterval(
    checkForChanges,
    2000
);

</script>
</body>
</html>
