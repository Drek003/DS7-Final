// JavaScript personalizado para la aplicación

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Inicializar popovers de Bootstrap
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Función para previsualizar imágenes antes de subir
    function previewImage(input, previewId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(previewId).src = e.target.result;
                document.getElementById(previewId).style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Event listener para inputs de archivo
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const previewId = this.getAttribute('data-preview');
            if (previewId) {
                previewImage(this, previewId);
            }
        });
    });

    // Función para confirmar eliminaciones
    window.confirmDelete = function(message) {
        return confirm(message || '¿Estás seguro de que quieres eliminar este elemento?');
    };

    // Función para mostrar loading en botones
    window.showLoading = function(button, text) {
        const originalText = button.innerHTML;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>' + (text || 'Cargando...');
        button.disabled = true;
        
        return function() {
            button.innerHTML = originalText;
            button.disabled = false;
        };
    };

    // Auto-hide alerts después de 5 segundos
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Función para formatear precios
    window.formatPrice = function(price) {
        return new Intl.NumberFormat('es-ES', {
            style: 'currency',
            currency: 'USD'
        }).format(price);
    };

    // Función para validar formularios
    window.validateForm = function(formId) {
        const form = document.getElementById(formId);
        if (!form) return false;

        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;

        inputs.forEach(function(input) {
            if (!input.value.trim()) {
                input.classList.add('is-invalid');
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
            }
        });

        return isValid;
    };

    // Event listener para validación en tiempo real
    const requiredInputs = document.querySelectorAll('input[required], select[required], textarea[required]');
    requiredInputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });
    });

    // Función para búsqueda en tiempo real
    window.setupLiveSearch = function(inputId, targetSelector) {
        const searchInput = document.getElementById(inputId);
        if (!searchInput) return;

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const items = document.querySelectorAll(targetSelector);

            items.forEach(function(item) {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    };

    // Función para lazy loading de imágenes
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        const lazyImages = document.querySelectorAll('img[data-src]');
        lazyImages.forEach(function(img) {
            imageObserver.observe(img);
        });
    }

    // Función para manejar el estado offline
    window.addEventListener('online', function() {
        showNotification('Conexión restaurada', 'success');
    });

    window.addEventListener('offline', function() {
        showNotification('Sin conexión a internet', 'warning');
    });

    // Función para mostrar notificaciones
    window.showNotification = function(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alertDiv);
        
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alertDiv);
            bsAlert.close();
        }, 5000);
    };

    // Función para manejar formularios con AJAX
    window.submitFormAjax = function(formId, successCallback) {
        const form = document.getElementById(formId);
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const hideLoading = showLoading(submitBtn);

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showNotification(data.message || 'Operación exitosa', 'success');
                    if (successCallback) successCallback(data);
                } else {
                    showNotification(data.message || 'Error en la operación', 'danger');
                }
            })
            .catch(error => {
                hideLoading();
                showNotification('Error de conexión', 'danger');
                console.error('Error:', error);
            });
        });
    };

    // Inicializar funcionalidades específicas según la página
    const currentPage = window.location.pathname;
    
    if (currentPage.includes('products')) {
        // Funcionalidades específicas para productos
        setupLiveSearch('search', '.product-card');
    }
    
    if (currentPage.includes('categories')) {
        // Funcionalidades específicas para categorías
        setupLiveSearch('search', '.category-card');
    }
});

// Función para detectar dispositivos móviles
window.isMobile = function() {
    return window.innerWidth <= 768;
};

// Función para scroll suave
window.smoothScroll = function(target) {
    document.querySelector(target).scrollIntoView({
        behavior: 'smooth'
    });
};

// Service Worker para funcionalidad offline (opcional)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js')
            .then(function(registration) {
                console.log('SW registrado con éxito:', registration.scope);
            })
            .catch(function(error) {
                console.log('SW falló al registrarse:', error);
            });
    });
}