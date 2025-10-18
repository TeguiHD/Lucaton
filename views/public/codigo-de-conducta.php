<?php
require_once __DIR__ . '/../components/navigation.php';

$current_page = $current_page ?? 'code_of_conduct';

$page_title = 'Código de Conducta - Lucatón';
$page_description = 'Compromisos y lineamientos para mantener una comunidad segura, respetuosa y honesta en Lucatón.';
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
            ['name' => 'Código de Conducta', 'href' => Router::url('codigo-conducta')],
        ]); ?>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <header class="px-6 sm:px-10 pt-10 pb-6 border-b border-gray-100 bg-gradient-to-br from-white to-gray-50">
                <p class="text-sm uppercase tracking-wide text-copihue-600 font-semibold">Nuestro compromiso comunitario</p>
                <h1 class="mt-3 text-3xl font-bold text-gray-900">Código de Conducta Lucatón</h1>
                <p class="mt-2 text-sm text-gray-500">Última actualización: <?= htmlspecialchars($last_updated) ?></p>
                <p class="mt-4 text-gray-600 max-w-3xl">
                    Lucatón reúne a personas que quieren ayudar y a quienes buscan apoyo para causas con impacto social. Para resguardar esa confianza, establecimos este código que aplica a todas las personas usuarias, equipos de campaña, donantes y colaboradores.
                </p>
                <p class="mt-3 text-sm text-gray-500">
                    Este documento forma parte de <?= htmlspecialchars(PROJECT_OWNER_NAME) ?>. <?= htmlspecialchars(PROJECT_DISCLAIMER) ?>
                </p>
            </header>

            <div class="px-6 sm:px-10 py-10 space-y-12 text-gray-700">
                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">1. Principios generales</h2>
                    <ul class="space-y-3 list-disc pl-6">
                        <li><strong>Integridad:</strong> describe tu campaña con honestidad, entrega información verificable y respeta lo comprometido con tus donantes.</li>
                        <li><strong>Respeto:</strong> trata a todas las personas con dignidad, evita lenguaje discriminatorio, agresivo o degradante.</li>
                        <li><strong>Transparencia:</strong> comunica avances, cambios y dificultades de manera oportuna. Si tu campaña ya no necesita aportes, ciérrala o actualiza su meta.</li>
                        <li><strong>Seguridad:</strong> protege datos personales, evita compartir información sensible de terceras personas sin consentimiento y denuncia actividades sospechosas.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">2. Reglas para campañas</h2>
                    <div class="space-y-4">
                        <p>El contenido de campaña debe cumplir con nuestra misión social y los lineamientos legales vigentes en Chile. Esto implica:</p>
                        <ul class="space-y-3 list-disc pl-6">
                            <li>No se permiten campañas que promuevan odio, violencia, discriminación, desinformación o actividades ilegales.</li>
                            <li>Debes contar con respaldo para la información que compartes (certificados médicos, presupuestos, comprobantes). Lucatón puede solicitarlos en cualquier momento.</li>
                            <li>Las imágenes y videos deben respetar la dignidad de las personas beneficiadas, evitando sensacionalismo o exposición innecesaria.</li>
                            <li>Si utilizas material con derechos de autor, asegúrate de contar con permisos válidos.</li>
                        </ul>
                        <p class="text-sm text-gray-600">Si detectamos una infracción podremos suspender la campaña, solicitar correcciones o eliminarla de forma definitiva.</p>
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">3. Interacciones y comunidad</h2>
                    <ul class="space-y-3 list-disc pl-6">
                        <li>Usa los espacios de comentarios para aportar, resolver dudas o agradecer. No toleramos acoso, spam ni mensajes ofensivos.</li>
                        <li>No compartas datos de contacto de otras personas sin su autorización explícita.</li>
                        <li>Si recibes mensajes abusivos, repórtalos usando el formulario de <a class="text-copihue-600 font-medium" href="<?= Router::url('reportar') ?>">reportar problema</a>.</li>
                        <li>Las actualizaciones deben enfocarse en información relevante y verificada. Evita promesas exageradas o engañosas.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">4. Contribuciones y pagos</h2>
                    <p>Como donante, confías en que la campaña usará los fondos de la forma descrita. Para asegurar esa confianza:</p>
                    <ul class="mt-4 space-y-3 list-disc pl-6">
                        <li>Usa métodos de pago legítimos y evita ofrecer incentivos financieros a cambio de donaciones.</li>
                        <li>No intentes recuperar donaciones por vías fraudulentas ni compartas comprobantes que incluyan datos personales de terceros.</li>
                        <li>Si detectas un cobro no autorizado o sospechas de fraude, contáctanos inmediatamente en <a class="text-copihue-600 font-medium" href="mailto:<?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?>"><?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?></a>.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">5. Uso de herramientas con IA</h2>
                    <p>Nuestras herramientas con inteligencia artificial están diseñadas para asistirte de forma responsable:</p>
                    <ul class="mt-4 space-y-3 list-disc pl-6">
                        <li>No utilices la IA para generar contenido engañoso, ofensivo o que incumpla las leyes.</li>
                        <li>Revisa y valida cada sugerencia antes de publicarla. Eres responsable final del material compartido.</li>
                        <li>Reporta cualquier salida incorrecta o sesgada para que podamos mejorar los modelos y proteger a la comunidad.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">6. Consecuencias ante incumplimientos</h2>
                    <p>Si detectamos una conducta que viola este código, aplicaremos medidas proporcionales:</p>
                    <ol class="mt-4 space-y-3 list-decimal pl-6">
                        <li><strong>Advertencia:</strong> solicitaremos correcciones o eliminaremos contenido puntual.</li>
                        <li><strong>Suspensión temporal:</strong> bloquearemos la campaña o cuenta hasta aclarar la situación.</li>
                        <li><strong>Suspensión definitiva:</strong> en casos graves o reiterados, cerraremos la cuenta y notificaremos a autoridades si corresponde.</li>
                    </ol>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">7. Protección de datos personales y derechos ARCO</h2>
                    <p>La comunidad de Lucatón debe resguardar la privacidad de todas las personas involucradas. Esto implica:</p>
                    <ul class="mt-4 space-y-3 list-disc pl-6">
                        <li>Recopilar o compartir datos personales solo cuando cuentes con consentimiento informado y sea estrictamente necesario para la campaña.</li>
                        <li>Eliminar documentos, capturas o listados que contengan información sensible una vez cumplida la finalidad para la que fueron enviados.</li>
                        <li>Respetar los derechos de acceso, rectificación, cancelación y oposición (ARCO) de donantes, beneficiarios y colaboradores. Si recibes una solicitud, notifícanos inmediatamente.</li>
                    </ul>
                    <p class="mt-3 text-gray-700">Puedes ejercer tus propios derechos ARCO escribiendo a <a class="text-copihue-600 font-medium" href="mailto:<?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?>"><?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?></a> con el asunto “Solicitud Derechos ARCO”. Responderemos en los plazos legales y mantendremos trazabilidad del requerimiento.</p>
                    <p class="mt-3 text-sm text-gray-600">El detalle del procedimiento está disponible en la Política de Privacidad y en el Centro de Ayuda. Cualquier represalia contra quienes ejerzan sus derechos está estrictamente prohibida.</p>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">8. Cómo solicitar ayuda o hacer observaciones</h2>
                    <div class="space-y-3">
                        <p>Queremos mejorar este código junto a la comunidad. Si tienes comentarios o necesitas orientación:</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li>Visita el <a class="text-copihue-600 font-medium" href="<?= Router::url('ayuda') ?>">Centro de Ayuda</a> para resolver dudas frecuentes.</li>
                            <li>Escríbenos a <a class="text-copihue-600 font-medium" href="mailto:<?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?>"><?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?></a> con sugerencias o denuncias.</li>
                            <li>Usa el formulario de <a class="text-copihue-600 font-medium" href="<?= Router::url('reportar') ?>">reportar problema</a> si detectas incumplimientos.</li>
                        </ul>
                        <p class="text-sm text-gray-500">Respondemos habitualmente en menos de 24 horas hábiles. Tu reporte puede mantenerse anónimo si lo prefieres.</p>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../layouts/partials/footer.php'; ?>
</body>
</html>
