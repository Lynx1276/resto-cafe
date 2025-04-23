<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/functions.php';
require_admin();

$conn = db_connect();

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$date = $conn->real_escape_string($date);

$sql = "SELECT SUM(amount) as amount FROM payments WHERE status = 'Completed' AND DATE(payment_date) = '$date'";
$result = $conn->query($sql);
$amount = $result->fetch_assoc()['amount'] ?? 0;

echo json_encode(['amount' => $amount]);
