<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache');

$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'sakamoto';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error']);
    exit();
}

session_start();
$username = $_SESSION['account_username'] ?? null;
// Close session lock immediately to prevent page freeze
session_write_close(); 

$type = $_GET['type'] ?? '';

// ADMIN CHECK: Generates a hash representing overall order state
if ($type === 'admin_check') {
    $res = $conn->query("
        SELECT MD5(CONCAT(
            IFNULL(COUNT(*), 0),
            IFNULL(SUM(CASE WHEN order_status = 'APPROVED' THEN 1 ELSE 0 END), 0),
            IFNULL(SUM(CASE WHEN mode_payment != '' THEN 1 ELSE 0 END), 0),
            IFNULL(SUM(CASE WHEN is_cancelled = 'CANCEL' THEN 1 ELSE 0 END), 0)
        )) AS state_hash
        FROM order_table
    ");
    $data = $res->fetch_assoc();
    echo json_encode([
        'status' => 'success',
        'hash' => $data['state_hash']
    ]);
    exit();
}

// USER CHECK: Tracks status, payment mode, and cancellation status
if ($type === 'user_check' && $username) {
    $stmt = $conn->prepare("
        SELECT order_status, is_cancelled, mode_payment 
        FROM order_table 
        WHERE order_username = ? 
        LIMIT 1
    ");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $userHash = md5($row['order_status'] . '|' . $row['is_cancelled'] . '|' . $row['mode_payment']);
        echo json_encode([
            'status' => 'success',
            'hash' => $userHash
        ]);
    } else {
        echo json_encode(['status' => 'no_order']);
    }
    $stmt->close();
    exit();
}

echo json_encode(['status' => 'invalid']);