<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    redirect('../../views/auth/login.php');
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];

// Obtener datos del usuario para prellenar el formulario
$user_query = "
    SELECT u.email, c.name, c.phone, c.address, c.city, c.country
    FROM users u 
    LEFT JOIN customers c ON u.customer_id = c.id 
    WHERE u.id = ?
";
$user_stmt = $db->prepare($user_query);
$user_stmt->execute([$user_id]);
$user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Si no hay datos del cliente, inicializar array vacío
if (!$user_data) {
    $user_data = [
        'email' => '',
        'name' => '',
        'phone' => '',
        'address' => '',
        'city' => '',
        'country' => ''
    ];
}

// Separar nombre y apellido si están juntos
$full_name_parts = explode(' ', $user_data['name'] ?? '', 2);
$first_name = $full_name_parts[0] ?? '';
$last_name = $full_name_parts[1] ?? '';

// Verificar si hay errores de checkout
$checkout_error = '';
if (isset($_SESSION['checkout_error'])) {
    $checkout_error = $_SESSION['checkout_error'];
    unset($_SESSION['checkout_error']);
}

// Verificar que hay productos en el carrito
$cart_count_stmt = $db->prepare("SELECT COUNT(*) FROM shopping_cart WHERE user_id = ?");
$cart_count_stmt->execute([$user_id]);
$cart_has_items = $cart_count_stmt->fetchColumn() > 0;

if (!$cart_has_items) {
    redirect('index.php');
}

// Obtener productos del carrito para mostrar en el resumen
$cart_query = "
    SELECT 
        sc.quantity,
        sc.price_at_time,
        p.name as product_name,
        p.image,
        (sc.quantity * sc.price_at_time) as subtotal
    FROM shopping_cart sc
    JOIN products p ON sc.product_id = p.id
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
$shipping = $subtotal >= 100 ? 0 : 15.00;
$total = $subtotal + $tax_amount + $shipping;

// Función para formatear precios correctamente
function formatPrice($amount) {
    $amount = floatval($amount);
    return '$' . number_format($amount, 2, '.', ',');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Catálogo de Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
    <link href="../../assets/css/bootstrap-dark.css" rel="stylesheet">
    <link href="../../assets/css/checkout.css" rel="stylesheet">
</head>
<body class="checkout-container">
    <?php include '../../includes/nav.php'; ?>

    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-11">
                <!-- Encabezado del checkout -->
                <div class="checkout-header">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                        <div class="mb-3 mb-md-0">
                            <h1 class="mb-2">
                                <i class="fas fa-credit-card me-2"></i> 
                                Finalizar Compra
                            </h1>
                            <p class="text-muted mb-0">Complete los datos para procesar su pedido</p>
                        </div>
                        <div>
                            <a href="index.php" class="btn btn-outline-secondary btn-checkout-action">
                                <i class="fas fa-arrow-left me-2"></i> Volver al Carrito
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Mensaje de error -->
                <?php if ($checkout_error): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($checkout_error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
                <?php endif; ?>

                <!-- Indicador de pasos -->
                <div class="checkout-steps">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="step active" id="stepIndicator1">
                                <div class="step-number">1</div>
                                <div class="step-text">Información de Envío</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="step inactive" id="stepIndicator2">
                                <div class="step-number">2</div>
                                <div class="step-text">Método de Pago</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="step inactive" id="stepIndicator3">
                                <div class="step-number">3</div>
                                <div class="step-text">Confirmación</div>
                            </div>
                        </div>
                    </div>
                    <!-- Barra de progreso -->
                    <div class="step-progress">
                        <div class="step-progress-bar" id="progressBar" style="width: 33.33%;"></div>
                    </div>
                </div>

                <div class="row">
                    <!-- Formulario de checkout -->
                    <div class="col-lg-8 col-12">
                        <form action="process_checkout.php" method="POST" id="checkoutForm">
                            <!-- Campos ocultos para el procesamiento -->
                            <input type="hidden" name="customer_name" id="customer_name">
                            <input type="hidden" name="customer_email" id="customer_email">
                            <input type="hidden" name="customer_phone" id="customer_phone">
                            <input type="hidden" name="payment_method" id="customer_payment_method">
                            <input type="hidden" name="notes" id="notes">
                            
                            <div class="checkout-form">
                                
                                <!-- ETAPA 1: Información de envío -->
                                <div class="checkout-step active" id="step1">
                                    <div class="form-section">
                                        <h5>
                                            <i class="fas fa-shipping-fast"></i>
                                            Información de Envío
                                        </h5>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="firstName" class="form-label">Nombre *</label>
                                                <input type="text" class="form-control" id="firstName" name="first_name" 
                                                       value="<?php echo htmlspecialchars($first_name); ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="lastName" class="form-label">Apellido *</label>
                                                <input type="text" class="form-control" id="lastName" name="last_name" 
                                                       value="<?php echo htmlspecialchars($last_name); ?>" required>
                                            </div>
                                            <div class="col-12">
                                                <label for="email" class="form-label">Correo Electrónico *</label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                                            </div>
                                            <div class="col-12">
                                                <label for="phone" class="form-label">Teléfono *</label>
                                                <input type="tel" class="form-control" id="phone" name="phone" 
                                                       value="<?php echo htmlspecialchars($user_data['phone']); ?>" 
                                                       required maxlength="14" placeholder="+507 6345-6789">
                                                <div class="form-text">Formato: +507 6345-6789</div>
                                            </div>
                                            <div class="col-12">
                                                <label for="address" class="form-label">Dirección *</label>
                                                <input type="text" class="form-control" id="address" name="address" 
                                                       value="<?php echo htmlspecialchars($user_data['address']); ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="city" class="form-label">Ciudad *</label>
                                                <input type="text" class="form-control" id="city" name="city" 
                                                       value="<?php echo htmlspecialchars($user_data['city']); ?>" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="state" class="form-label">Estado/Provincia *</label>
                                                <input type="text" class="form-control" id="state" name="state" 
                                                       value="<?php echo htmlspecialchars($user_data['country'] ?: 'Panamá'); ?>" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="zipCode" class="form-label">Código Postal *</label>
                                                <input type="text" class="form-control" id="zipCode" name="zip_code" required maxlength="6" pattern="[0-9]{1,6}" placeholder="000000">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="step-navigation">
                                        <a href="index.php" class="btn btn-outline-secondary btn-checkout-action">
                                            <i class="fas fa-arrow-left me-2"></i> Volver al Carrito
                                        </a>
                                        <button type="button" class="btn btn-primary btn-checkout-action" onclick="nextStep(2)">
                                            Continuar <i class="fas fa-arrow-right ms-2"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- ETAPA 2: Método de pago -->
                                <div class="checkout-step" id="step2">
                                    <div class="form-section">
                                        <h5>
                                            <i class="fas fa-credit-card"></i>
                                            Método de Pago
                                        </h5>
                                        
                                        <div class="payment-method" onclick="selectPaymentMethod('credit_card')">
                                            <label class="payment-method-label">
                                                <input type="radio" name="payment_method" value="credit_card" id="creditCard" required>
                                                <i class="fas fa-credit-card payment-method-icon"></i>
                                                Tarjeta de Crédito/Débito
                                            </label>
                                        </div>

                                        <div class="payment-method" onclick="selectPaymentMethod('paypal')">
                                            <label class="payment-method-label">
                                                <input type="radio" name="payment_method" value="paypal" id="paypal">
                                                <i class="fab fa-paypal payment-method-icon"></i>
                                                PayPal
                                            </label>
                                        </div>

                                        <div class="payment-method" onclick="selectPaymentMethod('cash_on_delivery')">
                                            <label class="payment-method-label">
                                                <input type="radio" name="payment_method" value="cash_on_delivery" id="cashOnDelivery">
                                                <i class="fas fa-money-bill payment-method-icon"></i>
                                                Pago contra entrega
                                            </label>
                                        </div>

                                        <!-- Campos de tarjeta de crédito -->
                                        <div id="creditCardFields" class="mt-3" style="display: none;">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label for="cardNumber" class="form-label">Número de Tarjeta</label>
                                                    <input type="text" class="form-control" id="cardNumber" name="card_number" 
                                                           placeholder="1234 5678 9012 3456" maxlength="19">
                                                </div>
                                                <div class="col-md-8">
                                                    <label for="cardName" class="form-label">Nombre en la Tarjeta</label>
                                                    <input type="text" class="form-control" id="cardName" name="card_name">
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="cardExpiry" class="form-label">MM/AA</label>
                                                    <input type="text" class="form-control" id="cardExpiry" name="card_expiry" 
                                                           placeholder="12/25" maxlength="5">
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="cardCvv" class="form-label">CVV</label>
                                                    <input type="text" class="form-control" id="cardCvv" name="card_cvv" 
                                                           placeholder="123" maxlength="4">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Comentarios adicionales -->
                                    <div class="form-section">
                                        <h5>
                                            <i class="fas fa-comment"></i>
                                            Comentarios Adicionales
                                        </h5>
                                        <div class="mb-3">
                                            <label for="comments" class="form-label">Notas especiales para el pedido (opcional)</label>
                                            <textarea class="form-control" id="comments" name="comments" rows="3" 
                                                      placeholder="Instrucciones especiales de entrega, referencias, etc."></textarea>
                                        </div>
                                    </div>

                                    <div class="step-navigation">
                                        <button type="button" class="btn btn-outline-secondary btn-checkout-action" onclick="prevStep(1)">
                                            <i class="fas fa-arrow-left me-2"></i> Anterior
                                        </button>
                                        <button type="button" class="btn btn-primary btn-checkout-action" onclick="nextStep(3)">
                                            Continuar <i class="fas fa-arrow-right ms-2"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- ETAPA 3: Confirmación -->
                                <div class="checkout-step" id="step3">
                                    <div class="form-section">
                                        <h5>
                                            <i class="fas fa-check-circle"></i>
                                            Confirma tu Pedido
                                        </h5>
                                        
                                        <!-- Información de envío -->
                                        <div class="confirmation-section">
                                            <h6>
                                                <i class="fas fa-shipping-fast"></i>
                                                Información de Envío
                                                <button type="button" class="edit-button ms-auto" onclick="editStep(1)">
                                                    <i class="fas fa-edit"></i> Editar
                                                </button>
                                            </h6>
                                            <div id="shippingConfirmation" class="confirmation-data">
                                                <!-- Se llena dinámicamente -->
                                            </div>
                                        </div>

                                        <!-- Método de pago -->
                                        <div class="confirmation-section">
                                            <h6>
                                                <i class="fas fa-credit-card"></i>
                                                Método de Pago
                                                <button type="button" class="edit-button ms-auto" onclick="editStep(2)">
                                                    <i class="fas fa-edit"></i> Editar
                                                </button>
                                            </h6>
                                            <div id="paymentConfirmation" class="confirmation-data">
                                                <!-- Se llena dinámicamente -->
                                            </div>
                                        </div>

                                        <!-- Términos y condiciones -->
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="terms" required>
                                            <label class="form-check-label" for="terms">
                                                Acepto los <a href="#" class="text-decoration-none">términos y condiciones</a> *
                                            </label>
                                        </div>
                                    </div>

                                    <div class="step-navigation">
                                        <button type="button" class="btn btn-outline-secondary btn-checkout-action" onclick="prevStep(2)">
                                            <i class="fas fa-arrow-left me-2"></i> Anterior
                                        </button>
                                        <button type="submit" class="btn btn-primary btn-checkout-action btn-lg">
                                            <i class="fas fa-lock me-2"></i> Realizar Pedido
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Resumen del pedido -->
                    <div class="col-lg-4 col-12">
                        <div class="order-summary p-4">
                            <div class="text-center mb-4">
                                <h4 class="mb-2">
                                    <i class="fas fa-receipt me-2"></i> 
                                    Resumen del Pedido
                                </h4>
                                <small class="text-muted">Revisa tu compra antes de continuar</small>
                            </div>

                            <!-- Lista de productos -->
                            <div class="order-items mb-4">
                                <?php foreach ($cart_items as $item): ?>
                                <div class="order-item">
                                    <?php if ($item['image']): ?>
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                         class="order-item-image"
                                         onerror="this.onerror=null; this.src='../../assets/images/placeholder.png';">
                                    <?php else: ?>
                                    <div class="order-item-image bg-secondary d-flex align-items-center justify-content-center">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="order-item-details">
                                        <div class="order-item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                        <div class="order-item-quantity">Cantidad: <?php echo $item['quantity']; ?></div>
                                    </div>
                                    
                                    <div class="order-item-price"><?php echo formatPrice($item['subtotal']); ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Totales -->
                            <div class="order-totals">
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
                                    <span class="text-muted">
                                        <i class="fas fa-shopping-bag me-1"></i>
                                        Subtotal (<?php echo $total_items; ?> <?php echo $total_items == 1 ? 'producto' : 'productos'; ?>)
                                    </span>
                                    <span class="fw-semibold"><?php echo formatPrice($subtotal); ?></span>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
                                    <span class="text-muted">
                                        <i class="fas fa-percentage me-1"></i>
                                        Impuestos (7%)
                                    </span>
                                    <span class="fw-semibold"><?php echo formatPrice($tax_amount); ?></span>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
                                    <?php if ($shipping == 0): ?>
                                    <span class="text-success">
                                        <i class="fas fa-truck me-1"></i>
                                        Envío gratuito
                                    </span>
                                    <span class="text-success fw-semibold"><?php echo formatPrice($shipping); ?></span>
                                    <?php else: ?>
                                    <span class="text-muted">
                                        <i class="fas fa-truck me-1"></i>
                                        Envío
                                    </span>
                                    <span class="fw-semibold"><?php echo formatPrice($shipping); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center py-3 mt-2">
                                    <span class="h5 mb-0 fw-bold">
                                        <i class="fas fa-calculator me-1"></i>
                                        Total Final
                                    </span>
                                    <span class="h4 fw-bold text-success mb-0"><?php echo formatPrice($total); ?></span>
                                </div>
                            </div>

                            <!-- Información de seguridad -->
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
                                            <i class="fas fa-lock me-1 text-info"></i> 
                                            Datos encriptados SSL
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
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/app.js"></script>
    <script>
        let currentStep = 1;
        const totalSteps = 3;

        // Funciones de navegación
        function nextStep(step) {
            if (validateStep(currentStep)) {
                showStep(step);
                updateConfirmation();
            }
        }

        function prevStep(step) {
            showStep(step);
        }

        function showStep(step) {
            // Ocultar todos los pasos
            document.querySelectorAll('.checkout-step').forEach(el => {
                el.classList.remove('active');
            });

            // Ocultar todos los indicadores
            document.querySelectorAll('.step').forEach(el => {
                el.classList.remove('active');
                el.classList.add('inactive');
            });

            // Mostrar el paso actual
            document.getElementById('step' + step).classList.add('active');
            document.getElementById('stepIndicator' + step).classList.remove('inactive');
            document.getElementById('stepIndicator' + step).classList.add('active');

            // Marcar pasos completados
            for (let i = 1; i < step; i++) {
                document.getElementById('stepIndicator' + i).classList.add('completed');
                document.getElementById('stepIndicator' + i).classList.remove('inactive');
            }

            // Actualizar barra de progreso
            const progressPercentage = (step / totalSteps) * 100;
            document.getElementById('progressBar').style.width = progressPercentage + '%';

            currentStep = step;
        }

        function validateStep(step) {
            switch (step) {
                case 1:
                    // Validar información de envío
                    const requiredFields = ['firstName', 'lastName', 'email', 'phone', 'address', 'city', 'state', 'zipCode'];
                    for (const field of requiredFields) {
                        const element = document.getElementById(field);
                        if (!element.value.trim()) {
                            element.focus();
                            showError(`Por favor, completa el campo ${element.previousElementSibling.textContent}`);
                            return false;
                        }
                    }
                    
                    // Validación específica de email
                    const email = document.getElementById('email');
                    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                    if (!emailRegex.test(email.value)) {
                        email.focus();
                        showError('Por favor, ingresa un correo electrónico válido (ejemplo: usuario@dominio.com)');
                        return false;
                    }
                    
                    // Validación específica de teléfono (formato +507 6345-6789)
                    const phone = document.getElementById('phone');
                    const phoneRegex = /^\+507 \d{4}-\d{4}$/;
                    if (!phoneRegex.test(phone.value)) {
                        phone.focus();
                        showError('El teléfono debe tener el formato +507 6345-6789');
                        return false;
                    }
                    // Validación específica de código postal (solo números, máximo 6 dígitos)
                    const zipCode = document.getElementById('zipCode');
                    const zipRegex = /^\d{1,6}$/;
                    if (!zipRegex.test(zipCode.value)) {
                        zipCode.focus();
                        showError('El código postal debe contener solo números y máximo 6 dígitos');
                        return false;
                    }
                    
                    return true;

                case 2:
                    // Validar método de pago
                    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
                    if (!paymentMethod) {
                        showError('Por favor, selecciona un método de pago');
                        return false;
                    }

                    // Si es tarjeta de crédito, validar campos
                    if (paymentMethod.value === 'credit_card') {
                        const cardFields = ['cardNumber', 'cardName', 'cardExpiry', 'cardCvv'];
                        for (const field of cardFields) {
                            const element = document.getElementById(field);
                            if (!element.value.trim()) {
                                element.focus();
                                showError(`Por favor, completa los datos de la tarjeta`);
                                return false;
                            }
                        }
                    }
                    return true;

                case 3:
                    // Validar términos y condiciones
                    const terms = document.getElementById('terms');
                    if (!terms.checked) {
                        showError('Debes aceptar los términos y condiciones');
                        return false;
                    }
                    return true;

                default:
                    return true;
            }
        }

        function showError(message) {
            // Crear o actualizar mensaje de error
            let errorDiv = document.querySelector('.checkout-error');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger checkout-error';
                document.querySelector('.checkout-form').prepend(errorDiv);
            }
            errorDiv.textContent = message;
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Remover el error después de 5 segundos
            setTimeout(() => {
                if (errorDiv) {
                    errorDiv.remove();
                }
            }, 5000);
        }

        function editStep(step) {
            showStep(step);
        }

        function updateConfirmation() {
            if (currentStep === 3) {
                // Actualizar información de envío
                const shippingInfo = `
                    <p><strong>Nombre:</strong> ${document.getElementById('firstName').value} ${document.getElementById('lastName').value}</p>
                    <p><strong>Email:</strong> ${document.getElementById('email').value}</p>
                    <p><strong>Teléfono:</strong> ${document.getElementById('phone').value}</p>
                    <p><strong>Dirección:</strong> ${document.getElementById('address').value}</p>
                    <p><strong>Ciudad:</strong> ${document.getElementById('city').value}, ${document.getElementById('state').value} ${document.getElementById('zipCode').value}</p>
                `;
                document.getElementById('shippingConfirmation').innerHTML = shippingInfo;

                // Actualizar información de pago
                const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
                let paymentInfo = '';
                if (paymentMethod) {
                    switch (paymentMethod.value) {
                        case 'credit_card':
                            const cardNumber = document.getElementById('cardNumber').value;
                            const maskedCard = '**** **** **** ' + cardNumber.slice(-4);
                            paymentInfo = `<p><strong>Tarjeta de Crédito:</strong> ${maskedCard}</p>`;
                            break;
                        case 'paypal':
                            paymentInfo = '<p><strong>PayPal</strong></p>';
                            break;
                        case 'cash_on_delivery':
                            paymentInfo = '<p><strong>Pago contra entrega</strong></p>';
                            break;
                    }
                }
                document.getElementById('paymentConfirmation').innerHTML = paymentInfo;
            }
        }

        // Función para seleccionar método de pago
        function selectPaymentMethod(method) {
            // Remover selección anterior
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });

            // Seleccionar el método actual
            event.currentTarget.classList.add('selected');
            
            // Marcar el radio button correspondiente
            const radioButton = event.currentTarget.querySelector('input[type="radio"]');
            if (radioButton) {
                radioButton.checked = true;
            }

            // Mostrar/ocultar campos de tarjeta
            const creditCardFields = document.getElementById('creditCardFields');
            if (method === 'credit_card') {
                creditCardFields.style.display = 'block';
                // Hacer campos obligatorios
                creditCardFields.querySelectorAll('input').forEach(input => {
                    input.required = true;
                });
            } else {
                creditCardFields.style.display = 'none';
                // Quitar obligatoriedad y limpiar valores
                creditCardFields.querySelectorAll('input').forEach(input => {
                    input.required = false;
                    input.value = '';
                });
            }
        }

        // Formatear número de tarjeta
        document.getElementById('cardNumber').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
            let formattedInputValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedInputValue;
        });

        // Formatear fecha de expiración
        document.getElementById('cardExpiry').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });

        // Validar solo números en CVV
        document.getElementById('cardCvv').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });

        // Validar solo números en código postal
        document.getElementById('zipCode').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
            if (e.target.value.length > 6) {
                e.target.value = e.target.value.substring(0, 6);
            }
        });

        // Formatear teléfono automáticamente (+507 6345-6789)
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Quitar todo lo que no sea número
            
            // Si no empieza con 507, agregarlo
            if (value.length > 0 && !value.startsWith('507')) {
                if (value.startsWith('6') || value.startsWith('2') || value.startsWith('3') || value.startsWith('4') || value.startsWith('5') || value.startsWith('7') || value.startsWith('8') || value.startsWith('9')) {
                    value = '507' + value;
                }
            }
            
            // Limitar a 11 dígitos (507 + 8 dígitos)
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            
            // Formatear según la longitud
            let formattedValue = '';
            if (value.length > 0) {
                if (value.length <= 3) {
                    formattedValue = '+' + value;
                } else if (value.length <= 7) {
                    formattedValue = '+' + value.substring(0, 3) + ' ' + value.substring(3);
                } else {
                    formattedValue = '+' + value.substring(0, 3) + ' ' + value.substring(3, 7) + '-' + value.substring(7);
                }
            }
            
            e.target.value = formattedValue;
        });

        // Al hacer focus en el teléfono, si está vacío, agregar +507
        document.getElementById('phone').addEventListener('focus', function(e) {
            if (e.target.value === '') {
                e.target.value = '+507 ';
            }
        });

        // Prevenir envío del formulario si no está en el último paso
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            if (currentStep !== 3) {
                e.preventDefault();
                nextStep(currentStep + 1);
            } else if (!validateStep(3)) {
                e.preventDefault();
            } else {
                // Mostrar indicador de procesamiento
                const submitBtn = e.target.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Procesando...';
                submitBtn.disabled = true;
                
                // Llenar campos ocultos con los datos correctos
                const firstName = document.getElementById('firstName').value;
                const lastName = document.getElementById('lastName').value;
                const email = document.getElementById('email').value;
                const phone = document.getElementById('phone').value;
                const comments = document.getElementById('comments').value;
                const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
                
                document.getElementById('customer_name').value = `${firstName} ${lastName}`;
                document.getElementById('customer_email').value = email;
                document.getElementById('customer_phone').value = phone;
                document.getElementById('customer_payment_method').value = paymentMethod ? paymentMethod.value : '';
                document.getElementById('notes').value = comments;
                
                // El formulario se enviará normalmente al process_checkout.php
                // No prevenir el envío
            }
        });

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            showStep(1);
            
            // Formatear teléfono existente si hay datos
            const phoneField = document.getElementById('phone');
            if (phoneField.value && !phoneField.value.startsWith('+507')) {
                // Si el teléfono no tiene el formato correcto, formatearlo
                let phoneValue = phoneField.value.replace(/\D/g, '');
                if (phoneValue.length === 8) {
                    phoneField.value = '+507 ' + phoneValue.substring(0, 4) + '-' + phoneValue.substring(4);
                } else if (phoneValue.length === 11 && phoneValue.startsWith('507')) {
                    phoneField.value = '+507 ' + phoneValue.substring(3, 7) + '-' + phoneValue.substring(7);
                }
            }
            
            // Animaciones al cargar la página
            const formSections = document.querySelectorAll('.form-section');
            formSections.forEach((section, index) => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    section.style.transition = 'all 0.5s ease';
                    section.style.opacity = '1';
                    section.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });
    </script>
</body>
</html>
