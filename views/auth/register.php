<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = cleanInput($_POST['name']);
    $username = cleanInput($_POST['username']);
    $email = cleanInput($_POST['email']);
    $phone = cleanInput($_POST['phone']);
    $address = cleanInput($_POST['address']);
    $city = cleanInput($_POST['city']);
    $tax_id = cleanInput($_POST['tax_id']);
    $customer_type = cleanInput($_POST['customer_type']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    $errors = [];
    
    // Validaciones
    if (empty($name)) {
        $errors[] = "El nombre es obligatorio";
    }
    
    if (empty($username)) {
        $errors[] = "El nombre de usuario es obligatorio";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "El nombre de usuario solo puede contener letras, números y guiones bajos";
    } elseif (strlen($username) < 3) {
        $errors[] = "El nombre de usuario debe tener al menos 3 caracteres";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El email es obligatorio y debe tener un formato válido";
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "La contraseña debe tener al menos 6 caracteres";
    }
    
    if ($password !== $password_confirm) {
        $errors[] = "Las contraseñas no coinciden";
    }
    
    if (empty($errors)) {
        $database = new Database();
        $db = $database->getConnection();
        
        try {
            // Verificar si el username ya existe
            $check_username = $db->prepare("SELECT id FROM users WHERE username = ?");
            $check_username->execute([$username]);
            if ($check_username->fetch()) {
                $errors[] = "Ya existe un usuario con ese nombre de usuario";
            }
            
            // Verificar si el email ya existe en customers
            $check_email = $db->prepare("SELECT id FROM customers WHERE email = ?");
            $check_email->execute([$email]);
            if ($check_email->fetch()) {
                $errors[] = "Ya existe un cliente con ese email";
            }
            
            // Verificar si el email ya existe en users
            $check_user_email = $db->prepare("SELECT id FROM users WHERE email = ?");
            $check_user_email->execute([$email]);
            if ($check_user_email->fetch()) {
                $errors[] = "Ya existe un usuario con ese email";
            }
            
            // Verificar si el RUC/Cédula ya existe (si se proporciona)
            if (!empty($tax_id)) {
                $check_tax = $db->prepare("SELECT id FROM customers WHERE tax_id = ?");
                $check_tax->execute([$tax_id]);
                if ($check_tax->fetch()) {
                    $errors[] = "Ya existe un cliente con ese RUC/Cédula";
                }
            }
            
            if (empty($errors)) {
                // Iniciar transacción
                $db->beginTransaction();
                
                try {
                    // 1. Insertar en tabla customers
                    $query_customer = "INSERT INTO customers (name, email, phone, address, city, country, tax_id, customer_type, password, notes) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt_customer = $db->prepare($query_customer);
                    $result_customer = $stmt_customer->execute([
                        $name,
                        $email,
                        $phone ?: null,
                        $address ?: null,
                        $city ?: 'Ciudad de Panamá',
                        'Panamá',
                        $tax_id ?: null,
                        $customer_type,
                        $password,
                        'Cliente registrado vía web'
                    ]);
                    
                    if ($result_customer) {
                        $customer_id = $db->lastInsertId();
                        
                        // 2. Crear usuario automáticamente con username personalizado
                        $user_data = createUserForCustomer($db, $customer_id, $email, $name, $password, $username);
                        
                        if ($user_data) {
                            // Confirmar transacción
                            $db->commit();
                            
                            // Iniciar sesión automáticamente
                            $query_login = "SELECT id, username, email, password, role, customer_id FROM users WHERE email = ?";
                            $stmt_login = $db->prepare($query_login);
                            $stmt_login->execute([$email]);
                            $user = $stmt_login->fetch(PDO::FETCH_ASSOC);
                            
                            if ($user) {
                                $_SESSION['user_id'] = $user['id'];
                                $_SESSION['username'] = $user['username'];
                                $_SESSION['email'] = $user['email'];
                                $_SESSION['role'] = $user['role'];
                                $_SESSION['customer_id'] = $user['customer_id'];
                                
                                $_SESSION['success'] = "¡Registro exitoso! Bienvenido " . $name;
                                redirect('../cart/index.php');
                            }
                        } else {
                            throw new Exception("Error al crear cuenta de usuario");
                        }
                    } else {
                        throw new Exception("Error al registrar cliente");
                    }
                    
                } catch (Exception $e) {
                    $db->rollback();
                    $errors[] = "Error en el registro: " . $e->getMessage();
                }
            }
            
        } catch (PDOException $e) {
            $errors[] = "Error de base de datos: " . $e->getMessage();
        }
    }
    
    // Si hay errores, volver al login con los errores
    if (!empty($errors)) {
        $_SESSION['register_errors'] = $errors;
        $_SESSION['register_data'] = $_POST;
        redirect('login.php');
    }
}

// Si se accede directamente, redirigir al login
redirect('login.php');
?>
