<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

// Establecer configuración regional para números
setlocale(LC_MONETARY, 'en_US.UTF-8');
setlocale(LC_NUMERIC, 'en_US.UTF-8');

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    redirect('../../views/auth/login.php');
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Procesar acciones del carrito
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_quantity':
                $cart_id = (int)$_POST['cart_id'];
                $quantity = (int)$_POST['quantity'];
                
                if ($quantity > 0) {
                    // Verificar stock disponible antes de actualizar
                    $stock_check = $db->prepare("
                        SELECT p.stock, p.name 
                        FROM shopping_cart sc 
                        JOIN products p ON sc.product_id = p.id 
                        WHERE sc.id = ? AND sc.user_id = ?
                    ");
                    $stock_check->execute([$cart_id, $user_id]);
                    $product_info = $stock_check->fetch(PDO::FETCH_ASSOC);
                    
                    if ($product_info && $quantity <= $product_info['stock']) {
                        $stmt = $db->prepare("UPDATE shopping_cart SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
                        if ($stmt->execute([$quantity, $cart_id, $user_id])) {
                            $message = "Cantidad actualizada correctamente.";
                        } else {
                            $error = "Error al actualizar la cantidad.";
                        }
                    } else {
                        $error = "Stock insuficiente. Solo hay {$product_info['stock']} unidades disponibles de {$product_info['name']}.";
                    }
                } else {
                    $error = "La cantidad debe ser mayor a 0.";
                }
                break;
                
            case 'remove_item':
                $cart_id = (int)$_POST['cart_id'];
                
                $stmt = $db->prepare("DELETE FROM shopping_cart WHERE id = ? AND user_id = ?");
                if ($stmt->execute([$cart_id, $user_id])) {
                    $message = "Producto eliminado del carrito.";
                } else {
                    $error = "Error al eliminar el producto.";
                }
                break;
                
            case 'clear_cart':
                $stmt = $db->prepare("DELETE FROM shopping_cart WHERE user_id = ?");
                if ($stmt->execute([$user_id])) {
                    $message = "Carrito vaciado correctamente.";
                } else {
                    $error = "Error al vaciar el carrito.";
                }
                break;
        }
    }
}

// Obtener productos del carrito con información de stock
$cart_query = "
    SELECT 
        sc.id as cart_id,
        sc.quantity,
        sc.price_at_time,
        sc.added_at,
        p.id as product_id,
        p.name as product_name,
        p.description,
        p.price as current_price,
        p.stock,
        p.image,
        c.name as category_name,
        (sc.quantity * sc.price_at_time) as subtotal
    FROM shopping_cart sc
    JOIN products p ON sc.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE sc.user_id = ?
    ORDER BY sc.added_at DESC
";

$cart_stmt = $db->prepare($cart_query);
$cart_stmt->execute([$user_id]);
$cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular totales
$subtotal = 0;
$total_items = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['subtotal'];
    $total_items += $item['quantity'];
}

$tax_rate = 0.07; // 7% de impuesto
$tax_amount = $subtotal * $tax_rate;
$total = $subtotal + $tax_amount;

// Función para formatear precios correctamente
function formatPrice($amount) {
    // Asegurar que el valor sea numérico
    $amount = floatval($amount);
    // Formatear con 2 decimales y separadores apropiados
    return '$' . number_format($amount, 2, '.', ',');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Carrito - Catálogo de Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
    <link href="../../assets/css/bootstrap-dark.css" rel="stylesheet">
    <style>
        /* Variables CSS personalizadas para el carrito */
        :root {
            --cart-bg: var(--surface-color);
            --cart-item-bg: var(--surface-light);
            --cart-border: rgba(255, 255, 255, 0.1);
            --cart-text: var(--text-primary);
            --cart-text-secondary: var(--text-secondary);
            --cart-text-muted: var(--text-muted);
        }

        /* Contenedor principal del carrito */
        .cart-container {
            background: var(--background-color);
            min-height: 100vh;
            padding-top: 20px;
        }

        /* Elementos del carrito mejorados */
        .cart-item {
            background: var(--cart-item-bg);
            border: 1px solid var(--cart-border);
            border-radius: var(--border-radius);
            transition: var(--transition);
            margin-bottom: 1rem;
            backdrop-filter: blur(10px);
            box-shadow: var(--box-shadow-light);
        }

        .cart-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--box-shadow);
            border-color: rgba(0, 212, 170, 0.3);
        }

        /* Imagen del producto */
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid var(--cart-border);
            transition: var(--transition);
        }

        .product-image:hover {
            border-color: var(--accent-color);
        }

        /* Destacar precios */
        .price-highlight {
            color: var(--accent-color);
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Resumen del carrito mejorado */
        .cart-summary {
            background: linear-gradient(135deg, var(--surface-color) 0%, var(--surface-light) 100%);
            border: 1px solid var(--cart-border);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            backdrop-filter: blur(10px);
            color: var(--cart-text);
            position: sticky;
            top: 20px;
            height: fit-content;
        }

        /* Botones del carrito */
        .btn-cart-action {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: var(--transition);
            border: 1px solid transparent;
        }

        .btn-cart-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 212, 170, 0.3);
        }

        .btn-primary.btn-cart-action {
            background: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--primary-dark);
        }

        .btn-outline-light.btn-cart-action {
            border-color: var(--cart-border);
            color: var(--cart-text);
        }

        .btn-outline-light.btn-cart-action:hover {
            background: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--primary-dark);
        }

        /* Carrito vacío */
        .empty-cart {
            text-align: center;
            padding: 80px 20px;
            background: var(--cart-item-bg);
            border-radius: var(--border-radius);
            border: 1px solid var(--cart-border);
            color: var(--cart-text-secondary);
        }

        .empty-cart i {
            font-size: 5rem;
            margin-bottom: 30px;
            color: var(--accent-color);
            opacity: 0.7;
        }

        .empty-cart h3 {
            color: var(--cart-text);
            margin-bottom: 15px;
        }

        /* Controles de cantidad mejorados */
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }

        .quantity-btn {
            width: 32px;
            height: 32px;
            border: 1px solid var(--cart-border);
            border-radius: 6px;
            background: var(--cart-item-bg);
            color: var(--cart-text);
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .quantity-btn:hover:not(:disabled) {
            background: var(--accent-color);
            color: var(--primary-dark);
            border-color: var(--accent-color);
            transform: scale(1.1);
        }

        .quantity-btn:disabled {
            background: var(--surface-color);
            color: var(--cart-text-muted);
            cursor: not-allowed;
            opacity: 0.5;
        }

        .quantity-display {
            min-width: 45px;
            text-align: center;
            font-weight: 600;
            color: var(--cart-text);
            font-size: 1.1rem;
        }

        /* Encabezado del carrito */
        .cart-header {
            background: var(--cart-item-bg);
            border: 1px solid var(--cart-border);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }

        .cart-header h1 {
            color: var(--cart-text);
            margin-bottom: 0;
        }

        /* Información del producto mejorada */
        .product-info h6 {
            color: var(--cart-text);
            margin-bottom: 8px;
        }

        .product-info a {
            color: var(--accent-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .product-info a:hover {
            color: var(--accent-light);
            text-decoration: underline;
        }

        .product-meta {
            color: var(--cart-text-muted);
            font-size: 0.9rem;
        }

        /* Estilos para el botón eliminar en esquina inferior */
        .cart-item .btn-outline-danger {
            font-size: 0.875rem;
            padding: 6px 12px;
            border-radius: 6px;
            transition: var(--transition);
        }

        .cart-item .btn-outline-danger:hover {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        /* Espaciado mejorado para los elementos del carrito */
        .cart-item .row:first-child {
            margin-bottom: 0;
        }

        .cart-item .row:last-child {
            margin-top: 0;
            padding-top: 10px;
            border-top: 1px solid var(--cart-border);
        }

        /* Animaciones y transiciones para actualizaciones en tiempo real */
        .cart-subtotal, .cart-tax, .cart-shipping, .cart-total {
            transition: all 0.3s ease;
        }
        
        .cart-total {
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        
        /* Efecto de actualización en tiempo real */
        .updating {
            opacity: 0.7;
            transform: scale(0.98);
        }
        
        .updated {
            animation: pulseGreen 0.6s ease-out;
        }
        
        @keyframes pulseGreen {
            0% {
                background-color: transparent;
                transform: scale(1);
            }
            50% {
                background-color: rgba(0, 212, 170, 0.1);
                transform: scale(1.02);
            }
            100% {
                background-color: transparent;
                transform: scale(1);
            }
        }

        /* Responsive design mejorado */
        @media (max-width: 768px) {
            .cart-item {
                margin-bottom: 15px;
            }
            
            .product-image {
                width: 60px;
                height: 60px;
            }
            
            .quantity-controls {
                gap: 5px;
            }
            
            .quantity-btn {
                width: 28px;
                height: 28px;
                font-size: 12px;
            }
            
            .quantity-display {
                min-width: 35px;
                font-size: 1rem;
            }
            
            .cart-summary {
                margin-top: 20px;
                position: relative !important;
            }
            
            .price-highlight {
                font-size: 1rem;
            }

            .cart-item .row:last-child {
                padding-top: 15px;
                margin-top: 10px;
            }
        }

        @media (max-width: 576px) {
            .cart-header {
                padding: 15px;
            }
            
            .cart-header h1 {
                font-size: 1.5rem;
            }
            
            .product-image {
                width: 50px;
                height: 50px;
            }
            
            .cart-item .row > div {
                margin-bottom: 8px;
            }
            
            .cart-item .btn-outline-danger {
                font-size: 0.8rem;
                padding: 5px 10px;
            }

            .cart-item .btn-outline-danger i {
                margin-right: 4px;
            }

            .cart-item .col-6 {
                text-align: center !important;
            }

            .cart-summary {
                margin-top: 30px;
            }
        }

        /* Animaciones suaves */
        .cart-item, .btn-cart-action, .quantity-btn {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Mejoras en la accesibilidad */
        .quantity-btn:focus {
            outline: 2px solid var(--accent-color);
            outline-offset: 2px;
        }

        .btn-cart-action:focus {
            outline: 2px solid var(--accent-color);
            outline-offset: 2px;
        }

        /* Estilos para alertas de stock */
        .stock-warning {
            background-color: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 0.75rem;
        }

        .stock-info {
            background-color: rgba(108, 117, 125, 0.1);
            border: 1px solid rgba(108, 117, 125, 0.3);
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 0.75rem;
        }

        /* Animación para stock bajo */
        .stock-warning {
            animation: pulse-warning 2s infinite;
        }

        @keyframes pulse-warning {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }
    </style>
</head>
<body class="cart-container">
    <?php include '../../includes/nav.php'; ?>

        <div class="container mt-4">
        <?php if (isset($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <h1 class="mb-4">
                    <i class="bi bi-cart3 me-2"></i>Mi Carrito de Compras
                </h1>

                <!-- Mensajes de estado -->
                <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Lista de productos del carrito -->
                    <div class="col-lg-8 col-12">
                        <?php if (empty($cart_items)): ?>
                        <div class="empty-cart">
                            <i class="fas fa-shopping-cart mb-4"></i>
                            <h3 class="mb-3">Tu carrito está vacío</h3>
                            <p class="mb-4">¡Explora nuestro catálogo y encuentra productos increíbles!</p>
                            <a href="../products/index.php" class="btn btn-primary btn-cart-action btn-lg">
                                <i class="fas fa-store me-2"></i> Ir al Catálogo
                            </a>
                        </div>
                        <?php else: ?>
                        
                        <!-- Encabezado de la lista de productos -->
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 p-3" 
                             style="background: var(--cart-item-bg); border: 1px solid var(--cart-border); border-radius: var(--border-radius);">
                            <div>
                                <h4 class="mb-1">Productos en tu carrito</h4>
                                <small class="text-muted"><?php echo $total_items; ?> <?php echo $total_items == 1 ? 'producto' : 'productos'; ?></small>
                            </div>
                            <form method="POST" class="mt-2 mt-sm-0" onsubmit="return confirm('¿Estás seguro de que quieres vaciar el carrito?')">
                                <input type="hidden" name="action" value="clear_cart">
                                <button type="submit" class="btn btn-outline-danger btn-sm btn-cart-action">
                                    <i class="fas fa-trash me-1"></i> Vaciar Carrito
                                </button>
                            </form>
                        </div>

                        <!-- Productos individuales -->
                        <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item p-3 p-md-4">
                            <div class="row align-items-center g-3">
                                <!-- Imagen del producto -->
                                <div class="col-auto">
                                    <?php if ($item['image']): ?>
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                         class="product-image img-fluid"
                                         onerror="this.onerror=null; this.src='../../assets/images/placeholder.png';">
                                    <?php else: ?>
                                    <div class="product-image bg-secondary d-flex align-items-center justify-content-center">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Información del producto -->
                                <div class="col-12 col-sm-6 col-lg-4">
                                    <div class="product-info">
                                        <h6 class="mb-2">
                                            <a href="../products/show.php?id=<?php echo $item['product_id']; ?>">
                                                <?php echo htmlspecialchars($item['product_name']); ?>
                                            </a>
                                        </h6>
                                        <div class="product-meta">
                                            <div class="mb-1">
                                                <i class="fas fa-tag me-1"></i> 
                                                <?php echo htmlspecialchars($item['category_name']); ?>
                                            </div>
                                            <div>
                                                <i class="fas fa-clock me-1"></i> 
                                                Agregado: <?php echo date('d/m/Y H:i', strtotime($item['added_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Precio unitario -->
                                <div class="col-6 col-sm-3 col-lg-2 text-center">
                                    <div class="mb-1">
                                        <small class="text-muted d-block">Precio</small>
                                        <div class="price-highlight"><?php echo formatPrice($item['price_at_time']); ?></div>
                                    </div>
                                    <?php if ($item['current_price'] != $item['price_at_time']): ?>
                                    <small class="text-warning">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Actual: <?php echo formatPrice($item['current_price']); ?>
                                    </small>
                                    <?php endif; ?>
                                </div>

                                <!-- Controles de cantidad -->
                                <div class="col-6 col-sm-3 col-lg-2">
                                    <div class="text-center mb-2">
                                        <small class="text-muted d-block">Cantidad</small>
                                        <div class="quantity-controls">
                                            <button type="button" class="quantity-btn" 
                                                    onclick="changeQuantity(<?php echo $item['cart_id']; ?>, -1, <?php echo $item['quantity']; ?>)" 
                                                    <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>
                                                    aria-label="Disminuir cantidad">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <span class="quantity-display"><?php echo $item['quantity']; ?></span>
                                            <button type="button" class="quantity-btn" 
                                                    onclick="changeQuantity(<?php echo $item['cart_id']; ?>, 1, <?php echo $item['quantity']; ?>)" 
                                                    <?php echo $item['quantity'] >= $item['stock'] ? 'disabled' : ''; ?>
                                                    <?php if ($item['quantity'] >= $item['stock']): ?>
                                                    title="Stock máximo: <?php echo $item['stock']; ?> unidades"
                                                    data-bs-toggle="tooltip"
                                                    <?php endif; ?>
                                                    aria-label="Aumentar cantidad">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <?php if ($item['stock'] <= 5): ?>
                                        <small class="text-warning mt-1 d-block">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            Solo quedan <?php echo $item['stock']; ?> en stock
                                        </small>
                                        <?php else: ?>
                                        <small class="text-muted mt-1 d-block">
                                            Stock: <?php echo $item['stock']; ?> disponibles
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Subtotal -->
                                <div class="col-12 col-lg-2">
                                    <div class="text-center mb-2">
                                        <small class="text-muted d-block">Subtotal</small>
                                        <div class="price-highlight h5 mb-0"><?php echo formatPrice($item['subtotal']); ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Botón eliminar en esquina inferior derecha -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex justify-content-end mt-2">
                                        <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este producto del carrito?')">
                                            <input type="hidden" name="action" value="remove_item">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm btn-cart-action" 
                                                    aria-label="Eliminar producto">
                                                <i class="fas fa-trash me-1"></i> Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Resumen del carrito al lado -->
                    <?php if (!empty($cart_items)): ?>
                    <div class="col-lg-4 col-12">
                        <div class="cart-summary p-4">
                            <div class="text-center mb-4">
                                <h4 class="mb-2">
                                    <i class="fas fa-receipt me-2"></i> 
                                    Resumen del Pedido
                                </h4>
                                <small class="text-muted">Revisa los detalles de tu compra</small>
                            </div>
                            
                            <div class="summary-details">
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
                                    <span class="text-muted">
                                        <i class="fas fa-shopping-bag me-1"></i>
                                        <span class="cart-items-text">Subtotal (<?php echo $total_items; ?> <?php echo $total_items == 1 ? 'producto' : 'productos'; ?>)</span>
                                    </span>
                                    <span class="fw-semibold fs-5 cart-subtotal"><?php echo formatPrice($subtotal); ?></span>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
                                    <span class="text-muted">
                                        <i class="fas fa-percentage me-1"></i>
                                        Impuestos (7%)
                                    </span>
                                    <span class="fw-semibold cart-tax"><?php echo formatPrice($tax_amount); ?></span>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary cart-shipping-row">
                                    <?php if ($subtotal >= 100): ?>
                                    <span class="text-success">
                                        <i class="fas fa-truck me-1"></i>
                                        Envío gratuito
                                    </span>
                                    <span class="text-success fw-semibold cart-shipping"><?php echo formatPrice(0); ?></span>
                                    <?php else: ?>
                                    <span class="text-muted">
                                        <i class="fas fa-truck me-1"></i>
                                        Envío
                                    </span>
                                    <span class="fw-semibold cart-shipping"><?php echo formatPrice(15.00); ?></span>
                                    <?php 
                                    $shipping = 15.00;
                                    $total += $shipping;
                                    ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center py-3 mt-2">
                                    <span class="h5 mb-0 fw-bold">
                                        <i class="fas fa-calculator me-1"></i>
                                        Total Final
                                    </span>
                                    <span class="h4 fw-bold text-success mb-0 cart-total"><?php echo formatPrice($total); ?></span>
                                </div>
                            </div>

                            <div class="d-grid gap-3 mt-4">
                                <a href="checkout.php" class="btn btn-primary btn-lg btn-cart-action">
                                    <i class="fas fa-credit-card me-2"></i> 
                                    Proceder al Pago
                                </a>
                                <a href="../products/index.php" class="btn btn-outline-light btn-cart-action">
                                    <i class="fas fa-plus me-2"></i> 
                                    Agregar Más Productos
                                </a>
                            </div>

                            <div class="mt-4 pt-3 border-top border-secondary">
                                <div class="row g-2 text-center">
                                    <div class="col-12">
                                        <small class="text-muted d-block mb-2">
                                            <i class="fas fa-shield-alt me-1 text-success"></i> 
                                            Compra 100% segura y protegida
                                        </small>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">
                                            <i class="fas fa-truck me-1 text-info"></i> 
                                            Envío gratuito en compras +$100
                                        </small>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">
                                            <i class="fas fa-undo me-1 text-warning"></i> 
                                            30 días de garantía
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/app.js"></script>
    <script>
        // Función mejorada para cambiar cantidad con validación de stock
        function changeQuantity(cartId, change, currentQuantity) {
            const newQuantity = currentQuantity + change;
            
            // Validaciones básicas
            if (newQuantity < 1) {
                showNotification('La cantidad mínima es 1', 'warning');
                return;
            }
            
            // Deshabilitar botones temporalmente para evitar clics múltiples
            const buttons = document.querySelectorAll(`[onclick*="${cartId}"]`);
            buttons.forEach(btn => btn.disabled = true);
            
            // Mostrar indicador de carga
            const quantityDisplay = event.target.closest('.quantity-controls').querySelector('.quantity-display');
            const originalText = quantityDisplay.textContent;
            quantityDisplay.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            // Mostrar indicador de actualización en los totales
            const summaryElements = ['.cart-subtotal', '.cart-tax', '.cart-shipping', '.cart-total'];
            summaryElements.forEach(selector => {
                const element = document.querySelector(selector);
                if (element) {
                    element.classList.add('updating');
                }
            });
            
            // Enviar actualización al servidor usando el nuevo endpoint
            const formData = new FormData();
            formData.append('cart_id', cartId);
            formData.append('quantity', newQuantity);
            
            fetch('update_quantity.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar la cantidad en la pantalla
                    quantityDisplay.textContent = data.new_quantity;
                    
                    // Actualizar totales con los datos del servidor
                    updateCartTotalsFromData(data);
                    
                    // Mostrar notificación de éxito con información de stock
                    let message = 'Cantidad actualizada correctamente';
                    if (data.stock_remaining !== undefined) {
                        message += ` (${data.stock_remaining} unidades restantes)`;
                    }
                    showNotification(message, 'success');
                    
                    // Actualizar contador del carrito
                    if (window.updateCartCount) {
                        window.updateCartCount(true);
                    }
                    
                    // Actualizar botones según stock disponible
                    updateQuantityButtons(cartId, data.new_quantity, data.stock_remaining + data.new_quantity);
                    
                } else {
                    // Error del servidor - restaurar cantidad original
                    quantityDisplay.textContent = originalText;
                    
                    // Mostrar mensaje de error específico
                    showNotification(data.message || 'Error al actualizar la cantidad', 'danger');
                    
                    // Si hay información de stock máximo, actualizar botones
                    if (data.max_stock !== undefined) {
                        updateQuantityButtons(cartId, currentQuantity, data.max_stock);
                    }
                }
                
                // Remover indicadores de actualización
                summaryElements.forEach(selector => {
                    const element = document.querySelector(selector);
                    if (element) {
                        element.classList.remove('updating');
                        if (data.success) {
                            element.classList.add('updated');
                            setTimeout(() => {
                                element.classList.remove('updated');
                            }, 600);
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error:', error);
                quantityDisplay.textContent = originalText;
                showNotification('Error de conexión al actualizar la cantidad', 'danger');
                
                // Remover indicadores de actualización en caso de error
                summaryElements.forEach(selector => {
                    const element = document.querySelector(selector);
                    if (element) {
                        element.classList.remove('updating');
                    }
                });
            })
            .finally(() => {
                // Rehabilitar botones después de un breve delay
                setTimeout(() => {
                    buttons.forEach(btn => btn.disabled = false);
                }, 500);
            });
        }

        // Función para actualizar botones de cantidad según stock disponible
        function updateQuantityButtons(cartId, currentQuantity, maxStock) {
            const buttons = document.querySelectorAll(`[onclick*="${cartId}"]`);
            buttons.forEach(btn => {
                const onclick = btn.getAttribute('onclick');
                
                // Botón de disminuir
                if (onclick.includes(', -1,')) {
                    btn.disabled = currentQuantity <= 1;
                }
                
                // Botón de aumentar
                if (onclick.includes(', 1,')) {
                    btn.disabled = currentQuantity >= maxStock;
                    
                    // Agregar tooltip si está en el límite
                    if (currentQuantity >= maxStock) {
                        btn.setAttribute('title', `Stock máximo: ${maxStock} unidades`);
                        btn.setAttribute('data-bs-toggle', 'tooltip');
                    } else {
                        btn.removeAttribute('title');
                        btn.removeAttribute('data-bs-toggle');
                    }
                }
            });
        }

        // Función para actualizar totales con datos del servidor
        function updateCartTotalsFromData(data) {
            const subtotalElement = document.querySelector('.cart-subtotal');
            const taxElement = document.querySelector('.cart-tax');
            const shippingElement = document.querySelector('.cart-shipping');
            const totalElement = document.querySelector('.cart-total');
            const itemsTextElement = document.querySelector('.cart-items-text');
            const shippingRowElement = document.querySelector('.cart-shipping-row');
            
            if (subtotalElement) {
                subtotalElement.textContent = data.subtotal_formatted;
                animateElement(subtotalElement);
            }
            
            if (taxElement) {
                taxElement.textContent = data.tax_formatted;
                animateElement(taxElement);
            }
            
            if (shippingElement) {
                shippingElement.textContent = data.shipping_formatted;
                
                // Actualizar color y texto del envío según si es gratuito o no
                const shippingSpan = shippingRowElement.querySelector('span:first-child');
                if (data.shipping === 0) {
                    shippingSpan.className = 'text-success';
                    shippingSpan.innerHTML = '<i class="fas fa-truck me-1"></i>Envío gratuito';
                    shippingElement.className = 'text-success fw-semibold cart-shipping';
                } else {
                    shippingSpan.className = 'text-muted';
                    shippingSpan.innerHTML = '<i class="fas fa-truck me-1"></i>Envío';
                    shippingElement.className = 'fw-semibold cart-shipping';
                }
            }
            
            if (totalElement) {
                totalElement.textContent = data.total_formatted;
                animateElement(totalElement, 1.1, 300);
            }
            
            if (itemsTextElement) {
                const productText = data.total_items === 1 ? 'producto' : 'productos';
                itemsTextElement.textContent = `Subtotal (${data.total_items} ${productText})`;
            }
        }

        // Función auxiliar para animar elementos
        function animateElement(element, scale = 1.05, duration = 200) {
            element.style.transform = `scale(${scale})`;
            setTimeout(() => {
                element.style.transform = 'scale(1)';
            }, duration);
        }

        // Función para actualizar totales dinámicamente
        function updateCartTotals() {
            fetch('/DS7-Final/views/cart/get_cart_totals.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Actualizar elementos del DOM con los nuevos totales
                        const subtotalElement = document.querySelector('.cart-subtotal');
                        const taxElement = document.querySelector('.cart-tax');
                        const shippingElement = document.querySelector('.cart-shipping');
                        const totalElement = document.querySelector('.cart-total');
                        const itemsTextElement = document.querySelector('.cart-items-text');
                        const shippingRowElement = document.querySelector('.cart-shipping-row');
                        
                        if (subtotalElement) {
                            subtotalElement.textContent = data.subtotal_formatted;
                            // Añadir animación sutil al actualizar
                            subtotalElement.style.transform = 'scale(1.05)';
                            setTimeout(() => {
                                subtotalElement.style.transform = 'scale(1)';
                            }, 200);
                        }
                        
                        if (taxElement) {
                            taxElement.textContent = data.tax_formatted;
                            taxElement.style.transform = 'scale(1.05)';
                            setTimeout(() => {
                                taxElement.style.transform = 'scale(1)';
                            }, 200);
                        }
                        
                        if (shippingElement) {
                            shippingElement.textContent = data.shipping_formatted;
                            
                            // Actualizar color y texto del envío según si es gratuito o no
                            const shippingSpan = shippingRowElement.querySelector('span:first-child');
                            if (data.shipping === 0) {
                                shippingSpan.className = 'text-success';
                                shippingSpan.innerHTML = '<i class="fas fa-truck me-1"></i>Envío gratuito';
                                shippingElement.className = 'text-success fw-semibold cart-shipping';
                            } else {
                                shippingSpan.className = 'text-muted';
                                shippingSpan.innerHTML = '<i class="fas fa-truck me-1"></i>Envío';
                                shippingElement.className = 'fw-semibold cart-shipping';
                            }
                        }
                        
                        if (totalElement) {
                            totalElement.textContent = data.total_formatted;
                            totalElement.style.transform = 'scale(1.1)';
                            setTimeout(() => {
                                totalElement.style.transform = 'scale(1)';
                            }, 300);
                        }
                        
                        if (itemsTextElement) {
                            const productText = data.total_items === 1 ? 'producto' : 'productos';
                            itemsTextElement.textContent = `Subtotal (${data.total_items} ${productText})`;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error updating totals:', error);
                });
        }

        // Función para mostrar notificaciones mejorada
        function showNotification(message, type = 'success') {
            // Remover notificaciones existentes
            const existingAlerts = document.querySelectorAll('.position-fixed.alert');
            existingAlerts.forEach(alert => alert.remove());
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px;';
            
            let icon = 'check-circle';
            if (type === 'danger' || type === 'error') icon = 'exclamation-circle';
            if (type === 'warning') icon = 'exclamation-triangle';
            if (type === 'info') icon = 'info-circle';
            
            alertDiv.innerHTML = `
                <i class="fas fa-${icon} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Auto-remover después de 4 segundos
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 4000);
        }

        // Mejorar la experiencia de hover en los elementos del carrito
        document.addEventListener('DOMContentLoaded', function() {
            // Actualizar contador del carrito al cargar la página
            if (window.refreshCartCount) {
                // Sin animación al cargar la página
                window.updateCartCount && window.updateCartCount(false);
            }
            
            // Animaciones suaves al cargar
            const cartItems = document.querySelectorAll('.cart-item');
            cartItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    item.style.transition = 'all 0.5s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Tooltips para botones
            const tooltipElements = document.querySelectorAll('[aria-label]');
            tooltipElements.forEach(element => {
                element.setAttribute('data-bs-toggle', 'tooltip');
                element.setAttribute('title', element.getAttribute('aria-label'));
            });

            // Inicializar tooltips de Bootstrap
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Mejorar accesibilidad con teclado
            const quantityBtns = document.querySelectorAll('.quantity-btn');
            quantityBtns.forEach(btn => {
                btn.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.click();
                    }
                });
            });
        });

        // Función para manejar el proceso de eliminación con confirmación mejorada
        function confirmRemoval(productName) {
            return confirm(`¿Estás seguro de que quieres eliminar "${productName}" del carrito?`);
        }

        // Prevenir envío accidental de formularios
        document.addEventListener('submit', function(e) {
            if (e.target.closest('form[onsubmit*="confirm"]')) {
                const form = e.target.closest('form');
                const action = form.querySelector('input[name="action"]').value;
                
                if (action === 'clear_cart') {
                    e.preventDefault();
                    if (confirm('¿Estás seguro de que quieres vaciar completamente el carrito? Esta acción no se puede deshacer.')) {
                        form.submit();
                    }
                }
            }
            
            // Interceptar formularios de eliminación de productos
            if (e.target.querySelector('input[name="action"][value="remove_item"]')) {
                // Dar tiempo para que se procese y luego actualizar el contador
                setTimeout(() => {
                    if (window.refreshCartCount) {
                        window.refreshCartCount();
                    }
                }, 100);
            }
        });
    </script>
</body>
</html>
