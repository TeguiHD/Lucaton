<?php
require_once __DIR__ . '/../components/navigation.php';

$current_page = $current_page ?? 'terms';

$page_title = 'Términos y Condiciones - Lucatón';
$page_description = 'Términos y condiciones de uso de la plataforma Lucatón. Conoce tus derechos y responsabilidades.';

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
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/public/assets/images/favicon.svg">

    <!-- Styles -->
    <link href="<?= APP_URL ?>/public/assets/css/app.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/public/assets/css/aliases.css" rel="stylesheet">
    
    
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
            ['name' => 'Términos y Condiciones', 'href' => Router::url('terminos')]
        ]); ?>

        <div class="lg:grid lg:grid-cols-4 lg:gap-8">
            <!-- Table of Contents -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow rounded-lg p-6 sticky top-8">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Contenido</h2>
                    <nav class="space-y-2">
                        <a href="#acceptance" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">1. Aceptación de Términos</a>
                        <a href="#definitions" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">2. Definiciones</a>
                        <a href="#platform" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">3. Descripción de la Plataforma</a>
                        <a href="#registration" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">4. Registro y Cuenta</a>
                        <a href="#campaigns" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">5. Campañas</a>
                        <a href="#donations" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">6. Donaciones</a>
                        <a href="#fees" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">7. Comisiones y Pagos</a>
                        <a href="#prohibited" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">8. Contenido Prohibido</a>
                        <a href="#intellectual" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">9. Propiedad Intelectual</a>
                        <a href="#liability" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">10. Limitación de Responsabilidad</a>
                        <a href="#termination" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">11. Terminación</a>
                        <a href="#modifications" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">12. Modificaciones</a>
                        <a href="#governing" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">13. Ley Aplicable</a>
                        <a href="#contact" class="block text-sm text-gray-600 hover:text-copihue-600 transition-colors">14. Contacto</a>
                    </nav>
                </div>
            </div>

            <!-- Content -->
            <div class="lg:col-span-3 mt-8 lg:mt-0">
                <div class="bg-white shadow rounded-lg p-8">
                    <!-- Header -->
                    <div class="mb-8">
                        <h1 class="text-3xl font-bold text-gray-900 mb-4">
                            Términos y Condiciones
                        </h1>
                        <p class="text-gray-600">
                            Última actualización: <?php echo $last_updated; ?>
                        </p>
                    </div>

                    <!-- Content Sections -->
                    <div class="prose max-w-none">
                        <!-- 1. Aceptación de Términos -->
                        <section id="acceptance" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">1. Aceptación de Términos</h2>
                            <p class="text-gray-700 mb-4">
                                Al acceder y utilizar la plataforma Lucatón ("la Plataforma"), usted acepta estar sujeto a estos Términos y Condiciones ("Términos"). Si no está de acuerdo con alguna parte de estos términos, no debe utilizar nuestros servicios.
                            </p>
                            <p class="text-gray-700">
                                Estos Términos constituyen un acuerdo legal vinculante entre usted y Lucatón SpA ("Lucatón", "nosotros", "nuestro"). Al crear una cuenta o utilizar nuestros servicios, usted confirma que ha leído, entendido y acepta cumplir con estos Términos.
                            </p>
                        </section>

                        <!-- 2. Definiciones -->
                        <section id="definitions" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">2. Definiciones</h2>
                            <ul class="list-disc pl-6 space-y-2 text-gray-700">
                                <li><strong>Plataforma:</strong> El sitio web y aplicaciones móviles de Lucatón.</li>
                                <li><strong>Usuario:</strong> Cualquier persona que accede o utiliza la Plataforma.</li>
                                <li><strong>Creador:</strong> Usuario que crea y publica campañas de recaudación de fondos.</li>
                                <li><strong>Colaborador:</strong> Usuario que realiza donaciones a las campañas.</li>
                                <li><strong>Campaña:</strong> Proyecto de recaudación de fondos publicado en la Plataforma.</li>
                                <li><strong>Donación:</strong> Contribución monetaria realizada por un Colaborador a una Campaña.</li>
                                <li><strong>Contenido:</strong> Cualquier información, texto, imagen, video u otro material publicado en la Plataforma.</li>
                            </ul>
                        </section>

                        <!-- 3. Descripción de la Plataforma -->
                        <section id="platform" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">3. Descripción de la Plataforma</h2>
                            <p class="text-gray-700 mb-4">
                                Lucatón es una plataforma de crowdfunding que permite a los usuarios crear campañas para recaudar fondos para proyectos sociales, educativos, de salud, ambientales y de ayuda comunitaria. La Plataforma facilita la conexión entre Creadores y Colaboradores, pero no garantiza el éxito de ninguna campaña.
                            </p>
                            <p class="text-gray-700">
                                Lucatón actúa únicamente como intermediario tecnológico y no es responsable de la ejecución de los proyectos financiados a través de la Plataforma.
                            </p>
                        </section>

                        <!-- 4. Registro y Cuenta -->
                        <section id="registration" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">4. Registro y Cuenta</h2>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">4.1 Elegibilidad</h3>
                            <p class="text-gray-700 mb-4">
                                Para utilizar la Plataforma, debe ser mayor de 18 años y tener capacidad legal para celebrar contratos. Al registrarse, declara que cumple con estos requisitos.
                            </p>
                            
                            <h3 class="text-lg font-medium text-gray-900 mb-2">4.2 Información de Registro</h3>
                            <p class="text-gray-700 mb-4">
                                Debe proporcionar información precisa, completa y actualizada durante el registro. Es su responsabilidad mantener la confidencialidad de sus credenciales de acceso y notificar inmediatamente cualquier uso no autorizado de su cuenta.
                            </p>
                            
                            <h3 class="text-lg font-medium text-gray-900 mb-2">4.3 Verificación</h3>
                            <p class="text-gray-700">
                                Nos reservamos el derecho de verificar su identidad y solicitar documentación adicional antes de permitir ciertas actividades en la Plataforma, especialmente para la creación de campañas.
                            </p>
                        </section>

                        <!-- 5. Campañas -->
                        <section id="campaigns" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">5. Campañas</h2>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">5.1 Creación de Campañas</h3>
                            <p class="text-gray-700 mb-4">
                                Los Creadores pueden publicar campañas que cumplan con nuestras políticas. Todas las campañas están sujetas a revisión y aprobación antes de su publicación. Nos reservamos el derecho de rechazar o eliminar campañas que no cumplan con nuestros estándares.
                            </p>
                            
                            <h3 class="text-lg font-medium text-gray-900 mb-2">5.2 Responsabilidad del Creador</h3>
                            <ul class="list-disc pl-6 space-y-2 text-gray-700 mb-4">
                                <li>Proporcionar información veraz y precisa sobre el proyecto</li>
                                <li>Utilizar los fondos recaudados únicamente para el propósito declarado</li>
                                <li>Mantener actualizados a los Colaboradores sobre el progreso del proyecto</li>
                                <li>Cumplir con todas las leyes aplicables</li>
                            </ul>
                            
                            <h3 class="text-lg font-medium text-gray-900 mb-2">5.3 Modelo "Todo o Nada"</h3>
                            <p class="text-gray-700">
                                Las campañas operan bajo el modelo "todo o nada". Si una campaña no alcanza su meta de recaudación dentro del plazo establecido, todos los fondos son devueltos a los Colaboradores.
                            </p>

                            <h3 class="text-lg font-medium text-gray-900 mb-2">5.4 Orden de visualización</h3>
                            <p class="text-gray-700 mb-4">
                                Para proteger la confianza de la comunidad, la Plataforma organiza las campañas mediante criterios públicos que buscan equilibrar impacto y oportunidad. La priorización considera, entre otros factores:
                            </p>
                            <ul class="list-disc pl-6 space-y-2 text-gray-700 mb-4">
                                <li>Campañas destacadas o verificadas por Lucatón tras una revisión documental adicional.</li>
                                <li>Nivel de urgencia, incluyendo días restantes y porcentaje de la meta pendiente.</li>
                                <li>Actividad reciente de la comunidad (donaciones, difusión o visitas legítimas).</li>
                                <li>Antigüedad de la campaña, para dar visibilidad a proyectos nuevos.</li>
                            </ul>
                            <p class="text-gray-700 mb-4">
                                Lucatón no vende posiciones privilegiadas ni garantiza resultados. Ajustamos periódicamente el algoritmo para prevenir abusos y mantener la transparencia.</p>
                        </section>

                        <!-- 6. Donaciones -->
                        <section id="donations" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">6. Donaciones</h2>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">6.1 Proceso de Donación</h3>
                            <p class="text-gray-700 mb-4">
                                Las donaciones se procesan a través de proveedores de pago externos certificados. Al realizar una donación, autoriza el cargo a su método de pago seleccionado.
                            </p>
                            
                            <h3 class="text-lg font-medium text-gray-900 mb-2">6.2 Cancelaciones y Reembolsos</h3>
                            <p class="text-gray-700 mb-4">
                                Las donaciones pueden cancelarse dentro de las 24 horas posteriores a su realización. Los reembolsos automáticos se procesan si una campaña no alcanza su meta o es cancelada por violación de políticas.
                            </p>
                            
                            <h3 class="text-lg font-medium text-gray-900 mb-2">6.3 Naturaleza de las Donaciones</h3>
                            <p class="text-gray-700">
                                Las donaciones son contribuciones voluntarias y no constituyen una compra de bienes o servicios. Los Colaboradores no tienen derecho a recompensas específicas a menos que se indique expresamente en la campaña.
                            </p>
                        </section>

                        <!-- 7. Comisiones y Pagos -->
                        <section id="fees" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">7. Comisiones y Pagos</h2>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">7.1 Comisiones de la Plataforma</h3>
                            <p class="text-gray-700 mb-4">
                                Lucatón cobra una comisión del 5% más IVA sobre los fondos recaudados exitosamente. Esta comisión se deduce automáticamente antes de la transferencia de fondos al Creador.
                            </p>
                            
                            <h3 class="text-lg font-medium text-gray-900 mb-2">7.2 Procesamiento de Pagos</h3>
                            <p class="text-gray-700 mb-4">
                                Los fondos se transfieren a los Creadores dentro de 5-7 días hábiles después del cierre exitoso de la campaña. Los Creadores deben proporcionar información bancaria válida para recibir los fondos.
                            </p>
                            
                            <h3 class="text-lg font-medium text-gray-900 mb-2">7.3 Impuestos</h3>
                            <p class="text-gray-700">
                                Los usuarios son responsables de cumplir con todas las obligaciones tributarias relacionadas con su uso de la Plataforma. Lucatón no proporciona asesoramiento fiscal.
                            </p>
                        </section>

                        <!-- 8. Contenido Prohibido -->
                        <section id="prohibited" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">8. Contenido Prohibido</h2>
                            <p class="text-gray-700 mb-4">
                                Está prohibido publicar campañas o contenido que:
                            </p>
                            <ul class="list-disc pl-6 space-y-2 text-gray-700">
                                <li>Viole leyes locales, nacionales o internacionales</li>
                                <li>Promueva actividades ilegales o fraudulentas</li>
                                <li>Contenga material ofensivo, discriminatorio o de odio</li>
                                <li>Infrinja derechos de propiedad intelectual de terceros</li>
                                <li>Sea de naturaleza comercial o con fines de lucro personal</li>
                                <li>Promueva actividades políticas o religiosas específicas</li>
                                <li>Solicite fondos para gastos personales no relacionados con causas sociales</li>
                            </ul>
                        </section>

                        <!-- 9. Propiedad Intelectual -->
                        <section id="intellectual" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">9. Propiedad Intelectual</h2>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">9.1 Contenido de Lucatón</h3>
                            <p class="text-gray-700 mb-4">
                                Todos los derechos de propiedad intelectual sobre la Plataforma, incluyendo diseño, código, marcas y contenido, pertenecen a Lucatón o sus licenciantes.
                            </p>
                            
                            <h3 class="text-lg font-medium text-gray-900 mb-2">9.2 Contenido del Usuario</h3>
                            <p class="text-gray-700">
                                Los usuarios conservan los derechos sobre el contenido que publican, pero otorgan a Lucatón una licencia no exclusiva para usar, mostrar y promocionar dicho contenido en relación con la Plataforma.
                            </p>
                        </section>

                        <!-- 10. Limitación de Responsabilidad -->
                        <section id="liability" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">10. Limitación de Responsabilidad</h2>
                            <p class="text-gray-700 mb-4">
                                Lucatón actúa únicamente como intermediario tecnológico. No somos responsables de:
                            </p>
                            <ul class="list-disc pl-6 space-y-2 text-gray-700 mb-4">
                                <li>La veracidad de la información proporcionada por los Creadores</li>
                                <li>El uso de los fondos recaudados por parte de los Creadores</li>
                                <li>La ejecución exitosa de los proyectos financiados</li>
                                <li>Disputas entre Creadores y Colaboradores</li>
                                <li>Pérdidas financieras derivadas del uso de la Plataforma</li>
                            </ul>
                            <p class="text-gray-700">
                                Nuestra responsabilidad total no excederá el monto de las comisiones recibidas por la transacción específica en cuestión.
                            </p>
                        </section>

                        <!-- 11. Terminación -->
                        <section id="termination" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">11. Terminación</h2>
                            <p class="text-gray-700 mb-4">
                                Podemos suspender o terminar su cuenta en cualquier momento si viola estos Términos o nuestras políticas. Usted puede cerrar su cuenta en cualquier momento contactando nuestro soporte.
                            </p>
                            <p class="text-gray-700">
                                La terminación no afecta las obligaciones ya contraídas ni los derechos adquiridos antes de la terminación.
                            </p>
                        </section>

                        <!-- 12. Modificaciones -->
                        <section id="modifications" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">12. Modificaciones</h2>
                            <p class="text-gray-700">
                                Nos reservamos el derecho de modificar estos Términos en cualquier momento. Las modificaciones entrarán en vigor inmediatamente después de su publicación en la Plataforma. El uso continuado de nuestros servicios constituye aceptación de los términos modificados.
                            </p>
                        </section>

                        <!-- 13. Ley Aplicable -->
                        <section id="governing" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">13. Ley Aplicable</h2>
                            <p class="text-gray-700">
                                Estos Términos se rigen por las leyes de la República de Chile. Cualquier disputa será resuelta en los tribunales competentes de Santiago, Chile.
                            </p>
                        </section>

                        <!-- 14. Contacto -->
                        <section id="contact" class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">14. Contacto</h2>
                            <p class="text-gray-700 mb-4">
                                Para preguntas sobre estos Términos y Condiciones, puede contactarnos:
                            </p>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-gray-700 mb-2"><strong>Lucatón SpA</strong></p>
                                <p class="text-gray-700 mb-2">Email: legal@lucaton.cl</p>
                                <p class="text-gray-700 mb-2">Teléfono: +56 2 2XXX XXXX</p>
                                <p class="text-gray-700">Dirección: [Dirección de la empresa], Santiago, Chile</p>
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
