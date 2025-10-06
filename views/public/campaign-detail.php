<?php
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/cards.php';
require_once __DIR__ . '/../components/alerts.php';
require_once __DIR__ . '/../components/navigation.php';
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

if (!isset($campaign)) {
    http_response_code(404);
    include VIEWS_PATH . '/errors/404.php';
    return;
}

$page_title = htmlspecialchars($campaign['title']) . ' - Lucatón';
$page_description = htmlspecialchars($campaign['summary'] ?? substr($campaign['story'] ?? '', 0, 150));
$breadcrumbs = [
    ['name' => 'Inicio', 'href' => Router::url('/')],
    ['name' => 'Campañas', 'href' => Router::url('campanas')],
    ['name' => $campaign['title'], 'href' => Router::url('campana/' . ($campaign['slug'] ?? $campaign['id']))]
];

$stats = $stats ?? [
    'goal_amount' => (float)($campaign['goal_amount'] ?? 0),
    'raised_amount' => (float)($campaign['raised_amount'] ?? 0),
    'progress' => $campaign['progress'] ?? 0,
    'days_left' => $campaign['days_left'] ?? null,
    'donors' => (int)($campaign['donor_count'] ?? $campaign['donors'] ?? 0)
];

$recent_supporters = $recent_supporters ?? [];
$galleryMedia = $galleryMedia ?? [];
$campaignUpdates = $campaignUpdates ?? [];
$creatorProfileData = $creatorProfileData ?? [];
$canManageUpdates = $canManageUpdates ?? false;
$updateFormErrors = $updateFormErrors ?? [];
$rawUpdateFormOld = isset($updateFormOld) && is_array($updateFormOld) ? $updateFormOld : [];
$updateFormOld = array_merge([
    'title' => '',
    'body' => '',
    'media_urls' => []
], $rawUpdateFormOld);
$updateMediaUrls = $updateFormOld['media_urls'];
if (!is_array($updateMediaUrls)) {
    $updateMediaUrls = [];
}
$updateMediaUrls = array_map(static function ($value) {
    return trim((string)$value);
}, array_slice($updateMediaUrls, 0, 3));
$updateMediaUrls = array_pad($updateMediaUrls, 3, '');
$celebrationOverlay = $celebrationOverlay ?? null;
$campaignIdentifier = (string)($campaign['slug'] ?? $campaign['id'] ?? '');
$campaignUpdateAction = $campaignIdentifier !== ''
    ? Router::url('campana/' . rawurlencode($campaignIdentifier) . '/actualizaciones')
    : Router::url('campana/' . ($campaign['id'] ?? ''));

$image_url = $campaignImageUrl
    ?? CampaignMediaUploadService::normalizePublicUrl($campaign['image_url'] ?? ($campaign['cover_image_url'] ?? null))
    ?? APP_URL . '/public/assets/images/campaigns/escuela-rural.svg';
$status_meta = CampaignPresenter::statusMeta($campaign['status'] ?? 'draft');
$status_badge = [
    'class' => $status_meta['badge_class'],
    'text' => $status_meta['label']
];
$donationFormErrors = $donationFormErrors ?? [];
$donationFormOld = $donationFormOld ?? [];
$isUserAuthenticated = SessionHelper::isAuthenticated();
$ai_assisted_flag = isset($campaign['ai_assisted']) ? (bool)$campaign['ai_assisted'] : null;

$video_url = trim((string)($campaign['video_url'] ?? ''));
$resolveYoutubeEmbed = static function (string $url): ?string {
    if ($url === '') {
        return null;
    }

    $patterns = [
        '/youtu\.be\/([^?&#\/]+)/i',
        '/v=([^&]+)/i',
        '/embed\/([^?&#\/]+)/i',
        '/shorts\/([^?&#\/]+)/i',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }
    }

    return null;
};

$video_embed_url = $resolveYoutubeEmbed($video_url);
$donationOld = array_merge([
    'amount' => '5000',
    'donor_name' => '',
    'donor_email' => '',
    'message' => '',
    'payment_method' => 'manual',
    'is_anonymous' => '0',
], $donationFormOld);
$donationAmountValue = preg_replace('/[^0-9]/', '', $donationOld['amount'] ?? '') ?: '5000';
$donationIsAnonymous = ($donationOld['is_anonymous'] ?? '0') === '1';
$donationPaymentMethod = $donationOld['payment_method'] ?? 'manual';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">

    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo Router::url('campana/' . ($campaign['slug'] ?? $campaign['id'])); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($campaign['title']); ?>">
    <meta property="og:description" content="<?php echo $page_description; ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($image_url); ?>">

    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/public/assets/images/favicon.svg">
    <link href="<?= APP_URL ?>/public/assets/css/app.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/public/assets/css/aliases.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <?php include __DIR__ . '/../layouts/partials/header.php'; ?>

    <main id="main-content" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <?php echo render_breadcrumb($breadcrumbs); ?>
        <?php include_flash_messages(); ?>

        <?php if (!empty($preview_mode) && $preview_mode && !empty($preview_notice)): ?>
            <div class="mt-6 mb-8">
                <?php echo render_alert([
                    'type' => $preview_notice['tone'] ?? 'info',
                    'title' => $preview_notice['title'] ?? 'Vista privada',
                    'message' => $preview_notice['message'] ?? 'Esta campaña aún no está disponible públicamente.',
                    'dismissible' => false,
                    'class' => 'shadow-soft border rounded-2xl'
                ]); ?>
            </div>
        <?php endif; ?>

        <div class="grid lg:grid-cols-3 lg:gap-10">
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white shadow-soft rounded-3xl overflow-hidden">
                    <div class="relative">
                        <img src="<?php echo htmlspecialchars($image_url); ?>" alt="Imagen de la campaña <?php echo htmlspecialchars($campaign['title']); ?>" class="w-full h-80 object-cover">
                        <div class="absolute top-4 left-4 space-y-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-white/80 text-gray-800">
                                <?php echo htmlspecialchars($campaign['category_name'] ?? ''); ?>
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?php echo $status_badge['class']; ?>">
                                <?php echo $status_badge['text']; ?>
                            </span>
                            <?php if ($ai_assisted_flag === true): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700 shadow-sm">
                                    IA asistida
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="absolute top-4 right-4 flex space-x-2">
                            <?php
                                $campaignSlug = $campaign['slug'] ?? $campaign['id'] ?? '';
                                $sharePayload = [
                                    'slug' => $campaignSlug,
                                    'title' => $campaign['title'] ?? 'Campaña Lucatón'
                                ];
                                $shareEncoded = htmlspecialchars(json_encode($sharePayload, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                $favoritePayload = [
                                    'id' => $campaign['id'] ?? $campaignSlug,
                                    'slug' => $campaignSlug,
                                    'title' => $campaign['title'] ?? 'Campaña Lucatón'
                                ];
                                $favoriteEncoded = htmlspecialchars(json_encode($favoritePayload, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                $favoriteIdAttr = htmlspecialchars((string)($campaign['id'] ?? $campaignSlug), ENT_QUOTES, 'UTF-8');
                            ?>
                            <button class="btn-ghost" title="Compartir" onclick="shareCampaign(<?= $shareEncoded ?>)">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
                                </svg>
                            </button>
                            <button class="btn-ghost" data-favorite-button data-favorite-id="<?= $favoriteIdAttr ?>" title="Guardar campaña" onclick="toggleFavorite(event, <?= $favoriteEncoded ?>)" aria-pressed="false">
                                <svg class="w-5 h-5 transition-colors" data-favorite-icon fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path data-favorite-path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <h1 class="text-3xl font-bold text-gray-900 leading-tight">
                            <?php echo htmlspecialchars($campaign['title']); ?>
                        </h1>
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <button type="button" data-creator-profile-trigger class="flex items-center gap-3 rounded-2xl bg-gray-100 px-4 py-3 text-left transition hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-copihue-500">
                                <div class="h-10 w-10 rounded-full bg-copihue-500 text-white flex items-center justify-center font-semibold">
                                    <?php
                                    $creatorName = $creatorProfileData['name'] ?? $campaign['creator_name'] ?? 'Usuario';
                                    $initials = 'U';
                                    if ($creatorName !== '') {
                                        $initials = strtoupper(mb_substr($creatorName, 0, 1));
                                    }
                                    if (!empty($creatorProfileData['avatar'])):
                                    ?>
                                        <img src="<?= htmlspecialchars($creatorProfileData['avatar']) ?>" alt="Avatar de <?= htmlspecialchars($creatorName) ?>" class="h-10 w-10 rounded-full object-cover">
                                    <?php else: ?>
                                        <?= htmlspecialchars($initials) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="space-y-1">
                                    <span class="flex items-center gap-2 text-sm font-semibold text-gray-900">
                                        <?= htmlspecialchars($creatorName) ?>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">
                                            <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                            Perfil verificado
                                        </span>
                                    </span>
                                    <span class="flex items-center gap-2 text-xs text-gray-500">
                                        <svg class="h-4 w-4 text-copihue-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 2a6 6 0 00-6 6c0 4.418 6 10 6 10s6-5.582 6-10a6 6 0 00-6-6zm0 8a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                                        Campaña verificada por administradores e IA
                                    </span>
                                </div>
                            </button>
                            <?php if (!empty($campaign['location_label'])): ?>
                                <div class="flex items-center space-x-2 text-sm text-gray-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.657 0 3-1.343 3-3S13.657 5 12 5 9 6.343 9 8s1.343 3 3 3z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22s8-4.5 8-13a8 8 0 10-16 0c0 8.5 8 13 8 13z" />
                                    </svg>
                                    <span><?php echo htmlspecialchars($campaign['location_label'] ?? ''); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <p class="text-gray-600 text-lg leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($campaign['story'] ?? '')); ?>
                        </p>
                    </div>
                </div>

                <?php if ($video_embed_url): ?>
                    <section class="bg-white shadow-soft rounded-3xl p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Video de la campaña</h2>
                        <div class="relative w-full overflow-hidden rounded-2xl bg-black" style="padding-bottom: 56.25%;">
                            <iframe
                                src="<?= htmlspecialchars($video_embed_url) ?>"
                                title="Video de la campaña <?= htmlspecialchars($campaign['title']) ?>"
                                class="absolute inset-0 h-full w-full"
                                frameborder="0"
                                allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"
                                allowfullscreen
                            ></iframe>
                        </div>
                        <p class="mt-3 text-sm text-gray-500">El responsable de la campaña compartió este video para complementar la historia.</p>
                    </section>
                <?php endif; ?>

                <?php if (!empty($galleryMedia)): ?>
                    <section class="bg-white shadow-soft rounded-3xl p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Galería de imágenes</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($galleryMedia as $index => $media): ?>
                                <figure class="relative overflow-hidden rounded-2xl bg-gray-100 shadow-sm">
                                    <button type="button" data-gallery-trigger data-gallery-index="<?= $index ?>" class="block w-full focus:outline-none focus:ring-2 focus:ring-copihue-500">
                                        <img src="<?= htmlspecialchars($media['url']) ?>" alt="Imagen <?= $index + 1 ?> de la campaña <?= htmlspecialchars($campaign['title']) ?>" class="h-48 w-full object-cover transition duration-300 hover:scale-105">
                                    </button>
                                    <?php if (!empty($media['caption'])): ?>
                                        <figcaption class="px-4 py-3 text-sm text-gray-600 bg-white/90">
                                            <?= htmlspecialchars($media['caption']) ?>
                                        </figcaption>
                                    <?php endif; ?>
                                </figure>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <section id="actualizaciones" class="bg-white shadow-soft rounded-3xl p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Actualizaciones de la campaña</h2>
                            <p class="text-xs text-gray-500 mt-1 flex items-center gap-1.5">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6 2a1 1 0 011 1v1h6V3a1 1 0 112 0v1h1a2 2 0 012 2v2H3V6a2 2 0 012-2h1V3a1 1 0 011-1z" /><path d="M3 9h14v5a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /></svg>
                                Comparte avances y transparencia con tu comunidad.
                            </p>
                        </div>
                        <?php if ($canManageUpdates): ?>
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                Eres el responsable de la campaña
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($canManageUpdates): ?>
                        <div class="mb-8 rounded-2xl border border-emerald-100 bg-emerald-50/70 p-5" id="actualizaciones-form">
                            <h3 class="text-sm font-semibold text-emerald-900">Publica una nueva actualización</h3>
                            <p class="mt-1 text-xs text-emerald-800">Informa avances, comparte fotos o agradece a tu comunidad. Nos encargamos de notificar a tus seguidores.</p>

                            <?php if (!empty($updateFormErrors)): ?>
                                <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4">
                                    <p class="text-sm font-semibold text-red-700">Revisa los siguientes puntos antes de publicar:</p>
                                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-600">
                                        <?php foreach ($updateFormErrors as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="<?= htmlspecialchars($campaignUpdateAction) ?>" class="mt-4 space-y-4">
                                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">
                                <div>
                                    <label for="campaign-update-title" class="block text-xs font-semibold text-emerald-900 uppercase tracking-wide">Título (opcional)</label>
                                    <input
                                        type="text"
                                        name="title"
                                        id="campaign-update-title"
                                        maxlength="150"
                                        value="<?= htmlspecialchars($updateFormOld['title']) ?>"
                                        class="mt-1 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2 text-sm text-emerald-900 shadow-inner focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-300"
                                        placeholder="Ej: Semana 3 · Ya superamos el 75%"
                                    >
                                </div>
                                <div>
                                    <label for="campaign-update-body" class="block text-xs font-semibold text-emerald-900 uppercase tracking-wide">Mensaje para tu comunidad</label>
                                    <textarea
                                        name="body"
                                        id="campaign-update-body"
                                        rows="4"
                                        maxlength="5000"
                                        class="mt-1 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2 text-sm text-emerald-900 shadow-inner focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-300"
                                        placeholder="Comparte avances, próximas actividades o agradece a quienes apoyan tu causa."><?= htmlspecialchars($updateFormOld['body']) ?></textarea>
                                    <p class="mt-1 text-xs text-emerald-700">Consejo: Sé claro y agradece. Los enlaces se convierten automáticamente en hipervínculos.</p>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-3">
                                    <?php foreach ($updateMediaUrls as $mediaIndex => $mediaUrl): ?>
                                        <div>
                                            <label class="block text-xs font-semibold text-emerald-900 uppercase tracking-wide">Enlace multimedia <?= $mediaIndex + 1 ?> (opcional)</label>
                                            <input
                                                type="url"
                                                name="media_urls[]"
                                                value="<?= htmlspecialchars($mediaUrl) ?>"
                                                class="mt-1 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2 text-sm text-emerald-900 shadow-inner focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-300"
                                                placeholder="https://..."
                                            >
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="text-xs text-emerald-700 flex items-center gap-2">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v8m4-4H8" />
                                        </svg>
                                        Puedes agregar hasta tres enlaces de imágenes, videos o documentos públicos.
                                    </p>
                                    <button type="submit" class="inline-flex items-center justify-center rounded-full bg-emerald-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                                        Publicar actualización
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($campaignUpdates)): ?>
                        <div class="space-y-6">
                            <?php foreach ($campaignUpdates as $index => $update): ?>
                                <?php
                                $updateId = $update['id'] ?? ('update-' . ($campaign['id'] ?? 'campaign') . '-' . $index);
                                $updateHeartCount = (int)($update['heart_count'] ?? 0);
                                $publishedAt = $update['published_at'] ?? $update['created_at'] ?? null;
                                ?>
                                <article class="rounded-2xl border border-gray-200 p-4 space-y-3">
                                    <header class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($update['title'] ?? 'Actualización') ?></h3>
                                            <?php if ($canManageUpdates && ($update['status'] ?? 'published') !== 'published'): ?>
                                                <span class="mt-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">
                                                    Estado: <?= htmlspecialchars(ucfirst($update['status'] ?? 'borrador')) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($publishedAt)): ?>
                                            <time datetime="<?= htmlspecialchars($publishedAt) ?>" class="text-xs text-gray-500">
                                                <?= date('d/m/Y H:i', strtotime($publishedAt)) ?>
                                            </time>
                                        <?php endif; ?>
                                    </header>
                                    <p class="text-sm text-gray-600 leading-relaxed"><?= nl2br(htmlspecialchars($update['body'] ?? '')) ?></p>
                                    <?php if (!empty($update['media'])): ?>
                                        <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                            <?php foreach ($update['media'] as $mediaItem): ?>
                                                <?php if (!empty($mediaItem['url'])): ?>
                                                    <a href="<?= htmlspecialchars($mediaItem['url']) ?>" target="_blank" rel="noopener noreferrer" class="group relative block overflow-hidden rounded-xl border border-gray-200">
                                                        <span class="absolute inset-0 bg-gradient-to-t from-black/40 via-black/10 to-transparent opacity-0 transition group-hover:opacity-100"></span>
                                                        <img src="<?= htmlspecialchars($mediaItem['url']) ?>" alt="Imagen de actualización" class="h-28 w-full object-cover transition group-hover:scale-105" loading="lazy">
                                                        <?php if (!empty($mediaItem['caption'])): ?>
                                                            <span class="absolute bottom-2 left-2 right-2 rounded-lg bg-black/60 px-2 py-1 text-[11px] font-medium text-white">
                                                                <?= htmlspecialchars($mediaItem['caption']) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </a>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex items-center justify-between pt-2">
                                        <span class="text-xs text-gray-500">Comparte tus logros con la comunidad</span>
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-white px-3 py-1 text-xs font-semibold text-rose-500 transition hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-400"
                                                data-update-heart
                                                data-update-id="<?= htmlspecialchars($updateId) ?>"
                                                data-update-initial="<?= $updateHeartCount ?>">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" data-update-heart-icon>
                                                    <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" data-update-heart-path></path>
                                                </svg>
                                                <span class="sr-only">Enviar corazón de apoyo</span>
                                            </button>
                                            <span class="text-xs text-gray-500" data-update-heart-count><?= $updateHeartCount ?></span>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-4 py-6 text-center text-sm text-gray-600">
                            <p class="font-medium text-gray-700">Aún no hay publicaciones de avance.</p>
                            <p class="mt-1">Pronto verás hitos, fotografías y mensajes del equipo de la campaña.</p>
                            <?php if ($canManageUpdates): ?>
                                <p class="mt-4 text-xs text-gray-500">Comienza publicando un resumen de la situación actual para motivar a más personas a donar.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="bg-white shadow-soft rounded-3xl p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Sobre la campaña</h2>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="font-medium text-gray-500">Creada por</dt>
                            <dd class="text-gray-900">
                                <button type="button" data-creator-profile-trigger class="inline-flex items-center gap-2 rounded-full border border-gray-200 px-3 py-1 text-xs font-semibold text-copihue-600 hover:bg-copihue-50 focus:outline-none focus:ring-2 focus:ring-copihue-500">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 3a3 3 0 110 6 3 3 0 010-6z" /><path fill-rule="evenodd" d="M3 14s1-4 7-4 7 4 7 4-1 4-7 4-7-4-7-4z" clip-rule="evenodd" /></svg>
                                    <?= htmlspecialchars($campaign['creator_name']); ?>
                                </button>
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Beneficiario</dt>
                            <dd class="text-gray-900"><?php echo htmlspecialchars($campaign['beneficiary_name'] ?? 'Por definir'); ?></dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Meta económica</dt>
                            <dd class="text-gray-900">$<?php echo number_format($stats['goal_amount'], 0, ',', '.'); ?></dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Monto recaudado</dt>
                            <dd class="text-gray-900">$<?php echo number_format($stats['raised_amount'], 0, ',', '.'); ?></dd>
                        </div>
                        <?php if ($ai_assisted_flag !== null): ?>
                            <div>
                                <dt class="font-medium text-gray-500">Edición asistida por IA</dt>
                                <dd class="text-gray-900">
                                    <?= $ai_assisted_flag ? 'Sí, se utilizaron herramientas de IA supervisadas.' : 'No, el responsable indicó edición totalmente manual.' ?>
                                </dd>
                            </div>
                        <?php endif; ?>
                    </dl>
                </section>

                <?php if (!empty($recent_supporters)): ?>
                    <section class="bg-white shadow-soft rounded-3xl p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Últimos aportes</h2>
                        <ul class="space-y-4">
                            <?php foreach ($recent_supporters as $supporter): ?>
                                <li class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            <?php
                                            $name = trim(($supporter['first_name'] ?? '') . ' ' . ($supporter['last_name'] ?? ''));
                                            if ($name === '') {
                                                $name = $supporter['donor_name'] ?? $supporter['username'] ?? 'Donador anónimo';
                                            }
                                            echo htmlspecialchars($name);
                                            ?>
                                        </p>
                                        <?php if (!empty($supporter['message'])): ?>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($supporter['message']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-gray-900">$<?php echo number_format($supporter['amount'] ?? 0, 0, ',', '.'); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo date('d M Y', strtotime($supporter['created_at'] ?? 'now')); ?></p>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>
            </div>

            <aside class="space-y-6">
                <div class="bg-white shadow-soft rounded-3xl p-6 space-y-6">
                    <div class="space-y-3">
                        <p class="text-3xl font-semibold text-gray-900">$<?php echo number_format($stats['raised_amount'], 0, ',', '.'); ?></p>
                        <p class="text-sm text-gray-500">de una meta de $<?php echo number_format($stats['goal_amount'], 0, ',', '.'); ?></p>
                        <div class="h-2 w-full bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-2 bg-gradient-to-r from-copihue-500 to-copihue-600" style="width: <?php echo min(100, $stats['progress']); ?>%"></div>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span><?php echo $stats['progress']; ?>% alcanzado</span>
                            <span><?php echo (int)($stats['donors'] ?? 0); ?> aportes</span>
                        </div>
                        <?php if ($stats['days_left'] !== null): ?>
                            <p class="text-sm text-gray-600"><?php echo max(0, (int)$stats['days_left']); ?> días restantes</p>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-4" id="donar">
                        <?php if (isset($donationFormErrors['general'])): ?>
                            <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                <?= htmlspecialchars($donationFormErrors['general']) ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST" action="<?= Router::url('api/donate/' . ($campaign['id'] ?? 0)) ?>" class="space-y-4" novalidate>
                            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">

                            <div>
                                <label for="donation-amount" class="block text-sm font-medium text-gray-700">Monto del aporte</label>
                                <input id="donation-amount" name="amount" type="number" min="1000" step="500" required value="<?= htmlspecialchars($donationAmountValue) ?>" class="mt-1 w-full rounded-md border <?= isset($donationFormErrors['amount']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm">
                                <?php if (isset($donationFormErrors['amount'])): ?>
                                    <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($donationFormErrors['amount']) ?></p>
                                <?php else: ?>
                                    <p class="mt-1 text-xs text-gray-500">Aportes simulados desde $1.000 CLP.</p>
                                <?php endif; ?>
                            </div>

                            <?php if (!$isUserAuthenticated): ?>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="donation-name" class="block text-sm font-medium text-gray-700">Tu nombre</label>
                                        <input id="donation-name" name="donor_name" type="text" required value="<?= htmlspecialchars($donationOld['donor_name'] ?? '') ?>" class="mt-1 w-full rounded-md border <?= isset($donationFormErrors['donor_name']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm">
                                        <?php if (isset($donationFormErrors['donor_name'])): ?>
                                            <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($donationFormErrors['donor_name']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <label for="donation-email" class="block text-sm font-medium text-gray-700">Correo electrónico</label>
                                        <input id="donation-email" name="donor_email" type="email" required value="<?= htmlspecialchars($donationOld['donor_email'] ?? '') ?>" class="mt-1 w-full rounded-md border <?= isset($donationFormErrors['donor_email']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm">
                                        <?php if (isset($donationFormErrors['donor_email'])): ?>
                                            <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($donationFormErrors['donor_email']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div>
                                <label for="donation-payment-method" class="block text-sm font-medium text-gray-700">Método de aporte</label>
                                <select id="donation-payment-method" name="payment_method" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500">
                                    <?php
                                    $paymentOptions = [
                                        'manual' => 'Transferencia simulada',
                                        'credit_card' => 'Tarjeta de crédito (demo)',
                                        'debit_card' => 'Tarjeta de débito (demo)',
                                        'bank_transfer' => 'Transferencia bancaria',
                                        'paypal' => 'PayPal (demo)',
                                        'webpay' => 'Webpay (demo)'
                                    ];
                                    foreach ($paymentOptions as $method => $label):
                                    ?>
                                        <option value="<?= htmlspecialchars($method) ?>" <?= $donationPaymentMethod === $method ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="donation-message" class="block text-sm font-medium text-gray-700">Mensaje para la campaña (opcional)</label>
                                <textarea id="donation-message" name="message" rows="3" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500" placeholder="Comparte unas palabras de apoyo."><?= htmlspecialchars($donationOld['message'] ?? '') ?></textarea>
                                <?php if (isset($donationFormErrors['message'])): ?>
                                    <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($donationFormErrors['message']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="flex items-center gap-2">
                                <input id="donation-anonymous" name="is_anonymous" type="checkbox" value="1" <?= $donationIsAnonymous ? 'checked' : '' ?> class="h-4 w-4 rounded border-gray-300 text-copihue-600 focus:ring-copihue-500">
                                <label for="donation-anonymous" class="text-sm text-gray-700">Prefiero que mi aporte aparezca como anónimo</label>
                            </div>

                            <button type="submit" class="w-full inline-flex items-center justify-center rounded-md bg-copihue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-copihue-700">
                                Registrar aporte simulado
                            </button>
                        </form>
                        <p class="text-xs text-gray-500 text-center">
                            Las donaciones se registran de forma simulada para fines académicos.
                        </p>
                    </div>
                </div>

                <div class="bg-white shadow-soft rounded-3xl p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">Comparte esta campaña</h2>
                    <div class="space-y-2">
                        <button type="button" class="btn-outline w-full" onclick="shareCampaign(<?= $shareEncoded ?>)">Compartir campaña</button>
                        <a class="btn-outline w-full" target="_blank" rel="noopener noreferrer" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(Router::url('campana/' . ($campaign['slug'] ?? $campaign['id']))); ?>">Compartir en Facebook</a>
                        <a class="btn-outline w-full" target="_blank" rel="noopener noreferrer" href="https://twitter.com/intent/tweet?url=<?php echo urlencode(Router::url('campana/' . ($campaign['slug'] ?? $campaign['id']))); ?>&text=<?php echo urlencode($campaign['title']); ?>">Compartir en X</a>
                    </div>
                </div>

            </aside>
        </div>
    </main>

    <?php if (!empty($celebrationOverlay)): ?>
        <?php
        $overlayData = $celebrationOverlay;
        include __DIR__ . '/../components/celebration-overlay.php';
        ?>
    <?php endif; ?>

    <div class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/80 px-4" data-gallery-lightbox aria-hidden="true" tabindex="-1">
        <button type="button" class="absolute inset-0 h-full w-full cursor-default" data-gallery-close tabindex="-1" aria-label="Cerrar galería"></button>
        <div class="relative w-full max-w-4xl space-y-4 rounded-3xl bg-white p-6 shadow-strong">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-500">Galería de la campaña</h3>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500" data-gallery-counter></span>
                    <button type="button" class="rounded-full bg-gray-100 p-2 text-gray-500 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-copihue-500" data-gallery-close>
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l8 8M6 14L14 6" stroke-linecap="round" /></svg>
                        <span class="sr-only">Cerrar</span>
                    </button>
                </div>
            </div>
            <div class="relative overflow-hidden rounded-2xl bg-gray-100">
                <img src="" alt="" data-gallery-current-image class="max-h-[70vh] w-full object-contain">
                <button type="button" class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-white/80 p-2 text-gray-700 shadow hover:bg-white focus:outline-none focus:ring-2 focus:ring-copihue-500" data-gallery-prev>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5l-5 5 5 5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    <span class="sr-only">Imagen anterior</span>
                </button>
                <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-white/80 p-2 text-gray-700 shadow hover:bg-white focus:outline-none focus:ring-2 focus:ring-copihue-500" data-gallery-next>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 5l5 5-5 5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    <span class="sr-only">Imagen siguiente</span>
                </button>
            </div>
            <p class="text-sm text-gray-600" data-gallery-current-caption></p>
        </div>
    </div>

    <div class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/70 px-4" data-creator-profile-modal aria-hidden="true" tabindex="-1">
        <button type="button" class="absolute inset-0 h-full w-full cursor-default" data-creator-profile-close tabindex="-1" aria-label="Cerrar perfil"></button>
        <div class="relative w-full max-w-md space-y-4 rounded-3xl bg-white p-6 shadow-strong">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-500">Perfil del creador</h3>
                <button type="button" class="rounded-full bg-gray-100 p-2 text-gray-500 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-copihue-500" data-creator-profile-close>
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l8 8M6 14L14 6" stroke-linecap="round" /></svg>
                    <span class="sr-only">Cerrar perfil</span>
                </button>
            </div>
            <div class="flex items-center gap-3">
                <div class="h-14 w-14 rounded-full bg-copihue-500 text-white flex items-center justify-center font-semibold text-lg" data-creator-profile-avatar></div>
                <div class="space-y-1">
                    <p class="text-base font-semibold text-gray-900" data-creator-profile-name></p>
                    <p class="text-xs text-gray-500 flex items-center gap-2">
                        <svg class="h-3.5 w-3.5 text-emerald-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 00-1.414 0L8 12.586 4.707 9.293a1 1 0 00-1.414 1.414l4 4a1 1 0 001.414 0l8-8a1 1 0 000-1.414z" clip-rule="evenodd" /></svg>
                        Usuario verificado en Lucatón
                    </p>
                    <p class="text-xs text-gray-500" data-creator-profile-username></p>
                </div>
            </div>
            <p class="text-xs text-gray-500">Respetamos la privacidad del creador. Solo compartimos información pública autorizada.</p>
        </div>
    </div>

    <?php
    ob_start();
    ?>
    <script>
        window.__campaignGallery = <?= json_encode($galleryMedia, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        window.__creatorProfile = <?= json_encode($creatorProfileData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <?php
    $additional_scripts = ($additional_scripts ?? '') . ob_get_clean();
    ?>

    <?php include __DIR__ . '/../layouts/partials/footer.php'; ?>
</body>
</html>
