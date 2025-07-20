// Funciones del carrito de compras

// Agregar producto al carrito con animación
function addToCartWithAnimation(productId, quantity = 1, button) {
    // Deshabilitar botón temporalmente
    if (button) {
        button.disabled = true;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';
    }

    window.addToCart(productId, quantity);

    // Restaurar botón después de un momento
    if (button) {
        setTimeout(() => {
            button.disabled = false;
            button.innerHTML = originalText;
        }, 1500);
    }
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
    if (quantity < 1 || quantity > 99) {
        showMessage('La cantidad debe estar entre 1 y 99', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update_quantity');
    formData.append('cart_id', cartId);
    formData.append('quantity', quantity);

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
        console.error('Error updating quantity:', error);
        showMessage('Error al actualizar la cantidad', 'error');
    });
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
