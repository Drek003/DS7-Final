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

// Obtener datos del cliente
$query = "SELECT * FROM customers WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    $_SESSION['error'] = "Cliente no encontrado";
    redirect('index.php');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($customer['name']); ?> - Detalles del Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
    <link href="../../assets/css/catg.css" rel="stylesheet">
    <link href="../../assets/css/bootstrap-dark.css" rel="stylesheet">
    <link href="../../assets/css/customers.css" rel="stylesheet">
</head>
<body>
    <?php include '../../includes/nav.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-user-circle"></i> Detalles del Cliente</h1>
                    <div class="btn-group">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                        <a href="edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <button type="button" class="btn btn-danger" 
                                onclick="confirmDelete(<?php echo $customer['id']; ?>, '<?php echo addslashes($customer['name']); ?>')">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header customer-header">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-<?php echo $customer['customer_type'] === 'empresa' ? 'building' : 'user'; ?> fa-2x me-3"></i>
                                    <div>
                                        <h4 class="mb-1"><?php echo htmlspecialchars($customer['name']); ?></h4>
                                        <span class="badge bg-light text-dark">
                                            <?php echo $customer['customer_type'] === 'empresa' ? 'Empresa' : 'Persona Individual'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <div class="info-label">
                                                <i class="fas fa-envelope me-2"></i>Correo Electrónico
                                            </div>
                                            <div class="info-value">
                                                <?php if ($customer['email']): ?>
                                                    <a href="mailto:<?php echo htmlspecialchars($customer['email']); ?>" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($customer['email']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">No especificado</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="info-item">
                                            <div class="info-label">
                                                <i class="fas fa-phone me-2"></i>Teléfono
                                            </div>
                                            <div class="info-value">
                                                <?php if ($customer['phone']): ?>
                                                    <a href="tel:<?php echo htmlspecialchars($customer['phone']); ?>" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($customer['phone']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">No especificado</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="info-item">
                                            <div class="info-label">
                                                <i class="fas fa-id-card me-2"></i>RUC / Cédula
                                            </div>
                                            <div class="info-value">
                                                <?php if ($customer['tax_id']): ?>
                                                    <code><?php echo htmlspecialchars($customer['tax_id']); ?></code>
                                                <?php else: ?>
                                                    <span class="text-muted">No especificado</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <div class="info-label">
                                                <i class="fas fa-map-marker-alt me-2"></i>Dirección
                                            </div>
                                            <div class="info-value">
                                                <?php if ($customer['address']): ?>
                                                    <?php echo nl2br(htmlspecialchars($customer['address'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No especificada</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="info-item">
                                            <div class="info-label">
                                                <i class="fas fa-city me-2"></i>Ciudad
                                            </div>
                                            <div class="info-value">
                                                <?php echo htmlspecialchars($customer['city'] ?: 'No especificada'); ?>
                                            </div>
                                        </div>

                                        <div class="info-item">
                                            <div class="info-label">
                                                <i class="fas fa-flag me-2"></i>País
                                            </div>
                                            <div class="info-value">
                                                <?php echo htmlspecialchars($customer['country'] ?: 'No especificado'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($customer['notes']): ?>
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-sticky-note me-2"></i>Notas
                                    </div>
                                    <div class="info-value">
                                        <div class="alert alert-info">
                                            <?php echo nl2br(htmlspecialchars($customer['notes'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-info-circle"></i> Información del Sistema
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-calendar-plus me-2"></i>Fecha de Registro
                                    </div>
                                    <div class="info-value">
                                        <?php echo date('d/m/Y \a \l\a\s H:i', strtotime($customer['created_at'])); ?>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-calendar-edit me-2"></i>Última Actualización
                                    </div>
                                    <div class="info-value">
                                        <?php echo date('d/m/Y \a \l\a\s H:i', strtotime($customer['updated_at'])); ?>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-hashtag me-2"></i>ID del Cliente
                                    </div>
                                    <div class="info-value">
                                        <code>#<?php echo str_pad($customer['id'], 6, '0', STR_PAD_LEFT); ?></code>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-tools"></i> Acciones Rápidas
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-warning">
                                        <i class="fas fa-edit"></i> Editar Información
                                    </a>
                                    
                                    <?php if ($customer['email']): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($customer['email']); ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-envelope"></i> Enviar Email
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($customer['phone']): ?>
                                    <a href="tel:<?php echo htmlspecialchars($customer['phone']); ?>" class="btn btn-outline-success">
                                        <i class="fas fa-phone"></i> Llamar
                                    </a>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="confirmDelete(<?php echo $customer['id']; ?>, '<?php echo addslashes($customer['name']); ?>')">
                                        <i class="fas fa-trash"></i> Eliminar Cliente
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-danger"></i> Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea eliminar al cliente <strong id="customerName"></strong>?</p>
                    <p class="text-danger">
                        <small><i class="fas fa-warning"></i> Esta acción no se puede deshacer.</small>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Eliminar cliente
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(customerId, customerName) {
            document.getElementById('customerName').textContent = customerName;
            document.getElementById('confirmDeleteBtn').href = 'delete.php?id=' + customerId;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html>
