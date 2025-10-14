<?php
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$page_title = $page_title ?? 'Auditoría';
$current_page = $current_page ?? 'admin-audit';
$auditEvents = $auditEvents ?? [];
$auditSummary = $auditSummary ?? [];
$auditFilters = $auditFilters ?? ['accion' => '', 'usuario' => null, 'rol' => '', 'ip' => '', 'limite' => 50];
$auditUsers = $auditUsers ?? [];
$auditLimit = $auditLimit ?? 50;

$supported = (bool)($auditSummary['supported'] ?? false);
$showCompatibilityNotice = !$supported && (int)($auditSummary['total'] ?? 0) === 0;
$totalEventos = (int)($auditSummary['total'] ?? 0);
$eventos24h = (int)($auditSummary['last_24h'] ?? 0);
$adminEventos = (int)($auditSummary['admin_actions'] ?? 0);
$usuariosUnicos = (int)($auditSummary['distinct_users'] ?? 0);
$topActions = $auditSummary['top_actions'] ?? [];

$roleOptions = [
    '' => 'Todos los roles',
    'admin' => 'Administradores',
    'superadmin' => 'Super administradores',
    'user' => 'Usuarios',
];
?>

<?php ob_start(); ?>
<div class="space-y-6">
    <?php include_flash_messages(); ?>

    <?php if ($showCompatibilityNotice): ?>
        <section class="bg-blue-50 border border-blue-100 text-blue-800 rounded-3xl p-5 flex items-start gap-3">
            <svg class="h-5 w-5 mt-0.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8 3a1 1 0 100 2 1 1 0 000-2zm.75-7.75a.75.75 0 00-1.5 0v5a.75.75 0 001.5 0v-5z" clip-rule="evenodd" />
            </svg>
            <div class="space-y-1 text-sm">
                <p class="font-semibold">Modo de compatibilidad de auditoría</p>
                <p>
                    Todavía no encontramos la tabla <code>audit_events</code>; mostraremos los eventos recientes desde los
                    archivos de log. Cuando ejecutes las migraciones podrás contar con historial persistente en base de datos.
                </p>
            </div>
        </section>
    <?php endif; ?>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Eventos totales</h3>
            <p class="mt-4 text-3xl font-semibold text-gray-900">
                <?= number_format($totalEventos, 0, ',', '.') ?>
            </p>
            <p class="mt-2 text-sm text-gray-500">
                <?= $supported ? 'Cantidad registrada en la base de datos.' : 'Total reunido desde los archivos de log.' ?>
            </p>
        </article>

        <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Últimas 24 horas</h3>
            <p class="mt-4 text-3xl font-semibold text-gray-900">
                <?= number_format($eventos24h, 0, ',', '.') ?>
            </p>
            <p class="mt-2 text-sm text-gray-500">Incluye cambios críticos y accesos administrativos.</p>
        </article>

        <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Acciones de administradores</h3>
            <p class="mt-4 text-3xl font-semibold text-gray-900">
                <?= number_format($adminEventos, 0, ',', '.') ?>
            </p>
            <p class="mt-2 text-sm text-gray-500">
                <?= $supported ? 'Filtra por rol para encontrar responsables específicos.' : 'Este dato se habilita cuando está disponible la tabla de auditoría.' ?>
            </p>
        </article>

        <article class="bg-white shadow-soft rounded-2xl p-6 border border-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Usuarios involucrados</h3>
            <p class="mt-4 text-3xl font-semibold text-gray-900">
                <?= number_format($usuariosUnicos, 0, ',', '.') ?>
            </p>
            <p class="mt-2 text-sm text-gray-500">Clientes y operadores registrados en la actividad reciente.</p>
        </article>
    </section>

    <?php if (!empty($topActions)): ?>
        <section class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900">Acciones más frecuentes</h2>
            <ul class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 text-sm text-gray-700">
                <?php foreach ($topActions as $entry): ?>
                    <li class="p-4 border border-gray-100 rounded-2xl bg-gray-50/60">
                        <p class="font-semibold text-gray-900"><?= htmlspecialchars($entry['action'] ?? '(sin etiqueta)') ?></p>
                        <p class="text-xs text-gray-500 mt-1"><?= number_format((int)($entry['total'] ?? 0), 0, ',', '.') ?> eventos</p>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6">
        <header class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Filtros de auditoría</h2>
                <p class="text-sm text-gray-500">Refina la búsqueda para investigar un incidente o validar permisos.</p>
            </div>
        </header>

        <form method="GET" action="<?= Router::url('admin/auditoria') ?>" class="grid grid-cols-1 lg:grid-cols-6 gap-4">
            <div class="lg:col-span-2">
                <label for="accion" class="block text-sm font-medium text-gray-700 mb-1">Acción</label>
                <input id="accion" name="accion" type="search" value="<?= htmlspecialchars($auditFilters['accion'] ?? '') ?>" placeholder="Ej: campaign.approved" class="form-input w-full rounded-lg border-gray-300 focus:border-copihue-500 focus:ring-copihue-500" />
            </div>

            <div class="lg:col-span-2">
                <label for="usuario" class="block text-sm font-medium text-gray-700 mb-1">Usuario</label>
                <select id="usuario" name="usuario" class="form-select w-full rounded-lg border-gray-300 focus:border-copihue-500 focus:ring-copihue-500">
                    <option value="">Todos</option>
                    <?php foreach ($auditUsers as $user): ?>
                        <option value="<?= (int)$user['id'] ?>" <?= (int)($auditFilters['usuario'] ?? 0) === (int)$user['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="rol" class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                <select id="rol" name="rol" class="form-select w-full rounded-lg border-gray-300 focus:border-copihue-500 focus:ring-copihue-500">
                    <?php foreach ($roleOptions as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($auditFilters['rol'] ?? '') === $value ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="ip" class="block text-sm font-medium text-gray-700 mb-1">Dirección IP</label>
                <input id="ip" name="ip" type="text" value="<?= htmlspecialchars($auditFilters['ip'] ?? '') ?>" placeholder="Ej: 192.168." class="form-input w-full rounded-lg border-gray-300 focus:border-copihue-500 focus:ring-copihue-500" />
            </div>

            <div>
                <label for="limite" class="block text-sm font-medium text-gray-700 mb-1">Registros</label>
                <select id="limite" name="limite" class="form-select w-full rounded-lg border-gray-300 focus:border-copihue-500 focus:ring-copihue-500">
                    <?php foreach ([10, 25, 50, 100, 200] as $option): ?>
                        <option value="<?= $option ?>" <?= (int)$auditLimit === $option ? 'selected' : '' ?>>
                            <?= $option ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lg:col-span-6 flex items-center gap-3">
                <button type="submit" class="btn-primary">Aplicar filtros</button>
                <a href="<?= Router::url('admin/auditoria') ?>" class="text-sm text-gray-500 hover:text-gray-700">Limpiar</a>
            </div>
        </form>
    </section>

    <section class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6">
        <header class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">
                Registros recientes <span class="text-sm text-gray-500">(<?= count($auditEvents) ?> elementos)</span>
            </h2>
            <div class="flex items-center gap-2 text-xs text-gray-500">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Fuente: BD</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-600">Fuente: Log</span>
            </div>
        </header>

        <?php if (empty($auditEvents)): ?>
            <p class="text-sm text-gray-500">No existen registros que coincidan con los filtros aplicados.</p>
        <?php else: ?>
            <ul class="space-y-4">
                <?php foreach ($auditEvents as $event): ?>
                    <?php
                    $source = $event['source'] ?? 'database';
                    $sourceClasses = $source === 'log'
                        ? 'bg-indigo-100 text-indigo-700'
                        : 'bg-gray-100 text-gray-700';
                    ?>
                    <li class="p-4 border border-gray-100 rounded-2xl hover:border-copihue-200 transition">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold <?= $sourceClasses ?>">
                                    <?= $source === 'log' ? 'Log diario' : 'Base de datos' ?>
                                </span>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900">
                                    <?= htmlspecialchars($event['action'] ?? '(sin acción)') ?>
                                </h3>
                                <p class="text-xs text-gray-500">
                                    <?= htmlspecialchars($event['entity_type'] ?? 'evento') ?>
                                    <?php if (!empty($event['entity_id'])): ?>
                                        · ID <?= (int)$event['entity_id'] ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="text-right text-xs text-gray-500">
                                <?php if (!empty($event['occurred_at'])): ?>
                                    <time datetime="<?= htmlspecialchars($event['occurred_at']) ?>">
                                        <?= date('d/m/Y H:i', strtotime($event['occurred_at'])) ?>
                                    </time>
                                <?php endif; ?>
                                <?php if (!empty($event['ip'])): ?>
                                    <p class="mt-1">IP: <?= htmlspecialchars($event['ip']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-gray-600">
                            <div>
                                <p class="font-semibold text-gray-500 uppercase tracking-wide">Usuario</p>
                                <p><?= htmlspecialchars($event['user_name'] ?? 'No identificado') ?></p>
                                <?php if (!empty($event['user_email'])): ?>
                                    <p><?= htmlspecialchars($event['user_email']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($event['role'])): ?>
                                    <span class="inline-flex items-center mt-2 px-2 py-0.5 rounded-full bg-copihue-100 text-copihue-700 font-medium">
                                        <?= htmlspecialchars($event['role']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($event['context'])): ?>
                                <div class="md:col-span-<?= !empty($event['metadata']) ? '1' : '2' ?>">
                                    <p class="font-semibold text-gray-500 uppercase tracking-wide">Contexto</p>
                                    <pre class="mt-1 text-[11px] bg-gray-50 border border-gray-100 rounded-xl p-3 overflow-x-auto"><?=
                                        htmlspecialchars(json_encode($event['context'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))
                                    ?></pre>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($event['metadata'])): ?>
                                <div class="md:col-span-1">
                                    <p class="font-semibold text-gray-500 uppercase tracking-wide">Metadatos</p>
                                    <pre class="mt-1 text-[11px] bg-gray-50 border border-gray-100 rounded-xl p-3 overflow-x-auto"><?=
                                        htmlspecialchars(json_encode($event['metadata'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))
                                    ?></pre>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($event['user_agent'])): ?>
                            <p class="mt-4 text-[11px] text-gray-400 break-words">
                                <span class="font-semibold text-gray-500 uppercase tracking-wide">User-Agent:</span>
                                <?= htmlspecialchars($event['user_agent']) ?>
                            </p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
<?php
$content = ob_get_clean();

include VIEWS_PATH . '/layouts/admin.php';
?>
