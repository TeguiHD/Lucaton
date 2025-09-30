<?php
if (!function_exists('include_flash_messages')) {
    function include_flash_messages() {
        $flash_types = [
            'success' => [
                'bg_color' => 'bg-green-50',
                'border_color' => 'border-green-200',
                'text_color' => 'text-green-800',
                'icon_color' => 'text-green-400',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />'
            ],
            'error' => [
                'bg_color' => 'bg-red-50',
                'border_color' => 'border-red-200',
                'text_color' => 'text-red-800',
                'icon_color' => 'text-red-400',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />'
            ],
            'warning' => [
                'bg_color' => 'bg-yellow-50',
                'border_color' => 'border-yellow-200',
                'text_color' => 'text-yellow-800',
                'icon_color' => 'text-yellow-400',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />'
            ],
            'info' => [
                'bg_color' => 'bg-blue-50',
                'border_color' => 'border-blue-200',
                'text_color' => 'text-blue-800',
                'icon_color' => 'text-blue-400',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'
            ]
        ];

        $normalized = [];
        $sessionFlash = $_SESSION['flash'] ?? [];

        foreach ($flash_types as $type => $config) {
            $messages = [];

            if (isset($sessionFlash[$type])) {
                $stored = $sessionFlash[$type];
                $messages = is_array($stored) ? $stored : [$stored];
            }

            if (isset($_SESSION["flash_{$type}"])) {
                $legacy = $_SESSION["flash_{$type}"];
                $legacyMessages = is_array($legacy) ? $legacy : [$legacy];
                $messages = array_merge($messages, $legacyMessages);
            }

            if (!empty($messages)) {
                $normalized[$type] = array_values(array_filter($messages, static function ($message) {
                    return $message !== null && $message !== '';
                }));
            }
        }

        if (empty($normalized)) {
            return;
        }

        echo '<div class="flash-messages space-y-4 mb-6">';
        foreach ($normalized as $type => $messages) {
            $config = $flash_types[$type];
            foreach ($messages as $message) {
                echo '<div class="' . $config['bg_color'] . ' ' . $config['border_color'] . ' border rounded-md p-4" role="alert">';
                echo '<div class="flex">';
                echo '<div class="flex-shrink-0"><svg class="h-5 w-5 ' . $config['icon_color'] . '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">' . $config['icon'] . '</svg></div>';
                echo '<div class="ml-3 flex-1"><p class="text-sm font-medium ' . $config['text_color'] . '">' . htmlspecialchars($message) . '</p></div>';
                echo '</div></div>';
            }
        }
        echo '</div>';

        foreach (array_keys($normalized) as $type) {
            unset($_SESSION['flash'][$type], $_SESSION["flash_{$type}"]);
        }

        if (isset($_SESSION['flash']) && empty($_SESSION['flash'])) {
            unset($_SESSION['flash']);
        }
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
