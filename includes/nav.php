<aside class="sidebar-nav bg-dark d-flex flex-column p-3" style="width: 250px; min-height: 100vh; position: fixed; top: 0; left: 0; z-index: 1040;">
    <a href="/DS6-2-Catalogo/index.php" class="navbar-brand mb-4 d-flex align-items-center text-white text-decoration-none">
        <i class="fas fa-store fa-lg me-2"></i>
        <span class="fs-4">Catálogo</span>
    </a>
    <hr class="text-secondary">
    <ul class="nav nav-pills flex-column mb-auto gap-1">
        <li class="nav-item">
            <a href="/DS6-2-Catalogo/index.php" class="nav-link text-white">
                <i class="fas fa-home me-2"></i> Inicio
            </a>
        </li>
        <li>
            <a href="/DS6-2-Catalogo/views/categories/index.php" class="nav-link text-white">
                <i class="fas fa-tags me-2"></i> Categorías
            </a>
        </li>
        <li>
            <a href="/DS6-2-Catalogo/views/products/index.php" class="nav-link text-white">
                <i class="fas fa-box me-2"></i> Productos
            </a>
        </li>
        <?php if (isAdmin()): ?>
        <li>
            <a class="nav-link text-white dropdown-toggle" data-bs-toggle="collapse" href="#adminMenu" role="button" aria-expanded="false" aria-controls="adminMenu">
                <i class="fas fa-cog me-2"></i> Administración
            </a>
            <div class="collapse" id="adminMenu">
                <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small ms-3">
                    <li><a href="/DS6-2-Catalogo/views/categories/create.php" class="nav-link text-white"><i class="fas fa-plus me-2"></i> Nueva Categoría</a></li>
                    <li><a href="/DS6-2-Catalogo/views/products/create.php" class="nav-link text-white"><i class="fas fa-plus me-2"></i> Nuevo Producto</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a href="/DS6-2-Catalogo/views/categories/index.php" class="nav-link text-white"><i class="fas fa-edit me-2"></i> Gestionar Categorías</a></li>
                    <li><a href="/DS6-2-Catalogo/views/products/index.php" class="nav-link text-white"><i class="fas fa-edit me-2"></i> Gestionar Productos</a></li>
                </ul>
            </div>
        </li>
        <?php endif; ?>
    </ul>
    <hr class="text-secondary mt-auto">
    <div class="dropdown mt-auto">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=00d4aa&color=fff" alt="avatar" width="36" height="36" class="rounded-circle me-2">
            <span>
                <?php echo $_SESSION['username']; ?>
                <span class="badge bg-<?php echo isAdmin() ? 'danger' : 'info'; ?> ms-1">
                    <?php echo ucfirst($_SESSION['role']); ?>
                </span>
            </span>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="userDropdown">
            <li><h6 class="dropdown-header"><i class="fas fa-info-circle"></i> Información de Usuario</h6></li>
            <li><span class="dropdown-item-text">
                <strong>Usuario:</strong> <?php echo $_SESSION['username']; ?><br>
                <strong>Email:</strong> <?php echo $_SESSION['email']; ?><br>
                <strong>Rol:</strong> <?php echo ucfirst($_SESSION['role']); ?>
            </span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="/DS6-2-Catalogo/views/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
        </ul>
    </div>
    <button class="btn btn-outline-secondary d-lg-none mt-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas">
        <i class="fas fa-bars"></i>
    </button>
</aside>

<!-- Espacio para el sidebar en el layout -->
<style>
    body {
        padding-left: 250px;
    }
    @media (max-width: 991.98px) {
        aside.sidebar-nav {
            position: static;
            width: 100%;
            min-height: auto;
            height: auto;
        }
        body {
            padding-left: 0;
        }
    }
</style>