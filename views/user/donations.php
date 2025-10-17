<?php
require_once __DIR__ . '/../layouts/partials/flash-messages.php';
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/navigation.php';
require_once APP_PATH . '/Services/CampaignPresenter.php';

$page_title = 'Mis Donaciones — Lucatón';
$page_description = 'Consulta el historial de aportes que has realizado en Lucatón.';

$donations = $donations ?? [];
$summary = $summary ?? [
    'total_donations' => 0,
    'total_amount' => 0.0,
    'completed_donations' => 0,
    'completed_amount' => 0.0,
    'average_completed' => 0.0,
    'last_donation_at' => null,
];
$statusCounts = $statusCounts ?? ['all' => $summary['total_donations'] ?? 0];
$statusOptions = $statusOptions ?? [
    'all' => 'Todos',
    'pending' => 'Pendientes',
    'processing' => 'En proceso',
    'completed' => 'Completadas',
    'failed' => 'Fallidas',
    'refunded' => 'Reembolsadas',
];
$orderOptions = $orderOptions ?? [
    'recent' => 'Más recientes',
    'amount_desc' => 'Monto: de mayor a menor',
    'amount_asc' => 'Monto: de menor a mayor',
];
$statusFilter = $statusFilter ?? 'all';
$searchTerm = $searchTerm ?? '';
$order = $order ?? 'recent';
$pagination = $pagination ?? [
    'page' => 1,
    'per_page' => 10,
    'total_pages' => 1,
    'total_items' => 0,
];
$lastDonationHuman = isset($lastDonationHuman) && $lastDonationHuman !== '' ? $lastDonationHuman : 'Sin registros';

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

$statusStyles = [
    'pending' => ['label' => 'Pendiente', 'class' => 'bg-amber-100 text-amber-800'],
    'processing' => ['label' => 'En proceso', 'class' => 'bg-blue-100 text-blue-800'],
    'completed' => ['label' => 'Completada', 'class' => 'bg-emerald-100 text-emerald-800'],
    'failed' => ['label' => 'Fallida', 'class' => 'bg-rose-100 text-rose-700'],
    'refunded' => ['label' => 'Reembolsada', 'class' => 'bg-slate-100 text-slate-700'],
];

$statusBadge = static function (string $status) use ($statusStyles): array {
    $normalized = strtolower($status);
    return $statusStyles[$normalized] ?? ['label' => ucfirst($normalized), 'class' => 'bg-gray-100 text-gray-700'];
};

$baseUrl = Router::url('mis-donaciones');
$buildQuery = static function (array $overrides = []) use ($statusFilter, $searchTerm, $order): string {
    $query = [
        'estado' => $statusFilter,
        'orden' => $order,
    ];
    if ($searchTerm !== '') {
        $query['q'] = $searchTerm;
    }
    $query = array_merge($query, $overrides);
    $query = array_filter($query, static fn ($value) => $value !== '' && $value !== null);
    return http_build_query($query);
};

$statusCountsNormalized = ['all' => $statusCounts['all'] ?? 0];
foreach ($statusOptions as $key => $label) {
    if ($key === 'all') {
        continue;
    }
    $statusCountsNormalized[$key] = $statusCounts[$key] ?? 0;
}

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
            ['name' => 'Mis Donaciones', 'href' => Router::url('mis-donaciones')],
        ]); ?>

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Mis donaciones</h1>
            <p class="mt-2 text-sm text-gray-600">
                Revisa tus aportes, filtra por estado y descarga la información que necesites para tu respaldo personal.
            </p>
        </div>

        <section class="grid gap-6 lg:grid-cols-4 mb-10">
            <div class="rounded-2xl bg-white shadow-soft border border-gray-100 p-5">
                <p class="text-sm font-medium text-gray-500">Aportes completados</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900"><?= number_format((int)$summary['completed_donations']) ?></p>
                <p class="mt-1 text-xs text-gray-500"><?= number_format((int)$summary['total_donations']) ?> totales (incluye pendientes y en revisión)</p>
            </div>
            <div class="rounded-2xl bg-white shadow-soft border border-gray-100 p-5">
                <p class="text-sm font-medium text-gray-500">Monto aportado</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900"><?= $formatCurrency((float)$summary['completed_amount']) ?></p>
                <p class="mt-1 text-xs text-gray-500">Considera sólo aportes marcados como completados</p>
            </div>
            <div class="rounded-2xl bg-white shadow-soft border border-gray-100 p-5">
                <p class="text-sm font-medium text-gray-500">Promedio por aporte</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900"><?= $formatCurrency((float)$summary['average_completed']) ?></p>
                <p class="mt-1 text-xs text-gray-500">Calculado sobre aportes completados</p>
            </div>
            <div class="rounded-2xl bg-white shadow-soft border border-gray-100 p-5">
                <p class="text-sm font-medium text-gray-500">Último aporte</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900"><?= htmlspecialchars($lastDonationHuman) ?></p>
                <p class="mt-1 text-xs text-gray-500"><?= $summary['last_donation_at'] ? 'Registrado el ' . htmlspecialchars($formatDate($summary['last_donation_at'])) : 'Todavía no registras aportes' ?></p>
            </div>
        </section>

        <section class="mb-8">
            <div class="bg-white shadow-soft border border-gray-100 rounded-2xl p-5">
                <form method="GET" action="<?= htmlspecialchars($baseUrl) ?>" class="grid gap-4 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <label for="donations-search" class="block text-sm font-medium text-gray-700">Buscar campaña o mensaje</label>
                        <div class="mt-1 relative">
                            <input
                                type="search"
                                id="donations-search"
                                name="q"
                                value="<?= htmlspecialchars($searchTerm) ?>"
                                placeholder="Ej: Emergencia Valparaíso"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-copihue-500 focus:ring-copihue-500"
                            >
                            <?php if ($searchTerm !== ''): ?>
                                <a href="<?= htmlspecialchars($baseUrl . '?' . $buildQuery(['q' => null, 'page' => 1])) ?>" class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600">
                                    <span class="sr-only">Limpiar búsqueda</span>
                                    &times;
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <label for="donations-status" class="block text-sm font-medium text-gray-700">Estado</label>
                        <select
                            id="donations-status"
                            name="estado"
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500"
                        >
                            <?php foreach ($statusOptions as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= $statusFilter === $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?> (<?= number_format($statusCountsNormalized[$value] ?? 0) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="donations-order" class="block text-sm font-medium text-gray-700">Ordenar por</label>
                        <select
                            id="donations-order"
                            name="orden"
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500"
                        >
                            <?php foreach ($orderOptions as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= $order === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-4 flex items-center justify-end gap-3">
                        <a href="<?= htmlspecialchars($baseUrl) ?>" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Restablecer
                        </a>
                        <button type="submit" class="inline-flex items-center rounded-md bg-copihue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-copihue-700 focus:outline-none focus:ring-2 focus:ring-copihue-500 focus:ring-offset-2">
                            Aplicar filtros
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <section class="bg-white shadow-soft border border-gray-100 rounded-2xl">
            <?php if (empty($donations)): ?>
                <div class="p-10 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-copihue-50 text-copihue-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2-1.343-2-3-2zm0-4v4m0 8c-1.657 0-3-.895-3-2s1.343-2 3-2 3 .895 3 2-1.343 2-3 2zm0 0v4" />
                        </svg>
                    </div>
                    <h2 class="mt-4 text-lg font-semibold text-gray-900">Todavía no hay aportes registrados</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Cuando realices una donación aparecerá aquí con todos los detalles y su estado actualizado.
                    </p>
                    <div class="mt-6">
                        <a href="<?= htmlspecialchars(Router::url('campanas')) ?>" class="inline-flex items-center rounded-md bg-copihue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-copihue-700 hover:text-white">
                            Explorar campañas
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th scope="col" class="px-6 py-3">Campaña</th>
                                <th scope="col" class="px-6 py-3">Monto</th>
                                <th scope="col" class="px-6 py-3">Estado</th>
                                <th scope="col" class="px-6 py-3">Mensaje</th>
                                <th scope="col" class="px-6 py-3">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <?php foreach ($donations as $donation): ?>
                            <?php
                                $campaignTitle = $donation['campaign_title'] ?? 'Campaña';
                                $campaignSlug = $donation['campaign_slug'] ?? null;
                                $campaignOwnerUsername = $donation['campaign_owner_username'] ?? null;
                                $campaignOwnerSlug = $donation['campaign_owner_slug'] ?? null;
                                $campaignId = $donation['campaign_id'] ?? $donation['campaignId'] ?? null;
                                $campaignPath = $donation['campaign_public_path'] ?? null;

                                if ($campaignPath === null) {
                                    $pathPayload = [
                                        'id' => $campaignId,
                                        'slug' => $campaignSlug,
                                    ];
                                    if ($campaignOwnerUsername !== null && $campaignOwnerUsername !== '') {
                                        $pathPayload['owner_username'] = $campaignOwnerUsername;
                                    } elseif ($campaignOwnerSlug !== null && $campaignOwnerSlug !== '') {
                                        $pathPayload['owner_username'] = $campaignOwnerSlug;
                                    }

                                    $campaignPath = CampaignPresenter::buildPublicPath($pathPayload);
                                }

                                $amount = (float)($donation['amount'] ?? 0);
                                $status = strtolower((string)($donation['status'] ?? ''));
                                $message = trim((string)($donation['message'] ?? ''));
                                $createdAt = $donation['created_at'] ?? null;
                                $badge = $statusBadge($status);
                            ?>
                            <tr class="hover:bg-gray-50/70">
                                <td class="px-6 py-4 align-top">
                                    <div class="font-semibold text-gray-900">
                                        <?php if ($campaignPath !== null): ?>
                                            <a
                                                href="<?= htmlspecialchars(Router::url($campaignPath)) ?>"
                                                class="inline-flex items-center gap-1 text-copihue-600 transition hover:text-copihue-700 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-copihue-500 focus-visible:ring-offset-2 rounded-sm"
                                                title="Abrir campaña"
                                            >
                                                <span><?= htmlspecialchars($campaignTitle) ?></span>
                                                <svg class="h-4 w-4 opacity-80" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M5.22 5.22a.75.75 0 0 1 1.06 0L12 10.94V7.5a.75.75 0 0 1 1.5 0v6a.75.75 0 0 1-.75.75h-6a.75.75 0 0 1 0-1.5h3.44L5.22 6.28a.75.75 0 0 1 0-1.06z" clip-rule="evenodd" />
                                                </svg>
                                            </a>
                                        <?php else: ?>
                                            <span><?= htmlspecialchars($campaignTitle) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($donation['cover_image_url'])): ?>
                                        <div class="mt-1 text-xs text-gray-400">ID #<?= htmlspecialchars((string)($donation['id'] ?? '')) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <span class="font-semibold text-gray-900"><?= $formatCurrency($amount) ?></span>
                                    <?php if (!empty($donation['currency']) && strtoupper($donation['currency']) !== 'CLP'): ?>
                                        <p class="mt-1 text-xs text-gray-500"><?= htmlspecialchars(strtoupper($donation['currency'])) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?= htmlspecialchars($badge['class']) ?>">
                                        <?= htmlspecialchars($badge['label']) ?>
                                    </span>
                                    <?php if (!empty($donation['payment_method'])): ?>
                                        <p class="mt-2 text-xs text-gray-500">Método: <?= htmlspecialchars(str_replace('_', ' ', $donation['payment_method'])) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <?php if ($message !== ''): ?>
                                        <p class="text-sm text-gray-700 leading-relaxed"><?= htmlspecialchars($message) ?></p>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">Sin mensaje</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <p class="text-sm text-gray-700"><?= htmlspecialchars($formatDate($createdAt)) ?></p>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($pagination['total_pages'] > 1): ?>
                    <nav class="flex items-center justify-between border-t border-gray-100 px-6 py-4 text-sm text-gray-600" aria-label="Paginación de donaciones">
                        <?php
                            $currentPage = $pagination['page'];
                            $prevPage = max(1, $currentPage - 1);
                            $nextPage = min($pagination['total_pages'], $currentPage + 1);
                        ?>
                        <div>
                            Mostrando
                            <span class="font-semibold"><?= (($currentPage - 1) * $pagination['per_page']) + 1 ?></span>
                            -
                            <span class="font-semibold"><?= min($pagination['total_items'], $currentPage * $pagination['per_page']) ?></span>
                            de <span class="font-semibold"><?= number_format($pagination['total_items']) ?></span> aportes
                        </div>

                        <div class="flex items-center gap-2">
                            <a
                                href="<?= htmlspecialchars($baseUrl . '?' . $buildQuery(['page' => $prevPage])) ?>"
                                class="inline-flex items-center rounded-md border border-gray-300 px-3 py-1.5 text-sm <?= $currentPage === 1 ? 'pointer-events-none text-gray-400' : 'text-gray-700 hover:bg-gray-50' ?>"
                                aria-disabled="<?= $currentPage === 1 ? 'true' : 'false' ?>"
                            >
                                Anterior
                            </a>
                            <span class="text-gray-400">•</span>
                            <a
                                href="<?= htmlspecialchars($baseUrl . '?' . $buildQuery(['page' => $nextPage])) ?>"
                                class="inline-flex items-center rounded-md border border-gray-300 px-3 py-1.5 text-sm <?= $currentPage === $pagination['total_pages'] ? 'pointer-events-none text-gray-400' : 'text-gray-700 hover:bg-gray-50' ?>"
                                aria-disabled="<?= $currentPage === $pagination['total_pages'] ? 'true' : 'false' ?>"
                            >
                                Siguiente
                            </a>
                        </div>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>

    <?php include VIEWS_PATH . '/layouts/partials/footer.php'; ?>
</body>
</html>
