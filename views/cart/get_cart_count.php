<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    echo json_encode(['cart_count' => 0]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];

// Obtener el número de productos únicos en el carrito (contador simple)
$count_stmt = $db->prepare("SELECT COUNT(*) as total FROM shopping_cart WHERE user_id = ?");
$count_stmt->execute([$user_id]);
$cart_count = $count_stmt->fetchColumn() ?: 0;

echo json_encode(['cart_count' => (int)$cart_count]);
?>
