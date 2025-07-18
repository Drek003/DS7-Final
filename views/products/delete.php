<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar si el usuario está logueado y es administrador
if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('index.php');
}

$product_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();

// Verificar si el producto existe
$query = "SELECT * FROM products WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    // Si el producto no existe, redirigir
    $_SESSION['error'] = 'El producto no existe';
    redirect('index.php');
}

try {
    // Eliminar el producto
    $delete_query = "DELETE FROM products WHERE id = ?";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->execute([$product_id]);
    
    // Mensaje de éxito
    $_SESSION['success'] = 'Producto "' . $product['name'] . '" eliminado correctamente';
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Error al eliminar el producto: ' . $e->getMessage();
}

// Redirigir a la lista de productos
redirect('index.php');
?>