<?php

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['account_username'])) {
    http_response_code(401);

    echo json_encode([
        'error' => 'Not logged in'
    ]);

    exit;
}

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

    http_response_code(500);

    echo json_encode([
        'error' => 'Database connection failed'
    ]);

    exit;
}

$username = $_SESSION['account_username'];

$stmt = $conn->prepare("
    SELECT
        MAX(updated_at) AS latest_change
    FROM order_table
    WHERE order_username = ?
");

if ($stmt === false) {

    http_response_code(500);

    echo json_encode([
        'error' => 'Query preparation failed'
    ]);

    $conn->close();

    exit;
}

$stmt->bind_param(
    "s",
    $username
);

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

echo json_encode([
    'latest_change' =>
        $row['latest_change'] ?? ''
]);

$stmt->close();
$conn->close();

?>