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
?>