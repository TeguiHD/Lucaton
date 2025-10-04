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

$coverCandidate = $campaign['cover_image_url'] ?? ($campaign['image_url'] ?? null);
$image_url = CampaignMediaUploadService::normalizePublicUrl($coverCandidate)
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
                            <button class="btn-ghost" title="Compartir" onclick="shareCampaign('<?php echo htmlspecialchars($campaign['slug'] ?? $campaign['id']); ?>')">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
                                </svg>
                            </button>
                            <button class="btn-ghost" title="Agregar a favoritos" onclick="toggleFavorite('<?php echo htmlspecialchars($campaign['id']); ?>')">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <h1 class="text-3xl font-bold text-gray-900 leading-tight">
                            <?php echo htmlspecialchars($campaign['title']); ?>
                        </h1>
                        <p class="text-gray-600 text-lg leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($campaign['story'] ?? '')); ?>
                        </p>
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
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen
                            ></iframe>
                        </div>
                        <p class="mt-3 text-sm text-gray-500">El responsable de la campaña compartió este video para complementar la historia.</p>
                    </section>
                <?php endif; ?>

                <section class="bg-white shadow-soft rounded-3xl p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Sobre la campaña</h2>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="font-medium text-gray-500">Creada por</dt>
                            <dd class="text-gray-900"><?php echo htmlspecialchars($campaign['creator_name']); ?></dd>
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
                        <button class="btn-outline w-full" onclick="shareCampaign('<?php echo htmlspecialchars($campaign['slug'] ?? $campaign['id']); ?>')">Copiar enlace</button>
                        <a class="btn-outline w-full" target="_blank" rel="noopener noreferrer" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(Router::url('campana/' . ($campaign['slug'] ?? $campaign['id']))); ?>">Compartir en Facebook</a>
                        <a class="btn-outline w-full" target="_blank" rel="noopener noreferrer" href="https://twitter.com/intent/tweet?url=<?php echo urlencode(Router::url('campana/' . ($campaign['slug'] ?? $campaign['id']))); ?>&text=<?php echo urlencode($campaign['title']); ?>">Compartir en X</a>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <?php include __DIR__ . '/../layouts/partials/footer.php'; ?>
</body>
</html>
