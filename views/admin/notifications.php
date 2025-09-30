<?php
require_once __DIR__ . '/../components/forms.php';
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/alerts.php';
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$page_title = $page_title ?? 'Notificaciones';
$current_page = $current_page ?? 'admin-notifications';
$notifications = $notifications ?? [];
$availableUsers = $availableUsers ?? [];
$typeBadges = [
    'info' => 'bg-blue-100 text-blue-700',
    'success' => 'bg-green-100 text-green-700',
    'warning' => 'bg-yellow-100 text-yellow-700',
    'error' => 'bg-red-100 text-red-700',
    'system' => 'bg-purple-100 text-purple-700'
];
?>

<?php ob_start(); ?>
<div class="max-w-5xl mx-auto space-y-8">
    <section class="bg-white shadow-soft rounded-3xl p-6">
        <header class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Enviar notificación</h1>
                <p class="mt-1 text-sm text-gray-600">Comparte mensajes importantes con los usuarios de Lucatón.</p>
            </div>
        </header>

        <?php include_flash_messages(); ?>

        <form method="POST" action="<?= Router::url('admin/notificaciones') ?>" class="space-y-6">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <?php echo render_text_input([
                        'label' => 'Título',
                        'name' => 'title',
                        'required' => true,
                        'placeholder' => 'Ej: Actualización de mantenimiento',
                        'value' => htmlspecialchars($old['title'] ?? '')
                    ]); ?>
                </div>
                <div class="md:col-span-2">
                    <?php echo render_textarea([
                        'label' => 'Mensaje',
                        'name' => 'message',
                        'required' => true,
                        'rows' => 4,
                        'placeholder' => 'Describe claramente el mensaje que quieres compartir.',
                        'value' => htmlspecialchars($old['message'] ?? '')
                    ]); ?>
                </div>
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                    <select id="type" name="type" class="form-select block w-full rounded-md border-gray-300 shadow-sm focus:border-copihue-500 focus:ring-copihue-500 sm:text-sm">
                        <?php
                        $types = [
                            'info' => 'Información',
                            'success' => 'Éxito',
                            'warning' => 'Alerta',
                            'error' => 'Error',
                            'system' => 'Sistema'
                        ];
                        $selectedType = $old['type'] ?? 'info';
                        foreach ($types as $value => $label): ?>
                            <option value="<?= $value ?>" <?= $selectedType === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Destinatarios</label>
                    <div class="space-y-2">
                        <label class="inline-flex items-center">
                            <input type="radio" name="audience" value="all" class="form-radio text-copihue-600" <?= ($old['audience'] ?? 'all') === 'all' ? 'checked' : '' ?>>
                            <span class="ml-2 text-sm text-gray-700">Todos los usuarios</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="audience" value="users" class="form-radio text-copihue-600" <?= ($old['audience'] ?? 'all') === 'users' ? 'checked' : '' ?>>
                            <span class="ml-2 text-sm text-gray-700">Usuarios específicos</span>
                        </label>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label for="user_ids" class="block text-sm font-medium text-gray-700 mb-1">Seleccionar usuarios</label>
                    <select id="user_ids" name="user_ids[]" class="form-multiselect block w-full rounded-md border-gray-300 shadow-sm focus:border-copihue-500 focus:ring-copihue-500 sm:text-sm" multiple size="6">
                        <?php foreach ($availableUsers as $user): ?>
                            <?php $displayName = trim($user['first_name'] . ' ' . $user['last_name']); ?>
                            <option value="<?= $user['id'] ?>" <?= in_array($user['id'], array_map('intval', $old['user_ids'] ?? []), true) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($displayName !== '' ? $displayName : $user['email']) ?>
                                (<?= htmlspecialchars($user['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Mantén presionada la tecla Ctrl o Cmd para seleccionar múltiples usuarios.</p>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3">
                <?php echo render_button([
                    'text' => 'Enviar notificación',
                    'variant' => 'primary'
                ]); ?>
            </div>
        </form>
    </section>

    <section class="bg-white shadow-soft rounded-3xl p-6">
        <header class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900">Historial reciente</h2>
        </header>

        <?php if (empty($notifications)): ?>
            <p class="text-sm text-gray-600">Aún no se han enviado notificaciones.</p>
        <?php else: ?>
            <ul class="divide-y divide-gray-200">
                <?php foreach ($notifications as $notification): ?>
                    <li class="py-4 flex items-start justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">
                                <?= htmlspecialchars($notification['title']) ?>
                                <?php $badgeClass = $typeBadges[$notification['type']] ?? 'bg-gray-100 text-gray-700'; ?>
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $badgeClass ?>">
                                    <?= strtoupper($notification['type']) ?>
                                </span>
                            </p>
                            <p class="mt-1 text-sm text-gray-600"><?= nl2br(htmlspecialchars($notification['message'])) ?></p>
                            <p class="mt-1 text-xs text-gray-400">Enviada el <?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?> · Destinatarios: <?= $notification['recipients'] ?><?= $notification['unread'] > 0 ? ' · Sin leer: ' . $notification['unread'] : '' ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var radios = document.querySelectorAll('input[name="audience"]');
    var select = document.getElementById('user_ids');

    function syncAudience() {
        if (!select) return;
        var usersOption = document.querySelector('input[name="audience"][value="users"]');
        var enabled = usersOption && usersOption.checked;
        select.disabled = !enabled;
        if (enabled) {
            select.classList.remove('bg-gray-100', 'cursor-not-allowed');
        } else {
            select.classList.add('bg-gray-100', 'cursor-not-allowed');
            select.selectedIndex = -1;
        }
    }

    if (radios.length && select) {
        radios.forEach(function (radio) {
            radio.addEventListener('change', syncAudience);
        });
        syncAudience();
    }
});
</script>
<?php
$content = ob_get_clean();
include VIEWS_PATH . '/layouts/admin.php';
?>
