<?php
// Start session to handle logout
session_start();

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// Handle logout action
if ($_POST && isset($_POST['logout']) && $is_logged_in) {
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to home page with success message
    header('Location: /?logout=success');
    exit;
}

require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/alerts.php';

$page_title = 'Cerrar Sesión - Lucatón';
$page_description = 'Cierra tu sesión de forma segura en Lucatón.';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    
    <!-- Prevent caching -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:title" content="<?php echo $page_title; ?>">
    <meta property="og:description" content="<?php echo $page_description; ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary">
    <meta property="twitter:url" content="<?php echo $_SERVER['REQUEST_URI']; ?>">
    <meta property="twitter:title" content="<?php echo $page_title; ?>">
    <meta property="twitter:description" content="<?php echo $page_description; ?>">

    <!-- Favicons -->
    <link rel="icon" type="image/svg+xml" href="<?= asset_url('images/favicon.svg') ?>">

    <!-- Styles -->
    <link href="<?= asset_url('css/app.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('css/aliases.css') ?>" rel="stylesheet">
    
    <!-- Alpine.js -->
    <script defer src="<?= asset_url('js/app.js') ?>"></script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <!-- Skip to content link -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <a href="<?= Router::url('/') ?>" class="flex items-center">
                        <svg class="h-8 w-8 text-copihue-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                        <span class="ml-2 text-xl font-bold text-gray-900">Lucatón</span>
                    </a>
                </div>
                <?php if ($is_logged_in): ?>
                <div class="text-sm text-gray-600">
                    <a href="<?= Router::url('panel') ?>" class="font-medium text-copihue-600 hover:text-copihue-500">
                        Ir al panel
                    </a>
                </div>
                <?php else: ?>
                <div class="text-sm text-gray-600">
                    <a href="<?= Router::url('login') ?>" class="font-medium text-copihue-600 hover:text-copihue-500">
                        Iniciar sesión
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main id="main-content" class="flex-1 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <?php if ($is_logged_in): ?>
                <!-- Logout Confirmation -->
                <div class="text-center">
                    <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-orange-100">
                        <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </div>
                    <h1 class="mt-6 text-3xl font-extrabold text-gray-900">
                        Cerrar Sesión
                    </h1>
                    <p class="mt-2 text-sm text-gray-600">
                        ¿Estás seguro de que quieres cerrar tu sesión?
                    </p>
                </div>

                <!-- Logout Form -->
                <div class="bg-white py-8 px-6 shadow rounded-lg" x-data="logoutForm()">
                    <form @submit.prevent="submitLogout()" class="space-y-6">
                        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars($_SESSION[CSRF_TOKEN_NAME] ?? '') ?>">
                        <!-- User Info -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 rounded-full bg-copihue-100 flex items-center justify-center">
                                        <svg class="h-5 w-5 text-copihue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuario'); ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col space-y-3">
                            <!-- Logout Button -->
                            <?php echo render_button([
                                'text' => 'Sí, cerrar sesión',
                                'type' => 'submit',
                                'variant' => 'danger',
                                'size' => 'lg',
                                'full_width' => true,
                                'attributes' => [
                                    ':disabled' => 'loading',
                                    ':class' => 'loading ? "opacity-75 cursor-not-allowed" : ""'
                                ]
                            ]); ?>

                            <!-- Cancel Button -->
                            <a href="<?= Router::url('panel') ?>" class="w-full flex justify-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500">
                                Cancelar
                            </a>
                        </div>

                        <!-- Loading State -->
                        <div x-show="loading" class="flex items-center justify-center">
                            <svg class="animate-spin h-5 w-5 text-copihue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="ml-2 text-sm text-gray-600">Cerrando sesión...</span>
                        </div>

                        <!-- Hidden logout field -->
                        <input type="hidden" name="logout" value="1">
                    </form>
                </div>

                <!-- Security Notice -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                            Consejo de seguridad
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Siempre cierra tu sesión cuando uses computadoras públicas o compartidas para proteger tu cuenta.</p>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Already Logged Out -->
                <div class="text-center">
                    <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-green-100">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h1 class="mt-6 text-3xl font-extrabold text-gray-900">
                        Sesión Cerrada
                    </h1>
                    <p class="mt-2 text-sm text-gray-600">
                        Tu sesión ha sido cerrada exitosamente
                    </p>
                </div>

                <!-- Actions -->
                <div class="bg-white py-8 px-6 shadow rounded-lg">
                    <div class="space-y-4">
                        <a href="<?= Router::url('login') ?>" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-copihue-600 hover:bg-copihue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500">
                            Iniciar sesión nuevamente
                        </a>
                        
                        <a href="<?= Router::url('/') ?>" class="w-full flex justify-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500">
                            Ir al inicio
                        </a>
                    </div>
                </div>

                <!-- Auto-redirect notice -->
                <div class="text-center" x-data="{ countdown: 10 }" x-init="
                    const interval = setInterval(() => {
                        countdown--;
                        if (countdown <= 0) {
                            clearInterval(interval);
                            window.location.href = '/';
                        }
                    }, 1000);
                ">
                    <p class="text-sm text-gray-500">
                        Serás redirigido al inicio en <span x-text="countdown" class="font-medium"></span> segundos
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="text-center text-sm text-gray-500">
                © <?php echo date('Y'); ?> Lucatón. Todos los derechos reservados.
            </div>
        </div>
    </footer>

    <script>
        function logoutForm() {
            return {
                loading: false,

                async submitLogout() {
                    this.loading = true;
                    
                    try {
                        const formData = new FormData();
                        formData.append('logout', '1');
                        
                        const response = await fetch('/auth/logout', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        
                        // Redirect regardless of response
                        window.location.href = '/?logout=success';
                    } catch (error) {
                        console.error('Logout error:', error);
                        // Still redirect on error
                        window.location.href = '/?logout=success';
                    }
                }
            }
        }

        // Handle keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // ESC key to cancel (go to dashboard)
            if (e.key === 'Escape' && <?php echo $is_logged_in ? 'true' : 'false'; ?>) {
                window.location.href = '/dashboard';
            }
        });

        // Auto-focus logout button if logged in
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($is_logged_in): ?>
            const logoutButton = document.querySelector('button[type="submit"]');
            if (logoutButton) {
                logoutButton.focus();
            }
            <?php endif; ?>
        });
    </script>
</body>
</html>
