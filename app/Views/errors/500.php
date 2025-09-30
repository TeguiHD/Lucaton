<?php
$title = '500 - Error del servidor - Lucatón';
$description = 'Ha ocurrido un error interno en el servidor';
$bodyClass = 'bg-light';

ob_start();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8 text-center">
            <!-- Ilustración del error -->
            <div class="mb-4">
                <i class="fas fa-exclamation-triangle fa-5x text-warning mb-3"></i>
                <h1 class="display-1 fw-bold text-danger">500</h1>
            </div>
            
            <!-- Mensaje principal -->
            <h2 class="h3 mb-3">Error interno del servidor</h2>
            <p class="text-muted mb-4 lead">
                Ha ocurrido un error inesperado en nuestros servidores. 
                Nuestro equipo técnico ha sido notificado y está trabajando para solucionarlo.
            </p>
            
            <!-- Acciones sugeridas -->
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center mb-5">
                <button onclick="location.reload()" class="btn btn-primary">
                    <i class="fas fa-redo me-2"></i>
                    Intentar de Nuevo
                </button>
                <a href="<?= Router::url('/') ?>" class="btn btn-outline-primary">
                    <i class="fas fa-home me-2"></i>
                    Ir al Inicio
                </a>
                <button onclick="history.back()" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver Atrás
                </button>
            </div>
            
            <!-- Información adicional -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">¿Qué puedes hacer?</h5>
                    <div class="row g-3 text-start">
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-clock text-primary me-3 mt-1"></i>
                                <div>
                                    <strong>Espera unos minutos</strong>
                                    <p class="text-muted mb-0 small">
                                        El problema podría ser temporal. Intenta recargar la página en unos minutos.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-envelope text-info me-3 mt-1"></i>
                                <div>
                                    <strong>Contacta al soporte</strong>
                                    <p class="text-muted mb-0 small">
                                        Si el problema persiste, puedes contactar a nuestro equipo de soporte.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-info-circle text-success me-3 mt-1"></i>
                                <div>
                                    <strong>Revisa el estado del servicio</strong>
                                    <p class="text-muted mb-0 small">
                                        Visita nuestra página de estado para ver si hay mantenimientos programados.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Información de contacto -->
            <div class="mt-4">
                <p class="text-muted small">
                    <strong>Código de error:</strong> 500 - Internal Server Error<br>
                    <strong>Hora:</strong> <?= date('Y-m-d H:i:s') ?><br>
                    Si necesitas ayuda, incluye esta información al contactar soporte.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Script para auto-refresh -->
<?php
$additionalScripts = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Auto-refresh después de 30 segundos (opcional)
    let countdown = 30;
    const refreshBtn = document.querySelector(".btn-primary");
    
    function updateCountdown() {
        if (countdown > 0) {
            refreshBtn.innerHTML = `<i class="fas fa-redo me-2"></i>Intentar de Nuevo (${countdown}s)`;
            countdown--;
            setTimeout(updateCountdown, 1000);
        } else {
            refreshBtn.innerHTML = `<i class="fas fa-redo me-2"></i>Intentar de Nuevo`;
            refreshBtn.classList.add("btn-pulse");
        }
    }
    
    // Iniciar countdown solo si el usuario no ha interactuado
    let userInteracted = false;
    document.addEventListener("click", () => userInteracted = true);
    document.addEventListener("keydown", () => userInteracted = true);
    
    setTimeout(() => {
        if (!userInteracted) {
            updateCountdown();
        }
    }, 5000);
});
</script>

<style>
.btn-pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
</style>
';

$content = ob_get_clean();
include VIEWS_PATH . '/layouts/main.php';
?>