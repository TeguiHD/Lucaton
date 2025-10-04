<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $meta_description ?? 'Accede a tu cuenta en Lucatón - Plataforma de crowdfunding ética con IA' ?>">
    <meta name="robots" content="noindex, nofollow">
    
    <title><?= $page_title ?? 'Autenticación' ?> - Lucatón</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/public/assets/images/favicon.svg">
    
    <!-- CSS -->
    <link href="<?= APP_URL ?>/public/assets/css/app.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/public/assets/css/aliases.css" rel="stylesheet">
    
    <!-- Additional head content -->
    <?= $additional_head ?? '' ?>
</head>
<body class="h-full bg-gray-50">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <!-- Header with logo -->
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                <a href="<?= Router::url('/') ?>" class="flex items-center space-x-2 group">
                    <div class="w-12 h-12 bg-gradient-to-br from-copihue-500 to-copihue-600 rounded-xl flex items-center justify-center shadow-lg">
                        <span class="text-white font-bold text-2xl">L</span>
                    </div>
                    <span class="text-2xl font-bold text-gray-900 group-hover:text-copihue-600 transition-colors">
                        Lucatón
                    </span>
                </a>
            </div>
            
            <?php if (isset($page_subtitle)): ?>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    <?= htmlspecialchars($page_subtitle) ?>
                </h2>
            <?php endif; ?>
            
            <?php if (isset($page_description)): ?>
                <p class="mt-2 text-center text-sm text-gray-600">
                    <?= htmlspecialchars($page_description) ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Main Content -->
        <main id="main-content" class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4" role="main">
            <!-- Flash Messages -->
            <?php if (function_exists('include_flash_messages')) { include_flash_messages(); } ?>

            <!-- Auth Card -->
            <div class="bg-white py-8 px-4 shadow-xl rounded-lg sm:px-10">
                <?= $content ?>
            </div>

            <!-- Additional links -->
            <?php if (isset($auth_links)): ?>
                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300" />
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-gray-50 text-gray-500">O</span>
                        </div>
                    </div>

                    <div class="mt-6 text-center space-y-2">
                        <?= $auth_links ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Back to home -->
            <div class="mt-8 text-center">
                <a href="<?= Router::url('/') ?>" class="text-sm text-copihue-600 hover:text-copihue-500 font-medium">
                    ← Volver al inicio
                </a>
            </div>
        </main>

        <!-- Footer minimal -->
        <footer class="mt-8 text-center text-xs text-gray-500" role="contentinfo">
            <p>© <?= date('Y') ?> Lucatón. Todos los derechos reservados.</p>
            <div class="mt-2 space-x-4">
                <a href="<?= Router::url('terminos') ?>" class="hover:text-gray-700 transition-colors">Términos</a>
                <a href="<?= Router::url('privacidad') ?>" class="hover:text-gray-700 transition-colors">Privacidad</a>
            </div>
        </footer>
    </div>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- JS de interacción ligera (sin CDN) -->
    <script src="<?= APP_URL ?>/public/assets/js/app.js?v=2025020503" defer></script>
    
    <!-- Additional scripts -->
    <?= $additional_scripts ?? '' ?>
</body>
</html>
