<?php
require_once __DIR__ . '/../components/navigation.php';
require_once __DIR__ . '/../components/alerts.php';

$status = $status ?? 'invalid';
$subscription = $result ?? null;
$siteToastQueue = array_map(static function ($toast) {
    return [
        'type' => $toast['type'] ?? 'info',
        'message' => $toast['message'] ?? ''
    ];
}, SessionHelper::pullSiteToasts());
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Newsletter Lucatón') ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description ?? '') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/public/assets/images/favicon.svg">
    <link href="<?= APP_URL ?>/public/assets/css/app.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/public/assets/css/aliases.css" rel="stylesheet">
    <?php if (!empty($siteToastQueue)): ?>
        <script>
            window.__SITE_TOASTS__ = <?= json_encode($siteToastQueue, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;
        </script>
    <?php endif; ?>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <?php include __DIR__ . '/../layouts/partials/header.php'; ?>

    <main class="flex-1">
        <section class="py-16">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white shadow-soft rounded-3xl p-8 sm:p-12 text-center space-y-6">
                    <?php if ($status === 'unsubscribed'): ?>
                        <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <h1 class="text-2xl font-semibold text-gray-900">Preferencias actualizadas</h1>
                        <p class="text-gray-600">Ya no recibirás novedades en <strong><?= htmlspecialchars($subscription['email'] ?? '') ?></strong>. Puedes volver a suscribirte cuando quieras desde el pie de página del sitio.</p>
                        <a href="<?= Router::url('/') ?>" class="btn-primary inline-flex items-center justify-center">Volver al inicio</a>
                    <?php else: ?>
                        <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-amber-100 text-amber-600">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 7.519l-3.9 6.76A2 2 0 007.764 18h8.472a2 2 0 001.784-3.721l-3.9-6.76a2 2 0 00-3.541 0z"/></svg>
                        </div>
                        <h1 class="text-2xl font-semibold text-gray-900">Enlace no disponible</h1>
                        <p class="text-gray-600">No pudimos validar el enlace de desuscripción. Es posible que ya no sea válido o que la suscripción haya sido eliminada.</p>
                        <a href="<?= Router::url('/') ?>" class="btn-outline inline-flex items-center justify-center">Ir al inicio</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../layouts/partials/footer.php'; ?>
    <div class="site-toast-stack" data-site-toast-container></div>
    <script defer src="<?= APP_URL ?>/public/assets/js/app.js?v=2025020503"></script>
</body>
</html>
