<?php
require_once __DIR__ . '/../components/forms.php';
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/alerts.php';
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$categories = $categories ?? [];
$categoryOptions = array_filter($categories, function ($key) {
    return $key !== '';
}, ARRAY_FILTER_USE_KEY);
$old = $_SESSION['old_campaign_form'] ?? [];
unset($_SESSION['old_campaign_form']);

$page_title = $page_title ?? 'Crear nueva campaña';
?>

<?php ob_start(); ?>
<div class="max-w-4xl mx-auto">
    <header class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($page_title); ?></h1>
        <p class="mt-2 text-sm text-gray-600">Completa la información base de tu campaña. Podrás adjuntar evidencias y mejorarla luego.</p>
    </header>

    <?php include_flash_messages(); ?>

    <form method="POST" action="<?= Router::url('campana/crear') ?>" class="space-y-8">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">

        <section class="bg-white shadow-soft rounded-3xl p-6 space-y-6">
            <h2 class="text-xl font-semibold text-gray-900">Información principal</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <?php echo render_text_input([
                        'name' => 'title',
                        'label' => 'Título de la campaña',
                        'placeholder' => 'Ej: Reconstrucción de escuela rural en Alto Biobío',
                        'required' => true,
                        'value' => htmlspecialchars($old['title'] ?? '')
                    ]); ?>
                </div>
                <div class="md:col-span-2">
                    <?php echo render_textarea([
                        'name' => 'short_description',
                        'label' => 'Descripción breve',
                        'placeholder' => 'Resume en pocas líneas el propósito de tu campaña.',
                        'required' => true,
                        'rows' => 3,
                        'value' => htmlspecialchars($old['short_description'] ?? '')
                    ]); ?>
                </div>
                <div class="md:col-span-2">
                    <?php echo render_textarea([
                        'name' => 'description',
                        'label' => 'Historia completa',
                        'placeholder' => 'Describe la situación, la necesidad y el impacto esperado.',
                        'required' => true,
                        'rows' => 8,
                        'value' => htmlspecialchars($old['description'] ?? '')
                    ]); ?>
                </div>
            </div>
        </section>

        <section class="bg-white shadow-soft rounded-3xl p-6 space-y-6">
            <h2 class="text-xl font-semibold text-gray-900">Meta y duración</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <?php echo render_text_input([
                        'name' => 'goal_amount',
                        'label' => 'Meta económica (CLP)',
                        'type' => 'number',
                        'min' => '1000',
                        'step' => '1000',
                        'required' => true,
                        'value' => htmlspecialchars($old['goal_amount'] ?? '')
                    ]); ?>
                </div>
                <div>
                    <?php echo render_text_input([
                        'name' => 'end_date',
                        'label' => 'Fecha de término',
                        'type' => 'date',
                        'required' => true,
                        'value' => htmlspecialchars($old['end_date'] ?? '')
                    ]); ?>
                </div>
                <div>
                    <?php echo render_select([
                        'name' => 'category',
                        'label' => 'Categoría',
                        'options' => $categoryOptions,
                        'required' => true,
                        'value' => $old['category'] ?? '',
                        'placeholder' => 'Selecciona una categoría'
                    ]); ?>
                </div>
                <div>
                    <?php echo render_text_input([
                        'name' => 'location',
                        'label' => 'Ubicación',
                        'placeholder' => 'Ciudad o región beneficiada',
                        'value' => htmlspecialchars($old['location'] ?? '')
                    ]); ?>
                </div>
            </div>
        </section>

        <section class="bg-white shadow-soft rounded-3xl p-6 space-y-6">
            <h2 class="text-xl font-semibold text-gray-900">Beneficiario</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <?php echo render_select([
                        'name' => 'beneficiary_type',
                       'label' => 'Tipo de beneficiario',
                       'options' => [
                           ['value' => 'individual', 'label' => 'Persona'],
                           ['value' => 'organization', 'label' => 'Organización'],
                           ['value' => 'community', 'label' => 'Comunidad']
                       ],
                        'value' => $old['beneficiary_type'] ?? 'individual'
                   ]); ?>
               </div>
                <div>
                    <?php echo render_text_input([
                        'name' => 'beneficiary_name',
                        'label' => 'Nombre del beneficiario',
                        'placeholder' => 'Nombre completo o nombre de la organización',
                        'value' => htmlspecialchars($old['beneficiary_name'] ?? '')
                    ]); ?>
                </div>
                <div class="md:col-span-2">
                    <?php echo render_text_input([
                        'name' => 'beneficiary_contact',
                        'label' => 'Contacto del beneficiario',
                        'placeholder' => 'Correo o teléfono de referencia',
                        'value' => htmlspecialchars($old['beneficiary_contact'] ?? '')
                    ]); ?>
                </div>
            </div>
        </section>

        <section class="bg-white shadow-soft rounded-3xl p-6 space-y-6">
            <h2 class="text-xl font-semibold text-gray-900">Multimedia</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <?php echo render_text_input([
                        'name' => 'featured_image_url',
                        'label' => 'Imagen destacada (URL)',
                        'placeholder' => 'https://...jpg',
                        'value' => htmlspecialchars($old['featured_image_url'] ?? '')
                    ]); ?>
                </div>
                <div>
                    <?php echo render_text_input([
                        'name' => 'video_url',
                        'label' => 'Video de apoyo (URL opcional)',
                        'placeholder' => 'https://www.youtube.com/watch?v=...',
                        'value' => htmlspecialchars($old['video_url'] ?? '')
                    ]); ?>
                </div>
            </div>
            <label class="inline-flex items-center space-x-2 text-sm text-gray-600">
                <input type="checkbox" name="ai_generated" value="1" <?php echo !empty($old['ai_generated']) ? 'checked' : ''; ?> class="rounded border-gray-300 text-copihue-600 focus:ring-copihue-500">
                <span>Parte del contenido fue generado con asistencia de IA</span>
            </label>
        </section>

        <div class="flex items-center justify-end space-x-3">
            <?php echo render_button([
                'text' => 'Cancelar',
                'href' => Router::url('panel'),
                'type' => 'secondary'
            ]); ?>
            <?php echo render_button([
                'text' => 'Guardar campaña',
                'type' => 'primary',
                'form_type' => 'submit'
            ]); ?>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
include VIEWS_PATH . '/layouts/main.php';
?>
