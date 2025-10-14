<?php 
$current_page = $current_page ?? '';

if (isset($_SESSION['flash_message'])) {
    SessionHelper::pushSiteToast($_SESSION['flash_type'] ?? 'info', (string)$_SESSION['flash_message']);
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

$siteToastQueue = array_map(static function ($toast) {
    return [
        'type' => $toast['type'] ?? 'info',
        'message' => $toast['message'] ?? ''
    ];
}, SessionHelper::pullSiteToasts());

$body_classes = $body_classes ?? '';
if (is_array($body_classes)) {
    $body_classes = implode(' ', $body_classes);
}
$body_classes = trim((string)$body_classes);
if ($body_classes === '') {
    $body_classes = 'h-full bg-gray-50 font-sans antialiased';
}
?>
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $meta_description ?? 'Lucatón - Plataforma de crowdfunding ética con asistencia de IA para campañas de impacto social en Chile' ?>">
    <meta name="keywords" content="crowdfunding, chile, inteligencia artificial, campañas sociales, donaciones">
    <meta name="author" content="Lucatón">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= APP_URL . $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:title" content="<?= $page_title ?? 'Lucatón' ?>">
    <meta property="og:description" content="<?= $meta_description ?? 'Plataforma de crowdfunding ética con IA' ?>">
    <meta property="og:image" content="<?= APP_URL ?>/public/assets/images/og-image.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= APP_URL . $_SERVER['REQUEST_URI'] ?>">
    <meta property="twitter:title" content="<?= $page_title ?? 'Lucatón' ?>">
    <meta property="twitter:description" content="<?= $meta_description ?? 'Plataforma de crowdfunding ética con IA' ?>">
    <meta property="twitter:image" content="<?= APP_URL ?>/public/assets/images/og-image.jpg">

    <title><?= $page_title ?? 'Lucatón' ?> - Crowdfunding Ético con IA</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/public/assets/images/favicon.svg">
    
    <!-- CSS -->
    <link href="<?= APP_URL ?>/public/assets/css/app.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/public/assets/css/aliases.css" rel="stylesheet">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="<?= APP_URL ?>/public/assets/css/app.css" as="style">

    <?php if (!empty($siteToastQueue)): ?>
        <script>
            window.__SITE_TOASTS__ = <?= json_encode($siteToastQueue, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;
        </script>
    <?php endif; ?>
    
    <!-- Additional head content -->
    <?= $additional_head ?? '' ?>
</head>
<body class="<?= htmlspecialchars($body_classes, ENT_QUOTES, 'UTF-8') ?>">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <div class="min-h-full flex flex-col">
        <!-- Header -->
        <?php include VIEWS_PATH . '/layouts/partials/header.php'; ?>

        <!-- Main Content -->
        <main id="main-content" class="flex-1" role="main">
            <!-- Page Content -->
            <?= $content ?>
        </main>

        <!-- Footer -->
        <?php include VIEWS_PATH . '/layouts/partials/footer.php'; ?>
    </div>

    <div class="site-toast-stack" data-site-toast-container></div>

    <!-- Additional scripts -->
    <?= $additional_scripts ?? '' ?>
</body>
</html>
