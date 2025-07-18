<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$database = new Database();
$db = $database->getConnection();

// Filtros
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;

// Construir consulta con filtros
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "p.name LIKE ?";
    $params[] = "%$search%";
}

if ($category_filter > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if ($min_price > 0) {
    $where_conditions[] = "p.price >= ?";
    $params[] = $min_price;
}

if ($max_price > 0) {
    $where_conditions[] = "p.price <= ?";
    $params[] = $max_price;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          $where_clause 
          ORDER BY p.name ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías para el filtro
$categories_query = "SELECT * FROM categories ORDER BY name ASC";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener nombre de categoría si se está filtrando
$category_name = '';
if ($category_filter > 0) {
    $cat_query = "SELECT name FROM categories WHERE id = ?";
    $cat_stmt = $db->prepare($cat_query);
    $cat_stmt->execute([$category_filter]);
    $category_name = $cat_stmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - Catálogo de Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
    <link href="../../assets/css/catg.css" rel="stylesheet">
    <link href="../../assets/css/bootstrap-dark.css" rel="stylesheet">
    <style>body {overflow-y: auto !important;}</style>
</head>
<body>
    <?php include '../../includes/nav.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1><i class="fas fa-box"></i> Productos</h1>
                        <?php if ($category_name): ?>
                        <p class="text-muted mb-0">
                            <i class="fas fa-filter"></i> Filtrando por categoría: 
                            <strong><?php echo htmlspecialchars($category_name); ?></strong>
                            <a href="index.php" class="btn btn-sm btn-outline-secondary ms-2">
                                <i class="fas fa-times"></i> Quitar filtro
                            </a>
                        </p>
                        <?php endif; ?>
                    </div>
                    <?php if (isAdmin()): ?>
                    <a href="create.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Nuevo Producto
                    </a>
                    <?php else: ?>
                    <span class="text-muted">
                        <i class="fas fa-info-circle"></i> Modo consulta - Solo visualización
                    </span>
                    <?php endif; ?>
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

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-filter"></i> Filtros de Búsqueda</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Buscar por nombre</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Nombre del producto...">
                            </div>
                            <div class="col-md-3">
                                <label for="category" class="form-label">Categoría</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">Todas las categorías</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="min_price" class="form-label">Precio mín.</label>
                                <input type="number" class="form-control" id="min_price" name="min_price" 
                                       value="<?php echo $min_price > 0 ? $min_price : ''; ?>" 
                                       step="0.01" min="0">
                            </div>
                            <div class="col-md-2">
                                <label for="max_price" class="form-label">Precio máx.</label>
                                <input type="number" class="form-control" id="max_price" name="max_price" 
                                       value="<?php echo $max_price > 0 ? $max_price : ''; ?>" 
                                       step="0.01" min="0">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Información de resultados -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">
                        <i class="fas fa-info-circle"></i> 
                        Mostrando <?php echo count($products); ?> producto(s)
                    </span>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times"></i> Limpiar filtros
                    </a>
                </div>

                <!-- Productos -->
                <div class="row g-3">
                    <?php foreach ($products as $product): ?>
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="card h-100 product-card-link" style="cursor:pointer;" onclick="window.location.href='show.php?id=<?php echo $product['id']; ?>'">
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <?php if ($product['image']): ?>
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     class="img-fluid" style="max-height: 100%; max-width: 100%;"
                                     onerror="this.onerror=null; this.src='../../assets/images/placeholder.png';">
                                <?php else: ?>
                                <i class="fas fa-image fa-3x text-muted"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                                <p class="card-text small"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 text-success mb-0">$<?php echo number_format($product['price'], 2); ?></span>
                                    <?php if ($product['category_name']): ?>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer">
                                <?php if (isAdmin()): ?>
                                <div class="btn-group w-100" role="group">
                                    <a href="edit.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-danger btn-sm" 
                                            onclick="event.stopPropagation();deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($products)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-box fa-3x text-muted mb-3"></i>
                    <h3>No se encontraron productos</h3>
                    <?php if (!empty($search) || $category_filter > 0 || $min_price > 0 || $max_price > 0): ?>
                    <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="fas fa-times"></i> Limpiar filtros
                    </a>
                    <?php elseif (isAdmin()): ?>
                    <p class="text-muted">Comienza agregando productos al catálogo</p>
                    <a href="create.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Crear Producto
                    </a>
                    <?php else: ?>
                    <p class="text-muted">Aún no hay productos disponibles para consultar</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if (isAdmin()): ?>
        function deleteProduct(id, name) {
            if (confirm('¿Estás seguro de que quieres eliminar el producto "' + name + '"?')) {
                window.location.href = 'delete.php?id=' + id;
            }
        }
        <?php endif; ?>
    </script>
</body>
</html>