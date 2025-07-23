-- =========================================
-- MÓDULO 1 - GESTIÓN DE CLIENTES
-- Modificaciones a la Base de Datos
-- =========================================
-- 
-- INSTRUCCIONES:
-- 1. Respalda tu base de datos actual antes de ejecutar
-- 2. Ejecuta este script en tu base de datos 'ds7'
-- 3. Este script agrega funcionalidades sin eliminar datos existentes
--

USE `ds7`;

-- =========================================
-- 1. AGREGAR CONTRASEÑA A TABLA CUSTOMERS
-- =========================================

-- Agregar campo password a la tabla customers (solo si no existe)
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'customers' 
AND COLUMN_NAME = 'password';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `customers` ADD COLUMN `password` VARCHAR(255) DEFAULT NULL AFTER `email`', 
    'SELECT "Columna password ya existe en customers" as mensaje');
    
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================
-- 2. ACTUALIZAR CLIENTES EXISTENTES CON CONTRASEÑAS
-- =========================================

-- Asignar contraseñas a los clientes existentes para que puedan hacer login
-- NOTA: En producción estas deberían estar hasheadas

UPDATE `customers` SET `password` = 'cliente123' WHERE `id` = 1; -- Juan Pérez
UPDATE `customers` SET `password` = 'maria2024' WHERE `id` = 2;  -- María González  
UPDATE `customers` SET `password` = 'carlos456' WHERE `id` = 3;  -- Carlos Rodríguez
UPDATE `customers` SET `password` = 'tecno2024' WHERE `id` = 4;  -- Tecno-Y
UPDATE `customers` SET `password` = 'epsilon123' WHERE `id` = 5; -- Empresas Épsilon

-- =========================================
-- 3. AGREGAR NUEVO ROL 'cliente' A TABLA USERS
-- =========================================

-- Verificar si el rol 'cliente' ya existe
SET @role_exists = 0;
SELECT COUNT(*) INTO @role_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'role' 
AND COLUMN_TYPE LIKE '%cliente%';

-- Modificar el enum de roles para incluir 'cliente' (solo si no existe)
SET @sql_role = IF(@role_exists = 0, 
    'ALTER TABLE `users` MODIFY COLUMN `role` ENUM(\'admin\',\'consultor\',\'cliente\') DEFAULT \'consultor\'', 
    'SELECT "Rol cliente ya existe en users" as mensaje');
    
PREPARE stmt FROM @sql_role;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================
-- 4. CREAR RELACIÓN ENTRE USERS Y CUSTOMERS
-- =========================================

-- Verificar si customer_id ya existe
SET @customer_id_exists = 0;
SELECT COUNT(*) INTO @customer_id_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'customer_id';

-- Agregar campo customer_id a tabla users para vincular cuentas (solo si no existe)
SET @sql_customer_id = IF(@customer_id_exists = 0, 
    'ALTER TABLE `users` ADD COLUMN `customer_id` INT DEFAULT NULL AFTER `role`', 
    'SELECT "Campo customer_id ya existe en users" as mensaje');
    
PREPARE stmt FROM @sql_customer_id;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar si la clave foránea ya existe
SET @fk_exists = 0;
SELECT COUNT(*) INTO @fk_exists 
FROM information_schema.KEY_COLUMN_USAGE 
WHERE CONSTRAINT_SCHEMA = DATABASE() 
AND TABLE_NAME = 'users' 
AND CONSTRAINT_NAME = 'fk_users_customer';

-- Agregar clave foránea con eliminación en cascada (solo si no existe)
SET @sql_fk = IF(@fk_exists = 0, 
    'ALTER TABLE `users` ADD CONSTRAINT `fk_users_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE ON UPDATE CASCADE', 
    'SELECT "Clave foránea fk_users_customer ya existe" as mensaje');
    
PREPARE stmt FROM @sql_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar si el índice ya existe
SET @idx_exists = 0;
SELECT COUNT(*) INTO @idx_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'users' 
AND INDEX_NAME = 'idx_users_customer_id';

-- Crear índice para optimizar búsquedas (solo si no existe)
SET @sql_idx = IF(@idx_exists = 0, 
    'ALTER TABLE `users` ADD INDEX `idx_users_customer_id` (`customer_id`)', 
    'SELECT "Índice idx_users_customer_id ya existe" as mensaje');
    
PREPARE stmt FROM @sql_idx;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================
-- 5. CREAR CUENTAS DE USUARIO PARA CLIENTES EXISTENTES
-- =========================================

-- Crear cuentas de usuario para los clientes existentes (usando INSERT IGNORE para evitar duplicados)
-- Estos usuarios tendrán rol 'cliente' y estarán vinculados a sus registros de customer

INSERT IGNORE INTO `users` (`username`, `email`, `password`, `role`, `customer_id`) VALUES
-- Clientes existentes
('juan_perez', 'juan.perez@email.com', 'cliente123', 'cliente', 1),
('maria_gonzalez', 'maria.gonzalez@email.com', 'maria2024', 'cliente', 2),
('carlos_rodriguez', 'carlos.rodriguez@email.com', 'carlos456', 'cliente', 3),
('tecno_y', 'tecnoY@gmail.com', 'tecno2024', 'cliente', 4),
('epsilon_admin', 'adrianalbertojimenez@gmail.com', 'epsilon123', 'cliente', 5);

-- =========================================
-- 6. AGREGAR INDICES PARA OPTIMIZAR BÚSQUEDAS
-- =========================================

-- Verificar y crear índice para email en customers (para login)
SET @idx_email_exists = 0;
SELECT COUNT(*) INTO @idx_email_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'customers' 
AND INDEX_NAME = 'idx_customer_email';

SET @sql_idx_email = IF(@idx_email_exists = 0, 
    'ALTER TABLE `customers` ADD INDEX `idx_customer_email` (`email`)', 
    'SELECT "Índice idx_customer_email ya existe" as mensaje');
    
PREPARE stmt FROM @sql_idx_email;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar y crear índice para combinación email + password en customers
SET @idx_login_exists = 0;
SELECT COUNT(*) INTO @idx_login_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'customers' 
AND INDEX_NAME = 'idx_customer_login';

SET @sql_idx_login = IF(@idx_login_exists = 0, 
    'ALTER TABLE `customers` ADD INDEX `idx_customer_login` (`email`, `password`)', 
    'SELECT "Índice idx_customer_login ya existe" as mensaje');
    
PREPARE stmt FROM @sql_idx_login;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================
-- 7. VERIFICACIÓN DE CAMBIOS
-- =========================================

-- Verificar la estructura de la tabla users
SELECT 'Estructura de tabla users:' as mensaje;
SHOW COLUMNS FROM users;

-- Verificar la estructura de la tabla customers  
SELECT 'Estructura de tabla customers:' as mensaje;
SHOW COLUMNS FROM customers;

-- Verificar las claves foráneas creadas
SELECT 'Claves foráneas en la base de datos:' as mensaje;
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE 
WHERE REFERENCED_TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('users', 'customers')
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Verificar que los datos se insertaron correctamente
SELECT 'Total de usuarios por rol:' as mensaje;
SELECT role, COUNT(*) as total FROM users GROUP BY role;

SELECT 'Total de clientes por tipo:' as mensaje;
SELECT customer_type, COUNT(*) as total FROM customers GROUP BY customer_type;

-- Verificar la relación users-customers
SELECT 'Relación Users-Customers:' as mensaje;
SELECT 
    u.username,
    u.role,
    c.name as customer_name,
    c.email,
    c.customer_type
FROM users u
LEFT JOIN customers c ON u.customer_id = c.id
WHERE u.role = 'cliente'
ORDER BY u.username;

-- =========================================
-- 8. PRUEBA DE INTEGRIDAD REFERENCIAL
-- =========================================

-- Esta sección es solo informativa para verificar que las CASCADE funcionan
-- NO EJECUTAR EN PRODUCCIÓN sin confirmación

/*
-- Ejemplo de prueba de CASCADE DELETE (NO EJECUTAR AUTOMÁTICAMENTE):
-- Si ejecutas esto, se eliminará un cliente y su usuario asociado:

-- SELECT 'ANTES - Usuario y Cliente de prueba:' as info;
-- SELECT u.username, c.name FROM users u 
-- JOIN customers c ON u.customer_id = c.id 
-- WHERE c.email = 'ana.martinez@email.com';

-- DELETE FROM customers WHERE email = 'ana.martinez@email.com';

-- SELECT 'DESPUÉS - Verificar que el usuario también se eliminó:' as info;
-- SELECT COUNT(*) as usuarios_eliminados FROM users WHERE email = 'ana.martinez@email.com';
*/

-- =========================================
-- 9. RESUMEN DE CAMBIOS IMPLEMENTADOS
-- =========================================

SELECT '
=== MÓDULO 1 - GESTIÓN DE CLIENTES ===
✓ Tabla customers modificada con campo password para autenticación
✓ Tabla users modificada con customer_id y soporte para rol "cliente"
✓ Clave foránea con CASCADE DELETE para integridad referencial
✓ Datos de prueba agregados con usuarios y clientes relacionados
✓ Índices optimizados para búsquedas de login
✓ Sistema preparado para autenticación integrada

ESTADO: ✅ MÓDULO COMPLETADO E IMPLEMENTADO
FECHA: Julio 2025
VERSION: 1.0 FINAL

FUNCIONALIDADES IMPLEMENTADAS:
✅ Admin puede ver/editar/eliminar clientes (sin crear)
✅ Clientes se auto-registran desde login
✅ Clientes gestionan su propio perfil
✅ Integridad de datos con CASCADE DELETE
✅ Navegación role-based funcional
✅ Sistema de autenticación completo

PRÓXIMOS PASOS:
- Módulo 1 COMPLETADO
- Continuar con Módulo 2 (siguiente funcionalidad)
' as resumen;

COMMIT;

-- Consulta para verificar que los cambios se aplicaron correctamente
SELECT 'VERIFICACIÓN DE CAMBIOS APLICADOS' as RESULTADO;

-- Mostrar estructura actualizada de customers
SHOW COLUMNS FROM `customers`;

-- Mostrar clientes con contraseñas
SELECT id, name, email, password, customer_type, created_at 
FROM `customers` 
ORDER BY id;

-- Mostrar estructura actualizada de users
SHOW COLUMNS FROM `users`;

-- Contar registros por tipo
SELECT 
    'CUSTOMERS' as tabla, 
    COUNT(*) as total_registros,
    COUNT(password) as con_password
FROM `customers`
UNION ALL
SELECT 
    'USERS' as tabla, 
    COUNT(*) as total_registros,
    COUNT(password) as con_password  
FROM `users`;

-- =========================================
-- NOTAS IMPORTANTES
-- =========================================

/*
CREDENCIALES DE PRUEBA DESPUÉS DE EJECUTAR ESTE SCRIPT:

ADMINISTRADORES (tabla users):
- admin_tech / admin123
- consultor_V / con123

CLIENTES CONVERTIDOS A USUARIOS (tabla users con rol 'cliente'):
- juan.perez@email.com / cliente123 (Juan Pérez - Individual)
- maria.gonzalez@email.com / maria2024 (María González - Individual)
- carlos.rodriguez@email.com / carlos456 (Carlos Rodríguez - Individual)
- tecnoY@gmail.com / tecno2024 (Tecno-Y - Empresa)
- adrianalbertojimenez@gmail.com / epsilon123 (Empresas Épsilon - Empresa)

TOTAL ESPERADO:
- 2 administradores/consultores originales
- 5 clientes convertidos a usuarios
- TOTAL: 7 usuarios

ROLES:
- admin: Acceso completo al sistema de gestión
- consultor: Solo lectura de datos
- cliente: Perfil propio + carrito + compras

ESTADO DEL MÓDULO 1: ✅ COMPLETADO E IMPLEMENTADO (Julio 2025)

FUNCIONALIDADES FINALES:
✅ Sistema de registro de clientes desde login
✅ Gestión de perfiles por los propios clientes
✅ Administradores solo pueden ver/editar/eliminar (no crear)
✅ Integridad referencial con CASCADE DELETE
✅ Navegación adaptativa según rol de usuario
✅ Autenticación unificada users/customers

*/

SELECT 'SCRIPT EJECUTADO EXITOSAMENTE - MÓDULO 1 CLIENTES COMPLETADO' as MENSAJE;
