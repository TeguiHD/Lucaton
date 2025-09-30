<?php
require_once __DIR__ . '/../components/navigation.php';

$current_page = $current_page ?? 'help_center';

$page_title = 'Centro de ayuda - Lucatón';
$page_description = 'Encuentra guías, preguntas frecuentes y recursos para gestionar campañas y donaciones en Lucatón.';

$help_categories = [
    [
        'title' => 'Comenzar con Lucatón',
        'description' => 'Requisitos, verificación y primeros pasos para publicar campañas.',
        'articles' => [
            ['title' => 'Crear tu cuenta y verificar correo', 'href' => '#crear-cuenta'],
            ['title' => 'Checklist previo a lanzar una campaña', 'href' => '#checklist-campana'],
            ['title' => 'Roles y permisos dentro de Lucatón', 'href' => '#roles-permisos'],
        ],
    ],
    [
        'title' => 'Campañas y recaudación',
        'description' => 'Buenas prácticas, IA asistente y seguimiento de aportes.',
        'articles' => [
            ['title' => 'Cómo usar la IA para mejorar tu historia', 'href' => '#ia-historia'],
            ['title' => 'Actualizar a tus donantes con transparencia', 'href' => '#actualizaciones'],
            ['title' => 'Qué hacer cuando alcanzas tu meta', 'href' => '#meta-alcanzada'],
        ],
    ],
    [
        'title' => 'Pagos y seguridad',
        'description' => 'Estados de aportes, comprobantes y prevención de fraudes.',
        'articles' => [
            ['title' => 'Ver el estado de mis donaciones', 'href' => '#estado-donaciones'],
            ['title' => 'Mi pago no aparece, ¿qué hago?', 'href' => '#pago-no-aparece'],
            ['title' => 'Cómo reportar una campaña sospechosa', 'href' => '#campana-sospechosa'],
        ],
    ],
];

$quick_answers = [
    '¿Cuánto demora validar una campaña?' => 'Nuestro equipo revisa la documentación en menos de 24 horas hábiles. Si falta información, te contactaremos.',
    '¿Puedo editar mi campaña después de publicarla?' => 'Sí. Puedes actualizar texto, imágenes y meta desde el panel, siempre manteniendo la transparencia con tus donantes.',
    '¿Cómo retiro los fondos recaudados?' => 'Una vez finalizada la campaña, sube los comprobantes requeridos y agenda la transferencia bancaria en el panel.',
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">

    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= APP_URL . $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_description) ?>">

    <meta property="twitter:card" content="summary">
    <meta property="twitter:url" content="<?= APP_URL . $_SERVER['REQUEST_URI'] ?>">
    <meta property="twitter:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="twitter:description" content="<?= htmlspecialchars($page_description) ?>">

    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/public/assets/images/favicon.svg">
    <link href="<?= APP_URL ?>/public/assets/css/app.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/public/assets/css/aliases.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <?php include __DIR__ . '/../layouts/partials/header.php'; ?>

    <main id="main-content" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">
        <?= render_breadcrumb([
            ['name' => 'Inicio', 'href' => Router::url('/')],
            ['name' => 'Centro de ayuda', 'href' => Router::url('ayuda')],
        ]); ?>

        <section class="bg-white rounded-lg shadow p-8">
            <div class="grid gap-8 lg:grid-cols-2 lg:items-center">
                <div>
                    <p class="text-sm uppercase tracking-wide text-gray-500">Centro de ayuda</p>
                    <h1 class="mt-2 text-3xl font-bold text-gray-900">Resolvamos tus dudas</h1>
                    <p class="mt-3 text-sm text-gray-600">Busca guías paso a paso, respuesta a preguntas frecuentes y recomendaciones para que tu campaña sea transparente y efectiva.</p>
                    <p class="mt-2 text-xs text-gray-500"><?= htmlspecialchars(PROJECT_OWNER_NAME) ?>. <?= htmlspecialchars(PROJECT_DISCLAIMER) ?></p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="<?= Router::url('reportar') ?>" class="inline-flex items-center rounded-md bg-copihue-600 px-4 py-2 text-sm font-medium text-white hover:bg-copihue-700">
                            Reportar un problema
                        </a>
                        <a href="<?= Router::url('contacto') ?>" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:border-copihue-500 hover:text-copihue-600">
                            Contactar soporte
                        </a>
                    </div>
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-600">Buscar en el centro de ayuda</h2>
                    <form id="help-search" action="#" method="get" class="mt-4 space-y-3">
                        <div>
                            <label class="sr-only" for="help-query">Buscar</label>
                            <input id="help-query" type="search" name="q" placeholder="Escribe palabras clave (p. ej. recibos, meta, IA)" class="w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500">
                        </div>
                        <button type="submit" class="inline-flex w-full justify-center rounded-md bg-marino-600 px-4 py-2 text-sm font-medium text-white hover:bg-marino-700">
                            Explorar artículos
                        </button>
                    </form>
                    <p class="mt-3 text-xs text-gray-500">Estamos indexando el contenido final. Por ahora, revisa las categorías destacadas o escríbenos directamente.</p>
                </div>
            </div>
        </section>

        <section class="space-y-6">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-900">Guías destacadas</h2>
                <a href="#" class="text-sm font-medium text-copihue-600 hover:text-copihue-700">Ver índice completo</a>
            </div>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($help_categories as $category): ?>
                    <article class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($category['title']) ?></h3>
                        <p class="mt-2 text-sm text-gray-600"><?= htmlspecialchars($category['description']) ?></p>
                        <ul class="mt-4 space-y-2 text-sm text-copihue-600">
                            <?php foreach ($category['articles'] as $article): ?>
                                <li>
                                    <a href="<?= htmlspecialchars($article['href']) ?>" class="inline-flex items-center hover:text-copihue-700">
                                        <?= htmlspecialchars($article['title']) ?>
                                        <svg class="ml-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="bg-white rounded-lg shadow p-8">
            <h2 class="text-2xl font-semibold text-gray-900">Respuestas rápidas</h2>
            <div class="mt-6 grid gap-6 md:grid-cols-2">
                <?php foreach ($quick_answers as $question => $answer): ?>
                    <article class="border border-gray-200 rounded-lg p-5">
                        <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($question) ?></h3>
                        <p class="mt-2 text-sm text-gray-600 leading-6"><?= htmlspecialchars($answer) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="mt-6 rounded-lg bg-gray-50 border border-gray-200 p-5 text-sm text-gray-600">
                ¿No encontraste lo que buscabas? Escríbenos a <a class="text-copihue-600 font-medium" href="mailto:<?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?>"><?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?></a> o revisa el formulario de <a class="text-copihue-600 font-medium" href="<?= Router::url('reportar') ?>">reportar un problema</a>.
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../layouts/partials/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('help-search');
        if (!form) return;

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            alert('Estamos finalizando la indexación del contenido. Mientras tanto, navega por las categorías destacadas o escríbenos a <?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?>.');
        });
    });
    </script>
</body>
</html>
