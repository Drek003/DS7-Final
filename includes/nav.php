<aside class="sidebar-nav bg-dark d-flex flex-column p-3" style="width: 250px; min-height: 100vh; position: fixed; top: 0; left: 0; z-index: 1040;">
    <a href="/DS7-Final/index.php" class="navbar-brand mb-4 d-flex align-items-center text-white text-decoration-none">
        <i class="fas fa-store fa-lg me-2"></i>
        <span class="fs-4">Catálogo</span>
    </a>
    <hr class="text-secondary">
    <ul class="nav nav-pills flex-column mb-auto gap-1">
        <li class="nav-item">
            <a href="/DS7-Final/index.php" class="nav-link text-white">
                <i class="fas fa-home me-2"></i> Inicio
            </a>
        </li>
        <li>
            <a href="/DS7-Final/views/categories/index.php" class="nav-link text-white">
                <i class="fas fa-tags me-2"></i> Categorías
            </a>
        </li>
        <li>
            <a href="/DS7-Final/views/products/index.php" class="nav-link text-white">
                <i class="fas fa-box me-2"></i> Productos
            </a>
        </li>
        <li>
            <a href="/DS7-Final/views/cart/index.php" class="nav-link text-white d-flex align-items-center">
                <i class="fas fa-shopping-cart me-2"></i> 
                <span>Carrito</span>
                <span id="cart-badge" class="badge bg-danger ms-2 cart-counter" style="display: none; animation-duration: 0.3s;">
                    0
                </span>
            </a>
        </li>
        <?php if (isAdmin()): ?>
        <li>
            <a href="/DS7-Final/views/customers/index.php" class="nav-link text-white">
                <i class="fas fa-users me-2"></i> Clientes
            </a>
        </li>
        <?php endif; ?>
        <?php if (isAdmin()): ?>
        <li>
            <a class="nav-link text-white dropdown-toggle" data-bs-toggle="collapse" href="#adminMenu" role="button" aria-expanded="false" aria-controls="adminMenu">
                <i class="fas fa-cog me-2"></i> Administración
            </a>
            <div class="collapse" id="adminMenu">
                <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small ms-3">
                    <li><a href="/DS7-Final/views/categories/create.php" class="nav-link text-white"><i class="fas fa-plus me-2"></i> Nueva Categoría</a></li>
                    <li><a href="/DS7-Final/views/products/create.php" class="nav-link text-white"><i class="fas fa-plus me-2"></i> Nuevo Producto</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a href="/DS7-Final/views/categories/index.php" class="nav-link text-white"><i class="fas fa-edit me-2"></i> Gestionar Categorías</a></li>
                    <li><a href="/DS7-Final/views/products/index.php" class="nav-link text-white"><i class="fas fa-edit me-2"></i> Gestionar Productos</a></li>
                    <li><a href="/DS7-Final/views/customers/index.php" class="nav-link text-white"><i class="fas fa-users-cog me-2"></i> Gestionar Clientes</a></li>
                </ul>
            </div>
        </li>
        <?php endif; ?>
        <?php if (isClient()): ?>
        <li>
            <a href="/DS7-Final/views/customers/profile.php" class="nav-link text-white">
                <i class="fas fa-user-edit me-2"></i> Mi Perfil
            </a>
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
            <li><a class="dropdown-item" href="/DS7-Final/views/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
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

    /* Animaciones para el contador del carrito */
    .cart-counter {
        border-radius: 12px !important;
        font-size: 0.75rem;
        font-weight: 600;
        min-width: 20px;
        height: 20px;
        display: flex !important;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .cart-counter.animate-bounce {
        animation: cartBounce 0.6s ease-out;
    }

    .cart-counter.animate-pulse {
        animation: cartPulse 0.4s ease-out;
    }

    .cart-counter.animate-scale {
        animation: cartScale 0.5s ease-out;
    }

    @keyframes cartBounce {
        0% {
            transform: scale(1);
        }
        25% {
            transform: scale(1.3) rotate(5deg);
        }
        50% {
            transform: scale(1.1) rotate(-3deg);
        }
        75% {
            transform: scale(1.2) rotate(2deg);
        }
        100% {
            transform: scale(1) rotate(0deg);
        }
    }

    @keyframes cartPulse {
        0% {
            transform: scale(1);
            background-color: #dc3545;
        }
        50% {
            transform: scale(1.2);
            background-color: #00d4aa;
            box-shadow: 0 0 10px rgba(0, 212, 170, 0.5);
        }
        100% {
            transform: scale(1);
            background-color: #dc3545;
        }
    }

    @keyframes cartScale {
        0% {
            transform: scale(0.5);
            opacity: 0.5;
        }
        50% {
            transform: scale(1.3);
            opacity: 1;
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    /* Efecto hover mejorado para el enlace del carrito */
    .nav-link:has(.cart-counter):hover .cart-counter {
        background-color: #00d4aa !important;
        transform: scale(1.1);
        box-shadow: 0 2px 8px rgba(0, 212, 170, 0.3);
    }
</style>

<script>
// Función para actualizar el contador del carrito
function updateCartCount(animate = false) {
    fetch('/DS7-Final/views/cart/get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('cart-badge');
            const currentCount = parseInt(badge.textContent) || 0;
            const newCount = data.cart_count || 0;
            
            if (newCount > 0) {
                badge.textContent = newCount;
                badge.style.display = 'flex';
                
                // Aplicar animación si hay cambio en el contador y se solicita
                if (animate && newCount !== currentCount) {
                    // Remover clases de animación anteriores
                    badge.classList.remove('animate-bounce', 'animate-pulse', 'animate-scale');
                    
                    // Determinar tipo de animación según el cambio
                    if (newCount > currentCount) {
                        // Se agregó producto - animación bounce
                        badge.classList.add('animate-bounce');
                        
                        // Remover clase después de la animación
                        setTimeout(() => {
                            badge.classList.remove('animate-bounce');
                        }, 600);
                    } else {
                        // Se removió producto - animación pulse
                        badge.classList.add('animate-pulse');
                        
                        setTimeout(() => {
                            badge.classList.remove('animate-pulse');
                        }, 400);
                    }
                }
            } else {
                // Animación de desaparición cuando llega a 0
                if (animate && currentCount > 0) {
                    badge.classList.add('animate-scale');
                    setTimeout(() => {
                        badge.style.display = 'none';
                        badge.classList.remove('animate-scale');
                    }, 300);
                } else {
                    badge.style.display = 'none';
                }
            }
        })
        .catch(error => console.error('Error updating cart count:', error));
}

// Función para inicio del polling en tiempo real
function startCartPolling() {
    // Actualizar cada 2 segundos
    setInterval(() => {
        updateCartCount(true);
    }, 2000);
}

// Función para detectar cambios y actualizar inmediatamente
function watchCartChanges() {
    let lastCount = 0;
    
    const observer = new MutationObserver(() => {
        const badge = document.getElementById('cart-badge');
        const currentCount = parseInt(badge.textContent) || 0;
        
        if (currentCount !== lastCount) {
            lastCount = currentCount;
            // Disparar evento personalizado
            window.dispatchEvent(new CustomEvent('cartChanged', { 
                detail: { count: currentCount } 
            }));
        }
    });
    
    const badge = document.getElementById('cart-badge');
    if (badge) {
        observer.observe(badge, { childList: true, characterData: true, subtree: true });
    }
}

// Actualizar contador al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
    startCartPolling(); // Iniciar polling en tiempo real
    watchCartChanges(); // Observar cambios en el DOM
});

// Función global para refrescar el carrito con animación (útil desde cart/index.php)
window.refreshCartCount = function() {
    updateCartCount(true);
};

// Event listener para cambios en el carrito
window.addEventListener('cartChanged', function(e) {
    console.log('Cart changed to:', e.detail.count);
    // Aquí puedes agregar lógica adicional cuando cambie el carrito
});

// Función global para agregar al carrito (puede ser llamada desde otras páginas)
window.addToCart = function(productId, quantity = 1) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    fetch('/DS7-Final/views/cart/add_to_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar mensaje de éxito
            showMessage(data.message, 'success');
            // Actualizar contador real desde el servidor con animación
            setTimeout(() => {
                updateCartCount(true);
            }, 100);
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error adding to cart:', error);
        showMessage('Error al agregar el producto al carrito', 'error');
    });
};

// Función para mostrar mensajes
function showMessage(message, type) {
    // Crear elemento de alerta
    const alert = document.createElement('div');
    alert.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alert.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alert);
    
    // Remover automáticamente después de 5 segundos
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}
</script>