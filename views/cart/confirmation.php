<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    redirect('../../views/auth/login.php');
}

$checkout_info = null;

// Si se accede directamente con un número de factura (para revisión)
if (isset($_GET['invoice']) && !empty($_GET['invoice'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $user_id = $_SESSION['user_id'];
    $invoice_number = $_GET['invoice'];
    
    try {
        // Obtener información de la venta desde la base de datos
        $sale_query = "
            SELECT 
                s.*,
                i.invoice_number,
                i.total_amount as invoice_total
            FROM sales s
            INNER JOIN invoices i ON s.id = i.sale_id
            WHERE i.invoice_number = ? AND s.user_id = ?
        ";
        
        $sale_stmt = $db->prepare($sale_query);
        $sale_stmt->execute([$invoice_number, $user_id]);
        $sale_info = $sale_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sale_info) {
            $checkout_info = [
                'invoice_number' => $sale_info['invoice_number'],
                'total' => $sale_info['invoice_total'],
                'customer_name' => $sale_info['customer_name'],
                'customer_email' => $sale_info['customer_email'],
                'customer_phone' => $sale_info['customer_phone'],
                'payment_method' => $sale_info['payment_method'],
                'subtotal' => $sale_info['total_amount'],
                'tax_amount' => $sale_info['tax_amount'],
                'shipping' => 0 // Se puede calcular si es necesario
            ];
        }
    } catch (Exception $e) {
        // Si hay error, continuar con la verificación normal de sesión
    }
}

// Verificar que hay información de confirmación (del proceso normal o de la base de datos)
if (!$checkout_info) {
    if (!isset($_SESSION['checkout_success'])) {
        redirect('index.php');
    }
    
    $checkout_info = $_SESSION['checkout_success'];
    // Solo limpiar la información de la sesión si viene del proceso normal
    if (!isset($_GET['invoice'])) {
        unset($_SESSION['checkout_success']);
    }
}

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
    <title>Pedido Confirmado - Catálogo de Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
    <link href="../../assets/css/bootstrap-dark.css" rel="stylesheet">
    <style>
        /* Variables CSS para confirmación */
        :root {
            --confirmation-bg: var(--surface-color);
            --confirmation-item-bg: var(--surface-light);
            --confirmation-border: rgba(255, 255, 255, 0.1);
            --confirmation-text: var(--text-primary);
            --confirmation-text-secondary: var(--text-secondary);
            --confirmation-text-muted: var(--text-muted);
        }

        /* Contenedor principal */
        .confirmation-container {
            background: var(--background-color);
            min-height: 100vh;
            padding-top: 20px;
        }

        /* Encabezado de éxito */
        .success-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: 1px solid rgba(40, 167, 69, 0.3);
            border-radius: var(--border-radius);
            padding: 40px 30px;
            margin-bottom: 30px;
            text-align: center;
            color: white;
            box-shadow: var(--box-shadow);
        }

        .success-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            animation: successPulse 2s ease-in-out;
        }

        @keyframes successPulse {
            0% { transform: scale(0.8); opacity: 0; }
            50% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }

        .success-header h1 {
            margin-bottom: 10px;
            font-weight: 600;
        }

        .success-header .order-number {
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 25px;
            display: inline-block;
            margin-top: 15px;
            font-weight: 500;
        }

        /* Tarjetas de información */
        .info-card {
            background: var(--confirmation-item-bg);
            border: 1px solid var(--confirmation-border);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
            box-shadow: var(--box-shadow-light);
            transition: var(--transition);
        }

        .info-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--box-shadow);
        }

        .info-card h5 {
            color: var(--confirmation-text);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--confirmation-border);
            padding-bottom: 15px;
        }

        .info-card h5 i {
            margin-right: 10px;
            color: var(--accent-color);
            font-size: 1.2rem;
        }

        /* Detalles del pedido */
        .order-details {
            color: var(--confirmation-text);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--confirmation-border);
        }

        .detail-row:last-child {
            border-bottom: none;
            font-weight: 600;
            color: var(--accent-color);
            font-size: 1.1rem;
        }

        .detail-label {
            color: var(--confirmation-text-muted);
        }

        .detail-value {
            color: var(--confirmation-text);
            font-weight: 500;
        }

        /* Información de contacto */
        .contact-info {
            color: var(--confirmation-text);
        }

        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            background: var(--background-color);
            border-radius: 8px;
            border: 1px solid var(--confirmation-border);
        }

        .contact-item i {
            margin-right: 12px;
            color: var(--accent-color);
            width: 20px;
            text-align: center;
        }

        /* Pasos siguientes */
        .next-steps {
            background: var(--confirmation-item-bg);
            border: 1px solid var(--confirmation-border);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 20px;
        }

        .step-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            padding: 15px;
            background: var(--background-color);
            border-radius: 8px;
            border-left: 4px solid var(--accent-color);
        }

        .step-item:last-child {
            margin-bottom: 0;
        }

        .step-number {
            background: var(--accent-color);
            color: var(--primary-dark);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .step-content {
            color: var(--confirmation-text);
        }

        .step-title {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .step-description {
            color: var(--confirmation-text-muted);
            font-size: 0.9rem;
        }

        /* Botones de acción */
        .btn-action {
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 500;
            transition: var(--transition);
            border: 1px solid transparent;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 212, 170, 0.3);
        }

        .btn-primary.btn-action {
            background: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--primary-dark);
        }

        .btn-success.btn-action {
            background: #28a745;
            border-color: #28a745;
            color: white;
        }

        .btn-outline-primary.btn-action {
            border-color: var(--accent-color);
            color: var(--accent-color);
        }

        .btn-outline-primary.btn-action:hover {
            background: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--primary-dark);
        }

        .btn-success.btn-action:hover {
            background: #218838;
            border-color: #1e7e34;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .success-header {
                padding: 30px 20px;
            }
            
            .success-icon {
                font-size: 3rem;
            }
            
            .info-card {
                padding: 20px;
            }
            
            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .step-item {
                padding: 12px;
            }
        }

        @media (max-width: 576px) {
            .success-header h1 {
                font-size: 1.5rem;
            }
            
            .success-header .order-number {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
        }

        /* Animaciones */
        .info-card {
            animation: slideIn 0.6s ease-out forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        .info-card:nth-child(1) { animation-delay: 0.1s; }
        .info-card:nth-child(2) { animation-delay: 0.2s; }
        .info-card:nth-child(3) { animation-delay: 0.3s; }
        .info-card:nth-child(4) { animation-delay: 0.4s; }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="confirmation-container">
    <?php include '../../includes/nav.php'; ?>

    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                
                <!-- Encabezado de éxito -->
                <div class="success-header">
                    <i class="fas fa-check-circle success-icon"></i>
                    <h1>¡Pedido Confirmado con Éxito!</h1>
                    <p class="mb-0">Gracias por tu compra, <?php echo htmlspecialchars($checkout_info['customer_name']); ?>!</p>
                    <div class="order-number">
                        <i class="fas fa-receipt me-2"></i>
                        Número de Factura: <?php echo htmlspecialchars($checkout_info['invoice_number']); ?>
                    </div>
                </div>

                <div class="row">
                    <!-- Información del pedido -->
                    <div class="col-lg-8 col-12">
                        
                        <!-- Detalles del pedido -->
                        <div class="info-card">
                            <h5>
                                <i class="fas fa-shopping-bag"></i>
                                Detalles del Pedido
                            </h5>
                            <div class="order-details">
                                <div class="detail-row">
                                    <span class="detail-label">Fecha del Pedido:</span>
                                    <span class="detail-value"><?php echo date('d/m/Y H:i:s'); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Método de Pago:</span>
                                    <span class="detail-value">
                                        <?php 
                                        $payment_methods = [
                                            'credit_card' => 'Tarjeta de Crédito',
                                            'paypal' => 'PayPal',
                                            'cash_on_delivery' => 'Pago contra entrega',
                                            'efectivo' => 'Efectivo',
                                            'tarjeta_credito' => 'Tarjeta de Crédito',
                                            'tarjeta_debito' => 'Tarjeta de Débito',
                                            'transferencia' => 'Transferencia Bancaria',
                                            'cheque' => 'Cheque'
                                        ];
                                        echo $payment_methods[$checkout_info['payment_method']] ?? 'Método no especificado';
                                        ?>
                                    </span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Subtotal:</span>
                                    <span class="detail-value"><?php echo formatPrice($checkout_info['subtotal'] ?? $checkout_info['total']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Impuestos:</span>
                                    <span class="detail-value"><?php echo formatPrice($checkout_info['tax_amount'] ?? 0); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Envío:</span>
                                    <span class="detail-value"><?php echo formatPrice($checkout_info['shipping'] ?? 0); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Total:</span>
                                    <span class="detail-value"><?php echo formatPrice($checkout_info['total']); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Información de envío -->
                        <div class="info-card">
                            <h5>
                                <i class="fas fa-shipping-fast"></i>
                                Información de Envío
                            </h5>
                            <div class="contact-info">
                                <div class="contact-item">
                                    <i class="fas fa-user"></i>
                                    <span><?php echo htmlspecialchars($checkout_info['customer_name']); ?></span>
                                </div>
                                <div class="contact-item">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($checkout_info['customer_email'] ?? 'No especificado'); ?></span>
                                </div>
                                <div class="contact-item">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo htmlspecialchars($checkout_info['customer_phone'] ?? 'No especificado'); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Acciones principales -->
                        <div class="d-flex flex-column flex-md-row gap-3 mb-4">
                            <a href="print_invoice.php?invoice=<?php echo urlencode($checkout_info['invoice_number']); ?>" 
                               class="btn btn-success btn-action btn-lg" target="_blank">
                                <i class="fas fa-print me-2"></i> Imprimir Factura
                            </a>
                            <a href="../products/index.php" class="btn btn-primary btn-action btn-lg">
                                <i class="fas fa-store me-2"></i> Seguir Comprando
                            </a>
                            <a href="../../index.php" class="btn btn-outline-primary btn-action btn-lg">
                                <i class="fas fa-home me-2"></i> Ir al Inicio
                            </a>
                        </div>
                    </div>

                    <!-- Pasos siguientes -->
                    <div class="col-lg-4 col-12">
                        <div class="next-steps">
                            <h5 class="mb-4">
                                <i class="fas fa-list-ol me-2" style="color: var(--accent-color);"></i>
                                ¿Qué sigue?
                            </h5>
                            
                            <div class="step-item">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <div class="step-title">Confirmación por Email</div>
                                    <div class="step-description">
                                        Recibirás un email de confirmación con todos los detalles de tu pedido.
                                    </div>
                                </div>
                            </div>

                            <div class="step-item">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <div class="step-title">Procesamiento</div>
                                    <div class="step-description">
                                        Tu pedido será procesado y preparado para envío en 1-2 días hábiles.
                                    </div>
                                </div>
                            </div>

                            <div class="step-item">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <div class="step-title">Envío</div>
                                    <div class="step-description">
                                        Te notificaremos cuando tu pedido sea enviado con información de seguimiento.
                                    </div>
                                </div>
                            </div>

                            <div class="step-item">
                                <div class="step-number">4</div>
                                <div class="step-content">
                                    <div class="step-title">Entrega</div>
                                    <div class="step-description">
                                        Tu pedido llegará en 3-5 días hábiles a la dirección especificada.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de contacto -->
                        <div class="info-card">
                            <h5>
                                <i class="fas fa-headset"></i>
                                ¿Necesitas Ayuda?
                            </h5>
                            <div class="contact-info">
                                <div class="contact-item">
                                    <i class="fas fa-envelope"></i>
                                    <span>soporte@tienda.com</span>
                                </div>
                                <div class="contact-item">
                                    <i class="fas fa-phone"></i>
                                    <span>+507 1234-5678</span>
                                </div>
                                <div class="contact-item">
                                    <i class="fas fa-clock"></i>
                                    <span>Lun-Vie: 8:00 AM - 6:00 PM</span>
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
        // Animación de confeti al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            // Crear efecto de confeti simple
            function createConfetti() {
                const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57'];
                const confettiContainer = document.createElement('div');
                confettiContainer.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    pointer-events: none;
                    z-index: 1000;
                `;
                document.body.appendChild(confettiContainer);

                for (let i = 0; i < 50; i++) {
                    const confetti = document.createElement('div');
                    confetti.style.cssText = `
                        position: absolute;
                        width: 10px;
                        height: 10px;
                        background: ${colors[Math.floor(Math.random() * colors.length)]};
                        border-radius: 50%;
                        left: ${Math.random() * 100}%;
                        top: -10px;
                        animation: confettiFall ${2 + Math.random() * 3}s linear forwards;
                    `;
                    confettiContainer.appendChild(confetti);
                }

                // Remover confeti después de la animación
                setTimeout(() => {
                    confettiContainer.remove();
                }, 5000);
            }

            // Agregar CSS para la animación de confeti
            const style = document.createElement('style');
            style.textContent = `
                @keyframes confettiFall {
                    to {
                        transform: translateY(100vh) rotate(360deg);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);

            // Ejecutar confeti después de un pequeño delay
            setTimeout(createConfetti, 500);
        });
    </script>
</body>
</html>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/app.js"></script>
</body>
</html>
