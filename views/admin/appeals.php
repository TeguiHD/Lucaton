<?php
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$page_title = $page_title ?? 'Apelaciones de campañas';
$current_page = $current_page ?? 'admin-appeals';
$appeals = $appeals ?? [];
$pagination = $pagination ?? ['page' => 1, 'per_page' => 25, 'total_pages' => 1];
$filters = $filters ?? [];
$statusFilter = strtolower($filters['status'] ?? 'open');
$searchQuery = $filters['search'] ?? '';

$statusOptions = [
    'open' => 'Pendientes / En revisión',
    'pending' => 'Solo pendientes',
    'under_review' => 'En revisión interna',
    'approved' => 'Resueltas (aprobadas)',
    'rejected' => 'Resueltas (rechazadas)',
    'closed' => 'Cerradas manualmente',
    'all' => 'Todas las apelaciones',
];
?>

<?php ob_start(); ?>
<div class="space-y-6">
    <?php include_flash_messages(); ?>

    <section class="bg-white shadow-soft rounded-3xl p-6 border border-gray-100">
        <form method="GET" action="<?= Router::url('admin/apelaciones') ?>" class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select id="status" name="status" class="form-select block w-full rounded-lg border-gray-300 focus:border-copihue-500 focus:ring-copihue-500">
                    <?php foreach ($statusOptions as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $statusFilter === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <input id="search" name="search" type="search" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Título de campaña, responsable o texto de la apelación" class="form-input block w-full rounded-lg border-gray-300 focus:border-copihue-500 focus:ring-copihue-500" />
            </div>
            <div class="md:col-span-3 flex items-center gap-3">
                <button type="submit" class="btn-primary">Filtrar resultados</button>
                <a href="<?= Router::url('admin/apelaciones') ?>" class="text-sm text-gray-500 hover:text-gray-700">Limpiar</a>
            </div>
        </form>
    </section>

    <section class="bg-white shadow-soft rounded-3xl border border-gray-100">
        <header class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Casos de apelación</h2>
                <p class="text-sm text-gray-500">
                    Evalúa solicitudes de reactivación, revisa evidencia adjunta y registra la resolución final.
                </p>
            </div>
            <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-700">
                <?= number_format($pending_appeals_count ?? 0, 0, ',', '.') ?> en cola
            </span>
        </header>

        <?php if (empty($appeals)): ?>
            <div class="px-6 py-12 text-center text-sm text-gray-600">
                No hay apelaciones que coincidan con los criterios actuales.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Apelación</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaña</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responsable</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Adjuntos</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actualización</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($appeals as $appeal): ?>
                            <?php $statusMeta = CampaignAppeal::statusMeta($appeal['status'] ?? ''); ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold text-gray-900">#<?= (int)$appeal['id'] ?></span>
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold <?= htmlspecialchars($statusMeta['badge']) ?>">
                                                <?= htmlspecialchars($statusMeta['label']) ?>
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 max-w-xs">
                                            <?= htmlspecialchars(mb_strimwidth((string)($appeal['reason'] ?? ''), 0, 100, '…', 'UTF-8')) ?>
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1 text-sm text-gray-700">
                                        <span class="font-semibold text-gray-900"><?= htmlspecialchars($appeal['campaign_title'] ?? 'Campaña sin título') ?></span>
                                        <?php if (!empty($appeal['campaign_path'])): ?>
                                            <a href="<?= Router::url($appeal['campaign_path']) ?>" target="_blank" rel="noopener noreferrer" class="text-xs text-copihue-600 hover:text-copihue-700">
                                                Ver campaña
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <div class="flex flex-col">
                                        <span class="font-medium"><?= htmlspecialchars($appeal['requester_name'] ?? '—') ?></span>
                                        <?php if (!empty($appeal['requester_email'])): ?>
                                            <span class="text-xs text-gray-500"><?= htmlspecialchars($appeal['requester_email']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if (!empty($appeal['files_count'])): ?>
                                        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M7 7h10M7 12h10M7 17h10" />
                                            </svg>
                                            <?= (int)$appeal['files_count'] ?> documento<?= (int)$appeal['files_count'] === 1 ? '' : 's' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">Sin adjuntos</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <div class="flex flex-col">
                                        <span><?= !empty($appeal['updated_at']) ? date('d/m/Y H:i', strtotime($appeal['updated_at'])) : '—' ?></span>
                                        <?php if (!empty($appeal['reviewer_name'])): ?>
                                            <span class="text-xs text-gray-500">Revisor: <?= htmlspecialchars($appeal['reviewer_name']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <a href="<?= Router::url('admin/apelaciones/' . (int)$appeal['id']) ?>" class="inline-flex items-center gap-2 rounded-lg border border-copihue-500 bg-white px-3 py-2 text-xs font-semibold text-copihue-600 shadow-sm transition hover:bg-copihue-50 focus:outline-none focus:ring-2 focus:ring-copihue-500 focus:ring-offset-2">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 20l9-5-9-5-9 5 9 5z" />
                                            <path d="M12 12l9-5-9-5-9 5 9 5z" />
                                        </svg>
                                        Revisar caso
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4">
                <?= render_pagination([
                    'current_page' => $pagination['page'] ?? 1,
                    'total_pages' => $pagination['total_pages'] ?? 1,
                    'base_url' => Router::url('admin/apelaciones'),
                    'query_params' => [
                        'status' => $statusFilter,
                        'search' => $searchQuery,
                    ],
                ]) ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php
$content = ob_get_clean();

include VIEWS_PATH . '/layouts/admin.php';
?>
