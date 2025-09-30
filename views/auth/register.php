<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../components/buttons.php';
require_once __DIR__ . '/../components/forms.php';
require_once __DIR__ . '/../components/alerts.php';
require_once __DIR__ . '/../layouts/partials/flash-messages.php';
$current_page = 'register';

$serverErrors = $_SESSION['validation_errors']['register'] ?? [];
$oldInput = $_SESSION['old_input']['register'] ?? [];
if (isset($_SESSION['validation_errors']['register'])) {
    unset($_SESSION['validation_errors']['register']);
}
if (isset($_SESSION['old_input']['register'])) {
    unset($_SESSION['old_input']['register']);
}

$page_title = 'Crear Cuenta - Lucatón';
$page_description = 'Únete a Lucatón y comienza a crear campañas de crowdfunding o apoya proyectos increíbles.';
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
    <style>[x-cloak]{display:none !important;}</style>
    
    <script>
    window.__REGISTER_INITIAL_FORM__ = <?= json_encode($oldInput, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;
    window.__REGISTER_INITIAL_ERRORS__ = <?= json_encode($serverErrors, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;
</script>
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="<?= APP_URL ?>/public/assets/js/app.js?v=2025012801"></script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <!-- Skip to content link -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-copihue-600 text-white px-4 py-2 rounded-md z-50">
        Saltar al contenido principal
    </a>

    <!-- Header (reutilizable) -->
    <?php include __DIR__ . '/../layouts/partials/header.php'; ?>

    <!-- Main Content -->
    <main id="main-content" class="flex-1 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <h1 class="text-3xl font-extrabold text-gray-900">
                    Crear Cuenta
                </h1>
                <p class="mt-2 text-sm text-gray-600">
                    Únete a la comunidad de crowdfunding más grande de Chile
                </p>
            </div>

            <!-- Flash Messages -->
            <?php include_flash_messages(); ?>

            <!-- Registration Form -->
            <div class="bg-white py-8 px-6 shadow rounded-lg">
                <form 
                    id="register-form"
                    x-data="registerForm()" 
                    method="POST"
                    action="<?= Router::url('registro') ?>"
                    @submit.prevent="submitForm()"
                    class="space-y-6"
                    novalidate
                >
                    <?php $generalError = $serverErrors['general'] ?? null; ?>
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(SessionHelper::getCSRFToken()) ?>">
                    <div
                        x-show="errors.general"
                        <?= $generalError ? 'style="display:block;"' : 'x-cloak' ?>
                        x-text="errors.general"
                        class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                        role="alert"
                        aria-live="assertive"
                    >
                        <?php if ($generalError): ?>
                            <?= htmlspecialchars($generalError) ?>
                        <?php endif; ?>
                    </div>
                    <!-- Name Fields -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <?php echo render_text_input([
                                'name' => 'first_name',
                                'label' => 'Nombre',
                                'placeholder' => 'Juan',
                                'required' => true,
                                'value' => $oldInput['first_name'] ?? '',
                                'attributes' => [
                                    'x-model' => 'form.first_name',
                                    '@blur' => 'validateFirstName()',
                                    ':class' => 'errors.first_name ? "border-red-300 focus:border-red-500 focus:ring-red-500" : ""'
                                ]
                            ]); ?>
                            <?php $first_nameError = $serverErrors['first_name'] ?? null; ?>
                            <div
                                x-show="errors.first_name"
                                <?= $first_nameError ? 'style="display:block;"' : 'x-cloak' ?>
                                x-text="errors.first_name"
                                class="mt-1 text-sm text-red-600"
                            >
                                <?php if ($first_nameError): ?>
                                    <?= htmlspecialchars($first_nameError) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <?php echo render_text_input([
                                'name' => 'last_name',
                                'label' => 'Apellido',
                                'placeholder' => 'Pérez',
                                'required' => true,
                                'value' => $oldInput['last_name'] ?? '',
                                'attributes' => [
                                    'x-model' => 'form.last_name',
                                    '@blur' => 'validateLastName()',
                                    ':class' => 'errors.last_name ? "border-red-300 focus:border-red-500 focus:ring-red-500" : ""'
                                ]
                            ]); ?>
                            <?php $last_nameError = $serverErrors['last_name'] ?? null; ?>
                            <div
                                x-show="errors.last_name"
                                <?= $last_nameError ? 'style="display:block;"' : 'x-cloak' ?>
                                x-text="errors.last_name"
                                class="mt-1 text-sm text-red-600"
                            >
                                <?php if ($last_nameError): ?>
                                    <?= htmlspecialchars($last_nameError) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Email Field -->
                    <div>
                        <?php echo render_text_input([
                            'name' => 'email',
                            'label' => 'Correo electrónico',
                            'type' => 'email',
                            'placeholder' => 'tu@email.com',
                            'required' => true,
                            'value' => $oldInput['email'] ?? '',
                            'attributes' => [
                                'x-model' => 'form.email',
                                '@blur' => 'validateEmail()',
                                ':class' => 'errors.email ? "border-red-300 focus:border-red-500 focus:ring-red-500" : ""'
                            ]
                        ]); ?>
                        <?php $emailError = $serverErrors['email'] ?? null; ?>
                        <div
                            x-show="errors.email"
                            <?= $emailError ? 'style="display:block;"' : 'x-cloak' ?>
                            x-text="errors.email"
                            class="mt-1 text-sm text-red-600"
                        >
                            <?php if ($emailError): ?>
                                <?= htmlspecialchars($emailError) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Password Fields -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            Contraseña <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                id="password"
                                name="password"
                                placeholder="••••••••"
                                required
                                autocomplete="new-password"
                                class="form-input block w-full rounded-md border-gray-300 shadow-sm focus:border-copihue-500 focus:ring-copihue-500 sm:text-sm pr-12"
                                :class="errors.password ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : ''"
                                :type="showPassword ? 'text' : 'password'"
                                x-model="form.password"
                                @input="validatePassword()"
                                @blur="validatePassword()"
                            >
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 z-10">
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                    :title="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                    :aria-pressed="showPassword"
                                    class="inline-flex items-center justify-center rounded-md p-1 text-gray-500 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-copihue-500 bg-white bg-opacity-75"
                                >
                                    <svg x-show="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <?php $passwordError = $serverErrors['password'] ?? null; ?>
                        <div
                            x-show="errors.password"
                            <?= $passwordError ? 'style="display:block;"' : 'x-cloak' ?>
                            x-text="errors.password"
                            class="mt-1 text-sm text-red-600"
                        >
                            <?php if ($passwordError): ?>
                                <?= htmlspecialchars($passwordError) ?>
                            <?php endif; ?>
                        </div>

                        <!-- Password Requirements -->
                        <div x-show="form.password" x-cloak class="mt-2 space-y-1 text-xs">
                            <div class="font-medium text-gray-600">Requisitos de la contraseña:</div>
                            <div class="flex items-center" :class="passwordCriteria.length ? 'text-green-600' : 'text-gray-500'">
                                <svg x-show="passwordCriteria.length" x-cloak class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <svg x-show="!passwordCriteria.length" x-cloak class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6" />
                                </svg>
                                <span>Al menos 6 caracteres</span>
                            </div>
                            <div class="flex items-center" :class="passwordCriteria.uppercase ? 'text-green-600' : 'text-gray-500'">
                                <svg x-show="passwordCriteria.uppercase" x-cloak class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <svg x-show="!passwordCriteria.uppercase" x-cloak class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6" />
                                </svg>
                                <span>Al menos una letra mayúscula</span>
                            </div>
                            <div class="flex items-center" :class="passwordCriteria.number ? 'text-green-600' : 'text-gray-500'">
                                <svg x-show="passwordCriteria.number" x-cloak class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <svg x-show="!passwordCriteria.number" x-cloak class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6" />
                                </svg>
                                <span>Al menos un número</span>
                            </div>
                            <div class="flex items-center" :class="passwordCriteria.special ? 'text-green-600' : 'text-gray-500'">
                                <svg x-show="passwordCriteria.special" x-cloak class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <svg x-show="!passwordCriteria.special" x-cloak class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6" />
                                </svg>
                                <span>Al menos un carácter especial</span>
                            </div>
                            <div class="mt-1" :class="passwordStrength === 4 ? 'text-green-600' : 'text-gray-500'">
                                <span x-text="passwordStrength === 4 ? 'Requisitos completos.' : 'Completa los requisitos pendientes.'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                            Confirmar contraseña <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                placeholder="••••••••"
                                required
                                autocomplete="new-password"
                                class="form-input block w-full rounded-md border-gray-300 shadow-sm focus:border-copihue-500 focus:ring-copihue-500 sm:text-sm pr-12"
                                :class="errors.password_confirmation ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : ''"
                                :type="showPasswordConfirm ? 'text' : 'password'"
                                x-model="form.password_confirmation"
                                @blur="validatePasswordConfirmation()"
                            >
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 z-10">
                                <button
                                    type="button"
                                    @click="showPasswordConfirm = !showPasswordConfirm"
                                    :aria-label="showPasswordConfirm ? 'Ocultar confirmación de contraseña' : 'Mostrar confirmación de contraseña'"
                                    :title="showPasswordConfirm ? 'Ocultar confirmación de contraseña' : 'Mostrar confirmación de contraseña'"
                                    :aria-pressed="showPasswordConfirm"
                                    class="inline-flex items-center justify-center rounded-md p-1 text-gray-500 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-copihue-500 bg-white bg-opacity-75"
                                >
                                    <svg x-show="!showPasswordConfirm" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showPasswordConfirm" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <?php $password_confirmationError = $serverErrors['password_confirmation'] ?? null; ?>
                        <div
                            x-show="errors.password_confirmation"
                            <?= $password_confirmationError ? 'style="display:block;"' : 'x-cloak' ?>
                            x-text="errors.password_confirmation"
                            class="mt-1 text-sm text-red-600"
                        >
                            <?php if ($password_confirmationError): ?>
                                <?= htmlspecialchars($password_confirmationError) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Terms and Privacy -->
                    <div>
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input
                                    id="terms"
                                    name="terms"
                                    type="checkbox"
                                    value="1"
                                    x-model="form.terms"
                                    @change="validateTerms()"
                                    class="focus:ring-copihue-500 h-4 w-4 text-copihue-600 border-gray-300 rounded"
                                    :class="errors.terms ? 'border-red-300' : ''"
                                    <?= (($oldInput['terms'] ?? '') === '1') ? 'checked' : '' ?>
                                >
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="terms" class="text-gray-700">
                                    Acepto los 
                                    <a href="<?= Router::url('terminos') ?>" target="_blank" class="text-copihue-600 hover:text-copihue-500 underline">
                                        Términos y Condiciones
                                    </a>
                                    y la 
                                    <a href="<?= Router::url('privacidad') ?>" target="_blank" class="text-copihue-600 hover:text-copihue-500 underline">
                                        Política de Privacidad
                                    </a>
                                </label>
                            </div>
                        </div>
                        <?php $termsError = $serverErrors['terms'] ?? null; ?>
                        <div
                            x-show="errors.terms"
                            <?= $termsError ? 'style="display:block;"' : 'x-cloak' ?>
                            x-text="errors.terms"
                            class="mt-1 text-sm text-red-600"
                        >
                            <?php if ($termsError): ?>
                                <?= htmlspecialchars($termsError) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Marketing Consent -->
                    <div>
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input
                                    id="marketing"
                                    name="marketing"
                                    type="checkbox"
                                    value="1"
                                    x-model="form.marketing"
                                    class="focus:ring-copihue-500 h-4 w-4 text-copihue-600 border-gray-300 rounded"
                                    <?= (($oldInput['marketing'] ?? '') === '1') ? 'checked' : '' ?>
                                >
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="marketing" class="text-gray-700">
                                    Quiero recibir noticias, actualizaciones y ofertas especiales por email
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <?php echo render_button([
                            'text' => 'Crear Cuenta',
                            'variant' => 'primary',
                            'size' => 'lg',
                            'full_width' => true,
                            'form_type' => 'submit',
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
                            <span class="ml-2 text-sm text-gray-600">Creando cuenta...</span>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">O regístrate con</span>
                        </div>
                    </div>

                    <!-- Social Registration Buttons -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="relative">
                            <button
                                type="button"
                                class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-400 opacity-60 cursor-not-allowed"
                                disabled
                                aria-disabled="true"
                                aria-describedby="oauth-google-helper"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 24 24">
                                    <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                                <span class="ml-2">Google</span>
                            </button>
                            <span id="oauth-google-helper" class="sr-only">Registro con Google no está disponible todavía.</span>
                            <div
                                class="absolute inset-0 rounded-md cursor-not-allowed"
                                @click.prevent="showProviderUnavailable('Google')"
                                title="Integración disponible próximamente"
                                aria-hidden="true"
                            ></div>
                        </div>

                        <div class="relative">
                            <button
                                type="button"
                                class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-400 opacity-60 cursor-not-allowed"
                                disabled
                                aria-disabled="true"
                                aria-describedby="oauth-facebook-helper"
                            >
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                                <span class="ml-2">Facebook</span>
                            </button>
                            <span id="oauth-facebook-helper" class="sr-only">Registro con Facebook no está disponible todavía.</span>
                            <div
                                class="absolute inset-0 rounded-md cursor-not-allowed"
                                @click.prevent="showProviderUnavailable('Facebook')"
                                title="Integración disponible próximamente"
                                aria-hidden="true"
                            ></div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Additional Links -->
            <div class="text-center">
                <p class="text-sm text-gray-600">
                    ¿Ya tienes una cuenta?
                    <a href="<?= Router::url('login') ?>" class="font-medium text-copihue-600 hover:text-copihue-500">
                        Inicia sesión
                    </a>
                </p>
            </div>

            <!-- Back to Home -->
            <div class="text-center">
                <a href="<?= Router::url('/') ?>" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver al inicio
                </a>
            </div>
        </div>
    </main>

    <!-- Footer (reutilizable) -->
    <?php include __DIR__ . '/../layouts/partials/footer.php'; ?>

    <script>
        function registerForm() {
            const initialForm = window.__REGISTER_INITIAL_FORM__ || {};
            const initialErrors = window.__REGISTER_INITIAL_ERRORS__ || {};

            return {
                form: {
                    first_name: initialForm.first_name ?? '',
                    last_name: initialForm.last_name ?? '',
                    email: initialForm.email ?? '',
                    password: '',
                    password_confirmation: '',
                    terms: initialForm.terms === '1' || initialForm.terms === true,
                    marketing: initialForm.marketing === '1' || initialForm.marketing === true
                },
                errors: initialErrors,
                loading: false,
                showPassword: false,
                showPasswordConfirm: false,

                get passwordCriteria() {
                    const password = this.form.password || '';
                    return {
                        length: password.length >= 6,
                        uppercase: /[A-Z]/.test(password),
                        number: /[0-9]/.test(password),
                        special: /[^A-Za-z0-9]/.test(password)
                    };
                },

                get passwordStrength() {
                    return Object.values(this.passwordCriteria).filter(Boolean).length;
                },

                validateFirstName() {
                    const name = this.form.first_name.trim();
                    
                    if (!name) {
                        this.errors.first_name = 'El nombre es requerido';
                        return false;
                    }
                    
                    if (name.length < 2) {
                        this.errors.first_name = 'El nombre debe tener al menos 2 caracteres';
                        return false;
                    }
                    
                    delete this.errors.first_name;
                    return true;
                },

                validateLastName() {
                    const name = this.form.last_name.trim();
                    
                    if (!name) {
                        this.errors.last_name = 'El apellido es requerido';
                        return false;
                    }
                    
                    if (name.length < 2) {
                        this.errors.last_name = 'El apellido debe tener al menos 2 caracteres';
                        return false;
                    }
                    
                    delete this.errors.last_name;
                    return true;
                },

                validateEmail() {
                    const email = this.form.email.trim();
                    
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
                    const password = this.form.password;
                    
                    if (!password) {
                        this.errors.password = 'La contraseña es requerida';
                        return false;
                    }

                    const criteria = this.passwordCriteria;

                    if (!criteria.length || !criteria.uppercase || !criteria.number || !criteria.special) {
                        this.errors.password = 'La contraseña debe tener al menos 6 caracteres, incluir una mayúscula, un número y un carácter especial';
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

                validateTerms() {
                    if (!this.form.terms) {
                        this.errors.terms = 'Debes aceptar los términos y condiciones';
                        return false;
                    }
                    
                    delete this.errors.terms;
                    return true;
                },

                clearGeneralError() {
                    if (this.errors.general) {
                        delete this.errors.general;
                    }
                },

                showProviderUnavailable(provider) {
                    this.errors.general = `La opción de registro con ${provider} estará disponible próximamente.`;
                },

                isFormValid() {
                    return this.form.first_name && 
                           this.form.last_name && 
                           this.form.email && 
                           this.form.password && 
                           this.form.password_confirmation && 
                           this.form.terms &&
                           Object.keys(this.errors).length === 0;
                },

                async submitForm() {
                    this.clearGeneralError();
                    // Validate all fields
                    const firstNameValid = this.validateFirstName();
                    const lastNameValid = this.validateLastName();
                    const emailValid = this.validateEmail();
                    const passwordValid = this.validatePassword();
                    const passwordConfirmValid = this.validatePasswordConfirmation();
                    const termsValid = this.validateTerms();
                    
                    if (!firstNameValid || !lastNameValid || !emailValid || !passwordValid || !passwordConfirmValid || !termsValid) {
                        return;
                    }
                    
                    this.loading = true;
                    
                    try {
                        const formElement = document.getElementById('register-form');
                        const formData = new FormData(formElement);
                        formData.set('terms', this.form.terms ? '1' : '0');
                        formData.set('marketing', this.form.marketing ? '1' : '0');
                        
                        const endpoint = formElement.getAttribute('action');
                        const response = await fetch(endpoint, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Redirect to panel o ruta recibida
                            window.location.href = data.redirect || '<?= Router::url("panel") ?>';
                        } else {
                            // Handle validation errors
                            if (data.errors) {
                                this.errors = data.errors;
                            } else {
                                this.errors.general = data.message || 'Error al crear la cuenta';
                            }
                        }
                    } catch (error) {
                        console.error('Registration error:', error);
                        this.errors.general = 'Error de conexión. Inténtalo de nuevo.';
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }

        // Auto-focus first input
        document.addEventListener('DOMContentLoaded', function() {
            const firstInput = document.querySelector('input[name="first_name"]');
            if (firstInput) {
                firstInput.focus();
            }
        });
    </script>
</body>
</html>
