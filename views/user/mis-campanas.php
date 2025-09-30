<?php
require_once __DIR__ . '/../layouts/partials/flash-messages.php';
require_once __DIR__ . '/../components/navigation.php';

$campaigns = $campaigns ?? [];
$totalCampaigns = $totalCampaigns ?? 0;
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;
$hasMore = $hasMore ?? false;

$page_title = 'Mis campañas — Lucatón';
$page_description = 'Administra tus campañas, consulta su estado y accede rápidamente a sus estadísticas.';

$formatCurrency = static function (float $amount): string {
    return '$' . number_format($amount, 0, ',', '.');
};

$statusLabels = [
    'draft' => ['label' => 'Borrador', 'class' => 'bg-gray-100 text-gray-700'],
    'under_review' => ['label' => 'En revisión', 'class' => 'bg-amber-100 text-amber-800'],
    'published' => ['label' => 'Publicada', 'class' => 'bg-green-100 text-green-800'],
    'active' => ['label' => 'Activa', 'class' => 'bg-green-100 text-green-800'],
    'completed' => ['label' => 'Completada', 'class' => 'bg-blue-100 text-blue-800'],
    'paused' => ['label' => 'Pausada', 'class' => 'bg-yellow-100 text-yellow-800'],
    'cancelled' => ['label' => 'Cancelada', 'class' => 'bg-red-100 text-red-800'],
    'archived' => ['label' => 'Archivada', 'class' => 'bg-gray-100 text-gray-600'],
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
    <script defer src="<?= APP_URL ?>/public/assets/js/app.js?v=2025012801"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <?php include VIEWS_PATH . '/layouts/partials/header.php'; ?>

    <main id="main-content" class="max-w-7xl mx-auto py-8 sm:px-6 lg:px-8">
        <?php include_flash_messages(); ?>

        <?= render_breadcrumb([
            ['name' => 'Inicio', 'href' => Router::url('/')],
            ['name' => 'Mi Panel', 'href' => Router::url('panel')],
            ['name' => 'Mis campañas', 'href' => Router::url('mis-campanas')],
        ]); ?>

        <div class="mb-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-gray-500">Tus iniciativas</p>
                <h1 class="mt-1 text-3xl font-bold text-gray-900">Mis campañas</h1>
                <p class="mt-2 text-sm text-gray-600">Organiza tus campañas, revisa su progreso y mantén a tus donantes informados.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="<?= Router::url('campana/crear') ?>" class="btn-primary inline-flex items-center">
                    <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Crear campaña
                </a>
                <a href="<?= Router::url('panel') ?>" class="btn-secondary inline-flex items-center">
                    <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L4.5 12m0 0l5.25-5M4.5 12H19.5" />
                    </svg>
                    Volver al panel
                </a>
            </div>
        </div>

        <section class="mb-10">
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-500">Campañas totales</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        <?= number_format($totalCampaigns) ?>
                    </p>
                    <p class="mt-1 text-sm text-gray-500">Incluye campañas en revisión, publicadas y finalizadas.</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-500">Campañas activas</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        <?= number_format(array_reduce($campaigns, static function ($carry, $campaign) {
                            $status = $campaign['status'] ?? '';
                            if (in_array($status, ['published', 'active'], true)) {
                                return $carry + 1;
                            }
                            return $carry;
                        }, 0)) ?>
                    </p>
                    <p class="mt-1 text-sm text-gray-500">Considera campañas publicadas o activas actualmente.</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-500">Próximo paso recomendado</p>
                    <p class="mt-2 text-base text-gray-900">Comparte una actualización con tus donantes</p>
                    <p class="mt-1 text-sm text-gray-500">Mantén informada a tu comunidad sobre avances o necesidades.</p>
                </div>
            </div>
        </section>

        <section>
            <?php if (empty($campaigns)): ?>
                <div class="bg-white rounded-lg border border-dashed border-gray-200 p-12 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-copihue-100 text-copihue-600">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3v6m9-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="mt-6 text-xl font-semibold text-gray-900">Aún no tienes campañas</h2>
                    <p class="mt-2 text-sm text-gray-600">Cuando publiques tu primera campaña aparecerá acá junto a sus métricas clave.</p>
                    <div class="mt-6 flex justify-center">
                        <a href="<?= Router::url('campana/crear') ?>" class="btn-primary inline-flex items-center">
                            Comenzar mi primera campaña
                            <svg class="ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($campaigns as $campaign): ?>
                        <?php
                            $statusKey = $campaign['status'] ?? 'draft';
                            $status = $statusLabels[$statusKey] ?? ['label' => ucfirst($statusKey), 'class' => 'bg-gray-100 text-gray-700'];
                            $goal = (float)($campaign['goal_amount'] ?? 0);
                            $raised = (float)($campaign['raised_amount'] ?? 0);
                            $progress = $goal > 0 ? min(100, round(($raised / $goal) * 100)) : 0;
                            $donors = (int)($campaign['donor_count'] ?? 0);
                            $createdAt = !empty($campaign['created_at']) ? date('d/m/Y', strtotime($campaign['created_at'])) : '—';
                            $campaignTitle = $campaign['title'] ?? 'Campaña sin título';
                            $campaignSummary = $campaign['summary'] ?? '';
                            $campaignId = $campaign['id'] ?? null;
                            $campaignSlug = $campaign['slug'] ?? null;
                            $viewUrl = $campaignSlug ? Router::url('campana/' . $campaignSlug) : Router::url('campana/' . $campaignId);
                            $editUrl = $campaignId ? Router::url('campana/' . $campaignId . '/editar') : null;
                        ?>
                        <article class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                            <div class="flex flex-col gap-4 p-6 md:flex-row md:items-start md:justify-between">
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?= $status['class'] ?>">
                                            <?= htmlspecialchars($status['label']) ?>
                                        </span>
                                        <?php if (!empty($campaign['visibility']) && $campaign['visibility'] !== 'public'): ?>
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                                                Visibilidad: <?= htmlspecialchars($campaign['visibility']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="inline-flex items-center text-xs text-gray-500">
                                            Creada el <?= htmlspecialchars($createdAt) ?>
                                        </span>
                                    </div>
                                    <h2 class="mt-3 text-xl font-semibold text-gray-900">
                                        <a href="<?= htmlspecialchars($viewUrl) ?>" class="hover:text-copihue-600 transition-colors">
                                            <?= htmlspecialchars($campaignTitle) ?>
                                        </a>
                                    </h2>
                                    <?php if (!empty($campaignSummary)): ?>
                                        <p class="mt-2 text-sm text-gray-600 leading-6">
                                            <?= htmlspecialchars($campaignSummary) ?>
                                        </p>
                                    <?php endif; ?>
                                    <dl class="mt-4 grid grid-cols-2 gap-4 text-sm text-gray-600 sm:grid-cols-4">
                                        <div>
                                            <dt class="font-medium text-gray-900">Meta</dt>
                                            <dd class="mt-1 text-gray-700"><?= htmlspecialchars($formatCurrency($goal)) ?></dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-gray-900">Recaudado</dt>
                                            <dd class="mt-1 text-gray-700"><?= htmlspecialchars($formatCurrency($raised)) ?></dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-gray-900">Avance</dt>
                                            <dd class="mt-1 text-gray-700"><?= $progress ?>%</dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-gray-900">Donantes</dt>
                                            <dd class="mt-1 text-gray-700"><?= number_format($donors) ?></dd>
                                        </div>
                                    </dl>
                                </div>
                                <div class="flex-shrink-0 w-full md:w-48 space-y-3">
                                    <div class="flex items-center justify-between text-sm text-gray-500">
                                        <span class="font-medium text-gray-900">Estado general</span>
                                        <span><?= $progress >= 100 ? 'Completada' : ($progress >= 50 ? 'Avanzando' : 'Recién iniciada') ?></span>
                                    </div>
                                    <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                                        <div class="h-full rounded-full bg-copihue-500" style="width: <?= $progress ?>%"></div>
                                    </div>
                                    <div class="flex flex-col gap-2 text-sm">
                                        <a href="<?= htmlspecialchars($viewUrl) ?>" class="inline-flex items-center justify-center rounded-md border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:border-copihue-500 hover:text-copihue-600">
                                            Ver campaña
                                        </a>
                                        <?php if ($editUrl): ?>
                                            <a href="<?= htmlspecialchars($editUrl) ?>" class="inline-flex items-center justify-center rounded-md bg-copihue-600 px-3 py-2 text-sm font-medium text-white hover:bg-copihue-700">
                                                Editar campaña
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="mt-8">
                        <?= render_pagination([
                            'current_page' => $page,
                            'total_pages' => $totalPages,
                            'base_url' => Router::url('mis-campanas'),
                        ]); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>

    <?php include VIEWS_PATH . '/layouts/partials/footer.php'; ?>
</body>
</html>
