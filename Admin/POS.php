<?php 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

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

session_start();


$receipt_alert = $_SESSION['receipt_alert'] ?? null;
unset($_SESSION['receipt_alert']);


// CALL NEXT CUSTOMER
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['call_next'])
) {

    // Check if someone is currently being served
    $checkServing = $conn->query("
        SELECT queue_number
        FROM order_table
        WHERE queue_status = 'SERVING'
        AND is_cancelled = 'ORDER'
        LIMIT 1
    ");

    if ($checkServing && $checkServing->num_rows > 0) {

        echo "<script>
            alert('There is already a customer being served.');
            window.location.href = 'Administrator.php?page=POS.php&tab=queue';
        </script>";
        exit;
    }


    // Find the next waiting queue
    $nextQueueQuery = "
        SELECT MIN(queue_number) AS next_queue
        FROM order_table
        WHERE queue_status = 'WAITING'
        AND is_cancelled = 'ORDER'
    ";

    $nextQueueResult = $conn->query($nextQueueQuery);

    if ($nextQueueResult === false) {
        die("Queue query failed: " . $conn->error);
    }

    $nextQueueRow = $nextQueueResult->fetch_assoc();
    $nextQueue = $nextQueueRow['next_queue'];


    if ($nextQueue === null) {

        echo "<script>
            alert('There are no customers waiting in the queue.');
            window.location.href = 'Administrator.php?page=POS.php&tab=queue';
        </script>";
        exit;
    }


    // Set the entire queue number to SERVING
    $stmt = $conn->prepare("
        UPDATE order_table
        SET queue_status = 'SERVING'
        WHERE queue_number = ?
        AND is_cancelled = 'ORDER'
    ");

    if ($stmt === false) {
        die("Queue update failed: " . $conn->error);
    }

    $stmt->bind_param("i", $nextQueue);
    $stmt->execute();
    $stmt->close();


    header("Location: Administrator.php?page=POS.php&tab=queue");
    exit;
}


// MARK CURRENT CUSTOMER AS SERVED
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['mark_served'])
) {

    $stmt = $conn->prepare("
        UPDATE order_table
        SET queue_status = 'SERVED'
        WHERE queue_status = 'SERVING'
        AND is_cancelled = 'ORDER'
    ");

    if ($stmt === false) {
        die("Queue update failed: " . $conn->error);
    }

    $stmt->execute();
    $stmt->close();


    header("Location: Administrator.php?page=POS.php&tab=queue");
    exit;
}


// HANDLE APPROVE CANCELLATION REQUEST

if (
    isset($_POST['approve_cancellation'])
    && isset($_POST['order_username'])
) {

    $order_username = $_POST['order_username'];

    // Query to check the current order status
    $check_query = "
        SELECT order_status 
        FROM order_table 
        WHERE order_username = ?
    ";

    $check_stmt = $conn->prepare($check_query);

    if ($check_stmt === false) {
        die("Query preparation failed: " . $conn->error);
    }

    $check_stmt->bind_param('s', $order_username);
    $check_stmt->execute();

    $check_result = $check_stmt->get_result();


    if ($check_result->num_rows > 0) {

        $row = $check_result->fetch_assoc();

        if ($row['order_status'] === 'APPROVED') {

            header("Location: Administrator.php?page=POS.php&tab=orders&order_username="
                . urlencode($order_username)
                . "&message=already_approved_cancellation");
            exit;

        } else {

            // Update order_status and cancellation_date
            $timestamp = date('Y-m-d H:i:s');

            $update_query = "
    UPDATE order_table 
    SET 
        order_status = 'APPROVED',
        cancellation_date = ?,
        updated_at = NOW()
    WHERE order_username = ?
    ";

            $update_stmt = $conn->prepare($update_query);

            if ($update_stmt === false) {
                die("Query preparation failed: " . $conn->error);
            }

            $update_stmt->bind_param(
                'ss',
                $timestamp,
                $order_username
            );

            $update_stmt->execute();
            $update_stmt->close();


            header(
    "Location: Administrator.php?page=POS.php"
    . "&tab=orders"
    . "&order_username="
    . urlencode($order_username)
    . "&message=cancellation_approved"
);

exit;
        }

    } else {

        header("Location: Administrator.php?page=POS.php&tab=orders&message=no_order");
        exit;
    }

    $check_stmt->close();
}

// HANDLE APPROVE ORDER REQUEST
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['approve_order'])
    && isset($_POST['order_username'])
) {

    $order_username = $_POST['order_username'];


    // Query to check if order is cancelled
    $check_query = "
        SELECT is_cancelled, order_status 
        FROM order_table 
        WHERE order_username = ?
    ";

    $check_stmt = $conn->prepare($check_query);

    if ($check_stmt === false) {
        die("Query preparation failed: " . $conn->error);
    }

    $check_stmt->bind_param(
        's',
        $order_username
    );

    $check_stmt->execute();

    $check_result = $check_stmt->get_result();


    if ($check_result->num_rows > 0) {

        $row = $check_result->fetch_assoc();


        if ($row['is_cancelled'] === 'CANCEL') {

            header("Location: Administrator.php?page=POS.php&tab=orders&order_username="
                . urlencode($order_username)
                . "&message=already_cancelled");
            exit;

        } elseif ($row['order_status'] === 'APPROVED') {

            header("Location: Administrator.php?page=POS.php&tab=orders&order_username="
                . urlencode($order_username)
                . "&message=already_approved");
            exit;

                } else {

            // APPROVE ORDER
            $update_query = "
                UPDATE order_table 
                SET 
                    order_status = 'APPROVED',
                    is_cancelled = 'ORDER',
                    updated_at = NOW()
                WHERE order_username = ?
            ";

            $update_stmt = $conn->prepare($update_query);

            if ($update_stmt === false) {
                die("Query preparation failed: " . $conn->error);
            }

            $update_stmt->bind_param(
                's',
                $order_username
            );

            if (!$update_stmt->execute()) {
                die(
                    "Order approval failed: "
                    . $update_stmt->error
                );
            }

            $update_stmt->close();


            // REDIRECT WITH SUCCESS MESSAGE
            header(
                "Location: Administrator.php?page=POS.php"
                . "&tab=orders"
                . "&order_username="
                . urlencode($order_username)
                . "&message=order_approved"
            );

            exit;
        }

    } else {

        header("Location: Administrator.php?page=POS.php&tab=orders&message=no_order");
        exit;
    }

    $check_stmt->close();
}


// FETCH USERNAMES FOR DROPDOWN
// EXCLUDE USERNAMES WHO ALREADY HAVE:
// 1. order_status = APPROVED
// 2. is_cancelled = CANCEL
// 3. is_cancelled = ORDER
$usernames_query = "
    SELECT 
        ot.order_username,
        MAX(ot.order_date) AS latest_time
    FROM order_table AS ot

    WHERE NOT EXISTS (
        SELECT 1
        FROM order_table AS excluded
        WHERE excluded.order_username = ot.order_username
        AND (
            excluded.order_status = 'APPROVED'
            OR excluded.is_cancelled = 'CANCEL'
            OR excluded.is_cancelled = 'ORDER'
        )
    )

    GROUP BY ot.order_username
    ORDER BY latest_time ASC
";

$usernames_result = $conn->query($usernames_query);

if ($usernames_result === false) {
    die("Username query failed: " . $conn->error);
}



// GET CURRENT TAB
$currentTab = isset($_GET['tab'])
    ? $_GET['tab']
    : 'orders';


// CHECK IF USERNAME IS SELECTED
$selected_username = isset($_GET['order_username'])
    ? trim($_GET['order_username'])
    : '';


// FETCH USERNAMES FOR DROPDOWN
//
// NORMAL USERS:
//   - Do NOT show APPROVED
//   - Do NOT show CANCEL
//   - Do NOT show ORDER
//
// EXCEPTION:
//   - ALWAYS keep the currently selected username visible
//     so automatic page reloads do not remove it.
$usernames_query = "
    SELECT
        ot.order_username,
        MAX(ot.order_date) AS latest_time
    FROM order_table AS ot

    WHERE
        (
            NOT EXISTS (
                SELECT 1
                FROM order_table AS excluded
                WHERE excluded.order_username = ot.order_username
                AND (
                    excluded.order_status = 'APPROVED'
                    OR excluded.is_cancelled = 'CANCEL'
                    OR excluded.is_cancelled = 'ORDER'
                )
            )

            OR

            ot.order_username = ?
        )

    GROUP BY ot.order_username
    ORDER BY latest_time ASC
";

$usernames_stmt = $conn->prepare($usernames_query);

if ($usernames_stmt === false) {
    die("Username query preparation failed: " . $conn->error);
}

$usernames_stmt->bind_param(
    "s",
    $selected_username
);

$usernames_stmt->execute();

$usernames_result = $usernames_stmt->get_result();

if ($usernames_result === false) {
    die("Username query failed: " . $conn->error);
}



// CHECK IF USERNAME IS SELECTED
$selected_username = isset($_GET['order_username'])
    ? $_GET['order_username']
    : '';



// FETCH ORDER DETAILS
$stmt = $conn->prepare("
    SELECT
    queue_number,
    order_product_name,
    order_cup_size,
    order_flavor,
    order_add_on,
    order_quantity,
    order_total_price,
    order_date,
    mode_payment,
    order_status,
    order_type,
    account_number,
    is_cancelled,
    payment_amount,
    change_amount
FROM order_table
WHERE order_username = ?
ORDER BY order_date ASC
");

if ($stmt === false) {
    die("Query preparation failed: " . $conn->error);
}


if ($selected_username) {

    $stmt->bind_param(
        "s",
        $selected_username
    );

    $stmt->execute();

    $result = $stmt->get_result();


    $total_price = 0;
    $payment_amount_db = 0;
    $change_amount_db = 0;

} else {

    $result = null;

    $total_price = 0;
    $payment_amount_db = 0;
    $change_amount_db = 0;
}


// REDUCE INVENTORY / SET ORDER
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['set_order'])
    && isset($_POST['order_username'])
) {

    $order_username = $_POST['order_username'];

    $payment_amount = isset($_POST['payment_amount'])
    ? (float)$_POST['payment_amount']
    : 0;

    // CHECK ORDER APPROVAL STATUS FIRST


    // The administrator must approve the order before
    // Set Order & Send Receipt can continue.
    $approval_check_query = "
        SELECT order_status, is_cancelled
        FROM order_table
        WHERE order_username = ?
        LIMIT 1
    ";

    $approval_check_stmt = $conn->prepare($approval_check_query);

    if ($approval_check_stmt === false) {
        die("Order approval check failed: " . $conn->error);
    }

    $approval_check_stmt->bind_param(
        's',
        $order_username
    );

    $approval_check_stmt->execute();

    $approval_result = $approval_check_stmt->get_result();

    if ($approval_result->num_rows === 0) {

        $approval_check_stmt->close();

        header(
            "Location: Administrator.php?page=POS.php"
            . "&tab=orders"
            . "&message=no_order"
        );

        exit;
    }

    $approval_row = $approval_result->fetch_assoc();

    $order_status_check = trim(
        $approval_row['order_status'] ?? ''
    );

    $is_cancelled_check = trim(
        $approval_row['is_cancelled'] ?? ''
    );

    $approval_check_stmt->close();


    // ORDER IS NOT YET APPROVED
    if ($order_status_check !== 'APPROVED') {

        header(
            "Location: Administrator.php?page=POS.php"
            . "&tab=orders"
            . "&order_username="
            . urlencode($order_username)
            . "&message=approve_first"
        );

        exit;
    }


    // CHECK PAYMENT METHOD AFTER APPROVAL
    $payment_check_query = "
        SELECT mode_payment
        FROM order_table
        WHERE order_username = ?
        LIMIT 1
    ";

    $payment_check_stmt = $conn->prepare($payment_check_query);

    if ($payment_check_stmt === false) {
        die("Payment check failed: " . $conn->error);
    }

    $payment_check_stmt->bind_param(
        's',
        $order_username
    );

    $payment_check_stmt->execute();

    $payment_result = $payment_check_stmt->get_result();

    if ($payment_result->num_rows === 0) {

        $payment_check_stmt->close();

        header(
            "Location: Administrator.php?page=POS.php"
            . "&tab=orders"
            . "&message=no_order"
        );

        exit;
    }

    $payment_row = $payment_result->fetch_assoc();

    $mode_payment = trim($payment_row['mode_payment'] ?? '');

    $payment_check_stmt->close();


    // NO PAYMENT METHOD
    if ($mode_payment === '') {

        header(
            "Location: Administrator.php?page=POS.php"
            . "&tab=orders"
            . "&order_username="
            . urlencode($order_username)
            . "&message=wait_payment"
        );

        exit;
    }

// SAVE PAYMENT AMOUNT AND CHANGE TO DATABASE
if (strtolower(trim($mode_payment)) === 'cash') {

    // Get total order price
    $total_query = "
        SELECT SUM(order_total_price) AS total_price
        FROM order_table
        WHERE order_username = ?
    ";

    $total_stmt = $conn->prepare($total_query);

    if ($total_stmt === false) {
        die("Total query failed: " . $conn->error);
    }

    $total_stmt->bind_param(
        's',
        $order_username
    );

    $total_stmt->execute();

    $total_result = $total_stmt->get_result();
    $total_row = $total_result->fetch_assoc();

    $order_total = (float)($total_row['total_price'] ?? 0);

    $total_stmt->close();


    // Calculate change
    $change_amount = $payment_amount - $order_total;


    // Check if payment is enough
    if ($payment_amount < $order_total) {

        echo "<script>
            alert('Payment amount is not enough to cover the order total.');
            window.history.back();
        </script>";

        exit;
    }


    // SAVE PAYMENT FIRST
    $payment_update_query = "
        UPDATE order_table
        SET
            payment_amount = ?,
            change_amount = ?,
            updated_at = NOW()
        WHERE order_username = ?
    ";

    $payment_update_stmt = $conn->prepare($payment_update_query);

    if ($payment_update_stmt === false) {
        die(
            "Payment UPDATE preparation failed: "
            . $conn->error
        );
    }

    $payment_update_stmt->bind_param(
        'dds',
        $payment_amount,
        $change_amount,
        $order_username
    );

    if (!$payment_update_stmt->execute()) {
        die(
            "Payment UPDATE failed: "
            . $payment_update_stmt->error
        );
    }

    $payment_update_stmt->close();

} else {

    // For cashless payments, these values should be zero.
    $payment_amount = 0;
    $change_amount = 0;

    $payment_update_query = "
        UPDATE order_table
        SET
            payment_amount = 0,
            change_amount = 0,
            updated_at = NOW()
        WHERE order_username = ?
    ";

    $payment_update_stmt =
        $conn->prepare($payment_update_query);

    if ($payment_update_stmt === false) {
        die(
            "Payment UPDATE preparation failed: "
            . $conn->error
        );
    }

    $payment_update_stmt->bind_param(
        's',
        $order_username
    );

    $payment_update_stmt->execute();

    $payment_update_stmt->close();
}

    // PAYMENT METHOD EXISTS
    // CONTINUE WITH INVENTORY REDUCTION
    $order_query = "
        SELECT
            order_product_name,
            order_cup_size,
            order_flavor,
            order_add_on,
            order_quantity
        FROM order_table
        WHERE order_username = ?
    ";

    $order_stmt = $conn->prepare($order_query);

    if ($order_stmt === false) {
        die("Query preparation failed: " . $conn->error);
    }

    $order_stmt->bind_param(
        's',
        $order_username
    );

    $order_stmt->execute();

    $order_result = $order_stmt->get_result();


    if ($order_result->num_rows > 0) {

        while ($order = $order_result->fetch_assoc()) {

            $quantity = (int)$order['order_quantity'];


            // REDUCE CUP INVENTORY
            $cup_type = trim($order['order_cup_size']);

            $update_cup_query = "
                UPDATE cups_table
                SET cup_quantity =
                    CAST(cup_quantity AS UNSIGNED) - ?
                WHERE cup_type = ?
            ";

            $update_cup_stmt =
                $conn->prepare($update_cup_query);

            if ($update_cup_stmt === false) {
                die("Cup UPDATE prepare failed: " . $conn->error);
            }

            $update_cup_stmt->bind_param(
                'is',
                $quantity,
                $cup_type
            );

            if (!$update_cup_stmt->execute()) {
                die(
                    "Cup UPDATE failed: "
                    . $update_cup_stmt->error
                );
            }

            $update_cup_stmt->close();


            // REDUCE FLAVOR INVENTORY
            $flavor_key = $order['order_flavor'];

            if (!empty($flavor_key)) {

                $update_flavor_query = "
                    UPDATE flavor_table
                    SET flavor_qty = flavor_qty - ?
                    WHERE flavor_key = ?
                ";

                $update_flavor_stmt =
                    $conn->prepare($update_flavor_query);

                if ($update_flavor_stmt === false) {
                    die(
                        "Flavor UPDATE prepare failed: "
                        . $conn->error
                    );
                }

                $update_flavor_stmt->bind_param(
                    'is',
                    $quantity,
                    $flavor_key
                );

                $update_flavor_stmt->execute();

                $update_flavor_stmt->close();
            }


            // REDUCE ADD-ON INVENTORY
            if (!empty($order['order_add_on'])) {

                $add_on_key =
                    $order['order_add_on'];

                $update_add_on_query = "
                    UPDATE add_on_table
                    SET add_on_qty = add_on_qty - ?
                    WHERE add_on_key = ?
                ";

                $update_add_on_stmt =
                    $conn->prepare($update_add_on_query);

                if ($update_add_on_stmt === false) {
                    die(
                        "Add-on UPDATE prepare failed: "
                        . $conn->error
                    );
                }

                $update_add_on_stmt->bind_param(
                    'is',
                    $quantity,
                    $add_on_key
                );

                $update_add_on_stmt->execute();

                $update_add_on_stmt->close();
            }
        }


        // INVENTORY UPDATED SUCCESSFULLY
        // NOW SEND RECEIPT BY EMAIL

    // GET CUSTOMER EMAIL

$email_stmt = $conn->prepare("
    SELECT account_id, account_email
    FROM user_table
    WHERE account_username = ?
    LIMIT 1
");


    if ($email_stmt === false) {
        die("Email query failed: " . $conn->error);
    }

    $email_stmt->bind_param(
        "s",
        $order_username
    );

    $email_stmt->execute();

    $email_result = $email_stmt->get_result();

    if ($email_result->num_rows === 0) {

        echo "<script>
            alert('Customer email address was not found.');
            window.location.href =
                'Administrator.php?page=POS.php';
        </script>";

        exit;
    }

    $customer = $email_result->fetch_assoc();

    $customer_email = trim($customer['account_email']);

    $account_id = (int)$customer['account_id'];

    $email_stmt->close();

    

    // GET ORDER INFORMATION
    $receipt_stmt = $conn->prepare("
        SELECT
            queue_number,
            order_product_name,
            order_quantity,
            order_total_price,
            order_date,
            mode_payment,
            order_status,
            account_number,
            order_cup_size,
            order_flavor,
            order_add_on,
            order_type,
            payment_amount,
            change_amount
        FROM order_table
        WHERE order_username = ?
        ORDER BY order_date ASC
    ");

    if ($receipt_stmt === false) {
        die("Receipt query failed: " . $conn->error);
    }

    $receipt_stmt->bind_param(
        "s",
        $order_username
    );

    $receipt_stmt->execute();

    $receipt_result = $receipt_stmt->get_result();


    if ($receipt_result->num_rows === 0) {

        echo "<script>
            alert('No order found for this customer.');
            window.location.href =
                'Administrator.php?page=POS.php';
        </script>";

        exit;
    }


    // BUILD RECEIPT
$receipt_items = '';
$total_price = 0;
$queue_number = null;
$order_date = '';
$mode_payment = '';
$order_status = '';
$order_type = '';

$payment_amount = 0;
$change_amount = 0;

    while ($row = $receipt_result->fetch_assoc()) {


        // Get queue number
        if ($queue_number === null) {
            $queue_number = $row['queue_number'];
        }

        // Get common order information
        $order_date = $row['order_date'];
        $mode_payment = $row['mode_payment'];
        $order_status = $row['order_status'];
        $account_number = $row['account_number'];
        $order_type = $row['order_type'];

        // Get saved payment information from database
        $payment_amount = (float)($row['payment_amount'] ?? 0);
        $change_amount = (float)($row['change_amount'] ?? 0);


        $product_name =
            htmlspecialchars(
                $row['order_product_name'],
                ENT_QUOTES,
                'UTF-8'
            );
        
        $safe_order_type =
    htmlspecialchars(
        strtoupper(trim($order_type ?? '')),
        ENT_QUOTES,
        'UTF-8'
    );

        $quantity = (int)$row['order_quantity'];

        $price = (float)$row['order_total_price'];

        $total_price += $price;


$product_name =
    htmlspecialchars(
        $row['order_product_name'],
        ENT_QUOTES,
        'UTF-8'
    );

// PRODUCT CUSTOMIZATIONS
$product_details = '';


// CUP SIZE
if (!empty(trim($row['order_cup_size'] ?? ''))) {

    $cup_size = htmlspecialchars(
        trim($row['order_cup_size']),
        ENT_QUOTES,
        'UTF-8'
    );

    $product_details .= $cup_size . ' size';
}



// FLAVOR
if (!empty(trim($row['order_flavor'] ?? ''))) {

    $flavor = htmlspecialchars(
        trim($row['order_flavor']),
        ENT_QUOTES,
        'UTF-8'
    );

    if ($product_details !== '') {
        $product_details .= '<br>';
    }

    $product_details .= $flavor . ' flavor';
}



// ADD-ON
if (!empty(trim($row['order_add_on'] ?? ''))) {

    $add_on = htmlspecialchars(
        trim($row['order_add_on']),
        ENT_QUOTES,
        'UTF-8'
    );

    if ($product_details !== '') {
        $product_details .= '<br>';
    }

    $product_details .= 'WITH ' . $add_on;
}


// RECEIPT ROW
$receipt_items .= "
    <tr>
        <td style=' padding: 8px; text-align: center; border-bottom: 1px solid #ddd; vertical-align: top;'>
            {$quantity}
        </td>
        <td style='padding: 8px; border-bottom: 1px solid #ddd; text-align: left;'>
        <strong> {$product_name}</strong>
            " . (
                $product_details !== ''
                ? "<br>{$product_details}"
                : ''
            ) . "
        </td>
        <td style='padding: 8px; text-align: right; border-bottom: 1px solid #ddd; vertical-align: top;'>₱" . number_format($price, 2) . "</td>
    </tr>
";
    }

    $receipt_stmt->close();


    // FORMAT QUEUE NUMBER
    if ($queue_number !== null) {

        $formatted_queue =
            str_pad(
                $queue_number,
                3,
                '0',
                STR_PAD_LEFT
            );

    } else {

        $formatted_queue = '---';
    }


    // ESCAPE CUSTOMER INFORMATION
    $safe_username =
        htmlspecialchars(
            $order_username,
            ENT_QUOTES,
            'UTF-8'
        );

    $safe_email =
        htmlspecialchars(
            $customer_email,
            ENT_QUOTES,
            'UTF-8'
        );

    $safe_payment =
        htmlspecialchars(
            $mode_payment ?? 'N/A',
            ENT_QUOTES,
            'UTF-8'
        );

    $safe_payment_amount =
    number_format(
        $payment_amount,
        2
    );

$safe_change_amount =
    number_format(
        $change_amount,
        2
    );

    $safe_status =
        htmlspecialchars(
            $order_status ?? 'N/A',
            ENT_QUOTES,
            'UTF-8'
        );


    // CREATE EMAIL
    $mail = new PHPMailer(true);


    try {

        // SMTP configuration
        $mail->isSMTP();

        $mail->Host =
            'smtp.gmail.com';

        $mail->SMTPAuth =
            true;

        // SAMPLE EMAIL ONLY
        $mail->Username =
            'example@gmail.com';

        // SAMPLE APP PASSWORD ONLY
        $mail->Password =
            '';

        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port =
            587;


        // SENDER
        $mail->setFrom(
            'example@gmail.com',
            "Sakamoto's"
        );


        // CUSTOMER
        $mail->addAddress(
            $customer_email,
            $order_username
        );


        // EMAIL CONTENT
        $mail->isHTML(true);
        $mail->Subject =
            "Sakamoto's Order Receipt - Queue #"
            . $formatted_queue;
        $mail->Body = "

        <div style='font-family: Arial, sans-serif;max-width: 650px;margin: auto;border: 1px solid #ddd;padding: 25px;'>
            <h1 style='text-align:center;'>Sakamoto's</h1>
            <h2 style='text-align:center;'>Order Receipt</h2>
            <hr>
            <p> <strong>Customer: </strong> {$safe_username}</p>
            <p> <strong>Email: </strong> {$safe_email}</p>
            <p> <strong>Queue Number: </strong> #{$formatted_queue}</p>
            <p>
                <strong>Date:</strong>
                " . htmlspecialchars(
                date('F j, Y h:i A', strtotime($order_date)),
                ENT_QUOTES,
                'UTF-8'
                ) .     "
            </p>
            <p style='
    text-align: center;
    font-weight: bold;
    font-size: 25px;
    margin: 15px 0;
'>
    ====== {$safe_order_type} ======
</p>


<table width='100%' cellspacing='0' cellpadding='0' style='border-collapse: collapse; margin-top: 20px;'>
    <thead>
        <tr>
            <th style =' padding: 8px; text-align: left; border-bottom: 2px solid #333;'> Qty </th>
            <th style=' padding: 8px; text-align: center; border-bottom: 2px solid #333; '> Product / Items </th>
            <th style=' padding: 8px; text-align: right; border-bottom: 2px solid #333;'> Price </th>
        </tr>
        </thead>
        <tbody>
            {$receipt_items}
        </tbody>
        </table>
        
        <div style='margin-top: 20px;'>

    <p>
        <strong>Payment Method:</strong>
        {$safe_payment}
    </p>

    " . (
        strtolower(trim($mode_payment)) === 'cash'
        ? "
            <p>
                <strong>Payment Amount:</strong>
                ₱{$safe_payment_amount}
            </p>

            <p>
                <strong>Change:</strong>
                ₱{$safe_change_amount}
            </p>
        "
        : ''
    ) . "

    <h2 style='text-align:right;'>
        Total: ₱" . number_format($total_price, 2) . "
    </h2>

</div>
            <hr>
            <p style='text-align:center;'>
                Thank you for ordering from Sakamoto's!
            </p>
        </div>
        ";


        // Plain-text fallback
        $mail->AltBody =
    "Sakamoto's Order Receipt\n\n"
    . "Customer: " . $order_username . "\n"
    . "Queue Number: #" . $formatted_queue . "\n"
    . "Date: " . $order_date . "\n"
    . "Order Type: " . $order_type . "\n"
    . "Payment: " . $mode_payment . "\n"
    . (
        strtolower(trim($mode_payment)) === 'cash'
        ? "Payment Amount: ₱"
            . number_format($payment_amount, 2)
            . "\n"
            . "Change: ₱"
            . number_format($change_amount, 2)
            . "\n"
        : ''
    )
    . "Status: " . $order_status . "\n"
    . "Total: ₱"
    . number_format($total_price, 2);


// SEND RECEIPT
//echo "<script>
//    alert('REACHED SEND RECEIPT');
//</script>";

flush();


// CREATE USER NOTIFICATION
$notification_title =
    "Order Receipt Available";

$notification_message =
    "Your order receipt has been sent successfully "
    . "to your email. Queue Number: #"
    . $formatted_queue
    . ".";


// INSERT NOTIFICATION
$notification_stmt = $conn->prepare("
    INSERT INTO notifications
    (
        account_id,
        title,
        message
    )
    VALUES
    (
        ?,
        ?,
        ?
    )
");

if ($notification_stmt !== false) {

    $notification_stmt->bind_param(
        "iss",
        $account_id,
        $notification_title,
        $notification_message
    );

    if (!$notification_stmt->execute()) {

        error_log(
            "Notification insertion failed: "
            . $notification_stmt->error
        );
    }

    $notification_stmt->close();

} else {

    error_log(
        "Notification preparation failed: "
        . $conn->error
    );
}


// SUCCESS ALERT

// At this point:
// 1. Receipt was successfully sent.
// 2. Notification was attempted.
// 3. Show success directly to administrator.

echo "<script>

    alert(" . json_encode(
        "Receipt has been sent successfully."
    ) . ");

    window.location.href =
        'Administrator.php?page=POS.php'
        + '&tab=orders'
        + '&order_username='
        + encodeURIComponent(" . json_encode($order_username) . ");

</script>";

exit;



    } catch (Exception $e) {

        echo "<script>
            alert(
                'Receipt could not be sent. "
                . addslashes($mail->ErrorInfo)
                . "'
            );

            window.location.href =
                'Administrator.php?page=POS.php';
        </script>";

        exit;
    }


    } else {

        $order_stmt->close();

        header(
            "Location: Administrator.php?page=POS.php"
            . "&tab=orders"
            . "&message=no_order"
        );

        exit;
    }

    $order_stmt->close();
}


// DISPLAY POST/REDIRECT/GET SUCCESS OR ERROR MESSAGES
if (isset($_GET['message'])) {

    $messages = [
        'order_approved' => 'Order has been successfully approved.',
        'cancellation_approved' => 'Order cancellation has been successfully approved.',
        'already_approved' => 'Order is already approved.',
        'already_cancelled' => 'Cannot approve. The order is already cancelled.',
        'already_approved_cancellation' => 'Order already approved cancellation.',
        'inventory_updated' => 'Inventory updated successfully.',
        'no_order' => 'No orders found for the selected username.',
        'wait_payment' => "WAIT FOR THE USER'S PAYMENT.",
        'approve_first' => 'ADMIN SHOULD APPROVE THE ORDER FIRST.',
        'receipt_sent' => 'Receipt has been sent successfully.',
        'receipt_failed' => 'Receipt could not be sent. Please check the email settings.'
    ];

    $messageKey = $_GET['message'];

    if (isset($messages[$messageKey])) {

        echo '<script>';

        echo 'alert(' .
            json_encode($messages[$messageKey]) .
            ');';

        // Remove ONLY the message parameter
        echo '
            const url = new URL(window.location.href);
            url.searchParams.delete("message");
            window.history.replaceState({}, document.title, url.toString());
        ';

        echo '</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="tableformat-9.css">
<style>
table th:last-child,
table td:last-child {
    width: 350px;
}

table th:first-child,
table td:first-child {
    width:100px;
}
</style>
</head>
<body>
<!-- POS HEADER -->

<div class="page-header">
    <h1 style="margin-left: 50px;">
        Point of Sales (POS)
    </h1>
</div>

<!-- POS TABS -->
<div class="pos-tabs">
    <a href="Administrator.php?page=POS.php&tab=orders" class="<?php echo ($currentTab === 'orders') ? 'active': ''; ?>">Orders</a>
    
    <a href="Administrator.php?page=POS.php&tab=queue" class="<?php echo ($currentTab === 'queue') ? 'active' : ''; ?>"> Queue</a>
</div>

<!-- ORDERS TAB -->
<?php if ($currentTab === 'orders'): ?>
<div class="page-header username-header">
    <!-- Dropdown for selecting username -->
    <form method="GET" action="Administrator.php">
        <input type="hidden" name="page" value="POS.php">
        <input type="hidden" name="tab" value="orders">
        <select id="order-username" name="order_username" required onchange="this.form.submit()">
            <option value="">Select Username</option>
            <?php while ($row = $usernames_result->fetch_assoc()): ?>
                
                <?php
                $username =
                    htmlspecialchars(
                        $row['order_username']
                    );

                $latest_time =
                    htmlspecialchars(
                        date(
                            "h:i:s A",
                            strtotime(
                                $row['latest_time']
                            )
                        )
                    );
                ?>


                <option value="<?php echo $username; ?>"<?php
                    echo ($username == $selected_username) ? 'selected' : ''; ?>>

                    <?php
                    echo "{$username} ({$latest_time})";
                    ?>
                </option>
            <?php endwhile; ?>
        </select>
    </form>
</div>


<section class="subsection">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th colspan="4" style="text-align:center;">Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if (
                $selected_username
                && $result
                && $result->num_rows > 0
            ):
            ?>

                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <!-- PRODUCT -->
                        <td colspan="4" style="text-align: center;">
                            <?php
                            echo htmlspecialchars(
                                $row['order_product_name']
                            );
                            ?>

                            <?php if (!empty($row['order_cup_size'])): ?>
                                <?php
                                echo ' ' . htmlspecialchars(
                                    $row['order_cup_size']
                                ) . ' size';
                                ?>
                            <?php endif; ?>

                            <?php if (!empty($row['order_flavor'])): ?>
                                <?php
                                echo ' ' . htmlspecialchars(
                                    $row['order_flavor']
                                ) . ' flavor';
                                ?>
                            <?php endif; ?>

                            <?php if (!empty($row['order_add_on'])): ?>
                                <?php
                                echo ' with ' . htmlspecialchars(
                                    $row['order_add_on']
                                );
                                ?>
                            <?php endif; ?>
                        </td>

                        <!-- QUANTITY -->
                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['order_quantity']
                            );
                            ?>
                        </td>

                        <!-- PRICE -->
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

                    $mode_payment =
                        $row['mode_payment'];

                    $account_number =
                        $row['account_number'];
                    
                    $order_type =
                        $row['order_type'];
                    
                    $payment_amount_db =
                        (float)($row['payment_amount'] ?? 0);

                    $change_amount_db =
                        (float)($row['change_amount'] ?? 0);
                    ?>

                <?php endwhile; ?>


                <!-- TOTAL -->
                <tr>
                    <td colspan="5" style="text-align: right; font-weight: bold;">Total: </td>
                    <td style="font-weight: bold;">
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
                <td colspan="5" style="text-align: right; font-weight: bold;">Status:</td>
                    <td style="font-weight: bold;">
                        <?php echo htmlspecialchars($status);?>
                        <?php echo htmlspecialchars($cancelled);?>
                    </td>
                </tr>

<!-- PAYMENT MODE -->

<?php

$payment_value = trim($mode_payment ?? '');

$display_payment = $payment_value !== ''
    ? $payment_value
    : 'WAITING FOR PAYMENT';

?>

<!-- ORDER TYPE -->
<tr>
    <td colspan="5" style="text-align: right; font-weight: bold;">Order Type:</td>
    <td style="font-weight: bold;">
        <?php
        echo !empty($order_type)
            ? htmlspecialchars(
                $order_type,
                ENT_QUOTES,
                'UTF-8'
            )
            : 'N/A';
        ?>
    </td>

</tr>

<tr>
    <td colspan="5" style="text-align: right; font-weight: bold;">Mode of Payment:</td>
    <td style="font-weight: bold;">
        <?php echo htmlspecialchars($display_payment, ENT_QUOTES, 'UTF-8');?>
    </td>
</tr>


<!-- ACCOUNT NUMBER FOR CASHLESS PAYMENT -->
<?php 
if (
    $payment_value !== ''
    && strtolower(trim($payment_value)) !== 'cash'
):
?>

<tr>
    <td colspan="5" style="text-align: right; font-weight: bold;">Account Number:</td>
    <td style="font-weight: bold;">
        <?php
        if (!empty($account_number)) {
            echo htmlspecialchars(
                $account_number,
                ENT_QUOTES,
                'UTF-8'
            );
        } else {
            echo 'N/A';
        }
        ?>
    </td>
</tr>
<?php endif; ?>


<!-- CASH PAYMENT DETAILS -->
<?php
if (
    strtolower($payment_value) === 'cash'
):
?>

<?php
// Check whether payment has already been saved
$payment_saved = $payment_amount_db > 0;
?>

<?php if (!$payment_saved): ?>

    <!-- CASH PAYMENT INPUT -->

    <tr>
        <td colspan="5" style="text-align: right; font-weight: bold;"> Payment Amount:</td>
        <td>
            <input class="amount-input" type="number" id="payment_amount" name="payment_amount" placeholder="Enter amount" step="0.01" min="0" oninput="calculateChange()">
        </td>
    </tr>

    <tr id="change_row" style="display: none;">
        <td colspan="5" style="text-align: right; font-weight: bold;"> Change: </td>

        <td id="change_amount" style="font-weight: bold;">0.00</td>
    </tr>

<?php else: ?>

    <!-- SAVED CASH PAYMENT FROM DATABASE -->
    <tr>
        <td colspan="5" style="text-align: right; font-weight: bold;">Payment Amount:</td>

        <td style="font-weight: bold;">
            <?php
            echo number_format(
                $payment_amount_db,
                2
            );
            ?>
        </td>
    </tr>

    <tr>
        <td colspan="5" style="text-align: right; font-weight: bold;">Change:</td>
        <td style="font-weight: bold;">
            <?php
            echo number_format(
                $change_amount_db,
                2
            );
            ?>
        </td>
    </tr>
        <?php endif; ?>
        <?php endif; ?>
    <?php else: ?>
                
        <tr>
        <td colspan="6">No orders found for the selected username.</td>
        </tr>
        <?php endif; ?>
    </tbody>
    </table>
    </div>

    <!-- ACTION BUTTONS -->

    <div class="cart-actions">
        <form method="POST" action="" onsubmit="copyPaymentAmount()">
            <input type="hidden" name="order_username" value="<?php echo htmlspecialchars($selected_username); ?>">
            <!-- CASH PAYMENT AMOUNT -->
    <input type="hidden" name="payment_amount" id="hidden_payment_amount" value="">
        <button type="submit" name="approve_cancellation" class="approve-cancel">Approve Cancellation</button>
        <button type="submit" name="set_order" class="send-receipt">Set Order & Send Receipt via Email</button>
        <button type="submit" class="approve-order" name="approve_order">Approve Order</button>
        </form>
    </div>
</section>
<?php endif; ?>



<!-- QUEUE TAB -->
<?php if ($currentTab === 'queue'): ?>
<?php

// GET CURRENTLY SERVING QUEUE
$servingQuery = "
    SELECT
        queue_number,
        order_username
    FROM order_table
    WHERE queue_status = 'SERVING'
    AND is_cancelled = 'ORDER'
    GROUP BY queue_number, order_username
    ORDER BY queue_number ASC
    LIMIT 1
";

$servingResult =
    $conn->query($servingQuery);

// GET WAITING QUEUE
$waitingQuery = "
    SELECT
        queue_number,
        order_username,
        MIN(order_date) AS queue_time
    FROM order_table
    WHERE queue_status = 'WAITING'
    AND is_cancelled = 'ORDER'
    GROUP BY queue_number, order_username
    ORDER BY queue_number ASC
";

$waitingResult =
    $conn->query($waitingQuery);

?>


<div class="queue-container">

<!-- NOW SERVING -->
    <div class="current-serving" id="current-serving">
        <h2> NOW SERVING </h2>

        <?php
        if (
            $servingResult
            && $servingResult->num_rows > 0
        ):
        ?>

            <?php
            $serving =
                $servingResult->fetch_assoc();
            ?>


            <div class="serving-number" id="serving-number">
                #
                <?php
                echo str_pad(
                    $serving['queue_number'],
                    3,
                    '0',
                    STR_PAD_LEFT
                );
                ?>
            </div>


            <div class="serving-customer" id="serving-customer">
                <?php
                echo htmlspecialchars(
                    $serving['order_username']
                );
                ?>
            </div>


<form method="POST">
<input type="hidden" name="tab" value="queue">
<button type="submit"name="mark_served" class="queue-served-button">MARK AS SERVED</button>
</form>
        <?php else: ?>
        <div class="no-serving"> No customer currently being served.</div>
        <?php endif; ?>
    </div>



    <!-- WAITING QUEUE -->
    <div class="waiting-section">
        <div class="waiting-header">
            <h2> WAITING QUEUE</h2>
            <form method="POST">
                <input type="hidden" name="tab" value="queue">
                <button type="submit" name="call_next" class="call-next-button">CALL NEXT</button>
            </form>
        </div>


        <div class="queue-table-container">
            <table class="queue-table" id="waiting-queue-table">
                <thead>
                    <tr>
                        <th> Queue #</th>
                        <th> Customer</th>
                        <th> Status </th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if (
                    $waitingResult
                    && $waitingResult->num_rows > 0
                ):
                ?>

                    <?php
                    while (
                        $queue =
                        $waitingResult->fetch_assoc()
                    ):
                    ?>


                        <tr>


                            <td class="queue-number-cell">

                                #

                                <?php

                                echo str_pad(
                                    $queue['queue_number'],
                                    3,
                                    '0',
                                    STR_PAD_LEFT
                                );

                                ?>

                            </td>
                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $queue['order_username']
                                );
                                ?>
                            </td>
                            <td> <span class="waiting-status">WAITING</span> </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                    <td colspan="3">No customers are currently waiting.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>



<script>
function calculateChange() {

    const paymentAmount =
        parseFloat(
            document.getElementById(
                'payment_amount'
            ).value
        );

    const totalPrice =
        <?php echo $total_price; ?>;

    const changeRow =
        document.getElementById(
            'change_row'
        );

    const changeAmount =
        document.getElementById(
            'change_amount'
        );


    if (
        !isNaN(paymentAmount)
        && paymentAmount >= totalPrice
    ) {

        const change =
            paymentAmount - totalPrice;

        changeAmount.textContent =
            change.toFixed(2);

        changeRow.style.display =
            'table-row';

    } else {

        changeAmount.textContent =
            '0.00';

        changeRow.style.display =
            'none';
    }
}

function copyPaymentAmount() {

    const paymentInput =
        document.getElementById('payment_amount');

    const hiddenPaymentInput =
        document.getElementById('hidden_payment_amount');

    if (paymentInput && hiddenPaymentInput) {

        hiddenPaymentInput.value =
            paymentInput.value;
    }
}
</script>

<script>
let lastChange = null;

function checkForChanges() {

    fetch('check_changes.php')
        .then(response => response.json())
        .then(data => {

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
                latestChange !=
                lastChange
            ) {

                lastChange =
                    latestChange;

                window.location.reload();

            }

        })
        .catch(error => {

            console.error(
                'Database change check failed:',
                error
            );

        });
}


// Check every 2 seconds
setInterval(
    checkForChanges,
    2000
);


// Initial check
checkForChanges();

</script>
</body>
</html>