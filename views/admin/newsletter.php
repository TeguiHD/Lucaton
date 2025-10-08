<?php
require_once __DIR__ . '/../components/forms.php';
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/alerts.php';
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$page_title = $page_title ?? 'Boletines y Newsletter';
$current_page = $current_page ?? 'admin-newsletter';
$templates = $templates ?? [];
$activeSubscribers = $activeSubscribers ?? 0;
$totalSubscribers = $totalSubscribers ?? 0;
$recentCampaigns = $recentCampaigns ?? [];
$old = $old ?? [];

if (empty($templates)) {
    $templates = [
        'general_update' => ['label' => 'Actualización general', 'description' => 'Resumen con varias novedades y eventos.'],
        'platform_update' => ['label' => 'Mejoras de plataforma', 'description' => 'Cuenta lo nuevo en Lucatón y los próximos pasos.'],
        'impact_story' => ['label' => 'Historia de impacto', 'description' => 'Destaca logros, agradecimientos y testimonios.']
    ];
}

$selectedTemplate = $old['template_key'] ?? array_key_first($templates);
if (!isset($templates[$selectedTemplate])) {
    $selectedTemplate = array_key_first($templates);
}

$subjectValue = trim($old['subject'] ?? '') !== '' ? trim($old['subject']) : 'Novedades de la comunidad Lucatón';
$messageValue = $old['message'] ?? '';
$ctaLabelValue = $old['cta_label'] ?? '';
$ctaUrlValue = $old['cta_url'] ?? '';

$lastCampaign = $recentCampaigns[0] ?? null;
$lastSentAt = $lastCampaign ? date('d/m/Y H:i', strtotime($lastCampaign['created_at'])) : '—';
$lastSubject = $lastCampaign['subject'] ?? '—';

?>

<?php ob_start(); ?>
<div class="space-y-8">
    <section class="rounded-3xl border border-gray-100 bg-white p-6 shadow-soft">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Boletines y newsletter</h1>
                <p class="text-sm text-gray-500">Comparte novedades con quienes se suscribieron desde el footer. Usa los templates para mantener un tono consistente.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4 text-center">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Suscriptores activos</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900"><?= number_format($activeSubscribers) ?></p>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4 text-center">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total registrados</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900"><?= number_format($totalSubscribers) ?></p>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4 text-center">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Último envío</p>
                    <p class="mt-2 text-sm font-semibold text-gray-900"><?= htmlspecialchars($lastSentAt) ?></p>
                    <p class="mt-1 text-xs text-gray-500 truncate" title="<?= htmlspecialchars($lastSubject) ?>"><?= htmlspecialchars($lastSubject) ?></p>
                </div>
            </div>
        </div>
    </section>

    <?php include_flash_messages(); ?>

    <div class="grid gap-6 xl:grid-cols-[2.2fr,1fr]">
        <section class="rounded-3xl border border-gray-100 bg-white p-6 shadow-soft">
            <form method="POST" action="<?= Router::url('admin/newsletter/enviar') ?>" class="space-y-6" id="newsletter-form">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="space-y-4">
                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700">Asunto</label>
                            <input type="text" id="subject" name="subject" required maxlength="180" value="<?= htmlspecialchars($subjectValue) ?>" class="form-input" placeholder="Novedades Lucatón: nuevas campañas y mejoras">
                        </div>

                        <div class="space-y-3">
                            <p class="text-sm font-medium text-gray-700">Template</p>
                            <div class="space-y-2">
                                <?php foreach ($templates as $key => $template): ?>
                                    <label class="flex items-start gap-3 rounded-2xl border <?= $selectedTemplate === $key ? 'border-copihue-200 bg-copihue-50' : 'border-gray-200 bg-white' ?> px-4 py-3 hover:border-copihue-200">
                                        <input type="radio" class="mt-1 h-4 w-4 text-copihue-600 focus:ring-copihue-500" name="template_key" value="<?= htmlspecialchars($key) ?>" <?= $selectedTemplate === $key ? 'checked' : '' ?> data-template-description="<?= htmlspecialchars($template['description'] ?? '') ?>" data-template-label="<?= htmlspecialchars($template['label'] ?? '') ?>">
                                        <span>
                                            <span class="block text-sm font-semibold text-gray-900"><?= htmlspecialchars($template['label'] ?? ucfirst($key)) ?></span>
                                            <span class="block text-xs text-gray-500 leading-relaxed"><?= htmlspecialchars($template['description'] ?? '') ?></span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div>
                            <label for="cta_label" class="block text-sm font-medium text-gray-700">Texto del botón (opcional)</label>
                            <input type="text" id="cta_label" name="cta_label" value="<?= htmlspecialchars($ctaLabelValue) ?>" maxlength="80" class="form-input" placeholder="Descubrir las nuevas campañas">
                        </div>

                        <div>
                            <label for="cta_url" class="block text-sm font-medium text-gray-700">Enlace del botón</label>
                            <input type="url" id="cta_url" name="cta_url" value="<?= htmlspecialchars($ctaUrlValue) ?>" class="form-input" placeholder="https://">
                            <p class="mt-1 text-xs text-gray-500">Si no incluyes un enlace, se omitirá el botón.</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label for="message" class="block text-sm font-medium text-gray-700">Mensaje</label>
                        <textarea id="message" name="message" rows="14" class="form-textarea" placeholder="Comparte tus novedades aquí..." required><?= htmlspecialchars($messageValue) ?></textarea>
                        <p class="text-xs text-gray-500">Usa párrafos cortos. Si dejas una línea en blanco se formateará como un nuevo párrafo.</p>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <a href="<?= Router::url('admin/notificaciones') ?>" class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:border-copihue-200 hover:text-copihue-700">Ir a notificaciones</a>
                    <?= render_button([
                        'text' => 'Enviar newsletter',
                        'type' => 'primary',
                        'form_type' => 'submit'
                    ]); ?>
                </div>
            </form>
        </section>

        <aside class="flex flex-col gap-6">
            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-soft">
                <header class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Vista previa</p>
                        <h2 class="text-lg font-semibold text-gray-900" id="preview-template-label"><?= htmlspecialchars($templates[$selectedTemplate]['label'] ?? 'Actualización') ?></h2>
                    </div>
                </header>
                <div class="mt-4 space-y-3 text-sm text-gray-600">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Asunto</p>
                        <p class="mt-1 text-sm font-medium text-gray-900" id="preview-subject"><?= htmlspecialchars($subjectValue) ?></p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Mensaje</p>
                        <div id="preview-message" class="mt-1 whitespace-pre-line leading-relaxed text-gray-600"><?= htmlspecialchars($messageValue !== '' ? $messageValue : 'Tu mensaje aparecerá aquí.') ?></div>
                    </div>
                    <div id="preview-cta" class="<?= ($ctaLabelValue && $ctaUrlValue) ? '' : 'hidden' ?>">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Botón</p>
                        <p class="mt-1 inline-flex items-center gap-2 rounded-full bg-copihue-100 px-3 py-1 text-sm font-medium text-copihue-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            <span id="preview-cta-label"><?= htmlspecialchars($ctaLabelValue !== '' ? $ctaLabelValue : 'Texto del botón') ?></span>
                        </p>
                        <p class="mt-1 text-xs text-gray-500" id="preview-cta-url"><?= htmlspecialchars($ctaUrlValue !== '' ? $ctaUrlValue : 'https://') ?></p>
                    </div>
                </div>
                <p class="mt-4 rounded-2xl bg-gray-50 px-4 py-3 text-xs text-gray-500" id="preview-template-description">
                    <?= htmlspecialchars($templates[$selectedTemplate]['description'] ?? '') ?>
                </p>
            </div>

            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-soft">
                <h2 class="text-lg font-semibold text-gray-900">Envíos recientes</h2>
                <?php if (empty($recentCampaigns)): ?>
                    <p class="mt-3 text-sm text-gray-500">Todavía no registras boletines. Cuando envíes uno aparecerá aquí.</p>
                <?php else: ?>
                    <ul class="mt-4 space-y-3">
                        <?php foreach ($recentCampaigns as $campaign): ?>
                            <li class="rounded-2xl border border-gray-100 bg-gray-50 px-4 py-3">
                                <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($campaign['subject']) ?></p>
                                <p class="text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($campaign['created_at'])) ?> · <?= htmlspecialchars($campaign['recipient_count']) ?> destinatarios</p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('newsletter-form');
    if (!form) return;

    const subjectInput = form.querySelector('#subject');
    const messageInput = form.querySelector('#message');
    const templateRadios = form.querySelectorAll('input[name="template_key"]');
    const ctaLabelInput = form.querySelector('#cta_label');
    const ctaUrlInput = form.querySelector('#cta_url');

    const previewSubject = document.getElementById('preview-subject');
    const previewMessage = document.getElementById('preview-message');
    const previewTemplateLabel = document.getElementById('preview-template-label');
    const previewTemplateDescription = document.getElementById('preview-template-description');
    const previewCta = document.getElementById('preview-cta');
    const previewCtaLabel = document.getElementById('preview-cta-label');
    const previewCtaUrl = document.getElementById('preview-cta-url');

    const updateSubject = () => {
        if (previewSubject) {
            previewSubject.textContent = subjectInput.value.trim() || 'Novedades de la comunidad Lucatón';
        }
    };

    const updateMessage = () => {
        if (!previewMessage) return;
        const text = messageInput.value.trim();
        previewMessage.textContent = text !== '' ? text : 'Tu mensaje aparecerá aquí.';
    };

    const updateTemplate = () => {
        templateRadios.forEach((radio) => {
            if (radio.checked) {
                if (previewTemplateLabel) {
                    previewTemplateLabel.textContent = radio.dataset.templateLabel || 'Actualización';
                }
                if (previewTemplateDescription) {
                    previewTemplateDescription.textContent = radio.dataset.templateDescription || '';
                }
            }
        });
    };

    const updateCta = () => {
        if (!previewCta) return;
        const label = ctaLabelInput.value.trim();
        const url = ctaUrlInput.value.trim();
        if (label !== '' && url !== '') {
            previewCta.classList.remove('hidden');
            previewCtaLabel.textContent = label;
            previewCtaUrl.textContent = url;
        } else {
            previewCta.classList.add('hidden');
        }
    };

    subjectInput.addEventListener('input', updateSubject);
    messageInput.addEventListener('input', updateMessage);
    templateRadios.forEach((radio) => radio.addEventListener('change', updateTemplate));
    ctaLabelInput.addEventListener('input', updateCta);
    ctaUrlInput.addEventListener('input', updateCta);

    updateSubject();
    updateMessage();
    updateTemplate();
    updateCta();
})();
</script>
<?php
$content = ob_get_clean();
include VIEWS_PATH . '/layouts/admin.php';
?>
