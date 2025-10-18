<?php
require_once __DIR__ . '/../../components/navigation.php';

$page_title = $page_title ?? 'Noticias Lucatón';
$page_description = $page_description ?? 'Las últimas noticias, comunicados y actualizaciones de la comunidad Lucatón.';
$filters = $filters ?? [];
$news_items = $news_items ?? [];
$categories = $categories ?? [];
$pagination = $pagination ?? ['current_page' => 1, 'total_pages' => 1, 'total' => count($news_items), 'per_page' => 9];
$current_page = 'news';
$current_category = $filters['category_slug'] ?? '';
$search_query = $filters['search'] ?? '';
$date_from = $filters['date_from'] ?? '';
$date_to = $filters['date_to'] ?? '';
$today = date('Y-m-d');
$max_date_from = $date_to !== '' ? min($date_to, $today) : $today;
$max_date_to = $today;
$min_date_to = $date_from !== '' ? $date_from : '';
?>
<?php
$canonical_url = Router::url('noticias');
$sanitized_title = htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8');
$sanitized_description = htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8');
$sanitized_canonical = htmlspecialchars($canonical_url, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $sanitized_title ?></title>
    <meta name="description" content="<?= $sanitized_description ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= $sanitized_canonical ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $sanitized_canonical ?>">
    <meta property="og:title" content="<?= $sanitized_title ?>">
    <meta property="og:description" content="<?= $sanitized_description ?>">
    <meta property="og:image" content="<?= asset_url('images/og-image.jpg') ?>">
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= $sanitized_canonical ?>">
    <meta property="twitter:title" content="<?= $sanitized_title ?>">
    <meta property="twitter:description" content="<?= $sanitized_description ?>">
    <meta property="twitter:image" content="<?= asset_url('images/og-image.jpg') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= asset_url('images/favicon.svg') ?>">
    <link href="<?= asset_url('css/app.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('css/aliases.css') ?>" rel="stylesheet">
</head>
<body class="bg-gray-50 text-marino-900">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">Saltar al contenido principal</a>

    <?php include VIEWS_PATH . '/layouts/partials/header.php'; ?>

    <main id="main-content" class="pb-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
            <?php echo render_breadcrumb([
                ['name' => 'Inicio', 'href' => Router::url('/')],
                ['name' => 'Noticias', 'href' => Router::url('noticias')]
            ]); ?>
        </div>
        <section class="relative isolate overflow-hidden bg-gradient-to-br from-marino-900 via-marino-800 to-marino-700 text-white mt-6 rounded-b-[2.5rem]">
            <div class="absolute inset-0 opacity-30" aria-hidden="true">
                <div class="absolute -top-20 left-10 h-40 w-40 rounded-full bg-copihue-500 blur-3xl"></div>
                <div class="absolute bottom-0 right-16 h-52 w-52 rounded-full bg-white/20 blur-3xl"></div>
            </div>
            <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-20">
                <div class="max-w-3xl space-y-4">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 text-sm font-semibold text-white/90">Noticias Lucatón</span>
                    <h1 class="text-3xl sm:text-4xl font-bold leading-tight text-white">Historias y actualizaciones de nuestra comunidad</h1>
                    <p class="text-base text-white/80">Descubre avances de campañas, comunicados oficiales y notas sobre el impacto que generamos en Chile.</p>
                </div>
                <form method="GET" class="mt-8 rounded-3xl border border-white/15 bg-gradient-to-br from-white/10 via-white/5 to-white/10 p-5 shadow-soft backdrop-blur-sm sm:p-8">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label for="q" class="block text-[0.7rem] font-semibold uppercase tracking-wide text-white/70">Buscar</label>
                            <div class="filter-field">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-white/70">
                                    <circle cx="11" cy="11" r="6" />
                                    <line x1="20" y1="20" x2="16.65" y2="16.65" />
                                </svg>
                                <input type="text" id="q" name="q" value="<?= htmlspecialchars($search_query) ?>" class="filter-input" placeholder="Busca por título, categoría o palabras clave">
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label for="categoria" class="block text-[0.7rem] font-semibold uppercase tracking-wide text-white/70">Categoría</label>
                            <div class="filter-field">
                                <select id="categoria" name="categoria" class="filter-input filter-select">
                                    <option value="" class="text-gray-900">Todas</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= htmlspecialchars($category['slug']) ?>" <?= $current_category === $category['slug'] ? 'selected' : '' ?> class="text-gray-900">
                                            <?= htmlspecialchars($category['name']) ?> (<?= (int)($category['article_count'] ?? 0) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <svg class="h-4 w-4 text-white/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 9l6 6 6-6" />
                                </svg>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label for="desde" class="block text-[0.7rem] font-semibold uppercase tracking-wide text-white/70">Desde</label>
                            <div class="filter-field">
                                <input type="date" id="desde" name="desde" value="<?= htmlspecialchars($date_from) ?>" max="<?= htmlspecialchars($max_date_from) ?>" class="filter-input" >
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label for="hasta" class="block text-[0.7rem] font-semibold uppercase tracking-wide text-white/70">Hasta</label>
                            <div class="filter-field">
                                <input type="date" id="hasta" name="hasta" value="<?= htmlspecialchars($date_to) ?>" max="<?= htmlspecialchars($max_date_to) ?>" <?= $min_date_to !== '' ? 'min="' . htmlspecialchars($min_date_to) . '"' : '' ?> class="filter-input">
                            </div>
                        </div>

                        <div class="sm:col-span-2 flex flex-wrap justify-end gap-3 pt-1">
                            <a href="<?= Router::url('noticias') ?>" class="inline-flex items-center justify-center rounded-full border border-white/40 px-5 py-2.5 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-white/10">Limpiar</a>
                            <button type="submit" class="inline-flex items-center justify-center rounded-full bg-white px-5 py-2.5 text-xs font-semibold uppercase tracking-wide text-marino-900 shadow-sm transition hover:bg-copihue-100">Aplicar</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="pointer-events-none absolute inset-x-0 bottom-0 overflow-hidden leading-[0] text-white" aria-hidden="true">
                <svg viewBox="0 0 1440 160" preserveAspectRatio="none" class="block w-full h-28">
                    <path fill="currentColor" d="M0,120C220,150,440,150,660,120C880,90,1100,30,1260,45C1420,60,1440,90,1440,90V160H0Z" />
                </svg>
            </div>
        </section>

        <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-10 space-y-8">
            <?php if (!empty($categories)): ?>
                <div class="flex flex-wrap gap-2">
                    <a href="<?= Router::url('noticias', array_merge($_GET, ['categoria' => null, 'page' => null])) ?>" class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold <?= $current_category === '' ? 'bg-copihue-600 text-white' : 'bg-white text-marino-700 border border-gray-200 hover:border-copihue-400 hover:text-copihue-600' ?>">
                        Todas las categorías
                    </a>
                    <?php foreach ($categories as $category): ?>
                        <a href="<?= Router::url('noticias', array_merge($_GET, ['categoria' => $category['slug'], 'page' => null])) ?>" class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold <?= $current_category === $category['slug'] ? 'bg-copihue-600 text-white' : 'bg-white text-marino-700 border border-gray-200 hover:border-copihue-400 hover:text-copihue-600' ?>">
                            <span class="h-2 w-2 rounded-full bg-copihue-500"></span>
                            <?= htmlspecialchars($category['name']) ?> (<?= (int)($category['article_count'] ?? 0) ?>)
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($news_items)): ?>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($news_items as $item): ?>
                        <article class="group relative flex flex-col overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-soft transition hover:-translate-y-2 hover:shadow-strong">
                            <?php if (!empty($item['cover_image'])): ?>
                                <div class="aspect-[16/10] overflow-hidden">
                                    <img src="<?= APP_URL . '/' . ltrim($item['cover_image'], '/') ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                </div>
                            <?php endif; ?>
                            <div class="flex flex-1 flex-col gap-3 p-6">
                                <div class="flex items-center justify-between text-xs text-gray-500 uppercase tracking-wide">
                                    <span><?= $item['category_name'] ? htmlspecialchars($item['category_name']) : 'Sin categoría' ?></span>
                                    <span><?= $item['published_at'] ? date('d M Y', strtotime($item['published_at'])) : '' ?></span>
                                </div>
                                <h2 class="text-lg font-semibold text-marino-900 leading-tight line-clamp-2">
                                    <a href="<?= Router::url('noticias/' . $item['slug']) ?>" class="stretched-link">
                                        <?= htmlspecialchars($item['title']) ?>
                                    </a>
                                </h2>
                                <?php if (!empty($item['summary'])): ?>
                                    <p class="text-sm text-marino-600 line-clamp-3">
                                        <?= htmlspecialchars($item['summary']) ?>
                                    </p>
                                <?php endif; ?>
                                <div class="mt-auto pt-3">
                                    <a href="<?= Router::url('noticias/' . $item['slug']) ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-copihue-600 transition group-hover:translate-x-1">
                                        Leer más
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="mt-10 flex items-center justify-between text-sm text-gray-500">
                        <p>Mostrando página <?= (int)$pagination['current_page'] ?> de <?= (int)$pagination['total_pages'] ?> (<?= (int)$pagination['total'] ?> noticias)</p>
                        <div class="flex items-center gap-2">
                            <?php if ($pagination['current_page'] > 1): ?>
                                <a href="<?= Router::url('noticias', array_merge($_GET, ['page' => $pagination['current_page'] - 1])) ?>" class="btn-secondary text-xs">Anterior</a>
                            <?php endif; ?>
                            <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                <a href="<?= Router::url('noticias', array_merge($_GET, ['page' => $pagination['current_page'] + 1])) ?>" class="btn-secondary text-xs">Siguiente</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="rounded-3xl border border-gray-200 bg-white p-8 text-center shadow-sm">
                    <h3 class="text-lg font-semibold text-marino-900">Aún no hay noticias con estos criterios.</h3>
                    <p class="mt-2 text-sm text-marino-600">Prueba con otra categoría o fecha para descubrir nuevas historias.</p>
                </div>
            <?php endif; ?>
        </section>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var fromInput = document.getElementById('desde');
            var toInput = document.getElementById('hasta');
            var today = new Date().toISOString().split('T')[0];

            function clampToToday(value) {
                return value && value > today ? today : value;
            }

            function syncDateControls() {
                if (fromInput) {
                    var limit = today;
                    if (toInput && toInput.value) {
                        var clampedTo = clampToToday(toInput.value);
                        limit = clampedTo && clampedTo < limit ? clampedTo : limit;
                    }
                    fromInput.max = limit;
                    if (fromInput.value && fromInput.value > fromInput.max) {
                        fromInput.value = fromInput.max;
                    }
                }
                if (toInput) {
                    toInput.max = today;
                    if (fromInput && fromInput.value) {
                        toInput.min = fromInput.value;
                        if (toInput.value && toInput.value < fromInput.value) {
                            toInput.value = fromInput.value;
                        }
                    } else {
                        toInput.removeAttribute('min');
                    }
                    if (toInput.value && toInput.value > today) {
                        toInput.value = today;
                    }
                }
            }

            if (fromInput || toInput) {
                syncDateControls();
                if (fromInput) {
                    fromInput.addEventListener('change', syncDateControls);
                }
                if (toInput) {
                    toInput.addEventListener('change', syncDateControls);
                }
            }
        });
        </script>
    </main>

    <?php include VIEWS_PATH . '/layouts/partials/footer.php'; ?>
    <script src="<?= asset_url('js/app.js') ?>" defer></script>
</body>
</html>
