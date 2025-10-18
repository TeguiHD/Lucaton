<?php
$current_page = $current_page ?? '';
// Definir elementos de navegación principal
$nav_items = [
    [
        'name' => 'Inicio',
        'href' => '',
        'current' => $current_page === 'home'
    ],
    [
        'name' => 'Campañas',
        'href' => 'campanas',
        'current' => $current_page === 'campaigns'
    ],
    [
        'name' => 'Noticias',
        'href' => 'noticias',
        'current' => $current_page === 'news'
    ],
    [
        'name' => 'Crear Campaña',
        'href' => 'campana/crear',
        'current' => $current_page === 'create-campaign',
        'auth_required' => true
    ],
    [
        'name' => 'Centro de Ayuda',
        'href' => 'ayuda',
        'current' => in_array($current_page, ['help_center', 'faq'], true)
    ]
];

if (!isset($is_admin)) {
    $is_admin = SessionHelper::userHasRole('admin');
}

if ($is_admin) {
    $nav_items[] = [
        'name' => 'Administración',
        'href' => 'admin',
        'current' => ($current_page === 'admin') || (strpos($current_page, 'admin-') === 0),
        'auth_required' => true,
        'admin_only' => true
    ];
}

// Asegurar que la página de Visión no aparezca en el navbar principal
$nav_items = array_values(array_filter($nav_items, static function ($item) {
    return ($item['href'] ?? '') !== 'vision';
}));

// Verificar si el usuario está autenticado
$is_authenticated = SessionHelper::isAuthenticated();
$user_name = $_SESSION['user_name'] ?? '';
$user_avatar = $_SESSION['user_avatar'] ?? '';
$user_initial = 'U';

if ($user_name !== '') {
    if (function_exists('mb_substr')) {
        $initial = mb_substr($user_name, 0, 1, 'UTF-8');
        if ($initial !== '') {
            $user_initial = function_exists('mb_strtoupper') ? mb_strtoupper($initial, 'UTF-8') : strtoupper($initial);
        }
    } else {
        $user_initial = strtoupper(substr($user_name, 0, 1));
    }
}
$notification_api_url = $is_authenticated ? Router::url('api/notifications') : null;
$notification_mark_url = $is_authenticated ? Router::url('api/notifications/mark-read') : null;
$notification_summary_url = $is_authenticated ? Router::url('api/notifications/summary') : null;
$notification_delete_url = $is_authenticated ? Router::url('api/notifications/delete') : null;
$notification_history_url = $is_authenticated ? Router::url('notificaciones') : Router::url('login');
$notification_csrf = $is_authenticated ? SessionHelper::getCSRFToken() : null;

$desktopBaseLinkClass = 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-200';
$desktopActiveLinkClass = 'border-copihue-500 text-marino-900';
$desktopInactiveLinkClass = 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700';
$mobileBaseLinkClass = 'block pl-3 pr-4 py-2 border-l-4 text-sm font-medium transition-colors duration-200';
$mobileActiveLinkClass = 'bg-copihue-50 border-copihue-500 text-copihue-700';
$mobileInactiveLinkClass = 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800';
?>

<header data-sticky-header class="sticky top-0 inset-x-0 z-50 transform transition-transform duration-300 ease-out will-change-transform backdrop-blur supports-[backdrop-filter]:bg-white/70 bg-white/90 shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo y navegación principal -->
            <div class="flex items-center">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="<?= Router::url('/') ?>" class="flex items-center">
                        <img class="h-8 w-auto" src="<?= asset_url('images/logo.svg') ?>" alt="Lucatón">
                        <span class="ml-2 text-xl font-bold text-marino-900">Lucatón</span>
                    </a>
                </div>
                
                <!-- Navegación desktop -->
                <nav class="hidden md:ml-8 md:flex md:space-x-8" aria-label="Navegación principal">
                    <?php foreach ($nav_items as $item): ?>
                        <?php
                        $requiresAuth = $item['auth_required'] ?? false;
                        $requiresAdmin = $item['admin_only'] ?? false;

                        if ($requiresAdmin && !$is_admin) {
                            continue;
                        }

                        if ($requiresAuth && !$is_authenticated) {
                            continue;
                        }
                        ?>
                        <?php
                        $isCurrent = $item['current'] ?? false;
                        $linkClasses = $desktopBaseLinkClass . ' ' . ($isCurrent ? $desktopActiveLinkClass : $desktopInactiveLinkClass);
                        ?>
                        <a href="<?= Router::url($item['href']) ?>"
                           class="<?= $linkClasses ?>"
                           <?= $isCurrent ? 'aria-current="page"' : '' ?>>
                            <?= $item['name'] ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <!-- Acciones del usuario -->
            <div class="flex items-center space-x-4">
                <?php if ($is_authenticated): ?>
                    <!-- Notificaciones -->
                    <div class="relative mr-2">
                        <button
                            data-toggle="notifications-menu"
                            data-notification-trigger
                            data-endpoint="<?= htmlspecialchars($notification_api_url) ?>"
                            data-read-endpoint="<?= htmlspecialchars($notification_mark_url) ?>"
                            data-summary-endpoint="<?= htmlspecialchars($notification_summary_url ?? '') ?>"
                            data-delete-endpoint="<?= htmlspecialchars($notification_delete_url ?? '') ?>"
                            data-limit="10"
                            data-refresh-interval="60000"
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
                                    <div class="flex items-center space-x-2 text-xs">
                                        <button type="button" data-notification-action="refresh" class="text-gray-500 hover:text-gray-700">Actualizar</button>
                                        <span class="text-gray-300">·</span>
                                        <button type="button" data-notification-action="mark-all" class="text-gray-500 hover:text-gray-700">Marcar todas leídas</button>
                                    </div>
                                </div>
                                <div class="max-h-[65vh] sm:max-h-80 overflow-y-auto" data-notification-scroll>
                                    <div data-notification-spinner class="py-6 text-center text-sm text-gray-500">Cargando...</div>
                                    <div data-notification-error class="hidden py-6 text-center text-sm text-red-500"></div>
                                    <div data-notification-empty class="hidden py-6 text-center text-sm text-gray-500">No tienes notificaciones.</div>
                                    <ul data-notification-list class="hidden divide-y divide-gray-100"></ul>
                                </div>
                                <div class="border-t border-gray-100 bg-gray-50 px-4 py-2 space-y-2 sm:space-y-1">
                                    <button type="button" data-notification-action="load-more" class="hidden w-full rounded-md bg-white px-3 py-2 text-sm font-medium text-copihue-600 hover:bg-copihue-50 border border-copihue-100">Ver más notificaciones</button>
                                    <a href="<?= htmlspecialchars($notification_history_url) ?>" class="block text-center text-xs text-gray-500 hover:text-gray-700">Ver todo</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Menú de usuario -->
                    <div class="relative">
                        <button data-toggle="user-menu" 
                                type="button" 
                                class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" 
                                id="user-menu-button" 
                                aria-expanded="false" 
                                aria-haspopup="true">
                            <span class="sr-only">Abrir menú de usuario</span>
                            <?php if ($user_avatar): ?>
                                <img class="h-8 w-8 rounded-full object-cover" src="<?= htmlspecialchars($user_avatar) ?>" alt="<?= htmlspecialchars($user_name) ?>">
                            <?php else: ?>
                                <div class="h-8 w-8 rounded-full bg-copihue-500 flex items-center justify-center">
                                    <span class="text-sm font-medium text-white">
                                        <?= htmlspecialchars($user_initial) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <span class="hidden md:ml-2 md:block text-sm font-medium text-gray-700">
                                <?= htmlspecialchars($user_name) ?>
                            </span>
                            <svg class="hidden md:ml-1 md:block h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <!-- Dropdown menu -->
                        <div data-menu="user-menu"
                             class="hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black/10 focus:outline-none z-50" 
                             role="menu" 
                             aria-orientation="vertical" 
                             aria-labelledby="user-menu-button" 
                             tabindex="-1">
                            <a href="<?= Router::url('panel') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Panel general</a>
                            <a href="<?= Router::url('perfil') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Mi Perfil</a>
                            <a href="<?= Router::url('mis-campanas') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Mis Campañas</a>
                            <?php if ($is_admin): ?>
                                <div class="border-t border-gray-100"></div>
                                <a href="<?= Router::url('admin') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                    <span class="flex items-center">
                                        <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Administración
                                    </span>
                                </a>
                            <?php endif; ?>
                            <div class="border-t border-gray-100"></div>
                            <form method="POST" action="<?= Router::url('logout') ?>" class="block">
                                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                    Cerrar Sesión
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Botones de autenticación -->
                    <div class="header-auth-buttons space-x-4">
                        <a href="<?= Router::url('login') ?>" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium transition-colors duration-200">
                            Iniciar Sesión
                        </a>
                        <a href="<?= Router::url('registro') ?>" class="btn-primary">
                            Registrarse
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Botón menú móvil -->
                <button data-toggle="mobile-menu"
                        type="button" 
                        class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-copihue-500" 
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
    </div>

    <!-- Menú móvil -->
    <div class="md:hidden hidden mobile-menu-panel" 
         id="mobile-menu">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white border-t border-gray-200">
            <?php foreach ($nav_items as $item): ?>
                <?php
                $requiresAuth = $item['auth_required'] ?? false;
                $requiresAdmin = $item['admin_only'] ?? false;

                if ($requiresAdmin && !$is_admin) {
                    continue;
                }

                if ($requiresAuth && !$is_authenticated) {
                    continue;
                }
                ?>
                <?php
                $isCurrent = $item['current'] ?? false;
                $mobileLinkClasses = $mobileBaseLinkClass . ' ' . ($isCurrent ? $mobileActiveLinkClass : $mobileInactiveLinkClass);
                ?>
                <a href="<?= Router::url($item['href']) ?>"
                   class="<?= $mobileLinkClasses ?>"
                   <?= $isCurrent ? 'aria-current="page"' : '' ?>>
                    <?= $item['name'] ?>
                </a>
            <?php endforeach; ?>
            
            <?php if (!$is_authenticated): ?>
                <div class="border-t border-gray-200 pt-4">
                    <a href="<?= Router::url('login') ?>" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800 text-base font-medium">
                        Iniciar Sesión
                    </a>
                    <a href="<?= Router::url('registro') ?>" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800 text-base font-medium">
                        Registrarse
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if ($is_authenticated): ?>
    <?php include __DIR__ . '/notification-modal.php'; ?>
<?php endif; ?>
