<?php
$currentUser = SessionHelper::get('user_id') ? User::find(SessionHelper::get('user_id')) : null;
$isLoggedIn = $currentUser !== null;
$isAdmin = $isLoggedIn && $currentUser->role === 'admin';
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <!-- Logo y marca -->
        <a class="navbar-brand fw-bold" href="<?= Router::url('/') ?>">
            <i class="fas fa-heart me-2"></i>
            Lucatón
        </a>
        
        <!-- Botón para móvil -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Menú de navegación -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Menú izquierdo -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= Router::url('/') ?>">
                        <i class="fas fa-home me-1"></i>
                        Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= Router::url('/campaigns') ?>">
                        <i class="fas fa-list me-1"></i>
                        Campañas
                    </a>
                </li>
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= Router::url('/campaigns/create') ?>">
                            <i class="fas fa-plus me-1"></i>
                            Crear Campaña
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <!-- Menú derecho -->
            <ul class="navbar-nav">
                <?php if ($isLoggedIn): ?>
                    <!-- Menú de usuario autenticado -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?= htmlspecialchars($currentUser->first_name) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?= Router::url('/dashboard') ?>">
                                    <i class="fas fa-tachometer-alt me-2"></i>
                                    Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= Router::url('/profile') ?>">
                                    <i class="fas fa-user me-2"></i>
                                    Mi Perfil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= Router::url('/donations') ?>">
                                    <i class="fas fa-heart me-2"></i>
                                    Mis Donaciones
                                </a>
                            </li>
                            <?php if ($isAdmin): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?= Router::url('/admin') ?>">
                                        <i class="fas fa-cog me-2"></i>
                                        Administración
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="<?= Router::url('/logout') ?>" class="d-inline">
                                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $_SESSION[CSRF_TOKEN_NAME] ?? '' ?>">
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-sign-out-alt me-2"></i>
                                        Cerrar Sesión
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Menú para usuarios no autenticados -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= Router::url('/login') ?>">
                            <i class="fas fa-sign-in-alt me-1"></i>
                            Iniciar Sesión
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light ms-2" href="<?= Router::url('/register') ?>">
                            <i class="fas fa-user-plus me-1"></i>
                            Registrarse
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>