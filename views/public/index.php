<?php
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/cards.php';
require_once __DIR__ . '/../components/alerts.php';

$featured_campaigns = $featured_campaigns ?? [];
$impact_stats = $impact_stats ?? [];
$top_categories = $top_categories ?? [];
$recent_campaigns = $recent_campaigns ?? [];
$success_stories = $success_stories ?? [];
$urgent_campaigns = $urgent_campaigns ?? [];
$donor_samples = $donor_samples ?? [];
$testimonial_showcase = $testimonial_showcase ?? [
    'highlight' => null,
    'secondary' => [],
    'count' => 0,
    'average' => null,
    'distribution' => [],
];
$can_submit_creator_feedback = $can_submit_creator_feedback ?? false;

$page_title = 'Lucatón — Dona a causas, cambia vidas';
$page_description = 'Crowdfunding social chileno con transparencia, IA accesible y comunidad para causas verificadas.';

$impact_defaults = [
    'supporters' => 0,
    'raised' => 0,
    'active_campaigns' => 0,
    'hours' => 0,
    'communities' => 0,
    'registered_users' => 0,
];

$impact_stats = array_merge($impact_defaults, $impact_stats);
$registered_users = (int)max($impact_stats['registered_users'], $impact_stats['supporters']);

$raw_supporters = (int)$impact_stats['supporters'];
$marketing_active_donors = $raw_supporters > 0 ? $raw_supporters : (int)round(max($registered_users * 0.32, 240));

if ($registered_users === 0 && !empty($donor_samples)) {
    $registered_users = count($donor_samples) * 8;
}

if ($registered_users > 0) {
    $marketing_active_donors = min($marketing_active_donors, $registered_users);
}

$total_raised = (float)$impact_stats['raised'];
$active_campaigns_count = (int)$impact_stats['active_campaigns'];
$impact_updated_at = (new DateTime('now'))->format('H:i');

if (!function_exists('lucaton_format_currency')) {
    function lucaton_format_currency(float $value): string {
        return '$' . number_format((int)round($value), 0, ',', '.');
    }
}

if (!function_exists('lucaton_format_short_number')) {
    function lucaton_format_short_number(int $number): string {
        if ($number >= 1000000) {
            $millions = $number / 1000000;
            $formatted = $millions >= 10 ? floor($millions) : round($millions, 1);
            return str_replace('.', ',', (string)$formatted) . 'M';
        }

        if ($number >= 1000) {
            $thousands = $number / 1000;
            $formatted = $thousands >= 10 ? floor($thousands) : round($thousands, 1);
            return str_replace('.', ',', (string)$formatted) . 'K';
        }

        return number_format($number, 0, ',', '.');
    }
}

if (!function_exists('lucaton_avatar_initials')) {
    function lucaton_avatar_initials(string $name): string {
        $parts = preg_split('/\s+/', trim($name));
        $initials = '';

        foreach ($parts as $part) {
            if ($part !== '') {
                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
            }
            if (mb_strlen($initials) >= 2) {
                break;
            }
        }

        return $initials !== '' ? $initials : 'L';
    }
}

if (!function_exists('lucaton_campaign_gap')) {
    function lucaton_campaign_gap(array $campaign): float {
        $goal = (float)($campaign['goal_amount'] ?? 0);
        $raised = (float)($campaign['raised_amount'] ?? 0);

        return max(0, $goal - $raised);
    }
}

if (!function_exists('lucaton_campaign_url')) {
    function lucaton_campaign_url(array $campaign): string {
        $publicPath = $campaign['public_path'] ?? CampaignPresenter::buildPublicPath($campaign);
        if ($publicPath !== null) {
            return Router::url($publicPath);
        }

        $identifier = $campaign['slug'] ?? ($campaign['id'] ?? null);
        if ($identifier === null) {
            return Router::url('campanas');
        }

        return Router::url('campana/' . $identifier);
    }
}

$hero_badges = [
    'Campañas verificadas',
    'IA con revisión humana',
    'Reportes en tiempo real',
];

$hero_donor_names = array_slice($donor_samples, 0, 3);
if (count($hero_donor_names) < 3) {
    $fallback_donors = ['Ana P.', 'Carlos R.', 'Isidora M.'];
    $hero_donor_names = array_pad($hero_donor_names, 3, array_shift($fallback_donors));
}

$hero_avatar_backgrounds = [
    'bg-gradient-to-br from-copihue-400 to-copihue-600',
    'bg-gradient-to-br from-pacifico-400 to-pacifico-600',
    'bg-gradient-to-br from-marino-400 to-marino-600',
];

$urgent_candidates = array_filter($urgent_campaigns, static function (array $campaign): bool {
    $priority = (float)($campaign['priority'] ?? 0);
    $days_left = $campaign['days_left'] ?? null;

    return !empty($campaign['urgent']) || $priority >= 0.7 || ($days_left !== null && $days_left <= 5);
});

if (!empty($urgent_candidates)) {
    usort($urgent_candidates, static function (array $a, array $b): int {
        $priority_diff = ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0);
        if ($priority_diff !== 0) {
            return $priority_diff;
        }

        $days_diff = ($a['days_left'] ?? PHP_INT_MAX) <=> ($b['days_left'] ?? PHP_INT_MAX);
        if ($days_diff !== 0) {
            return $days_diff;
        }

        $a_end = isset($a['end_date']) ? strtotime((string)$a['end_date']) : null;
        $b_end = isset($b['end_date']) ? strtotime((string)$b['end_date']) : null;

        if ($a_end !== null && $b_end !== null && $a_end !== $b_end) {
            return $a_end <=> $b_end;
        }

        return ($a['id'] ?? PHP_INT_MAX) <=> ($b['id'] ?? PHP_INT_MAX);
    });
}

$highlight_campaign = $urgent_candidates[0] ?? null;

if ($highlight_campaign === null) {
    $candidate_pool = !empty($featured_campaigns) ? $featured_campaigns : $recent_campaigns;

    if (!empty($candidate_pool)) {
        $preferred_pool = array_values(array_filter($candidate_pool, static function (array $candidate): bool {
            return empty($candidate['time_over']);
        }));
        if (!empty($preferred_pool)) {
            $candidate_pool = $preferred_pool;
        }

        usort($candidate_pool, static function (array $a, array $b): int {
            $gap_diff = lucaton_campaign_gap($b) <=> lucaton_campaign_gap($a);
            if ($gap_diff !== 0) {
                return $gap_diff;
            }

            $a_end = isset($a['end_date']) ? strtotime((string)$a['end_date']) : null;
            $b_end = isset($b['end_date']) ? strtotime((string)$b['end_date']) : null;

            if ($a_end !== null && $b_end !== null && $a_end !== $b_end) {
                return $a_end <=> $b_end;
            }

            return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
        });

        $highlight_campaign = $candidate_pool[0];
    }
}

$highlight_is_urgent = false;
if ($highlight_campaign !== null) {
    $days_left = $highlight_campaign['days_left'] ?? null;
    $priority = (float)($highlight_campaign['priority'] ?? 0);
    $highlight_time_over = !empty($highlight_campaign['time_over']);
    $highlight_is_urgent = !$highlight_time_over && (!empty($highlight_campaign['urgent']) || ($days_left !== null && $days_left <= 5) || $priority >= 0.7);
}

$active_campaigns = array_values(array_filter($recent_campaigns, static function (array $campaign): bool {
    $status = $campaign['status'] ?? 'draft';
    return in_array($status, ['published', 'active'], true) && empty($campaign['time_over']);
}));
$active_campaigns = array_slice($active_campaigns, 0, 6);
$active_state = !empty($active_campaigns) ? 'ready' : 'empty';
$badge_weekly_donors = (int)round(max(1300, $marketing_active_donors * 0.34));
if ($registered_users > 0) {
    $badge_weekly_donors = min($badge_weekly_donors, max(1, $registered_users));
}

$testimonial_highlight = $testimonial_showcase['highlight'] ?? null;
$testimonial_secondary = $testimonial_showcase['secondary'] ?? [];
$testimonial_cards = [];
if ($testimonial_highlight) {
    $testimonial_cards[] = $testimonial_highlight;
}
foreach ($testimonial_secondary as $card) {
    if (count($testimonial_cards) >= 3) {
        break;
    }
    $testimonial_cards[] = $card;
}

if (empty($testimonial_cards) && !empty($testimonial_showcase['count'])) {
    $testimonial_cards = array_slice($testimonial_secondary, 0, 3);
}

$meta_description = $meta_description ?? $page_description;
$current_page = $current_page ?? 'home';
$body_classes = $body_classes ?? 'h-full bg-white text-neutral-900 font-sans antialiased';

ob_start();
?>
<div class="flex flex-col gap-0">
        <!-- HERO JUVENIL -->
        <section class="relative isolate overflow-hidden bg-gradient-to-br from-marino-950 via-marino-900 to-marino-800 text-white">
            <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
                <div class="absolute bottom-10 right-10 h-24 w-24 rounded-full bg-white/10 blur-3xl animate-float-soft"></div>
                <div class="absolute top-16 left-1/3 h-16 w-16 rounded-full bg-copihue-500/40 blur-2xl animate-float-soft-delay"></div>
            </div>
            <div class="absolute inset-0 bg-gradient-to-r from-marino-950/90 via-marino-900/75 to-transparent"></div>
            <div class="absolute inset-y-0 right-0 w-full md:w-1/2 lg:w-[48%]">
                <img src="<?= asset_url('images/campaigns/emprendimiento-valpo.svg') ?>" alt="Personas celebrando una campaña financiada" class="h-full w-full object-cover opacity-85" loading="lazy">
                <div class="absolute inset-0 bg-gradient-to-l from-marino-900/85 via-marino-900/40 to-transparent"></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 lg:py-28">
                <div class="grid gap-14 lg:grid-cols-[1.05fr_0.95fr] items-center">
                    <div class="space-y-10 fade-up" data-animate="fade-up">
                        <div class="inline-flex items-center gap-2 rounded-full bg-white text-marino-900 px-4 py-2 text-sm font-medium tracking-wide shadow-sm">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Comunidad joven, transparente y solidaria
                        </div>
                        <div class="space-y-6">
                            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black leading-tight text-pretty">
                                Tu ayuda cambia historias 💙
                            </h1>
                            <p class="text-lg text-neutral-900 max-w-xl">
                                Crea tu campaña o dona a una causa real. En Lucatón, todo es claro y humano: combinamos IA con revisión humana para cuidar cada peso.
                            </p>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            <a href="<?= Router::url('campanas'); ?>" class="btn btn-primary btn-tilt hover-rise px-8 py-4 text-base" aria-label="Ir a las campañas">
                                Dona ahora
                            </a>
                            <a href="<?= Router::url('campana/crear'); ?>" class="btn hover-rise px-8 py-4 text-base bg-white text-marino-900 border border-white/90 focus:ring-white/60 shadow-soft" aria-label="Crear una nueva campaña">
                                Crea tu campaña
                            </a>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <?php foreach ($hero_badges as $badge): ?>
                                <span class="inline-flex items-center gap-2 rounded-full bg-white text-marino-900 border border-white/80 px-3 py-1 text-sm font-medium shadow-sm hover:bg-white/95">
                                    <?= htmlspecialchars($badge); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="lg:pl-10">
                        <article class="glass-hero rounded-3xl p-8 text-white shadow-strong fade-up" data-animate="fade-up" data-animate-delay="160">
                            <header class="flex items-center justify-between">
                                <h2 class="text-lg font-semibold">Transparencia en vivo</h2>
                                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white/80">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"></path></svg>
                                    Actualizado <?= $impact_updated_at; ?> 🔄
                                </span>
                            </header>
                            <div class="mt-6 space-y-5">
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-white/60">Recaudado este año</p>
                                    <p class="mt-1 text-3xl font-bold text-white">
                                        <span data-counter-value="<?= (int)round($total_raised); ?>" data-counter-format="currency" data-counter-duration="1200"><?= lucaton_format_currency($total_raised); ?></span>
                                    </p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-xs uppercase tracking-wide text-white/60">Donantes activos</p>
                                        <p class="mt-1 text-xl font-semibold">
                                            +<span data-counter-value="<?= $marketing_active_donors; ?>" data-counter-format="number" data-counter-duration="1100"><?= number_format($marketing_active_donors, 0, ',', '.'); ?></span>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-wide text-white/60">Campañas verificadas</p>
                                        <p class="mt-1 text-xl font-semibold">
                                            <span data-counter-value="<?= $active_campaigns_count; ?>" data-counter-format="number" data-counter-duration="1100"><?= number_format($active_campaigns_count, 0, ',', '.'); ?></span>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 pt-4 border-t border-white/10">
                                    <?php foreach ($hero_donor_names as $index => $name): ?>
                                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-full text-sm font-semibold text-white shadow-md ring-2 ring-white/40 <?= $hero_avatar_backgrounds[$index % count($hero_avatar_backgrounds)]; ?>">
                                            <?= htmlspecialchars(lucaton_avatar_initials($name)); ?>
                                        </span>
                                    <?php endforeach; ?>
                                    <span class="text-sm text-white/80">+<?= number_format(max(0, $marketing_active_donors - 3), 0, ',', '.'); ?> más conectando esperanza</span>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECCIÓN DE IMPACTO RÁPIDO -->
        <section class="relative z-10 -mt-16 pb-6">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="rounded-3xl bg-white shadow-strong border border-neutral-100/80 px-6 py-8 sm:px-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between fade-up" data-animate="fade-up" data-animate-delay="120">
                    <div class="space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-widest text-marino-500">Impacto real</p>
                        <p class="text-4xl font-black text-marino-900">
                            <span data-counter-value="<?= (int)round($total_raised); ?>" data-counter-format="currency" data-counter-duration="1200"><?= lucaton_format_currency($total_raised); ?></span>
                        </p>
                        <p class="text-sm text-neutral-600">Recaudado en lo que va del año gracias a la comunidad Lucatón.</p>
                        <div class="flex items-center gap-3 text-sm text-neutral-500">
                            <span class="inline-flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                                <strong class="font-semibold text-neutral-700">+<?= number_format($marketing_active_donors, 0, ',', '.'); ?></strong> donantes activos
                            </span>
                            <span class="hidden sm:inline" aria-hidden="true">•</span>
                            <span class="relative group inline-flex items-center gap-2">
                                <span class="text-xs bg-neutral-100 text-neutral-600 px-2 py-1 rounded-full">Campañas verificadas</span>
                                <span class="sr-only">Última actualización <?= $impact_updated_at; ?> hrs</span>
                            </span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center lg:flex-col lg:items-end lg:text-right">
                        <div class="flex items-center -space-x-4">
                            <?php $avatar_slice = array_slice($donor_samples, 0, 3); ?>
                            <?php foreach ($avatar_slice as $name): ?>
                                <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-copihue-400 to-copihue-600 text-white text-sm font-semibold shadow-soft ring-4 ring-white">
                                    <?= htmlspecialchars(lucaton_avatar_initials($name)); ?>
                                </span>
                            <?php endforeach; ?>
                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-neutral-100 text-neutral-600 text-xs font-semibold shadow-soft ring-4 ring-white">+<?= count($donor_samples) >= 3 ? number_format(count($donor_samples) - 3, 0, ',', '.') : 'Más'; ?></span>
                        </div>
                        <p class="text-xs text-neutral-500 flex items-center gap-2">
                            <svg class="h-4 w-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"></path></svg>
                            Actualizado <?= $impact_updated_at; ?> hrs
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CAMPAÑA URGENTE -->
        <section class="bg-neutral-50 py-24">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                <header class="space-y-3 text-center fade-up" data-animate="fade-up">
                    <p class="inline-flex items-center gap-2 rounded-full bg-copihue-100 px-3 py-1 text-xs font-semibold text-copihue-700 uppercase tracking-wide">Necesitamos actuar</p>
                    <h2 class="text-3xl font-bold text-marino-900">Esta causa nos necesita hoy</h2>
                    <p class="text-sm text-neutral-600 max-w-2xl mx-auto">Cada segundo cuenta. Súmate y acompaña a quienes están viviendo momentos críticos.</p>
                </header>

                <?php if ($highlight_campaign): ?>
                    <?php
                        $campaign_progress = isset($highlight_campaign['progress']) ? min(100, (float)$highlight_campaign['progress']) : 0;
                        $campaign_goal = (float)($highlight_campaign['goal_amount'] ?? 0);
                        $campaign_raised = (float)($highlight_campaign['raised_amount'] ?? 0);
                        $campaign_days_left = $highlight_campaign['days_left'] ?? null;
                        $campaign_time_label = $highlight_campaign['time_remaining_label'] ?? null;
                        $campaign_time_over = !empty($highlight_campaign['time_over']);
                        $campaign_currency = strtoupper($highlight_campaign['currency'] ?? 'CLP');
                        $campaign_goal_label = ($campaign_currency === 'CLP' ? '$' : $campaign_currency . ' ') . number_format($campaign_goal, 0, ',', '.');
                        $campaign_raised_label = ($campaign_currency === 'CLP' ? '$' : $campaign_currency . ' ') . number_format($campaign_raised, 0, ',', '.');
                        $campaign_progress_display = number_format($campaign_progress, 1, ',', '.');
                        $campaign_image = $highlight_campaign['image_url'] ?? APP_URL . '/public/assets/images/campaigns/escuela-rural.svg';
                    ?>
                    <article class="grid gap-0 lg:grid-cols-[1.2fr_1fr] overflow-hidden rounded-3xl border border-neutral-200 bg-white shadow-soft hover:-translate-y-1 hover:shadow-strong transition-transform duration-500 fade-up" data-animate="fade-up" data-animate-delay="120">
                        <div class="relative h-72 lg:h-full">
                            <img src="<?= htmlspecialchars($campaign_image); ?>" alt="<?= htmlspecialchars($highlight_campaign['title']); ?>" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
                            <div class="absolute top-4 left-4 flex flex-wrap items-center gap-2">
                                <?php if ($highlight_is_urgent): ?>
                                    <span class="inline-flex items-center gap-2 rounded-full bg-copihue-600 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white animate-heartbeat-urgent">
                                        <span class="h-2 w-2 rounded-full bg-white"></span>
                                        Urgente
                                    </span>
                                <?php endif; ?>
                                <span class="inline-flex items-center gap-2 rounded-full bg-black/60 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">
                                    <?= htmlspecialchars($highlight_campaign['category_name'] ?? 'Causa solidaria'); ?>
                                </span>
                            </div>
                        </div>
                        <div class="p-8 lg:p-10 space-y-6 flex flex-col justify-between">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between text-xs uppercase tracking-wide text-neutral-500">
                                    <span>
                                        <?php if ($campaign_time_over): ?>
                                            Campaña finalizada
                                        <?php elseif (!empty($campaign_time_label)): ?>
                                            <?= htmlspecialchars($campaign_time_label); ?>
                                        <?php elseif ($campaign_days_left !== null): ?>
                                            <?= max(0, (int)$campaign_days_left); ?> días restantes
                                        <?php else: ?>
                                            <?= htmlspecialchars(CampaignPresenter::statusMeta($highlight_campaign['status'] ?? 'draft')['label']); ?>
                                        <?php endif; ?>
                                    </span>
                                    <span><?= $campaign_raised_label; ?> de <?= $campaign_goal_label; ?></span>
                                </div>
                                <h3 class="text-2xl font-semibold text-marino-900 leading-snug">
                                    <?= htmlspecialchars($highlight_campaign['title']); ?>
                                </h3>
                                <p class="text-sm text-neutral-600 leading-relaxed">Cada aporte nos acerca a cumplir esta meta. <?= htmlspecialchars($highlight_campaign['summary'] ?? $highlight_campaign['story']); ?></p>
                                <div class="space-y-2">
                                    <div class="progress" aria-hidden="true">
                                        <div class="progress-fill" style="width: <?= $campaign_progress; ?>%"></div>
                                    </div>
                                    <div class="flex items-center justify-between text-sm text-neutral-600">
                                        <span><?= $campaign_progress_display; ?>% completado</span>
                                        <span><?= number_format((float)($highlight_campaign['donor_count'] ?? 0), 0, ',', '.'); ?> donantes</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <p class="text-sm text-neutral-600">Cada segundo cuenta. Súmate.</p>
                                <a href="<?= htmlspecialchars(lucaton_campaign_url($highlight_campaign)); ?>" class="btn btn-primary bg-gradient-to-r from-copihue-600 to-copihue-500 hover:from-copihue-500 hover:to-copihue-600 px-6 py-3 text-sm font-semibold shadow-lg hover-rise" aria-label="Ver campaña urgente">
                                    Ver campaña
                                </a>
                            </div>
                        </div>
                    </article>
                <?php else: ?>
                    <div class="rounded-3xl border border-dashed border-neutral-300 bg-white/60 px-6 py-12 text-center shadow-soft fade-up" data-animate="fade-up">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-copihue-100 text-copihue-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        </div>
                        <h3 class="mt-6 text-xl font-semibold text-marino-900">Tu campaña puede ser la próxima historia urgente</h3>
                        <p class="mt-2 text-sm text-neutral-600 max-w-2xl mx-auto">¿Tienes una emergencia? Súbela a Lucatón, validamos la información y priorizamos su difusión.</p>
                        <div class="mt-6">
                            <a href="<?= Router::url('campana/crear'); ?>" class="btn btn-outline hover-rise" aria-label="Crear campaña urgente">Crear campaña</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- CAMPAÑAS EN TENDENCIA -->
        <section class="py-24 bg-white" id="campanas-tendencia" data-state="<?= htmlspecialchars($active_state); ?>">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between fade-up" data-animate="fade-up">
                    <div class="space-y-3">
                        <span class="inline-flex items-center gap-2 rounded-full bg-marino-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-marino-700">Destacadas</span>
                        <h2 class="text-3xl font-bold text-marino-900">Campañas que están moviendo corazones 💖</h2>
                        <p class="text-sm text-neutral-600">Historias reales que hoy están recibiendo apoyo. Únete al movimiento o comparte con tus redes.</p>
                    </div>
                    <div class="flex flex-col items-start gap-3 text-sm text-neutral-600 md:items-end">
                        <div class="inline-flex items-center gap-2 rounded-md bg-copihue-50 px-3 py-1 text-copihue-700 font-semibold shadow-sm">
                            🔥 +<?= lucaton_format_short_number($badge_weekly_donors); ?> donantes esta semana
                        </div>
                    </div>
                </div>

                <div class="<?= $active_state === 'loading' ? '' : 'hidden'; ?>" data-state-view="loading">
                    <p class="text-center text-sm text-neutral-500 py-12">Cargando campañas…</p>
                </div>

                <div class="rounded-3xl border border-neutral-200 bg-white p-8 text-center shadow-soft space-y-4 <?= $active_state === 'empty' ? '' : 'hidden'; ?>" data-state-view="empty">
                    <h3 class="text-xl font-semibold text-neutral-800">Aún no hay campañas activas</h3>
                    <p class="text-sm text-neutral-600">Activa la primera campaña del día y combina IA + comunidad para financiar tu meta solidaria.</p>
                    <div class="flex flex-col sm:flex-row sm:justify-center gap-3">
                        <a href="<?= Router::url('campana/crear'); ?>" class="btn btn-primary" aria-label="Crear la primera campaña">Sé la primera campaña</a>
                        <a href="<?= Router::url('ayuda'); ?>" class="btn btn-outline" aria-label="Cómo funciona Lucatón">Cómo funciona Lucatón</a>
                    </div>
                </div>

                <div class="rounded-3xl border border-danger-200/70 bg-danger-50/60 p-6 text-danger-800 space-y-4 <?= $active_state === 'error' ? '' : 'hidden'; ?>" data-state-view="error">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <div class="space-y-1">
                            <p class="font-semibold">Ups, no pudimos cargar las campañas</p>
                            <p class="text-sm">Revisa tu conexión o inténtalo nuevamente. El equipo ya registró este evento para mejorar la estabilidad.</p>
                        </div>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" data-retry-campaigns aria-label="Reintentar cargar campañas">Reintentar</button>
                    </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3" data-state-view="ready" <?= $active_state === 'ready' ? '' : 'class="hidden"'; ?>>
                    <?php foreach ($active_campaigns as $index => $campaign): ?>
                        <?php
                            $campaign_progress = isset($campaign['progress']) ? min(100, (float)$campaign['progress']) : 0;
                            $campaign_goal = (float)($campaign['goal_amount'] ?? 0);
                            $campaign_raised = (float)($campaign['raised_amount'] ?? 0);
                            $campaign_days_left = $campaign['days_left'] ?? null;
                            $campaign_currency = strtoupper($campaign['currency'] ?? 'CLP');
                            $campaign_goal_label = ($campaign_currency === 'CLP' ? '$' : $campaign_currency . ' ') . number_format($campaign_goal, 0, ',', '.');
                            $campaign_raised_label = ($campaign_currency === 'CLP' ? '$' : $campaign_currency . ' ') . number_format($campaign_raised, 0, ',', '.');
                            $campaign_progress_display = number_format($campaign_progress, 1, ',', '.');
                            $animate_delay = $index * 80;
                            $campaign_goal_reached = !empty($campaign['goal_reached']) || ($campaign_goal > 0 && $campaign_progress >= 100);
                            $campaign_time_over = !empty($campaign['time_over']);
                            $campaign_status = $campaign['status'] ?? 'draft';
                            $campaign_time_label = $campaign['time_remaining_label'] ?? null;
                        ?>
                        <article class="group relative overflow-hidden rounded-3xl border border-neutral-200 bg-white shadow-soft transition duration-500 hover:-translate-y-2 hover:shadow-strong fade-up" data-animate="fade-up" data-animate-delay="<?= $animate_delay; ?>">
                            <div class="relative h-48 overflow-hidden">
                                <img src="<?= htmlspecialchars($campaign['image_url'] ?? APP_URL . '/public/assets/images/campaigns/escuela-rural.svg'); ?>" alt="<?= htmlspecialchars($campaign['title']); ?>" class="h-full w-full object-cover transition duration-700 group-hover:scale-105" loading="lazy">
                                <div class="absolute top-4 left-4 inline-flex items-center gap-2 rounded-full bg-black/60 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">
                                    <?= htmlspecialchars($campaign['category_name'] ?? 'Campaña'); ?>
                                </div>
                                <?php if (($campaign['ai_assisted'] ?? false)): ?>
                                    <div class="absolute top-4 right-4 inline-flex items-center gap-2 rounded-full bg-white/85 px-3 py-1 text-xs font-semibold text-marino-700">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 11V7a4 4 0 118 0v4m-4 4v4" /></svg>
                                        IA asistiendo
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="space-y-4 p-6">
                                <div class="space-y-2">
                                    <?php if ($campaign_goal_reached || $campaign_time_over || $campaign_status === 'completed'): ?>
                                        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                                            <?php if ($campaign_goal_reached): ?>
                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 text-emerald-700">
                                                    Meta alcanzada
                                                </span>
                                            <?php elseif ($campaign_time_over || $campaign_status === 'completed'): ?>
                                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 text-amber-700">
                                                    Meta no alcanzada
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <h3 class="text-lg font-semibold text-marino-900 leading-tight line-clamp-2">
                                        <?= htmlspecialchars($campaign['title']); ?>
                                    </h3>
                                    <p class="text-sm text-neutral-600 line-clamp-3">
                                        <?= htmlspecialchars($campaign['summary'] ?? 'Conoce la historia completa y súmate a esta meta comunitaria.'); ?>
                                    </p>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between text-sm text-neutral-600">
                                        <span><?= $campaign_raised_label; ?> recaudados</span>
                                        <span><?= $campaign_progress_display; ?>%</span>
                                    </div>
                                    <div class="progress" aria-hidden="true">
                                        <div class="progress-fill" style="width: <?= $campaign_progress; ?>%"></div>
                                    </div>
                                    <div class="flex flex-wrap items-center justify-between text-sm text-neutral-500">
                                        <span><?= number_format((float)($campaign['donor_count'] ?? 0), 0, ',', '.'); ?> donantes</span>
                                        <?php if ($campaign_time_over): ?>
                                            <span>Campaña finalizada</span>
                                        <?php elseif (!empty($campaign_time_label)): ?>
                                            <span><?= htmlspecialchars($campaign_time_label); ?></span>
                                        <?php elseif ($campaign_days_left !== null): ?>
                                            <span><?= (int)$campaign_days_left; ?> días restantes</span>
                                        <?php else: ?>
                                            <span><?= htmlspecialchars(CampaignPresenter::statusMeta($campaign['status'] ?? 'draft')['label']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <?php
                                        $share_target = $campaign['slug'] ?? ($campaign['id'] ?? '');
                                    $share_payload = [
                                        'slug' => $share_target,
                                        'title' => $campaign['title'] ?? 'Campaña Lucatón',
                                        'url' => lucaton_campaign_url($campaign)
                                    ];
                                    $share_attr = htmlspecialchars(json_encode($share_payload, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <a href="<?= htmlspecialchars(lucaton_campaign_url($campaign)); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-copihue-600 transition hover:text-copihue-700" aria-label="Ver campaña <?= htmlspecialchars($campaign['title']); ?>">
                                        Ver campaña
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                    </a>
                                    <button type="button" class="btn-ghost p-2 rounded-full" onclick="shareCampaign(this, <?= $share_attr; ?>)" aria-label="Compartir campaña <?= htmlspecialchars($campaign['title']); ?>">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="flex justify-center">
                    <a href="<?= Router::url('campanas'); ?>" class="btn btn-outline hover-rise px-6 py-3 text-sm font-semibold" aria-label="Ver todas las campañas">Ver todas las campañas</a>
                </div>
            </div>
        </section>

        <!-- TESTIMONIOS -->
        <section class="bg-neutral-900 text-white py-24">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                <header class="space-y-2 text-center fade-up" data-animate="fade-up">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide">Historias reales</span>
                    <h2 class="text-3xl font-bold text-white">Lo que dice nuestra comunidad 💬</h2>
                    <p class="text-sm text-white/70 max-w-2xl mx-auto">Creadores y donantes nos cuentan cómo Lucatón los acompañó con tecnología transparente y apoyo cercano.</p>
                </header>

                <?php if (!empty($testimonial_cards)): ?>
                    <div class="relative" data-testimonial-slider>
                        <button type="button" class="absolute -left-4 top-1/2 z-10 hidden -translate-y-1/2 rounded-full border border-white/20 bg-white/5 p-3 text-white/80 transition hover:bg-white/15 md:inline-flex" data-testimonial-prev aria-label="Anterior">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                        </button>
                        <button type="button" class="absolute -right-4 top-1/2 z-10 hidden -translate-y-1/2 rounded-full border border-white/20 bg-white/5 p-3 text-white/80 transition hover:bg-white/15 md:inline-flex" data-testimonial-next aria-label="Siguiente">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </button>
                        <div class="grid gap-6 md:grid-cols-3" data-testimonial-track>
                            <?php foreach ($testimonial_cards as $card): ?>
                                <article class="rounded-3xl border border-white/15 bg-white/10 p-8 backdrop-blur transition duration-500 hover:-translate-y-1 hover:border-white/25" data-testimonial-card>
                                    <div class="flex items-center gap-4">
                                        <?php if (!empty($card['avatar'])): ?>
                                            <img src="<?= htmlspecialchars($card['avatar']); ?>" alt="<?= htmlspecialchars($card['name']); ?>" class="h-12 w-12 rounded-full object-cover border border-white/40" loading="lazy">
                                        <?php else: ?>
                                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-white/15 text-base font-semibold text-white/85">
                                                <?= htmlspecialchars(lucaton_avatar_initials($card['name'] ?? 'Amigo')); ?>
                                            </span>
                                        <?php endif; ?>
                                        <div class="text-sm text-white/70">
                                            <p class="font-semibold text-white"><?= htmlspecialchars($card['name'] ?? 'Miembro de Lucatón'); ?></p>
                                            <p><?= htmlspecialchars($card['role'] ?? 'Creador verificado'); ?></p>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex items-center gap-2" aria-label="<?= htmlspecialchars($card['rating_display'] ?? '5,0'); ?> de 5 estrellas">
                                        <?php for ($star = 1; $star <= 5; $star++): ?>
                                            <?php $filled = $card['rating'] >= $star - 0.2; ?>
                                            <svg class="h-4 w-4 <?= $filled ? 'text-amber-300' : 'text-white/30'; ?>" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.176 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292c.3-.921-.755-1.688-1.54-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                        <?php endfor; ?>
                                        <span class="text-xs text-white/70"><?= htmlspecialchars($card['rating_display'] ?? '5,0'); ?></span>
                                    </div>
                                    <p class="mt-4 text-sm leading-relaxed text-white/85">“<?= htmlspecialchars($card['quote'] ?? 'Tu historia puede inspirar a miles. Súmate a Lucatón.'); ?>”</p>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-8 text-sm text-white/70 text-center backdrop-blur">
                        <p>Cuando terminemos la primera ronda de campañas publicaremos aquí sus testimonios destacados. ¿Quieres ser la primera persona en dejar su review?</p>
                        <?php if ($can_submit_creator_feedback): ?>
                            <button type="button" class="btn btn-outline mt-4" data-feedback-open>Compartir mi experiencia</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- CTA FINAL CREADORES -->
        <section class="relative isolate overflow-hidden bg-gradient-to-br from-marino-900 via-marino-800 to-copihue-900 text-white py-24">
            <div class="absolute inset-0 opacity-40" aria-hidden="true">
                <div class="absolute -top-20 -left-20 h-48 w-48 rounded-full bg-copihue-500 blur-3xl"></div>
                <div class="absolute top-32 right-10 h-40 w-40 rounded-full bg-white/20 blur-2xl"></div>
                <div class="absolute bottom-0 left-1/2 h-48 w-48 -translate-x-1/2 rounded-full bg-pacifico-400/30 blur-3xl"></div>
            </div>
            <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6 fade-up" data-animate="fade-up">
                <h2 class="text-3xl font-bold text-white">¿Eres creador? Tu feedback guía nuestra hoja de ruta</h2>
                <p class="text-sm text-white/80 max-w-2xl mx-auto">Cuéntanos cómo fue tu experiencia con Lucatón, califica nuestras herramientas con IA y comparte sugerencias para el equipo.</p>
                <div class="flex flex-col items-center gap-4">
                    <?php if ($can_submit_creator_feedback): ?>
                        <button type="button" class="inline-flex items-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-semibold text-marino-900 shadow-soft transition hover:-translate-y-0.5" data-feedback-open>
                            Dejar mi opinión
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        </button>
                        <p class="text-xs text-white/60">Tiempo estimado: 2 minutos · Tus respuestas quedan registradas con auditoría</p>
                    <?php elseif (SessionHelper::isAuthenticated()): ?>
                        <p class="text-sm text-white/80">Para enviar feedback debes haber creado al menos una campaña pública. Activa tu primera campaña y habilita este espacio.</p>
                        <a href="<?= Router::url('campana/crear'); ?>" class="inline-flex items-center gap-2 rounded-full border border-white/20 px-5 py-3 text-sm font-semibold text-white/90 hover:bg-white/10">
                            Crea tu campaña
                        </a>
                    <?php else: ?>
                        <p class="text-sm text-white/80">Inicia sesión como creador para compartir tu experiencia con el equipo Lucatón.</p>
                        <a href="<?= Router::url('login'); ?>?redirect=<?= urlencode(Router::url('/')); ?>" class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-3 text-sm font-semibold text-marino-900">
                            Iniciar sesión
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>

    <div class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 px-4 py-6" role="dialog" aria-modal="true" aria-label="Feedback de creadores" data-feedback-modal>
        <div class="relative w-full max-w-2xl rounded-3xl bg-white shadow-strong">
            <button type="button" class="absolute right-4 top-4 inline-flex h-10 w-10 items-center justify-center rounded-full bg-neutral-100 text-neutral-600 hover:bg-neutral-200" data-feedback-close aria-label="Cerrar">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
            <div class="p-8 space-y-6" data-feedback-form-wrapper>
                <div class="space-y-2">
                    <h2 class="text-2xl font-bold text-marino-900">Comparte tu experiencia en Lucatón</h2>
                    <p class="text-sm text-neutral-600">Tu opinión nos ayuda a priorizar mejoras. Califica del 1 al 5 y comparte detalles (280 a 600 caracteres).</p>
                </div>
                <form method="POST" action="<?= Router::url('api/feedback'); ?>" class="space-y-6" data-feedback-form>
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME; ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()); ?>">
                    <input type="hidden" name="rating" value="" data-feedback-rating-input>
                    <div class="space-y-3">
                        <label class="text-sm font-semibold text-neutral-700">¿Cómo calificarías tu experiencia general?</label>
                        <div class="flex items-center gap-3" data-feedback-rating-group>
                            <?php for ($star = 1; $star <= 5; $star++): ?>
                                <button type="button" class="flex h-12 w-12 items-center justify-center rounded-full border border-neutral-200 text-neutral-400 transition hover:border-copihue-400 hover:text-copihue-500" data-feedback-rating="<?= $star; ?>" aria-label="<?= $star; ?> estrellas">
                                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.176 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.365-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                </button>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <label for="feedback-comment" class="text-sm font-semibold text-neutral-700">Describe qué funcionó y qué mejorarías</label>
                        <textarea id="feedback-comment" name="comment" rows="6" minlength="280" maxlength="600" required class="block w-full rounded-2xl border-neutral-200 focus:border-copihue-500 focus:ring-copihue-500" placeholder="Cuéntanos sobre tu campaña, las herramientas que usaste, la transparencia, etc." data-feedback-comment></textarea>
                        <div class="flex items-center justify-between text-xs text-neutral-500">
                            <span>Entre 280 y 600 caracteres</span>
                            <span data-feedback-counter>0 / 600</span>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-xs text-neutral-500">
                        Guardamos tu feedback con fecha, hora e ID de usuario para auditoría interna. Nunca compartiremos tu opinión con donantes sin tu consentimiento.
                    </div>
                    <div class="flex items-center justify-between">
                        <button type="button" class="text-sm font-semibold text-neutral-500 hover:text-neutral-700" data-feedback-close>Cancelar</button>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-copihue-500 px-5 py-3 text-sm font-semibold text-white shadow-soft transition hover:-translate-y-0.5 hover:bg-copihue-600">
                            Enviar feedback
                        </button>
                    </div>
                    <div class="hidden rounded-2xl border border-danger-200 bg-danger-50 px-4 py-3 text-sm text-danger-700" data-feedback-error></div>
                </form>
            </div>
            <div class="hidden p-8 text-center space-y-4" data-feedback-success>
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </div>
                <h3 class="text-2xl font-bold text-marino-900">¡Gracias por compartir!</h3>
                <p class="text-sm text-neutral-600">Recibimos tu feedback y lo registramos con auditoría. Seguiremos mejorando para potenciar tus próximas campañas.</p>
                <button type="button" class="inline-flex items-center gap-2 rounded-full bg-marino-900 px-5 py-3 text-sm font-semibold text-white" data-feedback-close>Cerrar</button>
                <div class="absolute inset-0 pointer-events-none" data-confetti aria-hidden="true"></div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../layouts/partials/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

            function handleFadeUp() {
                const elements = document.querySelectorAll('[data-animate]');
                if (!elements.length) {
                    return;
                }

                if (prefersReducedMotion.matches || !('IntersectionObserver' in window)) {
                    elements.forEach((el) => {
                        el.classList.add('is-visible');
                    });
                    return;
                }

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.2, rootMargin: '0px 0px -8% 0px' });

                elements.forEach((el) => {
                    const delay = parseInt(el.getAttribute('data-animate-delay') || '0', 10);
                    if (delay) {
                        el.style.transitionDelay = `${delay}ms`;
                    }
                    observer.observe(el);
                });
            }

            handleFadeUp();

            function animateCounters() {
                const counters = document.querySelectorAll('[data-counter-value]');
                if (!counters.length) {
                    return;
                }

                const formatterCache = new Map();

                const resolveFormatter = (type) => {
                    if (formatterCache.has(type)) {
                        return formatterCache.get(type);
                    }

                    let formatter;
                    if (type === 'currency') {
                        formatter = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 });
                    } else {
                        formatter = new Intl.NumberFormat('es-CL', { maximumFractionDigits: 0 });
                    }

                    formatterCache.set(type, formatter);
                    return formatter;
                };

                counters.forEach((element) => {
                    const target = parseFloat(element.dataset.counterValue || '0');
                    const duration = parseInt(element.dataset.counterDuration || '900', 10);
                    const format = element.dataset.counterFormat || 'number';
                    const prefix = element.dataset.counterPrefix || '';
                    const suffix = element.dataset.counterSuffix || '';
                    const startValue = parseFloat(element.dataset.counterStart || '0');

                    if (prefersReducedMotion.matches) {
                        const formatter = resolveFormatter(format);
                        element.textContent = `${prefix}${formatter.format(target)}${suffix}`;
                        return;
                    }

                    let startTimestamp = null;
                    const formatter = resolveFormatter(format);

                    const step = (timestamp) => {
                        if (startTimestamp === null) {
                            startTimestamp = timestamp;
                        }
                        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                        const eased = 1 - Math.pow(1 - progress, 3);
                        const value = startValue + (target - startValue) * eased;
                        element.textContent = `${prefix}${formatter.format(value)}${suffix}`;
                        if (progress < 1) {
                            window.requestAnimationFrame(step);
                        }
                    };

                    window.requestAnimationFrame(step);
                });
            }

            animateCounters();

            function initProgressBars() {
                document.querySelectorAll('.progress-fill').forEach((bar) => {
                    const width = bar.style.width;
                    bar.style.width = '0%';
                    requestAnimationFrame(() => {
                        bar.style.width = width;
                    });
                });
            }

            if (!prefersReducedMotion.matches) {
                initProgressBars();
            }

            function initTestimonialSlider() {
                const slider = document.querySelector('[data-testimonial-slider]');
                if (!slider) {
                    return;
                }

                const track = slider.querySelector('[data-testimonial-track]');
                const cards = Array.from(slider.querySelectorAll('[data-testimonial-card]'));
                const prev = slider.querySelector('[data-testimonial-prev]');
                const next = slider.querySelector('[data-testimonial-next]');

                if (!track || cards.length <= 1) {
                    if (prev) prev.style.display = 'none';
                    if (next) next.style.display = 'none';
                    return;
                }

                let current = 0;
                let timer = null;

                const show = (index) => {
                    current = (index + cards.length) % cards.length;
                    cards.forEach((card, idx) => {
                        card.style.opacity = idx === current ? '1' : '0';
                        card.style.transform = idx === current ? 'translateY(0)' : 'translateY(20px)';
                        card.style.position = idx === current ? 'relative' : 'absolute';
                        card.style.pointerEvents = idx === current ? 'auto' : 'none';
                    });
                };

                const start = () => {
                    if (prefersReducedMotion.matches) {
                        return;
                    }
                    stop();
                    timer = window.setInterval(() => {
                        show(current + 1);
                    }, 6000);
                };

                const stop = () => {
                    if (timer) {
                        window.clearInterval(timer);
                        timer = null;
                    }
                };

                slider.addEventListener('mouseenter', stop);
                slider.addEventListener('mouseleave', start);

                if (prev) {
                    prev.addEventListener('click', () => {
                        show(current - 1);
                        start();
                    });
                }

                if (next) {
                    next.addEventListener('click', () => {
                        show(current + 1);
                        start();
                    });
                }

                track.style.position = 'relative';
                cards.forEach((card) => {
                    card.style.position = 'absolute';
                    card.style.top = '0';
                    card.style.left = '0';
                    card.style.right = '0';
                    card.style.opacity = '0';
                    card.style.transition = prefersReducedMotion.matches ? 'none' : 'opacity 0.5s ease, transform 0.5s ease';
                });

                show(0);
                start();
            }

            initTestimonialSlider();

            function initFeedbackModal() {
                const modal = document.querySelector('[data-feedback-modal]');
                if (!modal) {
                    return;
                }

                const openers = document.querySelectorAll('[data-feedback-open]');
                const closers = modal.querySelectorAll('[data-feedback-close]');
                const formWrapper = modal.querySelector('[data-feedback-form-wrapper]');
                const successView = modal.querySelector('[data-feedback-success]');
                const form = modal.querySelector('[data-feedback-form]');
                const ratingInput = modal.querySelector('[data-feedback-rating-input]');
                const ratingButtons = modal.querySelectorAll('[data-feedback-rating]');
                const commentInput = modal.querySelector('[data-feedback-comment]');
                const counter = modal.querySelector('[data-feedback-counter]');
                const errorBanner = modal.querySelector('[data-feedback-error]');
                const confettiContainer = modal.querySelector('[data-confetti]');

                let lastFocusedElement = null;

                const updateCounter = () => {
                    if (!counter || !commentInput) {
                        return;
                    }
                    counter.textContent = `${commentInput.value.length} / ${commentInput.maxLength}`;
                };

                const updateActiveStars = (value) => {
                    ratingButtons.forEach((button) => {
                        const starValue = parseInt(button.dataset.feedbackRating || '0', 10);
                        if (starValue <= value) {
                            button.classList.add('bg-copihue-500', 'text-white', 'border-copihue-500');
                            button.classList.remove('text-neutral-400', 'border-neutral-200');
                        } else {
                            button.classList.remove('bg-copihue-500', 'text-white', 'border-copihue-500');
                            button.classList.add('text-neutral-400', 'border-neutral-200');
                        }
                    });
                };

                ratingButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        const value = parseInt(button.dataset.feedbackRating || '0', 10);
                        ratingInput.value = String(value);
                        updateActiveStars(value);
                    });
                });

                const closeModal = () => {
                    modal.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                    if (lastFocusedElement) {
                        lastFocusedElement.focus();
                    }
                };

                const trapFocus = (event) => {
                    if (event.key !== 'Tab') {
                        return;
                    }
                    const focusable = modal.querySelectorAll('a[href], button:not([disabled]), textarea, input, select, [tabindex="0"]');
                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                };

                const openModal = () => {
                    lastFocusedElement = document.activeElement;
                    modal.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                    modal.addEventListener('keydown', trapFocus);
                    const firstInput = modal.querySelector('button, textarea, input');
                    if (firstInput) {
                        firstInput.focus();
                    }
                };

                openers.forEach((button) => {
                    button.addEventListener('click', (event) => {
                        event.preventDefault();
                        openModal();
                    });
                });

                closers.forEach((button) => {
                    button.addEventListener('click', (event) => {
                        event.preventDefault();
                        closeModal();
                    });
                });

                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        closeModal();
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                        closeModal();
                    }
                });

                if (commentInput) {
                    commentInput.addEventListener('input', updateCounter);
                    updateCounter();
                }

                const launchConfetti = () => {
                    if (!confettiContainer || prefersReducedMotion.matches) {
                        return;
                    }

                    confettiContainer.innerHTML = '';
                    const colors = ['#f87171', '#60a5fa', '#fbbf24', '#34d399', '#f472b6'];

                    for (let i = 0; i < 18; i += 1) {
                        const piece = document.createElement('span');
                        piece.className = 'confetti-piece';
                        piece.style.setProperty('--left', `${(Math.random() * 160) - 80}px`);
                        piece.style.setProperty('--drift', `${(Math.random() * 60) - 30}px`);
                        piece.style.setProperty('--rotation', `${Math.random() * 360}deg`);
                        piece.style.setProperty('--duration', `${1.2 + Math.random() * 0.6}s`);
                        piece.style.backgroundColor = colors[i % colors.length];
                        confettiContainer.appendChild(piece);
                    }

                    window.setTimeout(() => {
                        confettiContainer.innerHTML = '';
                    }, 2000);
                };

                if (form) {
                    form.addEventListener('submit', (event) => {
                        event.preventDefault();
                        if (errorBanner) {
                            errorBanner.classList.add('hidden');
                            errorBanner.textContent = '';
                        }

                        const rating = parseInt(ratingInput.value || '0', 10);
                        const comment = (commentInput?.value || '').trim();

                        if (!rating || rating < 1) {
                            if (errorBanner) {
                                errorBanner.textContent = 'Selecciona una calificación entre 1 y 5 estrellas.';
                                errorBanner.classList.remove('hidden');
                            }
                            return;
                        }

                        if (comment.length < 280 || comment.length > 600) {
                            if (errorBanner) {
                                errorBanner.textContent = 'El comentario debe tener entre 280 y 600 caracteres.';
                                errorBanner.classList.remove('hidden');
                            }
                            return;
                        }

                        const submitButton = form.querySelector('button[type="submit"]');
                        if (submitButton) {
                            submitButton.disabled = true;
                            submitButton.classList.add('opacity-60');
                        }

                        const formData = new FormData(form);

                        fetch(form.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                        })
                            .then((response) => {
                                if (!response.ok) {
                                    throw new Error('No se pudo registrar tu feedback.');
                                }
                                return response.json().catch(() => ({}));
                            })
                            .then(() => {
                                if (formWrapper) {
                                    formWrapper.classList.add('hidden');
                                }
                                if (successView) {
                                    successView.classList.remove('hidden');
                                    const close = successView.querySelector('[data-feedback-close]');
                                    if (close) {
                                        close.focus();
                                    }
                                }
                                launchConfetti();
                            })
                            .catch((error) => {
                                if (errorBanner) {
                                    errorBanner.textContent = error.message || 'Ocurrió un error inesperado. Inténtalo nuevamente.';
                                    errorBanner.classList.remove('hidden');
                                }
                            })
                            .finally(() => {
                                if (submitButton) {
                                    submitButton.disabled = false;
                                    submitButton.classList.remove('opacity-60');
                                }
                            });
                    });
                }
            }

            initFeedbackModal();
        });
    </script>
<?php
$content = ob_get_clean();
include VIEWS_PATH . '/layouts/main.php';
?>
