<?php
require_once __DIR__ . '/../components/navigation.php';

$current_page = $current_page ?? 'system_status';

$page_title = 'Estado del Sistema - Lucatón';
$page_description = 'Revisa la disponibilidad actual de la plataforma Lucatón, tiempos de respuesta y mantenimientos programados.';

$services = [
    [
        'name' => 'Sitio web y landing públicas',
        'status' => 'operativo',
        'badge_class' => 'bg-green-100 text-green-800',
        'description' => 'Portal público, campañas y páginas informativas.',
        'metrics' => [
            'Uptime 90 días' => '99.92%',
            'Tiempo promedio de carga' => '1.2 s'
        ],
    ],
    [
        'name' => 'Panel de usuarios y administración',
        'status' => 'operativo',
        'badge_class' => 'bg-green-100 text-green-800',
        'description' => 'Dashboard, creación de campañas y moderación.',
        'metrics' => [
            'Uptime 90 días' => '99.87%',
            'Latencia promedio' => '480 ms'
        ],
    ],
    [
        'name' => 'Procesamiento de pagos',
        'status' => 'intermitencias menores',
        'badge_class' => 'bg-yellow-100 text-yellow-800',
        'description' => 'Pasarela de pagos, conciliaciones y confirmaciones bancarias.',
        'metrics' => [
            'Tasa de éxito' => '98.5%',
            'Último incidente' => '27/09/2025'
        ],
    ],
    [
        'name' => 'Asistentes IA y generación de contenido',
        'status' => 'operativo',
        'badge_class' => 'bg-green-100 text-green-800',
        'description' => 'Redacción asistida, imágenes y moderación automática.',
        'metrics' => [
            'Disponibilidad' => '99.5%',
            'Peticiones/minuto' => '120'
        ],
    ],
    [
        'name' => 'Notificaciones y correos',
        'status' => 'operativo',
        'badge_class' => 'bg-green-100 text-green-800',
        'description' => 'Correos transaccionales, alertas y recordatorios.',
        'metrics' => [
            'Entrega en 5 min' => '97%',
            'Último retraso' => '12/09/2025'
        ],
    ],
];

$incidents = [
    [
        'date' => '27 de septiembre de 2025',
        'title' => 'Retrasos en confirmación de pagos con Banco Estado',
        'impact' => 'moderado',
        'description' => 'La pasarela externa reportó tiempos de conciliación superiores a lo habitual. Se aplicó reintento automático y notificamos a las campañas afectadas.',
        'resolution' => 'Servicios normalizados a las 16:45 CLT. Ninguna donación se perdió.',
        'status_class' => 'bg-yellow-100 text-yellow-800',
    ],
    [
        'date' => '9 de septiembre de 2025',
        'title' => 'Mantenimiento programado del panel de administración',
        'impact' => 'bajo',
        'description' => 'Desplegamos mejoras en reportes y verificación de campañas. El panel estuvo en modo sólo lectura por 20 minutos.',
        'resolution' => 'Actualización completada sin incidentes. Nuevas métricas disponibles para todos los administradores.',
        'status_class' => 'bg-blue-100 text-blue-800',
    ],
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
            ['name' => 'Estado del Sistema', 'href' => Router::url('estado')],
        ]); ?>

        <section class="bg-white rounded-lg shadow p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <p class="text-sm uppercase tracking-wide text-gray-500">Disponibilidad actual</p>
                    <h1 class="mt-2 text-3xl font-bold text-gray-900">Todos los sistemas operativos</h1>
                    <p class="mt-2 text-sm text-gray-600 max-w-2xl">
                        Monitoreamos la plataforma continuamente. Si ocurre un incidente, lo publicaremos aquí con actualizaciones periódicas y un resumen transparente una vez resuelto.
                    </p>
                </div>
                <div class="flex flex-col items-start md:items-end gap-2">
                    <span class="inline-flex items-center rounded-full bg-green-100 px-4 py-1 text-sm font-semibold text-green-700">
                        <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Operando normalmente
                    </span>
                    <p class="text-xs text-gray-500">Última actualización: <?= date('d/m/Y H:i') ?> CLT</p>
                </div>
            </div>
        </section>

        <section class="bg-white rounded-lg shadow p-8">
            <header class="mb-6">
                <h2 class="text-2xl font-semibold text-gray-900">Servicios monitoreados</h2>
                <p class="mt-1 text-sm text-gray-600">Cada servicio cuenta con métricas de uptime y alertas automáticas.</p>
            </header>
            <div class="grid gap-6 md:grid-cols-2">
                <?php foreach ($services as $service): ?>
                    <article class="border border-gray-200 rounded-lg p-6 h-full">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($service['name']) ?></h3>
                                <p class="mt-1 text-sm text-gray-600"><?= htmlspecialchars($service['description']) ?></p>
                            </div>
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium <?= $service['badge_class'] ?>">
                                <?= htmlspecialchars(ucfirst($service['status'])) ?>
                            </span>
                        </div>
                        <dl class="mt-4 grid grid-cols-1 gap-3 text-sm text-gray-600">
                            <?php foreach ($service['metrics'] as $label => $value): ?>
                                <div class="flex items-center justify-between">
                                    <dt class="font-medium text-gray-900"><?= htmlspecialchars($label) ?></dt>
                                    <dd><?= htmlspecialchars($value) ?></dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="bg-white rounded-lg shadow p-8">
            <header class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900">Historial reciente</h2>
                    <p class="mt-1 text-sm text-gray-600">Incidentes últimos 30 días y mantenimientos programados.</p>
                </div>
                <a href="<?= Router::url('reportar') ?>" class="inline-flex items-center rounded-md bg-copihue-600 px-4 py-2 text-sm font-medium text-white hover:bg-copihue-700">
                    Reportar un incidente
                </a>
            </header>

            <?php if (empty($incidents)): ?>
                <p class="text-sm text-gray-600">¡Excelente! No hemos registrado incidentes en los últimos 90 días.</p>
            <?php else: ?>
                <div class="flow-root">
                    <ul role="list" class="-mb-8">
                        <?php foreach ($incidents as $incident): ?>
                            <li>
                                <div class="relative pb-8">
                                    <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                    <div class="relative flex items-start space-x-3">
                                        <div>
                                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-copihue-100 text-copihue-600">
                                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                                <p class="text-base font-medium text-gray-900"><?= htmlspecialchars($incident['title']) ?></p>
                                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium <?= $incident['status_class'] ?>">
                                                    Impacto <?= htmlspecialchars($incident['impact']) ?>
                                                </span>
                                            </div>
                                            <p class="mt-1 text-sm text-gray-500"><?= htmlspecialchars($incident['date']) ?></p>
                                            <p class="mt-3 text-sm text-gray-600 leading-6"><?= htmlspecialchars($incident['description']) ?></p>
                                            <p class="mt-2 text-sm font-medium text-gray-700">Resolución</p>
                                            <p class="text-sm text-gray-600"><?= htmlspecialchars($incident['resolution']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </section>

        <section class="bg-white rounded-lg shadow p-8">
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Notificaciones proactivas</h2>
                    <p class="mt-2 text-sm text-gray-600">Suscríbete para recibir correos cuando publiquemos incidentes críticos.</p>
                    <form class="mt-4 flex flex-col sm:flex-row gap-3" id="status-subscription" action="#" method="post">
                        <label class="sr-only" for="status-email">Correo electrónico</label>
                        <input id="status-email" type="email" name="email" required placeholder="tu@correo.cl" class="w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500">
                        <button type="submit" class="inline-flex justify-center rounded-md bg-copihue-600 px-4 py-2 text-sm font-medium text-white hover:bg-copihue-700">
                            Quiero suscribirme
                        </button>
                    </form>
                    <p class="mt-3 text-xs text-gray-500">Respetamos tu privacidad. Puedes cancelar la suscripción en cualquier momento.</p>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">¿Necesitas reportar algo urgente?</h2>
                    <p class="mt-2 text-sm text-gray-600">Escríbenos a <a class="text-copihue-600 font-medium" href="mailto:infra@lucaton.cl">infra@lucaton.cl</a> o llama al +56 2 1234 5678 (lun-vie 09:00-18:00).</p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600 list-disc pl-5">
                        <li>Detalla el servicio afectado y si el problema se mantiene.</li>
                        <li>Agrega capturas o códigos de error si los tienes.</li>
                        <li>Recibirás una respuesta inicial en menos de 1 hora hábil.</li>
                    </ul>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../layouts/partials/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('status-subscription');
        if (!form) return;

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const button = form.querySelector('button[type="submit"]');
            const emailInput = form.querySelector('input[type="email"]');
            if (button) {
                button.disabled = true;
                button.textContent = '¡Listo!';
            }
            if (emailInput) {
                emailInput.value = '';
            }
            alert('Gracias por tu interés. Pronto habilitaremos el envío automático de alertas.');
        });
    });
    </script>
</body>
</html>
