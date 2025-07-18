<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$database = new Database();
$db = $database->getConnection();

// Obtener todas las categorías con conteo de productos
$query = "SELECT c.*, COUNT(p.id) as product_count 
          FROM categories c 
          LEFT JOIN products p ON c.id = p.category_id 
          GROUP BY c.id 
          ORDER BY c.name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorías - Catálogo de Productos</title>
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
                    <h1><i class="fas fa-tags"></i> Categorías</h1>
                    <?php if (isAdmin()): ?>
                    <a href="create.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Nueva Categoría
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

                <div class="row g-3">
                    <?php foreach ($categories as $category): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 category-card-link" style="cursor:pointer;" onclick="window.location.href='../products/index.php?category=<?php echo $category['id']; ?>'">
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <?php if ($category['image']): ?>
                                <img src="<?php echo htmlspecialchars($category['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($category['name']); ?>"
                                     class="img-fluid" style="max-height: 100%; max-width: 100%;"
                                     onerror="this.onerror=null; this.src='../../assets/images/placeholder.png';">
                                <?php else: ?>
                                <i class="fas fa-image fa-3x text-muted"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($category['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i> 
                                        <?php echo date('d/m/Y', strtotime($category['created_at'])); ?>
                                    </small>
                                    <span class="badge bg-primary">
                                        <?php echo $category['product_count']; ?> productos
                                    </span>
                                </div>
                            </div>
                            <div class="card-footer">
                                <?php if (isAdmin()): ?>
                                <div class="btn-group w-100" role="group">
                                    <a href="edit.php?id=<?php echo $category['id']; ?>" 
                                       class="btn btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-danger" 
                                            onclick="event.stopPropagation();deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($categories)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                    <h3>No hay categorías</h3>
                    <?php if (isAdmin()): ?>
                    <p class="text-muted">Comienza creando tu primera categoría</p>
                    <a href="create.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Crear Categoría
                    </a>
                    <?php else: ?>
                    <p class="text-muted">Aún no hay categorías disponibles para consultar</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if (isAdmin()): ?>
        function deleteCategory(id, name) {
            if (confirm('¿Estás seguro de que quieres eliminar la categoría "' + name + '"?\n\nNota: Los productos asociados a esta categoría quedarán sin categoría.')) {
                window.location.href = 'delete.php?id=' + id;
            }
        }
        <?php endif; ?>
    </script>
</body>
</html>