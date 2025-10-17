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

$totalCampanas = (int)($campaignStats['total'] ?? 0);
$campanasIA = (int)($campaignStats['ai'] ?? 0);
$campanasManual = (int)($campaignStats['manual'] ?? 0);
$aiPercentage = $campaignStats['ai_percentage'] ?? 0.0;
$publicadas = (int)($campaignStats['publicadas'] ?? 0);
$enRevision = (int)($campaignStats['en_revision'] ?? 0);
$nuevas30 = (int)($campaignStats['nuevas_30_dias'] ?? 0);
$metaPromedio = $campaignStats['meta_promedio'] ?? null;

$montoTotal = (float)($donationStats['monto_total'] ?? 0);
$totalDonaciones = (int)($donationStats['total_donaciones'] ?? 0);
$promedioDonacion = $donationStats['promedio_donacion'] ?? null;
$conversionRate = $donationStats['conversion_rate'] ?? null;
$ratioVisitantes = $donationStats['ratio_visitantes_donantes'] ?? null;
$donaciones30 = (int)($donationStats['donaciones_30_dias'] ?? 0);
$monto30 = (float)($donationStats['monto_30_dias'] ?? 0);

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
    <?php else: ?>
        <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100 flex flex-col gap-4">
                <header class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Campañas activas</h3>
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-copihue-100 text-copihue-600 text-sm font-semibold">
                        <?= number_format($nuevas30, 0, ',', '.') ?>
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
            </article>

            <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100 flex flex-col gap-4">
                <header class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Recaudación total</h3>
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 text-sm font-semibold">
                        $<?= number_format($promedioDonacion ?? 0, 0, ',', '.') ?>
                    </span>
                </header>
                <div>
                    <p class="text-3xl font-semibold text-gray-900 leading-tight">
                        $<?= number_format($montoTotal, 0, ',', '.') ?>
                    </p>
                    <p class="mt-2 text-sm text-gray-500">
                        <?= number_format($totalDonaciones, 0, ',', '.') ?> aportes · <?= number_format($totalDonantes, 0, ',', '.') ?> donantes únicos
                    </p>
                </div>
            </article>

            <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100 flex flex-col gap-4">
                <header class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Conversión a donantes</h3>
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-sky-100 text-sky-600 text-sm font-semibold">
                        <?= $conversionRate !== null ? number_format($ratioVisitantes ?? 0, 2, ',', '.') : '—' ?>
                    </span>
                </header>
                <div>
                    <p class="text-3xl font-semibold text-gray-900 leading-tight">
                        <?= $conversionDisplay ?>
                    </p>
                    <p class="mt-2 text-sm text-gray-500">
                        <?= number_format($totalVisitantes, 0, ',', '.') ?> visitas · ratio <?= $ratioVisitantes !== null ? number_format($ratioVisitantes, 2, ',', '.') . ':1' : 'sin datos' ?>
                    </p>
                </div>
            </article>

            <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100 flex flex-col gap-4">
                <header class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Difusión orgánica</h3>
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-600 text-sm font-semibold">
                        <?= number_format($totalCompartidos, 0, ',', '.') ?>
                    </span>
                </header>
                <div>
                    <p class="text-3xl font-semibold text-gray-900 leading-tight">
                        <?= $shareRate !== null ? number_format($shareRate, 2, ',', '.') . '%' : 'Sin datos' ?>
                    </p>
                    <p class="mt-2 text-sm text-gray-500">
                        Ratio donantes/compartidos <?= $shareToDonationRate !== null ? number_format($shareToDonationRate, 2, ',', '.') . '%' : 'sin datos' ?>
                    </p>
                </div>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <article class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6 xl:col-span-2">
                <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Actividad últimos 30 días</h2>
                        <p class="text-sm text-gray-500">Mide la tracción reciente para reportes semanales de la tesis.</p>
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                        Corte: <?= date('d/m/Y') ?>
                    </span>
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
                        <canvas id="donation-line-chart" data-series="<?= $donationSeriesJson ?>"></canvas>
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
                <header>
                    <h2 class="text-lg font-semibold text-gray-900">Distribución por estado</h2>
                    <p class="text-sm text-gray-500">Revisa el mix de campañas para mantener los procesos de revisión al día.</p>
                </header>
                <canvas id="status-doughnut-chart" data-series="<?= $statusJson ?>"></canvas>
            </article>
        </section>

        <section class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <article class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6">
                <header class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Top campañas por visitas</h2>
                        <p class="text-sm text-gray-500">Evalúa dónde enfocar comunicación y acompañamiento.</p>
                    </div>
                </header>
                <?php if (empty($topCampaigns)): ?>
                    <p class="text-sm text-gray-500">Aún no hay campañas con visitas registradas.</p>
                <?php else: ?>
                    <canvas id="top-campaigns-chart" data-series="<?= $topCampaignsJson ?>"></canvas>
                <?php endif; ?>
            </article>

            <article class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6">
                <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Categorías con mayor recaudación</h2>
                        <p class="text-sm text-gray-500">Prioriza alianzas y comunicaciones en función del desempeño consolidado.</p>
                    </div>
                    <?php if (!empty($categoryLeaders)): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-copihue-100 text-copihue-700 text-xs font-semibold">
                            <?= count($categoryLeaders) ?> categorías destacadas
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
                                        <?= htmlspecialchars($category['name']) ?>
                                    </h3>
                                    <p class="mt-1 text-xs text-gray-500">$<?= number_format((float)$category['raised'], 0, ',', '.') ?> recaudados</p>
                                </div>
                                <dl class="flex items-center gap-6 text-xs text-gray-500">
                                    <div class="text-right">
                                        <dt>Donantes</dt>
                                        <dd class="font-semibold text-gray-900"><?= number_format((int)$category['donors'], 0, ',', '.') ?></dd>
                                    </div>
                                    <div class="text-right">
                                        <dt>Visitas</dt>
                                        <dd class="font-semibold text-gray-900"><?= number_format((int)$category['views'], 0, ',', '.') ?></dd>
                                    </div>
                                </dl>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>
        </section>
    <?php endif; ?>
</div>

<?php if ($supported): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" integrity="sha384-p5O8Vt4vZmqtO+0SChQBxkv6AmGIjk+/j4Ka3zGWx/89Zc43GqSy+GhnAEG8bi7C" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const donationCanvas = document.getElementById('donation-line-chart');
            if (donationCanvas) {
                const series = JSON.parse(donationCanvas.dataset.series || '[]');
                if (series.length > 0 && window.Chart) {
                    new Chart(donationCanvas, {
                        type: 'line',
                        data: {
                            labels: series.map(item => item.label),
                            datasets: [{
                                label: 'Monto recaudado',
                                data: series.map(item => item.value),
                                tension: 0.35,
                                borderColor: '#0EA5E9',
                                backgroundColor: 'rgba(14, 165, 233, 0.15)',
                                pointBackgroundColor: '#0284C7',
                                fill: true,
                                borderWidth: 2
                            }]
                        },
                        options: {
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: ctx => '$' + Number(ctx.parsed.y).toLocaleString('es-CL')
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    ticks: {
                                        callback: value => '$' + Number(value).toLocaleString('es-CL')
                                    }
                                }
                            }
                        }
                    });
                } else {
                    donationCanvas.closest('article')?.classList.add('hidden');
                }
            }

            const statusCanvas = document.getElementById('status-doughnut-chart');
            if (statusCanvas) {
                const data = JSON.parse(statusCanvas.dataset.series || '{}');
                const labels = Object.keys(data);
                if (labels.length > 0 && window.Chart) {
                    new Chart(statusCanvas, {
                        type: 'doughnut',
                        data: {
                            labels,
                            datasets: [{
                                data: labels.map(label => data[label]),
                                backgroundColor: ['#F97316', '#0EA5E9', '#22C55E', '#6366F1', '#FACC15', '#9CA3AF'],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            plugins: {
                                legend: { position: 'bottom' }
                            },
                            cutout: '60%'
                        }
                    });
                } else {
                    statusCanvas.closest('article')?.classList.add('hidden');
                }
            }

            const topCampaignCanvas = document.getElementById('top-campaigns-chart');
            if (topCampaignCanvas) {
                const campaigns = JSON.parse(topCampaignCanvas.dataset.series || '[]');
                if (campaigns.length > 0 && window.Chart) {
                    new Chart(topCampaignCanvas, {
                        type: 'bar',
                        data: {
                            labels: campaigns.map(item => item.title),
                            datasets: [{
                                label: 'Visitas',
                                data: campaigns.map(item => item.views),
                                backgroundColor: 'rgba(14, 165, 233, 0.2)',
                                borderColor: '#0284C7',
                                borderWidth: 1.5,
                                borderRadius: 6
                            }, {
                                label: 'Compartidos',
                                data: campaigns.map(item => item.shares),
                                backgroundColor: 'rgba(249, 115, 22, 0.2)',
                                borderColor: '#F97316',
                                borderWidth: 1.5,
                                borderRadius: 6
                            }, {
                                label: 'Donantes',
                                data: campaigns.map(item => item.donors),
                                backgroundColor: 'rgba(16, 185, 129, 0.2)',
                                borderColor: '#10B981',
                                borderWidth: 1.5,
                                borderRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: ctx => ctx.dataset.label + ': ' + Number(ctx.parsed.y).toLocaleString('es-CL')
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    ticks: { callback: value => campaigns[value]?.title?.slice(0, 14) + (campaigns[value]?.title?.length > 14 ? '…' : '') }
                                }
                            }
                        }
                    });
                } else {
                    topCampaignCanvas.closest('article')?.classList.add('hidden');
                }
            }
        });
    </script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include VIEWS_PATH . '/layouts/admin.php';
