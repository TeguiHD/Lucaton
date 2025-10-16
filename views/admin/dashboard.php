<?php
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$page_title = $page_title ?? 'Panel de Administración';
$current_page = $current_page ?? 'admin-dashboard';
$metrics = $metrics ?? [];
$reviewQueue = $reviewQueue ?? [];
$recentUsers = $recentUsers ?? [];
$recentNotifications = $recentNotifications ?? [];
$apiUsage = $metrics['api_usage'] ?? ['supported' => false];
$apiColumns = $apiUsage['columns'] ?? [];
$apiTotals = $apiUsage['totals'] ?? [];
$formatNumber = static function ($value, int $decimals = 0): string {
    if ($value === null) {
        return '—';
    }

    if ($decimals > 0) {
        return number_format((float)$value, $decimals, ',', '.');
    }

    return number_format((float)$value, 0, ',', '.');
};

$cards = [
    [
        'label' => 'Campañas en revisión',
        'value' => number_format($metrics['pending_campaigns'] ?? 0, 0, ',', '.'),
        'description' => 'Pendientes de moderación',
        'accent' => 'bg-amber-100 text-amber-700'
    ],
    [
        'label' => 'Campañas activas',
        'value' => number_format($metrics['active_campaigns'] ?? 0, 0, ',', '.'),
        'description' => 'Publicadas o en curso',
        'accent' => 'bg-emerald-100 text-emerald-700'
    ],
    [
        'label' => 'Total recaudado',
        'value' => '$' . number_format((float)($metrics['total_raised'] ?? 0), 0, ',', '.'),
        'description' => 'Donaciones completadas',
        'accent' => 'bg-copihue-100 text-copihue-700'
    ],
    [
        'label' => 'Usuarios registrados',
        'value' => number_format($metrics['total_users'] ?? 0, 0, ',', '.'),
        'description' => 'Perfil con correo verificado',
        'accent' => 'bg-marino-100 text-marino-700'
    ],
];

$secondaryCards = [
    [
        'label' => 'Solicitudes privadas',
        'value' => number_format($metrics['private_campaigns'] ?? 0, 0, ',', '.'),
        'description' => 'Visibilidad restringida',
    ],
    [
        'label' => 'Revisión entre pares',
        'value' => number_format($metrics['awaiting_peer_review'] ?? 0, 0, ',', '.'),
        'description' => 'Requiere segundo administrador',
    ],
    [
        'label' => 'Donaciones completadas',
        'value' => number_format($metrics['completed_donations'] ?? 0, 0, ',', '.'),
        'description' => 'Procesadas exitosamente',
    ],
    [
        'label' => 'Nuevos usuarios (30 días)',
        'value' => number_format($metrics['new_users_30_days'] ?? 0, 0, ',', '.'),
        'description' => 'Tendencia reciente',
    ],
];
?>

<?php ob_start(); ?>
<div class="space-y-8">
    <?php include_flash_messages(); ?>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <?php foreach ($cards as $card): ?>
            <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100 flex flex-col justify-between">
                <header class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">
                        <?= htmlspecialchars($card['label']) ?>
                    </h3>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $card['accent'] ?>">
                        <?= htmlspecialchars($card['description']) ?>
                    </span>
                </header>
                <p class="mt-4 text-3xl font-semibold text-gray-900">
                    <?= $card['value'] ?>
                </p>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <?php foreach ($secondaryCards as $card): ?>
            <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100">
                <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wide">
                    <?= htmlspecialchars($card['label']) ?>
                </h4>
                <p class="mt-4 text-2xl font-semibold text-gray-900">
                    <?= $card['value'] ?>
                </p>
                <p class="mt-1 text-sm text-gray-500">
                    <?= htmlspecialchars($card['description']) ?>
                </p>
            </article>
        <?php endforeach; ?>
    </section>

    <?php if (!empty($apiUsage['supported'])): ?>
        <section class="bg-white shadow-soft rounded-3xl p-6 border border-gray-100">
            <header class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Uso de APIs de IA</h2>
                    <p class="text-sm text-gray-500">Supervisa el consumo de Gemini, OpenRouter y otros conectores.</p>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm text-gray-600 md:text-right">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Total histórico</p>
                        <p class="text-base font-semibold text-gray-900"><?= $formatNumber($apiTotals['total'] ?? 0) ?></p>
                    </div>
                    <?php if (!empty($apiColumns['has_last_24h'])): ?>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">Últimas 24h</p>
                            <p class="text-base font-semibold text-gray-900"><?= $formatNumber($apiTotals['last_24h'] ?? 0) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($apiColumns['has_status'])): ?>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">Éxitos</p>
                            <p class="text-base font-semibold text-emerald-700"><?= $formatNumber($apiTotals['success'] ?? 0) ?></p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">Errores</p>
                            <p class="text-base font-semibold text-rose-600"><?= $formatNumber($apiTotals['failed'] ?? 0) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </header>

            <?php if (!empty($apiUsage['providers'])): ?>
                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <?php foreach ($apiUsage['providers'] as $provider): ?>
                        <article class="rounded-2xl border border-gray-100 p-5 shadow-soft">
                            <header class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-900">
                                    <?= htmlspecialchars($provider['label']) ?>
                                </h3>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?= htmlspecialchars($provider['accent'] ?? 'bg-gray-100 text-gray-700') ?>">
                                    <?= htmlspecialchars($provider['tag'] ?? strtoupper($provider['key'] ?? 'API')) ?>
                                </span>
                            </header>
                            <dl class="mt-4 grid grid-cols-2 gap-4 text-sm text-gray-600">
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-gray-500">Total</dt>
                                    <dd class="text-base font-semibold text-gray-900"><?= $formatNumber($provider['total'] ?? 0) ?></dd>
                                </div>
                                <?php if (!empty($apiColumns['has_last_24h'])): ?>
                                    <div>
                                        <dt class="text-xs uppercase tracking-wide text-gray-500">Últimas 24h</dt>
                                        <dd class="text-base font-semibold text-gray-900"><?= $formatNumber($provider['last_24h'] ?? 0) ?></dd>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($apiColumns['has_status'])): ?>
                                    <div>
                                        <dt class="text-xs uppercase tracking-wide text-gray-500">Éxitos</dt>
                                        <dd class="text-base font-semibold text-emerald-700"><?= $formatNumber($provider['success'] ?? 0) ?></dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs uppercase tracking-wide text-gray-500">Errores</dt>
                                        <dd class="text-base font-semibold text-rose-600"><?= $formatNumber($provider['failed'] ?? 0) ?></dd>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($apiColumns['has_latency']) && $provider['avg_latency'] !== null): ?>
                                    <div>
                                        <dt class="text-xs uppercase tracking-wide text-gray-500">Latencia prom. (ms)</dt>
                                        <dd class="text-base font-semibold text-gray-900"><?= $formatNumber($provider['avg_latency'], 1) ?></dd>
                                    </div>
                                <?php endif; ?>
                                <?php if ((!empty($apiColumns['has_tokens_input']) || !empty($apiColumns['has_tokens_output'])) && $provider['avg_tokens_total'] !== null): ?>
                                    <div>
                                        <dt class="text-xs uppercase tracking-wide text-gray-500">Tokens prom.</dt>
                                        <dd class="text-base font-semibold text-gray-900"><?= $formatNumber($provider['avg_tokens_total'], 0) ?></dd>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($apiColumns['has_cost']) && $provider['avg_cost'] !== null): ?>
                                    <div>
                                        <dt class="text-xs uppercase tracking-wide text-gray-500">Costo prom. (USD)</dt>
                                        <dd class="text-base font-semibold text-gray-900">$<?= $formatNumber($provider['avg_cost'], 4) ?></dd>
                                    </div>
                                <?php endif; ?>
                            </dl>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="mt-4 text-sm text-gray-600">Todavía no registramos llamadas a las APIs de IA.</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="bg-white shadow-soft rounded-3xl p-6 border border-gray-100">
            <header class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Campañas en revisión</h2>
                    <p class="text-sm text-gray-500">Prioriza aprobaciones y coordina con otros administradores.</p>
                </div>
                <a href="<?= Router::url('admin/campanas') ?>?filter=pending" class="text-sm font-medium text-copihue-600 hover:text-copihue-700">
                    Ver todas
                </a>
            </header>

            <?php if (empty($reviewQueue)): ?>
                <p class="text-sm text-gray-600">No hay campañas esperando moderación en este momento.</p>
            <?php else: ?>
                <ul class="space-y-4">
                    <?php foreach ($reviewQueue as $campaign): ?>
                        <?php $statusMeta = CampaignPresenter::statusMeta($campaign['status']); ?>
                        <li class="p-4 border border-gray-100 rounded-2xl hover:border-copihue-200 transition-colors">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="<?= Router::url('admin/campanas') ?>?search=<?= urlencode($campaign['title']) ?>" class="text-base font-semibold text-gray-900 hover:text-copihue-600">
                                            <?= htmlspecialchars($campaign['title']) ?>
                                        </a>
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600">ID #<?= (int)$campaign['id'] ?></span>
                                    </div>
                                    <div class="mt-1 flex flex-wrap items-center gap-3 text-sm text-gray-500">
                                        <span><?= htmlspecialchars($campaign['owner_name'] ?? 'Campañista') ?></span>
                                        <?php if (!empty($campaign['owner_role']) && $campaign['owner_role'] === 'admin'): ?>
                                            <span class="inline-flex items-center rounded-full bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-700">Admin</span>
                                        <?php endif; ?>
                                        <?php if (!empty($campaign['submitted_at'])): ?>
                                            <time datetime="<?= htmlspecialchars($campaign['submitted_at']) ?>">
                                                <?= date('d/m/Y H:i', strtotime($campaign['submitted_at'])) ?>
                                            </time>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $statusMeta['badge_class'] ?>">
                                    <?= htmlspecialchars($statusMeta['label']) ?>
                                </span>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-4 text-sm text-gray-600">
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-gray-500">Meta</p>
                                    <p>$<?= number_format($campaign['goal_amount'] ?? 0, 0, ',', '.') ?></p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-gray-500">Avance</p>
                                    <p><?= number_format($campaign['progress'] ?? 0, 0) ?>%</p>
                                </div>
                                <?php if (!empty($campaign['requires_peer_review'])): ?>
                                    <div class="col-span-2">
                                        <span class="inline-flex items-center gap-2 text-xs font-medium text-amber-700 bg-amber-100 rounded-full px-3 py-1">
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0V10a.75.75 0 00.356.64l2.5 1.5a.75.75 0 10.738-1.28l-2.094-1.256V6.75z" clip-rule="evenodd" />
                                            </svg>
                                            Requiere segunda aprobación de administrador
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="space-y-6">
            <div class="bg-white shadow-soft rounded-3xl p-6 border border-gray-100">
                <header class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Usuarios recientes</h2>
                        <p class="text-sm text-gray-500">Seguimiento de registros y roles asignados.</p>
                    </div>
                    <a href="<?= Router::url('admin/usuarios') ?>" class="text-sm font-medium text-copihue-600 hover:text-copihue-700">
                        Gestionar usuarios
                    </a>
                </header>

                <?php if (empty($recentUsers)): ?>
                    <p class="text-sm text-gray-600">Aún no hay usuarios registrados.</p>
                <?php else: ?>
                    <ul class="space-y-4">
                        <?php foreach ($recentUsers as $user): ?>
                            <li class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">
                                        <?= htmlspecialchars($user['name']) ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <?= htmlspecialchars($user['email']) ?>
                                    </p>
                                </div>
                                <div class="text-right text-xs text-gray-500">
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 uppercase">
                                        <?= htmlspecialchars($user['role']) ?>
                                    </span>
                                    <?php if (!empty($user['created_at'])): ?>
                                        <p class="mt-1">
                                            <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="bg-white shadow-soft rounded-3xl p-6 border border-gray-100">
                <header class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Notificaciones recientes</h2>
                        <p class="text-sm text-gray-500">Comunicación enviada a la comunidad.</p>
                    </div>
                    <a href="<?= Router::url('admin/notificaciones') ?>" class="text-sm font-medium text-copihue-600 hover:text-copihue-700">
                        Ver historial
                    </a>
                </header>

                <?php if (empty($recentNotifications)): ?>
                    <p class="text-sm text-gray-600">Aún no se han enviado notificaciones.</p>
                <?php else: ?>
                    <ul class="space-y-3">
                        <?php foreach ($recentNotifications as $notification): ?>
                            <li class="border border-gray-100 rounded-2xl px-4 py-3">
                                <div class="flex items-start justify-between">
                                    <p class="text-sm font-semibold text-gray-900">
                                        <?= htmlspecialchars($notification['title']) ?>
                                    </p>
                                    <span class="text-xs text-gray-400">
                                        <?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?>
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-gray-600">
                                    <?= htmlspecialchars($notification['message']) ?>
                                </p>
                                <div class="mt-2 flex items-center gap-3 text-xs text-gray-500">
                                    <span><?= strtoupper($notification['type']) ?></span>
                                    <span><?= ($notification['recipients'] ?? 0) ?> destinatarios</span>
                                    <?php if (!empty($notification['unread'])): ?>
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700">
                                            <?= (int)$notification['unread'] ?> sin leer
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
<?php
$content = ob_get_clean();
include VIEWS_PATH . '/layouts/admin.php';
?>
