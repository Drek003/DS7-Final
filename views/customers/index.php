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

// Filtros de búsqueda
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? cleanInput($_GET['type']) : '';

// Construir consulta con filtros
$query = "SELECT * FROM customers WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE :search OR email LIKE :search OR phone LIKE :search OR city LIKE :search)";
    $params['search'] = "%$search%";
}

if (!empty($type_filter)) {
    $query .= " AND customer_type = :type";
    $params['type'] = $type_filter;
}

$query .= " ORDER BY name ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas
$stats_query = "SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN customer_type = 'individual' THEN 1 END) as individuals,
    COUNT(CASE WHEN customer_type = 'empresa' THEN 1 END) as companies
    FROM customers";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes - Catálogo de Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
    <link href="../../assets/css/catg.css" rel="stylesheet">
    <link href="../../assets/css/bootstrap-dark.css" rel="stylesheet">
    <style>
        body {overflow-y: auto !important;}
        .customer-card:hover {
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }
        .customer-type-badge {
            font-size: 0.75em;
        }
    </style>
</head>
<body>
    <?php include '../../includes/nav.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-users"></i> Gestión de Clientes</h1>
                    <div class="text-muted">
                        <small><i class="fas fa-info-circle"></i> Los clientes se registran desde el formulario de login</small>
                    </div>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['error']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="text-white"><?php echo $stats['total']; ?></h4>
                                        <p class="mb-0 text-white">Total Clientes</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="text-white"><?php echo $stats['individuals']; ?></h4>
                                        <p class="mb-0 text-white">Personas</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-user fa-2x text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="text-white"><?php echo $stats['companies']; ?></h4>
                                        <p class="mb-0 text-white">Empresas</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-building fa-2x text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros de búsqueda -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-6">
                                <label for="search" class="form-label">Buscar cliente</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Nombre, email, teléfono o ciudad...">
                            </div>
                            <div class="col-md-4">
                                <label for="type" class="form-label">Tipo de cliente</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="">Todos los tipos</option>
                                    <option value="individual" <?php echo $type_filter === 'individual' ? 'selected' : ''; ?>>
                                        Persona Individual
                                    </option>
                                    <option value="empresa" <?php echo $type_filter === 'empresa' ? 'selected' : ''; ?>>
                                        Empresa
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Buscar
                                    </button>
                                </div>
                            </div>
                        </form>
                        <?php if (!empty($search) || !empty($type_filter)): ?>
                        <div class="mt-2">
                            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times"></i> Limpiar filtros
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Lista de clientes -->
                <?php if (empty($customers)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h3 class="text-muted">No se encontraron clientes</h3>
                    <p class="text-muted">
                        <?php if (!empty($search) || !empty($type_filter)): ?>
                            No hay clientes que coincidan con los filtros aplicados.
                        <?php else: ?>
                            Aún no hay clientes registrados en el sistema.<br>
                            <small>Los clientes pueden registrarse desde la página de login.</small>
                        <?php endif; ?>
                    </p>
                </div>
                <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($customers as $customer): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 customer-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-<?php echo $customer['customer_type'] === 'empresa' ? 'building' : 'user'; ?> me-2"></i>
                                    <?php echo htmlspecialchars($customer['name']); ?>
                                </h6>
                                <span class="badge bg-<?php echo $customer['customer_type'] === 'empresa' ? 'info' : 'success'; ?> customer-type-badge">
                                    <?php echo $customer['customer_type'] === 'empresa' ? 'Empresa' : 'Individual'; ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-envelope me-1"></i>
                                        <?php echo htmlspecialchars($customer['email'] ?: 'No especificado'); ?>
                                    </small>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-phone me-1"></i>
                                        <?php echo htmlspecialchars($customer['phone'] ?: 'No especificado'); ?>
                                    </small>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($customer['city'] ?: 'No especificado'); ?>
                                    </small>
                                </div>
                                <?php if ($customer['tax_id']): ?>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-id-card me-1"></i>
                                        <?php echo htmlspecialchars($customer['tax_id']); ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                                <?php if ($customer['notes']): ?>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-sticky-note me-1"></i>
                                        <?php echo htmlspecialchars(substr($customer['notes'], 0, 50)) . (strlen($customer['notes']) > 50 ? '...' : ''); ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i> 
                                        <?php echo date('d/m/Y', strtotime($customer['created_at'])); ?>
                                    </small>
                                    <div class="btn-group btn-group-sm">
                                        <a href="show.php?id=<?php echo $customer['id']; ?>" 
                                           class="btn btn-outline-info" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $customer['id']; ?>" 
                                           class="btn btn-outline-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" 
                                                title="Eliminar" onclick="confirmDelete(<?php echo $customer['id']; ?>, '<?php echo addslashes($customer['name']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
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
