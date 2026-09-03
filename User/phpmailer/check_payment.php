<?php

$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'sakamoto';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

header('Content-Type: application/json');

if (!isset($_GET['order_username']) || empty($_GET['order_username'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No username provided'
    ]);
    exit;
}

$order_username = $_GET['order_username'];

$stmt = $conn->prepare("
    SELECT mode_payment
    FROM order_table
    WHERE order_username = ?
    LIMIT 1
");

$stmt->bind_param("s", $order_username);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $row = $result->fetch_assoc();

    $mode_payment = trim($row['mode_payment'] ?? '');

    echo json_encode([
        'success' => true,
        'payment_received' => ($mode_payment !== ''),
        'mode_payment' => $mode_payment
    ]);

} else {

    echo json_encode([
        'success' => false,
        'message' => 'Order not found'
    ]);
}

$stmt->close();
$conn->close();
?>