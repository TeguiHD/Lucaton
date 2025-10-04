<?php
require_once __DIR__ . '/../components/forms.php';
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/alerts.php';
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$categories = $categories ?? [];
$categoryOptions = array_filter($categories, static function ($key) {
    return $key !== '';
}, ARRAY_FILTER_USE_KEY);
$old = $_SESSION['old_campaign_form'] ?? [];
unset($_SESSION['old_campaign_form']);

$formErrors = $_SESSION['campaign_form_errors'] ?? [];
unset($_SESSION['campaign_form_errors']);

$draft_media = $draft_media ?? ['cover' => null, 'gallery' => [], 'attachments' => []];
$existing_cover_url = !empty($draft_media['cover']) ? CampaignMediaUploadService::normalizePublicUrl($draft_media['cover']) : null;
$existing_gallery_media = $draft_media['gallery'] ?? [];
$existing_attachments_media = $draft_media['attachments'] ?? [];

$page_title = $page_title ?? 'Crear nueva campaña';
$current_page = $current_page ?? 'create-campaign';

$goalAmountRaw = $old['goal_amount_input'] ?? '';
if ($goalAmountRaw === '' && !empty($old['goal_amount'])) {
    $goalAmountRaw = (string)$old['goal_amount'];
}
$goalAmountFormatted = $goalAmountRaw !== '' ? number_format((int)$goalAmountRaw, 0, ',', '.') : '';

$beneficiaryPhone = $old['beneficiary_phone'] ?? '';
$beneficiaryEmail = $old['beneficiary_email'] ?? '';

$wizard_steps = [
    1 => [
        'title' => 'Fundamentos',
        'summary' => 'Cuenta el propósito de tu campaña.'
    ],
    2 => [
        'title' => 'Meta y plazos',
        'summary' => 'Define montos y fechas realistas.'
    ],
    3 => [
        'title' => 'Beneficiarios',
        'summary' => 'Señala a quién apoyas y cómo validar.'
    ],
    4 => [
        'title' => 'Evidencias',
        'summary' => 'Sube una imagen y recursos de apoyo.'
    ],
];

$additional_head = ($additional_head ?? '') . <<<'HTML'
<style>
:root {
    --wizard-primary: #103c5d;
    --wizard-accent: #ee6352;
    --wizard-soft: #f3f5f9;
}
@keyframes fade-up {
    from { opacity: 0; transform: translate3d(0, 24px, 0); }
    to { opacity: 1; transform: translate3d(0, 0, 0); }
}
[data-animate="fade-up"] { animation: fade-up .55s ease-out both; }
[data-animate-delay="150"] { animation-delay: .15s; }
[data-animate-delay="300"] { animation-delay: .3s; }
[data-animate-delay="450"] { animation-delay: .45s; }

.wizard-step {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    border-radius: 1rem;
    border: 1px solid rgba(16, 60, 93, 0.18);
    background: rgba(255, 255, 255, 0.95);
    padding: 0.85rem 1rem;
    transition: all .25s ease;
    text-align: left;
}
.wizard-step:hover { border-color: rgba(16, 60, 93, 0.35); box-shadow: 0 8px 22px -14px rgba(16, 60, 93, .45); }
.wizard-step:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(238, 99, 82, 0.35); }
.wizard-step__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 999px;
    background: rgba(16, 60, 93, 0.12);
    color: var(--wizard-primary);
    font-weight: 600;
    transition: all .25s ease;
}
.wizard-step.is-active {
    border-color: rgba(238, 99, 82, 0.65);
    background: linear-gradient(135deg, rgba(238, 99, 82, 0.12), rgba(16, 60, 93, 0.05));
}
.wizard-step.is-complete {
    border-color: rgba(16, 60, 93, 0.28);
    background: rgba(16, 60, 93, 0.08);
}
.wizard-step.is-active .wizard-step__badge {
    background: var(--wizard-accent);
    color: #fff;
}
.wizard-step.is-complete .wizard-step__badge {
    background: var(--wizard-primary);
    color: #fff;
}
.wizard-panel { animation: fade-up .45s ease-out both; }
.wizard-panel.hidden { display: none; }
</style>
HTML;

$additional_scripts = ($additional_scripts ?? '') . <<<'HTML'
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-wizard-form]');
    if (!form) return;

    const panels = Array.from(form.querySelectorAll('[data-step]'));
    const totalSteps = panels.length;
    const currentInput = form.querySelector('[data-wizard-current]');
    const prevButton = form.querySelector('[data-wizard="prev"]');
    const nextButton = form.querySelector('[data-wizard="next"]');
    const submitButton = form.querySelector('[data-wizard="submit"]');
    const progressButtons = Array.from(document.querySelectorAll('[data-wizard-step]'));
    const summary = document.querySelector('[data-wizard-summary]');
    const indicator = document.querySelector('[data-wizard-indicator]');
    const anchor = document.querySelector('[data-wizard-anchor]');

    let current = parseInt(currentInput?.value || '1', 10) || 1;

    function scrollIntoView() {
        if (!anchor) return;
        const top = anchor.getBoundingClientRect().top + window.scrollY - 80;
        window.scrollTo({ top, behavior: 'smooth' });
    }

    function validateStep(stepNumber) {
        const panel = panels[stepNumber - 1];
        if (!panel) return true;
        const fields = Array.from(panel.querySelectorAll('input, textarea, select')).filter((el) => !el.disabled);
        for (const field of fields) {
            if (!field.checkValidity()) {
                field.reportValidity();
                return false;
            }
        }
        return true;
    }

    const fileInput = form.querySelector('[data-file-input]');
    const galleryInput = form.querySelector('[data-gallery-input]');
    const galleryList = form.querySelector('[data-gallery-list]');
    const attachmentInput = form.querySelector('[data-attachment-input]');
    const attachmentList = form.querySelector('[data-attachment-list]');

    let coverFile = null;
    let galleryFiles = [];
    let attachmentFiles = [];

    function restoreFileInputs() {
        restoreCoverFile();
        restoreMultipleFiles(galleryInput, galleryFiles, galleryList);
        restoreMultipleFiles(attachmentInput, attachmentFiles, attachmentList);
    }

    function updateUI() {
        panels.forEach((panel, index) => {
            if (index + 1 === current) {
                panel.classList.remove('hidden');
            } else {
                panel.classList.add('hidden');
            }
        });

        if (currentInput) currentInput.value = String(current);

        prevButton?.classList.toggle('hidden', current === 1);
        nextButton?.classList.toggle('hidden', current === totalSteps);
        submitButton?.classList.toggle('hidden', current !== totalSteps);

        progressButtons.forEach((btn, index) => {
            if (index + 1 === current) {
                btn.classList.add('is-active');
                btn.classList.remove('is-complete');
                btn.setAttribute('aria-current', 'step');
            } else if (index + 1 < current) {
                btn.classList.add('is-complete');
                btn.classList.remove('is-active');
                btn.removeAttribute('aria-current');
            } else {
                btn.classList.remove('is-active', 'is-complete');
                btn.removeAttribute('aria-current');
            }
        });

        const activePanel = panels[current - 1];
        if (summary && activePanel) {
            summary.textContent = activePanel.dataset.stepSummary || '';
        }
        if (indicator) {
            indicator.textContent = `Paso ${current} de ${totalSteps}`;
        }

        restoreFileInputs();
    }

    function moveToStep(target) {
        if (target < 1 || target > totalSteps || target === current) return;

        if (target > current) {
            for (let step = current; step < target; step += 1) {
                if (!validateStep(step)) {
                    updateUI();
                    return;
                }
                current = step + 1;
            }
        } else {
            current = target;
        }

        updateUI();
        scrollIntoView();
    }

    prevButton?.addEventListener('click', () => moveToStep(current - 1));
    nextButton?.addEventListener('click', () => {
        if (!validateStep(current)) return;
        moveToStep(current + 1);
    });
    progressButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = parseInt(btn.dataset.wizardStep || '0', 10);
            if (!Number.isNaN(target)) {
                moveToStep(target);
            }
        });
    });

    const currencyInput = form.querySelector('[data-currency-input]');
    const currencyHidden = form.querySelector('[data-currency-hidden]');
    const formatter = new Intl.NumberFormat('es-CL');

    function syncCurrency(digits) {
        if (!currencyInput || !currencyHidden) return;
        if (!digits) {
            currencyInput.value = '';
            currencyHidden.value = '';
            return;
        }
        const numeric = parseInt(digits, 10);
        currencyHidden.value = String(numeric);
        currencyInput.value = formatter.format(numeric);
    }

    currencyInput?.addEventListener('input', (event) => {
        const digits = event.target.value.replace(/[^0-9]/g, '');
        syncCurrency(digits);
    });

    currencyInput?.addEventListener('blur', () => {
        const digits = currencyHidden?.value || '';
        syncCurrency(digits);
    });

    if (currencyHidden && currencyHidden.value) {
        syncCurrency(currencyHidden.value);
    }

    form.addEventListener('submit', () => {
        restoreFileInputs();
        if (!currencyInput || !currencyHidden) return;
        const digits = currencyHidden.value || currencyInput.value.replace(/[^0-9]/g, '');
        syncCurrency(digits);
    });

    const fileTrigger = form.querySelector('[data-file-trigger]');
    const filePreview = form.querySelector('[data-file-preview]');
    const filePreviewImg = form.querySelector('[data-file-preview-img]');
    const filePreviewName = form.querySelector('[data-file-preview-name]');
    const fileReset = form.querySelector('[data-file-reset]');

    const existingCoverContainer = form.querySelector('[data-existing-cover]');
    const existingCoverInput = form.querySelector('[data-existing-cover-input]');
    const removeExistingCoverInput = form.querySelector('[data-remove-existing-cover]');
    const existingCoverRemove = form.querySelector('[data-existing-cover-remove]');

    const existingGalleryList = form.querySelector('[data-existing-gallery-list]');
    const existingAttachmentList = form.querySelector('[data-existing-attachment-list]');

    const GALLERY_MAX_FILES = 5;
    const ATTACHMENT_MAX_FILES = 5;

    let coverPreviewUrl = null;
    const coverState = {
        initialValue: existingCoverInput?.value || '',
        currentValue: existingCoverInput?.value || '',
        explicitlyRemoved: false,
    };

    const galleryRemovalMap = new Map();
    const attachmentRemovalMap = new Map();

    const supportsDataTransfer = (() => {
        if (typeof DataTransfer === 'undefined') {
            return false;
        }
        try {
            new DataTransfer();
            return true;
        } catch (error) {
            return false;
        }
    })();

    function ensureRemovalInput(name, value, cache) {
        if (!value) return null;
        if (cache.has(value)) {
            return cache.get(value);
        }
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        input.setAttribute('data-dynamic-remove', name);
        form.appendChild(input);
        cache.set(value, input);
        return input;
    }

    function updateExistingListVisibility(listEl, selector) {
        if (!listEl) return;
        const hasItems = listEl.querySelector(selector) !== null;
        listEl.classList.toggle('hidden', !hasItems);
    }

    function setRemoveExistingCoverFlag(value) {
        if (!removeExistingCoverInput) return;
        removeExistingCoverInput.value = value ? '1' : '0';
    }

    function setExistingCoverValue(value) {
        if (existingCoverInput) {
            existingCoverInput.value = value;
        }
        coverState.currentValue = value;
    }

    function hideExistingCover(options = {}) {
        const { explicit = false } = options;
        if (existingCoverContainer) {
            existingCoverContainer.classList.add('hidden');
        }
        setExistingCoverValue('');

        if (explicit) {
            coverState.explicitlyRemoved = true;
            setRemoveExistingCoverFlag(true);
            return;
        }

        if (coverState.explicitlyRemoved) {
            setRemoveExistingCoverFlag(true);
            return;
        }

        if (coverState.initialValue) {
            setRemoveExistingCoverFlag(true);
        } else {
            setRemoveExistingCoverFlag(false);
        }
    }

    function restoreExistingCoverIfAvailable() {
        if (!coverState.initialValue) {
            if (!coverState.explicitlyRemoved) {
                setRemoveExistingCoverFlag(false);
            }
            return;
        }

        if (existingCoverContainer) {
            existingCoverContainer.classList.remove('hidden');
        }
        setExistingCoverValue(coverState.initialValue);
        coverState.explicitlyRemoved = false;
        setRemoveExistingCoverFlag(false);
    }

    function getExistingGalleryCount() {
        if (!existingGalleryList) {
            return 0;
        }
        return existingGalleryList.querySelectorAll('[data-existing-gallery-item]').length;
    }

    function getExistingAttachmentCount() {
        if (!existingAttachmentList) {
            return 0;
        }
        return existingAttachmentList.querySelectorAll('[data-existing-attachment-item]').length;
    }

    function assignFilesToInput(inputEl, files) {
        if (!inputEl) {
            return;
        }
        if (files.length === 0) {
            inputEl.value = '';
            return;
        }
        if (!supportsDataTransfer) {
            return;
        }
        const dt = new DataTransfer();
        files.forEach((file) => dt.items.add(file));
        inputEl.files = dt.files;
    }

    function revokeCoverPreview() {
        if (coverPreviewUrl) {
            URL.revokeObjectURL(coverPreviewUrl);
            coverPreviewUrl = null;
        }
    }

    function updateCoverPreview() {
        if (!filePreview || !filePreviewImg || !filePreviewName) {
            return;
        }

        if (coverFile) {
            revokeCoverPreview();
            coverPreviewUrl = URL.createObjectURL(coverFile);
            filePreviewImg.src = coverPreviewUrl;
            filePreviewName.textContent = coverFile.name;
            filePreview.classList.remove('hidden');
        } else {
            filePreview.classList.add('hidden');
            filePreviewImg.src = '';
            filePreviewName.textContent = '';
        }
    }

    function restoreCoverFile() {
        if (!coverFile || !fileInput || (fileInput.files && fileInput.files.length > 0)) {
            updateCoverPreview();
            return;
        }

        if (!supportsDataTransfer) {
            updateCoverPreview();
            return;
        }

        const dt = new DataTransfer();
        dt.items.add(coverFile);
        fileInput.files = dt.files;
        updateCoverPreview();
    }

    function renderFileList(inputEl, listEl) {
        if (!listEl) return;
        const files = Array.from(inputEl?.files || []);
        if (files.length === 0) {
            listEl.classList.add('hidden');
            listEl.innerHTML = '';
            return;
        }

        const formatSize = (bytes) => {
            if (bytes >= 1_048_576) {
                return (bytes / 1_048_576).toFixed(1) + ' MB';
            }
            return (bytes / 1024).toFixed(1) + ' KB';
        };

        listEl.innerHTML = '';
        files.forEach((file) => {
            const item = document.createElement('li');
            item.textContent = `${file.name} — ${formatSize(file.size)}`;
            listEl.appendChild(item);
        });
        listEl.classList.remove('hidden');
    }

    function restoreMultipleFiles(inputEl, storedFiles, listEl) {
        if (!inputEl || storedFiles.length === 0 || (inputEl.files && inputEl.files.length > 0)) {
            renderFileList(inputEl, listEl);
            return;
        }
        assignFilesToInput(inputEl, storedFiles);
        renderFileList(inputEl, listEl);
    }

    fileTrigger?.addEventListener('click', () => fileInput?.click());

    fileInput?.addEventListener('change', () => {
        if (fileInput.files && fileInput.files[0]) {
            coverFile = fileInput.files[0];
            hideExistingCover();
        } else {
            coverFile = null;
            if (!coverState.explicitlyRemoved) {
                restoreExistingCoverIfAvailable();
            }
        }
        updateCoverPreview();
    });

    existingCoverRemove?.addEventListener('click', (event) => {
        event.preventDefault();
        coverFile = null;
        if (fileInput) {
            fileInput.value = '';
        }
        hideExistingCover({ explicit: true });
        revokeCoverPreview();
        updateCoverPreview();
    });

    fileReset?.addEventListener('click', (event) => {
        event.preventDefault();
        coverFile = null;
        if (fileInput) {
            fileInput.value = '';
        }
        revokeCoverPreview();
        updateCoverPreview();
        if (coverState.explicitlyRemoved) {
            setRemoveExistingCoverFlag(true);
        } else {
            restoreExistingCoverIfAvailable();
        }
    });

    function wireExistingGalleryItems() {
        if (!existingGalleryList) return;
        existingGalleryList.querySelectorAll('[data-existing-gallery-item]').forEach((item) => {
            const removeBtn = item.querySelector('[data-existing-gallery-remove]');
            if (!removeBtn) return;
            removeBtn.addEventListener('click', (event) => {
                event.preventDefault();
                const url = item.dataset.galleryUrl || '';
                item.remove();
                updateExistingListVisibility(existingGalleryList, '[data-existing-gallery-item]');
                if (url) {
                    ensureRemovalInput('remove_gallery[]', url, galleryRemovalMap);
                }
            });
        });
    }

    function wireExistingAttachmentItems() {
        if (!existingAttachmentList) return;
        existingAttachmentList.querySelectorAll('[data-existing-attachment-item]').forEach((item) => {
            const removeBtn = item.querySelector('[data-existing-attachment-remove]');
            if (!removeBtn) return;
            removeBtn.addEventListener('click', (event) => {
                event.preventDefault();
                const path = item.dataset.attachmentPath || '';
                item.remove();
                updateExistingListVisibility(existingAttachmentList, '[data-existing-attachment-item]');
                if (path) {
                    ensureRemovalInput('remove_attachments[]', path, attachmentRemovalMap);
                }
            });
        });
    }

    wireExistingGalleryItems();
    wireExistingAttachmentItems();

    galleryInput?.addEventListener('change', () => {
        const existingCount = getExistingGalleryCount();
        const availableSlots = Math.max(0, GALLERY_MAX_FILES - existingCount);
        galleryFiles = Array.from(galleryInput.files || []);

        if (availableSlots <= 0) {
            if (galleryFiles.length > 0) {
                alert('Ya alcanzaste el máximo de 5 imágenes en la galería. Elimina alguna existente para subir otra.');
            }
            galleryFiles = [];
            if (galleryInput) {
                galleryInput.value = '';
            }
            renderFileList(galleryInput, galleryList);
            return;
        }

        if (galleryFiles.length > availableSlots) {
            const message = availableSlots === 1
                ? 'Solo puedes agregar 1 imagen adicional para completar la galería.'
                : `Puedes agregar hasta ${availableSlots} imágenes adicionales (máximo total de ${GALLERY_MAX_FILES}).`;
            alert(message);
            galleryFiles = galleryFiles.slice(0, availableSlots);
            if (supportsDataTransfer) {
                assignFilesToInput(galleryInput, galleryFiles);
            } else if (galleryInput) {
                galleryInput.value = '';
            }
        } else if (supportsDataTransfer) {
            assignFilesToInput(galleryInput, galleryFiles);
        }

        if (!supportsDataTransfer && galleryInput && galleryInput.value === '' && galleryFiles.length > 0) {
            // No podemos re-asignar archivos sin DataTransfer, así que vaciamos selección para evitar inconsistencias.
            galleryFiles = [];
        }

        renderFileList(galleryInput, galleryList);
    });

    attachmentInput?.addEventListener('change', () => {
        const existingCount = getExistingAttachmentCount();
        const availableSlots = Math.max(0, ATTACHMENT_MAX_FILES - existingCount);
        attachmentFiles = Array.from(attachmentInput.files || []);

        if (availableSlots <= 0) {
            if (attachmentFiles.length > 0) {
                alert('Ya tienes 5 documentos de respaldo cargados. Elimina alguno para agregar uno nuevo.');
            }
            attachmentFiles = [];
            if (attachmentInput) {
                attachmentInput.value = '';
            }
            renderFileList(attachmentInput, attachmentList);
            return;
        }

        if (attachmentFiles.length > availableSlots) {
            const message = availableSlots === 1
                ? 'Solo puedes agregar 1 documento adicional para llegar al máximo permitido.'
                : `Puedes agregar hasta ${availableSlots} documentos adicionales (máximo total de ${ATTACHMENT_MAX_FILES}).`;
            alert(message);
            attachmentFiles = attachmentFiles.slice(0, availableSlots);
            if (supportsDataTransfer) {
                assignFilesToInput(attachmentInput, attachmentFiles);
            } else if (attachmentInput) {
                attachmentInput.value = '';
            }
        } else if (supportsDataTransfer) {
            assignFilesToInput(attachmentInput, attachmentFiles);
        }

        if (!supportsDataTransfer && attachmentInput && attachmentInput.value === '' && attachmentFiles.length > 0) {
            attachmentFiles = [];
        }

        renderFileList(attachmentInput, attachmentList);
    });

    updateExistingListVisibility(existingGalleryList, '[data-existing-gallery-item]');
    updateExistingListVisibility(existingAttachmentList, '[data-existing-attachment-item]');

    updateUI();
});
</script>

HTML;
?>

<?php ob_start(); ?>
<div class="relative isolate overflow-hidden bg-[var(--wizard-soft)] pb-16">
    <section class="relative z-10 mx-auto max-w-4xl px-4 pt-10 sm:px-6 lg:px-8" data-wizard-anchor>
        <div class="rounded-3xl border border-white/60 bg-white/95 p-8 shadow-xl shadow-slate-900/10" data-animate="fade-up">
            <span class="inline-flex items-center gap-2 rounded-full bg-[rgba(16,60,93,0.08)] px-4 py-1 text-xs font-semibold uppercase tracking-wide text-[var(--wizard-primary)]">
                Crear campaña
            </span>
            <h1 class="mt-4 text-3xl font-bold text-[var(--wizard-primary)] sm:text-4xl">
                Lanza tu campaña en cuatro pasos guiados
            </h1>
            <p class="mt-3 max-w-2xl text-sm text-gray-600 sm:text-base">
                Completa cada sección, guarda sin perder avances y deja que el equipo académico revise tus antecedentes antes de publicarla.
            </p>
            <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                <li class="rounded-2xl border border-gray-100 bg-white/90 px-4 py-3 text-sm text-gray-700">Formulario mobile-first: avanza o retrocede sin perder datos.</li>
                <li class="rounded-2xl border border-gray-100 bg-white/90 px-4 py-3 text-sm text-gray-700">Sube tu imagen principal directamente y agrega datos privados para verificación.</li>
            </ul>
        </div>

        <div class="mt-10 space-y-3" data-animate="fade-up" data-animate-delay="150">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--wizard-primary)]" data-wizard-indicator>Paso 1 de 4</span>
                <p class="text-sm text-gray-600" data-wizard-summary><?= htmlspecialchars($wizard_steps[1]['summary']) ?></p>
            </div>
            <ol class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <?php foreach ($wizard_steps as $index => $data): ?>
                    <li>
                        <button type="button"
                                class="wizard-step<?= $index === 1 ? ' is-active' : '' ?>"
                                data-wizard-step="<?= $index ?>"<?= $index === 1 ? ' aria-current="step"' : '' ?>>
                            <span class="wizard-step__badge"><?= $index ?></span>
                            <span class="flex flex-col">
                                <span class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($data['title']) ?></span>
                                <span class="text-xs text-gray-500"><?= htmlspecialchars($data['summary']) ?></span>
                            </span>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </section>

    <section class="relative z-10 mx-auto mt-10 max-w-5xl px-4 sm:px-6 lg:px-8" data-animate="fade-up" data-animate-delay="300">
        <form method="POST" action="<?= Router::url('campana/crear') ?>" class="space-y-10" data-wizard-form enctype="multipart/form-data">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">
            <input type="hidden" name="campaign_step" value="1" data-wizard-current>

            <?php include_flash_messages(); ?>

            <section class="wizard-panel rounded-3xl border border-gray-100 bg-white/95 p-6 shadow-xl shadow-gray-900/5 backdrop-blur-sm sm:p-8"
                     data-step="1"
                     data-step-summary="<?= htmlspecialchars($wizard_steps[1]['summary']) ?>">
                <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Fundamentos de la campaña</h2>
                        <p class="mt-1 text-sm text-gray-600">Explica el problema, la causa y el impacto que buscas.</p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-[rgba(238,99,82,0.12)] px-3 py-1 text-xs font-semibold uppercase tracking-wide text-[var(--wizard-accent)]">Paso 1</span>
                </header>
                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <div class="lg:col-span-2">
                        <?php echo render_text_input([
                            'name' => 'title',
                            'label' => 'Título de la campaña',
                            'required' => true,
                            'placeholder' => 'Ej: Reconstrucción de escuela rural en Alto Biobío',
                            'value' => htmlspecialchars($old['title'] ?? ''),
                            'error' => $formErrors['title'] ?? ''
                        ]); ?>
                    </div>
                    <div class="lg:col-span-2">
                        <?php echo render_textarea([
                            'name' => 'short_description',
                            'label' => 'Descripción breve',
                            'required' => true,
                            'rows' => 3,
                            'placeholder' => 'Resume la necesidad y a quién beneficia.',
                            'value' => htmlspecialchars($old['short_description'] ?? ''),
                            'error' => $formErrors['short_description'] ?? ''
                        ]); ?>
                    </div>
                    <div class="lg:col-span-2">
                        <?php echo render_textarea([
                            'name' => 'description',
                            'label' => 'Historia completa',
                            'required' => true,
                            'rows' => 8,
                            'placeholder' => 'Describe el contexto, los aliados y cómo se usará cada aporte.',
                            'value' => htmlspecialchars($old['description'] ?? ''),
                            'error' => $formErrors['description'] ?? ''
                        ]); ?>
                    </div>
                </div>
            </section>

            <section class="wizard-panel hidden rounded-3xl border border-gray-100 bg-white/95 p-6 shadow-xl shadow-gray-900/5 backdrop-blur-sm sm:p-8"
                     data-step="2"
                     data-step-summary="<?= htmlspecialchars($wizard_steps[2]['summary']) ?>">
                <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Meta y plazos</h2>
                        <p class="mt-1 text-sm text-gray-600">Define cuánto necesitas y hasta cuándo reunir aportes.</p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-[rgba(16,60,93,0.12)] px-3 py-1 text-xs font-semibold uppercase tracking-wide text-[var(--wizard-primary)]">Paso 2</span>
                </header>
                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="goal_amount_display" class="block text-sm font-medium text-gray-700">Meta económica (CLP) <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <input type="text"
                                   id="goal_amount_display"
                                   class="form-input block w-full rounded-md border-gray-300 shadow-sm focus:border-copihue-500 focus:ring-copihue-500 sm:text-sm"
                                   inputmode="numeric"
                                   placeholder="Ej: 120.000"
                                   data-currency-input
                                   value="<?= htmlspecialchars($goalAmountFormatted) ?>"
                                   required>
                            <input type="hidden" name="goal_amount" data-currency-hidden value="<?= htmlspecialchars($goalAmountRaw) ?>">
                        </div>
                        <?php if (!empty($formErrors['goal_amount'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($formErrors['goal_amount']) ?></p>
                        <?php endif; ?>
                        <p class="mt-1 text-xs text-gray-500">Aplicaremos comisión 0% durante la revisión académica.</p>
                    </div>
                    <div>
                        <?php echo render_text_input([
                            'type' => 'date',
                            'name' => 'end_date',
                            'label' => 'Fecha de término',
                            'required' => true,
                            'value' => htmlspecialchars($old['end_date'] ?? ''),
                            'error' => $formErrors['end_date'] ?? '',
                            'attributes' => ['min' => date('Y-m-d', strtotime('+1 day'))]
                        ]); ?>
                    </div>
                    <div>
                        <?php echo render_select([
                            'name' => 'category',
                            'label' => 'Categoría',
                            'options' => $categoryOptions,
                            'required' => true,
                            'value' => $old['category'] ?? '',
                            'error' => $formErrors['category'] ?? ''
                        ]); ?>
                    </div>
                    <div>
                        <?php echo render_text_input([
                            'name' => 'location',
                            'label' => 'Ubicación beneficiada',
                            'placeholder' => 'Ciudad o región',
                            'value' => htmlspecialchars($old['location'] ?? ''),
                            'error' => $formErrors['location'] ?? ''
                        ]); ?>
                    </div>
                </div>
            </section>

            <section class="wizard-panel hidden rounded-3xl border border-gray-100 bg-white/95 p-6 shadow-xl shadow-gray-900/5 backdrop-blur-sm sm:p-8"
                     data-step="3"
                     data-step-summary="<?= htmlspecialchars($wizard_steps[3]['summary']) ?>">
                <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Beneficiarios</h2>
                        <p class="mt-1 text-sm text-gray-600">Estos datos son privados y nos permiten auditar la campaña.</p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-[rgba(238,99,82,0.12)] px-3 py-1 text-xs font-semibold uppercase tracking-wide text-[var(--wizard-accent)]">Paso 3</span>
                </header>
                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    <div>
                        <?php echo render_select([
                            'name' => 'beneficiary_type',
                            'label' => 'Tipo de beneficiario',
                            'options' => [
                                ['value' => 'individual', 'label' => 'Persona'],
                                ['value' => 'organization', 'label' => 'Organización'],
                                ['value' => 'community', 'label' => 'Comunidad']
                            ],
                            'value' => $old['beneficiary_type'] ?? 'individual',
                            'error' => $formErrors['beneficiary_type'] ?? ''
                        ]); ?>
                    </div>
                    <div>
                        <?php echo render_text_input([
                            'name' => 'beneficiary_name',
                            'label' => 'Nombre del beneficiario',
                            'required' => true,
                            'placeholder' => 'Nombre completo o institucional',
                            'value' => htmlspecialchars($old['beneficiary_name'] ?? ''),
                            'error' => $formErrors['beneficiary_name'] ?? ''
                        ]); ?>
                    </div>
                    <div>
                        <?php echo render_text_input([
                            'name' => 'beneficiary_phone',
                            'label' => 'Teléfono del beneficiario (privado)',
                            'placeholder' => '+56 9 1234 5678',
                            'value' => htmlspecialchars($beneficiaryPhone),
                            'error' => $formErrors['beneficiary_phone'] ?? '',
                            'attributes' => ['inputmode' => 'tel', 'autocomplete' => 'tel']
                        ]); ?>
                    </div>
                    <div>
                        <?php echo render_text_input([
                            'name' => 'beneficiary_email',
                            'label' => 'Correo del beneficiario (privado)',
                            'placeholder' => 'contacto@ejemplo.cl',
                            'value' => htmlspecialchars($beneficiaryEmail),
                            'error' => $formErrors['beneficiary_email'] ?? '',
                            'attributes' => ['autocomplete' => 'email']
                        ]); ?>
                    </div>
                </div>
                <?php if (!empty($formErrors['beneficiary_contact'])): ?>
                    <p class="mt-2 text-sm text-red-600"><?= htmlspecialchars($formErrors['beneficiary_contact']) ?></p>
                <?php else: ?>
                    <p class="mt-2 text-xs text-gray-500">Los datos anteriores no se publican; el equipo del proyecto los usa para verificación.</p>
                <?php endif; ?>
            </section>

            <section class="wizard-panel hidden rounded-3xl border border-gray-100 bg-white/95 p-6 shadow-xl shadow-gray-900/5 backdrop-blur-sm sm:p-8"
                     data-step="4"
                     data-step-summary="<?= htmlspecialchars($wizard_steps[4]['summary']) ?>">
                <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Evidencias y transparencia</h2>
                        <p class="mt-1 text-sm text-gray-600">Sube una imagen principal y agrega recursos opcionales.</p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-[rgba(16,60,93,0.12)] px-3 py-1 text-xs font-semibold uppercase tracking-wide text-[var(--wizard-primary)]">Paso 4</span>
                </header>
                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    <div class="md:col-span-2 space-y-4">
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-gray-700" for="featured_image">Imagen principal <span class="text-red-500">*</span></label>
                        </div>
                        <?php if ($existing_cover_url): ?>
                            <div class="rounded-2xl border border-gray-200 bg-white/90 p-3 text-left" data-existing-cover>
                                <img src="<?= htmlspecialchars($existing_cover_url) ?>" alt="Portada actual" class="h-40 w-full rounded-xl object-cover">
                                <div class="mt-3 flex items-center justify-between text-xs text-gray-600">
                                    <span><?= htmlspecialchars(basename($draft_media['cover'])) ?></span>
                                    <button type="button" class="text-[var(--wizard-accent)] hover:underline" data-existing-cover-remove>Quitar portada</button>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="rounded-2xl border-2 border-dashed border-gray-300 bg-white/70 p-4 text-center">
                            <input type="file" name="featured_image" id="featured_image" accept="image/jpeg,image/png,image/webp" class="sr-only" data-file-input>
                            <div class="flex flex-col items-center gap-2">
                                <button type="button" class="btn-primary px-4 py-2 text-sm" data-file-trigger>Seleccionar imagen</button>
                                <p class="text-xs text-gray-500">Formatos permitidos: JPG, PNG o WebP. Máximo 5&nbsp;MB.</p>
                            </div>
                        </div>
                        <?php if (!empty($formErrors['featured_image'])): ?>
                            <p class="text-sm text-red-600"><?= htmlspecialchars($formErrors['featured_image']) ?></p>
                        <?php endif; ?>
                        <figure class="hidden rounded-2xl border border-gray-200 bg-white/80 p-3 text-left" data-file-preview>
                            <img src="" alt="Vista previa de la imagen" class="h-32 w-full rounded-lg object-cover" data-file-preview-img>
                            <figcaption class="mt-2 flex items-center justify-between text-xs text-gray-600">
                                <span data-file-preview-name></span>
                                <button class="text-[var(--wizard-accent)] hover:underline" data-file-reset>Quitar</button>
                            </figcaption>
                        </figure>
                        <input type="hidden" name="featured_image_existing" value="<?= htmlspecialchars($draft_media['cover'] ?? '') ?>" data-existing-cover-input>
                        <input type="hidden" name="remove_existing_cover" value="0" data-remove-existing-cover>
                    </div>

                    <div class="md:col-span-2 space-y-3">
                        <label class="block text-sm font-medium text-gray-700" for="gallery_images">Galería (opcional)</label>
                        <ul data-existing-gallery-list class="space-y-2 text-xs text-gray-600 <?= empty($existing_gallery_media) ? 'hidden' : '' ?>">
                            <?php foreach ($existing_gallery_media as $galleryItem): ?>
                                <?php $galleryUrl = CampaignMediaUploadService::normalizePublicUrl($galleryItem['url']); ?>
                                <li class="flex items-center justify-between rounded-xl border border-gray-200 bg-white px-3 py-2" data-existing-gallery-item data-gallery-url="<?= htmlspecialchars($galleryItem['url']) ?>">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-gray-700"><?= htmlspecialchars($galleryItem['filename'] ?? basename($galleryItem['url'])) ?></span>
                                        <span class="text-[11px] text-gray-500"><?= number_format((int)($galleryItem['size'] ?? 0) / 1024, 1) ?> KB</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="<?= htmlspecialchars($galleryUrl) ?>" target="_blank" rel="noopener" class="text-xs text-copihue-600 hover:underline">Ver</a>
                                        <button type="button" class="text-xs text-red-600 hover:underline" data-existing-gallery-remove>Quitar</button>
                                    </div>
                                    <input type="hidden" name="existing_gallery[]" value="<?= htmlspecialchars($galleryItem['url']) ?>" data-existing-gallery-value>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <input type="file" name="gallery_images[]" id="gallery_images" accept="image/jpeg,image/png,image/webp" multiple data-gallery-input class="block w-full text-sm text-gray-600">
                        <p class="text-xs text-gray-500">Puedes agregar hasta cinco imágenes complementarias para mostrar contexto o avances iniciales.</p>
                        <?php if (!empty($formErrors['gallery_images'])): ?>
                            <p class="text-sm text-red-600"><?= htmlspecialchars($formErrors['gallery_images']) ?></p>
                        <?php endif; ?>
                        <ul data-gallery-list class="hidden space-y-1 text-xs text-gray-600"></ul>
                    </div>

                    <div class="md:col-span-2 space-y-3">
                        <label class="block text-sm font-medium text-gray-700" for="supporting_files">Documentos de respaldo (privados)</label>
                        <ul data-existing-attachment-list class="space-y-2 text-xs text-gray-600 <?= empty($existing_attachments_media) ? 'hidden' : '' ?>">
                            <?php foreach ($existing_attachments_media as $attachment): ?>
                                <li class="flex items-center justify-between rounded-xl border border-gray-200 bg-white px-3 py-2" data-existing-attachment-item data-attachment-path="<?= htmlspecialchars($attachment['path']) ?>">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-gray-700"><?= htmlspecialchars($attachment['filename'] ?? basename($attachment['path'])) ?></span>
                                        <span class="text-[11px] text-gray-500"><?= number_format((int)($attachment['size'] ?? 0) / 1024, 1) ?> KB — <?= htmlspecialchars($attachment['mime'] ?? 'archivo') ?></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-400">Privado</span>
                                        <button type="button" class="text-xs text-red-600 hover:underline" data-existing-attachment-remove>Quitar</button>
                                    </div>
                                    <input type="hidden" name="existing_attachments[]" value="<?= htmlspecialchars($attachment['path']) ?>" data-existing-attachment-value>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <input type="file" name="supporting_files[]" id="supporting_files" accept="application/pdf,image/jpeg,image/png,image/webp" multiple data-attachment-input class="block w-full text-sm text-gray-600">
                        <p class="text-xs text-gray-500">Adjunta hasta cinco presupuestos, cotizaciones u otros respaldos en PDF o imagen. Estos archivos solo los revisa el equipo académico.</p>
                        <?php if (!empty($formErrors['supporting_files'])): ?>
                            <p class="text-sm text-red-600"><?= htmlspecialchars($formErrors['supporting_files']) ?></p>
                        <?php endif; ?>
                        <ul data-attachment-list class="hidden space-y-1 text-xs text-gray-600"></ul>
                    </div>

                    <div>
                        <?php echo render_text_input([
                            'name' => 'video_url',
                            'label' => 'Video de apoyo (opcional)',
                            'placeholder' => 'https://www.youtube.com/watch?v=...',
                            'value' => htmlspecialchars($old['video_url'] ?? ''),
                            'error' => $formErrors['video_url'] ?? ''
                        ]); ?>
                    </div>
                </div>
                <label class="mt-5 inline-flex items-center gap-3 rounded-2xl border border-dashed border-[var(--wizard-primary)] bg-[rgba(16,60,93,0.05)] px-4 py-3 text-sm text-[var(--wizard-primary)]">
                    <input type="checkbox" name="ai_generated" value="1" <?= !empty($old['ai_generated']) ? 'checked' : '' ?> class="rounded border-[var(--wizard-primary)] text-[var(--wizard-accent)] focus:ring-[var(--wizard-accent)]">
                    <span>Parte del contenido fue asistido por IA y está documentado en actualizaciones.</span>
                </label>

            </section>

            <div class="sticky bottom-4 left-0 right-0 z-40" data-animate="fade-up" data-animate-delay="450">
                <div class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white/95 p-4 shadow-2xl shadow-gray-900/10 backdrop-blur sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3 text-sm text-gray-600">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[rgba(16,60,93,0.1)] text-[var(--wizard-primary)]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                            </svg>
                        </div>
                        <p class="text-sm">Puedes revisar y editar cada paso antes de enviar a revisión.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <?= render_button([
                            'text' => 'Cancelar',
                            'type' => 'ghost',
                            'size' => 'sm',
                            'href' => Router::url('panel')
                        ]) ?>
                        <?= render_button([
                            'text' => 'Paso anterior',
                            'type' => 'secondary',
                            'size' => 'sm',
                            'form_type' => 'button',
                            'attributes' => ['data-wizard' => 'prev']
                        ]) ?>
                        <?= render_button([
                            'text' => 'Siguiente paso',
                            'type' => 'primary',
                            'size' => 'sm',
                            'form_type' => 'button',
                            'attributes' => ['data-wizard' => 'next']
                        ]) ?>
                        <?= render_button([
                            'text' => 'Guardar campaña',
                            'type' => 'primary',
                            'size' => 'sm',
                            'attributes' => ['data-wizard' => 'submit'],
                            'form_type' => 'submit'
                        ]) ?>
                    </div>
                </div>
            </div>
        </form>

        <details class="mt-10 rounded-3xl border border-gray-200 bg-white/95 p-6 shadow-xl shadow-gray-900/5 backdrop-blur-sm" data-animate="fade-up" data-animate-delay="450">
            <summary class="cursor-pointer text-sm font-semibold text-[var(--wizard-primary)]">Checklist de revisión y tips</summary>
            <div class="mt-4 grid gap-6 sm:grid-cols-2">
                <ul class="space-y-3 text-sm text-gray-700">
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex h-6 w-6 items-center justify-center rounded-full bg-[rgba(238,99,82,0.15)] text-[var(--wizard-accent)]">1</span>
                        Tu historia incluye hitos verificables y datos locales.
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex h-6 w-6 items-center justify-center rounded-full bg-[rgba(238,99,82,0.15)] text-[var(--wizard-accent)]">2</span>
                        Adjuntaste referencias de costos (cotizaciones, presupuestos, boletas previas).
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 flex h-6 w-6 items-center justify-center rounded-full bg-[rgba(238,99,82,0.15)] text-[var(--wizard-accent)]">3</span>
                        El beneficiario confirmó que conoce la campaña y autoriza su publicación.
                    </li>
                </ul>
                <div class="space-y-3 text-sm text-gray-700">
                    <p>¿Dudas? Escríbenos a <a class="font-semibold text-[var(--wizard-primary)] underline decoration-[var(--wizard-accent)] decoration-2 underline-offset-4" href="mailto:<?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?>"><?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?></a>. <?= htmlspecialchars(PROJECT_DISCLAIMER) ?></p>
                    <a class="inline-flex items-center gap-2 text-[var(--wizard-primary)] hover:text-[var(--wizard-accent)]" href="<?= Router::url('ayuda') ?>">
                        Revisar guías del centro de ayuda
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </details>
    </section>
</div>
<?php
$content = ob_get_clean();
include VIEWS_PATH . '/layouts/main.php';
?>
