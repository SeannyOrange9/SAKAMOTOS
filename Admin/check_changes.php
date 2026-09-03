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

    http_response_code(500);

    header('Content-Type: application/json');

    echo json_encode([
        'error' => 'Database connection failed'
    ]);

    exit;
}

header('Content-Type: application/json');

$query = "
    SELECT
        MAX(
            UNIX_TIMESTAMP(updated_at)
        ) AS latest_change
    FROM order_table
";

$result = $conn->query($query);

if ($result === false) {

    http_response_code(500);

    echo json_encode([
        'error' => 'Query failed: ' . $conn->error
    ]);

    $conn->close();

    exit;
}

$row = $result->fetch_assoc();

echo json_encode([
    'latest_change' => (int)($row['latest_change'] ?? 0)
]);

$conn->close();

?>