<?php
require_once __DIR__ . '/../layouts/partials/flash-messages.php';
require_once __DIR__ . '/../components/navigation.php';

$notifications = $notifications ?? [];
$selectedNotification = $selectedNotification ?? null;
$pagination = $pagination ?? ['page' => 1, 'limit' => 20, 'has_more' => false, 'next_page' => null];
$unreadCount = $unreadCount ?? 0;
$page_title = $page_title ?? 'Notificaciones';
$page_description = 'Consulta el historial completo de avisos enviados por el equipo de Lucatón.';
$current_page = $current_page ?? 'notifications';
$userName = $_SESSION['user_name'] ?? 'Usuario';

$selectedId = $selectedNotification['id'] ?? null;
$selectedMeta = $selectedNotification['meta'] ?? null;
$selectedCtaUrl = is_array($selectedMeta) ? ($selectedMeta['cta_url'] ?? null) : null;
$selectedCtaLabel = is_array($selectedMeta) ? ($selectedMeta['cta_label'] ?? 'Ver más detalles') : 'Ver más detalles';

$breadcrumbs = [
    ['name' => 'Inicio', 'href' => Router::url('/')],
    ['name' => 'Panel general', 'href' => Router::url('panel')],
    ['name' => 'Notificaciones', 'href' => Router::url('notificaciones')],
];

$formatDate = static function (?string $value): string {
    if (empty($value)) {
        return '—';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('d/m/Y H:i', $timestamp) : '—';
};

$notificationLink = static function (array $notification, array $pagination) {
    $query = [];
    if (($pagination['page'] ?? 1) > 1) {
        $query['page'] = (int)$pagination['page'];
    }
    $query['n'] = (int)$notification['id'];
    return Router::url('notificaciones', $query);
};
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — Lucatón</title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">

    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/public/assets/images/favicon.svg">
    <link href="<?= APP_URL ?>/public/assets/css/app.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/public/assets/css/aliases.css" rel="stylesheet">
    <script defer src="<?= APP_URL ?>/public/assets/js/app.js?v=2025020503"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <?php include VIEWS_PATH . '/layouts/partials/header.php'; ?>

    <main id="main-content" class="max-w-7xl mx-auto py-8 sm:px-6 lg:px-8">
        <?php include_flash_messages(); ?>

        <?= render_breadcrumb($breadcrumbs); ?>

        <div class="mb-8 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm uppercase tracking-wide text-gray-500">Historial de notificaciones</p>
                <h1 class="text-3xl font-bold text-gray-900">Hola, <?= htmlspecialchars($userName) ?></h1>
                <p class="mt-2 text-sm text-gray-600">Revisa todos los avisos que hemos enviado y mantente al día con las novedades.</p>
            </div>
            <div class="flex items-center gap-3 text-sm text-gray-500">
                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 font-medium text-gray-700">
                    No leídas: <?= number_format((int)$unreadCount, 0, ',', '.') ?>
                </span>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="lg:col-span-1">
                <div class="bg-white shadow-soft rounded-3xl border border-gray-100 overflow-hidden">
                    <header class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-900">Notificaciones</h2>
                        <span class="text-xs text-gray-500"><?= count($notifications) ?> recientes</span>
                    </header>
                    <?php if (empty($notifications)): ?>
                        <div class="px-5 py-6 text-sm text-gray-500">
                            Aún no registramos notificaciones en tu historial.
                        </div>
                    <?php else: ?>
                        <ul class="divide-y divide-gray-100 max-h-[28rem] overflow-y-auto" data-notification-list>
                            <?php foreach ($notifications as $notification): ?>
                                <?php
                                    $isActive = $selectedId === $notification['id'];
                                    $itemClasses = $isActive
                                        ? 'bg-copihue-50 border-l-4 border-copihue-500'
                                        : 'hover:bg-gray-50';
                                ?>
                                <li>
                                    <a href="<?= htmlspecialchars($notificationLink($notification, $pagination)) ?>"
                                       class="block px-4 py-3 text-sm transition-colors <?= $itemClasses ?>">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="font-semibold text-gray-900 flex items-center gap-2">
                                                    <?= htmlspecialchars($notification['title']) ?>
                                                    <?php if (!$notification['is_read']): ?>
                                                        <span class="inline-flex h-2 w-2 rounded-full bg-copihue-500"></span>
                                                    <?php endif; ?>
                                                </p>
                                                <p class="mt-1 text-xs text-gray-500 line-clamp-2">
                                                    <?= htmlspecialchars(mb_substr($notification['message'], 0, 110)) ?><?= mb_strlen($notification['message']) > 110 ? '…' : '' ?>
                                                </p>
                                            </div>
                                            <span class="text-xs text-gray-400 whitespace-nowrap">
                                                <?= $formatDate($notification['created_at']) ?>
                                            </span>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if ($pagination['has_more'] ?? false): ?>
                            <div class="px-5 py-4 border-t border-gray-100 text-center">
                                <a href="<?= htmlspecialchars(Router::url('notificaciones', ['page' => $pagination['next_page'] ?? (($pagination['page'] ?? 1) + 1)])) ?>"
                                   class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-copihue-600 ring-1 ring-inset ring-copihue-200 hover:bg-copihue-50">
                                    Cargar más
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="lg:col-span-2">
                <div class="bg-white shadow-soft rounded-3xl border border-gray-100 h-full">
                    <?php if (!$selectedNotification): ?>
                        <div class="px-6 py-12 text-center text-sm text-gray-500">
                            Selecciona una notificación en la columna izquierda para ver su detalle.
                        </div>
                    <?php else: ?>
                        <div class="px-6 py-6 space-y-6">
                            <header class="space-y-2">
                                <div class="flex items-center justify-between gap-3">
                                    <h2 class="text-2xl font-semibold text-gray-900">
                                        <?= htmlspecialchars($selectedNotification['title']) ?>
                                    </h2>
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-gray-100 text-gray-600">
                                        <?= $formatDate($selectedNotification['created_at']) ?>
                                    </span>
                                </div>
                                <?php if (!empty($selectedCtaUrl)): ?>
                                    <a href="<?= htmlspecialchars($selectedCtaUrl) ?>"
                                       class="inline-flex items-center text-sm font-semibold text-copihue-600 hover:text-copihue-700"
                                       target="_blank" rel="noopener noreferrer">
                                        <?= htmlspecialchars($selectedCtaLabel) ?>
                                        <svg class="ml-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </header>

                            <article class="prose prose-sm max-w-none text-gray-700">
                                <?= nl2br(htmlspecialchars($selectedNotification['message'])) ?>
                            </article>

                            <footer class="flex flex-wrap items-center gap-4 text-xs text-gray-500">
                                <?php if (!empty($selectedNotification['read_at'])): ?>
                                    <span>Leída el <?= $formatDate($selectedNotification['read_at']) ?></span>
                                <?php endif; ?>
                                <span>ID #<?= (int)$selectedNotification['id'] ?></span>
                            </footer>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <?php include VIEWS_PATH . '/layouts/partials/footer.php'; ?>
</body>
</html>
