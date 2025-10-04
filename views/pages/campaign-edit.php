<?php
require_once __DIR__ . '/../components/forms.php';
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/alerts.php';
require_once __DIR__ . '/../components/navigation.php';
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$campaignId = (int)($campaign['id'] ?? 0);
$formErrors = $formErrors ?? [];
$formOld = $formOld ?? [];
$categories = $categories ?? [];
$mediaManifest = $mediaManifest ?? [];

$baseOld = [
    'title' => $campaign['title'] ?? '',
    'short_description' => $campaign['summary'] ?? '',
    'description' => $campaign['story'] ?? '',
    'goal_amount_input' => isset($campaign['goal_amount']) ? (string)(int)$campaign['goal_amount'] : '',
    'end_date' => !empty($campaign['end_date']) ? date('Y-m-d', strtotime($campaign['end_date'])) : '',
    'category' => $campaign['category_slug'] ?? ($campaign['category'] ?? ''),
    'beneficiary_type' => $campaign['beneficiary_type'] ?? 'individual',
    'beneficiary_name' => $campaign['beneficiary_name'] ?? '',
    'beneficiary_contact_text' => $campaign['beneficiary_contact'] ?? '',
    'location' => $campaign['location_label'] ?? ($campaign['location'] ?? ''),
    'video_url' => $campaign['video_url'] ?? '',
    'ai_generated' => !empty($campaign['ai_assisted']) ? '1' : '0',
];

$old = array_merge($baseOld, $formOld);
$amountValue = $old['goal_amount_input'] !== '' ? $old['goal_amount_input'] : ($baseOld['goal_amount_input'] ?? '');
$beneficiaryContactValue = $old['beneficiary_contact_text'] ?? '';
$isAiChecked = !empty($old['ai_generated']);

$categoryOptions = array_filter(
    $categories,
    static fn ($label, $slug) => $slug !== '',
    ARRAY_FILTER_USE_BOTH
);

$coverPreview = $mediaManifest['cover_image'] ?? ($campaign['cover_image_url'] ?? $campaign['image_url'] ?? null);
$normalizePublicUrl = static function (?string $path) {
    return CampaignMediaUploadService::normalizePublicUrl($path);
};
$coverPreview = $normalizePublicUrl($coverPreview);
$galleryItems = array_map(static function (array $item) use ($normalizePublicUrl) {
    if (!isset($item['url'])) {
        return $item;
    }

    $item['url'] = $normalizePublicUrl($item['url']) ?? $item['url'];
    return $item;
}, $mediaManifest['gallery'] ?? []);
$attachmentItems = $mediaManifest['attachments'] ?? [];

$page_title = $page_title ?? 'Editar campaña';
$page_description = 'Actualiza los datos de tu campaña para que el equipo pueda revisarla nuevamente.';
$current_page = 'my_campaigns';
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
    <script defer src="<?= APP_URL ?>/public/assets/js/app.js?v=2025020503"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <?php include VIEWS_PATH . '/layouts/partials/header.php'; ?>

    <main id="main-content" class="max-w-5xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <?php include_flash_messages(); ?>

        <div class="mb-8">
            <?= render_breadcrumb([
                ['name' => 'Inicio', 'href' => Router::url('/')],
                ['name' => 'Mi Panel', 'href' => Router::url('panel')],
                ['name' => 'Mis campañas', 'href' => Router::url('mis-campanas')],
                ['name' => 'Editar campaña', 'href' => Router::url('campana/' . $campaignId . '/editar')],
            ]); ?>

            <h1 class="mt-4 text-3xl font-bold text-gray-900">Editar campaña</h1>
            <p class="mt-2 text-sm text-gray-600">Ajusta la información presentada a tu comunidad y al equipo académico. Los cambios se revisarán antes de volver a publicar la campaña.</p>
        </div>

        <form method="POST" action="<?= Router::url('campana/' . $campaignId . '/editar') ?>" enctype="multipart/form-data" class="space-y-8" novalidate>
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">

            <section class="bg-white shadow-soft rounded-3xl p-6 space-y-6">
                <header class="space-y-2">
                    <h2 class="text-xl font-semibold text-gray-900">Detalles principales</h2>
                    <p class="text-sm text-gray-600">Esta información aparecerá en la ficha pública de tu campaña.</p>
                </header>
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Título de la campaña</label>
                        <input id="title" name="title" type="text" maxlength="160" required value="<?= htmlspecialchars($old['title'] ?? '') ?>" class="mt-1 w-full rounded-md border <?= isset($formErrors['title']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm">
                        <?php if (isset($formErrors['title'])): ?>
                            <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($formErrors['title']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="goal_amount" class="block text-sm font-medium text-gray-700">Meta económica (CLP)</label>
                        <input id="goal_amount" name="goal_amount" type="number" min="1000" step="500" required value="<?= htmlspecialchars($amountValue) ?>" class="mt-1 w-full rounded-md border <?= isset($formErrors['goal_amount']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm">
                        <?php if (isset($formErrors['goal_amount'])): ?>
                            <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($formErrors['goal_amount']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700">Categoría</label>
                        <select id="category" name="category" required class="mt-1 w-full rounded-md border <?= isset($formErrors['category']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm">
                            <option value="">Selecciona una categoría</option>
                            <?php foreach ($categoryOptions as $slug => $label): ?>
                                <option value="<?= htmlspecialchars($slug) ?>" <?= ($old['category'] ?? '') === $slug ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($formErrors['category'])): ?>
                            <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($formErrors['category']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700">Fecha de término</label>
                        <input id="end_date" name="end_date" type="date" required value="<?= htmlspecialchars($old['end_date'] ?? '') ?>" class="mt-1 w-full rounded-md border <?= isset($formErrors['end_date']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm">
                        <?php if (isset($formErrors['end_date'])): ?>
                            <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($formErrors['end_date']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <label for="short_description" class="block text-sm font-medium text-gray-700">Descripción breve</label>
                    <textarea id="short_description" name="short_description" rows="3" maxlength="400" required class="mt-1 w-full rounded-md border <?= isset($formErrors['short_description']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm"><?= htmlspecialchars($old['short_description'] ?? '') ?></textarea>
                    <?php if (isset($formErrors['short_description'])): ?>
                        <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($formErrors['short_description']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Historia completa</label>
                    <textarea id="description" name="description" rows="8" required class="mt-1 w-full rounded-md border <?= isset($formErrors['description']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm"><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
                    <?php if (isset($formErrors['description'])): ?>
                        <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($formErrors['description']) ?></p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="bg-white shadow-soft rounded-3xl p-6 space-y-6">
                <header class="space-y-2">
                    <h2 class="text-xl font-semibold text-gray-900">Beneficiarios y contacto</h2>
                    <p class="text-sm text-gray-600">Ayúdanos a validar la campaña proporcionando información clara del beneficiario.</p>
                </header>
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="beneficiary_name" class="block text-sm font-medium text-gray-700">Nombre del beneficiario</label>
                        <input id="beneficiary_name" name="beneficiary_name" type="text" required value="<?= htmlspecialchars($old['beneficiary_name'] ?? '') ?>" class="mt-1 w-full rounded-md border <?= isset($formErrors['beneficiary_name']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm">
                        <?php if (isset($formErrors['beneficiary_name'])): ?>
                            <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($formErrors['beneficiary_name']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="beneficiary_type" class="block text-sm font-medium text-gray-700">Tipo de beneficiario</label>
                        <select id="beneficiary_type" name="beneficiary_type" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500">
                            <option value="individual" <?= ($old['beneficiary_type'] ?? 'individual') === 'individual' ? 'selected' : '' ?>>Persona o familia</option>
                            <option value="organization" <?= ($old['beneficiary_type'] ?? 'individual') === 'organization' ? 'selected' : '' ?>>Organización</option>
                            <option value="community" <?= ($old['beneficiary_type'] ?? 'individual') === 'community' ? 'selected' : '' ?>>Comunidad</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label for="beneficiary_contact_text" class="block text-sm font-medium text-gray-700">Datos de contacto del beneficiario</label>
                    <textarea id="beneficiary_contact_text" name="beneficiary_contact_text" rows="3" placeholder="Teléfono, correo o datos de referencia" class="mt-1 w-full rounded-md border <?= isset($formErrors['beneficiary_contact']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-copihue-500 focus:ring-copihue-500' ?> px-3 py-2 text-sm"><?= htmlspecialchars($beneficiaryContactValue) ?></textarea>
                    <?php if (isset($formErrors['beneficiary_contact'])): ?>
                        <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($formErrors['beneficiary_contact']) ?></p>
                    <?php else: ?>
                        <p class="mt-1 text-xs text-gray-500">Esta información la revisará el equipo académico; no se mostrará públicamente.</p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700">Ciudad o comuna</label>
                    <input id="location" name="location" type="text" value="<?= htmlspecialchars($old['location'] ?? '') ?>" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500" placeholder="Ejemplo: Santiago, Región Metropolitana">
                </div>
            </section>

            <section class="bg-white shadow-soft rounded-3xl p-6 space-y-6">
                <header class="space-y-2">
                    <h2 class="text-xl font-semibold text-gray-900">Material de apoyo</h2>
                    <p class="text-sm text-gray-600">Actualiza la imagen principal o agrega evidencia adicional para reforzar la transparencia.</p>
                </header>
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="featured_image" class="block text-sm font-medium text-gray-700">Imagen principal</label>
                        <?php if ($coverPreview): ?>
                            <div class="mt-2 overflow-hidden rounded-lg border border-gray-200">
                                <img src="<?= htmlspecialchars($coverPreview) ?>" alt="Portada actual" class="h-40 w-full object-cover">
                            </div>
                        <?php endif; ?>
                        <input id="featured_image" name="featured_image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-3 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-copihue-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-copihue-700">
                        <?php if (isset($formErrors['featured_image'])): ?>
                            <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($formErrors['featured_image']) ?></p>
                        <?php else: ?>
                            <p class="mt-1 text-xs text-gray-500">Formatos permitidos: JPG, PNG o WEBP. Máximo 5 MB.</p>
                        <?php endif; ?>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-gray-700">Galería de imágenes</label>
                        <?php if (!empty($galleryItems)): ?>
                            <div class="grid grid-cols-3 gap-2">
                                <?php foreach ($galleryItems as $item): ?>
                                    <img src="<?= htmlspecialchars($item['url'] ?? '') ?>" alt="Imagen de galería" class="h-20 w-full rounded-md object-cover">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <input name="gallery_images[]" type="file" multiple accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-white file:px-4 file:py-2 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-100">
                        <?php if (isset($formErrors['gallery_images'])): ?>
                            <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($formErrors['gallery_images']) ?></p>
                        <?php else: ?>
                            <p class="mt-1 text-xs text-gray-500">Puedes agregar hasta 5 imágenes adicionales (máx. 6 MB cada una).</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Documentos de respaldo</label>
                    <?php if (!empty($attachmentItems)): ?>
                        <ul class="mt-2 space-y-1 text-sm text-gray-600">
                            <?php foreach ($attachmentItems as $attachment): ?>
                                <li class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10M7 11h10M7 15h6" />
                                    </svg>
                                    <span><?= htmlspecialchars($attachment['filename'] ?? basename($attachment['path'] ?? 'documento')) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <input name="supporting_files[]" type="file" multiple accept="application/pdf,image/jpeg,image/png,image/webp" class="mt-2 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-white file:px-4 file:py-2 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-100">
                    <?php if (isset($formErrors['supporting_files'])): ?>
                        <p class="mt-1 text-xs text-red-600"><?= htmlspecialchars($formErrors['supporting_files']) ?></p>
                    <?php else: ?>
                        <p class="mt-1 text-xs text-gray-500">PDF o imágenes (hasta 8 MB). Adjunta presupuestos, cotizaciones u otros respaldos.</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="video_url" class="block text-sm font-medium text-gray-700">Video de la campaña (opcional)</label>
                    <input id="video_url" name="video_url" type="url" value="<?= htmlspecialchars($old['video_url'] ?? '') ?>" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500" placeholder="https://www.youtube.com/watch?v=...">
                </div>

                <div class="flex items-center gap-2">
                    <input id="ai_generated" name="ai_generated" type="checkbox" value="1" <?= $isAiChecked ? 'checked' : '' ?> class="h-4 w-4 rounded border-gray-300 text-copihue-600 focus:ring-copihue-500">
                    <label for="ai_generated" class="text-sm text-gray-700">Marcar como campaña asistida por IA (para fines de transparencia interna)</label>
                </div>
            </section>

            <div class="flex items-center justify-between">
                <a href="<?= Router::url('mis-campanas') ?>" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:border-gray-400 hover:text-gray-900">Cancelar</a>
                <button type="submit" class="inline-flex items-center rounded-md bg-copihue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-copihue-700">
                    Guardar cambios
                </button>
            </div>
        </form>
    </main>

    <?php include VIEWS_PATH . '/layouts/partials/footer.php'; ?>
</body>
</html>
