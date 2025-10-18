<?php
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$page_title = $page_title ?? 'Estadísticas';
$current_page = $current_page ?? 'admin-stats';
$statistics = $statistics ?? [];
$supported = (bool)($statistics['supported'] ?? false);

$campaignStats = $statistics['campaign'] ?? [];
$donationStats = $statistics['donations'] ?? [];
$engagementStats = $statistics['engagement'] ?? [];
$categoryLeaders = $statistics['category_leaders'] ?? [];
$aiByCategory = $statistics['ai_by_category'] ?? [];
$statusBreakdown = $statistics['status_breakdown'] ?? [];
$donationSeries = $statistics['donation_series'] ?? [];
$topCampaigns = $statistics['top_campaigns'] ?? [];

$goalProgress = $statistics['goal_progress'] ?? [];
$goalProgressSeries = $goalProgress['series'] ?? [];
$goalProgressJson = htmlspecialchars(json_encode($goalProgressSeries, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
$goalProgressAverage = $goalProgress['average_attainment'] ?? null;
$goalProgressTotalCampaigns = (int)($goalProgress['total_campaigns'] ?? 0);
$goalProgressTotalRaised = (float)($goalProgress['total_raised'] ?? 0.0);

$donorSegments = $statistics['donor_segments'] ?? [];
$donorSegmentsSeries = $donorSegments['series'] ?? [];
$donorSegmentsTotal = (int)($donorSegments['total_donors'] ?? 0);
$donorSegmentsAmount = (float)($donorSegments['total_amount'] ?? 0.0);
$donorSegmentsByKey = [];
foreach ($donorSegmentsSeries as $segmentEntry) {
    $segmentKey = $segmentEntry['key'] ?? ('segment_' . count($donorSegmentsByKey));
    $donorSegmentsByKey[$segmentKey] = $segmentEntry;
}

$paymentMix = $statistics['payment_mix'] ?? [];
$paymentMixSeries = $paymentMix['series'] ?? [];
$paymentMixTotalDonations = (int)($paymentMix['total_donations'] ?? 0);
$paymentMixTotalAmount = (float)($paymentMix['total_amount'] ?? 0.0);
$paymentMixCount = [];
$paymentMixAmount = [];
foreach ($paymentMixSeries as $entry) {
    $label = $entry['label'] ?? 'Medio';
    $paymentMixCount[$label] = (int)($entry['count'] ?? 0);
    $paymentMixAmount[$label] = round((float)($entry['amount'] ?? 0.0), 2);
}
$paymentMixCountJson = htmlspecialchars(json_encode($paymentMixCount, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
$paymentMixAmountJson = htmlspecialchars(json_encode($paymentMixAmount, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

$processingMetrics = $statistics['processing_metrics'] ?? [];
$insights = $statistics['insights'] ?? [];

$totalCampanas = (int)($campaignStats['total'] ?? 0);
$campanasIA = (int)($campaignStats['ai'] ?? 0);
$campanasManual = (int)($campaignStats['manual'] ?? 0);
$aiPercentage = $campaignStats['ai_percentage'] ?? 0.0;
$publicadas = (int)($campaignStats['publicadas'] ?? 0);
$enRevision = (int)($campaignStats['en_revision'] ?? 0);
$nuevas30 = (int)($campaignStats['nuevas_30_dias'] ?? 0);
$metaPromedio = $campaignStats['meta_promedio'] ?? null;
$metaPromedioDisplay = $metaPromedio !== null ? '$' . number_format($metaPromedio, 0, ',', '.') : 'Sin datos';
$goalProgressAverageDisplay = $goalProgressAverage !== null ? number_format($goalProgressAverage, 1, ',', '.') . '%' : 'Sin datos';

$montoTotal = (float)($donationStats['monto_total'] ?? 0);
$totalDonaciones = (int)($donationStats['total_donaciones'] ?? 0);
$promedioDonacion = $donationStats['promedio_donacion'] ?? null;
$promedioDonacionDisplay = $promedioDonacion !== null ? '$' . number_format($promedioDonacion, 0, ',', '.') : 'Sin datos';
$conversionRate = $donationStats['conversion_rate'] ?? null;
$ratioVisitantes = $donationStats['ratio_visitantes_donantes'] ?? null;
$donaciones30 = (int)($donationStats['donaciones_30_dias'] ?? 0);
$monto30 = (float)($donationStats['monto_30_dias'] ?? 0);
$conversionRatioDisplay = $ratioVisitantes !== null ? number_format($ratioVisitantes, 2, ',', '.') . ':1' : 'sin datos';

$totalVisitantes = (int)($engagementStats['total_visitantes'] ?? 0);
$totalDonantes = (int)($engagementStats['total_donantes'] ?? 0);
$visitas30 = (int)($engagementStats['visitas_30_dias'] ?? 0);
$totalCompartidos = (int)($engagementStats['total_compartidos'] ?? 0);
$compartidos30 = (int)($engagementStats['compartidos_30_dias'] ?? 0);

$shareRate = $totalVisitantes > 0 ? round(($totalCompartidos / max(1, $totalVisitantes)) * 100, 2) : null;
$shareToDonationRate = $totalCompartidos > 0 ? round(($totalDonantes / max(1, $totalCompartidos)) * 100, 2) : null;
$visitasProgress = $totalVisitantes > 0 ? min(100, round(($visitas30 / max(1, $totalVisitantes)) * 100, 1)) : null;
$sharesProgress = $totalCompartidos > 0 ? min(100, round(($compartidos30 / max(1, $totalCompartidos)) * 100, 1)) : null;
$donacionesProgress = $totalDonaciones > 0 ? min(100, round(($donaciones30 / max(1, $totalDonaciones)) * 100, 1)) : null;
$conversionDisplay = $conversionRate !== null ? number_format($conversionRate, 2, ',', '.') . '%' : 'Sin datos';
$averageProcessingHours = $processingMetrics['avg_hours'] ?? null;
$medianProcessingHours = $processingMetrics['median_hours'] ?? null;
$p90ProcessingHours = $processingMetrics['p90_hours'] ?? null;
$avgProcessing30d = $processingMetrics['avg_hours_30d'] ?? null;
$processingWithin24 = $processingMetrics['within_24h_percentage'] ?? null;

$funnel = [
    ['label' => 'Visitas', 'value' => $totalVisitantes, 'color' => 'bg-sky-500'],
    ['label' => 'Compartidos', 'value' => $totalCompartidos, 'color' => 'bg-amber-500'],
    ['label' => 'Donantes', 'value' => $totalDonantes, 'color' => 'bg-emerald-500'],
];
$funnelMax = max(1, max(array_column($funnel, 'value')));

$statusJson = htmlspecialchars(json_encode($statusBreakdown, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
$donationSeriesJson = htmlspecialchars(json_encode($donationSeries, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
$topCampaignsJson = htmlspecialchars(json_encode($topCampaigns, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

ob_start();
?>
<div class="space-y-6">
    <?php include_flash_messages(); ?>

    <?php if (!$supported): ?>
        <section class="bg-white shadow-soft rounded-3xl border border-dashed border-amber-300 p-8 text-center">
            <h2 class="text-lg font-semibold text-gray-900">Aún no hay datos de campañas registrados</h2>
            <p class="mt-2 text-sm text-gray-600">
                Necesitas ejecutar las migraciones y poblar datos de campañas, donaciones y métricas para visualizar indicadores.
                Cuando la información esté disponible, este panel mostrará conversiones, desempeño por categoría y adopción de IA.
            </p>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <article class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6 flex flex-col gap-6">
                <header class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Categorías con mayor recaudación</h2>
                        <p class="text-sm text-gray-500">Consolida resultados para priorizar alianzas estratégicas y focos narrativos.</p>
                    </div>
                    <?php if (!empty($categoryLeaders)): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-copihue-100 text-copihue-700 text-xs font-semibold">
                            <?= count($categoryLeaders) ?> categorías
                        </span>
                    <?php endif; ?>
                </header>
                <?php if (empty($categoryLeaders)): ?>
                    <p class="text-sm text-gray-500">Todavía no hay donaciones suficientes para segmentar por categoría.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($categoryLeaders as $category): ?>
                            <article class="p-4 rounded-2xl border border-gray-100 bg-gray-50 flex items-center justify-between gap-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">
                                        <?= htmlspecialchars($category['name'] ?? 'Categoría', ENT_QUOTES, 'UTF-8') ?>
                                    </h3>
                                    <p class="mt-1 text-xs text-gray-500">$<?= number_format((float)($category['raised'] ?? 0), 0, ',', '.') ?> recaudados</p>
                                </div>
                                <dl class="flex items-center gap-6 text-xs text-gray-500">
                                    <div class="text-right">
                                        <dt>Donantes</dt>
                                        <dd class="font-semibold text-gray-900"><?= number_format((int)($category['donors'] ?? 0), 0, ',', '.') ?></dd>
                                    </div>
                                    <div class="text-right">
                                        <dt>Visitas</dt>
                                        <dd class="font-semibold text-gray-900"><?= number_format((int)($category['views'] ?? 0), 0, ',', '.') ?></dd>
                                    </div>
                                </dl>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>

            <article class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6 flex flex-col gap-6">
                <header>
                    <h2 class="text-lg font-semibold text-gray-900">IA por categoría</h2>
                    <p class="text-sm text-gray-500">Observa dónde la asistencia de IA entrega mayor recaudación acumulada.</p>
                </header>
                <?php if (empty($aiByCategory)): ?>
                    <p class="text-sm text-gray-500">Aún no hay campañas asistidas por IA clasificadas por categoría.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($aiByCategory as $entry): ?>
                            <article class="flex items-center justify-between rounded-2xl border border-gray-100 bg-gray-50 p-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($entry['name'] ?? 'Categoría', ENT_QUOTES, 'UTF-8') ?></h3>
                                    <p class="mt-1 text-xs text-gray-500">
                                        $<?= number_format((float)($entry['raised'] ?? 0), 0, ',', '.') ?> recaudados · <?= number_format((int)($entry['campaigns'] ?? 0), 0, ',', '.') ?> campañas asistidas
                                    </p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>
        </section>
    <?php else: ?>
        <section class="rounded-3xl border border-gray-200 bg-white p-8 shadow-soft">
            <div class="flex flex-col gap-6">
                <header>
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Inteligencia de campañas Lucatón</h1>
                    <p class="mt-2 max-w-3xl text-sm text-gray-600">
                        Integra métricas críticas para la tesis: desempeño de campañas, adopción de IA y tiempos operativos para sostener decisiones basadas en evidencia.
                    </p>
                </header>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Recaudación consolidada</dt>
                        <dd class="mt-2 text-2xl font-semibold text-gray-900">$<?= number_format($montoTotal, 0, ',', '.') ?></dd>
                        <p class="mt-1 text-xs text-gray-500">+$<?= number_format($monto30, 0, ',', '.') ?> en los últimos 30 días</p>
                    </div>
                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Donantes únicos</dt>
                        <dd class="mt-2 text-2xl font-semibold text-gray-900"><?= number_format($totalDonantes, 0, ',', '.') ?></dd>
                        <p class="mt-1 text-xs text-gray-500"><?= number_format($donaciones30, 0, ',', '.') ?> aportes recientes</p>
                    </div>
                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Conversión visitante → donante</dt>
                        <dd class="mt-2 text-2xl font-semibold text-gray-900"><?= $conversionDisplay ?></dd>
                        <p class="mt-1 text-xs text-gray-500">Ratio <?= $conversionRatioDisplay ?></p>
                    </div>
                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Cumplimiento promedio de meta</dt>
                        <dd class="mt-2 text-2xl font-semibold text-gray-900"><?= $goalProgressAverageDisplay ?></dd>
                        <p class="mt-1 text-xs text-gray-500">
                            <?= $goalProgressTotalCampaigns > 0 ? number_format($goalProgressTotalCampaigns, 0, ',', '.') . ' campañas con meta activa' : 'Aún sin datos suficientes' ?>
                        </p>
                    </div>
                </dl>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 mt-6 md:grid-cols-2 xl:grid-cols-4">
            <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100 flex flex-col gap-4">
                <header class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Campañas activas</h3>
                    <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-600">
                        +<?= number_format($nuevas30, 0, ',', '.') ?> nuevas
                    </span>
                </header>
                <div>
                    <p class="text-3xl font-semibold text-gray-900 leading-tight">
                        <?= number_format($totalCampanas, 0, ',', '.') ?>
                    </p>
                    <p class="mt-2 text-sm text-gray-500">
                        <?= number_format($publicadas, 0, ',', '.') ?> publicadas · <?= number_format($enRevision, 0, ',', '.') ?> en revisión
                    </p>
                </div>
                <footer class="text-xs text-gray-500">Total vigentes en el ecosistema</footer>
            </article>

            <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100 flex flex-col gap-4">
                <header class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Adopción de IA</h3>
                    <span class="inline-flex items-center gap-1 rounded-full bg-copihue-100 px-3 py-1 text-xs font-semibold text-copihue-700">
                        <?= number_format($aiPercentage, 1, ',', '.') ?>%
                    </span>
                </header>
                <div>
                    <p class="text-3xl font-semibold text-gray-900 leading-tight">
                        <?= number_format($campanasIA, 0, ',', '.') ?>
                    </p>
                    <p class="mt-2 text-sm text-gray-500">
                        <?= number_format($campanasManual, 0, ',', '.') ?> campañas manuales en paralelo
                    </p>
                </div>
                <div class="mt-2">
                    <div class="h-2 rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-copihue-500" style="width: <?= max(0, min(100, (float)$aiPercentage)) ?>%;"></div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Cobertura IA sobre el total de campañas</p>
                </div>
            </article>

            <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100 flex flex-col gap-4">
                <header class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Audiencia comprometida</h3>
                    <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-600">
                        <?= number_format($visitas30, 0, ',', '.') ?> visitas recientes
                    </span>
                </header>
                <div>
                    <p class="text-3xl font-semibold text-gray-900 leading-tight">
                        <?= number_format($totalVisitantes, 0, ',', '.') ?>
                    </p>
                    <p class="mt-2 text-sm text-gray-500">
                        <?= number_format($totalCompartidos, 0, ',', '.') ?> compartidos orgánicos acumulados
                    </p>
                </div>
                <?php if ($visitasProgress !== null): ?>
                    <div class="mt-2">
                        <div class="h-2 rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-sky-500" style="width: <?= $visitasProgress ?>%;"></div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500"><?= number_format($visitasProgress, 1, ',', '.') ?>% del total ocurrió este mes</p>
                    </div>
                <?php endif; ?>
            </article>

            <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100 flex flex-col gap-4">
                <header class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Difusión y ticket promedio</h3>
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-600">
                        <?= number_format($totalCompartidos, 0, ',', '.') ?> compartidos
                    </span>
                </header>
                <div>
                    <p class="text-3xl font-semibold text-gray-900 leading-tight">
                        <?= $shareRate !== null ? number_format($shareRate, 2, ',', '.') . '%' : 'Sin datos' ?>
                    </p>
                    <p class="mt-2 text-sm text-gray-500">
                        Ticket promedio: <?= $promedioDonacionDisplay ?>
                    </p>
                </div>
                <?php if ($sharesProgress !== null): ?>
                    <div class="mt-2">
                        <div class="h-2 rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-amber-500" style="width: <?= $sharesProgress ?>%;"></div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500"><?= number_format($sharesProgress, 1, ',', '.') ?>% del alcance ocurrió este mes</p>
                    </div>
                <?php endif; ?>
                <footer class="text-xs text-gray-500">
                    Ratio donantes/compartidos <?= $shareToDonationRate !== null ? number_format($shareToDonationRate, 2, ',', '.') . '%' : 'sin datos' ?>
                </footer>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <article class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6 xl:col-span-2">
                <header class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Actividad últimos 30 días</h2>
                        <p class="text-sm text-gray-500">Evalúa montos recaudados y volumen de aportes para reportes de la tesis.</p>
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <span class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                            Corte: <?= date('d/m/Y') ?>
                        </span>
                        <div class="flex items-center gap-1 rounded-full bg-gray-100 p-1 text-xs font-semibold text-gray-600" data-chart-toggle-group="donation-line-chart">
                            <button type="button" class="rounded-full bg-white px-3 py-1 text-gray-900 shadow-sm transition" data-chart-toggle="donation-line-chart" data-mode="value" data-active="true">Montos</button>
                            <button type="button" class="rounded-full px-3 py-1 text-gray-600 transition hover:text-gray-900" data-chart-toggle="donation-line-chart" data-mode="donations">Donaciones</button>
                        </div>
                    </div>
                </header>

                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="p-4 rounded-2xl border border-gray-100 bg-gray-50">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Donaciones</p>
                        <p class="mt-1 text-xl font-semibold text-gray-900"><?= number_format($donaciones30, 0, ',', '.') ?> aportes</p>
                        <p class="mt-1 text-sm text-gray-500">$<?= number_format($monto30, 0, ',', '.') ?> recaudados</p>
                        <?php if ($donacionesProgress !== null): ?>
                            <div class="mt-3 h-2 rounded-full bg-white/80">
                                <div class="h-full rounded-full bg-copihue-500" style="width: <?= $donacionesProgress ?>%;"></div>
                            </div>
                            <p class="mt-1 text-[11px] uppercase tracking-wide text-copihue-700/80"><?= number_format($donacionesProgress, 1, ',', '.') ?>% del acumulado</p>
                        <?php endif; ?>
                    </div>

                    <div class="p-4 rounded-2xl border border-gray-100 bg-gray-50">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Visitas</p>
                        <p class="mt-1 text-xl font-semibold text-gray-900"><?= number_format($visitas30, 0, ',', '.') ?> visitas</p>
                        <p class="mt-1 text-sm text-gray-500">Total histórico: <?= number_format($totalVisitantes, 0, ',', '.') ?></p>
                        <?php if ($visitasProgress !== null): ?>
                            <div class="mt-3 h-2 rounded-full bg-white/80">
                                <div class="h-full rounded-full bg-sky-500" style="width: <?= $visitasProgress ?>%;"></div>
                            </div>
                            <p class="mt-1 text-[11px] uppercase tracking-wide text-sky-700/80"><?= number_format($visitasProgress, 1, ',', '.') ?>% del acumulado</p>
                        <?php endif; ?>
                    </div>

                    <div class="p-4 rounded-2xl border border-gray-100 bg-gray-50">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Compartidos</p>
                        <p class="mt-1 text-xl font-semibold text-gray-900"><?= number_format($compartidos30, 0, ',', '.') ?> veces</p>
                        <p class="mt-1 text-sm text-gray-500">Acumulado: <?= number_format($totalCompartidos, 0, ',', '.') ?></p>
                        <?php if ($sharesProgress !== null): ?>
                            <div class="mt-3 h-2 rounded-full bg-white/80">
                                <div class="h-full rounded-full bg-amber-500" style="width: <?= $sharesProgress ?>%;"></div>
                            </div>
                            <p class="mt-1 text-[11px] uppercase tracking-wide text-amber-700/80"><?= number_format($sharesProgress, 1, ',', '.') ?>% del acumulado</p>
                        <?php endif; ?>
                    </div>

                    <div class="p-4 rounded-2xl border border-gray-100 bg-gray-50">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Meta promedio</p>
                        <p class="mt-1 text-xl font-semibold text-gray-900">
                            <?= $metaPromedio !== null ? '$' . number_format($metaPromedio, 0, ',', '.') : 'Sin datos' ?>
                        </p>
                        <p class="mt-1 text-sm text-gray-500">Referente para planificar nuevas campañas.</p>
                    </div>
                </div>

                <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2">
                        <canvas id="donation-line-chart" data-series="<?= $donationSeriesJson ?>" data-mode="value"></canvas>
                        <p class="mt-3 text-xs text-gray-500" data-chart-feedback="donation-line-chart"></p>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Embudo</h3>
                            <span class="text-xs text-gray-500">Visitas → Compartidos → Donantes</span>
                        </div>
                        <div class="grid grid-cols-3 gap-3 items-end" data-funnel>
                            <?php foreach ($funnel as $stage): ?>
                                <?php
                                    $height = $funnelMax > 0 ? max(10, round(($stage['value'] / $funnelMax) * 120)) : 10;
                                    $tooltip = number_format($stage['value'], 0, ',', '.');
                                ?>
                                <div class="flex flex-col items-center gap-2">
                                    <div class="group relative flex h-36 w-12 items-end justify-center rounded-lg bg-gray-100">
                                        <div class="<?= $stage['color'] ?> w-10 rounded-lg transition-all duration-700 ease-out" style="height: <?= $height ?>px" data-funnel-bar data-value="<?= $stage['value'] ?>"></div>
                                        <div class="absolute -top-8 z-10 hidden whitespace-nowrap rounded-full bg-gray-900 px-3 py-1 text-xs font-semibold text-white shadow-lg group-hover:block">
                                            <?= $tooltip ?>
                                        </div>
                                    </div>
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 text-center">
                                        <?= htmlspecialchars($stage['label']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="mt-3 text-xs text-gray-500 text-center">Pasa el cursor para ver totales exactos.</p>
                    </div>
                </div>
            </article>

            <article class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6 flex flex-col gap-6">
                <header class="flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Avance hacia la meta</h2>
                            <p class="text-sm text-gray-500">Segmenta campañas según el porcentaje de cumplimiento para priorizar acompañamiento.</p>
                        </div>
                        <?php if (!empty($goalProgressSeries)): ?>
                            <div class="flex items-center gap-1 rounded-full bg-gray-100 p-1 text-xs font-semibold text-gray-600" data-chart-toggle-group="goal-progress-chart">
                                <button type="button" class="rounded-full bg-white px-3 py-1 text-gray-900 shadow-sm transition" data-chart-toggle="goal-progress-chart" data-mode="count" data-active="true">Campañas</button>
                                <button type="button" class="rounded-full px-3 py-1 text-gray-600 transition hover:text-gray-900" data-chart-toggle="goal-progress-chart" data-mode="raised">Recaudación</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($goalProgressTotalCampaigns > 0): ?>
                        <p class="text-xs text-gray-500">Promedio general: <?= $goalProgressAverageDisplay ?> · Total recaudado: $<?= number_format($goalProgressTotalRaised, 0, ',', '.') ?></p>
                    <?php endif; ?>
                </header>
                <?php if (empty($goalProgressSeries)): ?>
                    <p class="text-sm text-gray-500">Aún no hay campañas con metas económicas registradas para generar la visualización.</p>
                <?php else: ?>
                    <div>
                        <canvas id="goal-progress-chart" data-series="<?= $goalProgressJson ?>" data-mode="count"></canvas>
                        <p class="mt-3 text-xs text-gray-500" data-chart-feedback="goal-progress-chart"></p>
                    </div>
                    <ul class="grid grid-cols-1 gap-3 text-sm text-gray-600">
                        <?php foreach ($goalProgressSeries as $bucket): ?>
                            <li class="flex items-center justify-between rounded-2xl border border-gray-100 bg-gray-50 px-4 py-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($bucket['label']) ?></p>
                                    <p class="text-xs text-gray-500">
                                        $<?= number_format((float)($bucket['raised'] ?? 0), 0, ',', '.') ?> recaudados · <?= number_format((float)($bucket['raised_percentage'] ?? 0), 1, ',', '.') ?>% del monto
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-gray-900"><?= number_format((int)($bucket['count'] ?? 0), 0, ',', '.') ?> campañas</p>
                                    <p class="text-xs text-gray-500"><?= number_format((float)($bucket['count_percentage'] ?? 0), 1, ',', '.') ?>% del total</p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <article class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6 flex flex-col gap-6">
                <header>
                    <h2 class="text-lg font-semibold text-gray-900">Distribución por estado</h2>
                    <p class="text-sm text-gray-500">Controla el pipeline editorial y de activación para priorizar revisiones.</p>
                </header>
                <canvas id="status-doughnut-chart" data-series="<?= $statusJson ?>"></canvas>
                <p class="mt-3 text-xs text-gray-500" data-chart-feedback="status-doughnut-chart"></p>
            </article>

            <article class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6 flex flex-col gap-6">
                <header class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Lealtad de donantes</h2>
                        <p class="text-sm text-gray-500">Segmenta aportantes para diseñar estrategias de retención.</p>
                    </div>
                    <?php if ($donorSegmentsTotal > 0): ?>
                        <span class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                            <?= number_format($donorSegmentsTotal, 0, ',', '.') ?> donantes
                        </span>
                    <?php endif; ?>
                </header>
                <?php if (empty($donorSegmentsSeries)): ?>
                    <p class="text-sm text-gray-500">Todavía no hay suficientes donaciones para segmentar hábitos.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($donorSegmentsSeries as $segment): ?>
                            <?php
                                $segmentColor = $segment['color'] ?? '#0EA5E9';
                                $segmentCountPercent = (float)($segment['count_percentage'] ?? 0);
                                $segmentAmountPercent = (float)($segment['amount_percentage'] ?? 0);
                            ?>
                            <article class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($segment['label'] ?? 'Segmento', ENT_QUOTES, 'UTF-8') ?></h3>
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold text-gray-600" style="background-color: <?= htmlspecialchars($segmentColor, ENT_QUOTES, 'UTF-8') ?>1A;">
                                        <?= number_format($segmentCountPercent, 1, ',', '.') ?>%
                                    </span>
                                </div>
                                <div class="mt-3 h-2 rounded-full bg-white">
                                    <div class="h-full rounded-full" style="background: <?= htmlspecialchars($segmentColor, ENT_QUOTES, 'UTF-8') ?>; width: <?= max(3, min(100, $segmentCountPercent)) ?>%;"></div>
                                </div>
                                <p class="mt-3 text-xs text-gray-500">
                                    <?= number_format((int)($segment['count'] ?? 0), 0, ',', '.') ?> donantes · $<?= number_format((float)($segment['amount'] ?? 0), 0, ',', '.') ?>
                                    (<?= number_format($segmentAmountPercent, 1, ',', '.') ?>% del monto)
                                </p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>

            <article class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6 flex flex-col gap-6">
                <header class="flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Medios de pago y SLA</h2>
                            <p class="text-sm text-gray-500">Monitorea la mezcla de medios y la velocidad de procesamiento.</p>
                        </div>
                        <?php if (!empty($paymentMixSeries)): ?>
                            <div class="flex items-center gap-1 rounded-full bg-gray-100 p-1 text-xs font-semibold text-gray-600" data-chart-toggle-group="payment-mix-chart">
                                <button type="button" class="rounded-full bg-white px-3 py-1 text-gray-900 shadow-sm transition" data-chart-toggle="payment-mix-chart" data-mode="amount" data-active="true">Montos</button>
                                <button type="button" class="rounded-full px-3 py-1 text-gray-600 transition hover:text-gray-900" data-chart-toggle="payment-mix-chart" data-mode="count">Transacciones</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($paymentMixTotalDonations > 0): ?>
                        <p class="text-xs text-gray-500">
                            Total transacciones: <?= number_format($paymentMixTotalDonations, 0, ',', '.') ?> · Monto: $<?= number_format($paymentMixTotalAmount, 0, ',', '.') ?>
                        </p>
                    <?php endif; ?>
                </header>
                <?php if (empty($paymentMixSeries)): ?>
                    <p class="text-sm text-gray-500">Aún no se registran donaciones para calcular la mezcla de medios.</p>
                <?php else: ?>
                    <div>
                        <canvas
                            id="payment-mix-chart"
                            data-series-amount="<?= $paymentMixAmountJson ?>"
                            data-series-count="<?= $paymentMixCountJson ?>"
                            data-mode="amount"></canvas>
                        <p class="mt-3 text-xs text-gray-500" data-chart-feedback="payment-mix-chart"></p>
                    </div>
                <?php endif; ?>
                <div class="grid grid-cols-2 gap-4 text-xs text-gray-500">
                    <div>
                        <p class="font-semibold text-gray-900 text-sm">Promedio histórico</p>
                        <p><?= $averageProcessingHours !== null ? number_format($averageProcessingHours, 1, ',', '.') . ' h' : 'Sin datos' ?></p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 text-sm">Mediana</p>
                        <p><?= $medianProcessingHours !== null ? number_format($medianProcessingHours, 1, ',', '.') . ' h' : 'Sin datos' ?></p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 text-sm">P90</p>
                        <p><?= $p90ProcessingHours !== null ? number_format($p90ProcessingHours, 1, ',', '.') . ' h' : 'Sin datos' ?></p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 text-sm">Promedio 30 días</p>
                        <p><?= $avgProcessing30d !== null ? number_format($avgProcessing30d, 1, ',', '.') . ' h' : 'Sin datos' ?></p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 text-sm">Procesado &lt; 24h</p>
                        <p><?= $processingWithin24 !== null ? number_format($processingWithin24, 1, ',', '.') . '%' : 'Sin datos' ?></p>
                    </div>
                </div>
            </article>
        </section>

        <section class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <article class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6 flex flex-col gap-6">
                <header>
                    <h2 class="text-lg font-semibold text-gray-900">Hallazgos accionables</h2>
                    <p class="text-sm text-gray-500">Insights listos para documentar en la tesis o activar planes de mejora.</p>
                </header>
                <?php if (empty($insights)): ?>
                    <p class="text-sm text-gray-500">Todavía no hay hallazgos automáticos; valida que existan campañas, donaciones y métricas actualizadas.</p>
                <?php else: ?>
                    <ul class="space-y-4 text-sm text-gray-600">
                        <?php foreach ($insights as $insight): ?>
                            <li class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                                <h3 class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($insight['title'] ?? 'Insight', ENT_QUOTES, 'UTF-8') ?></h3>
                                <p class="mt-2 text-sm text-gray-600"><?= htmlspecialchars($insight['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </article>

            <article class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6 flex flex-col gap-6">
                <header class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Top campañas por visitas</h2>
                        <p class="text-sm text-gray-500">Evalúa dónde enfocar comunicación, curaduría y asesoría.</p>
                    </div>
                    <?php if (!empty($topCampaigns)): ?>
                        <span class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                            <?= count($topCampaigns) ?> campañas
                        </span>
                    <?php endif; ?>
                </header>
                <?php if (empty($topCampaigns)): ?>
                    <p class="text-sm text-gray-500">Aún no hay campañas con visitas registradas.</p>
                <?php else: ?>
                    <canvas id="top-campaigns-chart" data-series="<?= $topCampaignsJson ?>"></canvas>
                    <p class="mt-3 text-xs text-gray-500" data-chart-feedback="top-campaigns-chart"></p>
                <?php endif; ?>
            </article>
        </section>
    <?php endif; ?>
</div>

<?php if ($supported): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dpr = window.devicePixelRatio || 1;
            const defaultMessages = {
                'donation-line-chart': 'Haz clic en un punto para ver montos y donaciones del día.',
                'goal-progress-chart': 'Haz clic en una sección para ver detalle del avance.',
                'status-doughnut-chart': 'Haz clic en un segmento para ver el total por estado.',
                'payment-mix-chart': 'Haz clic en un segmento para ver detalle del medio de pago.',
                'top-campaigns-chart': 'Haz clic en una barra para conocer el desempeño de la campaña.'
            };

            const formatNumber = (value, { prefix = '', suffix = '', maximumFractionDigits = 0, minimumFractionDigits } = {}) => {
                const num = Number(value);
                if (!Number.isFinite(num)) {
                    return `${prefix}0${suffix}`;
                }
                const options = { maximumFractionDigits };
                if (typeof minimumFractionDigits === 'number') {
                    options.minimumFractionDigits = minimumFractionDigits;
                }
                return `${prefix}${num.toLocaleString('es-CL', options)}${suffix}`;
            };

            const parseJson = (value, fallback) => {
                if (!value) {
                    return fallback;
                }
                try {
                    return JSON.parse(value);
                } catch (error) {
                    console.warn('No se pudo parsear datos de gráfico', error);
                    return fallback;
                }
            };

            const setCanvasSize = (canvas, width, height) => {
                const resolvedWidth = Math.max(220, width || canvas.clientWidth || 480);
                const resolvedHeight = Math.max(180, height);
                canvas.width = resolvedWidth * dpr;
                canvas.height = resolvedHeight * dpr;
                canvas.style.width = resolvedWidth + 'px';
                canvas.style.height = resolvedHeight + 'px';
                return { width: resolvedWidth, height: resolvedHeight };
            };

            const storeChartState = (canvas, state) => {
                if (!canvas) {
                    return;
                }
                canvas.__chartState = state || null;
            };

            const getChartState = canvas => (canvas ? canvas.__chartState || null : null);

            const getFeedbackElement = canvas => {
                if (!canvas) {
                    return null;
                }
                return document.querySelector(`[data-chart-feedback="${canvas.id}"]`);
            };

            const setFeedbackMessage = (canvas, message) => {
                const target = getFeedbackElement(canvas);
                if (!target) {
                    return;
                }
                if (message && message.trim() !== '') {
                    target.textContent = message;
                    target.classList.remove('hidden');
                } else {
                    target.textContent = '';
                    target.classList.add('hidden');
                }
            };

            const getCanvasCoordinates = (event, canvas) => {
                const rect = canvas.getBoundingClientRect();
                const width = canvas.width / dpr;
                const height = canvas.height / dpr;
                const x = ((event.clientX - rect.left) / rect.width) * width;
                const y = ((event.clientY - rect.top) / rect.height) * height;
                return { x, y };
            };

            const ensureChartInteraction = canvas => {
                if (!canvas || canvas.__chartInteractionAttached) {
                    return;
                }
                canvas.addEventListener('click', handleChartClick);
                canvas.__chartInteractionAttached = true;
            };

            const drawEmptyState = (canvas, message, height = 220) => {
                const { width, height: resolvedHeight } = setCanvasSize(canvas, canvas.clientWidth, height);
                const ctx = canvas.getContext('2d');
                ctx.setTransform(1, 0, 0, 1, 0, 0);
                ctx.scale(dpr, dpr);
                ctx.clearRect(0, 0, width, resolvedHeight);
                ctx.fillStyle = '#94A3B8';
                ctx.font = '600 13px "Inter", system-ui, -apple-system, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(message, width / 2, resolvedHeight / 2);
                const legendContainer = canvas.parentElement ? canvas.parentElement.querySelector('[data-chart-legend]') : null;
                if (legendContainer) {
                    legendContainer.innerHTML = '';
                }
                storeChartState(canvas, null);
                setFeedbackMessage(canvas, message);
            };

            const drawLineChart = (canvas, series, mode) => {
                const { width, height } = setCanvasSize(canvas, canvas.clientWidth, 260);
                const ctx = canvas.getContext('2d');
                ctx.setTransform(1, 0, 0, 1, 0, 0);
                ctx.scale(dpr, dpr);
                ctx.clearRect(0, 0, width, height);

                const padding = { top: 24, right: 20, bottom: 44, left: 64 };
                const labels = series.map(item => item.label || '');
                const values = series.map(item => Number(item[mode]) || 0);
                const maxVal = Math.max(...values);
                const minVal = Math.min(...values);
                const range = maxVal - minVal || Math.max(maxVal, 1);
                const innerWidth = width - padding.left - padding.right;
                const innerHeight = height - padding.top - padding.bottom;
                const stepX = innerWidth / Math.max(1, series.length - 1);

                ctx.lineWidth = 1;
                ctx.strokeStyle = 'rgba(148, 163, 184, 0.25)';
                ctx.fillStyle = '#64748B';
                ctx.font = '11px "Inter", system-ui, -apple-system, sans-serif';

                const axisFormatter = mode === 'value'
                    ? value => formatNumber(value, { prefix: '$' })
                    : value => formatNumber(value);

                const gridLines = 4;
                for (let i = 0; i <= gridLines; i++) {
                    const ratio = i / gridLines;
                    const y = padding.top + innerHeight * ratio;
                    const value = maxVal - range * ratio;
                    ctx.beginPath();
                    ctx.moveTo(padding.left, y);
                    ctx.lineTo(width - padding.right, y);
                    ctx.stroke();
                    ctx.fillText(axisFormatter(value), 8, y + 4);
                }

                const points = series.map((item, index) => {
                    const targetValue = Number(item[mode] || 0);
                    const normalized = maxVal === minVal ? 0.5 : (maxVal - targetValue) / range;
                    const x = padding.left + stepX * index;
                    const y = padding.top + innerHeight * normalized;
                    return { x, y };
                });

                ctx.beginPath();
                ctx.moveTo(padding.left, padding.top + innerHeight);
                points.forEach(point => ctx.lineTo(point.x, point.y));
                ctx.lineTo(padding.left + innerWidth, padding.top + innerHeight);
                ctx.closePath();
                ctx.fillStyle = 'rgba(14, 165, 233, 0.12)';
                ctx.fill();

                ctx.beginPath();
                points.forEach((point, index) => {
                    if (index === 0) {
                        ctx.moveTo(point.x, point.y);
                    } else {
                        ctx.lineTo(point.x, point.y);
                    }
                });
                ctx.strokeStyle = '#0EA5E9';
                ctx.lineWidth = 2;
                ctx.lineJoin = 'round';
                ctx.stroke();

                points.forEach(point => {
                    ctx.beginPath();
                    ctx.arc(point.x, point.y, 3.5, 0, Math.PI * 2);
                    ctx.fillStyle = '#0284C7';
                    ctx.fill();
                });

                ctx.fillStyle = '#475569';
                ctx.textAlign = 'center';
                labels.forEach((label, index) => {
                    const x = points[index].x;
                    const y = height - padding.bottom / 2 + 12;
                    ctx.fillText(String(label), Math.max(padding.left, Math.min(x, width - padding.right)), y);
                });

                const pointMeta = series.map((item, index) => ({
                    label: labels[index],
                    amount: Number(item.value) || 0,
                    donations: Number(item.donations) || 0,
                    x: points[index].x,
                    y: points[index].y
                }));

                const state = { type: 'line', mode, points: pointMeta };
                storeChartState(canvas, state);
                return state;
            };

            const drawGoalProgressChart = (canvas, series, mode) => {
                const metricKey = mode === 'raised' ? 'raised' : 'count';
                const percentageKey = mode === 'raised' ? 'raised_percentage' : 'count_percentage';
                const filtered = series.filter(item => Number(item[metricKey]) > 0);
                if (!filtered.length) {
                    drawEmptyState(canvas, 'Sin datos para este modo', 200);
                    return;
                }

                const total = filtered.reduce((sum, item) => sum + (Number(item[metricKey]) || 0), 0);
                if (total <= 0) {
                    drawEmptyState(canvas, 'Sin datos para este modo', 200);
                    return;
                }

                const { width, height } = setCanvasSize(canvas, canvas.clientWidth, 200);
                const ctx = canvas.getContext('2d');
                ctx.setTransform(1, 0, 0, 1, 0, 0);
                ctx.scale(dpr, dpr);
                ctx.clearRect(0, 0, width, height);

                const padding = { top: 36, right: 32, bottom: 52, left: 32 };
                const barHeight = 44;
                const barWidth = width - padding.left - padding.right;
                const barY = padding.top;

                ctx.fillStyle = 'rgba(226, 232, 240, 0.65)';
                ctx.fillRect(padding.left, barY, barWidth, barHeight);

                const palette = ['#BAE6FD', '#7DD3FC', '#38BDF8', '#0EA5E9', '#0369A1', '#1D4ED8'];
                let currentX = padding.left;
                const segmentsMeta = [];

                filtered.forEach((bucket, index) => {
                    const value = Number(bucket[metricKey]) || 0;
                    if (value <= 0) {
                        return;
                    }

                    let segmentWidth = barWidth * (value / total);
                    if (index === filtered.length - 1) {
                        segmentWidth = padding.left + barWidth - currentX;
                    }

                    const color = bucket.color || palette[index % palette.length];
                    ctx.fillStyle = color;
                    ctx.fillRect(currentX, barY, segmentWidth, barHeight);

                    const midX = currentX + segmentWidth / 2;
                    const percent = Number(bucket[percentageKey]) || ((value / total) * 100);
                    if (segmentWidth >= 44) {
                        ctx.fillStyle = '#0F172A';
                        ctx.font = '600 12px "Inter", system-ui, -apple-system, sans-serif';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(percent.toFixed(1) + '%', midX, barY + barHeight / 2);
                    }

                    const label = String(bucket.label || '');
                    ctx.fillStyle = '#475569';
                    ctx.font = '11px "Inter", system-ui, -apple-system, sans-serif';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'alphabetic';
                    ctx.fillText(label.length > 18 ? label.slice(0, 17) + '…' : label, midX, barY + barHeight + 20);

                    const formattedValue = mode === 'raised'
                        ? formatNumber(value, { prefix: '$' })
                        : formatNumber(value);

                    ctx.fillStyle = '#0F172A';
                    ctx.font = '600 11px "Inter", system-ui, -apple-system, sans-serif';
                    ctx.fillText(formattedValue, midX, barY + barHeight + 36);

                    segmentsMeta.push({
                        key: bucket.key,
                        label: bucket.label,
                        count: Number(bucket.count || 0),
                        raised: Number(bucket.raised || 0),
                        countPercentage: Number(bucket.count_percentage || 0),
                        raisedPercentage: Number(bucket.raised_percentage || 0),
                        xStart: currentX,
                        xEnd: currentX + segmentWidth,
                        barTop: barY,
                        barBottom: barY + barHeight
                    });

                    currentX += segmentWidth;
                });

                const state = {
                    type: 'goal-progress',
                    mode,
                    segments: segmentsMeta,
                    barTop: barY,
                    barBottom: barY + barHeight
                };
                storeChartState(canvas, state);
                return state;
            };

            const drawDoughnutChart = (canvas, data, options = {}) => {
                const entries = Object.entries(data || {}).filter(([, value]) => Number(value) > 0);
                const colors = options.colors || ['#F97316', '#0EA5E9', '#22C55E', '#6366F1', '#FACC15', '#EF4444', '#14B8A6', '#94A3B8'];
                const valueFormatter = options.valueFormatter || (value => formatNumber(value));
                const centerFormatter = options.centerFormatter || valueFormatter;
                const emptyMessage = options.emptyMessage || 'Sin datos para visualizar';

                const size = Math.min(canvas.clientWidth || 320, 320);
                const { width, height } = setCanvasSize(canvas, size, size);
                const ctx = canvas.getContext('2d');
                ctx.setTransform(1, 0, 0, 1, 0, 0);
                ctx.scale(dpr, dpr);
                ctx.clearRect(0, 0, width, height);

                let legend = canvas.parentElement ? canvas.parentElement.querySelector('[data-chart-legend]') : null;
                if (!legend) {
                    legend = document.createElement('div');
                    legend.setAttribute('data-chart-legend', 'true');
                    legend.className = 'mt-4 flex flex-wrap gap-3 text-xs text-gray-600';
                    if (canvas.parentElement) {
                        canvas.parentElement.appendChild(legend);
                    }
                }
                legend.innerHTML = '';

                if (!entries.length) {
                    drawEmptyState(canvas, emptyMessage, size);
                    return;
                }

                const total = entries.reduce((sum, [, value]) => sum + Number(value), 0);
                if (total <= 0) {
                    drawEmptyState(canvas, emptyMessage, size);
                    return;
                }

                const center = width / 2;
                const radius = Math.min(width, height) / 2 - 12;
                const innerRadius = radius * 0.58;
                let startAngle = -Math.PI / 2;
                const entriesMeta = [];

                entries.forEach(([label, value], index) => {
                    const portion = Number(value) / total;
                    const angle = portion * Math.PI * 2;
                    const endAngle = startAngle + angle;
                    ctx.beginPath();
                    ctx.moveTo(center, center);
                    ctx.arc(center, center, radius, startAngle, endAngle);
                    ctx.closePath();
                    ctx.fillStyle = colors[index % colors.length];
                    ctx.fill();
                    entriesMeta.push({
                        label,
                        value: Number(value) || 0,
                        percentage: portion * 100,
                        startAngle,
                        endAngle,
                        color: colors[index % colors.length]
                    });
                    startAngle = endAngle;
                });

                ctx.beginPath();
                ctx.arc(center, center, innerRadius, 0, Math.PI * 2);
                ctx.fillStyle = '#FFFFFF';
                ctx.fill();

                const centerLabel = options.centerLabel
                    ? (typeof options.centerLabel === 'function' ? options.centerLabel(total) : options.centerLabel)
                    : centerFormatter(total);

                ctx.fillStyle = '#0F172A';
                ctx.font = '600 18px "Inter", system-ui, -apple-system, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(centerLabel, center, center);

                entries.forEach(([label, value], index) => {
                    const percentage = (Number(value) / total) * 100;
                    const legendItem = document.createElement('span');
                    legendItem.className = 'inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1';
                    const colorDot = document.createElement('span');
                    colorDot.className = 'block h-2 w-2 rounded-full';
                    colorDot.style.background = colors[index % colors.length];
                    legendItem.appendChild(colorDot);
                    const formattedValue = valueFormatter(Number(value));
                    const legendText = options.legendItemFormatter
                        ? options.legendItemFormatter({ label, value: Number(value), formattedValue, percentage })
                        : `${label}: ${formattedValue}`;
                    legendItem.appendChild(document.createTextNode(legendText));
                    legend.appendChild(legendItem);
                });

                const state = {
                    type: 'doughnut',
                    mode: options.mode || null,
                    entries: entriesMeta,
                    center,
                    innerRadius,
                    outerRadius: radius,
                    valueFormatter
                };
                storeChartState(canvas, state);
                return state;
            };

            const drawGroupedBarChart = (canvas, datasets) => {
                const metrics = [
                    { key: 'views', label: 'Visitas', color: '#0284C7' },
                    { key: 'shares', label: 'Compartidos', color: '#F97316' },
                    { key: 'donors', label: 'Donantes', color: '#10B981' }
                ];
                const values = [];
                datasets.forEach(item => {
                    metrics.forEach(metric => {
                        values.push(Number(item[metric.key]) || 0);
                    });
                });
                const maxVal = values.length ? Math.max(...values) : 0;

                if (!datasets.length || maxVal === 0) {
                    drawEmptyState(canvas, 'Sin datos para visualizar', 240);
                    return;
                }

                const height = Math.max(220, datasets.length * 80);
                const { width, height: resolvedHeight } = setCanvasSize(canvas, canvas.clientWidth, height);
                const ctx = canvas.getContext('2d');
                ctx.setTransform(1, 0, 0, 1, 0, 0);
                ctx.scale(dpr, dpr);
                ctx.clearRect(0, 0, width, resolvedHeight);

                const padding = { top: 32, right: 24, bottom: 48, left: 100 };
                const innerWidth = width - padding.left - padding.right;
                const innerHeight = resolvedHeight - padding.top - padding.bottom;
                const groupWidth = innerWidth / datasets.length;
                const barGap = 10;
                const barWidth = (groupWidth - barGap * (metrics.length - 1)) / metrics.length;
                const barsMeta = [];

                ctx.strokeStyle = 'rgba(148, 163, 184, 0.25)';
                ctx.fillStyle = '#64748B';
                ctx.font = '11px "Inter", system-ui, -apple-system, sans-serif';
                ctx.textAlign = 'right';

                const gridLines = 4;
                for (let i = 0; i <= gridLines; i++) {
                    const ratio = i / gridLines;
                    const y = padding.top + innerHeight * ratio;
                    const value = maxVal - (maxVal * ratio);
                    ctx.beginPath();
                    ctx.moveTo(padding.left, y);
                    ctx.lineTo(width - padding.right, y);
                    ctx.stroke();
                    ctx.fillText(formatNumber(value), padding.left - 10, y + 4);
                }

                datasets.forEach((item, index) => {
                    const baseX = padding.left + groupWidth * index;
                    metrics.forEach((metric, metricIndex) => {
                        const value = Number(item[metric.key]) || 0;
                        const heightRatio = value / maxVal;
                        const barHeight = innerHeight * heightRatio;
                        const x = baseX + metricIndex * (barWidth + barGap);
                        const y = padding.top + innerHeight - barHeight;
                        ctx.fillStyle = metric.color;
                        ctx.beginPath();
                        ctx.moveTo(x, y);
                        ctx.lineTo(x, y + barHeight);
                        ctx.lineTo(x + barWidth, y + barHeight);
                        ctx.lineTo(x + barWidth, y);
                        ctx.closePath();
                        ctx.globalAlpha = 0.15;
                        ctx.fill();
                        ctx.globalAlpha = 1;
                        ctx.lineWidth = 1.5;
                        ctx.strokeStyle = metric.color;
                        ctx.stroke();

                        ctx.fillStyle = metric.color;
                        ctx.textBaseline = 'bottom';
                        ctx.textAlign = 'right';
                        ctx.fillText(formatNumber(value), x + barWidth, y - 4);

                        barsMeta.push({
                            campaign: String(item.title || 'Campaña'),
                            metricKey: metric.key,
                            metricLabel: metric.label,
                            value,
                            x,
                            y,
                            width: barWidth,
                            height: barHeight
                        });
                    });

                    ctx.fillStyle = '#0F172A';
                    ctx.font = '12px "Inter", system-ui, -apple-system, sans-serif';
                    ctx.textBaseline = 'alphabetic';
                    ctx.textAlign = 'center';
                    const title = String(item.title || 'Campaña');
                    const label = title.length > 26 ? title.slice(0, 25) + '…' : title;
                    ctx.fillText(label, padding.left + groupWidth * index + groupWidth / 2, resolvedHeight - padding.bottom / 2 + 14);
                });

                let legend = canvas.parentElement ? canvas.parentElement.querySelector('[data-chart-legend]') : null;
                if (!legend) {
                    legend = document.createElement('div');
                    legend.setAttribute('data-chart-legend', 'true');
                    legend.className = 'mt-4 flex flex-wrap gap-3 text-xs text-gray-600';
                    if (canvas.parentElement) {
                        canvas.parentElement.appendChild(legend);
                    }
                }
                legend.innerHTML = '';
                metrics.forEach(metric => {
                    const item = document.createElement('span');
                    item.className = 'inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1';
                    const colorDot = document.createElement('span');
                    colorDot.className = 'block h-2 w-2 rounded-full';
                    colorDot.style.background = metric.color;
                    item.appendChild(colorDot);
                    item.appendChild(document.createTextNode(metric.label));
                    legend.appendChild(item);
                });

                const state = { type: 'grouped-bar', bars: barsMeta };
                storeChartState(canvas, state);
                return state;
            };

            const setupToggleGroup = (groupName, onChange, initialMode) => {
                const buttons = Array.from(document.querySelectorAll(`[data-chart-toggle="${groupName}"]`));
                if (!buttons.length) {
                    return null;
                }

                let currentMode = initialMode
                    || buttons.find(button => button.dataset.active === 'true')?.dataset.mode
                    || buttons[0].dataset.mode
                    || null;

                const applyState = () => {
                    buttons.forEach(button => {
                        const isActive = button.dataset.mode === currentMode;
                        if (isActive) {
                            button.dataset.active = 'true';
                            button.classList.add('bg-white', 'text-gray-900', 'shadow-sm');
                            button.classList.remove('text-gray-600');
                        } else {
                            delete button.dataset.active;
                            button.classList.remove('bg-white', 'text-gray-900', 'shadow-sm');
                            button.classList.add('text-gray-600');
                        }
                    });
                };

                applyState();

                buttons.forEach(button => {
                    button.addEventListener('click', () => {
                        const mode = button.dataset.mode;
                        if (!mode || mode === currentMode) {
                            return;
                        }
                        currentMode = mode;
                        applyState();
                        onChange(mode);
                    });
                });

                return currentMode;
            };

            const normalizeAngle = angle => {
                let result = angle;
                while (result < 0) {
                    result += Math.PI * 2;
                }
                while (result >= Math.PI * 2) {
                    result -= Math.PI * 2;
                }
                return result;
            };

            function handleChartClick(event) {
                const canvas = event.currentTarget;
                const state = getChartState(canvas);
                if (!state) {
                    return;
                }

                const { x, y } = getCanvasCoordinates(event, canvas);
                let message = defaultMessages[canvas.id] || '';

                switch (state.type) {
                    case 'line': {
                        const threshold = 18;
                        let nearest = null;
                        let minDistance = Infinity;
                        state.points.forEach(point => {
                            const distance = Math.hypot(point.x - x, point.y - y);
                            if (distance < minDistance) {
                                minDistance = distance;
                                nearest = point;
                            }
                        });
                        if (nearest && minDistance <= threshold) {
                            if (state.mode === 'value') {
                                message = `${nearest.label}: ${formatNumber(nearest.amount, { prefix: '$' })} recaudados · ${formatNumber(nearest.donations)} donaciones`;
                            } else {
                                message = `${nearest.label}: ${formatNumber(nearest.donations)} donaciones · ${formatNumber(nearest.amount, { prefix: '$' })} recaudados`;
                            }
                        }
                        break;
                    }
                    case 'goal-progress': {
                        const segment = state.segments.find(seg =>
                            x >= seg.xStart && x <= seg.xEnd && y >= seg.barTop && y <= seg.barBottom
                        );
                        if (segment) {
                            if (state.mode === 'raised') {
                                message = `${segment.label}: ${formatNumber(segment.raised, { prefix: '$' })} (${formatNumber(segment.raisedPercentage, { maximumFractionDigits: 1, suffix: '%' })})`;
                            } else {
                                message = `${segment.label}: ${formatNumber(segment.count)} campañas (${formatNumber(segment.countPercentage, { maximumFractionDigits: 1, suffix: '%' })})`;
                            }
                        }
                        break;
                    }
                    case 'doughnut': {
                        const dx = x - state.center;
                        const dy = y - state.center;
                        const distance = Math.hypot(dx, dy);
                        if (distance < state.innerRadius || distance > state.outerRadius) {
                            break;
                        }
                        const angle = normalizeAngle(Math.atan2(dy, dx));
                        let selected = null;
                        state.entries.forEach(entry => {
                            let start = normalizeAngle(entry.startAngle);
                            let end = normalizeAngle(entry.endAngle);
                            if (end < start) {
                                if (angle >= start || angle <= end) {
                                    selected = entry;
                                }
                            } else if (angle >= start && angle <= end) {
                                selected = entry;
                            }
                        });
                        if (selected) {
                            const formatter = state.valueFormatter || formatNumber;
                            const formattedValue = formatter(selected.value);
                            message = `${selected.label}: ${formattedValue} (${selected.percentage.toFixed(1)}%)`;
                        }
                        break;
                    }
                    case 'grouped-bar': {
                        const bar = state.bars.find(item =>
                            x >= item.x && x <= item.x + item.width && y >= item.y && y <= item.y + item.height
                        );
                        if (bar) {
                            message = `${bar.campaign}: ${bar.metricLabel} ${formatNumber(bar.value)}`;
                        }
                        break;
                    }
                    default:
                        break;
                }

                setFeedbackMessage(canvas, message);
            }

            const donationCanvas = document.getElementById('donation-line-chart');
            const goalProgressCanvas = document.getElementById('goal-progress-chart');
            const paymentMixCanvas = document.getElementById('payment-mix-chart');
            const statusCanvas = document.getElementById('status-doughnut-chart');
            const topCampaignCanvas = document.getElementById('top-campaigns-chart');

            const renderDonationChart = () => {
                if (!donationCanvas) {
                    return;
                }
                const series = parseJson(donationCanvas.dataset.series, []);
                const mode = donationCanvas.dataset.mode || 'value';
                const values = series.map(item => Number(item[mode]) || 0);
                const hasData = series.length > 0 && Math.max(...values) > 0;
                if (!hasData) {
                    drawEmptyState(donationCanvas, 'Sin datos para este modo', 260);
                    return;
                }
                const state = drawLineChart(donationCanvas, series, mode);
                ensureChartInteraction(donationCanvas);
                if (state) {
                    setFeedbackMessage(donationCanvas, defaultMessages[donationCanvas.id] || '');
                }
            };

            const renderGoalProgressChart = () => {
                if (!goalProgressCanvas) {
                    return;
                }
                const mode = goalProgressCanvas.dataset.mode || 'count';
                const series = parseJson(goalProgressCanvas.dataset.series, []);
                const state = drawGoalProgressChart(goalProgressCanvas, Array.isArray(series) ? series : [], mode);
                if (state) {
                    ensureChartInteraction(goalProgressCanvas);
                    setFeedbackMessage(goalProgressCanvas, defaultMessages[goalProgressCanvas.id] || '');
                }
            };

            const renderPaymentMixChart = () => {
                if (!paymentMixCanvas) {
                    return;
                }
                const mode = paymentMixCanvas.dataset.mode || 'amount';
                const data = mode === 'count'
                    ? parseJson(paymentMixCanvas.dataset.seriesCount, {})
                    : parseJson(paymentMixCanvas.dataset.seriesAmount, {});
                const state = drawDoughnutChart(paymentMixCanvas, data, {
                    emptyMessage: 'Sin datos para este modo',
                    valueFormatter: value => mode === 'amount'
                        ? formatNumber(value, { prefix: '$' })
                        : formatNumber(value),
                    centerFormatter: value => mode === 'amount'
                        ? formatNumber(value, { prefix: '$' })
                        : formatNumber(value),
                    legendItemFormatter: ({ label, formattedValue, percentage }) => `${label}: ${formattedValue} (${percentage.toFixed(1)}%)`,
                    mode
                });
                if (state) {
                    ensureChartInteraction(paymentMixCanvas);
                    setFeedbackMessage(paymentMixCanvas, defaultMessages[paymentMixCanvas.id] || '');
                }
            };

            const renderStatusChart = () => {
                if (!statusCanvas) {
                    return;
                }
                const data = parseJson(statusCanvas.dataset.series, {});
                const state = drawDoughnutChart(statusCanvas, data, {
                    emptyMessage: 'Sin estados registrados'
                });
                if (state) {
                    ensureChartInteraction(statusCanvas);
                    setFeedbackMessage(statusCanvas, defaultMessages[statusCanvas.id] || '');
                }
            };

            const renderTopCampaignsChart = () => {
                if (!topCampaignCanvas) {
                    return;
                }
                const campaigns = parseJson(topCampaignCanvas.dataset.series, []);
                const state = drawGroupedBarChart(topCampaignCanvas, Array.isArray(campaigns) ? campaigns : []);
                if (state) {
                    ensureChartInteraction(topCampaignCanvas);
                    setFeedbackMessage(topCampaignCanvas, defaultMessages[topCampaignCanvas.id] || '');
                }
            };

            if (donationCanvas) {
                const initialMode = setupToggleGroup('donation-line-chart', mode => {
                    donationCanvas.dataset.mode = mode;
                    renderDonationChart();
                }, donationCanvas.dataset.mode || 'value');
                donationCanvas.dataset.mode = initialMode || 'value';
                renderDonationChart();
            }

            if (goalProgressCanvas) {
                const initialMode = setupToggleGroup('goal-progress-chart', mode => {
                    goalProgressCanvas.dataset.mode = mode;
                    renderGoalProgressChart();
                }, goalProgressCanvas.dataset.mode || 'count');
                goalProgressCanvas.dataset.mode = initialMode || 'count';
                renderGoalProgressChart();
            }

            if (paymentMixCanvas) {
                const initialMode = setupToggleGroup('payment-mix-chart', mode => {
                    paymentMixCanvas.dataset.mode = mode;
                    renderPaymentMixChart();
                }, paymentMixCanvas.dataset.mode || 'amount');
                paymentMixCanvas.dataset.mode = initialMode || 'amount';
                renderPaymentMixChart();
            }

            if (statusCanvas) {
                renderStatusChart();
            }

            if (topCampaignCanvas) {
                renderTopCampaignsChart();
            }

            let resizeTimeout = null;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    renderDonationChart();
                    renderGoalProgressChart();
                    renderPaymentMixChart();
                    renderStatusChart();
                    renderTopCampaignsChart();
                }, 150);
            });
        });
    </script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include VIEWS_PATH . '/layouts/admin.php';
