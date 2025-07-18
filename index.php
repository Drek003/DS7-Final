<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Si no está logueado, redirigir al login
if (!isLoggedIn()) {
    redirect('views/auth/login.php');
}

// Obtener estadísticas para el dashboard
$database = new Database();
$db = $database->getConnection();

$stats = [];
$stats['categories'] = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$stats['products'] = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Solo mostrar estadísticas de usuarios si es admin
if (isAdmin()) {
    $stats['users'] = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
}

// Obtener productos recientes para mostrar en el dashboard
$recent_products_query = "SELECT p.*, c.name as category_name 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         ORDER BY p.created_at DESC 
                         LIMIT 6";
$recent_stmt = $db->prepare($recent_products_query);
$recent_stmt->execute();
$recent_products = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <link href="assets/css/catg.css" rel="stylesheet">
    <link href="assets/css/bootstrap-dark.css" rel="stylesheet">
    <style>
        /* Mejorar contraste en las cards de estadísticas */
        .card.bg-primary, .card.bg-success, .card.bg-info {
            background: linear-gradient(135deg, var(--accent-color, #00d4aa) 0%, var(--primary-color, #1a1a2e) 100%) !important;
            color: #fff !important;
            border: none;
            box-shadow: 0 4px 24px rgba(0,0,0,0.25);
        }
        .card.bg-primary .card-body, .card.bg-success .card-body, .card.bg-info .card-body {
            color: #fff !important;
        }
        .card.bg-primary .card-title, .card.bg-success .card-title, .card.bg-info .card-title,
        .card.bg-primary p, .card.bg-success p, .card.bg-info p {
            color: #fff !important;
            text-shadow: 0 1px 4px rgba(0,0,0,0.25);
        }
        .card.bg-primary .align-self-center i,
        .card.bg-success .align-self-center i,
        .card.bg-info .align-self-center i {
            color: #fff !important;
            text-shadow: 0 1px 4px rgba(0,0,0,0.25);
        }
        .card.bg-info {
            background: linear-gradient(135deg, var(--info, #4ecdc4) 0%, var(--primary-color, #1a1a2e) 100%) !important;
        }
        .card.bg-success {
            background: linear-gradient(135deg, var(--success, #00d4aa) 0%, var(--primary-color, #1a1a2e) 100%) !important;
        }
    </style>
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="fas fa-tachometer-alt"></i> 
                    <?php echo isAdmin() ? 'Panel de Administración' : 'Catálogo de Productos'; ?>
                </h1>
                
                <!-- Mensaje de bienvenida personalizado -->
                <div class="alert alert-info">
                    <i class="fas fa-user"></i> 
                    Bienvenido, <strong><?php echo $_SESSION['username']; ?></strong>
                    <?php if (isAdmin()): ?>
                    (Administrador) - Tienes acceso completo para gestionar el catálogo.
                    <?php else: ?>
                    (Consultor) - Puedes navegar y consultar todo el catálogo de productos.
                    <?php endif; ?>
                </div>
                
                <!-- Estadísticas -->
                <div class="row g-3 mb-4">
                    <div class="col-md-<?php echo isAdmin() ? '4' : '6'; ?>">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['categories']; ?></h4>
                                        <p class="mb-0">Categorías</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-tags fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-<?php echo isAdmin() ? '4' : '6'; ?>">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['products']; ?></h4>
                                        <p class="mb-0">Productos</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-box fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (isAdmin()): ?>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['users']; ?></h4>
                                        <p class="mb-0">Usuarios</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Accesos rápidos -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-tags"></i> Categorías</h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text">Explora todas las categorías de productos disponibles.</p>
                                <a href="views/categories/index.php" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> Ver Categorías
                                </a>
                                <?php if (isAdmin()): ?>
                                <a href="views/categories/create.php" class="btn btn-success ms-2">
                                    <i class="fas fa-plus"></i> Nueva Categoría
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-box"></i> Productos</h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text">Navega por todo el catálogo de productos.</p>
                                <a href="views/products/index.php" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> Ver Productos
                                </a>
                                <?php if (isAdmin()): ?>
                                <a href="views/products/create.php" class="btn btn-success ms-2">
                                    <i class="fas fa-plus"></i> Nuevo Producto
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Productos recientes -->
                <?php if (!empty($recent_products)): ?>
                <div class="row">
                    <div class="col-12">
                        <h3><i class="fas fa-clock"></i> Productos Recientes</h3>
                        <hr>
                        
                        <div class="row g-3">
                            <?php foreach ($recent_products as $product): ?>
                            <div class="col-md-6 col-lg-4 col-xl-2">
                                <div class="card h-100 product-card-link" style="cursor:pointer;" onclick="window.location.href='views/products/show.php?id=<?php echo $product['id']; ?>'">
                                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 120px;">
                                        <?php if ($product['image']): ?>
                                        <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             class="img-fluid" style="max-height: 100%; max-width: 100%;"
                                             onerror="this.onerror=null; this.src='assets/images/placeholder.png';">
                                        <?php else: ?>
                                        <i class="fas fa-image fa-2x text-muted"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body p-2">
                                        <h6 class="card-title small"><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <p class="card-text text-success small mb-1">$<?php echo number_format($product['price'], 2); ?></p>
                                        <?php if ($product['category_name']): ?>
                                        <span class="badge bg-secondary small"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>