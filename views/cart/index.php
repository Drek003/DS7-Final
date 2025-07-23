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

// Obtener productos del carrito
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
    <link href="../../assets/css/catg.css" rel="stylesheet">
    <link href="../../assets/css/bootstrap-dark.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
    <link href="../../assets/css/cart.css" rel="stylesheet">
</head>
<body class="cart-container">
    <?php include '../../includes/nav.php'; ?>

    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-11">
                <!-- Encabezado del carrito -->
                <div class="cart-header">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                        <div class="mb-3 mb-md-0">
                            <h1 class="mb-2">
                                <i class="fas fa-shopping-cart me-2"></i> 
                                Mi Carrito de Compras
                            </h1>
                            <p class="text-muted mb-0">Revisa y gestiona los productos en tu carrito</p>
                        </div>
                        <div>
                            <a href="../products/index.php" class="btn btn-outline-primary btn-cart-action">
                                <i class="fas fa-arrow-left me-2"></i> Seguir Comprando
                            </a>
                        </div>
                    </div>
                </div>

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
                    <div class="col-lg-7 col-12 order-1 order-lg-1">
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
                            <form method="POST" class="mt-2 mt-sm-0">
                                <input type="hidden" name="action" value="clear_cart">
                                <button type="submit" class="btn btn-outline-danger btn-sm btn-cart-action">
                                    <i class="fas fa-trash me-1"></i> Vaciar Carrito
                                </button>
                            </form>
                        </div>

                        <!-- Contenedor con scroll para productos -->
                        <div class="cart-products-container" id="cartProductsContainer" style="max-height: 60vh; overflow-y: auto;">
                            <!-- Productos individuales -->
                            <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item p-4" style="min-height: 140px;">
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
                                                    onclick="changeQuantity(<?php echo $item['cart_id']; ?>, -1)" 
                                                    <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>
                                                    aria-label="Disminuir cantidad">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <span class="quantity-display" id="quantity-<?php echo $item['cart_id']; ?>"><?php echo $item['quantity']; ?></span>
                                            <button type="button" class="quantity-btn" 
                                                    onclick="changeQuantity(<?php echo $item['cart_id']; ?>, 1)" 
                                                    <?php echo $item['quantity'] >= $item['stock'] ? 'disabled' : ''; ?>
                                                    aria-label="Aumentar cantidad"
                                                    title="<?php echo $item['quantity'] >= $item['stock'] ? 'Stock máximo alcanzado' : 'Aumentar cantidad'; ?>">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            Stock disponible: <?php echo ($item['stock'] - $item['quantity']); ?> 
                                            (Total: <?php echo $item['stock']; ?>)
                                        </small>
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
                        </div> <!-- Cerrar cart-products-container -->
                        <?php endif; ?>
                    </div>

                    <!-- Resumen del carrito al lado -->
                    <?php if (!empty($cart_items)): ?>
                    <div class="col-lg-4 col-12 order-2 order-lg-3 d-flex align-items-start">
                        <div class="cart-summary p-4 w-100" style="margin-top: 0;">
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
    <script src="../../assets/js/cart.js"></script>
    <script>
        // Función simple para cambiar cantidad
        function changeQuantity(cartId, change) {
            // Buscar el display de cantidad por cartId
            const quantityDisplay = document.querySelector(`#quantity-${cartId}`);
            if (!quantityDisplay) {
                console.error('No se encontró el display de cantidad para cart ID:', cartId);
                return;
            }
            
            // Obtener la cantidad actual del DOM
            const currentQuantity = parseInt(quantityDisplay.textContent) || 1;
            const newQuantity = currentQuantity + change;
            
            // Validación básica - solo mínimo 1
            if (newQuantity <= 0) {
                showNotification('La cantidad mínima es 1', 'warning');
                return;
            }
            
            // Deshabilitar botones temporalmente
            const buttons = document.querySelectorAll(`[onclick*="${cartId}"]`);
            buttons.forEach(btn => btn.disabled = true);
            
            const originalText = quantityDisplay.textContent;
            quantityDisplay.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            // Enviar al servidor
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
                    // Actualizar cantidad
                    quantityDisplay.textContent = newQuantity;
                    
                    // Actualizar totales
                    updateCartDisplayFromData(data);
                    
                    // Actualizar botones con validación simple
                    updateButtonsSimple(cartId, newQuantity, data.current_stock);
                    
                    showNotification(data.message, 'success');
                } else {
                    // Restaurar cantidad original si hay error
                    quantityDisplay.textContent = originalText;
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                quantityDisplay.textContent = originalText;
                showNotification('Error al actualizar la cantidad', 'error');
            })
            .finally(() => {
                // Rehabilitar botones
                buttons.forEach(btn => btn.disabled = false);
            });
        }
        
        // Función simple para actualizar botones
        function updateButtonsSimple(cartId, quantity, totalStock) {
            const decreaseBtn = document.querySelector(`[onclick*="${cartId}, -1"]`);
            const increaseBtn = document.querySelector(`[onclick*="${cartId}, 1"]`);
            const stockInfo = increaseBtn ? increaseBtn.closest('.col-6').querySelector('small:last-child') : null;
            
            // Botón disminuir: disabled si quantity <= 1
            if (decreaseBtn) {
                decreaseBtn.disabled = quantity <= 1;
            }
            
            // Botón aumentar: disabled si quantity >= totalStock
            if (increaseBtn) {
                increaseBtn.disabled = quantity >= totalStock;
                increaseBtn.title = quantity >= totalStock ? 'Stock máximo alcanzado' : 'Aumentar cantidad';
            }
            
            // Actualizar información de stock
            if (stockInfo) {
                const remaining = totalStock - quantity;
                if (remaining <= 0) {
                    stockInfo.className = 'text-warning d-block mt-1';
                    stockInfo.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Stock máximo alcanzado';
                } else {
                    stockInfo.className = 'text-muted d-block mt-1';
                    stockInfo.textContent = `Stock disponible: ${remaining} (Total: ${totalStock})`;
                }
            }
        }
        
        // Función para actualizar la interfaz con los datos del servidor
        function updateCartDisplayFromData(data) {
            // Actualizar subtotal
            const subtotalElement = document.querySelector('.cart-subtotal');
            if (subtotalElement && data.subtotal_formatted) {
                subtotalElement.textContent = data.subtotal_formatted;
            }
            
            // Actualizar impuesto
            const taxElement = document.querySelector('.cart-tax');
            if (taxElement && data.tax_formatted) {
                taxElement.textContent = data.tax_formatted;
            }
            
            // Actualizar envío
            const shippingElement = document.querySelector('.cart-shipping');
            if (shippingElement && data.shipping_formatted) {
                shippingElement.textContent = data.shipping_formatted;
            }
            
            // Actualizar total
            const totalElement = document.querySelector('.cart-total');
            if (totalElement && data.total_formatted) {
                totalElement.textContent = data.total_formatted;
            }
            
            // Actualizar contador de items
            const itemsText = document.querySelector('.cart-items-text');
            if (itemsText && data.total_items) {
                const items = data.total_items;
                itemsText.textContent = `Subtotal (${items} ${items == 1 ? 'producto' : 'productos'})`;
            }
            
            // Actualizar contador del carrito global si existe
            if (window.updateCartCount && data.total_items) {
                window.updateCartCount(false, data.total_items);
            }
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

            // Manejar el vaciado del carrito
            const clearCartForm = document.querySelector('.cart-header form');
            if (clearCartForm) {
                clearCartForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const submitBtn = clearCartForm.querySelector('button[type="submit"]');
                    let originalHtml = '';
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        originalHtml = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Vaciando...';
                    }
                    // Actualizar la interfaz directamente
                    const cartList = document.querySelector('.col-lg-8');
                    const cartSummary = document.querySelector('.col-lg-4');
                    if (cartList) {
                        cartList.innerHTML = `
                        <div class="empty-cart">
                            <i class="fas fa-shopping-cart mb-4"></i>
                            <h3 class="mb-3">Tu carrito está vacío</h3>
                            <p class="mb-4">¡Explora nuestro catálogo y encuentra productos increíbles!</p>
                            <a href="../products/index.php" class="btn btn-primary btn-cart-action btn-lg">
                                <i class="fas fa-store me-2"></i> Ir al Catálogo
                            </a>
                        </div>`;
                    }
                    if (cartSummary) {
                        cartSummary.innerHTML = '';
                    }
                    if (window.updateCartCount) window.updateCartCount(false, 0);
                    // Hacer la petición real al endpoint AJAX
                    const formData = new FormData(clearCartForm);
                    fetch('clear_cart.php', {
                        method: 'POST',
                        body: formData
                    })
                    .finally(() => {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalHtml || '<i class=\'fas fa-trash me-1\'></i> Vaciar Carrito';
                        }
                    });
                });
            }
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

        // Prevenir selección de texto con drag
        document.addEventListener('selectstart', function(e) {
            if (!e.target.closest('input, textarea, [contenteditable="true"], .selectable-text')) {
                e.preventDefault();
            }
        });

        // Prevenir menú contextual en algunos elementos
        document.addEventListener('contextmenu', function(e) {
            if (e.target.closest('.cart-item, .cart-summary')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
