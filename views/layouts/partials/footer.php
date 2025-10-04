<?php
// Enlaces del footer organizados por secciones
$footer_sections = [
    'Plataforma' => [
        ['name' => 'Inicio', 'href' => Router::url('/')],
        ['name' => 'Campañas', 'href' => Router::url('campanas')],
        ['name' => 'Noticias', 'href' => Router::url('noticias')],
        ['name' => 'Crear Campaña', 'href' => Router::url('campana/crear')],
        ['name' => 'Preguntas Frecuentes', 'href' => Router::url('faq')]
    ],
    'Soporte' => [
        ['name' => 'Centro de Ayuda', 'href' => Router::url('ayuda')],
        ['name' => 'Contacto', 'href' => Router::url('contacto')],
        ['name' => 'Reportar Problema', 'href' => Router::url('reportar')],
        ['name' => 'Estado del Sistema', 'href' => Router::url('estado')]
    ],
    'Legal' => [
        ['name' => 'Términos de Uso', 'href' => Router::url('terminos')],
        ['name' => 'Política de Privacidad', 'href' => Router::url('privacidad')],
        ['name' => 'Política de Cookies', 'href' => Router::url('cookies')],
        ['name' => 'Código de Conducta', 'href' => Router::url('codigo-conducta')]
    ]
];

$social_links = [
    [
        'name' => 'LinkedIn',
        'href' => '#',
        'icon' => '<path fill="currentColor" d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.35V9h3.414v1.561h.048c.476-.9 1.637-1.852 3.37-1.852 3.602 0 4.267 2.37 4.267 5.455v6.288zM5.337 7.433a2.062 2.062 0 11.001-4.124 2.062 2.062 0 01-.001 4.124zM7.119 20.452H3.554V9h3.565v11.452z"/>'
    ],
    [
        'name' => 'Instagram',
        'href' => '#',
        'icon' => '<path fill="currentColor" fill-rule="evenodd" d="M3 8a5 5 0 0 1 5-5h8a5 5 0 0 1 5 5v8a5 5 0 0 1-5 5H8a5 5 0 0 1-5-5V8Zm5-3a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3h8a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3H8Zm7.597 2.214a1 1 0 0 1 1-1h.01a1 1 0 1 1 0 2h-.01a1 1 0 0 1-1-1ZM12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm-5 3a5 5 0 1 1 10 0 5 5 0 0 1-10 0Z" clip-rule="evenodd"/>'
    ],
    [
        'name' => 'X',
        'href' => '#',
        'icon' => '<path d="M13.795 10.533 20.68 2h-3.073l-5.255 6.517L7.69 2H1l7.806 10.91L1.47 22h3.074l5.705-7.07L15.31 22H22l-8.205-11.467Zm-2.38 2.95L9.97 11.464 4.36 3.627h2.31l4.528 6.317 1.443 2.02 6.018 8.409h-2.31l-4.934-6.89Z"/>'
    ],
    [
        'name' => 'Facebook',
        'href' => '#',
        'icon' => '<path fill="currentColor" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/>'
    ],
    [
        'name' => 'YouTube',
        'href' => '#',
        'icon' => '<path fill="currentColor" d="M23.498 6.186a2.965 2.965 0 00-2.088-2.09C19.691 3.5 12 3.5 12 3.5s-7.691 0-9.41.596A2.965 2.965 0 00.502 6.186 31.398 31.398 0 000 12a31.398 31.398 0 00.502 5.814 2.965 2.965 0 002.088 2.09C4.309 20.5 12 20.5 12 20.5s7.691 0 9.41-.596a2.965 2.965 0 002.088-2.09A31.398 31.398 0 0024 12a31.398 31.398 0 00-.502-5.814zM9.75 15.02V8.98L15.5 12l-5.75 3.02z"/>'
    ],
    [
        'name' => 'TikTok',
        'href' => '#',
        'icon' => '<path fill="currentColor" d="M12 2.163c1.807 2.378 4.484 3.312 6.832 3.197v3.916c-3.174.075-5.424-1.045-6.832-2.534v9.514c0 3.598-2.912 6.526-6.508 6.526C2.912 22.782 0 19.854 0 16.256c0-3.399 2.667-6.169 6-6.511v3.986a2.55 2.55 0 00-1.924 2.441c0 1.414 1.141 2.559 2.55 2.559 1.409 0 2.55-1.145 2.55-2.559V2.163H12z"/>'
    ]
];
?>

<footer class="bg-gray-50 border-t border-gray-200">
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-8">
        <div class="xl:grid xl:grid-cols-3 xl:gap-8">
            <!-- Información principal -->
            <div class="space-y-8 xl:col-span-1">
                <div class="flex items-center">
                    <img class="h-10 w-auto" src="<?= APP_URL ?>/public/assets/images/logo.svg" alt="Lucatón">
                    <span class="ml-3 text-2xl font-bold text-marino-900">Lucatón</span>
                </div>
                <p class="text-gray-500 text-base max-w-md">
                    Prototipo académico de crowdfunding desarrollado en la Universidad Bernardo O'Higgins para fines de tesis. 
                    Las funcionalidades presentadas recrean escenarios reales sin constituir un servicio comercial activo.
                </p>
                <div class="flex space-x-6">
                    <?php foreach ($social_links as $social): ?>
                        <a href="<?= $social['href'] ?>" 
                           class="text-gray-400 hover:text-gray-500 transition-colors duration-200"
                           aria-label="<?= $social['name'] ?>">
                            <span class="sr-only"><?= $social['name'] ?></span>
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <?= $social['icon'] ?>
                            </svg>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Enlaces organizados -->
            <div class="mt-12 grid grid-cols-2 gap-8 xl:mt-0 xl:col-span-2">
                <div class="md:grid md:grid-cols-2 md:gap-8">
                    <?php foreach (array_slice($footer_sections, 0, 2) as $section_name => $links): ?>
                        <div class="<?= $section_name === 'Soporte' ? 'mt-12 md:mt-0' : '' ?>">
                            <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">
                                <?= $section_name ?>
                            </h3>
                            <ul role="list" class="mt-4 space-y-4">
                                <?php foreach ($links as $link): ?>
                                    <li>
                                        <a href="<?= $link['href'] ?>" 
                                           class="text-base text-gray-500 hover:text-gray-900 transition-colors duration-200">
                                            <?= $link['name'] ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="md:grid md:grid-cols-1 md:gap-8">
                    <?php foreach (array_slice($footer_sections, 2, 1) as $section_name => $links): ?>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">
                                <?= $section_name ?>
                            </h3>
                            <ul role="list" class="mt-4 space-y-4">
                                <?php foreach ($links as $link): ?>
                                    <li>
                                        <a href="<?= $link['href'] ?>" 
                                           class="text-base text-gray-500 hover:text-gray-900 transition-colors duration-200">
                                            <?= $link['name'] ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Newsletter -->
                    <div class="mt-12 md:mt-8">
                        <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">
                            Mantente Informado
                        </h3>
                        <p class="mt-4 text-base text-gray-500">
                            Recibe las últimas noticias sobre proyectos y actualizaciones de la plataforma.
                        </p>
                        <form class="mt-4 sm:flex sm:max-w-md" action="<?= Router::url('newsletter') ?>" method="POST">
                            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">
                            <label for="email-address" class="sr-only">Dirección de email</label>
                            <input type="email" 
                                   name="email" 
                                   id="email-address" 
                                   autocomplete="email" 
                                   required 
                                   class="appearance-none min-w-0 w-full bg-white border border-gray-300 rounded-md py-2 px-4 text-base text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-copihue-500 focus:border-copihue-500 focus:placeholder-gray-400" 
                                   placeholder="Ingresa tu email">
                            <div class="mt-3 rounded-md sm:mt-0 sm:ml-3 sm:flex-shrink-0">
                                <button type="submit" 
                                        class="w-full bg-copihue-600 border border-transparent rounded-md py-2 px-4 flex items-center justify-center text-base font-medium text-white hover:bg-copihue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500 transition-colors duration-200">
                                    Suscribirse
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Información legal y copyright -->
        <div class="mt-12 border-t border-gray-200 pt-8">
            <div class="md:flex md:items-center md:justify-between">
                <div class="flex space-x-6 md:order-2">
                    <a href="<?= Router::url('terminos') ?>" class="text-gray-400 hover:text-gray-500 text-sm transition-colors duration-200">
                        Términos
                    </a>
                    <a href="<?= Router::url('privacidad') ?>" class="text-gray-400 hover:text-gray-500 text-sm transition-colors duration-200">
                        Privacidad
                    </a>
                    <a href="<?= Router::url('cookies') ?>" class="text-gray-400 hover:text-gray-500 text-sm transition-colors duration-200">
                        Cookies
                    </a>
                </div>
                <p class="mt-8 text-base text-gray-400 md:mt-0 md:order-1">
                    &copy; <?= date('Y') ?> <?= htmlspecialchars(PROJECT_OWNER_NAME) ?>. <?= htmlspecialchars(PROJECT_DISCLAIMER) ?>
                    <span class="block sm:inline">Hecho con ❤️ en Chile.</span>
                </p>
            </div>
        </div>
    </div>
</footer>
<!-- JS de interacción ligera (sin CDN) -->
<script src="<?= APP_URL ?>/public/assets/js/app.js?v=2025020503" defer></script>
