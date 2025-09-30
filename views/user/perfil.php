<?php
require_once __DIR__ . '/../layouts/partials/flash-messages.php';
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/alerts.php';
require_once __DIR__ . '/../components/navigation.php';

$userProfile = $userProfile ?? [];
$communicationPreferences = $communicationPreferences ?? [];
$securityOverview = $securityOverview ?? [];

$page_title = 'Mi Perfil — Lucatón';
$page_description = 'Actualiza tus datos personales, preferencias de comunicación y configuraciones de seguridad.';

$displayName = $userProfile['name'] ?? 'Usuario';
$legalName = trim(($userProfile['first_name'] ?? '') . ' ' . ($userProfile['last_name'] ?? ''));
$avatarUrl = $userProfile['avatar_url'] ?? APP_URL . '/public/assets/images/avatars/default.jpg';
$isVerified = !empty($userProfile['email_verified_at']);

$formatDate = static function (?string $value): string {
    if (empty($value)) {
        return '—';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('d/m/Y H:i', $timestamp) : '—';
};

$memberSince = !empty($userProfile['created_at'])
    ? date('d/m/Y', strtotime($userProfile['created_at']))
    : '—';

$csrfToken = SessionHelper::getCSRFToken();
$socialLinks = is_array($userProfile['social_links'] ?? null) ? $userProfile['social_links'] : [];
$supportedSocials = [
    'linkedin' => 'LinkedIn',
    'instagram' => 'Instagram',
    'x' => 'X (Twitter)',
    'facebook' => 'Facebook',
    'youtube' => 'YouTube',
    'tiktok' => 'TikTok',
    'website' => 'Sitio web',
];

$nameReviewStatus = $userProfile['name_review_status'] ?? null;
$nameReviewBadge = null;
$nameReviewCopy = null;
if ($nameReviewStatus === 'pending') {
    $nameReviewBadge = ['text' => 'Cambio en revisión', 'class' => 'bg-amber-100 text-amber-800'];
    $nameReviewCopy = 'Solicitaste un cambio de nombre. Nuestro equipo lo aprobará o rechazará dentro de 24 horas hábiles.';
} elseif ($nameReviewStatus === 'rejected') {
    $nameReviewBadge = ['text' => 'Cambio rechazado', 'class' => 'bg-red-100 text-red-700'];
    $nameReviewCopy = $userProfile['name_review_notes'] ?? 'Revisa tu correo para conocer el motivo y vuelve a intentarlo.';
} elseif ($nameReviewStatus === 'approved') {
    $nameReviewBadge = ['text' => 'Cambio aprobado', 'class' => 'bg-green-100 text-green-700'];
}

$twoFactorEnabled = $securityOverview['two_factor_enabled'] ?? false;
$passwordUpdatedAt = $securityOverview['password_updated_at'] ?? null;
$lastPasswordReset = $securityOverview['last_password_reset'] ?? null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">

    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/public/assets/images/favicon.svg">
    <link href="<?= APP_URL ?>/public/assets/css/app.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/public/assets/css/aliases.css" rel="stylesheet">
    <script defer src="<?= APP_URL ?>/public/assets/js/app.js?v=2025012801"></script>
    <script defer src="<?= APP_URL ?>/public/assets/js/profile.js?v=2025020101"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <?php include VIEWS_PATH . '/layouts/partials/header.php'; ?>

    <main id="main-content" class="max-w-7xl mx-auto py-8 sm:px-6 lg:px-8">
        <?php include_flash_messages(); ?>

        <?= render_breadcrumb([
            ['name' => 'Inicio', 'href' => Router::url('/')],
            ['name' => 'Mi Panel', 'href' => Router::url('panel')],
            ['name' => 'Mi Perfil', 'href' => Router::url('perfil')],
        ]); ?>

        <div class="mb-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div class="flex items-center space-x-4">
                <img class="h-20 w-20 rounded-full object-cover shadow" src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar de <?= htmlspecialchars($displayName) ?>">
                <div>
                    <p class="text-sm text-gray-500 uppercase tracking-wide">Perfil personal</p>
                    <h1 class="text-3xl font-bold text-gray-900">
                        <?= htmlspecialchars($displayName) ?>
                    </h1>
                    <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-gray-600">
                        <span>Miembro desde <?= htmlspecialchars($memberSince) ?></span>
                        <span class="inline-flex items-center space-x-1">
                            <svg class="h-4 w-4 text-copihue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 10.5h15" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 10.5v6.75a3.75 3.75 0 007.5 0V10.5" />
                            </svg>
                            <span><?= htmlspecialchars(ucfirst($userProfile['status'] ?? 'activo')) ?></span>
                        </span>
                        <?php if ($isVerified): ?>
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                <svg class="h-3.5 w-3.5 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Email verificado
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">
                                <svg class="h-3.5 w-3.5 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Verifica tu correo
                            </span>
                        <?php endif; ?>
                        <?php if ($nameReviewBadge): ?>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?= $nameReviewBadge['class'] ?>">
                                <?= htmlspecialchars($nameReviewBadge['text']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($nameReviewCopy): ?>
                        <p class="mt-2 text-xs text-amber-700 flex items-center">
                            <svg class="h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                            </svg>
                            <?= htmlspecialchars($nameReviewCopy) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="<?= Router::url('campana/crear') ?>" class="btn-primary inline-flex items-center">
                    <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Lanzar nueva campaña
                </a>
                <a href="<?= Router::url('mis-campanas') ?>" class="btn-secondary inline-flex items-center">
                    <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    Ver mis campañas
                </a>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="lg:col-span-2 space-y-6">
                <div class="bg-white shadow rounded-lg p-6 space-y-6">
                    <header class="border-b border-gray-100 pb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Información básica</h2>
                        <p class="mt-1 text-sm text-gray-500">Actualiza tus datos personales y la información que la comunidad ve en tu perfil.</p>
                    </header>

                    <div class="space-y-6">
                        <form method="POST" action="<?= Router::url('perfil') ?>" enctype="multipart/form-data" class="rounded-lg border border-gray-100 p-4 transition-colors duration-150 hover:border-copihue-200 focus-within:border-copihue-300" data-profile-avatar>
                            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars($csrfToken) ?>">
                            <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
                                <div class="flex-shrink-0">
                                    <img class="h-24 w-24 rounded-full object-cover shadow" src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar de <?= htmlspecialchars($displayName) ?>" data-profile-avatar-preview data-original-src="<?= htmlspecialchars($avatarUrl) ?>">
                                </div>
                                <div class="flex-1 space-y-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Foto de perfil</p>
                                        <p class="mt-1 text-sm text-gray-600">Sube una imagen nítida y centrada. Revisamos cada carga para evitar contenido malicioso.</p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <button type="button" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:border-copihue-300 hover:text-copihue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" data-profile-avatar-trigger>
                                            Cambiar foto
                                        </button>
                                        <button type="submit" class="hidden inline-flex items-center rounded-md bg-copihue-600 px-3 py-2 text-sm font-medium text-white hover:bg-copihue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" data-profile-avatar-save>
                                            Guardar foto
                                        </button>
                                        <button type="button" class="hidden inline-flex items-center rounded-md border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" data-profile-avatar-cancel>
                                            Cancelar
                                        </button>
                                        <button type="submit" name="avatar_url" value="" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none focus:underline" data-profile-avatar-reset="true">
                                            Restablecer a la imagen predeterminada
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500">Formatos permitidos: JPG, PNG o WebP. Máximo 2&nbsp;MB.</p>
                                    <p class="text-xs text-copihue-600 hidden" data-profile-avatar-filename></p>
                                </div>
                                <input type="file" name="avatar_file" accept=".jpg,.jpeg,.png,.webp" class="hidden" data-profile-avatar-input>
                            </div>
                        </form>

                        <form method="POST" action="<?= Router::url('perfil') ?>" class="rounded-lg border border-gray-100 p-4 transition-colors duration-150 hover:border-copihue-200 focus-within:border-copihue-300" data-profile-edit>
                            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars($csrfToken) ?>">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex-1">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Nombre legal</p>
                                    <p class="mt-2 text-base font-medium text-gray-900" data-profile-display>
                                        <?= $legalName !== '' ? htmlspecialchars($legalName) : 'No registrado'; ?>
                                    </p>
                                    <div class="mt-4 hidden gap-4 sm:grid sm:grid-cols-2" data-profile-editor>
                                        <div>
                                            <label for="first_name" class="block text-sm font-medium text-gray-700">Nombre</label>
                                            <input id="first_name" name="first_name" type="text" value="<?= htmlspecialchars($userProfile['first_name'] ?? '') ?>" placeholder="Ej. María José" class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500" autocomplete="given-name" data-profile-input disabled>
                                        </div>
                                        <div>
                                            <label for="last_name" class="block text-sm font-medium text-gray-700">Apellido</label>
                                            <input id="last_name" name="last_name" type="text" value="<?= htmlspecialchars($userProfile['last_name'] ?? '') ?>" placeholder="Ej. González" class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500" autocomplete="family-name" data-profile-input disabled>
                                        </div>
                                    </div>
                                    <p class="mt-3 hidden text-xs text-gray-500" data-profile-editor>Los cambios se aprueban manualmente en un máximo de 24 horas hábiles.</p>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition hover:border-copihue-300 hover:text-copihue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" data-profile-action="edit">
                                        <span class="sr-only">Editar nombre legal</span>
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.862 4.487l1.688 1.688a2 2 0 010 2.828l-8.315 8.315L6 18l.682-4.235 8.315-8.315a2 2 0 012.828 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5l4 4" />
                                        </svg>
                                    </button>
                                    <div class="hidden items-center space-x-2" data-profile-actions>
                                        <button type="button" class="rounded-md border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" data-profile-action="cancel">Cancelar</button>
                                        <button type="submit" class="inline-flex items-center rounded-md bg-copihue-600 px-3 py-2 text-sm font-medium text-white hover:bg-copihue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" data-profile-action="save">Guardar</button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <form method="POST" action="<?= Router::url('perfil') ?>" class="rounded-lg border border-gray-100 p-4 transition-colors duration-150 hover:border-copihue-200 focus-within:border-copihue-300" data-profile-edit>
                            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars($csrfToken) ?>">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex-1">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Teléfono</p>
                                    <p class="mt-2 text-base font-medium text-gray-900" data-profile-display>
                                        <?= !empty($userProfile['phone']) ? htmlspecialchars($userProfile['phone']) : 'No registrado'; ?>
                                    </p>
                                    <div class="mt-4 hidden" data-profile-editor>
                                        <label for="phone" class="block text-sm font-medium text-gray-700">Número de contacto</label>
                                        <input id="phone" name="phone" type="tel" value="<?= htmlspecialchars($userProfile['phone'] ?? '') ?>" placeholder="+56 9 1234 5678" class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500" data-profile-input disabled>
                                        <p class="mt-2 text-xs text-gray-500">Usa solo números y símbolos +, -, ().</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition hover:border-copihue-300 hover:text-copihue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" data-profile-action="edit">
                                        <span class="sr-only">Editar teléfono</span>
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.862 4.487l1.688 1.688a2 2 0 010 2.828l-8.315 8.315L6 18l.682-4.235 8.315-8.315a2 2 0 012.828 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5l4 4" />
                                        </svg>
                                    </button>
                                    <div class="hidden items-center space-x-2" data-profile-actions>
                                        <button type="button" class="rounded-md border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" data-profile-action="cancel">Cancelar</button>
                                        <button type="submit" class="inline-flex items-center rounded-md bg-copihue-600 px-3 py-2 text-sm font-medium text-white hover:bg-copihue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" data-profile-action="save">Guardar</button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <form method="POST" action="<?= Router::url('perfil') ?>" class="rounded-lg border border-gray-100 p-4 transition-colors duration-150 hover:border-copihue-200 focus-within:border-copihue-300" data-profile-edit>
                            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars($csrfToken) ?>">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex-1">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Ubicación</p>
                                    <p class="mt-2 text-base font-medium text-gray-900" data-profile-display>
                                        <?= !empty($userProfile['location']) ? htmlspecialchars($userProfile['location']) : 'No especificada'; ?>
                                    </p>
                                    <div class="mt-4 hidden" data-profile-editor>
                                        <label for="location" class="block text-sm font-medium text-gray-700">Ciudad, país</label>
                                        <input id="location" name="location" type="text" value="<?= htmlspecialchars($userProfile['location'] ?? '') ?>" placeholder="Ciudad, país" class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500" data-profile-input disabled>
                                        <p class="mt-2 text-xs text-gray-500">Máximo 150 caracteres.</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition hover:border-copihue-300 hover:text-copihue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" data-profile-action="edit">
                                        <span class="sr-only">Editar ubicación</span>
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.862 4.487l1.688 1.688a2 2 0 010 2.828l-8.315 8.315L6 18l.682-4.235 8.315-8.315a2 2 0 012.828 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5l4 4" />
                                        </svg>
                                    </button>
                                    <div class="hidden items-center space-x-2" data-profile-actions>
                                        <button type="button" class="rounded-md border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" data-profile-action="cancel">Cancelar</button>
                                        <button type="submit" class="inline-flex items-center rounded-md bg-copihue-600 px-3 py-2 text-sm font-medium text-white hover:bg-copihue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" data-profile-action="save">Guardar</button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <form method="POST" action="<?= Router::url('perfil') ?>" class="rounded-lg border border-gray-100 p-4 transition-colors duration-150 hover:border-copihue-200 focus-within:border-copihue-300" data-profile-edit data-profile-textarea>
                            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars($csrfToken) ?>">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex-1">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Biografía</p>
                                    <p class="mt-2 text-sm text-gray-700" data-profile-display>
                                        <?= !empty($userProfile['bio']) ? nl2br(htmlspecialchars($userProfile['bio'])) : 'Comparte tu historia y el impacto que buscas generar.'; ?>
                                    </p>
                                    <div class="mt-4 hidden" data-profile-editor>
                                        <label for="bio" class="block text-sm font-medium text-gray-700">Biografía pública</label>
                                        <textarea id="bio" name="bio" rows="4" class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500" placeholder="Cuéntale a la comunidad qué te mueve y el tipo de campañas que impulsas." data-profile-input disabled><?= htmlspecialchars($userProfile['bio'] ?? '') ?></textarea>
                                        <p class="mt-2 text-xs text-gray-500">Máximo 1500 caracteres. Evita compartir datos personales de terceros.</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition hover:border-copihue-300 hover:text-copihue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" data-profile-action="edit">
                                        <span class="sr-only">Editar biografía</span>
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.862 4.487l1.688 1.688a2 2 0 010 2.828l-8.315 8.315L6 18l.682-4.235 8.315-8.315a2 2 0 012.828 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5l4 4" />
                                        </svg>
                                    </button>
                                    <div class="hidden items-center space-x-2" data-profile-actions>
                                        <button type="button" class="rounded-md border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" data-profile-action="cancel">Cancelar</button>
                                        <button type="submit" class="inline-flex items-center rounded-md bg-copihue-600 px-3 py-2 text-sm font-medium text-white hover:bg-copihue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" data-profile-action="save">Guardar</button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <form method="POST" action="<?= Router::url('perfil') ?>" class="rounded-lg border border-gray-100 p-4 transition-colors duration-150 hover:border-copihue-200 focus-within:border-copihue-300" data-profile-edit>
                            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars($csrfToken) ?>">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex-1">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Redes sociales</p>
                                    <?php $activeSocials = array_filter($socialLinks, static function ($value) { return !empty($value); }); ?>
                                    <?php if (!empty($activeSocials)): ?>
                                        <ul class="mt-2 space-y-2 text-sm text-gray-700" data-profile-display>
                                            <?php foreach ($activeSocials as $platform => $url): ?>
                                                <li class="flex flex-col sm:flex-row sm:items-center sm:gap-3">
                                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium uppercase tracking-wide text-gray-600">
                                                        <?= htmlspecialchars($supportedSocials[$platform] ?? ucfirst($platform)) ?>
                                                    </span>
                                                    <a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener noreferrer" class="mt-1 text-sm text-copihue-600 hover:text-copihue-700 sm:mt-0">
                                                        <?= htmlspecialchars($url) ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="mt-2 text-sm text-gray-500" data-profile-display>Aún no has agregado enlaces sociales.</p>
                                    <?php endif; ?>
                                    <div class="mt-4 hidden gap-4 sm:grid sm:grid-cols-2" data-profile-editor>
                                        <?php foreach ($supportedSocials as $key => $label): ?>
                                            <div>
                                                <label for="social-<?= $key ?>" class="block text-sm font-medium text-gray-700"><?= htmlspecialchars($label) ?></label>
                                                <input id="social-<?= $key ?>" name="social[<?= $key ?>]" type="url" value="<?= htmlspecialchars($socialLinks[$key] ?? '') ?>" placeholder="https://" class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500" data-profile-input disabled>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="mt-3 hidden text-xs text-gray-500" data-profile-editor>Solo mostramos enlaces verificados. Incluye https:// en cada URL.</p>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition hover:border-copihue-300 hover:text-copihue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" data-profile-action="edit">
                                        <span class="sr-only">Editar redes sociales</span>
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.862 4.487l1.688 1.688a2 2 0 010 2.828l-8.315 8.315L6 18l.682-4.235 8.315-8.315a2 2 0 012.828 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5l4 4" />
                                        </svg>
                                    </button>
                                    <div class="hidden items-center space-x-2" data-profile-actions>
                                        <button type="button" class="rounded-md border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" data-profile-action="cancel">Cancelar</button>
                                        <button type="submit" class="inline-flex items-center rounded-md bg-copihue-600 px-3 py-2 text-sm font-medium text-white hover:bg-copihue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" data-profile-action="save">Guardar</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <form method="POST" action="<?= Router::url('perfil/preferencias') ?>" class="bg-white shadow rounded-lg p-6 space-y-6">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars($csrfToken) ?>">
                    <header class="border-b border-gray-100 pb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Preferencias de comunicación</h2>
                        <p class="mt-1 text-sm text-gray-500">Elige qué mensajes quieres recibir de Lucatón.</p>
                    </header>
                    <div class="space-y-4">
                        <label class="flex items-start space-x-3">
                            <input type="checkbox" name="product_updates" value="1" class="mt-1 h-4 w-4 rounded border-gray-300 text-copihue-600 focus:ring-copihue-500" <?= !empty($communicationPreferences['product_updates']) ? 'checked' : '' ?>>
                            <span>
                                <span class="text-sm font-medium text-gray-900">Actualizaciones de producto</span>
                                <span class="block text-sm text-gray-500">Noticias sobre funciones nuevas, mejoras de seguridad y cambios relevantes.</span>
                            </span>
                        </label>
                        <label class="flex items-start space-x-3">
                            <input type="checkbox" name="campaign_tips" value="1" class="mt-1 h-4 w-4 rounded border-gray-300 text-copihue-600 focus:ring-copihue-500" <?= !empty($communicationPreferences['campaign_tips']) ? 'checked' : '' ?>>
                            <span>
                                <span class="text-sm font-medium text-gray-900">Tips y buenas prácticas</span>
                                <span class="block text-sm text-gray-500">Guías mensuales para mejorar la transparencia y el alcance de tus campañas.</span>
                            </span>
                        </label>
                        <label class="flex items-start space-x-3">
                            <input type="checkbox" name="donation_alerts" value="1" class="mt-1 h-4 w-4 rounded border-gray-300 text-copihue-600 focus:ring-copihue-500" <?= !empty($communicationPreferences['donation_alerts']) ? 'checked' : '' ?>>
                            <span>
                                <span class="text-sm font-medium text-gray-900">Alertas de donaciones</span>
                                <span class="block text-sm text-gray-500">Recibe un correo cada vez que alguien aporte a tus campañas.</span>
                            </span>
                        </label>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-marino-600 px-4 py-2 text-sm font-medium text-white hover:bg-marino-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-marino-500">
                            Guardar preferencias
                        </button>
                    </div>
                </form>
            </section>

            <section class="space-y-6">
                <form method="POST" action="<?= Router::url('perfil/seguridad') ?>" class="bg-white shadow rounded-lg p-6 space-y-4">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars($csrfToken) ?>">
                    <header class="border-b border-gray-100 pb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Seguridad de la cuenta</h2>
                        <p class="mt-1 text-sm text-gray-500">Refuerza tu acceso activando la verificación en dos pasos.</p>
                    </header>
                    <div class="text-sm text-gray-600 space-y-2">
                        <p><strong>Estado actual:</strong> <?= $twoFactorEnabled ? 'Verificación en dos pasos activada' : 'Protección adicional desactivada' ?></p>
                        <p>Cuando esté disponible el dominio definitivo podrás vincular tu app autenticadora (Google Authenticator, Authy, etc.). Por ahora dejamos listo el flujo para activarlo.</p>
                    </div>
                    <div>
                        <label for="two_factor_action" class="block text-sm font-medium text-gray-700">¿Qué deseas hacer?</label>
                        <select id="two_factor_action" name="two_factor_action" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500">
                            <option value="none">Mantener estado actual</option>
                            <?php if ($twoFactorEnabled): ?>
                                <option value="disable">Desactivar temporalmente</option>
                            <?php else: ?>
                                <option value="enable">Activar verificación en 2 pasos</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500">
                            Actualizar seguridad
                        </button>
                    </div>
                    <div class="border-t border-gray-100 pt-4 text-xs text-gray-500 space-y-1">
                        <p><strong>Último cambio de contraseña:</strong> <?= htmlspecialchars($formatDate($passwordUpdatedAt)) ?></p>
                        <p><strong>Último restablecimiento manual:</strong> <?= htmlspecialchars($formatDate($lastPasswordReset)) ?></p>
                    </div>
                </form>

                <form method="POST" action="<?= Router::url('perfil/password') ?>" class="bg-white shadow rounded-lg p-6 space-y-4">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars($csrfToken) ?>">
                    <header class="border-b border-gray-100 pb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Cambiar contraseña</h2>
                        <p class="mt-1 text-sm text-gray-500">Primero valida tu contraseña actual y luego confirma con el código que te enviaremos al correo.</p>
                    </header>
                    <div class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700">Contraseña actual</label>
                            <input id="current_password" name="current_password" type="password" required class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500" autocomplete="current-password">
                        </div>
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700">Nueva contraseña</label>
                            <input id="new_password" name="new_password" type="password" required minlength="8" class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500" autocomplete="new-password">
                            <p class="mt-2 text-xs text-gray-500">Usa al menos 8 caracteres. Combina mayúsculas, minúsculas y números.</p>
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirma nueva contraseña</label>
                            <input id="confirm_password" name="confirm_password" type="password" required class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500" autocomplete="new-password">
                        </div>
                        <div>
                            <label for="verification_code" class="block text-sm font-medium text-gray-700">Código de verificación</label>
                            <input id="verification_code" name="verification_code" type="text" inputmode="numeric" pattern="[0-9]{6}" placeholder="Ingresa el código de 6 dígitos" class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500">
                            <p class="mt-2 text-xs text-gray-500">Generaremos el código y lo enviaremos a tu correo. En este entorno de pruebas también lo verás en un mensaje informativo.</p>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Solicitar cambio de contraseña
                        </button>
                    </div>
                </form>

                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900">Soporte rápido</h2>
                    <p class="mt-2 text-sm text-gray-600">¿Necesitas ayuda para actualizar datos o resolver un incidente?</p>
                    <ul class="mt-4 space-y-3 text-sm text-gray-700">
                        <li class="flex items-start space-x-3">
                            <span class="mt-1 h-2 w-2 rounded-full bg-copihue-500"></span>
                            <div>
                                <p class="font-medium text-gray-900">Centro de ayuda</p>
                                <p>Guías paso a paso y preguntas frecuentes.</p>
                                <a class="mt-1 inline-flex items-center text-copihue-600 font-semibold" href="<?= Router::url('ayuda') ?>">
                                    Abrir centro de ayuda
                                    <svg class="ml-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </li>
                        <li class="flex items-start space-x-3">
                            <span class="mt-1 h-2 w-2 rounded-full bg-copihue-500"></span>
                            <div>
                                <p class="font-medium text-gray-900">Contactar soporte</p>
                                <p>Escríbenos a <a class="text-copihue-600 font-medium" href="mailto:soporte@lucaton.cl">soporte@lucaton.cl</a> y responderemos en menos de 24 horas hábiles.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </section>
        </div>
    </main>

    <?php include VIEWS_PATH . '/layouts/partials/footer.php'; ?>
</body>
</html>
