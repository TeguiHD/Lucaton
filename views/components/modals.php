<?php
/**
 * Componentes de modales reutilizables con Alpine.js
 */

/**
 * Modal base reutilizable
 */
function render_modal($options = []) {
    $defaults = [
        'id' => 'modal_' . uniqid(),
        'title' => '',
        'content' => '',
        'size' => 'md', // sm, md, lg, xl, full
        'closable' => true,
        'backdrop_close' => true,
        'show_header' => true,
        'show_footer' => false,
        'footer_content' => '',
        'modal_class' => '',
        'alpine_data' => 'modalOpen: false'
    ];
    
    $options = array_merge($defaults, $options);
    
    // Clases de tamaño
    $size_classes = [
        'sm' => 'max-w-md',
        'md' => 'max-w-lg',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
        'full' => 'max-w-full mx-4'
    ];
    
    $size_class = $size_classes[$options['size']] ?? $size_classes['md'];
    
    $html = '<div x-data="{ ' . $options['alpine_data'] . ' }" 
                  x-show="modalOpen" 
                  x-cloak
                  class="fixed inset-0 z-50 overflow-y-auto" 
                  aria-labelledby="' . $options['id'] . '-title" 
                  role="dialog" 
                  aria-modal="true">';
    
    // Backdrop
    $html .= '<div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">';
    
    // Overlay
    $html .= '<div x-show="modalOpen" 
                   x-transition:enter="ease-out duration-300"
                   x-transition:enter-start="opacity-0"
                   x-transition:enter-end="opacity-100"
                   x-transition:leave="ease-in duration-200"
                   x-transition:leave-start="opacity-100"
                   x-transition:leave-end="opacity-0"
                   class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                   aria-hidden="true"';
    
    if ($options['backdrop_close']) {
        $html .= ' @click="modalOpen = false"';
    }
    
    $html .= '></div>';
    
    // Spacer para centrar el modal
    $html .= '<span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>';
    
    // Modal panel
    $html .= '<div x-show="modalOpen" 
                   x-transition:enter="ease-out duration-300"
                   x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                   x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                   x-transition:leave="ease-in duration-200"
                   x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                   x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                   class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle ' . $size_class . ' sm:w-full ' . $options['modal_class'] . '">';
    
    // Header
    if ($options['show_header']) {
        $html .= '<div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">';
        $html .= '<div class="flex items-start justify-between">';
        
        if ($options['title']) {
            $html .= '<h3 class="text-lg leading-6 font-medium text-gray-900" id="' . $options['id'] . '-title">';
            $html .= htmlspecialchars($options['title']);
            $html .= '</h3>';
        }
        
        if ($options['closable']) {
            $html .= '<button type="button" 
                               class="bg-white rounded-md text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500" 
                               @click="modalOpen = false"
                               aria-label="Cerrar modal">';
            $html .= '<span class="sr-only">Cerrar</span>';
            $html .= '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">';
            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />';
            $html .= '</svg>';
            $html .= '</button>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
    }
    
    // Content
    $html .= '<div class="bg-white px-4 pt-5 pb-4 sm:p-6">';
    $html .= $options['content'];
    $html .= '</div>';
    
    // Footer
    if ($options['show_footer']) {
        $html .= '<div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">';
        $html .= $options['footer_content'];
        $html .= '</div>';
    }
    
    $html .= '</div>'; // Cierre del panel
    $html .= '</div>'; // Cierre del contenedor
    $html .= '</div>'; // Cierre del modal
    
    return $html;
}

/**
 * Modal de confirmación
 */
function render_confirmation_modal($options = []) {
    $defaults = [
        'id' => 'confirm_modal_' . uniqid(),
        'title' => 'Confirmar Acción',
        'message' => '¿Estás seguro de que deseas continuar?',
        'confirm_text' => 'Confirmar',
        'cancel_text' => 'Cancelar',
        'confirm_action' => '',
        'danger' => false,
        'alpine_data' => 'confirmModalOpen: false'
    ];
    
    $options = array_merge($defaults, $options);
    
    $confirm_class = $options['danger'] ? 'btn-danger' : 'btn-primary';
    
    $content = '<div class="sm:flex sm:items-start">';
    
    // Icono
    $icon_class = $options['danger'] ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-600';
    $icon_path = $options['danger'] ? 
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />' :
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />';
    
    $content .= '<div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full ' . $icon_class . ' sm:mx-0 sm:h-10 sm:w-10">';
    $content .= '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">';
    $content .= $icon_path;
    $content .= '</svg>';
    $content .= '</div>';
    
    // Mensaje
    $content .= '<div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">';
    $content .= '<p class="text-sm text-gray-500">';
    $content .= htmlspecialchars($options['message']);
    $content .= '</p>';
    $content .= '</div>';
    
    $content .= '</div>';
    
    // Footer con botones
    $footer = '<button type="button" 
                       class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 ' . $confirm_class . ' text-base font-medium text-white sm:ml-3 sm:w-auto sm:text-sm" 
                       @click="' . $options['confirm_action'] . '; confirmModalOpen = false">';
    $footer .= htmlspecialchars($options['confirm_text']);
    $footer .= '</button>';
    
    $footer .= '<button type="button" 
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" 
                        @click="confirmModalOpen = false">';
    $footer .= htmlspecialchars($options['cancel_text']);
    $footer .= '</button>';
    
    return render_modal([
        'id' => $options['id'],
        'title' => $options['title'],
        'content' => $content,
        'show_footer' => true,
        'footer_content' => $footer,
        'size' => 'sm',
        'alpine_data' => $options['alpine_data']
    ]);
}

/**
 * Modal de formulario
 */
function render_form_modal($options = []) {
    $defaults = [
        'id' => 'form_modal_' . uniqid(),
        'title' => 'Formulario',
        'form_content' => '',
        'form_action' => '',
        'form_method' => 'POST',
        'submit_text' => 'Guardar',
        'cancel_text' => 'Cancelar',
        'alpine_data' => 'formModalOpen: false, loading: false'
    ];
    
    $options = array_merge($defaults, $options);
    
    $content = '<form action="' . htmlspecialchars($options['form_action']) . '" method="' . htmlspecialchars($options['form_method']) . '" @submit="loading = true">';
    $content .= '<input type="hidden" name="' . htmlspecialchars(CSRF_TOKEN_NAME) . '" value="' . htmlspecialchars(SessionHelper::getCSRFToken()) . '">';
    $content .= $options['form_content'];
    $content .= '</form>';
    
    $footer = '<button type="submit" 
                       form="' . $options['id'] . '_form"
                       class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 btn-primary text-base font-medium sm:ml-3 sm:w-auto sm:text-sm" 
                       :disabled="loading"
                       x-text="loading ? \'Guardando...\' : \'' . htmlspecialchars($options['submit_text']) . '\'">';
    $footer .= '</button>';
    
    $footer .= '<button type="button" 
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-copihue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" 
                        @click="formModalOpen = false; loading = false">';
    $footer .= htmlspecialchars($options['cancel_text']);
    $footer .= '</button>';
    
    return render_modal([
        'id' => $options['id'],
        'title' => $options['title'],
        'content' => $content,
        'show_footer' => true,
        'footer_content' => $footer,
        'alpine_data' => $options['alpine_data']
    ]);
}

/**
 * Modal de imagen/galería
 */
function render_image_modal($options = []) {
    $defaults = [
        'id' => 'image_modal_' . uniqid(),
        'title' => '',
        'image_url' => '',
        'image_alt' => '',
        'description' => '',
        'alpine_data' => 'imageModalOpen: false, currentImage: \'\''
    ];
    
    $options = array_merge($defaults, $options);
    
    $content = '<div class="text-center">';
    $content .= '<img x-bind:src="currentImage" 
                      x-bind:alt="' . htmlspecialchars($options['image_alt']) . '"
                      class="max-w-full max-h-96 mx-auto rounded-lg shadow-lg">';
    
    if ($options['description']) {
        $content .= '<p class="mt-4 text-sm text-gray-600">';
        $content .= htmlspecialchars($options['description']);
        $content .= '</p>';
    }
    
    $content .= '</div>';
    
    return render_modal([
        'id' => $options['id'],
        'title' => $options['title'],
        'content' => $content,
        'size' => 'lg',
        'alpine_data' => $options['alpine_data']
    ]);
}

/**
 * Modal de notificación/alerta
 */
function render_alert_modal($options = []) {
    $defaults = [
        'id' => 'alert_modal_' . uniqid(),
        'title' => 'Notificación',
        'message' => '',
        'type' => 'info', // success, error, warning, info
        'button_text' => 'Entendido',
        'alpine_data' => 'alertModalOpen: false'
    ];
    
    $options = array_merge($defaults, $options);
    
    // Configuración por tipo
    $type_config = [
        'success' => [
            'icon_class' => 'bg-green-100 text-green-600',
            'icon_path' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />'
        ],
        'error' => [
            'icon_class' => 'bg-red-100 text-red-600',
            'icon_path' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />'
        ],
        'warning' => [
            'icon_class' => 'bg-yellow-100 text-yellow-600',
            'icon_path' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />'
        ],
        'info' => [
            'icon_class' => 'bg-blue-100 text-blue-600',
            'icon_path' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'
        ]
    ];
    
    $config = $type_config[$options['type']] ?? $type_config['info'];
    
    $content = '<div class="sm:flex sm:items-start">';
    
    // Icono
    $content .= '<div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full ' . $config['icon_class'] . ' sm:mx-0 sm:h-10 sm:w-10">';
    $content .= '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">';
    $content .= $config['icon_path'];
    $content .= '</svg>';
    $content .= '</div>';
    
    // Mensaje
    $content .= '<div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">';
    $content .= '<p class="text-sm text-gray-500">';
    $content .= htmlspecialchars($options['message']);
    $content .= '</p>';
    $content .= '</div>';
    
    $content .= '</div>';
    
    // Footer con botón
    $footer = '<button type="button" 
                       class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 btn-primary text-base font-medium sm:w-auto sm:text-sm" 
                       @click="alertModalOpen = false">';
    $footer .= htmlspecialchars($options['button_text']);
    $footer .= '</button>';
    
    return render_modal([
        'id' => $options['id'],
        'title' => $options['title'],
        'content' => $content,
        'show_footer' => true,
        'footer_content' => $footer,
        'size' => 'sm',
        'alpine_data' => $options['alpine_data']
    ]);
}
?>
