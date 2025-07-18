<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('index.php');
}

$product_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();

// Obtener datos del producto con información de categoría
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// Si el producto no existe, redirigir
if (!$product) {
    redirect('index.php');
}

// Obtener productos relacionados de la misma categoría
$related_products = [];
if ($product['category_id']) {
    $related_query = "SELECT * FROM products WHERE category_id = ? AND id != ? LIMIT 4";
    $related_stmt = $db->prepare($related_query);
    $related_stmt->execute([$product['category_id'], $product_id]);
    $related_products = $related_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Catálogo de Productos</title>
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
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../../index.php">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Productos</a></li>
                        <?php if ($product['category_name']): ?>
                        <li class="breadcrumb-item">
                            <a href="index.php?category=<?php echo $product['category_id']; ?>">
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <!-- Imagen del producto -->
                <div class="card">
                    <div class="card-body text-center">
                        <?php if ($product['image']): ?>
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="img-fluid rounded" style="max-height: 400px;"
                             onerror="this.onerror=null; this.src='../../assets/images/placeholder.png';">
                        <?php else: ?>
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 400px;">
                            <i class="fas fa-image fa-5x text-muted"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <!-- Información del producto -->
                <div class="card">
                    <div class="card-body">
                        <h1 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                        
                        <?php if ($product['category_name']): ?>
                        <div class="mb-3">
                            <a href="index.php?category=<?php echo $product['category_id']; ?>" 
                               class="badge bg-primary fs-6 text-decoration-none">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['category_name']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <h2 class="text-success">$<?php echo number_format($product['price'], 2); ?></h2>
                        </div>
                        
                        <?php if ($product['description']): ?>
                        <div class="mb-4">
                            <h5>Descripción</h5>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i> 
                                Agregado el <?php echo date('d/m/Y', strtotime($product['created_at'])); ?>
                            </small>
                        </div>
                        
                        <!-- Botones de acción -->
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver al catálogo
                            </a>
                            
                            <?php if ($product['category_name']): ?>
                            <a href="index.php?category=<?php echo $product['category_id']; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-tags"></i> Ver más de esta categoría
                            </a>
                            <?php endif; ?>
                            
                            <?php if (isAdmin()): ?>
                            <a href="edit.php?id=<?php echo $product['id']; ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <button class="btn btn-danger" 
                                    onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Productos relacionados -->
        <?php if (!empty($related_products)): ?>
        <div class="row mt-5">
            <div class="col-12">
                <h3><i class="fas fa-boxes"></i> Productos Relacionados</h3>
                <hr>
                
                <div class="row g-3">
                    <?php foreach ($related_products as $related): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100">
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 150px;">
                                <?php if ($related['image']): ?>
                                <img src="<?php echo htmlspecialchars($related['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($related['name']); ?>"
                                     class="img-fluid" style="max-height: 100%; max-width: 100%;"
                                     onerror="this.onerror=null; this.src='../../assets/images/placeholder.png';">
                                <?php else: ?>
                                <i class="fas fa-image fa-2x text-muted"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title"><?php echo htmlspecialchars($related['name']); ?></h6>
                                <p class="card-text text-success">$<?php echo number_format($related['price'], 2); ?></p>
                            </div>
                            <div class="card-footer">
                                <a href="show.php?id=<?php echo $related['id']; ?>" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-eye"></i> Ver detalles
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
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