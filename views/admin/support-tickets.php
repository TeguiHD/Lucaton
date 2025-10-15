<?php
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$page_title = $page_title ?? 'Reportes de soporte';
$current_page = $current_page ?? 'admin-support';
$visibleTickets = $filteredTickets ?? [];
$stats = $stats ?? [
    'total' => 0,
    'open' => 0,
    'by_severity' => ['alta' => 0, 'media' => 0, 'baja' => 0],
    'by_type' => [],
    'latest_created_at' => null,
];
$filters = array_merge([
    'severity' => 'all',
    'type' => 'all',
    'status' => 'all',
    'q' => '',
], $filters ?? []);

$severityLabels = [
    'all' => 'Todas',
    'alta' => 'Alta (bloqueante)',
    'media' => 'Media',
    'baja' => 'Baja',
];

$severityStyles = [
    'alta' => 'bg-red-100 text-red-700 border border-red-200',
    'media' => 'bg-amber-100 text-amber-700 border border-amber-200',
    'baja' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
];

$typeLabels = [
    'all' => 'Todos',
    'tecnico' => 'Error técnico',
    'pagos' => 'Pagos y donaciones',
    'contenido' => 'Contenido o campañas',
    'seguridad' => 'Seguridad',
    'otro' => 'Otro',
];

$statusLabels = [
    'all' => 'Todos',
    'open' => 'Pendientes',
    'closed' => 'Resueltos',
    'archived' => 'Archivados',
];

$statusStyles = [
    'open' => 'bg-blue-100 text-blue-700 border border-blue-200',
    'closed' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
    'archived' => 'bg-gray-100 text-gray-600 border border-gray-200',
];

$latestLabel = '—';
if (!empty($stats['latest_created_at'])) {
    $timestamp = strtotime((string)$stats['latest_created_at']);
    if ($timestamp !== false) {
        $latestLabel = date('d/m/Y H:i', $timestamp);
    }
}

$truncate = static function (?string $value, int $limit = 160): string {
    $text = trim((string)$value);
    if ($text === '') {
        return '—';
    }

    if (function_exists('mb_strimwidth')) {
        return mb_strlen($text, 'UTF-8') > $limit
            ? mb_strimwidth($text, 0, $limit, '...', 'UTF-8')
            : $text;
    }

    return strlen($text) > $limit ? substr($text, 0, $limit - 3) . '...' : $text;
};
?>

<?php ob_start(); ?>
<div class="space-y-6">
    <?php include_flash_messages(); ?>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Reportes totales</p>
            <p class="mt-3 text-3xl font-semibold text-gray-900">
                <?= number_format((int)($stats['total'] ?? 0), 0, ',', '.') ?>
            </p>
            <p class="mt-1 text-xs text-gray-500">Historial limitado a los últimos 200 registros.</p>
        </article>

        <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pendientes</p>
            <p class="mt-3 text-3xl font-semibold text-gray-900">
                <?= number_format((int)($stats['open'] ?? 0), 0, ',', '.') ?>
            </p>
            <p class="mt-1 text-xs text-gray-500">Reportes con estado abierto.</p>
        </article>

        <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Severidad alta</p>
            <p class="mt-3 text-3xl font-semibold text-gray-900">
                <?= number_format((int)($stats['by_severity']['alta'] ?? 0), 0, ',', '.') ?>
            </p>
            <p class="mt-1 text-xs text-red-500 font-medium">Prioriza estos casos.</p>
        </article>

        <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Último ingreso</p>
            <p class="mt-3 text-xl font-semibold text-gray-900">
                <?= htmlspecialchars($latestLabel) ?>
            </p>
            <p class="mt-1 text-xs text-gray-500">Fecha y hora del reporte más reciente.</p>
        </article>
    </section>

    <section class="bg-white shadow-soft rounded-3xl p-6 border border-gray-100">
        <form method="GET" action="<?= Router::url('admin/reportes') ?>" class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div>
                <label for="filter-severity" class="block text-sm font-medium text-gray-700 mb-1">Impacto</label>
                <select id="filter-severity" name="severity" class="form-select block w-full rounded-lg border-gray-300 focus:border-copihue-500 focus:ring-copihue-500">
                    <?php foreach ($severityLabels as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>" <?= $filters['severity'] === $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="filter-type" class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                <select id="filter-type" name="type" class="form-select block w-full rounded-lg border-gray-300 focus:border-copihue-500 focus:ring-copihue-500">
                    <?php foreach ($typeLabels as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>" <?= $filters['type'] === $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="filter-status" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select id="filter-status" name="status" class="form-select block w-full rounded-lg border-gray-300 focus:border-copihue-500 focus:ring-copihue-500">
                    <?php foreach ($statusLabels as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="md:col-span-1">
                <label for="filter-query" class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <input
                    id="filter-query"
                    name="q"
                    type="search"
                    value="<?= htmlspecialchars($filters['q'] ?? '') ?>"
                    placeholder="Nombre, correo o palabra clave"
                    class="form-input block w-full rounded-lg border-gray-300 focus:border-copihue-500 focus:ring-copihue-500"
                >
            </div>

            <div class="md:col-span-4 flex items-center gap-3">
                <button type="submit" class="btn-primary">Aplicar filtros</button>
                <a href="<?= Router::url('admin/reportes') ?>" class="text-sm text-gray-500 hover:text-gray-700">Restablecer</a>
            </div>
        </form>
    </section>

    <section class="bg-white shadow-soft rounded-3xl border border-gray-100 overflow-hidden">
        <header class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Reportes recibidos</h2>
                <p class="text-sm text-gray-500">Los reportes se almacenan en <code>storage/private/support-tickets.jsonl</code>.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                <?= number_format(count($visibleTickets), 0, ',', '.') ?> resultados
            </span>
        </header>

        <?php if (empty($visibleTickets)): ?>
            <div class="px-6 py-12 text-center text-sm text-gray-600">
                No hay reportes que coincidan con los filtros seleccionados.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Folio</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resumen</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contacto</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enlace</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recibido</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($visibleTickets as $ticket): ?>
                            <?php
                                $ticketId = htmlspecialchars((string)($ticket['id'] ?? 'N/A'));
                                $ticketSeverity = strtolower((string)($ticket['severity'] ?? 'baja'));
                                $ticketType = strtolower((string)($ticket['type'] ?? 'otro'));
                                $ticketStatus = strtolower((string)($ticket['status'] ?? 'open'));

                                $severityClass = $severityStyles[$ticketSeverity] ?? 'bg-gray-100 text-gray-700 border border-gray-200';
                                $severityLabel = $severityLabels[$ticketSeverity] ?? ucfirst($ticketSeverity);
                                $typeLabel = $typeLabels[$ticketType] ?? ucfirst($ticketType);
                                $statusLabel = $statusLabels[$ticketStatus] ?? ucfirst($ticketStatus);
                                $statusClass = $statusStyles[$ticketStatus] ?? 'bg-gray-100 text-gray-600 border border-gray-200';

                                $createdAt = $ticket['created_at'] ?? null;
                                $createdAtDisplay = '—';
                                if ($createdAt) {
                                    $createdTimestamp = strtotime((string)$createdAt);
                                    if ($createdTimestamp !== false) {
                                        $createdAtDisplay = date('d/m/Y H:i', $createdTimestamp);
                                    }
                                }

                                $snippet = $truncate($ticket['description'] ?? '', 160);
                                $fullDescription = nl2br(htmlspecialchars((string)($ticket['description'] ?? ''), ENT_QUOTES, 'UTF-8'), false);
                                $contactName = htmlspecialchars((string)($ticket['name'] ?? 'Anónimo'));
                                $contactEmail = htmlspecialchars((string)($ticket['email'] ?? 'Sin correo'));
                                $contactUrl = trim((string)($ticket['url'] ?? ''));
                                $userId = isset($ticket['user_id']) ? (int)$ticket['user_id'] : null;
                                $ipAddress = htmlspecialchars((string)($ticket['ip'] ?? ''));
                                $userAgent = trim((string)($ticket['user_agent'] ?? ''));
                                $userAgentSnippet = $truncate($userAgent, 80);
                            ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 align-top">
                                    <div class="text-sm font-semibold text-gray-900">#<?= $ticketId ?></div>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium <?= $statusClass ?>">
                                            <?= htmlspecialchars($statusLabel) ?>
                                        </span>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium <?= $severityClass ?>">
                                            <?= htmlspecialchars($severityLabel) ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($typeLabel) ?></p>
                                    <p class="mt-1 text-sm text-gray-600"><?= htmlspecialchars($snippet) ?></p>
                                    <?php if ($fullDescription !== ''): ?>
                                        <details class="mt-2 text-sm text-gray-600">
                                            <summary class="cursor-pointer text-copihue-600 hover:text-copihue-700 font-medium">Ver detalle completo</summary>
                                            <div class="mt-2 whitespace-pre-wrap leading-relaxed"><?= $fullDescription ?></div>
                                        </details>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 align-top text-sm text-gray-700">
                                    <p class="font-semibold text-gray-900"><?= $contactName ?></p>
                                    <p><?= $contactEmail ?></p>
                                    <?php if ($userId): ?>
                                        <p class="text-xs text-gray-500 mt-1">Usuario ID: <?= $userId ?></p>
                                    <?php endif; ?>
                                    <?php if ($ipAddress !== ''): ?>
                                        <p class="text-xs text-gray-400 mt-1">IP: <?= $ipAddress ?></p>
                                    <?php endif; ?>
                                    <?php if ($userAgentSnippet !== '—'): ?>
                                        <p class="text-xs text-gray-400 mt-1">UA: <?= htmlspecialchars($userAgentSnippet) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 align-top text-sm">
                                    <?php if ($contactUrl !== ''): ?>
                                        <a
                                            href="<?= htmlspecialchars($contactUrl) ?>"
                                            target="_blank"
                                            rel="noopener"
                                            class="inline-flex items-center text-copihue-600 hover:text-copihue-700">
                                            Abrir enlace
                                            <svg class="ml-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7m0 0v7m0-7L10 14m-4 7h7" />
                                            </svg>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400">Sin enlace</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 align-top text-sm text-gray-700">
                                    <time datetime="<?= htmlspecialchars((string)($ticket['created_at'] ?? '')) ?>">
                                        <?= htmlspecialchars($createdAtDisplay) ?>
                                    </time>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php $content = ob_get_clean(); ?>

<?php include __DIR__ . '/../layouts/admin.php'; ?>
