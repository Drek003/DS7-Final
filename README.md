# DS6-2-Catalogo
Proyecto de Desarrollo de software 6 - Catalogo de productos x Categorias + App Movil

# Catálogo de Productos - Sistema de Gestión

## Descripción del Proyecto

Aplicación web móvil que permite gestionar un catálogo completo de productos organizados por categorías. El sistema incluye funcionalidades de visualización, búsqueda, filtrado y administración completa de productos y categorías, con diferentes niveles de usuario y almacenamiento en base de datos local.

## Características Principales

### 🏷️ Gestión de Categorías
- Navegador de categorías con imagen representativa
- Operaciones CRUD completas (crear, ver, editar, eliminar)
- Organización jerárquica de productos

### 📦 Gestión de Productos
- Catálogo completo con nombre, descripción, precio e imágenes
- Asociación de productos con categorías específicas
- Operaciones CRUD completas
- Visualización optimizada para dispositivos móviles

### 🔍 Funcionalidades de Búsqueda
- Navegación por categorías
- Búsqueda por nombre de producto
- Sistema de filtros avanzados por precio y categoría

### 👥 Sistema de Usuarios
- Autenticación con login seguro
- Roles diferenciados: Administradores y Consultores
- Permisos específicos según el rol del usuario

### 📱 Interfaz Móvil
- Diseño responsivo optimizado para dispositivos móviles
- Experiencia de usuario intuitiva y atractiva
- Navegación fluida entre secciones

## Tecnologías Utilizadas

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend**: PHP 8+
- **Base de Datos**: MySQL XAMPP
- **Arquitectura**: MVC (Modelo-Vista-Controlador)

## Estructura del Proyecto

```
proyecto-catalogo/
├── index.php                 # Punto de entrada principal
├── config/
│   ├── database.php         # Configuración de base de datos
│   └── config.php           # Configuraciones generales
├── includes/
│   ├── header.php           # Cabecera común
│   ├── footer.php           # Pie de página común
│   └── nav.php              # Navegación principal
├── controllers/
│   ├── AuthController.php   # Controlador de autenticación
│   ├── CategoryController.php # Controlador de categorías
│   ├── ProductController.php  # Controlador de productos
│   └── UserController.php     # Controlador de usuarios
├── models/
│   ├── User.php             # Modelo de usuario
│   ├── Category.php         # Modelo de categoría
│   └── Product.php          # Modelo de producto
├── views/
│   ├── auth/
│   │   ├── login.php        # Vista de login
│   │   └── register.php     # Vista de registro
│   ├── categories/
│   │   ├── index.php        # Lista de categorías
│   │   ├── create.php       # Crear categoría
│   │   └── edit.php         # Editar categoría
│   ├── products/
│   │   ├── index.php        # Catálogo de productos
│   │   ├── show.php         # Detalle de producto
│   │   ├── create.php       # Crear producto
│   │   └── edit.php         # Editar producto
│   └── dashboard/
│       └── index.php        # Panel principal
├── assets/
│   ├── css/
│   │   ├── bootstrap.min.css
│   │   └── custom.css       # Estilos personalizados
│   ├── js/
│   │   ├── bootstrap.min.js
│   │   ├── jquery.min.js
│   │   └── app.js           # JavaScript personalizado
│   └── images/
│       ├── products/        # Imágenes de productos
│       └── categories/      # Imágenes de categorías
├── uploads/                 # Directorio para subida de archivos
├── sql/
│   └── database.sql         # Script de creación de BD
└── README.md
```

## Base de Datos

### Tabla: users
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'consultor') DEFAULT 'consultor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Tabla: categories
```sql
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Tabla: products
```sql
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    category_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);
```

## Instalación y Configuración

### Requisitos del Sistema
- PHP 8.0 o superior
- MySQL 5.7 o superior / MariaDB 10.3+
- Servidor web (Apache/Nginx)
- Extensiones PHP: mysqli, gd, fileinfo

### Pasos de Instalación

1. **Clonar o descargar el proyecto**
```bash
git clone [url-del-repositorio]
cd proyecto-catalogo
```

2. **Configurar la base de datos**
```bash
# Crear base de datos
mysql -u root -p
CREATE DATABASE catalogo_productos;
USE catalogo_productos;
SOURCE sql/database.sql;
```

3. **Configurar conexión a BD**
```php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'catalogo_productos');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
```

4. **Configurar permisos**
```bash
chmod 755 uploads/
chmod 755 assets/images/
```

5. **Acceder a la aplicación**
- URL: `http://localhost/proyecto-catalogo`
- Usuario admin por defecto: `admin / admin123`

## Funcionalidades por Rol

### 👨‍💼 Administrador
- ✅ Gestión completa de categorías (CRUD)
- ✅ Gestión completa de productos (CRUD)
- ✅ Gestión de usuarios
- ✅ Acceso a todas las funcionalidades

### 👨‍💻 Consultor
- ✅ Visualización de catálogo
- ✅ Búsqueda y filtrado de productos
- ✅ Navegación por categorías
- ❌ Sin permisos de edición

## Características Técnicas

### Seguridad
- Autenticación mediante sesiones PHP
- Validación y sanitización de datos
- Protección contra inyección SQL (prepared statements)
- Control de acceso basado en roles

### Rendimiento
- Carga dinámica de imágenes
- Paginación de productos
- Optimización de consultas SQL
- Cache de categorías frecuentes

### Responsividad
- Bootstrap 5 para diseño adaptativo
- Optimizado para móviles y tablets
- Interfaz táctil amigable
- Carga rápida en dispositivos móviles

## Uso de la Aplicación

### Para Administradores
1. Iniciar sesión con credenciales de administrador
2. Gestionar categorías desde el panel admin
3. Agregar/editar productos con imágenes
4. Administrar usuarios del sistema

### Para Consultores
1. Iniciar sesión con credenciales de consultor
2. Navegar por el catálogo de productos
3. Usar filtros de búsqueda
4. Visualizar detalles de productos

## Contribución

Para contribuir al proyecto:
1. Fork del repositorio
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit de cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## Soporte

Para reportar bugs o solicitar funcionalidades, crear un issue en el repositorio del proyecto.

---

**Versión**: 1.0.0  
**Última actualización**: Mayo 2025