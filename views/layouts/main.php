<?php 
$current_page = $current_page ?? '';
$csrfToken = htmlspecialchars(SessionHelper::getCSRFToken(), ENT_QUOTES, 'UTF-8');

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

$meta_description_value = $meta_description ?? 'Lucatón - Plataforma de crowdfunding ética con asistencia de IA para campañas de impacto social en Chile';
$meta_robots_value = $meta_robots ?? 'index, follow';
$page_title_value = $page_title ?? 'Lucatón';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedUri = parse_url($requestUri);
$canonicalPath = $parsedUri['path'] ?? '/';
if ($canonicalPath === '') {
    $canonicalPath = '/';
}
$canonicalUrl = rtrim(APP_URL, '/') . $canonicalPath;

$escapedTitle = htmlspecialchars($page_title_value, ENT_QUOTES, 'UTF-8');
$escapedFullTitle = htmlspecialchars($page_title_value . ' - Crowdfunding Ético con IA', ENT_QUOTES, 'UTF-8');
$escapedDescription = htmlspecialchars($meta_description_value, ENT_QUOTES, 'UTF-8');
$escapedRobots = htmlspecialchars($meta_robots_value, ENT_QUOTES, 'UTF-8');
$escapedCanonical = htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $escapedDescription ?>">
    <meta name="keywords" content="crowdfunding, chile, inteligencia artificial, campañas sociales, donaciones">
    <meta name="author" content="Lucatón">
    <meta name="robots" content="<?= $escapedRobots ?>">
    <link rel="canonical" href="<?= $escapedCanonical ?>">
    <meta name="csrf-token" content="<?= $csrfToken ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $escapedCanonical ?>">
    <meta property="og:title" content="<?= $escapedTitle ?>">
    <meta property="og:description" content="<?= $escapedDescription ?>">
    <meta property="og:image" content="<?= asset_url('images/og-image.jpg') ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= $escapedCanonical ?>">
    <meta property="twitter:title" content="<?= $escapedTitle ?>">
    <meta property="twitter:description" content="<?= $escapedDescription ?>">
    <meta property="twitter:image" content="<?= asset_url('images/og-image.jpg') ?>">

    <title><?= $escapedFullTitle ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= asset_url('images/favicon.svg') ?>">
    
    <!-- CSS -->
    <link href="<?= asset_url('css/app.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('css/aliases.css') ?>" rel="stylesheet">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="<?= asset_url('css/app.css') ?>" as="style">

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
