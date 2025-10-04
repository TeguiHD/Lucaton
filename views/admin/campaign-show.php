<?php
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$statusMeta = CampaignPresenter::statusMeta($campaignView['status'] ?? 'draft');
$aiFlag = isset($campaignView['ai_assisted']) ? (bool)$campaignView['ai_assisted'] : null;
$ownerName = $campaignView['owner_name'] ?? ($campaignView['creator_name'] ?? 'Campañista');
$ownerProfileData = is_array($ownerProfile) ? $ownerProfile : [];
$ownerEmail = $campaignView['owner_email'] ?? ($ownerProfileData['email'] ?? null);
$ownerPhone = $ownerProfileData['phone'] ?? ($campaignView['owner_phone'] ?? null);
$goalAmount = (float)($campaignView['goal_amount'] ?? 0);
$raisedAmount = (float)($campaignView['raised_amount'] ?? 0);
$progress = $goalAmount > 0 ? min(100, round(($raisedAmount / $goalAmount) * 100)) : 0;
$createdAt = !empty($campaignView['created_at']) ? date('d/m/Y H:i', strtotime($campaignView['created_at'])) : '—';
$updatedAt = !empty($campaignView['updated_at']) ? date('d/m/Y H:i', strtotime($campaignView['updated_at'])) : '—';
$endDate = !empty($campaignView['end_date']) ? date('d/m/Y', strtotime($campaignView['end_date'])) : '—';
$beneficiaryName = $campaignView['beneficiary_name'] ?? 'No especificado';
$beneficiaryType = $campaignView['beneficiary_type'] ?? '—';
$locationLabel = $campaignView['location_label'] ?? ($campaignView['location'] ?? '—');

$formatCurrency = static function (float $amount): string {
    return '$' . number_format($amount, 0, ',', '.');
};

$formatBytes = static function (int $bytes): string {
    if ($bytes <= 0) {
        return '—';
    }

    $units = ['B', 'KB', 'MB', 'GB'];
    $power = min((int)floor(log($bytes, 1024)), count($units) - 1);
    $value = $bytes / (1024 ** $power);
    return number_format($value, $power === 0 ? 0 : 2, ',', '.') . ' ' . $units[$power];
};

$ownerDisplayName = $ownerProfileData['display_name'] ?? $ownerName;
$ownerAvatar = $ownerProfileData['avatar_url'] ?? null;
$ownerUsername = $ownerProfileData['username'] ?? null;
$ownerRoleLabel = $ownerProfileData['role_name'] ?? ($ownerProfileData['role'] ?? null);
$ownerCreatedAt = !empty($ownerProfileData['created_at']) ? date('d/m/Y H:i', strtotime($ownerProfileData['created_at'])) : null;
$ownerLastLogin = !empty($ownerProfileData['last_login_at']) ? date('d/m/Y H:i', strtotime($ownerProfileData['last_login_at'])) : null;
$ownerVerifiedAt = !empty($ownerProfileData['email_verified_at']) ? date('d/m/Y H:i', strtotime($ownerProfileData['email_verified_at'])) : null;
$ownerOrg = $ownerProfileData['organization'] ?? null;
$ownerBio = $ownerProfileData['bio'] ?? null;
$initialSource = trim((string)$ownerDisplayName);
if ($initialSource === '') {
    $initialSource = 'C';
}
$ownerInitials = strtoupper(function_exists('mb_substr') ? mb_substr($initialSource, 0, 2, 'UTF-8') : substr($initialSource, 0, 2));

ob_start();
?>
<div class="space-y-6">
    <?php include_flash_messages(); ?>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="<?= Router::url('admin/campanas') ?>" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
                <svg class="mr-1 h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M12.293 15.707a1 1 0 010-1.414L15.586 11H4a1 1 0 110-2h11.586l-3.293-3.293a1 1 0 111.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
                Volver al listado
            </a>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <h1 class="text-3xl font-bold text-gray-900">
                    <?= htmlspecialchars($campaignView['title'] ?? 'Campaña') ?>
                </h1>
                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">ID #<?= (int)($campaignView['id'] ?? 0) ?></span>
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?= htmlspecialchars($statusMeta['badge_class']) ?>">
                    <?= htmlspecialchars($statusMeta['label']) ?>
                </span>
            </div>
            <p class="mt-1 text-sm text-gray-500">Revisa los antecedentes antes de aprobar o rechazar esta campaña.</p>
        </div>
        <div class="flex flex-col items-start gap-2 sm:items-end">
            <button type="button"
                    class="inline-flex items-center gap-3 rounded-full border border-copihue-200 bg-white px-4 py-2 text-left shadow-sm transition hover:border-copihue-300 hover:bg-copihue-50 focus:outline-none focus:ring-2 focus:ring-copihue-400"
                    data-owner-modal-trigger>
                <?php if ($ownerAvatar): ?>
                    <img src="<?= htmlspecialchars($ownerAvatar) ?>" alt="Avatar del campañista" class="h-8 w-8 rounded-full object-cover">
                <?php else: ?>
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-copihue-100 text-copihue-700 text-xs font-semibold uppercase">
                        <?= htmlspecialchars($ownerInitials) ?>
                    </span>
                <?php endif; ?>
                <span class="flex flex-col">
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Campañista</span>
                    <span class="text-sm font-semibold text-copihue-700 truncate">
                        <?= htmlspecialchars($ownerDisplayName) ?>
                    </span>
                </span>
            </button>
            <?php if ($ownerEmail || $ownerPhone): ?>
                <div class="flex flex-col items-start text-xs text-gray-500 sm:items-end">
                    <?php if ($ownerEmail): ?>
                        <a href="mailto:<?= htmlspecialchars($ownerEmail) ?>" class="hover:text-copihue-600"><?= htmlspecialchars($ownerEmail) ?></a>
                    <?php endif; ?>
                    <?php if ($ownerPhone): ?>
                        <span><?= htmlspecialchars($ownerPhone) ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ($aiFlag !== null): ?>
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?= $aiFlag ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600' ?>">
                    Edición asistida por IA: <?= $aiFlag ? 'Sí' : 'No' ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="bg-white border border-gray-100 shadow-soft rounded-3xl overflow-hidden">
                <img src="<?= htmlspecialchars($coverImageUrl) ?>" alt="Imagen de portada" class="w-full h-80 object-cover">
            </div>

            <?php if (!empty($campaignView['summary'])): ?>
                <section class="bg-white border border-gray-100 shadow-soft rounded-3xl p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Resumen</h2>
                    <p class="text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($campaignView['summary'])) ?></p>
                </section>
            <?php endif; ?>

            <?php if (!empty($campaignView['story'])): ?>
                <section class="bg-white border border-gray-100 shadow-soft rounded-3xl p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Historia completa</h2>
                    <div class="prose prose-sm max-w-none text-gray-800">
                        <?= nl2br(htmlspecialchars($campaignView['story'])) ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($videoEmbed) && isset($videoEmbed['embed_url'], $videoEmbed['thumbnail_url'])): ?>
                <section class="bg-white border border-gray-100 shadow-soft rounded-3xl p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Video de respaldo</h2>
                    <div class="relative overflow-hidden rounded-2xl border border-gray-100 bg-black" style="aspect-ratio: 16 / 9;" data-youtube-placeholder
                         data-youtube-id="<?= htmlspecialchars($videoEmbed['id']) ?>"
                         data-youtube-src="<?= htmlspecialchars($videoEmbed['embed_url']) ?>">
                        <img src="<?= htmlspecialchars($videoEmbed['thumbnail_url']) ?>"
                             alt="Miniatura del video"
                             class="h-full w-full object-cover"
                             loading="lazy">
                        <button type="button"
                                class="group absolute inset-0 flex h-full w-full items-center justify-center bg-black/40 transition hover:bg-black/55 focus:outline-none focus:ring-2 focus:ring-white"
                                data-youtube-trigger>
                            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-white/90 text-copihue-600 shadow-lg transition group-hover:bg-white">
                                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                            </span>
                            <span class="sr-only">Reproducir video</span>
                        </button>
                    </div>
                    <p class="mt-3 text-xs text-gray-500">
                        Cargamos el iframe de YouTube solo cuando lo reproduces para reducir rastreadores. Si prefieres verlo en otra pestaña,
                        abre el <a href="<?= htmlspecialchars($videoEmbed['watch_url']) ?>" target="_blank" rel="noopener" class="text-copihue-600 hover:text-copihue-700">enlace directo</a>.
                    </p>
                </section>
            <?php endif; ?>

            <?php if (!empty($galleryMedia)): ?>
                <section class="bg-white border border-gray-100 shadow-soft rounded-3xl p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Galería de apoyo</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <?php foreach ($galleryMedia as $media): ?>
                            <?php $imageAlt = $media['filename'] ?? 'Imagen de apoyo'; ?>
                            <button type="button"
                                    class="group relative overflow-hidden rounded-2xl border border-gray-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-copihue-500"
                                    data-gallery-image="<?= htmlspecialchars($media['url']) ?>"
                                    data-gallery-alt="<?= htmlspecialchars($imageAlt) ?>">
                                <img src="<?= htmlspecialchars($media['url']) ?>" alt="<?= htmlspecialchars($imageAlt) ?>" class="w-full h-48 object-cover transition-transform duration-300 group-hover:scale-105">
                                <span class="absolute inset-0 hidden items-center justify-center bg-black/40 text-white text-xs font-semibold uppercase tracking-wide group-hover:flex">Ampliar</span>
                                <?php if (!empty($media['filename'])): ?>
                                    <span class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent px-3 py-2 text-left text-xs text-white">
                                        <?= htmlspecialchars($media['filename']) ?>
                                    </span>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>

        <aside class="space-y-6">
            <section class="bg-white border border-gray-100 shadow-soft rounded-3xl p-6 space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Resumen de métricas</h2>
                    <p class="text-sm text-gray-500">Estos valores pueden cambiar según sea aprobada la campaña.</p>
                </div>
                <dl class="grid grid-cols-1 gap-3 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-gray-500">Meta económica</dt>
                        <dd class="font-semibold text-gray-900"><?= $formatCurrency($goalAmount) ?></dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-gray-500">Monto recaudado</dt>
                        <dd class="font-semibold text-gray-900"><?= $formatCurrency($raisedAmount) ?></dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-gray-500">Avance</dt>
                        <dd class="font-semibold text-gray-900"><?= number_format($progress, 0) ?>%</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-gray-500">Fecha límite</dt>
                        <dd class="font-semibold text-gray-900"><?= htmlspecialchars($endDate) ?></dd>
                    </div>
                </dl>
                <div class="h-2 w-full bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-copihue-500 to-copihue-600" style="width: <?= min(100, max(0, $progress)) ?>%"></div>
                </div>
            </section>

            <section class="bg-white border border-gray-100 shadow-soft rounded-3xl p-6 space-y-3 text-sm">
                <h2 class="text-lg font-semibold text-gray-900">Datos declarados</h2>
                <div class="flex items-center justify-between">
                    <span class="text-gray-500">Beneficiario</span>
                    <span class="font-medium text-gray-900 text-right">
                        <?= htmlspecialchars($beneficiaryName) ?><br>
                        <span class="text-xs text-gray-500">Tipo: <?= htmlspecialchars($beneficiaryType) ?></span>
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-500">Ubicación</span>
                    <span class="font-medium text-gray-900 text-right"><?= htmlspecialchars($locationLabel) ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-500">Creada</span>
                    <span class="font-medium text-gray-900 text-right"><?= htmlspecialchars($createdAt) ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-500">Última actualización</span>
                    <span class="font-medium text-gray-900 text-right"><?= htmlspecialchars($updatedAt) ?></span>
                </div>
            </section>

            <section class="bg-white border border-gray-100 shadow-soft rounded-3xl p-6 space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Documentos respaldatorios</h2>
                    <p class="text-sm text-gray-500">Previsualiza los archivos y descárgalos para un análisis más profundo.</p>
                </div>
                <?php if (empty($attachments)): ?>
                    <p class="text-sm text-gray-500">No se adjuntaron documentos adicionales.</p>
                <?php else: ?>
                    <ul class="space-y-3 text-sm">
                        <?php foreach ($attachments as $file): ?>
                            <?php $formattedSize = $formatBytes((int)$file['size']); ?>
                            <li class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-4">
                                <div class="space-y-3">
                                    <div class="min-w-0 break-words break-all">
                                        <p class="font-medium text-gray-800 leading-snug">
                                            <?= htmlspecialchars($file['filename']) ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            <?= htmlspecialchars($file['mime']) ?> · <?= $formattedSize ?>
                                        </p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="button"
                                                class="inline-flex items-center rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-copihue-600 ring-1 ring-inset ring-copihue-200 transition hover:bg-copihue-50 focus:outline-none focus:ring-2 focus:ring-copihue-300"
                                                data-preview-doc
                                                data-preview-url="<?= htmlspecialchars($file['preview_url'] ?? '') ?>"
                                                data-preview-name="<?= htmlspecialchars($file['filename']) ?>"
                                                data-preview-mime="<?= htmlspecialchars($file['mime']) ?>"
                                                data-preview-size="<?= htmlspecialchars($formattedSize) ?>"
                                                data-download-url="<?= htmlspecialchars($file['url']) ?>"
                                                data-preview-available="<?= !empty($file['preview_url']) ? '1' : '0' ?>">
                                            Visualizar
                                        </button>
                                        <a href="<?= htmlspecialchars($file['url']) ?>"
                                           class="inline-flex items-center rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-copihue-600 ring-1 ring-inset ring-copihue-200 transition hover:bg-copihue-50 focus:outline-none focus:ring-2 focus:ring-copihue-300"
                                           target="_blank"
                                           rel="noopener"
                                           download>
                                            Descargar
                                        </a>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <section class="bg-white border border-gray-100 shadow-soft rounded-3xl p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Decisión editorial</h2>
                <p class="text-sm text-gray-500">Comparte comentarios claros cuando rechaces para que el responsable pueda apelar o corregir.</p>
                <div class="flex flex-col gap-3">
                    <form method="POST" action="<?= Router::url('admin/campana/' . $campaignView['id'] . '/aprobar') ?>" class="space-y-3">
                        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">
                        <button type="submit"
                                class="inline-flex w-full items-center justify-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
                                data-confirm-action="approve"
                                data-confirm-message="¿Estás seguro que deseas confirmar esta campaña?">
                            Aprobar campaña
                        </button>
                    </form>
                    <form method="POST" action="<?= Router::url('admin/campana/' . $campaignView['id'] . '/rechazar') ?>" class="space-y-3" id="reject-form">
                        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">
                        <label for="reject-notes" class="text-sm font-medium text-gray-700">Motivo del rechazo</label>
                        <textarea id="reject-notes" name="notes" rows="3" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-red-500" placeholder="Describe los antecedentes faltantes o ajustes solicitados"></textarea>
                        <button type="submit"
                                class="inline-flex w-full items-center justify-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                data-confirm-action="reject"
                                data-confirm-message="¿Estás seguro que deseas rechazar esta campaña?">
                            Rechazar campaña
                        </button>
                    </form>
                </div>
            </section>
        </aside>
        </div>
    </div>

    <div id="media-lightbox" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/70 px-4 py-8" role="dialog" aria-modal="true">
        <div class="relative max-w-4xl w-full">
            <button type="button" class="absolute -top-10 right-0 text-white text-sm hover:text-gray-200" data-lightbox-close>Cerrar</button>
            <div class="overflow-hidden rounded-2xl bg-white shadow-strong">
                <img src="" alt="Vista previa" class="max-h-[70vh] w-full object-contain" data-lightbox-image>
                <div class="px-4 py-3 border-t border-gray-100 text-sm text-gray-600" data-lightbox-caption></div>
            </div>
        </div>
    </div>
</div>
</div>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm px-4 py-6 sm:py-10" role="dialog" aria-modal="true" data-doc-modal>
    <div class="relative w-full max-w-5xl">
        <div class="flex max-h-[98vh] flex-col overflow-hidden rounded-3xl bg-white shadow-strong">
            <header class="flex items-start justify-between border-b border-gray-100 px-6 py-4" data-doc-modal-header>
                <div>
                    <p class="text-sm font-semibold text-gray-900" data-doc-modal-title>Documento</p>
                    <p class="text-xs text-gray-500" data-doc-modal-meta></p>
                </div>
                <button type="button" class="text-sm text-gray-400 hover:text-gray-600" data-doc-modal-close>Cerrar</button>
            </header>
            <div class="flex-1 overflow-hidden bg-gray-50">
                <iframe data-doc-modal-frame
                        class="hidden h-full w-full bg-white"
                        sandbox="allow-same-origin allow-scripts allow-downloads"
                        loading="lazy"></iframe>
                <div class="flex h-full flex-col items-center justify-center gap-3 p-6 text-center" data-doc-modal-fallback>
                    <svg class="h-12 w-12 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h10M7 11h10M7 15h6m5 5H6a2 2 0 01-2-2V6a2 2 0 012-2h7l6 6v10a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-sm font-medium text-gray-700">No se pudo mostrar una vista previa</p>
                    <p class="text-xs text-gray-500">Descarga el archivo para revisarlo con el visor correspondiente.</p>
                </div>
            </div>
            <div class="border-t border-gray-100 bg-white/95" data-doc-modal-footer>
                <div class="sticky bottom-0 bg-white/95 px-6 py-4">
            <footer class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-gray-500">Los documentos se alojan de forma privada. Asegúrate de almacenarlos en un entorno seguro.</p>
                        <a href="#" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-md bg-copihue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-copihue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" data-doc-modal-download>
                            <svg class="h-4 w-4 flex-shrink-0 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12l4 4m0 0l4-4m-4 4V4" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 16.5v1a2.5 2.5 0 01-2.5 2.5h-11A2.5 2.5 0 014 17.5v-1" />
                            </svg>
                            <span class="text-white">Descargar documento</span>
                        </a>
                    </footer>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 px-4 py-8" role="dialog" aria-modal="true" data-owner-modal>
    <div class="relative w-full max-w-xl">
        <div class="overflow-hidden rounded-3xl bg-white shadow-strong">
            <header class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-gray-900">Perfil del campañista</h2>
                <button type="button" class="text-sm text-gray-400 hover:text-gray-600" data-owner-modal-close>Cerrar</button>
            </header>
            <div class="space-y-5 px-6 py-6">
                <div class="flex items-center gap-4">
                    <?php if ($ownerAvatar): ?>
                        <img src="<?= htmlspecialchars($ownerAvatar) ?>" alt="Avatar del campañista" class="h-14 w-14 rounded-full object-cover">
                    <?php else: ?>
                        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-copihue-100 text-lg font-semibold uppercase text-copihue-700">
                            <?= htmlspecialchars($ownerInitials) ?>
                        </span>
                    <?php endif; ?>
                    <div>
                        <p class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($ownerDisplayName) ?></p>
                        <?php if ($ownerUsername): ?>
                            <p class="text-sm text-gray-500">@<?= htmlspecialchars($ownerUsername) ?></p>
                        <?php endif; ?>
                        <?php if ($ownerRoleLabel): ?>
                            <p class="mt-1 inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600">Rol: <?= htmlspecialchars($ownerRoleLabel) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <dl class="grid gap-4 text-sm text-gray-600 sm:grid-cols-2">
                    <div>
                        <dt class="font-medium text-gray-500">Correo</dt>
                        <dd>
                            <?php if ($ownerEmail): ?>
                                <a href="mailto:<?= htmlspecialchars($ownerEmail) ?>" class="text-copihue-600 hover:text-copihue-700"><?= htmlspecialchars($ownerEmail) ?></a>
                            <?php else: ?>
                                <span>No registrado</span>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Teléfono</dt>
                        <dd><?= $ownerPhone ? htmlspecialchars($ownerPhone) : 'No informado' ?></dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Cuenta creada</dt>
                        <dd><?= $ownerCreatedAt ? htmlspecialchars($ownerCreatedAt) : 'Sin datos' ?></dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Último acceso</dt>
                        <dd><?= $ownerLastLogin ? htmlspecialchars($ownerLastLogin) : 'Sin registro' ?></dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Correo verificado</dt>
                        <dd><?= $ownerVerifiedAt ? htmlspecialchars($ownerVerifiedAt) : 'Pendiente de verificación' ?></dd>
                    </div>
                    <?php if ($ownerOrg): ?>
                        <div>
                            <dt class="font-medium text-gray-500">Organización</dt>
                            <dd><?= htmlspecialchars($ownerOrg) ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>

                <?php if ($ownerBio): ?>
                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4 text-sm text-gray-700">
                        <p class="font-semibold text-gray-900">Resumen</p>
                        <p class="mt-1 whitespace-pre-line"><?= nl2br(htmlspecialchars($ownerBio)) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 px-4 py-8" role="dialog" aria-modal="true" data-confirm-modal>
    <div class="relative w-full max-w-md">
        <div class="overflow-hidden rounded-3xl bg-white shadow-strong">
            <header class="border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-gray-900" data-confirm-title>Confirmar acción</h2>
            </header>
            <div class="space-y-4 px-6 py-5 text-sm text-gray-600">
                <p data-confirm-message>¿Deseas continuar con esta acción?</p>
            </div>
            <footer class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
                <button type="button" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300" data-confirm-cancel>Cancelar</button>
                <button type="button" class="inline-flex items-center rounded-md px-4 py-2 text-sm font-semibold text-white focus:outline-none focus:ring-2 focus:ring-offset-2" data-confirm-accept>Confirmar</button>
            </footer>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();

ob_start();
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleModal = (modal, show) => {
        if (!modal) return;
        if (show) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        } else {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    };

    // Galería: reutilizamos el lightbox existente
    const lightbox = document.getElementById('media-lightbox');
    const lightboxImage = lightbox ? lightbox.querySelector('[data-lightbox-image]') : null;
    const lightboxCaption = lightbox ? lightbox.querySelector('[data-lightbox-caption]') : null;

    if (lightbox && lightboxImage) {
        const closeLightbox = () => {
            toggleModal(lightbox, false);
            lightboxImage.src = '';
            if (lightboxCaption) {
                lightboxCaption.textContent = '';
            }
        };

        document.querySelectorAll('[data-gallery-image]').forEach(function (button) {
            button.addEventListener('click', function () {
                const imageUrl = this.getAttribute('data-gallery-image');
                const imageAlt = this.getAttribute('data-gallery-alt') || 'Imagen de apoyo';
                lightboxImage.src = imageUrl;
                lightboxImage.alt = imageAlt;
                if (lightboxCaption) {
                    lightboxCaption.textContent = imageAlt;
                }
                toggleModal(lightbox, true);
            });
        });

        lightbox.addEventListener('click', function (event) {
            if (event.target === lightbox) {
                closeLightbox();
            }
        });

        const closeButton = lightbox.querySelector('[data-lightbox-close]');
        if (closeButton) {
            closeButton.addEventListener('click', closeLightbox);
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !lightbox.classList.contains('hidden')) {
                closeLightbox();
            }
        });
    }

    // Carga diferida de videos YouTube
    document.querySelectorAll('[data-youtube-placeholder]').forEach(function (container) {
        const trigger = container.querySelector('[data-youtube-trigger]');
        const src = container.getAttribute('data-youtube-src');
        const videoId = container.getAttribute('data-youtube-id');
        if (!trigger || !src) {
            return;
        }

        const loadVideo = () => {
            if (container.dataset.youtubeLoaded === '1') {
                return;
            }

            const iframe = document.createElement('iframe');
            const autoplayUrl = src.includes('?') ? src + '&autoplay=1' : src + '?autoplay=1';
            iframe.src = autoplayUrl;
            iframe.title = 'Video de la campaña';
            iframe.loading = 'lazy';
            iframe.className = 'absolute inset-0 h-full w-full';
            iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture');
            iframe.setAttribute('allowfullscreen', '');
            iframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
            iframe.setAttribute('sandbox', 'allow-same-origin allow-scripts allow-popups allow-popups-to-escape-sandbox');

            container.innerHTML = '';
            container.appendChild(iframe);
            container.dataset.youtubeLoaded = '1';

            if (videoId) {
                container.setAttribute('data-youtube-loaded-id', videoId);
            }
        };

        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            loadVideo();
        });

        trigger.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                loadVideo();
            }
        });
    });

    // Modal de documentos
    const docModal = document.querySelector('[data-doc-modal]');
    const docModalFrame = docModal ? docModal.querySelector('[data-doc-modal-frame]') : null;
    const docModalFallback = docModal ? docModal.querySelector('[data-doc-modal-fallback]') : null;
    const docModalTitle = docModal ? docModal.querySelector('[data-doc-modal-title]') : null;
    const docModalMeta = docModal ? docModal.querySelector('[data-doc-modal-meta]') : null;
    const docModalDownload = docModal ? docModal.querySelector('[data-doc-modal-download]') : null;
    const closeDocModal = () => {
        if (!docModal) return;
        if (docModalFrame) {
            docModalFrame.src = 'about:blank';
            docModalFrame.classList.add('hidden');
        }
        if (docModalFallback) {
            docModalFallback.classList.remove('hidden');
        }
        toggleModal(docModal, false);
    };

    const openDocModal = ({ name, mime, size, previewUrl, downloadUrl, canPreview }) => {
        if (!docModal) {
            if (previewUrl) {
                window.open(previewUrl, '_blank');
            } else if (downloadUrl) {
                window.open(downloadUrl, '_blank');
            }
            return;
        }

        if (docModalTitle) {
            docModalTitle.textContent = name || 'Documento';
        }
        if (docModalMeta) {
            const metaParts = [mime || null, size || null].filter(Boolean);
            docModalMeta.textContent = metaParts.length ? metaParts.join(' · ') : 'Documento privado';
        }

        if (docModalDownload) {
            if (downloadUrl) {
                docModalDownload.href = downloadUrl;
                docModalDownload.setAttribute('download', name || 'documento');
                docModalDownload.classList.remove('pointer-events-none', 'opacity-60');
            } else {
                docModalDownload.href = '#';
                docModalDownload.removeAttribute('download');
                docModalDownload.classList.add('pointer-events-none', 'opacity-60');
            }
        }

        if (docModalFrame && canPreview && previewUrl) {
            docModalFrame.src = previewUrl;
            docModalFrame.classList.remove('hidden');
            docModalFallback?.classList.add('hidden');
        } else {
            if (docModalFrame) {
                docModalFrame.src = 'about:blank';
                docModalFrame.classList.add('hidden');
            }
            docModalFallback?.classList.remove('hidden');
        }

        toggleModal(docModal, true);
    };

    if (docModal) {
        const docCloseButtons = docModal.querySelectorAll('[data-doc-modal-close]');
        docCloseButtons.forEach((button) => button.addEventListener('click', closeDocModal));

        docModal.addEventListener('click', function (event) {
            if (event.target === docModal) {
                closeDocModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !docModal.classList.contains('hidden')) {
                closeDocModal();
            }
        });
    }

    document.querySelectorAll('[data-preview-doc]').forEach(function (button) {
        button.addEventListener('click', function () {
            const name = this.getAttribute('data-preview-name') || 'Documento';
            const mime = this.getAttribute('data-preview-mime') || '';
            const size = this.getAttribute('data-preview-size') || '';
            const previewUrl = this.getAttribute('data-preview-url') || '';
            const downloadUrl = this.getAttribute('data-download-url') || '';
            const canPreview = this.getAttribute('data-preview-available') === '1';

            openDocModal({ name, mime, size, previewUrl, downloadUrl, canPreview });
        });
    });

    // Modal de perfil del campañista
    const ownerModal = document.querySelector('[data-owner-modal]');
    const ownerModalClose = ownerModal ? ownerModal.querySelector('[data-owner-modal-close]') : null;
    const ownerModalTriggers = document.querySelectorAll('[data-owner-modal-trigger]');

    const closeOwnerModal = () => toggleModal(ownerModal, false);
    const openOwnerModal = () => toggleModal(ownerModal, true);

    ownerModalTriggers.forEach((button) => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            openOwnerModal();
        });
    });

    if (ownerModal) {
        ownerModal.addEventListener('click', function (event) {
            if (event.target === ownerModal) {
                closeOwnerModal();
            }
        });
        if (ownerModalClose) {
            ownerModalClose.addEventListener('click', closeOwnerModal);
        }
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !ownerModal.classList.contains('hidden')) {
                closeOwnerModal();
            }
        });
    }

    // Confirmaciones para aprobar o rechazar
    const confirmModal = document.querySelector('[data-confirm-modal]');
    const confirmAccept = confirmModal ? confirmModal.querySelector('[data-confirm-accept]') : null;
    const confirmCancel = confirmModal ? confirmModal.querySelector('[data-confirm-cancel]') : null;
    const confirmMessage = confirmModal ? confirmModal.querySelector('[data-confirm-message]') : null;
    const confirmTitle = confirmModal ? confirmModal.querySelector('[data-confirm-title]') : null;

    let pendingForm = null;
    let pendingAction = null;

    const applyConfirmStyling = (action) => {
        if (!confirmAccept) {
            return;
        }
        const approveClasses = ['bg-emerald-600', 'hover:bg-emerald-700', 'focus:ring-emerald-500'];
        const rejectClasses = ['bg-red-600', 'hover:bg-red-700', 'focus:ring-red-500'];

        confirmAccept.classList.remove(...approveClasses, ...rejectClasses);

        if (action === 'reject') {
            confirmAccept.classList.add(...rejectClasses);
            confirmAccept.textContent = 'Confirmar rechazo';
            if (confirmTitle) {
                confirmTitle.textContent = 'Rechazar campaña';
            }
        } else {
            confirmAccept.classList.add(...approveClasses);
            confirmAccept.textContent = 'Confirmar campaña';
            if (confirmTitle) {
                confirmTitle.textContent = 'Confirmar campaña';
            }
        }
    };

    const openConfirmModal = (action, message) => {
        if (!confirmModal) {
            return true;
        }
        applyConfirmStyling(action);
        if (confirmMessage) {
            confirmMessage.textContent = message;
        }
        toggleModal(confirmModal, true);
        return false;
    };

    document.querySelectorAll('[data-confirm-action]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            const form = this.closest('form');
            if (!form) {
                return;
            }

            if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
                return;
            }

            const action = this.getAttribute('data-confirm-action') || 'approve';
            const message = this.getAttribute('data-confirm-message')
                || (action === 'reject'
                    ? '¿Estás seguro que deseas rechazar esta campaña?'
                    : '¿Estás seguro que deseas confirmar la campaña?');

            if (!confirmModal) {
                const proceed = window.confirm(message);
                if (proceed) {
                    form.submit();
                }
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            pendingForm = form;
            pendingAction = action;
            openConfirmModal(action, message);
        });
    });

    if (confirmModal) {
        if (confirmCancel) {
            confirmCancel.addEventListener('click', () => {
                pendingForm = null;
                pendingAction = null;
                toggleModal(confirmModal, false);
            });
        }

        if (confirmAccept) {
            confirmAccept.addEventListener('click', () => {
                if (pendingForm) {
                    const formToSubmit = pendingForm;
                    pendingForm = null;
                    pendingAction = null;
                    toggleModal(confirmModal, false);
                    formToSubmit.submit();
                }
            });
        }

        confirmModal.addEventListener('click', function (event) {
            if (event.target === confirmModal) {
                pendingForm = null;
                pendingAction = null;
                toggleModal(confirmModal, false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !confirmModal.classList.contains('hidden')) {
                pendingForm = null;
                pendingAction = null;
                toggleModal(confirmModal, false);
            }
        });
    }
});
</script>
<?php
$additional_scripts = ($additional_scripts ?? '') . ob_get_clean();

include VIEWS_PATH . '/layouts/admin.php';
