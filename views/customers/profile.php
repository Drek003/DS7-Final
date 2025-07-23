<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

if (!isClient()) {
    redirect('../../index.php');
}

$database = new Database();
$db = $database->getConnection();

$customer_id = getCustomerId();
$success = '';
$errors = [];

// Obtener datos del cliente actual
$query = "SELECT * FROM customers WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    redirect('../../index.php');
}

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = cleanInput($_POST['name']);
    $email = cleanInput($_POST['email']);
    $phone = cleanInput($_POST['phone']);
    $address = cleanInput($_POST['address']);
    $city = cleanInput($_POST['city']);
    $country = cleanInput($_POST['country']);
    $tax_id = cleanInput($_POST['tax_id']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validaciones
    if (empty($name)) {
        $errors[] = "El nombre es obligatorio";
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El email no tiene un formato válido";
    }

    // Verificar si el email ya existe en otro cliente
    if (!empty($email) && $email !== $customer['email']) {
        $check_email = $db->prepare("SELECT id FROM customers WHERE email = ? AND id != ?");
        $check_email->execute([$email, $customer_id]);
        if ($check_email->fetch()) {
            $errors[] = "Ya existe otro cliente con ese email";
        }
    }

    // Verificar si el RUC/Cédula ya existe en otro cliente
    if (!empty($tax_id) && $tax_id !== $customer['tax_id']) {
        $check_tax = $db->prepare("SELECT id FROM customers WHERE tax_id = ? AND id != ?");
        $check_tax->execute([$tax_id, $customer_id]);
        if ($check_tax->fetch()) {
            $errors[] = "Ya existe otro cliente con ese RUC/Cédula";
        }
    }

    // Validar cambio de contraseña si se intenta cambiar
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            $errors[] = "Debes ingresar tu contraseña actual";
        } elseif ($current_password !== $customer['password']) {
            $errors[] = "La contraseña actual es incorrecta";
        }

        if (empty($new_password) || strlen($new_password) < 6) {
            $errors[] = "La nueva contraseña debe tener al menos 6 caracteres";
        }

        if ($new_password !== $confirm_password) {
            $errors[] = "Las contraseñas nuevas no coinciden";
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Actualizar tabla customers
            $update_fields = [
                $name,
                $email ?: null,
                $phone ?: null,
                $address ?: null,
                $city ?: null,
                $country,
                $tax_id ?: null,
            ];

            $query_customer = "UPDATE customers SET 
                name = ?, email = ?, phone = ?, address = ?, city = ?, country = ?, tax_id = ?";

            // Si se está cambiando la contraseña
            if (!empty($new_password)) {
                $query_customer .= ", password = ?";
                $update_fields[] = $new_password;
            }

            $query_customer .= " WHERE id = ?";
            $update_fields[] = $customer_id;

            $stmt_customer = $db->prepare($query_customer);
            $result_customer = $stmt_customer->execute($update_fields);

            // Actualizar tabla users si cambió el email o contraseña
            if (!empty($email) && $email !== $customer['email']) {
                $stmt_user = $db->prepare("UPDATE users SET email = ? WHERE customer_id = ?");
                $stmt_user->execute([$email, $customer_id]);
            }

            if (!empty($new_password)) {
                $stmt_user_pass = $db->prepare("UPDATE users SET password = ? WHERE customer_id = ?");
                $stmt_user_pass->execute([$new_password, $customer_id]);
            }

            if ($result_customer) {
                $db->commit();
                $success = "Perfil actualizado exitosamente";
                
                // Actualizar datos en memoria
                $customer['name'] = $name;
                $customer['email'] = $email;
                $customer['phone'] = $phone;
                $customer['address'] = $address;
                $customer['city'] = $city;
                $customer['country'] = $country;
                $customer['tax_id'] = $tax_id;
                if (!empty($new_password)) {
                    $customer['password'] = $new_password;
                }
            } else {
                throw new Exception("Error al actualizar el perfil");
            }
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = "Error al actualizar: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Catálogo de Productos</title>
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

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-user-edit"></i> Mi Perfil</h4>
                        <p class="mb-0 text-muted">Actualiza tu información personal</p>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="name" class="form-label">Nombre Completo *</label>
                                        <input type="text" id="name" name="name" class="form-control" 
                                               value="<?php echo htmlspecialchars($customer['name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" id="email" name="email" class="form-control" 
                                               value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="phone" class="form-label">Teléfono</label>
                                        <input type="text" id="phone" name="phone" class="form-control" 
                                               value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="tax_id" class="form-label">Cédula/RUC</label>
                                        <input type="text" id="tax_id" name="tax_id" class="form-control" 
                                               value="<?php echo htmlspecialchars($customer['tax_id'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="address" class="form-label">Dirección</label>
                                <input type="text" id="address" name="address" class="form-control" 
                                       value="<?php echo htmlspecialchars($customer['address'] ?? ''); ?>">
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="city" class="form-label">Ciudad</label>
                                        <input type="text" id="city" name="city" class="form-control" 
                                               value="<?php echo htmlspecialchars($customer['city'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="country" class="form-label">País</label>
                                        <input type="text" id="country" name="country" class="form-control" 
                                               value="<?php echo htmlspecialchars($customer['country'] ?? 'Panamá'); ?>">
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">
                            <h5 class="mb-3"><i class="fas fa-lock"></i> Cambiar Contraseña</h5>
                            <p class="text-muted small">Deja estos campos vacíos si no deseas cambiar tu contraseña</p>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label for="current_password" class="form-label">Contraseña Actual</label>
                                        <input type="password" id="current_password" name="current_password" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label for="new_password" class="form-label">Nueva Contraseña</label>
                                        <input type="password" id="new_password" name="new_password" class="form-control" minlength="6">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label for="confirm_password" class="form-label">Confirmar Nueva</label>
                                        <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Actualizar Perfil
                                </button>
                                <a href="../cart/index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Volver al Carrito
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validar confirmación de contraseña
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirm = this.value;
            
            if (confirm && newPassword !== confirm) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
