<?php
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$page_title = $page_title ?? 'Detalle de apelación';
$current_page = $current_page ?? 'admin-appeals';
$appeal = $appeal ?? [];
$campaign = $campaign ?? [];
$campaignView = $campaignView ?? [];
$statusMeta = CampaignAppeal::statusMeta($appeal['status'] ?? '');
$status = strtolower($appeal['status'] ?? '');
$resolutionDefault = match ($status) {
    'under_review' => 'under_review',
    'approved' => 'approved',
    'rejected' => 'rejected',
    'closed' => 'closed',
    default => 'approved',
};
$files = $appeal['files'] ?? [];
$csrfToken = SessionHelper::getCSRFToken();
?>

<?php ob_start(); ?>
<div class="space-y-6">
    <?php include_flash_messages(); ?>

    <section class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">
                    Apelación #<?= (int)($appeal['id'] ?? 0) ?>
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Recibida el <?= !empty($appeal['created_at']) ? date('d/m/Y H:i', strtotime($appeal['created_at'])) : '—' ?> por <?= htmlspecialchars($appeal['requester_name'] ?? 'Persona responsable') ?>
                </p>
            </div>
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?= htmlspecialchars($statusMeta['badge']) ?>">
                <?= htmlspecialchars($statusMeta['label']) ?>
            </span>
        </div>

        <?php if (!empty($statusMeta['description'])): ?>
            <p class="mt-3 text-sm text-gray-600">
                <?= htmlspecialchars($statusMeta['description']) ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($appeal['admin_response'])): ?>
            <div class="mt-4 rounded-2xl border border-blue-100 bg-blue-50 p-4">
                <h2 class="text-sm font-semibold text-blue-900">Notas registradas previamente</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-blue-900">
                    <?= htmlspecialchars($appeal['admin_response']) ?>
                </p>
                <?php if (!empty($appeal['reviewed_at'])): ?>
                    <p class="mt-2 text-xs text-blue-800">
                        Actualizado el <?= date('d/m/Y H:i', strtotime($appeal['reviewed_at'])) ?> por <?= htmlspecialchars($appeal['reviewer_name'] ?? 'Equipo Lucatón') ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <article class="lg:col-span-2 space-y-6">
            <div class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900">Argumentos del responsable</h2>
                <p class="mt-3 whitespace-pre-line text-sm text-gray-700">
                    <?= htmlspecialchars($appeal['reason'] ?? '—') ?>
                </p>
                <?php if (!empty($appeal['additional_evidence'])): ?>
                    <div class="mt-4 rounded-2xl border border-amber-100 bg-amber-50 p-4">
                        <h3 class="text-sm font-semibold text-amber-900">Notas adicionales</h3>
                        <p class="mt-2 whitespace-pre-line text-sm text-amber-900">
                            <?= htmlspecialchars($appeal['additional_evidence']) ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900">Documentos de respaldo</h2>
                <?php if (empty($files)): ?>
                    <p class="mt-3 text-sm text-gray-600">Esta apelación no incluye archivos adjuntos.</p>
                <?php else: ?>
                    <ul class="mt-4 space-y-3">
                        <?php foreach ($files as $file): ?>
                            <li class="flex items-center justify-between rounded-2xl border border-gray-100 bg-gray-50 px-4 py-3">
                                <div class="flex flex-col text-sm text-gray-700">
                                    <span class="font-semibold text-gray-900"><?= htmlspecialchars($file['original_name'] ?? 'Documento adjunto') ?></span>
                                    <span class="text-xs text-gray-500">
                                        <?= !empty($file['created_at']) ? date('d/m/Y H:i', strtotime($file['created_at'])) : '—' ?> · <?= number_format(((int)($file['size_bytes'] ?? 0)) / 1024, 1) ?> KB
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="<?= Router::url('admin/apelaciones/' . (int)($appeal['id'] ?? 0) . '/archivo/' . (int)($file['id'] ?? 0), ['mode' => 'inline']) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-md border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-100">
                                        Ver
                                    </a>
                                    <a href="<?= Router::url('admin/apelaciones/' . (int)($appeal['id'] ?? 0) . '/archivo/' . (int)($file['id'] ?? 0)) ?>" class="inline-flex items-center rounded-md bg-copihue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-copihue-700">
                                        Descargar
                                    </a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </article>

        <aside class="space-y-6">
            <div class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900">Campaña asociada</h2>
                <?php if (!empty($campaignView)): ?>
                    <div class="mt-3 space-y-3 text-sm text-gray-700">
                        <p class="font-semibold text-gray-900"><?= htmlspecialchars($campaignView['title'] ?? 'Campaña sin título') ?></p>
                        <p><span class="font-medium text-gray-900">Estado actual:</span> <?= htmlspecialchars(CampaignPresenter::statusMeta($campaignView['status'] ?? '')['label'] ?? 'N/A') ?></p>
                        <p><span class="font-medium text-gray-900">Responsable:</span> <?= htmlspecialchars($campaignView['owner_name'] ?? $appeal['requester_name'] ?? '—') ?></p>
                        <p><span class="font-medium text-gray-900">Recaudado:</span> $<?= number_format((float)($campaignView['raised_amount'] ?? 0), 0, ',', '.') ?></p>
                        <?php if (!empty($campaignView['public_path'])): ?>
                            <a href="<?= Router::url($campaignView['public_path']) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm font-medium text-copihue-600 hover:text-copihue-700">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 3h7v7" />
                                    <path d="M10 14 21 3" />
                                    <path d="M21 14v7h-7" />
                                    <path d="M3 10l11 11" />
                                </svg>
                                Ver campaña pública
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="mt-3 text-sm text-gray-600">No pudimos recuperar información adicional de la campaña.</p>
                <?php endif; ?>
            </div>

            <div class="bg-white shadow-soft rounded-3xl border border-gray-100 p-6" id="resolver">
                <h2 class="text-lg font-semibold text-gray-900">Resolver apelación</h2>
                <form method="POST" action="<?= Router::url('admin/apelaciones/' . (int)($appeal['id'] ?? 0) . '/resolver') ?>" class="mt-4 space-y-4">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Nuevo estado</label>
                        <select id="status" name="status" class="form-select block w-full rounded-lg border-gray-300 focus:border-copihue-500 focus:ring-copihue-500" required>
                            <option value="under_review" <?= $resolutionDefault === 'under_review' ? 'selected' : '' ?>>Marcar como en revisión</option>
                            <option value="approved" <?= $resolutionDefault === 'approved' ? 'selected' : '' ?>>Aprobar apelación y republicar</option>
                            <option value="rejected" <?= $resolutionDefault === 'rejected' ? 'selected' : '' ?>>Mantener rechazo original</option>
                            <option value="closed" <?= $resolutionDefault === 'closed' ? 'selected' : '' ?>>Cerrar manualmente</option>
                        </select>
                    </div>

                    <div>
                        <label for="response" class="block text-sm font-medium text-gray-700 mb-1">Notas para el responsable</label>
                        <textarea id="response" name="response" rows="4" class="form-textarea block w-full rounded-lg border-gray-300 focus:border-copihue-500 focus:ring-copihue-500" placeholder="Explica las acciones tomadas o los pasos a seguir."><?= htmlspecialchars($appeal['admin_response'] ?? '') ?></textarea>
                        <p class="mt-1 text-xs text-gray-500">Estas notas pueden compartirse con la persona responsable si activas la notificación.</p>
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="notify_owner" value="1" class="rounded border-gray-300 text-copihue-600 focus:ring-copihue-500" <?= in_array($status, ['pending', 'under_review'], true) ? 'checked' : '' ?>>
                        Notificar por correo y panel al responsable de la campaña
                    </label>

                    <button type="submit" class="btn-primary w-full justify-center">
                        Guardar resolución
                    </button>
                </form>
            </div>
        </aside>
    </section>
</div>
<?php
$content = ob_get_clean();

include VIEWS_PATH . '/layouts/admin.php';
?>
