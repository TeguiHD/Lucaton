<?php
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/navigation.php';
require_once __DIR__ . '/../components/alerts.php';

$page_title = 'Preguntas Frecuentes - Lucatón';
$page_description = 'Encuentra respuestas a las preguntas más comunes sobre Lucatón, nuestra plataforma de crowdfunding solidario.';

$faq_categories = [
    'general' => [
        'title' => 'General',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'questions' => [
            [
                'question' => '¿Qué es Lucatón?',
                'answer' => 'Lucatón es una plataforma de crowdfunding solidario que conecta a personas con causas sociales importantes. Permitimos que cualquier persona pueda crear campañas para recaudar fondos para proyectos educativos, de salud, ambientales y de ayuda comunitaria.'
            ],
            [
                'question' => '¿Cómo funciona Lucatón?',
                'answer' => 'Es muy simple: 1) Los creadores publican sus campañas con una meta de recaudación, 2) Las personas pueden colaborar con el monto que deseen, 3) Si la campaña alcanza su meta, los fondos se transfieren al creador para ejecutar el proyecto, 4) Los colaboradores reciben actualizaciones sobre el progreso del proyecto.'
            ],
            [
                'question' => '¿Es seguro usar Lucatón?',
                'answer' => 'Sí, la seguridad es nuestra prioridad. Utilizamos encriptación SSL, procesadores de pago certificados, y verificamos todas las campañas antes de publicarlas. Además, contamos con un sistema de reembolsos en caso de que una campaña no cumpla con sus objetivos.'
            ],
            [
                'question' => '¿Qué tipos de proyectos puedo financiar?',
                'answer' => 'Puedes financiar proyectos de educación, salud, medio ambiente, ayuda comunitaria, emergencias, y cualquier causa social que genere un impacto positivo. No permitimos campañas con fines comerciales o políticos.'
            ]
        ]
    ],
    'campaigns' => [
        'title' => 'Campañas',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />',
        'questions' => [
            [
                'question' => '¿Cómo creo una campaña?',
                'answer' => 'Para crear una campaña debes: 1) Registrarte en la plataforma, 2) Completar tu perfil y verificar tu identidad, 3) Crear tu campaña con título, descripción, meta de recaudación e imágenes, 4) Esperar la aprobación de nuestro equipo (24-48 horas), 5) ¡Publicar y compartir tu campaña!'
            ],
            [
                'question' => '¿Cuánto tiempo puede durar una campaña?',
                'answer' => 'Las campañas pueden durar entre 30 y 90 días. Recomendamos 60 días para tener tiempo suficiente de promoción. Puedes extender el plazo una vez por 30 días adicionales si es necesario.'
            ],
            [
                'question' => '¿Qué pasa si no alcanzo mi meta?',
                'answer' => 'Lucatón funciona con el modelo "todo o nada". Si no alcanzas tu meta, todos los colaboradores reciben un reembolso completo. Esto asegura que los proyectos solo se ejecuten si tienen los recursos suficientes para ser exitosos.'
            ],
            [
                'question' => '¿Puedo editar mi campaña después de publicarla?',
                'answer' => 'Puedes editar la descripción, agregar actualizaciones e imágenes. Sin embargo, no puedes cambiar la meta de recaudación ni el plazo una vez que la campaña esté activa y tenga colaboradores.'
            ]
        ]
    ],
    'donations' => [
        'title' => 'Donaciones',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />',
        'questions' => [
            [
                'question' => '¿Cuál es el monto mínimo para colaborar?',
                'answer' => 'El monto mínimo para colaborar es de $1.000 pesos chilenos. No hay límite máximo, puedes colaborar con el monto que desees y que esté dentro de tus posibilidades.'
            ],
            [
                'question' => '¿Qué métodos de pago aceptan?',
                'answer' => 'Aceptamos tarjetas de crédito y débito (Visa, Mastercard), transferencias bancarias, y pagos a través de Khipu. Todos los pagos son procesados de forma segura por nuestros socios certificados.'
            ],
            [
                'question' => '¿Puedo cancelar mi donación?',
                'answer' => 'Puedes cancelar tu donación hasta 24 horas después de realizarla, siempre que la campaña no haya finalizado. Después de este período, solo se puede cancelar si la campaña no alcanza su meta.'
            ],
            [
                'question' => '¿Recibiré un comprobante de mi donación?',
                'answer' => 'Sí, recibirás un comprobante por email inmediatamente después de realizar tu donación. Este comprobante incluye todos los detalles de la transacción y puede servir para fines tributarios si aplica.'
            ]
        ]
    ],
    'fees' => [
        'title' => 'Comisiones y Pagos',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />',
        'questions' => [
            [
                'question' => '¿Qué comisiones cobra Lucatón?',
                'answer' => 'Cobramos una comisión del 5% sobre los fondos recaudados exitosamente, más IVA. Esta comisión solo se aplica si la campaña alcanza su meta. No hay costos por crear o publicar una campaña.'
            ],
            [
                'question' => '¿Cuándo recibo los fondos de mi campaña?',
                'answer' => 'Los fondos se transfieren a tu cuenta bancaria dentro de 5-7 días hábiles después de que tu campaña termine exitosamente. Necesitas proporcionar tus datos bancarios verificados antes del pago.'
            ],
            [
                'question' => '¿Hay costos adicionales por transferencias?',
                'answer' => 'No cobramos costos adicionales por transferencias a cuentas bancarias chilenas. Para transferencias internacionales pueden aplicar comisiones bancarias estándar.'
            ],
            [
                'question' => '¿Qué pasa con los impuestos?',
                'answer' => 'Los fondos recaudados pueden estar sujetos a impuestos según la legislación chilena. Recomendamos consultar con un contador sobre las implicaciones tributarias de tu campaña específica.'
            ]
        ]
    ],
    'support' => [
        'title' => 'Soporte',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 2.25a9.75 9.75 0 11-9.75 9.75A9.75 9.75 0 0112 2.25z" />',
        'questions' => [
            [
                'question' => '¿Cómo puedo contactar al soporte?',
                'answer' => 'Puedes contactarnos a través de: email (soporte@lucaton.cl), chat en vivo (disponible de lunes a viernes de 9:00 a 18:00), o teléfono (+56 2 2XXX XXXX). También puedes enviar un mensaje desde tu panel de usuario.'
            ],
            [
                'question' => '¿Qué hago si tengo problemas técnicos?',
                'answer' => 'Si experimentas problemas técnicos, primero intenta refrescar la página o usar otro navegador. Si el problema persiste, contáctanos con una descripción detallada del problema y capturas de pantalla si es posible.'
            ],
            [
                'question' => '¿Cómo reporto una campaña sospechosa?',
                'answer' => 'Si encuentras una campaña que parece fraudulenta o viola nuestros términos, puedes reportarla usando el botón "Reportar" en la página de la campaña, o contactando directamente a nuestro equipo de seguridad.'
            ],
            [
                'question' => '¿Ofrecen capacitación para crear campañas exitosas?',
                'answer' => 'Sí, ofrecemos guías gratuitas, webinars mensuales y consultas personalizadas para ayudarte a crear campañas exitosas. Visita nuestra sección de recursos o contáctanos para más información.'
            ]
        ]
    ]
];
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
            ['name' => 'Preguntas Frecuentes', 'href' => Router::url('faq')]
        ]); ?>

        <!-- Header Section -->
        <div class="text-center mb-12">
            <h1 class="text-3xl font-bold text-gray-900 sm:text-4xl mb-4">
                Preguntas Frecuentes
            </h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Encuentra respuestas a las preguntas más comunes sobre nuestra plataforma de crowdfunding solidario.
            </p>
        </div>

        <!-- Search Box -->
        <div class="max-w-2xl mx-auto mb-12">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" 
                       class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-copihue-500 focus:border-copihue-500"
                       placeholder="Buscar en preguntas frecuentes..."
                       oninput="filterFAQ(this.value)">
            </div>
        </div>

        <!-- FAQ Categories Navigation -->
        <div class="mb-8">
            <nav class="flex space-x-8 overflow-x-auto pb-2" aria-label="Categorías">
                <?php foreach ($faq_categories as $key => $category): ?>
                    <button type="button" 
                            class="flex items-center px-3 py-2 text-sm font-medium rounded-md whitespace-nowrap transition-colors category-tab"
                            data-category="<?php echo $key; ?>"
                            onclick="showCategory('<?php echo $key; ?>')">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?php echo $category['icon']; ?>
                        </svg>
                        <?php echo htmlspecialchars($category['title']); ?>
                    </button>
                <?php endforeach; ?>
            </nav>
        </div>

        <!-- FAQ Content -->
        <div class="max-w-4xl mx-auto">
            <?php foreach ($faq_categories as $category_key => $category): ?>
                <div id="category-<?php echo $category_key; ?>" class="faq-category mb-12">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-copihue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?php echo $category['icon']; ?>
                        </svg>
                        <?php echo htmlspecialchars($category['title']); ?>
                    </h2>
                    
                    <div class="space-y-4">
                        <?php foreach ($category['questions'] as $index => $qa): ?>
                            <div class="bg-white shadow rounded-lg faq-item" 
                                 data-question="<?php echo strtolower(htmlspecialchars($qa['question'])); ?>"
                                 data-answer="<?php echo strtolower(htmlspecialchars($qa['answer'])); ?>">
                                <button type="button" 
                                        class="w-full px-6 py-4 text-left flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-copihue-500 focus:ring-inset rounded-lg"
                                        data-faq-toggle
                                        aria-expanded="false">
                                    <span class="text-lg font-medium text-gray-900">
                                        <?php echo htmlspecialchars($qa['question']); ?>
                                    </span>
                                    <svg class="w-5 h-5 text-gray-500 transition-transform duration-200"
                                         data-faq-icon
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                
                                <div class="px-6 pb-4 hidden faq-answer" data-faq-answer>
                                    <div class="text-gray-600 leading-relaxed">
                                        <?php echo nl2br(htmlspecialchars($qa['answer'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- No Results Message -->
        <div id="no-results" class="hidden text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.47-.881-6.08-2.33" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No se encontraron resultados</h3>
            <p class="mt-1 text-sm text-gray-500">
                Intenta con otros términos de búsqueda o explora las categorías.
            </p>
        </div>

        <!-- Contact Section -->
        <div class="bg-white shadow rounded-lg p-8 mt-12">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">
                    ¿No encontraste lo que buscabas?
                </h2>
                <p class="text-gray-600 mb-6">
                    Nuestro equipo de soporte está aquí para ayudarte con cualquier pregunta adicional.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <?php echo render_button([
                        'text' => 'Contactar Soporte',
                        'href' => '/contact',
                        'type' => 'primary',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />'
                    ]); ?>
                    
                    <?php echo render_button([
                        'text' => 'Chat en Vivo',
                        'type' => 'secondary',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />'
                    ]); ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include __DIR__ . '/../layouts/partials/footer.php'; ?>

    <script>
        function setupFaqToggles() {
            document.querySelectorAll('[data-faq-toggle]').forEach(button => {
                button.addEventListener('click', () => {
                    const item = button.closest('.faq-item');
                    if (!item) {
                        return;
                    }

                    if (button.getAttribute('aria-expanded') === 'true') {
                        closeFaqItem(item);
                    } else {
                        openFaqItem(item);
                    }
                });
            });
        }

        function openFaqItem(item) {
            const answer = item.querySelector('[data-faq-answer]');
            const button = item.querySelector('[data-faq-toggle]');
            const icon = item.querySelector('[data-faq-icon]');
            if (!answer || !button) {
                return;
            }

            answer.classList.remove('hidden');
            button.setAttribute('aria-expanded', 'true');
            if (icon) {
                icon.classList.add('rotate-180');
            }
        }

        function closeFaqItem(item) {
            const answer = item.querySelector('[data-faq-answer]');
            const button = item.querySelector('[data-faq-toggle]');
            const icon = item.querySelector('[data-faq-icon]');
            if (!answer || !button) {
                return;
            }

            answer.classList.add('hidden');
            button.setAttribute('aria-expanded', 'false');
            if (icon) {
                icon.classList.remove('rotate-180');
            }
        }

        // Category navigation
        function showCategory(categoryKey) {
            // Update active tab
            document.querySelectorAll('.category-tab').forEach(tab => {
                tab.classList.remove('bg-copihue-100', 'text-copihue-700');
                tab.classList.add('text-gray-500', 'hover:text-gray-700');
            });
            
            document.querySelector(`[data-category="${categoryKey}"]`).classList.add('bg-copihue-100', 'text-copihue-700');
            document.querySelector(`[data-category="${categoryKey}"]`).classList.remove('text-gray-500', 'hover:text-gray-700');
            
            // Show/hide categories
            document.querySelectorAll('.faq-category').forEach(category => {
                category.style.display = 'none';
            });
            
            document.getElementById(`category-${categoryKey}`).style.display = 'block';
        }

        // Search functionality
        function filterFAQ(searchTerm) {
            const term = searchTerm.toLowerCase().trim();
            const faqItems = document.querySelectorAll('.faq-item');
            const categories = document.querySelectorAll('.faq-category');
            const noResults = document.getElementById('no-results');
            let hasResults = false;

            if (term === '') {
                // Show all items and categories
                faqItems.forEach(item => item.style.display = 'block');
                categories.forEach(category => category.style.display = 'block');
                noResults.classList.add('hidden');
                return;
            }

            // Hide all categories first
            categories.forEach(category => category.style.display = 'block');

            faqItems.forEach(item => {
                const question = item.dataset.question;
                const answer = item.dataset.answer;
                
                if (question.includes(term) || answer.includes(term)) {
                    item.style.display = 'block';
                    hasResults = true;
                } else {
                    item.style.display = 'none';
                    closeFaqItem(item);
                }
            });

            // Hide categories with no visible items
            categories.forEach(category => {
                const visibleItems = category.querySelectorAll('.faq-item[style*="block"]');
                if (visibleItems.length === 0) {
                    category.style.display = 'none';
                }
            });

            // Show/hide no results message
            if (hasResults) {
                noResults.classList.add('hidden');
            } else {
                noResults.classList.remove('hidden');
            }
        }

        // Initialize first category as active
        document.addEventListener('DOMContentLoaded', function() {
            setupFaqToggles();
            showCategory('general');
        });
    </script>
</body>
</html>
