<?php
require_once __DIR__ . '/../components/navigation.php';

$current_page = $current_page ?? 'contact';

$page_title = 'Contacto - Lucatón';
$page_description = 'Escríbenos para resolver dudas sobre campañas, donaciones o alianzas con Lucatón.';

$contact_channels = [
    [
        'title' => 'Soporte general (prototipo)',
        'description' => 'Escenario de prueba para validar el flujo de soporte dentro del proyecto académico.',
        'email' => PROJECT_OWNER_EMAIL,
        'mail_subject' => 'Lucatón Tesis - Soporte general',
        'sla' => 'Respuesta académica en menos de 48 horas hábiles.',
    ],
    [
        'title' => 'Alianzas y organizaciones (demo)',
        'description' => 'Casos de estudio para municipalidades, fundaciones o empresas dentro del prototipo.',
        'email' => PROJECT_OWNER_EMAIL,
        'mail_subject' => 'Lucatón Tesis - Consulta de alianzas',
        'sla' => 'Coordinación académica en 72 horas hábiles.',
    ],
    [
        'title' => 'Prensa y medios (simulación)',
        'description' => 'Contacto referencial para validar el flujo comunicacional del prototipo.',
        'email' => PROJECT_OWNER_EMAIL,
        'mail_subject' => 'Lucatón Tesis - Consulta de prensa',
        'sla' => 'Respuesta académica en 48 horas hábiles.',
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

    <link rel="icon" type="image/svg+xml" href="<?= asset_url('images/favicon.svg') ?>">
    <link href="<?= asset_url('css/app.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('css/aliases.css') ?>" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <?php include __DIR__ . '/../layouts/partials/header.php'; ?>

    <main id="main-content" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">
        <?= render_breadcrumb([
            ['name' => 'Inicio', 'href' => Router::url('/')],
            ['name' => 'Contacto', 'href' => Router::url('contacto')],
        ]); ?>

        <section class="bg-white rounded-lg shadow p-8">
            <div class="grid gap-10 lg:grid-cols-2">
                <div>
                    <p class="text-sm uppercase tracking-wide text-gray-500">Estamos para ayudarte</p>
                    <h1 class="mt-2 text-3xl font-bold text-gray-900">Hablemos</h1>
                    <p class="mt-2 text-sm text-gray-600">Cuéntanos en qué podemos apoyarte y la persona indicada de nuestro equipo se pondrá en contacto.</p>
                    <p class="mt-2 text-xs text-gray-500"><?= htmlspecialchars(PROJECT_OWNER_NAME) ?>. <?= htmlspecialchars(PROJECT_DISCLAIMER) ?> Todos los canales comparten el correo académico <a class="text-copihue-600 font-medium" href="mailto:<?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?>"><?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?></a>.</p>

                    <div class="mt-6 space-y-4">
                        <?php foreach ($contact_channels as $channel): 
                            $channelEmail = $channel['email'] ?? PROJECT_OWNER_EMAIL;
                            $channelSubject = $channel['mail_subject'] ?? ($channel['title'] ?? 'Consulta Lucatón Tesis');
                            $mailtoHref = 'mailto:' . $channelEmail;
                            if (!empty($channelSubject)) {
                                $mailtoHref .= '?subject=' . rawurlencode($channelSubject);
                            }
                        ?>
                            <article class="rounded-lg border border-gray-200 p-5">
                                <h2 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($channel['title']) ?></h2>
                                <p class="mt-1 text-sm text-gray-600"><?= htmlspecialchars($channel['description']) ?></p>
                                <a class="mt-3 inline-flex items-center text-copihue-600 font-semibold" href="<?= htmlspecialchars($mailtoHref) ?>">
                                    <?= htmlspecialchars($channelEmail) ?>
                                    <svg class="ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                                <p class="mt-1 text-xs text-gray-500"><?= htmlspecialchars($channel['sla']) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-6 rounded-lg bg-gray-50 border border-gray-200 p-5 text-sm text-gray-600">
                        ¿Sabías que el <a class="text-copihue-600 font-medium" href="<?= Router::url('ayuda') ?>">Centro de ayuda</a> responde más del 70% de las consultas en menos de 5 minutos? Revisa las guías antes de escribirnos y podrás resolver tu duda al instante.
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">Formulario de contacto</h2>
                    <p class="mt-1 text-sm text-gray-600">Pronto habilitaremos el envío directo. Por ahora, envíanos este mensaje y te responderemos por correo.</p>

                    <form id="contact-form" action="#" method="post" class="mt-6 space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="contact-name">Nombre</label>
                            <input id="contact-name" name="name" type="text" required placeholder="Tu nombre" class="mt-1 w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="contact-email">Correo electrónico</label>
                            <input id="contact-email" name="email" type="email" required placeholder="tu@correo.cl" class="mt-1 w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="contact-topic">Motivo</label>
                            <select id="contact-topic" name="topic" required class="mt-1 w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500">
                                <option value="">Selecciona una opción</option>
                                <option value="campanas">Tengo dudas sobre mi campaña</option>
                                <option value="donaciones">Necesito ayuda con una donación</option>
                                <option value="alianza">Quiero proponer una alianza</option>
                                <option value="prensa">Prensa o medios</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="contact-message">Mensaje</label>
                            <textarea id="contact-message" name="message" rows="4" required placeholder="Escribe tu mensaje con el máximo de detalle" class="mt-1 w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500"></textarea>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <button type="submit" class="inline-flex justify-center rounded-md bg-copihue-600 px-4 py-2 text-sm font-medium text-white hover:bg-copihue-700">
                                Enviar mensaje
                            </button>
                            <p class="text-xs text-gray-500">Responderemos a tu correo sin utilizar tus datos para otros fines.</p>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <section class="grid gap-6 md:grid-cols-3">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900">Horario de soporte</h3>
                <p class="mt-2 text-sm text-gray-600">Lunes a viernes<br>09:00 a 18:00 (CLT)</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900">Chat en vivo</h3>
                <p class="mt-2 text-sm text-gray-600">Disponible para usuarios registrados desde el panel. Conversa con una persona real.</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900">Dirección referencial</h3>
                <p class="mt-2 text-sm text-gray-600">Universidad Bernardo O'Higgins<br>General Gana 1780, Santiago, Chile (uso académico)</p>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../layouts/partials/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('contact-form');
        if (!form) return;

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.textContent = 'Mensaje enviado';
            }
            form.reset();
            alert('Gracias por tu mensaje. Aún estamos integrando el envío desde la plataforma; por favor espera nuestro correo desde <?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?>.');
        });
    });
    </script>
</body>
</html>
