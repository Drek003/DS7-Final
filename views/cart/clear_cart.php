<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false]);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

$stmt = $db->prepare('DELETE FROM shopping_cart WHERE user_id = ?');
if ($stmt->execute([$user_id])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
} 