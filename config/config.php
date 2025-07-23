<?php
session_start();

// Configuraciones generales
define('BASE_URL', 'http://localhost/WEB/test/');
define('UPLOAD_PATH', 'assets/images/');

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Función para verificar si el usuario es admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Función para verificar si el usuario es consultor
function isConsultor() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'consultor';
}

// Función para verificar si el usuario es cliente
function isClient() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'cliente';
}

// Función para verificar si el usuario es admin o consultor
function isAdminOrConsultor() {
    return isAdmin() || isConsultor();
}

// Función para obtener el rol del usuario actual
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

// Función para obtener el customer_id del usuario cliente actual
function getCustomerId() {
    return $_SESSION['customer_id'] ?? null;
}

// Función para redirigir
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Función para limpiar datos de entrada
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para generar contraseña temporal
function generateTempPassword($length = 8) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}

// Función para crear usuario de cliente automáticamente
function createUserForCustomer($db, $customer_id, $email, $name, $password = null, $custom_username = null) {
    try {
        // Generar contraseña si no se proporciona
        if (!$password) {
            $password = generateTempPassword();
        }
        
        // Usar username personalizado o generar uno basado en el nombre
        if ($custom_username) {
            $username = $custom_username;
        } else {
            // Generar username único basado en el nombre
            $username = strtolower(str_replace([' ', '.', '@'], '_', $name));
            $username = preg_replace('/[^a-z0-9_]/', '', $username);
            
            // Verificar si el username ya existe y modificarlo si es necesario
            $check_user = $db->prepare("SELECT id FROM users WHERE username = ?");
            $check_user->execute([$username]);
            $counter = 1;
            $original_username = $username;
            
            while ($check_user->fetch()) {
                $username = $original_username . '_' . $counter;
                $check_user->execute([$username]);
                $counter++;
            }
        }
        
        // Insertar el usuario
        $query = "INSERT INTO users (username, email, password, role, customer_id) VALUES (?, ?, ?, 'cliente', ?)";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([$username, $email, $password, $customer_id]);
        
        return $result ? ['username' => $username, 'password' => $password] : false;
        
    } catch (PDOException $e) {
        return false;
    }
}
?>