<?php
require_once __DIR__ . '/../components/navigation.php';
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$current_page = $current_page ?? 'report_issue';

$page_title = 'Reportar un problema - Lucatón';
$page_description = 'Informa errores técnicos, pagos pendientes o contenidos sospechosos en Lucatón.';

$issue_types = [
    'tecnico' => 'Error técnico o pantalla en blanco',
    'pagos' => 'Problema con donaciones o cobros',
    'contenido' => 'Campaña o comentario inapropiado',
    'seguridad' => 'Sospecha de fraude o seguridad',
    'otro' => 'Otro motivo'
];

$severities = [
    'alta' => 'Alta: impide usar la plataforma',
    'media' => 'Media: afecta parcialmente',
    'baja' => 'Baja: es un detalle o sugerencia'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">

    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= APP_URL . $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_description) ?>">

    <meta property="twitter:card" content="summary">
    <meta property="twitter:url" content="<?= APP_URL . $_SERVER['REQUEST_URI'] ?>">
    <meta property="twitter:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="twitter:description" content="<?= htmlspecialchars($page_description) ?>">

    <link rel="icon" type="image/svg+xml" href="<?= asset_url('images/favicon.svg') ?>">
    <link href="<?= asset_url('css/app.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('css/aliases.css') ?>" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <?php include __DIR__ . '/../layouts/partials/header.php'; ?>

    <main id="main-content" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <?= render_breadcrumb([
            ['name' => 'Inicio', 'href' => Router::url('/')],
            ['name' => 'Reportar un problema', 'href' => Router::url('reportar')],
        ]); ?>

        <?php include_flash_messages(); ?>

        <div class="grid gap-10 lg:grid-cols-3">
            <section class="lg:col-span-2 bg-white shadow rounded-lg p-8">
                <header class="mb-6">
                    <p class="text-sm uppercase tracking-wide text-gray-500">Ayúdanos a mejorar</p>
                    <h1 class="mt-2 text-3xl font-bold text-gray-900">Reportar un problema</h1>
                    <p class="mt-2 text-sm text-gray-600">Completa el formulario con el máximo de detalle posible. Nuestro equipo revisará tu reporte y te contactará si requiere más información.</p>
                    <p class="mt-2 text-xs text-gray-500"><?= htmlspecialchars(PROJECT_OWNER_NAME) ?>. <?= htmlspecialchars(PROJECT_DISCLAIMER) ?></p>
                </header>

                <div data-report-feedback class="hidden rounded-md border px-4 py-3 text-sm" role="alert" tabindex="-1">
                    <div class="flex items-start gap-3">
                        <span data-report-icon class="mt-0.5 inline-flex h-5 w-5 flex-none items-center justify-center"></span>
                        <p data-report-message class="text-sm leading-6"></p>
                    </div>
                </div>

                <form id="report-issue-form"
                      action="<?= Router::url('reportar') ?>"
                      method="post"
                      class="space-y-6"
                      data-report-form
                      novalidate>
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="report-name">Nombre</label>
                            <input id="report-name" name="name" type="text" placeholder="Tu nombre" required class="mt-1 w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500">
                            <p data-report-error="name" class="mt-1 text-xs text-red-600 hidden"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="report-email">Correo electrónico</label>
                            <input id="report-email" name="email" type="email" placeholder="tu@correo.cl" required class="mt-1 w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500">
                            <p data-report-error="email" class="mt-1 text-xs text-red-600 hidden"></p>
                        </div>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="report-type">Tipo de problema</label>
                            <select id="report-type" name="type" required class="mt-1 w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500">
                                <option value="">Selecciona una opción</option>
                                <?php foreach ($issue_types as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p data-report-error="type" class="mt-1 text-xs text-red-600 hidden"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="report-severity">Impacto</label>
                            <select id="report-severity" name="severity" required class="mt-1 w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500">
                                <option value="">Selecciona el impacto</option>
                                <?php foreach ($severities as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p data-report-error="severity" class="mt-1 text-xs text-red-600 hidden"></p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="report-url">Enlace (opcional)</label>
                        <input id="report-url" name="url" type="url" placeholder="<?= htmlspecialchars(APP_URL) ?>/campana/tuUsuario/tu-campana" class="mt-1 w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500">
                        <p data-report-error="url" class="mt-1 text-xs text-red-600 hidden"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="report-description">Describe lo ocurrido</label>
                        <textarea id="report-description" name="description" rows="5" required class="mt-1 w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:border-copihue-500 focus:ring-copihue-500" placeholder="Cuéntanos qué pasó, qué estabas haciendo y si probaste alguna solución"></textarea>
                        <p class="mt-2 text-xs text-gray-500">Incluye capturas o códigos de error si los tienes. Puedes adjuntarlos respondiendo el correo de confirmación.</p>
                        <p data-report-error="description" class="mt-1 text-xs text-red-600 hidden"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="report-consent">Consentimiento</label>
                        <div class="mt-2 flex items-start gap-3 text-sm text-gray-600">
                            <input id="report-consent" name="consent" type="checkbox" required class="mt-1 h-4 w-4 rounded border-gray-300 text-copihue-600 focus:ring-copihue-500">
                            <p>Autorizo a Lucatón a utilizar la información enviada para investigar y resolver el problema, según la <a class="text-copihue-600 font-medium" href="<?= Router::url('privacidad') ?>">Política de Privacidad</a>.</p>
                        </div>
                        <p data-report-error="consent" class="mt-1 text-xs text-red-600 hidden"></p>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <button type="submit" data-report-submit class="inline-flex items-center justify-center rounded-md bg-copihue-600 px-4 py-2 text-sm font-medium text-white hover:bg-copihue-700 focus:outline-none focus:ring-2 focus:ring-copihue-500 focus:ring-offset-2 transition-colors">
                            <span data-report-submit-label>Enviar reporte</span>
                        </button>
                        <p class="text-xs text-gray-500">Tiempo de respuesta estimado: menos de 4 horas hábiles.</p>
                    </div>
                </form>
            </section>

            <aside class="space-y-6">
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900">Otras vías de contacto</h2>
                    <ul class="mt-4 space-y-3 text-sm text-gray-600">
                        <li>
                            <strong>Correo:</strong> <a class="text-copihue-600 font-medium" href="mailto:<?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?>"><?= htmlspecialchars(PROJECT_OWNER_EMAIL) ?></a>
                        </li>
                        <li>
                            <strong>Teléfono referencial (demo):</strong> +56 9 8765 4321 (lunes a viernes, 09:00-18:00)
                        </li>
                        <li>
                            <strong>Chat en vivo:</strong> disponible desde el panel de usuario en horario laboral.
                        </li>
                    </ul>
                </div>
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900">Casos más reportados</h2>
                    <ul class="mt-4 space-y-3 text-sm text-gray-600 list-disc pl-5">
                        <li>Confirmaciones de pago demoradas (pasarela externas)</li>
                        <li>Restablecimiento de contraseña no llega al correo</li>
                        <li>Campaña sospechosa o con información incompleta</li>
                    </ul>
                    <p class="mt-3 text-sm text-gray-600">Si tu problema coincide con alguno de estos casos, revisa el <a class="text-copihue-600 font-medium" href="<?= Router::url('ayuda') ?>">Centro de ayuda</a> para soluciones rápidas.</p>
                </div>
            </aside>
        </div>
    </main>

    <?php include __DIR__ . '/../layouts/partials/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('[data-report-form]');
        if (!form) {
            return;
        }

        const feedback = document.querySelector('[data-report-feedback]');
        const feedbackMessage = feedback ? feedback.querySelector('[data-report-message]') : null;
        const feedbackIcon = feedback ? feedback.querySelector('[data-report-icon]') : null;
        const submitButton = form.querySelector('[data-report-submit]');
        const submitLabel = form.querySelector('[data-report-submit-label]');
        const originalLabel = submitLabel ? submitLabel.textContent : '';

        const iconTemplates = {
            success: '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>',
            error: '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
        };

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            clearFieldErrors();

            if (!submitButton || !submitLabel) {
                return;
            }

            submitButton.disabled = true;
            submitButton.classList.add('opacity-70', 'cursor-not-allowed');
            submitLabel.textContent = 'Enviando...';

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });

                const data = await response.json().catch(function () {
                    return {};
                });

                if (!response.ok || data.success === false) {
                    handleFieldErrors(data);
                    const message = extractMessage(data) || 'No pudimos enviar el reporte. Intenta nuevamente.';
                    renderFeedback('error', message);
                    return;
                }

                renderFeedback('success', data.message || 'Tu reporte fue enviado correctamente.');
                form.reset();
            } catch (error) {
                renderFeedback('error', 'Ocurrió un error inesperado. Inténtalo en unos minutos.');
            } finally {
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-70', 'cursor-not-allowed');
                submitLabel.textContent = originalLabel;
            }
        });

        function extractMessage(payload) {
            if (!payload) {
                return '';
            }

            if (typeof payload.error === 'string') {
                return payload.error;
            }

            if (typeof payload.message === 'string') {
                return payload.message;
            }

            if (payload.errors) {
                const firstError = Object.values(payload.errors)[0];
                if (typeof firstError === 'string') {
                    return firstError;
                }
            }

            return '';
        }

        function handleFieldErrors(payload) {
            if (!payload || !payload.errors) {
                return;
            }

            Object.entries(payload.errors).forEach(function ([field, message]) {
                const errorTarget = form.querySelector('[data-report-error="' + field + '"]');
                if (!errorTarget) {
                    return;
                }
                errorTarget.textContent = message;
                errorTarget.classList.remove('hidden');
            });

            const firstField = Object.keys(payload.errors)[0];
            if (firstField) {
                const fieldElement = form.querySelector('[name="' + firstField + '"]');
                if (fieldElement && typeof fieldElement.focus === 'function') {
                    fieldElement.focus();
                }
            }
        }

        function clearFieldErrors() {
            form.querySelectorAll('[data-report-error]').forEach(function (element) {
                element.textContent = '';
                element.classList.add('hidden');
            });
        }

        function renderFeedback(status, message) {
            if (!feedback || !feedbackMessage) {
                return;
            }

            feedback.classList.remove('hidden', 'bg-red-50', 'border-red-200', 'text-red-700', 'bg-emerald-50', 'border-emerald-200', 'text-emerald-700');
            feedbackMessage.textContent = message;

            if (status === 'success') {
                feedback.classList.add('bg-emerald-50', 'border-emerald-200', 'text-emerald-700');
                if (feedbackIcon) {
                    feedbackIcon.innerHTML = iconTemplates.success;
                }
            } else {
                feedback.classList.add('bg-red-50', 'border-red-200', 'text-red-700');
                if (feedbackIcon) {
                    feedbackIcon.innerHTML = iconTemplates.error;
                }
            }

            setTimeout(function () {
                feedback.focus({ preventScroll: true });
            }, 50);
        }
    });
    </script>
</body>
</html>
