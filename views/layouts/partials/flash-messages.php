<?php
if (!function_exists('include_flash_messages')) {
    function include_flash_messages() {
        $toasts = SessionHelper::getLastSiteToasts();
        if (empty($toasts)) {
            return;
        }

        echo '<div class="sr-only" aria-live="assertive">';
        foreach ($toasts as $toast) {
            $typeLabel = strtoupper($toast['type'] ?? 'info');
            $message = (string)($toast['message'] ?? '');
            if ($message === '') {
                continue;
            }
            echo '<p>' . htmlspecialchars('[' . $typeLabel . '] ' . $message, ENT_QUOTES, 'UTF-8') . '</p>';
        }
        echo '</div>';
    }
}

// (already in PHP mode)
// Función helper para establecer mensajes flash (para uso en controladores)
if (!function_exists('set_flash_message')) {
    function set_flash_message($type, $message) {
        SessionHelper::setFlash($type, $message);
    }
}

// Funciones helper específicas para cada tipo
if (!function_exists('set_success_message')) {
    function set_success_message($message) {
        SessionHelper::setFlash('success', $message);
    }
}

if (!function_exists('set_error_message')) {
    function set_error_message($message) {
        SessionHelper::setFlash('error', $message);
    }
}

if (!function_exists('set_warning_message')) {
    function set_warning_message($message) {
        SessionHelper::setFlash('warning', $message);
    }
}

if (!function_exists('set_info_message')) {
    function set_info_message($message) {
        SessionHelper::setFlash('info', $message);
    }
}
?>
