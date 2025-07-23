# DS7-Final
Proyecto de Desarrollo de Software 7 - Sistema Completo de E-Commerce con Catálogo de Productos

# Sistema de E-Commerce - Catálogo de Productos y Carrito de Compras

## Descripción del Proyecto

Sistema completo de comercio electrónico que permite gestionar un catálogo de productos organizados por categorías, incluyendo carrito de compras, proceso de checkout completo, gestión de clientes, generación automática de facturas en XML y sistema de roles avanzado. Optimizado para dispositivos móviles con interfaz moderna y funcionalidades completas de e-commerce.

## Características Principales

### 🏷️ Gestión de Categorías
- Navegador de categorías con imagen representativa
- Operaciones CRUD completas (crear, ver, editar, eliminar)
- Organización jerárquica de productos
- Filtrado de productos por categoría

### 📦 Gestión de Productos
- Catálogo completo con nombre, descripción, precio e imágenes
- Asociación de productos con categorías específicas
- Operaciones CRUD completas con validaciones
- Control de inventario con stock y stock mínimo
- Visualización optimizada para dispositivos móviles

### 🛒 Sistema de Carrito de Compras
- Agregar/quitar productos del carrito
- Actualización de cantidades en tiempo real
- Cálculo automático de subtotales, impuestos y envío
- Persistencia del carrito por usuario
- Contador dinámico en la navegación

### � Proceso de Checkout Completo
- Formulario de checkout en 3 etapas
- Validación automática de datos de envío
- Múltiples métodos de pago (Tarjeta, PayPal, Contra entrega)
- Formateo automático de teléfono (+507 6345-6789)
- Precarga de datos del usuario registrado

### 👥 Gestión de Clientes
- Registro completo de información del cliente
- Perfiles con datos de contacto y dirección
- Historial de compras y facturas
- Operaciones CRUD para administradores
- Validación de datos únicos (email, RUC/Cédula)

### 🧾 Sistema de Facturación
- Generación automática de facturas
- Numeración secuencial de facturas
- Cálculo de impuestos (7% ITBMS)
- Envío gratuito para compras mayores a $100
- Generación automática de XML con datos fiscales

### 📄 Generación de Documentos
- Creación automática de archivos XML de factura
- Generación de imagen PNG de la factura
- Empaquetado en ZIP para descarga
- Cumplimiento con estándares fiscales panameños

### �🔍 Funcionalidades de Búsqueda
- Navegación por categorías
- Búsqueda por nombre de producto
- Sistema de filtros avanzados por precio y categoría
- Resultados en tiempo real

### 👥 Sistema de Usuarios Avanzado
- Autenticación segura con sesiones
- Roles diferenciados: Administradores y Usuarios
- Permisos específicos según el rol
- Registro de nuevos usuarios
- Gestión de perfiles de usuario

### 📱 Interfaz Móvil Moderna
- Diseño responsivo con Bootstrap 5
- Experiencia de usuario optimizada para móviles
- Navegación intuitiva y táctil
- Tema oscuro/claro adaptativo
- Animaciones y transiciones suaves

## Tecnologías Utilizadas

- **Frontend**: HTML5, CSS3, JavaScript ES6+, Bootstrap 5, Font Awesome 6
- **Backend**: PHP 8+, PDO con prepared statements
- **Base de Datos**: MySQL 8.0+ / MariaDB 10.3+
- **Arquitectura**: MVC (Modelo-Vista-Controlador)
- **APIs**: Integración con sistemas de pago (PayPal)
- **Extensiones PHP**: GD, ZIP, DOM, MySQLi
- **Generación de Documentos**: XML, PNG, ZIP

## Estructura del Proyecto

```
DS7-Final/
├── index.php                 # Página principal del catálogo
├── README.md                 # Documentación del proyecto
├── jquery3-4.min.js         # Librería jQuery
├── config/
│   ├── database.php         # Configuración de base de datos
│   └── config.php           # Configuraciones generales y funciones
├── includes/
│   └── nav.php              # Navegación principal con carrito
├── api/
│   └── api.php              # API para funciones AJAX
├── assets/
│   ├── css/
│   │   ├── bootstrap.min.css      # Bootstrap 5 framework
│   │   ├── bootstrap-dark.css     # Tema oscuro personalizado
│   │   ├── bootstrap-icons.css    # Iconos de Bootstrap
│   │   ├── styles.css             # Estilos generales
│   │   ├── custom.css             # Estilos personalizados
│   │   ├── catg.css               # Estilos para categorías
│   │   ├── cart.css               # Estilos del carrito
│   │   ├── checkout.css           # Estilos del checkout
│   │   ├── confirmation.css       # Estilos de confirmación
│   │   ├── customers.css          # Estilos de clientes
│   │   └── auth.css               # Estilos de autenticación
│   └── js/
│       ├── bootstrap.min.js       # Bootstrap JavaScript
│       ├── jquery.min.js          # jQuery
│       ├── app.js                 # JavaScript principal
│       └── cart.js                # JavaScript del carrito
├── views/
│   ├── auth/
│   │   ├── login.php              # Vista de login
│   │   ├── logout.php             # Proceso de logout
│   │   └── register.php           # Vista de registro
│   ├── categories/
│   │   ├── index.php              # Lista de categorías
│   │   ├── create.php             # Crear categoría
│   │   ├── edit.php               # Editar categoría
│   │   └── delete.php             # Eliminar categoría
│   ├── products/
│   │   ├── index.php              # Catálogo de productos
│   │   ├── show.php               # Detalle de producto
│   │   ├── create.php             # Crear producto
│   │   ├── edit.php               # Editar producto
│   │   └── delete.php             # Eliminar producto
│   ├── customers/
│   │   ├── index.php              # Lista de clientes
│   │   ├── show.php               # Detalle de cliente
│   │   ├── edit.php               # Editar cliente
│   │   ├── delete.php             # Eliminar cliente
│   │   └── profile.php            # Perfil del usuario
│   └── cart/
│       ├── index.php              # Vista del carrito
│       ├── add_to_cart.php        # Agregar al carrito
│       ├── update_quantity.php    # Actualizar cantidad
│       ├── get_cart_count.php     # Obtener contador
│       ├── get_cart_totals.php    # Obtener totales
│       ├── checkout.php           # Proceso de checkout
│       ├── process_checkout.php   # Procesar compra
│       ├── confirmation.php       # Confirmación de compra
│       └── print_invoice.php      # Imprimir factura
├── xml/
│   ├── xml_generator.php          # Generador de XML y ZIP
│   └── README                     # Documentación XML
└── sql/
    ├── ds6-2.sql                  # Base de datos principal
    └── modulo1-clientes-update.sql # Actualización de clientes
```

## Base de Datos

### Tabla: users
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    customer_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);
```

### Tabla: customers
```sql
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    country VARCHAR(50) DEFAULT 'Panamá',
    tax_id VARCHAR(20) UNIQUE,
    customer_type ENUM('individual', 'empresa') DEFAULT 'individual',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Tabla: categories
```sql
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Tabla: products
```sql
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    min_stock INT DEFAULT 5,
    image VARCHAR(255),
    category_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);
```

### Tabla: shopping_cart
```sql
CREATE TABLE shopping_cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price_at_time DECIMAL(10,2) NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

### Tabla: sales
```sql
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    customer_id INT NOT NULL,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) NOT NULL,
    shipping_amount DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);
```

### Tabla: sale_details
```sql
CREATE TABLE sale_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price_at_time DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);
```

## Instalación y Configuración

### Requisitos del Sistema
- PHP 8.0 o superior
- MySQL 5.7 o superior / MariaDB 10.3+
- XAMPP (recomendado para desarrollo)
- Extensiones PHP requeridas:
  - PDO y PDO_MySQL
  - GD (para generación de imágenes)
  - ZIP (para archivos comprimidos)
  - DOM (para generación de XML)
  - MySQLi

### Pasos de Instalación

1. **Clonar o descargar el proyecto**
```bash
git clone https://github.com/Drek003/DS7-Final.git
cd DS7-Final
```

2. **Configurar XAMPP**
- Instalar XAMPP y iniciar Apache y MySQL
- Colocar el proyecto en `C:\xampp\htdocs\DS7-Final`

3. **Configurar la base de datos**
```bash
# Acceder a phpMyAdmin o consola MySQL
# Crear base de datos
CREATE DATABASE ds6_2;
USE ds6_2;

# Importar el archivo SQL
SOURCE sql/ds6-2.sql;
```

4. **Configurar conexión a BD**
```php
// config/database.php
class Database {
    private $host = "localhost";
    private $database_name = "ds6_2";
    private $username = "root";
    private $password = "";
    // ... resto de la configuración
}
```

5. **Habilitar extensiones PHP en XAMPP**
```ini
# En C:\xampp\php\php.ini, descomentar:
extension=gd
extension=zip
extension=pdo_mysql
```

6. **Configurar permisos (si es necesario)**
```bash
# Para sistemas Linux/Mac
chmod 755 xml/
chmod 755 assets/
```

7. **Acceder a la aplicación**
- URL: `http://localhost/DS7-Final/`
- Usuario admin por defecto: Crear mediante registro

## Funcionalidades por Rol

### 👨‍💼 Administrador
- ✅ Gestión completa de categorías (CRUD)
- ✅ Gestión completa de productos (CRUD)
- ✅ Gestión completa de clientes (CRUD)
- ✅ Visualización de todas las ventas y facturas
- ✅ Acceso a reportes y estadísticas
- ✅ Configuración del sistema
- ✅ Todas las funcionalidades de usuario

### 👨‍💻 Usuario Registrado
- ✅ Visualización del catálogo completo
- ✅ Búsqueda y filtrado de productos
- ✅ Navegación por categorías
- ✅ Agregar productos al carrito
- ✅ Proceso completo de checkout
- ✅ Gestión de su perfil personal
- ✅ Historial de sus compras
- ✅ Descarga de facturas en XML/ZIP

## Características Técnicas

### Seguridad
- Autenticación robusta mediante sesiones PHP
- Validación y sanitización exhaustiva de datos
- Protección contra inyección SQL (prepared statements)
- Control de acceso basado en roles (RBAC)
- Sanitización de inputs con `htmlspecialchars()`
- Validación de archivos subidos

### Rendimiento
- Carga dinámica de imágenes con lazy loading
- Paginación optimizada de productos
- Consultas SQL optimizadas con índices
- Cache de sesiones y datos frecuentes
- Compresión de archivos ZIP para descargas

### Responsividad y UX
- Bootstrap 5 para diseño completamente adaptativo
- Optimizado para móviles, tablets y desktop
- Interfaz táctil amigable con gestos
- Carga rápida optimizada para dispositivos móviles
- Animaciones CSS3 y transiciones suaves
- Tema oscuro/claro adaptativo

### APIs y Integraciones
- API RESTful para operaciones AJAX
- Integración con PayPal para pagos
- Generación automática de documentos XML
- Sistema de notificaciones en tiempo real
- Contador de carrito dinámico

### Facturación Electrónica
- Generación automática de XML fiscal
- Numeración secuencial de facturas
- Cálculo automático de impuestos panameños (7% ITBMS)
- Creación de imágenes de factura en PNG
- Empaquetado automático en ZIP para descarga
- Cumplimiento con estándares DGI de Panamá

## Uso de la Aplicación

### Para Administradores
1. **Registro/Login**: Registrarse o iniciar sesión
2. **Gestión de Categorías**: 
   - Crear, editar y eliminar categorías
   - Asignar imágenes representativas
3. **Gestión de Productos**: 
   - Agregar productos con imágenes y precios
   - Control de inventario (stock y stock mínimo)
   - Asociar productos con categorías
4. **Gestión de Clientes**: 
   - Ver, editar y eliminar clientes
   - Gestionar información de contacto y facturación
5. **Administración de Ventas**: 
   - Visualizar todas las transacciones
   - Generar reportes de ventas

### Para Usuarios Registrados
1. **Explorar Catálogo**: 
   - Navegar por categorías
   - Buscar productos específicos
   - Usar filtros de precio y categoría
2. **Carrito de Compras**: 
   - Agregar productos al carrito
   - Modificar cantidades
   - Ver totales en tiempo real
3. **Proceso de Compra**: 
   - Completar información de envío (precargada)
   - Seleccionar método de pago
   - Confirmar pedido
4. **Post-Compra**: 
   - Recibir confirmación de compra
   - Descargar factura en formato XML/ZIP
   - Ver historial de compras

### Características del Checkout
- **Paso 1**: Información de envío (datos precargados del perfil)
- **Paso 2**: Método de pago (Tarjeta, PayPal, Contra entrega)
- **Paso 3**: Confirmación y términos (genera XML automáticamente)
- **Validación**: Teléfono formato panameño (+507 6345-6789)
- **Cálculos**: Subtotal + 7% ITBMS + Envío (gratis >$100)

## Novedades y Funcionalidades Avanzadas

### 🛒 Sistema de E-Commerce Completo
- Carrito de compras persistente por usuario
- Proceso de checkout en 3 etapas con validaciones
- Múltiples métodos de pago integrados
- Cálculo automático de impuestos y envío

### 📱 Optimización Móvil Avanzada
- Diseño Mobile-First con Bootstrap 5
- Gestos táctiles para navegación
- Formateo automático de campos (teléfono, tarjeta)
- Experiencia de compra optimizada para móviles

### 🧾 Facturación Electrónica
- Generación automática de XML fiscal
- Creación de facturas visuales en PNG
- Empaquetado automático en ZIP
- Cumplimiento normativo DGI Panamá

### 🔐 Seguridad Avanzada
- Validación exhaustiva en frontend y backend
- Sanitización completa de inputs
- Control de roles granular
- Protección contra ataques comunes

### 📊 Gestión de Inventario
- Control de stock en tiempo real
- Alertas de stock mínimo
- Historial de movimientos de inventario
- Reportes de productos más vendidos

## Resolución de Problemas Comunes

### Error: "MySQL server has gone away"
```bash
# Solución: Reiniciar MySQL en XAMPP y verificar conexión
# Verificar configuración en config/database.php
```

### Error: "Call to undefined function imagecreatetruecolor()"
```bash
# Solución: Habilitar extensión GD en php.ini
extension=gd
# Reiniciar Apache
```

### Error: "Class ZipArchive not found"
```bash
# Solución: Habilitar extensión ZIP en php.ini
extension=zip
# Reiniciar Apache
```

### Problema: Carrito no se actualiza
```bash
# Verificar que JavaScript esté habilitado
# Comprobar consola del navegador para errores
# Verificar que la tabla shopping_cart exista
```

## Contribución

Para contribuir al proyecto:
1. Fork del repositorio
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit de cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## Roadmap y Futuras Mejoras

### 🔮 Próximas Funcionalidades
- [ ] Sistema de cupones y descuentos
- [ ] Integración con más pasarelas de pago
- [ ] Notificaciones push para móviles
- [ ] Sistema de reviews y calificaciones
- [ ] Wishlist/Lista de deseos
- [ ] Comparador de productos
- [ ] Chat de soporte en vivo
- [ ] API REST completa para integraciones

### 📈 Mejoras Planificadas
- [ ] Dashboard de analytics avanzado
- [ ] Sistema de reportes detallados
- [ ] Integración con redes sociales
- [ ] Sistema de afiliados
- [ ] Multi-idioma (ES/EN)
- [ ] Sistema de subscripciones
- [ ] Integración con sistemas contables

## Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## Soporte y Contacto

Para reportar bugs, solicitar funcionalidades o soporte técnico:

- **GitHub Issues**: [Crear Issue](https://github.com/Drek003/DS7-Final/issues)
- **Email**: soporte@ds7final.com
- **Documentación**: Ver carpeta `/docs` para documentación técnica

## Créditos y Reconocimientos



### Tecnologías y Librerías
- Bootstrap 5 - Framework CSS
- Font Awesome 6 - Iconografía
- jQuery 3.4 - Manipulación DOM
- PHP 8+ - Backend
- MySQL - Base de datos

### Inspiración y Referencias
- Mejores prácticas de e-commerce
- Estándares de facturación electrónica de Panamá
- Principios de diseño Material Design y Bootstrap

---

**Versión**: 2.0.0  
**Última actualización**: Julio 2025  
**Estado**: Producción  
**Licencia**: MIT
