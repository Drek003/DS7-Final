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
    <link href="../../assets/css/auth.css" rel="stylesheet">
    <style>
        /* Fix inmediato para selectores en dark mode */
        select, .form-select, .form-control select {
            background-color: #2d3748 !important;
            color: #ffffff !important;
            border: 1px solid #4a5568 !important;
            padding: 6px 12px !important;
            border-radius: 6px !important;
            height: 38px !important;
            font-size: 14px !important;
        }
        
        select option, .form-select option, .form-control option {
            background-color: #2d3748 !important;
            color: #ffffff !important;
            padding: 8px 12px !important;
        }
        
        select option:hover, .form-select option:hover, .form-control option:hover {
            background-color: #4a5568 !important;
            color: #ffffff !important;
        }
        
        select option:checked, .form-select option:checked, .form-control option:checked {
            background-color: #4a5568 !important;
            color: #ffffff !important;
        }
        
        #reg_customer_type {
            background-color: #2d3748 !important;
            color: #ffffff !important;
            border: 1px solid #4a5568 !important;
            padding: 6px 12px !important;
            border-radius: 6px !important;
            font-size: 14px !important;
            height: 38px !important;
        }
        
        #reg_customer_type option {
            background-color: #2d3748 !important;
            color: #ffffff !important;
            padding: 8px 12px !important;
        }
        
        /* Asegurar visibilidad del texto en todos los navegadores */
        #reg_customer_type:focus {
            background-color: #2d3748 !important;
            color: #ffffff !important;
            border-color: #007bff !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
        }
        
        /* Estilos para los campos de cédula separados */
        .cedula-container {
            display: flex;
            gap: 6px;
            align-items: center;
            height: 100%;
        }
        
        .cedula-container input {
            text-align: center;
            font-family: monospace;
            font-weight: bold;
            height: 38px !important;
            padding: 6px 8px !important;
            border-radius: 6px !important;
        }
        
        .cedula-container span {
            font-weight: bold;
            font-size: 16px;
            color: #ffffff;
            line-height: 1;
        }
        
        /* Asegurar que todos los form-control tengan la misma altura */
        .register-form .form-control,
        .register-form .form-select {
            height: 38px !important;
            padding: 6px 12px !important;
            border-radius: 6px !important;
            font-size: 14px !important;
        }
        
        /* Alineación específica para los grupos de formulario */
        .register-form .form-group {
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
            height: auto;
        }
        
        .register-form .form-group label {
            margin-bottom: 4px;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        /* Espaciado consistente entre filas */
        .register-form .row {
            margin-bottom: 0;
        }
        
        .register-form .row .col-md-6 {
            padding-left: 7.5px;
            padding-right: 7.5px;
        }
        
        /* Mejorar la alineación del contenedor de cédula */
        .cedula-container {
            display: flex !important;
            gap: 6px !important;
            align-items: center !important;
            justify-content: flex-start !important;
            flex-wrap: nowrap !important;
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
                                           pattern="[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s\-'\.]+"
                                           title="Solo letras, espacios, guiones, apostrofes y puntos"
                                           maxlength="100"
                                           value="<?php echo htmlspecialchars($register_data['name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reg_email">Email *</label>
                                    <input type="email" id="reg_email" name="email" class="form-control" 
                                           placeholder="ejemplo@correo.com"
                                           maxlength="150"
                                           value="<?php echo htmlspecialchars($register_data['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reg_phone">Teléfono</label>
                                    <input type="text" id="reg_phone" name="phone" class="form-control"
                                           placeholder="+507 6123-4567"
                                           title="Solo números, espacios, guiones y paréntesis"
                                           value="<?php 
                                               $phone_value = $register_data['phone'] ?? '';
                                               // Si no está vacío y no empieza con +507, agregarlo
                                               if (!empty($phone_value) && strpos($phone_value, '+507') !== 0) {
                                                   $phone_value = '+507 ' . $phone_value;
                                               }
                                               echo htmlspecialchars($phone_value); 
                                           ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reg_customer_type">Tipo de Cliente</label>
                                    <select id="reg_customer_type" name="customer_type" class="form-control">
                                        <option value="persona" <?php echo ($register_data['customer_type'] ?? '') === 'persona' ? 'selected' : ''; ?>>Cliente</option>
                                        <option value="empresa" <?php echo ($register_data['customer_type'] ?? '') === 'empresa' ? 'selected' : ''; ?>>Empresa</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="reg_address">Dirección</label>
                            <input type="text" id="reg_address" name="address" class="form-control"
                                   placeholder="Ej: Calle 50, Edificio Torre Global, Piso 15"
                                   maxlength="255"
                                   value="<?php echo htmlspecialchars($register_data['address'] ?? ''); ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reg_city">Ciudad</label>
                                    <input type="text" id="reg_city" name="city" class="form-control" 
                                           pattern="[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s\-'\.]+"
                                           title="Solo letras, espacios, guiones, apostrofes y puntos"
                                           maxlength="100"
                                           value="<?php echo htmlspecialchars($register_data['city'] ?? 'Ciudad de Panamá'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reg_tax_id">Cédula/RUC</label>
                                    <div class="cedula-container">
                                        <input type="text" id="reg_tax_id_provincia" name="tax_id_provincia" class="form-control" 
                                               maxlength="2" 
                                               style="flex: 0 0 50px; min-width: 50px;"
                                               title="Código de provincia (01 a 13)"
                                               value="<?php 
                                                   // Priorizar campos individuales sobre el tax_id completo
                                                   if (isset($register_data['tax_id_provincia'])) {
                                                       echo htmlspecialchars($register_data['tax_id_provincia']);
                                                   } else {
                                                       echo htmlspecialchars(explode('-', $register_data['tax_id'] ?? '')[0] ?? '');
                                                   }
                                               ?>">
                                        <span>-</span>
                                        <input type="text" id="reg_tax_id_numero" name="tax_id_numero" class="form-control" 
                                               maxlength="4" 
                                               style="flex: 0 0 70px; min-width: 70px;"
                                               title="4 dígitos del número"
                                               value="<?php 
                                                   // Priorizar campos individuales sobre el tax_id completo
                                                   if (isset($register_data['tax_id_numero'])) {
                                                       echo htmlspecialchars($register_data['tax_id_numero']);
                                                   } else {
                                                       echo htmlspecialchars(explode('-', $register_data['tax_id'] ?? '')[1] ?? '');
                                                   }
                                               ?>">
                                        <span>-</span>
                                        <input type="text" id="reg_tax_id_verificador" name="tax_id_verificador" class="form-control" 
                                               maxlength="5" 
                                               style="flex: 0 0 80px; min-width: 80px;"
                                               title="4 o 5 dígitos verificadores"
                                               value="<?php 
                                                   // Priorizar campos individuales sobre el tax_id completo
                                                   if (isset($register_data['tax_id_verificador'])) {
                                                       echo htmlspecialchars($register_data['tax_id_verificador']);
                                                   } else {
                                                       echo htmlspecialchars(explode('-', $register_data['tax_id'] ?? '')[2] ?? '');
                                                   }
                                               ?>">
                                        <!-- Campo oculto que contendrá la cédula completa -->
                                        <input type="hidden" id="reg_tax_id" name="tax_id" value="<?php echo htmlspecialchars($register_data['tax_id'] ?? ''); ?>">
                                    </div>
                                    <small class="text-muted mt-1" style="font-size: 11px;">Provincia (01-13) - 4 dígitos - 4 o 5 dígitos</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reg_password">Contraseña *</label>
                                    <input type="password" id="reg_password" name="password" class="form-control" 
                                           title="Mínimo 6 caracteres, debe contener al menos una letra y un número"
                                           maxlength="255"
                                           required minlength="6">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reg_password_confirm">Confirmar Contraseña *</label>
                                    <input type="password" id="reg_password_confirm" name="password_confirm" class="form-control" 
                                           title="Debe coincidir con la contraseña anterior"
                                           required>
                                </div>
                            </div>
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
                        <strong>Demo Cliente:</strong> consultor_V/con123
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
        
        // Validaciones del formulario de registro
        document.addEventListener('DOMContentLoaded', function() {
            const regForm = document.querySelector('form[action="register.php"]');
            const nameInput = document.getElementById('reg_name');
            const emailInput = document.getElementById('reg_email');
            const phoneInput = document.getElementById('reg_phone');
            const taxIdProvinciaInput = document.getElementById('reg_tax_id_provincia');
            const taxIdNumeroInput = document.getElementById('reg_tax_id_numero');
            const taxIdVerificadorInput = document.getElementById('reg_tax_id_verificador');
            const taxIdHiddenInput = document.getElementById('reg_tax_id');
            const cityInput = document.getElementById('reg_city');
            const passwordInput = document.getElementById('reg_password');
            const confirmPasswordInput = document.getElementById('reg_password_confirm');
            
            // Validación del nombre - solo letras, espacios y caracteres especiales permitidos
            nameInput.addEventListener('input', function() {
                const namePattern = /^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s\-'\.]+$/;
                if (this.value && !namePattern.test(this.value)) {
                    this.setCustomValidity('El nombre solo puede contener letras, espacios, guiones, apostrofes y puntos');
                } else if (this.value.length > 100) {
                    this.setCustomValidity('El nombre no puede exceder 100 caracteres');
                } else {
                    this.setCustomValidity('');
                }
            });
            
            // Validación del teléfono - solo números, espacios, guiones y paréntesis
            phoneInput.addEventListener('input', function() {
                let value = this.value;
                
                // Si el campo está vacío o el usuario borra todo, agregar +507
                if (value === '' || value === '+') {
                    this.value = '+507 ';
                    return;
                }
                
                // Si no empieza con +507, agregarlo
                if (!value.startsWith('+507')) {
                    // Si empieza con 507, agregar el +
                    if (value.startsWith('507')) {
                        this.value = '+' + value;
                        value = this.value;
                    } else {
                        // Si no tiene el código, agregarlo
                        this.value = '+507 ' + value.replace(/^\+?/, '');
                        value = this.value;
                    }
                }
                
                // Limpiar y formatear - permitir solo números después del código de país
                let cleanValue = value.replace('+507 ', '');
                cleanValue = cleanValue.replace(/[^\d\s\-\(\)]/g, '');
                this.value = '+507 ' + cleanValue;
                
                // Validar longitud del número (sin el código de país)
                const phoneClean = cleanValue.replace(/[\s\-\(\)]/g, '');
                if (phoneClean.length > 0 && (phoneClean.length < 7 || phoneClean.length > 8)) {
                    this.setCustomValidity('El teléfono debe tener entre 7 y 8 dígitos después del código de país');
                } else {
                    this.setCustomValidity('');
                }
            });
            
            // Agregar +507 cuando el campo recibe focus si está vacío
            phoneInput.addEventListener('focus', function() {
                if (this.value === '') {
                    this.value = '+507 ';
                }
            });
            
            // Validar que no se borre el código de país
            phoneInput.addEventListener('keydown', function(e) {
                const cursorPosition = this.selectionStart;
                const currentValue = this.value;
                
                // No permitir borrar el código de país +507
                if ((e.key === 'Backspace' || e.key === 'Delete') && cursorPosition <= 5) {
                    e.preventDefault();
                }
                
                // No permitir posicionar el cursor antes del espacio después de +507
                if (e.key === 'ArrowLeft' && cursorPosition <= 5) {
                    e.preventDefault();
                }
            });
            
            // Asegurar que el cursor no se posicione antes del código de país
            phoneInput.addEventListener('click', function() {
                if (this.selectionStart < 5) {
                    this.setSelectionRange(5, 5);
                }
            });
            
            // Validaciones de la cédula/RUC - 3 campos separados
            
            // Campo 1: Provincia (01-13) - acepta 1 o 2 dígitos
            taxIdProvinciaInput.addEventListener('input', function() {
                // Solo números
                this.value = this.value.replace(/\D/g, '');
                
                // Limitar a 2 dígitos
                if (this.value.length > 2) {
                    this.value = this.value.substring(0, 2);
                }
                
                // Validar rango 1-13
                const provincia = parseInt(this.value);
                if (this.value && (provincia < 1 || provincia > 13)) {
                    this.setCustomValidity('El código de provincia debe estar entre 01 y 13');
                } else {
                    this.setCustomValidity('');
                }
                
                // Completar con 0 al frente si es un solo dígito y se mueve al siguiente campo
                if (this.value.length === 1 && provincia >= 1 && provincia <= 9) {
                    // No hacer nada aquí, dejar que el usuario decida
                } else if (this.value.length === 2 || (this.value.length === 1 && provincia > 9)) {
                    // Moverse al siguiente campo
                    taxIdNumeroInput.focus();
                }
                
                updateFullTaxId();
            });
            
            // Completar con 0 al salir del campo si es necesario
            taxIdProvinciaInput.addEventListener('blur', function() {
                if (this.value.length === 1) {
                    this.value = '0' + this.value;
                    updateFullTaxId();
                }
            });
            
            // Campo 2: Número central (4 dígitos)
            taxIdNumeroInput.addEventListener('input', function() {
                // Solo números
                this.value = this.value.replace(/\D/g, '');
                
                // Limitar a 4 dígitos
                if (this.value.length > 4) {
                    this.value = this.value.substring(0, 4);
                }
                
                // Moverse al siguiente campo cuando esté completo
                if (this.value.length === 4) {
                    taxIdVerificadorInput.focus();
                }
                
                updateFullTaxId();
            });
            
            // Campo 3: Verificador (5 dígitos)
            taxIdVerificadorInput.addEventListener('input', function() {
                // Solo números
                this.value = this.value.replace(/\D/g, '');
                
                // Limitar a 5 dígitos
                if (this.value.length > 5) {
                    this.value = this.value.substring(0, 5);
                }
                
                updateFullTaxId();
            });
            
            // Función para actualizar el campo oculto con la cédula completa
            function updateFullTaxId() {
                const provincia = taxIdProvinciaInput.value.padStart(2, '0');
                const numero = taxIdNumeroInput.value;
                const verificador = taxIdVerificadorInput.value;
                
                if (provincia && numero && verificador) {
                    if (provincia.length === 2 && numero.length === 4 && (verificador.length === 4 || verificador.length === 5)) {
                        taxIdHiddenInput.value = provincia + '-' + numero + '-' + verificador;
                    } else {
                        taxIdHiddenInput.value = '';
                    }
                } else {
                    taxIdHiddenInput.value = '';
                }
            }
            
            // Manejar navegación con teclas
            taxIdProvinciaInput.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowRight' || e.key === 'Tab') {
                    if (this.value.length >= 1) {
                        e.preventDefault();
                        if (this.value.length === 1) {
                            this.value = '0' + this.value;
                            updateFullTaxId();
                        }
                        taxIdNumeroInput.focus();
                    }
                }
            });
            
            taxIdNumeroInput.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowLeft' && this.selectionStart === 0) {
                    e.preventDefault();
                    taxIdProvinciaInput.focus();
                } else if (e.key === 'ArrowRight' || e.key === 'Tab') {
                    if (this.value.length === 4) {
                        e.preventDefault();
                        taxIdVerificadorInput.focus();
                    }
                }
            });
            
            taxIdVerificadorInput.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowLeft' && this.selectionStart === 0) {
                    e.preventDefault();
                    taxIdNumeroInput.focus();
                }
            });
            
            // Validación de la ciudad - solo letras, espacios y caracteres especiales permitidos
            cityInput.addEventListener('input', function() {
                const cityPattern = /^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s\-'\.]+$/;
                if (this.value && !cityPattern.test(this.value)) {
                    this.setCustomValidity('La ciudad solo puede contener letras, espacios, guiones, apostrofes y puntos');
                } else if (this.value.length > 100) {
                    this.setCustomValidity('La ciudad no puede exceder 100 caracteres');
                } else {
                    this.setCustomValidity('');
                }
            });
            
            // Validación de la contraseña
            passwordInput.addEventListener('input', function() {
                const passwordPattern = /^(?=.*[a-zA-Z])(?=.*\d)/;
                if (this.value.length < 6) {
                    this.setCustomValidity('La contraseña debe tener al menos 6 caracteres');
                } else if (this.value.length > 255) {
                    this.setCustomValidity('La contraseña no puede exceder 255 caracteres');
                } else if (!passwordPattern.test(this.value)) {
                    this.setCustomValidity('La contraseña debe contener al menos una letra y un número');
                } else {
                    this.setCustomValidity('');
                }
                // Revalidar confirmación si ya tiene valor
                if (confirmPasswordInput.value) {
                    confirmPasswordInput.dispatchEvent(new Event('input'));
                }
            });
            
            // Validación de confirmación de contraseña
            confirmPasswordInput.addEventListener('input', function() {
                const password = passwordInput.value;
                const confirm = this.value;
                
                if (confirm && password !== confirm) {
                    this.setCustomValidity('Las contraseñas no coinciden');
                } else {
                    this.setCustomValidity('');
                }
            });
            
            // Validación antes de enviar el formulario
            regForm.addEventListener('submit', function(e) {
                const inputs = [nameInput, emailInput, phoneInput, taxIdProvinciaInput, taxIdNumeroInput, taxIdVerificadorInput, cityInput, passwordInput, confirmPasswordInput];
                let hasErrors = false;
                
                // Validación específica de la cédula completa
                if (taxIdProvinciaInput.value || taxIdNumeroInput.value || taxIdVerificadorInput.value) {
                    const provincia = taxIdProvinciaInput.value.padStart(2, '0');
                    const numero = taxIdNumeroInput.value;
                    const verificador = taxIdVerificadorInput.value;
                    
                    if (!provincia || provincia.length !== 2) {
                        hasErrors = true;
                        taxIdProvinciaInput.setCustomValidity('Debe ingresar el código de provincia (01-13)');
                        taxIdProvinciaInput.reportValidity();
                    } else if (!numero || numero.length !== 4) {
                        hasErrors = true;
                        taxIdNumeroInput.setCustomValidity('Debe ingresar 4 dígitos');
                        taxIdNumeroInput.reportValidity();
                    } else if (!verificador || (verificador.length !== 4 && verificador.length !== 5)) {
                        hasErrors = true;
                        taxIdVerificadorInput.setCustomValidity('Debe ingresar 4 o 5 dígitos verificadores');
                        taxIdVerificadorInput.reportValidity();
                    } else {
                        // Actualizar el campo oculto antes del envío
                        updateFullTaxId();
                    }
                }
                
                inputs.forEach(input => {
                    if (input && !input.checkValidity()) {
                        hasErrors = true;
                        input.reportValidity();
                    }
                });
                
                if (hasErrors) {
                    e.preventDefault();
                }
            });
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