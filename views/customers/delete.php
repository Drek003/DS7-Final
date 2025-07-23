<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

if (!isAdmin()) {
    redirect('../../index.php');
}

$database = new Database();
$db = $database->getConnection();

// Obtener ID del cliente
$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$customer_id) {
    $_SESSION['error'] = "ID de cliente no válido";
    redirect('index.php');
}

// Verificar que el cliente existe
$check_query = "SELECT name FROM customers WHERE id = ?";
$check_stmt = $db->prepare($check_query);
$check_stmt->execute([$customer_id]);
$customer = $check_stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    $_SESSION['error'] = "Cliente no encontrado";
    redirect('index.php');
}

try {
    // Comenzar transacción
    $db->beginTransaction();

    // TODO: Aquí podrían agregarse verificaciones adicionales
    // Por ejemplo, verificar si el cliente tiene pedidos/facturas asociadas
    // $orders_check = $db->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = ?");
    // $orders_check->execute([$customer_id]);
    // if ($orders_check->fetchColumn() > 0) {
    //     throw new Exception("No se puede eliminar el cliente porque tiene pedidos asociados");
    // }

    // Eliminar el cliente
    $delete_query = "DELETE FROM customers WHERE id = ?";
    $delete_stmt = $db->prepare($delete_query);
    $result = $delete_stmt->execute([$customer_id]);

    if ($result && $delete_stmt->rowCount() > 0) {
        // Confirmar transacción
        $db->commit();
        $_SESSION['success'] = "Cliente '{$customer['name']}' eliminado exitosamente";
    } else {
        $db->rollback();
        $_SESSION['error'] = "No se pudo eliminar el cliente";
    }

} catch (PDOException $e) {
    // Revertir transacción en caso de error
    $db->rollback();
    
    // Verificar si es un error de constraint (referencias foráneas)
    if ($e->getCode() == '23000') {
        $_SESSION['error'] = "No se puede eliminar el cliente porque tiene información relacionada en el sistema (pedidos, facturas, etc.)";
    } else {
        $_SESSION['error'] = "Error al eliminar el cliente: " . $e->getMessage();
    }
} catch (Exception $e) {
    $db->rollback();
    $_SESSION['error'] = $e->getMessage();
}

// Redirigir de vuelta a la lista de clientes
redirect('index.php');
?>
