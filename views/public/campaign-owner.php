<?php
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/cards.php';
require_once __DIR__ . '/../components/alerts.php';
require_once __DIR__ . '/../components/navigation.php';
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$profile = $profile ?? [];
$campaigns = $campaigns ?? [];
$breadcrumbs = $breadcrumbs ?? [];
$page_title = $page_title ?? 'Campañas publicadas por la comunidad - Lucatón';
$page_description = $page_description ?? 'Explora campañas solidarias creadas por nuestra comunidad.';

$avatarUrl = $profile['avatar'] ?? (APP_URL . '/public/assets/images/avatars/default.jpg');
$displayName = $profile['name'] ?? 'Campañista';
$username = $profile['username'] ?? '';
$location = $profile['location'] ?? null;
$bio = $profile['bio'] ?? null;
$joinedAt = $profile['joined_at'] ?? null;
$joinedLabel = null;
if ($joinedAt) {
    $timestamp = strtotime($joinedAt);
    if ($timestamp !== false) {
        $spanishMonths = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ];
        $monthNumber = (int)date('n', $timestamp);
        $monthName = $spanishMonths[$monthNumber] ?? date('F', $timestamp);
        $joinedLabel = sprintf(
            '%d de %s de %s',
            (int)date('d', $timestamp),
            $monthName,
            date('Y', $timestamp)
        );
    }
}
$campaignCount = (int)($profile['campaign_count'] ?? count($campaigns));
$totalRaised = (float)($profile['total_raised'] ?? 0.0);
$totalSupporters = (int)($profile['total_supporters'] ?? 0);
$hasCampaigns = !empty($campaigns);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta property="og:type" content="profile">
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_description) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($avatarUrl) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/public/assets/images/favicon.svg">
    <link href="<?= APP_URL ?>/public/assets/css/app.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/public/assets/css/aliases.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <?php include __DIR__ . '/../layouts/partials/header.php'; ?>

    <main id="main-content" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
        <?php if (!empty($breadcrumbs)): ?>
            <?= render_breadcrumb($breadcrumbs); ?>
        <?php endif; ?>

        <?php include_flash_messages(); ?>

        <section class="grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-8">
            <aside class="bg-white shadow-soft rounded-3xl p-6 space-y-6">
                <div class="flex flex-col items-center text-center space-y-3">
                    <img class="h-28 w-28 rounded-full object-cover shadow-lg" src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar de <?= htmlspecialchars($displayName) ?>">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($displayName) ?></h1>
                        <?php if ($username !== ''): ?>
                            <p class="text-sm text-gray-400 font-medium mt-1">@<?= htmlspecialchars($username) ?></p>
                        <?php endif; ?>
                        <?php if ($bio): ?>
                            <p class="mt-2 text-sm text-gray-600 leading-relaxed"><?= htmlspecialchars($bio) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <dl class="grid grid-cols-1 gap-4 text-sm text-gray-600">
                    <?php if ($joinedLabel): ?>
                        <div class="rounded-2xl bg-gray-50 border border-gray-100 px-4 py-3">
                            <dt class="font-semibold text-gray-700">En Lucatón desde</dt>
                            <dd><?= htmlspecialchars($joinedLabel) ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if ($location): ?>
                        <div class="rounded-2xl bg-gray-50 border border-gray-100 px-4 py-3">
                            <dt class="font-semibold text-gray-700">Ubicación</dt>
                            <dd><?= htmlspecialchars($location) ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>

                <div class="rounded-2xl bg-gradient-to-br from-copihue-50 via-white to-copihue-100/60 border border-copihue-200 px-4 py-5">
                    <h2 class="text-sm font-semibold text-copihue-700 uppercase tracking-wide mb-3">Impacto de sus campañas</h2>
                    <dl class="grid grid-cols-1 gap-3 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-600">Campañas publicadas</dt>
                            <dd class="text-base font-semibold text-gray-900"><?= number_format($campaignCount, 0, ',', '.') ?></dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-600">Monto recaudado</dt>
                            <dd class="text-base font-semibold text-gray-900">
                                $<?= number_format($totalRaised, 0, ',', '.') ?>
                            </dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-600">Personas que apoyaron</dt>
                            <dd class="text-base font-semibold text-gray-900"><?= number_format($totalSupporters, 0, ',', '.') ?></dd>
                        </div>
                    </dl>
                </div>
            </aside>

            <section class="space-y-6">
                <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Campañas publicadas</h2>
                        <p class="text-sm text-gray-600 mt-1">
                            <?= $hasCampaigns
                                ? 'Estas son las campañas que ha compartido con la comunidad.'
                                : 'Aún no tiene campañas disponibles públicamente.'; ?>
                        </p>
                    </div>
                    <?php if (SessionHelper::isAuthenticated() && SessionHelper::getUser()['username'] === $username): ?>
                        <a href="<?= Router::url('campana/crear') ?>" class="btn-primary inline-flex items-center gap-2">
                            <span>Crear nueva campaña</span>
                        </a>
                    <?php endif; ?>
                </header>

                <?php if ($hasCampaigns): ?>
                    <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        <?php foreach ($campaigns as $campaign): ?>
                            <?= render_campaign_card($campaign, ['show_id' => false]); ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="rounded-3xl border border-dashed border-gray-200 bg-white p-10 text-center">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-copihue-50 text-copihue-600">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"></path></svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Pronto conocerás nuevas campañas</h3>
                        <p class="mt-2 text-sm text-gray-600">Cuando publique su primera campaña, aparecerá aquí para que puedas revisarla y apoyar su causa.</p>
                    </div>
                <?php endif; ?>
            </section>
        </section>
    </main>

    <?php include __DIR__ . '/../layouts/partials/footer.php'; ?>
</body>
</html>
