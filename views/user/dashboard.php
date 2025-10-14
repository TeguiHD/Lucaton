<?php
require_once __DIR__ . '/../layouts/partials/flash-messages.php';
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/cards.php';
require_once __DIR__ . '/../components/alerts.php';
require_once __DIR__ . '/../components/navigation.php';

$user = $user ?? [];
$campaigns = $campaigns ?? [];
$recentActivity = $recentActivity ?? [];
$notifications = $notifications ?? [];
$campaignInsights = $campaignInsights ?? ['donations_count' => 0, 'total_donated' => 0];
$dashboardCelebration = $dashboardCelebration ?? null;
$campaignMetricsEndpoint = $campaignMetricsEndpoint ?? Router::url('api/mis-campanas/resumen');

$page_title = 'Panel de Usuario — Lucatón';
$page_description = 'Gestiona tus campañas, consulta métricas y mantente informado de tus donaciones.';

$displayName = $user['name'] ?? 'Usuario';
$memberSince = !empty($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : '';
$totalCampaigns = (int)($user['total_campaigns'] ?? 0);
$totalRaised = (float)($user['total_raised'] ?? 0);
$totalSupporters = (int)($user['total_supporters'] ?? 0);
$successRate = (float)($user['success_rate'] ?? 0);
$donationsCount = (int)($campaignInsights['donations_count'] ?? 0);
$totalDonated = (float)($campaignInsights['total_donated'] ?? 0);
$isVerified = !empty($user['verified']);

$formatCurrency = static fn (float $amount): string => '$' . number_format($amount, 0, ',', '.');
$avatarUrl = $user['avatar'] ?? APP_URL . '/public/assets/images/avatars/default.jpg';

$statCards = [
    [
        'title' => 'Campañas creadas',
        'value' => number_format($totalCampaigns),
        'description' => 'Incluye campañas en revisión y activas',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />',
        'color' => 'marino'
    ],
    [
        'title' => 'Total recaudado',
        'value' => $formatCurrency($totalRaised),
        'description' => 'Suma de aportes recibidos en tus campañas',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2-1.343-2-3-2zm0 0V4m0 8c-2.21 0-4 1.343-4 3v1m4-4c2.21 0 4 1.343 4 3v1m0 0h-8m8 0h1a2 2 0 002-2v-2m-1-9h-3a1 1 0 01-.707-.293l-1.414-1.414A1 1 0 0013.586 3H10a2 2 0 00-2 2v1" />',
        'color' => 'green'
    ],
    [
        'title' => 'Donantes únicos',
        'value' => number_format($totalSupporters),
        'description' => 'Personas que apoyaron tus campañas',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a3 3 0 11-6 0 3 3 0 016 0zm-12 0a3 3 0 11-6 0 3 3 0 016 0z" />',
        'color' => 'pacifico'
    ],
    [
        'title' => 'Tasa de éxito',
        'value' => number_format($successRate, 1) . '%',
        'description' => 'Campañas publicadas o completadas',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17a1 1 0 001 1h6m-7-4h2a1 1 0 001-1V5a1 1 0 00-1-1H9.5a1 1 0 00-.8.4L7 6.5M5 15v-2m0-4V7" />',
        'color' => 'yellow'
    ],
];

$statusLabels = [
    'draft' => ['label' => 'Borrador', 'class' => 'bg-gray-100 text-gray-700'],
    'under_review' => ['label' => 'En revisión', 'class' => 'bg-amber-100 text-amber-800'],
    'published' => ['label' => 'Publicada', 'class' => 'bg-green-100 text-green-800'],
    'active' => ['label' => 'Activa', 'class' => 'bg-green-100 text-green-800'],
    'completed' => ['label' => 'Completada', 'class' => 'bg-blue-100 text-blue-800'],
    'paused' => ['label' => 'Pausada', 'class' => 'bg-yellow-100 text-yellow-800'],
    'cancelled' => ['label' => 'Cancelada', 'class' => 'bg-red-100 text-red-800'],
    'archived' => ['label' => 'Archivada', 'class' => 'bg-gray-100 text-gray-700'],
];

$notificationStyles = [
    'success' => 'bg-green-50 border border-green-200',
    'info' => 'bg-blue-50 border border-blue-200',
    'warning' => 'bg-yellow-50 border border-yellow-200',
    'error' => 'bg-red-50 border border-red-200',
    'system' => 'bg-gray-50 border border-gray-200',
];

$buttonIcons = [
    'plus' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />',
    'user' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 15c2.507 0 4.847.655 6.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />',
    'chevron-right' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />',
    'currency-dollar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2-1.343-2-3-2zm0-4v4m0 8c-1.657 0-3-.895-3-2s1.343-2 3-2 3 .895 3 2-1.343 2-3 2zm0 0v4" />',
    'chart-bar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6a2 2 0 012-2h1m6 15V8a2 2 0 00-2-2h-1m-4 13V4a2 2 0 00-2-2H9" />'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">

    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/public/assets/images/favicon.svg">
    <link href="<?= APP_URL ?>/public/assets/css/app.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/public/assets/css/aliases.css" rel="stylesheet">
    <script defer src="<?= APP_URL ?>/public/assets/js/app.js?v=2025020503"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <?php include VIEWS_PATH . '/layouts/partials/header.php'; ?>

    <main id="main-content" class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php include_flash_messages(); ?>

        <div class="md:flex md:items-center md:justify-between mb-8">
            <div class="flex items-center space-x-4">
                <img class="h-16 w-16 rounded-full object-cover" src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar de <?= htmlspecialchars($displayName) ?>">
                <div>
                    <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl">
                        ¡Hola, <?= htmlspecialchars($displayName) ?>!
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        <?= $isVerified ? 'Cuenta verificada' : 'Verifica tu correo para acceder a más funciones' ?>
                        <?= $memberSince ? ' • Miembro desde ' . htmlspecialchars($memberSince) : '' ?>
                    </p>
                </div>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4 space-x-2">
                <?= render_button([
                    'text' => 'Crear campaña',
                    'href' => Router::url('campana/crear'),
                    'type' => 'primary',
                    'icon' => $buttonIcons['plus']
                ]) ?>
                <?= render_button([
                    'text' => 'Editar perfil',
                    'href' => Router::url('perfil'),
                    'type' => 'secondary',
                    'icon' => $buttonIcons['user']
                ]) ?>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <?php foreach ($statCards as $card): ?>
                <?= render_stat_card($card) ?>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">
                <section class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">Mis campañas</h2>
                                <p class="text-sm text-gray-500">Resumen de las campañas más recientes</p>
                            </div>
                            <?= render_button([
                                'text' => 'Ver todas',
                                'href' => Router::url('mis-campanas'),
                                'type' => 'ghost',
                                'icon_position' => 'right',
                                'icon' => $buttonIcons['chevron-right']
                            ]) ?>
                        </div>

                        <?php if (empty($campaigns)): ?>
                            <div class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">Aún no tienes campañas</h3>
                                <p class="mt-1 text-sm text-gray-500">Crea tu primera campaña para comenzar a recibir aportes.</p>
                                <div class="mt-6 inline-flex">
                                    <?= render_button([
                                        'text' => 'Crear campaña',
                                        'href' => Router::url('campana/crear'),
                                        'type' => 'primary'
                                    ]) ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($campaigns as $campaign): ?>
                                    <?php
                                        $goalAmount = (float)($campaign['goal_amount'] ?? 0);
                                        $raisedAmount = (float)($campaign['raised_amount'] ?? 0);
                                        $progress = $goalAmount > 0 ? min(100, ($raisedAmount / $goalAmount) * 100) : 0;
                                        $progressLabel = number_format($progress, 0);
                                        $currencyCode = strtoupper((string)($campaign['currency'] ?? 'CLP'));
                            $imageCandidate = $campaign['cover_image_url'] ?? ($campaign['image_url'] ?? null);
                            $imageUrl = CampaignMediaUploadService::normalizePublicUrl($imageCandidate)
                                ?? (APP_URL . '/public/assets/images/campaigns/placeholder.jpg');
                                        $status = $campaign['status'] ?? 'draft';
                                        $statusMeta = $statusLabels[$status] ?? ['label' => ucfirst($status), 'class' => 'bg-gray-100 text-gray-700'];
                                        $campaignPublicPath = $campaign['public_path'] ?? CampaignPresenter::buildPublicPath($campaign);
                                        $campaignUrl = $campaignPublicPath ? Router::url($campaignPublicPath) : '#';
                                        $categoryLabel = $campaign['category_name'] ?? null;
                                        $endDate = !empty($campaign['end_date']) ? strtotime($campaign['end_date']) : null;
                                        $daysLeft = null;
                                        if ($endDate) {
                                            $diffDays = (int)ceil(($endDate - time()) / 86400);
                                            $daysLeft = $diffDays > 0 ? $diffDays : 0;
                                        }
                                    ?>
                                    <article class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow" data-campaign-card data-campaign-id="<?= (int)($campaign['id'] ?? 0) ?>" data-campaign-currency="<?= htmlspecialchars($currencyCode) ?>">
                                        <div class="flex items-start space-x-4">
                                            <div class="flex-shrink-0">
                                                <img class="h-16 w-16 rounded-lg object-cover" src="<?= htmlspecialchars($imageUrl) ?>" alt="Imagen de <?= htmlspecialchars($campaign['title'] ?? 'Campaña') ?>">
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="flex items-center gap-2 min-w-0">
                                                        <h3 class="text-sm font-semibold text-gray-900 truncate">
                                                            <a href="<?= htmlspecialchars($campaignUrl) ?>" class="hover:text-copihue-600">
                                                                <?= htmlspecialchars($campaign['title'] ?? 'Campaña sin título') ?>
                                                            </a>
                                                        </h3>
                                                        <?php if (!empty($campaign['id'])): ?>
                                                            <span class="hidden sm:inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600 flex-shrink-0">ID #<?= (int)$campaign['id'] ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= htmlspecialchars($statusMeta['class']) ?>">
                                                        <?= htmlspecialchars($statusMeta['label']) ?>
                                                    </span>
                                                </div>
                                                <?php if (!empty($campaign['id'])): ?>
                                                    <span class="sm:hidden inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600 mt-1">ID #<?= (int)$campaign['id'] ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($campaign['summary'])): ?>
                                                    <p class="mt-1 text-sm text-gray-500 line-clamp-2">
                                                        <?= htmlspecialchars($campaign['summary']) ?>
                                                    </p>
                                                <?php endif; ?>
                                    <div class="mt-3">
                                        <div class="flex items-center justify-between text-sm text-gray-600">
                                            <span data-campaign-amount><?= $formatCurrency($raisedAmount) ?> de <?= $formatCurrency($goalAmount) ?></span>
                                            <span data-campaign-progress-label><?= $progressLabel ?>%</span>
                                        </div>
                                        <div class="mt-2 w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                            <div class="bg-gradient-to-r from-copihue-500 to-copihue-600 h-2" data-campaign-progress-bar style="width: <?= $progress ?>%"></div>
                                        </div>
                                        <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                                            <span><?= number_format((int)($campaign['donor_count'] ?? 0)) ?> aportes</span>
                                            <?php if ($daysLeft !== null): ?>
                                                <span><?= $daysLeft ?> días restantes</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($categoryLabel): ?>
                                            <div class="mt-1 text-xs text-copihue-600 font-medium">
                                                <?= htmlspecialchars($categoryLabel) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="mt-4 flex flex-wrap items-center gap-2">
                                            <?php if ($campaignUrl && $campaignUrl !== '#'): ?>
                                                <a href="<?= htmlspecialchars($campaignUrl) ?>" class="btn inline-flex items-center rounded-md border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 hover:border-copihue-500 hover:text-copihue-600">
                                                    Ver campaña
                                                </a>
                                            <?php endif; ?>
                                            <?php
                                                $canEditDashboard = in_array(strtolower((string)$campaign['status']), ['draft', 'under_review', 'cancelled'], true);
                                                $editUrl = !empty($campaign['id']) ? Router::url('campana/' . $campaign['id'] . '/editar') : null;
                                            ?>
                                            <?php if ($canEditDashboard && $editUrl): ?>
                                                <a href="<?= htmlspecialchars($editUrl) ?>" class="btn inline-flex items-center rounded-md bg-copihue-600 px-3 py-2 text-xs font-medium text-white hover:bg-copihue-700 hover:text-white focus:text-white">
                                                    Editar
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">Actividad reciente</h2>
                                <p class="text-sm text-gray-500">Últimos movimientos en tus campañas y aportes</p>
                            </div>
                        </div>

                        <?php if (empty($recentActivity)): ?>
                            <p class="text-sm text-gray-500 text-center py-6">Aún no registramos actividad reciente.</p>
                        <?php else: ?>
                            <ul class="divide-y divide-gray-200">
                                <?php foreach ($recentActivity as $activity): ?>
                                    <li class="py-4">
                                        <div class="flex items-start space-x-3">
                                            <div class="flex-shrink-0">
                                                <?php if (($activity['icon'] ?? '') === 'heart'): ?>
                                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-red-100">
                                                        <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 18.657l-6.828-6.829a4 4 0 010-5.656z" />
                                                        </svg>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-copihue-100">
                                                        <svg class="w-4 h-4 text-copihue-600" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M13 7H7v6h6V7z" />
                                                        </svg>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900">
                                                    <?= htmlspecialchars($activity['message'] ?? '') ?>
                                                </p>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    Hace <?= htmlspecialchars($activity['time'] ?? '') ?>
                                                </p>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <aside class="space-y-8">
                <section class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Acciones rápidas</h2>
                        <div class="space-y-3">
                            <?= render_button([
                                'text' => 'Nueva campaña',
                                'href' => Router::url('campana/crear'),
                                'type' => 'primary',
                                'full_width' => true,
                                'icon' => $buttonIcons['plus']
                            ]) ?>
                            <?= render_button([
                                'text' => 'Mis donaciones',
                                'href' => Router::url('mis-donaciones'),
                                'type' => 'secondary',
                                'full_width' => true,
                                'icon' => $buttonIcons['currency-dollar']
                            ]) ?>
                            <?= render_button([
                                'text' => 'Ver estadísticas',
                                'href' => Router::url('mis-estadisticas'),
                                'type' => 'secondary',
                                'full_width' => true,
                                'icon' => $buttonIcons['chart-bar']
                            ]) ?>
                        </div>
                        <div class="mt-6 rounded-lg bg-gray-50 p-4">
                            <p class="text-sm font-semibold text-gray-700">Tus aportes como donante</p>
                            <p class="text-2xl font-bold text-gray-900 mt-2">
                                <?= $formatCurrency($totalDonated) ?>
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                En <?= number_format($donationsCount) ?> donaciones completadas
                            </p>
                        </div>
                    </div>
                </section>

                <section class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-medium text-gray-900">Notificaciones</h2>
                            <?php if (!empty($notifications)): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?= number_format(count(array_filter($notifications, static fn($n) => empty($n['read'])))) ?> nuevas
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if (empty($notifications)): ?>
                            <p class="text-sm text-gray-500 text-center py-6">No tienes notificaciones pendientes.</p>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($notifications as $notification): ?>
                                    <?php
                                        $type = $notification['type'] ?? 'info';
                                        $containerClass = $notificationStyles[$type] ?? $notificationStyles['info'];
                                        $meta = $notification['meta'] ?? null;
                                        $ctaUrl = is_array($meta) ? ($meta['cta_url'] ?? null) : null;
                                        $ctaLabel = is_array($meta) ? ($meta['cta_label'] ?? 'Ver detalles') : 'Ver detalles';
                                    ?>
                                    <div class="p-3 rounded-lg <?= htmlspecialchars($containerClass) ?>">
                                        <p class="text-sm font-semibold text-gray-900">
                                            <?= htmlspecialchars($notification['title'] ?? 'Notificación') ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <?= htmlspecialchars($notification['message'] ?? '') ?>
                                        </p>
                                        <?php if ($ctaUrl): ?>
                                            <a href="<?= htmlspecialchars($ctaUrl) ?>"
                                               class="mt-2 inline-flex items-center text-xs font-semibold text-copihue-600 hover:text-copihue-700"
                                               target="_blank" rel="noopener noreferrer">
                                                <?= htmlspecialchars($ctaLabel) ?>
                                                <svg class="ml-1 h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </a>
                                        <?php endif; ?>
                                        <p class="text-xs text-gray-400 mt-1">
                                            Hace <?= htmlspecialchars($notification['time'] ?? '') ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-3">Consejos para mejorar</h2>
                        <ul class="space-y-3 text-sm text-gray-600">
                            <li class="flex items-start space-x-2">
                                <span class="text-copihue-600 mt-1">•</span>
                                <span>Comparte novedades cada semana para mantener a tu comunidad informada.</span>
                            </li>
                            <li class="flex items-start space-x-2">
                                <span class="text-copihue-600 mt-1">•</span>
                                <span>Sube comprobantes a la sección de transparencia una vez recibas los fondos.</span>
                            </li>
                            <li class="flex items-start space-x-2">
                                <span class="text-copihue-600 mt-1">•</span>
                                <span>Activa recordatorios automáticos y comparte el enlace corto en redes sociales.</span>
                            </li>
                        </ul>
                    </div>
                </section>
            </aside>
        </div>
    </main>

    <?php if (!empty($dashboardCelebration)): ?>
        <?php
        $overlayData = $dashboardCelebration;
        include __DIR__ . '/../components/celebration-overlay.php';
        ?>
    <?php endif; ?>

    <?php include VIEWS_PATH . '/layouts/partials/footer.php'; ?>

    <script>
        (function () {
            var endpoint = '<?= htmlspecialchars($campaignMetricsEndpoint, ENT_QUOTES, 'UTF-8') ?>';
            if (!window.fetch || !endpoint) {
                return;
            }

            var cards = document.querySelectorAll('[data-campaign-card]');
            if (!cards.length) {
                return;
            }

            var cardIndex = {};
            cards.forEach(function (card) {
                var id = card.getAttribute('data-campaign-id');
                if (id) {
                    cardIndex[id] = card;
                }
            });

            function toNumber(value) {
                var number = parseFloat(value);
                return isNaN(number) ? 0 : number;
            }

            function formatCurrency(amount, currency) {
                currency = (currency || 'CLP').toUpperCase();

                try {
                    return new Intl.NumberFormat('es-CL', {
                        style: 'currency',
                        currency: currency
                    }).format(amount);
                } catch (error) {
                    var rounded = Math.round(amount);
                    var formatted = rounded.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    return (currency === 'CLP' ? '$' : currency + ' ') + formatted;
                }
            }

            function applyMetrics(data) {
                if (!data || typeof data.id === 'undefined') {
                    return;
                }

                var id = String(data.id);
                var card = cardIndex[id];
                if (!card) {
                    return;
                }

                var currency = (data.currency || card.getAttribute('data-campaign-currency') || 'CLP').toUpperCase();
                var goal = toNumber(data.goal_amount);
                var raised = toNumber(data.raised_amount);
                var progress = Math.min(100, Math.max(0, toNumber(data.progress)));

                var amountElement = card.querySelector('[data-campaign-amount]');
                if (amountElement) {
                    amountElement.textContent = formatCurrency(raised, currency) + ' de ' + formatCurrency(goal, currency);
                }

                var progressLabel = card.querySelector('[data-campaign-progress-label]');
                if (progressLabel) {
                    var formattedProgress = progress >= 10 ? progress.toFixed(0) : progress.toFixed(1);
                    progressLabel.textContent = formattedProgress.replace(/\.0$/, '') + '%';
                }

                var progressBar = card.querySelector('[data-campaign-progress-bar]');
                if (progressBar) {
                    progressBar.style.width = Math.min(100, progress) + '%';
                }
            }

            function fetchMetrics() {
                fetch(endpoint, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('request_failed');
                        }
                        return response.json();
                    })
                    .then(function (payload) {
                        if (!payload || !Array.isArray(payload.data)) {
                            return;
                        }
                        payload.data.forEach(applyMetrics);
                    })
                    .catch(function () {
                        // silencioso: evitamos ruido en consola del panel
                    });
            }

            fetchMetrics();
            setInterval(fetchMetrics, 30000);
        })();
    </script>

    <script>
        document.addEventListener('click', function (event) {
            if (event.target.closest('[data-notification-trigger]')) {
                // El archivo app.js maneja la lógica de notificaciones.
                return;
            }
        });
    </script>
</body>
</html>
