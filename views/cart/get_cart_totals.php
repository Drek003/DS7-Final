<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Obtener items del carrito con información del producto
    $stmt = $db->prepare("
        SELECT 
            sc.id,
            sc.product_id,
            sc.quantity,
            sc.price_at_time,
            p.name as product_name,
            p.image,
            (sc.quantity * sc.price_at_time) as subtotal
        FROM shopping_cart sc
        INNER JOIN products p ON sc.product_id = p.id
        WHERE sc.user_id = ?
        ORDER BY sc.created_at DESC
    ");
    
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular totales
    $subtotal = 0;
    $total_items = 0;
    
    foreach ($cart_items as $item) {
        $subtotal += $item['subtotal'];
        $total_items += $item['quantity'];
    }
    
    // Calcular impuestos y envío
    $tax_rate = 0.07; // 7%
    $tax_amount = $subtotal * $tax_rate;
    $shipping = $subtotal >= 100 ? 0 : 15.00; // Envío gratis para compras >= $100, sino $15
    $total = $subtotal + $tax_amount + $shipping;
    
    // Función para formatear precios
    function formatPrice($amount) {
        return '$' . number_format($amount, 2, '.', ',');
    }
    
    echo json_encode([
        'success' => true,
        'subtotal' => $subtotal,
        'subtotal_formatted' => formatPrice($subtotal),
        'tax_amount' => $tax_amount,
        'tax_formatted' => formatPrice($tax_amount),
        'shipping' => $shipping,
        'shipping_formatted' => formatPrice($shipping),
        'total' => $total,
        'total_formatted' => formatPrice($total),
        'total_items' => $total_items
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener totales del carrito: ' . $e->getMessage()
    ]);
}
?>
