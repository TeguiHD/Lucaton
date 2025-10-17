<?php
require_once __DIR__ . '/../layouts/partials/flash-messages.php';
require_once __DIR__ . '/../components/navigation.php';

$campaigns = $campaigns ?? [];
$totalCampaigns = $totalCampaigns ?? 0;
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;
$hasMore = $hasMore ?? false;
$campaignAppeals = $campaignAppeals ?? [];
$appealFormErrors = $appealFormErrors ?? [];
$appealFormOld = $appealFormOld ?? [];

$page_title = 'Mis campañas — Lucatón';
$page_description = 'Administra tus campañas, consulta su estado y accede rápidamente a sus estadísticas.';

$formatCurrency = static function (float $amount): string {
    return '$' . number_format($amount, 0, ',', '.');
};

$statusLabels = [
    'draft' => ['label' => 'Borrador', 'class' => 'bg-gray-100 text-gray-700'],
    'under_review' => ['label' => 'En revisión', 'class' => 'bg-amber-100 text-amber-800'],
    'published' => ['label' => 'Publicada', 'class' => 'bg-green-100 text-green-800'],
    'active' => ['label' => 'Activa', 'class' => 'bg-green-100 text-green-800'],
    'completed' => ['label' => 'Completada', 'class' => 'bg-blue-100 text-blue-800'],
    'paused' => ['label' => 'Pausada', 'class' => 'bg-yellow-100 text-yellow-800'],
    'cancelled' => ['label' => 'Cancelada', 'class' => 'bg-red-100 text-red-800'],
    'archived' => ['label' => 'Archivada', 'class' => 'bg-gray-100 text-gray-600'],
    'pending_review' => ['label' => 'En revisión', 'class' => 'bg-amber-100 text-amber-800'],
];

$publicStatuses = ['published', 'active', 'completed'];
$statusGuidance = [
    'draft' => 'Esta campaña es un borrador. Ajusta los detalles pendientes y envíala a revisión cuando estés listo.',
    'under_review' => 'Estamos revisando los antecedentes. Solo tú y el equipo de Lucatón pueden ver esta campaña por ahora.',
    'cancelled' => 'La campaña fue rechazada. Revisa las observaciones del equipo y actualiza la información antes de solicitar una nueva evaluación.',
    'paused' => 'La campaña está pausada y no es visible públicamente. Puedes solicitar su reactivación una vez que actualices los datos necesarios.',
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">

    <link rel="icon" type="image/svg+xml" href="<?= asset_url('images/favicon.svg') ?>">
    <link href="<?= asset_url('css/app.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('css/aliases.css') ?>" rel="stylesheet">
    <script defer src="<?= asset_url('js/app.js') ?>"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <?php include VIEWS_PATH . '/layouts/partials/header.php'; ?>

    <main id="main-content" class="max-w-7xl mx-auto py-8 sm:px-6 lg:px-8">
        <?php include_flash_messages(); ?>

        <?= render_breadcrumb([
            ['name' => 'Inicio', 'href' => Router::url('/')],
            ['name' => 'Mi Panel', 'href' => Router::url('panel')],
            ['name' => 'Mis campañas', 'href' => Router::url('mis-campanas')],
        ]); ?>

        <div class="mb-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-wide text-gray-500">Tus iniciativas</p>
                <h1 class="mt-1 text-3xl font-bold text-gray-900">Mis campañas</h1>
                <p class="mt-2 text-sm text-gray-600">Organiza tus campañas, revisa su progreso y mantén a tus donantes informados.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="<?= Router::url('campana/crear') ?>" class="btn-primary inline-flex items-center">
                    <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Crear campaña
                </a>
                <a href="<?= Router::url('panel') ?>" class="btn-secondary inline-flex items-center">
                    <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L4.5 12m0 0l5.25-5M4.5 12H19.5" />
                    </svg>
                    Volver al panel
                </a>
            </div>
        </div>

        <section class="mb-10">
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-500">Campañas totales</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        <?= number_format($totalCampaigns) ?>
                    </p>
                    <p class="mt-1 text-sm text-gray-500">Incluye campañas en revisión, publicadas y finalizadas.</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-500">Campañas activas</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        <?= number_format(array_reduce($campaigns, static function ($carry, $campaign) {
                            $status = $campaign['status'] ?? '';
                            if (in_array($status, ['published', 'active'], true)) {
                                return $carry + 1;
                            }
                            return $carry;
                        }, 0)) ?>
                    </p>
                    <p class="mt-1 text-sm text-gray-500">Considera campañas publicadas o activas actualmente.</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-500">Próximo paso recomendado</p>
                    <p class="mt-2 text-base text-gray-900">Comparte una actualización con tus donantes</p>
                    <p class="mt-1 text-sm text-gray-500">Mantén informada a tu comunidad sobre avances o necesidades.</p>
                </div>
            </div>
        </section>

        <section>
            <?php if (empty($campaigns)): ?>
                <div class="bg-white rounded-lg border border-dashed border-gray-200 p-12 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-copihue-100 text-copihue-600">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3v6m9-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="mt-6 text-xl font-semibold text-gray-900">Aún no tienes campañas</h2>
                    <p class="mt-2 text-sm text-gray-600">Cuando publiques tu primera campaña aparecerá acá junto a sus métricas clave.</p>
                    <div class="mt-6 flex justify-center">
                        <a href="<?= Router::url('campana/crear') ?>" class="btn-primary inline-flex items-center">
                            Comenzar mi primera campaña
                            <svg class="ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($campaigns as $campaign): ?>
                        <?php
                            $statusKey = $campaign['status'] ?? 'draft';
                            $status = $statusLabels[$statusKey] ?? ['label' => ucfirst(str_replace('_', ' ', $statusKey)), 'class' => 'bg-gray-100 text-gray-700'];
                            $isRestricted = !in_array($statusKey, $publicStatuses, true);
                            $goal = (float)($campaign['goal_amount'] ?? 0);
                            $raised = (float)($campaign['raised_amount'] ?? 0);
                            $progress = $goal > 0 ? min(100, round(($raised / $goal) * 100)) : 0;
                            $donors = (int)($campaign['donor_count'] ?? 0);
                            $createdAt = !empty($campaign['created_at']) ? date('d/m/Y', strtotime($campaign['created_at'])) : '—';
                            $campaignTitle = $campaign['title'] ?? 'Campaña sin título';
                            $campaignSummary = $campaign['summary'] ?? '';
                            $campaignId = $campaign['id'] ?? null;
                            $campaignSlug = $campaign['slug'] ?? null;
                            $visibilityLabel = $campaign['visibility'] ?? '';
                            $coverCandidate = $campaign['cover_image_url'] ?? ($campaign['image_url'] ?? null);
                            $cardImageUrl = CampaignMediaUploadService::normalizePublicUrl($coverCandidate)
                                ?? (APP_URL . '/public/assets/images/campaigns/placeholder.jpg');
                            $aiFlag = isset($campaign['ai_assisted']) ? (bool)$campaign['ai_assisted'] : null;
                            if ($visibilityLabel === 'private') {
                                $visibilityLabel = 'Privada';
                            } elseif ($visibilityLabel === 'public') {
                                $visibilityLabel = 'Pública';
                            } elseif ($visibilityLabel) {
                                $visibilityLabel = ucfirst($visibilityLabel);
                            }
                            $campaignPublicPath = $campaign['public_path'] ?? CampaignPresenter::buildPublicPath($campaign);
                            $viewUrl = $campaignPublicPath ? Router::url($campaignPublicPath) : null;
                            $editUrl = $campaignId ? Router::url('campana/' . $campaignId . '/editar') : null;
                        ?>
                        <?php
                            $appealRecord = $campaignAppeals[$campaignId] ?? null;
                            $appealStatus = strtolower((string)($appealRecord['status'] ?? ''));
                            $appealOldData = $appealFormOld[$campaignId] ?? [];
                            $appealErrors = $appealFormErrors[$campaignId] ?? [];
                            $canAppeal = in_array($statusKey, ['cancelled', 'paused'], true);
                        ?>
                        <article id="campaign-<?= $campaignId ?>" class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                            <div class="flex flex-col gap-4 p-6 md:flex-row md:items-start">
                                <div class="w-full md:w-48 flex-shrink-0">
                                    <img src="<?= htmlspecialchars($cardImageUrl) ?>" alt="Imagen de <?= htmlspecialchars($campaignTitle) ?>" class="h-40 w-full rounded-lg object-cover shadow-sm">
                                </div>
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?= $status['class'] ?>">
                                            <?= htmlspecialchars($status['label']) ?>
                                        </span>
                                        <?php if ($visibilityLabel && strtolower($campaign['visibility'] ?? '') !== 'public'): ?>
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                                                Visibilidad: <?= htmlspecialchars($visibilityLabel) ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="inline-flex items-center text-xs text-gray-500">
                                            Creada el <?= htmlspecialchars($createdAt) ?>
                                        </span>
                                        <?php if ($aiFlag !== null): ?>
                                            <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700">
                                                IA asistida: <?= $aiFlag ? 'Sí' : 'No' ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <h2 class="text-xl font-semibold text-gray-900">
                                            <?php if ($viewUrl): ?>
                                                <a href="<?= htmlspecialchars($viewUrl) ?>" class="hover:text-copihue-600 transition-colors">
                                                    <?= htmlspecialchars($campaignTitle) ?>
                                                </a>
                                            <?php else: ?>
                                                <?= htmlspecialchars($campaignTitle) ?>
                                            <?php endif; ?>
                                        </h2>
                                        <?php if ($campaignId): ?>
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600">ID #<?= (int)$campaignId ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isRestricted): ?>
                                        <div class="mt-2 flex items-start gap-2 text-xs text-amber-600">
                                            <svg class="h-4 w-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.38 0 2.5-1.12 2.5-2.5S13.38 6 12 6 9.5 7.12 9.5 8.5 10.62 11 12 11zm0 0v4m-6 4h12a2 2 0 002-2v-3.586a1 1 0 00-.293-.707l-6.414-6.414a1 1 0 00-1.414 0L4.293 12.707A1 1 0 004 13.414V17a2 2 0 002 2z" />
                                            </svg>
                                            <span><?= htmlspecialchars($statusGuidance[$statusKey] ?? 'Esta campaña aún no está publicada. Solo tú y el equipo de Lucatón pueden verla.') ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($campaignSummary)): ?>
                                        <p class="mt-2 text-sm text-gray-600 leading-6">
                                            <?= htmlspecialchars($campaignSummary) ?>
                                        </p>
                                    <?php endif; ?>
                                    <dl class="mt-4 grid grid-cols-2 gap-4 text-sm text-gray-600 sm:grid-cols-4">
                                        <div>
                                            <dt class="font-medium text-gray-900">Meta</dt>
                                            <dd class="mt-1 text-gray-700"><?= htmlspecialchars($formatCurrency($goal)) ?></dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-gray-900">Recaudado</dt>
                                            <dd class="mt-1 text-gray-700"><?= htmlspecialchars($formatCurrency($raised)) ?></dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-gray-900">Avance</dt>
                                            <dd class="mt-1 text-gray-700"><?= $progress ?>%</dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-gray-900">Donantes</dt>
                                            <dd class="mt-1 text-gray-700"><?= number_format($donors) ?></dd>
                                        </div>
                                    </dl>
                                </div>
                                <div class="flex-shrink-0 w-full md:w-48 space-y-3">
                                    <div class="flex items-center justify-between text-sm text-gray-500">
                                        <span class="font-medium text-gray-900">Estado general</span>
                                        <span><?= $progress >= 100 ? 'Completada' : ($progress >= 50 ? 'Avanzando' : 'Recién iniciada') ?></span>
                                    </div>
                                    <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                                        <div class="h-full rounded-full bg-copihue-500" style="width: <?= $progress ?>%"></div>
                                    </div>
                                    <div class="flex flex-col gap-2 text-sm">
                                        <?php if ($viewUrl): ?>
                                            <a href="<?= htmlspecialchars($viewUrl) ?>" class="btn inline-flex items-center justify-center rounded-md border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:border-copihue-500 hover:text-copihue-600">
                                                <?= $isRestricted ? 'Ver vista privada' : 'Ver campaña' ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="inline-flex items-center justify-center rounded-md border border-dashed border-amber-300 px-3 py-2 text-sm font-medium text-amber-600">
                                                En revisión interna
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($editUrl && in_array($statusKey, ['draft', 'under_review', 'cancelled'], true)): ?>
                                            <a href="<?= htmlspecialchars($editUrl) ?>" class="btn inline-flex items-center justify-center rounded-md bg-copihue-600 px-3 py-2 text-sm font-medium text-white hover:bg-copihue-700 hover:text-white focus:text-white">
                                                Editar campaña
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($canAppeal): ?>
                                        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                            <h3 class="font-semibold text-amber-900">¿Necesitas apelar esta campaña?</h3>
                                            <?php if (in_array($appealStatus, ['pending', 'under_review'], true)): ?>
                                                <p class="mt-2">Tu apelación está en revisión. Te notificaremos cuando el equipo académico responda.</p>
                                                <p class="mt-1 text-xs text-amber-700">Enviada el <?= !empty($appealRecord['created_at']) ? date('d/m/Y H:i', strtotime($appealRecord['created_at'])) : '—' ?>.</p>
                                                <?php if (!empty($appealRecord['files'])): ?>
                                                    <div class="mt-2 rounded-md border border-amber-200 bg-white/60 p-3">
                                                        <p class="text-xs font-medium text-amber-900">Documentos adjuntos:</p>
                                                        <ul class="mt-1 space-y-1 text-xs text-amber-800">
                                                            <?php foreach ($appealRecord['files'] as $file): ?>
                                                                <li class="flex items-center justify-between gap-2">
                                                                    <span class="truncate" title="<?= htmlspecialchars($file['original_name'] ?? '') ?>"><?= htmlspecialchars($file['original_name'] ?? 'Documento') ?></span>
                                                                    <?php if (!empty($file['size_bytes'])): ?>
                                                                        <span class="text-[11px] text-amber-600"><?= number_format(($file['size_bytes'] / 1024), 1) ?> KB</span>
                                                                    <?php endif; ?>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>
                                                <?php endif; ?>
                                            <?php elseif ($appealStatus === 'approved'): ?>
                                                <p class="mt-2 text-emerald-700">Tu apelación fue aprobada. Revisa tu bandeja para seguir los próximos pasos.</p>
                                            <?php else: ?>
                                                <?php if (!empty($appealErrors['general'])): ?>
                                                    <div class="mt-2 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-red-700">
                                                        <?= htmlspecialchars($appealErrors['general']) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <form method="POST" action="<?= Router::url('campana/' . $campaignId . '/apelar') ?>" class="mt-2 space-y-3" novalidate enctype="multipart/form-data">
                                                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">
                                                    <div>
                                                        <label for="appeal-reason-<?= $campaignId ?>" class="block text-xs font-medium text-amber-900">Cuéntanos por qué debemos revisar el caso</label>
                                                        <textarea id="appeal-reason-<?= $campaignId ?>" name="reason" rows="3" required class="mt-1 w-full rounded-md border <?= isset($appealErrors['reason']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-amber-200 focus:border-amber-400 focus:ring-amber-400' ?> bg-white px-3 py-2 text-sm" placeholder="Describe la información adicional o correcciones realizadas."><?= htmlspecialchars($appealOldData['reason'] ?? '') ?></textarea>
                                                        <?php if (isset($appealErrors['reason'])): ?>
                                                            <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($appealErrors['reason']) ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <label for="appeal-notes-<?= $campaignId ?>" class="block text-xs font-medium text-amber-900">Notas adicionales (opcional)</label>
                                                        <textarea id="appeal-notes-<?= $campaignId ?>" name="additional_evidence" rows="2" class="mt-1 w-full rounded-md border border-amber-200 bg-white px-3 py-2 text-sm focus:border-amber-400 focus:ring-amber-400" placeholder="Enlaces o contexto adicional para el equipo revisor."><?= htmlspecialchars($appealOldData['additional_evidence'] ?? '') ?></textarea>
                                                    </div>
                                                    <div>
                                                        <label for="appeal-files-<?= $campaignId ?>" class="block text-xs font-medium text-amber-900">Documentos de respaldo (PDF o imágenes, máx. 5)</label>
                                                        <input id="appeal-files-<?= $campaignId ?>" name="evidence_files[]" type="file" multiple accept=".pdf,image/jpeg,image/png,image/webp" class="mt-1 block w-full text-xs text-amber-900 file:mr-3 file:rounded-md file:border-0 file:bg-amber-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-amber-800 hover:file:bg-amber-200" />
                                                        <p class="mt-1 text-xs text-amber-700">Adjunta recibos, certificados u otros documentos para respaldar tu apelación (máx. 8&nbsp;MB c/u).</p>
                                                        <?php if (isset($appealErrors['evidence_files'])): ?>
                                                            <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($appealErrors['evidence_files']) ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-xs text-amber-700">El equipo responderá dentro de 48 horas hábiles.</span>
                                                        <button type="submit" class="inline-flex items-center rounded-md bg-amber-600 px-3 py-2 text-sm font-medium text-white hover:bg-amber-700">
                                                            Enviar apelación
                                                        </button>
                                                    </div>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="mt-8">
                        <?= render_pagination([
                            'current_page' => $page,
                            'total_pages' => $totalPages,
                            'base_url' => Router::url('mis-campanas'),
                        ]); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>

    <?php include VIEWS_PATH . '/layouts/partials/footer.php'; ?>
</body>
</html>
