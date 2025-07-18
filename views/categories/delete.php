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

$category_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();

// Verificar si la categoría existe
$query = "SELECT * FROM categories WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    // Si la categoría no existe, redirigir
    $_SESSION['error'] = 'La categoría no existe';
    redirect('index.php');
}

// Verificar si hay productos asociados a esta categoría
$query = "SELECT COUNT(*) FROM products WHERE category_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$category_id]);
$product_count = $stmt->fetchColumn();

// Iniciar transacción
$db->beginTransaction();

try {
    // Si hay productos, actualizar su categoría a NULL
    if ($product_count > 0) {
        $update_query = "UPDATE products SET category_id = NULL WHERE category_id = ?";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute([$category_id]);
    }
    
    // Eliminar la categoría
    $delete_query = "DELETE FROM categories WHERE id = ?";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->execute([$category_id]);
    
    // Eliminar la imagen si existe
    if ($category['image'] && file_exists("../../assets/images/categories/" . $category['image'])) {
        unlink("../../assets/images/categories/" . $category['image']);
    }
    
    // Confirmar transacción
    $db->commit();
    
    // Mensaje de éxito
    $_SESSION['success'] = 'Categoría eliminada correctamente';
    
    if ($product_count > 0) {
        $_SESSION['success'] .= ". Se han actualizado $product_count productos que estaban asociados a esta categoría.";
    }
    
} catch (Exception $e) {
    // Revertir cambios en caso de error
    $db->rollBack();
    $_SESSION['error'] = 'Error al eliminar la categoría: ' . $e->getMessage();
}

// Redirigir a la lista de categorías
redirect('index.php');
?>