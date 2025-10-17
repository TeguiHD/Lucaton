<?php
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/navigation.php';
require_once __DIR__ . '/../components/alerts.php';
require_once __DIR__ . '/../components/cards.php';

$campaign = $campaign ?? [];
$campaignTitle = $campaign['title'] ?? 'Campaña';
$campaignSlug = $campaign['slug'] ?? ($campaign['id'] ?? '');
$campaignPublicPath = $campaign['public_path'] ?? CampaignPresenter::buildPublicPath($campaign);
if ($campaignPublicPath === null && $campaignSlug !== '') {
    $campaignPublicPath = 'campana/' . rawurlencode($campaignSlug);
}
$campaignUrl = $campaignPublicPath !== null ? Router::url($campaignPublicPath) : Router::url('campanas');
$breadcrumbs = $breadcrumbs ?? [];
$donations = $donations ?? [];
$page = max(1, (int)($page ?? 1));
$totalPages = max(1, (int)($totalPages ?? 1));
$totalDonations = (int)($totalDonations ?? 0);
$perPage = (int)($perPage ?? 15);
$firstItem = $totalDonations > 0 ? (($page - 1) * $perPage) + 1 : 0;
$lastItem = $totalDonations > 0 ? min($totalDonations, $firstItem + count($donations) - 1) : 0;
$page_title = $page_title ?? ('Aportes de ' . $campaignTitle . ' - Lucatón');
$page_description = $page_description ?? ('Historial de aportes registrados para la campaña ' . $campaignTitle . '.');
$activeFilter = $activeFilter ?? 'all';
$donationFilterOptions = $donationFilterOptions ?? [
    'all' => 'Todos los aportes',
    'public' => 'Aportes públicos',
    'anonymous' => 'Aportes anónimos'
];
$filterCounts = $filterCounts ?? [];
$totalAllDonations = (int)($filterCounts['all'] ?? $totalDonations);
$baseFilterLabel = $donationFilterOptions[$activeFilter] ?? 'Todos los aportes';
$filterHelpText = [
    'all' => 'Mostrando el historial completo de colaboraciones.',
    'public' => 'Se muestran solo aportes con nombre visible.',
    'anonymous' => 'Se muestran aportes que decidieron mantener su nombre oculto.'
];
$baseDonationsUrl = $campaignPublicPath !== null
    ? Router::url(rtrim($campaignPublicPath, '/') . '/donaciones')
    : Router::url('campana/' . $campaignSlug . '/donaciones');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($baseDonationsUrl) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_description) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= asset_url('images/favicon.svg') ?>">
    <link href="<?= asset_url('css/app.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('css/aliases.css') ?>" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <?php include __DIR__ . '/../layouts/partials/header.php'; ?>

    <main id="main-content" class="py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <?php if (!empty($breadcrumbs)): ?>
                <nav aria-label="Breadcrumb" class="text-sm">
                    <ol class="flex flex-wrap items-center gap-2 text-gray-500">
                        <?php foreach ($breadcrumbs as $index => $breadcrumb): ?>
                            <?php if (!empty($breadcrumb['href']) && $index < count($breadcrumbs) - 1): ?>
                                <li>
                                    <a href="<?= htmlspecialchars($breadcrumb['href']) ?>" class="hover:text-copihue-600 transition">
                                        <?= htmlspecialchars($breadcrumb['name']) ?>
                                    </a>
                                </li>
                                <li class="text-gray-400">/</li>
                            <?php else: ?>
                                <li class="text-gray-700 font-medium"><?= htmlspecialchars($breadcrumb['name']) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                </nav>
            <?php endif; ?>

            <header class="space-y-3">
                <h1 class="text-3xl font-bold text-gray-900">Aportes a "<?= htmlspecialchars($campaignTitle) ?>"</h1>
                <p class="text-gray-600 text-sm sm:text-base">
                    Consulta quiénes han apoyado esta campaña. Para proteger la privacidad, los aportes marcados como anónimos muestran solo el monto.
                </p>
                <a href="<?= htmlspecialchars($campaignUrl) ?>" class="inline-flex items-center gap-2 text-sm text-copihue-600 hover:text-copihue-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Volver a la campaña
                </a>
            </header>

            <section class="bg-white shadow-soft rounded-3xl p-6 space-y-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <form method="get" action="<?= htmlspecialchars($baseDonationsUrl) ?>" class="w-full max-w-xs">
                        <label for="donations-filter" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Filtrar aportes
                        </label>
                        <div class="mt-1 relative">
                            <select id="donations-filter" name="filtro" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-copihue-500 focus:outline-none focus:ring-2 focus:ring-copihue-400" onchange="this.form.submit()">
                                <?php foreach ($donationFilterOptions as $filterKey => $label): ?>
                                    <?php $count = (int)($filterCounts[$filterKey] ?? ($filterKey === 'all' ? $totalAllDonations : 0)); ?>
                                    <option value="<?= htmlspecialchars($filterKey) ?>" <?= $activeFilter === $filterKey ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label . ' (' . $count . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <noscript>
                            <button type="submit" class="mt-2 inline-flex items-center rounded-md bg-copihue-600 px-3 py-1 text-xs font-semibold text-white">
                                Aplicar filtro
                            </button>
                        </noscript>
                    </form>
                    <div class="text-xs text-gray-500">
                        <p><?= htmlspecialchars($filterHelpText[$activeFilter] ?? $filterHelpText['all']) ?></p>
                        <?php if ($activeFilter !== 'all'): ?>
                            <p>Total general: <?= (int)$totalAllDonations ?> aportes registrados.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($totalDonations === 0): ?>
                    <p class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                        <?php if ($activeFilter === 'anonymous'): ?>
                            Aún no se registran aportes anónimos para esta campaña.
                        <?php elseif ($activeFilter === 'public'): ?>
                            Todavía no hay aportes públicos visibles. ¡Anima a tu comunidad a compartir su apoyo!
                        <?php else: ?>
                            Aún no se registran aportes. ¡Sé la primera persona en apoyar esta causa!
                        <?php endif; ?>
                    </p>
                <?php else: ?>
                    <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-gray-500">
                        <span>Mostrando <?= $firstItem ?>-<?= $lastItem ?> de <?= $totalDonations ?> aportes</span>
                        <span><?= htmlspecialchars($baseFilterLabel) ?> · Ordenados del más reciente al más antiguo</span>
                    </div>

                    <ul class="space-y-4">
                        <?php foreach ($donations as $donation): ?>
                            <?php
                            $isAnonymous = !empty($donation['is_anonymous']);
                            $name = 'Aporte anónimo';
                            if (!$isAnonymous) {
                                $name = trim(($donation['first_name'] ?? '') . ' ' . ($donation['last_name'] ?? ''));
                                if ($name === '') {
                                    $name = $donation['supporter_name'] ?? $donation['donor_name'] ?? $donation['username'] ?? 'Colaborador';
                                }
                            }
                            $amount = '$' . number_format((float)($donation['amount'] ?? 0), 0, ',', '.');
                            $dateLabel = isset($donation['created_at'])
                                ? date('d M Y', strtotime($donation['created_at']))
                                : null;
                            ?>
                            <li class="rounded-2xl border border-gray-100 bg-gray-50 p-4 sm:p-5">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div class="space-y-1">
                                        <p class="text-base font-semibold text-gray-900"><?= htmlspecialchars($name) ?></p>
                                        <?php if (!empty($donation['message'])): ?>
                                            <p class="text-sm text-gray-600 leading-relaxed">“<?= htmlspecialchars($donation['message']) ?>”</p>
                                        <?php endif; ?>
                                        <?php if ($dateLabel): ?>
                                            <p class="text-xs text-gray-500">Registrado el <?= htmlspecialchars($dateLabel) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-right sm:text-left">
                                        <p class="text-lg font-semibold text-copihue-600"><?= htmlspecialchars($amount) ?></p>
                                        <?php if ($isAnonymous): ?>
                                            <p class="text-xs text-gray-500">Aporte anónimo</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ($totalPages > 1): ?>
                        <nav class="flex items-center justify-between pt-4" aria-label="Paginación">
                            <?php
                            $prevPage = max(1, $page - 1);
                            $nextPage = min($totalPages, $page + 1);
                            $buildPageUrl = static function (int $target) use ($baseDonationsUrl, $activeFilter) {
                                $query = [];
                                if ($target > 1) {
                                    $query['page'] = $target;
                                }
                                if ($activeFilter !== 'all') {
                                    $query['filtro'] = $activeFilter;
                                }

                                if (empty($query)) {
                                    return $baseDonationsUrl;
                                }

                                return $baseDonationsUrl . '?' . http_build_query($query);
                            };
                            ?>
                            <a href="<?= htmlspecialchars($buildPageUrl($prevPage)) ?>" class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-copihue-600 <?= $page <= 1 ? 'pointer-events-none opacity-40' : '' ?>">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Anteriores
                            </a>
                            <span class="text-sm text-gray-500">Página <?= $page ?> de <?= $totalPages ?></span>
                            <a href="<?= htmlspecialchars($buildPageUrl($nextPage)) ?>" class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-copihue-600 <?= $page >= $totalPages ? 'pointer-events-none opacity-40' : '' ?>">
                                Siguientes
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <?php include __DIR__ . '/../layouts/partials/footer.php'; ?>
</body>
</html>
