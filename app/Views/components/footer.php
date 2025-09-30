<footer class="bg-dark text-light py-5 mt-5">
    <div class="container">
        <div class="row">
            <!-- Información de la plataforma -->
            <div class="col-lg-4 col-md-6 mb-4">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-heart me-2 text-primary"></i>
                    Lucatón
                </h5>
                <p class="text-muted">
                    Prototipo académico de crowdfunding desarrollado para la tesis de la Universidad Bernardo O'Higgins. 
                    Reproduce escenarios reales con fines educativos y de investigación.
                </p>
                <div class="d-flex gap-3">
                    <a href="#" class="text-light fs-4"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-light fs-4"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-light fs-4"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-light fs-4"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
            
            <!-- Enlaces rápidos -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="fw-bold mb-3">Enlaces Rápidos</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="<?= Router::url('/') ?>" class="text-muted text-decoration-none">
                            Inicio
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?= Router::url('/campaigns') ?>" class="text-muted text-decoration-none">
                            Campañas
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?= Router::url('/campaigns/create') ?>" class="text-muted text-decoration-none">
                            Crear Campaña
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="text-muted text-decoration-none">
                            Cómo Funciona
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Soporte -->
            <div class="col-lg-3 col-md-6 mb-4">
                <h6 class="fw-bold mb-3">Soporte</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="#" class="text-muted text-decoration-none">
                            Centro de Ayuda
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="text-muted text-decoration-none">
                            Términos y Condiciones
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="text-muted text-decoration-none">
                            Política de Privacidad
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="text-muted text-decoration-none">
                            Contacto
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Contacto -->
            <div class="col-lg-3 col-md-6 mb-4">
                <h6 class="fw-bold mb-3">Contacto</h6>
                <div class="text-muted">
                    <p class="mb-2">
                        <i class="fas fa-envelope me-2"></i>
                        <?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?>
                    </p>
                    <p class="mb-2">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Universidad Bernardo O'Higgins, Santiago de Chile (referencial)
                    </p>
                </div>
            </div>
        </div>
        
        <hr class="my-4 border-secondary">
        
        <!-- Copyright -->
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="text-muted mb-0">
                    &copy; <?= date('Y') ?> <?= htmlspecialchars(PROJECT_OWNER_NAME) ?>. <?= htmlspecialchars(PROJECT_DISCLAIMER) ?>
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="text-muted mb-0">
                    Hecho con <i class="fas fa-heart text-danger"></i> para la comunidad
                </p>
            </div>
        </div>
    </div>
</footer>
