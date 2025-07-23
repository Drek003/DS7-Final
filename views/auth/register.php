<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = cleanInput($_POST['name']);
    $email = cleanInput($_POST['email']);
    $phone = cleanInput($_POST['phone']);
    $address = cleanInput($_POST['address']);
    $city = cleanInput($_POST['city']);
    
    // Procesar cédula - unir los 3 campos separados
    $tax_id = '';
    if (!empty($_POST['tax_id_provincia']) || !empty($_POST['tax_id_numero']) || !empty($_POST['tax_id_verificador'])) {
        $provincia = cleanInput($_POST['tax_id_provincia']);
        $numero = cleanInput($_POST['tax_id_numero']);
        $verificador = cleanInput($_POST['tax_id_verificador']);
        
        // Completar provincia con 0 si es necesario
        if (!empty($provincia) && strlen($provincia) == 1) {
            $provincia = '0' . $provincia;
        }
        
        // Si todos los campos están completos, formar la cédula
        if (!empty($provincia) && !empty($numero) && !empty($verificador)) {
            if (strlen($provincia) == 2 && strlen($numero) == 4 && (strlen($verificador) == 4 || strlen($verificador) == 5)) {
                $tax_id = $provincia . '-' . $numero . '-' . $verificador;
            }
        }
    }
    
    $customer_type = cleanInput($_POST['customer_type']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    $errors = [];
    
    // Validaciones
    // Validación del nombre - solo letras, espacios y algunos caracteres especiales permitidos
    if (empty($name)) {
        $errors[] = "El nombre es obligatorio";
    } elseif (!preg_match("/^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s\-'\.]+$/", $name)) {
        $errors[] = "El nombre solo puede contener letras, espacios, guiones, apostrofes y puntos";
    } elseif (strlen($name) < 2 || strlen($name) > 100) {
        $errors[] = "El nombre debe tener entre 2 y 100 caracteres";
    }
    
    // Validación del email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El email es obligatorio y debe tener un formato válido";
    } elseif (strlen($email) > 150) {
        $errors[] = "El email no puede exceder 150 caracteres";
    }
    
    // Validación del teléfono - debe empezar con +507 y tener formato válido
    if (!empty($phone)) {
        // Verificar que empiece con +507
        if (!preg_match("/^\+507\s/", $phone)) {
            $errors[] = "El teléfono debe empezar con +507";
        } else {
            // Extraer solo los números después del código de país
            $phone_clean = preg_replace('/^\+507\s/', '', $phone);
            $phone_clean = preg_replace('/[\s\-\(\)]/', '', $phone_clean);
            
            if (!preg_match("/^[0-9]+$/", $phone_clean)) {
                $errors[] = "El teléfono solo puede contener números, espacios, guiones y paréntesis después del código de país";
            } elseif (strlen($phone_clean) < 7 || strlen($phone_clean) > 8) {
                $errors[] = "El teléfono debe tener entre 7 y 8 dígitos después del código de país (+507)";
            }
        }
    }
    
    // Validación de la dirección
    if (!empty($address) && strlen($address) > 255) {
        $errors[] = "La dirección no puede exceder 255 caracteres";
    }
    
    // Validación de la ciudad - solo letras, espacios y algunos caracteres especiales
    if (!empty($city)) {
        if (!preg_match("/^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s\-'\.]+$/", $city)) {
            $errors[] = "La ciudad solo puede contener letras, espacios, guiones, apostrofes y puntos";
        } elseif (strlen($city) > 100) {
            $errors[] = "La ciudad no puede exceder 100 caracteres";
        }
    }
    
    // Validación del RUC/Cédula - formato ##-####-##### y primera parte 01-13
    if (!empty($_POST['tax_id_provincia']) || !empty($_POST['tax_id_numero']) || !empty($_POST['tax_id_verificador'])) {
        $provincia = cleanInput($_POST['tax_id_provincia']);
        $numero = cleanInput($_POST['tax_id_numero']);
        $verificador = cleanInput($_POST['tax_id_verificador']);
        
        // Validar que todos los campos estén completos si se empezó a llenar alguno
        if (empty($provincia) || empty($numero) || empty($verificador)) {
            $errors[] = "Si ingresa la cédula, debe completar todos los campos (provincia, número y verificador)";
        } else {
            // Completar provincia con 0 si es necesario
            if (strlen($provincia) == 1) {
                $provincia = '0' . $provincia;
            }
            
            // Validar longitudes
            if (strlen($provincia) != 2) {
                $errors[] = "El código de provincia debe tener 2 dígitos";
            }
            if (strlen($numero) != 4) {
                $errors[] = "El número de cédula debe tener 4 dígitos";
            }
            if (strlen($verificador) != 4 && strlen($verificador) != 5) {
                $errors[] = "El verificador de cédula debe tener 4 o 5 dígitos";
            }
            
            // Validar que solo contengan números
            if (!preg_match("/^[0-9]+$/", $provincia)) {
                $errors[] = "El código de provincia solo puede contener números";
            }
            if (!preg_match("/^[0-9]+$/", $numero)) {
                $errors[] = "El número de cédula solo puede contener números";
            }
            if (!preg_match("/^[0-9]+$/", $verificador)) {
                $errors[] = "El verificador de cédula solo puede contener números";
            }
            
            // Validar rango de provincia (01-13)
            if (empty($errors)) {
                $provincia_num = (int)$provincia;
                if ($provincia_num < 1 || $provincia_num > 13) {
                    $errors[] = "El código de provincia debe estar entre 01 y 13";
                }
                
                // Si todo está bien, formar la cédula completa
                if (empty($errors)) {
                    $tax_id = $provincia . '-' . $numero . '-' . $verificador;
                }
            }
        }
    }
    
    // Validación del tipo de cliente
    if (empty($customer_type)) {
        $errors[] = "El tipo de cliente es obligatorio";
    } elseif (!in_array($customer_type, ['persona', 'empresa'])) {
        $errors[] = "El tipo de cliente debe ser 'persona' o 'empresa'";
    }
    
    // Validación de la contraseña
    if (empty($password)) {
        $errors[] = "La contraseña es obligatoria";
    } elseif (strlen($password) < 6) {
        $errors[] = "La contraseña debe tener al menos 6 caracteres";
    } elseif (strlen($password) > 255) {
        $errors[] = "La contraseña no puede exceder 255 caracteres";
    } elseif (!preg_match("/^(?=.*[a-zA-Z])(?=.*\d)/", $password)) {
        $errors[] = "La contraseña debe contener al menos una letra y un número";
    }
    
    // Validación de confirmación de contraseña
    if ($password !== $password_confirm) {
        $errors[] = "Las contraseñas no coinciden";
    }
    
    if (empty($errors)) {
        $database = new Database();
        $db = $database->getConnection();
        
        try {
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
                        
                        // 2. Crear usuario automáticamente
                        $user_data = createUserForCustomer($db, $customer_id, $email, $name, $password);
                        
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
