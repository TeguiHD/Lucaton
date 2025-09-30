/**
 * JavaScript principal para Lucatón
 * Funcionalidades comunes y utilidades
 */

// Configuración global
const LucatonApp = {
    config: {
        apiUrl: '/Tesis/api',
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        debug: false
    },
    
    // Inicialización
    init() {
        this.setupEventListeners();
        this.setupAjax();
        this.setupTooltips();
        this.setupModals();
        this.setupForms();
        this.setupAnimations();
        
        console.log('Lucatón App initialized');
    },
    
    // Configurar event listeners globales
    setupEventListeners() {
        // Confirmar eliminaciones
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-confirm]')) {
                const message = e.target.getAttribute('data-confirm');
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
        
        // Auto-hide alerts
        document.querySelectorAll('.alert').forEach(alert => {
            if (alert.classList.contains('alert-success')) {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            }
        });
        
        // Smooth scroll para enlaces internos
        document.addEventListener('click', (e) => {
            if (e.target.matches('a[href^="#"]')) {
                e.preventDefault();
                const target = document.querySelector(e.target.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    },
    
    // Configurar AJAX
    setupAjax() {
        // Configurar headers por defecto
        if (typeof $ !== 'undefined') {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': this.config.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            // Manejar errores AJAX globalmente
            $(document).ajaxError((event, xhr, settings, thrownError) => {
                console.error('AJAX Error:', xhr.status, thrownError);
                
                if (xhr.status === 419) {
                    this.showAlert('Tu sesión ha expirado. Por favor, recarga la página.', 'warning');
                } else if (xhr.status === 500) {
                    this.showAlert('Error interno del servidor. Inténtalo de nuevo.', 'danger');
                } else if (xhr.status === 403) {
                    this.showAlert('No tienes permisos para realizar esta acción.', 'warning');
                }
            });
        }
    },
    
    // Configurar tooltips
    setupTooltips() {
        if (typeof bootstrap !== 'undefined') {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        }
    },
    
    // Configurar modales
    setupModals() {
        // Auto-focus en modales
        document.addEventListener('shown.bs.modal', (e) => {
            const firstInput = e.target.querySelector('input, textarea, select');
            if (firstInput) {
                firstInput.focus();
            }
        });
    },
    
    // Configurar formularios
    setupForms() {
        // Validación en tiempo real
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (e) => {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    submitBtn.disabled = true;
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
                    
                    // Re-habilitar después de 5 segundos como fallback
                    setTimeout(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }, 5000);
                }
            });
        });
        
        // Auto-resize para textareas
        document.querySelectorAll('textarea[data-auto-resize]').forEach(textarea => {
            textarea.addEventListener('input', () => {
                textarea.style.height = 'auto';
                textarea.style.height = textarea.scrollHeight + 'px';
            });
        });
    },
    
    // Configurar animaciones
    setupAnimations() {
        // Intersection Observer para animaciones al scroll
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in');
                        observer.unobserve(entry.target);
                    }
                });
            });
            
            document.querySelectorAll('.animate-on-scroll').forEach(el => {
                observer.observe(el);
            });
        }
    },
    
    // Utilidades
    utils: {
        // Formatear números
        formatNumber(num, decimals = 0) {
            return new Intl.NumberFormat('es-ES', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(num);
        },
        
        // Formatear moneda
        formatCurrency(amount) {
            return new Intl.NumberFormat('es-ES', {
                style: 'currency',
                currency: 'USD'
            }).format(amount);
        },
        
        // Formatear fechas
        formatDate(date, options = {}) {
            const defaultOptions = {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            return new Intl.DateTimeFormat('es-ES', { ...defaultOptions, ...options }).format(new Date(date));
        },
        
        // Debounce function
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        // Throttle function
        throttle(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        }
    },
    
    // Mostrar alertas dinámicas
    showAlert(message, type = 'info', duration = 5000) {
        const alertContainer = document.querySelector('.container') || document.body;
        const alertId = 'alert-' + Date.now();
        
        const alertHTML = `
            <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas fa-${this.getAlertIcon(type)} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        alertContainer.insertAdjacentHTML('afterbegin', alertHTML);
        
        // Auto-remove
        if (duration > 0) {
            setTimeout(() => {
                const alert = document.getElementById(alertId);
                if (alert) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            }, duration);
        }
    },
    
    // Obtener icono para alertas
    getAlertIcon(type) {
        const icons = {
            success: 'check-circle',
            danger: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    },
    
    // Cargar contenido dinámico
    loadContent(url, container, showLoading = true) {
        const targetContainer = typeof container === 'string' ? 
            document.querySelector(container) : container;
        
        if (!targetContainer) {
            console.error('Container not found:', container);
            return Promise.reject('Container not found');
        }
        
        if (showLoading) {
            targetContainer.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
        }
        
        return fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.config.csrfToken
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.text();
        })
        .then(html => {
            targetContainer.innerHTML = html;
            // Re-inicializar componentes en el nuevo contenido
            this.setupTooltips();
            return html;
        })
        .catch(error => {
            console.error('Error loading content:', error);
            targetContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Error al cargar el contenido. <a href="javascript:void(0)" onclick="location.reload()">Recargar página</a>
                </div>
            `;
            throw error;
        });
    }
};

// Funciones específicas para campañas
const CampaignManager = {
    // Actualizar progreso de campaña
    updateProgress(campaignId, currentAmount, goalAmount) {
        const percentage = goalAmount > 0 ? Math.min(100, (currentAmount / goalAmount) * 100) : 0;
        const progressBar = document.querySelector(`[data-campaign-id="${campaignId}"] .progress-bar`);
        const percentageText = document.querySelector(`[data-campaign-id="${campaignId}"] .percentage-text`);
        
        if (progressBar) {
            progressBar.style.width = percentage + '%';
            progressBar.setAttribute('aria-valuenow', percentage);
        }
        
        if (percentageText) {
            percentageText.textContent = LucatonApp.utils.formatNumber(percentage, 1) + '%';
        }
    },
    
    // Cargar más campañas (infinite scroll)
    loadMoreCampaigns(page = 1) {
        const container = document.querySelector('#campaigns-container');
        const loadMoreBtn = document.querySelector('#load-more-btn');
        
        if (loadMoreBtn) {
            loadMoreBtn.disabled = true;
            loadMoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Cargando...';
        }
        
        return fetch(`${LucatonApp.config.apiUrl}/campaigns?page=${page}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.campaigns && data.campaigns.length > 0) {
                // Agregar nuevas campañas al contenedor
                data.campaigns.forEach(campaign => {
                    container.insertAdjacentHTML('beforeend', this.renderCampaignCard(campaign));
                });
                
                // Actualizar botón
                if (loadMoreBtn) {
                    if (data.hasMore) {
                        loadMoreBtn.disabled = false;
                        loadMoreBtn.innerHTML = 'Cargar más campañas';
                        loadMoreBtn.setAttribute('data-page', page + 1);
                    } else {
                        loadMoreBtn.style.display = 'none';
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error loading campaigns:', error);
            LucatonApp.showAlert('Error al cargar más campañas', 'danger');
        });
    },
    
    // Renderizar card de campaña
    renderCampaignCard(campaign) {
        const percentage = campaign.goal_amount > 0 ? 
            Math.min(100, (campaign.current_amount / campaign.goal_amount) * 100) : 0;
        
        return `
            <div class="col-lg-4 col-md-6 mb-4" data-campaign-id="${campaign.id}">
                <div class="card h-100 shadow-sm hover-lift">
                    <img src="${campaign.image_url || '/assets/images/placeholder.jpg'}" 
                         class="card-img-top" alt="${campaign.title}" style="height: 200px; object-fit: cover;">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">
                            <a href="/campaigns/${campaign.id}" class="text-decoration-none">
                                ${campaign.title}
                            </a>
                        </h5>
                        <p class="card-text text-muted flex-grow-1">
                            ${campaign.description.substring(0, 120)}...
                        </p>
                        <div class="mb-3">
                            <div class="progress mb-2" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: ${percentage}%"></div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-success fw-bold">
                                    ${LucatonApp.utils.formatCurrency(campaign.current_amount)}
                                </small>
                                <small class="percentage-text">${LucatonApp.utils.formatNumber(percentage, 1)}%</small>
                            </div>
                        </div>
                        <a href="/campaigns/${campaign.id}" class="btn btn-outline-primary btn-sm">
                            Ver Detalles
                        </a>
                    </div>
                </div>
            </div>
        `;
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    LucatonApp.init();
});

// Exportar para uso global
window.LucatonApp = LucatonApp;
window.CampaignManager = CampaignManager;