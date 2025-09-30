<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso denegado - Lucatón</title>
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/public/assets/images/favicon.svg">
    <link href="<?= APP_URL ?>/public/assets/css/app.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center">
    <main class="max-w-xl w-full bg-white shadow-lg rounded-2xl p-10 text-center space-y-6">
        <span class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-copihue-100 text-copihue-600">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </span>
        <div class="space-y-2">
            <h1 class="text-3xl font-bold text-gray-900">403 · Acceso denegado</h1>
            <p class="text-gray-600">No cuentas con los permisos necesarios para ver esta sección.</p>
        </div>
        <div class="flex flex-col sm:flex-row sm:justify-center gap-3">
            <a href="<?= Router::url('/') ?>" class="btn-primary">Volver al inicio</a>
            <a href="<?= Router::url('login') ?>" class="btn-outline">Iniciar sesión con otra cuenta</a>
        </div>
    </main>
</body>
</html>
