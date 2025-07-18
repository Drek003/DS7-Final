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

$category_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();

// Obtener datos de la categoría
$query = "SELECT * FROM categories WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

// Si la categoría no existe, redirigir
if (!$category) {
    redirect('index.php');
}

$error = '';
$success = '';
$image_url = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar y limpiar datos
    $name = cleanInput($_POST['name']);
    $description = cleanInput($_POST['description']);
    $image_url = isset($_POST['image_url']) ? cleanInput($_POST['image_url']) : '';
    
    // Validar nombre (obligatorio)
    if (empty($name)) {
        $error = 'El nombre de la categoría es obligatorio';
    } else if (empty($image_url)) {
        $error = 'La URL de la imagen es obligatoria';
    } else {
        // Guardar solo la URL de la imagen
        $query = "UPDATE categories SET name = ?, description = ?, image = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        if ($stmt->execute([$name, $description, $image_url, $category_id])) {
            $success = 'Categoría actualizada correctamente';
            
            // Actualizar datos de la categoría para mostrar los cambios
            $category['name'] = $name;
            $category['description'] = $description;
            $category['image'] = $image_url;
            $image_url = '';
        } else {
            $error = 'Error al actualizar la categoría';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Categoría - Catálogo de Productos</title>
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
                        <h4 class="mb-0"><i class="fas fa-edit"></i> Editar Categoría</h4>
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
                                    <i class="fas fa-list"></i> Ver todas las categorías
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <form id="categoryForm" method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre de la Categoría *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($category['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Descripción</label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="3"><?php echo htmlspecialchars($category['description']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="image_url" class="form-label">URL de la Imagen *</label>
                                <input type="url" class="form-control" id="image_url" name="image_url" placeholder="https://..." value="<?php echo htmlspecialchars($category['image']); ?>" required>
                                <div class="form-text">Ingresa la URL de la imagen de la categoría.</div>
                            </div>
                            
                            <?php if ($category['image']): ?>
                            <div class="mb-4 text-center">
                                <p>Imagen actual:</p>
                                <img src="<?php echo htmlspecialchars($category['image']); ?>" 
                                     class="img-fluid img-thumbnail" style="max-height: 200px;" 
                                     alt="<?php echo htmlspecialchars($category['name']); ?>">
                            </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save"></i> Actualizar Categoría
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
        // Validación del formulario
        document.getElementById('categoryForm').addEventListener('submit', function(e) {
            const nameInput = document.getElementById('name');
            if (!nameInput.value.trim()) {
                e.preventDefault();
                nameInput.classList.add('is-invalid');
                alert('El nombre de la categoría es obligatorio');
            }
        });
    </script>
</body>
</html>