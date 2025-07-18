<?php
require_once '../../config/config.php';

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la sesión
session_destroy();

// Redirigir al login
redirect('login.php');
?>