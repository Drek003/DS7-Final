<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

$error = '';
$register_errors = [];
$register_data = [];

// Verificar errores de registro
if (isset($_SESSION['register_errors'])) {
    $register_errors = $_SESSION['register_errors'];
    unset($_SESSION['register_errors']);
}

// Recuperar datos del formulario de registro
if (isset($_SESSION['register_data'])) {
    $register_data = $_SESSION['register_data'];
    unset($_SESSION['register_data']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id, username, email, password, role, customer_id FROM users WHERE username = ? OR email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$username, $username]);
        
        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($password === $user['password']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['customer_id'] = $user['customer_id']; // Para clientes
                
                // Redirigir según el rol
                if ($user['role'] === 'cliente') {
                    redirect('../../views/cart/index.php'); // Los clientes van al carrito
                } else {
                    redirect('../../index.php'); // Admins y consultores van al dashboard
                }
            } else {
                $error = 'Credenciales incorrectas';
            }
        } else {
            $error = 'Usuario no encontrado';
        }
    } else {
        $error = 'Por favor complete todos los campos';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Catálogo de Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
    <link href="../../assets/css/bootstrap-dark.css" rel="stylesheet">
    <style>
        .register-form {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 2rem;
            margin-top: 1rem;
            border: 1px solid var(--border-color);
        }
        
        .register-header h3 {
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .register-header p {
            color: var(--text-muted);
            margin-bottom: 0;
        }
        
        .register-form .form-group {
            margin-bottom: 1rem;
        }
        
        .register-form label {
            color: var(--text-primary);
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .register-form .form-control {
            background: var(--input-bg) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-primary) !important;
            border-radius: 8px !important;
            padding: 0.75rem !important;
            width: 100% !important;
            height: auto !important;
            font-size: 1rem !important;
        }
        
        .register-form .form-control:focus {
            background: var(--input-bg) !important;
            border-color: var(--primary) !important;
            color: var(--text-primary) !important;
            box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.25) !important;
        }
        
        .register-form .form-select {
            background: var(--input-bg) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-primary) !important;
            border-radius: 8px !important;
            padding: 0.75rem !important;
            width: 100% !important;
            height: auto !important;
            font-size: 1rem !important;
        }
        
        .register-form .form-select:focus {
            background: var(--input-bg) !important;
            border-color: var(--primary) !important;
            color: var(--text-primary) !important;
            box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.25) !important;
        }
        
        .error-message {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #dc3545;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .error-message i {
            margin-right: 0.5rem;
        }
        
        /* Fix para los campos input específicamente */
        .register-form input[type="text"],
        .register-form input[type="email"],
        .register-form input[type="password"],
        .register-form select {
            background-color: #2d3748 !important;
            border: 1px solid #4a5568 !important;
            color: #ffffff !important;
            padding: 12px !important;
            border-radius: 8px !important;
            width: 100% !important;
            font-size: 16px !important;
            line-height: 1.5 !important;
        }
        
        .register-form input[type="text"]:focus,
        .register-form input[type="email"]:focus,
        .register-form input[type="password"]:focus,
        .register-form select:focus {
            background-color: #2d3748 !important;
            border-color: #4a90e2 !important;
            color: #ffffff !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1) !important;
        }
        
        /* Centrar el botón de login */
        .login-button {
            width: 100% !important;
            display: block !important;
            margin: 0 auto !important;
            text-align: center !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <!-- Sidebar -->
            <div class="login-sidebar">
                <div class="brand">
                    <div class="logo">
                        <i class="fas fa-store"></i>
                    </div>
                    <h1>CodeCorp</h1>
                </div>
                
                <div class="user-profile">
                    <div class="avatar-container">
                        <div class="avatar-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    <div class="welcome-text">
                        <h4>Bienvenido de vuelta</h4>
                        <p>Inicia sesión para acceder a tu catálogo personal</p>
                    </div>
                </div>
                
                <div class="sidebar-footer">
                    <div class="copyright">
                        © 2024 Catálogo de Productos<br>
                        Todos los derechos reservados
                    </div>
                </div>
            </div>
            
            <!-- Login Content -->
            <div class="login-content">
                <div class="login-header">
                    <h2>Iniciar Sesión</h2>
                    <p>Ingresa tus credenciales para acceder al sistema</p>
                </div>
                
                <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($register_errors)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php foreach ($register_errors as $reg_error): ?>
                        <div><?php echo $reg_error; ?></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label for="username">Usuario o Email</label>
                        <div class="input-container">
                            <i class="fas fa-user"></i>
                            <input type="text" id="username" name="username" placeholder="Ingresa tu usuario o email" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <div class="input-container">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required>
                            <button type="button" class="toggle-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Recordarme</label>
                        </div>
                        
                    </div>
                    
                    <button type="submit" class="login-button">
                        <i class="fas fa-sign-in-alt"></i>
                        Iniciar Sesión
                    </button>
                </form>
                
                <div class="login-footer">
                    <div class="separator">
                        <span>¿No tienes cuenta?</span>
                    </div>
                    <button type="button" class="btn btn-outline-light w-100 mt-3" onclick="toggleRegisterForm()">
                        <i class="fas fa-user-plus"></i>
                        Registrarme como cliente
                    </button>
                </div>
                
                <!-- Formulario de Registro (inicialmente oculto) -->
                <div id="registerForm" class="register-form" style="display: none;">
                    <div class="register-header">
                        <h3>Registro de Cliente</h3>
                        <p>Crea tu cuenta para realizar compras</p>
                    </div>
                    
                    <form method="POST" action="register.php" class="mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reg_name">Nombre Completo *</label>
                                    <input type="text" id="reg_name" name="name" class="form-control" 
                                           value="<?php echo htmlspecialchars($register_data['name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reg_email">Email *</label>
                                    <input type="email" id="reg_email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($register_data['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reg_phone">Teléfono</label>
                                    <input type="text" id="reg_phone" name="phone" class="form-control"
                                           value="<?php echo htmlspecialchars($register_data['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reg_tax_id">Cédula/RUC</label>
                                    <input type="text" id="reg_tax_id" name="tax_id" class="form-control"
                                           value="<?php echo htmlspecialchars($register_data['tax_id'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="reg_address">Dirección</label>
                            <input type="text" id="reg_address" name="address" class="form-control"
                                   value="<?php echo htmlspecialchars($register_data['address'] ?? ''); ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reg_city">Ciudad</label>
                                    <input type="text" id="reg_city" name="city" class="form-control" 
                                           value="<?php echo htmlspecialchars($register_data['city'] ?? 'Ciudad de Panamá'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reg_customer_type">Tipo de Cliente</label>
                                    <select id="reg_customer_type" name="customer_type" class="form-select">
                                        <option value="individual" <?php echo ($register_data['customer_type'] ?? '') === 'individual' ? 'selected' : ''; ?>>Individual</option>
                                        <option value="empresa" <?php echo ($register_data['customer_type'] ?? '') === 'empresa' ? 'selected' : ''; ?>>Empresa</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="reg_password">Contraseña *</label>
                            <input type="password" id="reg_password" name="password" class="form-control" required minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label for="reg_password_confirm">Confirmar Contraseña *</label>
                            <input type="password" id="reg_password_confirm" name="password_confirm" class="form-control" required>
                        </div>
                        
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="fas fa-user-plus"></i>
                                Registrarse
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="toggleRegisterForm()">
                                <i class="fas fa-arrow-left"></i>
                                Volver al Login
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="text-center mt-4">
                    <small style="color: var(--text-muted);">
                        <strong>Demo Admin:</strong> admin_tech/admin123<br>
                        <strong>Demo Cliente:</strong> juan.perez@email.com/cliente123
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Toggle register form
        function toggleRegisterForm() {
            const registerForm = document.getElementById('registerForm');
            const loginForm = document.querySelector('.login-form');
            const loginHeader = document.querySelector('.login-header');
            const loginFooter = document.querySelector('.login-footer');
            
            if (registerForm.style.display === 'none') {
                // Mostrar formulario de registro
                registerForm.style.display = 'block';
                loginForm.style.display = 'none';
                loginHeader.style.display = 'none';
                loginFooter.style.display = 'none'; // Ocultar el botón "Registrarme como cliente"
            } else {
                // Mostrar formulario de login
                registerForm.style.display = 'none';
                loginForm.style.display = 'block';
                loginHeader.style.display = 'block';
                loginFooter.style.display = 'block'; // Mostrar el botón "Registrarme como cliente"
            }
        }
        
        // Validate password confirmation
        document.getElementById('reg_password_confirm').addEventListener('input', function() {
            const password = document.getElementById('reg_password').value;
            const confirm = this.value;
            
            if (confirm && password !== confirm) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Add shake animation to form on error
        <?php if ($error): ?>
        document.querySelector('.login-form').classList.add('shake');
        <?php endif; ?>
        
        // Show register form if there are registration errors
        <?php if (!empty($register_errors)): ?>
        toggleRegisterForm();
        <?php endif; ?>
    </script>
</body>
</html>