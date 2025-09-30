<?php
$title = '404 - Página no encontrada - Lucatón';
$description = 'La página que buscas no existe o ha sido movida';
$bodyClass = 'bg-light';

ob_start();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8 text-center">
            <!-- Ilustración del error -->
            <div class="mb-4">
                <i class="fas fa-search fa-5x text-muted mb-3"></i>
                <h1 class="display-1 fw-bold text-primary">404</h1>
            </div>
            
            <!-- Mensaje principal -->
            <h2 class="h3 mb-3">¡Oops! Página no encontrada</h2>
            <p class="text-muted mb-4 lead">
                La página que estás buscando no existe, ha sido movida o el enlace es incorrecto.
            </p>
            
            <!-- Acciones sugeridas -->
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center mb-5">
                <a href="<?= Router::url('/') ?>" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i>
                    Ir al Inicio
                </a>
                <a href="<?= Router::url('/campaigns') ?>" class="btn btn-outline-primary">
                    <i class="fas fa-list me-2"></i>
                    Ver Campañas
                </a>
                <button onclick="history.back()" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver Atrás
                </button>
            </div>
            
            <!-- Enlaces útiles -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">¿Necesitas ayuda?</h5>
                    <p class="card-text text-muted">
                        Aquí tienes algunos enlaces que podrían ser útiles:
                    </p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="<?= Router::url('/campaigns') ?>" class="text-decoration-none">
                                <i class="fas fa-rocket text-primary me-2"></i>
                                Explorar Campañas
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="<?= Router::url('/campaigns/create') ?>" class="text-decoration-none">
                                <i class="fas fa-plus text-success me-2"></i>
                                Crear Campaña
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="#" class="text-decoration-none">
                                <i class="fas fa-question-circle text-info me-2"></i>
                                Centro de Ayuda
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="#" class="text-decoration-none">
                                <i class="fas fa-envelope text-warning me-2"></i>
                                Contactar Soporte
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script para búsqueda -->
<?php
$additionalScripts = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Auto-focus en el botón principal después de 2 segundos
    setTimeout(function() {
        const homeBtn = document.querySelector(".btn-primary");
        if (homeBtn) {
            homeBtn.focus();
        }
    }, 2000);
});
</script>
';

$content = ob_get_clean();
include VIEWS_PATH . '/layouts/main.php';
?>