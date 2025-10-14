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
?>

<?php ob_start(); ?>
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
            <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Campañas activas</h3>
                <p class="mt-4 text-3xl font-semibold text-gray-900">
                    <?= number_format($totalCampanas, 0, ',', '.') ?>
                </p>
                <p class="mt-2 text-sm text-gray-500">
                    <?= number_format($publicadas, 0, ',', '.') ?> publicadas · <?= number_format($enRevision, 0, ',', '.') ?> en revisión
                </p>
            </article>

            <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Adopción de IA</h3>
                <p class="mt-4 text-3xl font-semibold text-copihue-600">
                    <?= number_format($aiPercentage, 1, ',', '.') ?>%
                </p>
                <p class="mt-2 text-sm text-gray-500">
                    <?= number_format($campanasIA, 0, ',', '.') ?> campañas con IA · <?= number_format($campanasManual, 0, ',', '.') ?> manuales
                </p>
            </article>

            <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Conversión visitantes &rarr; donantes</h3>
                <p class="mt-4 text-3xl font-semibold text-gray-900">
                    <?= $conversionRate !== null ? number_format($conversionRate, 2, ',', '.') . '%' : 'Sin datos' ?>
                </p>
                <p class="mt-2 text-sm text-gray-500">
                    <?= number_format($totalDonantes, 0, ',', '.') ?> donantes · <?= number_format($totalVisitantes, 0, ',', '.') ?> visitantes
                </p>
            </article>

            <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Monto recaudado</h3>
                <p class="mt-4 text-3xl font-semibold text-gray-900">
                    $<?= number_format($montoTotal, 0, ',', '.') ?>
                </p>
                <p class="mt-2 text-sm text-gray-500">
                    <?= number_format($totalDonaciones, 0, ',', '.') ?> donaciones completadas en la plataforma.
                </p>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <article class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6 space-y-4">
                <header>
                    <h2 class="text-lg font-semibold text-gray-900">Panorama de campañas</h2>
                    <p class="text-sm text-gray-500">
                        Supervisión rápida del pipeline y de la adopción de herramientas asistidas.
                    </p>
                </header>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700">
                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                        <dt class="font-semibold text-gray-500 uppercase tracking-wide text-xs">Nuevas (30 días)</dt>
                        <dd class="mt-1 text-xl font-semibold text-gray-900">
                            <?= number_format($nuevas30, 0, ',', '.') ?>
                        </dd>
                        <p class="mt-1 text-xs text-gray-500">Incluye borradores y envíos recientes.</p>
                    </div>
                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                        <dt class="font-semibold text-gray-500 uppercase tracking-wide text-xs">Meta promedio</dt>
                        <dd class="mt-1 text-xl font-semibold text-gray-900">
                            <?= $metaPromedio !== null ? '$' . number_format($metaPromedio, 0, ',', '.') : 'Sin datos' ?>
                        </dd>
                        <p class="mt-1 text-xs text-gray-500">Analiza la carga financiera promedio por campaña.</p>
                    </div>
                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                        <dt class="font-semibold text-gray-500 uppercase tracking-wide text-xs">Campañas IA</dt>
                        <dd class="mt-1 text-xl font-semibold text-copihue-600">
                            <?= number_format($campanasIA, 0, ',', '.') ?>
                        </dd>
                        <p class="mt-1 text-xs text-gray-500">Supervisa la dependencia de prompts y revisión automática.</p>
                    </div>
                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                        <dt class="font-semibold text-gray-500 uppercase tracking-wide text-xs">Campañas manuales</dt>
                        <dd class="mt-1 text-xl font-semibold text-gray-900">
                            <?= number_format($campanasManual, 0, ',', '.') ?>
                        </dd>
                        <p class="mt-1 text-xs text-gray-500">Comparar desempeño entre campañas asistidas vs tradicionales.</p>
                    </div>
                </dl>
            </article>

            <article class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6 space-y-4">
                <header>
                    <h2 class="text-lg font-semibold text-gray-900">Embudo de donaciones</h2>
                    <p class="text-sm text-gray-500">
                        Observa la relación entre tráfico, apoyo económico y tendencias recientes.
                    </p>
                </header>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700">
                    <div class="p-4 rounded-2xl bg-copihue-50 border border-copihue-100">
                        <p class="text-xs font-semibold uppercase tracking-wide text-copihue-600">Ratio visitantes / donantes</p>
                        <p class="mt-1 text-xl font-semibold text-copihue-700">
                            <?= $ratioVisitantes !== null ? number_format($ratioVisitantes, 2, ',', '.') . ':1' : 'Sin datos' ?>
                        </p>
                        <?php if ($ratioVisitantes !== null): ?>
                            <p class="mt-1 text-xs text-copihue-700/80">
                                Cada <?= number_format($ratioVisitantes, 2, ',', '.') ?> visitas genera 1 donante, en promedio.
                            </p>
                        <?php else: ?>
                            <p class="mt-1 text-xs text-copihue-700/80">
                                Aún no hay suficientes datos para estimar la conversión.
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Donaciones recientes</p>
                        <p class="mt-1 text-xl font-semibold text-gray-900">
                            <?= number_format($donaciones30, 0, ',', '.') ?>
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            $<?= number_format($monto30, 0, ',', '.') ?> recaudados en los últimos 30 días.
                        </p>
                    </div>
                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Promedio por aporte</p>
                        <p class="mt-1 text-xl font-semibold text-gray-900">
                            <?= $promedioDonacion !== null ? '$' . number_format($promedioDonacion, 0, ',', '.') : 'Sin datos' ?>
                        </p>
                        <p class="mt-1 text-xs text-gray-500">Ayuda a planificar metas realistas y tiempos de recaudación.</p>
                    </div>
                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Visitas 30 días</p>
                        <p class="mt-1 text-xl font-semibold text-gray-900">
                            <?= number_format($visitas30, 0, ',', '.') ?>
                        </p>
                        <p class="mt-1 text-xs text-gray-500">Útil para detectar campañas que requieren difusión extra.</p>
                    </div>
                </div>
            </article>
        </section>

        <section class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6">
            <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Categorías con mayor recaudación</h2>
                    <p class="text-sm text-gray-500">Identifica dónde está el mayor impacto para diseñar alianzas.</p>
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
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <?php foreach ($categoryLeaders as $category): ?>
                        <article class="p-5 rounded-3xl border border-gray-100 bg-gray-50/80">
                            <h3 class="text-sm font-semibold text-gray-900">
                                <?= htmlspecialchars($category['name']) ?>
                            </h3>
                            <dl class="mt-3 space-y-2 text-xs text-gray-600">
                                <div class="flex items-center justify-between">
                                    <dt>Recaudado</dt>
                                    <dd class="font-semibold text-gray-900">$<?= number_format((float)$category['raised'], 0, ',', '.') ?></dd>
                                </div>
                                <div class="flex items-center justify-between">
                                    <dt>Donantes</dt>
                                    <dd class="font-semibold text-gray-900"><?= number_format((int)$category['donors'], 0, ',', '.') ?></dd>
                                </div>
                                <div class="flex items-center justify-between">
                                    <dt>Visitas</dt>
                                    <dd class="font-semibold text-gray-900"><?= number_format((int)$category['views'], 0, ',', '.') ?></dd>
                                </div>
                            </dl>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6">
            <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Impacto de la IA por categoría</h2>
                    <p class="text-sm text-gray-500">Mide dónde la asistencia automática está generando mejores resultados.</p>
                </div>
            </header>

            <?php if (empty($aiByCategory)): ?>
                <p class="text-sm text-gray-500">No se registran campañas asistidas por IA con recaudación significativa.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600 uppercase tracking-wide">Categoría</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600 uppercase tracking-wide">Campañas IA</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-600 uppercase tracking-wide">Recaudado</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php foreach ($aiByCategory as $row): ?>
                                <tr>
                                    <td class="px-4 py-3 text-gray-800 font-medium"><?= htmlspecialchars($row['name']) ?></td>
                                    <td class="px-4 py-3 text-gray-700"><?= number_format((int)$row['campaigns'], 0, ',', '.') ?></td>
                                    <td class="px-4 py-3 text-right text-gray-900">$<?= number_format((float)$row['raised'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();

include VIEWS_PATH . '/layouts/admin.php';
?>
