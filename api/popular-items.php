<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/functions.php';
require_admin();

$conn = db_connect();

$filter = isset($_GET['start_date']) ? $_GET['start_date'] : 'today';
if ($filter === 'today') {
    $start_date = 'CURDATE()';
} elseif ($filter === 'this_week') {
    $start_date = 'DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
} elseif ($filter === 'this_month') {
    $start_date = 'DATE_SUB(CURDATE(), INTERVAL 1 MONTH)';
} else {
    $start_date = 'CURDATE()';
}

$sql = "SELECT i.name, SUM(oi.quantity) as total_sold 
        FROM order_items oi 
        JOIN items i ON oi.item_id = i.item_id 
        JOIN orders o ON oi.order_id = o.order_id
        WHERE o.created_at >= $start_date
        GROUP BY i.item_id, i.name 
        ORDER BY total_sold DESC 
        LIMIT 5";

$result = $conn->query($sql);
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode($items);
?>