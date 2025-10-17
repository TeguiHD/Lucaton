<?php
require_once __DIR__ . '/../layouts/partials/flash-messages.php';
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/navigation.php';

$page_title = 'Mis Estadísticas — Lucatón';
$page_description = 'Analiza el desempeño de tus campañas y el impacto de tus aportes.';

$campaignStats = $campaignStats ?? [
    'total_campaigns' => 0,
    'total_raised' => 0.0,
    'total_supporters' => 0,
    'successful_campaigns' => 0,
];
$donationSummary = $donationSummary ?? [
    'total_donations' => 0,
    'total_amount' => 0.0,
    'completed_donations' => 0,
    'completed_amount' => 0.0,
    'average_completed' => 0.0,
    'last_donation_at' => null,
];
$donationStatusCounts = $donationStatusCounts ?? ['all' => $donationSummary['total_donations'] ?? 0];
$topCampaigns = $topCampaigns ?? [];
$recentCampaignDonations = $recentCampaignDonations ?? [];
$donorTrends = $donorTrends ?? [];

$formatCurrency = static function (float $amount): string {
    return '$' . number_format($amount, 0, ',', '.');
};

$formatDate = static function (?string $timestamp): string {
    if (!$timestamp) {
        return 'Sin fecha';
    }
    $time = strtotime($timestamp);
    return $time ? date('d/m/Y H:i', $time) : 'Sin fecha';
};

$successRate = 0.0;
if (!empty($campaignStats['total_campaigns'])) {
    $successRate = min(
        100,
        round((($campaignStats['successful_campaigns'] ?? 0) / max(1, $campaignStats['total_campaigns'])) * 100, 1)
    );
}

$statusStyles = [
    'pending' => ['label' => 'Pendiente', 'class' => 'bg-amber-100 text-amber-800'],
    'processing' => ['label' => 'En proceso', 'class' => 'bg-blue-100 text-blue-800'],
    'completed' => ['label' => 'Completada', 'class' => 'bg-emerald-100 text-emerald-800'],
    'failed' => ['label' => 'Fallida', 'class' => 'bg-rose-100 text-rose-700'],
    'refunded' => ['label' => 'Reembolsada', 'class' => 'bg-slate-100 text-slate-700'],
];

$statusOptions = [
    'completed' => 'Completadas',
    'processing' => 'En proceso',
    'pending' => 'Pendientes',
    'failed' => 'Fallidas',
    'refunded' => 'Reembolsadas',
];

$donationStatusCounts = array_merge(['all' => $donationStatusCounts['all'] ?? 0], $donationStatusCounts);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">

    <link rel="icon" type="image/svg+xml" href="<?= asset_url('images/favicon.svg') ?>">
    <link href="<?= asset_url('css/app.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('css/aliases.css') ?>" rel="stylesheet">
    <script defer src="<?= asset_url('js/app.js') ?>"></script>
</head>
<body class="bg-professional min-h-screen">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <?php include VIEWS_PATH . '/layouts/partials/header.php'; ?>

    <main id="main-content" class="max-w-7xl mx-auto py-8 sm:px-6 lg:px-8">
        <?php include_flash_messages(); ?>

        <?= render_breadcrumb([
            ['name' => 'Inicio', 'href' => Router::url('/')],
            ['name' => 'Mi Panel', 'href' => Router::url('panel')],
            ['name' => 'Mis Estadísticas', 'href' => Router::url('mis-estadisticas')],
        ]); ?>

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Mis estadísticas</h1>
            <p class="mt-2 text-sm text-gray-600">
                Observa cómo avanzan tus campañas, identifica el ritmo de tus donaciones y detecta oportunidades para volver a apoyar.
            </p>
        </div>

        <section class="grid gap-6 lg:grid-cols-4 mb-10">
            <div class="rounded-2xl bg-white shadow-soft border border-gray-100 p-5">
                <p class="text-sm font-medium text-gray-500">Campañas creadas</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900"><?= number_format((int)$campaignStats['total_campaigns']) ?></p>
                <p class="mt-1 text-xs text-gray-500"><?= number_format((int)$campaignStats['successful_campaigns']) ?> publicadas o completadas</p>
            </div>
            <div class="rounded-2xl bg-white shadow-soft border border-gray-100 p-5">
                <p class="text-sm font-medium text-gray-500">Monto recaudado</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900"><?= $formatCurrency((float)$campaignStats['total_raised']) ?></p>
                <p class="mt-1 text-xs text-gray-500">Suma total de aportes a tus campañas</p>
            </div>
            <div class="rounded-2xl bg-white shadow-soft border border-gray-100 p-5">
                <p class="text-sm font-medium text-gray-500">Donantes únicos</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900"><?= number_format((int)$campaignStats['total_supporters']) ?></p>
                <p class="mt-1 text-xs text-gray-500">Personas distintas que aportaron</p>
            </div>
            <div class="rounded-2xl bg-white shadow-soft border border-gray-100 p-5">
                <p class="text-sm font-medium text-gray-500">Tasa de éxito</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900"><?= number_format($successRate, 1) ?>%</p>
                <p class="mt-1 text-xs text-gray-500">Campañas publicadas o completadas respecto del total</p>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-4 mb-10">
            <div class="rounded-2xl bg-white shadow-soft border border-gray-100 p-5">
                <p class="text-sm font-medium text-gray-500">Aportes completados</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900"><?= number_format((int)$donationSummary['completed_donations']) ?></p>
                <p class="mt-1 text-xs text-gray-500"><?= number_format((int)$donationSummary['total_donations']) ?> totales registrados</p>
            </div>
            <div class="rounded-2xl bg-white shadow-soft border border-gray-100 p-5">
                <p class="text-sm font-medium text-gray-500">Monto aportado</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900"><?= $formatCurrency((float)$donationSummary['completed_amount']) ?></p>
                <p class="mt-1 text-xs text-gray-500">Sólo aportes confirmados</p>
            </div>
            <div class="rounded-2xl bg-white shadow-soft border border-gray-100 p-5">
                <p class="text-sm font-medium text-gray-500">Promedio por aporte</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900"><?= $formatCurrency((float)$donationSummary['average_completed']) ?></p>
                <p class="mt-1 text-xs text-gray-500">Incluye sólo aportes completados</p>
            </div>
            <div class="rounded-2xl bg-white shadow-soft border border-gray-100 p-5">
                <p class="text-sm font-medium text-gray-500">Último aporte</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900">
                    <?php
                        $relative = $donationSummary['last_donation_at'] ? $formatDate($donationSummary['last_donation_at']) : 'Sin registros';
                        echo htmlspecialchars($relative);
                    ?>
                </p>
                <p class="mt-1 text-xs text-gray-500">Fecha del registro más reciente</p>
            </div>
        </section>

        <section class="mb-10">
            <div class="rounded-2xl bg-white shadow-soft border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900">Distribución de estados de tus aportes</h2>
                <p class="mt-2 text-sm text-gray-600">Visualiza cuántos aportes siguen pendientes o necesitan revisión.</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <span class="inline-flex items-center rounded-full border border-gray-200 px-3 py-1 text-xs font-medium text-gray-600">
                        Total: <?= number_format($donationStatusCounts['all'] ?? 0) ?>
                    </span>
                    <?php foreach ($statusOptions as $statusKey => $label): ?>
                        <?php
                            $count = $donationStatusCounts[$statusKey] ?? 0;
                            $style = $statusStyles[$statusKey] ?? ['label' => $label, 'class' => 'bg-gray-100 text-gray-700'];
                        ?>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium <?= htmlspecialchars($style['class']) ?>">
                            <?= htmlspecialchars($style['label']) ?>: <?= number_format($count) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2 mb-10">
            <div class="rounded-2xl bg-white shadow-soft border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Campañas destacadas por recaudación</h2>
                    <a href="<?= htmlspecialchars(Router::url('mis-campanas')) ?>" class="text-sm font-medium text-copihue-600 hover:text-copihue-700">
                        Administrar campañas
                    </a>
                </div>
                <p class="mt-2 text-sm text-gray-600">Las cifras se actualizan cuando registras nuevas donaciones simuladas.</p>
                <?php if (empty($topCampaigns)): ?>
                    <p class="mt-6 text-sm text-gray-500">Crea una campaña o registra aportes para ver su rendimiento.</p>
                <?php else: ?>
                    <ul class="mt-6 space-y-4">
                        <?php foreach ($topCampaigns as $campaign): ?>
                            <?php
                                $raised = (float)($campaign['raised_amount'] ?? 0);
                                $goal = (float)($campaign['goal_amount'] ?? 0);
                                $progress = $goal > 0 ? min(100, round(($raised / $goal) * 100, 1)) : 0;
                                $status = strtolower((string)($campaign['status'] ?? ''));
                                $statusMeta = $statusStyles[$status] ?? ['label' => ucfirst($status), 'class' => 'bg-gray-100 text-gray-700'];
                                $publicPath = $campaign['public_path'] ?? CampaignPresenter::buildPublicPath($campaign);
                                $publicUrl = $publicPath ? Router::url($publicPath) : '#';
                            ?>
                            <li class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                                <div class="flex items-center justify-between">
                                    <a href="<?= htmlspecialchars($publicUrl) ?>" class="text-base font-semibold text-gray-900 hover:text-copihue-600">
                                        <?= htmlspecialchars($campaign['title'] ?? 'Campaña') ?>
                                    </a>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold <?= htmlspecialchars($statusMeta['class']) ?>">
                                        <?= htmlspecialchars($statusMeta['label']) ?>
                                    </span>
                                </div>
                                <p class="mt-2 text-sm text-gray-600"><?= $formatCurrency($raised) ?> de <?= $formatCurrency($goal) ?></p>
                                <div class="mt-3 h-2 rounded-full bg-white shadow-inner">
                                    <div class="h-2 rounded-full bg-copihue-500" style="width: <?= $progress ?>%;"></div>
                                </div>
                                <p class="mt-2 text-xs text-gray-500">Progreso <?= $progress ?>%</p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="rounded-2xl bg-white shadow-soft border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Tendencia de tus aportes</h2>
                    <span class="text-xs font-medium text-gray-500">Últimos 6 meses</span>
                </div>
                <p class="mt-2 text-sm text-gray-600">Revisa cómo evolucionan tus donaciones mes a mes.</p>
                <?php if (empty($donorTrends)): ?>
                    <p class="mt-6 text-sm text-gray-500">Registra aportes para visualizar esta información.</p>
                <?php else: ?>
                    <ul class="mt-6 space-y-3">
                        <?php foreach ($donorTrends as $trend): ?>
                            <?php
                                $period = $trend['period'] ?? '';
                                $label = 'Sin fecha';
                                if ($period !== '') {
                                    $date = DateTime::createFromFormat('Y-m', $period);
                                    if ($date) {
                                        $months = [
                                            '01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril',
                                            '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto',
                                            '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre',
                                        ];
                                        $label = ucfirst(($months[$date->format('m')] ?? $date->format('M')) . ' ' . $date->format('Y'));
                                    }
                                }
                            ?>
                            <li class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50/60 px-4 py-2.5 text-sm">
                                <span class="font-medium text-gray-800"><?= htmlspecialchars($label) ?></span>
                                <span class="text-gray-600">
                                    <?= $formatCurrency((float)($trend['total_amount'] ?? 0)) ?>
                                    <span class="text-xs text-gray-400">(<?= number_format((int)($trend['total_donations'] ?? 0)) ?> aportes)</span>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>

        <section class="rounded-2xl bg-white shadow-soft border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900">Últimos aportes recibidos en tus campañas</h2>
            <p class="mt-2 text-sm text-gray-600">Estos registros ayudan a detectar actividad reciente y posibles revisiones manuales.</p>
            <?php if (empty($recentCampaignDonations)): ?>
                <p class="mt-6 text-sm text-gray-500">Aún no hay aportes registrados en tus campañas o están en revisión.</p>
            <?php else: ?>
                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm text-gray-700">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th scope="col" class="px-4 py-3">Campaña</th>
                                <th scope="col" class="px-4 py-3">Donante</th>
                                <th scope="col" class="px-4 py-3">Monto</th>
                                <th scope="col" class="px-4 py-3">Estado</th>
                                <th scope="col" class="px-4 py-3">Registrado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($recentCampaignDonations as $donation): ?>
                                <?php
                                    $status = strtolower((string)($donation['status'] ?? ''));
                                    $badge = $statusStyles[$status] ?? ['label' => ucfirst($status), 'class' => 'bg-gray-100 text-gray-700'];
                                    $supporter = !empty($donation['is_anonymous'])
                                        ? 'Aporte anónimo'
                                        : ($donation['supporter_name'] ?? 'Donante registrado');
                                    $relativeTime = $donation['relative_time'] ?? '';
                                ?>
                                <tr class="hover:bg-gray-50/70">
                                    <td class="px-4 py-3 font-semibold text-gray-900"><?= htmlspecialchars($donation['campaign_title'] ?? 'Campaña') ?></td>
                                    <td class="px-4 py-3"><?= htmlspecialchars($supporter) ?></td>
                                    <td class="px-4 py-3 font-semibold text-gray-900"><?= $formatCurrency((float)($donation['amount'] ?? 0)) ?></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold <?= htmlspecialchars($badge['class']) ?>">
                                            <?= htmlspecialchars($badge['label']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <?= htmlspecialchars($relativeTime ?: $formatDate($donation['created_at'] ?? null)) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php include VIEWS_PATH . '/layouts/partials/footer.php'; ?>
</body>
</html>
