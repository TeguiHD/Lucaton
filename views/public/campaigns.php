<?php
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/cards.php';
require_once __DIR__ . '/../components/forms.php';
require_once __DIR__ . '/../components/navigation.php';
require_once __DIR__ . '/../components/alerts.php';

$filters = $filters ?? ['search' => '', 'category' => '', 'status' => null];
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;
$totalCampaigns = $totalCampaigns ?? count($campaigns ?? []);
$search_query = $filters['search'] ?? '';
$category_filter = $filters['category'] ?? '';
$status_filter = $filters['status'] ?? '';
$sort_by = $sort ?? 'recent';
$count_on_page = count($campaigns ?? []);
$from_item = $totalCampaigns > 0 ? (($page - 1) * ($perPage ?? 9)) + 1 : 0;
$to_item = $totalCampaigns > 0 ? min($totalCampaigns, $from_item + $count_on_page - 1) : 0;

$all_statuses = $statuses ?? [];
$status_placeholder = $all_statuses[''] ?? 'Todas las campañas';
$status_options = $all_statuses;
if (isset($status_options[''])) {
    unset($status_options['']);
}

$status_filter_label = '';
if ($status_filter !== '') {
    $status_filter_label = $all_statuses[$status_filter] ?? ucfirst(str_replace('_', ' ', $status_filter));
}

$page_title = 'Campañas - Lucatón';
$page_description = 'Descubre y apoya campañas de crowdfunding en Chile. Proyectos sociales, educativos y emprendimientos sustentables.';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta property="og:image" content="/assets/images/og-campaigns.jpg">

    <!-- Favicons -->
    <link rel="icon" type="image/svg+xml" href="<?= asset_url('images/favicon.svg') ?>">

    <!-- Styles -->
    <link href="<?= asset_url('css/app.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('css/aliases.css') ?>" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>


</head>
<body class="bg-gray-50">
    <!-- Skip to content link -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <!-- Header -->
    <?php include __DIR__ . '/../layouts/partials/header.php'; ?>

    <!-- Main Content -->
    <main id="main-content" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumb -->
        <?php echo render_breadcrumb([
            ['name' => 'Inicio', 'href' => Router::url('/')],
            ['name' => 'Campañas', 'href' => Router::url('campanas')]
        ]); ?>

        

        <!-- Page Header -->
        <div class="md:flex md:items-center md:justify-between mb-8">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Campañas de Crowdfunding
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Descubre proyectos increíbles y ayuda a hacerlos realidad
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <?php echo render_button([
                    'text' => 'Crear Campaña',
                    'href' => Router::url('campana/crear'),
                    'type' => 'primary',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />'
                ]); ?>
            </div>
        </div>

        <!-- Filtros de búsqueda -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sm:p-6 mb-6">
            <form method="GET" action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" class="space-y-4">
                <!-- Título de la sección -->
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Buscar Campañas</h3>
                </div>

                <!-- Campo de búsqueda principal y filtros en estructura móvil optimizada -->
                <div class="space-y-4">
                    <!-- Primera fila: Búsqueda y Categoría (2x1 en móvil, 2x1 en desktop) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Campo de búsqueda principal -->
                        <div class="sm:col-span-1">
                            <?php echo render_text_input([
                                'name' => 'search',
                                'id' => 'search',
                                'label' => 'Buscar',
                                'placeholder' => 'Título, descripción o creador...',
                                'value' => $search_query,
                                'wrapper_class' => 'mb-0',
                                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>',
                                'icon_position' => 'left',
                                'input_class' => 'text-base',
                                'label_class' => 'text-sm font-medium text-gray-700'
                            ]); ?>
                        </div>

                        <!-- Categoría -->
                        <div class="sm:col-span-1">
                            <?php echo render_select([
                                'name' => 'category',
                                'id' => 'category',
                                'label' => 'Categoría',
                                'value' => $category_filter,
                                'placeholder' => 'Todas las categorías',
                                'options' => $categories,
                                'wrapper_class' => 'mb-0',
                                'label_class' => 'text-sm font-medium text-gray-700'
                            ]); ?>
                        </div>
                    </div>

                    <!-- Segunda fila: Estado y Ordenar (2x1 en móvil, 2x1 en desktop) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Estado -->
                        <div class="sm:col-span-1">
                            <?php echo render_select([
                                'name' => 'status',
                                'id' => 'status',
                                'label' => 'Estado',
                                'value' => $status_filter,
                                'placeholder' => $status_placeholder,
                                'options' => $status_options,
                                'wrapper_class' => 'mb-0',
                                'label_class' => 'text-sm font-medium text-gray-700'
                            ]); ?>
                        </div>

                        <!-- Ordenar por -->
                        <div class="sm:col-span-1">
                            <?php echo render_select([
                                'name' => 'sort',
                                'id' => 'sort',
                                'label' => 'Ordenar por',
                                'value' => $sort_by,
                                'options' => [
                                    'newest' => 'Más recientes',
                                    'oldest' => 'Más antiguos',
                                    'title' => 'Título A-Z',
                                    'progress' => 'Progreso',
                                    'goal' => 'Meta más alta'
                                ],
                                'wrapper_class' => 'mb-0',
                                'label_class' => 'text-sm font-medium text-gray-700'
                            ]); ?>
                        </div>
                    </div>
                </div>

                <!-- Botones de acción optimizados para móvil -->
                <div class="pt-4 border-t border-gray-200">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- Botón Buscar -->
                        <button type="submit" 
                                class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-copihue-600 text-white text-sm font-medium rounded-md hover:bg-copihue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Aplicar Filtros
                        </button>
                        
                        <!-- Botón Limpiar -->
                        <?php if (!empty($search_query) || !empty($category_filter) || !empty($status_filter) || !empty($sort_by)): ?>
                            <a href="<?= Router::url('campanas') ?>" 
                               class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Limpiar Filtros
                            </a>
                        <?php else: ?>
                            <button type="reset" 
                                    class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Limpiar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Información de resultados -->
                <?php if (!empty($search_query) || !empty($category_filter) || !empty($status_filter)): ?>
                    <div class="bg-blue-50 border border-blue-200 rounded-md p-3">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-blue-400 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="text-sm text-blue-700">
                                <p class="font-medium">Filtros aplicados:</p>
                                <div class="mt-1 flex flex-wrap gap-2">
                                    <?php if (!empty($search_query)): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Búsqueda: "<?= htmlspecialchars($search_query) ?>"
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($category_filter)): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Categoría: <?= htmlspecialchars($categories[$category_filter] ?? $category_filter) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($status_filter)): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Estado: <?= htmlspecialchars($status_filter_label) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Resultados y estadísticas -->
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-gray-900">
                <?php if (!empty($search_query) || !empty($category_filter) || !empty($status_filter)): ?>
                    Resultados de búsqueda
                <?php else: ?>
                    Todas las Campañas
                <?php endif; ?>
            </h2>
            <p class="text-sm text-gray-600 mt-1">
                <?php if ($totalCampaigns > 0): ?>
                    Mostrando <?= $from_item ?>-<?= $to_item ?> de <?= $totalCampaigns ?> campañas
                <?php else: ?>
                    No se encontraron campañas
                <?php endif; ?>
            </p>
        </div>

        <?php if ($totalCampaigns > 0): ?>
            <div x-data="{ view: 'grid' }" class="space-y-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2 text-sm text-gray-500 mr-4">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Actualizado hace unos minutos</span>
                    </div>

                    <!-- View Toggle -->
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-500">Vista:</span>
                            <button @click="view = 'grid'" :class="view === 'grid' ? 'bg-copihue-100 text-copihue-600' : 'text-gray-400 hover:text-gray-500'" class="p-2 rounded-md">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                            </button>
                            <button @click="view = 'list'" :class="view === 'list' ? 'bg-copihue-100 text-copihue-600' : 'text-gray-400 hover:text-gray-500'" class="p-2 rounded-md">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                            </button>
                        </div>
                </div>

                <div>
                    <div x-show="view === 'grid'" x-cloak class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($campaigns as $campaign): ?>
                            <?php echo render_campaign_card($campaign); ?>
                        <?php endforeach; ?>
                    </div>

                    <div x-show="view === 'list'" x-cloak class="space-y-6">
                        <?php foreach ($campaigns as $campaign): ?>
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="sm:flex">
                            <div class="sm:flex-shrink-0">
                                <?php
                                    $listImageCandidates = [
                                        $campaign['image_url'] ?? null,
                                        $campaign['cover_image_url'] ?? null,
                                        $campaign['featured_image_url'] ?? null,
                                        $campaign['featured_image'] ?? null,
                                        $campaign['banner_image_url'] ?? null,
                                        $campaign['banner_url'] ?? null,
                                        $campaign['main_image_url'] ?? null,
                                        $campaign['image'] ?? null,
                                        $campaign['owner_avatar'] ?? null,
                                        $campaign['creator_avatar'] ?? null,
                                    ];

                                    $listImage = APP_URL . '/public/assets/images/campaigns/escuela-rural.svg';
                                    foreach ($listImageCandidates as $candidate) {
                                        if (empty($candidate)) {
                                            continue;
                                        }

                                        $normalized = CampaignMediaUploadService::normalizePublicUrl($candidate);
                                        if ($normalized !== null) {
                                            $listImage = $normalized;
                                            break;
                                        }
                                    }
                                ?>
                                <img class="h-48 w-full object-cover sm:h-32 sm:w-48" src="<?php echo htmlspecialchars($listImage); ?>" alt="Imagen de la campaña <?php echo htmlspecialchars($campaign['title']); ?>">
                            </div>
                            <div class="p-6 flex-1">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <?php $campaignPublicPath = $campaign['public_path'] ?? CampaignPresenter::buildPublicPath($campaign); ?>
                                        <h3 class="text-lg font-medium text-gray-900">
                    <a href="<?= htmlspecialchars($campaignPublicPath ? Router::url($campaignPublicPath) : '#') ?>" class="hover:text-copihue-600">
                        <?php echo htmlspecialchars($campaign['title']); ?>
                    </a>
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            por <?php echo htmlspecialchars($campaign['owner_name'] ?? 'Campañista'); ?>
                                        </p>
                                        <p class="mt-2 text-sm text-gray-600 line-clamp-2">
                                            <?php echo htmlspecialchars($campaign['summary'] ?? $campaign['description'] ?? ''); ?>
                                        </p>
                                    </div>
                                    <div class="ml-6 flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-copihue-100 text-copihue-800">
                                            <?php echo htmlspecialchars($campaign['category_name'] ?? 'Causa social'); ?>
                                        </span>
                                    </div>
                                </div>
                                <?php
                                    $progressPercent = min(100, max(0, (float)($campaign['progress'] ?? 0)));
                                    $progressDisplay = number_format($progressPercent, 1, ',', '.');
                                    $donorCount = (int)($campaign['donor_count'] ?? 0);
                                    $raisedDisplay = number_format((float)($campaign['raised_amount'] ?? 0), 0, ',', '.');
                                    $timeLabel = '';
                                    if (!empty($campaign['time_over'])) {
                                        $timeLabel = 'Campaña finalizada';
                                    } elseif (!empty($campaign['time_remaining_label'])) {
                                        $timeLabel = $campaign['time_remaining_label'];
                                    } elseif ($campaign['days_left'] !== null) {
                                        $timeLabel = (int)$campaign['days_left'] . ' días restantes';
                                    } else {
                                        $timeLabel = 'Sin fecha límite';
                                    }
                                ?>
                                <div class="mt-4 space-y-2">
                                    <div class="flex items-center justify-between text-sm text-gray-500">
                                        <span class="font-medium text-gray-900">$<?php echo $raisedDisplay; ?> recaudados</span>
                                        <span><?php echo $progressDisplay; ?>% completado</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden" aria-hidden="true">
                                        <div class="h-2 bg-gradient-to-r from-copihue-500 to-copihue-600 transition-all duration-500 ease-out" style="width: <?php echo $progressPercent; ?>%"></div>
                                    </div>
                                    <div class="flex items-center justify-between text-xs text-gray-500">
                                        <span><?php echo $donorCount; ?> colaboradores</span>
                                        <span><?php echo htmlspecialchars($timeLabel); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Empty State -->
        <?php if (empty($campaigns)): ?>
            <?php echo (function_exists('render_empty_state') ? render_empty_state([
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />',
                'title' => 'No se encontraron campañas',
                'description' => 'Intenta ajustar tus filtros de búsqueda o crear una nueva campaña.',
                'action_text' => 'Crear Campaña',
                'action_href' => Router::url('campana/crear'),
                'secondary_action_text' => 'Ver Todas',
                'secondary_action_href' => Router::url('campanas')
            ]) : ''); ?>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="mt-8">
                <?php echo render_pagination([
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'base_url' => Router::url('campanas'),
                    'query_params' => [
                        'search' => $search_query,
                        'category' => $category_filter,
                        'status' => $status_filter,
                        'sort' => $sort_by
                    ]
                ]); ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <?php include __DIR__ . '/../layouts/partials/footer.php'; ?>
</body>
</html>
