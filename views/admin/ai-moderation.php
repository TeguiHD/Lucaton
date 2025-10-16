<?php
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$page_title = $page_title ?? 'Moderación IA';
$current_page = $current_page ?? 'admin-ai';
$aiSummary = $aiSummary ?? ($summary ?? []);
$aiSupported = $aiSupported ?? false;
$aiFilters = $aiFilters ?? ['estado' => 'pendientes', 'accion' => 'pendientes'];
$aiStatusCounts = $aiStatusCounts ?? [];
$aiProviderCounts = $aiProviderCounts ?? [];
$aiModeCounts = $aiModeCounts ?? [];
$aiGenerations = $aiGenerations ?? [];
$aiPolicyEvents = $aiPolicyEvents ?? [];
$aiStatusMeta = $aiStatusMeta ?? [];
$aiPolicyMeta = $aiPolicyMeta ?? [];
$aiUsageMetrics = $aiUsageMetrics ?? [];
$aiUsageSupported = $aiUsageMetrics['supported'] ?? false;
$aiUsageProviders = $aiUsageMetrics['providers'] ?? [];
$aiUsageTotals = $aiUsageMetrics['totals'] ?? [];
$aiUsageColumns = $aiUsageMetrics['columns'] ?? [];

$totalGenerations = (int)($aiSummary['total_generations'] ?? 0);
$needsAttention = (int)($aiSummary['needs_attention_total'] ?? 0);
$flaggedCount = (int)($aiSummary['flagged_events'] ?? 0);
$avgLatency = $aiSummary['avg_latency'] ?? null;
$avgCost = $aiSummary['avg_cost'] ?? null;
$last24h = (int)($aiSummary['last_24h'] ?? 0);

$formatUsageNumber = static function (?float $value, int $decimals = 0): string {
    if ($value === null) {
        return '—';
    }

    if ($decimals > 0) {
        return number_format($value, $decimals, ',', '.');
    }

    return number_format((float)$value, 0, ',', '.');
};

$formatPercentage = static function (int $part, int $total, int $decimals = 1): string {
    if ($total <= 0) {
        return '—';
    }

    return number_format(($part / $total) * 100, $decimals, ',', '.') . '%';
};
?>

<?php ob_start(); ?>
<div class="space-y-6">
    <?php include_flash_messages(); ?>

    <section class="bg-white shadow-soft rounded-3xl border border-gray-100 p-4 md:p-6">
        <nav class="flex flex-wrap gap-2 md:gap-3 text-sm font-medium text-gray-600">
            <a href="#ai-overview" class="inline-flex items-center gap-2 rounded-full border border-copihue-200 px-3 py-1.5 text-copihue-600 hover:bg-copihue-50 focus:outline-none focus:ring-2 focus:ring-copihue-500 focus:ring-offset-1 transition">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M2 11a7 7 0 1014 0 7 7 0 00-14 0zm8-3a1 1 0 00-2 0v3a1 1 0 00.553.894l2 1a1 1 0 10.894-1.788L10 10.382V8z" />
                </svg>
                Resumen
            </a>
            <?php if ($aiUsageSupported): ?>
                <a href="#ai-usage" class="inline-flex items-center gap-2 rounded-full border border-gray-200 px-3 py-1.5 text-gray-600 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-1 transition">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v3h2V5h3V3H4zm6 0h3v2h-3V3zm5 0a2 2 0 012 2v3h-2V5h-3V3h3zm-5 6h3v2h-3V9zm5 0h3v2h-3V9zm-5 4h3v2h-3v-2zm5 0h3v2h-3v-2zM2 9h3v2H2V9zm0 4h3v2H2v-2zm8 4h3v2h-3v-2zm-5 0h3v2H5v-2z" clip-rule="evenodd" />
                    </svg>
                    Uso de APIs
                </a>
            <?php endif; ?>
            <a href="#ai-alertas" class="inline-flex items-center gap-2 rounded-full border border-gray-200 px-3 py-1.5 text-gray-600 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-1 transition">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8.257 3.099c.366-.756 1.42-.756 1.786 0l6.518 13.492c.33.685-.173 1.409-.893 1.409H2.632c-.72 0-1.223-.724-.893-1.409L8.257 3.1zM9 13h2v2H9v-2zm0-5h2v4H9V8z" clip-rule="evenodd" />
                </svg>
                Alertas y Moderación
            </a>
        </nav>
    </section>

    <?php if (!$aiSupported): ?>
        <section id="ai-overview" class="bg-white shadow-soft rounded-3xl border border-dashed border-amber-300 p-8 text-center">
            <h2 class="text-lg font-semibold text-gray-900">Moderación asistida por IA no configurada</h2>
            <p class="mt-2 text-sm text-gray-600">
                Aún no se detectan las tablas <code>ai_generations</code> ni <code>ai_policy_logs</code>.
                Ejecuta las migraciones de base de datos para habilitar el monitoreo de las asistencias automáticas.
            </p>
            <p class="mt-4 text-sm text-gray-500">
                Una vez disponibles, esta vista mostrará los contenidos generados, revisiones automáticas,
                alertas de políticas y métricas de desempeño para el equipo moderador.
            </p>
        </section>
    <?php else: ?>
        <section id="ai-overview" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Solicitudes IA totales</h3>
                <p class="mt-4 text-3xl font-semibold text-gray-900">
                    <?= number_format($totalGenerations, 0, ',', '.') ?>
                </p>
                <p class="mt-2 text-sm text-gray-500">Historial completo de asistencias en la plataforma.</p>
            </article>

            <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Atención pendiente</h3>
                <p class="mt-4 text-3xl font-semibold text-amber-600">
                    <?= number_format($needsAttention, 0, ',', '.') ?>
                </p>
                <p class="mt-2 text-sm text-gray-500">
                    Incluye generaciones en estado <strong>pendiente</strong> o <strong>moderado</strong> y alertas sin revisar.
                </p>
            </article>

            <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Latencia promedio</h3>
                <p class="mt-4 text-3xl font-semibold text-gray-900">
                    <?= $avgLatency !== null ? number_format($avgLatency, 0, ',', '.') . ' ms' : 'Sin datos' ?>
                </p>
                <p class="mt-2 text-sm text-gray-500">Tiempo medio de respuesta de los proveedores disponibles.</p>
            </article>

            <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Generaciones 24h</h3>
                <p class="mt-4 text-3xl font-semibold text-gray-900">
                    <?= number_format($last24h, 0, ',', '.') ?>
                </p>
                <p class="mt-2 text-sm text-gray-500">Actividad más reciente para detectar anomalías o picos.</p>
            </article>
        </section>

        <?php if ($aiUsageSupported): ?>
            <?php
                $usageTotal = (int)($aiUsageTotals['total'] ?? 0);
                $usageSuccess = (int)($aiUsageTotals['success'] ?? 0);
                $usageFailed = (int)($aiUsageTotals['failed'] ?? 0);
                $usageLast24h = (int)($aiUsageTotals['last_24h'] ?? 0);
                $usageTokensInput = (float)($aiUsageTotals['tokens_input'] ?? 0);
                $usageTokensOutput = (float)($aiUsageTotals['tokens_output'] ?? 0);
                $usageTokensTotal = $usageTokensInput + $usageTokensOutput;
                $usageCostTotal = (float)($aiUsageTotals['cost'] ?? 0);
                $usageSuccessRate = $usageTotal > 0 ? ($usageSuccess / max(1, $usageTotal)) * 100 : null;
            ?>
            <section id="ai-usage" class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6 space-y-6">
                <header class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Uso consolidado de los proveedores</h2>
                        <p class="text-sm text-gray-500">Monitorea actividad, rendimiento y consumo para cada API configurada.</p>
                    </div>
                    <?php if ($usageSuccessRate !== null): ?>
                        <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 00-1.414 0L8.5 12.086l-3.293-3.293a1 1 0 00-1.414 1.414l4 4a1 1 0 001.414 0l7.5-7.5a1 1 0 000-1.414z" clip-rule="evenodd" />
                            </svg>
                            Tasa de éxito global: <?= $formatPercentage($usageSuccess, $usageTotal) ?>
                        </div>
                    <?php endif; ?>
                </header>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-2xl border border-gray-100 bg-gray-50/40 p-5">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total de solicitudes</h3>
                        <p class="mt-2 text-3xl font-semibold text-gray-900"><?= number_format($usageTotal, 0, ',', '.') ?></p>
                        <p class="mt-2 text-xs text-gray-500">Histórico de llamadas registradas.</p>
                    </article>
                    <article class="rounded-2xl border border-gray-100 bg-gray-50/40 p-5">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">Balance de resultados</h3>
                        <p class="mt-2 text-lg font-semibold text-emerald-700">Éxitos: <?= number_format($usageSuccess, 0, ',', '.') ?></p>
                        <p class="text-sm font-semibold text-rose-600">Errores: <?= number_format($usageFailed, 0, ',', '.') ?></p>
                        <p class="mt-2 text-xs text-gray-500">Investiga si la tasa de fallos supera el 10%.</p>
                    </article>
                    <article class="rounded-2xl border border-gray-100 bg-gray-50/40 p-5">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">Actividad en 24 horas</h3>
                        <p class="mt-2 text-3xl font-semibold text-gray-900"><?= number_format($usageLast24h, 0, ',', '.') ?></p>
                        <p class="mt-2 text-xs text-gray-500">Ayuda a detectar picos o abusos recientes.</p>
                    </article>
                    <?php if (!empty($aiUsageColumns['has_token_totals']) || !empty($aiUsageColumns['has_cost'])): ?>
                        <article class="rounded-2xl border border-gray-100 bg-gray-50/40 p-5">
                            <?php if (!empty($aiUsageColumns['has_token_totals'])): ?>
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tokens consumidos</h3>
                                <p class="mt-2 text-2xl font-semibold text-gray-900"><?= number_format($usageTokensTotal, 0, ',', '.') ?></p>
                                <p class="mt-1 text-xs text-gray-500">Entrada: <?= number_format($usageTokensInput, 0, ',', '.') ?> · Salida: <?= number_format($usageTokensOutput, 0, ',', '.') ?></p>
                            <?php endif; ?>
                            <?php if (!empty($aiUsageColumns['has_cost'])): ?>
                                <div class="mt-3 border-t border-gray-200 pt-3">
                                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">Costo estimado</h3>
                                    <p class="mt-1 text-lg font-semibold text-gray-900">$<?= number_format($usageCostTotal, 4, ',', '.') ?> USD</p>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endif; ?>
                </div>

                <?php if (!empty($aiUsageProviders)): ?>
                    <div class="mt-6 space-y-4">
                        <?php foreach ($aiUsageProviders as $provider): ?>
                            <?php
                                $providerTotal = (int)($provider['total'] ?? 0);
                                $providerSuccess = (int)($provider['success'] ?? 0);
                                $providerFailed = (int)($provider['failed'] ?? 0);
                                $providerSuccessRate = $providerTotal > 0 ? ($providerSuccess / max(1, $providerTotal)) * 100 : null;
                                $providerTokensTotal = $provider['total_tokens'] ?? null;
                                $providerCostTotal = $provider['total_cost'] ?? null;
                            ?>
                            <article class="rounded-3xl border border-gray-100 p-6 shadow-sm hover:shadow-md transition-shadow bg-white">
                                <header class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?= htmlspecialchars($provider['accent'] ?? 'bg-gray-100 text-gray-700') ?>">
                                            <?= htmlspecialchars($provider['tag'] ?? strtoupper($provider['key'] ?? 'API')) ?>
                                        </span>
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($provider['label'] ?? 'Proveedor') ?></h3>
                                            <p class="text-xs text-gray-500 uppercase tracking-wide">Total solicitudes: <?= number_format($providerTotal, 0, ',', '.') ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 font-medium text-emerald-700">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.172 7.707 8.879a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            <?= number_format($providerSuccess, 0, ',', '.') ?> éxitos
                                        </span>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 font-medium text-rose-600">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-.25-7.75a.75.75 0 00-1.5 0v3.5a.75.75 0 001.5 0v-3.5z" clip-rule="evenodd" />
                                            </svg>
                                            <?= number_format($providerFailed, 0, ',', '.') ?> fallos
                                        </span>
                                    </div>
                                </header>

                                <?php if ($providerSuccessRate !== null): ?>
                                    <div class="mt-4">
                                        <div class="flex items-center justify-between text-xs uppercase tracking-wide text-gray-500">
                                            <span>Tasa de éxito</span>
                                            <span class="font-semibold text-gray-900"><?= number_format($providerSuccessRate, 1, ',', '.') ?>%</span>
                                        </div>
                                        <div class="mt-2 h-2 rounded-full bg-gray-100">
                                            <div
                                                class="h-2 rounded-full bg-emerald-400 transition-all"
                                                style="width: <?= min(100, max(0, $providerSuccessRate)) ?>%"
                                                aria-hidden="true"
                                            ></div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3 text-sm text-gray-600">
                                    <?php if (!empty($aiUsageColumns['has_last_24h'])): ?>
                                        <div>
                                            <dt class="text-xs uppercase tracking-wide text-gray-500">Últimas 24h</dt>
                                            <dd class="mt-1 text-base font-semibold text-gray-900"><?= number_format((int)($provider['last_24h'] ?? 0), 0, ',', '.') ?></dd>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($aiUsageColumns['has_latency']) && $provider['avg_latency'] !== null): ?>
                                        <div>
                                            <dt class="text-xs uppercase tracking-wide text-gray-500">Latencia promedio (ms)</dt>
                                            <dd class="mt-1 text-base font-semibold text-gray-900"><?= $formatUsageNumber($provider['avg_latency'], 1) ?></dd>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($aiUsageColumns['has_tokens_input']) || !empty($aiUsageColumns['has_tokens_output'])): ?>
                                        <div>
                                            <dt class="text-xs uppercase tracking-wide text-gray-500">Tokens promedio</dt>
                                            <dd class="mt-1 text-base font-semibold text-gray-900"><?= $formatUsageNumber($provider['avg_tokens_total'] ?? null, 0) ?></dd>
                                            <p class="text-xs text-gray-500">IN: <?= $formatUsageNumber($provider['avg_tokens_input'] ?? null, 0) ?> · OUT: <?= $formatUsageNumber($provider['avg_tokens_output'] ?? null, 0) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </dl>

                                <?php if (!empty($aiUsageColumns['has_token_totals']) || !empty($aiUsageColumns['has_cost'])): ?>
                                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <?php if (!empty($aiUsageColumns['has_token_totals']) && $providerTokensTotal !== null): ?>
                                            <div class="rounded-2xl border border-dashed border-gray-200 p-4 bg-gray-50">
                                                <h4 class="text-xs uppercase tracking-wide text-gray-500">Tokens totales</h4>
                                                <p class="mt-1 text-lg font-semibold text-gray-900"><?= $formatUsageNumber($providerTokensTotal, 0) ?></p>
                                                <p class="mt-1 text-xs text-gray-500">IN: <?= $formatUsageNumber($provider['total_tokens_input'] ?? null, 0) ?> · OUT: <?= $formatUsageNumber($provider['total_tokens_output'] ?? null, 0) ?></p>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($aiUsageColumns['has_cost']) && $providerCostTotal !== null): ?>
                                            <div class="rounded-2xl border border-dashed border-gray-200 p-4 bg-gray-50">
                                                <h4 class="text-xs uppercase tracking-wide text-gray-500">Costo estimado</h4>
                                                <p class="mt-1 text-lg font-semibold text-gray-900">$<?= $formatUsageNumber($providerCostTotal, 4) ?> USD</p>
                                                <p class="mt-1 text-xs text-gray-500">Promedio por solicitud: $<?= $formatUsageNumber($provider['avg_cost'] ?? null, 4) ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-gray-600">Todavía no hay registros de llamadas a las APIs de IA.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <article class="bg-white shadow-soft rounded-3xl p-6 border border-gray-100">
                <header class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Estados de generación</h2>
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Distribución</span>
                </header>

                <div class="space-y-3">
                    <?php foreach ($aiStatusCounts as $status => $count): ?>
                        <?php
                        $meta = $aiStatusMeta[$status] ?? [
                            'label' => ucfirst($status),
                            'badge_class' => 'bg-gray-100 text-gray-700',
                        ];
                        $percentage = $totalGenerations > 0 ? round(($count / $totalGenerations) * 100, 1) : 0;
                        ?>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center gap-2 text-sm font-semibold text-gray-800">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $meta['badge_class'] ?>">
                                        <?= htmlspecialchars($meta['label']) ?>
                                    </span>
                                    <span class="text-xs text-gray-500"><?= htmlspecialchars($meta['description'] ?? '') ?></span>
                                </span>
                                <span class="text-sm font-medium text-gray-700">
                                    <?= number_format($count, 0, ',', '.') ?> (<?= $percentage ?>%)
                                </span>
                            </div>
                            <div class="mt-2 h-2 rounded-full bg-gray-100 overflow-hidden">
                                <div
                                    class="h-2 rounded-full bg-copihue-500"
                                    style="width: <?= min(100, $percentage) ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($aiStatusCounts)): ?>
                        <p class="text-sm text-gray-500">Sin datos registrados.</p>
                    <?php endif; ?>
                </div>
            </article>

            <article class="bg-white shadow-soft rounded-3xl p-6 border border-gray-100 space-y-6">
                <header>
                    <h2 class="text-lg font-semibold text-gray-900">Proveedores y modos utilizados</h2>
                    <p class="text-sm text-gray-500">
                        Conocer la mezcla de servicios ayuda a priorizar presupuestos y monitorear políticas.
                    </p>
                </header>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-2">Proveedores</h3>
                        <ul class="space-y-2">
                            <?php foreach ($aiProviderCounts as $provider => $count): ?>
                                <li class="flex items-center justify-between text-sm text-gray-700">
                                    <span class="capitalize"><?= htmlspecialchars(str_replace('_', ' ', $provider)) ?></span>
                                    <span class="font-semibold"><?= number_format($count, 0, ',', '.') ?></span>
                                </li>
                            <?php endforeach; ?>
                            <?php if (empty($aiProviderCounts)): ?>
                                <li class="text-sm text-gray-500">Sin solicitudes registradas.</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-2">Modos</h3>
                        <ul class="space-y-2">
                            <?php foreach ($aiModeCounts as $mode => $count): ?>
                                <li class="flex items-center justify-between text-sm text-gray-700">
                                    <span class="capitalize"><?= htmlspecialchars(str_replace('_', ' ', $mode)) ?></span>
                                    <span class="font-semibold"><?= number_format($count, 0, ',', '.') ?></span>
                                </li>
                            <?php endforeach; ?>
                            <?php if (empty($aiModeCounts)): ?>
                                <li class="text-sm text-gray-500">Todavía no se utiliza la asistencia.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 text-amber-500 mt-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M8.257 3.099c.366-.756 1.42-.756 1.786 0l6.518 13.492c.33.685-.173 1.409-.893 1.409H2.632c-.72 0-1.223-.724-.893-1.409L8.257 3.1zM11 14a1 1 0 10-2 0 1 1 0 002 0zm-.25-6.75a.75.75 0 00-1.5 0v3.5a.75.75 0 001.5 0v-3.5z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-amber-700">
                                Alertas de políticas pendientes: <?= number_format($flaggedCount, 0, ',', '.') ?>
                            </p>
                            <p class="mt-1 text-xs text-amber-700">
                                Revisa el bloque inferior para ver detalles y definir acciones de seguimiento.
                            </p>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <section class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6">
            <header class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Generaciones recientes</h2>
                    <p class="text-sm text-gray-500">
                        Filtra por estado para identificar solicitudes que necesitan intervención o seguimiento.
                    </p>
                </div>
                <form method="GET" action="<?= Router::url('admin/ia') ?>" class="flex flex-col sm:flex-row gap-3">
                    <label class="flex flex-col text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Estado
                        <select name="estado" class="form-select mt-1 rounded-lg border-gray-300 focus:border-copihue-500 focus:ring-copihue-500">
                            <?php
                            $estadoOptions = [
                                'pendientes' => 'Pendientes / moderación',
                                'moderadas' => 'Solo moderadas',
                                'completadas' => 'Completadas',
                                'fallidas' => 'Fallidas',
                                'rechazadas' => 'Rechazadas',
                                'todas' => 'Todas las solicitudes',
                            ];
                            foreach ($estadoOptions as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $aiFilters['estado'] === $value ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="flex flex-col text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Alertas
                        <select name="accion" class="form-select mt-1 rounded-lg border-gray-300 focus:border-copihue-500 focus:ring-copihue-500">
                            <?php
                            $accionOptions = [
                                'pendientes' => 'Pendientes de revisión',
                                'bloqueadas' => 'Bloqueadas',
                                'permitidas' => 'Permitidas',
                                'revisadas' => 'Revisadas por humano',
                                'todas' => 'Todas las alertas',
                            ];
                            foreach ($accionOptions as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $aiFilters['accion'] === $value ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="submit" class="btn-primary self-end sm:self-center">Actualizar</button>
                </form>
            </header>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600 uppercase tracking-wide">Fecha</th>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600 uppercase tracking-wide">Usuario</th>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600 uppercase tracking-wide">Modo / Modelo</th>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600 uppercase tracking-wide">Prompt</th>
                            <th scope="col" class="px-4 py-3 text-center font-semibold text-gray-600 uppercase tracking-wide">Tokens</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-600 uppercase tracking-wide">Costo</th>
                            <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600 uppercase tracking-wide">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <?php foreach ($aiGenerations as $generation): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                    <?php if (!empty($generation['created_at'])): ?>
                                        <time datetime="<?= htmlspecialchars($generation['created_at']) ?>">
                                            <?= date('d/m/Y H:i', strtotime($generation['created_at'])) ?>
                                        </time>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-gray-800"><?= htmlspecialchars($generation['user_name'] ?? 'Usuario') ?></span>
                                        <?php if (!empty($generation['user_email'])): ?>
                                            <span class="text-xs text-gray-500"><?= htmlspecialchars($generation['user_email']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    <div class="flex flex-col">
                                        <span class="capitalize font-medium"><?= htmlspecialchars($generation['mode']) ?></span>
                                        <span class="text-xs text-gray-500"><?= htmlspecialchars($generation['model_used'] ?? '') ?></span>
                                        <?php if (!empty($generation['provider'])): ?>
                                            <span class="text-xs text-gray-400">Proveedor: <?= htmlspecialchars($generation['provider']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    <p class="line-clamp-3"><?= nl2br(htmlspecialchars($generation['prompt_excerpt'] ?? '')) ?></p>
                                    <?php if (!empty($generation['error_message'])): ?>
                                        <span class="mt-1 inline-flex items-center gap-1 text-xs text-rose-600">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3a1 1 0 102 0V7zm-1 6a1.25 1.25 0 110 2.5 1.25 1.25 0 010-2.5z" clip-rule="evenodd" />
                                            </svg>
                                            <?= htmlspecialchars($generation['error_message']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center text-gray-700">
                                    <span class="block text-xs text-gray-500">Entrada</span>
                                    <span class="font-semibold"><?= $generation['tokens_input'] !== null ? number_format($generation['tokens_input'], 0, ',', '.') : '—' ?></span>
                                    <span class="block text-xs text-gray-500 mt-1">Salida</span>
                                    <span class="font-semibold"><?= $generation['tokens_output'] !== null ? number_format($generation['tokens_output'], 0, ',', '.') : '—' ?></span>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-700">
                                    <?= $generation['cost_estimate'] !== null ? '$' . number_format($generation['cost_estimate'], 3, ',', '.') : '—' ?>
                                    <?php if ($generation['latency_ms'] !== null): ?>
                                        <span class="block text-xs text-gray-500 mt-1"><?= number_format($generation['latency_ms'], 0, ',', '.') ?> ms</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php $meta = $generation['status_meta'] ?? null; ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $meta['badge_class'] ?? 'bg-gray-100 text-gray-700' ?>">
                                        <?= htmlspecialchars($meta['label'] ?? ucfirst($generation['status'] ?? 'pendiente')) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($aiGenerations)): ?>
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">
                                    No se encontraron solicitudes con los filtros seleccionados.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6">
            <header class="flex flex-col gap-2 mb-6">
                <h2 id="ai-alertas" class="text-lg font-semibold text-gray-900">Alertas de políticas y seguridad</h2>
                <p class="text-sm text-gray-500">
                    Revisa los casos marcados por la moderación automática para decidir si se aprueban, bloquean o escalan.
                </p>
            </header>

            <?php if (empty($aiPolicyEvents)): ?>
                <p class="text-sm text-gray-500">No hay alertas recientes. Excelente trabajo de supervisión.</p>
            <?php else: ?>
                <ul class="space-y-4">
                    <?php foreach ($aiPolicyEvents as $event): ?>
                        <li class="border border-gray-100 rounded-2xl p-4 hover:border-copihue-200 transition">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $event['action_meta']['badge_class'] ?? 'bg-gray-100 text-gray-700' ?>">
                                        <?= htmlspecialchars($event['action_meta']['label'] ?? ucfirst($event['action'] ?? '')) ?>
                                    </span>
                                    <h3 class="mt-2 text-sm font-semibold text-gray-900">
                                        <?= htmlspecialchars(ucfirst($event['policy_type'] ?? 'moderación')) ?>
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-600">
                                        <?= htmlspecialchars($event['reason'] ?? 'Sin detalle adicional') ?>
                                    </p>
                                    <?php if ($event['confidence_score'] !== null): ?>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Confianza estimada: <?= number_format($event['confidence_score'], 1, ',', '.') ?>%
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right text-xs text-gray-500 space-y-1">
                                    <?php if (!empty($event['created_at'])): ?>
                                        <time datetime="<?= htmlspecialchars($event['created_at']) ?>">
                                            <?= date('d/m/Y H:i', strtotime($event['created_at'])) ?>
                                        </time>
                                    <?php endif; ?>
                                    <?php if ($event['reviewed_at']): ?>
                                        <p>Revisado: <?= date('d/m/Y H:i', strtotime($event['reviewed_at'])) ?></p>
                                        <?php if (!empty($event['reviewer_name'])): ?>
                                            <p><?= htmlspecialchars($event['reviewer_name']) ?></p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-700">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0V10a.75.75 0 00.356.64l2.5 1.5a.75.75 0 10.738-1.28l-2.094-1.256V6.75z" clip-rule="evenodd" />
                                            </svg>
                                            Pendiente revisión
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-gray-600">
                                <div>
                                    <p class="font-semibold text-gray-500 uppercase tracking-wide">Usuario</p>
                                    <p><?= htmlspecialchars($event['user_name'] ?? 'Sin identificar') ?></p>
                                    <?php if (!empty($event['user_email'])): ?>
                                        <p><?= htmlspecialchars($event['user_email']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-500 uppercase tracking-wide">Generación asociada</p>
                                    <p>
                                        <?= $event['generation_mode'] ? htmlspecialchars(str_replace('_', ' ', $event['generation_mode'])) . ' · ' : '' ?>
                                        <?= $event['generation_provider'] ? htmlspecialchars(str_replace('_', ' ', $event['generation_provider'])) : '' ?>
                                    </p>
                                    <?php if ($event['generation_status_meta']): ?>
                                        <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-[11px] font-medium <?= $event['generation_status_meta']['badge_class'] ?>">
                                            <?= htmlspecialchars($event['generation_status_meta']['label']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($event['flagged_content'])): ?>
                                    <div class="md:col-span-2">
                                        <p class="font-semibold text-gray-500 uppercase tracking-wide">Detalle del contenido</p>
                                        <pre class="mt-1 text-xs bg-gray-50 border border-gray-100 rounded-xl p-3 overflow-x-auto"><?=
                                            htmlspecialchars(is_array($event['flagged_content'])
                                                ? json_encode($event['flagged_content'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                                                : (string)$event['flagged_content'])
                                        ?></pre>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();

include VIEWS_PATH . '/layouts/admin.php';
?>
