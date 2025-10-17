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

$campaignPublicPath = $campaign['public_path'] ?? CampaignPresenter::buildPublicPath($campaign);
if ($campaignPublicPath === null) {
    $fallbackIdentifier = (string)($campaign['slug'] ?? $campaign['id'] ?? '');
    if ($fallbackIdentifier !== '') {
        $campaignPublicPath = 'campana/' . rawurlencode($fallbackIdentifier);
    }
}
$campaignPublicUrl = $campaignPublicPath !== null ? Router::url($campaignPublicPath) : Router::url('campanas');

$page_title = htmlspecialchars($campaign['title']) . ' - Lucatón';
$page_description = htmlspecialchars($campaign['summary'] ?? substr($campaign['story'] ?? '', 0, 150));
$breadcrumbs = [
    ['name' => 'Inicio', 'href' => Router::url('/')],
    ['name' => 'Campañas', 'href' => Router::url('campanas')],
    ['name' => $campaign['title'], 'href' => $campaignPublicUrl]
];

$statusSlug = strtolower((string)($campaign['status'] ?? 'draft'));

$stats = $stats ?? [
    'goal_amount' => (float)($campaign['goal_amount'] ?? 0),
    'raised_amount' => (float)($campaign['raised_amount'] ?? 0),
    'progress' => $campaign['progress'] ?? 0,
    'days_left' => $campaign['days_left'] ?? null,
    'donors' => (int)($campaign['donor_count'] ?? $campaign['donors'] ?? 0)
];
$finalUpdateAllowed = $finalUpdateAllowed ?? false;
$campaignFinalLocked = $campaignFinalLocked ?? false;

$campaign_goal_reached = !empty($campaign['goal_reached']);
if (!$campaign_goal_reached && ($stats['goal_amount'] ?? 0) > 0) {
    $campaign_goal_reached = ($stats['raised_amount'] ?? 0) >= ($stats['goal_amount'] ?? 0);
}

$campaign_end_timestamp = $campaign['end_timestamp'] ?? null;
if ($campaign_end_timestamp === null && !empty($campaign['end_date'])) {
    $parsedEnd = strtotime((string)$campaign['end_date']);
    if ($parsedEnd !== false) {
        $campaign_end_timestamp = $parsedEnd;
    }
}
$campaign_time_over = !empty($campaign['time_over']);
if (!$campaign_time_over && $campaign_end_timestamp !== null) {
    $campaign_time_over = $campaign_end_timestamp <= time();
}

$acceptingDonations = in_array($statusSlug, ['published', 'paused'], true)
    && !$campaign_goal_reached
    && !$campaign_time_over;

$recent_supporters = $recent_supporters ?? [];
$recent_supporters = array_slice($recent_supporters, 0, 10);
$donationSidebarItems = array_slice($recent_supporters, 0, 5);
$galleryMedia = $galleryMedia ?? [];
$campaignUpdates = $campaignUpdates ?? [];
$creatorProfileData = $creatorProfileData ?? [];
$canManageUpdates = $canManageUpdates ?? false;
$updatesLockedByStatus = isset($updatesLockedByStatus) ? (bool)$updatesLockedByStatus : false;
$updateFormErrors = $updateFormErrors ?? [];
$rawUpdateFormOld = isset($updateFormOld) && is_array($updateFormOld) ? $updateFormOld : [];
$updateFormOld = array_merge([
    'title' => '',
    'body' => '',
    'social_links' => [],
    'media_urls' => [],
], $rawUpdateFormOld);
$updateSocialLinks = $updateFormOld['social_links'];
if (!is_array($updateSocialLinks) || empty($updateSocialLinks)) {
    $updateSocialLinks = $updateFormOld['media_urls'] ?? [];
}
if (!is_array($updateSocialLinks)) {
    $updateSocialLinks = [];
}
$updateSocialLinks = array_map(static function ($value) {
    return trim((string)$value);
}, array_slice($updateSocialLinks, 0, 3));
$updateSocialLinks = array_pad($updateSocialLinks, 3, '');
$celebrationOverlay = $celebrationOverlay ?? null;
$campaignIdentifier = (string)($campaign['slug'] ?? $campaign['id'] ?? '');
$campaignUpdatePath = null;
if ($campaignPublicPath !== null) {
    $campaignUpdatePath = rtrim($campaignPublicPath, '/') . '/actualizaciones';
} elseif ($campaignIdentifier !== '') {
    $campaignUpdatePath = 'campana/' . rawurlencode($campaignIdentifier) . '/actualizaciones';
}
$campaignUpdateAction = $campaignUpdatePath !== null ? Router::url($campaignUpdatePath) : Router::url('campanas');

$image_url = $campaignImageUrl
    ?? CampaignMediaUploadService::normalizePublicUrl($campaign['image_url'] ?? ($campaign['cover_image_url'] ?? null))
    ?? APP_URL . '/public/assets/images/campaigns/escuela-rural.svg';
$status_meta = CampaignPresenter::statusMeta($statusSlug);
$status_badge = [
    'class' => $status_meta['badge_class'],
    'text' => $status_meta['label']
];

if ($statusSlug === 'completed') {
    if ($campaign_goal_reached) {
    $status_badge = [
        'class' => 'bg-emerald-100 text-emerald-700',
        'text' => 'Meta alcanzada'
    ];
    } elseif ($campaign_time_over) {
        $status_badge = [
            'class' => 'bg-amber-100 text-amber-700',
            'text' => 'Meta no alcanzada'
        ];
    }
}
$donationFormErrors = $donationFormErrors ?? [];
$donationFormOld = $donationFormOld ?? [];
$isUserAuthenticated = SessionHelper::isAuthenticated();
$currentUser = SessionHelper::getUser();
$donorAccountName = '';
$donorAccountEmail = '';

if (is_array($currentUser)) {
    $nameParts = array_filter([
        $currentUser['first_name'] ?? null,
        $currentUser['last_name'] ?? null,
    ]);
    $donorAccountName = trim(implode(' ', $nameParts));
    if ($donorAccountName === '' && !empty($currentUser['name'])) {
        $donorAccountName = trim((string)$currentUser['name']);
    }

    $donorAccountEmail = trim((string)($currentUser['email'] ?? ''));
}

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
    'amount' => '1000',
    'donor_name' => '',
    'donor_email' => '',
    'message' => '',
    'payment_method' => 'manual',
    'is_anonymous' => '0',
    'payment_fields' => [],
], $donationFormOld);
$donationAmountRaw = preg_replace('/[^0-9]/', '', $donationOld['amount'] ?? '');
if ($donationAmountRaw === '') {
    $donationAmountRaw = '1000';
}
$donationAmountDisplay = number_format((int)$donationAmountRaw, 0, ',', '.');
$donationIsAnonymous = ($donationOld['is_anonymous'] ?? '0') === '1';
$donationPaymentMethod = $donationOld['payment_method'] ?? 'manual';
if ($donationPaymentMethod === 'manual') {
    $donationPaymentMethod = 'bank_transfer';
}
$defaultPaymentFields = [
    'card_holder' => '',
    'card_number' => '',
    'card_expiration' => '',
    'card_cvv' => '',
    'transfer_bank' => '',
    'transfer_reference' => '',
    'paypal_email' => '',
    'webpay_rut' => '',
    'webpay_bank' => '',
];
$donationPaymentFields = array_merge($defaultPaymentFields, is_array($donationOld['payment_fields'] ?? null) ? $donationOld['payment_fields'] : []);
$paymentSectionVisibility = [
    'card' => in_array($donationPaymentMethod, ['credit_card', 'debit_card'], true),
    'transfer' => in_array($donationPaymentMethod, ['bank_transfer', 'manual'], true),
    'paypal' => $donationPaymentMethod === 'paypal',
    'webpay' => $donationPaymentMethod === 'webpay',
];
$webpayBanks = [
    'BancoEstado',
    'Banco de Chile',
    'BCI',
    'Scotiabank',
    'Santander',
    'Itaú',
];
$campaignShareSlug = $campaign['slug'] ?? $campaign['id'] ?? '';
$donationRedirectTarget = $campaignPublicUrl . '#donar';
$campaignDonationsUrl = $campaignPublicPath !== null
    ? Router::url(rtrim($campaignPublicPath, '/') . '/donaciones')
    : Router::url('campana/' . $campaignShareSlug . '/donaciones');
$loginRedirectUrl = Router::url('login') . '?redirect=' . urlencode($donationRedirectTarget);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">

    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $campaignPublicUrl; ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($campaign['title']); ?>">
    <meta property="og:description" content="<?php echo $page_description; ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($image_url); ?>">

    <link rel="icon" type="image/svg+xml" href="<?= asset_url('images/favicon.svg') ?>">
    <link href="<?= asset_url('css/app.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('css/aliases.css') ?>" rel="stylesheet">
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
                                    'url' => $campaignPublicUrl,
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
                            <button class="btn-ghost" title="Compartir" onclick="shareCampaign(this, <?= $shareEncoded ?>)">
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
                                    <button
                                        type="button"
                                        data-gallery-trigger
                                        data-gallery-index="<?= $index ?>"
                                        data-gallery-url="<?= htmlspecialchars($media['url']) ?>"
                                        data-gallery-caption="<?= htmlspecialchars($media['caption'] ?? '') ?>"
                                        class="block w-full focus:outline-none focus:ring-2 focus:ring-copihue-500"
                                    >
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

                    <?php if ($finalUpdateAllowed): ?>
                        <div class="mb-8 rounded-2xl border border-amber-200 bg-amber-50/70 p-5">
                            <h3 class="text-sm font-semibold text-amber-900">Tu mensaje de cierre</h3>
                            <p class="mt-1 text-xs text-amber-800">La campaña está finalizada y esta es tu última actualización. Agradece a quienes apoyaron y cuéntales cómo cerrarás el proceso.</p>
                        </div>
                    <?php endif; ?>

                    <?php if ($campaignFinalLocked): ?>
                        <div class="mb-8 rounded-2xl border border-gray-200 bg-gray-50 p-5">
                            <h3 class="text-sm font-semibold text-gray-800">Campaña cerrada</h3>
                            <p class="mt-1 text-xs text-gray-600">Esta campaña ya no acepta donaciones ni nuevas actualizaciones. Si necesitas seguir recibiendo apoyo, puedes iniciar una nueva campaña con información actualizada.</p>
                            <div class="mt-3">
                                <a href="<?= Router::url('campana/crear'); ?>" class="inline-flex items-center gap-2 rounded-full border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100">
                                    Crear nueva campaña
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($updatesLockedByStatus): ?>
                        <div class="mb-8 rounded-2xl border border-sky-200 bg-sky-50 p-5">
                            <h3 class="text-sm font-semibold text-sky-900">Actualizaciones deshabilitadas</h3>
                            <p class="mt-1 text-xs text-sky-800">Tu campaña aún está en revisión. Cuando el equipo de Lucatón la apruebe podrás publicar novedades con fotos y redes sociales desde aquí.</p>
                        </div>
                    <?php endif; ?>

                    <?php if ($canManageUpdates): ?>
                        <div class="mb-8 rounded-2xl border border-emerald-100 bg-emerald-50/70 p-5" id="actualizaciones-form">
                            <h3 class="text-sm font-semibold text-emerald-900">
                                <?= $finalUpdateAllowed ? 'Publica tu mensaje de cierre' : 'Publica una nueva actualización'; ?>
                            </h3>
                            <p class="mt-1 text-xs text-emerald-800">
                                <?= $finalUpdateAllowed
                                    ? 'Agradece a quienes apoyaron, confirma la transparencia final y comparte próximos pasos. Este mensaje se enviará a tu comunidad.'
                                    : 'Informa avances, comparte fotos o agradece a tu comunidad. Nos encargamos de notificar a tus seguidores.'; ?>
                            </p>

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

                            <form method="POST" action="<?= htmlspecialchars($campaignUpdateAction) ?>" enctype="multipart/form-data" class="mt-4 space-y-4">
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
                                <div>
                                    <label class="block text-xs font-semibold text-emerald-900 uppercase tracking-wide">Adjunta imágenes (opcional)</label>
                                    <input type="file" name="update_images[]" accept="image/jpeg,image/png,image/webp" multiple class="mt-1 block w-full rounded-xl border border-emerald-200 bg-white px-3 py-2 text-sm text-emerald-900 shadow-inner focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-300">
                                    <p class="mt-1 text-xs text-emerald-700">Hasta 3 imágenes JPG, PNG o WebP (máx. 6&nbsp;MB cada una). Refuerza la transparencia con registros del avance.</p>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-3">
                                    <?php foreach ($updateSocialLinks as $index => $socialLink): ?>
                                        <div>
                                            <label class="block text-xs font-semibold text-emerald-900 uppercase tracking-wide">Red social <?= $index + 1 ?> (opcional)</label>
                                            <input
                                                type="url"
                                                name="social_links[]"
                                                value="<?= htmlspecialchars($socialLink) ?>"
                                                class="mt-1 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2 text-sm text-emerald-900 shadow-inner focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-300"
                                                placeholder="https://instagram.com/tu_cuenta"
                                            >
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="text-xs text-emerald-700 flex items-center gap-2">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v8m4-4H8" />
                                        </svg>
                                        Comparte evidencia visual y tus canales oficiales para reforzar la transparencia con tu comunidad.
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
                                    <?php
                                    $updateMediaItems = is_array($update['media'] ?? null) ? $update['media'] : [];
                                    $updateMediaImages = array_values(array_filter($updateMediaItems, static function ($item) {
                                        return ($item['type'] ?? 'image') === 'image' && !empty($item['url']);
                                    }));
                                    $updateSocialLinks = array_values(array_filter($updateMediaItems, static function ($item) {
                                        return ($item['type'] ?? '') === 'link' && !empty($item['url']);
                                    }));
                                    ?>
                                    <?php if (!empty($updateMediaImages)): ?>
                                        <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                            <?php foreach ($updateMediaImages as $mediaItem): ?>
                                                <a href="<?= htmlspecialchars($mediaItem['url']) ?>" target="_blank" rel="noopener noreferrer" class="group relative block overflow-hidden rounded-xl border border-gray-200">
                                                    <span class="absolute inset-0 bg-gradient-to-t from-black/40 via-black/10 to-transparent opacity-0 transition group-hover:opacity-100"></span>
                                                    <img src="<?= htmlspecialchars($mediaItem['url']) ?>" alt="Imagen de actualización" class="h-28 w-full object-cover transition group-hover:scale-105" loading="lazy">
                                                    <?php if (!empty($mediaItem['caption'])): ?>
                                                        <span class="absolute bottom-2 left-2 right-2 rounded-lg bg-black/60 px-2 py-1 text-[11px] font-medium text-white">
                                                            <?= htmlspecialchars($mediaItem['caption']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($updateSocialLinks)): ?>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <?php foreach ($updateSocialLinks as $social): ?>
                                                <?php
                                                $platformLabel = trim((string)($social['label'] ?? ($social['platform'] ?? 'Enlace')));
                                                if ($platformLabel === '') {
                                                    $platformLabel = 'Enlace';
                                                }
                                                $initialSeed = $social['initial'] ?? mb_substr($platformLabel, 0, 2);
                                                $badgeInitial = strtoupper(mb_substr($initialSeed, 0, 2));
                                                ?>
                                                <a href="<?= htmlspecialchars($social['url']) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-full border border-copihue-200 bg-white px-3 py-1 text-xs font-semibold text-copihue-700 transition hover:bg-copihue-50 focus:outline-none focus:ring-2 focus:ring-copihue-500">
                                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-copihue-100 text-copihue-600 text-[11px] font-bold"><?= htmlspecialchars($badgeInitial) ?></span>
                                                    <span><?= htmlspecialchars($platformLabel) ?></span>
                                                </a>
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

            </div>

            <aside class="space-y-6 lg:sticky lg:top-8">
                <div class="bg-white shadow-soft rounded-3xl p-6 space-y-6">
                    <div class="space-y-3">
                        <p class="text-3xl font-semibold text-gray-900">$<?php echo number_format($stats['raised_amount'], 0, ',', '.'); ?></p>
                        <p class="text-sm text-gray-500">de una meta de $<?php echo number_format($stats['goal_amount'], 0, ',', '.'); ?></p>
                        <div class="progress" aria-hidden="true">
                            <div class="progress-fill" style="width: <?php echo min(100, $stats['progress']); ?>%"></div>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span><?php echo $stats['progress']; ?>% alcanzado</span>
                            <span><?php echo (int)($stats['donors'] ?? 0); ?> aportes</span>
                        </div>
                        <?php
                            $timeLabel = null;
                            if (!empty($campaign['time_over'])) {
                                $timeLabel = 'Campaña finalizada';
                            } elseif (!empty($campaign['time_remaining_label'])) {
                                $timeLabel = $campaign['time_remaining_label'];
                            } elseif ($stats['days_left'] !== null) {
                                $timeLabel = max(0, (int)$stats['days_left']) . ' días restantes';
                            }
                        ?>
                        <?php if ($timeLabel !== null): ?>
                            <p class="text-sm text-gray-600"><?= htmlspecialchars($timeLabel); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-4" id="donar">
                        <?php if (!$acceptingDonations): ?>
                            <?php if (in_array($statusSlug, ['under_review', 'draft'], true)): ?>
                                <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                                    Esta campaña está en revisión por el equipo de Lucatón. Pronto estará disponible para recibir aportes.
                                </div>
                            <?php elseif ($statusSlug === 'paused'): ?>
                                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                    Esta campaña se encuentra en pausa temporalmente. Te avisaremos cuando vuelva a estar activa.
                                </div>
                            <?php elseif ($campaign_goal_reached): ?>
                                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                                    Esta campaña alcanzó su meta y ya no recibe nuevos aportes. ¡Gracias por apoyar!
                                </div>
                            <?php else: ?>
                                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                    Esta campaña finalizó y ya no recibe nuevos aportes. El tiempo terminó sin alcanzar la meta, pero agradecemos tu apoyo ✨
                                </div>
                            <?php endif; ?>
                        <?php elseif (!$isUserAuthenticated): ?>
                            <a href="<?= htmlspecialchars($loginRedirectUrl) ?>" class="btn-primary w-full text-center">Donar</a>
                        <?php else: ?>
                            <?php if (isset($donationFormErrors['general'])): ?>
                                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                    <?= htmlspecialchars($donationFormErrors['general']) ?>
                                </div>
                            <?php endif; ?>

                            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-4 text-sm text-gray-700 space-y-2">
                                <div>
                                    <p class="font-medium text-gray-900">Aportarás como <?= htmlspecialchars($donorAccountName !== '' ? $donorAccountName : 'tu cuenta') ?></p>
                                    <?php if ($donorAccountEmail !== ''): ?>
                                        <p class="text-xs text-gray-500">Confirmaremos el aporte en tu correo. Si necesitas cambiarlo, actualízalo desde tu perfil.</p>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs text-gray-500">Puedes elegir mostrar tu nombre o mantenerlo oculto en la página pública. Los aportes por transferencia con comprobante quedarán en revisión manual.</p>
                            </div>

                            <button type="button"
                                class="w-full inline-flex items-center justify-center rounded-md bg-copihue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-copihue-700 focus:outline-none focus:ring-2 focus:ring-copihue-500 focus:ring-offset-2"
                                data-donation-modal-open>
                                Donar ahora
                            </button>
                            <p class="text-xs text-gray-500 text-center">
                                Las donaciones se registran de forma simulada para fines académicos.
                            </p>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($donationSidebarItems)): ?>
                        <div class="space-y-3 pt-4 border-t border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-900">Aportes recientes</h3>
                            <ul class="space-y-3">
                                <?php foreach ($donationSidebarItems as $sidebarDonation): ?>
                                    <?php
                                    $isAnonymousSidebar = !empty($sidebarDonation['is_anonymous']);
                                    $sidebarName = 'Aporte anónimo';
                                    if (!$isAnonymousSidebar) {
                                        $sidebarName = trim(($sidebarDonation['first_name'] ?? '') . ' ' . ($sidebarDonation['last_name'] ?? ''));
                                        if ($sidebarName === '') {
                                            $sidebarName = $sidebarDonation['supporter_name'] ?? $sidebarDonation['donor_name'] ?? $sidebarDonation['username'] ?? 'Colaborador';
                                        }
                                    }
                                    $sidebarAmount = '$' . number_format((float)($sidebarDonation['amount'] ?? 0), 0, ',', '.');
                                    $sidebarDate = isset($sidebarDonation['created_at'])
                                        ? date('d M Y', strtotime($sidebarDonation['created_at']))
                                        : null;
                                    ?>
                                    <li class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50 px-3 py-2">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($sidebarName) ?></p>
                                            <?php if (!empty($sidebarDonation['message'])): ?>
                                                <p class="text-xs text-gray-500 leading-snug"><?= htmlspecialchars($sidebarDonation['message']) ?></p>
                                            <?php elseif ($sidebarDate): ?>
                                                <p class="text-xs text-gray-500 leading-snug">Aportó el <?= htmlspecialchars($sidebarDate) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <span class="ml-3 text-sm font-semibold text-copihue-600"><?= htmlspecialchars($sidebarAmount) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <a href="<?= htmlspecialchars($campaignDonationsUrl) ?>" class="btn-outline w-full text-center">Ver todos los aportes</a>
                        </div>
                    <?php endif; ?>
                </div>

            </aside>
        </div>
    </main>

    <?php
$paymentOptionCards = [
    'credit_card' => ['label' => 'Tarjeta crédito', 'description' => 'Visa, Mastercard demo · 4242 4242 4242 4242'],
    'debit_card' => ['label' => 'Tarjeta débito', 'description' => 'Redcompra demo · 5222 2222 2222 2222'],
    'bank_transfer' => ['label' => 'Transferencia / depósito', 'description' => 'Referencia simulada y comprobante ficticio'],
    'paypal' => ['label' => 'PayPal', 'description' => 'Cuenta sandbox · demo@paypal.com'],
    'webpay' => ['label' => 'Webpay', 'description' => 'RUT y banco de demostración'],
];
    ?>

    <div class="fixed inset-0 z-50 hidden" data-donation-modal data-open-on-load="<?= !empty($donationFormErrors) ? 'true' : 'false' ?>">
        <div class="absolute inset-0 bg-gray-900/70" data-donation-modal-close aria-hidden="true"></div>
        <div class="relative z-10 mx-auto flex min-h-full w-full items-center justify-center px-4 py-8">
            <form method="POST" action="<?= Router::url('api/donate/' . ($campaign['id'] ?? 0)) ?>" class="w-full max-w-2xl space-y-8 rounded-[28px] border border-slate-100 bg-white/95 p-6 shadow-strong backdrop-blur sm:p-8 lg:p-10 max-h-[90vh] overflow-y-auto" enctype="multipart/form-data" novalidate data-donation-form>
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">
                <input type="hidden" name="payment_method" id="donation-modal-method" value="<?= htmlspecialchars($donationPaymentMethod) ?>">

                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <span class="inline-flex items-center rounded-full border border-copihue-200/70 bg-copihue-50/70 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-[0.22em] text-copihue-600">Simulación Lucatón</span>
                        <h2 class="text-2xl font-semibold text-slate-900">Registrar aporte</h2>
                        <p class="text-sm text-slate-500">Campaña "<?= htmlspecialchars($campaign['title'] ?? 'Campaña') ?>"</p>
                    </div>
                    <button type="button" class="rounded-full border border-slate-200 bg-slate-50 p-2 text-slate-500 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-copihue-500" data-donation-modal-close>
                        <span class="sr-only">Cerrar</span>
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l8 8M6 14L14 6" stroke-linecap="round" /></svg>
                    </button>
                </div>

                <div class="rounded-3xl border border-sky-100/80 bg-gradient-to-br from-sky-50 via-white to-white p-5 text-sm text-sky-900 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-sky-600 text-white shadow">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11v2m0 4h.01M4.318 6.318a4.5 4.5 0 006.364 6.364L12 9.364l1.318 1.318a4.5 4.5 0 106.364-6.364A4.5 4.5 0 0012 2.5a4.5 4.5 0 00-7.682 3.818z" /></svg>
                        </div>
                        <div class="space-y-2">
                            <p class="text-base font-semibold text-sky-900">Simulación segura de pago</p>
                            <ul class="space-y-1 text-xs leading-relaxed text-sky-900/90">
                                <li class="flex items-start gap-2">
                                    <svg class="mt-0.5 h-3 w-3 flex-shrink-0 text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    Usa datos ficticios; no generamos cargos reales ni guardamos tarjetas verdaderas.
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg class="mt-0.5 h-3 w-3 flex-shrink-0 text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    Los comprobantes de transferencia quedan en revisión privada hasta su validación.
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg class="mt-0.5 h-3 w-3 flex-shrink-0 text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    Confirmaremos el aporte en tu correo registrado para mantener trazabilidad.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <?php if (isset($donationFormErrors['general'])): ?>
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <?= htmlspecialchars($donationFormErrors['general']) ?>
                    </div>
                <?php endif; ?>

                <div class="space-y-3">
                    <label for="donation-modal-amount" class="block text-sm font-medium text-slate-800">Monto del aporte</label>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:gap-4">
                        <div class="sm:flex-1">
                            <input id="donation-modal-amount" name="amount" type="text" inputmode="numeric" pattern="[0-9\.]*" autocomplete="off" required value="<?= htmlspecialchars($donationAmountDisplay) ?>" class="w-full rounded-2xl border <?= isset($donationFormErrors['amount']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-slate-300 focus:border-copihue-500 focus:ring-copihue-500' ?> bg-white px-4 py-3 text-base font-medium text-slate-900 shadow-inner transition">
                            <?php if (isset($donationFormErrors['amount'])): ?>
                                <p class="mt-2 text-xs text-red-600"><?= htmlspecialchars($donationFormErrors['amount']) ?></p>
                            <?php else: ?>
                                <p class="mt-2 text-xs text-slate-500">Ingresa el monto en pesos chilenos. Aplicaremos los separadores automáticamente.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="space-y-2">
                    <label for="donation-modal-message" class="block text-sm font-medium text-slate-800">Mensaje (opcional)</label>
                    <textarea id="donation-modal-message" name="message" rows="3" class="w-full rounded-2xl border <?= isset($donationFormErrors['message']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-slate-300 focus:border-copihue-500 focus:ring-copihue-500' ?> bg-white px-4 py-2.5 text-sm text-slate-900 shadow-inner transition" placeholder="Comparte unas palabras de apoyo."><?= htmlspecialchars($donationOld['message'] ?? '') ?></textarea>
                    <?php if (isset($donationFormErrors['message'])): ?>
                        <p class="text-xs text-red-600"><?= htmlspecialchars($donationFormErrors['message']) ?></p>
                    <?php endif; ?>
                </div>

                <?php
$methodBadges = [
    'credit_card' => 'CC',
    'debit_card' => 'DB',
    'bank_transfer' => 'TR',
    'paypal' => 'PP',
    'webpay' => 'WP',
];
?>
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Método de aporte</p>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3" data-donation-method-buttons>
                        <?php foreach ($paymentOptionCards as $methodKey => $option): ?>
                            <?php $isActive = $donationPaymentMethod === $methodKey; ?>
                            <button type="button"
                                class="group relative flex items-start gap-3 rounded-2xl border <?= $isActive ? 'border-copihue-500 bg-copihue-50/60 text-copihue-700 shadow-sm' : 'border-slate-200 bg-white text-slate-700 hover:border-copihue-400 hover:text-copihue-600' ?> px-4 py-3 text-left text-sm transition"
                                data-donation-method-button="<?= htmlspecialchars($methodKey) ?>">
                                <span class="mt-0.5 inline-flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full border <?= $isActive ? 'border-copihue-500 bg-white text-copihue-600' : 'border-slate-200 bg-slate-50 text-slate-500' ?> text-[10px] font-semibold"><?= htmlspecialchars($methodBadges[$methodKey] ?? 'AP') ?></span>
                                <span class="space-y-0.5">
                                    <span class="block font-semibold"><?= htmlspecialchars($option['label']) ?></span>
                                    <span class="block text-xs text-slate-500"><?= htmlspecialchars($option['description']) ?></span>
                                </span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <?php if (isset($donationFormErrors['payment'])): ?>
                        <p class="text-xs text-red-600"><?= htmlspecialchars($donationFormErrors['payment']) ?></p>
                    <?php else: ?>
                        <p class="text-xs text-slate-500">Selecciona un método y completa los campos con datos de demostración para continuar.</p>
                    <?php endif; ?>
                </div>

                <div class="space-y-4" data-donation-details>
                    <div class="<?= $paymentSectionVisibility['card'] ? '' : 'hidden' ?> rounded-2xl border border-slate-200/80 bg-slate-50/70 px-4 py-4" data-donation-method-panel="credit_card,debit_card">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Datos de tarjeta (simulación)</p>
                        <div class="mt-3 space-y-3">
                            <div>
                                <label for="donation-card-holder" class="block text-sm font-medium text-slate-800">Nombre del titular</label>
                                <input id="donation-card-holder" name="card_holder" type="text" autocomplete="cc-name" value="<?= htmlspecialchars($donationPaymentFields['card_holder']) ?>" class="mt-1 w-full rounded-lg border <?= isset($donationFormErrors['payment_card_holder']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-slate-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm">
                                <?php if (isset($donationFormErrors['payment_card_holder'])): ?>
                                    <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($donationFormErrors['payment_card_holder']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label for="donation-card-number" class="block text-sm font-medium text-slate-800">Número de tarjeta</label>
                                <input id="donation-card-number" name="card_number" type="text" inputmode="numeric" autocomplete="cc-number" placeholder="4242 4242 4242 4242" class="mt-1 w-full rounded-lg border <?= isset($donationFormErrors['payment_card_number']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-slate-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm">
                                <?php if (isset($donationFormErrors['payment_card_number'])): ?>
                                    <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($donationFormErrors['payment_card_number']) ?></p>
                                <?php else: ?>
                                    <p class="mt-1 text-xs text-gray-500">Ejemplo aceptado: 4242 4242 4242 4242.</p>
                                <?php endif; ?>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="donation-card-expiration" class="block text-sm font-medium text-slate-800">Vencimiento (MM/AA)</label>
                                    <input id="donation-card-expiration" name="card_expiration" type="text" inputmode="numeric" autocomplete="cc-exp" placeholder="08/28" value="<?= htmlspecialchars($donationPaymentFields['card_expiration']) ?>" class="mt-1 w-full rounded-lg border <?= isset($donationFormErrors['payment_card_expiration']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-slate-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm">
                                    <?php if (isset($donationFormErrors['payment_card_expiration'])): ?>
                                        <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($donationFormErrors['payment_card_expiration']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <label for="donation-card-cvv" class="block text-sm font-medium text-slate-800">CVV</label>
                                    <input id="donation-card-cvv" name="card_cvv" type="text" inputmode="numeric" autocomplete="cc-csc" placeholder="123" class="mt-1 w-full rounded-lg border <?= isset($donationFormErrors['payment_card_cvv']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-slate-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm">
                                    <?php if (isset($donationFormErrors['payment_card_cvv'])): ?>
                                        <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($donationFormErrors['payment_card_cvv']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="<?= $paymentSectionVisibility['transfer'] ? '' : 'hidden' ?> rounded-2xl border border-slate-200/80 bg-slate-50/70 px-4 py-4" data-donation-method-panel="bank_transfer,manual">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Datos de transferencia simulada</p>
                        <div class="mt-3 space-y-3">
                            <div>
                                <label for="donation-transfer-bank" class="block text-sm font-medium text-slate-800">Banco de origen</label>
                                <input id="donation-transfer-bank" name="transfer_bank" type="text" value="<?= htmlspecialchars($donationPaymentFields['transfer_bank']) ?>" class="mt-1 w-full rounded-lg border <?= isset($donationFormErrors['payment_transfer_bank']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-slate-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm" placeholder="BancoEstado, BCI, Santander...">
                                <?php if (isset($donationFormErrors['payment_transfer_bank'])): ?>
                                    <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($donationFormErrors['payment_transfer_bank']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label for="donation-transfer-reference" class="block text-sm font-medium text-slate-800">Número de comprobante</label>
                                <input id="donation-transfer-reference" name="transfer_reference" type="text" value="<?= htmlspecialchars($donationPaymentFields['transfer_reference']) ?>" class="mt-1 w-full rounded-lg border <?= isset($donationFormErrors['payment_transfer_reference']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-slate-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm" placeholder="TRX-9981">
                                <?php if (isset($donationFormErrors['payment_transfer_reference'])): ?>
                                    <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($donationFormErrors['payment_transfer_reference']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label for="donation-transfer-receipt" class="block text-sm font-medium text-slate-800">Adjuntar comprobante ficticio (opcional)</label>
                                <input id="donation-transfer-receipt" name="transfer_receipt" type="file" accept="image/jpeg,image/png,application/pdf" class="mt-2 block w-full text-sm text-slate-600 file:mr-4 file:rounded-md file:border-0 file:bg-copihue-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-copihue-700">
                                <?php if (isset($donationFormErrors['payment_transfer_receipt'])): ?>
                                    <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($donationFormErrors['payment_transfer_receipt']) ?></p>
                                <?php else: ?>
                                    <p class="mt-1 text-xs text-gray-500">Sube un comprobante ficticio en JPG, PNG o PDF (máx. 2&nbsp;MB). Quedará almacenado de forma privada para revisión.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="<?= $paymentSectionVisibility['paypal'] ? '' : 'hidden' ?> rounded-2xl border border-slate-200/80 bg-slate-50/70 px-4 py-4" data-donation-method-panel="paypal">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Cuenta PayPal demo</p>
                        <div class="mt-3">
                            <label for="donation-paypal-email" class="block text-sm font-medium text-slate-800">Correo electrónico</label>
                            <input id="donation-paypal-email" name="paypal_email" type="email" value="<?= htmlspecialchars($donationPaymentFields['paypal_email']) ?>" class="mt-1 w-full rounded-lg border <?= isset($donationFormErrors['payment_paypal_email']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-slate-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm" placeholder="demo@paypal.com">
                            <?php if (isset($donationFormErrors['payment_paypal_email'])): ?>
                                <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($donationFormErrors['payment_paypal_email']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="<?= $paymentSectionVisibility['webpay'] ? '' : 'hidden' ?> rounded-2xl border border-slate-200/80 bg-slate-50/70 px-4 py-4" data-donation-method-panel="webpay">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Simulación Webpay</p>
                        <div class="mt-3 space-y-3">
                            <div>
                                <label for="donation-webpay-rut" class="block text-sm font-medium text-slate-800">RUT del pagador</label>
                                <input id="donation-webpay-rut" name="webpay_rut" type="text" value="<?= htmlspecialchars($donationPaymentFields['webpay_rut']) ?>" class="mt-1 w-full rounded-lg border <?= isset($donationFormErrors['payment_webpay_rut']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-slate-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm" placeholder="11.111.111-1">
                                <?php if (isset($donationFormErrors['payment_webpay_rut'])): ?>
                                    <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($donationFormErrors['payment_webpay_rut']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label for="donation-webpay-bank" class="block text-sm font-medium text-slate-800">Banco seleccionado</label>
                                <select id="donation-webpay-bank" name="webpay_bank" class="mt-1 w-full rounded-lg border <?= isset($donationFormErrors['payment_webpay_bank']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-slate-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm">
                                    <option value="">Selecciona una opción</option>
                                    <?php foreach ($webpayBanks as $bankOption): ?>
                                        <option value="<?= htmlspecialchars($bankOption) ?>" <?= $donationPaymentFields['webpay_bank'] === $bankOption ? 'selected' : '' ?>><?= htmlspecialchars($bankOption) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($donationFormErrors['payment_webpay_bank'])): ?>
                                    <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($donationFormErrors['payment_webpay_bank']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input id="donation-modal-anonymous" name="is_anonymous" type="checkbox" value="1" <?= $donationIsAnonymous ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-300 text-copihue-600 focus:ring-copihue-500">
                    Publicar mi aporte como anónimo
                </label>

                <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-slate-500">Los aportes con comprobante quedan en revisión manual antes de mostrarse públicamente.</p>
                    <div class="flex items-center gap-2">
                        <button type="button" class="inline-flex items-center justify-center rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-copihue-500" data-donation-modal-close>Cancelar</button>
                        <button type="submit" class="inline-flex items-center justify-center rounded-full bg-copihue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-copihue-700 focus:outline-none focus:ring-2 focus:ring-copihue-500 focus:ring-offset-2">Confirmar aporte</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.querySelector('[data-donation-modal]');
            if (!modal) {
                return;
            }

            const openButtons = document.querySelectorAll('[data-donation-modal-open]');
            const closeElements = modal.querySelectorAll('[data-donation-modal-close]');
            const form = modal.querySelector('[data-donation-form]');
            const amountInput = modal.querySelector('#donation-modal-amount');
            const methodInput = modal.querySelector('#donation-modal-method');
            const methodButtons = modal.querySelectorAll('[data-donation-method-button]');
            const detailPanels = modal.querySelectorAll('[data-donation-method-panel]');
            const cardNumberInput = modal.querySelector('#donation-card-number');
            const sampleFactories = {
                credit_card: function () {
                    return {
                        card_holder: 'María González',
                        card_number: '4242 4242 4242 4242',
                        card_expiration: '08/28',
                        card_cvv: '123'
                    };
                },
                debit_card: function () {
                    return {
                        card_holder: 'Carlos Díaz',
                        card_number: '5222 2222 2222 2222',
                        card_expiration: '10/27',
                        card_cvv: '456'
                    };
                },
                bank_transfer: function () {
                    return {
                        transfer_bank: 'BancoEstado',
                        transfer_reference: 'TRX-' + (Math.floor(Math.random() * 900000) + 100000)
                    };
                },
                paypal: function () {
                    return {
                        paypal_email: 'sandbox.cliente@example.com'
                    };
                },
                webpay: function () {
                    return {
                        webpay_rut: '17.765.432-1',
                        webpay_bank: 'BancoEstado'
                    };
                }
            };

            const applySampleData = function (method) {
                if (!form) {
                    return;
                }
                const normalizedMethod = method === 'manual' ? 'bank_transfer' : method;
                const factory = sampleFactories[normalizedMethod];
                if (typeof factory !== 'function') {
                    return;
                }
                const sample = factory();
                Object.keys(sample).forEach(function (fieldName) {
                    const field = form.querySelector('[name=\"' + fieldName + '\"]');
                    if (!field) {
                        return;
                    }
                    if (field.tagName === 'SELECT') {
                        if (!field.value) {
                            field.value = sample[fieldName];
                        }
                        return;
                    }
                    if (field.value) {
                        return;
                    }
                    field.value = sample[fieldName];
                    try {
                        field.dispatchEvent(new Event('input', { bubbles: true }));
                    } catch (error) {
                        field.dispatchEvent(new Event('input'));
                    }
                });
            };

            const toggleBodyScroll = function (enabled) {
                document.body.classList.toggle('overflow-hidden', enabled);
            };

            const openModal = function () {
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
                toggleBodyScroll(true);
                const autofocusTarget = modal.querySelector('#donation-modal-amount');
                if (autofocusTarget) {
                    setTimeout(function () { autofocusTarget.focus({ preventScroll: true }); }, 60);
                }
            };

            const closeModal = function () {
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                toggleBodyScroll(false);
            };

            openButtons.forEach(function (button) {
                button.addEventListener('click', openModal);
            });

            closeElements.forEach(function (el) {
                el.addEventListener('click', closeModal);
            });

            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });

            const formatThousands = function (value) {
                const digits = (value || '').replace(/\D/g, '');
                if (!digits) {
                    return '';
                }
                return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            };

            if (amountInput) {
                amountInput.value = formatThousands(amountInput.value);

                amountInput.addEventListener('input', function () {
                    amountInput.value = formatThousands(amountInput.value);
                });

                amountInput.addEventListener('blur', function () {
                    amountInput.value = formatThousands(amountInput.value);
                });
            }

            const activateMethod = function (method) {
                if (!methodInput) {
                    return;
                }
                methodInput.value = method;

                methodButtons.forEach(function (btn) {
                    const isActive = btn.getAttribute('data-donation-method-button') === method;
                    btn.classList.toggle('border-copihue-500', isActive);
                    btn.classList.toggle('bg-copihue-50', isActive);
                    btn.classList.toggle('text-copihue-700', isActive);
                    btn.classList.toggle('border-gray-200', !isActive);
                    btn.classList.toggle('bg-white', !isActive);
                    btn.classList.toggle('text-gray-700', !isActive);
                });

                detailPanels.forEach(function (panel) {
                    const methods = (panel.getAttribute('data-donation-method-panel') || '').split(',');
                    const isVisible = methods.indexOf(method) !== -1;
                    panel.classList.toggle('hidden', !isVisible);
                    panel.querySelectorAll('input, select, textarea').forEach(function (field) {
                        field.disabled = !isVisible;
                        if (!isVisible && (field.name === 'card_number' || field.name === 'card_cvv')) {
                            field.value = '';
                        }
                    });
                });
                applySampleData(method);
            };

            methodButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const targetMethod = btn.getAttribute('data-donation-method-button');
                    activateMethod(targetMethod);
                });
            });

            if (cardNumberInput) {
                const formatCard = function (value) {
                    const digits = (value || '').replace(/\D/g, '');
                    return digits.replace(/(.{4})/g, '$1 ').trim();
                };

                cardNumberInput.addEventListener('input', function () {
                    cardNumberInput.value = formatCard(cardNumberInput.value);
                });

                cardNumberInput.addEventListener('blur', function () {
                    cardNumberInput.value = formatCard(cardNumberInput.value);
                });
            }

            if (form) {
                form.addEventListener('submit', function () {
                    if (amountInput) {
                        amountInput.value = (amountInput.value || '').replace(/\D/g, '');
                    }
                });
            }

            const initialMethod = methodInput ? methodInput.value : null;
            if (initialMethod) {
                activateMethod(initialMethod);
            } else if (methodButtons.length > 0) {
                activateMethod(methodButtons[0].getAttribute('data-donation-method-button'));
            }

            const openOnLoad = modal.getAttribute('data-open-on-load') === 'true';
            if (openOnLoad) {
                openModal();
            }
        })();
    </script>

    <?php if (!empty($celebrationOverlay)): ?>
        <?php
        $overlayData = $celebrationOverlay;
        include __DIR__ . '/../components/celebration-overlay.php';
        ?>
    <?php endif; ?>

    <div class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/80 px-4" data-gallery-lightbox aria-hidden="true" tabindex="-1">
        <button type="button" class="absolute inset-0 h-full w-full cursor-default" data-gallery-close tabindex="-1" aria-label="Cerrar galería"></button>
        <div class="relative w-full max-w-5xl space-y-4 rounded-3xl bg-white p-4 sm:p-6 lg:p-8 shadow-strong transition-all duration-300" data-gallery-modal-container>
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
                <img src="" alt="" data-gallery-current-image class="w-full object-contain transition-all duration-300 ease-out">
                <button type="button" class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-white/80 p-2 text-gray-700 shadow hover:bg-white focus:outline-none focus:ring-2 focus:ring-copihue-500" data-gallery-prev>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5l-5 5 5 5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    <span class="sr-only">Imagen anterior</span>
                </button>
                <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-white/80 p-2 text-gray-700 shadow hover:bg-white focus:outline-none focus:ring-2 focus:ring-copihue-500" data-gallery-next>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 5l5 5-5 5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    <span class="sr-only">Imagen siguiente</span>
                </button>
            </div>
            <div class="space-y-1 text-sm text-gray-600">
                <p data-gallery-current-caption></p>
                <p class="text-xs text-gray-500">Usa las flechas del teclado o los botones laterales para recorrer la galería.</p>
            </div>
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
            <div class="hidden space-y-2" data-creator-profile-socials-wrapper>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Redes verificadas</p>
                <div class="flex flex-wrap gap-2" data-creator-profile-socials></div>
            </div>
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
