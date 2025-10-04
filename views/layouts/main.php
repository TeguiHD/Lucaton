<?php 
$current_page = $current_page ?? '';
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
    
    <!-- Additional head content -->
    <?= $additional_head ?? '' ?>
</head>
<body class="h-full bg-gray-50 font-sans antialiased">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <div class="min-h-full flex flex-col">
        <!-- Header -->
        <?php include VIEWS_PATH . '/layouts/partials/header.php'; ?>

        <!-- Main Content -->
        <main id="main-content" class="flex-1" role="main">
            <!-- Flash Messages -->
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                    <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?>" role="alert">
                        <?= htmlspecialchars($_SESSION['flash_message']) ?>
                    </div>
                </div>
                <?php 
                unset($_SESSION['flash_message'], $_SESSION['flash_type']); 
                ?>
            <?php endif; ?>

            <!-- Page Content -->
            <?= $content ?>
        </main>

        <!-- Footer -->
        <?php include VIEWS_PATH . '/layouts/partials/footer.php'; ?>
    </div>

    <!-- Additional scripts -->
    <?= $additional_scripts ?? '' ?>
</body>
</html>
