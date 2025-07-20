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

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = cleanInput($_POST['name']);
    $email = cleanInput($_POST['email']);
    $phone = cleanInput($_POST['phone']);
    $address = cleanInput($_POST['address']);
    $city = cleanInput($_POST['city']);
    $country = cleanInput($_POST['country']);
    $tax_id = cleanInput($_POST['tax_id']);
    $customer_type = cleanInput($_POST['customer_type']);
    $notes = cleanInput($_POST['notes']);

    $errors = [];

    // Validaciones
    if (empty($name)) {
        $errors[] = "El nombre es obligatorio";
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El email no tiene un formato válido";
    }

    // Verificar si el email ya existe (excluyendo el cliente actual)
    if (!empty($email)) {
        $check_email = $db->prepare("SELECT id FROM customers WHERE email = ? AND id != ?");
        $check_email->execute([$email, $customer_id]);
        if ($check_email->fetch()) {
            $errors[] = "Ya existe otro cliente con ese email";
        }
    }

    // Verificar si el RUC/Cédula ya existe (excluyendo el cliente actual)
    if (!empty($tax_id)) {
        $check_tax = $db->prepare("SELECT id FROM customers WHERE tax_id = ? AND id != ?");
        $check_tax->execute([$tax_id, $customer_id]);
        if ($check_tax->fetch()) {
            $errors[] = "Ya existe otro cliente con ese RUC/Cédula";
        }
    }

    if (empty($errors)) {
        try {
            $query = "UPDATE customers SET 
                     name = ?, email = ?, phone = ?, address = ?, city = ?, 
                     country = ?, tax_id = ?, customer_type = ?, notes = ?, 
                     updated_at = CURRENT_TIMESTAMP 
                     WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                $name,
                $email ?: null,
                $phone ?: null,
                $address ?: null,
                $city ?: null,
                $country,
                $tax_id ?: null,
                $customer_type,
                $notes ?: null,
                $customer_id
            ]);

            if ($result) {
                $_SESSION['success'] = "Cliente actualizado exitosamente";
                redirect('show.php?id=' . $customer_id);
            } else {
                $errors[] = "Error al actualizar el cliente";
            }
        } catch (PDOException $e) {
            $errors[] = "Error de base de datos: " . $e->getMessage();
        }
    }
} else {
    // Llenar formulario con datos existentes
    $_POST = $customer;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente - <?php echo htmlspecialchars($customer['name']); ?></title>
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
                    <h1><i class="fas fa-user-edit"></i> Editar Cliente</h1>
                    <div class="btn-group">
                        <a href="show.php?id=<?php echo $customer['id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-list"></i> Lista de Clientes
                        </a>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle"></i> Se encontraron los siguientes errores:</h6>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user-circle"></i> Información del Cliente
                                </h5>
                                <small class="text-muted">
                                    Cliente ID: #<?php echo str_pad($customer['id'], 6, '0', STR_PAD_LEFT); ?>
                                </small>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="customerForm">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label for="name" class="form-label">
                                                    Nombre completo <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control" id="name" name="name" 
                                                       value="<?php echo htmlspecialchars($_POST['name']); ?>"
                                                       required maxlength="100">
                                                <div class="form-text">Nombre completo de la persona o empresa</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="customer_type" class="form-label">
                                                    Tipo de cliente <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select" id="customer_type" name="customer_type" required>
                                                    <option value="">Seleccionar tipo</option>
                                                    <option value="individual" <?php echo ($_POST['customer_type'] === 'individual') ? 'selected' : ''; ?>>
                                                        Persona Individual
                                                    </option>
                                                    <option value="empresa" <?php echo ($_POST['customer_type'] === 'empresa') ? 'selected' : ''; ?>>
                                                        Empresa
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">
                                                    <i class="fas fa-envelope"></i> Correo electrónico
                                                </label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                                       maxlength="100">
                                                <div class="form-text">Email para comunicación y facturación</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="phone" class="form-label">
                                                    <i class="fas fa-phone"></i> Teléfono
                                                </label>
                                                <input type="tel" class="form-control" id="phone" name="phone" 
                                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                                       placeholder="+507 6123-4567" maxlength="20">
                                                <div class="form-text">Número de contacto principal</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="address" class="form-label">
                                            <i class="fas fa-map-marker-alt"></i> Dirección
                                        </label>
                                        <textarea class="form-control" id="address" name="address" rows="3"
                                                  placeholder="Dirección completa, edificio, piso, apartamento..."><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                                        <div class="form-text">Dirección para entregas o correspondencia</div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="city" class="form-label">
                                                    <i class="fas fa-city"></i> Ciudad
                                                </label>
                                                <input type="text" class="form-control" id="city" name="city" 
                                                       value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>"
                                                       placeholder="Ciudad de Panamá" maxlength="50">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="country" class="form-label">
                                                    <i class="fas fa-flag"></i> País
                                                </label>
                                                <input type="text" class="form-control" id="country" name="country" 
                                                       value="<?php echo htmlspecialchars($_POST['country'] ?? 'Panamá'); ?>"
                                                       maxlength="50">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="tax_id" class="form-label">
                                            <i class="fas fa-id-card"></i> RUC / Cédula
                                        </label>
                                        <input type="text" class="form-control" id="tax_id" name="tax_id" 
                                               value="<?php echo htmlspecialchars($_POST['tax_id'] ?? ''); ?>"
                                               placeholder="8-123-456789 o 12345678-1-123456" maxlength="50">
                                        <div class="form-text">
                                            <span id="tax_id_help_individual" style="display: none;">
                                                Cédula de identidad personal (ej: 8-123-456789)
                                            </span>
                                            <span id="tax_id_help_empresa" style="display: none;">
                                                RUC de la empresa (ej: 12345678-1-123456)
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="notes" class="form-label">
                                            <i class="fas fa-sticky-note"></i> Notas adicionales
                                        </label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"
                                                  placeholder="Información adicional, preferencias, descuentos especiales..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                                        <div class="form-text">Información relevante sobre el cliente</div>
                                    </div>

                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="show.php?id=<?php echo $customer['id']; ?>" class="btn btn-secondary me-md-2">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-save"></i> Actualizar Cliente
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cambiar texto de ayuda según tipo de cliente
        document.getElementById('customer_type').addEventListener('change', function() {
            const type = this.value;
            const helpIndividual = document.getElementById('tax_id_help_individual');
            const helpEmpresa = document.getElementById('tax_id_help_empresa');
            
            if (type === 'individual') {
                helpIndividual.style.display = 'block';
                helpEmpresa.style.display = 'none';
            } else if (type === 'empresa') {
                helpIndividual.style.display = 'none';
                helpEmpresa.style.display = 'block';
            } else {
                helpIndividual.style.display = 'none';
                helpEmpresa.style.display = 'none';
            }
        });

        // Trigger initial change event
        document.getElementById('customer_type').dispatchEvent(new Event('change'));

        // Validación del formulario
        document.getElementById('customerForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const customerType = document.getElementById('customer_type').value;
            
            if (!name) {
                e.preventDefault();
                alert('El nombre es obligatorio');
                document.getElementById('name').focus();
                return;
            }
            
            if (!customerType) {
                e.preventDefault();
                alert('Debe seleccionar el tipo de cliente');
                document.getElementById('customer_type').focus();
                return;
            }
        });
    </script>
</body>
</html>
