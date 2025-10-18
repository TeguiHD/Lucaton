<?php
require_once __DIR__ . '/../components/navigation.php';

$current_page = $current_page ?? 'vision';
$page_title = 'Visión y Agenda 2030 - Lucatón';
$meta_description = 'Conoce cómo Lucatón alinea su plataforma de crowdfunding con los Objetivos de Desarrollo Sostenible y los principios de impacto social.';

$sdg_focus = [
    [
        'code' => 'ODS 1',
        'title' => 'Fin de la pobreza',
        'color' => 'from-[#E5243B] to-[#C21C34]',
        'accent' => '#E5243B',
        'border' => 'rgba(229, 36, 59, 0.25)',
        'badge_text' => 'text-white',
        'icon' => '<path d="M4 6h16v2H4zm0 4h16v2H4zm0 4h10v2H4zm0 4h6v2H4z" />',
        'description' => 'Aceleramos campañas que atienden necesidades básicas y fortalecen la resiliencia económica de comunidades subfinanciadas.',
        'lines' => [
            'Modelo de priorización que cruza urgencia socioeconómica, evidencia de beneficiarios y potencial de impacto local.',
            'Paneles de seguimiento que reportan hitos de uso de fondos, responsables y resultados verificables.'
        ]
    ],
    [
        'code' => 'ODS 4',
        'title' => 'Educación de calidad',
        'color' => 'from-[#C5192D] to-[#A71126]',
        'accent' => '#C5192D',
        'border' => 'rgba(197, 25, 45, 0.25)',
        'badge_text' => 'text-white',
        'icon' => '<path d="M3 7l9-4 9 4-9 4-9-4zm0 6l9 4 9-4v3l-9 4-9-4v-3zm0-3l9 4 9-4v2l-9 4-9-4V10z" />',
        'description' => 'Fortalecemos proyectos que amplían la alfabetización digital y el acceso a conocimiento para estudiantes y equipos educativos.',
        'lines' => [
            'Mentorías y recursos pedagógicos sobre storytelling responsable, finanzas y ética de datos para líderes de campaña.',
            'Indicadores de aprendizaje que miden cobertura, permanencia estudiantil y adopción de herramientas tecnológicas.'
        ]
    ],
    [
        'code' => 'ODS 8',
        'title' => 'Trabajo decente y crecimiento económico',
        'color' => 'from-[#A21942] to-[#7E1233]',
        'accent' => '#A21942',
        'border' => 'rgba(162, 25, 66, 0.25)',
        'badge_text' => 'text-white',
        'icon' => '<path d="M4 3h16v2H4zm0 4h10v2H4zm0 4h16v2H4zm0 4h10v2H4zm0 4h16v2H4z" />',
        'description' => 'Apalancamos financiamiento colectivo para emprendimientos y cooperativas que generan empleo formal y sostenibilidad local.',
        'lines' => [
            'Seguimiento a empleos creados, reconversión laboral y formalización de oficios en cada campaña.',
            'Acompañamiento en planes de negocio, gobernanza participativa y diversificación de ingresos.'
        ]
    ],
    [
        'code' => 'ODS 9',
        'title' => 'Industria, innovación e infraestructura',
        'color' => 'from-[#FF3A21] to-[#D32E1B]',
        'accent' => '#FF3A21',
        'border' => 'rgba(255, 58, 33, 0.25)',
        'badge_text' => 'text-white',
        'icon' => '<path d="M12 2l8 4.5v9L12 20l-8-4.5v-9L12 2zm0 2.236L6 7v6.764l6 3.236 6-3.236V7l-6-2.764z" />',
        'description' => 'Desarrollamos infraestructura digital con IA responsable para optimizar verificación, análisis de impacto y accesibilidad.',
        'lines' => [
            'Arquitectura modular con protocolos de ciberseguridad y monitoreo continuo de riesgos.',
            'Analítica descriptiva y predictiva que detecta patrones de recaudación y propone mejoras operativas.'
        ]
    ],
    [
        'code' => 'ODS 10',
        'title' => 'Reducción de las desigualdades',
        'color' => 'from-[#DD1367] to-[#B20F52]',
        'accent' => '#DD1367',
        'border' => 'rgba(221, 19, 103, 0.25)',
        'badge_text' => 'text-white',
        'icon' => '<path d="M12 2a7 7 0 00-7 7v2a7 7 0 1014 0V9a7 7 0 00-7-7zm0 2a5 5 0 015 5v2a5 5 0 11-10 0V9a5 5 0 015-5zm-1 6h2v5h-2zm0 6h2v2h-2z" />',
        'description' => 'Diseñamos procesos inclusivos que priorizan a colectivos subrepresentados y resguardan su seguridad digital.',
        'lines' => [
            'Mecanismos de admisión con cuotas para proyectos liderados por mujeres, territorios rurales y comunidades indígenas.',
            'Políticas de accesibilidad, moderación y soporte multicanal para remover barreras tecnológicas y de comunicación.'
        ]
    ],
    [
        'code' => 'ODS 16',
        'title' => 'Paz, justicia e instituciones sólidas',
        'color' => 'from-[#00689D] to-[#00527D]',
        'accent' => '#00689D',
        'border' => 'rgba(0, 104, 157, 0.25)',
        'badge_text' => 'text-white',
        'icon' => '<path d="M4 4h16v2H4zm0 4h8v2H4zm0 4h16v2H4zm0 4h8v2H4z" />',
        'description' => 'Elevamos la confianza pública con trazabilidad completa, auditorías independientes y protocolos de respuesta rápida.',
        'lines' => [
            'Registro de auditoría end-to-end, gestión de denuncias y cumplimiento normativo documentado.',
            'Tableros de transparencia con estados de campaña, acuerdos de uso de fondos y evidencia respaldatoria.'
        ]
    ],
];

$principles = [
    [
        'title' => 'Gobernanza basada en datos',
        'text' => 'Definimos indicadores de impacto, riesgo y cumplimiento antes de publicar cada campaña, con tableros que habilitan decisiones oportunas para gestores y aliados.'
    ],
    [
        'title' => 'IA supervisada de extremo a extremo',
        'text' => 'Las herramientas de asistencia funcionan con modelos auditables, límites de uso y revisión humana obligatoria para prevenir sesgos y proteger a las comunidades.'
    ],
    [
        'title' => 'Transparencia y cumplimiento',
        'text' => 'Sistemas de verificación, auditorías trimestrales y protocolos antifraude garantizan trazabilidad financiera y alineación con normativas locales e internacionales.'
    ],
    [
        'title' => 'Diseño centrado en comunidades',
        'text' => 'Investigación etnográfica, mesas de co-creación y soporte multicanal aseguran que la plataforma responda a las necesidades reales de donantes y beneficiarios.'
    ],
];

ob_start();
?>
<div class="flex flex-col gap-0">
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-marino-50 via-white to-neutral-50 text-neutral-900">
        <div class="absolute inset-0 opacity-30" aria-hidden="true">
            <div class="absolute -top-24 left-8 h-48 w-48 rounded-full bg-copihue-500 blur-3xl"></div>
            <div class="absolute bottom-0 right-16 h-64 w-64 rounded-full bg-emerald-400 blur-3xl"></div>
        </div>
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-24">
            <?php echo render_breadcrumb([
                ['name' => 'Inicio', 'href' => Router::url('/')],
                ['name' => 'Visión y ODS', 'href' => Router::url('vision')],
            ]); ?>
            <div class="mt-10 grid gap-10 lg:grid-cols-[1.1fr_0.9fr] items-center">
                <div class="space-y-6">
                    <span class="inline-flex items-center gap-2 rounded-full bg-marino-100 px-4 py-2 text-sm font-semibold uppercase tracking-wide text-marino-800">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Propósito y sostenibilidad
                    </span>
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold leading-tight text-marino-900">Construimos una plataforma de impacto alineada con la Agenda 2030</h1>
                    <p class="text-base sm:text-lg text-neutral-700 max-w-2xl">
                        Lucatón vincula tecnología, gobernanza y alianzas sociales para canalizar recursos hacia territorios vulnerables. Cada módulo combina IA responsable, trazabilidad financiera y acompañamiento estratégico para contribuir a las metas de desarrollo sostenible.
                    </p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
                            <p class="text-sm font-semibold uppercase tracking-wide text-marino-600">ODS priorizados</p>
                            <p class="mt-1 text-2xl font-semibold text-marino-900">1, 4, 8, 9, 10, 16</p>
                        </div>
                        <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
                            <p class="text-sm font-semibold uppercase tracking-wide text-marino-600">Principios rectores</p>
                            <p class="mt-1 text-2xl font-semibold text-marino-900">Transparencia + IA ética</p>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <div class="absolute inset-0 rounded-3xl bg-gradient-to-tr from-marino-100 via-white to-transparent blur-3xl"></div>
                    <div class="relative rounded-3xl border border-neutral-200 bg-white p-8 shadow-strong">
                        <h2 class="text-xl font-semibold text-marino-900">Cómo integramos la Agenda 2030</h2>
                        <ul class="mt-4 space-y-3 text-sm text-neutral-700">
                            <li class="flex items-start gap-2">
                                <span class="mt-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-marino-100 text-xs font-semibold text-marino-700">1</span>
                                <span>Diagnosticamos brechas territoriales con datos socioeconómicos, entrevistas longitudinales y análisis de impacto potencial.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="mt-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-marino-100 text-xs font-semibold text-marino-700">2</span>
                                <span>Co-diseñamos funcionalidades con pilotos y validadoras ciudadanas, asegurando viabilidad técnica, legal y operativa.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="mt-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-marino-100 text-xs font-semibold text-marino-700">3</span>
                                <span>Medimos recaudación, permanencia de donantes y niveles de transparencia para iterar el roadmap y escalar mejores prácticas.</span>
                            </li>
                        </ul>
                        <div class="mt-6 rounded-2xl border border-neutral-200 bg-neutral-50 p-4 text-xs text-neutral-600">
                            <p><strong class="text-neutral-900">Gobernanza:</strong> El programa opera con políticas ESG, auditorías independientes y principios de la Agenda 2030 para asegurar legitimidad y continuidad.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white py-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <header class="space-y-3 text-center">
                <span class="inline-flex items-center gap-2 rounded-full bg-marino-100 px-4 py-1 text-xs font-semibold uppercase tracking-wide text-marino-700">ODS prioritarios</span>
                <h2 class="text-3xl font-bold text-marino-900">Seis focos que guían nuestras decisiones de producto</h2>
                <p class="text-sm text-neutral-600 max-w-3xl mx-auto">Cada bloque del roadmap se evalúa según el aporte directo o indirecto a estos objetivos. Ajustamos el backlog con datos, feedback de usuarios y métricas de impacto.</p>
            </header>

            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($sdg_focus as $sdg): ?>
                    <article class="rounded-3xl border bg-neutral-50 p-8 shadow-soft transition duration-300 hover:-translate-y-1 hover:shadow-strong" style="border-color: <?= htmlspecialchars($sdg['border']) ?>;">
                        <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r <?= htmlspecialchars($sdg['color']) ?> px-3 py-1 text-xs font-semibold <?= htmlspecialchars($sdg['badge_text']) ?> shadow-md">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><?= $sdg['icon'] ?></svg>
                            <?= htmlspecialchars($sdg['code']) ?>
                        </div>
                        <h3 class="mt-4 text-xl font-semibold" style="color: <?= htmlspecialchars($sdg['accent']) ?>;"><?= htmlspecialchars($sdg['title']) ?></h3>
                        <p class="mt-3 text-sm text-neutral-600 leading-relaxed"><?= htmlspecialchars($sdg['description']) ?></p>
                        <ul class="mt-4 space-y-2 text-sm text-neutral-700">
                            <?php foreach ($sdg['lines'] as $line): ?>
                                <li class="flex items-start gap-2">
                                    <span class="mt-1 inline-flex h-2 w-2 flex-shrink-0 rounded-full" style="background-color: <?= htmlspecialchars($sdg['accent']) ?>;"></span>
                                    <span><?= htmlspecialchars($line) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="bg-neutral-50 pt-20 pb-32 mt-16 mb-16">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <header class="max-w-3xl space-y-4">
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-4 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-700">Principios operativos</span>
                <h2 class="text-3xl font-bold text-marino-900">Cómo evaluamos transparencia, seguridad y adopción tecnológica</h2>
                <p class="text-sm text-neutral-600">Estos principios bajan a políticas concretas: checklists de QA, límites de IA, auditorías, comunicación y soporte a creadores/as.</p>
            </header>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <?php foreach ($principles as $principle): ?>
                    <article class="rounded-3xl border border-neutral-200 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-md">
                        <h3 class="text-lg font-semibold text-marino-900"><?= htmlspecialchars($principle['title']) ?></h3>
                        <p class="mt-3 text-sm text-neutral-600 leading-relaxed"><?= htmlspecialchars($principle['text']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="rounded-3xl border border-marino-100 bg-white px-6 py-7 sm:px-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between shadow-soft">
                <div class="space-y-2">
                    <h3 class="text-xl font-semibold text-marino-900">Seguimiento y documentación constante</h3>
                    <p class="text-sm text-neutral-600 max-w-2xl">Cada iteración se respalda con actas de gobernanza, roadmaps trimestrales y reportes de cumplimiento, asegurando trazabilidad y mejora continua.</p>
                </div>
                <a href="<?= Router::url('noticias') ?>" class="inline-flex items-center gap-2 rounded-full bg-[#D32E1B] px-5 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-[#B52817] hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#F28C7D] focus:ring-offset-white">
                    Ver avances en noticias
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>
    </section>
</div>
<?php
$content = ob_get_clean();
include VIEWS_PATH . '/layouts/main.php';
