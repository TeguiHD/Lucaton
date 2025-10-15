<?php
require_once __DIR__ . '/../components/navigation.php';

$current_page = $current_page ?? 'help_center';
$page_context = $page_context ?? 'help';

$page_title = 'Centro de ayuda - Lucatón';
$page_description = 'Encuentra respuestas rápidas, guías paso a paso y soporte especializado para gestionar tus campañas exitosamente.';
$faq_categories = require __DIR__ . '/partials/faq-data.php';

// Categorías de ayuda reorganizadas para mejor UX
$help_categories = [
    [
        'key' => 'primeros-pasos',
        'title' => 'Primeros pasos',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5s3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253" />',
        'description' => 'Todo lo que necesitas para comenzar en Lucatón',
        'color' => 'bg-blue-50 text-blue-700 border-blue-200',
        'articles' => [
            [
                'id' => 'crear-cuenta',
                'title' => 'Crear cuenta y verificar email',
                'summary' => 'Guía completa para registrarte y activar tu cuenta',
                'body' => static function (): string {
                    $registerUrl = htmlspecialchars(Router::url('registro'), ENT_QUOTES, 'UTF-8');
                    $supportEmail = htmlspecialchars(PROJECT_OWNER_EMAIL, ENT_QUOTES, 'UTF-8');
                    return <<<HTML
<div class="space-y-4">
    <p class="text-gray-700">Crear tu cuenta en Lucatón es rápido y te permite acceder a todas las funcionalidades de la plataforma.</p>
    
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r-lg">
        <h4 class="font-semibold text-blue-800 mb-2">Pasos para registrarte:</h4>
        <ol class="list-decimal list-inside space-y-2 text-blue-700">
            <li>Visita la <a href="$registerUrl" class="font-medium underline hover:no-underline">página de registro</a></li>
            <li>Completa el formulario con tus datos reales</li>
            <li>Revisa tu email y confirma tu cuenta</li>
            <li>¡Listo! Ya puedes explorar y crear campañas</li>
        </ol>
    </div>
    
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <p class="text-sm text-yellow-800"><strong>💡 Consejo:</strong> Usa un email que revises frecuentemente para recibir notificaciones importantes.</p>
    </div>
</div>
HTML;
                },
            ],
            [
                'id' => 'checklist-campana',
                'title' => 'Checklist para crear tu primera campaña',
                'summary' => 'Requisitos y documentos necesarios antes de publicar',
                'body' => static function (): string {
                    return <<<HTML
<div class="space-y-4">
    <p class="text-gray-700">Antes de publicar tu campaña, asegúrate de tener todo preparado para maximizar tus posibilidades de éxito.</p>
    
    <div class="grid gap-4 md:grid-cols-2">
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <h4 class="font-semibold text-green-800 mb-3">📋 Documentación necesaria</h4>
            <ul class="space-y-2 text-green-700 text-sm">
                <li class="flex items-start gap-2">
                    <span class="text-green-500 mt-0.5">✓</span>
                    <span>Identificación personal válida</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-green-500 mt-0.5">✓</span>
                    <span>Presupuesto detallado del proyecto</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-green-500 mt-0.5">✓</span>
                    <span>Imágenes de alta calidad</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-green-500 mt-0.5">✓</span>
                    <span>Certificados o permisos (si aplica)</span>
                </li>
            </ul>
        </div>
        
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <h4 class="font-semibold text-purple-800 mb-3">✨ Elementos clave</h4>
            <ul class="space-y-2 text-purple-700 text-sm">
                <li class="flex items-start gap-2">
                    <span class="text-purple-500 mt-0.5">•</span>
                    <span>Historia clara y emotiva</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-purple-500 mt-0.5">•</span>
                    <span>Meta realista y justificada</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-purple-500 mt-0.5">•</span>
                    <span>Plan de comunicación con donantes</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-purple-500 mt-0.5">•</span>
                    <span>Cronograma de ejecución</span>
                </li>
            </ul>
        </div>
    </div>
</div>
HTML;
                },
            ],
        ],
    ],
    [
        'key' => 'campanas',
        'title' => 'Gestión de campañas',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />',
        'description' => 'Optimiza y administra tus campañas efectivamente',
        'color' => 'bg-green-50 text-green-700 border-green-200',
        'articles' => [
            [
                'id' => 'ia-historia',
                'title' => 'Usar IA para mejorar tu historia',
                'summary' => 'Aprovecha la inteligencia artificial de forma ética y efectiva',
                'body' => <<<HTML
<div class="space-y-4">
    <p class="text-gray-700">Nuestra IA puede ayudarte a estructurar y mejorar la narrativa de tu campaña, siempre manteniendo la autenticidad.</p>
    
    <div class="bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-lg p-4">
        <h4 class="font-semibold text-gray-800 mb-3">🤖 Cómo usar la IA efectivamente:</h4>
        <div class="space-y-3">
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold">1</span>
                <div>
                    <p class="font-medium text-gray-800">Define el tono</p>
                    <p class="text-sm text-gray-600">Elige si quieres un enfoque emotivo, profesional o urgente</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold">2</span>
                <div>
                    <p class="font-medium text-gray-800">Proporciona datos reales</p>
                    <p class="text-sm text-gray-600">La IA necesita información verídica para generar contenido auténtico</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold">3</span>
                <div>
                    <p class="font-medium text-gray-800">Revisa y personaliza</p>
                    <p class="text-sm text-gray-600">Siempre ajusta el contenido generado para que refleje tu voz</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
        <p class="text-sm text-amber-800"><strong>⚠️ Importante:</strong> El contenido generado por IA puede requerir revisión manual antes de la publicación.</p>
    </div>
</div>
HTML,
            ],
            [
                'id' => 'actualizaciones',
                'title' => 'Mantener informados a tus donantes',
                'summary' => 'Estrategias para comunicación transparente y efectiva',
                'body' => <<<HTML
<div class="space-y-4">
    <p class="text-gray-700">La comunicación regular con tus donantes es clave para mantener la confianza y el compromiso con tu causa.</p>
    
    <div class="grid gap-4 md:grid-cols-3">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
            <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <h4 class="font-semibold text-blue-800 mb-2">Fotos del progreso</h4>
            <p class="text-sm text-blue-700">Comparte imágenes que muestren el avance real de tu proyecto</p>
        </div>
        
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h4 class="font-semibold text-green-800 mb-2">Hitos alcanzados</h4>
            <p class="text-sm text-green-700">Celebra los logros y explica cómo se han usado los fondos</p>
        </div>
        
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
            <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
            </div>
            <h4 class="font-semibold text-purple-800 mb-2">Próximos pasos</h4>
            <p class="text-sm text-purple-700">Mantén a todos informados sobre lo que viene</p>
        </div>
    </div>
    
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <h4 class="font-semibold text-gray-800 mb-2">📅 Frecuencia recomendada:</h4>
        <ul class="text-sm text-gray-700 space-y-1">
            <li>• <strong>Semanal:</strong> Durante los primeros 30 días</li>
            <li>• <strong>Quincenal:</strong> Para campañas de mediano plazo</li>
            <li>• <strong>Mensual:</strong> Para proyectos de largo plazo</li>
        </ul>
    </div>
</div>
HTML,
            ],
        ],
    ],
    [
        'key' => 'pagos-seguridad',
        'title' => 'Pagos y seguridad',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />',
        'description' => 'Información sobre transacciones y medidas de seguridad',
        'color' => 'bg-red-50 text-red-700 border-red-200',
        'articles' => [
            [
                'id' => 'estado-donaciones',
                'title' => 'Revisar el estado de mis donaciones',
                'summary' => 'Cómo consultar y descargar comprobantes de tus aportes',
                'body' => static function (): string {
                    return <<<HTML
<div class="space-y-4">
    <p class="text-gray-700">Mantén un registro completo de todas tus donaciones y su estado desde tu panel personal.</p>
    
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r-lg">
        <h4 class="font-semibold text-blue-800 mb-3">🔍 Cómo revisar tus donaciones:</h4>
        <ol class="list-decimal list-inside space-y-2 text-blue-700">
            <li>Accede a tu <strong>Panel de usuario</strong></li>
            <li>Ve a la sección <strong>"Mis donaciones"</strong></li>
            <li>Filtra por fecha, campaña o estado</li>
            <li>Descarga comprobantes en PDF</li>
        </ol>
    </div>
    
    <div class="grid gap-4 md:grid-cols-2">
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <h4 class="font-semibold text-green-800 mb-2">✅ Estados normales</h4>
            <ul class="text-sm text-green-700 space-y-1">
                <li>• <strong>Completado:</strong> Donación procesada exitosamente</li>
                <li>• <strong>Procesando:</strong> En verificación (hasta 24h)</li>
            </ul>
        </div>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <h4 class="font-semibold text-yellow-800 mb-2">⚠️ Requiere atención</h4>
            <ul class="text-sm text-yellow-700 space-y-1">
                <li>• <strong>Pendiente:</strong> Esperando confirmación bancaria</li>
                <li>• <strong>Fallido:</strong> Error en el procesamiento</li>
            </ul>
        </div>
    </div>
</div>
HTML;
                },
            ],
            [
                'id' => 'reportar-problema',
                'title' => 'Reportar campaña sospechosa',
                'summary' => 'Cómo ayudar a mantener la plataforma segura',
                'body' => static function (): string {
                    $reportUrl = htmlspecialchars(Router::url('reportar'), ENT_QUOTES, 'UTF-8');
                    return <<<HTML
<div class="space-y-4">
    <p class="text-gray-700">Tu vigilancia ayuda a mantener Lucatón como un espacio seguro y confiable para todos.</p>
    
    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <h4 class="font-semibold text-red-800 mb-3">🚨 Señales de alerta:</h4>
        <ul class="text-sm text-red-700 space-y-2">
            <li class="flex items-start gap-2">
                <span class="text-red-500 mt-0.5">⚠️</span>
                <span>Información inconsistente o contradictoria</span>
            </li>
            <li class="flex items-start gap-2">
                <span class="text-red-500 mt-0.5">⚠️</span>
                <span>Imágenes que no corresponden al proyecto</span>
            </li>
            <li class="flex items-start gap-2">
                <span class="text-red-500 mt-0.5">⚠️</span>
                <span>Promesas poco realistas o exageradas</span>
            </li>
            <li class="flex items-start gap-2">
                <span class="text-red-500 mt-0.5">⚠️</span>
                <span>Falta de documentación de respaldo</span>
            </li>
        </ul>
    </div>
    
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r-lg">
        <h4 class="font-semibold text-blue-800 mb-2">📝 Proceso de reporte:</h4>
        <ol class="list-decimal list-inside space-y-2 text-blue-700">
            <li>Recopila evidencia (capturas, enlaces)</li>
            <li>Completa el <a href="$reportUrl" class="font-medium underline hover:no-underline">formulario de reporte</a></li>
            <li>Nuestro equipo revisará en 24-48 horas</li>
            <li>Te contactaremos si necesitamos más información</li>
        </ol>
    </div>
</div>
HTML;
                },
            ],
        ],
    ],
];

// Acciones rápidas mejoradas
$quick_actions = [
    [
        'title' => 'Crear mi primera campaña',
        'description' => 'Guía paso a paso para lanzar tu proyecto exitosamente',
        'href' => Router::url('campana/crear'),
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />',
        'variant' => 'primary',
        'badge' => 'Nuevo'
    ],
    [
        'title' => 'Contactar soporte',
        'description' => 'Obtén ayuda personalizada de nuestro equipo',
        'href' => Router::url('contacto'),
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />',
        'variant' => 'secondary',
    ],
    [
        'title' => 'Reportar problema',
        'description' => 'Informa sobre incidentes técnicos o campañas sospechosas',
        'href' => Router::url('reportar'),
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />',
        'variant' => 'outline',
    ],
    [
        'title' => 'Estado del sistema',
        'description' => 'Verifica el estado de nuestros servicios',
        'href' => Router::url('estado'),
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'variant' => 'outline',
    ],
];

// Preguntas frecuentes destacadas
$featured_faqs = [
    '¿Cuánto tiempo toma aprobar una campaña?' => 'Nuestro equipo revisa las campañas en un máximo de 24 horas hábiles. Te notificaremos por email sobre el estado.',
    '¿Puedo editar mi campaña después de publicarla?' => 'Sí, puedes actualizar la descripción, imágenes y meta en cualquier momento desde tu panel de control.',
    '¿Cómo retiro los fondos recaudados?' => 'Una vez que tu campaña termine, podrás solicitar el retiro desde tu panel. El proceso toma 3-5 días hábiles.',
    '¿Qué comisión cobra Lucatón?' => 'Cobramos una comisión del 5% sobre los fondos recaudados para mantener y mejorar la plataforma.',
];

$total_help_articles = array_reduce(
    $help_categories,
    static function (int $carry, array $category): int {
        return $carry + count($category['articles']);
    },
    0
);

$stripAccents = static function (string $value): string {
    $value = (string)$value;

    if ($value === '') {
        return '';
    }

    if (class_exists('\Normalizer')) {
        $normalized = \Normalizer::normalize($value, \Normalizer::FORM_D);
        if ($normalized !== false) {
            $withoutMarks = preg_replace('/\p{Mn}+/u', '', $normalized);
            if (is_string($withoutMarks)) {
                $value = $withoutMarks;
            } else {
                $value = $normalized;
            }
        }
    }

    $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($transliterated !== false) {
        $value = $transliterated;
    }

    $value = strtr(
        $value,
        [
            'á' => 'a',
            'Á' => 'A',
            'é' => 'e',
            'É' => 'E',
            'í' => 'i',
            'Í' => 'I',
            'ó' => 'o',
            'Ó' => 'O',
            'ú' => 'u',
            'Ú' => 'U',
            'ü' => 'u',
            'Ü' => 'U',
            'ñ' => 'n',
            'Ñ' => 'N',
        ]
    );

    return $value;
};

$slugify = static function (string $value) use ($stripAccents): string {
    $normalized = strtolower($stripAccents($value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $normalized);
    return trim((string)$slug, '-');
};

$toSearchIndex = static function (string $value) use ($stripAccents): string {
    $withoutAccents = $stripAccents($value);
    $lower = strtolower($withoutAccents);
    $normalized = preg_replace('/[^a-z0-9]+/', ' ', $lower);

    return trim((string)$normalized);
};

$truncateText = static function (string $value, int $length = 180): string {
    $sanitized = trim(preg_replace('/\s+/', ' ', $value));
    if ($sanitized === '') {
        return '';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($sanitized) <= $length) {
            return $sanitized;
        }

        return rtrim(mb_substr($sanitized, 0, $length)) . '…';
    }

    if (strlen($sanitized) <= $length) {
        return $sanitized;
    }

    return rtrim(substr($sanitized, 0, $length)) . '…';
};

$search_index = [];

foreach ($help_categories as $category) {
    foreach ($category['articles'] as $article) {
        $articleHtml = is_callable($article['body']) ? (string)$article['body']() : (string)$article['body'];
        $articlePlain = trim(preg_replace('/\s+/', ' ', strip_tags($articleHtml)));
        $summary = $article['summary'] ?? '';
        $snippetSource = $articlePlain !== '' ? $articlePlain : $summary;

        $search_index[] = [
            'id' => $article['id'],
            'type' => 'article',
            'title' => $article['title'],
            'category' => $category['title'],
            'categoryKey' => $category['key'] ?? '',
            'summary' => $summary,
            'snippet' => $truncateText($snippetSource),
            'content' => $articlePlain,
            'html' => $articleHtml,
            'search' => $toSearchIndex($article['title'] . ' ' . $summary . ' ' . $articlePlain . ' ' . $category['title']),
        ];
    }
}

foreach ($faq_categories as $faqCategory) {
    foreach ($faqCategory['questions'] as $qa) {
        $rawId = $qa['id'] ?? '';
        if (!is_string($rawId) || $rawId === '') {
            $rawId = $slugify($qa['question']);
            if ($rawId === '') {
                $rawId = md5($qa['question']);
            }
        }

        $faqId = 'faq-' . $rawId;
        $answerPlain = trim(preg_replace('/\s+/', ' ', (string)$qa['answer']));
        $faqHtml = '<div class="space-y-4"><p class="text-gray-700">' . nl2br(htmlspecialchars((string)$qa['answer'], ENT_QUOTES, 'UTF-8')) . '</p></div>';

        $search_index[] = [
            'id' => $faqId,
            'type' => 'faq',
            'title' => $qa['question'],
            'category' => $faqCategory['title'],
            'categoryKey' => $faqCategory['key'] ?? '',
            'summary' => (string)$qa['answer'],
            'snippet' => $truncateText($answerPlain !== '' ? $answerPlain : (string)$qa['answer']),
            'content' => $answerPlain,
            'html' => $faqHtml,
            'search' => $toSearchIndex($qa['question'] . ' ' . $qa['answer'] . ' ' . $faqCategory['title']),
        ];
    }
}

if (!empty($featured_faqs)) {
    foreach ($featured_faqs as $question => $answer) {
        $faqId = 'featured-faq-' . $slugify((string)$question);
        if ($faqId === 'featured-faq-') {
            $faqId = 'featured-faq-' . md5((string)$question);
        }

        $answerPlain = trim(preg_replace('/\s+/', ' ', (string)$answer));
        $faqHtml = '<div class="space-y-4"><p class="text-gray-700">' . nl2br(htmlspecialchars((string)$answer, ENT_QUOTES, 'UTF-8')) . '</p></div>';

        $search_index[] = [
            'id' => $faqId,
            'type' => 'featured_faq',
            'title' => (string)$question,
            'category' => 'Preguntas frecuentes destacadas',
            'categoryKey' => 'faq-featured',
            'summary' => (string)$answer,
            'snippet' => $truncateText($answerPlain !== '' ? $answerPlain : (string)$answer),
            'content' => $answerPlain,
            'html' => $faqHtml,
            'search' => $toSearchIndex((string)$question . ' ' . (string)$answer),
        ];
    }
}

$search_index_json = json_encode(
    $search_index,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP
);

if ($search_index_json === false) {
    $search_index_json = '[]';
}
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

    <link rel="icon" type="image/svg+xml" href="<?= asset_url('images/favicon.svg') ?>">
    <link href="<?= asset_url('css/app.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('css/aliases.css') ?>" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <?php include __DIR__ . '/../layouts/partials/header.php'; ?>

    <main id="main-content" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumb -->
        <?= render_breadcrumb([
            ['name' => 'Inicio', 'href' => Router::url('/')],
            ['name' => 'Centro de ayuda', 'href' => Router::url('ayuda')],
        ]); ?>

        <!-- Hero Section -->
        <section class="relative overflow-hidden rounded-3xl bg-white p-8 lg:p-12 mb-12 shadow-2xl border border-gray-100">
            <div class="max-w-4xl">
                <h1 class="text-4xl lg:text-6xl font-bold text-gray-900 mb-6 leading-tight">
                    ¿En qué podemos <span class="text-yellow-500">ayudarte</span>?
                </h1>
                
                <p class="text-xl text-gray-600 mb-8 max-w-2xl leading-relaxed">
                    Encuentra respuestas rápidas, guías detalladas y soporte especializado para hacer realidad tus proyectos en Lucatón.
                </p>

                <!-- Search Bar -->
                <div class="relative max-w-2xl">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input 
                        id="help-search" 
                        type="search" 
                        placeholder="Buscar en el centro de ayuda..." 
                        class="w-full pl-12 pr-4 py-4 text-lg bg-gray-50 border border-gray-200 rounded-2xl shadow-sm focus:ring-4 focus:ring-copihue-500/20 focus:border-copihue-500 focus:outline-none transition-all duration-200"
                        autocomplete="off"
                    >
                </div>

                <!-- Quick search suggestions -->
                <div class="flex flex-wrap gap-2 mt-6">
                    <span class="text-gray-500 text-sm font-medium">Búsquedas populares:</span>
                    <?php foreach (['crear campaña', 'verificar cuenta', 'retirar fondos', 'reportar problema'] as $suggestion): ?>
                        <button class="bg-gray-100 hover:bg-copihue-50 hover:text-copihue-700 text-gray-700 text-sm px-4 py-2 rounded-full border border-gray-200 hover:border-copihue-200 transition-all duration-200 hover:shadow-sm" 
                                onclick="document.getElementById('help-search').value = '<?= htmlspecialchars($suggestion) ?>'; searchHelp(true);">
                            <?= htmlspecialchars($suggestion) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Search Results -->
        <section id="search-results" class="hidden mb-12">
            <div class="bg-white border border-gray-200 rounded-3xl shadow-lg p-6 lg:p-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Resultados de búsqueda</h2>
                        <p class="text-sm text-gray-600">Selecciona un resultado para abrir la guía o respuesta completa.</p>
                    </div>
                    <p id="search-results-info" class="text-sm text-gray-500"></p>
                </div>
                <div class="mt-6 space-y-3" data-search-results>
                    <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center text-sm text-gray-600">
                        Escribe al menos dos caracteres para buscar en todo el centro de ayuda.
                    </div>
                </div>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="mb-12" data-search-hide>
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Acciones rápidas</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Accede directamente a las funciones más utilizadas</p>
            </div>
            
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <?php foreach ($quick_actions as $action): ?>
                    <?php
                    $variant = $action['variant'] ?? 'secondary';
                    $baseClasses = 'group relative block p-6 rounded-2xl border transition-all duration-300 hover:scale-105 hover:shadow-xl';
                    
                    switch ($variant) {
                        case 'primary':
                            $cardClasses = $baseClasses . ' bg-gradient-to-br from-copihue-500 to-copihue-600 border-copihue-500 text-white shadow-lg';
                            $iconClasses = 'w-12 h-12 bg-white/20 text-white rounded-xl flex items-center justify-center mb-4';
                            break;
                        case 'secondary':
                            $cardClasses = $baseClasses . ' bg-white border-gray-200 hover:border-blue-300 shadow-md';
                            $iconClasses = 'w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center mb-4';
                            break;
                        default:
                            $cardClasses = $baseClasses . ' bg-white border-gray-200 hover:border-gray-300 shadow-md';
                            $iconClasses = 'w-12 h-12 bg-gray-100 text-gray-600 rounded-xl flex items-center justify-center mb-4';
                    }
                    ?>
                    <a href="<?= htmlspecialchars($action['href']) ?>" class="<?= $cardClasses ?>">
                        <?php if (isset($action['badge'])): ?>
                            <span class="absolute -top-2 -right-2 bg-yellow-400 text-yellow-900 text-xs font-bold px-2 py-1 rounded-full">
                                <?= htmlspecialchars($action['badge']) ?>
                            </span>
                        <?php endif; ?>
                        
                        <div class="<?= $iconClasses ?>">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <?= $action['icon'] ?>
                            </svg>
                        </div>
                        
                        <h3 class="font-semibold text-lg mb-2 <?= $variant === 'primary' ? 'text-white' : 'text-gray-900' ?>">
                            <?= htmlspecialchars($action['title']) ?>
                        </h3>
                        
                        <p class="text-sm <?= $variant === 'primary' ? 'text-white/80' : 'text-gray-600' ?>">
                            <?= htmlspecialchars($action['description']) ?>
                        </p>
                        
                        <div class="mt-4 flex items-center gap-2 <?= $variant === 'primary' ? 'text-white' : 'text-blue-600' ?>">
                            <span class="text-sm font-medium">Ir ahora</span>
                            <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Featured FAQs -->
        <section class="mb-12" data-search-hide>
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Preguntas frecuentes</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Las dudas más comunes resueltas al instante</p>
            </div>
            
            <div class="grid gap-4 md:grid-cols-2">
                <?php foreach ($featured_faqs as $question => $answer): ?>
                    <details class="group bg-white rounded-2xl border border-gray-200 shadow-md hover:shadow-lg transition-all duration-200">
                        <summary class="flex items-center justify-between p-6 cursor-pointer list-none">
                            <h3 class="font-semibold text-gray-900 pr-4"><?= htmlspecialchars($question) ?></h3>
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center transition-transform group-open:rotate-180">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </summary>
                        <div class="px-6 pb-6 text-gray-600 border-t border-gray-100 pt-4">
                            <?= htmlspecialchars($answer) ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Help Categories -->
        <section class="mb-12" data-search-hide>
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Guías por categoría</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Explora nuestras guías organizadas por temas</p>
            </div>
            
            <div class="grid gap-8 lg:grid-cols-3">
                <?php foreach ($help_categories as $category): ?>
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden">
                        <!-- Category Header -->
                        <div class="<?= $category['color'] ?> p-6">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <?= $category['icon'] ?>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg"><?= htmlspecialchars($category['title']) ?></h3>
                                    <p class="text-sm opacity-80"><?= count($category['articles']) ?> artículos</p>
                                </div>
                            </div>
                            <p class="text-sm opacity-90"><?= htmlspecialchars($category['description']) ?></p>
                        </div>
                        
                        <!-- Articles List -->
                        <div class="p-6">
                            <div class="space-y-4">
                                <?php foreach ($category['articles'] as $article): ?>
                                    <button 
                                        type="button"
                                        class="w-full text-left p-4 rounded-xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50 transition-all duration-200 group"
                                        data-article-id="<?= htmlspecialchars($article['id']) ?>"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex-1">
                                                <h4 class="font-semibold text-gray-900 group-hover:text-blue-700 mb-1">
                                                    <?= htmlspecialchars($article['title']) ?>
                                                </h4>
                                                <p class="text-sm text-gray-600">
                                                    <?= htmlspecialchars($article['summary']) ?>
                                                </p>
                                            </div>
                                            <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600 transition-colors flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </div>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Contact Support -->
        <section class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-3xl p-8 lg:p-12 text-center" data-search-hide>
            <div class="max-w-3xl mx-auto">
                <div class="w-16 h-16 bg-blue-500 rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 2.25a9.75 9.75 0 11-9.75 9.75A9.75 9.75 0 0112 2.25z"></path>
                    </svg>
                </div>
                
                <h2 class="text-3xl font-bold text-white mb-4">¿No encontraste lo que buscabas?</h2>
                <p class="text-xl text-gray-300 mb-8">Nuestro equipo de soporte está aquí para ayudarte personalmente</p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="<?= Router::url('contacto') ?>" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-4 rounded-xl transition-colors duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Contactar soporte
                    </a>
                    <a href="<?= Router::url('reportar') ?>" class="inline-flex items-center gap-2 glass text-white font-semibold px-8 py-4 rounded-xl border border-white/20 transition-colors duration-200 hover:glass-strong">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        Reportar problema
                    </a>
                </div>
                
                <p class="text-gray-400 text-sm mt-6">
                    Tiempo promedio de respuesta: <strong class="text-white">2 horas</strong> • Disponible 24/7
                </p>
            </div>
        </section>
    </main>

    <!-- Article Modal -->
    <div id="articleModal" class="fixed inset-0 glass-dark z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="glass-strong rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden shadow-2xl">
                <div class="flex items-center justify-between p-6 border-b border-white/20">
                    <h2 id="modalTitle" class="text-2xl font-bold text-gray-900 mr-4"></h2>
                    <button onclick="closeArticleModal()" class="w-10 h-10 glass-subtle hover:glass-strong rounded-full flex items-center justify-center transition-all duration-200">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                    <div id="modalContent" class="prose prose-lg max-w-none"></div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../layouts/partials/footer.php'; ?>

    <script>
        (function () {
            const HELP_CENTER_INDEX = <?= $search_index_json ?>;
            const helpIndexMap = new Map();
            const ACCENT_REPLACEMENTS = Object.freeze({
                Á: 'A', À: 'A', Â: 'A', Ã: 'A', Ä: 'A',
                á: 'a', à: 'a', â: 'a', ã: 'a', ä: 'a',
                É: 'E', È: 'E', Ê: 'E', Ë: 'E',
                é: 'e', è: 'e', ê: 'e', ë: 'e',
                Í: 'I', Ì: 'I', Î: 'I', Ï: 'I',
                í: 'i', ì: 'i', î: 'i', ï: 'i',
                Ó: 'O', Ò: 'O', Ô: 'O', Õ: 'O', Ö: 'O',
                ó: 'o', ò: 'o', ô: 'o', õ: 'o', ö: 'o',
                Ú: 'U', Ù: 'U', Û: 'U', Ü: 'U',
                ú: 'u', ù: 'u', û: 'u', ü: 'u',
                Ç: 'C', ç: 'c',
                Ñ: 'N', ñ: 'n',
                ß: 'ss'
            });
            const TOKEN_SUFFIX_RULES = Object.freeze([
                { suffix: 'es', minRoot: 4 },
                { suffix: 's', minRoot: 4 },
                { suffix: 'ar', minRoot: 4 },
                { suffix: 'er', minRoot: 4 },
                { suffix: 'ir', minRoot: 4 }
            ]);
            const tokenVariantCache = new Map();

            HELP_CENTER_INDEX.forEach((item) => {
                if (item && item.id) {
                    helpIndexMap.set(item.id, item);
                }
            });

            const MIN_SEARCH_LENGTH = 2;
            const SEARCH_DEBOUNCE_MS = 350;
            const searchInput = document.getElementById('help-search');
            const searchResultsSection = document.getElementById('search-results');
            const searchResultsInfo = document.getElementById('search-results-info');
            const searchResultsContainer = searchResultsSection ? searchResultsSection.querySelector('[data-search-results]') : null;
            const defaultResultsPlaceholder = searchResultsContainer ? searchResultsContainer.innerHTML : '';
            const sectionsToToggle = document.querySelectorAll('[data-search-hide]');
            const modalElement = document.getElementById('articleModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalContent = document.getElementById('modalContent');
            let searchDebounceId = null;

            function stripAccents(value) {
                if (value === null || value === undefined) {
                    return '';
                }

                let normalized = value.toString();
                if (typeof normalized.normalize === 'function') {
                    normalized = normalized.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                }

                return normalized.replace(/[\u00C0-\u024F]/g, (char) => ACCENT_REPLACEMENTS[char] || char);
            }

            function normalizeTerm(value) {
                if (value === null || value === undefined) {
                    return '';
                }

                const stripped = stripAccents(value);
                return stripped
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, ' ')
                    .replace(/\s+/g, ' ')
                    .trim();
            }

            function tokenizeQuery(value) {
                if (!value) {
                    return [];
                }

                return value
                    .split(' ')
                    .map((token) => token.trim())
                    .filter((token) => token.length >= 2);
            }

            function getTokenVariants(token) {
                if (tokenVariantCache.has(token)) {
                    return tokenVariantCache.get(token);
                }

                const variants = new Set([token]);

                TOKEN_SUFFIX_RULES.forEach(({ suffix, minRoot }) => {
                    if (!token.endsWith(suffix)) {
                        return;
                    }
                    const root = token.slice(0, token.length - suffix.length);
                    if (root.length >= minRoot) {
                        variants.add(root);
                    }
                });

                const result = Array.from(variants);
                tokenVariantCache.set(token, result);
                return result;
            }

            function matchesQuery(item, normalizedQuery, tokens) {
                if (!item || !item.search) {
                    return false;
                }

                const haystack = item.search;

                if (normalizedQuery !== '' && haystack.includes(normalizedQuery)) {
                    return true;
                }

                if (!tokens.length) {
                    return false;
                }

                return tokens.every((token) => {
                    const variants = getTokenVariants(token);
                    return variants.some((variant) => variant !== '' && haystack.includes(variant));
                });
            }

            function escapeHTML(value) {
                if (value === null || value === undefined) {
                    return '';
                }

                return String(value).replace(/[&<>"']/g, function (char) {
                    const entities = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
                    return entities[char] || char;
                });
            }

            function toggleSections(active) {
                sectionsToToggle.forEach((section) => {
                    section.classList.toggle('hidden', Boolean(active));
                });
            }

            function clearSearchResults() {
                if (searchDebounceId) {
                    clearTimeout(searchDebounceId);
                    searchDebounceId = null;
                }
                if (searchResultsSection) {
                    searchResultsSection.classList.add('hidden');
                }
                if (searchResultsContainer) {
                    searchResultsContainer.innerHTML = defaultResultsPlaceholder;
                }
                if (searchResultsInfo) {
                    searchResultsInfo.textContent = '';
                }
                toggleSections(false);
            }

            function createResultItem(item) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'w-full text-left rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:border-copihue-400 hover:shadow-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-copihue-500';
                button.addEventListener('click', () => openArticleById(item.id));

                const isFaq = item.type === 'faq' || item.type === 'featured_faq';
                const badgeClasses = isFaq ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700';
                const badgeLabel = isFaq ? 'FAQ' : 'Guía';
                const categoryLabel = item.category
                    ? `<span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">${escapeHTML(item.category)}</span>`
                    : '';
                const description = item.snippet ? `<p class="text-xs text-gray-600 leading-relaxed">${escapeHTML(item.snippet)}</p>` : '';

                button.innerHTML = `
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="${badgeClasses} px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide">${badgeLabel}</span>
                                ${categoryLabel}
                            </div>
                            <p class="text-sm font-semibold text-gray-900">${escapeHTML(item.title)}</p>
                            ${description}
                        </div>
                        <svg class="w-4 h-4 text-gray-300 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </div>
                `;

                return button;
            }

            function renderSearchResults(matches, rawQuery) {
                if (!searchResultsSection || !searchResultsContainer) {
                    toggleSections(false);
                    return;
                }

                toggleSections(true);
                searchResultsSection.classList.remove('hidden');
                searchResultsContainer.innerHTML = '';

                searchResultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });

                if (!matches.length) {
                    if (searchResultsInfo) {
                        searchResultsInfo.textContent = rawQuery ? `Sin coincidencias para “${rawQuery}”` : 'Sin coincidencias';
                    }

                    const emptyState = document.createElement('div');
                    emptyState.className = 'rounded-xl border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center text-sm text-gray-600';
                    emptyState.innerHTML = '<p class="font-semibold text-gray-900 mb-2">No encontramos resultados</p><p class="text-sm">Intenta con otras palabras clave o revisa las categorías disponibles.</p>';
                    searchResultsContainer.appendChild(emptyState);
                    return;
                }

                if (searchResultsInfo) {
                    searchResultsInfo.textContent = matches.length === 1
                        ? '1 coincidencia encontrada'
                        : `${matches.length} coincidencias encontradas`;
                }

                const maxResults = 12;
                const limited = matches.slice(0, maxResults);

                const CATEGORY_ORDER = ['primeros-pasos', 'gestion-campanas', 'pagos-seguridad', 'otros'];
                const articleGroups = [];
                const articleGroupMap = new Map();
                const faqGroups = [];
                const faqGroupMap = new Map();

                limited.forEach((item) => {
                    const isArticle = item.type === 'article';

                    if (isArticle) {
                        const key = item.categoryKey || item.category || 'otros';
                        if (!articleGroupMap.has(key)) {
                            articleGroupMap.set(key, {
                                key,
                                title: item.category || 'Otras guías',
                                items: [],
                            });
                            articleGroups.push(articleGroupMap.get(key));
                        }
                        articleGroupMap.get(key).items.push(item);
                        return;
                    }

                    const faqKey = item.category || 'Preguntas frecuentes';
                    if (!faqGroupMap.has(faqKey)) {
                        faqGroupMap.set(faqKey, {
                            key: faqKey,
                            title: faqKey,
                            items: [],
                        });
                        faqGroups.push(faqGroupMap.get(faqKey));
                    }
                    faqGroupMap.get(faqKey).items.push(item);
                });

                articleGroups.sort((a, b) => {
                    const indexA = CATEGORY_ORDER.indexOf(a.key);
                    const indexB = CATEGORY_ORDER.indexOf(b.key);
                    return (indexA === -1 ? CATEGORY_ORDER.length : indexA) - (indexB === -1 ? CATEGORY_ORDER.length : indexB);
                });

                faqGroups.sort((a, b) => a.title.localeCompare(b.title));

                const fragment = document.createDocumentFragment();

                if (articleGroups.length) {
                    articleGroups.forEach((group) => {
                        const section = document.createElement('div');
                        section.className = 'rounded-2xl border border-gray-200 bg-gray-50/80 px-5 py-6 space-y-4';

                        const header = document.createElement('div');
                        header.className = 'flex flex-wrap items-center justify-between gap-3';
                        header.innerHTML = `
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-blue-700">Guías</span>
                                <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-gray-700 shadow-sm">${escapeHTML(group.title)}</span>
                            </div>
                            <span class="text-xs text-gray-500">${group.items.length === 1 ? '1 resultado' : group.items.length + ' resultados'}</span>
                        `;
                        section.appendChild(header);

                        const list = document.createElement('div');
                        list.className = 'space-y-3';
                        group.items.forEach((item) => {
                            list.appendChild(createResultItem(item));
                        });
                        section.appendChild(list);

                        fragment.appendChild(section);
                    });
                }

                if (faqGroups.length) {
                    faqGroups.forEach((group, index) => {
                        const section = document.createElement('div');
                        section.className = 'rounded-2xl border border-gray-200 bg-white px-5 py-6 space-y-4';

                        const header = document.createElement('div');
                        header.className = 'flex flex-wrap items-center justify-between gap-3';
                        header.innerHTML = `
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-purple-700">Preguntas frecuentes</span>
                                ${group.title !== 'Preguntas frecuentes' ? `<span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">${escapeHTML(group.title)}</span>` : ''}
                            </div>
                            <span class="text-xs text-gray-500">${group.items.length === 1 ? '1 resultado' : group.items.length + ' resultados'}</span>
                        `;
                        section.appendChild(header);

                        const list = document.createElement('div');
                        list.className = 'space-y-3';
                        group.items.forEach((item) => {
                            list.appendChild(createResultItem(item));
                        });
                        section.appendChild(list);

                        fragment.appendChild(section);
                    });
                }

                searchResultsContainer.appendChild(fragment);

                if (matches.length > limited.length) {
                    const moreInfo = document.createElement('p');
                    moreInfo.className = 'text-xs text-gray-500 text-right';
                    moreInfo.textContent = `Mostrando ${limited.length} de ${matches.length} resultados. Refina tu búsqueda para ver coincidencias más específicas.`;
                    searchResultsContainer.appendChild(moreInfo);
                }
            }

            function showModal(title, html) {
                if (!modalElement || !modalTitle || !modalContent) {
                    return;
                }

                modalTitle.textContent = title;
                modalContent.innerHTML = html;
                modalElement.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }

            function closeArticleModalLocal() {
                if (!modalElement || !modalContent) {
                    return;
                }

                modalElement.classList.add('hidden');
                modalContent.innerHTML = '';
                document.body.style.overflow = 'auto';
            }

            function openArticleById(id) {
                const item = helpIndexMap.get(id);
                if (!item) {
                    return;
                }

                const contentHtml = item.html && item.html.trim() !== ''
                    ? item.html
                    : `<div class="space-y-4"><p class="text-gray-700 leading-relaxed">${escapeHTML(item.content || item.summary || '')}</p></div>`;

                showModal(item.title, contentHtml);
            }

            function runSearch(force) {
                if (!searchInput) {
                    return;
                }

                if (searchDebounceId) {
                    clearTimeout(searchDebounceId);
                    searchDebounceId = null;
                }

                const rawQuery = searchInput.value.trim();
                const normalized = normalizeTerm(rawQuery);
                const shouldSearch = force ? normalized.length >= 1 : normalized.length >= MIN_SEARCH_LENGTH;

                const tokens = tokenizeQuery(normalized);
                const hasTokens = tokens.length > 0;
                const meetsLength = normalized.length >= MIN_SEARCH_LENGTH;
                const allowSearch = force ? hasTokens : (meetsLength && hasTokens);

                if (!allowSearch) {
                    clearSearchResults();
                    return;
                }

                const matches = HELP_CENTER_INDEX.filter((item) => matchesQuery(item, shouldSearch ? normalized : '', tokens));
                const uniqueMatches = [];
                const seenIds = new Set();

                matches.forEach((item) => {
                    if (!item || !item.id) {
                        return;
                    }
                    if (seenIds.has(item.id)) {
                        return;
                    }
                    seenIds.add(item.id);
                    uniqueMatches.push(item);
                });

                renderSearchResults(uniqueMatches, rawQuery);
            }

            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    if (searchDebounceId) {
                        clearTimeout(searchDebounceId);
                    }
                    searchDebounceId = window.setTimeout(() => {
                        runSearch(false);
                    }, SEARCH_DEBOUNCE_MS);
                });
                searchInput.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        runSearch(true);
                    }
                });
            }

            document.addEventListener('keydown', (event) => {
                if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                    event.preventDefault();
                    if (searchInput) {
                        searchInput.focus();
                        searchInput.select();
                    }
                }

                if (event.key === 'Escape') {
                    if (modalElement && !modalElement.classList.contains('hidden')) {
                        closeArticleModalLocal();
                        return;
                    }
                    if (searchInput && searchInput.value.length > 0) {
                        searchInput.value = '';
                        clearSearchResults();
                    }
                }
            });

            const articleButtons = document.querySelectorAll('[data-article-id]');
            articleButtons.forEach((button) => {
                button.addEventListener('click', () => openArticleById(button.dataset.articleId));
            });

            if (modalElement) {
                modalElement.addEventListener('click', (event) => {
                    if (event.target === modalElement) {
                        closeArticleModalLocal();
                    }
                });
            }

            window.openArticleById = openArticleById;
            window.searchHelp = function (force = false) {
                runSearch(Boolean(force));
            };
            window.closeArticleModal = closeArticleModalLocal;

            clearSearchResults();
        })();
    </script>
</body>
</html>
