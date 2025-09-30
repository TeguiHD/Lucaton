<?php
$is_authenticated = SessionHelper::isAuthenticated();
$notification_api_url = $is_authenticated ? Router::url('api/notifications') : null;
$notification_mark_url = $is_authenticated ? Router::url('api/notifications/mark-read') : null;
$notification_csrf = $is_authenticated ? SessionHelper::getCSRFToken() : null;
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $meta_description ?? 'Panel de Administración - Lucatón' ?>">
    <meta name="robots" content="noindex, nofollow">
    
    <title><?= $page_title ?? 'Administración' ?> - Lucatón</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/public/assets/images/favicon.svg">
    
    <!-- CSS -->
    <link href="<?= APP_URL ?>/public/assets/css/app.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/public/assets/css/aliases.css" rel="stylesheet">
    
    <!-- Additional head content -->
    <?= $additional_head ?? '' ?>
</head>
<body class="h-full" x-data="{ sidebarOpen: false }">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <div class="min-h-full">
        <!-- Mobile sidebar overlay -->
        <div x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 flex z-40 md:hidden" 
             role="dialog" 
             aria-modal="true">
            <div @click="sidebarOpen = false" 
                 class="fixed inset-0 bg-gray-600 bg-opacity-75" 
                 aria-hidden="true"></div>

            <!-- Mobile sidebar -->
            <div x-show="sidebarOpen"
                 x-transition:enter="transition ease-in-out duration-300 transform"
                 x-transition:enter-start="-translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in-out duration-300 transform"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="-translate-x-full"
                 class="relative flex-1 flex flex-col max-w-xs w-full pt-5 pb-4 bg-white">
                <div class="absolute top-0 right-0 -mr-12 pt-2">
                    <button @click="sidebarOpen = false" 
                            class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                        <span class="sr-only">Cerrar sidebar</span>
                        <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Mobile sidebar content -->
                <div class="flex-shrink-0 flex items-center px-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-br from-copihue-500 to-copihue-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-lg">L</span>
                        </div>
                        <span class="text-xl font-bold text-gray-900">Lucatón Admin</span>
                    </div>
                </div>
                <div class="mt-5 flex-1 h-0 overflow-y-auto">
                    <nav class="px-2 space-y-1">
                        <?php include __DIR__ . '/partials/admin-nav.php'; ?>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Desktop sidebar -->
        <div class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0">
            <div class="flex flex-col flex-grow pt-5 bg-white overflow-y-auto border-r border-gray-200">
                <!-- Logo -->
                <div class="flex items-center flex-shrink-0 px-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-br from-copihue-500 to-copihue-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-lg">L</span>
                        </div>
                        <span class="text-xl font-bold text-gray-900">Lucatón Admin</span>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="mt-5 flex-grow flex flex-col">
                    <nav class="flex-1 px-2 pb-4 space-y-1">
                        <?php include __DIR__ . '/partials/admin-nav.php'; ?>
                    </nav>
                </div>

                <!-- User info -->
                <div class="flex-shrink-0 flex border-t border-gray-200 p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-8 w-8 rounded-full bg-copihue-100 flex items-center justify-center">
                                <span class="text-sm font-medium text-copihue-700">
                                    <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
                                </span>
                            </div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700"><?= $_SESSION['user_name'] ?? 'Admin' ?></p>
                            <p class="text-xs font-medium text-gray-500">Administrador</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="md:pl-64 flex flex-col flex-1">
            <!-- Top bar -->
            <div class="sticky top-0 z-10 flex-shrink-0 flex h-16 bg-white shadow border-b border-gray-200">
                <!-- Mobile menu button -->
                <button @click="sidebarOpen = true" 
                        class="px-4 border-r border-gray-200 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-copihue-500 md:hidden">
                    <span class="sr-only">Abrir sidebar</span>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                    </svg>
                </button>

                <!-- Top bar content -->
                <div class="flex-1 px-4 flex justify-between items-center">
                    <!-- Page title -->
                    <div class="flex-1">
                        <h1 class="text-2xl font-semibold text-gray-900">
                            <?= $page_title ?? 'Panel de Administración' ?>
                        </h1>
                    </div>

                    <!-- Top bar actions -->
                    <div class="ml-4 flex items-center md:ml-6">
                        <!-- Notifications -->
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
                class="hidden origin-top-right absolute right-0 mt-2 w-80 rounded-lg shadow-lg bg-white ring-1 ring-black/10 focus:outline-none z-40">
                                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                    <h3 class="text-sm font-semibold text-gray-900">Notificaciones</h3>
                                </div>
                                <div class="max-h-80 overflow-y-auto">
                                    <div data-notification-spinner class="py-6 text-center text-sm text-gray-500">Cargando...</div>
                                    <div data-notification-error class="hidden py-6 text-center text-sm text-red-500"></div>
                                    <div data-notification-empty class="hidden py-6 text-center text-sm text-gray-500">No tienes notificaciones.</div>
                                    <ul data-notification-list class="hidden divide-y divide-gray-100"></ul>
                                </div>
                            </div>
                        </div>

                        <!-- User menu -->
                        <div class="ml-3 relative" x-data="{ open: false }">
                            <button @click="open = !open" 
                                    class="max-w-xs bg-white flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" 
                                    id="user-menu-button" 
                                    aria-expanded="false" 
                                    aria-haspopup="true">
                                <span class="sr-only">Abrir menú de usuario</span>
                                <div class="h-8 w-8 rounded-full bg-copihue-100 flex items-center justify-center">
                                    <span class="text-sm font-medium text-copihue-700">
                                        <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
                                    </span>
                                </div>
                            </button>
                            
            <div x-show="open" 
                 @click.away="open = false"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black/10 focus:outline-none z-50" 
                 role="menu" 
                                 aria-orientation="vertical" 
                                 aria-labelledby="user-menu-button">
                                <a href="<?= Router::url('/') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                    Ver Sitio Público
                                </a>
                                <a href="<?= Router::url('perfil') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                    Mi Perfil
                                </a>
                                <form method="POST" action="<?= Router::url('logout') ?>" class="block">
                                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                        Cerrar Sesión
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page content -->
            <main id="main-content" class="flex-1" role="main">
                <!-- Flash Messages -->
                <?php if (SessionHelper::hasFlash()): ?>
                    <?php $messages = SessionHelper::getAllFlash(); ?>
                    <div class="p-4 space-y-3">
                        <?php foreach ($messages as $type => $message): ?>
                            <div class="alert alert-<?= htmlspecialchars($type) ?>" role="alert">
                                <?= htmlspecialchars($message) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif (isset($_SESSION['flash_message'])): ?>
                    <div class="p-4">
                        <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?>" role="alert">
                            <?= htmlspecialchars($_SESSION['flash_message']) ?>
                        </div>
                    </div>
                    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                <?php endif; ?>

                <!-- Content -->
                <div class="py-6">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                        <?= $content ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- JS de interacción ligera (sin CDN) -->
    <script src="<?= APP_URL ?>/public/assets/js/app.js?v=2025012801" defer></script>
    
    <!-- Additional scripts -->
    <?= $additional_scripts ?? '' ?>
</body>
</html>
