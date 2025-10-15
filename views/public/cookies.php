<?php
require_once __DIR__ . '/../components/navigation.php';

$current_page = $current_page ?? 'cookies';

$page_title = 'Política de Cookies - Lucatón';
$page_description = 'Conoce qué cookies utilizamos en Lucatón, para qué sirven y cómo puedes administrarlas.';
$last_updated = '29 de septiembre de 2025';
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

    <main id="main-content" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <?= render_breadcrumb([
            ['name' => 'Inicio', 'href' => Router::url('/')],
            ['name' => 'Política de Cookies', 'href' => Router::url('cookies')],
        ]); ?>

        <div class="lg:grid lg:grid-cols-4 lg:gap-8">
            <aside class="lg:col-span-1 mb-8 lg:mb-0">
                <div class="bg-white shadow rounded-lg p-6 sticky top-8">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Contenido</h2>
                    <nav class="space-y-2 text-sm">
                        <a href="#intro" class="block text-gray-600 hover:text-copihue-600">1. Introducción</a>
                        <a href="#definition" class="block text-gray-600 hover:text-copihue-600">2. ¿Qué son las cookies?</a>
                        <a href="#types" class="block text-gray-600 hover:text-copihue-600">3. Tipos de cookies que usamos</a>
                        <a href="#purposes" class="block text-gray-600 hover:text-copihue-600">4. Finalidades del uso</a>
                        <a href="#management" class="block text-gray-600 hover:text-copihue-600">5. Cómo gestionarlas</a>
                        <a href="#third-parties" class="block text-gray-600 hover:text-copihue-600">6. Servicios de terceros</a>
                        <a href="#updates" class="block text-gray-600 hover:text-copihue-600">7. Cambios a esta política</a>
                        <a href="#contact" class="block text-gray-600 hover:text-copihue-600">8. Contacto</a>
                    </nav>
                </div>
            </aside>

            <section class="lg:col-span-3">
                <article class="bg-white shadow rounded-lg p-8">
                    <header class="mb-8">
                        <h1 class="text-3xl font-bold text-gray-900 mb-4">Política de Cookies</h1>
                        <p class="text-sm text-gray-500">Última actualización: <?= htmlspecialchars($last_updated) ?></p>
                        <p class="mt-2 text-sm text-gray-500"><?= htmlspecialchars(PROJECT_OWNER_NAME) ?>. <?= htmlspecialchars(PROJECT_DISCLAIMER) ?></p>
                        <p class="mt-3 text-gray-600">
                            En esta página te explicamos cómo y por qué Lucatón utiliza cookies y tecnologías similares. Queremos que tomes decisiones informadas sobre tus datos y tengas el control de tu experiencia.
                        </p>
                    </header>

                    <div class="prose max-w-none text-gray-700">
                        <section id="intro" class="mb-10">
                            <h2 class="text-2xl font-semibold text-gray-900">1. Introducción</h2>
                            <p>Esta política complementa nuestra <a class="text-copihue-600 font-medium" href="<?= Router::url('privacidad') ?>">Política de Privacidad</a>. Cuando visitas <?= htmlspecialchars(APP_URL) ?> o utilizas nuestros servicios digitales, almacenamos cookies en tu navegador para ofrecerte una experiencia segura, rápida y personalizada dentro del contexto del proyecto académico.</p>
                        </section>

                        <section id="definition" class="mb-10">
                            <h2 class="text-2xl font-semibold text-gray-900">2. ¿Qué son las cookies?</h2>
                            <p>Las cookies son pequeños archivos de texto que un sitio web guarda en tu dispositivo. Permiten recordar tus preferencias, mantener tu sesión activa y comprender cómo se utiliza la plataforma. Algunas cookies son necesarias para que el sitio funcione y otras nos ayudan a mejorar.</p>
                        </section>

                        <section id="types" class="mb-10">
                            <h2 class="text-2xl font-semibold text-gray-900">3. Tipos de cookies que usamos</h2>
                            <div class="overflow-hidden rounded-lg border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duración</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr>
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900">Estrictamente necesarias</td>
                                            <td class="px-6 py-4 text-sm text-gray-600">Permiten iniciar sesión, proteger tu cuenta y acceder a áreas seguras. Sin ellas el sitio no funciona correctamente.</td>
                                            <td class="px-6 py-4 text-sm text-gray-600">Caducan al cerrar sesión o después de 12 meses.</td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900">De preferencias</td>
                                            <td class="px-6 py-4 text-sm text-gray-600">Guardan idioma, región y recordatorios sobre campañas recientes o formularios en progreso.</td>
                                            <td class="px-6 py-4 text-sm text-gray-600">Entre 6 y 12 meses.</td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900">Analíticas</td>
                                            <td class="px-6 py-4 text-sm text-gray-600">Nos ayudan a entender qué secciones se usan más y detectar errores para mejorar tu experiencia.</td>
                                            <td class="px-6 py-4 text-sm text-gray-600">Varía entre sesión y 24 meses.</td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900">Funcionales</td>
                                            <td class="px-6 py-4 text-sm text-gray-600">Activan funciones opcionales como el guardado de borradores de campañas o el asistente IA.</td>
                                            <td class="px-6 py-4 text-sm text-gray-600">Hasta 30 días.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section id="purposes" class="mb-10">
                            <h2 class="text-2xl font-semibold text-gray-900">4. Finalidades del uso de cookies</h2>
                            <ul class="mt-4 space-y-3 list-disc pl-6">
                                <li><strong>Seguridad:</strong> detectar accesos sospechosos, prevenir abusos y proteger tus sesiones.</li>
                                <li><strong>Experiencia personalizada:</strong> recordar tus últimas campañas, preferencias de idioma y rutas más visitadas.</li>
                                <li><strong>Analítica ética:</strong> medir visitas agregadas para orientar mejoras. No vendemos ni compartimos identificadores con terceros.</li>
                                <li><strong>Soporte:</strong> recordar si ya mostramos mensajes informativos para no saturarte con banners repetidos.</li>
                            </ul>
                        </section>

                        <section id="management" class="mb-10">
                            <h2 class="text-2xl font-semibold text-gray-900">5. Cómo gestionar tus cookies</h2>
                            <p>Puedes gestionar y eliminar cookies desde la configuración de tu navegador. Si deseas restringirlas, considera lo siguiente:</p>
                            <ul class="mt-4 space-y-3 list-disc pl-6">
                                <li>Desactivar cookies estrictamente necesarias puede impedir que inicies sesión o completes donaciones.</li>
                                <li>Puedes usar la navegación privada para limitar el almacenamiento permanente.</li>
                                <li>La mayoría de los navegadores permiten aceptar o rechazar cookies por sitio. Consulta la ayuda de Chrome, Firefox, Safari o Edge para más detalles.</li>
                            </ul>
                        </section>

                        <section id="third-parties" class="mb-10">
                            <h2 class="text-2xl font-semibold text-gray-900">6. Servicios de terceros</h2>
                            <p>Lucatón utiliza integraciones que también pueden establecer cookies propias para funcionar:</p>
                            <ul class="mt-4 space-y-3 list-disc pl-6">
                                <li><strong>Pasarela de pagos:</strong> mantiene medidas antifraude cuando completes aportes.</li>
                                <li><strong>Herramientas analíticas propias:</strong> se alojan en infraestructura de Lucatón para evitar compartir datos con terceros.</li>
                                <li><strong>Chat de soporte:</strong> si lo activas, guarda tu nombre y correo para agilizar la conversación.</li>
                            </ul>
                            <p class="mt-3">No alojamos cookies publicitarias ni rastreadores de redes sociales. Si en el futuro incorporamos un nuevo servicio, lo informaremos en esta página antes de habilitarlo.</p>
                        </section>

                        <section id="updates" class="mb-10">
                            <h2 class="text-2xl font-semibold text-gray-900">7. Cambios a esta política</h2>
                            <p>Actualizaremos esta política si agregamos nuevos tipos de cookies o cambiamos su finalidad. Publicaremos la nueva versión con fecha de actualización y, si la modificación es relevante, te enviaremos una notificación en el panel.</p>
                        </section>

                        <section id="contact" class="mb-6">
                            <h2 class="text-2xl font-semibold text-gray-900">8. Contacto</h2>
                            <p>Si tienes dudas sobre el uso de cookies o quieres ejercer tus derechos de privacidad, escríbenos a <a class="text-copihue-600 font-medium" href="mailto:<?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?>"><?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?></a>. <?= htmlspecialchars(PROJECT_DISCLAIMER) ?></p>
                        </section>
                    </div>
                </article>
            </section>
        </div>
    </main>

    <?php include __DIR__ . '/../layouts/partials/footer.php'; ?>
</body>
</html>
