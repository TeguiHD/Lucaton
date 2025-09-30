<?php
$page_title = $page_title ?? 'Gestión de noticias';
$search = $search ?? ($_GET['q'] ?? '');
$status = $status ?? ($_GET['status'] ?? '');
ob_start();
?>
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Panel de noticias</h2>
            <p class="text-sm text-gray-500">Publica actualizaciones, comunicados y notas de impacto para la comunidad.</p>
        </div>
        <a href="<?= Router::url('admin/news/create') ?>" class="inline-flex items-center gap-2 rounded-full bg-copihue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-copihue-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nueva noticia
        </a>
    </div>

    <div class="card">
        <div class="card-body space-y-4">
            <form method="GET" class="grid gap-4 md:grid-cols-[2fr_1fr_auto] md:items-end">
                <div>
                    <label for="search" class="form-label">Buscar por título o contenido</label>
                    <input type="text" id="search" name="q" value="<?= htmlspecialchars($search) ?>" class="form-input" placeholder="Buscar noticia...">
                </div>
                <div>
                    <label for="status" class="form-label">Estado</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Borradores</option>
                        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Publicadas</option>
                        <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archivadas</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary flex-1 md:flex-none">Filtrar</button>
                    <a href="<?= Router::url('admin/news') ?>" class="btn-secondary flex-1 md:flex-none">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0 overflow-hidden">
            <?php if (!empty($articles)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Título</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Categoría</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Publicado</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($articles as $item): ?>
                                <tr>
                                    <td class="px-4 py-4">
                                        <div class="space-y-1">
                                            <p class="text-sm font-semibold text-gray-900 line-clamp-2"><?= htmlspecialchars($item['title']) ?></p>
                                            <p class="text-xs text-gray-500"><?= htmlspecialchars($item['summary'] ?? '') ?></p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-600">
                                        <?= $item['category_name'] ? htmlspecialchars($item['category_name']) : '<span class="text-gray-400">Sin categoría</span>' ?>
                                    </td>
                                    <td class="px-4 py-4">
                                        <?php
                                            $statusLabel = match($item['status']) {
                                                'published' => ['Publicado', 'bg-emerald-100 text-emerald-700'],
                                                'archived' => ['Archivado', 'bg-gray-200 text-gray-700'],
                                                default => ['Borrador', 'bg-yellow-100 text-yellow-700']
                                            };
                                        ?>
                                        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold <?= $statusLabel[1] ?>">
                                            <span class="h-2 w-2 rounded-full bg-current"></span>
                                            <?= $statusLabel[0] ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-600">
                                        <?php if (!empty($item['published_at'])): ?>
                                            <?= date('d/m/Y H:i', strtotime($item['published_at'])) ?>
                                        <?php else: ?>
                                            <span class="text-gray-400">No publicada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4 text-right text-sm">
                                        <div class="flex justify-end gap-2">
                                            <a href="<?= Router::url('noticias/' . $item['slug']) ?>" target="_blank" class="inline-flex items-center gap-1 rounded-full bg-white border border-gray-200 px-3 py-1 text-xs font-semibold text-gray-600 hover:border-copihue-400 hover:text-copihue-600">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7m0 0v7m0-7L10 14"/></svg>
                                                Ver
                                            </a>
                                            <a href="<?= Router::url('admin/news/' . $item['id'] . '/edit') ?>" class="inline-flex items-center gap-1 rounded-full bg-copihue-600 px-3 py-1 text-xs font-semibold text-white hover:bg-copihue-700">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                Editar
                                            </a>
                                            <form method="POST" action="<?= Router::url('admin/news/' . $item['id'] . '/delete') ?>" onsubmit="return confirm('¿Seguro deseas eliminar esta noticia?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-full bg-danger-100 px-3 py-1 text-xs font-semibold text-danger-700 hover:bg-danger-200">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="p-4 border-t border-gray-100">
                        <div class="flex items-center justify-between text-sm text-gray-500">
                            <p>Mostrando página <?= $pagination['current_page'] ?> de <?= $pagination['total_pages'] ?> (<?= $pagination['total'] ?> noticias)</p>
                            <div class="flex gap-2">
                                <?php if ($pagination['current_page'] > 1): ?>
                                    <a href="<?= Router::url('admin/news', array_merge($_GET, ['page' => $pagination['current_page'] - 1])) ?>" class="btn-secondary text-xs">Anterior</a>
                                <?php endif; ?>
                                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                    <a href="<?= Router::url('admin/news', array_merge($_GET, ['page' => $pagination['current_page'] + 1])) ?>" class="btn-secondary text-xs">Siguiente</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="p-8 text-center text-gray-500">
                    <p class="text-base font-medium">Aún no hay noticias publicadas.</p>
                    <p class="text-sm mt-2">Crea una nueva nota para mantener informada a la comunidad.</p>
                    <a href="<?= Router::url('admin/news/create') ?>" class="mt-4 inline-flex items-center gap-2 rounded-full bg-copihue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-copihue-700">
                        Crear mi primera noticia
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include VIEWS_PATH . '/layouts/admin.php';
?>
