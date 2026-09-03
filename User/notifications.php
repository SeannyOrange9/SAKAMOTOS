<?php

$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'sakamoto';

$conn = new mysqli(
    $host,
    $username,
    $password,
    $dbname
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// CHECK LOGIN
if (!isset($_SESSION['account_username'])) {
    header("Location: Log-in.php");
    exit;
}

$account_username = $_SESSION['account_username'];


// GET ACCOUNT ID
$account_stmt = $conn->prepare("
    SELECT account_id
    FROM user_table
    WHERE account_username = ?
    LIMIT 1
");

if ($account_stmt === false) {
    die("Account query failed: " . $conn->error);
}

$account_stmt->bind_param(
    "s",
    $account_username
);

$account_stmt->execute();

$account_result = $account_stmt->get_result();


if ($account_result->num_rows === 0) {
    die("User account not found.");
}


$account = $account_result->fetch_assoc();

$account_id = (int)$account['account_id'];

$account_stmt->close();


// GET NOTIFICATIONS
$notification_stmt = $conn->prepare("
    SELECT
        notification_id,
        title,
        message,
        is_read,
        created_at
    FROM notifications
    WHERE account_id = ?
    ORDER BY created_at DESC
");

if ($notification_stmt === false) {
    die("Notification query failed: " . $conn->error);
}

$notification_stmt->bind_param(
    "i",
    $account_id
);

$notification_stmt->execute();

$notification_result =
    $notification_stmt->get_result();


// MARK NOTIFICATIONS AS READ
$read_stmt = $conn->prepare("
    UPDATE notifications
    SET is_read = 1
    WHERE account_id = ?
");

if ($read_stmt !== false) {

    $read_stmt->bind_param(
        "i",
        $account_id
    );

    $read_stmt->execute();

    $read_stmt->close();
}

?>

<div class="notifications-container">

    <h1>Notifications</h1>
    <?php if ($notification_result->num_rows > 0): ?>
        <?php while ($notification = $notification_result->fetch_assoc()): ?>
            <div class="notification-item">
                <h3> <?php echo htmlspecialchars( $notification['title'], ENT_QUOTES,'UTF-8');?> </h3>
                <p> <?php echo nl2br( htmlspecialchars($notification['message'],ENT_QUOTES,'UTF-8'));?> </p>
                <small> <?php echo htmlspecialchars( date('F j, Y h:i A',strtotime($notification['created_at'])),ENT_QUOTES, 'UTF-8');?></small>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p> No notifications yet.</p>
    <?php endif; ?>
</div>
<?php

$notification_stmt->close();
$conn->close();

?>
<style>
/* NOTIFICATIONS PAGE */

.notifications-container {
    width: calc(100% - 30px);
    margin-left: 15px;
    padding-top: 30px;
    padding-bottom: 30px;
    box-sizing: border-box;
}

/* PAGE TITLE */
.notifications-container h1 {
    margin: 0 0 20px 0;
    color: #d22e2e;
    font-size: 28px;
    font-weight: bold;
    text-align: center;
}

/* NOTIFICATION ITEM */
.notification-item {
    background-color: #ffffff;
    border-radius: 10px;
    padding: 18px 20px;
    margin-bottom: 15px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border-left: 5px solid #d22e2e;
    transition: transform 0.2s ease,
                box-shadow 0.2s ease;
}

/* NOTIFICATION HOVER */

.notification-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

/* NOTIFICATION TITLE */

.notification-item h3 {
    margin: 0 0 8px 0;
    color: #d22e2e;
    font-size: 19px;
    font-weight: bold;
}

/* NOTIFICATION MESSAGE */

.notification-item p {
    margin: 0 0 12px 0;
    color: #333333;
    font-size: 15px;
    line-height: 1.6;
}

/* NOTIFICATION DATE */

.notification-item small {
    display: block;
    color: #777777;
    font-size: 13px;
}

/* EMPTY NOTIFICATIONS */
.notifications-container > p {
    background-color: #ffffff;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    color: #777777;
    font-size: 15px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    margin: 0;
}

/* MOBILE RESPONSIVE */
@media (max-width: 600px) {
    .notifications-container {
        width: calc(100% - 20px);
        margin-left: 10px;
        padding-top: 20px;
    }

    .notifications-container h1 {
        font-size: 24px;
    }

    .notification-item {
        padding: 15px;
    }

    .notification-item h3 {
        font-size: 17px;
    }

    .notification-item p {
        font-size: 14px;
    }

    .notification-item small {
        font-size: 12px;
    }
}
</style>
<link rel="stylesheet" href="style.php">