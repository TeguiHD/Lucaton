<?php
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/forms.php';
require_once __DIR__ . '/../components/alerts.php';
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$current_page = 'login';

$serverErrors = $_SESSION['validation_errors']['login'] ?? [];
$oldInput = $_SESSION['old_input']['login'] ?? [];
unset($_SESSION['validation_errors']['login'], $_SESSION['old_input']['login']);

$page_title = 'Iniciar Sesión - Lucatón';
$page_description = 'Inicia sesión en tu cuenta de Lucatón para gestionar tus campañas y donaciones.';

$initialForm = json_encode($oldInput, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);
$initialErrors = json_encode($serverErrors, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_description) ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
    <meta property="twitter:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="twitter:description" content="<?= htmlspecialchars($page_description) ?>">

    <!-- Favicons -->
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/public/assets/images/favicon.svg">

    <!-- Styles -->
    <link href="<?= APP_URL ?>/public/assets/css/app.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/public/assets/css/aliases.css" rel="stylesheet">

    <style>
        [x-cloak] { display: none !important; }
    </style>

    <script>
        window.__LOGIN_INITIAL_FORM__ = <?= $initialForm ?> || {};
        window.__LOGIN_INITIAL_ERRORS__ = <?= $initialErrors ?> || {};
    </script>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="<?= APP_URL ?>/public/assets/js/app.js?v=2025012801"></script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <?php include __DIR__ . '/../layouts/partials/header.php'; ?>

    <main id="main-content" class="flex-1 py-12 sm:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-lg mx-auto">
                <div class="bg-white shadow-soft rounded-2xl p-8 sm:p-10 space-y-6">
                    <div class="text-center space-y-2">
                        <h1 class="text-3xl font-semibold text-gray-900">Iniciar sesión</h1>
                    </div>

                    <?php include_flash_messages(); ?>

                    <form id="login-form"
                          x-data="loginForm()"
                          @submit.prevent="submitForm()"
                          method="POST"
                          action="<?= Router::url('login') ?>"
                          class="space-y-6"
                          novalidate>
                        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">

                        <?php $generalError = $serverErrors['general'] ?? null; ?>
                        <div x-show="errors.general"
                             <?= $generalError ? 'style="display:block;"' : 'x-cloak' ?>
                             x-text="errors.general"
                             class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                             role="alert" aria-live="assertive">
                            <?php if ($generalError): ?>
                                <?= htmlspecialchars($generalError) ?>
                            <?php endif; ?>
                        </div>

                        <div class="space-y-1">
                            <label for="email" class="block text-sm font-medium text-gray-700">
                                Correo electrónico <span class="text-red-500">*</span>
                            </label>
                            <input id="email" name="email" type="email" autocomplete="email" required
                                   placeholder="tu@email.com"
                                   value="<?= htmlspecialchars($oldInput['email'] ?? '') ?>"
                                   class="form-input w-full rounded-md border-gray-300 shadow-sm focus:border-copihue-500 focus:ring-copihue-500 sm:text-sm"
                                   :class="errors.email ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : ''"
                                   x-model="form.email"
                                   @blur="validateEmail()">
                            <?php $emailError = $serverErrors['email'] ?? null; ?>
                            <div x-show="errors.email"
                                 <?= $emailError ? 'style="display:block;"' : 'x-cloak' ?>
                                 x-text="errors.email"
                                 class="mt-1 text-sm text-red-600">
                                <?php if ($emailError): ?>
                                    <?= htmlspecialchars($emailError) ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                Contraseña <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input id="password" name="password" type="password" autocomplete="current-password" required
                                       placeholder="••••••••"
                                       class="form-input w-full rounded-md border-gray-300 shadow-sm focus:border-copihue-500 focus:ring-copihue-500 sm:text-sm pr-12"
                                       :class="errors.password ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : ''"
                                       x-model="form.password"
                                       @blur="validatePassword()">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <button type="button"
                                            class="inline-flex items-center justify-center rounded-md p-1 text-gray-500 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-copihue-500 bg-white bg-opacity-75"
                                            data-password-toggle="#password"
                                            data-password-label-hidden="Mostrar contraseña"
                                            data-password-label-visible="Ocultar contraseña"
                                            aria-label="Mostrar contraseña"
                                            title="Mostrar contraseña"
                                            aria-pressed="false">
                                        <svg data-password-icon="hidden" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg data-password-icon="visible" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <?php $passwordError = $serverErrors['password'] ?? null; ?>
                            <div x-show="errors.password"
                                 <?= $passwordError ? 'style="display:block;"' : 'x-cloak' ?>
                                 x-text="errors.password"
                                 class="mt-1 text-sm text-red-600">
                                <?php if ($passwordError): ?>
                                    <?= htmlspecialchars($passwordError) ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-sm">
                            <div class="flex items-center">
                                <?php echo render_checkbox([
                                    'name' => 'remember',
                                    'label' => 'Recordarme',
                                    'checked' => ($oldInput['remember'] ?? '') === '1' || ($oldInput['remember'] ?? '') === 1,
                                    'attributes' => ['x-model' => 'form.remember'],
                                    'wrapper_class' => 'mb-0'
                                ]); ?>
                            </div>
                            <a href="<?= Router::url('recuperar') ?>" class="font-medium text-copihue-600 hover:text-copihue-500">
                                ¿Olvidaste tu contraseña?
                            </a>
                        </div>

                        <div class="space-y-3">
                            <?php echo render_button([
                                'text' => 'Iniciar Sesión',
                                'type' => 'primary',
                                'size' => 'lg',
                                'full_width' => true,
                                'form_type' => 'submit',
                                'attributes' => [
                                    ':disabled' => 'loading || !isFormValid()',
                                    ':class' => 'loading ? "opacity-75 cursor-not-allowed" : ""'
                                ]
                            ]); ?>
                            <div x-show="loading" class="flex items-center justify-center text-sm text-gray-500" x-cloak>
                                <svg class="animate-spin h-5 w-5 text-copihue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="ml-2">Validando credenciales...</span>
                            </div>
                        </div>

                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-200"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">O continúa con</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <?php echo render_button([
                                'text' => 'Google',
                                'type' => 'button',
                                'full_width' => true,
                                'attributes' => [
                                    'disabled' => 'disabled',
                                    'aria-disabled' => 'true',
                                    'title' => 'Integración próximamente',
                                    'class' => 'border border-gray-300 text-gray-500 bg-white hover:bg-gray-50'
                                ]
                            ]); ?>
                            <?php echo render_button([
                                'text' => 'Facebook',
                                'type' => 'button',
                                'full_width' => true,
                                'attributes' => [
                                    'disabled' => 'disabled',
                                    'aria-disabled' => 'true',
                                    'title' => 'Integración próximamente',
                                    'class' => 'border border-gray-300 text-gray-500 bg-white hover:bg-gray-50'
                                ]
                            ]); ?>
                        </div>

                        <div class="pt-4 border-t border-gray-100 text-center text-sm text-gray-600">
                            ¿No tienes una cuenta?
                            <a href="<?= Router::url('registro') ?>" class="font-medium text-copihue-600 hover:text-copihue-500">Regístrate gratis</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../layouts/partials/footer.php'; ?>

    <?php $panelRedirectUrl = Router::url('panel'); ?>
    <script>
        const PANEL_REDIRECT_URL = <?= json_encode($panelRedirectUrl, JSON_UNESCAPED_SLASHES) ?>;

        function loginForm() {
            const initialForm = window.__LOGIN_INITIAL_FORM__ || {};
            const initialErrors = window.__LOGIN_INITIAL_ERRORS__ || {};
            return {
                form: {
                    email: initialForm.email ?? '',
                    password: '',
                    remember: initialForm.remember === '1' || initialForm.remember === true
                },
                errors: { ...initialErrors },
                loading: false,
                clearGeneralError() {
                    if (this.errors.general) {
                        delete this.errors.general;
                    }
                },
                validateEmail() {
                    const email = (this.form.email || '').trim();
                    if (!email) {
                        this.errors.email = 'El correo electrónico es requerido';
                        return false;
                    }
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        this.errors.email = 'Ingresa un correo electrónico válido';
                        return false;
                    }
                    delete this.errors.email;
                    return true;
                },
                validatePassword() {
                    const password = this.form.password || '';
                    if (!password) {
                        this.errors.password = 'La contraseña es requerida';
                        return false;
                    }
                    if (password.length < 6) {
                        this.errors.password = 'La contraseña debe tener al menos 6 caracteres';
                        return false;
                    }
                    delete this.errors.password;
                    return true;
                },
                isFormValid() {
                    return this.form.email && this.form.password && Object.keys(this.errors).length === 0;
                },
                async submitForm() {
                    this.clearGeneralError();
                    const emailValid = this.validateEmail();
                    const passwordValid = this.validatePassword();
                    if (!emailValid || !passwordValid) {
                        return;
                    }
                    this.loading = true;
                    try {
                        const formElement = document.getElementById('login-form');
                        const formData = new FormData(formElement);
                        formData.set('remember', this.form.remember ? '1' : '0');
                        const response = await fetch(formElement.getAttribute('action'), {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const data = await response.json();
                        if (data.success) {
                            window.location.href = data.redirect || PANEL_REDIRECT_URL;
                        } else if (data.errors) {
                            this.errors = data.errors;
                        } else {
                            this.errors.general = data.message || 'Error al iniciar sesión';
                        }
                    } catch (error) {
                        console.error('Login error:', error);
                        this.errors.general = 'Error de conexión. Inténtalo de nuevo.';
                    } finally {
                        this.loading = false;
                    }
                }
            };
        }
    </script>
</body>
</html>
