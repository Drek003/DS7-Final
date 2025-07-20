<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

try {
    $cart_id = (int)$_POST['cart_id'];
    $new_quantity = (int)$_POST['quantity'];
    
    // Validación básica de cantidad
    if ($new_quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'La cantidad mínima es 1']);
        exit;
    }
    
    // Obtener información del producto en el carrito y su stock actual
    $check_query = "
        SELECT 
            sc.product_id,
            sc.quantity as current_cart_quantity,
            p.stock,
            p.name as product_name
        FROM shopping_cart sc
        JOIN products p ON sc.product_id = p.id
        WHERE sc.id = ? AND sc.user_id = ?
    ";
    
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$cart_id, $user_id]);
    $cart_info = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cart_info) {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado en el carrito']);
        exit;
    }
    
    // Validación de stock - solo verificar que no exceda el stock total
    if ($new_quantity > $cart_info['stock']) {
        echo json_encode([
            'success' => false, 
            'message' => "Stock insuficiente. Solo hay {$cart_info['stock']} unidades disponibles de {$cart_info['product_name']}",
            'max_stock' => $cart_info['stock']
        ]);
        exit;
    }
    
    // Actualizar la cantidad en el carrito
    $update_stmt = $db->prepare("UPDATE shopping_cart SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
    
    if ($update_stmt->execute([$new_quantity, $cart_id, $user_id])) {
        // Obtener totales actualizados
        $totals_query = "
            SELECT 
                SUM(sc.quantity * sc.price_at_time) as subtotal,
                SUM(sc.quantity) as total_items
            FROM shopping_cart sc
            WHERE sc.user_id = ?
        ";
        
        $totals_stmt = $db->prepare($totals_query);
        $totals_stmt->execute([$user_id]);
        $totals = $totals_stmt->fetch(PDO::FETCH_ASSOC);
        
        $subtotal = floatval($totals['subtotal']);
        $total_items = intval($totals['total_items']);
        $tax_rate = 0.07;
        $tax_amount = $subtotal * $tax_rate;
        $shipping = $subtotal >= 100 ? 0 : 15.00;
        $total = $subtotal + $tax_amount + $shipping;
        
        // Función para formatear precios
        function formatPrice($amount) {
            return '$' . number_format(floatval($amount), 2, '.', ',');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Cantidad actualizada correctamente',
            'new_quantity' => $new_quantity,
            'subtotal' => $subtotal,
            'subtotal_formatted' => formatPrice($subtotal),
            'tax_amount' => $tax_amount,
            'tax_formatted' => formatPrice($tax_amount),
            'shipping' => $shipping,
            'shipping_formatted' => formatPrice($shipping),
            'total' => $total,
            'total_formatted' => formatPrice($total),
            'total_items' => $total_items,
            'current_stock' => $cart_info['stock'],
            'stock_remaining' => $cart_info['stock'] - $new_quantity
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la cantidad']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>
