<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    redirect('../../views/auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    redirect('index.php');
}

// Validar que se hayan enviado los datos requeridos
$required_fields = ['customer_name', 'customer_email', 'customer_phone', 'payment_method'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['checkout_error'] = 'Faltan datos requeridos para procesar el pedido.';
        redirect('checkout.php');
    }
}

// Recoger si se debe crear el XML
$crear_xml = isset($_POST['crear_xml']) && $_POST['crear_xml'] === 'on';

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];

try {
    // Iniciar transacción
    $db->beginTransaction();

    // Obtener productos del carrito
    $cart_query = "
        SELECT 
            sc.product_id,
            sc.quantity,
            sc.price_at_time,
            p.name as product_name,
            (sc.quantity * sc.price_at_time) as subtotal
        FROM shopping_cart sc
        JOIN products p ON sc.product_id = p.id
        WHERE sc.user_id = ?
    ";

    $cart_stmt = $db->prepare($cart_query);
    $cart_stmt->execute([$user_id]);
    $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cart_items)) {
        throw new Exception('El carrito está vacío.');
    }

    // Calcular totales
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['subtotal'];
    }

    $tax_rate = 0.07;
    $tax_amount = $subtotal * $tax_rate;
    $shipping = $subtotal >= 100 ? 0 : 15.00; // Envío gratis para compras >= $100, sino $15
    $final_amount = $subtotal + $tax_amount + $shipping;

    // Generar número de factura
    $current_year = date('Y');
    $invoice_number = 'INV-' . $current_year . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

    // Verificar que el número de factura sea único
    $check_invoice = $db->prepare("SELECT COUNT(*) FROM sales WHERE invoice_number = ?");
    $check_invoice->execute([$invoice_number]);
    
    while ($check_invoice->fetchColumn() > 0) {
        $invoice_number = 'INV-' . $current_year . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $check_invoice->execute([$invoice_number]);
    }

    // Insertar la venta
    $sale_stmt = $db->prepare("
        INSERT INTO sales (
            user_id, 
            customer_name, 
            customer_email, 
            customer_phone, 
            total_amount, 
            tax_amount, 
            final_amount, 
            payment_method, 
            payment_status, 
            notes, 
            invoice_number
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pagado', ?, ?)
    ");

    $sale_stmt->execute([
        $user_id,
        $_POST['customer_name'],
        $_POST['customer_email'],
        $_POST['customer_phone'],
        $subtotal,
        $tax_amount,
        $final_amount,
        $_POST['payment_method'],
        $_POST['notes'] ?? '',
        $invoice_number
    ]);

    $sale_id = $db->lastInsertId();

    // Insertar los detalles de la venta
    $detail_stmt = $db->prepare("
        INSERT INTO sale_details (
            sale_id, 
            product_id, 
            product_name, 
            product_price, 
            quantity, 
            subtotal
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($cart_items as $item) {
        $detail_stmt->execute([
            $sale_id,
            $item['product_id'],
            $item['product_name'],
            $item['price_at_time'],
            $item['quantity'],
            $item['subtotal']
        ]);

        // Restar stock del producto
        $update_stock_stmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $update_stock_stmt->execute([$item['quantity'], $item['product_id']]);
    }

    // Crear la factura
    $invoice_stmt = $db->prepare("
        INSERT INTO invoices (
            sale_id,
            invoice_number,
            subtotal,
            tax_amount,
            total_amount,
            invoice_status,
            payment_terms
        ) VALUES (?, ?, ?, ?, ?, 'enviada', 'Pago inmediato')
    ");

    $invoice_stmt->execute([
        $sale_id,
        $invoice_number,
        $subtotal,
        $tax_amount,
        $final_amount
    ]);


    // Limpiar el carrito
    $clear_cart = $db->prepare("DELETE FROM shopping_cart WHERE user_id = ?");
    $clear_cart->execute([$user_id]);

    // Confirmar transacción
    $db->commit();

    // Preparar datos para XML/imagen si corresponde
    $zip_name = null;
    if ($crear_xml) {
        require_once __DIR__ . '/../../xml/xml_generator.php';
        $xml_data = [
            'invoice_number' => $invoice_number,
            'total' => $final_amount,
            'customer_name' => $_POST['customer_name'],
            'customer_lastname' => $_POST['customer_lastname'] ?? ($_POST['last_name'] ?? ''),
            'customer_email' => $_POST['customer_email'],
            'customer_phone' => $_POST['customer_phone'],
            'customer_address' => $_POST['customer_address'] ?? ($_POST['address'] ?? ''),
        ];
        $zip_name = generateXMLAndImageZip($xml_data, $cart_items);
    }

    // Redirigir a página de confirmación
    $_SESSION['checkout_success'] = [
        'invoice_number' => $invoice_number,
        'total' => $final_amount,
        'subtotal' => $subtotal,
        'tax_amount' => $tax_amount,
        'shipping' => $shipping,
        'customer_name' => $_POST['customer_name'],
        'customer_email' => $_POST['customer_email'],
        'customer_phone' => $_POST['customer_phone'],
        'payment_method' => $_POST['payment_method'],
        'sale_id' => $sale_id,
        'zip_name' => $zip_name
    ];

    redirect('confirmation.php');

} catch (Exception $e) {
    // Revertir transacción en caso de error
    $db->rollBack();
    
    $_SESSION['checkout_error'] = 'Error al procesar el pedido: ' . $e->getMessage();
    redirect('checkout.php');
}
?>
