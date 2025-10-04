<?php
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$page_title = $page_title ?? 'Perfil de usuario';
$current_page = $current_page ?? 'admin-users';
$pending_campaigns_count = $pending_campaigns_count ?? 0;
$user = $user ?? [];
$campaignStats = $campaignStats ?? ['total' => 0, 'published' => 0, 'completed' => 0];
$donationStats = $donationStats ?? ['total' => 0, 'completed' => 0, 'sum' => 0.0];
$recentCampaigns = $recentCampaigns ?? [];
$recentDonations = $recentDonations ?? [];
$recentAuditEvents = $recentAuditEvents ?? [];
$isSuperAdmin = SessionHelper::isSuperAdmin();

$displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
if ($displayName === '') {
    $displayName = $user['email'] ?? 'Usuario';
}

$roleSlug = strtolower($user['role'] ?? 'user');
$roleName = $user['role_name'] ?? ucfirst($roleSlug);
$status = $user['status'] ?? 'active';
$email = $user['email'] ?? '';
$username = $user['username'] ?? '';
$createdAt = $user['created_at'] ?? null;
$lastLoginAt = $user['last_login_at'] ?? null;
$emailVerified = !empty($user['email_verified_at']);
$phone = $user['phone'] ?? '';
$location = $user['location'] ?? '';
$socialLinksRaw = $user['social_links'] ?? null;
$socialLinks = [];
if (is_string($socialLinksRaw) && $socialLinksRaw !== '') {
    $decoded = json_decode($socialLinksRaw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $socialLinks = array_filter($decoded, static function ($value) {
            return $value !== null && $value !== '';
        });
    }
} elseif (is_array($socialLinksRaw)) {
    $socialLinks = array_filter($socialLinksRaw, static function ($value) {
        return $value !== null && $value !== '';
    });
}
?>

<?php ob_start(); ?>
<div class="space-y-6">
    <?php include_flash_messages(); ?>

    <div class="bg-white shadow-soft rounded-3xl p-6 border border-gray-100">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
            <div class="flex items-start gap-4">
                <div class="h-16 w-16 rounded-full bg-copihue-100 flex items-center justify-center text-2xl font-semibold text-copihue-700">
                    <?= htmlspecialchars(mb_strtoupper(mb_substr($displayName, 0, 1, 'UTF-8'), 'UTF-8')) ?>
                </div>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900"><?= htmlspecialchars($displayName) ?></h1>
                    <p class="text-sm text-gray-500 mt-1">ID #<?= (int)($user['id'] ?? 0) ?></p>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <?php
                        switch ($roleSlug) {
                            case 'superadmin':
                                $badge = 'bg-amber-100 text-amber-700';
                                break;
                            case 'admin':
                                $badge = 'bg-violet-100 text-violet-700';
                                break;
                            default:
                                $badge = 'bg-gray-100 text-gray-600';
                                break;
                        }
                        ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= $badge ?>">Rol: <?= htmlspecialchars($roleName) ?></span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= $status === 'active' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                            Estado: <?= htmlspecialchars($status) ?>
                        </span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= $emailVerified ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500' ?>">
                            <?= $emailVerified ? 'Email verificado' : 'Email pendiente' ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php if ($isSuperAdmin && $roleSlug !== 'superadmin'): ?>
                <div class="flex gap-3">
                    <form method="POST" action="<?= Router::url('admin/usuarios/' . (int)$user['id'] . '/reset-password') ?>" onsubmit="return confirm('¿Restablecer la contraseña de <?= htmlspecialchars($displayName) ?>?');">
                        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-100">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-3.866 1.343-7 3-7m-3 7c0 3.866-1.343 7-3 7m3-7H3m9 0h9" /></svg>
                            Restablecer contraseña
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-2xl border border-gray-100 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Correo electrónico</dt>
                <dd class="mt-2 text-sm text-gray-900 break-all"><?= htmlspecialchars($email) ?></dd>
            </div>
            <div class="rounded-2xl border border-gray-100 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Usuario</dt>
                <dd class="mt-2 text-sm text-gray-900"><?= htmlspecialchars($username ?: '—') ?></dd>
            </div>
            <div class="rounded-2xl border border-gray-100 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Registrado</dt>
                <dd class="mt-2 text-sm text-gray-900">
                    <?= $createdAt ? date('d/m/Y H:i', strtotime($createdAt)) : '—' ?>
                </dd>
            </div>
            <div class="rounded-2xl border border-gray-100 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Último acceso</dt>
                <dd class="mt-2 text-sm text-gray-900">
                    <?= $lastLoginAt ? date('d/m/Y H:i', strtotime($lastLoginAt)) : 'Sin registro' ?>
                </dd>
            </div>
            <div class="rounded-2xl border border-gray-100 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Teléfono</dt>
                <dd class="mt-2 text-sm text-gray-900">
                    <?= $phone !== '' ? htmlspecialchars($phone) : '—' ?>
                </dd>
            </div>
            <div class="rounded-2xl border border-gray-100 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Ubicación</dt>
                <dd class="mt-2 text-sm text-gray-900">
                    <?= $location !== '' ? htmlspecialchars($location) : '—' ?>
                </dd>
            </div>
            <?php if (!empty($socialLinks)): ?>
                <div class="rounded-2xl border border-gray-100 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Perfiles sociales</dt>
                    <dd class="mt-2 text-sm text-gray-900 space-y-1">
                        <?php foreach ($socialLinks as $network => $handle): ?>
                            <?php
                            $label = is_string($network) && $network !== '' ? ucfirst($network) : 'Perfil';
                            $isUrl = is_string($handle) && filter_var($handle, FILTER_VALIDATE_URL);
                            ?>
                            <div>
                                <span class="text-xs font-semibold text-gray-500 mr-2"><?= htmlspecialchars($label) ?>:</span>
                                <?php if ($isUrl): ?>
                                    <a href="<?= htmlspecialchars($handle) ?>" target="_blank" rel="noopener" class="text-copihue-600 hover:text-copihue-700">
                                        <?= htmlspecialchars($handle) ?>
                                    </a>
                                <?php else: ?>
                                    <span><?= htmlspecialchars(is_scalar($handle) ? (string)$handle : json_encode($handle)) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </dd>
                </div>
            <?php endif; ?>
        </dl>
    </div>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <article class="bg-white shadow-soft rounded-3xl p-6 border border-gray-100 lg:col-span-2">
            <h2 class="text-lg font-semibold text-gray-900">Actividad recenté</h2>
            <p class="text-sm text-gray-500">Últimos eventos auditados asociados a esta cuenta.</p>
            <?php if (empty($recentAuditEvents)): ?>
                <p class="mt-6 text-sm text-gray-500">Todavía no registramos acciones relevantes para esta persona.</p>
            <?php else: ?>
                <ol class="mt-6 space-y-4">
                    <?php foreach ($recentAuditEvents as $event): ?>
                        <?php
                        $timeAgo = date('d/m/Y H:i', $event['timestamp']);
                        $scopeLabel = $event['scope'] === 'actor' ? 'Acción realizada' : 'Cambio recibido';
                        ?>
                        <li class="border-l-2 border-gray-200 pl-4">
                            <div class="flex items-center gap-2 text-xs uppercase tracking-wide text-gray-400">
                                <span><?= htmlspecialchars($scopeLabel) ?></span>
                                <span>•</span>
                                <span><?= $timeAgo ?></span>
                            </div>
                            <p class="mt-1 text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($event['action']) ?>
                            </p>
                            <?php if (!empty($event['details'])): ?>
                                <pre class="mt-2 text-xs text-gray-500 bg-gray-50 rounded-lg p-3 overflow-x-auto"><?= htmlspecialchars(json_encode($event['details'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></pre>
                            <?php endif; ?>
                            <?php if (!empty($event['ip'])): ?>
                                <p class="mt-1 text-xs text-gray-400">IP: <?= htmlspecialchars($event['ip']) ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </article>
        <aside class="space-y-4">
            <div class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6 space-y-3">
                <h3 class="text-sm font-semibold text-gray-900">Actividades</h3>
                <dl class="space-y-3 text-sm text-gray-700">
                    <div class="flex items-center justify-between">
                        <dt>Campañas totales</dt>
                        <dd class="font-semibold text-gray-900"><?= number_format($campaignStats['total'] ?? 0) ?></dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt>Campañas publicadas</dt>
                        <dd class="font-semibold text-gray-900"><?= number_format($campaignStats['published'] ?? 0) ?></dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt>Campañas completadas</dt>
                        <dd class="font-semibold text-gray-900"><?= number_format($campaignStats['completed'] ?? 0) ?></dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt>Donaciones registradas</dt>
                        <dd class="font-semibold text-gray-900"><?= number_format($donationStats['completed'] ?? $donationStats['total'] ?? 0) ?></dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt>Monto aportado</dt>
                        <dd class="font-semibold text-gray-900">CLP <?= number_format((float)($donationStats['sum'] ?? 0), 0, ',', '.') ?></dd>
                    </div>
                </dl>
            </div>
        </aside>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <article class="bg-white shadow-soft rounded-3xl p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Campañas recientes</h3>
                <span class="text-xs text-gray-400"><?= count($recentCampaigns) ?> items</span>
            </div>
            <?php if (empty($recentCampaigns)): ?>
                <p class="mt-4 text-sm text-gray-500">Sin campañas registradas.</p>
            <?php else: ?>
                <ul class="mt-4 space-y-3">
                    <?php foreach ($recentCampaigns as $campaign): ?>
                        <li class="border border-gray-100 rounded-2xl p-4">
                            <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($campaign['title'] ?? 'Campaña') ?></p>
                            <p class="text-xs text-gray-500 mt-1">Estado: <?= htmlspecialchars($campaign['status_label'] ?? ($campaign['status'] ?? 'desconocido')) ?></p>
                            <?php if (!empty($campaign['created_at'])): ?>
                                <p class="text-xs text-gray-400">Creada el <?= date('d/m/Y', strtotime($campaign['created_at'])) ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>

        <article class="bg-white shadow-soft rounded-3xl p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Donaciones recientes</h3>
                <span class="text-xs text-gray-400"><?= count($recentDonations) ?> items</span>
            </div>
            <?php if (empty($recentDonations)): ?>
                <p class="mt-4 text-sm text-gray-500">Todavía no registra donaciones con cuenta autenticada.</p>
            <?php else: ?>
                <ul class="mt-4 space-y-3">
                    <?php foreach ($recentDonations as $donation): ?>
                        <li class="border border-gray-100 rounded-2xl p-4">
                            <p class="text-sm text-gray-900">Campaña: <?= htmlspecialchars($donation['campaign_title'] ?? 'Campaña') ?></p>
                            <p class="text-xs text-gray-500 mt-1">Monto: <?= number_format((float)($donation['amount'] ?? 0), 0, ',', '.') ?> <?= htmlspecialchars($donation['currency'] ?? 'CLP') ?></p>
                            <?php if (!empty($donation['created_at'])): ?>
                                <p class="text-xs text-gray-400">Fecha: <?= date('d/m/Y H:i', strtotime($donation['created_at'])) ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>
    </section>
</div>
<?php
$content = ob_get_clean();
include VIEWS_PATH . '/layouts/admin.php';
?>
