<?php
require_once __DIR__ . '/../components/forms.php';
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/alerts.php';
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$page_title = $page_title ?? 'Notificaciones';
$current_page = $current_page ?? 'admin-notifications';
$notifications = $notifications ?? [];
$availableUsers = $availableUsers ?? [];
$availableNews = $availableNews ?? [];
$old = $old ?? [];

$typeBadges = [
    'info' => 'bg-blue-100 text-blue-700',
    'success' => 'bg-green-100 text-green-700',
    'warning' => 'bg-yellow-100 text-yellow-700',
    'error' => 'bg-red-100 text-red-700',
    'system' => 'bg-purple-100 text-purple-700'
];

$newsById = [];
foreach ($availableNews as $article) {
    $newsById[(int)($article['id'] ?? 0)] = $article;
}
$selectedNews = null;
if (!empty($old['news_article_id'])) {
    $selectedNews = $newsById[(int)$old['news_article_id']] ?? null;
}

$previewTitle = trim($old['title'] ?? '') !== '' ? trim($old['title']) : 'Título de notificación';
$previewMessage = trim($old['message'] ?? '') !== ''
    ? nl2br(htmlspecialchars(trim($old['message']), ENT_QUOTES, 'UTF-8'))
    : 'El mensaje aparece aquí tal como llegará a la audiencia.';
$previewAudience = ($old['audience'] ?? 'all') === 'users'
    ? 'Usuarios seleccionados'
    : 'Todos los usuarios';
$previewBadge = strtoupper($old['type'] ?? 'info');
$selectedType = $old['type'] ?? 'info';
$selectedNewsTitle = $selectedNews['title'] ?? null;
$selectedNewsUrl = $selectedNews ? Router::url('noticias/' . ($selectedNews['slug'] ?? '')) : null;

$totalSent = count($notifications);
$pendingUnread = 0;
foreach ($notifications as $item) {
    $pendingUnread += (int)($item['unread'] ?? 0);
}
$lastSentAt = isset($notifications[0]['created_at']) ? date('d/m/Y H:i', strtotime($notifications[0]['created_at'])) : '—';
?>

<?php ob_start(); ?>
<div class="space-y-8">
    <section class="rounded-3xl border border-gray-100 bg-white p-6 shadow-soft">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Centro de notificaciones</h1>
                <p class="text-sm text-gray-500">Mensaje corto, audiencia clara, un enlace máximo.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="button" data-notification-action="refresh" class="inline-flex items-center gap-2 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700 hover:border-copihue-200 hover:text-copihue-700">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M20 4l-5.5 5.5M4 20l5.5-5.5" /></svg>
                    Actualizar
                </button>
                <a href="<?= Router::url('admin/notificaciones/historial') ?>" class="inline-flex items-center gap-2 rounded-2xl border border-copihue-200 bg-copihue-50 px-4 py-2 text-sm font-medium text-copihue-700 hover:bg-copihue-100">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .843-3 1.884v4.232C9 15.157 10.343 16 12 16s3-.843 3-1.884V9.884C15 8.843 13.657 8 12 8zm0-3v3m0 8v3" /></svg>
                    Historial
                </a>
            </div>
        </div>
        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4 text-center">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Envíos</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900"><?= number_format($totalSent) ?></p>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4 text-center">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Sin leer</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900"><?= number_format($pendingUnread) ?></p>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4 text-center">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Último envío</p>
                <p class="mt-2 text-lg font-semibold text-gray-900"><?= htmlspecialchars($lastSentAt) ?></p>
            </div>
        </div>
    </section>

    <?php include_flash_messages(); ?>

    <div class="grid gap-6 lg:grid-cols-1 xl:grid-cols-[2.2fr,1fr]">
        <section class="rounded-3xl border border-gray-100 bg-white p-6 shadow-soft">
            <form method="POST" action="<?= Router::url('admin/notificaciones') ?>" class="space-y-6" id="notification-form">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="lg:col-span-2 space-y-4">
                        <div class="flex flex-col gap-4 lg:flex-row">
                            <div class="flex-1">
                                <?= render_text_input([
                                    'label' => 'Título',
                                    'name' => 'title',
                                    'required' => true,
                                    'placeholder' => 'Ej: Mantenimiento mañana 09:00',
                                    'value' => htmlspecialchars($old['title'] ?? '')
                                ]); ?>
                            </div>
                            <div class="lg:w-44">
                                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Etiqueta</label>
                                <select id="type" name="type" class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-copihue-500 focus:ring-copihue-500 sm:text-sm">
                                    <?php
                                    $types = [
                                        'info' => 'Info',
                                        'success' => 'Éxito',
                                        'warning' => 'Alerta',
                                        'error' => 'Error',
                                        'system' => 'Sistema'
                                    ];
                                    foreach ($types as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= $selectedType === $value ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <?= render_textarea([
                            'label' => 'Mensaje',
                            'name' => 'message',
                            'required' => true,
                            'rows' => 4,
                            'placeholder' => 'En dos líneas: qué cambia y qué debe hacer la audiencia.',
                            'value' => htmlspecialchars($old['message'] ?? '')
                        ]); ?>
                    </div>

                    <div class="space-y-4">
                        <label class="flex items-center gap-3 rounded-2xl border border-gray-200 px-4 py-3 hover:border-copihue-200">
                            <input type="radio" name="audience" value="all" class="h-4 w-4 text-copihue-600 focus:ring-copihue-500" <?= ($old['audience'] ?? 'all') === 'all' ? 'checked' : '' ?>>
                            <span class="text-sm text-gray-700">Todos los usuarios</span>
                        </label>
                        <label class="flex items-center gap-3 rounded-2xl border border-gray-200 px-4 py-3 hover:border-copihue-200">
                            <input type="radio" name="audience" value="users" class="h-4 w-4 text-copihue-600 focus:ring-copihue-500" <?= ($old['audience'] ?? 'all') === 'users' ? 'checked' : '' ?>>
                            <span class="text-sm text-gray-700">Solo seleccionados</span>
                        </label>
                        <div>
                            <label for="user_ids" class="block text-sm font-medium text-gray-700 mb-1">Usuarios</label>
                            <select id="user_ids" name="user_ids[]" class="form-multiselect block w-full rounded-md border-gray-300 shadow-sm focus:border-copihue-500 focus:ring-copihue-500 sm:text-sm" multiple size="6">
                                <?php foreach ($availableUsers as $user): ?>
                                    <?php $displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?>
                                    <option value="<?= $user['id'] ?>" <?= in_array($user['id'], array_map('intval', $old['user_ids'] ?? []), true) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($displayName !== '' ? $displayName : ($user['username'] ?? $user['email'])) ?> (<?= htmlspecialchars($user['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <label for="news_article_id" class="block text-sm font-medium text-gray-700">Añadir enlace</label>
                        <select id="news_article_id" name="news_article_id" class="form-select block w-full rounded-md border-gray-300 shadow-sm focus:border-copihue-500 focus:ring-copihue-500 sm:text-sm">
                            <option value="">Sin enlace</option>
                            <?php foreach ($availableNews as $article): ?>
                                <?php
                                    $articleId = (int)($article['id'] ?? 0);
                                    $isSelected = (int)($old['news_article_id'] ?? 0) === $articleId;
                                    $newsUrl = Router::url('noticias/' . ($article['slug'] ?? ''));
                                    $publishedLabel = !empty($article['published_at']) ? date('d/m/Y H:i', strtotime($article['published_at'])) : 'Sin fecha';
                                ?>
                                <option
                                    value="<?= $articleId ?>"
                                    <?= $isSelected ? 'selected' : '' ?>
                                    data-title="<?= htmlspecialchars($article['title'] ?? '') ?>"
                                    data-url="<?= htmlspecialchars($newsUrl) ?>"
                                    data-published="<?= htmlspecialchars($publishedLabel) ?>">
                                    <?= htmlspecialchars($article['title'] ?? 'Noticia sin título') ?> · <?= htmlspecialchars($publishedLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="rounded-2xl bg-gray-50 p-4 text-xs text-gray-600">
                            Añade un enlace solo si complementa el mensaje.
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <a href="<?= Router::url('admin/notificaciones/historial') ?>" class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:border-copihue-200 hover:text-copihue-700">
                        Historial
                    </a>
                    <?= render_button([
                        'text' => 'Enviar',
                        'type' => 'primary',
                        'form_type' => 'submit'
                    ]); ?>
                </div>
            </form>
        </section>

        <aside class="flex flex-col gap-6">
            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-soft">
                <header class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Vista previa</p>
                        <h2 class="text-lg font-semibold text-gray-900">Instantánea</h2>
                    </div>
                    <span data-preview-type class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?= $typeBadges[$selectedType] ?? 'bg-gray-100 text-gray-700' ?>">
                        <?= $previewBadge ?>
                    </span>
                </header>
                <div class="mt-4 space-y-3">
                    <h3 data-preview-title class="text-base font-semibold text-gray-900 leading-tight"><?= htmlspecialchars($previewTitle) ?></h3>
                    <div data-preview-message class="rounded-2xl bg-gray-50 p-4 text-sm text-gray-700 leading-relaxed"><?= $previewMessage ?></div>
                    <div data-preview-news class="<?= $selectedNewsTitle ? '' : 'hidden' ?>">
                        <?php if ($selectedNewsTitle): ?>
                            <a href="<?= htmlspecialchars($selectedNewsUrl ?? '#') ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-xs font-medium text-copihue-600 hover:text-copihue-700">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h6m0 0v6m0-6L10 16m2 4h-8a2 2 0 01-2-2V6a2 2 0 012-2h8" />
                                </svg>
                                <span data-preview-news-label><?= htmlspecialchars($selectedNewsTitle) ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between rounded-xl bg-gray-50 px-4 py-2 text-xs text-gray-500">
                    <span data-preview-audience><?= htmlspecialchars($previewAudience) ?></span>
                    <span data-preview-meta><?= date('d/m/Y H:i') ?></span>
                </div>
            </div>

            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-soft">
                <h3 class="text-sm font-semibold text-gray-900">Recordatorios</h3>
                <ul class="mt-3 space-y-2 text-sm text-gray-600">
                    <li class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-copihue-500"></span>2 frases máximo.</li>
                    <li class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-copihue-500"></span>Un CTA claro.</li>
                    <li class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-copihue-500"></span>Evita enviar duplicados.</li>
                </ul>
            </div>

            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-soft">
                <h3 class="text-sm font-semibold text-gray-900">Checklist express</h3>
                <ul class="mt-3 space-y-2 text-sm text-gray-600">
                    <li class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-copihue-500"></span>¿Título describe el cambio?</li>
                    <li class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-copihue-500"></span>¿Audiencia mínima necesaria?</li>
                    <li class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-copihue-500"></span>¿Mensaje legible en móvil?</li>
                </ul>
            </div>
        </aside>
    </div>

    <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-soft">
        <header class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Últimos envíos</h2>
            <a href="<?= Router::url('admin/notificaciones/historial') ?>" class="text-xs font-semibold text-copihue-600 hover:text-copihue-700">Ver todo</a>
        </header>
        <?php if (empty($notifications)): ?>
            <p class="mt-4 text-sm text-gray-500">Aún no se han enviado notificaciones.</p>
        <?php else: ?>
            <ul class="mt-4 grid gap-4 sm:grid-cols-2">
                <?php foreach ($notifications as $notification): ?>
                    <?php $badgeClass = $typeBadges[$notification['type']] ?? 'bg-gray-100 text-gray-700'; ?>
                    <li class="rounded-2xl border border-gray-100 p-4">
                        <p class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                            <?= htmlspecialchars($notification['title']) ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $badgeClass ?>"><?= strtoupper($notification['type']) ?></span>
                        </p>
                        <p class="mt-1 text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?> · <?= (int)$notification['recipients'] ?> destinatarios</p>
                        <?php if (!empty($notification['meta']['news_article_id'])): ?>
                            <span class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-copihue-600">⚑ Noticia vinculada</span>
                        <?php endif; ?>
                        <?php if (!empty($notification['meta']['milestone'])): ?>
                            <span class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-copihue-600">★ Hito automático</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('notification-form');
    if (!form) {
        return;
    }

    var radios = form.querySelectorAll('input[name="audience"]');
    var selectUsers = document.getElementById('user_ids');
    var selectNews = document.getElementById('news_article_id');
    var titleInput = form.querySelector('input[name="title"]');
    var messageInput = form.querySelector('textarea[name="message"]');
    var typeSelect = document.getElementById('type');

    var preview = {
        title: document.querySelector('[data-preview-title]'),
        message: document.querySelector('[data-preview-message]'),
        type: document.querySelector('[data-preview-type]'),
        audience: document.querySelector('[data-preview-audience]'),
        news: document.querySelector('[data-preview-news]'),
        newsLabel: document.querySelector('[data-preview-news-label]'),
        meta: document.querySelector('[data-preview-meta]')
    };

    function syncAudience() {
        if (!selectUsers) return;
        var usersOption = form.querySelector('input[name="audience"][value="users"]');
        var enabled = usersOption && usersOption.checked;
        selectUsers.disabled = !enabled;
        if (enabled) {
            selectUsers.classList.remove('bg-gray-100', 'cursor-not-allowed');
        } else {
            selectUsers.classList.add('bg-gray-100', 'cursor-not-allowed');
            selectUsers.selectedIndex = -1;
        }
        updateAudiencePreview();
    }

    function updateAudiencePreview() {
        if (!preview.audience) return;
        var selectedAudience = form.querySelector('input[name="audience"]:checked');
        if (!selectedAudience) return;
        if (selectedAudience.value === 'users') {
            var selectedCount = 0;
            if (selectUsers) {
                selectedCount = Array.from(selectUsers.selectedOptions || []).length;
            }
            preview.audience.textContent = selectedCount > 0
                ? 'Usuarios específicos (' + selectedCount + ')'
                : 'Usuarios específicos';
        } else {
            preview.audience.textContent = 'Todos los usuarios';
        }
    }

    function updateTypePreview() {
        if (!preview.type || !typeSelect) return;
        var badgeMap = {
            info: 'bg-blue-100 text-blue-700',
            success: 'bg-green-100 text-green-700',
            warning: 'bg-yellow-100 text-yellow-700',
            error: 'bg-red-100 text-red-700',
            system: 'bg-purple-100 text-purple-700'
        };
        preview.type.textContent = typeSelect.value.toUpperCase();
        preview.type.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ' + (badgeMap[typeSelect.value] || 'bg-gray-100 text-gray-700');
    }

    function updateMessagePreview() {
        if (!preview.title || !preview.message) return;
        var title = titleInput && titleInput.value.trim() !== '' ? titleInput.value.trim() : 'Título de notificación';
        preview.title.textContent = title;

        if (messageInput) {
            var message = messageInput.value.trim();
            preview.message.innerHTML = message !== ''
                ? message.replace(/\n/g, '<br>')
                : 'El mensaje aparece aquí tal como llegará a la audiencia.';
        }
    }

    function updateNewsPreview() {
        if (!preview.news || !selectNews) return;
        var option = selectNews.options[selectNews.selectedIndex];
        if (option && option.value) {
            var label = option.getAttribute('data-title') || option.textContent;
            var url = option.getAttribute('data-url') || '#';
            var link = preview.news.querySelector('a');
            if (link) {
                link.textContent = label;
                link.href = url;
                link.removeAttribute('hidden');
            }
            preview.news.classList.remove('hidden');
        } else {
            var linkHidden = preview.news.querySelector('a');
            if (linkHidden) {
                linkHidden.setAttribute('hidden', 'hidden');
            }
            preview.news.classList.add('hidden');
        }
    }

    if (radios.length && selectUsers) {
        radios.forEach(function (radio) {
            radio.addEventListener('change', syncAudience);
        });
        selectUsers.addEventListener('change', updateAudiencePreview);
        syncAudience();
    }

    if (titleInput) {
        titleInput.addEventListener('input', updateMessagePreview);
    }
    if (messageInput) {
        messageInput.addEventListener('input', updateMessagePreview);
    }
    if (typeSelect) {
        typeSelect.addEventListener('change', updateTypePreview);
        updateTypePreview();
    }
    if (selectNews) {
        selectNews.addEventListener('change', updateNewsPreview);
        updateNewsPreview();
    }

    updateAudiencePreview();
    updateMessagePreview();

    if (preview.meta) {
        preview.meta.textContent = new Date().toLocaleString('es-CL');
    }
});
</script>
<?php
$content = ob_get_clean();
include VIEWS_PATH . '/layouts/admin.php';
?>
