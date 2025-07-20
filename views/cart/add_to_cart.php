<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

// Establecer header JSON
header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para agregar productos al carrito.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $user_id = $_SESSION['user_id'];
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    // Validar que la cantidad sea válida (mínimo 1, sin límite máximo)
    if ($quantity < 1) {
        echo json_encode(['success' => false, 'message' => 'La cantidad debe ser al menos 1.']);
        exit;
    }
    
    // Verificar que el producto existe y obtener el stock
    $product_stmt = $db->prepare("SELECT id, name, price, stock FROM products WHERE id = ?");
    $product_stmt->execute([$product_id]);
    $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado.']);
        exit;
    }
    
    // Verificar que hay stock disponible
    if ($product['stock'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'Producto sin stock disponible.']);
        exit;
    }
    
    try {
        // Verificar si el producto ya está en el carrito
        $check_stmt = $db->prepare("SELECT id, quantity FROM shopping_cart WHERE user_id = ? AND product_id = ?");
        $check_stmt->execute([$user_id, $product_id]);
        $existing_item = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_item) {
            // Calcular nueva cantidad total
            $new_quantity = $existing_item['quantity'] + $quantity;
            
            // Verificar que no exceda el stock disponible
            if ($new_quantity > $product['stock']) {
                $available = $product['stock'] - $existing_item['quantity'];
                if ($available <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Ya tienes el máximo disponible de este producto en tu carrito.']);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'message' => "Solo puedes agregar {$available} unidades más. Stock disponible: {$product['stock']}"]);
                    exit;
                }
            }
            
            // Actualizar cantidad existente
            $update_stmt = $db->prepare("UPDATE shopping_cart SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $update_stmt->execute([$new_quantity, $existing_item['id']]);
            
            $message = 'Cantidad actualizada en el carrito.';
            $action = 'updated';
        } else {
            // Verificar que la cantidad no exceda el stock para producto nuevo
            if ($quantity > $product['stock']) {
                echo json_encode(['success' => false, 'message' => "Cantidad solicitada ({$quantity}) excede el stock disponible ({$product['stock']})."]);
                exit;
            }
            
            // Agregar nuevo producto al carrito
            $insert_stmt = $db->prepare("INSERT INTO shopping_cart (user_id, product_id, quantity, price_at_time) VALUES (?, ?, ?, ?)");
            $insert_stmt->execute([$user_id, $product_id, $quantity, $product['price']]);
            
            $message = 'Producto agregado al carrito.';
            $action = 'added';
        }
        
        // Obtener el número de productos únicos en el carrito (contador simple)
        $count_stmt = $db->prepare("SELECT COUNT(*) as total FROM shopping_cart WHERE user_id = ?");
        $count_stmt->execute([$user_id]);
        $cart_count = $count_stmt->fetchColumn() ?: 0;
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'action' => $action,
            'cart_count' => (int)$cart_count
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al agregar el producto al carrito.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}
?>
