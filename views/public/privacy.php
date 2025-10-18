<?php
require_once __DIR__ . '/../components/navigation.php';

$current_page = $current_page ?? 'privacy';

$page_title = 'Política de Privacidad - Lucatón';
$page_description = 'Política de privacidad de Lucatón. Conoce cómo protegemos y utilizamos tu información personal.';

$last_updated = '29 de septiembre de 2025';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:title" content="<?php echo $page_title; ?>">
    <meta property="og:description" content="<?php echo $page_description; ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary">
    <meta property="twitter:url" content="<?php echo $_SERVER['REQUEST_URI']; ?>">
    <meta property="twitter:title" content="<?php echo $page_title; ?>">
    <meta property="twitter:description" content="<?php echo $page_description; ?>">

    <!-- Favicons -->
    <link rel="icon" type="image/svg+xml" href="<?= asset_url('images/favicon.svg') ?>">

    <!-- Styles -->
    <link href="<?= asset_url('css/app.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('css/aliases.css') ?>" rel="stylesheet">
    
    
</head>
<body class="bg-gray-50">
    <!-- Skip to content link -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <!-- Header -->
    <?php include __DIR__ . '/../layouts/partials/header.php'; ?>

    <!-- Main Content -->
    <main id="main-content" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumb -->
        <?php echo render_breadcrumb([
            ['name' => 'Inicio', 'href' => Router::url('/')],
            ['name' => 'Política de Privacidad', 'href' => Router::url('privacidad')]
        ]); ?>

        <div class="lg:grid lg:grid-cols-4 lg:gap-8">
            <!-- Table of Contents -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow rounded-lg p-6 sticky top-8">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Contenido</h2>
                    <nav class="space-y-2">
                        <a href="#introduction" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">1. Introducción</a>
                        <a href="#information" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">2. Información que Recopilamos</a>
                        <a href="#usage" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">3. Cómo Usamos la Información</a>
                        <a href="#sharing" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">4. Compartir Información</a>
                        <a href="#security" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">5. Seguridad de Datos</a>
                        <a href="#retention" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">6. Retención de Datos</a>
                        <a href="#rights" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">7. Derechos ARCO y otras facultades</a>
                        <a href="#cookies" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">8. Cookies y Tecnologías</a>
                        <a href="#minors" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">9. Menores de Edad</a>
                        <a href="#international" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">10. Transferencias Internacionales</a>
                        <a href="#changes" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">11. Cambios a esta Política</a>
                        <a href="#contact" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">12. Contacto</a>
                    </nav>
                </div>
            </div>

            <!-- Content -->
            <div class="lg:col-span-3 mt-8 lg:mt-0">
                <div class="bg-white shadow rounded-lg p-8">
                    <!-- Header -->
                    <div class="mb-8">
                        <h1 class="text-3xl font-bold text-gray-900 mb-4">
                            Política de Privacidad
                        </h1>
                        <p class="text-gray-600">
                            Última actualización: <?php echo $last_updated; ?>
                        </p>
                        <p class="mt-2 text-sm text-gray-500"><?= htmlspecialchars(PROJECT_OWNER_NAME) ?>. <?= htmlspecialchars(PROJECT_DISCLAIMER) ?></p>
                    </div>

                    <!-- Content Sections -->
                    <div class="prose max-w-none">
                        <!-- 1. Introducción -->
                        <section id="introduction" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">1. Introducción</h2>
                            <p class="text-gray-700 mb-4">
                                En <?= htmlspecialchars(PROJECT_OWNER_NAME) ?> ("Lucatón", "nosotros", "nuestro"), respetamos su privacidad y nos comprometemos a proteger su información personal. Esta Política de Privacidad explica cómo recopilamos, utilizamos, compartimos y protegemos su información cuando utiliza nuestra plataforma como parte de un prototipo académico.
                            </p>
                            <p class="text-gray-700">
                                Al utilizar nuestros servicios, usted acepta las prácticas descritas en esta Política de Privacidad. Si no está de acuerdo con estas prácticas, no utilice nuestros servicios.
                            </p>
                        </section>

                        <!-- 2. Información que Recopilamos -->
                        <section id="information" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">2. Información que Recopilamos</h2>
                            
                            <h3 class="text-lg font-medium text-gray-900 mb-2">2.1 Información que Usted Proporciona</h3>
                            <ul class="list-disc pl-6 space-y-2 text-gray-700 mb-4">
                                <li><strong>Información de registro:</strong> Nombre, email, contraseña, fecha de nacimiento</li>
                                <li><strong>Información de perfil:</strong> Biografía, foto de perfil, ubicación</li>
                                <li><strong>Información de campañas:</strong> Títulos, descripciones, imágenes, videos, metas de recaudación</li>
                                <li><strong>Información de pago:</strong> Datos bancarios, información de tarjetas (procesada por terceros)</li>
                                <li><strong>Comunicaciones:</strong> Mensajes, comentarios, actualizaciones de campañas</li>
                                <li><strong>Documentos de verificación:</strong> Cédula de identidad, comprobantes de domicilio</li>
                            </ul>

                            <h3 class="text-lg font-medium text-gray-900 mb-2">2.2 Información Recopilada Automáticamente</h3>
                            <ul class="list-disc pl-6 space-y-2 text-gray-700 mb-4">
                                <li><strong>Información técnica:</strong> Dirección IP, tipo de navegador, sistema operativo</li>
                                <li><strong>Datos de uso:</strong> Páginas visitadas, tiempo de permanencia, clics, búsquedas</li>
                                <li><strong>Información del dispositivo:</strong> Identificadores únicos, configuración de idioma</li>
                                <li><strong>Datos de ubicación:</strong> Ubicación aproximada basada en IP</li>
                            </ul>

                            <h3 class="text-lg font-medium text-gray-900 mb-2">2.3 Información de Terceros</h3>
                            <p class="text-gray-700">
                                Podemos recibir información sobre usted de redes sociales, proveedores de verificación de identidad, y otros socios comerciales, siempre con su consentimiento o según lo permitido por la ley.
                            </p>
                        </section>

                        <!-- 3. Cómo Usamos la Información -->
                        <section id="usage" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">3. Cómo Usamos la Información</h2>
                            <p class="text-gray-700 mb-4">
                                Utilizamos su información personal para los siguientes propósitos:
                            </p>
                            
                            <h3 class="text-lg font-medium text-gray-900 mb-2">3.1 Prestación de Servicios</h3>
                            <ul class="list-disc pl-6 space-y-2 text-gray-700 mb-4">
                                <li>Crear y mantener su cuenta</li>
                                <li>Procesar campañas y donaciones</li>
                                <li>Facilitar comunicaciones entre usuarios</li>
                                <li>Proporcionar soporte al cliente</li>
                                <li>Verificar identidad y prevenir fraudes</li>
                            </ul>

                            <h3 class="text-lg font-medium text-gray-900 mb-2">3.2 Mejora de Servicios</h3>
                            <ul class="list-disc pl-6 space-y-2 text-gray-700 mb-4">
                                <li>Analizar el uso de la plataforma</li>
                                <li>Desarrollar nuevas funcionalidades</li>
                                <li>Personalizar la experiencia del usuario</li>
                                <li>Realizar investigación y análisis</li>
                            </ul>

                            <h3 class="text-lg font-medium text-gray-900 mb-2">3.3 Comunicaciones</h3>
                            <ul class="list-disc pl-6 space-y-2 text-gray-700 mb-4">
                                <li>Enviar notificaciones sobre su cuenta</li>
                                <li>Informar sobre actualizaciones de campañas</li>
                                <li>Proporcionar información promocional (con su consentimiento)</li>
                                <li>Responder a sus consultas</li>
                            </ul>

                            <h3 class="text-lg font-medium text-gray-900 mb-2">3.4 Cumplimiento Legal</h3>
                            <ul class="list-disc pl-6 space-y-2 text-gray-700">
                                <li>Cumplir con obligaciones legales</li>
                                <li>Responder a solicitudes de autoridades</li>
                                <li>Proteger nuestros derechos y los de nuestros usuarios</li>
                                <li>Prevenir actividades ilegales o fraudulentas</li>
                            </ul>

                            <h3 class="text-lg font-medium text-gray-900 mb-2">3.5 Prioridad ética de campañas</h3>
                            <p class="text-gray-700">
                                Utilizamos métricas agregadas y pseudonimizadas (por ejemplo, avance de recaudación, días restantes, actividad reciente) para ordenar las campañas y resaltar proyectos urgentes o verificados. No vendemos ni comercializamos espacios privilegiados y evitamos decisiones automatizadas que produzcan discriminación injusta. Puede oponerse a este uso escribiéndonos a <?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?>.</p>
                        </section>

                        <!-- 4. Compartir Información -->
                        <section id="sharing" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">4. Compartir Información</h2>
                            <p class="text-gray-700 mb-4">
                                No vendemos su información personal. Compartimos información limitada en las siguientes circunstancias:
                            </p>

                            <h3 class="text-lg font-medium text-gray-900 mb-2">4.1 Información Pública</h3>
                            <p class="text-gray-700 mb-4">
                                Cierta información es pública por naturaleza, incluyendo nombres de creadores, títulos de campañas, descripciones, y comentarios públicos.
                            </p>

                            <h3 class="text-lg font-medium text-gray-900 mb-2">4.2 Proveedores de Servicios</h3>
                            <p class="text-gray-700 mb-4">
                                Compartimos información con proveedores que nos ayudan a operar la plataforma, incluyendo procesadores de pago, servicios de hosting, y herramientas de análisis.
                            </p>

                            <h3 class="text-lg font-medium text-gray-900 mb-2">4.3 Requerimientos Legales</h3>
                            <p class="text-gray-700 mb-4">
                                Podemos divulgar información cuando sea requerido por ley, orden judicial, o para proteger nuestros derechos o los de otros usuarios.
                            </p>

                            <h3 class="text-lg font-medium text-gray-900 mb-2">4.4 Transferencias Comerciales</h3>
                            <p class="text-gray-700">
                                En caso de fusión, adquisición o venta de activos, su información puede ser transferida como parte de la transacción.
                            </p>
                        </section>

                        <!-- 5. Seguridad de Datos -->
                        <section id="security" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">5. Seguridad de Datos</h2>
                            <p class="text-gray-700 mb-4">
                                Implementamos medidas de seguridad técnicas, administrativas y físicas para proteger su información personal:
                            </p>
                            <ul class="list-disc pl-6 space-y-2 text-gray-700 mb-4">
                                <li>Encriptación SSL/TLS para transmisión de datos</li>
                                <li>Encriptación de datos sensibles en reposo</li>
                                <li>Controles de acceso estrictos</li>
                                <li>Monitoreo continuo de seguridad</li>
                                <li>Auditorías regulares de seguridad</li>
                                <li>Capacitación del personal en privacidad</li>
                            </ul>
                            <p class="text-gray-700">
                                Sin embargo, ningún sistema es completamente seguro. Le recomendamos mantener seguras sus credenciales de acceso y reportar cualquier actividad sospechosa.
                            </p>
                        </section>

                        <!-- 6. Retención de Datos -->
                        <section id="retention" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">6. Retención de Datos</h2>
                            <p class="text-gray-700 mb-4">
                                Conservamos su información personal durante el tiempo necesario para:
                            </p>
                            <ul class="list-disc pl-6 space-y-2 text-gray-700 mb-4">
                                <li>Proporcionar nuestros servicios</li>
                                <li>Cumplir con obligaciones legales</li>
                                <li>Resolver disputas</li>
                                <li>Hacer cumplir nuestros acuerdos</li>
                            </ul>
                            <p class="text-gray-700">
                                Generalmente, conservamos datos de cuenta durante 7 años después del cierre de la cuenta, y datos de transacciones según los requerimientos legales aplicables.
                            </p>
                        </section>

                        <!-- 7. Sus Derechos -->
                        <section id="rights" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">7. Sus Derechos y la Solicitud ARCO</h2>
                            <p class="text-gray-700 mb-4">
                                En cumplimiento de la Ley N.º 19.628 sobre Protección de la Vida Privada y los lineamientos de la Agenda Digital Chile, usted puede ejercer los siguientes derechos sobre sus datos personales almacenados en Lucatón:
                            </p>
                            <ul class="list-disc pl-6 space-y-2 text-gray-700 mb-4">
                                <li><strong>Acceso:</strong> Conocer si tratamos sus datos, el origen, destinatarios y finalidad del tratamiento.</li>
                                <li><strong>Rectificación:</strong> Solicitar la actualización o corrección de información incompleta, inexacta o desactualizada.</li>
                                <li><strong>Cancelación (Supresión):</strong> Pedir la eliminación o bloqueo cuando los datos carezcan de fundamento legal o ya no sean necesarios para la finalidad declarada.</li>
                                <li><strong>Oposición:</strong> Resistirse al tratamiento en casos de finalidades de marketing, perfilamiento o cuando existan motivos fundados relacionados con su situación particular.</li>
                                <li><strong>Portabilidad y limitación:</strong> Recibir sus datos en formato interoperable y pedir que se suspenda temporalmente el tratamiento mientras se revisa una reclamación.</li>
                            </ul>
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 space-y-3 mb-4">
                                <p class="text-sm text-gray-700 font-semibold uppercase tracking-wide">Cómo presentar una solicitud ARCO</p>
                                <ol class="list-decimal pl-5 space-y-2 text-gray-700 text-sm">
                                    <li>Envíe un correo a <a class="text-copihue-600 font-medium" href="mailto:<?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?>"><?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?></a> con el asunto “Solicitud Derechos ARCO”.</li>
                                    <li>Incluya su nombre completo, RUT o documento de identidad, datos de contacto y una descripción clara del derecho que desea ejercer.</li>
                                    <li>Adjunte documentación que respalde la solicitud (por ejemplo, copia de identificación o antecedentes que acrediten la inexactitud de los datos).</li>
                                </ol>
                                <p class="text-sm text-gray-600">
                                    Acusaremos recibo dentro de 2 días hábiles y gestionaremos la respuesta definitiva en un máximo de 10 días hábiles. Si necesitamos información adicional para verificar su identidad, se lo solicitaremos dentro del mismo plazo.
                                </p>
                            </div>
                            <p class="text-gray-700 mb-3">
                                Todas las solicitudes quedan registradas en nuestro libro de control de privacidad y recibirá seguimiento vía correo electrónico. Si la respuesta no satisface sus expectativas, puede escalarla ante el Consejo para la Transparencia o la Dirección de Protección de Datos una vez entre en vigor la nueva normativa chilena.
                            </p>
                            <p class="text-gray-700">
                                También estamos habilitando un panel de autoservicio en el perfil de usuario para que puedas iniciar y monitorear solicitudes ARCO sin salir de la plataforma. Informaremos su disponibilidad a través del Centro de Ayuda.
                            </p>
                        </section>

                        <!-- 8. Cookies y Tecnologías -->
                        <section id="cookies" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">8. Cookies y Tecnologías Similares</h2>
                            <p class="text-gray-700 mb-4">
                                Utilizamos cookies y tecnologías similares para:
                            </p>
                            <ul class="list-disc pl-6 space-y-2 text-gray-700 mb-4">
                                <li>Mantener su sesión activa</li>
                                <li>Recordar sus preferencias</li>
                                <li>Analizar el uso de la plataforma</li>
                                <li>Proporcionar contenido personalizado</li>
                                <li>Mejorar la seguridad</li>
                            </ul>
                            <p class="text-gray-700">
                                Puede controlar las cookies a través de la configuración de su navegador, aunque esto puede afectar la funcionalidad de la plataforma.
                            </p>
                        </section>

                        <!-- 9. Menores de Edad -->
                        <section id="minors" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">9. Menores de Edad</h2>
                            <p class="text-gray-700">
                                Nuestros servicios están dirigidos a personas mayores de 18 años. No recopilamos intencionalmente información personal de menores de edad. Si descubrimos que hemos recopilado información de un menor, la eliminaremos inmediatamente.
                            </p>
                        </section>

                        <!-- 10. Transferencias Internacionales -->
                        <section id="international" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">10. Transferencias Internacionales</h2>
                            <p class="text-gray-700">
                                Su información puede ser transferida y procesada en países fuera de Chile. Cuando esto ocurra, implementamos salvaguardas apropiadas para proteger su información, incluyendo cláusulas contractuales estándar y certificaciones de adecuación.
                            </p>
                        </section>

                        <!-- 11. Cambios a esta Política -->
                        <section id="changes" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">11. Cambios a esta Política</h2>
                            <p class="text-gray-700">
                                Podemos actualizar esta Política de Privacidad ocasionalmente. Le notificaremos sobre cambios significativos por email o mediante un aviso prominente en la plataforma. La fecha de la última actualización se indica al inicio de esta política.
                            </p>
                        </section>

                        <!-- 12. Contacto -->
                        <section id="contact" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">12. Contacto</h2>
                            <p class="text-gray-700 mb-4">
                                Si tiene preguntas sobre esta Política de Privacidad o desea ejercer sus derechos, puede contactarnos:
                            </p>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-gray-700 mb-2"><strong>Responsable del proyecto académico</strong></p>
                                <p class="text-gray-700 mb-2"><?= htmlspecialchars(PROJECT_OWNER_NAME) ?></p>
                                <p class="text-gray-700 mb-2">Email: <a class="text-copihue-600 font-medium" href="mailto:<?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?>"><?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?></a></p>
                                <p class="text-gray-700"><?= htmlspecialchars(PROJECT_DISCLAIMER) ?></p>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include __DIR__ . '/../layouts/partials/footer.php'; ?>

    <script>
        // Smooth scrolling for anchor links
        document.addEventListener('DOMContentLoaded', function() {
            const links = document.querySelectorAll('a[href^="#"]');
            
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').substring(1);
                    const targetElement = document.getElementById(targetId);
                    
                    if (targetElement) {
                        targetElement.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
