<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$username, $username]);
        
        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($password === $user['password']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                redirect('../../index.php');
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
                        <span>Credenciales</span>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <small style="color: var(--text-muted);">
                        <strong>Demo:</strong> admin_tech/admin123 o consultor_V/con123
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
        
        // Add shake animation to form on error
        <?php if ($error): ?>
        document.querySelector('.login-form').classList.add('shake');
        <?php endif; ?>
    </script>
</body>
</html>