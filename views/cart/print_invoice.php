<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    redirect('../../views/auth/login.php');
}

// Verificar que se proporcionó un número de factura
if (!isset($_GET['invoice']) || empty($_GET['invoice'])) {
    redirect('index.php');
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$invoice_number = $_GET['invoice'];

try {
    // Obtener información de la venta y factura
    $sale_query = "
        SELECT 
            s.*,
            i.invoice_number,
            i.subtotal as invoice_subtotal,
            i.tax_amount as invoice_tax,
            i.total_amount as invoice_total,
            i.invoice_status,
            i.payment_terms,
            i.created_at as invoice_date,
            u.username as processed_by
        FROM sales s
        INNER JOIN invoices i ON s.id = i.sale_id
        INNER JOIN users u ON s.user_id = u.id
        WHERE i.invoice_number = ? AND s.user_id = ?
    ";

    $sale_stmt = $db->prepare($sale_query);
    $sale_stmt->execute([$invoice_number, $user_id]);
    $sale_info = $sale_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale_info) {
        $_SESSION['error'] = 'Factura no encontrada o no tiene permisos para verla.';
        redirect('index.php');
    }

    // Obtener detalles de la venta
    $details_query = "
        SELECT 
            sd.*,
            p.image,
            p.description,
            c.name as category_name
        FROM sale_details sd
        LEFT JOIN products p ON sd.product_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE sd.sale_id = ?
        ORDER BY sd.id
    ";

    $details_stmt = $db->prepare($details_query);
    $details_stmt->execute([$sale_info['id']]);
    $sale_details = $details_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $_SESSION['error'] = 'Error al cargar la factura: ' . $e->getMessage();
    redirect('index.php');
}

// Función para formatear precios
function formatPrice($amount) {
    $amount = floatval($amount);
    return '$' . number_format($amount, 2, '.', ',');
}

// Función para formatear fechas
function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura <?php echo htmlspecialchars($sale_info['invoice_number']); ?> - Catálogo de Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Estilos para impresión optimizados para una sola página */
        @media print {
            .no-print {
                display: none !important;
            }
            
            .print-only {
                display: block !important;
            }
            
            body {
                margin: 0;
                padding: 10px !important;
                background: white !important;
                color: black !important;
                font-size: 12px !important;
                line-height: 1.3 !important;
            }
            
            .invoice-container {
                box-shadow: none !important;
                border: 1px solid #000 !important;
                margin: 0 !important;
                padding: 15px !important;
                max-width: 100% !important;
                width: 100% !important;
                page-break-inside: avoid;
            }
            
            .invoice-header {
                padding: 10px !important;
                margin-bottom: 10px !important;
                border-bottom: 2px solid #000 !important;
                background: white !important;
                color: black !important;
            }
            
            .company-logo {
                font-size: 18px !important;
                margin-bottom: 5px !important;
            }
            
            .company-info {
                font-size: 10px !important;
            }
            
            .invoice-info {
                padding: 10px !important;
                margin-bottom: 10px !important;
                border-bottom: 1px solid #000 !important;
                background: white !important;
            }
            
            .invoice-details-section {
                padding: 10px !important;
            }
            
            .product-table {
                margin: 10px 0 !important;
                width: 100% !important;
            }
            
            .product-table th {
                background: #f0f0f0 !important;
                color: black !important;
                font-weight: bold !important;
                border: 1px solid #000 !important;
                padding: 5px !important;
                font-size: 10px !important;
            }
            
            .product-table td {
                padding: 4px !important;
                border: 1px solid #000 !important;
                font-size: 10px !important;
                vertical-align: top !important;
            }
            
            .product-image {
                width: 30px !important;
                height: 30px !important;
                display: none !important; /* Ocultar imágenes para ahorrar espacio */
            }
            
            .totals-section {
                background: white !important;
                padding: 10px !important;
                border-top: 2px solid #000 !important;
                margin-top: 10px !important;
            }
            
            .total-row {
                padding: 3px 0 !important;
                border-bottom: 1px solid #ccc !important;
                font-size: 11px !important;
            }
            
            .total-row:last-child {
                border-bottom: 2px solid #000 !important;
                font-size: 12px !important;
                font-weight: bold !important;
            }
            
            .invoice-footer {
                background: white !important;
                color: black !important;
                padding: 8px !important;
                border-top: 1px solid #000 !important;
                font-size: 9px !important;
                text-align: center !important;
            }
            
            .status-badge {
                background: white !important;
                color: black !important;
                border: 1px solid #000 !important;
                padding: 2px 8px !important;
                font-size: 9px !important;
            }
            
            h4, h5, h6 {
                font-size: 12px !important;
                margin: 8px 0 5px 0 !important;
            }
            
            p {
                margin: 3px 0 !important;
                font-size: 10px !important;
            }
            
            .row {
                margin: 0 !important;
            }
            
            .col-md-6 {
                width: 50% !important;
                float: left !important;
                padding: 0 5px !important;
            }
            
            .text-md-end {
                text-align: right !important;
            }
            
            /* Comprimir el espacio entre elementos */
            .mb-2, .mb-3, .mb-4 {
                margin-bottom: 5px !important;
            }
            
            .mt-4 {
                margin-top: 8px !important;
            }
            
            .p-3 {
                padding: 5px !important;
            }
            
            .border-start {
                border-left: 3px solid #000 !important;
            }
            
            /* Ajustar tabla para que quepa mejor */
            .table-responsive {
                overflow: visible !important;
            }
            
            .product-table th:first-child,
            .product-table td:first-child {
                display: none !important; /* Ocultar columna de imagen */
            }
            
            /* Asegurar que todo quepa en una página */
            .page-break {
                page-break-before: avoid !important;
                page-break-after: avoid !important;
                page-break-inside: avoid !important;
            }
            
            /* Compresión adicional para factura compacta */
            .invoice-details-section {
                padding: 8px !important;
            }
            
            .notes-section {
                display: none !important; /* Ocultar notas en impresión para ahorrar espacio */
            }
            
            /* Comprimir información del cliente */
            .customer-info p {
                margin: 2px 0 !important;
                font-size: 9px !important;
            }
            
            /* Comprimir totales */
            .totals-section .col-md-6 {
                width: 50% !important;
                float: left !important;
                padding: 5px !important;
            }
            
            .payment-info {
                font-size: 9px !important;
            }
            
            .payment-info p {
                margin: 2px 0 !important;
            }
            
            /* Ocultar elementos no esenciales en impresión */
            .text-secondary,
            .text-muted,
            .no-print {
                display: none !important;
            }
            
            /* Hacer visible elementos específicos para impresión */
            .print-only {
                display: inline !important;
            }
        }

        @media screen {
            .print-only {
                display: none;
            }
            
            .notes-section {
                display: block !important;
            }
        }

        /* Estilos generales para la factura */
        .invoice-container {
            background: white;
            color: #333;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
            margin: 20px auto;
            max-width: 800px;
        }

        .invoice-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .company-logo {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .company-info {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .invoice-info {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 2px solid #e9ecef;
        }

        .invoice-details-section {
            padding: 30px;
        }

        .product-table {
            margin: 20px 0;
        }

        .product-table th {
            background: #343a40;
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px 10px;
        }

        .product-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }

        .totals-section {
            background: #f8f9fa;
            padding: 20px;
            border-top: 2px solid #e9ecef;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .total-row:last-child {
            border-bottom: none;
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .invoice-footer {
            background: #2c3e50;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-paid {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .print-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .back-button {
            position: fixed;
            bottom: 30px;
            left: 30px;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        @media (max-width: 768px) {
            .invoice-container {
                margin: 10px;
                border-radius: 0;
            }
            
            .invoice-header {
                padding: 20px;
            }
            
            .company-logo {
                font-size: 2rem;
            }
            
            .invoice-details-section {
                padding: 20px;
            }
            
            .product-table th,
            .product-table td {
                padding: 8px 5px;
                font-size: 0.9rem;
            }
            
            .product-image {
                width: 40px;
                height: 40px;
            }
            
            .print-button,
            .back-button {
                position: relative;
                bottom: auto;
                right: auto;
                left: auto;
                margin: 10px;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Encabezado de la empresa -->
        <div class="invoice-header">
            <div class="company-logo">
                <i class="fas fa-store me-2"></i>
                CodeCorp Electronics
            </div>
            <div class="company-info">
                Catálogo de Productos Tecnológicos<br>
                Panamá, Panamá | Tel: +507 123-4567<br>
                info@codecorp.com | www.codecorp.com
            </div>
        </div>

        <!-- Información de la factura -->
        <div class="invoice-info">
            <div class="row">
                <div class="col-md-6">
                    <h4 class="text-primary mb-3">
                        <i class="fas fa-file-invoice me-2"></i>
                        Factura: <?php echo htmlspecialchars($sale_info['invoice_number']); ?>
                    </h4>
                    <p class="mb-2">
                        <strong>Fecha de Emisión:</strong> <?php echo formatDate($sale_info['invoice_date']); ?>
                    </p>
                    <p class="mb-2">
                        <strong>Fecha de Venta:</strong> <?php echo formatDate($sale_info['sale_date']); ?>
                    </p>
                    <p class="mb-2">
                        <strong>Procesado por:</strong> <?php echo htmlspecialchars($sale_info['processed_by']); ?>
                    </p>
                </div>
                <div class="col-md-6 text-md-end customer-info">
                    <h5 class="text-secondary mb-3">Datos del Cliente</h5>
                    <p class="mb-2">
                        <strong><?php echo htmlspecialchars($sale_info['customer_name']); ?></strong>
                    </p>
                    <?php if ($sale_info['customer_email']): ?>
                    <p class="mb-2">
                        <i class="fas fa-envelope me-1"></i>
                        <?php echo htmlspecialchars($sale_info['customer_email']); ?>
                    </p>
                    <?php endif; ?>
                    <?php if ($sale_info['customer_phone']): ?>
                    <p class="mb-2">
                        <i class="fas fa-phone me-1"></i>
                        <?php echo htmlspecialchars($sale_info['customer_phone']); ?>
                    </p>
                    <?php endif; ?>
                    <p class="mb-0">
                        <span class="status-badge status-<?php echo $sale_info['payment_status'] == 'pagado' ? 'paid' : ($sale_info['payment_status'] == 'pendiente' ? 'pending' : 'cancelled'); ?>">
                            <?php echo ucfirst($sale_info['payment_status']); ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Detalles de la venta -->
        <div class="invoice-details-section">
            <h5 class="mb-4">
                <i class="fas fa-list me-2"></i>
                Detalles de la Compra
            </h5>

            <div class="table-responsive">
                <table class="table product-table page-break">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Imagen</th>
                            <th>Producto</th>
                            <th style="width: 100px;" class="text-center">Cantidad</th>
                            <th style="width: 120px;" class="text-end">Precio Unit.</th>
                            <th style="width: 120px;" class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sale_details as $detail): ?>
                        <tr>
                            <td>
                                <?php if ($detail['image']): ?>
                                <img src="<?php echo htmlspecialchars($detail['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($detail['product_name']); ?>"
                                     class="product-image"
                                     onerror="this.style.display='none';">
                                <?php else: ?>
                                <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                    <i class="fas fa-image text-muted"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($detail['product_name']); ?></strong>
                                    <span class="print-only" style="display: none;">
                                        <?php if ($detail['category_name']): ?>
                                        - <?php echo htmlspecialchars($detail['category_name']); ?>
                                        <?php endif; ?>
                                    </span>
                                    <div class="no-print">
                                        <?php if ($detail['category_name']): ?>
                                        <br><small class="text-muted">
                                            <i class="fas fa-tag me-1"></i>
                                            <?php echo htmlspecialchars($detail['category_name']); ?>
                                        </small>
                                        <?php endif; ?>
                                        <?php if ($detail['description'] && strlen($detail['description']) > 0): ?>
                                        <br><small class="text-muted">
                                            <?php echo htmlspecialchars(substr($detail['description'], 0, 50)); ?><?php echo strlen($detail['description']) > 50 ? '...' : ''; ?>
                                        </small>
                                        <?php endif; ?>
                                        <?php if ($detail['discount_per_item'] > 0): ?>
                                        <br><small class="text-success">
                                            <i class="fas fa-percent me-1"></i>
                                            Descuento: <?php echo formatPrice($detail['discount_per_item']); ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary fs-6 no-print">
                                    <?php echo $detail['quantity']; ?>
                                </span>
                                <span class="print-only" style="display: none;">
                                    <?php echo $detail['quantity']; ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <?php echo formatPrice($detail['product_price']); ?>
                            </td>
                            <td class="text-end">
                                <strong><?php echo formatPrice($detail['subtotal']); ?></strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($sale_info['notes']): ?>
            <div class="mt-4 p-3 bg-light border-start border-primary border-4 notes-section">
                <h6 class="text-primary mb-2">
                    <i class="fas fa-sticky-note me-2"></i>
                    Notas Adicionales
                </h6>
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($sale_info['notes'])); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sección de totales -->
        <div class="totals-section">
            <div class="row">
                <div class="col-md-6 payment-info">
                    <h6 class="text-secondary mb-3">Información de Pago</h6>
                    <p class="mb-2">
                        <strong>Método de Pago:</strong> 
                        <?php 
                        $payment_methods = [
                            'efectivo' => 'Efectivo',
                            'tarjeta_credito' => 'Tarjeta de Crédito',
                            'tarjeta_debito' => 'Tarjeta de Débito',
                            'transferencia' => 'Transferencia Bancaria',
                            'cheque' => 'Cheque'
                        ];
                        echo $payment_methods[$sale_info['payment_method']] ?? ucfirst($sale_info['payment_method']);
                        ?>
                    </p>
                    <p class="mb-2">
                        <strong>Términos de Pago:</strong> <?php echo htmlspecialchars($sale_info['payment_terms']); ?>
                    </p>
                    <p class="mb-0">
                        <strong>Estado:</strong> 
                        <span class="status-badge status-<?php echo $sale_info['payment_status'] == 'pagado' ? 'paid' : ($sale_info['payment_status'] == 'pendiente' ? 'pending' : 'cancelled'); ?>">
                            <?php echo ucfirst($sale_info['payment_status']); ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-6">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span><?php echo formatPrice($sale_info['invoice_subtotal']); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Impuestos (7%):</span>
                        <span><?php echo formatPrice($sale_info['invoice_tax']); ?></span>
                    </div>
                    <?php if ($sale_info['discount_amount'] > 0): ?>
                    <div class="total-row text-success">
                        <span>Descuento:</span>
                        <span>-<?php echo formatPrice($sale_info['discount_amount']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="total-row">
                        <span><strong>TOTAL A PAGAR:</strong></span>
                        <span><strong><?php echo formatPrice($sale_info['invoice_total']); ?></strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie de página -->
        <div class="invoice-footer">
            <p class="mb-2">
                <strong>¡Gracias por su compra!</strong>
            </p>
            <p class="mb-0">
                Esta factura es un documento generado electrónicamente.<br>
                Para cualquier consulta, contáctenos al +507 123-4567 o info@codecorp.com
            </p>
        </div>
    </div>

    <!-- Botones de acción (solo en pantalla) -->
    <div class="no-print">
        <button onclick="printInvoice()" class="btn btn-primary btn-lg print-button">
            <i class="fas fa-print me-2"></i>
            Imprimir Factura
        </button>
        
        <a href="confirmation.php?invoice=<?php echo urlencode($sale_info['invoice_number']); ?>" 
           class="btn btn-secondary btn-lg back-button">
            <i class="fas fa-arrow-left me-2"></i>
            Volver
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function printInvoice() {
            // Configurar la página para impresión óptima
            const originalTitle = document.title;
            document.title = 'Factura - <?php echo $sale_info['invoice_number']; ?>';
            
            // Crear un estilo temporal para optimizar la impresión
            const printStyle = document.createElement('style');
            printStyle.textContent = `
                @media print {
                    @page { 
                        size: A4; 
                        margin: 0.5in; 
                    }
                    body { 
                        -webkit-print-color-adjust: exact; 
                        print-color-adjust: exact; 
                    }
                }
            `;
            document.head.appendChild(printStyle);
            
            // Enfocar la ventana y imprimir
            window.focus();
            window.print();
            
            // Limpiar después de imprimir
            setTimeout(() => {
                document.title = originalTitle;
                if (printStyle.parentNode) {
                    document.head.removeChild(printStyle);
                }
            }, 1000);
        }

        // Función para imprimir automáticamente si se especifica en la URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('auto_print') === '1') {
                setTimeout(() => {
                    printInvoice();
                }, 1000);
            }
        });

        // Manejar el evento de impresión
        window.addEventListener('beforeprint', function() {
            document.title = 'Factura <?php echo htmlspecialchars($sale_info['invoice_number']); ?> - CodeCorp Electronics';
        });

        window.addEventListener('afterprint', function() {
            document.title = 'Factura <?php echo htmlspecialchars($sale_info['invoice_number']); ?> - Catálogo de Productos';
        });

        // Función para descargar como PDF (si es necesario en el futuro)
        function downloadPDF() {
            // Esta función se puede implementar con librerías como jsPDF o enviando a un endpoint del servidor
            alert('Función de descarga PDF en desarrollo');
        }

        // Atajos de teclado
        document.addEventListener('keydown', function(e) {
            // Ctrl+P o Cmd+P para imprimir
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                printInvoice();
            }
            
            // Escape para volver
            if (e.key === 'Escape') {
                window.history.back();
            }
        });
    </script>
</body>
</html>
