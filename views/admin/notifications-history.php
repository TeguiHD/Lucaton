<?php
require_once __DIR__ . '/../components/forms.php';
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/alerts.php';
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$page_title = $page_title ?? 'Historial de notificaciones';
$current_page = $current_page ?? 'admin-notifications-history';
$notifications = $notifications ?? [];
$pagination = $pagination ?? ['total' => 0, 'per_page' => 20, 'current_page' => 1, 'total_pages' => 1];
$activeFilters = $activeFilters ?? ['query' => '', 'type' => '', 'audience' => '', 'with_news' => false];

$typeBadges = [
    'info' => 'bg-blue-100 text-blue-700',
    'success' => 'bg-green-100 text-green-700',
    'warning' => 'bg-yellow-100 text-yellow-700',
    'error' => 'bg-red-100 text-red-700',
    'system' => 'bg-purple-100 text-purple-700'
];

function build_history_url(array $overrides = []): string {
    $baseUrl = Router::url('admin/notificaciones/historial');
    $params = array_filter(array_merge([
        'q' => $_GET['q'] ?? '',
        'type' => $_GET['type'] ?? '',
        'audience' => $_GET['audience'] ?? '',
        'with_news' => $_GET['with_news'] ?? ''
    ], $overrides), function ($value) {
        if (is_bool($value)) {
            return $value;
        }
        return $value !== '' && $value !== null;
    });

    if (empty($params)) {
        return $baseUrl;
    }

    return $baseUrl . '?' . http_build_query($params);
}
?>

<?php ob_start(); ?>
<div class="max-w-6xl mx-auto space-y-8">
    <section class="bg-white shadow-soft rounded-3xl p-6">
        <header class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Historial de notificaciones</h1>
            <p class="text-sm text-gray-600 mt-1">Consulta los mensajes enviados, filtra por tipo o audiencia y revisa qué campañas tienen enlaces a noticias.</p>
        </header>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="history-search">Buscar</label>
                <input
                    type="text"
                    id="history-search"
                    name="q"
                    value="<?= htmlspecialchars($activeFilters['query'] ?? '') ?>"
                    placeholder="Título o contenido"
                    class="form-input block w-full rounded-md border-gray-300 shadow-sm focus:border-copihue-500 focus:ring-copihue-500 sm:text-sm"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="history-type">Tipo</label>
                <select
                    id="history-type"
                    name="type"
                    class="form-select block w-full rounded-md border-gray-300 shadow-sm focus:border-copihue-500 focus:ring-copihue-500 sm:text-sm"
                >
                    <option value="">Todos</option>
                    <?php foreach (['info' => 'Información', 'success' => 'Éxito', 'warning' => 'Alerta', 'error' => 'Error', 'system' => 'Sistema'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($activeFilters['type'] ?? '') === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="history-audience">Audiencia</label>
                <select
                    id="history-audience"
                    name="audience"
                    class="form-select block w-full rounded-md border-gray-300 shadow-sm focus:border-copihue-500 focus:ring-copihue-500 sm:text-sm"
                >
                    <option value="">Todas</option>
                    <option value="all" <?= ($activeFilters['audience'] ?? '') === 'all' ? 'selected' : '' ?>>Toda la plataforma</option>
                    <option value="users" <?= ($activeFilters['audience'] ?? '') === 'users' ? 'selected' : '' ?>>Usuarios específicos</option>
                </select>
            </div>
            <div class="flex flex-col justify-end space-y-2">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="with_news" value="1" <?= !empty($activeFilters['with_news']) ? 'checked' : '' ?> class="rounded border-gray-300 text-copihue-600 focus:ring-copihue-500">
                    Solo con noticia
                </label>
                <div class="flex gap-2">
                    <a href="<?= Router::url('admin/notificaciones/historial') ?>" class="flex-1 inline-flex items-center justify-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:border-copihue-200 hover:text-copihue-700">Limpiar</a>
                    <button type="submit" class="flex-1 inline-flex items-center justify-center rounded-xl bg-copihue-600 px-4 py-2 text-sm font-medium text-white hover:bg-copihue-700">Aplicar</button>
                </div>
            </div>
        </form>
    </section>

    <section class="bg-white shadow-soft rounded-3xl p-6 space-y-6">
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900"><?= number_format($pagination['total'] ?? 0) ?> notificaciones registradas</h2>
                <?php if (!empty($activeFilters['query']) || !empty($activeFilters['type']) || !empty($activeFilters['audience']) || !empty($activeFilters['with_news'])): ?>
                    <p class="text-xs text-gray-500">Mostrando resultados con los filtros seleccionados.</p>
                <?php endif; ?>
            </div>
            <a href="<?= Router::url('admin/notificaciones') ?>" class="inline-flex items-center gap-2 text-sm font-medium text-copihue-600 hover:text-copihue-700">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Enviar nueva notificación
            </a>
        </header>

        <?php if (empty($notifications)): ?>
            <div class="rounded-2xl border border-dashed border-gray-200 p-10 text-center">
                <p class="text-sm text-gray-600">No encontramos notificaciones que coincidan con la búsqueda.</p>
            </div>
        <?php else: ?>
            <ul class="space-y-4">
                <?php foreach ($notifications as $notification): ?>
                    <?php
                        $badgeClass = $typeBadges[$notification['type']] ?? 'bg-gray-100 text-gray-700';
                        $messageExcerpt = strip_tags($notification['message']);
                        if (mb_strlen($messageExcerpt) > 220) {
                            $messageExcerpt = mb_substr($messageExcerpt, 0, 220) . '…';
                        }
                        $audienceLabel = $notification['audience'] === 'all' ? 'Toda la plataforma' : 'Usuarios específicos';
                        $creatorLabel = $notification['creator_name'] ?? 'Sistema';
                        $hasNews = !empty($notification['meta']['news_article_id']);
                        $milestone = $notification['meta']['milestone'] ?? null;
                    ?>
                    <li class="rounded-2xl border border-gray-100 p-5 hover:border-copihue-200 transition">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div class="space-y-2 md:max-w-3xl">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-base font-semibold text-gray-900"><?= htmlspecialchars($notification['title']) ?></h3>
                                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-semibold <?= $badgeClass ?>">
                                        <?= strtoupper($notification['type']) ?>
                                    </span>
                                    <?php if ($hasNews): ?>
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-copihue-600">
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h6m0 0v6m0-6L10 16" />
                                            </svg>
                                            Con noticia
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($milestone): ?>
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-copihue-600">
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8l3 6H9l3-6zm0 9v-2" />
                                            </svg>
                                            Hito: <?= htmlspecialchars(ucwords(str_replace('_', ' ', $milestone))) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($messageExcerpt)) ?></p>
                            </div>
                            <div class="flex flex-shrink-0 flex-col items-start gap-2 text-xs text-gray-500">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .843-3 1.884v4.232C9 15.157 10.343 16 12 16s3-.843 3-1.884V9.884C15 8.843 13.657 8 12 8z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10V7a2 2 0 012-2h10a2 2 0 012 2v3" />
                                    </svg>
                                    <?= htmlspecialchars($audienceLabel) ?>
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10" />
                                    </svg>
                                    <?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?>
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12A4 4 0 118 12a4 4 0 018 0z" />
                                    </svg>
                                    <?= (int)$notification['recipients'] ?> destinatarios
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A7.5 7.5 0 0112 5.5v0a7.5 7.5 0 016.879 12.304L12 21.5l-6.879-3.696z" />
                                    </svg>
                                    <?= htmlspecialchars($creatorLabel) ?>
                                </span>
                                <?php if ((int)$notification['unread'] > 0): ?>
                                    <span class="inline-flex items-center gap-1 text-copihue-600">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659" />
                                        </svg>
                                        <?= (int)$notification['unread'] ?> sin leer
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if (($pagination['total_pages'] ?? 1) > 1): ?>
                <nav class="flex items-center justify-between pt-6" aria-label="Paginación">
                    <?php $currentPage = $pagination['current_page'] ?? 1; ?>
                    <a
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:text-copihue-700 <?= $currentPage <= 1 ? 'pointer-events-none opacity-60' : '' ?>"
                        href="<?= $currentPage <= 1 ? '#' : build_history_url(['page' => $currentPage - 1]) ?>"
                    >
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Anterior
                    </a>
                    <div class="hidden sm:flex items-center gap-2 text-sm text-gray-600">
                        Página <span class="font-semibold text-gray-900"><?= $currentPage ?></span> de <span class="font-semibold text-gray-900"><?= $pagination['total_pages'] ?></span>
                    </div>
                    <a
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:text-copihue-700 <?= $currentPage >= ($pagination['total_pages'] ?? 1) ? 'pointer-events-none opacity-60' : '' ?>"
                        href="<?= $currentPage >= ($pagination['total_pages'] ?? 1) ? '#' : build_history_url(['page' => $currentPage + 1]) ?>"
                    >
                        Siguiente
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
<?php
$content = ob_get_clean();
include VIEWS_PATH . '/layouts/admin.php';
?>
