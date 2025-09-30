<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Lucatón - Plataforma de Crowdfunding' ?></title>
    
    <!-- Meta tags para SEO -->
    <meta name="description" content="<?= $description ?? 'Lucatón es una plataforma de crowdfunding que conecta proyectos innovadores con personas que quieren apoyarlos.' ?>">
    <meta name="keywords" content="crowdfunding, donaciones, proyectos, financiamiento colectivo">
    <meta name="author" content="Lucatón">
    
    <!-- Open Graph para redes sociales -->
    <meta property="og:title" content="<?= $title ?? 'Lucatón' ?>">
    <meta property="og:description" content="<?= $description ?? 'Plataforma de crowdfunding' ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= Router::url($_SERVER['REQUEST_URI']) ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="<?= Router::url('assets/css/main.css') ?>">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?= $_SESSION[CSRF_TOKEN_NAME] ?? '' ?>">
    
    <?php if (isset($additionalHead)): ?>
        <?= $additionalHead ?>
    <?php endif; ?>
</head>
<body class="<?= $bodyClass ?? '' ?>">
    <!-- Navegación -->
    <?php include VIEWS_PATH . '/components/navbar.php'; ?>
    
    <!-- Contenido principal -->
    <main class="main-content">
        <!-- Alertas y mensajes -->
        <?php include VIEWS_PATH . '/components/alerts.php'; ?>
        
        <!-- Contenido de la página -->
        <?= $content ?? '' ?>
    </main>
    
    <!-- Footer -->
    <?php include VIEWS_PATH . '/components/footer.php'; ?>
    
    <!-- Scripts -->
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Scripts personalizados -->
    <script src="<?= Router::url('assets/js/main.js') ?>"></script>
    
    <?php if (isset($additionalScripts)): ?>
        <?= $additionalScripts ?>
    <?php endif; ?>
    
    <!-- Script para CSRF Token -->
    <script>
        // Configurar CSRF token para AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
</body>
</html>