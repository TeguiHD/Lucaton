<?php
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/cards.php';
require_once __DIR__ . '/../components/alerts.php';

$featured_campaigns = $featured_campaigns ?? [];
$impact_stats = $impact_stats ?? ['supporters' => 0, 'raised' => 0, 'active_campaigns' => 0, 'hours' => 0];
$top_categories = $top_categories ?? [];
$recent_campaigns = $recent_campaigns ?? [];
$success_stories = $success_stories ?? [];
$urgent_campaigns = $urgent_campaigns ?? [];

$highlight_campaign = $urgent_campaigns[0] ?? ($featured_campaigns[0] ?? ($recent_campaigns[0] ?? null));

$page_title = 'Lucatón — Dona a causas, cambia vidas';
$page_description = 'Una plataforma chilena de crowdfunding social que potencia campañas con transparencia, impacto y tecnología.';

$impact_metrics = [
    [
        'label' => 'Fondos recaudados',
        'value' => '$' . number_format((int)($impact_stats['raised'] ?? 0), 0, ',', '.'),
    ],
    [
        'label' => 'Horas de acompañamiento',
        'value' => number_format((int)($impact_stats['hours'] ?? 0), 0, ',', '.') . ' hrs',
    ],
    [
        'label' => 'Personas donando',
        'value' => number_format((int)($impact_stats['supporters'] ?? 0), 0, ',', '.'),
    ],
];

$why_cards = [
    [
        'title' => 'Transparencia radical',
        'description' => 'Panel público de donaciones, comprobantes obligatorios y auditorías semanales con IA y equipo humano.',
        'icon' => '<svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5 5V9a2 2 0 00-2-2h-2.586a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 0011.586 4H9a2 2 0 00-2 2v2m12 5a2 2 0 01-2 2H7a2 2 0 01-2-2m14 0v3a2 2 0 01-2 2H7a2 2 0 01-2-2v-3"/></svg>',
    ],
    [
        'title' => 'IA que potencia tu campaña',
        'description' => 'Redacta mensajes claros, genera imágenes éticas y recibe sugerencias personalizadas para tu público objetivo.',
        'icon' => '<svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2-1.343-2-3-2zm0 0V4m0 8c-2.21 0-4 1.343-4 3v1m4-4c2.21 0 4 1.343 4 3v1m0 0h-8m8 0h1a2 2 0 002-2v-2m-1-9h-3a1 1 0 01-.707-.293l-1.414-1.414A1 1 0 0013.586 3H10a2 2 0 00-2 2v1"/></svg>',
    ],
    [
        'title' => 'Comunidad activa',
        'description' => 'Más de mil campañas apoyadas por vecinos, municipios y organizaciones que confían en el proceso.',
        'icon' => '<svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4a4 4 0 11-4 4 4 4 0 014-4zm6 8a4 4 0 11-4 4 4 4 0 014-4zM6 12a4 4 0 11-4 4 4 4 0 014-4zm6 8a6 6 0 00-6-6h12a6 6 0 00-6 6z"/></svg>',
    ],
];

$ia_highlights = [
    'Editor inteligente que mejora luz, contraste y formatos para redes sociales',
    'Recomendaciones de difusión para llegar a nuevas audiencias en el momento ideal',
    'Panel de métricas en tiempo real con visitas, donaciones y porcentajes de conversión',
    'Alertas automáticas cuando la campaña necesita impulso o actualización',
];

$journey_steps = [
    [
        'label' => '01',
        'title' => 'Comparte tu historia',
        'description' => 'Sube evidencias, metas y contactos. Lucatón valida la información en menos de 24 horas.',
    ],
    [
        'label' => '02',
        'title' => 'Activa tu comunidad',
        'description' => 'Lanza tu campaña con herramientas de difusión, recordatorios automáticos y métricas en vivo.',
    ],
    [
        'label' => '03',
        'title' => 'Entrega con respaldo',
        'description' => 'Rinde cada peso con fotos, documentos y mensajes que quedan disponibles para tus donantes.',
    ],
];

$is_authenticated = isset($_SESSION['user_id']);
$ia_alert = !$is_authenticated && isset($_GET['ia-alert']);
$ia_cta_href = $is_authenticated
    ? Router::url('campana/crear')
    : Router::url('/') . '?ia-alert=1#ia-tools';
$ia_cta_label = $is_authenticated ? 'Explorar herramientas IA' : 'Inicia sesión para usar IA';
$ia_login_redirect = Router::url('login') . '?redirect=' . urlencode(Router::url('campana/crear'));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">

    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo Router::url('/'); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta property="og:image" content="<?= APP_URL ?>/public/assets/images/og-image.jpg">

    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/public/assets/images/favicon.svg">
    <link href="<?= APP_URL ?>/public/assets/css/app.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/public/assets/css/aliases.css" rel="stylesheet">
</head>
<body class="bg-white text-marino-900 font-sans">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md">
        Saltar al contenido principal
    </a>

    <?php include __DIR__ . '/../layouts/partials/header.php'; ?>

    <main id="main" class="flex flex-col gap-0">
        <!-- Hero interactivo -->
        <section class="relative isolate overflow-hidden bg-gradient-to-br from-marino-900 via-marino-800 to-marino-700 text-white">
            <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
                <div class="animate-float-soft absolute -top-24 -left-32 h-72 w-72 rounded-full bg-gradient-to-br from-copihue-500/70 to-copihue-300/40 blur-3xl"></div>
                <div class="animate-float-soft-delay absolute top-1/3 -right-24 h-64 w-64 rounded-full bg-gradient-to-br from-white/20 to-pacifico-400/30 blur-3xl"></div>
                <div class="absolute bottom-0 left-1/4 h-32 w-32 rounded-full bg-white/10 blur-2xl"></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-28">
                <div class="grid gap-16 lg:grid-cols-[1.05fr_0.95fr] items-center">
                    <div class="space-y-10 motion-safe:animate-fade-in">
                        <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-medium tracking-wide backdrop-blur">
                            <span class="h-2 w-2 rounded-full bg-copihue-300 animate-pulse"></span>
                            Crowdfunding chileno con IA y transparencia
                        </div>

                        <div class="space-y-6">
                            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black leading-tight text-white">
                                <span class="block">Dona a causas,</span>
                                <span class="block">cambia vidas</span>
                            </h1>
                            <p class="text-lg text-white/80 max-w-xl">
                                Lucatón activa redes solidarias con tecnología accesible. Crea campañas memorables, recibe apoyo seguro y comparte cada entrega con la comunidad.
                            </p>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            <a href="<?= Router::url('campana/crear'); ?>" class="inline-flex items-center justify-center gap-3 rounded-full bg-gradient-to-r from-copihue-500 to-rose-500 px-8 py-4 text-base font-semibold text-white shadow-strong transition duration-300 hover:-translate-y-1 hover:from-copihue-600 hover:to-rose-500 focus:outline-none focus:ring-4 focus:ring-copihue-500/40">
                                Crea tu campaña
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            </a>
                            <a href="<?= Router::url('campanas'); ?>" class="inline-flex items-center justify-center gap-3 rounded-full border border-white/30 px-8 py-4 text-base font-semibold text-white/90 transition duration-300 hover:-translate-y-1 hover:bg-white/10 focus:outline-none focus:ring-4 focus:ring-white/20">
                                Ver campañas activas
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </div>

                        <div class="flex flex-wrap items-center gap-6 text-sm text-white/75">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/10"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c.5304 0 1.0391-.2107 1.4142-.5858C13.7893 10.0391 14 9.5304 14 9s-.2107-1.0391-.5858-1.4142C13.0391 7.2107 12.5304 7 12 7s-1.0391.2107-1.4142.5858C10.2107 7.9609 10 8.4696 10 9s.2107 1.0391.5858 1.4142C10.9609 10.7893 11.4696 11 12 11zm0 0c-1.0607 0-2.0783.4214-2.8284 1.1716C8.4214 12.9217 8 13.9393 8 15m4-4c1.0607 0 2.0783.4214 2.8284 1.1716C15.5786 12.9217 16 13.9393 16 15m-4 4v0"/></svg></span>
                                Protección de datos certificada
                            </div>
                            <div class="hidden sm:flex items-center gap-2">
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/10"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg></span>
                                Acompañamiento humano + IA
                            </div>
                        </div>
                    </div>

                    <div class="relative">
                        <div class="absolute -inset-6 rounded-3xl bg-white/10 blur-2xl opacity-70"></div>
                        <?php if ($highlight_campaign): ?>
                            <?php
                                $highlight_image = !empty($highlight_campaign['image_url']) ? $highlight_campaign['image_url'] : APP_URL . '/public/assets/images/campaigns/escuela-rural.svg';
                                $highlight_progress = isset($highlight_campaign['progress']) ? min(100, (float)$highlight_campaign['progress']) : 0;
                                $highlight_amount = number_format((float)($highlight_campaign['raised_amount'] ?? 0), 0, ',', '.');
                                $highlight_goal = isset($highlight_campaign['goal_amount']) ? number_format((float)$highlight_campaign['goal_amount'], 0, ',', '.') : null;
                                $highlight_category = $highlight_campaign['category_name'] ?? ($highlight_campaign['category'] ?? 'Campaña destacada');
                                $highlight_summary = $highlight_campaign['summary'] ?? ($highlight_campaign['description'] ?? '');
                                $highlight_status_meta = CampaignPresenter::statusMeta($highlight_campaign['status'] ?? 'draft');
                                $highlight_status_label = $highlight_status_meta['label'];
                            ?>
                            <article class="relative overflow-hidden rounded-3xl border border-white/15 bg-white/10 backdrop-blur-xl shadow-strong transition duration-500 hover:-translate-y-2 motion-safe:animate-slide-up">
                                <div class="relative aspect-[4/3] overflow-hidden">
                                    <img src="<?= htmlspecialchars($highlight_image); ?>" alt="<?= htmlspecialchars($highlight_campaign['title']); ?>" class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/65 via-black/20 to-transparent"></div>
                                    <div class="absolute top-4 left-4 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-wide">
                                        <?= htmlspecialchars($highlight_category); ?>
                                    </div>
                                    <?php if (!empty($highlight_campaign['urgent'])): ?>
                                        <div class="absolute top-4 right-4 inline-flex items-center gap-2 rounded-full bg-copihue-500 px-3 py-1 text-xs font-semibold text-white uppercase">
                                            <span class="h-2 w-2 rounded-full bg-white animate-pulse"></span>
                                            Urgente
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="space-y-5 p-6 text-marino-900">
                                    <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-marino-500">
                                        <?php if (isset($highlight_campaign['days_left'])): ?>
                                            <span><?= max(0, (int)$highlight_campaign['days_left']); ?> días restantes</span>
                                        <?php else: ?>
                                            <span><?= htmlspecialchars($highlight_status_label); ?></span>
                                        <?php endif; ?>
                                        <span>$<?= $highlight_amount; ?></span>
                                    </div>
                                    <h3 class="text-xl font-semibold leading-tight text-marino-900">
                                        <?= htmlspecialchars($highlight_campaign['title']); ?>
                                    </h3>
                                    <p class="text-sm text-marino-600 line-clamp-3">
                                        <?= htmlspecialchars($highlight_summary); ?>
                                    </p>
                                    <div class="space-y-2">
                                        <div class="h-2 w-full rounded-full bg-neutral-200/80 overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-copihue-500 via-copihue-400 to-rose-300" style="width: <?= $highlight_progress; ?>%"></div>
                                        </div>
                                        <?php if ($highlight_goal): ?>
                                            <p class="text-xs text-marino-500">Meta: $<?= $highlight_goal; ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <a href="<?= Router::url('campana/' . ($highlight_campaign['slug'] ?? $highlight_campaign['id'])); ?>" class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-copihue-500 px-6 py-3 text-sm font-semibold text-white shadow-md transition hover:-translate-y-0.5 hover:bg-copihue-600 focus:outline-none focus:ring-2 focus:ring-copihue-300">
                                        Donar ahora
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </article>
                        <?php else: ?>
                            <div class="relative space-y-5 rounded-3xl border border-white/15 bg-white/10 p-10 text-center backdrop-blur-xl shadow-strong motion-safe:animate-slide-up">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-white/15">
                                    <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                </div>
                                <h3 class="text-2xl font-semibold text-white">Tu campaña puede estar aquí</h3>
                                <p class="text-sm text-white/75">Crea tu historia hoy y conviértete en la siguiente portada de Lucatón. Te guiamos con IA y acompañamiento humano.</p>
                                <a href="<?= Router::url('campana/crear'); ?>" class="inline-flex items-center justify-center gap-2 rounded-full bg-white/90 px-6 py-3 text-sm font-semibold text-marino-900 transition hover:bg-white">
                                    Crear campaña
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-16 grid gap-6 sm:grid-cols-3">
                    <?php foreach ($impact_metrics as $index => $metric): ?>
                        <div class="rounded-2xl border border-white/15 bg-white/10 px-6 py-5 backdrop-blur-sm shadow-soft transition duration-300 hover:-translate-y-1 hover:border-white/30 motion-safe:animate-fade-in <?php echo 'animation-delay-' . (($index + 1) * 200); ?>">
                            <p class="text-sm uppercase tracking-wide text-white/60">
                                <?= htmlspecialchars($metric['label']); ?>
                            </p>
                            <p class="mt-2 text-3xl font-semibold text-white">
                                <?= htmlspecialchars($metric['value']); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

                <div class="pointer-events-none absolute inset-x-0 bottom-0 overflow-hidden leading-[0]" aria-hidden="true">
                <svg viewBox="0 0 1440 160" preserveAspectRatio="none" class="block w-full h-28 text-white">
                    <path fill="currentColor" d="M0,120C180,150,360,140,540,110C720,80,900,30,1080,40C1260,50,1350,95,1440,120V160H0Z" />
                </svg>
                </div>
        </section>

        <!-- Campañas urgentes -->
        <section class="bg-neutral-50 py-24">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-3xl font-bold text-marino-900">Campañas urgentes</h2>
                        <p class="text-sm text-marino-600">Apoya causas que necesitan financiamiento inmediato para seguir adelante.</p>
                    </div>
                    <a href="<?= Router::url('campanas', ['filtro' => 'urgentes']); ?>" class="text-sm font-semibold text-copihue-600 hover:text-copihue-700">Ver todas →</a>
                </div>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <?php if (!empty($urgent_campaigns)): ?>
                        <?php foreach ($urgent_campaigns as $campaign): ?>
                            <?php
                                $campaign_image = !empty($campaign['image_url']) ? $campaign['image_url'] : APP_URL . '/public/assets/images/campaigns/tratamiento-sofia.svg';
                                $campaign_progress = isset($campaign['progress']) ? min(100, (float)$campaign['progress']) : 0;
                                $campaign_amount = number_format((float)($campaign['raised_amount'] ?? 0), 0, ',', '.');
                                $campaign_summary = $campaign['summary'] ?? ($campaign['description'] ?? '');
                                $campaign_status_meta = CampaignPresenter::statusMeta($campaign['status'] ?? 'draft');
                                $campaign_status_label = $campaign_status_meta['label'];
                            ?>
                            <article class="group flex flex-col overflow-hidden rounded-3xl border border-neutral-200 bg-white shadow-soft transition duration-500 hover:-translate-y-3 hover:shadow-strong">
                                <div class="relative h-44 overflow-hidden">
                                    <img src="<?= htmlspecialchars($campaign_image); ?>" alt="<?= htmlspecialchars($campaign['title']); ?>" class="h-full w-full object-cover transition duration-700 group-hover:scale-110">
                                    <span class="absolute bottom-3 left-3 inline-flex items-center gap-2 rounded-full bg-copihue-500 px-3 py-1 text-xs font-semibold text-white uppercase">
                                        <span class="h-2 w-2 rounded-full bg-white animate-pulse"></span>
                                        Urgente
                                    </span>
                                </div>
                                <div class="flex flex-1 flex-col gap-4 p-6">
                                    <div class="space-y-2">
                                        <h3 class="text-lg font-semibold text-marino-900 leading-tight line-clamp-2">
                                            <?= htmlspecialchars($campaign['title']); ?>
                                        </h3>
                                        <p class="text-sm text-marino-600 line-clamp-3">
                                            <?= htmlspecialchars($campaign_summary); ?>
                                        </p>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-marino-500">
                                            <?php if (isset($campaign['days_left'])): ?>
                                                <span><?= max(0, (int)$campaign['days_left']); ?> días</span>
                                            <?php else: ?>
                                                <span><?= htmlspecialchars($campaign_status_label); ?></span>
                                            <?php endif; ?>
                                            <span>$<?= $campaign_amount; ?></span>
                                        </div>
                                        <div class="h-2 w-full overflow-hidden rounded-full bg-neutral-200">
                                            <div class="h-full bg-gradient-to-r from-copihue-500 via-copihue-400 to-rose-300" style="width: <?= $campaign_progress; ?>%"></div>
                                        </div>
                                    </div>
                                    <a href="<?= Router::url('campana/' . ($campaign['slug'] ?? $campaign['id'])); ?>" class="mt-auto inline-flex items-center gap-2 text-sm font-semibold text-copihue-600 transition group-hover:translate-x-1">
                                        Apoyar campaña
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="sm:col-span-2 lg:col-span-3 xl:col-span-4">
                            <?php echo render_empty_state([
                                'title' => 'No hay campañas urgentes por ahora',
                                'description' => 'Explora causas activas o crea tu alerta para reaccionar a tiempo.',
                                'action_text' => 'Explorar campañas',
                                'action_href' => Router::url('campanas')
                            ]); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Historias destacadas -->
        <section class="bg-white py-24">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="space-y-3">
                        <span class="inline-flex items-center gap-2 rounded-full bg-pacifico-100 px-4 py-2 text-sm font-semibold text-pacifico-700">Historias que inspiran</span>
                        <h2 class="text-3xl font-bold text-marino-900">Resultados reales que cambiaron vidas</h2>
                        <p class="text-sm text-marino-600">Conoce campañas que alcanzaron su meta y cómo reportaron el impacto a la comunidad.</p>
                    </div>
                    <a href="<?= Router::url('campanas', ['status' => 'finalized']); ?>" class="text-sm font-semibold text-copihue-600 hover:text-copihue-700">Ver todas las historias →</a>
                </div>

                <div class="relative">
                    <div class="flex items-center justify-end gap-2 pb-4 text-xs text-marino-400">
                        Desliza para explorar
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12h16m-4-4l4 4-4 4"/></svg>
                    </div>
                    <div class="flex snap-x snap-mandatory gap-6 overflow-x-auto pb-2">
                        <?php if (!empty($success_stories)): ?>
                            <?php foreach ($success_stories as $story): ?>
                                <?php
                                    $currencyCode = strtoupper($story['currency'] ?? 'CLP');
                                    $raised = (float)($story['raised_amount'] ?? 0);
                                    $goal = (float)($story['goal_amount'] ?? 0);
                                    $progress = (float)($story['progress'] ?? ($goal > 0 ? ($raised / $goal) * 100 : 0));
                                    $formattedRaised = ($currencyCode === 'CLP' ? '$' : $currencyCode . ' ') . number_format($raised, 0, ',', '.');
                                    $formattedGoal = ($currencyCode === 'CLP' ? '$' : $currencyCode . ' ') . number_format($goal, 0, ',', '.');
                                    $progressLabel = number_format(min(100, $progress), 0);
                                ?>
                                <article class="min-w-[280px] snap-start rounded-3xl border border-neutral-100 bg-white shadow-soft transition duration-500 hover:-translate-y-2 hover:shadow-strong">
                                    <div class="relative h-48 overflow-hidden rounded-3xl rounded-b-none">
                                        <img src="<?= htmlspecialchars($story['image_url']); ?>" alt="<?= htmlspecialchars($story['title']); ?>" class="h-full w-full object-cover transition duration-700 hover:scale-105">
                                        <span class="absolute top-4 left-4 inline-flex items-center gap-2 rounded-full bg-success-500 px-3 py-1 text-xs font-semibold text-white">
                                            <?= $progressLabel ?>% de la meta
                                        </span>
                                    </div>
                                    <div class="space-y-3 p-6">
                                        <h3 class="text-lg font-semibold text-marino-900 leading-tight line-clamp-2">
                                            <?= htmlspecialchars($story['title']); ?>
                                        </h3>
                                        <p class="text-sm text-marino-600 line-clamp-3">
                                            <?= htmlspecialchars($story['excerpt']); ?>
                                        </p>
                                        <div class="text-sm font-semibold text-success-600">
                                            <?= $formattedRaised ?> recaudados / <?= $formattedGoal ?> objetivo
                                        </div>
                                        <a href="<?= Router::url('campana/' . $story['slug']); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-copihue-600 hover:text-copihue-700">
                                            Leer más
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="min-w-full">
                                <?php echo render_empty_state([
                                    'title' => 'Tu campaña puede ser la próxima historia inspiradora',
                                    'description' => 'Cuando completes tu meta podrás registrar entregas y agradecimientos para toda la comunidad Lucatón.',
                                    'action_text' => 'Crear campaña',
                                    'action_href' => Router::url('campana/crear')
                                ]); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Por qué Lucatón -->
        <section class="relative isolate -mt-px bg-white py-16 md:py-20">
            <div class="pointer-events-none absolute inset-x-0 top-0 -translate-y-[1px] overflow-hidden leading-[0] text-white/90" aria-hidden="true">
                <svg viewBox="0 0 1440 120" preserveAspectRatio="none" class="block h-16 w-full">
                    <path fill="currentColor" d="M0,64L48,53.3C96,43,192,21,288,21.3C384,21,480,43,576,48C672,53,768,43,864,58.7C960,75,1056,117,1152,117.3C1248,117,1344,75,1392,53.3L1440,32V0H1392C1344,0,1248,0,1152,0C1056,0,960,0,864,0C768,0,672,0,576,0C480,0,384,0,288,0C192,0,96,0,48,0H0Z" />
                </svg>
            </div>
            <div class="absolute inset-0 -z-10 bg-white"></div>

            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10 md:space-y-12">
                <div class="space-y-6 md:flex md:items-end md:justify-between md:space-y-0">
                    <div class="space-y-4 motion-safe:animate-slide-up">
                        <span class="inline-flex items-center gap-2 rounded-full bg-copihue-100 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-copihue-700">Por qué confiar en Lucatón</span>
                        <h2 class="text-3xl sm:text-4xl font-bold text-marino-900 text-pretty">Una experiencia mobile first para campañas que inspiran en 2025</h2>
                        <p class="text-sm text-marino-600 max-w-xl">Integramos transparencia, IA y comunidad en microinteracciones pensadas para pantallas pequeñas y grandes.</p>
                        <div class="flex flex-wrap gap-2 text-xs text-marino-500">
                            <span class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1 font-semibold shadow-sm"><span class="h-2 w-2 rounded-full bg-copihue-500"></span>Flujo inmediato</span>
                            <span class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1 font-semibold shadow-sm"><span class="h-2 w-2 rounded-full bg-marino-500"></span>Evidencia verificable</span>
                            <span class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1 font-semibold shadow-sm"><span class="h-2 w-2 rounded-full bg-pacifico-500"></span>IA accesible</span>
                        </div>
                    </div>
                    <a href="<?= Router::url('campanas'); ?>" class="inline-flex items-center gap-2 rounded-full border border-copihue-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-wide text-copihue-600 shadow-soft transition hover:-translate-y-1 hover:border-copihue-300 hover:text-copihue-700">
                        Ver casos auditados
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                <div class="md:hidden -mx-4 motion-safe:animate-fade-in">
                    <div class="flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-2 touch-manipulation">
                        <?php foreach ($why_cards as $card): ?>
                            <article class="group relative min-w-[260px] snap-start overflow-hidden rounded-3xl border border-neutral-200 bg-white px-6 py-8 shadow-soft transition duration-500 hover:-translate-y-2 hover:shadow-strong">
                                <div class="absolute inset-0 opacity-0 transition duration-500 group-hover:opacity-100" style="background: radial-gradient(circle at top right, rgba(220,38,38,0.12), transparent 55%);"></div>
                                <div class="relative space-y-4">
                                    <div class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-copihue-500/10 to-marino-500/10 text-copihue-600">
                                        <?= $card['icon']; ?>
                                    </div>
                                    <h3 class="text-lg font-semibold text-marino-900">
                                        <?= htmlspecialchars($card['title']); ?>
                                    </h3>
                                    <p class="text-sm text-marino-600 leading-relaxed">
                                        <?= htmlspecialchars($card['description']); ?>
                                    </p>
                                    <span class="inline-flex items-center gap-2 text-sm font-semibold text-copihue-600 transition group-hover:translate-x-2">Descubrir más<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="hidden md:grid md:grid-cols-3 md:gap-6">
                    <?php foreach ($why_cards as $card): ?>
                        <article class="group relative overflow-hidden rounded-3xl border border-neutral-200 bg-white p-8 shadow-soft transition duration-500 hover:-translate-y-2 hover:shadow-strong motion-safe:animate-fade-in">
                            <div class="absolute inset-0 opacity-0 transition duration-500 group-hover:opacity-100" style="background: radial-gradient(circle at top right, rgba(220,38,38,0.12), transparent 55%);"></div>
                            <div class="relative space-y-4">
                                <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-copihue-500/10 to-marino-500/10 text-copihue-600">
                                    <?= $card['icon']; ?>
                                </div>
                                <h3 class="text-xl font-semibold text-marino-900">
                                    <?= htmlspecialchars($card['title']); ?>
                                </h3>
                                <p class="text-sm text-marino-600 leading-relaxed">
                                    <?= htmlspecialchars($card['description']); ?>
                                </p>
                                <span class="inline-flex items-center gap-2 text-sm font-semibold text-copihue-600 transition group-hover:translate-x-2">Descubrir más<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Experiencia IA -->
        <section id="ia-tools" class="relative isolate overflow-hidden bg-gradient-to-br from-marino-900 via-marino-800 to-marino-900 text-white py-24">
            <div class="absolute inset-0 opacity-40" aria-hidden="true">
                <div class="animate-float-soft absolute left-1/3 top-8 h-48 w-48 rounded-full bg-white/10 blur-3xl"></div>
                <div class="animate-float-soft-delay absolute right-1/4 bottom-0 h-60 w-60 rounded-full bg-copihue-500/30 blur-3xl"></div>
            </div>
            <div class="pointer-events-none absolute inset-x-0 top-0 -translate-y-[1px] overflow-hidden leading-[0] text-white" aria-hidden="true">
                <svg viewBox="0 0 1440 120" preserveAspectRatio="none" class="block w-full h-24">
                    <path fill="currentColor" d="M0,32L60,58.7C120,85,240,139,360,149.3C480,160,600,128,720,106.7C840,85,960,75,1080,69.3C1200,64,1320,64,1380,64L1440,64V0H1380C1320,0,1200,0,1080,0C960,0,840,0,720,0C600,0,480,0,360,0C240,0,120,0,60,0H0Z" />
                </svg>
            </div>

            <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 grid gap-12 lg:grid-cols-[1.1fr_0.9fr]">
                <div class="relative motion-safe:animate-fade-in">
                    <div class="space-y-6 rounded-3xl border border-white/20 bg-white/10 p-6 sm:p-8 backdrop-blur">
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 text-sm font-semibold text-white/90">IA que amplifica tu alcance</span>
                        <h2 class="text-3xl sm:text-4xl font-bold leading-tight text-white">Llega a más personas con herramientas inteligentes</h2>
                        <p class="text-base text-white/80 max-w-xl">Lucatón combina edición visual, recomendaciones de difusión y analítica clara para que cada publicación sume nuevas miradas a tu causa.</p>
                        <ul class="grid gap-4 sm:grid-cols-2">
                            <?php foreach ($ia_highlights as $item): ?>
                                <li class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-white/85 shadow-inner">
                                    <span class="mt-0.5 inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-400 text-xs font-semibold text-emerald-900 shadow-sm">✓</span>
                                    <span class="leading-relaxed"><?= htmlspecialchars($item); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="flex flex-wrap gap-3 pt-4">
                            <span class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white/85">
                                <span class="h-2 w-2 rounded-full bg-copihue-300"></span>Optimización visual IA
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white/85">
                                <span class="h-2 w-2 rounded-full bg-pacifico-300"></span>Difusión inteligente
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white/85">
                                <span class="h-2 w-2 rounded-full bg-emerald-300"></span>Analítica en vivo
                            </span>
                        </div>
                        <?php if ($ia_alert): ?>
                            <div class="flex items-start gap-3 rounded-2xl border border-copihue-200/70 bg-white/90 px-4 py-3 text-sm text-copihue-800 shadow-soft">
                                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3l-8.47-14.14a2 2 0 00-3.42 0z"/></svg>
                                <div class="space-y-2">
                                    <p class="font-medium">Para probar nuestras herramientas de IA debes iniciar sesión.</p>
                                    <a href="<?= htmlspecialchars($ia_login_redirect); ?>" class="inline-flex items-center gap-2 rounded-full bg-copihue-500 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-copihue-600">
                                        Iniciar sesión ahora
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        <a href="<?= htmlspecialchars($ia_cta_href); ?>"
                           class="mt-4 inline-flex items-center gap-2 rounded-full bg-copihue-500 px-5 py-2 text-xs font-semibold uppercase tracking-wide text-white shadow-soft transition hover:-translate-y-0.5 hover:bg-copihue-600 focus:outline-none focus:ring-2 focus:ring-copihue-400/60">
                            <?= htmlspecialchars($ia_cta_label); ?>
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>

                <div class="relative motion-safe:animate-slide-up">
                    <div class="absolute inset-0 rounded-3xl bg-gradient-to-br from-white/10 to-white/5 blur-2xl"></div>
                    <div class="relative overflow-hidden rounded-3xl border border-white/15 bg-white/10 backdrop-blur-lg shadow-strong transition-transform duration-500 hover:-translate-y-2 hover:shadow-strong">
                        <div class="flex items-center justify-between px-6 py-4 text-sm text-white/80 border-b border-white/10">
                            <span class="font-semibold">Suite IA Lucatón</span>
                            <span class="inline-flex items-center gap-1">
                                <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                En vivo
                            </span>
                        </div>
                        <div class="space-y-6 px-6 py-8">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-white/50">Mejora sugerida</p>
                                <div class="mt-3 rounded-2xl bg-white/12 p-4 text-sm text-white/80 shadow-inner">
                                    “Corregimos la iluminación y centramos la escena para destacar el momento de entrega. Ideal para historias y publicaciones con CTA.”
                                </div>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-white/50">Vista previa optimizada</p>
                                <div class="relative mt-3 overflow-hidden rounded-2xl border border-white/10">
                                    <img src="<?= APP_URL ?>/public/assets/images/campaigns/tratamiento-sofia.svg" alt="Vista previa optimizada" class="h-48 w-full object-cover">
                                    <div class="absolute bottom-3 left-3 inline-flex items-center gap-2 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-marino-900">
                                        Versión IA lista para RRSS
                                    </div>
                                </div>
                            </div>
                            <div class="grid gap-4 rounded-2xl border border-white/10 bg-white/5 p-4 text-xs text-white/80">
                                <div class="flex items-center justify-between text-sm text-white">
                                    <span>Visitas últimas 24h</span>
                                    <span class="font-semibold">1.2K</span>
                                </div>
                                <div class="flex items-center justify-between text-sm text-white">
                                    <span>Donaciones nuevas</span>
                                    <span class="font-semibold">86</span>
                                </div>
                                <div class="pt-1 space-y-3">
                                    <div class="flex items-center justify-between text-[11px] text-white/70">
                                        <span>Objetivo de alcance semanal</span>
                                        <span>68%</span>
                                    </div>
                                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-white/10">
                                        <div class="h-full bg-gradient-to-r from-copihue-300 via-copihue-200 to-white/80" style="width: 68%"></div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-[11px] text-white/70">
                                            <span>Evolución de visitas (demo)</span>
                                            <span>+24%</span>
                                        </div>
                                        <div class="h-20 w-full rounded-xl bg-gradient-to-b from-white/15 to-white/5 p-2">
                                            <div class="h-full w-full rounded-lg bg-[linear-gradient(90deg,rgba(255,255,255,0.35)_0%,rgba(255,255,255,0)_100%)]">
                                                <svg viewBox="0 0 100 40" class="h-full w-full" preserveAspectRatio="none">
                                                    <defs>
                                                        <linearGradient id="iaArea" x1="0" y1="0" x2="0" y2="1">
                                                            <stop offset="0%" stop-color="rgba(252, 211, 77, 0.6)" />
                                                            <stop offset="100%" stop-color="rgba(252, 211, 77, 0)" />
                                                        </linearGradient>
                                                    </defs>
                                                    <path d="M0 30 L10 28 L20 25 L30 24 L40 18 L50 22 L60 14 L70 16 L80 10 L90 12 L100 6 L100 40 L0 40 Z" fill="url(#iaArea)" stroke="rgba(255,255,255,0.6)" stroke-width="1.5" stroke-linejoin="round" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pointer-events-none absolute inset-x-0 bottom-0 overflow-hidden leading-[0] text-white" aria-hidden="true">
                <svg viewBox="0 0 1440 160" preserveAspectRatio="none" class="block w-full h-28">
                    <path fill="currentColor" d="M0,120C220,150,440,150,660,120C880,90,1100,30,1260,45C1420,60,1440,90,1440,90V160H0Z" />
                </svg>
            </div>
        </section>

        <!-- Trayectoria guiada -->
        <section class="relative isolate bg-gradient-to-br from-white via-pacifico-50/40 to-white py-24">
            <div class="pointer-events-none absolute inset-x-0 top-0 -translate-y-[1px] overflow-hidden leading-[0] text-neutral-100" aria-hidden="true">
                <svg viewBox="0 0 1440 120" preserveAspectRatio="none" class="block w-full h-24">
                    <path fill="currentColor" d="M0,0L40,10.7C80,21,160,43,240,69.3C320,96,400,128,480,122.7C560,117,640,75,720,64C800,53,880,75,960,80C1040,85,1120,75,1200,64C1280,53,1360,32,1400,21.3L1440,11V0H1400C1360,0,1280,0,1200,0C1120,0,1040,0,960,0C880,0,800,0,720,0C640,0,560,0,480,0C400,0,320,0,240,0C160,0,80,0,40,0H0Z" />
                </svg>
            </div>

            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                <div class="text-center space-y-4 motion-safe:animate-fade-in">
                    <span class="inline-flex items-center gap-2 rounded-full bg-marino-100 px-4 py-2 text-sm font-semibold text-marino-700">Cada paso importa</span>
                    <h2 class="text-3xl sm:text-4xl font-bold text-marino-900">Tu recorrido con Lucatón en tres pasos claros</h2>
                    <p class="text-base text-marino-600 max-w-2xl mx-auto">Desde validar la causa hasta rendir cuentas, te acompañamos con recursos, IA y una comunidad atenta.</p>
                </div>

                <div class="relative grid gap-8 md:grid-cols-3">
                    <?php foreach ($journey_steps as $index => $step): ?>
                        <article class="relative rounded-3xl border border-neutral-200 bg-white p-8 shadow-soft transition duration-500 hover:-translate-y-2 hover:shadow-strong motion-safe:animate-slide-up <?php echo 'animation-delay-' . (($index + 1) * 200); ?>">
                            <?php if ($index < count($journey_steps) - 1): ?>
                                <div class="hidden md:block absolute top-20 right-0 h-px w-16 translate-x-1/2 bg-gradient-to-r from-copihue-200 to-transparent"></div>
                            <?php endif; ?>
                            <div class="flex items-center gap-4">
                                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-copihue-500 to-rose-400 text-lg font-bold text-white shadow-lg animate-pulse-glow">
                                    <?= htmlspecialchars($step['label']); ?>
                                </span>
                                <h3 class="text-xl font-semibold text-marino-900">
                                    <?= htmlspecialchars($step['title']); ?>
                                </h3>
                            </div>
                            <p class="mt-4 text-sm text-marino-600 leading-relaxed">
                                <?= htmlspecialchars($step['description']); ?>
                            </p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- CTA final -->
        <section class="relative isolate overflow-hidden bg-gradient-to-br from-marino-900 via-marino-800 to-copihue-900 text-white py-24">
            <div class="absolute inset-0 opacity-30" aria-hidden="true">
                <div class="absolute -top-20 left-12 h-48 w-48 rounded-full bg-copihue-500 blur-3xl"></div>
                <div class="absolute bottom-0 right-16 h-60 w-60 rounded-full bg-white/25 blur-3xl"></div>
                <div class="absolute top-40 right-1/3 h-36 w-36 rounded-full bg-pacifico-400/40 blur-2xl"></div>
            </div>
            <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid gap-12 lg:grid-cols-[1.25fr_0.75fr] items-start">
                    <div class="space-y-8">
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 text-sm font-semibold text-white/90">
                            <span class="h-2 w-2 rounded-full bg-emerald-300 animate-pulse"></span>
                            Acompañamiento integral en cada etapa
                        </span>
                        <div class="space-y-4">
                            <h2 class="text-3xl sm:text-4xl font-bold leading-tight">
                                Activa tu red solidaria con respaldo humano e inteligencia aplicada
                            </h2>
                            <p class="text-base text-white/80 max-w-2xl">
                                Lucatón combina especialistas sociales, periodistas y analistas de riesgo con un copiloto IA que potencia tu campaña. Transformamos tus evidencias en confianza y tus mensajes en acción.
                            </p>
                        </div>
                        <div class="grid gap-6 sm:grid-cols-2">
                            <article class="rounded-3xl border border-white/15 bg-white/10 p-6 backdrop-blur-lg shadow-soft">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-400/80 text-marino-900 font-semibold">H"></span>
                                    <div class="space-y-2">
                                        <h3 class="text-lg font-semibold text-white">Equipo humano dedicado</h3>
                                        <ul class="space-y-1.5 text-sm text-white/75">
                                            <li class="flex items-start gap-2"><span class="mt-1 h-1.5 w-1.5 rounded-full bg-white/70"></span>Verificación legal y financiera en menos de 24 horas.</li>
                                            <li class="flex items-start gap-2"><span class="mt-1 h-1.5 w-1.5 rounded-full bg-white/70"></span>Mentoría narrativa para contar tu causa con claridad.</li>
                                            <li class="flex items-start gap-2"><span class="mt-1 h-1.5 w-1.5 rounded-full bg-white/70"></span>Red de municipios y organizaciones aliadas.</li>
                                        </ul>
                                    </div>
                                </div>
                            </article>
                            <article class="rounded-3xl border border-white/15 bg-white/10 p-6 backdrop-blur-lg shadow-soft">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-400/80 text-marino-900 font-semibold">IA"></span>
                                    <div class="space-y-2">
                                        <h3 class="text-lg font-semibold text-white">Copiloto IA en tiempo real</h3>
                                        <ul class="space-y-1.5 text-sm text-white/75">
                                            <li class="flex items-start gap-2"><span class="mt-1 h-1.5 w-1.5 rounded-full bg-white/70"></span>Diagnóstico automático de mensajes y piezas visuales.</li>
                                            <li class="flex items-start gap-2"><span class="mt-1 h-1.5 w-1.5 rounded-full bg-white/70"></span>Alertas cuando baja la conversión o se acerca la meta.</li>
                                            <li class="flex items-start gap-2"><span class="mt-1 h-1.5 w-1.5 rounded-full bg-white/70"></span>Recomendaciones segmentadas por audiencia y canal.</li>
                                        </ul>
                                    </div>
                                </div>
                            </article>
                            <article class="sm:col-span-2 rounded-3xl border border-white/15 bg-white/10 p-6 backdrop-blur-lg shadow-soft">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-400/85 text-marino-900 font-semibold">✔</span>
                                    <div class="space-y-2">
                                        <h3 class="text-lg font-semibold text-white">Confianza y transparencia certificada</h3>
                                        <p class="text-sm text-white/80">Panel público de auditoría, registro de entregas, trazabilidad de gastos y reportes descargables para tus donantes.</p>
                                        <div class="grid gap-3 sm:grid-cols-3 text-xs text-white/70">
                                            <div class="rounded-2xl border border-white/10 bg-white/5 px-3 py-2">ISO/IEC 27001 en proceso</div>
                                            <div class="rounded-2xl border border-white/10 bg-white/5 px-3 py-2">Detección antifraude 24/7</div>
                                            <div class="rounded-2xl border border-white/10 bg-white/5 px-3 py-2">Reportes compartibles en un clic</div>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <?php echo render_button([
                            'text' => 'Iniciar mi campaña',
                            'href' => Router::url('campana/crear'),
                            'type' => 'primary',
                            'size' => 'lg',
                            'class' => 'bg-white text-marino-900 hover:bg-copihue-100 shadow-strong'
                        ]); ?>
                            <?php echo render_button([
                            'text' => 'Hablar con un especialista',
                            'href' => Router::url('faq'),
                            'type' => 'secondary',
                            'size' => 'lg',
                            'class' => 'border border-white/60 bg-transparent text-white hover:bg-white/10'
                        ]); ?>
                        </div>
                        <p class="text-xs uppercase tracking-[0.2em] text-white/60">Respuesta promedio del equipo: 9h 45m · Cobertura nacional · Soporte en español</p>
                    </div>
                    <div class="space-y-6">
                        <div class="rounded-3xl border border-white/10 bg-white/10 p-6 backdrop-blur-lg shadow-strong">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-white/70">Tu ruta con Lucatón</h3>
                            <ol class="mt-4 space-y-4">
                                <li class="flex gap-3">
                                    <span class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full bg-white text-marino-900 text-sm font-semibold">1</span>
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold">Verificamos tu campaña</p>
                                        <p class="text-xs text-white/70">Revisamos documentos, evidencias y definimos metas realistas.</p>
                                    </div>
                                </li>
                                <li class="flex gap-3">
                                    <span class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full bg-white/80 text-marino-900 text-sm font-semibold">2</span>
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold">Lanzamos con difusión precisa</p>
                                        <p class="text-xs text-white/70">Tareas automatizadas, contenidos listos y mentoría para tus voceros.</p>
                                    </div>
                                </li>
                                <li class="flex gap-3">
                                    <span class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full bg-white/60 text-marino-900 text-sm font-semibold">3</span>
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold">Rendimos cada aporte</p>
                                        <p class="text-xs text-white/70">Plantillas de transparencia, reportes autogenerados y publicación de entregas.</p>
                                    </div>
                                </li>
                            </ol>
                        </div>
                        <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-white/60">Satisfacción promedio</p>
                                    <p class="mt-1 text-3xl font-semibold text-white">4.8/5</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs uppercase tracking-wide text-white/60">Campañas con entrega</p>
                                    <p class="mt-1 text-3xl font-semibold text-white">92%</p>
                                </div>
                            </div>
                            <p class="mt-4 text-xs text-white/70">Medimos la experiencia de donantes y creadores cada semana para seguir mejorando.</p>
                        </div>
                        <div class="rounded-3xl border border-white/10 bg-white text-marino-900 p-6 shadow-strong">
                            <div class="flex flex-col gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-copihue-600">¿Tienes dudas?</p>
                                    <h3 class="text-lg font-semibold text-marino-900">Agendemos una sesión de evaluación gratuita</h3>
                                </div>
                                <p class="text-sm text-marino-700">Cuéntanos tu objetivo y te sugerimos la mejor ruta para financiarlo con respaldo humano + IA.</p>
                                <a href="mailto:<?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?>" class="inline-flex items-center gap-2 self-start rounded-full bg-marino-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-marino-800">
                                    <?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?>
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../layouts/partials/footer.php'; ?>
</body>
</html>
