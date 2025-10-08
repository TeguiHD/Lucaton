<?php
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/forms.php';
require_once __DIR__ . '/../components/alerts.php';
require_once __DIR__ . '/../layouts/partials/flash-messages.php';

// Get token from URL
$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

$page_title = 'Restablecer Contraseña - Lucatón';
$page_description = 'Establece una nueva contraseña para tu cuenta de Lucatón.';
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
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/public/assets/images/favicon.svg">

    <!-- Styles -->
    <link href="<?= APP_URL ?>/public/assets/css/app.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/public/assets/css/aliases.css" rel="stylesheet">
    
    <?php if (!empty($siteToastQueue)): ?>
    <script>
        window.__SITE_TOASTS__ = <?= json_encode($siteToastQueue, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;
    </script>
    <?php endif; ?>
    
    <!-- Alpine.js -->
    <script defer src="<?= APP_URL ?>/public/assets/js/app.js?v=2025020503"></script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <!-- Skip to content link -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <a href="<?= Router::url('/') ?>" class="flex items-center">
                        <svg class="h-8 w-8 text-copihue-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                        <span class="ml-2 text-xl font-bold text-gray-900">Lucatón</span>
                    </a>
                </div>
                <div class="text-sm text-gray-600">
                    ¿Ya tienes cuenta? 
                    <a href="<?= Router::url('login') ?>" class="font-medium text-copihue-600 hover:text-copihue-500">
                        Inicia sesión
                    </a>
                </div>
            </div>
        </div>
    </header>

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
                    Restablecer Contraseña
                </h1>
                <p class="mt-2 text-sm text-gray-600">
                    Ingresa tu nueva contraseña para <?php echo htmlspecialchars($email); ?>
                </p>
            </div>

            <!-- Flash Messages -->
            <?php include_flash_messages(); ?>

            <!-- Reset Password Form -->
            <div class="bg-white py-8 px-6 shadow rounded-lg" x-data="resetPasswordForm()">
                <?php if (empty($token)): ?>
                    <!-- Invalid Token -->
                    <div class="text-center">
                        <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-red-100 mb-4">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            Enlace Inválido
                        </h3>
                        <p class="text-sm text-gray-600 mb-6">
                            El enlace de restablecimiento de contraseña es inválido o ha expirado.
                        </p>
                        <a href="<?= Router::url('auth/forgot-password') ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-copihue-600 hover:bg-copihue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500">
                            Solicitar nuevo enlace
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Reset Form -->
                    <form @submit.prevent="submitForm()" class="space-y-6" novalidate>
                        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">
                        <!-- Hidden Fields -->
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

                        <!-- New Password Field -->
                        <div>
                            <?php echo render_text_input([
                                'name' => 'password',
                                'label' => 'Nueva contraseña',
                                'type' => 'password',
                                'placeholder' => 'Ingresa tu nueva contraseña',
                                'required' => true,
                                'attributes' => [
                                    'x-model' => 'form.password',
                                    '@input' => 'validatePassword()',
                                    ':class' => 'errors.password ? "border-red-300 focus:border-red-500 focus:ring-red-500" : ""'
                                ]
                            ]); ?>
                            
                            <!-- Password Strength Indicator -->
                            <div x-show="form.password" class="mt-2">
                                <div class="flex items-center space-x-2">
                                    <div class="flex-1">
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div 
                                                class="h-2 rounded-full transition-all duration-300"
                                                :class="{
                                                    'bg-red-500 w-1/4': passwordStrength === 1,
                                                    'bg-orange-500 w-2/4': passwordStrength === 2,
                                                    'bg-yellow-500 w-3/4': passwordStrength === 3,
                                                    'bg-green-500 w-full': passwordStrength === 4
                                                }"
                                            ></div>
                                        </div>
                                    </div>
                                    <span 
                                        class="text-xs font-medium"
                                        :class="{
                                            'text-red-600': passwordStrength === 1,
                                            'text-orange-600': passwordStrength === 2,
                                            'text-yellow-600': passwordStrength === 3,
                                            'text-green-600': passwordStrength === 4
                                        }"
                                        x-text="passwordStrengthText"
                                    ></span>
                                </div>
                                
                                <!-- Password Requirements -->
                                <div class="mt-2 space-y-1">
                                    <div class="flex items-center text-xs" :class="requirements.length ? 'text-green-600' : 'text-gray-500'">
                                        <svg class="w-3 h-3 mr-1" :class="requirements.length ? 'text-green-500' : 'text-gray-400'" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        Al menos 8 caracteres
                                    </div>
                                    <div class="flex items-center text-xs" :class="requirements.uppercase ? 'text-green-600' : 'text-gray-500'">
                                        <svg class="w-3 h-3 mr-1" :class="requirements.uppercase ? 'text-green-500' : 'text-gray-400'" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        Una letra mayúscula
                                    </div>
                                    <div class="flex items-center text-xs" :class="requirements.lowercase ? 'text-green-600' : 'text-gray-500'">
                                        <svg class="w-3 h-3 mr-1" :class="requirements.lowercase ? 'text-green-500' : 'text-gray-400'" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        Una letra minúscula
                                    </div>
                                    <div class="flex items-center text-xs" :class="requirements.number ? 'text-green-600' : 'text-gray-500'">
                                        <svg class="w-3 h-3 mr-1" :class="requirements.number ? 'text-green-500' : 'text-gray-400'" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        Un número
                                    </div>
                                </div>
                            </div>
                            
                            <div x-show="errors.password" x-text="errors.password" class="mt-1 text-sm text-red-600"></div>
                        </div>

                        <!-- Confirm Password Field -->
                        <div>
                            <?php echo render_text_input([
                                'name' => 'password_confirmation',
                                'label' => 'Confirmar contraseña',
                                'type' => 'password',
                                'placeholder' => 'Confirma tu nueva contraseña',
                                'required' => true,
                                'attributes' => [
                                    'x-model' => 'form.password_confirmation',
                                    '@blur' => 'validatePasswordConfirmation()',
                                    ':class' => 'errors.password_confirmation ? "border-red-300 focus:border-red-500 focus:ring-red-500" : ""'
                                ]
                            ]); ?>
                            <div x-show="errors.password_confirmation" x-text="errors.password_confirmation" class="mt-1 text-sm text-red-600"></div>
                        </div>

                        <!-- Submit Button -->
                        <div>
                            <?php echo render_button([
                                'text' => 'Restablecer Contraseña',
                                'type' => 'submit',
                                'variant' => 'primary',
                                'size' => 'lg',
                                'full_width' => true,
                                'attributes' => [
                                    ':disabled' => 'loading || !isFormValid()',
                                    ':class' => 'loading ? "opacity-75 cursor-not-allowed" : ""'
                                ]
                            ]); ?>
                            <div x-show="loading" class="flex items-center justify-center mt-2">
                                <svg class="animate-spin h-5 w-5 text-copihue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="ml-2 text-sm text-gray-600">Restableciendo contraseña...</span>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>

                <!-- General Error -->
                <div x-show="errors.general" class="mt-4">
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
                <div>
                    <a href="<?= Router::url('login') ?>" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver al inicio de sesión
                    </a>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                            Seguridad de tu cuenta
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Tu nueva contraseña debe ser única y no utilizarla en otros sitios web. Te recomendamos usar un gestor de contraseñas para mayor seguridad.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="text-center text-sm text-gray-500">
                © <?php echo date('Y'); ?> Lucatón. Todos los derechos reservados.
            </div>
        </div>
    </footer>

    <script>
        function resetPasswordForm() {
            return {
                form: {
                    password: '',
                    password_confirmation: ''
                },
                errors: {},
                loading: false,
                passwordStrength: 0,
                passwordStrengthText: '',
                requirements: {
                    length: false,
                    uppercase: false,
                    lowercase: false,
                    number: false
                },

                validatePassword() {
                    const password = this.form.password;
                    
                    // Check requirements
                    this.requirements.length = password.length >= 8;
                    this.requirements.uppercase = /[A-Z]/.test(password);
                    this.requirements.lowercase = /[a-z]/.test(password);
                    this.requirements.number = /\d/.test(password);
                    
                    // Calculate strength
                    let strength = 0;
                    if (this.requirements.length) strength++;
                    if (this.requirements.uppercase) strength++;
                    if (this.requirements.lowercase) strength++;
                    if (this.requirements.number) strength++;
                    
                    this.passwordStrength = strength;
                    
                    // Set strength text
                    const strengthTexts = ['', 'Débil', 'Regular', 'Buena', 'Fuerte'];
                    this.passwordStrengthText = strengthTexts[strength];
                    
                    // Validate
                    if (!password) {
                        this.errors.password = 'La contraseña es requerida';
                        return false;
                    }
                    
                    if (password.length < 8) {
                        this.errors.password = 'La contraseña debe tener al menos 8 caracteres';
                        return false;
                    }
                    
                    if (strength < 3) {
                        this.errors.password = 'La contraseña debe cumplir al menos 3 de los requisitos';
                        return false;
                    }
                    
                    delete this.errors.password;
                    
                    // Re-validate confirmation if it exists
                    if (this.form.password_confirmation) {
                        this.validatePasswordConfirmation();
                    }
                    
                    return true;
                },

                validatePasswordConfirmation() {
                    const confirmation = this.form.password_confirmation;
                    
                    if (!confirmation) {
                        this.errors.password_confirmation = 'Confirma tu contraseña';
                        return false;
                    }
                    
                    if (confirmation !== this.form.password) {
                        this.errors.password_confirmation = 'Las contraseñas no coinciden';
                        return false;
                    }
                    
                    delete this.errors.password_confirmation;
                    return true;
                },

                isFormValid() {
                    return this.form.password && 
                           this.form.password_confirmation && 
                           this.passwordStrength >= 3 &&
                           Object.keys(this.errors).length === 0;
                },

                async submitForm() {
                    // Validate all fields
                    const passwordValid = this.validatePassword();
                    const confirmationValid = this.validatePasswordConfirmation();
                    
                    if (!passwordValid || !confirmationValid) {
                        return;
                    }
                    
                    this.loading = true;
                    delete this.errors.general;
                    
                    try {
                        const formData = new FormData();
                        formData.append('token', '<?php echo htmlspecialchars($token); ?>');
                        formData.append('email', '<?php echo htmlspecialchars($email); ?>');
                        formData.append('password', this.form.password);
                        formData.append('password_confirmation', this.form.password_confirmation);
                        
                        const response = await fetch('/auth/reset-password', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Redirect to login with success message
                            window.location.href = '/auth/login?reset=success';
                        } else {
                            // Handle validation errors
                            if (data.errors) {
                                this.errors = data.errors;
                            } else {
                                this.errors.general = data.message || 'Error al restablecer la contraseña';
                            }
                        }
                    } catch (error) {
                        console.error('Reset password error:', error);
                        this.errors.general = 'Error de conexión. Inténtalo de nuevo.';
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }

        // Auto-focus password input
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.querySelector('input[name="password"]');
            if (passwordInput) {
                passwordInput.focus();
            }
        });
    </script>
    <div class="site-toast-stack" data-site-toast-container></div>
</body>
</html>
