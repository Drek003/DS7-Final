<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar si el usuario está logueado y es administrador
if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('index.php');
}

$product_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();

// Obtener datos del producto
$query = "SELECT * FROM products WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// Si el producto no existe, redirigir
if (!$product) {
    redirect('index.php');
}

// Obtener categorías para el select
$categories_query = "SELECT * FROM categories ORDER BY name ASC";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar y limpiar datos
    $name = cleanInput($_POST['name']);
    $description = cleanInput($_POST['description']);
    $price = cleanInput($_POST['price']);
    $image_url = cleanInput($_POST['image_url']);
    $category_id = isset($_POST['category_id']) && !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    
    // Validaciones
    if (empty($name)) {
        $error = 'El nombre del producto es obligatorio';
    } elseif (empty($price) || !is_numeric($price) || $price <= 0) {
        $error = 'El precio debe ser un número mayor a 0';
    } else {
        // Validar URL de imagen si se proporciona
        if (!empty($image_url) && !filter_var($image_url, FILTER_VALIDATE_URL)) {
            $error = 'La URL de la imagen no es válida';
        } else {
            // Si no hay errores, actualizar en la base de datos
            $query = "UPDATE products SET name = ?, description = ?, price = ?, image = ?, category_id = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$name, $description, $price, $image_url, $category_id, $product_id])) {
                $success = 'Producto actualizado correctamente';
                
                // Actualizar datos del producto para mostrar los cambios
                $product['name'] = $name;
                $product['description'] = $description;
                $product['price'] = $price;
                $product['image'] = $image_url;
                $product['category_id'] = $category_id;
            } else {
                $error = 'Error al actualizar el producto';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - Catálogo de Productos</title>
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
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h4 class="mb-0"><i class="fas fa-edit"></i> Editar Producto</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            <div class="mt-2">
                                <a href="index.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-list"></i> Ver todos los productos
                                </a>
                                <a href="show.php?id=<?php echo $product_id; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> Ver producto
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <form id="productForm" method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre del Producto *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($product['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Descripción</label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Precio *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="price" name="price" 
                                                   value="<?php echo $product['price']; ?>" 
                                                   step="0.01" min="0" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Categoría</label>
                                        <select class="form-select" id="category_id" name="category_id">
                                            <option value="">Sin categoría</option>
                                            <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                    <?php echo $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="image_url" class="form-label">URL de la Imagen</label>
                                
                                <?php if ($product['image']): ?>
                                <div class="mb-2 text-center">
                                    <p>Imagen actual:</p>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                         class="img-fluid img-thumbnail" style="max-height: 200px;" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         onerror="this.onerror=null; this.src='../../assets/images/placeholder.png'; this.alt='Imagen no disponible';">
                                </div>
                                <?php endif; ?>
                                
                                <input type="url" class="form-control" id="image_url" name="image_url" 
                                       value="<?php echo htmlspecialchars($product['image']); ?>" 
                                       placeholder="https://ejemplo.com/imagen.jpg">
                                <div class="form-text">Ingrese la URL completa de la imagen (https://...)</div>
                                
                                <div class="mt-2 text-center">
                                    <img id="imagePreview" class="img-fluid img-thumbnail" 
                                         style="max-height: 200px; display: none;" alt="Vista previa">
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save"></i> Actualizar Producto
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/app.js"></script>
    <script>
        // Vista previa de la imagen desde URL
        const imageUrlInput = document.getElementById('image_url');
        const preview = document.getElementById('imagePreview');
        
        // Mostrar vista previa cuando cambia la URL
        imageUrlInput.addEventListener('input', function() {
            const url = this.value.trim();
            
            if (url) {
                preview.src = url;
                preview.style.display = 'block';
                
                // Manejar error de carga de imagen
                preview.onerror = function() {
                    preview.style.display = 'none';
                    alert('No se pudo cargar la imagen. Verifique que la URL sea correcta.');
                };
            } else {
                preview.style.display = 'none';
            }
        });
        
        // Validación del formulario
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const nameInput = document.getElementById('name');
            const priceInput = document.getElementById('price');
            
            let isValid = true;
            
            if (!nameInput.value.trim()) {
                nameInput.classList.add('is-invalid');
                isValid = false;
            } else {
                nameInput.classList.remove('is-invalid');
            }
            
            if (!priceInput.value || parseFloat(priceInput.value) <= 0) {
                priceInput.classList.add('is-invalid');
                isValid = false;
            } else {
                priceInput.classList.remove('is-invalid');
            }
            
            // Validar URL solo si se ha ingresado algo
            if (imageUrlInput.value.trim() && !isValidUrl(imageUrlInput.value.trim())) {
                imageUrlInput.classList.add('is-invalid');
                isValid = false;
            } else {
                imageUrlInput.classList.remove('is-invalid');
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Por favor complete todos los campos obligatorios correctamente');
            }
        });
        
        // Función para validar URL
        function isValidUrl(url) {
            try {
                new URL(url);
                return true;
            } catch (e) {
                return false;
            }
        }
    </script>
</body>
</html>