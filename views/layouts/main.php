<?php 
$current_page = $current_page ?? '';
$is_authenticated = isset($_SESSION['user_id']);
$notification_api_url = $is_authenticated ? Router::url('api/notifications') : null;
$notification_mark_url = $is_authenticated ? Router::url('api/notifications/mark-read') : null;
$notification_csrf = $is_authenticated ? SessionHelper::getCSRFToken() : null;
?>
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $meta_description ?? 'Lucatón - Plataforma de crowdfunding ética con asistencia de IA para campañas de impacto social en Chile' ?>">
    <meta name="keywords" content="crowdfunding, chile, inteligencia artificial, campañas sociales, donaciones">
    <meta name="author" content="Lucatón">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= APP_URL . $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:title" content="<?= $page_title ?? 'Lucatón' ?>">
    <meta property="og:description" content="<?= $meta_description ?? 'Plataforma de crowdfunding ética con IA' ?>">
    <meta property="og:image" content="<?= APP_URL ?>/public/assets/images/og-image.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= APP_URL . $_SERVER['REQUEST_URI'] ?>">
    <meta property="twitter:title" content="<?= $page_title ?? 'Lucatón' ?>">
    <meta property="twitter:description" content="<?= $meta_description ?? 'Plataforma de crowdfunding ética con IA' ?>">
    <meta property="twitter:image" content="<?= APP_URL ?>/public/assets/images/og-image.jpg">

    <title><?= $page_title ?? 'Lucatón' ?> - Crowdfunding Ético con IA</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/public/assets/images/favicon.svg">
    
    <!-- CSS -->
    <link href="<?= APP_URL ?>/public/assets/css/app.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/public/assets/css/aliases.css" rel="stylesheet">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="<?= APP_URL ?>/public/assets/css/app.css" as="style">
    
    <!-- Additional head content -->
    <?= $additional_head ?? '' ?>
</head>
<body class="h-full bg-gray-50 font-sans antialiased">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <div class="min-h-full flex flex-col">
        <!-- Header -->
        <header data-sticky-header class="sticky top-0 inset-x-0 z-50 transform transition-transform duration-300 ease-out will-change-transform backdrop-blur supports-[backdrop-filter]:bg-white/70 bg-white/90 shadow-sm border-b border-gray-200" role="banner">
            <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" aria-label="Navegación principal">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <div class="flex-shrink-0">
                        <a href="<?= Router::url('/') ?>" class="flex items-center space-x-2 group">
                            <div class="w-8 h-8 bg-gradient-to-br from-copihue-500 to-copihue-600 rounded-lg flex items-center justify-center shadow-soft">
                                <span class="text-white font-bold text-lg">L</span>
                            </div>
                            <span class="text-xl font-bold text-gray-900 group-hover:text-copihue-600 transition-colors">
                                Lucatón
                            </span>
                        </a>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-4">
                            <a href="<?= Router::url('/') ?>" class="nav-link hover:shadow-sm hover:scale-[1.02] transition-transform <?= $current_page === 'home' ? 'nav-link-active' : '' ?>">
                                Inicio
                            </a>
                            <a href="<?= Router::url('campanas') ?>" class="nav-link hover:shadow-sm hover:scale-[1.02] transition-transform <?= $current_page === 'campaigns' ? 'nav-link-active' : '' ?>">
                                Campañas
                            </a>
                            <a href="<?= Router::url('noticias') ?>" class="nav-link hover:shadow-sm hover:scale-[1.02] transition-transform <?= $current_page === 'news' ? 'nav-link-active' : '' ?>">
                                Noticias
                            </a>
                            <a href="<?= Router::url('faq') ?>" class="nav-link hover:shadow-sm hover:scale-[1.02] transition-transform <?= $current_page === 'faq' ? 'nav-link-active' : '' ?>">
                                Preguntas Frecuentes
                            </a>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="<?= Router::url('panel') ?>" class="nav-link <?= $current_page === 'dashboard' ? 'nav-link-active' : '' ?>">
                                    Mi Panel
                                </a>
                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                    <a href="<?= Router::url('admin') ?>" class="nav-link <?= $current_page === 'admin' ? 'nav-link-active' : '' ?>">
                                        Administración
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- User Menu / Auth Buttons -->
                    <div class="hidden md:block">
                        <div class="ml-4 flex items-center md:ml-6">
                            <?php if ($is_authenticated): ?>
                                <div class="relative mr-2">
                                    <button
                                        data-toggle="notifications-menu"
                                        data-notification-trigger
                                        data-endpoint="<?= htmlspecialchars($notification_api_url) ?>"
                                        data-read-endpoint="<?= htmlspecialchars($notification_mark_url) ?>"
                                        data-csrf-name="<?= CSRF_TOKEN_NAME ?>"
                                        data-csrf-value="<?= htmlspecialchars($notification_csrf ?? '') ?>"
                                        class="relative bg-white p-1 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500"
                                        aria-label="Ver notificaciones">
                                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                        </svg>
                                        <span data-notification-count class="hidden absolute -top-1 -right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-semibold leading-none text-white bg-red-500 rounded-full"></span>
                                    </button>
                                    <div
                                        data-menu="notifications-menu"
                                        class="hidden fixed inset-x-0 top-[4rem] z-[60] px-4 sm:absolute sm:inset-auto sm:right-0 sm:top-full sm:mt-3 sm:px-0">
                                        <div class="mx-auto w-full max-w-md sm:mx-0 sm:w-80 sm:max-w-none overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-strong ring-1 ring-black/10">
                                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                                <h3 class="text-sm font-semibold text-gray-900">Notificaciones</h3>
                                            </div>
                                            <div class="max-h-[65vh] sm:max-h-80 overflow-y-auto">
                                                <div data-notification-spinner class="py-6 text-center text-sm text-gray-500">Cargando...</div>
                                                <div data-notification-error class="hidden py-6 text-center text-sm text-red-500"></div>
                                                <div data-notification-empty class="hidden py-6 text-center text-sm text-gray-500">No tienes notificaciones.</div>
                                                <ul data-notification-list class="hidden divide-y divide-gray-100"></ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="ml-3 relative">
                                    <button data-toggle="user-menu" type="button" class="max-w-xs bg-white flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" id="user-menu-button" aria-haspopup="true" aria-expanded="false">
                                        <span class="sr-only">Abrir menú de usuario</span>
                                        <div class="h-8 w-8 rounded-full bg-copihue-100 flex items-center justify-center">
                                            <span class="text-sm font-medium text-copihue-700">
                                                <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                                            </span>
                                        </div>
                                    </button>

                                    <div data-menu="user-menu"
                                         class="hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black/10 focus:outline-none z-50" 
                                         role="menu" 
                                         aria-orientation="vertical"
                                         aria-labelledby="user-menu-button">
                                        <a href="<?= Router::url('perfil') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                            Mi Perfil
                                        </a>
                                        <a href="<?= Router::url('panel') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                            Mi Panel
                                        </a>
                                        <form method="POST" action="<?= Router::url('logout') ?>" class="block">
                                            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars($notification_csrf ?? SessionHelper::getCSRFToken()) ?>">
                                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                                Cerrar Sesión
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Auth buttons -->
                                <div class="header-auth-buttons items-center space-x-4">
                                    <a href="<?= Router::url('login') ?>" class="btn-secondary">
                                        Iniciar Sesión
                                    </a>
                                    <a href="<?= Router::url('registro') ?>" class="btn-primary">
                                        Registrarse
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="md:hidden">
                        <button data-toggle="mobile-menu"
                                class="bg-white inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-copihue-500"
                                aria-controls="mobile-menu"
                                aria-expanded="false">
                            <span class="sr-only">Abrir menú principal</span>
                            <svg data-toggle-icon="open" class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <svg data-toggle-icon="close" class="hidden h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Mobile menu -->
                <div class="md:hidden hidden mobile-menu-panel" id="mobile-menu">
                    <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 border-t border-gray-200">
                        <a href="<?= Router::url('/') ?>" class="mobile-nav-link <?= $current_page === 'home' ? 'mobile-nav-link-active' : '' ?>">
                            Inicio
                        </a>
                        <a href="<?= Router::url('campanas') ?>" class="mobile-nav-link <?= $current_page === 'campaigns' ? 'mobile-nav-link-active' : '' ?>">
                            Campañas
                        </a>
                        <a href="<?= Router::url('noticias') ?>" class="mobile-nav-link <?= $current_page === 'news' ? 'mobile-nav-link-active' : '' ?>">
                            Noticias
                        </a>
                        <a href="<?= Router::url('faq') ?>" class="mobile-nav-link <?= $current_page === 'faq' ? 'mobile-nav-link-active' : '' ?>">
                            Preguntas Frecuentes
                        </a>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="<?= Router::url('panel') ?>" class="mobile-nav-link <?= $current_page === 'dashboard' ? 'mobile-nav-link-active' : '' ?>">
                                Mi Panel
                            </a>
                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <a href="<?= Router::url('admin') ?>" class="mobile-nav-link <?= $current_page === 'admin' ? 'mobile-nav-link-active' : '' ?>">
                                    Administración
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="pt-4 pb-3 border-t border-gray-200">
                            <div class="flex items-center px-5">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 rounded-full bg-copihue-100 flex items-center justify-center">
                                        <span class="text-sm font-medium text-copihue-700">
                                            <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <div class="text-base font-medium text-gray-800"><?= $_SESSION['user_name'] ?? 'Usuario' ?></div>
                                    <div class="text-sm font-medium text-gray-500"><?= $_SESSION['user_email'] ?? '' ?></div>
                                </div>
                            </div>
                            <div class="mt-3 px-2 space-y-1">
                                <a href="<?= Router::url('perfil') ?>" class="mobile-nav-link">Mi Perfil</a>
                                <form method="POST" action="<?= Router::url('logout') ?>">
                                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">
                                    <button type="submit" class="w-full text-left mobile-nav-link">
                                        Cerrar Sesión
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="pt-4 pb-3 border-t border-gray-200">
                            <div class="px-2 space-y-1">
                                <a href="<?= Router::url('login') ?>" class="mobile-nav-link">Iniciar Sesión</a>
                                <a href="<?= Router::url('registro') ?>" class="mobile-nav-link">Registrarse</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </nav>
        </header>

        <!-- Main Content -->
        <main id="main-content" class="flex-1" role="main">
            <!-- Flash Messages -->
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                    <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?>" role="alert">
                        <?= htmlspecialchars($_SESSION['flash_message']) ?>
                    </div>
                </div>
                <?php 
                unset($_SESSION['flash_message'], $_SESSION['flash_type']); 
                ?>
            <?php endif; ?>

            <!-- Page Content -->
            <?= $content ?>
        </main>

        <!-- Footer -->
        <?php include VIEWS_PATH . '/layouts/partials/footer.php'; ?>
    </div>

    <!-- Additional scripts -->
    <?= $additional_scripts ?? '' ?>
</body>
</html>
