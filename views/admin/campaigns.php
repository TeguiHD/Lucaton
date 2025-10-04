<?php
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$page_title = $page_title ?? 'Moderación de Campañas';
$current_page = $current_page ?? 'admin-campaigns';
$campaigns = $campaigns ?? [];
$filters = $filters ?? [];
$selectedFilter = $filters['filter'] ?? 'pending';
$searchQuery = $filters['search'] ?? '';
?>

<?php ob_start(); ?>
<div class="space-y-6">
    <?php include_flash_messages(); ?>

    <section class="bg-white shadow-soft rounded-3xl p-6 border border-gray-100">
        <form method="GET" action="<?= Router::url('admin/campanas') ?>" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="filter" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select id="filter" name="filter" class="form-select block w-full rounded-lg border-gray-300 focus:border-copihue-500 focus:ring-copihue-500">
                    <?php
                    $options = [
                        'pending' => 'Pendientes de revisión',
                        'published' => 'Publicadas / activas',
                        'paused' => 'Pausadas',
                        'private' => 'Privadas',
                        'public' => 'Visibles públicamente',
                        'all' => 'Todas las campañas',
                    ];
                    foreach ($options as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $selectedFilter === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <input id="search" name="search" type="search" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Título, slug o responsable" class="form-input block w-full rounded-lg border-gray-300 focus:border-copihue-500 focus:ring-copihue-500" />
            </div>
            <div class="md:col-span-3 flex items-center gap-3">
                <button type="submit" class="btn-primary">Filtrar resultados</button>
                <a href="<?= Router::url('admin/campanas') ?>" class="text-sm text-gray-500 hover:text-gray-700">Limpiar</a>
            </div>
        </form>
    </section>

    <section class="bg-white shadow-soft rounded-3xl border border-gray-100">
        <header class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Campañas</h2>
                <p class="text-sm text-gray-500">
                    Gestiona aprobaciones, pausas y rechazos. Usa las acciones rápidas para mantener la coherencia editorial.
                </p>
            </div>
            <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-700">
                <?= number_format($pending_campaigns_count ?? 0, 0, ',', '.') ?> en revisión
            </span>
        </header>

        <?php if (empty($campaigns)): ?>
            <div class="px-6 py-12 text-center text-sm text-gray-600">
                No se encontraron campañas con los criterios seleccionados.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaña</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responsable</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progreso</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($campaigns as $campaign): ?>
                            <?php $statusMeta = CampaignPresenter::statusMeta($campaign['status']); ?>
                            <tr class="hover:bg-gray-50 transition-colors" :class="activeCampaignId === <?= (int)$campaign['id'] ?> ? 'bg-red-50 ring-2 ring-red-200' : ''">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                            <?= htmlspecialchars($campaign['title']) ?>
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600">ID #<?= (int)$campaign['id'] ?></span>
                                        </span>
                                        <?php if (!empty($campaign['slug'])): ?>
                                            <span class="text-xs text-gray-500">
                                                <a href="<?= Router::url('campana/' . $campaign['slug']) ?>" class="text-copihue-600 hover:text-copihue-700" target="_blank" rel="noopener">
                                                    Ver ficha pública
                                                </a>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($campaign['submitted_at'])): ?>
                                            <span class="text-xs text-gray-400 mt-1">
                                                Creada el <?= date('d/m/Y H:i', strtotime($campaign['submitted_at'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-700">
                                        <?= htmlspecialchars($campaign['owner_name'] ?? 'Campañista') ?>
                                        <?php if (!empty($campaign['owner_role']) && $campaign['owner_role'] === 'admin'): ?>
                                            <span class="ml-2 inline-flex items-center rounded-full bg-violet-100 px-2 py-0.5 text-[11px] font-medium text-violet-700">Admin</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($campaign['owner_email'])): ?>
                                        <div class="text-xs text-gray-500">
                                            <?= htmlspecialchars($campaign['owner_email']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $statusMeta['badge_class'] ?>">
                                        <?= htmlspecialchars($statusMeta['label']) ?>
                                    </span>
                                    <?php if (($campaign['visibility'] ?? '') === 'private'): ?>
                                        <span class="ml-2 inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                            Privada
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-700 font-semibold">
                                        <?= number_format($campaign['progress'] ?? 0, 0) ?>%
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        $<?= number_format($campaign['raised_amount'] ?? 0, 0, ',', '.') ?> de $<?= number_format($campaign['goal_amount'] ?? 0, 0, ',', '.') ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <div class="flex justify-end items-center gap-2">
                                        <a href="<?= Router::url('admin/campana/' . $campaign['id']) ?>" class="inline-flex items-center rounded-lg bg-copihue-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-copihue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500">
                                            Revisar y decidir
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </section>
</div>
<?php
$content = ob_get_clean();

include VIEWS_PATH . '/layouts/admin.php';
?>
