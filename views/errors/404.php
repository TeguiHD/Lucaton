<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada - Lucatón</title>
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/public/assets/images/favicon.svg">
    <link href="<?= APP_URL ?>/public/assets/css/app.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/public/assets/css/aliases.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9fafb;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: #111827;
        }

        main {
            width: 100%;
            max-width: 32rem;
            background: #ffffff;
            border-radius: 1.5rem;
            padding: 2.5rem;
            text-align: center;
            box-shadow: 0 30px 40px -25px rgba(15, 23, 42, 0.35);
            box-sizing: border-box;
        }

        h1 {
            margin: 0;
            font-size: 1.875rem;
            font-weight: 700;
            color: #111827;
        }

        p {
            margin: 0;
            color: #4b5563;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 4rem;
            height: 4rem;
            border-radius: 9999px;
            background: #dbeafe;
            color: #1d4ed8;
            margin: 0 auto 1.5rem auto;
        }

        svg {
            width: 2rem;
            height: 2rem;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.75rem;
            border-radius: 9999px;
            background: #0e7490;
            color: #ffffff;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 20px 25px -15px rgba(14, 116, 144, 0.45);
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .btn-primary:hover {
            background: #155e75;
            transform: translateY(-1px);
            box-shadow: 0 30px 45px -20px rgba(14, 116, 144, 0.45);
        }

        .space-y-6 > * + * {
            margin-top: 1.5rem;
        }

        .space-y-2 > * + * {
            margin-top: 0.75rem;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center">
    <main class="max-w-xl w-full bg-white shadow-lg rounded-2xl p-10 text-center space-y-6">
        <span class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-pacifico-100 text-pacifico-600 status-pill">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </span>
        <div class="space-y-2">
            <h1 class="text-3xl font-bold text-gray-900">404 · Página no encontrada</h1>
            <p class="text-gray-600">La página que buscas no existe o fue movida. Revisa la URL o vuelve a la página principal.</p>
        </div>
        <a href="<?= Router::url('/') ?>" class="btn-primary">Ir a la página de inicio</a>
    </main>
</body>
</html>
