<?php
// Enlaces del footer organizados por secciones
$footer_sections = [
    'Plataforma' => [
        ['name' => 'Inicio', 'href' => Router::url('/')],
        ['name' => 'Campañas', 'href' => Router::url('campanas')],
        ['name' => 'Noticias', 'href' => Router::url('noticias')],
        ['name' => 'Crear Campaña', 'href' => Router::url('campana/crear')],
        ['name' => 'Centro de Ayuda y FAQ', 'href' => Router::url('ayuda')]
    ],
    'Soporte' => [
        ['name' => 'Guías y preguntas frecuentes', 'href' => Router::url('ayuda') . '#preguntas-frecuentes'],
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
                    <img class="h-10 w-auto" src="<?= asset_url('images/logo.svg') ?>" alt="Lucatón">
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
                    
                    <?php
                        $newsletterAnchorId = 'newsletter-signup';
                        $newsletterAuthenticated = SessionHelper::isAuthenticated();
                        $newsletterUser = $newsletterAuthenticated ? SessionHelper::getUser() : null;
                        $newsletterEmail = $newsletterAuthenticated ? trim((string)($newsletterUser['email'] ?? '')) : '';
                        $newsletterName = '';
                        if ($newsletterAuthenticated && is_array($newsletterUser)) {
                            $newsletterName = trim(($newsletterUser['first_name'] ?? '') . ' ' . ($newsletterUser['last_name'] ?? ''));
                            if ($newsletterName === '' && !empty($newsletterUser['name'])) {
                                $newsletterName = trim((string)$newsletterUser['name']);
                            }
                        }
                    ?>
                    <!-- Newsletter -->
                    <div class="mt-12 md:mt-8" id="<?= $newsletterAnchorId ?>">
                        <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">
                            Mantente Informado
                        </h3>
                        <p class="mt-4 text-base text-gray-500">
                            Recibe las últimas noticias sobre proyectos y actualizaciones de la plataforma.
                        </p>

                        <?php if (!$newsletterAuthenticated): ?>
                            <form class="mt-4 sm:flex sm:max-w-md" action="<?= Router::url('newsletter') ?>" method="POST" data-newsletter-form>
                                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">
                                <label for="newsletter-email" class="sr-only">Correo electronico</label>
                                <input type="email"
                                       name="email"
                                       id="newsletter-email"
                                       autocomplete="email"
                                       required
                                       data-newsletter-email
                                       class="appearance-none min-w-0 w-full bg-white border border-gray-300 rounded-md py-2 px-4 text-base text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-copihue-500 focus:border-copihue-500 focus:placeholder-gray-400"
                                       placeholder="Ingresa tu correo">
                                <div class="mt-3 rounded-md sm:mt-0 sm:ml-3 sm:flex-shrink-0">
                                    <button type="submit"
                                            data-newsletter-submit
                                            class="w-full bg-copihue-600 border border-transparent rounded-md py-2 px-4 flex items-center justify-center text-base font-medium text-white hover:bg-copihue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500 transition-colors duration-200">
                                        Suscribirse
                                    </button>
                                </div>
                            </form>
                            <p class="mt-2 text-xs text-gray-500">Necesitamos que inicies sesión para confirmar la suscripción con tu cuenta Lucatón y proteger tu correo.</p>
                        <?php else: ?>
                            <form class="mt-4 sm:flex sm:max-w-md" action="<?= Router::url('newsletter') ?>" method="POST" data-newsletter-form>
                                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">
                                <label for="newsletter-email-account" class="sr-only">Correo electronico</label>
                                <input type="email"
                                       name="email"
                                       id="newsletter-email-account"
                                       autocomplete="email"
                                       required
                                       readonly
                                       value="<?= htmlspecialchars($newsletterEmail) ?>"
                                       data-newsletter-email
                                       class="appearance-none min-w-0 w-full bg-gray-100 border border-gray-300 rounded-md py-2 px-4 text-base text-gray-900 focus:outline-none focus:ring-2 focus:ring-copihue-500 focus:border-copihue-500"
                                       placeholder="<?= htmlspecialchars($newsletterEmail ?: 'tu@correo.com') ?>">
                                <div class="mt-3 rounded-md sm:mt-0 sm:ml-3 sm:flex-shrink-0">
                                    <button type="submit"
                                            data-newsletter-submit
                                            class="w-full bg-copihue-600 border border-transparent rounded-md py-2 px-4 flex items-center justify-center text-base font-medium text-white hover:bg-copihue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500 transition-colors duration-200">
                                        Suscribirse
                                    </button>
                                </div>
                            </form>
                            <p class="mt-2 text-xs text-gray-400">Suscribiremos tu cuenta (<?= htmlspecialchars($newsletterEmail) ?>). Podrás darte de baja en cualquier momento desde el enlace incluido en cada correo.</p>
                        <?php endif; ?>
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
<div id="share-modal" class="fixed inset-0 z-50 hidden items-center justify-center px-4 py-6" aria-hidden="true">
    <div class="absolute inset-0 bg-gray-900/50" data-share-overlay style="backdrop-filter: blur(8px);"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl share-modal-panel" role="dialog" aria-modal="true" aria-labelledby="share-modal-title" data-share-panel>
        <div class="flex items-start justify-between border-b border-gray-100 px-6 py-4">
            <div>
                <h2 id="share-modal-title" class="text-lg font-semibold text-gray-900">Compartir campaña</h2>
                <p class="text-sm text-gray-500">Comparte <span data-share-name class="font-medium text-gray-900">esta campaña</span> con tu comunidad.</p>
            </div>
            <button type="button" class="text-gray-400 hover:text-gray-600" data-share-close aria-label="Cerrar">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-6 py-5 space-y-4">
            <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">Enlace directo</label>
                <div class="flex items-center gap-2">
                    <input type="text" data-share-url readonly class="flex-1 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 focus:border-copihue-500 focus:ring-copihue-500">
                    <button type="button" data-share-copy class="inline-flex items-center gap-2 rounded-lg bg-copihue-600 px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-copihue-700 focus:outline-none focus:ring-2 focus:ring-copihue-500 focus:ring-offset-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8h2a2 2 0 012 2v8a2 2 0 01-2 2h-8a2 2 0 01-2-2v-2"/></svg>
                        Copiar
                    </button>
                </div>
                <p class="text-xs text-emerald-600 hidden" data-share-feedback>Enlace copiado al portapapeles.</p>
            </div>
            <div class="space-y-2">
                <h3 class="text-sm font-medium text-gray-700">Compartir en redes</h3>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <a href="#" target="_blank" rel="noopener" data-share-network="whatsapp" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-copihue-500 hover:text-copihue-600">
                        <svg class="h-5 w-5" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                            <g stroke-width="0"></g>
                            <g stroke-linecap="round" stroke-linejoin="round"></g>
                            <g>
                                <title>whatsapp</title>
                                <path d="M26.576 5.363c-2.69-2.69-6.406-4.354-10.511-4.354-8.209 0-14.865 6.655-14.865 14.865 0 2.732 0.737 5.291 2.022 7.491l-0.038-0.070-2.109 7.702 7.879-2.067c2.051 1.139 4.498 1.809 7.102 1.809h0.006c8.209-0.003 14.862-6.659 14.862-14.868 0-4.103-1.662-7.817-4.349-10.507l0 0zM16.062 28.228h-0.005c-0 0-0.001 0-0.001 0-2.319 0-4.489-0.64-6.342-1.753l0.056 0.031-0.451-0.267-4.675 1.227 1.247-4.559-0.294-0.467c-1.185-1.862-1.889-4.131-1.889-6.565 0-6.822 5.531-12.353 12.353-12.353s12.353 5.531 12.353 12.353c0 6.822-5.53 12.353-12.353 12.353h-0zM22.838 18.977c-0.371-0.186-2.197-1.083-2.537-1.208-0.341-0.124-0.589-0.185-0.837 0.187-0.246 0.371-0.958 1.207-1.175 1.455-0.216 0.249-0.434 0.279-0.805 0.094-1.15-0.466-2.138-1.087-2.997-1.852l0.010 0.009c-0.799-0.74-1.484-1.587-2.037-2.521l-0.028-0.052c-0.216-0.371-0.023-0.572 0.162-0.757 0.167-0.166 0.372-0.434 0.557-0.65 0.146-0.179 0.271-0.384 0.366-0.604l0.006-0.017c0.043-0.087 0.068-0.188 0.068-0.296 0-0.131-0.037-0.253-0.101-0.357l0.002 0.003c-0.094-0.186-0.836-2.014-1.145-2.758-0.302-0.724-0.609-0.625-0.836-0.637-0.216-0.010-0.464-0.012-0.712-0.012-0.395 0.010-0.746 0.188-0.988 0.463l-0.001 0.002c-0.802 0.761-1.3 1.834-1.3 3.023 0 0.026 0 0.053 0.001 0.079l-0-0.004c0.131 1.467 0.681 2.784 1.527 3.857l-0.012-0.015c1.604 2.379 3.742 4.282 6.251 5.564l0.094 0.043c0.548 0.248 1.25 0.513 1.968 0.74l0.149 0.041c0.442 0.14 0.951 0.221 1.479 0.221 0.303 0 0.601-0.027 0.889-0.078l-0.031 0.004c1.069-0.223 1.956-0.868 2.497-1.749l0.009-0.017c0.165-0.366 0.261-0.793 0.261-1.242 0-0.185-0.016-0.366-0.047-0.542l0.003 0.019c-0.092-0.155-0.34-0.247-0.712-0.434z"></path>
                            </g>
                        </svg>
                        <span>WhatsApp</span>
                    </a>
                    <a href="#" target="_blank" rel="noopener" data-share-network="facebook" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-copihue-500 hover:text-copihue-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M22 12.07C22 6.55 17.52 2 12 2S2 6.55 2 12.07c0 5.02 3.66 9.18 8.44 9.93v-7.03H7.9v-2.9h2.54V9.78c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.23.2 2.23.2v2.45h-1.26c-1.24 0-1.63.77-1.63 1.56v1.87h2.78l-.44 2.9h-2.34v7.03C18.34 21.25 22 17.09 22 12.07Z"/>
                        </svg>
                        <span>Facebook</span>
                    </a>
                    <a href="#" target="_blank" rel="noopener" data-share-network="x" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-copihue-500 hover:text-copihue-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M13.795 10.533 20.68 2h-3.073l-5.255 6.517L7.69 2H1l7.806 10.91L1.47 22h3.074l5.705-7.07L15.31 22H22l-8.205-11.467Zm-2.38 2.95L9.97 11.464 4.36 3.627h2.31l4.528 6.317 1.443 2.02 6.018 8.409h-2.31l-4.934-6.89Z"/>
                        </svg>
                        <span>X</span>
                    </a>
                    <a href="#" target="_blank" rel="noopener" data-share-network="linkedin" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-copihue-500 hover:text-copihue-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M20.45 20.45h-3.55v-5.57c0-1.33-.03-3.04-1.85-3.04-1.85 0-2.13 1.45-2.13 2.94v5.67H9.35V9h3.41v1.56h.05c.47-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28ZM5.34 7.43a2.06 2.06 0 1 1 0-4.12 2.06 2.06 0 0 1 0 4.12ZM7.12 20.45H3.55V9h3.57v11.45Z"/>
                        </svg>
                        <span>LinkedIn</span>
                    </a>
                    <a href="#" target="_blank" rel="noopener" data-share-network="instagram" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-copihue-500 hover:text-copihue-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path fill="currentColor" fill-rule="evenodd" d="M3 8a5 5 0 0 1 5-5h8a5 5 0 0 1 5 5v8a5 5 0 0 1-5 5H8a5 5 0 0 1-5-5V8Zm5-3a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3h8a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3H8Zm7.597 2.214a1 1 0 0 1 1-1h.01a1 1 0 1 1 0 2h-.01a1 1 0 0 1-1-1ZM12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm-5 3a5 5 0 1 1 10 0 5 5 0 0 1-10 0Z" clip-rule="evenodd"/>
                        </svg>
                        <span>Instagram</span>
                    </a>
                    <a href="#" data-share-network="email" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-copihue-500 hover:text-copihue-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M4.5 5.75h15a1.75 1.75 0 0 1 1.75 1.75v9a1.75 1.75 0 0 1-1.75 1.75h-15A1.75 1.75 0 0 1 2.75 16.5v-9A1.75 1.75 0 0 1 4.5 5.75Z"/>
                            <path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="m5 7 7 5 7-5"/>
                        </svg>
                        <span>Correo</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- JS de interacción ligera (sin CDN) -->
<script src="<?= asset_url('js/app.js') ?>" defer></script>
