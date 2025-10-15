<?php
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/forms.php';
require_once __DIR__ . '/../components/alerts.php';
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

$current_page = $current_page ?? 'forgot-password';

$page_title = 'Recuperar Contraseña - Lucatón';
$page_description = 'Recupera el acceso a tu cuenta de Lucatón mediante tu correo electrónico.';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:title" content="<?php echo $page_title; ?>">
    <meta property="og:description" content="<?php echo $page_description; ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary">
    <meta property="twitter:url" content="<?php echo $_SERVER['REQUEST_URI']; ?>">
    <meta property="twitter:title" content="<?php echo $page_title; ?>">
    <meta property="twitter:description" content="<?php echo $page_description; ?>">

    <!-- Favicons -->
    <link rel="icon" type="image/svg+xml" href="<?= asset_url('images/favicon.svg') ?>">

    <!-- Styles -->
    <link href="<?= asset_url('css/app.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('css/aliases.css') ?>" rel="stylesheet">
    <style>[x-cloak]{display:none !important;}</style>
    
    <?php if (!empty($siteToastQueue)): ?>
    <script>
        window.__SITE_TOASTS__ = <?= json_encode($siteToastQueue, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;
    </script>
    <?php endif; ?>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="<?= asset_url('js/app.js') ?>"></script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <!-- Skip to content link -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <!-- Header -->
    <?php include __DIR__ . '/../layouts/partials/header.php'; ?>

    <!-- Main Content -->
    <main id="main-content" class="flex-1 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-copihue-100">
                    <svg class="h-6 w-6 text-copihue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <h1 class="mt-6 text-3xl font-extrabold text-gray-900">
                    Recuperar Contraseña
                </h1>
                <p class="mt-2 text-sm text-gray-600">
                    Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña
                </p>
            </div>

            <!-- Flash Messages -->
            <?php include_flash_messages(); ?>

            <!-- Forgot Password Form -->
            <div class="bg-white py-8 px-6 shadow rounded-lg" x-data="forgotPasswordForm()">
                <!-- Step 1: Email Input -->
                <div x-show="!emailSent">
                    <form
                        id="forgot-password-form"
                        method="POST"
                        action="<?= Router::url('recuperar') ?>"
                        @submit.prevent="submitForm()"
                        class="space-y-6"
                        novalidate
                    >
                        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">
                        <!-- Email Field -->
                        <div>
                            <?php echo render_text_input([
                                'name' => 'email',
                                'label' => 'Correo electrónico',
                                'type' => 'email',
                                'placeholder' => 'tu@email.com',
                                'required' => true,
                                'attributes' => [
                                    'x-model' => 'form.email',
                                    '@input' => 'clearGeneralError()',
                                    '@blur' => 'validateEmail()',
                                    ':class' => 'errors.email ? "border-red-300 focus:border-red-500 focus:ring-red-500" : ""'
                                ]
                            ]); ?>
                            <div x-show="errors.email" x-text="errors.email" class="mt-1 text-sm text-red-600"></div>
                        </div>

                        <!-- Submit Button -->
                        <div>
                            <?php echo render_button([
                                'text' => 'Enviar Enlace de Recuperación',
                                'type' => 'submit',
                                'variant' => 'primary',
                                'size' => 'lg',
                                'full_width' => true,
                                'attributes' => [
                                    ':disabled' => 'loading || !isFormValid()',
                                    ':class' => 'loading ? "opacity-75 cursor-not-allowed" : ""'
                                ]
                            ]); ?>
                            <div x-show="loading" x-cloak class="flex items-center justify-center mt-2">
                                <svg class="animate-spin h-5 w-5 text-copihue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="ml-2 text-sm text-gray-600">Enviando enlace...</span>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Step 2: Success Message -->
                <div x-show="emailSent" x-cloak class="text-center">
                    <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-green-100 mb-4">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <template x-if="successMessage">
                        <p class="text-sm text-gray-600 mb-4" x-text="successMessage"></p>
                    </template>
                    <template x-if="!successMessage">
                        <p class="text-sm text-gray-600 mb-4">
                            Hemos enviado un enlace de recuperación a <strong x-text="form.email"></strong>
                        </p>
                    </template>
                    <p class="text-sm text-gray-500 mb-6">
                        Revisa tu bandeja de entrada y sigue las instrucciones para restablecer tu contraseña. 
                        El enlace expirará en 1 hora.
                    </p>
                    
                    <!-- Resend Button -->
                    <div class="space-y-4">
                        <button
                            @click="resendEmail()"
                            :disabled="resendCooldown > 0"
                            class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span x-show="resendCooldown === 0" x-cloak>Reenviar enlace</span>
                            <span x-show="resendCooldown > 0" x-cloak>Reenviar en <span x-text="resendCooldown"></span>s</span>
                        </button>
                        
                        <button
                            @click="resetForm()"
                            class="w-full flex justify-center py-2 px-4 text-sm font-medium text-copihue-600 hover:text-copihue-500"
                        >
                            Usar otro correo electrónico
                        </button>
                    </div>

                    <template x-if="demoResetUrl">
                        <div class="mt-6 text-left text-sm text-gray-600 bg-gray-50 border border-dashed border-gray-300 rounded-md p-4">
                            <p class="font-medium text-gray-700 mb-1">Enlace de demostración:</p>
                            <p class="break-words"><a :href="demoResetUrl" class="text-copihue-600 underline" x-text="demoResetUrl"></a></p>
                        </div>
                    </template>
                </div>

                <!-- General Error -->
                <div x-show="errors.general" x-cloak class="mt-4">
                    <?php echo render_alert([
                        'type' => 'error',
                        'message' => '',
                        'attributes' => [
                            'x-text' => 'errors.general'
                        ]
                    ]); ?>
                </div>
            </div>

            <!-- Additional Links -->
            <div class="text-center space-y-4">
                <p class="text-sm text-gray-600">
                    ¿No tienes una cuenta?
                    <a href="<?= Router::url('registro') ?>" class="font-medium text-copihue-600 hover:text-copihue-500">
                        Regístrate gratis
                    </a>
                </p>
                
                <!-- Back to Login -->
                <div>
                    <a href="<?= Router::url('login') ?>" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver al inicio de sesión
                    </a>
                </div>
            </div>

            <!-- Help Section -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                            ¿Necesitas ayuda?
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Si no recibes el correo en unos minutos:</p>
                            <ul class="list-disc list-inside mt-1 space-y-1">
                                <li>Revisa tu carpeta de spam o correo no deseado</li>
                                <li>Verifica que el correo esté escrito correctamente</li>
                                <li>Contacta a nuestro <a href="<?= Router::url('support') ?>" class="underline hover:text-blue-600">soporte técnico</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include __DIR__ . '/../layouts/partials/footer.php'; ?>

    <script>
        function forgotPasswordForm() {
            return {
                form: {
                    email: ''
                },
                errors: {},
                loading: false,
                emailSent: false,
                resendCooldown: 0,
                resendIntervalId: null,
                successMessage: '',
                demoResetUrl: '',

                validateEmail() {
                    const email = this.form.email.trim();
                    this.form.email = email;
                    
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

                clearGeneralError() {
                    if (this.errors.general) {
                        delete this.errors.general;
                    }
                },

                getFormElement() {
                    return document.getElementById('forgot-password-form');
                },

                isFormValid() {
                    return this.form.email && Object.keys(this.errors).length === 0;
                },

                async submitForm() {
                    // Validate email
                    if (!this.validateEmail()) {
                        return;
                    }
                    
                    this.loading = true;
                    this.clearGeneralError();
                    
                    try {
                        const formElement = this.getFormElement();
                        const formData = formElement ? new FormData(formElement) : new FormData();
                        formData.set('email', this.form.email);
                        const endpoint = formElement && formElement.getAttribute('action') ? formElement.getAttribute('action') : '<?= Router::url('recuperar') ?>';

                        const response = await fetch(endpoint, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            this.emailSent = true;
                            this.errors = {};
                            this.startResendCooldown();

                            if (data.message) {
                                this.successMessage = data.message;
                            }

                            if (data.reset_url) {
                                this.demoResetUrl = data.reset_url;
                            }
                        } else {
                            if (data.errors) {
                                this.errors = data.errors;
                            } else {
                                this.errors.general = data.message || 'Error al enviar el enlace de recuperación';
                            }
                        }
                    } catch (error) {
                        console.error('Forgot password error:', error);
                        this.errors.general = 'Error de conexión. Inténtalo de nuevo.';
                    } finally {
                        this.loading = false;
                    }
                },

                async resendEmail() {
                    if (this.resendCooldown > 0) return;
                    
                    this.clearGeneralError();
                    this.loading = true;
                    
                    try {
                        const formElement = this.getFormElement();
                        const formData = formElement ? new FormData(formElement) : new FormData();
                        formData.set('email', this.form.email);
                        const endpoint = formElement && formElement.getAttribute('action') ? formElement.getAttribute('action') : '<?= Router::url('recuperar') ?>';

                        const response = await fetch(endpoint, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            this.startResendCooldown();
                            this.clearGeneralError();

                            if (data.message) {
                                this.successMessage = data.message;
                            }

                            if (data.reset_url) {
                                this.demoResetUrl = data.reset_url;
                            }
                        } else {
                            this.errors.general = data.message || 'Error al reenviar el enlace';
                        }
                    } catch (error) {
                        console.error('Resend error:', error);
                        this.errors.general = 'Error de conexión. Inténtalo de nuevo.';
                    } finally {
                        this.loading = false;
                    }
                },

                startResendCooldown() {
                    if (this.resendIntervalId) {
                        clearInterval(this.resendIntervalId);
                    }

                    this.resendCooldown = 60; // 60 seconds cooldown
                    this.resendIntervalId = setInterval(() => {
                        this.resendCooldown = Math.max(this.resendCooldown - 1, 0);

                        if (this.resendCooldown <= 0) {
                            clearInterval(this.resendIntervalId);
                            this.resendIntervalId = null;
                        }
                    }, 1000);
                },

                resetForm() {
                    this.emailSent = false;
                    this.form.email = '';
                    this.errors = {};
                    this.resendCooldown = 0;
                    this.clearGeneralError();
                    this.successMessage = '';
                    this.demoResetUrl = '';

                    if (this.resendIntervalId) {
                        clearInterval(this.resendIntervalId);
                        this.resendIntervalId = null;
                    }
                }
            }
        }

        // Auto-focus email input
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.querySelector('input[name="email"]');
            if (emailInput) {
                emailInput.focus();
            }
        });
    </script>
    <div class="site-toast-stack" data-site-toast-container></div>
</body>
</html>
