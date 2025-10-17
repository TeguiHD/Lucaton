<?php
// Definir elementos de navegación del admin
$admin_nav_items = [
    [
        'name' => 'Dashboard',
        'href' => '/admin',
        'icon' => 'home',
        'current' => $current_page === 'admin-dashboard'
    ],
    [
        'name' => 'Campañas',
        'href' => '/admin/campanas',
        'icon' => 'collection',
        'current' => $current_page === 'admin-campaigns',
        'badge' => $pending_campaigns_count ?? null
    ],
    [
        'name' => 'Apelaciones',
        'href' => '/admin/apelaciones',
        'icon' => 'scales',
        'current' => $current_page === 'admin-appeals',
        'badge' => $pending_appeals_count ?? null
    ],
    [
        'name' => 'Noticias',
        'href' => '/admin/news',
        'icon' => 'newspaper',
        'current' => $current_page === 'admin-news'
    ],
    [
        'name' => 'Usuarios',
        'href' => '/admin/usuarios',
        'icon' => 'users',
        'current' => $current_page === 'admin-users'
    ],
    [
        'name' => 'Notificaciones',
        'href' => '/admin/notificaciones',
        'icon' => 'bell',
        'current' => $current_page === 'admin-notifications'
    ],
    [
        'name' => 'Newsletter',
        'href' => '/admin/newsletter',
        'icon' => 'mail',
        'current' => $current_page === 'admin-newsletter'
    ],
    [
        'name' => 'Reportes',
        'href' => '/admin/reportes',
        'icon' => 'help-circle',
        'current' => $current_page === 'admin-support'
    ],
    [
        'name' => 'Moderación IA',
        'href' => '/admin/ia',
        'icon' => 'sparkles',
        'current' => $current_page === 'admin-ai',
        'badge' => $ai_pending_count ?? null
    ],
    [
        'name' => 'Auditoría',
        'href' => '/admin/auditoria',
        'icon' => 'clipboard-list',
        'current' => $current_page === 'admin-audit'
    ],
    [
        'name' => 'Estadísticas',
        'href' => '/admin/estadisticas',
        'icon' => 'chart-bar',
        'current' => $current_page === 'admin-stats'
    ]
];

// Iconos SVG
$icons = [
    'home' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />',
    'collection' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />',
    'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />',
    'sparkles' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />',
    'clipboard-list' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />',
    'chart-bar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />',
    'bell' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM10.5 3.75a6 6 0 016 6v2.25a3 3 0 003 3v.75H4.5v-.75a3 3 0 003-3V9.75a6 6 0 016-6z" />',
    'mail' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8m-18 8h18a2 2 0 002-2V8a2 2 0 00-2-2H3a2 2 0 00-2 2v6a2 2 0 002 2z" />',
    'newspaper' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 5H8a2 2 0 00-2 2v11a1 1 0 01-1 1H5a1 1 0 01-1-1V6a2 2 0 012-2h13a1 1 0 011 1v11a2 2 0 01-2 2H9" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7h2M16 11h2M16 15h2M10 7h2M10 11h2M10 15h2" />'
    ,
    'help-circle' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10a4 4 0 118 0c0 1.657-1.343 3-3 3h-1v1m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'
    ,
    'scales' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v3m0 0l-6 7h12l-6-7zm0 0V3m0 10v8m-6 0h12" />'
];
?>

<!-- Navigation items -->
<?php foreach ($admin_nav_items as $item): ?>
    <a href="<?= Router::url(ltrim($item['href'], '/')) ?>" 
       class="<?= $item['current'] ? 'admin-nav-link-active' : 'admin-nav-link' ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md">
        <svg class="<?= $item['current'] ? 'text-copihue-500' : 'text-gray-400 group-hover:text-gray-500' ?> mr-3 flex-shrink-0 h-6 w-6" 
             xmlns="http://www.w3.org/2000/svg" 
             fill="none" 
             viewBox="0 0 24 24" 
             stroke="currentColor" 
             aria-hidden="true">
            <?= $icons[$item['icon']] ?>
        </svg>
        <?= $item['name'] ?>
        <?php if (isset($item['badge']) && $item['badge'] > 0): ?>
            <span class="ml-auto inline-block py-0.5 px-2 text-xs font-medium rounded-full bg-copihue-100 text-copihue-600">
                <?= $item['badge'] ?>
            </span>
        <?php endif; ?>
    </a>
<?php endforeach; ?>

<!-- Divider -->
<div class="border-t border-gray-200 my-4"></div>

<!-- Quick actions -->
<div class="px-2">
    <h3 class="px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
        Acciones Rápidas
    </h3>
    <div class="mt-2 space-y-1">
        <a href="<?= Router::url('admin/campanas') ?>?filter=pending" 
           class="admin-nav-link group flex items-center px-2 py-2 text-sm font-medium rounded-md">
            <svg class="text-yellow-400 group-hover:text-yellow-500 mr-3 flex-shrink-0 h-6 w-6" 
                 xmlns="http://www.w3.org/2000/svg" 
                 fill="none" 
                 viewBox="0 0 24 24" 
                 stroke="currentColor" 
                 aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            Pendientes de Revisión
            <?php if (isset($pending_campaigns_count) && $pending_campaigns_count > 0): ?>
                <span class="ml-auto inline-block py-0.5 px-2 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                    <?= $pending_campaigns_count ?>
                </span>
            <?php endif; ?>
        </a>
        
        <a href="<?= Router::url('admin/usuarios') ?>?filter=new" 
           class="admin-nav-link group flex items-center px-2 py-2 text-sm font-medium rounded-md">
            <svg class="text-green-400 group-hover:text-green-500 mr-3 flex-shrink-0 h-6 w-6" 
                 xmlns="http://www.w3.org/2000/svg" 
                 fill="none" 
                 viewBox="0 0 24 24" 
                 stroke="currentColor" 
                 aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            Nuevos Usuarios
        </a>
        
        <a href="<?= Router::url('admin/ia') ?>?filter=flagged" 
           class="admin-nav-link group flex items-center px-2 py-2 text-sm font-medium rounded-md">
            <svg class="text-red-400 group-hover:text-red-500 mr-3 flex-shrink-0 h-6 w-6" 
                 xmlns="http://www.w3.org/2000/svg" 
                 fill="none" 
                 viewBox="0 0 24 24" 
                 stroke="currentColor" 
                 aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
            </svg>
            Contenido Marcado
            <?php if (isset($ai_pending_count) && $ai_pending_count > 0): ?>
                <span class="ml-auto inline-block py-0.5 px-2 text-xs font-medium rounded-full bg-red-100 text-red-800">
                    <?= $ai_pending_count ?>
                </span>
            <?php endif; ?>
        </a>

        <a href="<?= Router::url('admin/news/create') ?>"
           class="admin-nav-link group flex items-center px-2 py-2 text-sm font-medium rounded-md">
            <svg class="text-copihue-400 group-hover:text-copihue-500 mr-3 flex-shrink-0 h-6 w-6"
                 xmlns="http://www.w3.org/2000/svg"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor"
                 aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10m6 0v6m0 0l-3-3m3 3l3-3" />
            </svg>
            Publicar noticia
        </a>
    </div>
</div>
