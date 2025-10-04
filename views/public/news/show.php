<?php
require_once __DIR__ . '/../../components/navigation.php';

$page_title = $page_title ?? ($article['title'] ?? 'Noticia Lucatón');
$page_description = $page_description ?? ($article['summary'] ?? 'Actualizaciones y noticias de la comunidad Lucatón.');
$current_page = 'news';
$article = $article ?? [];
$related = $related ?? [];
$recent = $recent ?? [];
$share_links = $share_links ?? [];
$article_url = $article_url ?? Router::url('noticias');
$breadcrumbs = [
    ['name' => 'Inicio', 'href' => Router::url('/')],
    ['name' => 'Noticias', 'href' => Router::url('noticias')],
    ['name' => $article['title'] ?? 'Detalle noticia', 'href' => $article_url]
];

$cover_image = !empty($article['cover_image'])
    ? APP_URL . '/' . ltrim($article['cover_image'], '/')
    : APP_URL . '/public/assets/images/campaigns/escuela-rural.svg';

$published_at = $article['published_at'] ?? $article['created_at'] ?? null;
$published_date = null;
if ($published_at) {
    try {
        $published_date = (new DateTime($published_at))->format('d \de F \de Y');
    } catch (Exception $e) {
        $published_date = date('d \de F \de Y', strtotime($published_at));
    }
}

$author = trim(($article['first_name'] ?? '') . ' ' . ($article['last_name'] ?? ''));
if ($author === '') {
    $author = $article['username'] ?? 'Equipo Lucatón';
}

$category_name = $article['category_name'] ?? null;
$category_slug = $article['category_slug'] ?? null;
$gallery = $article['gallery'] ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_description) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($article_url) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($cover_image) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($cover_image) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/public/assets/images/favicon.svg">
    <link href="<?= APP_URL ?>/public/assets/css/app.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/public/assets/css/aliases.css" rel="stylesheet">
</head>
<body class="bg-gray-50 text-marino-900">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">Saltar al contenido principal</a>

    <?php include VIEWS_PATH . '/layouts/partials/header.php'; ?>

    <main id="main-content" class="pb-16">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
            <?php echo render_breadcrumb($breadcrumbs); ?>
        </div>
        <article>
            <section class="relative isolate overflow-hidden bg-gradient-to-br from-marino-900 via-marino-800 to-marino-700 text-white mt-6 rounded-b-[2.5rem]">
                <div class="absolute inset-0 opacity-30" aria-hidden="true">
                    <div class="absolute -top-32 right-20 h-64 w-64 rounded-full bg-copihue-500 blur-3xl"></div>
                    <div class="absolute bottom-10 left-10 h-48 w-48 rounded-full bg-white/20 blur-3xl"></div>
                </div>

                <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
                    <div class="space-y-6">
                        <div class="flex flex-wrap items-center gap-3 text-sm font-medium text-white/80">
                            <?php if ($category_name): ?>
                                <a href="<?= Router::url('noticias', ['categoria' => $category_slug]) ?>" class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-xs uppercase tracking-wide hover:bg-white/25 transition">
                                    <span class="h-2 w-2 rounded-full bg-white"></span>
                                    <?= htmlspecialchars($category_name) ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($published_date): ?>
                                <span class="inline-flex items-center gap-2 text-white/80">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <?= htmlspecialchars($published_date) ?>
                                </span>
                            <?php endif; ?>
                            <span class="inline-flex items-center gap-2 text-white/80">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A3 3 0 017 12h10a3 3 0 011.879 5.303L12 22.118l-6.879-4.314z" />
                                </svg>
                                <?= htmlspecialchars($author) ?>
                            </span>
                        </div>
                        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold leading-tight text-white max-w-4xl">
                            <?= htmlspecialchars($article['title'] ?? 'Noticia Lucatón') ?>
                        </h1>
                        <?php if (!empty($article['summary'])): ?>
                            <p class="text-base sm:text-lg text-white/85 max-w-3xl">
                                <?= htmlspecialchars($article['summary']) ?>
                            </p>
                        <?php endif; ?>
                        <div class="flex flex-wrap items-center gap-4" role="group" aria-label="Compartir noticia">
                            <?php
                            $share_items = [
                                'whatsapp' => ['label' => 'WhatsApp', 'icon' => '<path d="M17.472 14.382c-.297-.149-1.758-.867-2.029-.967-.273-.102-.472-.149-.671.149-.197.297-.768.966-.941 1.164-.173.199-.347.223-.644.075-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.372-.025-.521-.074-.149-.671-1.611-.919-2.206-.242-.579-.487-.5-.671-.51-.173-.009-.372-.011-.571-.011-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.078 4.487.709.306 1.262.489 1.693.626.711.226 1.356.194 1.868.118.569-.085 1.758-.719 2.006-1.413.248-.695.248-1.291.173-1.414-.074-.123-.272-.198-.57-.347z"/>'],
                                'facebook' => ['label' => 'Facebook', 'icon' => '<path d="M22.676 0H1.326C.593 0 0 .593 0 1.326v21.348C0 23.407.593 24 1.326 24h11.495v-9.294H9.691v-3.622h3.13V8.413c0-3.1 1.893-4.788 4.658-4.788 1.325 0 2.463.099 2.794.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12V24h6.116C23.407 24 24 23.407 24 22.674V1.326C24 .593 23.407 0 22.676 0z"/>'],
                                'instagram' => ['label' => 'Instagram', 'icon' => '<path d="M12 2.163c3.204 0 3.584.012 4.85.07 1.17.054 1.97.24 2.428.403a4.92 4.92 0 011.772 1.153 4.92 4.92 0 011.153 1.772c.163.458.349 1.258.403 2.428.058 1.266.07 1.646.07 4.85s-.012 3.584-.07 4.85c-.054 1.17-.24 1.97-.403 2.428a4.92 4.92 0 01-1.153 1.772 4.92 4.92 0 01-1.772 1.153c-.458.163-1.258.349-2.428.403-1.266.058-1.646.07-4.85.07s-3.584-.012-4.85-.07c-1.17-.054-1.97-.24-2.428-.403a4.92 4.92 0 01-1.772-1.153 4.92 4.92 0 01-1.153-1.772c-.163-.458-.349-1.258-.403-2.428C2.175 15.647 2.163 15.267 2.163 12s.012-3.584.07-4.85c.054-1.17.24-1.97.403-2.428a4.92 4.92 0 011.153-1.772A4.92 4.92 0 015.561 1.795c.458-.163 1.258-.349 2.428-.403C9.255 1.335 9.635 1.323 12 1.323zm0-1.323C8.356 0 7.944.013 6.675.072 5.407.131 4.473.346 3.68.659a6.243 6.243 0 00-2.26 1.48A6.243 6.243 0 00-.04 4.4C-.353 5.193-.568 6.127-.627 7.395-.686 8.664-.698 9.076-.698 12s.013 3.336.072 4.605c.059 1.268.274 2.202.587 2.995a6.243 6.243 0 001.48 2.26 6.243 6.243 0 002.26 1.48c.793.313 1.727.528 2.995.587 1.269.059 1.68.072 4.605.072s3.336-.013 4.605-.072c1.268-.059 2.202-.274 2.995-.587a6.243 6.243 0 002.26-1.48 6.243 6.243 0 001.48-2.26c.313-.793.528-1.727.587-2.995.059-1.269.072-1.68.072-4.605s-.013-3.336-.072-4.605c-.059-1.268-.274-2.202-.587-2.995a6.243 6.243 0 00-1.48-2.26A6.243 6.243 0 0020.32.659C19.527.346 18.593.131 17.325.072 16.056.013 15.644 0 12 0z"/>
                                    <path d="M12 5.838A6.162 6.162 0 005.838 12 6.162 6.162 0 0012 18.162 6.162 6.162 0 0018.162 12 6.162 6.162 0 0012 5.838zm0 10.162A4 4 0 118 12a4 4 0 014 4z"/>
                                    <circle cx="18.406" cy="5.594" r="1.44"/>'],
                                'x' => ['label' => 'X', 'icon' => '<path d="M20.273 0H24l-5.227 5.96L24 18h-7.547l-3.32-5.746L9.18 18H0l5.59-6.381L0 0h7.727l3.063 5.263L13.773 0h6.5zm-3.227 16.2h1.793l-8.483-14.4H8.562l8.484 14.4z"/>'],
                                'linkedin' => ['label' => 'LinkedIn', 'icon' => '<path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452z"/>'],
                                'email' => ['label' => 'Email', 'icon' => '<path d="M2 4a2 2 0 00-2 2v12a2 2 0 002 2h20a2 2 0 002-2V6a2 2 0 00-2-2H2zm0 2l10 7 10-7v-.001L12 13 2 6.001V6z"/>'],
                            ];
                            foreach ($share_items as $key => $item):
                                if (!isset($share_links[$key])) {
                                    continue;
                                }
                                ?>
                                <a href="<?= htmlspecialchars($share_links[$key]) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:-translate-y-0.5 hover:bg-white/25 focus:outline-none focus:ring-2 focus:ring-white/40">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <?= $item['icon'] ?>
                                    </svg>
                                    <?= $item['label'] ?>
                                </a>
                            <?php endforeach; ?>
                            <button type="button" data-copy-link="<?= htmlspecialchars($article_url) ?>" class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:-translate-y-0.5 hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/40">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8a2 2 0 002-2V6a2 2 0 00-2-2H8a2 2 0 00-2 2v8a2 2 0 002 2zm0 0v2a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                </svg>
                                Copiar enlace
                            </button>
                            <span class="hidden text-xs font-semibold text-white" data-copy-feedback>¡Enlace copiado!</span>
                        </div>
                    </div>
                </div>
                <div class="pointer-events-none absolute inset-x-0 bottom-0" aria-hidden="true">
                    <svg class="h-16 w-full text-gray-50" viewBox="0 0 1440 80" preserveAspectRatio="none">
                        <path fill="currentColor" d="M0,64L48,53.3C96,43,192,21,288,26.7C384,32,480,64,576,69.3C672,75,768,53,864,37.3C960,21,1056,11,1152,21.3C1248,32,1344,64,1392,80L1440,96L1440,160L1392,160C1344,160,1248,160,1152,160C1056,160,960,160,864,160C768,160,672,160,576,160C480,160,384,160,288,160C192,160,96,160,48,160L0,160Z" />
                    </svg>
                </div>
            </section>

            <section class="-mt-10 sm:-mt-14 relative z-10">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="space-y-10 sm:space-y-14">
                        <div class="overflow-hidden rounded-3xl bg-white shadow-xl ring-1 ring-gray-100">
                            <div class="relative aspect-[16/9] bg-gray-900">
                                <img src="<?= htmlspecialchars($cover_image) ?>" alt="<?= htmlspecialchars($article['title'] ?? 'Noticia Lucatón') ?>" class="h-full w-full object-cover">
                                <div class="absolute inset-0 bg-gradient-to-t from-marino-900/60 via-marino-900/10 to-transparent"></div>
                            </div>
                            <div class="px-6 py-8 sm:p-10">
                                <div class="space-y-6 text-base leading-7 text-marino-800" id="news-article-content">
                                    <?= $article['content'] ?? '<p>Pronto podrás leer la historia completa.</p>' ?>
                                </div>
                                <?php if (!empty($gallery)): ?>
                                    <div class="mt-10">
                                        <h2 class="text-lg font-semibold text-marino-900">Galería de momentos</h2>
                                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                            <?php foreach ($gallery as $item): 
                                                $imagePath = APP_URL . '/' . ltrim($item['image_path'], '/');
                                                $caption = $item['caption'] ?? '';
                                            ?>
                                                <figure class="group relative overflow-hidden rounded-2xl border border-gray-100 bg-gray-50 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                                                    <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($caption ?: ($article['title'] ?? 'Galería Lucatón')) ?>" class="h-56 w-full object-cover transition duration-500 group-hover:scale-105">
                                                    <?php if ($caption): ?>
                                                        <figcaption class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-marino-900/80 via-marino-900/30 to-transparent p-4 text-sm font-medium text-white">
                                                            <?= htmlspecialchars($caption) ?>
                                                        </figcaption>
                                                    <?php endif; ?>
                                                </figure>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($related)): ?>
                            <section aria-labelledby="related-news" class="space-y-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h2 id="related-news" class="text-2xl font-bold text-marino-900">También te puede interesar</h2>
                                        <p class="text-sm text-marino-600">Historias relacionadas que mantienen viva la comunidad.</p>
                                    </div>
                                    <a href="<?= Router::url('noticias') ?>" class="inline-flex items-center gap-2 rounded-full bg-marino-900 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:-translate-y-0.5 hover:bg-marino-800 focus:outline-none focus:ring-2 focus:ring-marino-500/60">
                                        Ver todas las noticias
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>
                                <div class="grid gap-6 sm:grid-cols-2">
                                    <?php foreach ($related as $item): 
                                        $relatedUrl = Router::url('noticias/' . $item['slug']);
                                        $thumb = !empty($item['cover_image']) ? APP_URL . '/' . ltrim($item['cover_image'], '/') : APP_URL . '/public/assets/images/campaigns/escuela-rural.svg';
                                        $itemDate = $item['published_at'] ?? $item['created_at'] ?? null;
                                    ?>
                                        <article class="group flex flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                                            <div class="relative h-44 overflow-hidden">
                                                <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                                <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent"></div>
                                                <div class="absolute top-4 left-4 inline-flex items-center gap-2 rounded-full bg-white/85 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-marino-800">
                                                    <?= htmlspecialchars($item['category_name'] ?? 'Lucatón') ?>
                                                </div>
                                            </div>
                                            <div class="flex flex-1 flex-col gap-4 p-6">
                                                <div class="space-y-2">
                                                    <h3 class="text-lg font-semibold text-marino-900 group-hover:text-copihue-600">
                                                        <a href="<?= htmlspecialchars($relatedUrl) ?>" class="block">
                                                            <?= htmlspecialchars($item['title']) ?>
                                                        </a>
                                                    </h3>
                                                    <?php if (!empty($item['summary'])): ?>
                                                        <p class="text-sm text-marino-600 line-clamp-3">
                                                            <?= htmlspecialchars($item['summary']) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="mt-auto flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-marino-500">
                                                    <span>
                                                        <?= $itemDate ? date('d M Y', strtotime($itemDate)) : 'Lucatón' ?>
                                                    </span>
                                                    <span class="inline-flex items-center gap-1 text-copihue-600">
                                                        Leer más
                                                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                                        </svg>
                                                    </span>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endif; ?>

                        <?php if (!empty($recent)): ?>
                            <section aria-labelledby="recent-news" class="space-y-6">
                                <h2 id="recent-news" class="text-xl font-semibold text-marino-900">Últimas noticias</h2>
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <?php foreach ($recent as $item): 
                                        $recentUrl = Router::url('noticias/' . $item['slug']);
                                        $recentDate = $item['published_at'] ?? $item['created_at'] ?? null;
                                    ?>
                                        <article class="rounded-2xl border border-gray-100 bg-white/70 p-6 shadow-sm backdrop-blur transition hover:-translate-y-1 hover:shadow-md">
                                            <div class="flex flex-col gap-3">
                                                <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-marino-500">
                                                    <span class="h-2 w-2 rounded-full bg-copihue-500"></span>
                                                    <?= $recentDate ? date('d M Y', strtotime($recentDate)) : 'Lucatón' ?>
                                                </span>
                                                <h3 class="text-lg font-semibold text-marino-900">
                                                    <a href="<?= htmlspecialchars($recentUrl) ?>" class="hover:text-copihue-600 transition">
                                                        <?= htmlspecialchars($item['title']) ?>
                                                    </a>
                                                </h3>
                                                <?php if (!empty($item['summary'])): ?>
                                                    <p class="text-sm text-marino-600 line-clamp-3">
                                                        <?= htmlspecialchars($item['summary']) ?>
                                                    </p>
                                                <?php endif; ?>
                                                <div>
                                                    <a href="<?= htmlspecialchars($recentUrl) ?>" class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-copihue-600">
                                                        Leer noticia completa
                                                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                                        </svg>
                                                    </a>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </article>
    </main>

    <?php include VIEWS_PATH . '/layouts/partials/footer.php'; ?>

    <script src="<?= APP_URL ?>/public/assets/js/app.js?v=2025020503" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const copyButton = document.querySelector('[data-copy-link]');
            const feedback = document.querySelector('[data-copy-feedback]');
            if (!copyButton) return;
            copyButton.addEventListener('click', async () => {
                const link = copyButton.getAttribute('data-copy-link');
                try {
                    await navigator.clipboard.writeText(link);
                    if (feedback) {
                        feedback.classList.remove('hidden');
                        feedback.classList.add('inline-flex');
                        copyButton.classList.add('bg-white/25');
                        setTimeout(() => {
                            feedback.classList.add('hidden');
                            feedback.classList.remove('inline-flex');
                            copyButton.classList.remove('bg-white/25');
                        }, 2500);
                    }
                } catch (err) {
                    console.error('No se pudo copiar el enlace', err);
                }
            });
        });
    </script>
</body>
</html>
