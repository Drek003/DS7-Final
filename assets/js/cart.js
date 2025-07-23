// Funciones del carrito de compras

// Función simple de respaldo para agregar al carrito
function simpleAddToCart(productId, quantity = 1, button) {
    console.log('🔧 Función simple activada');
    
    if (!button) return;
    
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';
    
    setTimeout(() => {
        button.innerHTML = '<i class="fas fa-check"></i> ¡Agregado!';
        button.style.backgroundColor = '#28a745';
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.style.backgroundColor = '';
            button.disabled = false;
        }, 1500);
    }, 500);
}

// Agregar producto al carrito con animación
function addToCartWithAnimation(productId, quantity = 1, button) {
    console.log('🛒 Iniciando función addToCart');
    
    if (!button) {
        console.error('❌ No se proporcionó el botón');
        return;
    }
    
    // Guardar estado original del botón
    const originalText = button.innerHTML;
    const originalClasses = button.className;
    
    // Deshabilitar botón y mostrar estado de carga
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';
    
    console.log('🎯 Cambiando a estado de éxito INMEDIATAMENTE');
    
    // Cambiar inmediatamente a estado de éxito (sin esperar AJAX)
    setTimeout(() => {
        button.innerHTML = '<i class="fas fa-check"></i> ¡Producto Agregado!';
        button.className = button.className.replace('btn-primary', 'btn-success');
        
        // Hacer la petición AJAX en segundo plano
        submitToBackend(productId, quantity);
        
        // Restaurar botón después de 2 segundos
        setTimeout(() => {
            button.innerHTML = originalText;
            button.className = originalClasses;
            button.disabled = false;
        }, 2000);
        
    }, 500); // Solo esperar 500ms antes de mostrar éxito
}

// Función para hacer la petición AJAX en segundo plano
function submitToBackend(productId, quantity) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    
    // Determinar la ruta correcta
    let cartPath = '../cart/add_to_cart.php';
    if (window.location.pathname.includes('/cart/')) {
        cartPath = 'add_to_cart.php';
    } else if (window.location.pathname.includes('/products/')) {
        cartPath = '../cart/add_to_cart.php';
    } else {
        cartPath = './views/cart/add_to_cart.php';
    }
    
    console.log('📡 Enviando petición en segundo plano a:', cartPath);
    
    fetch(cartPath, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('✅ Respuesta del servidor:', data);
        
        // Actualizar contador del carrito si existe
        if (data.success && window.updateCartCount) {
            window.updateCartCount(true);
        }
    })
    .catch(error => {
        console.error('❌ Error en segundo plano:', error);
    });
}

// Función para mostrar detalles rápidos del producto
function showQuickView(productId) {
    // Esta función puede expandirse para mostrar un modal con detalles del producto
    fetch(`/DS7-Final/api/product_details.php?id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar modal con detalles (implementar según necesidades)
                console.log('Product details:', data.product);
            }
        })
        .catch(error => console.error('Error fetching product details:', error));
}

// Validar cantidad antes de agregar al carrito
function validateQuantityAndAdd(productId, quantityInput, button) {
    const quantity = parseInt(quantityInput.value);
    
    if (quantity < 1) {
        showMessage('La cantidad debe ser al menos 1', 'error');
        quantityInput.value = 1;
        return;
    }
    
    if (quantity > 99) {
        showMessage('La cantidad máxima es 99', 'error');
        quantityInput.value = 99;
        return;
    }
    
    addToCartWithAnimation(productId, quantity, button);
}

// Actualizar cantidad en el carrito
function updateCartQuantity(cartId, quantity) {
    if (quantity < 1) {
        showMessage('La cantidad debe ser al menos 1', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('cart_id', cartId);
    formData.append('quantity', quantity);

    // Usar el endpoint específico para actualizar cantidad con validación de stock
    fetch('update_quantity.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar la interfaz sin recargar la página
            updateCartDisplay(data);
            showMessage(data.message, 'success');
        } else {
            showMessage(data.message, 'error');
            // Si hay error de stock, restaurar la cantidad anterior
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error updating quantity:', error);
        showMessage('Error al actualizar la cantidad', 'error');
        location.reload();
    });
}

// Función para actualizar la interfaz del carrito
function updateCartDisplay(data) {
    // Actualizar el subtotal
    const subtotalElement = document.querySelector('#cart-subtotal');
    if (subtotalElement) {
        subtotalElement.textContent = data.subtotal_formatted;
    }
    
    // Actualizar el impuesto
    const taxElement = document.querySelector('#cart-tax');
    if (taxElement) {
        taxElement.textContent = data.tax_formatted;
    }
    
    // Actualizar el envío
    const shippingElement = document.querySelector('#cart-shipping');
    if (shippingElement) {
        shippingElement.textContent = data.shipping_formatted;
    }
    
    // Actualizar el total
    const totalElement = document.querySelector('#cart-total');
    if (totalElement) {
        totalElement.textContent = data.total_formatted;
    }
    
    // Actualizar contador de items si existe
    if (window.updateCartCount && data.total_items) {
        window.updateCartCount(false, data.total_items);
    }
}

// Remover item del carrito con confirmación
function removeFromCart(cartId, productName) {
    if (confirm(`¿Estás seguro de que quieres eliminar "${productName}" del carrito?`)) {
        const formData = new FormData();
        formData.append('action', 'remove_item');
        formData.append('cart_id', cartId);

        fetch('/DS7-Final/views/cart/index.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                location.reload(); // Recargar la página para mostrar cambios
            }
        })
        .catch(error => {
            console.error('Error removing item:', error);
            showMessage('Error al eliminar el producto', 'error');
        });
    }
}

// Vaciar carrito completo
function clearCart() {
    if (confirm('¿Estás seguro de que quieres vaciar todo el carrito?')) {
        const formData = new FormData();
        formData.append('action', 'clear_cart');

        fetch('/DS7-Final/views/cart/index.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                location.reload(); // Recargar la página para mostrar cambios
            }
        })
        .catch(error => {
            console.error('Error clearing cart:', error);
            showMessage('Error al vaciar el carrito', 'error');
        });
    }
}

// Ir al checkout
function goToCheckout() {
    window.location.href = '/DS7-Final/views/cart/checkout.php';
}

// Continuar comprando
function continueShopping() {
    window.location.href = '/DS7-Final/views/products/index.php';
}

// Formatear precio
function formatPrice(price) {
    return '$' + parseFloat(price).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Calcular total del carrito (lado cliente para validación)
function calculateCartTotal() {
    let subtotal = 0;
    const cartItems = document.querySelectorAll('.cart-item');
    
    cartItems.forEach(item => {
        const quantity = parseInt(item.querySelector('.quantity-input').value);
        const price = parseFloat(item.querySelector('.item-price').dataset.price);
        subtotal += quantity * price;
    });
    
    const taxRate = 0.07;
    const taxAmount = subtotal * taxRate;
    const total = subtotal + taxAmount;
    
    return {
        subtotal: subtotal,
        tax: taxAmount,
        total: total
    };
}

// Animación para botones del carrito
document.addEventListener('DOMContentLoaded', function() {
    // Animar botones de agregar al carrito
    const addToCartButtons = document.querySelectorAll('.btn-add-to-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Efecto de onda
            const ripple = document.createElement('span');
            const rect = button.getBoundingClientRect();
            const size = Math.max(rect.height, rect.width);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(255,255,255,0.6);
                transform: scale(0);
                animation: ripple 0.6s linear;
                left: ${x}px;
                top: ${y}px;
                width: ${size}px;
                height: ${size}px;
            `;
            
            button.style.position = 'relative';
            button.style.overflow = 'hidden';
            button.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
    
    // Actualizar contador del carrito al cargar
    if (typeof updateCartCount === 'function') {
        updateCartCount();
    }
    
    // Inicializar funcionalidad de scroll para el carrito
    initCartScroll();
});

// CSS para la animación de ripple
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Función para manejar el scroll del contenedor del carrito
function initCartScroll() {
    const cartContainer = document.getElementById('cartProductsContainer');
    const scrollIndicator = document.querySelector('.scroll-indicator');
    const scrollDownBtn = document.querySelector('.scroll-down-btn button');
    
    if (!cartContainer) {
        console.log('ℹ️ Contenedor del carrito no encontrado - probablemente no estamos en la página del carrito');
        return;
    }
    
    console.log('🛒 Inicializando funcionalidad de scroll del carrito');
    
    // Función para verificar si hay scroll disponible
    function hasScroll() {
        return cartContainer.scrollHeight > cartContainer.clientHeight;
    }
    
    // Función para mostrar/ocultar indicadores
    function updateScrollIndicators() {
        const isAtTop = cartContainer.scrollTop === 0;
        const isAtBottom = cartContainer.scrollTop + cartContainer.clientHeight >= cartContainer.scrollHeight - 5;
        const hasScrollContent = hasScroll();
        
        // Mostrar indicador solo si hay scroll y estamos en la parte superior
        if (scrollIndicator) {
            if (hasScrollContent && isAtTop) {
                scrollIndicator.style.display = 'block';
                cartContainer.classList.remove('scrolled');
            } else {
                scrollIndicator.style.display = 'none';
                cartContainer.classList.add('scrolled');
            }
        }
        
        // Mostrar botón de scroll hacia abajo solo si hay scroll y no estamos en la parte inferior
        if (scrollDownBtn) {
            const btnContainer = scrollDownBtn.parentElement;
            if (hasScrollContent && !isAtBottom) {
                btnContainer.style.display = 'block';
            } else {
                btnContainer.style.display = 'none';
            }
        }
    }
    
    // Evento de scroll
    cartContainer.addEventListener('scroll', function() {
        updateScrollIndicators();
        
        // Añadir clase para animaciones
        cartContainer.classList.add('scrolling');
        
        // Remover clase después de un tiempo
        clearTimeout(cartContainer.scrollTimeout);
        cartContainer.scrollTimeout = setTimeout(() => {
            cartContainer.classList.remove('scrolling');
        }, 150);
    });
    
    // Evento del botón de scroll hacia abajo
    if (scrollDownBtn) {
        scrollDownBtn.addEventListener('click', function() {
            const scrollAmount = cartContainer.clientHeight * 0.8; // Scroll 80% de la altura visible
            cartContainer.scrollBy({
                top: scrollAmount,
                behavior: 'smooth'
            });
        });
    }
    
    // Inicializar indicadores
    updateScrollIndicators();
    
    // Observer para cambios en el contenido
    const observer = new MutationObserver(function() {
        setTimeout(updateScrollIndicators, 100); // Pequeño delay para que se actualice el DOM
    });
    
    observer.observe(cartContainer, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['style']
    });
    
    // Actualizar en resize de ventana
    window.addEventListener('resize', updateScrollIndicators);
    
    console.log('✅ Funcionalidad de scroll del carrito inicializada correctamente');
}

// Función de utilidad para scroll suave hacia un elemento específico
function scrollToCartItem(itemId) {
    const cartContainer = document.getElementById('cartProductsContainer');
    const targetItem = document.querySelector(`[data-product-id="${itemId}"]`);
    
    if (cartContainer && targetItem) {
        const itemTop = targetItem.offsetTop;
        const containerTop = cartContainer.offsetTop;
        const scrollPosition = itemTop - containerTop - 20; // 20px de margen
        
        cartContainer.scrollTo({
            top: scrollPosition,
            behavior: 'smooth'
        });
    }
}
